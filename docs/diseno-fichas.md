# Diseño de fichas (estilo "fv")

Guía para rediseñar otras vistas de Trackbitos con el mismo estilo que la ficha de variante nueva de Piezas. Referencia viva:

- Vista: `app/Views/piezas/variante_nueva.php`
- Estilos: `public/assets/css/ficha-variante.css` (todo bajo `.fv`, no afecta al resto de la app)
- Conmutador: `Web::variante()` en `app/Controllers/Piezas/Web.php` (`?nueva=1` / `?nueva=0`, recordado en sesión)

## 1. Cómo trabajar con el usuario

- Responder siempre en castellano. Directrices claras, sin rollo.
- No tocar git (ni commit ni push) salvo petición. Despliegue por FTP manual: al terminar, listar los archivos que hay que subir.
- No añadir comentarios ni docblocks al código.
- Poner `confirm()` en todo lo que quite, borre o saque cosas.
- SPEC.md no es palabra sagrada: si el diseño mejora, se puede saltar.
- Ir ligero en tokens: cambios pequeños, sin releer archivos enteros.

### Proceso que ha funcionado

1. **Diagnóstico** de la vista actual: qué agobia, qué se repite, qué falta. Sin tocar nada.
2. **Maquetas en un artifact** (HTML aparte con datos de ejemplo), nunca directamente en código. Primero 3 enfoques distintos (A/B/C). El usuario suele quedarse con piezas de cada uno, así que luego se hace una combinada (D).
3. **Iterar la maqueta** con lo que diga el usuario. Revisar la vista original y listar lo que falta de ella antes de dar la maqueta por buena.
4. Si el usuario dice que "no tiene toque de diseñador", **rehacer desde cero** con un sistema coherente. No seguir poniendo parches encima.
5. **Integrar en real como vista paralela** (`vista_nueva.php` y `?nueva=1`), con la original intacta, para que deshacer sea borrar dos archivos y unas líneas.
6. **Reutilizar las ventanas y formularios de la vista original** copiándolos por bloques (ver §6). Solo cambia el diseño visible; la lógica y las rutas son las de siempre.
7. **Comprobar:** `php -l`, renderizar con piezas reales mediante un comando spark temporal (§7) y mirarlo en Chrome.
8. Después, **ajustes de colocación** uno a uno: el usuario mueve bloques de sitio en mensajes cortos.

Lo que el usuario rechazó:
- Mover tarjetas Bootstrap de sitio sin cambiar el planteamiento ("más de lo mismo").
- Emojis como iconos.
- Píldoras de colores por todas partes.
- Una historia o actividad mezclada (no permite seguir nada).
- Desplegables perdidos al final de la página.
- Sensación de agobio (demasiadas cajas desplegadas a la vez).
- Negro puro: hay que usar el gris oscuro de Bootstrap de la web.

## 2. Estética

**Idea:** oscuro y sobrio, al estilo de un panel de control cuidado. Bordes finos en vez de bloques de color, un solo acento y el estado expresado con un punto y un texto.

### Variables de color y forma (en `.fv`)

| Variable | Valor | Para qué |
|---|---|---|
| `--fv-bg` | `var(--bs-body-bg)` | Fondo: el gris de Bootstrap de la web, no negro |
| `--fv-panel` | `var(--bs-tertiary-bg)` | Tarjetas |
| `--fv-raised` | `var(--bs-secondary-bg)` | Botones y relieves |
| `--fv-hover` | `rgb(255 255 255 / .06)` | Fondo al pasar el ratón por una fila |
| `--fv-inset` | `rgb(0 0 0 / .18)` | Huecos interiores (campos, detalles, comandos) |
| `--fv-border` / `--fv-border-2` | blanco al 7 % / 12 % | Bordes finos |
| `--fv-text` / `-2` / `-3` | `--bs-emphasis-color` / `--bs-secondary-color` / `--bs-tertiary-color` | Tres niveles de texto |
| `--fv-accent` | `#ff6a3d` (coral) | **Solo** para la acción principal, el paso actual y la pestaña activa |
| `--fv-ok` / `--fv-warn` / `--fv-bad` / `--fv-info` | `#3ecf8e` / `#f5a524` / `#f0553f` / `#8e8cff` | Solo para estados, nunca de adorno |
| Radios | 16 px tarjeta · 10 px botón o campo · 6 px pequeño | Tres tamaños, sin inventar más |

