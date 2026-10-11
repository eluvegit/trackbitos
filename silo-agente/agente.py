#!/usr/bin/env python3
"""
Agente de Silo — ejecutor tonto que habla por API con la web (ver
docs/silo-ingesta-propagacion.md): no decide nada, hace `os.scandir` real
del primer nivel del root de cada unidad configurada y reporta lo que
encuentra; la web (App\\Controllers\\Silo\\Agente) es quien clasifica cada
entrada y decide qué se ingesta. Fase 1 (ingesta del Maestro) con
detección de cambios N0–N3: el manifiesto de cada unidad vive en su raíz
(`.silo_manifest.json`) y solo se mandan a la web las carpetas que
cambiaron; al terminar deja también la réplica del catálogo
(`.catalogo.sql.gz`) y la copia de las miniaturas (`.silo_proxies/`).
Fase 3 (propagación física a USB de nivel 2/3 y espejos): `--copiar` /
`--renombrar`, ver más abajo y README.md de este directorio.

Se lanza a mano (`silo` en la terminal, ver perfil de PowerShell) o se deja
corriendo con `--daemon`: en ambos casos, si la web dejó un escaneo
pendiente para una unidad (botón "Solicitar escaneo" en /silo/unidades,
tabla `silo_tareas`), este script lo detecta en el handshake y lo cierra
solo — la web nunca ejecuta nada en esta máquina, solo dejar la petición
esperando a que este script pase por aquí.

Sin dependencias de Python fuera de la librería estándar (mismo criterio que
piezas-cli/trackbitos.py): tiene que arrancar en cualquier máquina sin
instalar nada. Única excepción: los proxies de previsualización (fotos/vídeo)
usan el binario externo `ffmpeg` si está en el PATH — si no lo está, el
agente avisa una vez y sigue escaneando normal, sin proxies.
"""
from __future__ import annotations

import argparse
import base64
import hashlib
import json
import mimetypes
import os
import re
import shutil
import ssl
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request
import uuid
from pathlib import Path

CONFIG_PATH = Path(__file__).resolve().parent / "config.json"


def cargar_config() -> dict:
    if not CONFIG_PATH.is_file():
        raise RuntimeError(f"falta {CONFIG_PATH}. Copia config.example.json a config.json y ajústalo.")

    return json.loads(CONFIG_PATH.read_text(encoding="utf-8"))


def _contexto_tls(config: dict):
    # Útil contra el certificado local de ServBay en desarrollo; en
    # producción déjalo a true (por defecto).
    if config.get("verificar_tls", True):
        return None

    contexto = ssl.create_default_context()
    contexto.check_hostname = False
    contexto.verify_mode = ssl.CERT_NONE
    return contexto


