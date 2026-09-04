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
- Sin cola de tareas real: cada ejecución hace handshake + escanea + reporta
  en el momento, no espera aprobación humana desde la web.
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

Sin dependencias fuera de la librería estándar de Python 3 (mismo criterio
que `../piezas-cli/trackbitos.py`): no hace falta `pip install` nada.

## Siguiente paso, cuando esto funcione

Mover el escaneo real de discos aquí no cambia el resto del plan: cola de
tareas de verdad (`silo_tareas` ya existe con ese fin), hashing +
manifiesto por-fichero (N1–N3), generación de proxies, y el volcado de BD
por unidad (`.catalogo.sql.gz`). Todo descrito en el doc de diseño.
