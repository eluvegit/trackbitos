# Trackbitos · Piezas — Tutorial de uso

Guía práctica de "qué hago y en qué orden". Para el detalle técnico de cómo está construido, ver `SPEC.md`.

---

## 1. Configurar el CLI (una vez por máquina)

`trackbitos.py` es Python 3 puro, sin dependencias que instalar. Cópialo a cualquier carpeta de cada equipo (Mac y Windows) — no depende de vivir dentro del repo.

Crea `~/.trackbitos/config.json` (en Windows, `C:\Users\<tú>\.trackbitos\config.json`):

```json
{
  "url_base": "https://trackbitos.host/public/piezas/api",
  "token": "<piezas.apiToken del .env del servidor>"
}
```

El `uuid` de máquina se genera solo la primera vez que ejecutas cualquier comando. El nombre de la máquina sale del hostname del equipo: **no hay pantalla para cambiarlo**, así que si quieres que se lea bien en los avisos, ponle nombre al equipo antes de la primera ejecución.

⚠️ El token vive en el `.env` del servidor (`piezas.apiToken`, no versionado). Si trabajas contra producción y contra un entorno local, cada uno tiene el suyo — no asumas que es el mismo.

---

## 2. El reparto de trabajo: web vs. CLI

- **La web** (`/piezas`) es para mirar, juzgar y administrar: catálogo, historial de versiones, marcar impresa/validar/descartar, referencias, renders, STL. Nunca toca el `.blend` de trabajo.
- **El CLI** es el único que toca el `.blend`: abrir sesión, bajar, subir, cerrar, promocionar. Necesita saber desde qué máquina se ejecuta (por eso el UUID), porque hay que cuadrar qué copia vive en qué disco.

Regla mnemónica: si es un fichero que **editas** (el `.blend`), es CLI. Si es un fichero que **generas una vez y adjuntas** (foto, render, STL) o una decisión (validar, descartar), es web.

---

## 3. Flujo completo de una pieza nueva

```bash
mkdir ~/Piezas/torso-recto && cd ~/Piezas/torso-recto
```

1. **Crear la pieza** en la web (`/piezas`, botón "Pieza"). Nace con su variante `base` y su rama de trabajo ya abiertas: no hay que decidir nada más para empezar. Solo se añaden **variantes** si esa pieza acaba teniendo varias líneas de diseño (una silla alta y otra baja); lo normal es no tocarlas.

2. **Abrir sesión** en el CLI, en la carpeta de trabajo de esa pieza:
   ```
   trackbitos bajar torso-recto
   ```
   Como es una variante nueva, no hay nada que descargar: el propio comando lo detecta solo y se limita a reclamar la máquina y abrir la sesión 1. El nombre admite la pieza, la variante o ambas (ver el truco de la sección 5).

3. **Modelar en Blender**, guardando el `.blend` en esa misma carpeta.

4. **Subir** cuando quieras dejar constancia (se puede subir varias veces dentro de la misma sesión):
   ```
   trackbitos subir --log "primer boceto del torso"
   ```

5. **Cerrar la sesión** cuando das esa tanda de trabajo por terminada:
   ```
   trackbitos cerrar
   ```

6. **Promocionar** cuando el estado merece congelarse como versión (crea `v001`, cierra la rama actual y abre `desde-v001` para seguir trabajando):
   ```
   trackbitos promocionar --cambio "primera versión completa del torso" --medidas "alto 45mm, ancho hombros 22mm"
   ```

7. **Exportar el STL desde Blender** y adjuntarlo a esa versión, ya en la **web** (ficha de la variante → tarjeta de la versión → "Adjuntar STL"). Es aparte de promocionar porque no siempre se exporta en el mismo momento, y es inmutable una vez puesto: si el modelo cambia, toca una versión nueva. Puedes adjuntar **varios**, cada uno con su nombre — los brazos por separado, o una pieza más alta que la placa partida en trozos. El `.blend` sigue siendo uno solo: ahí están todas las partes juntas.

