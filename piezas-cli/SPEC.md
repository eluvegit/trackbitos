# Trackbitos · Módulo Piezas — Especificación de implementación

## Estado de implementación

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Migraciones + modelos, invariantes 1-4 | ✅ Hecho |
| 2 | `PiezaService` con los verbos | ✅ Hecho |
| 3 | `trackbitos estado` (hash de nube desde fichero local) | ✅ Hecho |
| 4 | API de lectura (`/variantes`, `/variante/{id}/estado`) + cliente conectado | ✅ Hecho |
| 5 | API de subida y descarga, con verificación de hash en ambos extremos | ✅ Hecho |
| 6 | Interfaz web: ficha de variante y botones de los verbos | ✅ Hecho |
| 7 | Papelera y purga de sesiones al validar | ✅ Hecho |
| 8-13 | Imágenes, STL, SKU/galería/placa, descarga web del `.blend`, «Pieza» + variante `base`, categorías y listado | ✅ Hecho (detalle abajo) |
| 14 | Papelera de piezas (borrar/restaurar/purgar una familia entera) | ✅ Hecho (detalle abajo) |
| 15 | Auto-actualización del cliente (`trackbitos actualizar`) | ✅ Hecho (detalle abajo) |
| 16 | `catalogo` por categoría, `variantes <pieza>`, `abrir` ya no pisa ficheros | ✅ Hecho (detalle abajo) |
| 17 | "Compuesta de": qué otras piezas estaban en la escena de una variante | ✅ Hecho (detalle abajo) |
| 18 | Enlace al original, liberar sitio de una sesión suelta, y `resolver_variante` más preciso | ✅ Hecho (detalle abajo) |
| 19 | Columna "Estado": qué hay terminado + si hay trabajo encima (`modificando`) | ✅ Hecho (detalle abajo) |
| 20 | Invariante 9 (impresión sin juzgar) y `cerrar` sin subir | ✅ Hecho (detalle abajo) |
| 21 | Varios STL por versión (imprimir a trozos y montar) | ✅ Hecho (detalle abajo) |
| 22 | `abrir` fusionado en `bajar`: un solo comando para pieza nueva o con historial | ✅ Hecho (detalle abajo) |
| 23 | El cliente se actualiza solo de verdad, sin pedirlo | ✅ Hecho (detalle abajo) |
| 24 | Papelera también por variante suelta (no solo la pieza entera) | ✅ Hecho (detalle abajo) |
| 25 | Directorio vacío ya no confunde con "corrupto", y sesiones activas en el índice | ✅ Hecho (detalle abajo) |

Dónde vive cada cosa:
- Migración: `app/Database/Migrations/2026-08-16-000001_CreatePiezasTables.php`
- Modelos: `app/Models/Pieza{Maquina,Categoria,Familia,Variante,Version,Rama,Sesion,Descarga}Model.php`
  (prefijados "Pieza" a propósito — ya existe un `SesionModel`/tabla `sesiones` para el módulo de rodajes fotográficos, completamente distinto)
- Verbos: `app/Services/PiezaService.php`
- Círculo descarga→subida (invariante 8): `app/Services/PiezaSyncService.php`
- Ficheros en disco: `app/Services/PiezaAlmacen.php` (bajo `writable/piezas/`)
- API: `app/Controllers/Piezas/Api.php`, filtro `app/Filters/PiezasApiAuth.php`, rutas bajo `piezas/api` en `app/Config/Routes.php`, token en `.env` (`piezas.apiToken`, no versionado)
- Web: `app/Controllers/Piezas/Web.php` + `app/Views/piezas/{index,variante}.php`, rutas bajo `piezas` (filtro `auth`)
- Purga de la papelera del servidor: `php spark piezas:purgar` (`app/Commands/PiezasPurgar.php`), pensado para cron diario
- Cliente CLI: `piezas-cli/trackbitos.py` (fuera de `app/`, se puede mover a cualquier carpeta del equipo — no depende de vivir en este repo). Config real en `~/.trackbitos/config.json`.

**Las siete fases están cerradas.** El módulo funciona de punta a punta: dos máquinas, ficheros
versionados, asientos que cuadran, web para juzgar versiones y purga automática.

**Fase 8 (añadida tras el cierre, 2026-08-17): imágenes.** `trackbitos catalogo` en el cliente
(catálogo desde la terminal, sección 5.1) y referencias/renders en la web (sección 7.4). A
diferencia del resto del módulo, las imágenes se suben y se sirven directamente desde el
navegador — no hace falta identidad de máquina para mirar una foto. Migración
`2026-08-17-000001_CreatePiezasImagenesTables.php`, modelos `PiezaReferenciaModel`/
`PiezaRenderModel`, rutas de fichero en `PiezaAlmacen::rutaReferencia()`/`rutaRender()`.

**Fase 9 (añadida el mismo día): STL para imprimir.** `ruta_stl`/`hash_stl` ya existían en el
esquema desde la fase 1 pero no había ningún verbo que los rellenase — el bloqueo de
`PiezaVersionModel::CAMPOS_INMUTABLES` (invariante 4) trataba la primera asignación (de vacío a
un valor) igual que una modificación, así que habría reventado en cuanto se intentara. Arreglado:
ese guard ahora solo bloquea sobreescribir un valor que ya estaba puesto, no ponerlo por primera
vez. Verbo nuevo `PiezaService::adjuntarStl()`, ruta de fichero en
`PiezaAlmacen::rutaStl()` (junto al `.blend` de la misma versión), web en `Web::subirStl()`/
`descargarStl()` (botón en la tarjeta de cada versión del historial). Mismo criterio que las
imágenes — sin identidad de máquina, se sube y se descarga directamente desde el navegador —
pero con descarga como adjunto, no inline: el STL se abre en el laminador, no se mira en el
navegador. Inmutable en cuanto se adjunta: si el modelo cambia, toca promocionar una versión
nueva y subir el STL ahí.

**Fase 10 (añadida el mismo día): SKU, galería y placa de impresión.**

- **SKU**: columna `sku` en `piezas_variantes` (nullable, único a nivel de esquema —
  `AddSkuToPiezasVariantes`), referencia manual para cuando alguien pide una pieza por el código
  que usa en otro sitio (tienda, Etsy...). Trackbitos no sincroniza con ese otro sistema, solo
  guarda el mismo texto. Verbo `PiezaService::actualizarSku()` (la única edición posible tras
  crear una variante); unicidad comprobada en el servicio, no solo en el índice, para dar un
  mensaje que diga qué variante ya lo tiene. Editable en el alta y con un lápiz en la cabecera de
  la ficha. Buscador por nombre/SKU en el índice (JS puro, sin ida y vuelta al servidor).
- **Galería** (`/piezas/galeria`, `Web::galeria()`): solo variantes con versión validada — es la
  vista de "qué tengo listo para imprimir", no el catálogo de trabajo en curso. Miniatura: el
  render más reciente de la versión validada y, si no hay, la referencia más reciente de la
  familia.
- **Placa de impresión**: un carrito de versiones validadas con STL, en sesión de navegador
  (`Web::SESION_CARRITO`) — no es tabla, es de usar y vaciar en cada tanda. "Añadir/quitar de la
  placa" desde la galería o desde la ficha de variante; "Descargar placa" empaqueta los STL en un
  `.zip` (`ZipArchive`, fichero temporal en `writable/piezas/tmp/`, borrado con
  `register_shutdown_function` tras servirlo) listo para importar de golpe en el laminador. El
  carrito no se vacía solo al descargar — "Vaciar placa" es una acción aparte y explícita, para no
  perder la selección si la descarga falla a mitad.

**Fase 11 (2026-08-17): descargar el `.blend` de una versión desde la web.**

La web ya dejaba bajar el STL de una versión pero no su `.blend`, siendo los dos igual de
inmutables y colgando de la misma versión — una asimetría accidental, no una decisión. Ahora
`GET /piezas/version/{id}/blend/descargar` (`Web::descargarBlend()`) lo sirve como adjunto.

**Por qué no rompe la sección 4.4:** lo que obliga a declarar máquina no es la extensión del
fichero, es que haya **trabajo que deba volver**. Una versión es inmutable (invariante 4) y está
cerrada: nadie espera que la devuelvas, así que no hay asiento que abrir ni cadena `hash_padre`
que romper. Las **sesiones** siguen siendo exclusivas del cliente, que es donde vive ese riesgo.
Y si alguien acabase trabajando sobre esta copia, el cliente no la daría por buena: sin
`.sesion.json` la tabla 4.3 la trata como divergencia (fila 4).

Aun así, la ficha lo avisa junto al botón, como texto visible y no como `title` (7.1): *"Ese
`.blend` no queda registrado: para mirar, no para trabajar."* El aviso va en una sola línea a
propósito — se repite en cada versión del historial, y la explicación completa vive en el panel
lateral "Desde tu máquina".

**Nombre de fichero con SKU.** Las descargas se llamaban `version-v002.stl`, que es el nombre en
disco (derivado de IDs, sección 8) y no dice de qué pieza son. Ahora `Web::nombreArchivo()` las
sirve como `PM-042-torso-recto-v002.blend`: SKU delante cuando lo hay, porque es el código por el
que se pide la pieza fuera de Trackbitos y lo que la hace reconocible en la carpeta de descargas
o dentro del laminador. Aplica también al STL suelto y a los ficheros dentro del `.zip` de la
placa.

**Fase 12 (2026-08-17): "Pieza" en vez de "Familia", y variante `base` automática.**

Dos cambios que vienen del primer uso real, con ~15 piezas por meter y solo dos con variantes
de verdad (una silla y unas piernas).

**Nota de vocabulario — desajuste deliberado.** Lo que el esquema llama `familia`
(`piezas_familias`, `familia_id`, `PiezaFamiliaModel`) **la interfaz lo llama "Pieza"**.
"Familia" es vocabulario de catálogo industrial y no es lo que uno piensa al abrir Blender; y
que el módulo Piezas contenga piezas no es redundancia, es coherencia — igual que Comidas
contiene comidas. No se renombró el esquema por dos razones: `piezas_piezas` sería peor que lo
que hay, y el módulo ya está en producción con datos. **Al leer el código, `familia` = "pieza"
de cara al usuario.** El vocabulario visible se cambió en las vistas, los mensajes del
controlador y el CLI; el esquema, los modelos y las rutas (`piezas/familia/...`) se quedan.

**Variante `base` automática** (`PiezaService::VARIANTE_BASE`). `crearFamilia()` crea de una vez
la pieza y su primera variante, llamada `base`, y devuelve `['familia' => …, 'variante' => …]`.
Antes había que rellenar dos formularios e inventarse un nombre de variante para poder empezar a
modelar; las variantes son la excepción (la mayoría de piezas son una sola cosa), así que ese
peaje lo pagaba siempre quien no las necesitaba. La jerarquía no cambia — sigue haciendo falta
para numerar versiones por variante y para colgar las referencias de la pieza (1.1) — lo que se
quita es el trabajo manual. El alta acepta ahora el SKU, que se aplica a esa variante base.
El nombre es `base` y no `estándar`/`original` porque no promete nada sobre las que vengan.

En la ficha, el contador de variantes solo aparece a partir de dos: con una sola es ruido.

**Fase 13 (2026-08-17): categorías y vista de listado en el índice.**

El índice era una tarjeta grande por pieza, una detrás de otra. Con tres piezas se leía bien;
con quince —las que hay pendientes de meter— era un chorizo sin orden. Ahora es un **listado
denso agrupado por categoría**, con las quince en una pantalla.

- **Categorías** (`piezas_categorias`, `categoria_id` en `piezas_familias`): las carpetas que ya
  existen en disco, no una taxonomía nueva. Un nivel **por encima** de la pieza, plano y sin
  anidar. Tabla y no un campo de texto: con texto libre una errata crea una categoría fantasma
  en silencio y renombrar obliga a repasar todas las piezas. Verbos en `PiezaService`:
  `crearCategoria`, `renombrarCategoria`, `borrarCategoria`, `moverCategoria`,
  `clasificarFamilia`. Se gestionan desde el modal «Categorías» del índice.
