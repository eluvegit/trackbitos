#!/usr/bin/env python3
"""
Exportación .blend -> STL de piezas-cli (Trackbitos) — plan .blend->STL,
decisiones LOCKED del 2026-09-01/02.

Aparte de trackbitos.py (que es stdlib-only y máquina de estados de
sesiones) porque éste necesita Blender y es batch sin estado: comparte
config y helpers de red importándolos de allí (mismo ~/.trackbitos/config.json,
mismo token). Su propia config, `stl.config.json`, es POR MÁQUINA (Windows y
Mac tienen cada una la suya, nada se sincroniza) — decisión 8 del plan.

El .blend es la autoridad absoluta (decisión 3): este script SIEMPRE
regenera el STL desde el .blend, nunca confía en los STL ya subidos al
servidor. Una pieza "compuesta" (tiene componentes anotados en
piezas_composiciones) no tiene geometría propia — decisión 11 — así que no
se exporta su .blend: se deja constancia de qué componentes usa
(composicion.json), y el STL de cada componente vive en la carpeta propia de
ESE componente, porque `biblioteca` recorre el catálogo entero.
"""
from __future__ import annotations

import argparse
import json
import re
import shutil
import subprocess
import sys
import tempfile
import unicodedata
from datetime import datetime
from pathlib import Path
from typing import Optional

from trackbitos import (
    CONFIG_DIR,
    ENCODING_LECTURA,
    api_descargar,
    api_get,
    cargar_config,
    nombre_completo,
    resolver_variante,
    sha256_de,
)

VERSION = "0.1.0"

STL_CONFIG_PATH = CONFIG_DIR / "stl.config.json"
EXPORT_SCRIPT = Path(__file__).resolve().parent / "export_batch.py"

# Nombres de las subcarpetas de salida bajo el directorio de invocación
# (--dir, o el actual) — NO rutas fijas por máquina. Cada conjunto de
# piezas vive donde el usuario ya las organiza (p.ej. "R:\PIEZAS
# PLAYMOBIL\STL"), igual que trackbitos.py ya hace con `--dir` para las
# mesas de trabajo. Ver resolver_backup_dir()/resolver_placas_dir().
NOMBRE_CARPETA_BIBLIOTECA = "biblioteca"
NOMBRE_CARPETA_PLACAS = "placas"
DEFAULT_ESCALA = 10.0

# Papelera LOCAL de `generar` (2026-09-02): cuando una pieza sube de
# versión, la carpeta de entrega vieja de esa misma pieza en el mismo
# directorio se aparta aquí en vez de quedarse suelta o borrarse — mismo
# invariante 6 que ya sigue trackbitos.py con sus .blend locales
# (a_papelera()), pero local al directorio de trabajo, no global a
# ~/.trackbitos: la salida ya no es fija por máquina (ver más arriba).
NOMBRE_PAPELERA_ENTREGAS = ".papelera-stl"
DIAS_PAPELERA_ENTREGAS = 30
MARCADOR_ENTREGA = ".entrega.json"

BLENDER_TIMEOUT_SEGUNDOS = 600


# --------------------------------------------------------------------------
# Config por máquina (stl.config.json)
# --------------------------------------------------------------------------

def autodetectar_blender() -> Optional[str]:
    """
    Rutas estándar de instalación (decisión 8: "autodetectada si es
    estándar"). Si Blender se instaló en otro sitio, o hay varias versiones
    y no se quiere la más reciente, `stl.config.json` se edita a mano — no
    hay UI para esto, es un fichero de texto por máquina.
    """
    if sys.platform.startswith("win"):
        base = Path("C:/Program Files/Blender Foundation")
        if base.is_dir():
            candidatos = sorted(base.glob("Blender */blender.exe"), reverse=True)
            if candidatos:
                return str(candidatos[0])
    elif sys.platform == "darwin":
        candidato = Path("/Applications/Blender.app/Contents/MacOS/Blender")
        if candidato.is_file():
            return str(candidato)

    return None


def cargar_stl_config() -> dict:
    """
    Si falta, se crea sola con lo que se pueda autodetectar — igual que
    cargar_config() genera el uuid de máquina solo (spec 4.5) — pero a
    diferencia de eso SÍ se avisa de lo que se puso: una ruta de Blender mal
    detectada rompe cualquier comando que exporte, y aquí no hay margen para
    that "ya se corregirá sola".

    `backup_dir`/`placas_dir` NO se fijan aquí (corregido 2026-09-02): por
    defecto la salida sigue el directorio desde el que se invoca cada
    comando (ver resolver_backup_dir()/resolver_placas_dir()), no una ruta
    única por máquina — cada conjunto de piezas vive donde el usuario ya lo
    tiene organizado. Solo `blender` y `escala` son de verdad "de esta
    máquina" y se quedan fijos aquí.
    """
    if STL_CONFIG_PATH.is_file():
        return json.loads(STL_CONFIG_PATH.read_text(encoding=ENCODING_LECTURA))

    config = {
        "blender": autodetectar_blender(),
        "escala": DEFAULT_ESCALA,
    }
    guardar_stl_config(config)

    print(f"\n  · creado {STL_CONFIG_PATH} con:")
    print(f"      blender: {config['blender'] or '(no detectado — edita el fichero a mano)'}")
    print(f"      escala:  {config['escala']}")
    print("    (la carpeta de salida no se fija aquí: sigue el directorio desde el que")
    print("     invoques cada comando — usa --dir para cambiarlo, o añade \"backup_dir\"")
    print("     / \"placas_dir\" a este fichero si de verdad quieres una ruta fija.)\n")

    return config


