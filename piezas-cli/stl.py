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
)

VERSION = "0.1.0"

STL_CONFIG_PATH = CONFIG_DIR / "stl.config.json"
EXPORT_SCRIPT = Path(__file__).resolve().parent / "export_batch.py"

DEFAULT_BACKUP_DIR = CONFIG_DIR / "biblioteca"
DEFAULT_PLACAS_DIR = CONFIG_DIR / "placas"
DEFAULT_ESCALA = 10.0

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
    """
    if STL_CONFIG_PATH.is_file():
        return json.loads(STL_CONFIG_PATH.read_text(encoding=ENCODING_LECTURA))

    config = {
        "blender": autodetectar_blender(),
        "backup_dir": str(DEFAULT_BACKUP_DIR),
        "placas_dir": str(DEFAULT_PLACAS_DIR),
        "escala": DEFAULT_ESCALA,
    }
    guardar_stl_config(config)

    print(f"\n  · creado {STL_CONFIG_PATH} con:")
    print(f"      blender:    {config['blender'] or '(no detectado — edita el fichero a mano)'}")
    print(f"      backup_dir: {config['backup_dir']}")
    print(f"      placas_dir: {config['placas_dir']}\n")

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
    de Blender por sí solo no distingue "no había ningún STL_*" (normal, se
    apunta en FALTAN.txt) de "reventó a mitad" (error real).
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

def _procesar_simple(config: dict, blender_exe: str, escala: float, carpeta: Path, version: dict, sin_blend: bool) -> str:
    """
    Devuelve "omitida" (nada cambió desde la última vez), "vacia" (se
    exportó pero el .blend no tenía ningún STL_* dentro) o "exportada".
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
    backup_dir = Path(stl_cfg.get("backup_dir") or DEFAULT_BACKUP_DIR)
    escala = float(stl_cfg.get("escala") or DEFAULT_ESCALA)

    catalogo = api_get(config, "/variantes")
    variantes = catalogo.get("variantes", [])
    if not variantes:
        print("\n  · no hay ninguna variante todavía en el catálogo.\n")
        return 0

    exportadas, omitidas, referencias, sin_version, vacias, errores = [], [], [], [], [], []

    for v in variantes:
        etiqueta = nombre_completo(v)
        version = v.get("version_para_imprimir")
        if not version:
            sin_version.append(etiqueta)
            continue

        carpeta = carpeta_version(backup_dir, v.get("categoria_nombre"), v.get("familia_nombre"), v["nombre"], version["numero"])

        try:
            componentes = api_get(config, f"/variante/{v['id']}/composicion").get("componentes", [])
        except RuntimeError as e:
            errores.append(f"{etiqueta}: no se pudo consultar su composición ({e})")
            continue

        try:
            if componentes:
                resultado = _procesar_compuesta(config, carpeta, version, componentes, args.sin_blend)
            else:
                resultado = _procesar_simple(config, blender_exe, escala, carpeta, version, args.sin_blend)
        except (RuntimeError, subprocess.TimeoutExpired) as e:
            errores.append(f"{etiqueta}: {e}")
            continue

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
        print(f"  ⚠ {len(vacias)} con .blend pero sin ninguna STL_* dentro:")
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


def _asegurar_stl_en_biblioteca(config: dict, blender_exe: str, escala: float, backup_dir: Path, hoja: dict) -> Path:
    """
    Reutiliza la caché de `biblioteca` (misma carpeta, mismo manifest.json,
    mismo salto incremental) en vez de exportar de cero: si esta pieza+
    versión ya se generó al construir `biblioteca` o al montar otra placa,
    no se vuelve a invocar Blender — no es solo ahorro de espacio (la
    motivación original del plan), también de tiempo. El .blend nunca se
    persiste aquí (`sin_blend=True`): decisión 7, "placa nunca baja .blend".
    """
    carpeta = carpeta_version(backup_dir, hoja["categoria"], hoja["familia"], hoja["variante"], hoja["version"]["numero"])
    _procesar_simple(config, blender_exe, escala, carpeta, hoja["version"], sin_blend=True)
    return carpeta


def cmd_placa(args) -> int:
    config = cargar_config()
    stl_cfg = cargar_stl_config()
    blender_exe = exigir_blender(stl_cfg)
    backup_dir = Path(stl_cfg.get("backup_dir") or DEFAULT_BACKUP_DIR)
    placas_dir = Path(stl_cfg.get("placas_dir") or DEFAULT_PLACAS_DIR)
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

    for hoja in hojas:
        try:
            carpeta_cache = _asegurar_stl_en_biblioteca(config, blender_exe, escala, backup_dir, hoja)
        except (RuntimeError, subprocess.TimeoutExpired) as e:
            faltantes.append(f"{_etiqueta_nodo(hoja)} v{hoja['version']['numero']:03d}: {e}")
            continue

        stls = sorted(carpeta_cache.glob("*.stl"))
        if not stls:
            faltantes.append(f"{_etiqueta_nodo(hoja)} v{hoja['version']['numero']:03d}: sin ninguna STL_* en el .blend.")
            continue

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
# CLI
# --------------------------------------------------------------------------

def main(argv: Optional[list] = None) -> int:
    for flujo in (sys.stdout, sys.stderr):
        if hasattr(flujo, "reconfigure"):
            flujo.reconfigure(encoding="utf-8", errors="replace")

    parser = argparse.ArgumentParser(prog="stl", description="Exportación .blend -> STL de piezas-cli (Trackbitos).")
    subs = parser.add_subparsers(dest="comando", required=True)

    p = subs.add_parser(
        "biblioteca",
        help="Backup local de STL + .blend de todo el catálogo, con salto incremental por hash.",
    )
    p.add_argument("--sin-blend", action="store_true", dest="sin_blend", help="No guardar copia del .blend, solo los STL.")
    p.set_defaults(func=cmd_biblioteca)

    p = subs.add_parser(
        "placa",
        help="STL de una placa (id o nombre) en una carpeta plana, lista para ChituBox.",
    )
    p.add_argument("placa", help="Id o nombre (o trozo del nombre) de la placa.")
    p.add_argument(
        "--como-anotado", action="store_true", dest="como_anotado",
        help="Usa la versión anotada de cada componente en vez de la vigente, para reproducir la placa bit a bit.",
    )
    p.set_defaults(func=cmd_placa)

    args = parser.parse_args(argv)

    try:
        return args.func(args)
    except RuntimeError as e:
        print(f"\n  ⚠ {e}\n", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