- **Sin clasificar es un estado legítimo**, no un error: una pieza recién creada puede no saber
  todavía dónde va, y aparece en un grupo propio al final. Borrar una categoría **no borra sus
  piezas** (`ON DELETE SET NULL`, más el mismo efecto explícito en el servicio): se quedan sin
  clasificar y el mensaje dice cuántas son, porque verlas aparecer de golpe abajo sin aviso es
  justo lo que hace pensar que se han perdido.
- **Modo «Organizar»**: los selectores de categoría por fila y las flechas de orden solo se ven
  al encenderlo, y el modo se recuerda entre cargas — cada pieza que cambia de categoría recarga
  la página, y colocar quince seguidas sería encenderlo quince veces.
- El plegado de cada categoría se recuerda en `localStorage`; el buscador abre todos los grupos
  mientras se busca y restaura el plegado al vaciar el campo.
- **Renombrar variantes** (`PiezaService::renombrarVariante()`, lápiz en la cabecera de la ficha,
  junto al SKU). Hacía falta desde el primer uso real: una pieza con varias líneas de diseño nace
  con una variante `base` (fase 12), y en cuanto llega la segunda ese nombre deja de decir nada —
  `base` y `grande` no se leen como una pareja; `pequeña` y `grande` sí. Es cosmético para el
  registro (lo que identifica a la variante es su id: ni versiones, ni hashes, ni asientos
  dependen del nombre), pero **no es cosmético para el cliente**, que resuelve las variantes por
  nombre — por eso el mensaje avisa de que el comando cambia, y por eso el nombre debe ser único
  **dentro de su pieza**: dos `grande` en la misma pistola harían ambiguo un `trackbitos bajar`.
  Entre piezas distintas se repite libremente (`base` está en casi todas). La misma comprobación
  se aplica al crear y al derivar variantes: si solo viviera en el renombrado, un duplicado
  entraría por la otra puerta.
- **Pantalla de máquinas** (`/piezas/maquinas`, icono en la cabecera del índice): la que faltaba
  desde la 4.5, que ya daba el nombre por editable en la web. Se da de alta con el hostname
  (`DESKTOP-4F2K1`) y ese nombre es el que sale en los avisos de «sesión abierta en…», donde
  tiene que entenderse sin pensar. El nombre es lo **único** editable — el UUID es la identidad y
  lo pone el cliente — y se exige único: dos máquinas llamadas «MacBook» convierten ese aviso en
  una frase que no dice dónde ir a mirar. Cada máquina muestra además cuántas sesiones tiene
  abiertas ahora mismo, que es lo que decide si puedes olvidarte de ella.
- **Las fotos de referencia se mudan a la ficha de variante.** Vivían en las tarjetas del índice,
  que ya no existen, y la ficha es donde de verdad se miran: mientras modelas. Siguen siendo de
  la pieza entera (1.1), así que se ven iguales desde cualquiera de sus variantes; el formulario
  lleva la variante de vuelta en `volver_a_variante`, y el controlador comprueba que sea de esa
  misma pieza antes de redirigir.

**Fase 14 (2026-08-17/18): papelera de piezas.** Con el catálogo real ya en marcha hacía falta
poder borrar una pieza que "por lo que sea no convence" — creada de más, duplicada, un
experimento que no cuajó — sin que fuera un borrado de verdad (invariante 6, extendida ahora a la
familia entera, no solo a ficheros sueltos).

- Columna `borrado_en` (nullable) en `piezas_familias`: mientras esté vacía la pieza es una pieza
  normal; en cuanto se pone, desaparece del índice, la galería y `GET /variantes`, y pasa a
  listarse solo en `/piezas/papelera`.
- Verbos (`PiezaService`): `borrarFamilia()` — se niega si alguna variante tiene una sesión de
  trabajo abierta (el bloqueo quedaría huérfano) —, `restaurarFamilia()`, y
  `purgarFamiliasBorradas($dias = 30)` para el borrado definitivo a los 30 días: aparta a la
  papelera de ficheros (`PiezaAlmacen::aPapelera`) todo lo que un `.blend`/`.stl`/imagen aún
  viviera en su sitio original antes de borrar la fila — la cascada de FK se lleva variantes,
  versiones, ramas, sesiones y descargas.
- `php spark piezas:purgar` ahora purga las dos papeleras en el mismo paso: primero las piezas
  (que aparta sus ficheros a la papelera de ficheros), luego la papelera de ficheros por edad —
  en ese orden, para no purgar por edad un fichero que la purga de piezas acaba de apartar.
- Web: botón de borrar por fila en el índice (modo «Organizar», con confirmación — como ya hacía
  borrar categoría), y `/piezas/papelera` con «Restaurar» y los días que quedan antes de la
  purga.

**Fase 15 (2026-08-17): el cliente se actualiza solo.** `trackbitos.py` vive fuera de este repo,
copiado a mano en cada máquina (sección 5), así que una mejora en el script no llegaba a las dos
sin ir a buscarlo. Ahora se avisa solo y se actualiza cuando se le pide, nunca sin pedirlo — el
principio del módulo es "el sistema se niega y explica", no "el sistema decide por ti".

- **Versión**: constante `VERSION` al principio de `trackbitos.py` (semver simple, `"1.0.0"`).
  La "oficial" es la que tiene el propio fichero desplegado en el servidor junto al resto de la
  app — no se guarda aparte en la base de datos, para que no se pueda desincronizar del código
  real. El servidor la lee por regex de ese mismo fichero (`Api::clienteVersion`).
- **Aviso automático, actualización manual.** Cada ejecución de `trackbitos` comprueba en
  silencio si hay versión nueva (`comprobar_version_remota`, timeout corto y propio de 3s) y, si
  la hay, imprime un aviso de una línea al final — después del comando, nunca antes, para no
  añadirle latencia a lo que el usuario vino a hacer. Ante cualquier fallo (sin red, sin config
  todavía, servidor caído) se calla del todo: es un extra, no el motivo de la llamada.
- **`trackbitos actualizar`** hace la actualización de verdad: compara versiones, descarga
  `/cliente/descargar` (el mismo fichero, servido por `Api::clienteDescargar`), comprueba que
  compila como Python antes de tocar nada (una descarga a medias no debe dejar el script roto en
  disco) y se reemplaza a sí mismo (`Path(__file__).resolve()`). La versión anterior se aparta a
  la papelera local igual que un `.blend` (invariante 6), no se pisa sin más.
- **Autenticación**: el mismo token Bearer que el resto de `piezas/api` (filtro `piezasApi`). No
  hace falta declarar máquina — esto no toca ningún asiento ni sesión, solo lee un fichero.

**Fase 16 (2026-08-18): `catalogo` agrupado por categoría, `variantes <pieza>` nuevo, y "abrir"
ya no pisa el `.blend` de la pieza anterior.**

- **`trackbitos variantes`** pasó a llamarse **`trackbitos catalogo`** (es lo que ya decía su
  propia ayuda: "el catálogo completo"). Ahora se sirve agrupado por categoría, en el mismo
  orden que ya usan el índice y la galería web — `GET /variantes` devuelve además `categorias`
  (la lista ordenada) y `categoria_id`/`categoria_nombre` por variante. De paso, el endpoint deja
  de listar variantes de piezas en la papelera (mismo descuido que ya se arregló en `/piezas` y
  `/piezas/galeria`).
- **`trackbitos variantes <pieza>`** (nombre libre, reaprovechado): el zoom sobre una pieza
  concreta — cuántas variantes tiene y cómo se llama cada una. Complementa a `catalogo`, que es
  la foto completa.
- **"abrir" apartaba a la papelera el `.blend` sobrante de una pieza anterior — no lo hacía.**
  Detectado en producción: si se reutiliza una carpeta de trabajo para una pieza distinta sin
  borrar antes su `.blend` (con `bajar` esto ya no pasaba, el fichero anterior se apartaba solo),
  `trackbitos subir` encontraba un único fichero — el de la pieza vieja — y lo subía tal cual,
  **sin avisar**, como si fuera la primera versión de la pieza nueva. Al ser una rama recién
  estrenada (invariante 8, "rama estrenada") el servidor ni siquiera exige un `hash_padre` que
  pudiera delatarlo. Arreglado: `abrir` ahora aparta a la papelera cualquier `.blend` que
  encuentre en la carpeta antes de escribir el `.sesion.json` nuevo, igual que ya hacía `bajar`.
  La carpeta queda limpia; si se olvida guardar el fichero nuevo, `subir` se niega ("no hay
  ningún .blend") en vez de subir contenido de otra pieza por error.

**Fase 17 (2026-08-18): "Compuesta de".**

Algunas piezas nuevas son composición de otras ya hechas — un "Mini playmobil" que es varias
piezas de cuerpo juntas, o una variante que se modela dejando la pieza anterior en la misma
escena para partir de ella. Hasta ahora no había dónde anotarlo: la única pista era `Completo`
(sección 11.1), que convive con el problema a base de escribirlo a mano en el `cambio` de cada
versión — texto libre, nada que se pueda consultar ni que avise si la pieza de la que partiste
ha cambiado.

- **Tabla `piezas_composiciones`**: `variante_id` (la pieza que compone) → `version_componente_id`
  (la versión concreta de OTRA pieza que estaba en su escena), con `notas` libre. `UNIQUE
  (variante_id, version_componente_id)` — no se anota la misma dos veces. `ON DELETE CASCADE` en
  las dos FK: si la variante o la pieza referenciada se purgan de la papelera, la fila deja de
  tener sentido y se va sola.
- **Deliberadamente aparte de `origen_version_id`** (el campo que ya tenía `piezas_variantes`,
  usado por `derivarVariante` y por la sincronización para la cadena de hashes, spec 4.4 —
  "tras promocionar"). Ese tiene que seguir siendo uno solo, porque es de qué fichero concreto se
  partió de verdad. "Compuesta de" es una lista aparte, puramente informativa: no afecta a ningún
  invariante, no se recalcula, no se promociona ni se fusiona sola — solo dice "esto también
  estaba en la escena". No hay límite de cuántas piezas se anoten (el caso que la motivó era
  justo "de muchas a una", que `origen_version_id` no podía cubrir por ser un campo único).
- **Verbos** (`PiezaService`): `declararComponente(varianteId, versionComponenteId, notas?)` —
  rechaza auto-referencia (una pieza no puede componerse de una versión de sí misma) y duplicados
  — y `quitarComponente(composicionId)`.
- **Ficha de variante**: sección "Compuesta de", con las piezas ya anotadas (enlace a su ficha,
  notas, y un aviso pasivo si esa versión concreta ya quedó `superada` o `descartada` — nunca se
  actualiza sola) y un selector para añadir cualquier versión de cualquier otra pieza activa
  (self-referencia excluida en la propia lista). Rutas: `POST piezas/variante/{id}/componente`,
  `POST piezas/componente/{id}/borrar`.

**Fase 18 (2026-08-18): enlace al original, liberar sitio de una sesión suelta, y
`resolver_variante` más preciso.** Motivado por generar piezas con IA (image-to-3D): la malla
en bruto puede pesar 100 MB frente a los ~350 KB de un modelo hecho a mano (sección 0) — un
peso que no tiene sentido versionar en el propio servidor.

- **`enlace_original`** (`piezas_variantes`, tras `notas`): dónde vive el máster de máxima
  calidad — normalmente fuera del tracker, en Drive o similar, porque no necesita bloqueo entre
  máquinas ni versionado, solo poder volver a él. Solo se guarda el enlace, nunca el fichero.
  Editable desde la ficha (mismo modal que nombre/SKU); se muestra como badge "original" con
  enlace directo cuando hay uno. Ruta: `POST piezas/variante/{id}/enlace-original`.
- **Liberar sitio de una sesión suelta** (`PiezaService::descartarFicheroSesion`): aparta a mano
  el `.blend` de una sesión ya cerrada y sin promocionar — p. ej. una subida de prueba
  demasiado pesada que se va a reemplazar por una reducida. Es lo mismo que ya hacía
  `purgarSesionesDe` al validar (invariante 5), pero disparado antes de ese punto: la rama sigue
  abierta, así que sin esto el fichero se queda ocupando sitio para siempre (nada en el módulo
  llega nunca a purgar esa rama si no se promociona). La fila no se borra, solo se marca
  `purgada` y se mueve el fichero — igual que el resto del módulo (invariante 6). Se niega si la
  sesión sigue abierta o si tiene una descarga sin cerrar. Botón "liberar sitio" en la ficha,
  junto a cada sesión cerrada y sin purgar. Ruta: `POST piezas/sesion/{id}/descartar-fichero`.