def guardar_stl_config(config: dict) -> None:
    CONFIG_DIR.mkdir(parents=True, exist_ok=True)
    STL_CONFIG_PATH.write_text(json.dumps(config, indent=2, ensure_ascii=False), encoding="utf-8")


def exigir_blender(config: dict) -> str:
    blender = config.get("blender")
    if not blender or not Path(blender).is_file():
        raise RuntimeError(
            f"no hay un Blender válido configurado. Edita {STL_CONFIG_PATH} y pon la ruta"
            ' en "blender" (p.ej. "C:/Program Files/Blender Foundation/Blender 5.2/blender.exe").'
        )
    return blender


def resolver_backup_dir(stl_cfg: dict, base_dir: Path) -> Path:
    """
    Subcarpeta del directorio de invocación (`--dir`, o el actual) — no una
    ruta fija por máquina. Solo si `stl.config.json` trae "backup_dir" a
    mano se usa como override fijo, para quien de verdad quiera una única
    biblioteca centralizada en vez de una por carpeta de trabajo.
    """
    configurado = stl_cfg.get("backup_dir")
    return Path(configurado).resolve() if configurado else base_dir / NOMBRE_CARPETA_BIBLIOTECA


def resolver_placas_dir(stl_cfg: dict, base_dir: Path) -> Path:
    """Igual que resolver_backup_dir() pero para las placas."""
    configurado = stl_cfg.get("placas_dir")
    return Path(configurado).resolve() if configurado else base_dir / NOMBRE_CARPETA_PLACAS


def apartar_entregas_viejas(base_dir: Path, variante_id: int, numero_actual: int) -> list:
    """
    Antes de entregar una versión nueva de `generar` (decisión del usuario
    2026-09-02), aparta a la papelera local cualquier carpeta de entrega
    ANTERIOR de esta MISMA pieza que siga suelta en `base_dir`. Identifica
    "misma pieza" por `variante_id` dentro de MARCADOR_ENTREGA, no por el
    nombre visible de la carpeta — así no se confunde si la pieza cambia de
    nombre, y no toca ninguna carpeta que el usuario haya creado él mismo
    (esas no tienen el marcador, se ignoran sin más).

    Nada se borra (invariante 6, igual que a_papelera() en trackbitos.py):
    se mueve con marca de tiempo, y purgar_papelera_entregas() la limpia
    sola a los 30 días. Devuelve los nombres apartados.
    """
    if not base_dir.is_dir():
        return []

    especiales = {NOMBRE_CARPETA_BIBLIOTECA, NOMBRE_CARPETA_PLACAS, NOMBRE_PAPELERA_ENTREGAS}
    apartadas = []

    for hijo in base_dir.iterdir():
        if not hijo.is_dir() or hijo.name in especiales:
            continue

        marcador = _leer_json(hijo / MARCADOR_ENTREGA)
        if not marcador or marcador.get("variante_id") != variante_id or marcador.get("numero") == numero_actual:
            continue

        papelera = base_dir / NOMBRE_PAPELERA_ENTREGAS
        papelera.mkdir(exist_ok=True)
        destino = papelera / f"{datetime.now():%Y%m%d-%H%M%S}-{hijo.name}"
        shutil.move(str(hijo), str(destino))
        apartadas.append(hijo.name)

    return apartadas


def purgar_papelera_entregas(base_dir: Path, dias: int = DIAS_PAPELERA_ENTREGAS) -> list:
    """Lo apartado por apartar_entregas_viejas() caduca a los 30 días — mismo criterio que purgar_papelera() de trackbitos.py, pero con carpetas enteras en vez de ficheros sueltos."""
    papelera = base_dir / NOMBRE_PAPELERA_ENTREGAS
    if not papelera.is_dir():
        return []

    limite = datetime.now().timestamp() - (dias * 86400)
    purgadas = []
    for hijo in papelera.iterdir():
        try:
            if hijo.is_dir() and hijo.stat().st_mtime < limite:
                shutil.rmtree(hijo)
                purgadas.append(hijo.name)
        except OSError:
            # Una carpeta bloqueada no debe tumbar el comando que el
            # usuario venía a ejecutar: ya caducará en la siguiente.
            pass

    return purgadas


# --------------------------------------------------------------------------
# Nombres de carpeta y ficheros de estado
# --------------------------------------------------------------------------

def _slug(texto: Optional[str]) -> str:
    """Mismo criterio que Api::paraNombreDeArchivo() en el servidor: solo lo que sobrevive intacto a cualquier sistema de ficheros."""
    plano = unicodedata.normalize("NFKD", texto or "").encode("ascii", "ignore").decode()
    plano = re.sub(r"[^A-Za-z0-9_-]+", "-", plano).strip("-")
    return plano or "sin-nombre"


def carpeta_version(backup_dir: Path, categoria: Optional[str], familia: Optional[str], variante: str, numero: int) -> Path:
    """`backup_dir/<categoria>/<familia>/<variante>/vNNN` — decisión 10 del plan."""
    return backup_dir / _slug(categoria or "sin categoria") / _slug(familia) / _slug(variante) / f"v{numero:03d}"


def _leer_json(ruta: Path) -> Optional[dict]:
    if not ruta.is_file():
        return None
    try:
        return json.loads(ruta.read_text(encoding=ENCODING_LECTURA))
    except (json.JSONDecodeError, OSError):
        return None


