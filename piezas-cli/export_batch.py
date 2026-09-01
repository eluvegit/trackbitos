"""
Exporta a STL las collections `STL_*` de un .blend, una vez por collection.
Se ejecuta DENTRO de Blender, nunca a mano:

    blender --background <fichero.blend> --python export_batch.py -- <carpeta_salida> [escala]

Es la mitad "batch" del plan .blend->STL de piezas-cli/stl.py: éste la llama
por subprocess, un .blend a la vez, y lee el resultado de la última línea de
stdout (prefijo EXPORT_BATCH_RESULT:), no del código de salida a solas — el
código de salida solo distingue "algo se abortó" de "terminó", no qué se
exportó.

Convención del catálogo (decisión 16 del plan): cada trozo imprimible de la
escena vive en su propia collection cuyo nombre empieza por "STL_"; lo que no
lleva ese prefijo (referencias, guías, el Playmobil de calibre) se ignora sin
más. Una collection SIN ningún mesh dentro (de referencia, vacía a
propósito) no cuenta como exportada: ni error ni fichero, simplemente no
aporta nada a este .blend.
"""
from __future__ import annotations

import json
import sys
from pathlib import Path

import bpy

PREFIJO = "STL_"


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
        if not coleccion.name.startswith(PREFIJO):
            continue

        nombre = coleccion.name[len(PREFIJO):].strip()
        if not nombre:
            continue

        if nombre in vistos:
            # Ruidoso a propósito (decisión 16): dos trozos con el mismo
            # nombre en el mismo .blend se pisarían en el zip de la placa sin
            # que nadie lo notara hasta imprimir de menos.
            _error(f"colisión de nombre: dos collections '{PREFIJO}{nombre}' (o equivalentes) en el mismo .blend.")
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

    # Sin ningún STL_* exportado no se aborta (decisión 16): se lista en
    # FALTAN.txt más arriba, en stl.py, que es quien conoce el resto del
    # catálogo — este script solo sabe de ESTE .blend.
    print("EXPORT_BATCH_RESULT:" + json.dumps({
        "exportados": exportados,
        "blender_version": bpy.app.version_string,
    }))


main()