8. **Imprimir** la pieza. En la web, marcar esa versión como **Impresa** (con los parámetros de impresión).

9. **Juzgar el resultado**, también en la web:
   - **Validar** → es la buena. La anterior validada (si la había) pasa a "superada" sola.
   - **Descartar** → no sirve, con el motivo. No se borra nunca.

   ¿Y si le das al botón que no era, o escribes mal el motivo? **Deshacer** devuelve la versión a borrador (solo desde "impresa" y "descartada") y vuelves a empezar ese paso. Es para arreglar un error tuyo, no para cambiar de opinión sobre una pieza que ya tienes en la mano: para eso están validar y descartar.

   > **Este paso no se puede saltar.** Mientras una versión siga en "impresa" sin juzgar, la pieza queda parada: `trackbitos bajar` y "Devolver a trabajo" se niegan. Seguir modelando encima sería partir de algo que no sabes si funciona. Si ya sabes que no vale, descártala con el motivo y sigue desde ahí.

10. Si sigues iterando: `trackbitos bajar` te trae el punto de partida de la rama nueva (el `.blend` de la versión que acabas de promocionar), editas, `subir`, `promocionar` otra vez para `v002`, y repites desde el paso 3.

---

### Empezar de un golpe

Para retomar una pieza sin preparar nada a mano:

```
trackbitos trabajar "pincel"
```

Crea la carpeta con el nombre de la pieza (`pincel-de-pintura`) donde estés, baja dentro el `.blend`, abre la sesión y lanza Blender. Si ya estás dentro de esa carpeta, trabaja ahí en vez de anidar otra. Si el nombre encaja con varias piezas, te las lista y **no crea ninguna carpeta** hasta que concretes. `--no-abrir` hace todo menos lanzar el programa.

Es `bajar` con los pasos de alrededor, no un camino distinto: se niega exactamente en los mismos casos (copia viva en la otra máquina, impresión sin juzgar, cambios sin subir aquí), y si se niega no abre nada.

**`trabajar` no puede hacer que tu terminal "entre" en la carpeta que crea** — un proceso hijo no puede cambiar el directorio del shell que lo lanzó, es una limitación del sistema operativo, no un descuido de este script. Lo que sí hace es dejar escrita esa carpeta en `~/.trackbitos/ultimo_directorio`, para que una **función de shell** (no un alias normal — un alias no puede ejecutar un `cd` después de llamar al programa) la lea y haga el `cd` de verdad.

Configúrala una vez, en `~/.zshrc` (macOS) — sustituye la ruta por donde tengas guardado `trackbitos.py`:

```bash
trackbitos() {
    python3 "$HOME/bin/trackbitos.py" "$@"
    local codigo=$?

    if { [ "$1" = "trabajar" ] || [ "$1" = "t" ]; } && [ -f "$HOME/.trackbitos/ultimo_directorio" ]; then
        cd "$(cat "$HOME/.trackbitos/ultimo_directorio")" || true
    fi

    return $codigo
}
```

Recarga la terminal (`source ~/.zshrc` o ábrela de nuevo) y ya está: `trackbitos trabajar pincel` te deja **dentro** de la carpeta, con Blender ya abierto. De paso, esto resuelve también lo de escribir `.././trackbitos.py` cada vez — la función te deja llamar `trackbitos <lo que sea>` desde cualquier carpeta, para cualquier comando, no solo `trabajar`.

Si prefieres no tener una función (por ejemplo en Windows, donde esto no aplica igual), un alias normal sigue sirviendo para lo primero — solo no hará el `cd` automático:

```bash
alias trackbitos="python3 $HOME/bin/trackbitos.py"
```

---

### Recoger la mesa cuando terminas