def _escribir_json(ruta: Path, datos: dict) -> None:
    ruta.write_text(json.dumps(datos, indent=2, ensure_ascii=False), encoding="utf-8")


# --------------------------------------------------------------------------
# Blender
# --------------------------------------------------------------------------

def ejecutar_export(blender_exe: str, ruta_blend: Path, carpeta_salida: Path, escala: float) -> dict:
    """
    Un .blend a la vez, en background. El resultado se lee de la última
    línea de stdout con prefijo EXPORT_BATCH_RESULT: — el código de salida
    de Blender por sí solo no distingue "no había ninguna collection STL"
    (normal, se apunta en FALTAN.txt) de "reventó a mitad" (error real).
    """
    proceso = subprocess.run(
        [blender_exe, "--background", str(ruta_blend), "--python", str(EXPORT_SCRIPT), "--", str(carpeta_salida), str(escala)],
        capture_output=True, text=True, timeout=BLENDER_TIMEOUT_SEGUNDOS,
    )

    linea = next((l for l in reversed(proceso.stdout.splitlines()) if l.startswith("EXPORT_BATCH_RESULT:")), None)
    if proceso.returncode != 0 or linea is None:
        detalle = (proceso.stderr.strip() or proceso.stdout.strip() or "(sin salida)")[-2000:]
        raise RuntimeError(f"Blender falló exportando {ruta_blend.name}:\n    {detalle}")

    return json.loads(linea[len("EXPORT_BATCH_RESULT:"):])


# --------------------------------------------------------------------------
# biblioteca
# --------------------------------------------------------------------------

_TEXTO_RESULTADO = {
    "omitida": "sin cambios",
    "vacia": "vacía (sin collection \"STL\")",
    "exportada": "exportada",
    "referencia": "compuesta (solo referencia)",
}


def _procesar_simple(config: dict, blender_exe: str, escala: float, carpeta: Path, version: dict, sin_blend: bool) -> str:
    """
    Devuelve "omitida" (nada cambió desde la última vez), "vacia" (se
    exportó pero el .blend no tenía ninguna collection "STL" dentro) o
    "exportada".
    """
    anterior = _leer_json(carpeta / "manifest.json")
    if anterior and anterior.get("hash_blend") == version["hash_blend"] and anterior.get("script_version") == VERSION:
        return "omitida"

    carpeta.mkdir(parents=True, exist_ok=True)

    with tempfile.TemporaryDirectory() as tmp:
        ruta_blend_tmp = Path(tmp) / "origen.blend"
        entrega = api_descargar(config, f"/version/{version['id']}/blend", ruta_blend_tmp)
        if entrega.get("hash") and entrega["hash"] != version["hash_blend"]:
            raise RuntimeError(
                f"el .blend descargado no cuadra con el hash del catálogo "
                f"({entrega['hash'][:12]}... != {version['hash_blend'][:12]}...)."
            )

        resultado = ejecutar_export(blender_exe, ruta_blend_tmp, carpeta, escala)

        if not sin_blend:
            shutil.copy2(ruta_blend_tmp, carpeta / "origen.blend")

    _escribir_json(carpeta / "manifest.json", {
        "version_id": version["id"],
        "hash_blend": version["hash_blend"],
        "generado_en": datetime.now().isoformat(timespec="seconds"),
        "blender_version": resultado.get("blender_version"),
        "collections": resultado.get("exportados", []),
        "script_version": VERSION,
    })

    return "vacia" if not resultado.get("exportados") else "exportada"


def _procesar_compuesta(config: dict, carpeta: Path, version: dict, componentes: list, sin_blend: bool) -> str:
    """
    Sin geometría propia (decisión 11): no se exporta el .blend de esta
    pieza. Se deja constancia de qué componentes usa ahora mismo
    (composicion.json) — el STL de cada componente vive en la carpeta propia
    de ese componente, porque `biblioteca` recorre el catálogo entero y ya
    la genera al llegarle su turno. Devuelve "omitida" o "referencia".
    """
    firma = sorted(
        [c["variante_id"], (c.get("version_vigente") or {}).get("hash_blend") or ""]
        for c in componentes
    )

    anterior = _leer_json(carpeta / "composicion.json")
    if (anterior
            and anterior.get("hash_blend") == version["hash_blend"]
            and anterior.get("script_version") == VERSION
            and anterior.get("firma_componentes") == firma):
        return "omitida"

    carpeta.mkdir(parents=True, exist_ok=True)

    if not sin_blend:
        with tempfile.TemporaryDirectory() as tmp:
            ruta_tmp = Path(tmp) / "origen.blend"
            api_descargar(config, f"/version/{version['id']}/blend", ruta_tmp)
            shutil.copy2(ruta_tmp, carpeta / "origen.blend")

    _escribir_json(carpeta / "composicion.json", {
        "version_id": version["id"],
        "hash_blend": version["hash_blend"],
        "generado_en": datetime.now().isoformat(timespec="seconds"),
        "script_version": VERSION,
        "firma_componentes": firma,
        "nota": "Pieza compuesta: sin geometría propia. El STL de cada componente vive en la "
                "carpeta propia de ese componente dentro de esta misma biblioteca.",
        "componentes": [
            {
                "variante_id": c["variante_id"],
                "nombre": (f"{c['familia']} / {c['variante']}" if c.get("familia") else c.get("variante")),
                "version_vigente": (c.get("version_vigente") or {}).get("etiqueta"),
                "hash_blend": (c.get("version_vigente") or {}).get("hash_blend"),
            }
            for c in componentes
        ],
    })

    return "referencia"


