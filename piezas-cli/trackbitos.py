#!/usr/bin/env python3
"""
Cliente de Piezas (Trackbitos) — versionado de modelos 3D.

Fase 5: además de diagnosticar ("estado"), el cliente ya mueve ficheros —
bajar/ver/subir/cerrar/promocionar contra la API real, con el hash
verificado en los dos extremos.

El script razona, no el usuario (spec 5.2): cada comando termina con un
veredicto en lenguaje natural y, si hay algo que hacer, el comando exacto.
Nunca se imprime un hash crudo para que lo compares tú.

Comportamiento defensivo (spec 5.3): "bajar" se niega si hay trabajo sin
subir, ningún borrado es directo (todo pasa por ~/.trackbitos/papelera/), y
antes de escribir nada se comprueba que lo descargado es lo que el servidor
dice que es.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import mimetypes
import platform
import shutil
import socket
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid as uuidlib
from datetime import datetime
from pathlib import Path
from typing import Optional

SENTINEL_NAME = ".sesion.json"
CONFIG_DIR = Path.home() / ".trackbitos"
CONFIG_PATH = CONFIG_DIR / "config.json"
PAPELERA_DIR = CONFIG_DIR / "papelera"
DIAS_PAPELERA = 30

# Se sube junto al resto de este fichero: cada mejora que se despliegue en el
# servidor viene con la versión que le corresponde. "trackbitos actualizar"
# la compara con la que sirve el servidor (GET /cliente/version, leída del
# propio trackbitos.py desplegado allí) para saber si hay algo nuevo.
VERSION = "1.7.1"

# Espejo de PiezaService::VARIANTE_BASE en el servidor: el nombre que se le
# pone sola a la primera variante de cada pieza. Se usa solo para no
# repetirlo en los listados (_listado_agrupado) — si el servidor cambiara
# ese nombre, lo peor que pasa aquí es que deje de ocultarse, no que rompa
# nada.
PIEZA_VARIANTE_BASE = "base"


# --------------------------------------------------------------------------
# Ficheros locales
# --------------------------------------------------------------------------

def sha256_de(ruta: Path) -> str:
    h = hashlib.sha256()
    with ruta.open("rb") as f:
        for bloque in iter(lambda: f.read(1 << 20), b""):
            h.update(bloque)
    return h.hexdigest()


# Los JSON que lee este script (config.json a mano, .sesion.json propio) se
# leen con utf-8-sig, no utf-8: en Windows casi cualquier forma cómoda de
# crear un fichero de texto (Bloc de notas, PowerShell 5.1 con -Encoding
# utf8) le mete un BOM delante, y json.loads revienta con él. utf-8-sig se
# come el BOM si está y no estorba si no está. Al escribir se usa utf-8 a
# secas: el BOM se tolera de entrada, no se propaga.
ENCODING_LECTURA = "utf-8-sig"


def encontrar_blend(directorio: Path) -> Optional[Path]:
    """
    El .sesion.json no dice el nombre del fichero: se asume que hay un
    único .blend en el directorio de trabajo (así lo deja "bajar"). Si hay
    más de uno o ninguno, es estado ambiguo/corrupto — no se adivina.
    """
    candidatos = sorted(p for p in directorio.glob("*.blend") if p.is_file())
    return candidatos[0] if len(candidatos) == 1 else None


def cargar_json(ruta: Path) -> Optional[dict]:
    if not ruta.is_file():
        return None
    try:
        return json.loads(ruta.read_text(encoding=ENCODING_LECTURA))
    except (json.JSONDecodeError, OSError):
        return None


def escribir_sentinel(directorio: Path, datos: dict) -> None:
    (directorio / SENTINEL_NAME).write_text(
        json.dumps(datos, indent=2, ensure_ascii=False), encoding="utf-8"
    )


def actualizar_sentinel(directorio: Path, cambios: dict) -> dict:
    sentinel = cargar_json(directorio / SENTINEL_NAME) or {}
    sentinel.update(cambios)
    escribir_sentinel(directorio, sentinel)
    return sentinel


def a_papelera(ruta: Path) -> Path:
    """
    Nada se borra (invariante 6): se aparta con marca de tiempo. Un .blend
    ocupa 350 KB; recuperarlo cuando te has equivocado no tiene precio.
    """
    PAPELERA_DIR.mkdir(parents=True, exist_ok=True)
    destino = PAPELERA_DIR / f"{datetime.now():%Y%m%d-%H%M%S}-{ruta.name}"
    shutil.move(str(ruta), str(destino))
    return destino


def purgar_papelera(dias: int = DIAS_PAPELERA) -> list:
    """
    Lo apartado caduca a los 30 días (invariante 6). Se hace aquí, en cada
    ejecución, en vez de con una tarea programada: son dos máquinas de
    escritorio que se encienden a ratos, y un cron en cada una es una pieza
    más que mantener para algo que cuesta un listado de directorio.
    """
    if not PAPELERA_DIR.is_dir():
        return []

    limite = datetime.now().timestamp() - (dias * 86400)
    borrados = []

    for fichero in PAPELERA_DIR.iterdir():
        try:
            if fichero.is_file() and fichero.stat().st_mtime < limite:
                fichero.unlink()
                borrados.append(fichero.name)
        except OSError:
            # Un fichero bloqueado o sin permisos no debe tumbar el comando
            # que el usuario venía a ejecutar: ya caducará en la siguiente.
            pass

    return borrados


# --------------------------------------------------------------------------
# Configuración y API
# --------------------------------------------------------------------------

def cargar_config() -> dict:
    """
    url_base y token no se pueden adivinar (no hay servidor "por defecto").
    Lanza RuntimeError con instrucciones si falta el fichero, en vez de
    sys.exit: comandos como "estado" quieren poder seguir mostrando la
    comparación local-vs-origen (puramente local) aunque no haya config
    todavía, tratándolo igual que un fallo de red. El uuid de máquina se
    genera solo, la primera vez (spec 4.5: alta automática).
    """
    if not CONFIG_PATH.is_file():
        raise RuntimeError(
            f"falta {CONFIG_PATH}. Créalo así (ajusta url_base a tu servidor):\n"
            '    {"url_base": "http://localhost/trackbitos/public/piezas/api", '
            '"token": "<piezas.apiToken del .env del servidor>"}'
        )

    config = json.loads(CONFIG_PATH.read_text(encoding=ENCODING_LECTURA))
    if not config.get("uuid"):
        config["uuid"] = str(uuidlib.uuid4())
        guardar_config(config)

    return config


def guardar_config(config: dict) -> None:
    CONFIG_DIR.mkdir(parents=True, exist_ok=True)
    CONFIG_PATH.write_text(json.dumps(config, indent=2), encoding="utf-8")


def _cabeceras(config: dict) -> dict:
    return {
        "Authorization": f"Bearer {config['token']}",
        "Accept": "application/json",
        # La identidad de máquina va aparte del token: el token dice quién
        # eres, el uuid dice desde qué disco (spec 4.5).
        "X-Maquina-Uuid": config["uuid"],
    }


def _abrir(peticion: urllib.request.Request, config: dict):
    try:
        return urllib.request.urlopen(peticion, timeout=60)
    except urllib.error.HTTPError as e:
        cuerpo = e.read().decode("utf-8", errors="replace")
        # El cuerpo de un error no siempre es el {"error": "..."} de la API:
        # un hosting puede colar su propia página, y json.loads() de eso
        # devuelve texto suelto (o revienta) en vez de un diccionario. Sin
        # este cuidado, el fallo al leer el error sustituía al error mismo y
        # dejaba al usuario sin saber qué respondió el servidor.
        try:
            datos = json.loads(cuerpo)
        except json.JSONDecodeError:
            datos = None
        mensaje = datos.get("error") if isinstance(datos, dict) else None
        mensaje = mensaje or cuerpo.strip() or e.reason
        # El código HTTP es la mitad del diagnóstico (401 token, 404 ruta o
        # máquina, 409 asiento que no cuadra) y se estaba perdiendo.
        raise RuntimeError(f"HTTP {e.code} desde {peticion.full_url}\n    {mensaje}") from e
    except urllib.error.URLError as e:
        raise RuntimeError(f"no se pudo conectar con {config.get('url_base')}: {e.reason}") from e


def api_get(config: dict, ruta: str) -> dict:
    peticion = urllib.request.Request(config["url_base"].rstrip("/") + ruta, headers=_cabeceras(config))
    with _abrir(peticion, config) as resp:
        return json.loads(resp.read().decode("utf-8"))


def api_post(config: dict, ruta: str, cuerpo: Optional[dict] = None) -> dict:
    datos = json.dumps(cuerpo or {}).encode("utf-8")
    peticion = urllib.request.Request(
        config["url_base"].rstrip("/") + ruta,
        data=datos,
        headers={**_cabeceras(config), "Content-Type": "application/json"},
        method="POST",
    )
    with _abrir(peticion, config) as resp:
        return json.loads(resp.read().decode("utf-8"))


def api_post_fichero(config: dict, ruta: str, campos: dict, fichero: Path) -> dict:
    """
    multipart/form-data a mano: el cliente no debe depender de nada que no
    traiga Python de serie (spec 5: dependencias mínimas, tiene que arrancar
    en las dos máquinas sin instalar nada).
    """
    frontera = uuidlib.uuid4().hex
    lineas: list[bytes] = []

    for clave, valor in campos.items():
        if valor is None:
            continue
        lineas += [
            f"--{frontera}".encode(),
            f'Content-Disposition: form-data; name="{clave}"'.encode(),
            b"",
            str(valor).encode("utf-8"),
        ]

    tipo = mimetypes.guess_type(fichero.name)[0] or "application/octet-stream"
    lineas += [
        f"--{frontera}".encode(),
        f'Content-Disposition: form-data; name="blend"; filename="{fichero.name}"'.encode(),
        f"Content-Type: {tipo}".encode(),
        b"",
    ]

    cuerpo = b"\r\n".join(lineas) + b"\r\n" + fichero.read_bytes() + f"\r\n--{frontera}--\r\n".encode()

    peticion = urllib.request.Request(
        config["url_base"].rstrip("/") + ruta,
        data=cuerpo,
        headers={**_cabeceras(config), "Content-Type": f"multipart/form-data; boundary={frontera}"},
        method="POST",
    )
    with _abrir(peticion, config) as resp:
        return json.loads(resp.read().decode("utf-8"))


def api_descargar(config: dict, ruta: str, destino_temporal: Path) -> dict:
    """
    Descarga a un fichero temporal y devuelve las cabeceras del asiento. No
    escribe en el sitio definitivo: quien llama verifica antes el hash, y si
    no cuadra lo tira sin haber tocado nada de lo que ya había.
    """
    peticion = urllib.request.Request(config["url_base"].rstrip("/") + ruta, headers=_cabeceras(config))
    with _abrir(peticion, config) as resp:
        destino_temporal.write_bytes(resp.read())
        cab = resp.headers

    return {
        "hash": cab.get("X-Hash-Blend"),
        "descarga_id": int(cab.get("X-Descarga-Id") or 0) or None,
        "variante_id": int(cab.get("X-Variante-Id") or 0) or None,
        "variante": urllib.parse.unquote(cab.get("X-Variante-Nombre") or ""),
        "familia": urllib.parse.unquote(cab.get("X-Familia-Nombre") or ""),
        "rama_id": int(cab.get("X-Rama-Id") or 0) or None,
        "rama": urllib.parse.unquote(cab.get("X-Rama-Nombre") or ""),
        "sesion_id": int(cab.get("X-Sesion-Id") or 0) or None,
        "sesion": int(cab.get("X-Sesion-Numero") or 0) or None,
        "nombre_fichero": _nombre_de_cabecera(cab.get("Content-Disposition")),
    }


def _nombre_de_cabecera(disposicion: Optional[str]) -> str:
    if disposicion and 'filename="' in disposicion:
        return disposicion.split('filename="', 1)[1].split('"', 1)[0]
    return "pieza.blend"


def asegurar_maquina(config: dict) -> dict:
    """
    Alta automática / ping (spec 4.5). Se llama antes de cualquier escritura:
    la API rechaza UUIDs desconocidos a propósito, para que el registro de
    máquinas no se llene de fantasmas a mitad de una subida.
    """
    return api_post(config, "/maquina/registrar", {
        "uuid": config["uuid"],
        "hostname": socket.gethostname(),
        "so": f"{platform.system()} {platform.release()}",
    })


def nombre_completo(variante: dict) -> str:
    """
    "Pincel de pintura / estandar". El nombre de la variante solo es único
    dentro de su pieza ("base" se repite en TODAS las piezas, porque es la
    que se crea sola), así que en cualquier mensaje al usuario va con el
    nombre de la pieza delante: una
    lista de tres "estandar" no sirve para elegir ninguno.
    """
    familia = variante.get("familia_nombre")
    return f"{familia} / {variante['nombre']}" if familia else variante["nombre"]


def estado_de_version(variante: dict) -> str:
    """
    Cómo va de "lista" una pieza, en una columna. Mismo vocabulario que el
    listado de la web a propósito: son la misma pregunta mirada desde dos
    sitios, y dos nombres distintos para el mismo estado obligarían a
    traducir mentalmente al pasar de la terminal al navegador.
    """
    if variante["version_validada"]:
        return f"v{variante['version_validada']['numero']:03d} ✓"

    # Servidor anterior a este campo: el cliente se actualiza solo (fase 15)
    # y la web no, así que durante un tiempo se hablan versiones distintas.
    # Sin el dato no se puede afinar, y "sin validar" es lo único cierto en
    # todos los casos — mejor eso que afirmar un estado inventado.
    if "ultima_version_estado" not in variante:
        return "sin validar"

    estado = variante["ultima_version_estado"]
    if estado is None:
        return "sin versión"
    if estado == "borrador":
        return "versión sin imprimir"
    if estado == "descartada":
        return "no sirve"

    return "sin validar"


def avisos_de(variante: dict) -> list:
    """
    Lo que hay encima de la pieza ahora mismo, al margen de lo terminado que
    esté. "sesión abierta" ya dice que se está modificando, así que el aviso
    de trabajo sin promocionar solo aparece cuando no la hay: el caso que
    antes no se veía en ninguna parte — subido, cerrado y ahí parado, sin
    bloqueo ni descarga pendiente que lo delatase.
    """
    avisos = []

    if variante["sesion_abierta"]:
        avisos.append(f"sesión abierta en {variante.get('sesion_maquina') or '?'}")
    elif variante.get("trabajo_en_curso"):
        avisos.append("modificando, sin promocionar")

    if variante["descargas_pendientes"]:
        avisos.append(f"{variante['descargas_pendientes']} descarga(s) sin cerrar")

    return avisos


def _listado_agrupado(variantes: list, categorias_orden: list) -> str:
    """
    Una pieza por línea, agrupada por categoría — el mismo orden que ya usa
    "catalogo" (spec 11.1) — en vez de la única línea larguísima separada
    por comas que había antes. "base" (la variante que se crea sola con
    cada pieza) no se muestra: repetida en casi todas las líneas no dice
    nada. Si una pieza tiene además otras variantes con nombre propio, esas
    sí se listan entre paréntesis, que es justo lo que distingue una de otra.
    """
    por_categoria: dict = {}
    for v in variantes:
        pieza = v.get("familia_nombre") or v["nombre"]
        por_categoria.setdefault(v.get("categoria_nombre"), {}).setdefault(pieza, []).append(v["nombre"])

    lineas = []
    for categoria in categorias_orden + [None]:
        piezas = por_categoria.pop(categoria, None)
        if not piezas:
            continue

        lineas.append(f"  {categoria or 'Sin clasificar'}:")
        for pieza in sorted(piezas, key=str.lower):
            nombres = sorted(n for n in piezas[pieza] if n != PIEZA_VARIANTE_BASE)
            lineas.append(f"    {pieza}" + (f" ({', '.join(nombres)})" if nombres else ""))

    return "\n".join(lineas)


def resolver_variante(config: dict, texto: str) -> dict:
    """
    Acepta el id, el nombre de la pieza, el de la variante, o trozos de
    ambos en cualquier orden ("pincel", "estandar", "pincel estandar").

    Se busca sobre pieza + variante porque es como se piensa: la pieza lleva
    el nombre real ("Pincel de pintura") y la variante suele ser
    una etiqueta genérica ("estandar") que por sí sola no dice qué es.
    """
    respuesta = api_get(config, "/variantes")
    variantes = respuesta.get("variantes", [])
    if not variantes:
        raise RuntimeError("no hay ninguna variante todavía. Créala en la web.")
    categorias_orden = respuesta.get("categorias", [])

    def buscable(v: dict) -> str:
        return f"{v.get('familia_nombre') or ''} {v['nombre']}".lower()

    if texto.isdigit():
        exactas = [v for v in variantes if v["id"] == int(texto)]
    else:
        texto_l = texto.lower()

        # 1) El nombre exacto de variante gana siempre: si tienes una
        # variante llamada "estandar" y escribes "estandar", es esa, aunque
        # el texto también aparezca dentro del nombre de otras piezas.
        exactas = [v for v in variantes if v["nombre"].lower() == texto_l]

        # 2) El nombre exacto de PIEZA gana sobre cualquiera que solo lo
        # contenga como subcadena: escribir "Brazo" no debe traer también
        # "Brazo integral" o "Brazo y mano" solo por compartir la palabra.
        if not exactas:
            exactas = [v for v in variantes if (v.get("familia_nombre") or "").lower() == texto_l]

        # 3) "pieza variante" completos y exactos, en ese orden — para
        # cuando la pieza exacta (paso 2) tiene más de una variante, p. ej.
        # "Brazo base" con "Brazo integral" y "Brazo y mano" también en el
        # catálogo: sin este paso, el 4 los mezclaría a los tres por
        # compartir la palabra "brazo".
        if not exactas:
            palabras = texto_l.split()
            for corte in range(1, len(palabras)):
                familia_txt  = " ".join(palabras[:corte])
                variante_txt = " ".join(palabras[corte:])
                exactas = [
                    v for v in variantes
                    if (v.get("familia_nombre") or "").lower() == familia_txt
                    and v["nombre"].lower() == variante_txt
                ]
                if exactas:
                    break

        # 4) Por trozos, el último recurso: cualquiera cuyo nombre
        # combinado (pieza + variante) contenga todas las palabras escritas.
        if not exactas:
            exactas = [v for v in variantes if all(p in buscable(v) for p in palabras)]

    if len(exactas) == 1:
        return exactas[0]
    if not exactas:
        raise RuntimeError(
            f"no hay ninguna variante que sea '{texto}'. Piezas disponibles:\n\n"
            f"{_listado_agrupado(variantes, categorias_orden)}\n"
        )

    raise RuntimeError(
        f"'{texto}' encaja con varias:\n\n"
        f"{_listado_agrupado(exactas, categorias_orden)}\n\n"
        "    Concreta añadiendo la pieza, p.ej.: trackbitos bajar \"pincel base\""
    )


# --------------------------------------------------------------------------
# La tabla de decisión (spec 4.3)
# --------------------------------------------------------------------------

def evaluar(hash_local: Optional[str], hash_origen: Optional[str], hash_nube: Optional[str],
            sesion_nueva: bool = False) -> dict:
    """
    Tabla 4.3 de la spec, como función pura (sin tocar disco) para poder
    probarla directamente. Devuelve un dict con la situación, el veredicto
    de cada comparación y qué acción ofrecer.
    """
    if sesion_nueva:
        # Sesión abierta sin descargar nada (variante estrenada): no hay
        # "origen" con el que comparar porque nunca hubo fichero anterior.
        return {
            "situacion": "sesion_nueva",
            "veredicto_local": None,
            "veredicto_nube": None,
            "mensaje": "Sesión abierta para empezar de cero. Todavía no has subido nada."
                       if hash_local else "Sesión abierta para empezar de cero. Aún no hay .blend en este directorio.",
            "accion": "subir" if hash_local else None,
        }

    if hash_local is None:
        return {
            "situacion": "corrupto",
            "veredicto_local": None,
            "veredicto_nube": None,
            "mensaje": "Falta el .blend (o no se pudo leer). Estado corrupto: no se borra nada.",
            "accion": None,
        }

    if hash_origen is None:
        # Caso límite explícito (4.3): nunca asumir que está al día.
        return {
            "situacion": "divergencia",
            "veredicto_local": None,
            "veredicto_nube": None,
            "mensaje": f"Falta {SENTINEL_NAME}: no se puede saber si esto está al día. Se trata como divergencia.",
            "accion": "subir",
        }

    local_igual_origen = hash_local == hash_origen

    if hash_nube is None:
        return {
            "situacion": "sin_nube",
            "veredicto_local": local_igual_origen,
            "veredicto_nube": None,
            "mensaje": "No se pudo determinar el hash de la nube (¿API no disponible todavía?).",
            "accion": None,
        }

    origen_igual_nube = hash_origen == hash_nube

    if local_igual_origen and origen_igual_nube:
        return {
            "situacion": "al_dia",
            "veredicto_local": True,
            "veredicto_nube": True,
            "mensaje": "Al día.",
            "accion": "borrable",
        }
    if local_igual_origen and not origen_igual_nube:
        return {
            "situacion": "nube_avanzo",
            "veredicto_local": True,
            "veredicto_nube": False,
            "mensaje": "La nube avanzó.",
            "accion": "descargar",
        }
    if not local_igual_origen and origen_igual_nube:
        return {
            "situacion": "sin_subir",
            "veredicto_local": False,
            "veredicto_nube": True,
            "mensaje": "Tienes cambios sin subir.",
            "accion": "subir",
        }
    return {
        "situacion": "divergencia",
        "veredicto_local": False,
        "veredicto_nube": False,
        "mensaje": "Divergencia real: la nube avanzó y tú tienes cambios sin subir a la vez.",
        "accion": "subir_como_nueva",
    }


def _marca(veredicto: Optional[bool]) -> str:
    if veredicto is None:
        return "?"
    return "✓" if veredicto else "⚠"  # ✓ / ⚠


def imprimir_veredicto(directorio: Path, sentinel: Optional[dict], resultado: dict) -> None:
    if sentinel:
        cabecera = f"{sentinel.get('variante', '?')} · rama {sentinel.get('rama', '?')}"
        if sentinel.get("sesion"):
            cabecera += f" · sesión {sentinel['sesion']}"
        if sentinel.get("descarga_id"):
            cabecera += f" · descarga de {sentinel.get('motivo', 'trabajo')} sin cerrar"
    else:
        cabecera = str(directorio)
    print(f"\n{cabecera}\n")

    situacion = resultado["situacion"]

    if situacion in ("corrupto", "sesion_nueva"):
        print(f"  {'⚠' if situacion == 'corrupto' else '·'} {resultado['mensaje']}")
        siguiente = ("Ejecuta: trackbitos subir" if resultado["accion"] == "subir"
                     else "Guarda tu .blend en este directorio y ejecuta: trackbitos subir"
                     if situacion == "sesion_nueva"
                     else "Revisa el directorio a mano. No se toca nada hasta que esté claro qué hay ahí.")
        print(f"\n  → {siguiente}\n")
        return

    if situacion == "sin_nube":
        marca_local = _marca(resultado["veredicto_local"])
        etiqueta_local = "sin cambios" if resultado["veredicto_local"] else "tienes cambios sin subir"
        print(f"  local  {'=' if resultado['veredicto_local'] else '≠'} origen   {marca_local} {etiqueta_local}")
        print(f"  {resultado['mensaje']}")
        print("\n  → No se puede dar veredicto sobre la nube todavía.\n")
        return

    if situacion == "divergencia" and resultado["veredicto_local"] is None:
        # Caso límite: sin .sesion.json, no hay "local vs origen" que mostrar.
        print(f"  ⚠ {resultado['mensaje']}")
        print("\n  → Ejecuta: trackbitos subir\n")
        return

    marca_local = _marca(resultado["veredicto_local"])
    signo_local = "=" if resultado["veredicto_local"] else "≠"
    etiqueta_local = "sin cambios" if resultado["veredicto_local"] else "tienes cambios sin subir"
    print(f"  local  {signo_local} origen   {marca_local} {etiqueta_local}")

    marca_nube = _marca(resultado["veredicto_nube"])
    signo_nube = "=" if resultado["veredicto_nube"] else "≠"
    etiqueta_nube = "la nube no ha avanzado" if resultado["veredicto_nube"] else "la nube avanzó (o hay divergencia)"
    print(f"  origen {signo_nube} nube     {marca_nube} {etiqueta_nube}")

    acciones = {
        "borrable": "Al día. Puedes borrar la copia local con seguridad.",
        "cerrar_sesion": "Todo subido, pero la sesión sigue abierta y bloquea el otro equipo. Ejecuta: trackbitos cerrar",
        "cerrar_sin_cambios": "No has tocado nada. Ejecuta: trackbitos cerrar --sin-cambios",
        "descargar": "Descarga la sesión nueva. Es seguro: no tienes cambios locales que perder.",
        "subir": "Ejecuta: trackbitos subir",
        "subir_como_nueva": "Divergencia real. Ejecuta: trackbitos subir (se guardará como sesión nueva; nada se fusiona).",
    }
    print(f"\n  → {acciones.get(resultado['accion'], resultado['mensaje'])}\n")


def imprimir_avisos(estado_api: dict, config: dict, maquina_id: Optional[int]) -> None:
    """
    Lo que pasa en la OTRA máquina, que es lo que los tres hashes no ven
    (spec 4.4). Se imprime antes del veredicto porque es lo que evita bajar
    aquí y machacar trabajo que quedó allí.
    """
    # Los ids llegan de MySQL y pueden venir como texto: comparados en crudo,
    # "1" != 1 y el aviso saltaría contra tu propia máquina.
    def _mismo(otro) -> bool:
        return maquina_id is not None and otro is not None and int(otro) == int(maquina_id)

    sesion = estado_api.get("sesion_abierta")
    if sesion and not _mismo(sesion.get("maquina_id")):
        print(f"  ⚠ Sesión abierta en {sesion.get('maquina_nombre') or 'otra máquina'} "
              f"desde {sesion.get('abierta_en', '?')}")

    for d in estado_api.get("descargas_pendientes", []):
        if _mismo(d.get("maquina_id")):
            continue
        print(f"  ⚠ Descarga sin cerrar en {d.get('maquina_nombre') or 'otra máquina'} — "
              f"{d.get('descargado_en', '?')}, motivo: {d.get('motivo')}")
        print("    Esa copia nunca se cerró. Si trabajaste ahí, perderás ese trabajo.")


# --------------------------------------------------------------------------
# Comandos
# --------------------------------------------------------------------------

def _directorio(args) -> Path:
    directorio = Path(args.dir).resolve()
    if not directorio.is_dir():
        raise RuntimeError(f"el directorio {directorio} no existe.")
    return directorio


def _exigir_sentinel(directorio: Path) -> dict:
    sentinel = cargar_json(directorio / SENTINEL_NAME)
    if not sentinel:
        raise RuntimeError(
            f"no hay {SENTINEL_NAME} en {directorio}: este directorio no es una mesa de trabajo.\n"
            "  → Empieza con: trackbitos bajar <variante>"
        )
    return sentinel


def cmd_estado(args) -> int:
    directorio = _directorio(args)
    sentinel = cargar_json(directorio / SENTINEL_NAME)
    blend = encontrar_blend(directorio)

    # Ni .sesion.json ni .blend: no es que algo se haya corrompido, es que
    # esto nunca fue una mesa de trabajo. Antes caía en evaluar() con todo
    # a None y salía como "estado corrupto" — alarmante y engañoso para lo
    # que en realidad es un directorio vacío cualquiera. Mismo mensaje que
    # ya da _exigir_sentinel() (subir/cerrar/promocionar), para no decir
    # dos cosas distintas de la misma situación según el comando.
    if not sentinel and not blend:
        print(f"\n{directorio}\n")
        print(f"  · No es un directorio de trabajo: no hay {SENTINEL_NAME} ni ningún .blend aquí.")
        print("\n  → Empieza con: trackbitos bajar <variante>\n")
        return 1

    hash_local = sha256_de(blend) if blend else None
    hash_origen = sentinel.get("hash_origen") if sentinel else None

    hash_nube = None
    if sentinel and "variante_id" in sentinel:
        # Sin .sesion.json no hay variante_id que consultar — evaluar()
        # ya trata ese caso como divergencia sin necesidad de red.
        try:
            config = cargar_config()
            maquina = asegurar_maquina(config)
            estado_api = api_get(config, f"/variante/{sentinel['variante_id']}/estado")
            hash_nube = estado_api.get("hash_nube")
            print()
            imprimir_avisos(estado_api, config, maquina.get("id"))
        except RuntimeError as e:
            print(f"  ⚠ No se pudo consultar la nube: {e}\n", file=sys.stderr)

    resultado = evaluar(hash_local, hash_origen, hash_nube,
                        sesion_nueva=bool(sentinel and sentinel.get("origen") == "nuevo"))

    # "Al día" no siempre significa "ya está": si queda un asiento abierto
    # hay que cerrarlo (y la prueba de que no tocaste nada solo la tienes
    # aquí), y si queda una sesión viva, la otra máquina sigue bloqueada.
    if resultado["accion"] == "borrable" and sentinel:
        if sentinel.get("descarga_id"):
            resultado["accion"] = "cerrar_sin_cambios"
        elif sentinel.get("sesion_id"):
            resultado["accion"] = "cerrar_sesion"

    imprimir_veredicto(directorio, sentinel, resultado)

    # Código de salida distinto de cero cuando hay algo pendiente de
    # resolver: útil para scripts/cron, y coherente con "bajar se niega".
    return 0 if resultado["accion"] in ("borrable", "descargar") else 1


def _bajar(args, motivo: str) -> int:
    directorio = _directorio(args)
    config = cargar_config()
    maquina = asegurar_maquina(config)

    sentinel = cargar_json(directorio / SENTINEL_NAME)
    blend = encontrar_blend(directorio)

    if args.variante:
        variante = resolver_variante(config, args.variante)
        # nombre_completo: "base" a secas no dice de qué pieza es (spec:
        # abrir/bajar deben confirmar SIN AMBIGÜEDAD qué se acaba de tocar).
        variante_id, variante_nombre = variante["id"], nombre_completo(variante)
    elif sentinel and sentinel.get("variante_id"):
        variante_id, variante_nombre = sentinel["variante_id"], sentinel.get("variante", "?")
    else:
        raise RuntimeError("dime qué variante: trackbitos bajar <variante>")

    # Se niega antes de tocar la red si aquí hay trabajo sin subir (spec 5.3).
    if blend and sentinel:
        local = sha256_de(blend)
        if sentinel.get("hash_origen") and local != sentinel["hash_origen"]:
            print(f"\n{sentinel.get('variante', '?')} · rama {sentinel.get('rama', '?')}\n")
            print("  ⚠ El fichero de este directorio ha cambiado y no está subido.")
            print("    (puede ser una modificación real o solo un guardado)")
            print("\n  → Súbelo antes de bajar nada: trackbitos subir\n")
            return 1
        if sentinel.get("origen") == "nuevo":
            print("\n  ⚠ Tienes una sesión abierta sin subir en este directorio.")
            print("\n  → Súbela antes de bajar nada: trackbitos subir\n")
            return 1

    # Una consulta encima de una mesa de trabajo dejaría la sesión abierta
    # sin nadie que la recuerde: el .sesion.json es el único sitio donde
    # vive su id en este equipo.
    if motivo == "consulta" and sentinel and sentinel.get("sesion_id"):
        print(f"\n  ⚠ Este directorio tiene la sesión {sentinel.get('sesion')} abierta: "
              "mirar aquí taparía su .sesion.json.")
        print("\n  → Usa otro directorio: trackbitos ver <variante> --dir <otra-carpeta>\n")
        return 1

    estado_api = api_get(config, f"/variante/{variante_id}/estado")
    print()
    imprimir_avisos(estado_api, config, maquina.get("id"))

    origen = estado_api.get("origen_descarga")
    if not origen:
        if motivo == "consulta":
            # "ver" es de solo lectura: sin ningún fichero que mirar, no hay
            # nada que hacer aquí (a diferencia de "trabajo", más abajo, no
            # tiene sentido abrir una sesión solo para consultar el vacío).
            print(f"\n{variante_nombre}: no hay ningún fichero subido todavía, no hay nada que mirar.")
            # El id, no el nombre, para que se pueda pegar tal cual: con
            # varias piezas que comparten "base" el nombre por sí solo no
            # resolvería necesariamente la misma.
            print(f"\n  → Para empezar a trabajar: trackbitos bajar {variante_id}\n")
            return 1

        # Pieza recién estrenada: no existe ningún fichero todavía, así que
        # no hay nada que descargar — se abre la sesión directamente, igual
        # que antes hacía el comando aparte "abrir" (fusionado aquí: como
        # con `git`, un solo comando sirve para las dos situaciones, sin que
        # haga falta saber de antemano si hay historial o no).
        if blend:
            apartado = a_papelera(blend)
            print(f"  · Había un .blend suelto de otra pieza en esta carpeta: se apartó a {apartado}")

        respuesta = api_post(config, f"/variante/{variante_id}/sesion/abrir")
        sesion = respuesta["sesion"]
        rama = (estado_api.get("rama") or {})

        escribir_sentinel(directorio, {
            "variante_id": variante_id,
            "variante": variante_nombre,
            "rama_id": rama.get("id"),
            "rama": rama.get("nombre"),
            "sesion_id": sesion["id"],
            "sesion": sesion["numero"],
            "descarga_id": None,
            "motivo": "trabajo",
            "origen": "nuevo",
            "hash_origen": None,
            "descargado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
        })

        print(f"\n{variante_nombre} · rama {rama.get('nombre')} · sesión {sesion['numero']} abierta")
        print("  (pieza recién estrenada: no había ningún fichero que descargar)\n")
        print(f"  → Guarda tu .blend en {directorio} y ejecuta: trackbitos subir\n")
        return 0

    ruta = f"/{origen['tipo']}/{origen['id']}/descargar?motivo={motivo}"
    if args.ignorar_pendiente:
        ruta += "&ignorar_pendiente=1"

    temporal = directorio / ".descarga-parcial"
    try:
        asiento = api_descargar(config, ruta, temporal)

        # Spec 5.3: verificar antes de escribir nada definitivo. Si el
        # fichero llegó tocado, se tira y el directorio queda como estaba.
        real = sha256_de(temporal)
        if real != asiento["hash"]:
            temporal.unlink(missing_ok=True)
            print("\n  ⚠ Lo descargado no coincide con el hash que declara el servidor. No se ha escrito nada.")
            print("\n  → Vuelve a intentarlo. Si se repite, revisa la conexión.\n")
            return 1

        if blend:
            apartado = a_papelera(blend)
            print(f"  · El .blend anterior se apartó a {apartado}")

        destino = directorio / asiento["nombre_fichero"]
        temporal.replace(destino)
    finally:
        temporal.unlink(missing_ok=True)

    # Pieza + variante, no la variante a secas ("base" no distingue nada):
    # es lo que va a la confirmación de abajo y a todo lo que luego lea
    # este sentinel (estado, cerrar, promocionar...).
    identidad = f"{asiento['familia']} / {asiento['variante']}" if asiento.get("familia") else asiento["variante"]

    escribir_sentinel(directorio, {
        "variante_id": asiento["variante_id"] or variante_id,
        "variante": identidad or variante_nombre,
        "rama_id": asiento["rama_id"],
        "rama": asiento["rama"],
        "sesion_id": asiento["sesion_id"],
        "sesion": asiento["sesion"],
        "descarga_id": asiento["descarga_id"],
        "motivo": motivo,
        "origen": f"{origen['tipo']} {origen['numero']}",
        "hash_origen": asiento["hash"],
        "descargado_en": datetime.now().astimezone().isoformat(timespec="seconds"),
        "maquina": maquina.get("nombre"),
    })

    print(f"\n{identidad or variante_nombre} · rama {asiento['rama']}"
          + (f" · sesión {asiento['sesion']} abierta" if asiento["sesion"] else " · solo consulta") + "\n")
    print(f"  ✓ {destino.name} listo en {directorio}")
    if motivo == "trabajo":
        print("\n  → Trabaja y luego: trackbitos subir\n")
    else:
        print("\n  → Cuando termines de mirarlo: trackbitos cerrar --sin-cambios\n")
    return 0


def cmd_bajar(args) -> int:
    return _bajar(args, "trabajo")


def cmd_ver(args) -> int:
    return _bajar(args, "consulta")


def cmd_subir(args) -> int:
    directorio = _directorio(args)
    config = cargar_config()
    asegurar_maquina(config)

    sentinel = _exigir_sentinel(directorio)
    blend = encontrar_blend(directorio)
    if not blend:
        raise RuntimeError(f"no hay ningún .blend (o hay más de uno) en {directorio}. No se adivina cuál subir.")

    sesion_id = sentinel.get("sesion_id")
    if not sesion_id:
        # Aquí no hay sesión viva: o bajaste solo a mirar y has acabado
        # tocando algo (spec 4.4 — el motivo declarado al bajar no ata), o
        # acabas de promocionar y sigues trabajando sobre la rama nueva. En
        # los dos casos, subir abre sesión como cualquier otra.
        print("\n  · No hay ninguna sesión abierta en este directorio: se abre una para subir.")
        sesion = api_post(config, f"/variante/{sentinel['variante_id']}/sesion/abrir")["sesion"]
        sesion_id = sesion["id"]
        sentinel = actualizar_sentinel(directorio, {"sesion_id": sesion_id, "sesion": sesion["numero"]})

    hash_local = sha256_de(blend)
    respuesta = api_post_fichero(config, f"/sesion/{sesion_id}/subir", {
        "hash": hash_local,
        "hash_padre": sentinel.get("hash_origen"),
        "log": args.log,
    }, blend)

    subida = respuesta["sesion"]
    actualizar_sentinel(directorio, {
        "hash_origen": subida["hash_blend"],
        "descarga_id": None,
        "origen": f"sesion {subida['numero']}",
    })

    print(f"\n{sentinel.get('variante', '?')} · rama {sentinel.get('rama', '?')} · sesión {subida['numero']} subida\n")
    print(f"  ✓ {blend.name} — {subida['tamano_bytes']} bytes")
    print("\n  → Cuando cierres Blender: trackbitos cerrar")
    print("     Si esto ya es la versión buena: trackbitos promocionar --cambio \"...\"\n")
    return 0


def cmd_cerrar(args) -> int:
    directorio = _directorio(args)
    config = cargar_config()
    asegurar_maquina(config)

    sentinel = _exigir_sentinel(directorio)
    blend = encontrar_blend(directorio)

    if args.sin_cambios:
        descarga_id = sentinel.get("descarga_id")
        if not descarga_id:
            raise RuntimeError(
                "no hay ninguna descarga abierta anotada aquí: no hay nada que cerrar sin cambios."
            )
        if not blend:
            raise RuntimeError(
                "no encuentro el .blend, y la prueba de que no lo has tocado es justamente su hash.\n"
                "  → Si el fichero ya no existe, hay que forzar el cierre desde la web."
            )

        api_post(config, f"/descarga/{descarga_id}/cerrar-sin-cambios", {"hash_local": sha256_de(blend)})
        actualizar_sentinel(directorio, {"descarga_id": None, "sesion_id": None, "sesion": None})

        print(f"\n{sentinel.get('variante', '?')} · descarga cerrada sin cambios\n")
        print("  ✓ El asiento cuadra: no se subió nada porque no hacía falta.")
        print("\n  → Esta copia ya es borrable con seguridad.\n")
        return 0

    sesion_id = sentinel.get("sesion_id")
    if not sesion_id:
        raise RuntimeError("no hay ninguna sesión abierta anotada en este directorio.")

    # Cerrar con trabajo sin subir dejaría ese trabajo fuera del sistema: la
    # sesión quedaría cerrada y vacía, y el .blend solo en este disco.
    #
    # Sin .blend delante no hay nada que subir (sesión de consulta, o abierta
    # y no usada), y ahí cerrar es legítimo. Pero si lo hay, hay dos motivos
    # distintos para negarse, y antes solo se cubría el segundo:
    if blend:
        origen = sentinel.get("hash_origen")

        # 1) Sesión abierta de cero ("abrir" escribe hash_origen: None porque
        #    no se partió de ningún fichero). No había con qué comparar y eso
        #    se leía como "nada que comprobar" — justo al revés: si hay un
        #    .blend, está entero sin subir.
        if not origen:
            print("\n  ⚠ Esta sesión no tiene nada subido, y aquí hay un .blend.")
            print(f"    ({blend.name})")
            print("\n  → Súbelo antes de cerrar: trackbitos subir")
            print("    Si de verdad no quieres conservarlo, borra el fichero y vuelve a cerrar.\n")
            return 1

        # 2) Se subió algo, pero después se siguió tocando.
        if sha256_de(blend) != origen:
            print("\n  ⚠ El fichero ha cambiado desde la última subida.")
            print("    (puede ser una modificación real o solo un guardado)")
            print("\n  → Súbelo antes de cerrar: trackbitos subir\n")
            return 1

    api_post(config, f"/sesion/{sesion_id}/cerrar")
    numero = sentinel.get("sesion", "?")
    actualizar_sentinel(directorio, {"sesion_id": None, "sesion": None, "descarga_id": None})

    print(f"\n{sentinel.get('variante', '?')} · sesión {numero} cerrada\n")
    print("  ✓ La máquina queda libre: ya puedes trabajar desde el otro equipo.")
    print("\n  → Si esta es la versión buena: trackbitos promocionar --cambio \"...\"\n")
    return 0


def cmd_papelera(args) -> int:
    """Qué hay apartado y cuánto le queda antes de caducar."""
    if not PAPELERA_DIR.is_dir():
        print(f"\nLa papelera está vacía ({PAPELERA_DIR}).\n")
        return 0

    ficheros = sorted((f for f in PAPELERA_DIR.iterdir() if f.is_file()),
                      key=lambda f: f.stat().st_mtime, reverse=True)
    if not ficheros:
        print(f"\nLa papelera está vacía ({PAPELERA_DIR}).\n")
        return 0

    print(f"\n{PAPELERA_DIR}\n")
    total = 0
    for f in ficheros:
        dias = int((datetime.now().timestamp() - f.stat().st_mtime) / 86400)
        total += f.stat().st_size
        print(f"  {f.name}  ·  hace {dias} día(s)  ·  caduca en {max(0, DIAS_PAPELERA - dias)}")

    tamano = f"{total / 1024:.0f} KB" if total >= 1024 else f"{total} bytes"
    print(f"\n  {len(ficheros)} fichero(s), {tamano}. Se borran solos a los {DIAS_PAPELERA} días.\n")
    return 0


def cmd_catalogo(args) -> int:
    """
    El catálogo completo desde la terminal — la misma foto que la portada
    web (/piezas), para cuando se trabaja por SSH o simplemente no se
    quiere abrir el navegador para saber "¿qué tengo y por dónde voy?".

    Agrupado por categoría, en el mismo orden que ya usan el índice y la
    galería web (spec 11.1) — son las carpetas que el usuario ya tiene en la
    cabeza, y agrupar así evita tener que abrir el navegador solo para saber
    en qué carpeta anda cada pieza. Dentro de cada categoría, una línea por
    variante con el nombre completo (nombre_completo: "pieza variante"),
    sin una cabecera aparte por pieza: con el catálogo real (quince y
    subiendo) sería el doble de líneas para la misma información, y el
    nombre completo ya dice de qué pieza es cada una.
    """
    config = cargar_config()
    asegurar_maquina(config)

    respuesta = api_get(config, "/variantes")
    variantes = respuesta.get("variantes", [])
    if not variantes:
        print("\nTodavía no hay ninguna variante. Créala en la web.\n")
        return 0

    por_categoria: dict = {}
    for v in variantes:
        por_categoria.setdefault(v.get("categoria_nombre"), []).append(v)

    # None = sin clasificar; va al final, igual que en el índice web.
    orden_categorias = respuesta.get("categorias", []) + [None]

    print()
    for categoria in orden_categorias:
        de_esta_categoria = por_categoria.pop(categoria, [])
        if not de_esta_categoria:
            continue

        print(categoria or "Sin clasificar")
        for v in sorted(de_esta_categoria, key=lambda v: nombre_completo(v).lower()):
            buena = estado_de_version(v)

            avisos = avisos_de(v)

            # 21, no 16: es lo que mide "versión sin imprimir" más el espacio
            # de separación. Con 16 la columna de la derecha se descolocaba
            # en cada pieza cuyo estado fuese más largo que eso.
            linea = f"  {nombre_completo(v):<30} {buena:<21} {v['versiones']} versión(es)"
            if avisos:
                linea += "  ⚠ " + " · ".join(avisos)
            print(linea)
        print()

    return 0


def cmd_variantes(args) -> int:
    """
    El zoom sobre una pieza concreta: cuántas variantes tiene y cómo se
    llama cada una. "catalogo" es la foto completa; esto es para cuando ya
    sabes qué pieza quieres mirar y no hace falta barrer las demás —
    "¿cuántas líneas de diseño tiene ya la Silla, y cómo se llaman?".
    """
    config = cargar_config()
    asegurar_maquina(config)

    todas = api_get(config, "/variantes").get("variantes", [])
    if not todas:
        print("\nTodavía no hay ninguna variante. Créala en la web.\n")
        return 0

    familias = sorted({v["familia_nombre"] for v in todas if v.get("familia_nombre")})
    texto = args.pieza.lower()

    # Mismo criterio que resolver_variante: el nombre exacto gana siempre
    # (para que "silla" no se ambigüe consigo misma), y si no hay exacto se
    # prueba por trozos del nombre.
    exactas = [f for f in familias if f.lower() == texto]
    if not exactas:
        exactas = [f for f in familias if texto in f.lower()]

    if not exactas:
        raise RuntimeError(f"no hay ninguna pieza que sea '{args.pieza}'. Hay: {', '.join(familias)}")
    if len(exactas) > 1:
        raise RuntimeError(f"'{args.pieza}' encaja con varias piezas: {', '.join(exactas)}. Concreta más.")

    familia = exactas[0]
    suyas = sorted((v for v in todas if v.get("familia_nombre") == familia), key=lambda v: v["nombre"])

    print(f"\n{familia} — {len(suyas)} variante(s)\n")
    for v in suyas:
        buena = estado_de_version(v)

        avisos = avisos_de(v)

        linea = f"  {v['nombre']:<20} {buena:<21} {v['versiones']} versión(es)"
        if avisos:
            linea += "  ⚠ " + " · ".join(avisos)
        print(linea)
    print()

    return 0


def cmd_promocionar(args) -> int:
    directorio = _directorio(args)
    config = cargar_config()
    asegurar_maquina(config)

    sentinel = _exigir_sentinel(directorio)
    cambio = (args.cambio or "").strip()
    if not cambio:
        raise RuntimeError(
            'promocionar exige --cambio "qué se modificó". Es el campo que dará valor al historial dentro de tres meses.'
        )

    respuesta = api_post(config, f"/variante/{sentinel['variante_id']}/promocionar",
                         {"cambio": cambio, "medidas": args.medidas})
    version = respuesta["version"]
    rama_nueva = respuesta.get("rama_nueva") or {}

    actualizar_sentinel(directorio, {
        "rama_id": rama_nueva.get("id"),
        "rama": rama_nueva.get("nombre"),
        "sesion_id": None,
        "sesion": None,
        "descarga_id": None,
        "origen": f"version {version['numero']}",
        "hash_origen": version["hash_blend"],
    })

    # Spec 7.3: promocionar cierra un ciclo de trabajo, y la confirmación
    # tiene que dejarlo ver — número asignado, fecha y la rama ya abierta.
    print(f"\n  ✓ {sentinel.get('variante', '?')} · {version['etiqueta']} promocionada")
    print(f"    {version['promocionada_en']} — {version['cambio']}")
    print(f"    Rama nueva abierta: {rama_nueva.get('nombre')}\n")
    print("  → Cuando la imprimas, anótalo con el estado: impresa / validada / descartada.\n")
    return 0


# --------------------------------------------------------------------------
# Auto-actualización
# --------------------------------------------------------------------------

def _version_tupla(v: str) -> tuple:
    """"1.2.10" -> (1, 2, 10), para comparar sin los líos de comparar texto ("1.9" > "1.10")."""
    try:
        return tuple(int(p) for p in v.strip().split("."))
    except ValueError:
        return (0,)


def comprobar_version_remota(config: dict) -> Optional[str]:
    """
    Un vistazo silencioso a si hay versión nueva. Aviso automático,
    actualización manual (nunca se autoactualiza sola): esto solo mira y
    devuelve la versión si es más nueva, o None ante cualquier tropiezo — sin
    red, sin config todavía, servidor caído. Timeout corto y propio (no el de
    _abrir/api_get) a propósito: esto es un extra, y no puede ser lo que deje
    esperando al comando que el usuario vino a ejecutar de verdad. Si de
    verdad hace falta quejarse de que no se pudo comprobar, ya se queja
    "trackbitos actualizar", que sí es una petición explícita.
    """
    try:
        peticion = urllib.request.Request(
            config["url_base"].rstrip("/") + "/cliente/version", headers=_cabeceras(config)
        )
        with urllib.request.urlopen(peticion, timeout=3) as resp:
            remota = json.loads(resp.read().decode("utf-8")).get("version")
    except Exception:
        return None

    if remota and _version_tupla(remota) > _version_tupla(VERSION):
        return remota
    return None


def _aplicar_actualizacion(config: dict, remota: str) -> Path:
    """
    Descarga, verifica que compila y se reemplaza a sí mismo. Compartido por
    "actualizar" (invocación explícita) y por la actualización automática de
    cada ejecución (fase 23) — la lógica de CÓMO actualizar es una sola; lo
    que cambia entre las dos es quién la dispara y qué dice al terminar.
    """
    peticion = urllib.request.Request(
        config["url_base"].rstrip("/") + "/cliente/descargar", headers=_cabeceras(config)
    )
    with _abrir(peticion, config) as resp:
        contenido_nuevo = resp.read()

    # No se reemplaza nada sin comprobar antes que es Python válido: una
    # descarga a medias o un fallo del servidor no debe dejar el script roto
    # en disco, que es peor que quedarse en la versión vieja.
    try:
        compile(contenido_nuevo, "trackbitos.py", "exec")
    except SyntaxError as e:
        raise RuntimeError(f"lo descargado no es un trackbitos.py válido, no se toca nada: {e}")

    ruta_actual = Path(__file__).resolve()

    # Nada se pierde (invariante 6, igual que con los .blend): la versión que
    # tenías se aparta a la papelera antes de escribir la nueva, por si hay
    # que volver atrás a mano.
    apartada = a_papelera(ruta_actual)
    ruta_actual.write_bytes(contenido_nuevo)

    return apartada


def cmd_actualizar(args) -> int:
    """
    Comprobación y actualización explícitas, a petición. Con la
    actualización automática (fase 23) esto ya no hace falta para el día a
    día — cada ejecución se actualiza sola —, pero sigue sirviendo para
    forzarlo ya mismo (sin esperar al próximo comando) o para ver el error
    de verdad si la automática viene fallando en silencio.
    """
    config = cargar_config()

    remota = api_get(config, "/cliente/version").get("version")
    if not remota:
        raise RuntimeError("el servidor no ha devuelto ninguna versión.")

    if _version_tupla(remota) <= _version_tupla(VERSION):
        print(f"\nYa tienes la última versión (v{VERSION}).\n")
        return 0

    print(f"\nHay una versión nueva: v{VERSION} → v{remota}. Descargando...")
    apartada = _aplicar_actualizacion(config, remota)

    print(f"\n  ✓ Actualizado a v{remota}.")
    print(f"    La versión anterior quedó en {apartada}")
    print("\n  → Vuelve a ejecutar el comando: los cambios se aplican desde la próxima ejecución.\n")
    return 0


# --------------------------------------------------------------------------

def main(argv: Optional[list] = None) -> int:
    # En Windows, la consola por defecto no suele estar en UTF-8 y print()
    # revienta con los símbolos (✓ ⚠ ≠ →) en cuanto sale de ASCII. Se fuerza
    # aquí en vez de evitar esos símbolos: son los que pide la spec para que
    # el veredicto se lea de un vistazo.
    for flujo in (sys.stdout, sys.stderr):
        if hasattr(flujo, "reconfigure"):
            flujo.reconfigure(encoding="utf-8", errors="replace")

    parser = argparse.ArgumentParser(prog="trackbitos", description="Cliente de Piezas (Trackbitos).")
    subs = parser.add_subparsers(dest="comando", required=True)

    def con_dir(p):
        p.add_argument("--dir", default=".", help="Directorio de trabajo (por defecto, el actual).")
        return p

    # Alias cortos (spec: una letra para los de uso diario, dos para el
    # resto, sin que ninguno choque con otro): "trackbitos b <pieza>" en vez
    # de "trackbitos bajar <pieza>". El nombre completo se sigue aceptando
    # igual — esto es azúcar, no un reemplazo.
    p = con_dir(subs.add_parser("estado", aliases=["e"], help="Diagnóstico del directorio de trabajo actual. (alias: e)"))
    p.set_defaults(func=cmd_estado)

    # "abrir" existió como comando aparte hasta la fase 22; se fusionó en
    # "bajar", que ya distinguía internamente si había algo que descargar o
    # no (origen_descarga) y antes se limitaba a negarse en el segundo caso.
    # Alias "a" conservado por compatibilidad con la costumbre del comando
    # viejo, junto al "b" de siempre — los dos apuntan a lo mismo ahora.
    p = con_dir(subs.add_parser(
        "bajar", aliases=["b", "a"],
        help="Descarga la mesa de trabajo y abre sesión (o solo abre sesión, si la pieza está recién estrenada). (alias: b, a)"
    ))
    p.add_argument("variante", nargs="?", help="Nombre o id; si ya hay .sesion.json, se deduce.")
    p.add_argument("--ignorar-pendiente", action="store_true", dest="ignorar_pendiente",
                   help="Continuar aunque haya una descarga sin cerrar en otra máquina.")
    p.set_defaults(func=cmd_bajar)

    p = con_dir(subs.add_parser("ver", aliases=["v"], help="Descarga solo para mirar: no abre sesión. (alias: v)"))
    p.add_argument("variante", nargs="?", help="Nombre o id de la variante.")
    p.add_argument("--ignorar-pendiente", action="store_true", dest="ignorar_pendiente")
    p.set_defaults(func=cmd_ver)

    p = con_dir(subs.add_parser("subir", aliases=["s"], help="Sube el .blend de este directorio a su sesión. (alias: s)"))
    p.add_argument("--log", help='Nota de la sesión (p.ej. "guardado sin cambios de geometría").')
    p.set_defaults(func=cmd_subir)

    p = con_dir(subs.add_parser("cerrar", aliases=["c"], help="Cierra la sesión (o la descarga, con --sin-cambios). (alias: c)"))
    p.add_argument("--sin-cambios", action="store_true", dest="sin_cambios",
                   help="Cierra la descarga sin subir: exige que el fichero siga siendo el entregado.")
    p.set_defaults(func=cmd_cerrar)

    p = con_dir(subs.add_parser("promocionar", aliases=["p"], help="Congela la última sesión subida como versión nueva. (alias: p)"))
    p.add_argument("--cambio", help="Obligatorio: qué se modificó, en una línea.")
    p.add_argument("--medidas", help="Cotas de calibre relevantes.")
    p.set_defaults(func=cmd_promocionar)

    p = subs.add_parser("papelera", aliases=["pa"], help="Qué hay apartado y cuándo caduca. (alias: pa)")
    p.set_defaults(func=cmd_papelera)

    p = subs.add_parser("catalogo", aliases=["ca"], help="El catálogo completo: qué piezas hay y por dónde va cada una. (alias: ca)")
    p.set_defaults(func=cmd_catalogo)

    p = subs.add_parser("variantes", aliases=["va"], help="Cuántas variantes tiene una pieza y cómo se llama cada una. (alias: va)")
    p.add_argument("pieza", help="Nombre o parte del nombre de la pieza.")
    p.set_defaults(func=cmd_variantes)

    p = subs.add_parser("actualizar", aliases=["ac"], help="Comprueba si hay una versión nueva del cliente y, si la hay, se reemplaza. (alias: ac)")
    p.set_defaults(func=cmd_actualizar)

    args = parser.parse_args(argv)

    # Lo apartado caduca a los 30 días. Se aprovecha cualquier ejecución en
    # vez de montar un cron por máquina, y en silencio: el usuario vino a
    # otra cosa, y enterarse de que se borró algo que ya no quería no aporta.
    purgar_papelera()

    try:
        resultado = args.func(args)
    except RuntimeError as e:
        print(f"\n  ⚠ {e}\n", file=sys.stderr)
        resultado = 2

    # Actualización automática (fase 23): antes solo avisaba y exigía
    # "trackbitos actualizar" a mano; ahora se aplica ella sola. Se dispara
    # después del comando (nunca antes, para no añadirle latencia a lo que
    # el usuario vino a hacer de verdad) y en silencio ante cualquier fallo
    # — un tropiezo actualizándose no debe ensombrecer el resultado del
    # comando real, que es lo que importaba de esta ejecución. "actualizar"
    # (o su alias "ac") se salta esto porque ya lo acaba de hacer por su
    # cuenta, con sus propios mensajes. Se compara la función resuelta, no
    # args.comando: con alias, args.comando guarda lo que se escribió de
    # verdad ("ac"), no el nombre canónico, así que comparar contra la
    # cadena "actualizar" se rompería.
    if args.func is not cmd_actualizar:
        try:
            config_actual = cargar_config()
            nueva = comprobar_version_remota(config_actual)
            if nueva:
                _aplicar_actualizacion(config_actual, nueva)
                print(f"\n  ✓ trackbitos se actualizó solo: v{VERSION} → v{nueva}. "
                      "Se aplica desde la próxima ejecución.\n")
        except Exception:
            pass

    return resultado


if __name__ == "__main__":
    sys.exit(main())
