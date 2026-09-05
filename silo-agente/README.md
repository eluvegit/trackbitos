# Agente de Silo (primer esbozo)

Ejecutor que habla por API con la web de Trackbitos (diseño completo en
`../docs/silo-ingesta-propagacion.md`): escanea el primer nivel del root de
una unidad Maestro con `os.scandir` y manda lo que encuentra a
`POST /silo/agente/escaneo`. **La web decide** qué carpeta se ingesta y cuál
se salta (y por qué, ver `SiloService::clasificarEntradaRoot()`) — este
script no clasifica nada, solo lista disco y reporta.

## Alcance de este primer esbozo

- Solo Fase 1 (ingesta del Maestro), solo el primer nivel del root.
- Ficheros sin hash todavía (`tamano_bytes` sí, `hash` no) — la web los
  acepta igual; simplemente no hay detección de cambios real todavía
  (eso es N0–N3 del doc, pendiente).
- Cola de tareas real solo para `escaneo_maestro`: la web puede pedir un
  escaneo (botón "Solicitar escaneo" en `/silo/unidades`) y este script lo
  detecta en el `handshake` y lo cierra — ver "Lanzarlo desde la web" más
  abajo. Otros tipos de tarea (mover piezas, propagación física) siguen sin
  aprobación humana ni cola real.
- Sin generación de proxies (fotos/vídeo de muestra) ni réplica de BD en el
  disco — siguen simulados/pendientes en la web.

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

## Siguiente paso, cuando esto funcione

Mover el escaneo real de discos aquí no cambia el resto del plan: cola de
tareas de verdad (`silo_tareas` ya existe con ese fin), hashing +
manifiesto por-fichero (N1–N3), generación de proxies, y el volcado de BD
por unidad (`.catalogo.sql.gz`). Todo descrito en el doc de diseño.