Cuando ya has subido, cerrado y `trackbitos estado` dice **"Al día"**, la copia de tu disco no aporta nada: lo que vale está en el servidor. `trackbitos limpiar` la aparta a la papelera local (30 días para arrepentirse, `trackbitos papelera` para verla) y se lleva también el `.sesion.json`, así que la carpeta queda limpia de verdad.

Si falta cualquier cosa —algo sin subir, la sesión abierta, una descarga sin cerrar, la nube más adelantada, o ni siquiera se puede preguntar al servidor— **no toca nada** y te dice qué falta, con el mismo diagnóstico de `estado`. No hay `--forzar`: si de verdad quieres tirar algo sin subirlo, eso se borra a mano.

---

## 4. Cambiar de máquina

En el Mac: `trackbitos subir` + `trackbitos cerrar` antes de irte. En Windows: `trackbitos bajar` trae lo último. Si alguien se olvida de cerrar y lo detectas desde la otra máquina, la web tiene **"Forzar cierre"** en la ficha de la variante — es la válvula de escape para cuando la copia ya no se puede recuperar (disco formateado, portátil roto), no un atajo del día a día.

---

## 5. Comandos del CLI

| Comando | Qué hace |
|---|---|
| `trackbitos estado` | El más usado: diagnóstico en lenguaje natural de la carpeta actual — qué hacer, sin comparar hashes tú. |
| `trackbitos catalogo` | El catálogo completo desde la terminal, agrupado por categoría: qué hay y por dónde va cada cosa. |
| `trackbitos variantes <pieza>` | Zoom sobre una pieza: cuántas variantes tiene y cómo se llama cada una. |
| `trackbitos actualizar` | Comprueba si hay una versión nueva del cliente y, si la hay, se reemplaza a sí mismo. |
| `trackbitos trabajar <pieza>` | **El atajo del día a día**: crea la carpeta, baja dentro y abre el `.blend` en Blender, todo de un golpe. |
| `trackbitos bajar [<pieza>]` | Descarga la mesa de trabajo y abre sesión — o, si la pieza es nueva y no hay nada que descargar, solo abre sesión. Se niega si hay cambios sin subir. |
| `trackbitos ver <pieza>` | Descarga solo para mirar (comparar una cota); no abre sesión ni consume número. |
| `trackbitos subir [--log "..."]` | Sube el `.blend` de la carpeta actual. |
| `trackbitos cerrar [--sin-cambios]` | Cierra la sesión, o la descarga sin subir fichero. |
| `trackbitos promocionar --cambio "..." [--medidas "..."]` | Congela la última sesión subida como versión nueva. |
| `trackbitos limpiar` | Recoge la mesa: aparta el `.blend` de esta carpeta a la papelera **si ya está todo subido y cerrado**. Si falta algo, no toca nada y te dice qué. |
| `trackbitos papelera` | Qué hay apartado localmente y cuándo caduca (30 días). |

Todos aceptan `--dir` (por defecto, la carpeta actual). `bajar`/`ver` aceptan `--ignorar-pendiente` para saltarse el aviso de copia viva en la otra máquina.

Truco: si usas **una carpeta por pieza**, `trackbitos estado` ya sabe en qué variante/rama/sesión estás con solo mirar el `.sesion.json` que él mismo dejó — no hace falta escribir el nombre salvo al empezar algo nuevo.

**Cómo nombrar la pieza** en los comandos que lo piden (`bajar`, `ver`): vale el nombre de la **pieza**, el de la **variante**, trozos de cualquiera de los dos, los dos juntos, o el id. Para una pieza "Pincel de pintura" con su variante "base", todo esto funciona igual:

```
trackbitos bajar pincel
trackbitos bajar base
trackbitos bajar "pincel base"
trackbitos bajar 3
```

