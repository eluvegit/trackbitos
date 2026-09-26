#!/usr/bin/env python3
"""
Agente de Silo — ejecutor tonto que habla por API con la web (ver
docs/silo-ingesta-propagacion.md): no decide nada, hace `os.scandir` real
del primer nivel del root de cada unidad configurada y reporta lo que
encuentra; la web (App\\Controllers\\Silo\\Agente) es quien clasifica cada
entrada y decide qué se ingesta. Primer esbozo: solo Fase 1 (ingesta del
Maestro), sin hashing todavía (los `ficheros` van sin `hash`, la web los
acepta igual) ni detección de cambios N0-N3 ni propagación física — ver
README.md de este directorio para el alcance exacto.

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


def escanear_primer_nivel(ruta: Path) -> list[dict]:
    """
    Solo el primer nivel del root (plan Silo: las carpetas-pieza cuelgan
    directas de la raíz del Maestro, sin contenedores de año/temática por
    encima). Para cada carpeta lista también sus ficheros sueltos (nombre +
    tamaño, sin hash todavía) — la clasificación candidata/saltada (y el
    motivo) la hace la web, aquí solo se reporta lo que hay en disco.
    """
    entradas = []
    with os.scandir(ruta) as it:
        for entrada in sorted(it, key=lambda e: e.name.lower()):
            item = {"nombre": entrada.name, "es_carpeta": entrada.is_dir()}
            if entrada.is_dir():
                item["ficheros"] = [
                    {"nombre": f.name, "tamano_bytes": f.stat().st_size}
                    for f in sorted(os.scandir(entrada.path), key=lambda e: e.name.lower())
                    if f.is_file()
                ]
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


def generar_proxies_pieza(config: dict, pieza_id: int, ruta_carpeta: Path, ficheros: list[dict]) -> None:
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
    primero = True  # se apaga tras la PRIMERA subida que de verdad se envía, no tras la primera tarea (si esa falla al generar, el flag de reemplazo tiene que esperar a la que sí lo consiga)
    with tempfile.TemporaryDirectory(prefix="silo_proxy_") as tmp:
        for tipo, orden, nombre_fichero, segundo in tareas:
            destino = Path(tmp) / f"{tipo}-{orden}.webp"
            origen = ruta_carpeta / nombre_fichero
            ok = _generar_proxy_foto(origen, destino) if tipo == "foto" else _generar_proxy_video(origen, destino, segundo)
            if not ok:
                print(f"      ! no se pudo generar el proxy de «{nombre_fichero}», se salta.")
                continue

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

    if subidos:
        print(f"      proxies: {subidos}/{len(tareas)} generado(s) y subido(s).")


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


def handshake(config: dict) -> dict:
    unidades = [{"unidad_id": u.get("unidad_id"), "ruta_montaje": u["ruta"]} for u in config["unidades"]]
    return api_post(config, "/silo/agente/handshake", {"unidades": unidades})


def tarea_pendiente(unidad: dict, tipo: str) -> dict | None:
    """Primera tarea de `tipo` que la web dejó pendiente/en_curso para esta unidad (ver Agente::handshake)."""
    for t in unidad.get("tareas", []):
        if t.get("tipo") == tipo and t.get("estado") in ("pendiente", "en_curso"):
            return t
    return None


def escanear_unidad(config: dict, unidad_id: int, ruta: str, dry_run: bool, tarea_id: int | None = None) -> None:
    ruta_path = Path(ruta)
    if not ruta_path.is_dir():
        print(f"  ! {ruta}: no existe o no está montada ahora mismo, se salta.")
        return

    entradas = escanear_primer_nivel(ruta_path)
    print(f"  {ruta}: {len(entradas)} entrada(s) en el primer nivel.")

    if dry_run:
        for e in entradas:
            tipo = "carpeta" if e["es_carpeta"] else "fichero suelto"
            extra = f", {len(e.get('ficheros', []))} fichero(s)" if e["es_carpeta"] else ""
            print(f"    - {e['nombre']}  ({tipo}{extra})")
        print("  (--dry-run: no se ha mandado nada a la web)")
        return

    cuerpo = {
        "unidad_id": unidad_id,
        "lista_negra": config.get("lista_negra", []),
        "entradas": entradas,
    }
    if tarea_id:
        # Cierra la tarea que la web dejó pendiente (botón "Solicitar
        # escaneo" en /silo/unidades) — Agente::escaneo() la marca
        # hecha/error con este resumen como resultado.
        cuerpo["tarea_id"] = tarea_id

    resultado = api_post(config, "/silo/agente/escaneo", cuerpo)

    print(f"    ingestadas: {len(resultado.get('ingestadas', []))}")
    for s in resultado.get("saltadas", []):
        print(f"    saltada:    {s['nombre']}  ({s['motivo']})")
    for d in resultado.get("desaparecidas", []):
        print(f"    BORRADA:    {d['nombre']}  (ya no está en el Maestro)")
    for err in resultado.get("errores", []):
        print(f"    ERROR:      {err['nombre']}  -> {err['error']}")

    pendientes = [i for i in resultado.get("ingestadas", []) if i.get("necesita_proxies")]
    if pendientes and _ffmpeg_disponible():
        ficheros_por_nombre = {e["nombre"]: e.get("ficheros", []) for e in entradas}
        print(f"    proxies pendientes: {len(pendientes)} carpeta(s)")
        for item in pendientes:
            print(f"    generando proxies: {item['nombre']}")
            generar_proxies_pieza(
                config,
                item["pieza_id"],
                ruta_path / item["nombre"],
                ficheros_por_nombre.get(item["nombre"], []),
            )


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
                if not u:
                    continue

                tarea = tarea_pendiente(u, "escaneo_maestro")
                if not tarea:
                    continue

                print(f"  tarea #{tarea['id']}: escaneo solicitado desde la web para unidad #{u['numero']} <- {cfg_unidad['ruta']}")
                escanear_unidad(config, u["unidad_id"], cfg_unidad["ruta"], dry_run=False, tarea_id=tarea["id"])
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
    args = parser.parse_args()

    config = cargar_config()

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
        # Pasada manual: escanea siempre, y si de paso hay un escaneo
        # pedido desde la web para esta unidad, lo cierra con este mismo
        # resultado en vez de dejarlo esperando al agente en --daemon.
        tarea = tarea_pendiente(u, "escaneo_maestro")
        escanear_unidad(config, u["unidad_id"], cfg_unidad["ruta"], args.dry_run, tarea_id=tarea["id"] if tarea else None)

    return 0


if __name__ == "__main__":
    sys.exit(main())