def cmd_biblioteca(args) -> int:
    config = cargar_config()
    stl_cfg = cargar_stl_config()
    blender_exe = exigir_blender(stl_cfg)
    backup_dir = resolver_backup_dir(stl_cfg, Path(args.dir).resolve())
    escala = float(stl_cfg.get("escala") or DEFAULT_ESCALA)

    catalogo = api_get(config, "/variantes")
    variantes = catalogo.get("variantes", [])
    if not variantes:
        print("\n  · no hay ninguna variante todavía en el catálogo.\n")
        return 0

    exportadas, omitidas, referencias, sin_version, vacias, errores = [], [], [], [], [], []

    print(f"\n  {len(variantes)} piezas en el catálogo. Puede tardar varios minutos la primera vez"
          " (las siguientes, con salto incremental, son mucho más rápidas).\n")

    # Progreso por pieza (2026-09-02): con el catálogo entero cada vuelta
    # puede tardar bastante — descarga + Blender en background por pieza —
    # y sin nada impreso mientras tanto la terminal parece colgada. Cada
    # línea termina en su veredicto en cuanto se sabe, no al final.
    total = len(variantes)
    for i, v in enumerate(variantes, 1):
        etiqueta = nombre_completo(v)
        print(f"  [{i}/{total}] {etiqueta}...", end="", flush=True)

        version = v.get("version_para_imprimir")
        if not version:
            sin_version.append(etiqueta)
            print(" sin versión para imprimir")
            continue

        carpeta = carpeta_version(backup_dir, v.get("categoria_nombre"), v.get("familia_nombre"), v["nombre"], version["numero"])

        try:
            componentes = api_get(config, f"/variante/{v['id']}/composicion").get("componentes", [])
        except RuntimeError as e:
            errores.append(f"{etiqueta}: no se pudo consultar su composición ({e})")
            print(f" ERROR: {e}")
            continue

        try:
            if componentes:
                resultado = _procesar_compuesta(config, carpeta, version, componentes, args.sin_blend)
            else:
                resultado = _procesar_simple(config, blender_exe, escala, carpeta, version, args.sin_blend)
        except (RuntimeError, subprocess.TimeoutExpired) as e:
            errores.append(f"{etiqueta}: {e}")
            print(f" ERROR: {e}")
            continue

        print(f" {_TEXTO_RESULTADO[resultado]}")
        {
            "omitida": omitidas,
            "referencia": referencias,
            "vacia": vacias,
            "exportada": exportadas,
        }[resultado].append(etiqueta)

    _veredicto_biblioteca(backup_dir, exportadas, omitidas, referencias, sin_version, vacias, errores)

    return 0 if not errores else 1


def _veredicto_biblioteca(backup_dir, exportadas, omitidas, referencias, sin_version, vacias, errores) -> None:
    print(f"\n  Biblioteca en {backup_dir}\n")
    print(f"  ✓ {len(exportadas)} exportada(s)")
    print(f"  · {len(omitidas)} sin cambios, omitida(s)")
    if referencias:
        print(f"  → {len(referencias)} compuesta(s): solo composicion.json (+ .blend si no había --sin-blend)")
    if vacias:
        print(f"  ⚠ {len(vacias)} con .blend pero sin ninguna collection \"STL\" dentro:")
        for nombre in vacias:
            print(f"      - {nombre}")
    if sin_version:
        print(f"  · {len(sin_version)} sin ninguna versión para imprimir todavía")
    if errores:
        print(f"\n  ✗ {len(errores)} con error:")
        for e in errores:
            print(f"      - {e}")
    print()


# --------------------------------------------------------------------------
# generar
# --------------------------------------------------------------------------

