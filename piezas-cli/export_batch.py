"""
Exporta a STL las collections "STL" / "STL <trozo>" de un .blend, una vez
por collection. Se ejecuta DENTRO de Blender, nunca a mano:

    blender --background <fichero.blend> --python export_batch.py -- <carpeta_salida> [escala]

Es la mitad "batch" del plan .blend->STL de piezas-cli/stl.py: éste la llama
por subprocess, un .blend a la vez, y lee el resultado de la última línea de
stdout (prefijo EXPORT_BATCH_RESULT:), no del código de salida a solas — el
código de salida solo distingue "algo se abortó" de "terminó", no qué se
exportó.

Convención real del catálogo del usuario (corregida el 2026-09-02 sobre la
decisión 16 original del plan, que asumía un prefijo "STL_" con guion bajo
que ningún .blend real usa):
- Collection llamada exactamente "STL" → la pieza es un solo trozo; se
  exporta como "completo.stl".
- Collections "STL <nombre del trozo>" (con espacio) → una por trozo, p.ej.
  "STL Brazo izquierdo" y "STL Brazo derecho" en el mismo .blend.
- Cualquier otra collection (referencias, guías, el Playmobil de calibre,
  o algo que solo empiece por las letras "STL" sin ese espacio o coincidencia
  exacta, como "STLibrary") se ignora sin más.
- Una collection SIN ningún mesh dentro (de referencia, vacía a propósito)
  no cuenta como exportada: ni error ni fichero, simplemente no aporta nada
  a este .blend.
"""
from __future__ import annotations

import json
import sys
from pathlib import Path

import bpy

NOMBRE_TROZO_UNICO = "completo"


def _nombre_trozo(nombre_coleccion: str) -> str | None:
    """
    "STL" a secas → pieza de un solo trozo (NOMBRE_TROZO_UNICO). "STL
    <resto>" → el trozo se llama <resto>. Cualquier otra cosa (incluido
    "STLibrary" o "STLalgo", que empiezan por las letras pero no por la
    palabra suelta) no es una collection de exportación → None.
    """
    texto = nombre_coleccion.strip()
    if texto == "STL":
        return NOMBRE_TROZO_UNICO
    if texto.startswith("STL "):
        resto = texto[len("STL "):].strip()
        return resto or NOMBRE_TROZO_UNICO
    return None


def _argumentos() -> list:
    argv = sys.argv
    return argv[argv.index("--") + 1:] if "--" in argv else []


def _error(mensaje: str) -> None:
    # A stderr, no a stdout: stdout se reserva para la línea de resultado que
    # lee stl.py: mezclar aquí un mensaje de error la haría invisible entre
    # el resto de trazas que suelta Blender al arrancar.
    print(f"EXPORT_BATCH_ERROR: {mensaje}", file=sys.stderr)
    sys.exit(1)


def main() -> None:
    argumentos = _argumentos()
    if not argumentos:
        _error("falta la carpeta de salida (primer argumento tras '--').")

    salida = Path(argumentos[0])
    escala = float(argumentos[1]) if len(argumentos) > 1 else 10.0
    salida.mkdir(parents=True, exist_ok=True)

    exportados = []
    vistos = set()

    for coleccion in bpy.data.collections:
        nombre = _nombre_trozo(coleccion.name)
        if nombre is None:
            continue

        if nombre in vistos:
            # Ruidoso a propósito (decisión 16): dos trozos con el mismo
            # nombre en el mismo .blend se pisarían en el zip de la placa sin
            # que nadie lo notara hasta imprimir de menos.
            _error(f"colisión de nombre: dos collections resuelven al trozo '{nombre}' en el mismo .blend.")
        vistos.add(nombre)

        # all_objects, no .objects: una collection puede anidar otras (p.ej.
        # variantes de una misma pieza agrupadas), y .objects se queda solo
        # con los miembros directos.
        objetos = [o for o in coleccion.all_objects if o.type == "MESH"]
        if not objetos:
            continue

        bpy.ops.object.select_all(action="DESELECT")
        for objeto in objetos:
            objeto.select_set(True)
        bpy.context.view_layer.objects.active = objetos[0]

        destino = salida / f"{nombre}.stl"
        bpy.ops.wm.stl_export(
            filepath=str(destino),
            export_selected_objects=True,
            apply_modifiers=True,
            global_scale=escala,
        )
        exportados.append(nombre)

    # Sin ninguna collection "STL"/"STL <trozo>" exportada no se aborta
    # (decisión 16): se lista en FALTAN.txt más arriba, en stl.py, que es
    # quien conoce el resto del catálogo — este script solo sabe de ESTE
    # .blend.
    print("EXPORT_BATCH_RESULT:" + json.dumps({
        "exportados": exportados,
        "blender_version": bpy.app.version_string,
    }))


main()