- **`resolver_variante` (cliente) más preciso.** Con el catálogo real, el emparejamiento por
  subcadenas se quedaba corto: escribir `brazo` para abrir la pieza "Brazo" también encajaba con
  "Brazo integral" y "Brazo y mano" por compartir la palabra, y ni siquiera `"brazo base"`
  desambiguaba (las tres piezas tienen una variante `base`, y la subcadena "base" aparece en las
  tres). Ahora el nombre exacto de PIEZA gana sobre cualquiera que solo lo contenga (como ya
  pasaba con el nombre de variante), y antes de caer al emparejamiento por trozos se prueba
  "pieza variante" completos y exactos en cualquier corte de palabras — así `"brazo base"`
  encuentra justo la pieza "Brazo", no las tres.
- **Listados de coincidencias legibles.** Cuando `resolver_variante` no encuentra nada o
  encuentra varias, el mensaje ya no es una única línea con todo separado por comas (ilegible
  pasadas diez piezas) — es el mismo formato agrupado por categoría que usa `catalogo`, una
  pieza por línea, con "base" (la variante que se crea sola) oculto porque no aporta nada
  repetido en casi todas las líneas.
- **Las confirmaciones de `abrir`/`bajar`/`ver` ya identifican la pieza, no solo la variante.**
  Antes decían solo "base · rama … · sesión … abierta" — indistinguible entre piezas distintas,
  porque casi todas comparten esa misma variante. El servidor manda ahora también el nombre de
  la familia en la descarga (cabecera `X-Familia-Nombre`, junto a `X-Variante-Nombre`;
  `PiezaSyncService::entregar` la añade al buscar la variante), y el cliente guarda la
  identidad completa (`nombre_completo`: "Pieza / variante") en el `.sesion.json` en vez del
  nombre suelto — así todo lo que ya lee de ahí (`estado`, `cerrar`, `promocionar`...) queda
  arreglado sin tocarlo aparte. La sugerencia de "empieza de cero: trackbitos abrir …" usa ahora
  el id numérico, no el nombre, para que sea pegable sin ambigüedad.
- **Renombrar la pieza entera** (`PiezaService::renombrarFamilia`, lápiz en la cabecera de la
  ficha — mismo modal que ya editaba nombre de variante/SKU/enlace). Hasta ahora solo se podía
  renombrar la variante (`renombrarVariante`); la pieza en sí no tenía forma de corregirse una
  vez creada. Sin comprobación de unicidad, igual que `crearFamilia`. Ruta:
  `POST piezas/familia/{id}/nombre`.
- **Alias cortos en el cliente** (sección 5.1): una letra para los seis comandos de uso diario
  (`e`, `a`, `b`, `v`, `s`, `c`, `p` para estado/abrir/bajar/ver/subir/cerrar/promocionar) y dos
  para el resto (`pa`, `ca`, `va`, `ac`), vía `add_parser(..., aliases=[...])` de argparse — el
  nombre completo se sigue aceptando igual. Cuidado al leer `args.comando` en `main()`: con
  alias, guarda lo que se escribió de verdad ("ac"), no el nombre canónico ("actualizar"), así
  que la comprobación de "no comprobar versión nueva tras `actualizar`" compara `args.func`
  (la función ya resuelta), no la cadena.
- **"Marcar impresa" sugiere los parámetros de la última vez.** El textarea de exposición/capa/
  posición en la placa salía siempre en blanco, con solo un ejemplo genérico de placeholder —
  aunque la posición en la placa rara vez cambia entre reimpresiones de la misma pieza.
  `Web::variante()` busca ahora el `params_impresion` no vacío más reciente entre TODAS las
  versiones de la variante (`$sugerenciaImpresion`) y precarga el textarea con él, editable; el
  placeholder de ejemplo menciona también la posición en la placa, no solo exposición/capa.
- **Cuánto ocupa cada fichero, y cuánto el módulo entero.** `PiezaAlmacen::tamano()` calcula el
  tamaño de un fichero del almacén leyendo el disco (ninguna tabla guarda el tamaño de
  referencias/renders/versiones, así que no hay columna que consultar); se usa en la ficha junto
  a "Descargar .blend"/"Descargar STL" de cada versión, y las sesiones ya mostraban el suyo.
  `PiezaAlmacen::tamanoTotal()`/`tamanoPapelera()` recorren `writable/piezas` entero (o solo su
  subcarpeta `papelera/`) para las estadísticas globales — nueva pantalla `/piezas/estadisticas`
  (icono en la cabecera del índice): total del almacén, cuánto de eso está en la papelera (lo
  que se liberaría con `piezas:purgar` sin esperar a los 30 días) y qué piezas concretas pesan
  más (suma de sus versiones + sesiones, apartadas o no), para saber cuál aligerar primero con
  el botón "liberar sitio" (Fase 18) en vez de adivinarlo.
- **El listado principal distingue la línea de vida, no solo "tiene versión buena o no".**
  Antes, cualquier pieza sin versión validada se veía igual ("sin versión buena"), tanto la que
  nunca se ha intentado imprimir como la que ya se probó y falló. `Web::resumen()` añade
  `ultima_version_estado` (el estado de la versión más reciente por número) y el índice pinta
  cuatro casos distintos cuando no hay validada (ver más abajo, fase 19).
- **Tarjeta de estadísticas en la ficha**, justo debajo de la que dice cuál es la versión buena:
  tamaño en disco (desglosado entre versiones y sesiones, para saber si el peso está en lo que
  importa o en trabajo intermedio que ya podría aligerarse), intentos de impresión (versiones
  que no se quedaron en borrador), sesiones de trabajo totales, y días desde que se creó.
  `Web::pesoDeVariante()` reutiliza el mismo cálculo que ya usaba `estadisticas()` por familia,
  ahora también por variante suelta.
- **El índice pasa de tarjetas con badges que se envuelven a una tabla real.** Con el catálogo ya
  grande, las filas de `list-group-item` con badges en `flex-wrap` se leían mal: cada fila
  envolvía en un sitio distinto según cuántos avisos tuviera, sin ninguna columna alineada. Ahora
  es una única `<table>` para todo el listado (columnas: Pieza, SKU, Estado, Versiones, Aviso, y
  una de Organizar que solo aparece en ese modo) — un `<table>` no puede llevar un `<div>` como
  hijo directo, así que cada categoría son dos `<tbody>` consecutivos (uno de cabecera, siempre
  visible; otro con el `id="cat-N"` de siempre, plegable) en vez del `<div data-grupo>` que las
  envolvía antes. Las filas de variante (cuando hay más de una) llevan `data-subpieza` en vez de
  `data-pieza` — mismo `data-buscar` que su fila de pieza, para que el buscador las oculte juntas
  — pero fuera del recuento de "cuántas piezas hay" en la cabecera de cada categoría, que sigue
  contando solo `data-pieza`.
- **Reordenada la ficha**: versión buena → historial → estadísticas → "Compuesta de" → resto.
  Antes estadísticas y "Compuesta de" salían justo debajo de la versión buena, antes del propio
  historial.