def cmd_generar(args) -> int:
    """
    El STL de una sola pieza, entregado en una carpeta simple con su nombre
    y versión (`<Familia>-<Variante>-vNNN/`) — no la ruta anidada de
    `biblioteca`. Reutiliza la misma caché por debajo (`_asegurar_stl_en_
    biblioteca`, igual que `placa`): si ya estaba generada y el hash de la
    versión no ha cambiado, se copia tal cual, sin volver a invocar Blender
    ("menos proceso", palabras del usuario 2026-09-02).

    "Al corriente con la web" no es una comprobación aparte: `resolver_
    variante()` pide `/variantes` fresco en cada ejecución (no hay ningún
    puntero local que pueda quedarse viejo), así que la versión y su
    hash_blend son siempre los que el servidor tiene AHORA MISMO — el salto
    por hash de la caché ya compara contra eso.

    Si la pieza es compuesta, se expande igual que en `placa` (decisión 11:
    siempre se expande) y la carpeta de salida lleva el STL de cada
    componente, con el nombre del componente delante para no mezclarlos.
    """
    config = cargar_config()
    stl_cfg = cargar_stl_config()
    blender_exe = exigir_blender(stl_cfg)
    base_dir = Path(args.dir).resolve()
    backup_dir = resolver_backup_dir(stl_cfg, base_dir)
    escala = float(stl_cfg.get("escala") or DEFAULT_ESCALA)

    variante = resolver_variante(config, args.pieza)
    etiqueta = nombre_completo(variante)

    version = variante.get("version_para_imprimir")
    if not version:
        print(f"\n  · {etiqueta} todavía no tiene ninguna versión para imprimir.\n")
        return 0

    print(f"\n  {etiqueta}: v{version['numero']:03d} ({version['estado']}) es la versión vigente en el servidor ahora mismo.\n")

    nodo = {
        "variante_id": variante["id"],
        "familia": variante.get("familia_nombre"),
        "variante": variante["nombre"],
        "categoria": variante.get("categoria_nombre"),
        "version": version,
    }
    hojas, avisos = _expandir_placa(config, [nodo], como_anotado=False)

    # Purga lo apartado hace 30+ días y aparta ahora cualquier carpeta de
    # entrega ANTERIOR de esta misma pieza que siga suelta en base_dir —
    # antes de crear la nueva, para no dejar dos versiones sueltas
    # confundibles en el mismo sitio (ver conversación 2026-09-02).
    purgar_papelera_entregas(base_dir)
    apartadas = apartar_entregas_viejas(base_dir, variante["id"], version["numero"])
    for nombre in apartadas:
        print(f"  · versión anterior de esta pieza apartada a {NOMBRE_PAPELERA_ENTREGAS}/: {nombre}")

    carpeta_salida = base_dir / f"{_slug(variante.get('familia_nombre'))}-{_slug(variante['nombre'])}-v{version['numero']:03d}"
    carpeta_salida.mkdir(parents=True, exist_ok=True)

    varias_hojas = len(hojas) > 1
    faltantes, copiados, reutilizados = [], 0, 0

    total = len(hojas)
    for i, hoja in enumerate(hojas, 1):
        print(f"  [{i}/{total}] {_etiqueta_nodo(hoja)}...", end="", flush=True)
        try:
            carpeta_cache, resultado = _asegurar_stl_en_biblioteca(config, blender_exe, escala, backup_dir, hoja, sin_blend=args.sin_blend)
        except (RuntimeError, subprocess.TimeoutExpired) as e:
            faltantes.append(f"{_etiqueta_nodo(hoja)}: {e}")
            print(f" ERROR: {e}")
            continue

        stls = sorted(carpeta_cache.glob("*.stl"))
        if not stls:
            faltantes.append(f"{_etiqueta_nodo(hoja)}: sin ninguna collection \"STL\" en el .blend.")
            print(" sin STL")
            continue

        if resultado == "omitida":
            reutilizados += 1
            print(f" {len(stls)} STL (ya estaba en la biblioteca, copiado sin reprocesar)")
        else:
            print(f" {len(stls)} STL (exportado ahora)")

        for stl in stls:
            nombre_final = f"{_slug(hoja['familia'])}-{_slug(hoja['variante'])}-{stl.name}" if varias_hojas else stl.name
            shutil.copy2(stl, carpeta_salida / nombre_final)
            copiados += 1

    faltantes.extend(avisos)

    # Marcador oculto para que una futura entrega de esta misma pieza (por
    # variante_id, no por el nombre visible) sepa apartar ÉSTA cuando ya no
    # sea la vigente — ver apartar_entregas_viejas().
    _escribir_json(carpeta_salida / MARCADOR_ENTREGA, {
        "variante_id": variante["id"],
        "numero": version["numero"],
        "hash_blend": version["hash_blend"],
        "generado_en": datetime.now().isoformat(timespec="seconds"),
    })

    print(f"\n  {etiqueta} v{version['numero']:03d} en {carpeta_salida}\n")
    if varias_hojas:
        print(f"  (pieza compuesta, {total} componente(s) expandido(s))")
    print(f"  ✓ {copiados} STL" + (f" ({reutilizados} sin reprocesar)" if reutilizados else ""))
    if faltantes:
        print(f"  ⚠ {len(faltantes)} con problema:")
        for f in faltantes:
            print(f"      - {f}")
    print()

    return 0 if copiados > 0 and not faltantes else 1


# --------------------------------------------------------------------------
# placa
# --------------------------------------------------------------------------

def resolver_placa(config: dict, texto: str) -> dict:
    """
    Mismo espíritu que resolver_variante() de trackbitos.py, pero más
    simple: el histórico de placas no crece tan rápido como el catálogo de
    piezas, así que "el texto está contenido en el nombre" basta — no hace
    falta la escalera de coincidencias exactas.
    """
    placas = api_get(config, "/placas").get("placas", [])
    if not placas:
        raise RuntimeError("no hay ninguna placa en el histórico todavía.")

    if texto.isdigit():
        exactas = [p for p in placas if p["id"] == int(texto)]
    else:
        texto_l = texto.lower()
        exactas = [p for p in placas if texto_l in p["nombre"].lower()]

    if len(exactas) == 1:
        return exactas[0]

    def _listado(items: list) -> str:
        return "\n".join(f"    {p['id']:>4}  {p['nombre']}  ({p['fecha']})" for p in items)

    if not exactas:
        raise RuntimeError(f"no hay ninguna placa que sea '{texto}'. Placas disponibles:\n\n{_listado(placas)}\n")

    raise RuntimeError(f"'{texto}' encaja con varias:\n\n{_listado(exactas)}\n\n    Concreta con el id.")


def _etiqueta_nodo(nodo: dict) -> str:
    return f"{nodo['familia']} / {nodo['variante']}" if nodo.get("familia") else (nodo.get("variante") or "?")


