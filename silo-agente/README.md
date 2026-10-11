# Agente de Silo (primer esbozo)

Ejecutor que habla por API con la web de Trackbitos (diseño completo en
`../docs/silo-ingesta-propagacion.md`): escanea el primer nivel del root de
una unidad Maestro con `os.scandir` y manda lo que encuentra a
`POST /silo/agente/escaneo`. **La web decide** qué carpeta se ingesta y cuál
se salta (y por qué, ver `SiloService::clasificarEntradaRoot()`) — este
script no clasifica nada, solo lista disco y reporta.

## Alcance

- Fase 1 (ingesta del Maestro), solo el primer nivel del root.
- **Detección de cambios N0–N3** (2026-09-27): el manifiesto de cada unidad
  vive en su raíz (`.silo_manifest.json`, tamaño + fecha + hash de cada
  fichero). Solo se mandan a la web las carpetas que cambiaron; si el
  manifiesto no casa con la última sincronización de la web (N0), se manda
  todo. Un fichero con otra fecha pero el mismo tamaño se hashea (N2).
  `silo --verificar` re-hashea TODO (N3, lento) y avisa de ficheros con el
  mismo tamaño y fecha pero otro contenido (corrupción).
- **Proxies** (ffmpeg): se generan al dar de alta una carpeta y se
  regeneran cuando cambian sus fotos o vídeos. Copia en el Maestro en
  `.silo_proxies/<id_negocio>/`: si la web los pierde, se vuelven a subir
  desde ahí sin ffmpeg.
- **Réplica del catálogo**: al final de cada pasada deja en la raíz de la
  unidad `.catalogo.sql.gz` + `.catalogo.meta.json` (todas las tablas
  `silo_*`). Para restaurar: `silo --restaurar-catalogo <unidad_id>` desde
  este PC (sube la réplica a la web, pide escribir RESTAURAR), o
  `php spark silo:restaurar <ruta>` en el servidor. Si un disco trae una
  réplica más nueva que la BD viva, el panel de avisos de la web lo dice.
- Cola de tareas real solo para `escaneo_maestro` (ver "Lanzarlo desde la
  web").
- **Propagación física** a USB de nivel 2/3 y espejos: ver "Copias a USB".

## Uso

1. Da de alta la unidad Nivel 1 (Maestro) en `/silo/unidades` de la web si
   no existe todavía.
2. `cp config.example.json config.json` y ajusta:
   - `api_base`: la URL base de la web (sin `/silo` al final).
   - `token`: el mismo valor que `silo.apiToken` en el `.env` del servidor.
   - `unidades`: una entrada por disco — `ruta` es dónde se monta en ESTA
     máquina (ej. `D:\Maestro`, `/Volumes/Maestro`). `unidad_id` es
     opcional si ya lo sabes; si no, déjalo a `null` y añade la misma
     `ruta` como "ruta de montaje" de la unidad en `/silo/unidades` — el
     primer `handshake` la resuelve por ruta y ya no hace falta tocar
     `unidad_id` a mano.
   - `verificar_tls`: pon `false` solo si el servidor usa un certificado
     local no confiado (ej. ServBay en desarrollo).
3. `python agente.py --dry-run` — escanea y muestra qué detectaría, sin
   tocar la base de datos.
4. `python agente.py --solo-handshake` — solo comprueba que la web
   reconoce cada unidad configurada (útil para verificar `config.json`
   antes de escanear de verdad).
5. `python agente.py` — escanea e ingesta de verdad.
6. `python agente.py --daemon [--intervalo N]` — se queda corriendo,
   sondeando cada `N` segundos (por defecto 20); a diferencia del modo de
   una pasada, aquí **no escanea solo** — espera a que la web pida un
   escaneo (ver siguiente sección).

Sin dependencias fuera de la librería estándar de Python 3 (mismo criterio
que `../piezas-cli/trackbitos.py`): no hace falta `pip install` nada.

## Comando de terminal (`silo`)

Igual que `trackbitos`/`stl` (ver perfil de PowerShell): la función `silo`
llama a este script sin tener que hacer `cd` ni recordar la ruta —
`silo`, `silo --daemon`, `silo --dry-run`, etc. desde cualquier carpeta.

## Lanzarlo desde la web

`/silo/unidades` tiene un botón "Solicitar escaneo" en cada unidad Maestro
(nivel 1) que deja una tarea `escaneo_maestro` pendiente en `silo_tareas` —
la web nunca toca disco, solo anota la petición. Este script la recoge de
dos formas:

- **Pasada manual** (`silo`): escanea siempre igual, y si de paso hay una
  tarea pendiente para esa unidad, la cierra con el mismo resultado.
- **`silo --daemon`**: pensado para dejarlo corriendo en una terminal (o de
  fondo) mientras el disco está conectado — no escanea solo, solo cuando
  detecta la tarea en el sondeo.

En ambos casos la tarjeta de la unidad en `/silo/unidades` refleja el
estado (esperando agente / escaneado hace X / error).

## Copias a USB (Fase 3)

- `silo --copiar` — lista las unidades de copia con trabajo pendiente (USB de
  nivel 2/3 y espejos del Maestro) y las **pide de una en una**: conecta el
  USB que te dice, pulsa Enter, y le copia/renombra lo que le toca. Un USB
  que ya pasó por aquí se reconoce solo (`.silo_unit.json`); uno nuevo se
  elige de la lista de discos sin identificar. Hace falta el Maestro
  conectado para copiar (si no está, también lo pide).
- `silo --copiar 55 56` — solo esas unidades (aunque estén al día: sirve de
  verificación rápida por `stat`).
- `silo --renombrar` — solo propaga los **cambios de nombre** del Maestro
  (carpetas y ficheros), sin copiar nada ni necesitar el Maestro.
- `--purgar` — además borra de la copia lo que ya no le toca (pide BORRAR).
- `--dry-run` — enseña lo que haría sin tocar nada.

La clave es el **ID de negocio**: en Copia 2/3 las carpetas se llaman
`<cubo>/<fecha …> [260015]`, en un espejo igual que en el Maestro. Escanea
el Maestro (`silo`) antes de copiar para que la web tenga sus nombres y
ficheros al día. Las unidades de copia no hace falta ponerlas en
`config.json` (si están, el escaneo normal las salta).
