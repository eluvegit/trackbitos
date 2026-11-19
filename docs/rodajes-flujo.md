# Rodajes: flujo de la idea al plató

Rediseño acordado de Rodajes (+ moodboard de Sesiones) para trabajo de stock foto/vídeo.

## Flujo (barra de pasos del rodaje, con contadores)

Lo normal es que las ubicaciones salgan de las ideas; al revés (ver un sitio y pensar qué hacer allí) es la excepción y también se permite.

Simulación de referencia (solo FLUJO): artifact «Simulador de rodaje» (https://claude.ai/artifact/JM3eC4k5LbA3g6M9PaEP5X). La apariencia NO se copia de ahí: se reutiliza la de las vistas actuales de Rodajes/Sesiones (estilo fv, *_nueva.php) para mantener la uniformidad.

1. **Ingesta** — todo entra junto en una bandeja (moodboard con situacion_id NULL, el grupo «General»): soltar/pegar varias imágenes a la vez y ideas escritas (una por línea).
2. **Temáticas** (= situaciones) — seleccionar varias ideas de la bandeja y moverlas juntas a una temática, crear temática con ellas o descartarlas. Opcional: una idea suelta también puede pasar a toma.
3. **Revisar ideas** — por temática (y «Sin temática») y una a una: Descartar / Nueva toma / Variante de toma existente. Al crear la toma se le pone zona (sugerencias de las existentes o «+ crear» al vuelo), franja, setup (select o «+ crear»), props, prioridad y variantes. Contador «N ideas sin revisar».
3b. **Foto o vídeo** — marcar varias tomas y tocar Foto / Vídeo / Foto y vídeo (como Momento); luego cambiar sueltas con el select. Al cambiar, el estado del plató se mueve con rod_cambio_formato (toma y variantes).
4. **Ubicaciones** — con las zonas que han salido: ordenar la ruta (qué zona antes y cuál después), imagen guía subida por zona y **puntos** marcados sobre ella; asignar punto a las tomas de cada zona. La ubicación es una **zona** amplia; el punto es opcional. Caso inverso: crear zona aquí y «+ toma aquí».
5. **Setups** — repasar los creados al vuelo. Cada setup es de **foto** o de **vídeo** (tipo) con sus parámetros: lente, soporte, diafragma, velocidad/obturación, ISO, flash/luz (foto) y además fps y ND (vídeo). La toma «Foto y vídeo» lleva dos setups asignados por separado: `setup_id` = principal (foto; o el de vídeo si la toma es solo vídeo) y `setup_video_id` = el de vídeo. El plan y el plató agrupan por el principal (foto manda). Las variantes heredan los setups de su toma.
6. **Plan** — orden: Franja → Zona (orden de ruta) → Setup → Toma → Variantes.
7. **Plató** — ficha: foto con debajo un botón por setup (foto izquierda, vídeo derecha, o uno solo) que despliega sus parámetros; a la derecha, en grande, props, focal de foto y de vídeo y flash/luz. Las variantes usan la misma ficha con una franja izquierda más gruesa. Mismo orden; setup terminado se pliega; imprescindibles marcadas; entre tomas del mismo setup se muestra el cambio de props («+ bolsas · − paraguas»).

## Toma (= escena en BD; en pantalla «Toma»)

Visible: título, formato, ubicación + punto (selects), franja, setup (select), props (etiquetas de la lista «Qué llevar» de la sesión), prioridad, notas, variantes.
Resto de campos actuales → «Más detalles» (no se borra nada).

- Franjas: mañana, mediodía, tarde, hora dorada, noche.
- Prioridad: imprescindible / si da tiempo (toma y variante).
- Variante: texto, prioridad, estado de plató, opcionalmente la imagen del moodboard de la que sale.
- Toma sin variantes: estado como ahora. Con variantes: hecha cuando todas resueltas.
- Variante que necesita otro setup → otra toma (no se mueven variantes entre setups).

## Datos

- `rodajes_ubicaciones` (proyecto_id, nombre, imagen, notas, orden) — sin coordenadas ni mapa (descartado)
- `rodajes_puntos` (ubicacion_id, nombre, x, y en % sobre la imagen, notas)
- `rodajes_setups` (proyecto_id, nombre, optica, soporte, fps, luz, notas, orden)
- `rodajes_variantes` (escena_id, texto, prioridad, moodboard_item_id, toma_estado, toma_estado_at, orden)
- `rodajes_escenas` + ubicacion_id, punto_id, setup_id, franja, prioridad, props
- `moodboard_items` + origen 'texto', estado (pendiente/descartada/usada), escena_id, variante_id
- Migración: escena_ubicacion (texto) → una ubicación por nombre distinto en cada rodaje; el texto se conserva.

## Datos existentes (no se pierde nada)

Regla: solo se añaden tablas y columnas; no se borra ni renombra ninguna. Los campos antiguos siguen en «Más detalles».

- escena_descripcion → es el título (misma columna).
- formato, toma_estado, toma_estado_foto → igual; las variantes también llevan estado foto/vídeo para «ambas».
- escena_ubicacion (texto) → zona automática por nombre distinto y rodaje; el texto se conserva. Valores vagos («Exterior», «Interior») se renombran o fusionan a mano.
- camara_optica / camara_soporte / camara_fps → setup propuesto por combinación distinta en cada rodaje («35mm · Trípode»); los campos siguen en la escena.
- plano_hora_dia se usa como «Clasificación de rodaje» (Actores, Sin personas…), NO es franja: franja va en columna nueva. La Clasificación y su storyboard se mantienen.
- plano_objetos (texto libre) → se queda como texto junto a los props; no se trocea automáticamente.
- escena_bloque, orden, pizarra (narrativa/ubicación/montaje), storyboard, imágenes de referencia, situacion_id y la sincronización con Sesiones → intactos. Los rodajes narrativos siguen funcionando como ahora.
- moodboard_items existentes → entran como «sin revisar» en su temática (o en la bandeja si no tienen situación).



1. Ingesta en bandeja + temáticas con selección múltiple + notas de texto y estado en el moodboard.
2. Zonas y setups como tablas mínimas (creables al vuelo) + migración del texto de ubicación.
3. Ficha corta de la toma (variantes, franja, prioridad, props) + pantalla Revisar ideas.
4. Pantalla Ubicaciones: orden de ruta, imagen guía con puntos.
5. Plató / plan reagrupado + diferencias de props. Pizarra adaptada a ubicacion_id/setup_id.
6. Barra de pasos.