def _expandir_placa(config: dict, nodos: list, como_anotado: bool) -> tuple:
    """
    Expande recursivamente las piezas compuestas de una placa (decisión 11:
    "compuesta de" es SIEMPRE la suma de sus componentes, sin geometría
    propia — se expande siempre, sin interruptor) hasta quedarse solo con
    piezas simples (hojas): las que de verdad tienen un .blend con
    geometría que exportar.

    Detecta ciclos de composición (decisión 13: A compuesta de B, B
    compuesta de A — el servidor hoy solo impide componerse de sí misma, no
    ciclos transitivos) y aborta esa rama en concreto con un aviso, sin
    tirar el resto de la placa por un dato mal anotado en otro sitio.

    Dedup (decisión 14): una misma variante alcanzada por dos caminos
    distintos de la misma placa sale una sola vez, con la primera versión
    con la que se encontró en el recorrido.

    `como_anotado` decide, en cada nivel de la recursión, si un componente
    se resuelve con su `version_vigente` (por defecto) o su
    `version_anotada` (--como-anotado, decisión 15: para reproducir una
    placa vieja bit a bit).

    Devuelve (hojas, avisos).
    """
    hojas: list = []
    vistos: set = set()
    avisos: list = []

    def visitar(nodo: dict, cadena: list) -> None:
        vid = nodo["variante_id"]
        if vid in cadena:
            ruta = " -> ".join(str(x) for x in cadena + [vid])
            avisos.append(f"ciclo de composición ({ruta}): se omite esta rama.")
            return

        try:
            componentes = api_get(config, f"/variante/{vid}/composicion").get("componentes", [])
        except RuntimeError as e:
            avisos.append(f"{_etiqueta_nodo(nodo)}: no se pudo consultar su composición ({e}).")
            return

        if not componentes:
            if vid in vistos:
                return  # dedup: ya se añadió por otro camino de la misma placa
            vistos.add(vid)
            hojas.append(nodo)
            return

        for c in componentes:
            comp_vid = c.get("variante_id")
            comp_version = c.get("version_anotada" if como_anotado else "version_vigente")
            if comp_vid is None or not comp_version:
                cual = "anotada" if como_anotado else "vigente"
                avisos.append(f"{_etiqueta_nodo(nodo)}: un componente ya no tiene versión {cual} (¿borrada?) — se omite.")
                continue
            visitar({
                "variante_id": comp_vid,
                "familia": c.get("familia"),
                "variante": c.get("variante"),
                "categoria": c.get("categoria"),
                "version": comp_version,
            }, cadena + [vid])

    for nodo in nodos:
        visitar(nodo, [])

    return hojas, avisos


def _asegurar_stl_en_biblioteca(config: dict, blender_exe: str, escala: float, backup_dir: Path, hoja: dict, sin_blend: bool = True) -> tuple:
    """
    Reutiliza la caché de `biblioteca` (misma carpeta, mismo manifest.json,
    mismo salto incremental) en vez de exportar de cero: si esta pieza+
    versión ya se generó al construir `biblioteca`, al montar otra placa o
    con un `generar` anterior, no se vuelve a invocar Blender — no es solo
    ahorro de espacio (la motivación original del plan), también de tiempo.
    La versión que se compara es la que ACABA de llegar de la API (fresca,
    no un puntero local viejo), así que el salto por hash ya implica "esto
    sigue siendo lo que hay ahora mismo en el servidor" — no hace falta
    ninguna comprobación aparte para saber que está al corriente con la web.

    `sin_blend` decide si se persiste una copia del `.blend` en la caché;
    por defecto True porque quien más llama a esto es `placa` (decisión 7:
    "placa nunca baja .blend"), pero `generar` puede pasar False si el
    usuario no puso `--sin-blend`.

    Devuelve (carpeta, resultado) — resultado como en _procesar_simple():
    "omitida" (ya estaba, se copia tal cual), "vacia" o "exportada".
    """
    carpeta = carpeta_version(backup_dir, hoja["categoria"], hoja["familia"], hoja["variante"], hoja["version"]["numero"])
    resultado = _procesar_simple(config, blender_exe, escala, carpeta, hoja["version"], sin_blend=sin_blend)
    return carpeta, resultado