### Tipografía

- Geist y Geist Mono, desde Google Fonts, con un `<link>` en la vista.
- Título de la página: 32–44 px, peso 600, `letter-spacing: -.035em`.
- Título de una tarjeta de estado: 20 px (24 en vista de sección).
- Texto normal: 13–14 px.
- Datos secundarios: 12 px. Etiquetas pequeñas: 11 px.
- Números grandes (stock, disco): 56 px (80 en sección), con `tabular-nums`.
- Códigos, versiones (`v013`), SKU y comandos en mono.

### Reglas visuales

- **Iconos:** Bootstrap Icons (`bi bi-*`), que ya están en el layout. Nunca emojis.
- **Estado:** `.fv-estado.e-ok` pinta un punto con halo y el texto en color. No usar badges rellenos.
- **Listas:** filas limpias que se iluminan al pasar el ratón (`--fv-hover`). Las acciones de cada fila (descargar, derivar, volver) aparecen al pasar el ratón y siempre en táctil (`@media (hover: none)`).
- **Tarjetas:** borde de 1 px con un ligero brillo arriba (`box-shadow: inset 0 1px 0 rgb(255 255 255 / .03)`). Sin sombras grandes.
- **Avisos:** degradado suave del color de estado y borde tenue (ver `.fv-aviso` y `.fv-banner`).
- **Imagen principal:** cuadrada (`aspect-ratio: 1`) con `object-fit: contain`, para que no se recorte.
- **Vacíos:** una línea gris (`.fv-vacio`), no párrafos.

## 3. Organización de la página

```
migas ················· acciones visibles (Editar · Original · Congelar · Independizar │ Borrar · ⇆ clásica)
TÍTULO GRANDE
● estado · SKU · derivada de… · visible en sterclicks (se puede pulsar)
[franja de nevera, si aplica]
Todo | Trabajo 2 | Pieza 9 | Stock y pedidos 7 | Versiones 14 | Impresiones 4 | Espacio 312 MB
─────────────────────────────────────────────────────────────
 Col 1 (la cosa)       Col 2 (trabajo)        Col 3 (datos y acciones frecuentes)
```

Distribución final de la ficha de variante, elegida por el usuario:

| Izquierda | Centro | Derecha |
|---|---|---|
| Imagen y galería (Refs · Renders · Capturas) | Advertencia | Descargar .blend / Derivar |
| Espacio en disco | **Ahora toca** (estado) | Notas (solo si hay) |
| Cifras: Por hacer · Placas | Tareas | Stock |
| Compuesta de · Desde tu máquina | Versiones | Pedidos |
| | | Impresiones |

Principios:
- **Lo que más se usa, a mano:** en esta ficha eran descargar el .blend, derivar, ver el stock y liberar espacio.
- **Acciones a la vista:** nada escondido en un menú `⋯`. El usuario prefiere ver todos los botones, con Borrar separado por una línea.
- **No repetir datos:** si el total en disco ya está en una tarjeta, no va también en las cifras.
- **Lo que solo aparece si existe** (notas, advertencia, nevera) no deja huecos.
- **Listas largas:** mostrar 3–4 elementos en Todo y el resto en su pestaña. Los descartados, ocultos tras "Mostrar descartadas (n)".

### Pestañas de sección ("Todo y dosis")

- **Todo** = las 3 columnas. Cada pestaña muestra solo los bloques con `data-sec="…"`, más grandes y con todo desplegado (`.solo-sec` visible, `.solo-todo` oculto, `.extra` visible).
- **Columnas según el número de bloques visibles:** 1 → una columna centrada de 820 px (`sec-solo`); 2 → dos columnas; 3 o más → tres columnas (`sec-tres`). Por debajo de 1100 px bajan a 2, y en móvil a 1.
- **Advertencia y botones de acción principales** ocupan toda la fila (`grid-column: 1 / -1; order: -2`).
- **La pestaña va en la URL** (`#versiones`). Los anclas antiguas se redirigen: `#capturas` → impresiones, `#sesiones-trabajo` → trabajo, `#version-N` abre esa versión.
- **Móvil:** la columna de trabajo va primero (`order: -1`) y las pestañas se deslizan en horizontal.

