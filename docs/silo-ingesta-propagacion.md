# Silo — ingesta y propagación (pendiente de desarrollar)

Diseño acordado en conversación, 2026-09-03. Nada de esto está implementado todavía
más allá de lo que se indica en "Estado actual del código".

## Idea del sistema

Silo cataloga y respalda fotos/vídeos organizados en **carpetas** (cada carpeta = una
"pieza": un ID de negocio + fecha + clasificación semántica). El respaldo vive en
**unidades físicas** (discos, USB) organizadas en tres niveles:

- **Nivel 1 — Maestro**: uno o varios discos que son la fuente de verdad del contenido.
- **Nivel 2 — Año**: copia repartida por año.
- **Nivel 3 — Temática**: copia repartida por categoría.

El sistema son **dos procesos** — **ingesta** (Maestro → base de datos) y **propagación**
(base de datos → copias físicas de nivel 2 y 3) — y **la web es la coordinadora y la
presentación**: tiene el catálogo autoritativo, calcula el trabajo, muestra estado,
diffs y alertas, y pide aprobación humana. La web **no** es donde se introducen datos de
contenido; eso entra por ingesta.

### Reparto de responsabilidades

El acceso a disco lo hace un programa aparte, **el agente `.py`**, que hablamos por API
con la web. El `.py` es un **ejecutor**: no decide nada, pregunta a la web qué hacer, lo
hace y reporta.

Regla: **lo que toca bytes o disco va al `.py`** (tiene la CPU y el acceso al sistema de
ficheros); la web solo decide y presenta.

| Web (cerebro) | Agente `.py` (potencia + acceso a disco) |
|---|---|
| Catálogo autoritativo (MySQL) | Recorrer árboles (`os.scandir`), `stat`, hashing |
| Plan de propagación y análisis de cascada | Copiar / mover / borrar ficheros |
| Generar el manifiesto **esperado** de cada unidad | Escribir los ficheros de control en la raíz de cada unidad |
| Recibir el *delta* y calcular acciones | **Diff local**: baja el manifiesto esperado, lo compara con su escaneo, sube solo el delta |
| Cola de tareas, resúmenes, aprobación humana, alertas | Ejecutar la lista de acciones aprobada y reportar progreso |
| Generar el volcado de BD que se guarda en cada unidad | Copiar ese volcado a la raíz de las unidades tocadas |

El **diff local en el `.py`** es deliberado: menos datos por la API y usa su CPU. La web
solo recibe un changeset pequeño.

## Las tres fases

### Fase 0 — Alta de unidades físicas

Antes de nada, cada disco/USB se registra en `silo_unidades`: nivel, número de orden,
etiqueta, `identificacion_fisica` (nº de serie, etiqueta del volumen, marca/modelo,
color, dónde está guardado…), capacidad, y su fichero de control `.silo_unit.json` que
se copia en la raíz del disco para identificarlo y seguirlo.

"Mi PC" es la pantalla de este mapa: unidad definida → disco físico real. Solo interesa
**reconocer** cada unidad (etiqueta, capacidad, identificación física), **no** su
contenido — nada de "tiene N piezas".

### Fase 1 — Ingesta del Maestro

El `.py` escanea **solo el primer nivel** del root del Maestro (las carpetas-pieza
cuelgan directas de la raíz, no hay contenedores de año/temática por encima), calcula
`hash` + `tamaño` + `mtime` + **timestamp de captura** (EXIF `DateTimeOriginal` en
fotos, fecha del contenedor en vídeos) por fichero, y sube un lote por carpeta:
`{ nombre_carpeta, ficheros: [{ ruta_relativa, tamano, hash, mtime, capturado_en }] }`.

La web mete cada lote por `SiloIngestaService::ingestarCarpeta()` (ya existe): parsea el
nombre de carpeta → pieza + atributos + ubicación.

Resultado: el catálogo en BD refleja lo que hay en el Maestro.

#### Contrato de entrada — cómo es el Maestro

No hay modo de migración: el contenido entra **ya bien puesto**, el `.py` solo lee. En el
root del Maestro hay carpetas-pieza consecutivas ya organizadas; **lo demás puede ser
material no trackeable** y se ignora.