def cmd_placa(args) -> int:
    config = cargar_config()
    stl_cfg = cargar_stl_config()
    blender_exe = exigir_blender(stl_cfg)
    base_dir = Path(args.dir).resolve()
    backup_dir = resolver_backup_dir(stl_cfg, base_dir)
    placas_dir = resolver_placas_dir(stl_cfg, base_dir)
    escala = float(stl_cfg.get("escala") or DEFAULT_ESCALA)

    placa = resolver_placa(config, args.placa)
    filas = api_get(config, f"/placa/{placa['id']}").get("versiones", [])
    if not filas:
        print(f"\n  · la placa \"{placa['nombre']}\" está vacía, no hay nada que exportar.\n")
        return 0

    nodos = [{
        "variante_id": f["variante_id"],
        "familia": f["familia"],
        "variante": f["variante"],
        "categoria": f["categoria"],
        "version": {"id": f["version_id"], "hash_blend": f["hash_blend"], "numero": f["numero"], "estado": f["estado"]},
    } for f in filas]

    hojas, avisos = _expandir_placa(config, nodos, args.como_anotado)

    carpeta_salida = placas_dir / _slug(placa["nombre"])
    carpeta_salida.mkdir(parents=True, exist_ok=True)

    bitacora, faltantes, copiados = [], [], 0

    # Mismo progreso por pieza que `biblioteca`/`revisar` (2026-09-02).
    total = len(hojas)
    for i, hoja in enumerate(hojas, 1):
        print(f"  [{i}/{total}] {_etiqueta_nodo(hoja)}...", end="", flush=True)

        try:
            carpeta_cache, _resultado = _asegurar_stl_en_biblioteca(config, blender_exe, escala, backup_dir, hoja)
        except (RuntimeError, subprocess.TimeoutExpired) as e:
            faltantes.append(f"{_etiqueta_nodo(hoja)} v{hoja['version']['numero']:03d}: {e}")
            print(f" ERROR: {e}")
            continue

        stls = sorted(carpeta_cache.glob("*.stl"))
        if not stls:
            faltantes.append(f"{_etiqueta_nodo(hoja)} v{hoja['version']['numero']:03d}: sin ninguna collection \"STL\" en el .blend.")
            print(" sin STL")
            continue

        print(f" {len(stls)} STL")
        for stl in stls:
            nombre_final = f"{_slug(hoja['familia'])}-{_slug(hoja['variante'])}-v{hoja['version']['numero']:03d}-{_slug(stl.stem)}.stl"
            shutil.copy2(stl, carpeta_salida / nombre_final)
            copiados += 1
            bitacora.append(f"{nombre_final}\n    de: {_etiqueta_nodo(hoja)} v{hoja['version']['numero']:03d} ({hoja['version']['estado']})")

    faltantes.extend(avisos)

    (carpeta_salida / "placa.txt").write_text(
        f"Placa \"{placa['nombre']}\" ({placa['fecha']})\n"
        f"Generada: {datetime.now().isoformat(timespec='seconds')}\n"
        f"{'Versión anotada (--como-anotado)' if args.como_anotado else 'Versión vigente'} de cada componente.\n\n"
        + "\n".join(bitacora) + "\n",
        encoding="utf-8",
    )
    if faltantes:
        (carpeta_salida / "FALTAN.txt").write_text(
            "Piezas de esta placa sin STL en la carpeta:\n\n" + "\n".join(faltantes) + "\n",
            encoding="utf-8",
        )

    print(f"\n  Placa \"{placa['nombre']}\" en {carpeta_salida}\n")
    print(f"  ✓ {copiados} STL")
    if faltantes:
        print(f"  ⚠ {len(faltantes)} con problema (ver FALTAN.txt):")
        for f in faltantes:
            print(f"      - {f.splitlines()[0]}")
    print()

    return 0 if not faltantes else 1


# --------------------------------------------------------------------------
# revisar
# --------------------------------------------------------------------------

def cmd_revisar(args) -> int:
    """
    Recorre el catálogo entero comparando lo que stl.py generaría ahora
    desde cada .blend contra lo que ya hay subido a mano al servidor
    (`piezas_version_stls`). Decisión 3: el .blend es la autoridad absoluta,
    pero los dos sistemas conviven a propósito — esto no fusiona nada, solo
    avisa cuándo han divergido (el .blend cambió sin resubir, o al revés).

    También aplica el guardarraíl de la decisión 12: si una pieza tiene
    componentes anotados (`piezas_composiciones`) Y ADEMÁS ya tiene algún
    STL subido a mano, se señala en vez de asumir en silencio que sigue
    siendo "caso 2" (compuesta sin geometría propia, decisión 11) — es la
    señal barata de "esto podría tener geometría propia" sin invocar
    Blender para piezas que, por diseño, ni siquiera se exportan.
    """
    config = cargar_config()
    stl_cfg = cargar_stl_config()
    blender_exe = exigir_blender(stl_cfg)
    backup_dir = resolver_backup_dir(stl_cfg, Path(args.dir).resolve())
    escala = float(stl_cfg.get("escala") or DEFAULT_ESCALA)

    variantes = api_get(config, "/variantes").get("variantes", [])
    if not variantes:
        print("\n  · no hay ninguna variante todavía en el catálogo.\n")
        return 0

    ok, divergencias, solo_local, solo_servidor = [], [], [], []
    guardarrail, vacias, sin_version, errores = [], [], [], []

    print(f"\n  {len(variantes)} piezas en el catálogo. Puede tardar varios minutos.\n")

    # Mismo progreso por pieza que `biblioteca` (2026-09-02): esto también
    # recorre el catálogo entero invocando Blender pieza a pieza.
    total = len(variantes)
    for i, v in enumerate(variantes, 1):
        etiqueta = nombre_completo(v)
        print(f"  [{i}/{total}] {etiqueta}...", end="", flush=True)

        version = v.get("version_para_imprimir")
        if not version:
            sin_version.append(etiqueta)
            print(" sin versión para imprimir")
            continue

        try:
            componentes = api_get(config, f"/variante/{v['id']}/composicion").get("componentes", [])
            subidos = {s["nombre"]: s["hash"] for s in api_get(config, f"/variante/{v['id']}/stls").get("stls", [])}
        except RuntimeError as e:
            errores.append(f"{etiqueta}: {e}")
            print(f" ERROR: {e}")
            continue

        if componentes:
            if subidos:
                guardarrail.append(
                    f"{etiqueta} v{version['numero']:03d}: tiene {len(componentes)} componente(s) Y "
                    f"{len(subidos)} STL subido(s) a mano — revisa si de verdad sigue siendo 'caso 2'."
                )
                print(" ⚠ componentes Y STL a la vez")
            else:
                print(" compuesta, sin STL subido")
            continue

        carpeta = carpeta_version(backup_dir, v.get("categoria_nombre"), v.get("familia_nombre"), v["nombre"], version["numero"])
        try:
            _procesar_simple(config, blender_exe, escala, carpeta, version, sin_blend=True)
        except (RuntimeError, subprocess.TimeoutExpired) as e:
            errores.append(f"{etiqueta}: {e}")
            print(f" ERROR: {e}")
            continue

        generados = {stl.stem: sha256_de(stl) for stl in carpeta.glob("*.stl")}
        if not generados:
            nota = f" (hay {len(subidos)} STL subido(s) sin nada que comparar)" if subidos else ""
            vacias.append(f"{etiqueta} v{version['numero']:03d}: sin collection \"STL\" en el .blend{nota}.")
            print(" vacía")
            continue

        divergio = False
        for nombre in sorted(set(generados) | set(subidos)):
            local_hash = generados.get(nombre)
            servidor_hash = subidos.get(nombre)
            etiqueta_pieza = f"{etiqueta} v{version['numero']:03d} / {nombre}"

            if local_hash and servidor_hash:
                if local_hash == servidor_hash:
                    ok.append(etiqueta_pieza)
                else:
                    divergencias.append(f"{etiqueta_pieza}: .blend={local_hash[:12]}... servidor={servidor_hash[:12]}...")
                    divergio = True
            elif local_hash:
                solo_local.append(f"{etiqueta_pieza}: el .blend lo genera, nunca se subió (o se subió con otro nombre).")
            else:
                solo_servidor.append(f"{etiqueta_pieza}: subido a mano, el .blend ya no lo genera con ese nombre.")
        print(" DIVERGE" if divergio else " ok")

    _veredicto_revisar(ok, divergencias, solo_local, solo_servidor, guardarrail, vacias, sin_version, errores)

    return 0 if not (divergencias or guardarrail or errores) else 1