Se busca sobre pieza + variante porque es como se piensa: la pieza lleva el nombre real y la variante suele ser una etiqueta genérica —`base` se repite en **todas** las piezas, porque es la que se crea sola. Por eso, si lo que escribes encaja con varias, te las lista **con su pieza delante** (`Pincel de pintura / base`, `Casco romano / base`) para que puedas concretar; un nombre de variante exacto siempre gana sobre las coincidencias parciales.

---

## 6. La web, por secciones

- **`/piezas`** — catálogo: cada pieza con sus variantes, en qué punto está (versión validada, o "sin empezar" / "sin versión" / "para imprimir" / "sin validar" / "no sirve") y si además hay trabajo encima sin promocionar ("modificando"), más los avisos de sesión abierta o descargas pendientes. Aquí también se dan de alta piezas y variantes, y se suben **referencias** (fotos del original con medidas de calibre, comunes a toda la pieza).

  Sobre la tabla hay una fila de **filtros**, con cuántas piezas hay en cada uno: *Definitivas*, *Imprimir · con STL*, *Imprimir · falta STL*, *Sin STL*, *Sin validar*, *No sirven*, *Modificando* y *Sin empezar*. Se pulsa uno cada vez (volver a pulsarlo lo quita) y se combina con el buscador. Los dos de imprimir están separados a propósito porque lo siguiente que hay que hacer es distinto: una va a la impresora, la otra hay que exportarla antes — y en esas filas el icono de descarga de al lado te baja el `.blend` (copia de solo lectura) para hacerlo.
- **Ficha de variante** (`/piezas/variante/{id}`) — historial completo de versiones con sus **renders** (imágenes por versión, para ver la evolución), botones de los verbos (marcar impresa, validar, descartar, devolver a trabajo, derivar variante), los **STL** de cada versión (uno por trozo a imprimir), y una caja "Desde tu máquina" con los comandos ya escritos con el nombre exacto, listos para copiar.

---

## 7. Imágenes y STL — quién sirve qué

| Fichero | Quién lo sube/baja | Por qué |
|---|---|---|
| `.blend` de trabajo | Solo el CLI | Necesita identidad de máquina para cuadrar qué copia vive en qué disco (invariante 8). |
| Referencias (de la pieza) | Web | Fotos que se miran, no se editan iterativamente. |
| Renders (versión) | Web | Igual: resultado visual de una iteración, no algo que se sincroniza entre discos. |
| STL (versión) | Web | Se exporta una vez y se adjunta; inmutable después. Se descarga como adjunto (para el laminador), no se muestra inline. |

---

## 8. SKU, galería y placa de impresión

**SKU** — campo de texto libre y opcional en cada variante (al dar de alta la pieza, al crear una variante, o con el lápiz junto al nombre en la ficha). Es solo una referencia manual tuya: cuando alguien te pide una pieza por el código que usa en tu tienda/Etsy, lo apuntas aquí para poder buscarla luego. Trackbitos no sincroniza con esa otra web, solo guarda el mismo texto. En el índice (`/piezas`) hay un buscador que filtra por nombre o SKU al escribir.

**Galería** (`/piezas/galeria`) — solo las piezas con **versión validada**: es la vista de "qué tengo listo para imprimir", no el catálogo de trabajo en curso. La miniatura sale del render más reciente de esa versión (o de la referencia de la pieza si no hay render).

**Placa de impresión** — un carrito para juntar varias piezas y descargar todos sus STL de golpe:
1. Desde la galería (o desde la ficha de una variante validada, junto al botón "Descargar STL"), pulsa **"Añadir a la placa"** en cada pieza que quieras imprimir junta.
2. Cuando tengas las que quieres, **"Descargar placa"** te da un `.zip` con todos los STL, listo para abrir en el laminador y colocar en una plancha.
3. El carrito no se vacía solo al descargar (por si hace falta repetir la descarga) — usa **"Vaciar placa"** cuando termines esa tanda.

El carrito vive en la sesión del navegador, no en la base de datos: es de usar y tirar en cada impresión, no un historial.