- **"Descargar .blend" pasa por un modal de aviso.** Antes era un enlace directo con una línea de
  aviso escrita debajo en cada versión del historial ("no queda registrado: para mirar, no para
  trabajar") — fácil de dejar de leer de puro repetida. Ahora el botón abre un modal
  (`modalDescargarBlend{id}`) con la advertencia completa (incluye que la carpeta donde se
  guarde es responsabilidad de quien la baja, no algo que el módulo purgue solo) y el enlace de
  descarga de verdad dentro. No es una barrera (spec 0: "se niega y explica", no "¿estás
  seguro?") — sigue siendo una descarga libre, solo obliga a verla una vez antes de cada bajada.
- **"Devolver a trabajo" ya no se ofrece en la versión de la que la rama abierta ya parte.**
  Detectado en producción: tras promocionar (o tras un "devolver" anterior sin subir nada
  todavía), la rama abierta ya arranca de esa versión — pulsar "Devolver a trabajo" ahí mismo
  cerraría esa rama vacía para abrir una idéntica, un no-operación disfrazada de advertencia
  seria ("¿estás seguro de abandonar…?") sin nada real que abandonar. `Web::accionesDisponibles()`
  expone ahora `rama_desde_version_id`; la ficha compara cada versión contra ese id
  (`$puedeDevolver($v)`) y desactiva el botón justo en esa versión con su propio motivo — el resto
  de versiones (superadas, descartadas de ramas ya cerradas) lo siguen ofreciendo con normalidad.

**Fase 19 (2026-08-18): la columna "Estado" pasa a decir dos cosas.** "Sin versión buena" era una
negación ("no tiene la buena") que no decía qué le pasaba a la pieza ni qué tocaba hacer con
ella, y tras la fase 18 ya ni siquiera cubría lo que su nombre sugería: impresa y descartada se
habían separado a badges propios, así que la etiqueta genérica se había quedado significando algo
mucho más concreto de lo que decía. Y faltaba una información entera: si encima hay trabajo en
marcha.

**Eje 1 — qué hay terminado** (`$badgeMadurez` en la vista; siempre exactamente uno):

| Situación | Etiqueta | Badge |
|---|---|---|
| Hay versión validada | `v001 ✓` | success |
| Ninguna versión promocionada todavía | **`sin versión`** | secondary |
| Promocionada, aún sin imprimir (`borrador`) | **`para imprimir`** | secondary |
| Impresa, pendiente de juzgar | `sin validar` | primary |
| La última se descartó | **`no sirve`** | danger |

- **`sin versión` vs `para imprimir`** es la distinción nueva, y no es cosmética: la
  primera dice que el trabajo sigue en la sesión y todavía no ha llegado al historial (lo que
  toca es `promocionar`); la segunda, que ya hay una versión congelada esperando a la impresora
  (lo que toca es imprimirla y marcarla impresa). Antes las dos se veían igual.
- **`no sirve`** en vez de `descartada`, que es como lo define esta misma spec (1.3). Se descartó
  la opción "con errores" porque presupone una causa que muchas veces no es la real: se descarta
  también porque quedó pequeña o porque el diseño cambió, no solo porque el modelo esté mal. El
  ENUM de la base de datos, el verbo y el botón siguen llamándose *descartar*; lo que cambia es
  la etiqueta del listado, que responde a "cómo está la pieza", no a "qué le hiciste".
- Un estado `superada` como última versión sin ninguna validada solo puede darse si se descartó
  la validada después; cae en `sin validar`, que sigue siendo cierto.
- `sin validar` va en **primary** (azul) y no en el `info` que tenía: el cyan de Bootstrap sobre
  el tema oscuro sale chillón. El mismo cambio en la ficha de variante (`$badges['impresa']`),
  porque es el mismo estado y tenerlo de dos colores según la pantalla no ayuda a nadie.
- **La cabecera de categoría pliega entera**, en el índice y en la galería: `data-plegar` pasó
  del `<button>` de la flecha al contenedor de la línea. El botón se queda dentro (sin el
  atributo) para que el plegado siga siendo alcanzable con el teclado — su clic burbujea hasta
  el contenedor, así que la acción se ejecuta una sola vez — y ahora lleva `aria-expanded`, que
  `pintar()` mantiene al día. El manejador ignora los clics que salgan de un `form` o un enlace,
  porque los botones de mover categoría de «Organizar» viven en esa misma línea y colocar una
  categoría no debe plegarla de paso.

**Eje 2 — si además hay trabajo encima**: badge `modificando`, detrás del anterior y sin
sustituirlo nunca. Una pieza puede estar validada Y modificándose a la vez (es el ciclo normal),
y si se sustituyeran se perdería de vista qué versión buena hay justo en las piezas que se están
tocando. Con borde en vez de color sólido, porque modificar es lo normal y no debe competir por
la atención con lo que sí la reclama.

- Condición (`trabajo_en_curso` en `Web::resumen()`): **rama abierta con al menos una sesión**,
  abierta o ya subida. No cuesta ninguna consulta extra — `estadoDeSincronizacion()` ya devolvía
  las dos cosas.
- **Recién promocionada NO cuenta**: la rama nueva nace vacía (4.4). Si contase, `modificando`
  saldría en casi todas las piezas siempre y dejaría de decir nada.
- Lo que de verdad no se veía en ninguna parte era el caso **subido, cerrado y sin promocionar**:
  no hay sesión abierta que muestre el candado ni descarga pendiente que avisar, así que la fila
  se veía idéntica a una pieza intacta. La sesión abierta ya salía, pero en la columna "Aviso" y
  como candado con el nombre de la máquina — que se lee como "bloqueado", no como "en marcha".
  Ese candado se queda donde está: aporta **en qué máquina**, que `modificando` no dice.

**En el cliente, el mismo vocabulario** (`estado_de_version()` y `avisos_de()` en
`trackbitos.py`, usadas por `catalogo` y `variantes`): dos vocabularios para el mismo estado
obligarían a traducir mentalmente al pasar de la terminal al navegador. Para poder distinguirlos,
`resumenVariante()` de la API expone ahora `ultima_version_estado` y `trabajo_en_curso`, que ya
tenía `Web::resumen()` pero no salían por la API.

- `modificando, sin promocionar` solo se añade **cuando no hay sesión abierta**: si la hay, el
  aviso de sesión ya dice que se está modificando, y con la máquina además.
- Es aditivo, así que un cliente anterior lo ignora sin romperse; y al revés — el cliente se
  autoactualiza (fase 15) y la web no, así que un cliente nuevo contra un servidor viejo
  **detecta que la clave no viene y cae a `sin validar`**, sin aviso de trabajo en curso. Es lo
  único cierto con lo que ese servidor sabe contar, y preferible a afirmar un estado inventado.
- La columna del listado del cliente pasa de `:<16` a `:<21`. Con 16 ya se descuadraba desde
  antes: "sin versión buena" medía 17.
- Cliente **v1.5.0**. Tocar `trackbitos.py` sin subir `VERSION` dejaría a las dos máquinas sin
  enterarse: la autoactualización (fase 15) compara esa constante con la del fichero desplegado
  en el servidor, así que el número es parte del cambio, no un trámite posterior.

**Fase 20 (2026-08-18): no se sigue trabajando a ciegas.** Dos agujeros por los que el trabajo se
salía del registro, uno de diseño y otro un fallo.

**Invariante 9 — una impresión sin juzgar bloquea el trabajo nuevo** (sección 2, donde está el
razonamiento completo). `PiezaService::exigirNadaSinJuzgar()`, llamado desde `abrirSesion()` y
`devolverATrabajo()`. En la web, `accionesDisponibles()` desactiva "Devolver a trabajo" con su
motivo, y la ficha lo avisa **arriba del todo**, antes que el bloqueo de sesión: es lo que impide
trabajar desde el cliente, y allí no hay botones que desactivar — el usuario solo se encontraría
un error al escribir `abrir`. El aviso lleva un enlace directo a cada versión pendiente
(`#version-{id}`) para juzgarla sin buscarla por el historial.

**`cerrar` dejaba escapar el trabajo de una sesión abierta de cero.** El cliente ya se negaba a
cerrar con cambios sin subir, pero comparando contra `hash_origen` — y `abrir` (empezar una pieza
de cero, sin partir de ningún fichero) lo escribe a `None`. La condición exigía que existiese para
actuar, así que en ese caso concreto no protegía nada: modelabas, guardabas, cerrabas, y el
`.blend` se quedaba solo en tu disco con la sesión cerrada y vacía. Ahora, si hay un `.blend`
delante, sin subida previa se niega igual. Sin `.blend` sí se puede cerrar: no hay nada que subir
y esa sesión de consulta es legítima.

Esta comprobación vive **solo en el cliente**, a diferencia del invariante 9: el servidor no puede
saber si hay un fichero sin subir en el disco de la otra máquina. Lo único que ve es una sesión
sin `subida_en`, que es exactamente lo mismo que una sesión de consulta legítima.

**Fase 21 (2026-08-18): varios STL por versión.** Un modelo se imprime a trozos más veces de lo
que parece: los dos brazos van por separado aunque compartan escena, y una pieza más alta que la
placa se corta y se monta. Con una sola columna `ruta_stl` había que elegir qué trozo se guardaba,
o inventarse versiones falsas para meter los demás.

**El `.blend` sigue siendo uno solo**, y no es una limitación pendiente de levantar: ahí están
todas las partes juntas, y eso es justo lo que lo hace la fuente de la versión. Lo que se
multiplica es la exportación para imprimir, no el modelo.

- **Tabla `piezas_version_stls`**: `version_id` → `nombre` (qué trozo es: "brazo izquierdo",
  "completo"), `ruta_stl`, `hash_stl`, `tamano_bytes`, `subido_en`. `UNIQUE (version_id, nombre)`
  — el nombre es lo único que distingue un trozo de otro, y repetido daría dos ficheros
  indistinguibles al bajarlos. `ON DELETE CASCADE`.
- **Las columnas `ruta_stl`/`hash_stl` de `piezas_versiones` se retiran**, después de que la
  migración copie su contenido a la tabla nueva con el nombre `completo` (que es lo que de verdad
  eran). Dejarlas habría sido tener dos sitios donde mirar y ninguna forma de saber cuál manda.
  El `down()` devuelve el más antiguo de cada versión. Salen también de
  `PiezaVersionModel::CAMPOS_INMUTABLES`: esa inmutabilidad se aplica ahora en
  `PiezaService::adjuntarStl()`.
- **Alta en dos pasos**, como las referencias: `reservarStl()` crea la fila y devuelve su id,
  porque la ruta del fichero lo lleva dentro (`version-v002-stl-7.stl`) — sin él, el segundo STL
  pisaría el fichero del primero, que es exactamente lo que la ruta anterior (determinista por
  variante+número) hacía. Luego `adjuntarStl()` confirma ruta y hash. Si el guardado falla a
  mitad, el controlador deshace la reserva para no dejar un STL fantasma sin fichero.
- **`quitarStl()`**: con varios por versión, subir el equivocado deja de ser un accidente raro y
  hace falta una vía de vuelta. El fichero va a la **papelera** (invariante 6), no se borra. La
  fila sí se borra, a diferencia de las sesiones purgadas: una sesión conserva número, hashes y
  log porque documenta trabajo que existió; un STL retirado no documenta nada que el historial
  necesite.
- **La placa se lleva todos los trozos de la versión**: media pieza no se imprime. El `.zip`
  nombra cada fichero con el trozo al final (`PM-042-brazos-base-v002-brazo-izquierdo.stl`) —
  sin eso, tres trozos de la misma versión se descargarían con el mismo nombre y el navegador
  los numeraría `(1)`, `(2)`.
- La galería muestra cuántos trozos tiene una pieza cuando son más de uno, para no mandar a la
  placa media pieza creyendo que va entera.
- **Rutas**: subir sigue colgando de la versión (`POST version/{id}/stl`, es a lo que se adjunta),
  pero descargar y quitar cuelgan del STL concreto (`GET stl/{id}/descargar`,
  `POST stl/{id}/quitar`).

**Fase 22 (2026-08-19): `abrir` fusionado en `bajar`.** El comando aparte "abrir" (sesión sin
descargar, solo para piezas recién estrenadas) desaparece: `bajar` ya calculaba internamente si
había algo que descargar (`origen_descarga`) y, si no había nada, simplemente se negaba y remitía
a "abrir". Ahora, en ese mismo caso — y solo con motivo "trabajo", no con "ver" — hace lo que
hacía "abrir": abre la sesión sin descargar nada, en vez de negarse. Un único comando para las dos
situaciones, sin que el usuario tenga que saber de antemano si hay algo que traer o no (mismo
principio que `git checkout`/`pull` sobre una rama vacía o con historial). Alias `a` (antes de
"abrir") se conserva apuntando ahora a "bajar", junto al `b` de siempre. La llamada al servidor
sigue siendo la misma (`POST /variante/{id}/sesion/abrir`), así que el invariante 9 (fase 20,
"no se sigue trabajando a ciegas") se aplica igual que antes — no hizo falta tocar nada del lado
del servidor.

**Fase 23 (2026-08-19): el cliente se actualiza solo, sin pedirlo.** La fase 15 dejó avisando
("hay una versión nueva: trackbitos actualizar") pero exigiendo el paso a mano — con dos
máquinas y sesiones que se turnan para tocar el módulo, ese paso se olvida, y entre medias cada
una corre una versión distinta del cliente.

- **`_aplicar_actualizacion(config, remota)`**: se extrae de `cmd_actualizar` la parte de
  "cómo" actualizar (descargar, comprobar que compila antes de tocar nada, apartar la versión
  vieja a la papelera —invariante 6—, escribir la nueva), para que la compartan `cmd_actualizar`
  (invocación explícita) y el gancho automático. La lógica de reemplazo no cambia — sigue
  verificando sintaxis antes de escribir y apartando en vez de sobrescribir directamente.
- **El gancho de `main()`** (antes solo `comprobar_version_remota` + un aviso) ahora, si hay
  versión nueva, llama a `_aplicar_actualizacion` directamente y confirma con un mensaje
  ("trackbitos se actualizó solo: vX → vY"). Sigue disparándose DESPUÉS del comando (nunca
  antes, para no añadir latencia a lo que el usuario vino a hacer) y en silencio ante cualquier
  fallo — un tropiezo actualizándose no debe ensombrecer el resultado del comando real que sí le
  importaba a esa ejecución. Probado extremo a extremo con un servidor HTTP local de prueba:
  detecta la versión remota, descarga, reemplaza el fichero y aparta la versión vieja
  correctamente.
- **`trackbitos actualizar` se conserva** para forzar la comprobación ya mismo (sin esperar al
  próximo comando) o para ver el error de verdad si la automática viene fallando en silencio —
  ella sola nunca explica por qué no se actualizó, a propósito, para no ensuciar la salida del
  comando que el usuario sí pidió.
- **De un salto de versión a otro**: una máquina en una versión anterior a esta fase no se
  autoactualiza sola la primera vez — necesita un `trackbitos actualizar` manual, como hasta
  ahora, para llegar aquí. A partir de entonces, ya no hace falta ningún paso más.

**Fase 24 (2026-08-19): papelera también por variante suelta.** Hasta ahora solo se podía borrar
la pieza entera (`borrarFamilia`). Una pieza con varias líneas de diseño puede tener alguna
abandonada — un tamaño que no se pidió nunca más, un prototipo descartado — sin que el resto
tenga nada que ver; borrar la familia entera para quitar solo esa variante se habría llevado por
delante las demás.

- **`piezas_variantes.borrado_en`**, mismo criterio que `piezas_familias.borrado_en`: mientras
  esté vacío es una variante normal; en cuanto se pone, desaparece del índice, la galería y el
  catálogo del cliente (`Api::variantes()`), pero se puede restaurar durante 30 días desde
  `/piezas/papelera` antes de que `piezas:purgar` se la lleve de verdad.
- **`PiezaService::borrarVariante()`/`restaurarVariante()`/`purgarVariantesBorradas()`**, calcados
  de sus equivalentes de familia: se niega si la variante tiene una sesión de trabajo abierta;
  la purga aparta versiones, STL (varios por versión, fase 21), renders y sesiones sin promocionar
  a la papelera de ficheros antes de borrar la fila — pero **no** toca las referencias, que son de
  la familia entera (spec 1.1), no de esta línea de diseño.
- **`piezas:purgar`** ahora corre las dos purgas (familias y variantes) antes de la de ficheros
  por edad, por el mismo motivo de siempre: las dos primeras mueven ficheros a la papelera de
  ficheros, así que tienen que ir antes de que esa papelera se purgue por antigüedad.
- **Dónde se borra**: un botón en la propia ficha de la variante (junto al lápiz de editar), y
  uno por cada subfila en el índice cuando una pieza tiene más de una variante (modo
  "Organizar") — con una sola variante ya está `borrarFamilia` para eso, no hace falta un botón
  redundante.
- **`/piezas/papelera`** separa ahora "Piezas enteras" y "Variantes sueltas" en dos listas, cada
  una con su propio botón de restaurar; el contador del índice suma las dos.

**Fase 25 (2026-08-19).** Dos arreglos sueltos, uno en cada lado del módulo.

- **`trackbitos estado` en un directorio vacío ya no dice "corrupto".** Sin `.sesion.json` ni
  `.blend`, `evaluar()` recibía todo a `None` y caía en la rama de "estado corrupto: no se borra
  nada" — un mensaje pensado para cuando algo que SÍ era una mesa de trabajo pierde su fichero,
  no para un directorio que nunca lo fue. `cmd_estado` corta ahora antes, con el mismo mensaje
  que ya daba `_exigir_sentinel()` para `subir`/`cerrar`/`promocionar` ("no es un directorio de
  trabajo... empieza con trackbitos bajar"), para no decir dos cosas distintas de la misma
  situación según el comando.
- **El índice web muestra quién está trabajando en qué, arriba del todo.** Antes solo se veía el
  bloqueo mirando la ficha de cada variante una por una. `Web::calcularSesionesActivas()` barre
  todas las sesiones abiertas de la pieza (puede haber varias a la vez, una por máquina, en
  piezas distintas) y las remonta hasta su variante y familia. Se refresca sola cada 20s vía
  `fetch()` a `GET /piezas/sesiones-activas` — repinta solo esa franja, no la página entera
  (recargar borraría el buscador escrito o el modo "Organizar" encendido), construyendo el DOM a
  mano (`textContent`, no `innerHTML` con texto interpolado) para no abrir un XSS con un nombre
  de máquina o de pieza. Se detiene sola si la pestaña está en segundo plano (Page Visibility
  API) — no gasta peticiones en algo que no se ve.

**Fase 26 (2026-08-19): el índice contesta preguntas, y un botón mal pulsado se deshace.**

- **Vocabulario: lo siguiente que hay que hacer, no lo que falta.** `borrador` se lee ahora
  **`para imprimir`** en el índice (era "versión sin imprimir") y **`para imprimir y evaluar`** en
  el historial de la ficha, donde antes salía el nombre crudo del ENUM. Es lo mismo por fuera,
  pero "sin imprimir" nombra una carencia y "para imprimir" nombra la acción — que es a lo que se
  viene. El resto de estados no se traducen: ya se explican solos, y alejarlos del nombre de la
  base de datos por gusto no gana nada. En el CLI, `estado_de_version()` dice lo mismo (v1.7.2) —
  web y terminal no pueden llamar de dos maneras distintas al mismo sitio.
- **"Sin empezar"**, badge nuevo en la columna Estado: una pieza dada de alta y sin ningún
  `.blend` no está "sin versión" como la que tiene trabajo encima esperando a promocionarse —
  está sin abrir Blender. Nunca convive con "modificando": en cuanto hay sesión abierta o algo
  subido, vuelve a ser "sin versión" (si no, la celda se contradiría a sí misma).
- **Filtros del índice**, una barra de chips sobre la tabla con el contador de piezas de cada
  uno: Definitivas, Imprimir, Sin STL, Sin validar, No sirven, Modificando, Sin empezar. Son las
  preguntas que uno se hace de verdad ("¿qué me falta exportar?", "¿qué mando a la impresora
  hoy?"), no la lista de estados internos. **"Imprimir" es un botón partido**: la mitad
  izquierda filtra todas las pendientes de imprimir de un clic (la pregunta más frecuente) y la
  flecha abre *Todas / Con STL / Falta STL*, porque lo siguiente que hay que hacer no es lo mismo
  en los dos casos — una va a la impresora, la otra hay que exportarla antes. Con el menú cerrado
  el botón enseña cuál de las tres está puesta ("Imprimir · Con STL") y su contador; si no, la
  mitad de los filtros quedarían escondidos detrás de una flecha. Uno cada vez (no facetas que se
  sumen), se combinan con el buscador en Y, y no persisten
  entre recargas: un filtro pegado de la sesión anterior se lee como piezas perdidas. Los tokens
  de cada fila (`data-tokens`) salen de lo que **se ve** en la columna STL, no de un cálculo
  paralelo: filtrar por algo distinto de lo pintado parecería un error. La fila de una pieza con
  varias variantes lleva la unión de los tokens de todas, para que aparezca cuando encaje
  cualquiera de ellas.
- **El `.blend` a mano, desde la propia fila.** Junto al badge "sin STL" hay un icono de descarga
  (`GET version/{id}/blend/descargar`, el mismo de la ficha, con su sufijo `solo-lectura` en el
  nombre): lo que falta ahí es exportar, y para exportar hace falta el fichero. Filtrando por
  "Imprimir · falta STL" se bajan todos de una tacada en vez de entrar y salir de cada ficha.
  Solo aparece donde falta el STL — en las demás filas sería ruido, y esta descarga no queda
  registrada (no pasa por el cliente), así que no conviene ofrecerla más de lo necesario.
- **Deshacer** (verbo `devolverABorrador`, tabla de la sección 7.1): descartar era irreversible,
  así que equivocarse de botón dejaba la versión descartada para siempre — y con `descartada`
  fuera de todas las transiciones, la ficha parecía un callejón sin salida.
- **`trackbitos trabajar <pieza>` (cliente v1.9.0)**: de "quiero seguir con el pincel" a tenerlo
  abierto en Blender, en un comando — crea la carpeta, baja dentro, abre la sesión y lanza el
  fichero. Los tres pasos de alrededor de `bajar` eran manuales y se repetían cada vez.

  **Delega en el mismo `_bajar`**, así que las negativas son exactamente las de siempre (copia
  viva en la otra máquina, invariante 9, cambios sin subir aquí) y si `bajar` se niega no se abre
  nada. El nombre se resuelve **antes** de crear ningún directorio: si encaja con varias piezas,
  `resolver_variante` las lista y no queda por el camino una carpeta vacía a medio nombrar.
  Estando ya dentro de la mesa de esa misma variante se trabaja ahí, en vez de anidar
  `pincel/pincel/pincel`.

  `carpeta_para()` da el nombre: sin acentos ni espacios (esa ruta se escribe a mano y se pega en
  rutas de Blender, y lo legal en Windows y en macOS no coincide), y sin el sufijo de la variante
  cuando es la `base` — está en todas las piezas y no distingue nada, igual que en los listados.

  `abrir_en_el_sistema()` usa la asociación del sistema: `os.startfile` en Windows (lo mismo que
  `ii` en PowerShell), `open` en macOS, `xdg-open` en el resto. Si falla no tumba el comando: lo
  importante —bajar y abrir sesión— ya está hecho, y se dice que lo abra a mano. Sin `.blend`
  (pieza recién estrenada) abre la carpeta, que es donde hay que guardar el fichero que aún no
  existe.
- **`trackbitos limpiar` (cliente v1.8.0)**: recoge la mesa de trabajo — aparta el `.blend` local
  (y su `.sesion.json`) a la papelera del cliente. El veredicto de `estado` ya decía desde la
  fase 4 "puedes borrar la copia local con seguridad" (`accion: "borrable"`), pero borrarla
  quedaba como gesto manual en el explorador, que es justo donde uno se equivoca: basta con que
  quedara algo sin subir para perderlo.

  **Solo actúa con el veredicto `borrable` exacto**, ya corregido con las dos salvedades que
  aplica `estado` (sesión abierta → `cerrar_sesion`; descarga sin cerrar → `cerrar_sin_cambios`).
  Cualquier otra cosa —cambios sin subir, la nube más adelantada, o no poder consultarla siquiera—
  y no toca nada: imprime el mismo diagnóstico que `estado` y sale con 1. **Sin `--forzar` a
  propósito**: tirar algo sin subirlo es una decisión, no un flag. Para que las dos órdenes no
  puedan discrepar sobre qué es "estar en su sitio", el cálculo de los tres hashes se extrajo a
  `_diagnostico()`, compartido por `cmd_estado` y `cmd_limpiar`.

  El `.sesion.json` se va con el `.blend` porque un sentinel huérfano haría que el siguiente
  `estado` cantara "falta el .blend, estado corrupto" sobre un directorio que se recogió a
  propósito; recogido del todo, dice lo que toca ("empieza con trackbitos bajar").
- **El comando, donde hace falta.** Cuando la rama abierta ya parte de la versión que estás
  mirando, todos sus botones de estado están apagados con razón: se sigue desde el cliente. Antes
  eso lo decía una frase gris que remitía a otra caja al final de la página; ahora esa misma
  tarjeta lleva el `trackbitos bajar "<pieza> <variante>"` listo para copiar.

---

## 0. Contexto

Trackbitos es una aplicación personal en **CodeIgniter 4** alojada en Hostinger. Se le añade un módulo **Piezas** para controlar el versionado de modelos 3D de figuras Playmobil-compatibles diseñadas en Blender e impresas en resina.

El problema que resuelve: al iterar sobre una pieza (modelar → imprimir → detectar fallo → corregir → reimprimir) se pierde el rastro de qué fichero es el bueno, especialmente al alternar entre dos máquinas (macOS y Windows).

**Decisión de diseño central:** Trackbitos es la **única fuente de verdad**. No se usa Git ni carpetas "definitivos". Los ficheros `.blend` son pequeños (~350 KB), así que se almacenan en el propio servidor.

**Principio rector:** el sistema debe ser mecánico y a prueba de despistes. Ante cualquier operación destructiva o ambigua, el sistema **se niega y explica**, en lugar de preguntar "¿estás seguro?". El usuario tiene TDAH y un diálogo de confirmación a las once de la noche siempre se responde que sí.

Mono-usuario: solo dos equipos (Windows + macOS). Auth de la API: un único token Bearer compartido, no tokens por máquina.

---

## 1. Modelo de datos

### 1.1 Jerarquía

```
familia  →  variante  →  version
                      →  rama  →  sesion
```

- **familia**: la pieza conceptual (cuerpo, brazo, casco). ⚠️ **En la interfaz se llama "Pieza"** — ver la nota de vocabulario en la fase 12.
- **variante**: una línea de diseño dentro de la familia (torso-recto, pose-futbolista). Cada variante tiene su propia numeración de versiones desde v001.
- **version**: un estado congelado y consolidado, creado al promocionar. **Inmutable.**
- **rama**: línea de trabajo abierta partiendo de una versión. Nunca se edita una versión existente; se apila encima.
- **sesion**: cada vez que se abre Blender para trabajar. Genera un `.blend` numerado.

Las **referencias** (medidas del original con calibre, fotos) y las **imágenes de compartir** cuelgan de la *familia*, no de la variante: son comunes a todas las variantes y no deben duplicarse.

### 1.2 Tablas

```sql
maquina
  id, uuid, nombre, hostname, so, primera_vez, ultima_vez
  UNIQUE (uuid)
  -- Alta automática desde el cliente. El nombre se propone a partir
  -- del hostname y es editable en la web.

familia
  id, nombre, notas, creado_en

variante
  id, familia_id, nombre, origen_version_id (nullable), notas, creado_en
  -- origen_version_id: de qué versión de qué otra variante se derivó.
  -- NULL solo para la variante inicial de la familia.

version
  id, variante_id, numero, estado, promocionada_en,
  ruta_blend, hash_blend,   -- un solo .blend: todas las partes en la misma escena
  cambio,              -- obligatorio, una línea: qué se modificó
  medidas,             -- texto libre: cotas de calibre relevantes
  params_impresion,    -- exposición, altura de capa, capas base
  resultado,           -- rellenado tras imprimir
  UNIQUE (variante_id, numero)

version_stl            -- fase 21: una pieza se imprime a trozos y se monta
  id, version_id, nombre, ruta_stl, hash_stl, tamano_bytes, subido_en
  UNIQUE (version_id, nombre)

rama
  id, variante_id, desde_version_id (nullable), abierta,
  cerrada_por_version_id (nullable), abierta_en, cerrada_en
  -- nombre derivado: "desde-v002". La rama inicial tiene desde_version_id NULL.

sesion
  id, rama_id, numero, maquina_id, abierta_en, cerrada_en (nullable),
  ruta_blend (nullable), hash_blend (nullable), tamano_bytes (nullable),
  hash_padre (nullable),   -- hash del que se partió al descargar
  subida_en (nullable), log, purgada (bool, default false)
  UNIQUE (rama_id, numero)

descarga
  id, sesion_id (nullable), variante_id, rama_id, maquina_id,
  motivo (enum: trabajo | consulta), descargado_en, hash_entregado,
  cerrada (bool, default false), cerrada_en (nullable),
  cerrada_por (enum: subida | sin_cambios | forzado, nullable),
  cerrada_sesion_id (nullable),   -- la sesión que la cerró, si fue por subida
  motivo_forzado (nullable)
  -- sesion_id es NULL en descargas de consulta: no abren sesión.
  -- Registro append-only. Nunca se edita salvo para cerrarlo. Nunca se borra.
```

### 1.3 Estados de `version`

```
borrador → impresa → validada
                  ↘ descartada
validada → superada   (automático al validar otra)
```

- `borrador`: promocionada pero aún no impresa.
- `impresa`: existe pieza física, pendiente de juicio.
- `validada`: es la buena. **Máximo una por variante.**
- `superada`: fue validada, la reemplazó otra.
- `descartada`: no sirve. **No se borra nunca**, se conserva con el motivo en `resultado`.

---

## 2. Invariantes (obligatorios a nivel de modelo, no solo de UI)

Estos son el núcleo del sistema. Deben validarse en los modelos/servicios y, donde sea posible, con restricciones de base de datos. Que la UI los respete no es suficiente.

1. **Una sola versión `validada` por variante.** Al validar una, la anterior pasa automáticamente a `superada` en la misma transacción.
2. **Una sola rama `abierta` por variante.** Promocionar la cierra.
3. **Una sola sesión sin cerrar por variante.** Actúa como bloqueo de máquina.
4. **Las versiones son inmutables** en `ruta_blend`, `hash_blend` y `numero`. Los campos de anotación (`resultado`, `medidas`) sí son editables. Cada STL de la versión es igual de inmutable una vez subido (fase 21): se añade o se quita, nunca se sobreescribe.
5. **Las sesiones no se purgan hasta que la versión que las cerró pasa a `validada`.** No al promocionar: si la impresión sale mal, aún hacen falta.
6. **Nada se borra: se mueve a papelera.** Servidor y cliente. Purga automática a los 30 días.

**Qué significa "purgar una sesión" (fase 7).** Se aparta su `.blend`, no su registro. La fila conserva número, hashes, máquina, fecha y log, y solo se marca `purgada = 1`: lo que ocupa sitio es el fichero, y lo que da valor al historial dentro de tres meses es el registro. La versión validada tiene su propia copia del fichero, así que no se pierde nada recuperable — y durante los 30 días de gracia el `.blend` sigue en la papelera, con `ruta_blend` apuntando ahí por si hace falta rescatarlo a mano.
7. `version.cambio` es obligatorio y no puede quedar vacío. Es el campo que da valor al historial dentro de tres meses.
8. **Toda descarga se cierra**, por subida, por declaración de "sin cambios" o por cierre forzado con motivo. Una subida solo se acepta si su `hash_padre` coincide con el `hash_entregado` de una descarga abierta de esa misma máquina.
9. **Una impresión sin juzgar bloquea el trabajo nuevo** (fase 20). Mientras la variante tenga alguna versión en estado `impresa`, no se puede `abrirSesion` ni `devolverATrabajo`. Se sale validando o descartando.

**Por qué el 9.** Seguir modelando encima de algo que se imprimió pero no se juzgó es trabajar a ciegas: no sabes si partes de una pieza buena. Y ese juicio, si no se hace en caliente —con la pieza recién salida de la impresora en la mano—, no se hace nunca: quedan versiones `impresa` para siempre y el historial deja de decir cuál era la buena. Si ya sabes que no vale, el camino es descartarla con el motivo (que queda escrito, spec 1.3) y seguir desde ahí — no dejarla en el limbo.

Mira **todas** las versiones, no solo la última: si mirase solo la última, promocionar otra encima bastaría para que el bloqueo desapareciera y la impresa se quedara sin juzgar, que es justo lo que la regla existe para impedir.

Lo que **no** bloquea, a propósito: subir y cerrar una sesión que ya estaba abierta cuando se marcó la impresión, y promocionar lo que ya estaba subido. La regla cierra las puertas de entrada al trabajo nuevo, no atrapa dentro el que ya estaba en marcha. Tampoco afecta a `derivarVariante`: derivar abre otra línea de diseño, y el juicio pendiente es de la variante original.

**Nota de implementación (fase 1):** MySQL no soporta índices únicos parciales (`WHERE estado='validada'`), así que el invariante 1 no tiene respaldo a nivel de esquema — vive solo en `PiezaVersionModel::marcarValidada()` (transacción + `SELECT ... FOR UPDATE`). Igual para 2 y 3 en `PiezaRamaModel::abrir()` / `PiezaSesionModel::abrir()`.

---

## 3. Verbos (acciones del dominio)

Cada uno es una transacción atómica que debe fallar entera si viola un invariante.

| Verbo | Efecto |
|---|---|
| **Abrir sesión** | Reclama la máquina. Crea `sesion` en la rama abierta. Falla si ya hay una sesión sin cerrar. |
| **Subir sesión** | Guarda el `.blend`, calcula y almacena hash y tamaño, marca `subida_en`. |
| **Cerrar sesión** | Marca `cerrada_en`. Libera el bloqueo de máquina. |
| **Promocionar** | Crea `version` con el `.blend` de la última sesión subida. Cierra la rama actual. Abre rama nueva `desde-vNNN`. Exige `cambio` no vacío. |
| **Devolver a trabajo** | Abre rama nueva partiendo de una versión. **No modifica la versión.** |
| **Marcar impresa** | `borrador → impresa`, con `params_impresion`. |
| **Validar** | `impresa → validada`. Degrada la anterior validada a `superada`. Habilita la purga de sesiones de la rama que cerró esa versión. |
| **Descartar** | Pasa a `descartada` exigiendo motivo en `resultado`. |
| **Deshacer** | `impresa`/`descartada → borrador`. Solo para arreglar un botón mal pulsado o un texto mal escrito, no para cambiar de opinión sobre una pieza ya impresa. Borra el texto del juicio que deshace (`resultado` al deshacer un descarte, `params_impresion` al deshacer una impresión); los `params_impresion` sobreviven a deshacer un descarte, que no era lo que estaba mal. `validada` y `superada` quedan fuera: encima de ellas ya hay cadena montada (invariante 1) que esto desharía en silencio. |
| **Derivar variante** | Crea variante nueva con `origen_version_id` apuntando a la versión de partida. Numeración propia desde v001. No copia ficheros ni referencias. |

Implementados en `PiezaService` (fase 2) como: `crearFamilia`, `crearVariante`, `abrirSesion`, `subirSesion`, `cerrarSesion`, `promocionar`, `devolverATrabajo`, `marcarImpresa`, `validar`, `descartar`, `devolverABorrador`, `derivarVariante`.

**Corrección de "devolver a trabajo" (descubierta en la fase 6).** Tal como estaba, el verbo no podía ejecutarse nunca: siempre hay una rama abierta (promocionar cierra una y abre otra), y el invariante 2 no admite una segunda. Retomar una versión antigua implica por fuerza abandonar la línea en curso, así que el verbo ahora cierra la rama abierta —sin versión que la cierre, porque no se promociona— y abre la nueva. Al ser destructivo, por defecto **se niega y explica** cuántas sesiones subidas quedarían sin promocionar; solo procede con confirmación explícita (`abandonar_rama`), que en la web es una casilla que nombra la consecuencia. Las sesiones no se pierden: quedan colgando de la rama cerrada.

**Matiz (fase 26): la rama vacía no se defiende.** Esa negativa solo tiene sentido si hay algo dentro. Con cero sesiones subidas —el caso normal justo después de promocionar— el aviso decía literalmente "0 sesión(es) subida(s) sin promocionar": un obstáculo delante de una puerta que no daba a ningún sitio. Ahora solo salta con `$subidas > 0`.

---

## 4. Sincronización por hashes

### 4.1 Por qué no fechas

Comparar por fecha de modificación no vale: abrir un `.blend` y guardarlo sin cambios altera el timestamp, y los relojes de las dos máquinas pueden diferir. Se compara **SHA-256 del contenido**.

### 4.2 El fichero centinela

Al descargar una mesa de trabajo, el cliente deja junto al `.blend` un fichero `.sesion.json`:

```json
{
  "variante_id": 3,
  "variante": "torso-recto",
  "rama": "desde-v002",
  "sesion_id": 41,
  "sesion": 8,
  "hash_origen": "a3f1...",
  "descargado_en": "2026-08-15T18:42:00+02:00",
  "maquina": "MacBook"
}
```

`hash_origen` es el ancla del sistema: es lo que permite distinguir "no he tocado nada" de "he perdido trabajo".

### 4.3 Los tres hashes

- **local**: SHA-256 del `.blend` en disco ahora.
- **origen**: `hash_origen` del `.sesion.json` (lo que se descargó).
- **nube**: hash de la última sesión subida en el servidor.

**Qué significa que el hash cambie.** Si el fichero no se guarda, el hash es idéntico con total garantía: abrir un `.blend` no altera los bytes en disco. Pero si se guarda, el hash cambia casi siempre aunque no se mueva ningún vértice, porque el `.blend` almacena también estado de la interfaz y bloques internos que varían entre ejecuciones.

Esto es deseable: fuerza a subir en caso de duda, y subir 350 KB no cuesta nada. El coste de un falso positivo (subes algo que no cambió) es despreciable; el de un falso negativo (das por bueno algo que sí cambió) es perder trabajo.

**Consecuencia para los mensajes:** el cliente nunca debe afirmar que se modificó el modelo, porque no puede saberlo. Redacción correcta:

```
  local ≠ entregado   ⚠ el fichero ha cambiado en disco
    (puede ser una modificación real o solo un guardado)

  → Ejecuta: trackbitos subir
```

El campo `sesion.log` debe permitir anotar cosas como "guardado sin cambios de geometría", para que dentro de dos meses se entienda por qué existe esa sesión.

| local vs origen | origen vs nube | Situación | Acción del cliente |
|---|---|---|---|
| = | = | Al día | Borrable con seguridad |
| = | ≠ | La nube avanzó | Descarga segura |
| ≠ | = | Trabajo sin subir | **Bloquear descarga y borrado.** Ofrecer subir |
| ≠ | ≠ | Divergencia real | Bloquear. Ofrecer subir como sesión nueva |

**Casos límite a manejar explícitamente:**
- Falta el `.sesion.json` → tratar como divergencia (fila 4). Nunca asumir que está al día.
- `.sesion.json` presente pero sin `.blend` → estado corrupto, avisar y no borrar nada.
- Sesión abierta en otra máquina → avisar antes de cualquier operación.

Implementado y probado (fase 3) en `piezas-cli/trackbitos.py::evaluar()` — función pura, las 4 filas + los 2 casos límite verificados creando ficheros a mano.

### 4.4 El círculo descarga → subida

Los tres hashes solo ven la máquina actual. Existe un cuarto riesgo que no cubren: **una copia descargada en la otra máquina con cambios que nunca se subieron.** El servidor no puede detectarla comparando hashes porque esos bytes nunca le llegaron.

Se resuelve tratando cada descarga como un asiento que debe cuadrarse. **Toda descarga se cierra con una subida desde la misma máquina, y esa subida declara de qué hash partió.**

**Al descargar:** se crea el registro con `hash_entregado`, máquina y fecha. Queda abierto.

Hay **dos motivos de descarga** y conviene declararlos al bajar:

- `trabajo` — vas a modificar. Abre sesión y consume número correlativo.
- `consulta` — solo vas a mirar (revisar una cota, comparar con la pieza física). **No abre sesión ni consume número.** Deja el asiento de descarga abierto igualmente, porque el fichero está en tu disco y el sistema debe saberlo.

Una descarga de consulta se cierra normalmente con `--sin-cambios`. Si al mirarla acabas modificando algo, se cierra con una subida como cualquier otra, y esa subida sí abre sesión. El motivo declarado al bajar no ata: solo evita generar sesiones vacías en el caso normal.

**Al subir:** el cliente envía `hash_padre` (el `hash_origen` de su `.sesion.json`) junto al fichero nuevo. El servidor comprueba:

1. Existe una descarga abierta para esa máquina y esa rama.
2. `hash_padre` coincide con `hash_entregado` de esa descarga.

Si ambas se cumplen, guarda la sesión nueva, registra su `hash_padre` y cierra la descarga con `cerrada_por = subida`. Si no coinciden, **rechaza la subida** (HTTP 409) e informa de qué hash esperaba: el fichero no procede de la última descarga y aceptarlo silenciosamente perdería trabajo intermedio.

**Descarga sin cambios:** si bajaste y no tocaste nada, no hace falta subir un fichero idéntico. `trackbitos cerrar --sin-cambios` cierra el asiento con `cerrada_por = sin_cambios`, previa verificación de que el hash local sigue siendo igual al entregado. El cliente lo verifica; el servidor no se fía y no acepta el cierre sin que el cliente declare el hash local, que debe coincidir. **No crea versión ni sesión: no deja rastro salvo el propio asiento cerrado.**

Este cierre **solo puede ejecutarse desde la máquina que tiene la copia**, porque la prueba es el hash del fichero en ese disco. No debe existir forma de cerrarlo como "sin cambios" desde la otra máquina ni desde la web; para eso está el cierre forzado, que se registra distinto precisamente porque no hay prueba.

**Efecto:** el encadenamiento `hash_padre` → `hash_blend` de cada sesión forma una cadena verificable desde una versión hasta la siguiente. Si algún eslabón no cuadra, hay una copia perdida en algún sitio y el sistema lo sabe.

**Aviso en la otra máquina:** si existe alguna descarga abierta en una máquina distinta de la actual, avisar antes de abrir sesión o descargar. El aviso exige confirmación explícita.

```
$ trackbitos bajar
torso-recto · rama desde-v002

  ⚠ Descarga sin cerrar en MacBook — 12/08, motivo: consulta
    Esa copia nunca se cerró. Si trabajaste ahí, perderás ese trabajo.

  → Revisa el portátil antes de continuar.
    Si sabes que no tocaste nada:  trackbitos bajar --ignorar-pendiente
```

Resolución esperada: vas al Mac y ejecutas `trackbitos estado`. El script compara el hash del fichero que hay allí con el entregado y te dice cuál de las dos cosas hacer:

```
$ trackbitos estado          # en el MacBook
torso-recto · descarga de consulta del 12/08 · sin cerrar

  local = entregado   ✓ no se modificó nada

  → Ejecuta: trackbitos cerrar --sin-cambios
```

Si sí se modificó, el mismo comando te dirá que subas. En ningún caso tienes que comparar hashes a ojo: el script emite el veredicto y el comando exacto.

**Escape obligatorio.** Si la máquina que tiene la descarga abierta no está disponible (disco formateado, fichero borrado, portátil roto), el asiento no puede cuadrarse nunca y el sistema quedaría bloqueado para siempre. Debe existir un cierre forzado desde la web, que exige un motivo escrito y queda registrado con `cerrada_por = forzado`. No es un atajo: es la válvula que evita que un sistema estricto se convierta en una trampa.

Implementado (fase 5) en `PiezaSyncService`: `entregarSesion`/`entregarVersion` abren el asiento, `recibir` lo cuadra y lo cierra, `cerrarSinCambios` y `forzarCierre` son las otras dos salidas. Verificado con las dos máquinas contra la API real.

**Tres cadenas se aceptan, no una.** Además del caso normal (asiento abierto + `hash_padre` = `hash_entregado`), hay dos encadenamientos igual de verificables que si no se admitieran obligarían a bajar un fichero que ya tienes delante:

1. **Rama estrenada**: no hay `hash_padre` porque no había nada que descargar. Solo se acepta si la rama no tiene ninguna sesión subida.
2. **Segunda subida de la misma sesión**: el `hash_padre` es tu propia subida anterior. El asiento ya se cerró con la primera.
3. **Primera sesión tras promocionar**: el `hash_padre` es el hash de la versión que abrió la rama. En cuanto la rama tiene una subida, esta puerta se cierra sola — por eso no permite pisar trabajo de la otra máquina.

### 4.5 Identidad de máquina

La identidad la declara **el cliente, no el navegador**. El `User-Agent` solo revela el sistema operativo, no el equipo: dos Macs serían indistinguibles, y la web puede consultarse desde el móvil, donde no hay ningún fichero. Quien toca el disco es el script, así que es el script quien tiene que identificarse.

**Alta automática.** En el primer arranque el cliente genera un **UUID v4** y lo guarda en `~/.trackbitos/config.json`. Lo registra enviando además hostname y sistema operativo, que sirven solo para proponer un nombre por defecto:

```
POST /maquina/registrar
{ "uuid": "…", "hostname": "MacBook-de-Jesus", "so": "macOS 15.2" }
```

El servidor da de alta la máquina si el UUID es nuevo, o actualiza `ultima_vez` si ya existe. El nombre es editable en la web para que quede legible (`MacBook`, `Sobremesa`). Toda petición posterior va firmada con el UUID.

Un equipo nuevo aparece solo con ejecutar el script: cero mantenimiento de listas. Si se reinstala el sistema, el UUID cambia y se registra como máquina distinta — que es lo correcto, porque el disco anterior ya no existe y sus descargas abiertas necesitan cierre forzado igualmente.

**Uso del navegador:** solo para redactar mejor los avisos, nunca para decidir. Si el sistema operativo del navegador no coincide con el de la máquina que tiene una descarga abierta, el mensaje puede afinarse:

> ⚠️ Descarga sin cerrar en **MacBook** — estás en Windows, revísala allí

Es presentación, no lógica.

Implementado (fase 4): `POST /maquina/registrar` vía `PiezaMaquinaModel::registrar()`, alta automática de UUID en `piezas-cli/trackbitos.py::cargar_config()`. El renombrado en la web llegó en la fase 13: `/piezas/maquinas` (`Web::maquinas()`), con `PiezaService::renombrarMaquina()`.

---

## 5. Cliente de línea de comandos

Multiplataforma (macOS y Windows). **Python 3** con dependencias mínimas, ejecutable con un solo comando. Vive fuera de este repo, en cualquier carpeta del equipo — no depende de estar dentro de Trackbitos.

Configuración en `~/.trackbitos/config.json`: URL base, token de API, **UUID de máquina** (generado en el primer arranque), directorio de trabajo, directorio de papelera. El nombre visible de la máquina lo guarda el servidor, no el cliente.

### 5.1 Comandos

```
trackbitos estado      (e)      # el más usado — diagnóstico completo       ✅ hecho
trackbitos bajar       (b, a)   [<variante>]  # descarga y abre sesión; si la pieza está
                                 # recién estrenada (nada que descargar), solo abre sesión ✅ hecho
trackbitos ver         (v)      <variante>  # descarga de consulta, sin abrir sesión ✅ hecho
trackbitos subir       (s)      [--log "..."]                               ✅ hecho
trackbitos cerrar      (c)                                                  ✅ hecho
trackbitos cerrar --sin-cambios   # cierra la descarga sin subir fichero    ✅ hecho
trackbitos promocionar (p)      --cambio "..." [--medidas "..."]            ✅ hecho
trackbitos papelera    (pa)     # qué hay apartado y cuándo caduca          ✅ hecho
trackbitos catalogo    (ca)     # catálogo completo, agrupado por categoría ✅ hecho
trackbitos variantes   (va)     <pieza>  # cuántas variantes tiene una pieza y cómo se llaman ✅ hecho
trackbitos actualizar  (ac)     # comprueba y aplica una versión nueva del cliente ✅ hecho
```

Alias cortos entre paréntesis (`trackbitos b "perro"` = `trackbitos bajar "perro"`): una letra
para los de uso diario, dos para el resto, elegidos para no chocar entre sí. El nombre completo
sigue funcionando igual — es azúcar, no un modo nuevo. `bajar` tiene dos alias (`b` y `a`) porque
hasta la fase 22 `a` era de un comando aparte, `abrir` — ver esa fase.

La papelera local se purga sola a los 30 días, aprovechando cualquier ejecución del script:
son dos máquinas de escritorio que se encienden a ratos, y un cron en cada una sería una pieza
más que mantener para algo que cuesta un listado de directorio. En el servidor sí hay comando
para cron: `php spark piezas:purgar`.

Todos aceptan `--dir` (por defecto, el directorio actual). `bajar`/`ver` aceptan
`--ignorar-pendiente` para el aviso de copia viva en la otra máquina.

El cierre forzado **no tiene comando**: es de uso web a propósito (spec 4.4), para
que no se convierta en el atajo de cada noche.

### 5.2 Requisito clave: el script razona, no el usuario

`trackbitos estado` debe imprimir un veredicto en lenguaje natural, no hashes crudos. El usuario no debe tener que interpretar nada ni encadenar comandos.

```
$ trackbitos estado
torso-recto · rama desde-v002 · sesión 8

  local  = origen   ✓ sin cambios
  origen ≠ nube     ⚠ hay sesión 9 subida desde Windows

  → Descarga la sesión 9. Es seguro.
```

```
$ trackbitos estado
torso-recto · rama desde-v002 · sesión 8

  local  ≠ origen   ⚠ tienes cambios sin subir
  origen = nube     ✓ la nube no ha avanzado

  → Ejecuta: trackbitos subir
```

### 5.3 Comportamiento defensivo

- `bajar` **se niega** (código de salida distinto de cero, sin preguntar) si `local ≠ origen`. Sugiere `subir` primero.
- Ningún borrado directo: mover a `~/.trackbitos/papelera/` con marca de tiempo. Purga a los 30 días.
- Antes de cualquier escritura, verificar el hash de lo descargado contra el que declara el servidor. Si no coincide, abortar.

---

## 6. API

Endpoints REST bajo `/piezas/api/`, autenticados por token Bearer único compartido en cabecera (mono-usuario — ver `App\Filters\PiezasApiAuth`).

Toda escritura declara además la máquina en la cabecera **`X-Maquina-Uuid`** (spec 4.5). Un UUID desconocido se rechaza con 404 en vez de darse de alta solo: el alta es explícita, vía `/maquina/registrar`, para que el registro de máquinas no se llene de fantasmas a mitad de una subida.

```
POST /maquina/registrar                  { uuid, hostname, so } → alta o ping        ✅ hecho
GET  /cliente/version                    versión del trackbitos.py desplegado         ✅ hecho
GET  /cliente/descargar                  el propio fichero, para "trackbitos actualizar" ✅ hecho
GET  /variantes                          lista con estado resumido + categoría          ✅ hecho
GET  /variante/{id}/estado               rama abierta, última sesión, hash nube,      ✅ hecho
                                         origen de descarga, bloqueo y pendientes
POST /variante/{id}/sesion/abrir         409 si ya hay sesión abierta                 ✅ hecho
GET  /sesion/{id}/descargar              devuelve el .blend + asiento en cabeceras;   ✅ hecho
                                         ?motivo=trabajo|consulta [&ignorar_pendiente=1]
GET  /version/{id}/descargar             igual, partiendo de una versión promocionada ✅ hecho
POST /sesion/{id}/subir                  multipart + hash_padre; 409 si no cuadra     ✅ hecho
POST /descarga/{id}/cerrar-sin-cambios   { hash_local } → debe igualar hash_entregado ✅ hecho
POST /descarga/{id}/forzar-cierre        { motivo } → solo desde la web               ✅ hecho
POST /sesion/{id}/cerrar                                                              ✅ hecho
POST /variante/{id}/promocionar          { cambio, medidas? } → crea versión, cierra rama  ✅ hecho
POST /version/{id}/impresa               { params_impresion }                         ✅ hecho
POST /version/{id}/validar               { resultado } → degrada la anterior          ✅ hecho
POST /version/{id}/descartar             { resultado } → motivo obligatorio           ✅ hecho
POST /version/{id}/devolver-a-trabajo    abre rama nueva                              ✅ hecho
POST /variante/derivar                   { origen_version_id, nombre }                ✅ hecho
```

`GET /version/{id}/descargar` no estaba en el diseño original y hizo falta: al promocionar, la rama nueva nace sin ninguna sesión subida, así que sin este endpoint el ciclo normal (promocionar → seguir trabajando) no tendría de dónde bajar el fichero.

El servidor **recalcula el hash** de todo fichero recibido y rechaza la subida (422) si no coincide con el declarado. Es una comprobación distinta e independiente de la del `hash_padre` (409): una responde "¿me ha llegado entero lo que dices?", la otra "¿parte de la copia que yo te entregué?".

Las cabeceras del asiento que devuelve una descarga: `X-Hash-Blend`, `X-Descarga-Id`, `X-Variante-Id`, `X-Variante-Nombre`, `X-Familia-Nombre`, `X-Rama-Id`, `X-Rama-Nombre`, `X-Sesion-Id`, `X-Sesion-Numero`, `X-Origen-Tipo`, `X-Origen-Numero`. El cliente las vuelca en su `.sesion.json`.

Códigos de error: 404 no encontrado, 403 máquina equivocada, 409 el asiento no cuadra (o el verbo no aplica en ese estado), 422 el fichero o los datos no son lo que dicen ser. Que el sistema se niegue no es un 500: es su trabajo.

Nota: los verbos de versión son llamadas directas a `PiezaService` (fase 2) — ahí la fase 5 solo expone por HTTP, no reinventa lógica. Lo que sí es nuevo es todo el manejo de ficheros y el cuadre de asientos.

---

## 7. Interfaz web

Sobria y orientada al estado. Lo que debe responder de un vistazo: **cuál es la buena y dónde está el trabajo en curso.**

Implementada (fase 6) en `app/Controllers/Piezas/Web.php` y `app/Views/piezas/`. Índice de piezas → variantes, ficha de variante, y el alta de piezas/variantes (sin ella el módulo no arrancaba desde cero). El índice pasó a ser un listado agrupado por categorías en la fase 13.

**La web no descarga ficheros de trabajo, y es deliberado.** La identidad de máquina la declara el script, nunca el navegador (4.5): un `.blend` de sesión bajado desde el móvil no tendría disco que registrar ni asiento que cuadrar. Así que para el trabajo en curso la ficha muestra el hash de la nube y el comando exacto a ejecutar, y quien toca esos ficheros sigue siendo `trackbitos.py`. La excepción del `.blend` de una versión ya cerrada está en la fase 11.

**Lo que sí es exclusivo de la web:** el cierre forzado de una descarga (4.4). No existe como comando del cliente a propósito — es la válvula de escape, no el atajo de cada noche.

### 7.1 Ficha de variante

- Cabecera con la versión `validada` destacada.
- Aviso de bloqueo si hay sesión abierta:
  > ⚠️ Sesión abierta en MacBook desde hace 2 días
- Aviso de descargas pendientes, visible siempre que las haya:
  > 📥 Última descarga: **MacBook** · sesión 8 · 12/08 19:20 · sin subir

  Debe leerse antes que el botón de descarga, no debajo. Es el aviso que evita
  bajar desde el sobremesa y machacar trabajo que quedó en el portátil.
- Historial de versiones en orden inverso, con estado, `cambio` y `resultado`.
- Botones = los verbos de la sección 3. Deshabilitados con explicación cuando no aplican (no ocultos: el usuario debe entender por qué no puede).

  Dos detalles que se descubrieron al verlo en pantalla, y que la implementación ya respeta:
  la explicación va como **texto visible**, no como `title` — un botón deshabilitado no recibe
  eventos de ratón, así que su tooltip no llega a mostrarse nunca; y un botón deshabilitado se
  pinta **gris**, no en su color vivo atenuado, porque sobre fondo oscuro un `btn-outline-info`
  al 65% de opacidad sigue leyéndose como disponible.

### 7.2 Avisos

- Al descargar una mesa de trabajo, mostrar el hash de la nube para que el cliente pueda contrastar.
- Si una versión lleva más de N días en `borrador` o `impresa` sin resolverse, marcarla visualmente como pendiente de juicio.

### 7.3 Feedback de la promoción

Promocionar es el momento que cierra un ciclo de trabajo. La confirmación debe ser explícita y satisfactoria: número de versión asignado, fecha, y la rama nueva ya abierta. Es el punto donde el usuario ve una victoria cerrada.

### 7.4 Referencias y renders (fase 8)

Dos galerías de imágenes, cada una en el nivel de la jerarquía que le corresponde (spec 1.1):

- **Referencias**, en el índice (`/piezas`), dentro de la tarjeta de cada familia. Fotos del
  original con medidas de calibre — comunes a todas las variantes, así que no se duplican por
  variante. Con notas de texto libre (la medida, el ángulo).
- **Renders**, en la ficha de variante, dentro de cada tarjeta de versión del historial. El
  resultado visual de esa iteración concreta, así que cuelga de la versión: permite ver la
  evolución del modelo a lo largo del historial en vez de una sola galería suelta.

Ambas son subida real (JPEG/PNG/WEBP, máximo 20 MB), no solo enlace — a diferencia del `.blend`,
mirar una foto no exige identidad de máquina, así que la sube y la sirve la propia web
(`Web::subirReferencia`/`subirRender`, `Web::imagenReferencia`/`imagenRender`), con el filtro
`auth` de sesión de navegador. Borrar aparta el fichero a la papelera (invariante 6 en espíritu)
pero sí quita el registro: a diferencia de una sesión o una versión, una foto de más no es parte
del histórico de trabajo que hay que conservar.

---

## 8. Almacenamiento

- Los `.blend` y `.stl` se guardan en el servidor, fuera del directorio público, servidos solo vía API autenticada.
- Rutas por variante y versión, con nombres derivados de los IDs (no de texto libre del usuario).

Disposición real (`PiezaAlmacen`, bajo `writable/piezas/`):

```
variante-3/rama-5/sesion-41.blend                      ← trabajo en curso, purgable (invariante 5)
variante-3/version-v002.blend                          ← copia propia de la versión, nunca se purga
familia-2/referencia-7.jpg                              ← foto de referencia (fase 8)
variante-3/version-9/render-4.jpg                       ← render de una versión concreta (fase 8)
papelera/20260816-154424-variante-3-rama-5-sesion-41.blend   ← apartado al validar, caduca a los 30 días
```

La versión se lleva **su propia copia** aunque el contenido sea idéntico al de la sesión que promocionó. Si apuntase al fichero de la sesión, la purga de la fase 7 se llevaría por delante justo el fichero que nunca debe perderse.

- Incluir el directorio de subidas en el backup existente a Backblaze B2.
- **Fuera de este sistema todavía**: ficheros `.ctb` de placas laminadas. Las fotos de referencia y las imágenes de render sí se guardan aquí desde la fase 8 (decisión revisada: el coste de una foto de móvil es bajo y merece la pena tenerlas a mano en la ficha).

---

## 9. Fuera de alcance

- Integración con Git.
- Sincronización automática en segundo plano. La sincronización es explícita y bajo demanda.
- Fusión de ficheros `.blend`. Ante divergencia, el usuario elige; nunca se fusiona.
- Multiusuario. Es un sistema de un solo usuario con varias máquinas.

---

## 10. Orden de implementación

Cada fase debe quedar funcionando y verificable por sí sola antes de pasar a la siguiente.

1. **Migraciones y modelos** de las seis tablas (`maquina` incluida), con los invariantes 1-4 aplicados en los modelos y probados con datos de prueba. ✅
2. **Lógica de verbos** como servicio (`PiezaService`), sin interfaz. Probar por consola que promocionar cierra la rama y abre la siguiente, y que validar degrada la anterior. ✅
3. **`trackbitos estado`** en el cliente, con el hash de la nube leído de un fichero local en lugar de la API. Verificar las cuatro filas de la tabla 4.3 creando ficheros a mano. ✅
4. **API** de lectura (`/variantes`, `/variante/{id}/estado`) y conectar el cliente contra ella. ✅
5. **API de subida y descarga**, con verificación de hash en ambos extremos. ✅
6. **Interfaz web**: ficha de variante y botones de los verbos. ✅
7. **Papelera y purga** de sesiones al validar. ✅

La fase 3 es la que concentraba el riesgo del diseño — quedó probada a fondo (incluido un bug real de codificación UTF-8 en Windows) antes de construir nada encima.

---

## 11. Pendiente

### 11.1 Meter las piezas que ya existen fuera del sistema

**Decidido (2026-08-17): se suben a mano por la web, sin importador.** Se diseñó uno (escaneo a
CSV revisable + comando `importar` en el CLI, con cuatro endpoints nuevos de API: crear pieza,
crear variante, listar categorías y adjuntar STL) y se descartó por desproporcionado para 27
ficheros que se meten una sola vez. Si algún día vuelve a hacer falta, el diseño está aquí
descrito y los verbos que necesitaba ya existen en `PiezaService`; lo único que falta es la capa
de API y el comando.

**Inventario real** (27 ficheros → 24 piezas, en cuatro categorías que son sus carpetas):

- **Objetos**: Lupa, Micrófono, Rastrillo, Zoleta, Libro, Flores, Mojón, Mini playmobil, Copa,
  **Pistola** (`pequeña`/`grande`) y **Silla** (dos variantes).
- **Cuerpo**: Brazo integral, Brazo, Mano, Brazo y mano, Estructura interior, Pelo, Torso,
  Completo, **Cabeza** (`normal`/`calva`) y **Piernas** (`rectas`).
- **Otros**: Junta pistola. · **Pruebas**: Números, Modelo conos.

Tres cosas que conviene tener presentes al meterlas:

1. **La unidad es el fichero, no la pieza física.** «Brazo y mano en el mismo `.blend`» no puede
   colgar a la vez de *Brazo* y de *Mano* — serían dos copias vivas del mismo fichero, justo lo
   que el módulo existe para impedir. Entra como una pieza propia.
2. **`Completo` es un ensamblaje y aquí no hay ensamblajes.** Entra como una pieza más, sin
   relación con torso/pelo/piernas: si se promociona un torso nuevo, *Completo* no se entera. Se
   convive anotando en su `cambio` de qué versiones parte, pero es disciplina, no una
   comprobación del sistema.
3. **Las de `Pruebas` no se validan nunca** (son calibraciones), así que se quedarán siempre en
   *sin validar* en el listado. Es esperado, no un olvido.

**Regla de partida al meterlas: no se reconstruye el pasado.** Cada pieza entra como **v001** con
un `cambio` del tipo "importada del trabajo anterior a Trackbitos". Las iteraciones hechas fuera
no tienen hashes, ni sesiones, ni de qué copia partía cada una — inventarles un historial haría el
registro menos fiable, no más. El valor empieza en el siguiente toque de cada pieza.

El ciclo a mano por pieza: alta por web (nombre, categoría, SKU) → `trackbitos bajar <pieza>` →
copiar el `.blend` → `subir` → `cerrar` → `promocionar --cambio "importada…"` → y si ya está
impresa y es buena, marcarla impresa y validarla desde la ficha, más adjuntar el `.stl` ahí mismo.
Los `.stl` ya están exportados junto a los `.blend`, así que se pueden subir en la misma pasada.

### 11.2 Cabos sueltos menores

- **Despliegue**: cron diario de `php spark piezas:purgar` e incluir `writable/piezas/` en el
  backup a Backblaze B2 (sección 8). Sigue sin hacerse.
- **La primera pieza real** (`Pincel de pintura`) tiene su variante llamada `estandar`, anterior
  a la variante `base` automática de la fase 12. Ya se puede renombrar desde la ficha (fase 13)
  si molesta; funciona igual como está.
- **`piezas-cli/__pycache__/`** está versionado en git y aparece como modificado en cada
  ejecución del cliente. Debería ir al `.gitignore`.