def _veredicto_revisar(ok, divergencias, solo_local, solo_servidor, guardarrail, vacias, sin_version, errores) -> None:
    print("\n  Revisión del catálogo\n")
    print(f"  ✓ {len(ok)} coinciden (.blend y servidor de acuerdo)")
    if divergencias:
        print(f"\n  ✗ {len(divergencias)} DIVERGEN (el .blend cambió sin resubir el STL, o al revés):")
        for d in divergencias:
            print(f"      - {d}")
    if guardarrail:
        print(f"\n  ⚠ {len(guardarrail)} con componentes Y STL subido a la vez (decisión 12):")
        for g in guardarrail:
            print(f"      - {g}")
    if solo_local:
        print(f"\n  · {len(solo_local)} solo en el .blend, nunca subidos:")
        for s in solo_local:
            print(f"      - {s}")
    if solo_servidor:
        print(f"\n  · {len(solo_servidor)} solo subidos, el .blend ya no los genera:")
        for s in solo_servidor:
            print(f"      - {s}")
    if vacias:
        print(f"\n  · {len(vacias)} sin nada que comparar:")
        for vv in vacias:
            print(f"      - {vv}")
    if sin_version:
        print(f"\n  · {len(sin_version)} sin ninguna versión para imprimir todavía")
    if errores:
        print(f"\n  ✗ {len(errores)} con error:")
        for e in errores:
            print(f"      - {e}")
    print()


# --------------------------------------------------------------------------
# CLI
# --------------------------------------------------------------------------

def main(argv: Optional[list] = None) -> int:
    for flujo in (sys.stdout, sys.stderr):
        if hasattr(flujo, "reconfigure"):
            flujo.reconfigure(encoding="utf-8", errors="replace")

    parser = argparse.ArgumentParser(prog="stl", description="Exportación .blend -> STL de piezas-cli (Trackbitos).")
    subs = parser.add_subparsers(dest="comando", required=True)

    def con_dir(p):
        # Mismo patrón que trackbitos.py: la salida sigue el directorio
        # desde el que se invoca (biblioteca/placas como subcarpetas), no
        # una ruta fija por máquina — cada conjunto de piezas vive donde el
        # usuario ya las organiza.
        p.add_argument("--dir", default=".", help="Carpeta base para la salida (biblioteca/placas). Por defecto, la actual.")
        return p

    p = con_dir(subs.add_parser(
        "biblioteca", aliases=["b"],
        help="Backup local de STL + .blend de todo el catálogo, con salto incremental por hash. (alias: b)",
    ))
    p.add_argument("--sin-blend", action="store_true", dest="sin_blend", help="No guardar copia del .blend, solo los STL.")
    p.set_defaults(func=cmd_biblioteca)

    p = con_dir(subs.add_parser(
        "generar", aliases=["g"],
        help="Exporta solo una pieza (rápido, sin recorrer el catálogo entero) — para probar un .blend recién preparado. (alias: g)",
    ))
    p.add_argument("pieza", help="Nombre o id de la pieza (mismo criterio que trackbitos.py).")
    p.add_argument("--sin-blend", action="store_true", dest="sin_blend", help="No guardar copia del .blend, solo los STL.")
    p.set_defaults(func=cmd_generar)

    p = con_dir(subs.add_parser(
        "placa", aliases=["p"],
        help="STL de una placa (id o nombre) en una carpeta plana, lista para ChituBox. (alias: p)",
    ))
    p.add_argument("placa", help="Id o nombre (o trozo del nombre) de la placa.")
    p.add_argument(
        "--como-anotado", action="store_true", dest="como_anotado",
        help="Usa la versión anotada de cada componente en vez de la vigente, para reproducir la placa bit a bit.",
    )
    p.set_defaults(func=cmd_placa)

    p = con_dir(subs.add_parser(
        "revisar", aliases=["r"],
        help="Compara todo el catálogo: lo que generaría el .blend ahora mismo vs lo ya subido a mano. (alias: r)",
    ))
    p.set_defaults(func=cmd_revisar)

    args = parser.parse_args(argv)

    try:
        return args.func(args)
    except RuntimeError as e:
        print(f"\n  ⚠ {e}\n", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
