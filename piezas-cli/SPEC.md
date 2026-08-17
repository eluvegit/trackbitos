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

**Fase 8 (añadida tras el cierre, 2026-08-17): imágenes.** `trackbitos variantes` en el cliente
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
  ruta_blend, hash_blend, ruta_stl (nullable), hash_stl (nullable),
  cambio,              -- obligatorio, una línea: qué se modificó
  medidas,             -- texto libre: cotas de calibre relevantes
  params_impresion,    -- exposición, altura de capa, capas base
  resultado,           -- rellenado tras imprimir
  UNIQUE (variante_id, numero)

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
4. **Las versiones son inmutables** en `ruta_blend`, `hash_blend`, `ruta_stl`, `hash_stl` y `numero`. Los campos de anotación (`resultado`, `medidas`) sí son editables.
5. **Las sesiones no se purgan hasta que la versión que las cerró pasa a `validada`.** No al promocionar: si la impresión sale mal, aún hacen falta.
6. **Nada se borra: se mueve a papelera.** Servidor y cliente. Purga automática a los 30 días.

**Qué significa "purgar una sesión" (fase 7).** Se aparta su `.blend`, no su registro. La fila conserva número, hashes, máquina, fecha y log, y solo se marca `purgada = 1`: lo que ocupa sitio es el fichero, y lo que da valor al historial dentro de tres meses es el registro. La versión validada tiene su propia copia del fichero, así que no se pierde nada recuperable — y durante los 30 días de gracia el `.blend` sigue en la papelera, con `ruta_blend` apuntando ahí por si hace falta rescatarlo a mano.
7. `version.cambio` es obligatorio y no puede quedar vacío. Es el campo que da valor al historial dentro de tres meses.
8. **Toda descarga se cierra**, por subida, por declaración de "sin cambios" o por cierre forzado con motivo. Una subida solo se acepta si su `hash_padre` coincide con el `hash_entregado` de una descarga abierta de esa misma máquina.

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
| **Derivar variante** | Crea variante nueva con `origen_version_id` apuntando a la versión de partida. Numeración propia desde v001. No copia ficheros ni referencias. |

Implementados en `PiezaService` (fase 2) como: `crearFamilia`, `crearVariante`, `abrirSesion`, `subirSesion`, `cerrarSesion`, `promocionar`, `devolverATrabajo`, `marcarImpresa`, `validar`, `descartar`, `derivarVariante`.

**Corrección de "devolver a trabajo" (descubierta en la fase 6).** Tal como estaba, el verbo no podía ejecutarse nunca: siempre hay una rama abierta (promocionar cierra una y abre otra), y el invariante 2 no admite una segunda. Retomar una versión antigua implica por fuerza abandonar la línea en curso, así que el verbo ahora cierra la rama abierta —sin versión que la cierre, porque no se promociona— y abre la nueva. Al ser destructivo, por defecto **se niega y explica** cuántas sesiones subidas quedarían sin promocionar; solo procede con confirmación explícita (`abandonar_rama`), que en la web es una casilla que nombra la consecuencia. Las sesiones no se pierden: quedan colgando de la rama cerrada.

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
trackbitos estado          # el más usado — diagnóstico completo          ✅ hecho
trackbitos abrir <variante>        # sesión sin descargar: variante estrenada  ✅ hecho
trackbitos bajar [<variante>]                                             ✅ hecho
trackbitos ver <variante>          # descarga de consulta, sin abrir sesión  ✅ hecho
trackbitos subir [--log "..."]                                            ✅ hecho
trackbitos cerrar                                                         ✅ hecho
trackbitos cerrar --sin-cambios   # cierra la descarga sin subir fichero  ✅ hecho
trackbitos promocionar --cambio "..." [--medidas "..."]                   ✅ hecho
trackbitos papelera                # qué hay apartado y cuándo caduca    ✅ hecho
trackbitos variantes               # catálogo completo, agrupado por familia ✅ hecho
```

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
GET  /variantes                          lista con estado resumido                    ✅ hecho
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

Las cabeceras del asiento que devuelve una descarga: `X-Hash-Blend`, `X-Descarga-Id`, `X-Variante-Id`, `X-Variante-Nombre`, `X-Rama-Id`, `X-Rama-Nombre`, `X-Sesion-Id`, `X-Sesion-Numero`, `X-Origen-Tipo`, `X-Origen-Numero`. El cliente las vuelca en su `.sesion.json`.

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
   *sin versión buena* en el listado. Es esperado, no un olvido.

**Regla de partida al meterlas: no se reconstruye el pasado.** Cada pieza entra como **v001** con
un `cambio` del tipo "importada del trabajo anterior a Trackbitos". Las iteraciones hechas fuera
no tienen hashes, ni sesiones, ni de qué copia partía cada una — inventarles un historial haría el
registro menos fiable, no más. El valor empieza en el siguiente toque de cada pieza.

El ciclo a mano por pieza: alta por web (nombre, categoría, SKU) → `trackbitos abrir <pieza>` →
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