**Qué se ingesta**: una entrada del root es carpeta-pieza si su nombre casa el patrón
`<id> <YYYYMMDD|sinfecha> <categoría>[, elems…]` (primer token = ID de negocio, segundo =
fecha o `sinfecha`).

**El ID lo pone el usuario a mano** al crear la carpeta — formato `AAnnnn` (2 dígitos del
año del contenido + 4 correlativos que reinician cada año, ej. `260001`). El `.py` lo lee
del nombre tal cual (`SiloService::parsearNombreCarpeta()`): **no acuña IDs ni renombra
carpetas**.

- `silo_contador` pasa a ser **espejo**: en cada ingesta se ajusta al ID más alto visto
  por año, para que el alta manual de la web (que sí usa el contador) nunca reparta un
  número ya usado en disco.
- La ingesta **avisa si dos carpetas comparten ID** (va al informe de eventos).
- El ID en el nombre es el ancla carpeta↔pieza; mientras no se cambie, se puede editar el
  resto del nombre (reclasificar) sin que se cree una pieza duplicada.
- El correlativo por año da, de propina, **el orden de alta** visible en cualquier
  explorador (las carpetas de un año ordenan por el número).

**Qué se salta** (por cualquiera de estos motivos):

- **Prefijo de ignorar**: el nombre empieza por `_`, `.` o `~` (carpetas de trabajo,
  borradores, RAW sin editar; `.` ya es oculto).
- **Lista negra**: nombres exactos en el `config.json` del `.py` (`"Descartes"`,
  `"Sin revisar"`, …).
- **No parece pieza**: el nombre no casa el patrón (sin ID / sin token de fecha), o la
  entrada del root es un fichero suelto en vez de una carpeta.

**Nada silencioso**: el resultado de la ingesta trae un **informe de lo no trackeado**
(nombre + motivo: prefijo / lista negra / no-parece-pieza), y la web lo muestra como
referencia para que el usuario lo revise. No bloquea la ingesta, es informativo.

#### Contrato de entrada — cómo son las carpetas

- Dentro de una carpeta-pieza: **ficheros sueltos, sin subcarpetas**. Fotos y vídeos
  mezclados, con sus nombres de cámara.
- **Los vídeos llevan el prefijo `+`** en el nombre (`+MVI_0042.mp4`); las fotos no se
  tocan. `+` ordena antes que dígitos y letras, así que en cualquier visor externo
  (explorador, tele por USB) **los vídeos salen primero** y luego las fotos, cada grupo
  en orden de nombre de cámara (≈ orden de disparo). Es el objetivo real: que el Maestro
  se disfrute fuera de la web sin nada especial.
  - Si en una carpeta se mezclan varias cámaras/móviles, el nombre de cámara deja de ser
    cronológico → se antepone una marca de tiempo compacta tras el `+` /
    (`+20230715-141230 MVI_0042.mp4`). Con una sola fuente no hace falta.
- El `.py` quita el marcador `+` inicial para obtener el nombre real; el tipo (foto/vídeo)
  lo saca de la extensión igual.
- **Ediciones**: no hay convención de nombres para esto. Una foto editada convive con su
  original como dos ficheros normales; si algún día molesta, es un filtro del visor web,
  no una regla de disco.

#### Orden de visualización

- **Fuera de la web** (el fin último): orden alfabético del nombre — por eso el `+`.
- **En el visor web**: mismo criterio para ser consistente — **vídeos primero, luego
  fotos**; dentro de cada grupo, orden por `capturado_en`. Rejilla con carga perezosa,
  pantalla completa secuencial con flechas, vídeos reproducibles en línea, separadores
  por día si la carpeta abarca varios.

### Fase 2 — Propagación lógica (en BD)

Con el catálogo ya en BD, se calcula el reparto de cada "cubo" en unidades de nivel 2
(año) y nivel 3 (categoría en slug): qué pieza va a qué unidad física, creando unidades
nuevas cuando no caben por capacidad. Todo esto primero **en la base de datos**, sin
tocar discos. Lo hace `SiloPropagacionService` (ya existe una primera versión).

### Fase 3 — Propagación física

