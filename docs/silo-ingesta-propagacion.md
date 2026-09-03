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

El `.py` recorre el/los disco(s) Maestro, calcula `hash` + `tamaño` + `mtime` por
fichero, **agrupa por carpeta de primer nivel** (= la carpeta/pieza) y sube lotes
`{ nombre_carpeta, ficheros: [{ ruta_relativa, tamano, hash, mtime }] }`.

La web mete cada lote por `SiloIngestaService::ingestarCarpeta()` (ya existe): parsea el
nombre de carpeta → pieza + atributos + ubicación. Agrupar por carpeta es trivial para
el `.py` (primer segmento de la ruta) y ahorra a la web tragarse una lista plana gigante.

Resultado: el catálogo en BD refleja lo que hay en el Maestro.

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
- Auth: token estático en el `config.json` del `.py` (single-user, sobra).

Modelo de ejecución: el `.py` se lanza cuando el usuario conecta discos (o corre como
demonio de bandeja), hace `handshake`, recibe trabajo, lo ejecuta y reporta. Sin
websockets.

## Añadidos al esquema

- `silo_unidades`: `hash_indice` (promover de `.silo_unit.json` a columna),
  `bd_version_en_disco`, `ultima_verificacion` (además de `ultima_sincronizacion`).
- Manifiesto: ampliar `silo_ficheros` con `mtime` (y `ruta_relativa` por unidad si no
  está), o una tabla `silo_manifiesto (unidad_id, ruta_relativa, tamano, mtime, hash)`.
- `silo_tareas`: cola del `.py` — `tipo`
  (`ingesta_maestro` / `escaneo_rapido` / `verificar_hashes` / `propagar` / `volcar_bd` /
  `restaurar`), `payload`, `unidades_requeridas`, `estado`, `aprobada`, `resultado`,
  `error`, timestamps.
- `silo_eventos`: log de drifts detectados y su resolución, para el panel de alertas.

## Estado actual del código

- `SiloService::crearUnidad()` genera el `.silo_unit.json` con `hash_indice` /
  `ultima_sincronizacion` a `null`.
- `SiloIngestaService::ingestarCarpeta()` + `php spark silo:simular-ingesta` — ingesta
  **simulada**: recibe una lista de ficheros ya montada, no escanea disco real.
- `SiloPropagacionService::propagarTodo()` / `propagarPieza()` + `php spark silo:propagar`
  — propagación **lógica** en BD (Fase 2). No hay Fase 3.
- `Silo\Web` + vistas — coordinación y presentación: Mi PC, Unidades, listado/galería de
  carpetas, alta y reclasificación de piezas.
- **No existe todavía**: el agente `.py`, la API del agente, el escaneo real de disco, la
  detección de cambios (N0–N3), la réplica de BD en disco y su restauración, la cola
  `silo_tareas` y el panel de alertas.

## Cosas que NO son features (no reintroducir)

- **Sellar / precintar unidades**: que un disco "no vuelva a escribirse" es criterio
  humano sobre el backup, no un estado del sistema. Se borró el 2026-09-03.
- **"Año abierto / año cerrado"**: es una forma de razonar sobre la cadencia de
  verificación, no un campo ni un filtro.