## 4. Tarjeta de estado ("Ahora toca")

La lección más importante: **la tarjeta tiene que reflejar el estado real, no "lo que se puede hacer".** En Piezas, al promocionar se abre una rama nueva automáticamente, así que "rama abierta sin sesiones" no significa que estés trabajando en ella.

Se muestra **un único estado**: el primero que se cumpla, en este orden.

| Estado | Condición | Tono |
|---|---|---|
| `sesion` | `$bloqueo` (sesión sin cerrar) | morado, "Trabajando"; ámbar "olvidada" si lleva 3 días o más |
| `descarga` | `$pendientes` no vacío | igual |
| `juzgar` | alguna versión `impresa` | morado, Validar / Descartar |
| `promocionar` | rama con `ultima_subida` | coral, campo y botón |
| `imprimir` | hay una versión `borrador` | coral, Marcar impresa / Descartar |
| `lista` | hay `$validada` | **verde y en calma**, sin latido ni pasos: "La vX es la buena. No hay nada pendiente." "¿Quieres mejorarla?" va plegado |
| `modelar` | hay versiones pero ninguna buena | coral, comando para bajar |
| `empezar` | sin versiones y con rama | coral, comando para bajar |
| `parada` | sin rama | gris |

- **Pasos:** Modelar → Promocionar → Imprimir → Juzgar → Lista (`.fv-pasos`, con las clases `hecho` y `actual`).
- **Tonos:** clases `ok`, `juzga`, `curso`, `bloq` y `calma` sobre `.fv-ahora`.
- **Comandos del CLI:** `trackbitos bajar "<familia> <variante>"` lleva el nombre; `trackbitos subir` no lleva nada y se lanza desde la carpeta de la pieza.

Para aplicarlo a otra vista: listar los estados reales del modelo (leyendo el servicio, no la vista), ordenarlos por prioridad y diseñar una tarjeta por estado con una sola acción principal.

## 5. Componentes (clases de `ficha-variante.css`)

| Clase | Qué es |
|---|---|
| `.fv-top`, `.fv-crumbs`, `.fv-acciones`, `.fv-gbtn` (`.on`, `.peligro`), `.fv-vsep` | Cabecera con migas y acciones sin fondo |
| `.fv-hero`, `.fv-meta`, `.fv-dot`, `.fv-estado` + `.e-ok/.e-warn/.e-bad/.e-info/.e-acc/.e-neu` | Título, línea de datos y estado |
| `.fv-banner` | Franja informativa (nevera) |
| `.fv-secs` | Pestañas subrayadas con contador |
| `.fv-grid`, `.fv-col`, `.fv-panel`, `.fv-ph` (h3, `.cuenta`, `.der`) | Rejilla, tarjeta y cabecera de tarjeta |
| `.fv-btn`, `.fv-btn-pri`, `.fv-btn-sm`, `.fv-btn-peligro`, `.fv-ibtn`, `.fv-link` | Botones y enlaces |
| `.fv-duo` | Dos botones grandes con subtítulo |
| `.fv-media`, `.fv-escena`, `.fv-galeria`, `.fv-subtabs`, `.fv-thumbs`, `.fv-thumb` (`.add`, `.fv-x`, `.fv-tag`) | Imagen principal y galería |
| `.fv-stock`, `.fv-kv`, `.fv-nivel` | Número grande, lista de datos y barra con marca de mínimo |
| `.fv-trio` (`.fv-duo-cifras` para 2) | Cifras separadas por líneas finas |
| `.fv-cuadros`, `.q-ok/.q-warn/.q-bad/.q-pend` | Mini mapa de cuadraditos (veredictos) |
| `.fv-aviso` | Advertencia |
| `.fv-ahora`, `.eti`, `.fv-pasos`, `.fv-campo`, `.fv-input`, `.fv-sube` | Tarjeta de estado |
| `.fv-mas`, `.fv-cuerpo`, `.fv-linea`, `.fv-cmd` (con `data-copiar`) | Desplegables y comando con botón de copiar |
| `.fv-check`, `.fv-nueva` | Tareas (marcar = tachar y "Deshacer" 4 s, luego se guarda) y fila de añadir |
| `.fv-vlista`, `.rap`, `.fv-vdet`, `.fv-datos`, `.fv-ficha`, `.fv-vbotones`, `.fv-porque` | Lista de versiones con detalle |
| `.fv-plista`, `.fv-placa-fila`, `.fv-capturas` | Filas de pedidos e impresiones |
| `.fv-resumen-esp`, `.fv-barra-esp`, `.fv-leyenda`, `.fv-elista`, `.c-ver/.c-act/.c-ses` | Espacio en disco |
| `.fv-flash-ok/.fv-flash-err` | Mensajes de sesión |