def _cabeceras(config: dict) -> dict:
    return {
        "Authorization": f"Bearer {config['token']}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    }


def api_post(config: dict, ruta: str, cuerpo: dict) -> dict:
    datos = json.dumps(cuerpo).encode("utf-8")
    peticion = urllib.request.Request(
        config["api_base"].rstrip("/") + ruta,
        data=datos,
        headers=_cabeceras(config),
        method="POST",
    )
    try:
        with urllib.request.urlopen(peticion, timeout=120, context=_contexto_tls(config)) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        cuerpo_error = e.read().decode("utf-8", errors="replace")
        try:
            mensaje = json.loads(cuerpo_error).get("error", cuerpo_error)
        except json.JSONDecodeError:
            mensaje = cuerpo_error
        raise RuntimeError(f"HTTP {e.code} en {ruta}: {mensaje}") from e
    except urllib.error.URLError as e:
        raise RuntimeError(f"no se pudo conectar con {config.get('api_base')}: {e.reason}") from e


def api_post_multipart(config: dict, ruta: str, campos: dict, archivo_campo: str, archivo_path: Path) -> dict:
    """
    Igual que api_post() pero como `multipart/form-data` con un fichero —
    usada solo para subir proxies (Agente::subirProxy()). A mano con la
    librería estándar (sin `requests`, mismo criterio de cero dependencias de
    Python que el resto del agente).
    """
    boundary = uuid.uuid4().hex
    trozos = []
    for clave, valor in campos.items():
        trozos.append(
            f'--{boundary}\r\nContent-Disposition: form-data; name="{clave}"\r\n\r\n{valor}\r\n'.encode("utf-8")
        )

    tipo_mime = mimetypes.guess_type(archivo_path.name)[0] or "application/octet-stream"
    trozos.append(
        (
            f"--{boundary}\r\n"
            f'Content-Disposition: form-data; name="{archivo_campo}"; filename="{archivo_path.name}"\r\n'
            f"Content-Type: {tipo_mime}\r\n\r\n"
        ).encode("utf-8")
    )
    trozos.append(archivo_path.read_bytes())
    trozos.append(f"\r\n--{boundary}--\r\n".encode("utf-8"))

    peticion = urllib.request.Request(
        config["api_base"].rstrip("/") + ruta,
        data=b"".join(trozos),
        headers={
            "Authorization": f"Bearer {config['token']}",
            "Content-Type": f"multipart/form-data; boundary={boundary}",
            "Accept": "application/json",
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(peticion, timeout=60, context=_contexto_tls(config)) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        cuerpo_error = e.read().decode("utf-8", errors="replace")
        try:
            mensaje = json.loads(cuerpo_error).get("error", cuerpo_error)
        except json.JSONDecodeError:
            mensaje = cuerpo_error
        raise RuntimeError(f"HTTP {e.code} en {ruta}: {mensaje}") from e
    except urllib.error.URLError as e:
        raise RuntimeError(f"no se pudo conectar con {config.get('api_base')}: {e.reason}") from e


# Lo que el propio agente deja en la raíz de cada unidad: no son piezas ni
# "entradas ignoradas", así que ni se reportan a la web.
FICHEROS_CONTROL = {
    ".silo_unit.json", ".silo_manifest.json", ".catalogo.sql.gz", ".catalogo.meta.json", ".silo_proxies",
}


def escanear_primer_nivel(ruta: Path) -> list[dict]:
    """
    Solo el primer nivel del root (plan Silo: las carpetas-pieza cuelgan
    directas de la raíz del Maestro, sin contenedores de año/temática por
    encima). Para cada carpeta lista también sus ficheros sueltos: nombre,
    tamaño y mtime — solo `stat`, nunca se abre un fichero (N1). La
    clasificación candidata/saltada (y el motivo) la hace la web, aquí solo
    se reporta lo que hay en disco.
    """
    entradas = []
    with os.scandir(ruta) as it:
        for entrada in sorted(it, key=lambda e: e.name.lower()):
            if entrada.name in FICHEROS_CONTROL or entrada.name.endswith(".silo_tmp"):
                continue
            item = {"nombre": entrada.name, "es_carpeta": entrada.is_dir()}
            if entrada.is_dir():
                item["ficheros"] = []
                for f in sorted(os.scandir(entrada.path), key=lambda e: e.name.lower()):
                    if f.is_file():
                        st = f.stat()
                        item["ficheros"].append({"nombre": f.name, "tamano_bytes": st.st_size, "mtime": int(st.st_mtime)})
            entradas.append(item)

    return entradas


# ---------------------------------------------------------------------------
# Proxies de previsualización (fotos/vídeo) — plan cerrado 2026-09-06,
# rescatado e implementado 2026-09-26, ampliado el mismo día tras ver el
# resultado de la primera pasada (ver docs/silo-ingesta-propagacion.md §
# "Proxies / capturas para visualizar carpetas"). Genera hasta
# MAX_PROXIES_FOTO fotos + MAX_PROXIES_VIDEO fotogramas de vídeo por carpeta
# con `ffmpeg` (redimensionadas / frames interiores, WebP) y los sube a la
# web (Agente::subirProxy()) justo después de ingestar una carpeta que
# todavía no tenga proxies reales (`necesita_proxies` en la respuesta de
# /silo/agente/escaneo). Sin `capturado_en` por fichero todavía (columna
# pendiente, ver doc "Añadidos al esquema"), la selección "repartida a lo
# largo de la línea de tiempo" usa el ORDEN DE NOMBRE dentro de cada carpeta
# como aproximación (mismo criterio que ya usa el resto del sistema: el
# nombre de cámara/el prefijo "+" ya aproximan el orden de disparo) en vez de
# fecha de captura real — determinista, así no hace falta semilla ni shuffle
# para que no baile entre reescaneos.
#
# Fotogramas de vídeo — nunca al arranque ni al final (primer intento: fijo a
# 1s, salía negro/borroso en muchos vídeos reales: fundidos de entrada, unos
# frames de inicialización de la cámara...): con la duración real (ffprobe)
# se reparten SIEMPRE por el interior del vídeo. Si hay menos vídeos que
# MAX_PROXIES_VIDEO, a los que hay les toca más de un fotograma (repartidos
# también por su interior) para completar el objetivo — con un único vídeo
# en la carpeta, hasta MAX_PROXIES_VIDEO fotogramas suyos en instantes
# distintos en vez de uno solo.
# ---------------------------------------------------------------------------

PROXY_LADO_MAX = 800
MAX_PROXIES_FOTO = 10
MAX_PROXIES_VIDEO = 10
_FFMPEG_AVISADO = False


def _ffmpeg_disponible() -> bool:
    global _FFMPEG_AVISADO
    if shutil.which("ffmpeg") and shutil.which("ffprobe"):
        return True
    if not _FFMPEG_AVISADO:
        print("  ! ffmpeg/ffprobe no están en el PATH: no se generarán proxies de previsualización (el resto del escaneo sigue igual).")
        _FFMPEG_AVISADO = True
    return False


def _muestra_espaciada(items: list, k: int) -> list:
    """Hasta `k` elementos de `items` repartidos de principio a fin (incluye siempre el primero y el último si hay más de uno)."""
    n = len(items)
    if n <= k:
        return list(items)
    if k <= 1:
        return items[:1]
    return [items[round(i * (n - 1) / (k - 1))] for i in range(k)]


def _reparto_por_video(n_videos: int, objetivo: int) -> list[int]:
    """Cuántos fotogramas le tocan a cada uno de `n_videos` vídeos (mismo orden que la lista elegida) para sumar `objetivo` en total, lo más parejo posible. Requiere n_videos <= objetivo."""
    base, resto = divmod(objetivo, n_videos)
    return [base + (1 if i < resto else 0) for i in range(n_videos)]


def _puntos_interiores(duracion: float, n: int) -> list[float]:
    """`n` instantes DENTRO del vídeo, nunca en el arranque ni en el final (ahí es donde suelen salir negros o con fundido) — con n=1, justo la mitad."""
    return [duracion * (i + 1) / (n + 1) for i in range(n)]


def _duracion_video(ruta: Path) -> float | None:
    resultado = subprocess.run(
        ["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", str(ruta)],
        capture_output=True, text=True,
    )
    if resultado.returncode != 0:
        return None
    try:
        duracion = float(resultado.stdout.strip())
    except ValueError:
        return None
    return duracion if duracion > 0 else None


def _ffmpeg_frame(args_previos: list[str], origen: Path, destino: Path) -> bool:
    resultado = subprocess.run(
        [
            "ffmpeg", "-y", *args_previos, "-i", str(origen),
            "-vf", f"scale='min({PROXY_LADO_MAX},iw)':'min({PROXY_LADO_MAX},ih)':force_original_aspect_ratio=decrease",
            "-update", "1", "-frames:v", "1", str(destino),
        ],
        capture_output=True,
    )
    return resultado.returncode == 0 and destino.is_file() and destino.stat().st_size > 0


def _generar_proxy_foto(origen: Path, destino: Path) -> bool:
    return _ffmpeg_frame([], origen, destino)


def _generar_proxy_video(origen: Path, destino: Path, segundo: float | None) -> bool:
    """Frame en el instante interior calculado (ver _puntos_interiores); si falla (p.ej. duración no fiable), el primer fotograma disponible como último recurso."""
    if segundo is not None and segundo > 0 and _ffmpeg_frame(["-ss", f"{segundo:.2f}"], origen, destino):
        return True
    return _ffmpeg_frame([], origen, destino)


def generar_proxies_pieza(config: dict, pieza_id: int, ruta_carpeta: Path, ficheros: list[dict], copia: Path | None = None) -> None:
    """
    Genera (ffmpeg) y sube los proxies de una carpeta. Con `copia` (la
    carpeta `.silo_proxies/<id_negocio>/` del Maestro) deja además allí los
    .webp generados y su `proxies.json`, para poder volver a subirlos sin
    ffmpeg si la web los pierde (ver guardar_copia_proxies()).
    """
    fotos = sorted(
        (f["nombre"] for f in ficheros if _tipo_extension(f["nombre"]) == "foto"),
        key=str.lower,
    )
    videos = sorted(
        (f["nombre"] for f in ficheros if _tipo_extension(f["nombre"]) == "video"),
        key=str.lower,
    )

    tareas = [("foto", i, nombre, None) for i, nombre in enumerate(_muestra_espaciada(fotos, MAX_PROXIES_FOTO))]

    videos_elegidos = _muestra_espaciada(videos, MAX_PROXIES_VIDEO)
    if videos_elegidos:
        orden = 0
        for nombre, n_frames in zip(videos_elegidos, _reparto_por_video(len(videos_elegidos), MAX_PROXIES_VIDEO)):
            duracion = _duracion_video(ruta_carpeta / nombre)
            puntos = _puntos_interiores(duracion, n_frames) if duracion else [None] * n_frames
            for segundo in puntos:
                tareas.append(("video", orden, nombre, segundo))
                orden += 1

    if not tareas:
        return

    subidos = 0
    generados = []  # (destino, tipo, orden, nombre_fichero) — para la copia en el Maestro
    primero = True  # se apaga tras la PRIMERA subida que de verdad se envía, no tras la primera tarea (si esa falla al generar, el flag de reemplazo tiene que esperar a la que sí lo consiga)
    with tempfile.TemporaryDirectory(prefix="silo_proxy_") as tmp:
        for tipo, orden, nombre_fichero, segundo in tareas:
            destino = Path(tmp) / f"{tipo}-{orden}.webp"
            origen = ruta_carpeta / nombre_fichero
            ok = _generar_proxy_foto(origen, destino) if tipo == "foto" else _generar_proxy_video(origen, destino, segundo)
            if not ok:
                print(f"      ! no se pudo generar el proxy de «{nombre_fichero}», se salta.")
                continue
            generados.append((destino, tipo, orden, nombre_fichero))

            try:
                api_post_multipart(
                    config,
                    f"/silo/agente/piezas/{pieza_id}/proxies",
                    {
                        "tipo": tipo,
                        "orden": orden,
                        "fichero_nombre": nombre_fichero,
                        # Solo en la primera subida que de verdad se envía:
                        # borra en la web cualquier proxy previo de esta pieza
                        # (simulado o de una generación anterior) antes de
                        # repoblar.
                        "reemplazar": "1" if primero else "0",
                    },
                    "archivo",
                    destino,
                )
                subidos += 1
                primero = False
            except RuntimeError as e:
                print(f"      ! error subiendo el proxy de «{nombre_fichero}»: {e}")

        if copia is not None and generados:
            guardar_copia_proxies(
                copia, pieza_id, firma_multimedia(ficheros),
                [(tipo, orden, nombre_fichero, destino.read_bytes()) for destino, tipo, orden, nombre_fichero in generados],
            )

    if subidos:
        print(f"      proxies: {subidos}/{len(tareas)} generado(s) y subido(s).")


# ---------------------------------------------------------------------------
# Copia de los proxies en el Maestro (petición 2026-09-27): `.silo_proxies/
# <id_negocio>/` en la raíz de la unidad, con los .webp y un `proxies.json`
# que dice de qué versión de la carpeta salieron (`firma` de sus fotos y
# vídeos, ver firma_multimedia()). Por id_negocio y no por nombre, así
# renombrar la carpeta no la invalida. Sirve para reconstruir la parte
# visual de la web sin volver a pasar ffmpeg por todo el disco: si la web
# pide proxies de una carpeta cuyas fotos/vídeos no cambiaron, se suben los
# de la copia.
# ---------------------------------------------------------------------------

def firma_multimedia(ficheros: list[dict]) -> str:
    """Huella de las fotos y vídeos de una carpeta (nombre, tamaño, mtime): si no cambia, sus proxies siguen valiendo."""
    lineas = sorted(
        f"{f['nombre']}\t{f.get('tamano_bytes')}\t{f.get('mtime')}"
        for f in ficheros if _tipo_extension(f["nombre"]) != "otro"
    )
    return hashlib.sha256("\n".join(lineas).encode("utf-8")).hexdigest()


def leer_copia_proxies(copia: Path) -> dict | None:
    try:
        return json.loads((copia / "proxies.json").read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None


def guardar_copia_proxies(copia: Path, pieza_id: int, firma: str, proxies: list[tuple]) -> None:
    """`proxies`: (tipo, orden, fichero_nombre, bytes .webp). Sustituye la copia anterior entera."""
    try:
        if copia.exists():
            shutil.rmtree(copia)
        copia.mkdir(parents=True)
        indice = []
        for tipo, orden, fichero_nombre, contenido in proxies:
            archivo = f"{tipo}-{orden}.webp"
            (copia / archivo).write_bytes(contenido)
            indice.append({"tipo": tipo, "orden": orden, "fichero_nombre": fichero_nombre, "archivo": archivo})
        (copia / "proxies.json").write_text(
            json.dumps({"pieza_id": pieza_id, "firma": firma, "proxies": indice}, ensure_ascii=False, indent=1),
            encoding="utf-8",
        )
    except OSError as e:
        print(f"      ! no se pudo guardar la copia de proxies en {copia}: {e}")


def subir_proxies_desde_copia(config: dict, pieza_id: int, copia: Path, indice: dict) -> int:
    subidos = 0
    for p in indice.get("proxies", []):
        try:
            api_post_multipart(
                config,
                f"/silo/agente/piezas/{pieza_id}/proxies",
                {
                    "tipo": p["tipo"],
                    "orden": p["orden"],
                    "fichero_nombre": p.get("fichero_nombre") or "",
                    "reemplazar": "1" if subidos == 0 else "0",
                },
                "archivo",
                copia / p["archivo"],
            )
            subidos += 1
        except (RuntimeError, OSError) as e:
            print(f"      ! error subiendo {p.get('archivo')} desde la copia: {e}")
    return subidos


def descargar_copia_proxies(config: dict, pieza_id: int, copia: Path, firma: str) -> bool:
    """Guarda en el Maestro los proxies que ya tiene la web (piezas proxied antes de que existiera la copia)."""
    listado = api_post(config, f"/silo/agente/piezas/{pieza_id}/proxies/listar", {}).get("proxies", [])
    if not listado:
        return False
    proxies = []
    for p in listado:
        try:
            with urllib.request.urlopen(p["url"], timeout=60, context=_contexto_tls(config)) as resp:
                proxies.append((p["tipo"], p["orden"], p.get("fichero_nombre"), resp.read()))
        except (urllib.error.URLError, OSError) as e:
            print(f"      ! no se pudo bajar {p['url']}: {e}")
    if proxies:
        guardar_copia_proxies(copia, pieza_id, firma, proxies)
    return bool(proxies)


# ---------------------------------------------------------------------------
# --etiquetar-contenido: herramienta puntual (petición 2026-09-26) que repasa
# el Maestro y propone (o aplica con --aplicar) añadir al final del TEMA de
# cada carpeta la etiqueta de contenido "(Fotos)" / "(Vídeos)" /
# "(Fotos + Vídeos)" / "(Montajes)" según lo que de verdad haya dentro —
# mismo formato que ya reconoce la web (silo_contenido_detectar() en
# app/Helpers/silo_helper.php). "Montajes" no se detecta por contenido real
# (eso no es posible por fichero) sino por una HEURÍSTICA pedida 2026-09-26:
# una carpeta con solo vídeos (sin fotos) y pocos (<= UMBRAL_VIDEOS_MONTAJE)
# es más probable que sea un montaje ya editado que un volcado bruto de
# cámara, así que se propone "Montajes" en vez de "Vídeos" — ahorra
# reetiquetar a mano el caso más frecuente, a costa de algún falso positivo
# que hay que repasar en el informe (el propio informe avisa de cuáles son
# heurística, no detección real). Puramente local (no habla con la API ni la
# BD) — tras aplicar, el siguiente escaneo normal recoge el tema nuevo.
# ---------------------------------------------------------------------------

EXTENSIONES_FOTO = {"jpg", "jpeg", "jpe", "png", "bmp", "tif", "tiff", "heic", "raw", "cr2", "nef"}
EXTENSIONES_VIDEO = {"mp4", "mov", "avi", "mkv", "mpg", "mpeg", "m4v", "wmv", "webm", "3gp", "mts", "m2ts", "flv"}
UMBRAL_VIDEOS_MONTAJE = 4  # solo vídeos y <= esto -> "Montajes" (heurística); más -> "Vídeos"

_RE_CANDIDATA = re.compile(r"^\d{5,6}\s+(\d{4}|\d{6}|\d{8}|sinfecha)\b", re.IGNORECASE)
_RE_FECHA_PREFIJO = re.compile(r"^(\d{8}|\d{6}|\d{4}|sinfecha)\b[\s,]*", re.IGNORECASE)
_RE_PARENTESIS_FINAL = re.compile(r"^(.*?)\s*\(([^()]+)\)\s*$")
_MAPA_CLAVE_CONTENIDO = {
    "foto": "fotos", "fotos": "fotos",
    "video": "videos", "videos": "videos",
    "montaje": "montajes", "montajes": "montajes",
}
_TABLA_ACENTOS = str.maketrans("áéíóúÁÉÍÓÚ", "aeiouAEIOU")


def _tipo_extension(nombre: str) -> str:
    ext = Path(nombre).suffix.lstrip(".").lower()
    if ext in EXTENSIONES_FOTO:
        return "foto"
    if ext in EXTENSIONES_VIDEO:
        return "video"
    return "otro"


def _es_candidata(nombre: str, lista_negra: list[str]) -> bool:
    """Mismo criterio que SiloService::clasificarEntradaRoot() en la web (sin motivo, aquí solo interesa sí/no)."""
    if re.match(r"^[_.~]", nombre):
        return False
    negra = {n.strip().lower() for n in lista_negra}
    if nombre.strip().lower() in negra:
        return False
    return bool(_RE_CANDIDATA.match(nombre.strip()))


def _ya_tiene_etiqueta_contenido(texto: str) -> bool:
    """Puerto de silo_contenido_detectar() (solo el sí/no de si YA lleva etiqueta reconocible al final)."""
    m = _RE_PARENTESIS_FINAL.match(texto)
    if not m:
        return False

    claves = set()
    for trozo in re.split(r"\s*[+,&]\s*|\s+y\s+|\s+e\s+", m.group(2), flags=re.IGNORECASE):
        trozo = trozo.strip()
        if trozo == "":
            continue
        clave = _MAPA_CLAVE_CONTENIDO.get(trozo.translate(_TABLA_ACENTOS).lower())
        if clave is None:
            return False
        claves.add(clave)

    return len(claves) > 0


def _etiqueta_por_contenido_real(ficheros: list[dict]) -> tuple[str, bool] | None:
    """
    (etiqueta, es_heuristica) o None si no hay ni fotos ni vídeos
    detectables. `es_heuristica` marca el caso "Montajes" (solo vídeos, pocos
    — ver UMBRAL_VIDEOS_MONTAJE arriba): es una suposición, no una detección
    real, para que el informe pueda avisar de cuáles conviene repasar.
    """
    n_fotos = sum(1 for f in ficheros if _tipo_extension(f["nombre"]) == "foto")
    n_videos = sum(1 for f in ficheros if _tipo_extension(f["nombre"]) == "video")

    if n_fotos and n_videos:
        return "Fotos + Vídeos", False
    if n_fotos:
        return "Fotos", False
    if n_videos:
        if n_videos <= UMBRAL_VIDEOS_MONTAJE:
            return "Montajes", True
        return "Vídeos", False
    return None


def _nombre_con_tema_etiquetado(nombre: str, etiqueta: str) -> str | None:
    """
    None si no aplica: no es "<id> <fecha> ..." reconocible, no tiene hueco de
    tema (posición 1 tras la categoría, contrato de campos fijos), el hueco
    de tema está vacío, o el tema ya termina en una etiqueta de contenido
    reconocible (no se toca — puede llevar ya "+ Montajes" a mano). El resto
    del nombre (id, fecha, categoría, lugar, personas) se conserva tal cual,
    solo recortando espacios sueltos alrededor de cada coma al reconstruir.
    """
    partes_id = nombre.split(None, 1)
    if len(partes_id) != 2:
        return None
    id_negocio, resto_completo = partes_id

    m = _RE_FECHA_PREFIJO.match(resto_completo)
    if not m:
        return None

    prefijo = nombre[: len(id_negocio) + 1 + m.end()]
    trozos = resto_completo[m.end():].split(",")
    while len(trozos) > 1 and trozos[-1].strip() == "":
        trozos.pop()
    if trozos == [""]:
        trozos = []

    if len(trozos) < 2:
        return None  # sin categoría+tema, o sin hueco de tema

    categoria = trozos[0].strip()
    tema = trozos[1].strip()
    if tema == "" or _ya_tiene_etiqueta_contenido(tema):
        return None

    elementos = [f"{tema} ({etiqueta})"] + [e.strip() for e in trozos[2:]]

    return prefijo + categoria + ", " + ", ".join(elementos)


def etiquetar_contenido(config: dict, aplicar: bool) -> int:
    lista_negra = config.get("lista_negra", [])
    propuestas = aplicadas = errores = heuristicas = 0

    for cfg_unidad in config["unidades"]:
        ruta = Path(cfg_unidad["ruta"])
        if not ruta.is_dir():
            print(f"  ! {ruta}: no existe o no está montada ahora mismo, se salta.")
            continue

        print(f"{ruta}:")
        with os.scandir(ruta) as it:
            entradas = sorted(it, key=lambda e: e.name.lower())

        for entrada in entradas:
            if not entrada.is_dir() or not _es_candidata(entrada.name, lista_negra):
                continue

            ficheros = [{"nombre": f.name} for f in os.scandir(entrada.path) if f.is_file()]
            resultado = _etiqueta_por_contenido_real(ficheros)
            if resultado is None:
                continue
            etiqueta, es_heuristica = resultado

            nuevo_nombre = _nombre_con_tema_etiquetado(entrada.name, etiqueta)
            if nuevo_nombre is None or nuevo_nombre == entrada.name:
                continue

            propuestas += 1
            if es_heuristica:
                heuristicas += 1
            print(f"  [{'RENOMBRADA' if aplicar else 'propuesta'}] {entrada.name}")
            print(f"          -> {nuevo_nombre}")
            if es_heuristica:
                print("          (heurística: pocos vídeos y ninguna foto — revisa si de verdad es un montaje)")

            if not aplicar:
                continue

            destino = Path(entrada.path).with_name(nuevo_nombre)
            if destino.exists():
                print(f"          ! ya existe «{nuevo_nombre}», se salta para no machacar nada.")
                errores += 1
                continue
            try:
                Path(entrada.path).rename(destino)
                aplicadas += 1
            except OSError as e:
                print(f"          ! ERROR al renombrar: {e}")
                errores += 1

    if aplicar:
        print(f"\n{aplicadas} carpeta(s) renombrada(s) ({heuristicas} por heurística de Montajes), {errores} error(es)."
              " Lanza un escaneo normal para que la web recoja el tema nuevo.")
    else:
        print(f"\n{propuestas} carpeta(s) con propuesta de renombrado ({heuristicas} por heurística de Montajes)."
              " Repásalas y relanza con --etiquetar-contenido --aplicar para aplicarlas de verdad.")

    return 1 if errores else 0


# ---------------------------------------------------------------------------
# Detección de cambios N0–N3 (docs/silo-ingesta-propagacion.md § "Detección
# de cambios", implementada 2026-09-27). El manifiesto de cada unidad vive en
# su raíz, `.silo_manifest.json`:
#   { "unidad_id", "generado_en",
#     "carpetas": { "<carpeta>": { "firma", "ficheros": { "<fichero>": {"t": tamaño, "m": mtime, "h": sha256|null} } } } }
#   · N0 — el rollup del manifiesto (hash_indice) se compara con el que la
#     web guardó en la última sincronización: si no casan (primer escaneo,
#     BD restaurada, manifiesto borrado...) se manda TODO completo.
#   · N1 — `stat` de cada fichero (tamaño + mtime, sin abrirlo): una carpeta
#     cuya firma no cambió va a la web como "sin cambios", sin su lista.
#   · N2 — mismo tamaño pero otro mtime: se hashea SOLO ese fichero y el
#     hash va a la web, que decide si de verdad cambió.
#   · N3 — `--verificar`: se re-hashea todo; mismo tamaño y mtime pero otro
#     hash = corrupción silenciosa -> aviso `hash_distinto` en la web.
# ---------------------------------------------------------------------------

MANIFIESTO = ".silo_manifest.json"


def hash_fichero(ruta: Path) -> str:
    h = hashlib.sha256()
    with open(ruta, "rb") as f:
        for bloque in iter(lambda: f.read(1024 * 1024), b""):
            h.update(bloque)
    return h.hexdigest()


def firma_carpeta(ficheros: list[dict]) -> str:
    lineas = sorted(f"{f['nombre']}\t{f.get('tamano_bytes')}\t{f.get('mtime')}" for f in ficheros)
    return hashlib.sha256("\n".join(lineas).encode("utf-8")).hexdigest()


def hash_indice(manifiesto: dict) -> str:
    lineas = sorted(f"{nombre}\t{c['firma']}" for nombre, c in manifiesto.get("carpetas", {}).items())
    return hashlib.sha256("\n".join(lineas).encode("utf-8")).hexdigest()


def leer_json(ruta: Path) -> dict | None:
    try:
        return json.loads(ruta.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None


def escribir_atomico(ruta: Path, contenido: bytes) -> None:
    """Escribe a un temporal y lo renombra encima: nunca queda un fichero de control a medias."""
    tmp = ruta.with_name(ruta.name + ".silo_tmp")
    tmp.write_bytes(contenido)
    os.replace(tmp, ruta)


def comparar_con_manifiesto(ruta: Path, entradas: list[dict], anterior: dict | None, verificar: bool) -> tuple[list[dict], dict, list[dict]]:
    """
    Decide qué carpetas van completas a la web y deja preparada la entrada
    de manifiesto de cada una. Devuelve (entradas para la web, manifiesto
    nuevo por carpeta, corruptos N3). `anterior` = None -> todo completo.
    """
    previas = (anterior or {}).get("carpetas", {})
    para_web, nuevas, corruptos = [], {}, []
    hasheados = 0

    for e in entradas:
        if not e["es_carpeta"]:
            para_web.append(e)
            continue

        ficheros = e["ficheros"]
        firma = firma_carpeta(ficheros)
        previa = previas.get(e["nombre"]) if anterior is not None else None

        if previa and previa.get("firma") == firma and not verificar:
            # N1: nada cambió. La web solo recalcula lo que sale del nombre.
            para_web.append({"nombre": e["nombre"], "es_carpeta": True})
            nuevas[e["nombre"]] = previa
            continue

        previos = (previa or {}).get("ficheros", {})
        entrada_manifiesto = {}
        for f in ficheros:
            antes = previos.get(f["nombre"])
            mismo_stat = antes is not None and antes.get("t") == f["tamano_bytes"] and antes.get("m") == f["mtime"]
            hash_ = antes.get("h") if mismo_stat else None
            # N2 (mismo tamaño, otro mtime) o N3 (--verificar): se lee el fichero.
            candidato = antes is not None and antes.get("t") == f["tamano_bytes"] and antes.get("m") != f["mtime"]
            if verificar or candidato:
                try:
                    nuevo_hash = hash_fichero(ruta / e["nombre"] / f["nombre"])
                    hasheados += 1
                except OSError as err:
                    print(f"    ! no se pudo leer {e['nombre']}/{f['nombre']}: {err}")
                    nuevo_hash = None
                if verificar and mismo_stat and antes.get("h") and nuevo_hash and nuevo_hash != antes["h"]:
                    corruptos.append({"carpeta": e["nombre"], "fichero": f["nombre"]})
                hash_ = nuevo_hash or hash_
            if hash_:
                f["hash"] = hash_
            entrada_manifiesto[f["nombre"]] = {"t": f["tamano_bytes"], "m": f["mtime"], "h": hash_}

        para_web.append(e)
        nuevas[e["nombre"]] = {"firma": firma, "ficheros": entrada_manifiesto}

    if hasheados:
        print(f"    hash leído de {hasheados} fichero(s) ({'verificación completa' if verificar else 'solo los que cambiaron de fecha'}).")
    return para_web, nuevas, corruptos


def handshake(config: dict) -> dict:
    unidades = []
    for u in config["unidades"]:
        item = {"unidad_id": u.get("unidad_id"), "ruta_montaje": u["ruta"]}
        # Sello de la réplica del catálogo que hay en ese disco: si es más
        # nueva que la BD viva, la web avisa (ver Agente::handshake()).
        meta = leer_json(Path(u["ruta"]) / ".catalogo.meta.json")
        if meta:
            item["catalogo_meta"] = meta
        unidades.append(item)
    return api_post(config, "/silo/agente/handshake", {"unidades": unidades})


def tarea_pendiente(unidad: dict, tipo: str) -> dict | None:
    """Primera tarea de `tipo` que la web dejó pendiente/en_curso para esta unidad (ver Agente::handshake)."""
    for t in unidad.get("tareas", []):
        if t.get("tipo") == tipo and t.get("estado") in ("pendiente", "en_curso"):
            return t
    return None


def escanear_unidad(config: dict, unidad: dict, ruta: str, dry_run: bool, tarea_id: int | None = None, verificar: bool = False) -> None:
    unidad_id = unidad["unidad_id"]
    ruta_path = Path(ruta)
    if not ruta_path.is_dir():
        print(f"  ! {ruta}: no existe o no está montada ahora mismo, se salta.")
        return

    entradas = escanear_primer_nivel(ruta_path)
    print(f"  {ruta}: {len(entradas)} entrada(s) en el primer nivel.")

    # N0: ¿el manifiesto del disco es el mismo que la web dio por bueno?
    anterior = leer_json(ruta_path / MANIFIESTO)
    if anterior and anterior.get("unidad_id") != unidad_id:
        anterior = None
    if anterior is None:
        print("    N0: sin manifiesto en el disco -> se manda todo completo.")
    elif not unidad.get("hash_indice") or hash_indice(anterior) != unidad["hash_indice"]:
        print("    N0: el manifiesto del disco no casa con la última sincronización de la web -> se manda todo completo.")
        anterior = None

    para_web, nuevas, corruptos = comparar_con_manifiesto(ruta_path, entradas, anterior, verificar)
    completas = sum(1 for e in para_web if e["es_carpeta"] and "ficheros" in e)
    sin_cambios = sum(1 for e in para_web if e["es_carpeta"] and "ficheros" not in e)
    print(f"    carpetas: {completas} con cambios (se mandan completas), {sin_cambios} sin cambios.")
    for c in corruptos:
        print(f"    ! CONTENIDO DISTINTO con la misma fecha: {c['carpeta']}/{c['fichero']}")

    if dry_run:
        for e in para_web:
            if e["es_carpeta"] and "ficheros" in e:
                print(f"    - {e['nombre']}  ({len(e['ficheros'])} fichero(s))")
        print("  (--dry-run: no se ha mandado nada a la web ni se ha escrito nada en el disco)")
        return

    cuerpo = {
        "unidad_id": unidad_id,
        "lista_negra": config.get("lista_negra", []),
        "entradas": para_web,
        "corruptos": corruptos,
        "verificacion": verificar,
    }
    if tarea_id:
        # Cierra la tarea que la web dejó pendiente (botón "Solicitar
        # escaneo" en /silo/unidades) — Agente::escaneo() la marca
        # hecha/error con este resumen como resultado.
        cuerpo["tarea_id"] = tarea_id

    resultado = api_post(config, "/silo/agente/escaneo", cuerpo)

    print(f"    ingestadas: {len(resultado.get('ingestadas', []))}")
    for s_ in resultado.get("saltadas", []):
        print(f"    saltada:    {s_['nombre']}  ({s_['motivo']})")
    for d in resultado.get("desaparecidas", []):
        print(f"    BORRADA:    {d['nombre']}  (ya no está en el Maestro)")
    for err in resultado.get("errores", []):
        print(f"    ERROR:      {err['nombre']}  -> {err['error']}")

    # Manifiesto nuevo: solo lo que la web aceptó. Una carpeta con error se
    # queda fuera (el próximo escaneo la mandará completa otra vez) y las
    # saltadas no son piezas.
    fuera = {x["nombre"] for x in resultado.get("errores", [])} | {x["nombre"] for x in resultado.get("saltadas", [])}
    carpetas = {nombre: entrada for nombre, entrada in nuevas.items() if nombre not in fuera}
    manifiesto = {"formato": 1, "unidad_id": unidad_id, "generado_en": time.strftime("%Y-%m-%dT%H:%M:%S"), "carpetas": carpetas}

    try:
        escribir_atomico(ruta_path / MANIFIESTO, json.dumps(manifiesto, ensure_ascii=False).encode("utf-8"))
        sinc = api_post(config, f"/silo/agente/unidades/{unidad_id}/sincronizada", {"hash_indice": hash_indice(manifiesto)})
        control = sinc.get("fichero_control") or {}
        escribir_atomico(ruta_path / ".silo_unit.json", json.dumps(control, ensure_ascii=False, indent=4).encode("utf-8"))
        print(f"    manifiesto guardado ({len(carpetas)} carpeta(s)).")
    except (OSError, RuntimeError) as e:
        print(f"    ! no se pudo guardar el manifiesto / sincronizar: {e} (el próximo escaneo mandará todo completo)")

    procesar_proxies(config, ruta_path, entradas, resultado)
    guardar_catalogo(config, unidad_id, ruta_path)


def procesar_proxies(config: dict, ruta_path: Path, entradas: list[dict], resultado: dict) -> None:
    """
    Proxies tras el escaneo, con la copia del Maestro (`.silo_proxies/`):
      · la web los pide (carpeta nueva o con fotos/vídeos cambiados) -> si la
        copia del disco es de esta misma versión de la carpeta, se suben de
        ahí; si no, ffmpeg y se guarda la copia nueva.
      · la web ya los tiene pero el disco no -> se bajan a la copia.
      · carpetas borradas del catálogo -> fuera su copia.
    """
    raiz = ruta_path / ".silo_proxies"
    ficheros_por_nombre = {e["nombre"]: e.get("ficheros", []) for e in entradas}

    for d in resultado.get("desaparecidas", []):
        copia = raiz / str(d.get("id_negocio"))
        if copia.is_dir():
            shutil.rmtree(copia, ignore_errors=True)

    ingestadas = resultado.get("ingestadas", [])
    pendientes = [i for i in ingestadas if i.get("necesita_proxies")]
    if pendientes:
        print(f"    proxies pendientes: {len(pendientes)} carpeta(s)")
    for item in pendientes:
        ficheros = ficheros_por_nombre.get(item["nombre"], [])
        copia = raiz / str(item["id_negocio"])
        indice = leer_copia_proxies(copia)
        if indice and indice.get("firma") == firma_multimedia(ficheros) and indice.get("proxies"):
            n = subir_proxies_desde_copia(config, item["pieza_id"], copia, indice)
            print(f"    proxies desde la copia del disco: {item['nombre']} ({n})")
            continue
        if not _ffmpeg_disponible():
            continue
        print(f"    generando proxies: {item['nombre']}")
        generar_proxies_pieza(config, item["pieza_id"], ruta_path / item["nombre"], ficheros, copia)

    sin_copia = [i for i in ingestadas if not i.get("necesita_proxies") and not (raiz / str(i["id_negocio"]) / "proxies.json").is_file()]
    if sin_copia:
        print(f"    copiando al disco los proxies que ya tiene la web: {len(sin_copia)} carpeta(s)...")
        copiadas = 0
        for item in sin_copia:
            try:
                if descargar_copia_proxies(config, item["pieza_id"], raiz / str(item["id_negocio"]), firma_multimedia(ficheros_por_nombre.get(item["nombre"], []))):
                    copiadas += 1
            except RuntimeError as e:
                print(f"      ! {item['nombre']}: {e}")
        print(f"    copia de proxies guardada para {copiadas} carpeta(s).")


def guardar_catalogo(config: dict, unidad_id: int, ruta_path: Path) -> None:
    """Réplica del catálogo en la raíz de la unidad (ver SiloCatalogoService en la web)."""
    try:
        r = api_post(config, "/silo/agente/catalogo", {"unidad_id": unidad_id})
        gz = base64.b64decode(r["contenido_b64"])
        if hashlib.sha256(gz).hexdigest() != r["meta"].get("sha256_gz"):
            raise RuntimeError("la réplica llegó corrupta (sha256 no casa)")
        escribir_atomico(ruta_path / ".catalogo.sql.gz", gz)
        escribir_atomico(ruta_path / ".catalogo.meta.json", json.dumps(r["meta"], ensure_ascii=False, indent=2).encode("utf-8"))
        print(f"    réplica del catálogo guardada ({len(gz) // 1024} KB, {r['meta']['tablas'].get('silo_piezas', '?')} piezas).")
    except (OSError, RuntimeError, KeyError, ValueError) as e:
        print(f"    ! no se pudo guardar la réplica del catálogo: {e}")


def restaurar_catalogo(config: dict, unidad_id: int) -> int:
    """`--restaurar-catalogo ID`: sube a la web la réplica que hay en la raíz de esa unidad y la restaura (pide confirmación)."""
    cfg = next((u for u in config["unidades"] if u.get("unidad_id") == unidad_id), None)
    if not cfg:
        print(f"La unidad {unidad_id} no está en config.json.")
        return 1
    raiz = Path(cfg["ruta"])
    gz_path = raiz / ".catalogo.sql.gz"
    if not gz_path.is_file():
        print(f"No hay réplica en {gz_path}.")
        return 1
    meta = leer_json(raiz / ".catalogo.meta.json") or {}
    print(f"Réplica: {gz_path}")
    print(f"  generada:      {meta.get('generado_en', '?')}")
    print(f"  último evento: #{meta.get('ultimo_evento_id', '?')}")
    print(f"  piezas:        {meta.get('tablas', {}).get('silo_piezas', '?')}")
    print("Esto SUSTITUYE todas las tablas de Silo de la web por la réplica (la web guarda antes una copia del estado actual).")
    if input('Escribe RESTAURAR para seguir: ').strip() != "RESTAURAR":
        print("Cancelado.")
        return 0
    r = api_post(config, "/silo/agente/catalogo/restaurar", {
        "confirmar": "RESTAURAR",
        "unidad_id": unidad_id,
        "meta": meta,
        "contenido_b64": base64.b64encode(gz_path.read_bytes()).decode("ascii"),
    })
    print(f"Restaurado: {r.get('sentencias')} sentencia(s). Estado anterior guardado en el servidor: {r.get('copia_previa')}")
    return 0


# ---------------------------------------------------------------------------
# Propagación física (Fase 3, 2026-10-11): `--copiar` y `--renombrar`.
#
# La web (SiloCopiaService) dice qué carpetas le tocan a cada unidad de copia
# — USB de nivel 2 (año) / 3 (categoría) y espejos del Maestro — y con qué
# nombre. El agente va pidiendo los discos de uno en uno, los reconoce por su
# `.silo_unit.json` (o, si es un USB nuevo, pregunta cuál es y se lo escribe)
# y reconcilia el disco contra ese plan POR ID DE NEGOCIO, nunca por el
# nombre completo:
#   · Copia 2/3:  "<cubo>/<fecha …> [260015]"  -> el ID va entre corchetes al final.
#   · Espejo:     "260015 <fecha …>"           -> mismo nombre que el Maestro.
# Si la carpeta con ese ID está con otro nombre (se renombró en el Maestro)
# se renombra/mueve en el sitio, sin volver a copiar nada. Dentro de cada
# carpeta, un fichero renombrado en el Maestro (mismo tamaño y fecha) también
# se renombra en vez de copiarse. Solo se copia lo que falta o cambió.
# Lo que sobra (carpetas cuyo ID ya no le toca a esta unidad, ficheros que ya
# no están en el Maestro) se avisa y solo se borra con --purgar.
# ---------------------------------------------------------------------------

_RE_ID_COPIA = re.compile(r"\[(\d{5,6})\]\s*$")
_RE_ID_MAESTRO = re.compile(r"^(\d{5,6})\s")
CARPETAS_SISTEMA = {"System Volume Information", "$RECYCLE.BIN", "RECYCLER", ".Trashes", ".Spotlight-V100", ".fseventsd"}
TOLERANCIA_MTIME = 2  # FAT32/exFAT guardan la fecha con 2 s de resolución
TMP = ".silo_tmp"


class Abortar(Exception):
    pass


def id_de_carpeta(nombre: str) -> str | None:
    m = _RE_ID_COPIA.search(nombre) or _RE_ID_MAESTRO.match(nombre)
    return m.group(1) if m else None


def _ignorable(nombre: str) -> bool:
    return nombre in CARPETAS_SISTEMA or nombre in FICHEROS_CONTROL or nombre[:1] in "._~$" or nombre.endswith(TMP)


def _tamano(n: int | None) -> str:
    n = n or 0
    for unidad, factor in (("TB", 1e12), ("GB", 1e9), ("MB", 1e6), ("KB", 1e3)):
        if n >= factor:
            return f"{n / factor:.1f} {unidad}"
    return f"{n} B"


def _nombre_unidad(u: dict) -> str:
    if u.get("espejo_de"):
        tipo = f"Espejo del Maestro (unidad {u['espejo_de']})"
    else:
        tipo = {1: "Maestro", 2: "Nivel 2 (año)", 3: "Nivel 3 (temática)"}.get(u["nivel"], f"Nivel {u['nivel']}")
    etiqueta = f" «{u['etiqueta']}»" if u.get("etiqueta") else ""
    cubos = f" [{u['buckets']}]" if u.get("buckets") else ""
    return f"{tipo} #{u['numero']}{etiqueta}{cubos}"


def raices_montadas() -> list[Path]:
    """Raíz de cada disco montado ahora mismo (letras en Windows; /Volumes, /media… en el resto)."""
    if os.name == "nt":
        import ctypes
        import string

        k32 = ctypes.windll.kernel32
        k32.SetErrorMode(1)  # sin diálogos de "inserte un disco" en lectores vacíos
        mascara = k32.GetLogicalDrives()
        return [Path(f"{l}:\\") for i, l in enumerate(string.ascii_uppercase) if mascara >> i & 1 and os.path.isdir(f"{l}:\\")]

    raices = []
    for base in ("/Volumes", "/media", f"/media/{os.environ.get('USER', '')}", f"/run/media/{os.environ.get('USER', '')}", "/mnt"):
        if os.path.isdir(base):
            raices += [Path(e.path) for e in os.scandir(base) if e.is_dir()]
    return raices


def _describir_disco(raiz: Path) -> str:
    etiqueta = ""
    if os.name == "nt":
        import ctypes

        buf = ctypes.create_unicode_buffer(261)
        if ctypes.windll.kernel32.GetVolumeInformationW(str(raiz), buf, 261, None, None, None, None, 0):
            etiqueta = buf.value
    try:
        uso = shutil.disk_usage(raiz)
        espacio = f"{_tamano(uso.total)} ({_tamano(uso.free)} libres)"
    except OSError:
        espacio = "?"
    return f"{raiz}  {('«' + etiqueta + '»  ') if etiqueta else ''}{espacio}"


def _unit_json(raiz: Path) -> dict | None:
    return leer_json(raiz / ".silo_unit.json")


def localizar_unidad(config: dict, unidad_id: int) -> Path | None:
    """Raíz donde está montada la unidad: por su `.silo_unit.json` (o por config.json, para el Maestro)."""
    for u in config.get("unidades", []):
        if u.get("unidad_id") == unidad_id and Path(u["ruta"]).is_dir():
            return Path(u["ruta"])
    for raiz in raices_montadas():
        datos = _unit_json(raiz)
        if datos and datos.get("unidad_id") == unidad_id:
            return raiz
    return None


def _conectadas_ahora() -> str:
    partes = []
    for raiz in raices_montadas():
        datos = _unit_json(raiz)
        if datos and datos.get("unidad_id"):
            partes.append(f"{raiz} = unidad {datos['unidad_id']} (nivel {datos.get('nivel', '?')} #{datos.get('numero', '?')})")
    return "; ".join(partes) or "ninguna unidad de Silo"


def pedir_unidad(config: dict, destino: dict, fichero_control: dict, dry_run: bool) -> Path | None:
    """
    Pide al usuario que conecte la unidad y espera a verla. Una unidad que
    ya pasó por aquí se reconoce sola por su `.silo_unit.json`; un USB nuevo
    (sin él) se elige de la lista de discos sin identificar y se le escribe.
    None = saltar esta unidad.
    """
    unidad_id = destino["unidad_id"]
    sistema = Path(os.environ.get("SystemDrive", "C:") + "\\") if os.name == "nt" else Path("/")

    raiz = localizar_unidad(config, unidad_id)
    while raiz is None:
        resp = input(f"  Conecta {_nombre_unidad(destino)} y pulsa Enter  (s = saltar esta, q = terminar): ").strip().lower()
        if resp == "q":
            raise Abortar()
        if resp == "s":
            return None
        raiz = localizar_unidad(config, unidad_id)
        if raiz:
            break

        nuevos = [r for r in raices_montadas() if r != sistema and _unit_json(r) is None]
        print(f"  No encuentro su .silo_unit.json. Conectadas: {_conectadas_ahora()}.")
        if not nuevos:
            print("  Tampoco veo ningún disco sin identificar (¿no ha terminado de montarse?).")
            continue
        print("  Discos sin identificar (¿es uno de estos un USB nuevo para esta unidad?):")
        for i, r in enumerate(nuevos, 1):
            print(f"    {i}) {_describir_disco(r)}")
        eleccion = input("  Número del disco (Enter = volver a buscar): ").strip()
        if not eleccion.isdigit() or not 1 <= int(eleccion) <= len(nuevos):
            continue
        candidato = nuevos[int(eleccion) - 1]

        contenido = [e.name for e in os.scandir(candidato) if not _ignorable(e.name)]
        capacidad = destino.get("capacidad_bytes")
        total = shutil.disk_usage(candidato).total
        if capacidad and total < capacidad * 0.9:
            print(f"  ! Ese disco tiene {_tamano(total)} y la unidad está dada de alta con {_tamano(capacidad)}.")
        if contenido:
            print(f"  ! No está vacío ({len(contenido)} entrada(s): {', '.join(contenido[:5])}{'…' if len(contenido) > 5 else ''}).")
            print("    Lo que no sea una carpeta de Silo se queda tal cual (se avisa como sobrante solo si lleva un ID).")
        if input(f"  ¿Usar {candidato} como {_nombre_unidad(destino)}? Escribe SI: ").strip().upper() != "SI":
            continue
        if dry_run:
            print("  (--dry-run: no se escribe su .silo_unit.json)")
        else:
            escribir_atomico(candidato / ".silo_unit.json", json.dumps(fichero_control, ensure_ascii=False, indent=4).encode("utf-8"))
        raiz = candidato

    print(f"  Unidad en {raiz}")
    return raiz


def indexar_destino(raiz: Path) -> tuple[dict, list[str]]:
    """
    Carpetas de Silo que hay en el disco, por ID: en la raíz (espejo, o una
    carpeta suelta) o un nivel por debajo (dentro de su cubo de año /
    categoría). Devuelve ({id: ruta_relativa}, [rutas con ID repetido]).
    """
    por_id, repetidas = {}, []

    def anotar(ruta_rel: str, nombre: str) -> bool:
        id_ = id_de_carpeta(nombre)
        if id_ is None:
            return False
        if id_ in por_id:
            repetidas.append(ruta_rel)
        else:
            por_id[id_] = ruta_rel
        return True

    for e in sorted(os.scandir(raiz), key=lambda x: x.name.lower()):
        if not e.is_dir() or _ignorable(e.name):
            continue
        if anotar(e.name, e.name):
            continue
        for s in sorted(os.scandir(e.path), key=lambda x: x.name.lower()):
            if s.is_dir() and not _ignorable(s.name):
                anotar(f"{e.name}/{s.name}", s.name)

    return por_id, repetidas


def _mismo_fichero(st: os.stat_result, tamano: int | None, mtime: int | None) -> bool:
    if tamano is not None and st.st_size != tamano:
        return False
    if mtime is None:
        return True
    diff = abs(int(st.st_mtime) - mtime)
    # ±1 h: FAT32/exFAT guardan hora local y el cambio de horario la desplaza.
    return diff <= TOLERANCIA_MTIME or abs(diff - 3600) <= TOLERANCIA_MTIME


def _ficheros_en(carpeta: Path) -> dict:
    if not carpeta.is_dir():
        return {}
    return {f.name: f.stat() for f in os.scandir(carpeta) if f.is_file() and not f.name.endswith(TMP)}


def _diff_carpeta(carpeta: Path, esperados: list[dict]) -> tuple[list[dict], list[tuple[str, str]], list[str]]:
    """(ficheros a copiar, renombrados (viejo, nuevo), sobrantes) dentro de una carpeta."""
    actuales = _ficheros_en(carpeta)
    faltan = [f for f in esperados if f["nombre"] not in actuales or not _mismo_fichero(actuales[f["nombre"]], f.get("tamano_bytes"), f.get("mtime"))]
    nombres = {f["nombre"] for f in esperados}
    extras = [n for n in actuales if n not in nombres]

    renombres = []
    for f in list(faltan):
        if f["nombre"] in actuales:
            continue
        pareja = next((n for n in extras if _mismo_fichero(actuales[n], f.get("tamano_bytes"), f.get("mtime"))), None)
        if pareja and f.get("tamano_bytes"):
            renombres.append((pareja, f["nombre"]))
            extras.remove(pareja)
            faltan.remove(f)
    return faltan, renombres, extras


def _renombrar(origen: Path, destino: Path) -> None:
    destino.parent.mkdir(parents=True, exist_ok=True)
    if destino.exists() and str(origen).lower() != str(destino).lower():
        raise OSError(f"ya existe «{destino.name}»")
    os.rename(origen, destino)


def _limpiar_cubos_vacios(raiz: Path) -> None:
    for e in os.scandir(raiz):
        if e.is_dir() and not _ignorable(e.name) and id_de_carpeta(e.name) is None:
            try:
                os.rmdir(e.path)  # solo si está vacío
            except OSError:
                pass


def sincronizar_destino(config: dict, destino: dict, raiz: Path, plan: dict, solo_renombrar: bool, purgar: bool, dry_run: bool) -> dict:
    items = sorted(plan["items"], key=lambda i: i["ruta"].lower())
    por_id, repetidas = indexar_destino(raiz)
    esperados = {i["id_negocio"]: i for i in items if i.get("id_negocio")}
    sobrantes = sorted([ruta for id_, ruta in por_id.items() if id_ not in esperados] + repetidas, key=str.lower)
    res = {"completas": [], "faltan": [], "sobrantes": [], "errores": [], "renombradas": 0, "copiados_bytes": 0}
    accion = "(dry-run) " if dry_run else ""

    # 1) Renombrar/mover carpetas que ya están con otro nombre: barato y sin el Maestro.
    for item in items:
        actual = por_id.get(item["id_negocio"])
        if actual is None or actual == item["ruta"]:
            continue
        print(f"    {accion}renombrar  {actual}\n               -> {item['ruta']}")
        if not dry_run:
            try:
                _renombrar(raiz / actual, raiz / item["ruta"])
            except OSError as e:
                res["errores"].append({"ruta": actual, "error": f"no se pudo renombrar a «{item['ruta']}»: {e}"})
                continue
            por_id[item["id_negocio"]] = item["ruta"]
        res["renombradas"] += 1

    # 2) Qué falta dentro de cada carpeta (solo stat, sin abrir nada).
    trabajo = []  # (item, carpeta_de_trabajo, faltan, renombres, extras, es_nueva)
    for item in items:
        en_disco = por_id.get(item["id_negocio"])  # en dry-run, todavía con el nombre viejo
        es_nueva = en_disco is None
        trabajo_dir = Path(str(raiz / item["ruta"]) + TMP) if es_nueva else raiz / en_disco
        faltan, renombres, extras = _diff_carpeta(trabajo_dir, item["ficheros"])
        trabajo.append((item, trabajo_dir, faltan, renombres, extras, es_nueva))

    a_copiar = sum((f.get("tamano_bytes") or 0) for _, _, faltan, _, _, _ in trabajo for f in faltan)
    n_faltan = sum(1 for t in trabajo if t[2] or t[5])
    print(f"    {len(items)} carpeta(s) en el plan · {res['renombradas']} renombrada(s) · {n_faltan} con ficheros por copiar ({_tamano(a_copiar)}) · {len(sobrantes)} sobrante(s)")

    maestros = {m["unidad_id"]: m for m in plan.get("maestros", [])}
    raices_maestro = {}
    sin_espacio = False
    if not solo_renombrar and a_copiar and not dry_run:
        libre = shutil.disk_usage(raiz).free
        if a_copiar > libre:
            print(f"    ! No cabe: hay que copiar {_tamano(a_copiar)} y quedan {_tamano(libre)} libres. Solo se hacen los renombrados.")
            sin_espacio = True

    copiado = 0
    interrumpido = False
    for item, trabajo_dir, faltan, renombres, extras, es_nueva in trabajo:
        if interrumpido:
            res["faltan"].append({"pieza_id": item["pieza_id"], "ruta": item["ruta"], "motivo": "interrumpido"})
            continue
        try:
            for viejo, nuevo in renombres:
                print(f"    {accion}renombrar fichero  {item['ruta']}/{viejo} -> {nuevo}")
                if not dry_run:
                    os.rename(trabajo_dir / viejo, trabajo_dir / nuevo)

            if faltan or es_nueva:
                motivo = None
                if solo_renombrar:
                    motivo = "no está en el disco" if es_nueva else f"{len(faltan)} fichero(s) por copiar"
                elif sin_espacio:
                    motivo = "sin espacio en la unidad"
                elif dry_run:
                    print(f"    (dry-run) copiar  {item['ruta']}  ({len(faltan)} fichero(s), {_tamano(sum(f.get('tamano_bytes') or 0 for f in faltan))})")
                    continue
                if motivo:
                    res["faltan"].append({"pieza_id": item["pieza_id"], "ruta": item["ruta"], "motivo": motivo})
                    continue

                origen = item.get("origen")
                if not origen:
                    res["errores"].append({"ruta": item["ruta"], "error": "la pieza no tiene Copia 1 (Maestro) de donde copiar"})
                    continue
                if origen["unidad_id"] not in raices_maestro:
                    m = maestros.get(origen["unidad_id"], {"unidad_id": origen["unidad_id"], "nivel": 1, "numero": "?"})
                    print(f"    Hace falta el Maestro para copiar.")
                    raices_maestro[origen["unidad_id"]] = pedir_unidad(config, m, {}, dry_run=True)
                maestro = raices_maestro[origen["unidad_id"]]
                if maestro is None:
                    res["faltan"].append({"pieza_id": item["pieza_id"], "ruta": item["ruta"], "motivo": "Maestro no conectado"})
                    continue

                trabajo_dir.mkdir(parents=True, exist_ok=True)
                print(f"    copiar  {item['ruta']}  ({len(faltan)} fichero(s))")
                for f in faltan:
                    src = maestro / origen["ruta"] / f["nombre"]
                    if not src.is_file():
                        raise OSError(f"«{f['nombre']}» no está en el Maestro ({src.parent}); escanea el Maestro (silo) antes de copiar")
                    if f.get("tamano_bytes") is not None and src.stat().st_size != f["tamano_bytes"]:
                        raise OSError(f"«{f['nombre']}» cambió en el Maestro desde el último escaneo; escanea el Maestro (silo) antes de copiar")
                    tmp = trabajo_dir / (f["nombre"] + TMP)
                    shutil.copy2(src, tmp)
                    os.replace(tmp, trabajo_dir / f["nombre"])
                    copiado += f.get("tamano_bytes") or 0
                    pct = f"{copiado * 100 // a_copiar:3d}%" if a_copiar else ""
                    print(f"\r      {pct} {_tamano(copiado)} / {_tamano(a_copiar)}  {f['nombre'][:50]:<50}", end="", flush=True)
                print()
                if es_nueva:
                    os.rename(trabajo_dir, raiz / item["ruta"])
                    trabajo_dir = raiz / item["ruta"]

            for n in extras:
                if purgar and not dry_run:
                    (trabajo_dir / n).unlink()
                    print(f"    borrado fichero sobrante  {item['ruta']}/{n}")
                else:
                    res["sobrantes"].append(f"{item['ruta']}/{n}")

            if not dry_run:
                res["completas"].append({"pieza_id": item["pieza_id"], "ubicacion_id": item.get("ubicacion_id"), "ruta": item["ruta"]})
        except KeyboardInterrupt:
            print("\n    Interrumpido: lo copiado a medias se queda en *.silo_tmp y se retoma en la próxima pasada.")
            res["faltan"].append({"pieza_id": item["pieza_id"], "ruta": item["ruta"], "motivo": "interrumpido"})
            interrumpido = True
        except OSError as e:
            print()
            res["errores"].append({"ruta": item["ruta"], "error": str(e)})
            print(f"    ! {item['ruta']}: {e}")
    res["copiados_bytes"] = copiado

    # 3) Carpetas que ya no le tocan a esta unidad.
    for ruta in sobrantes:
        print(f"    sobrante  {ruta}")
    if sobrantes and purgar and not dry_run and not interrumpido:
        if input(f"    Escribe BORRAR para borrar {len(sobrantes)} carpeta(s) sobrante(s) de esta unidad: ").strip() == "BORRAR":
            for ruta in sobrantes:
                shutil.rmtree(raiz / ruta, ignore_errors=True)
            sobrantes = []
    res["sobrantes"] = sobrantes + res["sobrantes"]

    if not dry_run:
        _limpiar_cubos_vacios(raiz)
    if interrumpido:
        res["interrumpido"] = True
    return res


def _manifiesto_destino(raiz: Path, unidad_id: int) -> dict:
    """Mismo formato que el del Maestro, con la ruta relativa (cubo/carpeta) como clave."""
    carpetas = {}
    por_id, _ = indexar_destino(raiz)
    for ruta in por_id.values():
        ficheros = [{"nombre": n, "tamano_bytes": st.st_size, "mtime": int(st.st_mtime)} for n, st in _ficheros_en(raiz / ruta).items()]
        carpetas[ruta] = {"firma": firma_carpeta(ficheros), "ficheros": {f["nombre"]: {"t": f["tamano_bytes"], "m": f["mtime"], "h": None} for f in ficheros}}
    return {"formato": 1, "unidad_id": unidad_id, "generado_en": time.strftime("%Y-%m-%dT%H:%M:%S"), "carpetas": carpetas}


def propagar_a_copias(config: dict, ids: list[int], solo_renombrar: bool, purgar: bool, dry_run: bool) -> int:
    destinos = api_post(config, "/silo/agente/destinos", {}).get("destinos", [])
    if ids:
        destinos = [d for d in destinos if d["unidad_id"] in ids]
        desconocidos = set(ids) - {d["unidad_id"] for d in destinos}
        if desconocidos:
            print(f"! No son unidades de copia (nivel 2/3 o espejo): {sorted(desconocidos)}")
    else:
        destinos = [d for d in destinos if not d["pendiente"]["al_dia"]]
        if solo_renombrar:
            destinos = [d for d in destinos if d["pendiente"]["renombrar"] or d.get("espejo_de")]

    if not destinos:
        print("Todas las copias están al día." if not ids else "Nada que hacer.")
        return 0

    print(f"{'Renombrados' if solo_renombrar else 'Copia'} pendiente en {len(destinos)} unidad(es):")
    for d in destinos:
        p = d["pendiente"]
        detalle = "por detrás del Maestro" if d.get("espejo_de") else f"{p['piezas']} carpeta(s) por copiar ({_tamano(p['bytes'])}), {p['renombrar']} renombrado(s)"
        print(f"  · {_nombre_unidad(d)} — {'al día' if p['al_dia'] else detalle}")

    errores = 0
    try:
        for d in destinos:
            print(f"\n== {_nombre_unidad(d)} ==")
            plan = api_post(config, f"/silo/agente/unidades/{d['unidad_id']}/plan-copia", {})
            raiz = pedir_unidad(config, d, plan["unidad"].get("fichero_control") or {}, dry_run)
            if raiz is None:
                print("  Saltada.")
                continue

            res = sincronizar_destino(config, d, raiz, plan, solo_renombrar, purgar, dry_run)
            if dry_run:
                print("  (--dry-run: no se ha tocado el disco ni la web)")
                continue

            res["hash_origen"] = plan.get("hash_origen")
            r = api_post(config, f"/silo/agente/unidades/{d['unidad_id']}/resultado-copia", res)
            errores += len(res["errores"])
            print(f"  {r.get('resumen')}")
            for f in res["faltan"][:10]:
                print(f"    sin completar: {f['ruta']} ({f['motivo']})")
            if len(res["faltan"]) > 10:
                print(f"    … y {len(res['faltan']) - 10} más")

            try:
                manifiesto = _manifiesto_destino(raiz, d["unidad_id"])
                escribir_atomico(raiz / MANIFIESTO, json.dumps(manifiesto, ensure_ascii=False).encode("utf-8"))
                sinc = api_post(config, f"/silo/agente/unidades/{d['unidad_id']}/sincronizada", {"hash_indice": hash_indice(manifiesto)})
                escribir_atomico(raiz / ".silo_unit.json", json.dumps(sinc.get("fichero_control") or {}, ensure_ascii=False, indent=4).encode("utf-8"))
            except (OSError, RuntimeError) as e:
                print(f"  ! no se pudo guardar el manifiesto: {e}")
            guardar_catalogo(config, d["unidad_id"], raiz)

            if res.get("interrumpido"):
                raise Abortar()
            print("  Listo: ya puedes desconectarla.")
    except (Abortar, KeyboardInterrupt):
        print("\nTerminado a petición. Lo que quede se retoma la próxima vez.")

    return 1 if errores else 0


def modo_daemon(config: dict, intervalo: int) -> int:
    """
    Sondeo periódico para el botón "Solicitar escaneo" de /silo/unidades: a
    diferencia de una pasada manual (que siempre escanea), aquí solo se
    escanea una unidad cuando la web dejó de verdad una tarea
    `escaneo_maestro` pendiente — si no, sería martirizar el disco cada
    `intervalo` segundos sin motivo. Pensado para dejarlo corriendo en una
    terminal (o de fondo) mientras el disco está conectado.
    """
    print(f"Daemon: sondeando cada {intervalo}s (Ctrl+C para parar). Solo escanea cuando la web lo pide.")
    while True:
        try:
            resultado = handshake(config)
            for u in resultado.get("desconocidas", []):
                print(f"  ! unidad no reconocida por la web: {u}")

            for cfg_unidad in config["unidades"]:
                u = next(
                    (x for x in resultado.get("unidades", [])
                     if x["unidad_id"] == cfg_unidad.get("unidad_id") or x.get("ruta_montaje") == cfg_unidad["ruta"]),
                    None,
                )
                if not u or u.get("nivel") != 1 or u.get("espejo_de"):
                    continue

                tarea = tarea_pendiente(u, "escaneo_maestro")
                if not tarea:
                    continue

                print(f"  tarea #{tarea['id']}: escaneo solicitado desde la web para unidad #{u['numero']} <- {cfg_unidad['ruta']}")
                escanear_unidad(config, u, cfg_unidad["ruta"], dry_run=False, tarea_id=tarea["id"])
        except RuntimeError as e:
            print(f"  ! {e}")

        time.sleep(intervalo)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--dry-run", action="store_true", help="escanea y muestra el resultado, no llama a la API de escaneo")
    parser.add_argument("--solo-handshake", action="store_true", help="solo resuelve unidades + tareas pendientes, no escanea disco")
    parser.add_argument("--daemon", action="store_true", help="se queda corriendo, sondeando cada --intervalo segundos; solo escanea cuando la web pide un escaneo (botón en /silo/unidades)")
    parser.add_argument("--intervalo", type=int, default=20, help="segundos entre sondeos en modo --daemon (por defecto 20)")
    parser.add_argument("--etiquetar-contenido", action="store_true", help="herramienta puntual: propone (o aplica con --aplicar) la etiqueta de contenido Fotos/Vídeos/Montajes que falte en el tema de cada carpeta, según lo que haya de verdad dentro (Montajes es heurística: solo vídeos y pocos). No toca la API/BD.")
    parser.add_argument("--aplicar", action="store_true", help="con --etiquetar-contenido, renombra de verdad en disco en vez de solo listar la propuesta")
    parser.add_argument("--verificar", action="store_true", help="N3: re-hashea TODOS los ficheros (lento) para detectar corrupción silenciosa; manda todas las carpetas completas")
    parser.add_argument("--restaurar-catalogo", type=int, metavar="UNIDAD_ID", help="sube a la web la réplica del catálogo (.catalogo.sql.gz) de esa unidad y restaura la BD de Silo con ella (pide confirmación)")
    parser.add_argument("--copiar", nargs="*", type=int, metavar="UNIDAD_ID", help="propagación física: pide uno a uno los USB de nivel 2/3 y espejos con trabajo pendiente (o los indicados) y les copia/renombra lo que les toca, reconciliando por ID")
    parser.add_argument("--renombrar", nargs="*", type=int, metavar="UNIDAD_ID", help="como --copiar pero solo propaga los cambios de nombre del Maestro (no copia nada, no hace falta el Maestro)")
    parser.add_argument("--purgar", action="store_true", help="con --copiar/--renombrar: borra de la copia las carpetas y ficheros que ya no le tocan (pide escribir BORRAR)")
    args = parser.parse_args()

    config = cargar_config()

    if args.copiar is not None or args.renombrar is not None:
        solo_renombrar = args.copiar is None
        return propagar_a_copias(config, args.renombrar if solo_renombrar else args.copiar, solo_renombrar, args.purgar, args.dry_run)

    if args.restaurar_catalogo:
        return restaurar_catalogo(config, args.restaurar_catalogo)

    if args.etiquetar_contenido:
        return etiquetar_contenido(config, args.aplicar)

    if args.daemon:
        return modo_daemon(config, args.intervalo)

    print("Handshake...")
    resultado = handshake(config)

    for u in resultado.get("desconocidas", []):
        print(f"  ! unidad no reconocida por la web: {u}")

    # Empareja por unidad_id/ruta, no por índice de lista: el orden que
    # devuelve la web no tiene por qué coincidir con config["unidades"].
    resueltas_por_ruta = {}
    for cfg_unidad in config["unidades"]:
        for u in resultado.get("unidades", []):
            if u["unidad_id"] == cfg_unidad.get("unidad_id") or u.get("ruta_montaje") == cfg_unidad["ruta"]:
                resueltas_por_ruta[cfg_unidad["ruta"]] = u
                break

    if args.solo_handshake:
        for ruta, u in resueltas_por_ruta.items():
            print(f"  unidad #{u['numero']} (id={u['unidad_id']}) <- {ruta}, {len(u['tareas'])} tarea(s) pendiente(s)")
        return 0

    print("Escaneando...")
    for cfg_unidad in config["unidades"]:
        u = resueltas_por_ruta.get(cfg_unidad["ruta"])
        if not u:
            print(f"  ! {cfg_unidad['ruta']}: la web no la reconoce (¿falta dar de alta la unidad o su ruta de montaje?), se salta.")
            continue
        if u.get("nivel") != 1 or u.get("espejo_de"):
            continue  # unidad de copia (nivel 2/3 o espejo): se rellena con --copiar, no se escanea
        # Pasada manual: escanea siempre, y si de paso hay un escaneo
        # pedido desde la web para esta unidad, lo cierra con este mismo
        # resultado en vez de dejarlo esperando al agente en --daemon.
        tarea = tarea_pendiente(u, "escaneo_maestro")
        escanear_unidad(config, u, cfg_unidad["ruta"], args.dry_run, tarea_id=tarea["id"] if tarea else None, verificar=args.verificar)

    return 0


if __name__ == "__main__":
    sys.exit(main())
