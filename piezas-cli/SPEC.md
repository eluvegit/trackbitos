# Trackbitos · Módulo Piezas — Especificación de implementación

## Estado de implementación

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Migraciones + modelos, invariantes 1-4 | ✅ Hecho |
| 2 | `PiezaService` con los verbos | ✅ Hecho |
| 3 | `trackbitos estado` (hash de nube desde fichero local) | ✅ Hecho |
| 4 | API de lectura (`/variantes`, `/variante/{id}/estado`) + cliente conectado | ✅ Hecho |
| 5 | API de subida y descarga, con verificación de hash en ambos extremos | ⬜ Pendiente |
| 6 | Interfaz web: ficha de variante y botones de los verbos | ⬜ Pendiente |
| 7 | Papelera y purga de sesiones al validar | ⬜ Pendiente |

Dónde vive cada cosa:
- Migración: `app/Database/Migrations/2026-08-16-000001_CreatePiezasTables.php`
- Modelos: `app/Models/Pieza{Maquina,Familia,Variante,Version,Rama,Sesion,Descarga}Model.php`
  (prefijados "Pieza" a propósito — ya existe un `SesionModel`/tabla `sesiones` para el módulo de rodajes fotográficos, completamente distinto)
- Verbos: `app/Services/PiezaService.php`
- API: `app/Controllers/Piezas/Api.php`, filtro `app/Filters/PiezasApiAuth.php`, rutas bajo `piezas/api` en `app/Config/Routes.php`, token en `.env` (`piezas.apiToken`, no versionado)
- Cliente CLI: `piezas-cli/trackbitos.py` (fuera de `app/`, se puede mover a cualquier carpeta del equipo — no depende de vivir en este repo). Config real en `~/.trackbitos/config.json`.

Para continuar: pide la fase 5 ("API de subida y descarga") y sigue el orden de la sección 10 más abajo.

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

- **familia**: la pieza conceptual (cuerpo, brazo, casco).
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

**Pendiente de implementar (fase 5):** ninguna de las rutas de escritura (abrir sesión real vía API, subir con verificación de `hash_padre`, cerrar-sin-cambios, forzar-cierre) existe todavía. `PiezaDescargaModel` es CRUD simple a la espera de esta lógica.

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

Implementado (fase 4): `POST /maquina/registrar` vía `PiezaMaquinaModel::registrar()`, alta automática de UUID en `piezas-cli/trackbitos.py::cargar_config()`.

---

## 5. Cliente de línea de comandos

Multiplataforma (macOS y Windows). **Python 3** con dependencias mínimas, ejecutable con un solo comando. Vive fuera de este repo, en cualquier carpeta del equipo — no depende de estar dentro de Trackbitos.

Configuración en `~/.trackbitos/config.json`: URL base, token de API, **UUID de máquina** (generado en el primer arranque), directorio de trabajo, directorio de papelera. El nombre visible de la máquina lo guarda el servidor, no el cliente.

### 5.1 Comandos

```
trackbitos estado          # el más usado — diagnóstico completo          ✅ hecho
trackbitos abrir <variante>                                               ⬜ fase 5
trackbitos bajar                                                          ⬜ fase 5
trackbitos ver <variante>          # descarga de consulta, sin abrir sesión  ⬜ fase 5
trackbitos subir                                                          ⬜ fase 5
trackbitos cerrar                                                         ⬜ fase 5
trackbitos cerrar --sin-cambios   # cierra la descarga sin subir fichero  ⬜ fase 5
trackbitos promocionar                                                    ⬜ fase 5
```

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

```
POST /maquina/registrar                  { uuid, hostname, so } → alta o ping        ✅ hecho
GET  /variantes                          lista con estado resumido                    ✅ hecho
GET  /variante/{id}/estado               rama abierta, última sesión, hash nube,      ✅ hecho
                                         bloqueo y descargas pendientes por máquina
POST /variante/{id}/sesion/abrir         { maquina } → 409 si ya hay sesión abierta   ⬜ fase 5
GET  /sesion/{id}/descargar              devuelve el .blend + hash en cabecera;       ⬜ fase 5
                                         abre el asiento de descarga (máquina, fecha)
POST /sesion/{id}/subir                  multipart + hash_padre; 409 si no cuadra     ⬜ fase 5
POST /descarga/{id}/cerrar-sin-cambios   { hash_local } → debe igualar hash_entregado ⬜ fase 5
POST /descarga/{id}/forzar-cierre        { motivo } → solo desde la web               ⬜ fase 5
POST /sesion/{id}/cerrar                                                              ⬜ fase 5
POST /variante/{id}/promocionar          { cambio, medidas? } → crea versión, cierra rama  ⬜ fase 5
POST /version/{id}/impresa               { params_impresion }                         ⬜ fase 5
POST /version/{id}/validar               { resultado } → degrada la anterior          ⬜ fase 5
POST /version/{id}/descartar             { resultado } → motivo obligatorio           ⬜ fase 5
POST /version/{id}/devolver-a-trabajo    abre rama nueva                              ⬜ fase 5
POST /variante/derivar                   { origen_version_id, nombre }                ⬜ fase 5
```

El servidor debe **recalcular el hash** de todo fichero recibido y rechazar la subida si no coincide con el declarado.

Nota: los endpoints de fase 5 son en su mayoría llamadas directas a los verbos ya implementados en `PiezaService` (fase 2) — la fase 5 es sobre todo "exponerlos por HTTP con manejo de ficheros", no reinventar la lógica.

---

## 7. Interfaz web

Sobria y orientada al estado. Lo que debe responder de un vistazo: **cuál es la buena y dónde está el trabajo en curso.**

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

### 7.2 Avisos

- Al descargar una mesa de trabajo, mostrar el hash de la nube para que el cliente pueda contrastar.
- Si una versión lleva más de N días en `borrador` o `impresa` sin resolverse, marcarla visualmente como pendiente de juicio.

### 7.3 Feedback de la promoción

Promocionar es el momento que cierra un ciclo de trabajo. La confirmación debe ser explícita y satisfactoria: número de versión asignado, fecha, y la rama nueva ya abierta. Es el punto donde el usuario ve una victoria cerrada.

---

## 8. Almacenamiento

- Los `.blend` y `.stl` se guardan en el servidor, fuera del directorio público, servidos solo vía API autenticada.
- Rutas por variante y versión, con nombres derivados de los IDs (no de texto libre del usuario).
- Incluir el directorio de subidas en el backup existente a Backblaze B2.
- **Fuera de este sistema** (van aparte, en almacenamiento en la nube en modo transmitir): fotos de referencia en alta resolución, ficheros `.ctb` de placas laminadas, imágenes de compartir en alta. Solo se guarda la ruta o una nota, no el binario.

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
5. **API de subida y descarga**, con verificación de hash en ambos extremos. ⬜ ← siguiente
6. **Interfaz web**: ficha de variante y botones de los verbos. ⬜
7. **Papelera y purga** de sesiones al validar. ⬜

La fase 3 es la que concentraba el riesgo del diseño — quedó probada a fondo (incluido un bug real de codificación UTF-8 en Windows) antes de construir nada encima.