Para otra vista, conviene **sacar los componentes comunes a `public/assets/css/fv.css`** y dejar en otro archivo lo propio de cada página.

## 6. Integrar en real sin romper nada

- **Vista paralela.** En el controlador:
  ```php
  $nueva = $this->request->getGet('nueva');
  if ($nueva !== null) { session()->set('ficha_X_nueva', $nueva === '1'); }
  return view(session('ficha_X_nueva') ? 'modulo/x_nueva' : 'modulo/x', [...]);
  ```
- **Reutilizar las ventanas de la vista original** con un script Python que copia bloques: busca la línea de inicio (`<div class="modal fade" id="…"`) y cuenta `<div>` y `</div>` hasta que se equilibran. Los modales que estaban dentro de un `foreach` se envuelven en un `foreach` propio. Los scripts finales (subida con progreso, Enter en modales, foto grande) se copian tal cual.
- **Comprobar que todo botón tiene su ventana:** comparar los `id="modal…"` con los `data-bs-target="#modal…"`.
- **Acciones nuevas sin crear rutas:** reutilizar los endpoints que ya existen. Por ejemplo, las tareas una a una se guardan enviando el texto completo al formulario de siempre (`variante/{id}/tareas`).
- **Si algo necesita una ruta nueva** (como "liberar todo"), no inventarla: avisar al usuario.
- **Caché del CSS:** subir `?v=N` en el `<link>` a cada cambio.

## 7. Comprobación

- `php -l` de la vista y del controlador.
- **Renderizar con datos reales** sin iniciar sesión: comando temporal `app/Commands/TmpRenderFicha.php`, que se borra al acabar.
  ```php
  $req = \Config\Services::incomingrequest(null, false);
  \Config\Services::injectMock('request', $req);   // csrf_field() necesita IncomingRequest
  $req->setGlobal('get', ['nueva' => '1']);
  $c = new \App\Controllers\Piezas\Web();
  $c->initController($req, service('response'), service('logger'));
  ob_start(); $out = $c->variante($id); ob_get_clean();
  ```
  Buscar en el HTML `Undefined|Warning:|ErrorException`, y extraer `data-estado` para ver cuántas piezas caen en cada estado.
- **Base de datos local:** `mysql -h trackbitos.host -uroot -pServBay.dev trackbitos_db` (las credenciales están en `.env`).
- **Mirarlo en Chrome** (Claude in Chrome, el usuario ya tiene sesión iniciada) en `https://trackbitos.host/...?nueva=1`. Cerrar la pestaña al terminar.
- En local, los .blend y los STL no están, así que los tamaños en disco salen 0 KB. Es normal.

## 8. Trampas ya resueltas

- **Botones sin relleno:** `.fv button { padding: 0 }` gana a `.fv-btn` y deja los botones sin relleno. Usar `.fv :where(button)` y `.fv :where(a)` para los reseteos.
- **Estilos de Bootstrap que se cuelan:** `!important` en los colores de `.fv-btn`, `.fv-gbtn` y `.fv-input`.
- **Avisos dentro de resumen:** un botón dentro de un `<summary>` despliega el bloque al pulsarlo. Hay que llamar a `preventDefault()` en su clic; Bootstrap sigue abriendo la ventana igualmente.
- **Columnas que desaparecen:** en las pestañas de sección, `.fv-col { display: contents }` para que los bloques de las tres columnas formen una sola rejilla.
- **Especificidad en móvil:** las reglas para móvil deben repetir los selectores con más peso (`.fv.sec.sec-tres .fv-grid`) o no se aplican.
- **Iconos de Bootstrap en `content:`:** no fiarse de los códigos; mejor un carácter (`?`) dentro de un círculo hecho con CSS.
- **Botones que quedan apagados:** los de acciones que no aplican no se muestran. Los motivos van en "¿Por qué no aparecen otras acciones?", con `$porQueNo($v)`.
