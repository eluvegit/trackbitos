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

Sin dependencias fuera de la librería estándar (mismo criterio que
piezas-cli/trackbitos.py): tiene que arrancar en cualquier máquina sin
instalar nada.
"""
from __future__ import annotations

import argparse
import json
import os
import ssl
import sys
import urllib.error
import urllib.request
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


def handshake(config: dict) -> dict:
    unidades = [{"unidad_id": u.get("unidad_id"), "ruta_montaje": u["ruta"]} for u in config["unidades"]]
    return api_post(config, "/silo/agente/handshake", {"unidades": unidades})


def escanear_unidad(config: dict, unidad_id: int, ruta: str, dry_run: bool) -> None:
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

    resultado = api_post(config, "/silo/agente/escaneo", {
        "unidad_id": unidad_id,
        "lista_negra": config.get("lista_negra", []),
        "entradas": entradas,
    })

    print(f"    ingestadas: {len(resultado.get('ingestadas', []))}")
    for s in resultado.get("saltadas", []):
        print(f"    saltada:    {s['nombre']}  ({s['motivo']})")
    for err in resultado.get("errores", []):
        print(f"    ERROR:      {err['nombre']}  -> {err['error']}")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--dry-run", action="store_true", help="escanea y muestra el resultado, no llama a la API de escaneo")
    parser.add_argument("--solo-handshake", action="store_true", help="solo resuelve unidades + tareas pendientes, no escanea disco")
    args = parser.parse_args()

    config = cargar_config()

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
        escanear_unidad(config, u["unidad_id"], cfg_unidad["ruta"], args.dry_run)

    return 0


if __name__ == "__main__":
    sys.exit(main())
