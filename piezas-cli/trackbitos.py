#!/usr/bin/env python3
"""
Cliente de Piezas (Trackbitos) — versionado de modelos 3D.

Fase 4: el comando "estado" ya consulta la API real (GET
/variante/{id}/estado) para el hash de la nube, en vez del fichero local
.nube.json de la fase 3 — la tabla de decisión (evaluar) no cambió nada,
solo cambió de dónde sale hash_nube.

El script razona, no el usuario (spec 5.2): "estado" imprime un veredicto
en lenguaje natural y el comando exacto a ejecutar, nunca hashes en crudo
para que los compares tú.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import sys
import urllib.error
import urllib.request
import uuid as uuidlib
from pathlib import Path
from typing import Optional

SENTINEL_NAME = ".sesion.json"
CONFIG_DIR = Path.home() / ".trackbitos"
CONFIG_PATH = CONFIG_DIR / "config.json"


def sha256_de(ruta: Path) -> str:
    h = hashlib.sha256()
    with ruta.open("rb") as f:
        for bloque in iter(lambda: f.read(1 << 20), b""):
            h.update(bloque)
    return h.hexdigest()


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
        return json.loads(ruta.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return None


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

    config = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
    if not config.get("uuid"):
        config["uuid"] = str(uuidlib.uuid4())
        guardar_config(config)

    return config


def guardar_config(config: dict) -> None:
    CONFIG_DIR.mkdir(parents=True, exist_ok=True)
    CONFIG_PATH.write_text(json.dumps(config, indent=2), encoding="utf-8")


def api_get(config: dict, ruta: str) -> dict:
    url = config["url_base"].rstrip("/") + ruta
    peticion = urllib.request.Request(url, headers={
        "Authorization": f"Bearer {config['token']}",
        "Accept": "application/json",
    })
    try:
        with urllib.request.urlopen(peticion, timeout=10) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        detalle = e.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"la API respondió {e.code}: {detalle.strip()}") from e
    except urllib.error.URLError as e:
        raise RuntimeError(f"no se pudo conectar con {config.get('url_base')}: {e.reason}") from e


def evaluar(hash_local: Optional[str], hash_origen: Optional[str], hash_nube: Optional[str]) -> dict:
    """
    Tabla 4.3 de la spec, como función pura (sin tocar disco) para poder
    probarla directamente. Devuelve un dict con la situación, el veredicto
    de cada comparación y qué acción ofrecer.
    """
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
        cabecera = f"{sentinel.get('variante', '?')} · rama {sentinel.get('rama', '?')} · sesión {sentinel.get('sesion', '?')}"
    else:
        cabecera = str(directorio)
    print(f"\n{cabecera}\n")

    situacion = resultado["situacion"]

    if situacion == "corrupto":
        print(f"  ⚠ {resultado['mensaje']}")
        print("\n  → Revisa el directorio a mano. No se toca nada hasta que esté claro qué hay ahí.\n")
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
        print(f"\n  → Ejecuta: trackbitos subir\n")
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
        "descargar": "Descarga la sesión nueva. Es seguro: no tienes cambios locales que perder.",
        "subir": "Ejecuta: trackbitos subir",
        "subir_como_nueva": "Divergencia real. Ejecuta: trackbitos subir (se guardará como sesión nueva; nada se fusiona).",
    }
    print(f"\n  → {acciones.get(resultado['accion'], resultado['mensaje'])}\n")


def cmd_estado(args: argparse.Namespace) -> int:
    directorio = Path(args.dir).resolve()
    if not directorio.is_dir():
        print(f"El directorio {directorio} no existe.", file=sys.stderr)
        return 2

    sentinel = cargar_json(directorio / SENTINEL_NAME)
    blend = encontrar_blend(directorio)

    hash_local = sha256_de(blend) if blend else None
    hash_origen = sentinel.get("hash_origen") if sentinel else None

    hash_nube = None
    if sentinel and "variante_id" in sentinel:
        # Sin .sesion.json no hay variante_id que consultar — evaluar()
        # ya trata ese caso como divergencia sin necesidad de red.
        try:
            config = cargar_config()
            estado_api = api_get(config, f"/variante/{sentinel['variante_id']}/estado")
            hash_nube = estado_api.get("hash_nube")
        except RuntimeError as e:
            print(f"  ⚠ No se pudo consultar la nube: {e}\n", file=sys.stderr)

    resultado = evaluar(hash_local, hash_origen, hash_nube)
    imprimir_veredicto(directorio, sentinel, resultado)

    # Código de salida distinto de cero cuando hay algo pendiente de
    # resolver: útil para scripts/cron, y coherente con "bajar se niega".
    return 0 if resultado["accion"] in ("borrable", "descargar") else 1


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

    p_estado = subs.add_parser("estado", help="Diagnóstico del directorio de trabajo actual.")
    p_estado.add_argument("--dir", default=".", help="Directorio de trabajo (por defecto, el actual).")
    p_estado.set_defaults(func=cmd_estado)

    args = parser.parse_args(argv)
    return args.func(args)


if __name__ == "__main__":
    sys.exit(main())