La web pide **conectar cada unidad estipulada** y, unidad a unidad, el `.py` vuelca a
disco lo que la BD dice que le toca. Al terminar con una unidad, el `.py` reescribe sus
ficheros de control y su volcado de BD.

## Detección de cambios

Objetivo: al conectar una unidad, verificar rápido si su contenido coincide con lo que
la BD espera, y detectar drift. La clave para que sea barato: **nunca abrir un fichero
salvo que haya sospecha concreta.** Cuatro niveles, de instantáneo a caro:

### N0 — Identidad (instantáneo)

El `.py` lee `.silo_unit.json` de la raíz y manda `unidad_id` + `hash_indice` +
`bd_version`. La web compara `hash_indice` con el que tiene guardado para esa unidad:

- **igual** → la unidad está tal como la BD la dejó. Fin (salvo verificación periódica).
- **distinto** → o la BD ha avanzado (toca propagar *hacia* esta unidad) o la unidad
  cambió por fuera (toca reconciliar).

### N1 — Manifiesto por `stat`, sin leer contenido (segundos)

Se guarda por unidad un manifiesto `{ ruta_relativa, tamano, mtime, hash }` de cada
fichero que **debería** estar (en BD y en `.silo_manifest.json` en el disco). El `.py`
hace un `os.scandir` recursivo recogiendo solo `(ruta, tamano, mtime)` — **cero
lecturas** — y compara:

- ruta que falta / ruta nueva / **tamaño distinto** → cambio confirmado
- mismo tamaño, **mtime distinto** → *candidato* (mtime no es de fiar), pasa a N2

Un árbol de decenas de miles de ficheros se recorre en segundos porque nunca se abre
nada.

### N2 — Hash dirigido (solo candidatos)

Se hashea **únicamente** la lista de candidatos de N1. Confirma si el contenido cambió
de verdad.

### N3 — Re-hash completo (raro, acción explícita "verificación profunda")

Todo el árbol. Bajo demanda o con cadencia larga (mes / trimestre) como seguro contra
manipulación externa y **bit rot** — importante en un sistema de backup.

### Rollup `hash_indice`

`hash_indice` = hash de la lista ordenada `ruta_relativa:tamano:hash_contenido`. Se
recalcula desde el manifiesto **sin tocar disco**. En el caso normal ("nada ha
cambiado"), tras el `stat`-walk el `.py` reconstruye el rollup y confirma que sigue
igual al de la BD: **una sola comparación** dice "esta unidad diverge" sin diffear nada.

Por qué funciona barato: como el `.py` es **el único que escribe**, tras cada volcado
actualiza manifiesto + rollup de forma atómica. En operación normal N1 no encuentra
candidatos y N2/N3 no se ejecutan nunca. N2/N3 existen solo para interferencia externa y
corrupción silenciosa.

## Proxies / capturas para visualizar carpetas

`silo_proxies` ya existe: hasta **3 fotos + 3 vídeos por carpeta**, para hacerse una idea
de qué hay dentro sin abrir el original. Hoy están simulados (URL de placeholder); los
reales los genera el agente `.py`.

Decisiones acordadas:

- **Quién y cuándo**: el `.py` los genera durante la ingesta del Maestro, y los
  **regenera** cuando cambian los ficheros de una pieza (lo detecta el diff de
  manifiesto). El `.py` tiene los ficheros y la CPU — es el único sitio donde se pueden
  hacer.
- **Selección**: hasta 3 fotos y 3 vídeos, **repartidos a lo largo de la línea de tiempo
  de la carpeta** (`capturado_en`: p.ej. primer / medio / último tercio) para que las 3
  den idea del conjunto. Elección con **semilla estable** (derivada de `pieza_id` o del
  `hash_indice` de la carpeta) para que no bailen en cada reescaneo.
- **Formato**: foto → redimensionada (lado largo ~800 px, WebP/JPEG). Vídeo → un frame
  póster + opcionalmente un WebP animado corto y mudo. Tamaños/códecs exactos, más
  adelante.
- **Dónde viven**: son **derivados regenerables**, no van en el disco de la unidad como
  dato de backup. Se sirven desde el servidor web como assets
  (`public/assets/silo/proxies/<pieza_id>/<orden>.webp`), y `silo_proxies.url` apunta
  ahí.
- **Transporte**: el `.py` sube el binario por la API
  (`POST /api/silo/agente/piezas/{id}/proxies`, multipart); la web lo coloca en assets y
  crea/actualiza las filas de `silo_proxies`.

Pendiente de decidir: ¿guardar también una copia de los proxies en el Maestro
(`.silo_proxies/` junto a cada carpeta) para poder reconstruir la parte visual de la web
sin re-escanear todo el disco, o aceptar que reconstruir la web = re-generar proxies?

Esto **no bloquea** el núcleo (esquema, cola, detección de cambios): es un hito posterior
de la Fase 1.

## Réplica de la base de datos en cada unidad

Cada unidad que se toca guarda en su raíz una **réplica del catálogo** (que es el
cerebro). Es una **red de seguridad**: el sistema no depende de ella, sirve para
reestablecer si se cae el servidor.

**Decisión de formato: `mysqldump` comprimido — `.catalogo.sql.gz`.** Más un
`.catalogo.meta.json` pequeño al lado con `{ hash_indice_global, timestamp,
version_esquema (última migración), unidad_origen }`.

Motivo: al ser una red de seguridad que nadie consulta en vivo, se optimiza para
- **escribir rápido**: un dump por streaming, gzip al vuelo;
- **almacenar simple**: un solo blob que el `.py` solo tiene que copiar;
- **reestablecer fiable**: es el esquema exacto, vuelve a MySQL (lo que corre el
  servidor) con un comando y **sin conversor que mantener** ni riesgo de desajuste de
  esquema.

Se descartó SQLite: su única ventaja sería que el `.py` lo consultara offline, pero el
`.py` hace su diff contra el **manifiesto** que baja de la web, no contra el catálogo
entero — así que SQLite no aporta nada aquí y costaría un conversor MySQL→SQLite que hay
que actualizar con cada migración.

El volcado es **solo metadatos** (piezas, ficheros, hashes, ubicaciones, vocabulario,
unidades) — sin binarios —, así que pesa poco aunque haya cientos de miles de ficheros.

Se escribe **una vez por sesión de propagación en cada unidad tocada** (al final, no por
cada operación de fichero).

Restaurar: `php spark silo:restaurar <ruta_al_.sql.gz>` → `gunzip | mysql`. Si al
conectar una unidad su `.catalogo.meta.json` dice una versión **más nueva** que la BD
viva, es una alerta fuerte (el cerebro se perdió o se revirtió) → se ofrece restaurar
desde esa unidad.

## Flujo de reorganización (máquina de estados)

Los cambios en la organización pueden decidirse **a discreción** (el usuario mueve
piezas entre unidades en la web) o **surgir de las unidades** (un escaneo detecta drift).

```
detectado         → la web registra el cambio (silo_eventos)
resumen           → la web calcula y muestra: qué ficheros mover/copiar/borrar,
                    en qué unidades, y qué OTRAS unidades quedan afectadas en cascada
esperando_conexión→ pide conectar las unidades necesarias
aprobación_humana → el usuario ve el resumen de acciones y aprueba
ejecutando        → la web pasa la lista de acciones al .py, que ejecuta y reporta progreso
hecho             → el .py recalcula hash_indice, reescribe .silo_unit.json +
                    .silo_manifest.json + .catalogo.sql.gz; si quedan unidades en
                    cascada, vuelven a esperando_conexión
```

## Varias unidades conectadas a la vez

Soportado.

- La cola de tareas se indexa por `unidad_id` (o `unidades_requeridas[]` para tareas
  multi-unidad, ej. un move A→B).
- El `.py` levanta **un worker por unidad montada** y tira de las tareas de esa unidad.
- La web **serializa solo lo que colisiona** (misma unidad, o conjuntos de unidades que
  se solapan); unidades independientes van en paralelo.
- Una tarea multi-unidad no se vuelve ejecutable hasta que **todas** sus unidades están
  presentes.

## Ficheros de control en la raíz de cada unidad

Todos ocultos (con punto delante) — molestan visualmente si no.

- `.silo_unit.json` — identidad de la unidad (`unidad_id`, nivel, número), `hash_indice`,
  `bd_version`, `ultima_sincronizacion`.
- `.silo_manifest.json` — manifiesto por `stat`: `{ ruta_relativa, tamano, mtime, hash }`
  de cada fichero que debería estar.
- `.catalogo.sql.gz` + `.catalogo.meta.json` — réplica del catálogo y su sello de versión.

## API web ↔ agente `.py` (esbozo)

- `POST /api/silo/agente/handshake` — el `.py` anuncia qué unidades hay montadas ahora
  (mount point + contenido de su `.silo_unit.json`). La web responde con las tareas
  pendientes para esas unidades.
- `GET  /api/silo/agente/tareas` — sondeo de la cola.
- `GET  /api/silo/agente/unidades/{id}/manifiesto` — manifiesto esperado, para el diff
  local.
- `POST /api/silo/agente/tareas/{id}/resultado` — delta de manifiesto, hashes, progreso,
  fin, error.
- `POST /api/silo/agente/piezas/{id}/proxies` — sube (multipart) los proxies generados;
  la web los coloca en assets y actualiza `silo_proxies`.
- Auth: token estático en el `config.json` del `.py` (single-user, sobra).

Modelo de ejecución: el `.py` se lanza cuando el usuario conecta discos (o corre como
demonio de bandeja), hace `handshake`, recibe trabajo, lo ejecuta y reporta. Sin
websockets.

## Añadidos al esquema

- `silo_unidades`: `hash_indice` (promover de `.silo_unit.json` a columna),
  `bd_version_en_disco`, `ultima_verificacion` (además de `ultima_sincronizacion`).
- `silo_ficheros`: `capturado_en` (timestamp de captura EXIF/contenedor, para ordenar la
  visualización), `es_video` derivado o `orden_grupo` (`+` → vídeo primero).
- Manifiesto: ampliar `silo_ficheros` con `mtime` (y `ruta_relativa` por unidad si no
  está), o una tabla `silo_manifiesto (unidad_id, ruta_relativa, tamano, mtime, hash)`.
- `silo_tareas`: cola del `.py` — `tipo`
  (`ingesta_maestro` / `escaneo_rapido` / `verificar_hashes` / `propagar` / `volcar_bd` /
  `generar_proxies` / `restaurar`), `payload`, `unidades_requeridas`, `estado`,
  `aprobada`, `resultado`, `error`, timestamps.
- `silo_eventos`: log para el panel de alertas — drifts detectados y su resolución, el
  informe de carpetas no trackeadas de cada ingesta (nombre + motivo), y los IDs
  duplicados encontrados.
- `silo_contador`: sin cambios de esquema, pero cambia de rol — ahora es **espejo** del
  ID más alto por año que hay en disco (lo ajusta la ingesta), no la única fuente.

## Estado actual del código

- `SiloService::crearUnidad()` genera el `.silo_unit.json` con `hash_indice` /
  `ultima_sincronizacion` a `null`.
- `SiloIngestaService::ingestarCarpeta()` + `php spark silo:simular-ingesta` — ingesta
  **simulada**: recibe una lista de ficheros ya montada, no escanea disco real.
- `SiloPropagacionService::propagarTodo()` / `propagarPieza()` + `php spark silo:propagar`
  — propagación **lógica** en BD (Fase 2). No hay Fase 3.
- `Silo\Web` + vistas — coordinación y presentación: Mi PC, Unidades, listado/galería de
  carpetas, alta y reclasificación de piezas.
- `silo_proxies` existe y la web ya pinta los proxies (galería y `show`), pero se
  insertan **simulados** (URL de placeholder) desde `SiloIngestaService`.
- **No existe todavía**: el agente `.py`, la API del agente, el escaneo real de disco, la
  detección de cambios (N0–N3), la generación real de proxies, la réplica de BD en disco
  y su restauración, la cola `silo_tareas` y el panel de alertas.

## Cosas que NO son features (no reintroducir)

- **Sellar / precintar unidades**: que un disco "no vuelva a escribirse" es criterio
  humano sobre el backup, no un estado del sistema. Se borró el 2026-09-03.
- **"Año abierto / año cerrado"**: es una forma de razonar sobre la cadencia de
  verificación, no un campo ni un filtro.
