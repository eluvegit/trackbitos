# Unificación de recordatorios (Coche, Lentillas, Recordatorios) — pendiente de desarrollar

## Motivación

Actualmente hay tres sistemas de "recordatorios" independientes:

- **Recordatorios** (`recordatorios`) — el módulo central, genérico, con `periodo_meses`/`periodo_dias`.
- **Coche** (`car_reminders` + `car_actions`) — el vencimiento se deriva de la última fila de
  `car_actions` que coincide + `interval_days`/`interval_km`. Renovar inserta una fila nueva en
  `car_actions` (efecto secundario: log de historial).
- **Lentillas** (`lentillas_avisos` + stock) — renovar ("sustitución") registra historial **y**
  descuenta cantidades de `lentillas_stock` (efecto secundario: stock).

El plan es hacer que Braintogram (Telegram + IA) pueda listar y renovar recordatorios por chat.
Para eso conviene que exista **una única fuente de verdad y una única acción de "renovar"**, en vez
de que el bot tenga que conocer la lógica particular de cada módulo. Se descartó la opción de
"vistas agregadas" (leer en vivo de las 3 tablas sin moverlas) porque no resuelve ese problema de
fondo, solo lo maquilla en la pantalla central.

## Diseño acordado (sin implementar todavía)

### Esquema — ampliar la tabla `recordatorios`

- `modulo` (varchar nullable): `null` (genérico), `'coche'`, `'lentillas'`, ... — de qué módulo es,
  para filtrar y pintar cada uno con su estilo/contexto propio.
- `intervalo_cantidad` (int nullable) — umbral de un contador genérico (NO solo km). Repetición
  "cada X unidades de un contador", al mismo nivel que `periodo_meses`/`periodo_dias` (que es
  repetición por tiempo). Reutilizable por cualquier módulo futuro (km, usos, ciclos...), no
  hardcodeado a Coche.
- `intervalo_unidad` (varchar nullable) — etiqueta para mostrarlo: `'km'`, `'usos'`, `'ciclos'`...
- `meta` (JSON nullable) — SOLO para datos idiosincráticos de un módulo que nadie fuera de su
  lógica de renovado necesita interpretar (ej. en Lentillas, qué ítem(s) de stock descontar al
  renovar). No debe usarse para cosas que Recordatorios/Braintogram necesiten mostrar o entender de
  forma genérica — eso va en columnas propias (como `intervalo_*`).

Nota: el valor ACTUAL de un contador tipo kilometraje NO vive en `recordatorios` (sigue siendo dato
del propio módulo, ej. última entrada de kilometraje en Coche). Calcular "cuánto queda" para un
recordatorio de tipo contador necesita su propio hook por módulo, igual que el renovado — no debe
meterse esa lógica en el helper genérico `recordatorio_estado()`.

### Renovado — un único endpoint con efectos por módulo

Dentro de `Recordatorios::renovar()`, tras mover la fecha, despachar según `modulo`:

```php
match ($recordatorio['modulo']) {
    'coche'     => // inserta fila en car_actions
    'lentillas' => // inserta sustitución + descuenta stock (misma lógica ya existente)
    default     => null, // recordatorio normal, sin efecto extra
};
```

Este es el único sitio donde vive esa lógica. Tanto la web como Braintogram lo llaman igual — el
bot no necesita saber nada de coches ni lentillas, solo "renovar recordatorio X".

### Pasos de migración

1. Migración de esquema: añadir `modulo`, `intervalo_cantidad`, `intervalo_unidad`, `meta` a
   `recordatorios`.
2. Migrar datos: copiar filas de `car_reminders` y `lentillas_avisos` a `recordatorios` con su
   `modulo` correspondiente (mapear `interval_days`→`periodo_dias`, `interval_km`→
   `intervalo_cantidad`/`intervalo_unidad='km'`, etc.).
3. Las pantallas de Coche → Recordatorios y Lentillas → Avisos pasan a leer de la misma tabla
   central filtrada por `modulo` (visualmente pueden seguir pareciendo secciones propias de cada
   módulo).
4. Reescribir los controladores de Coche/Lentillas para que el renovado delegue en la lógica única
   de `Recordatorios` (mismo endpoint o mismo servicio compartido), no dos implementaciones
   distintas.
5. Retirar `car_reminders`/`lentillas_avisos` (o dejarlas un tiempo sin usar como red de seguridad
   antes de borrarlas).
6. Con todo esto ya unificado, construir la integración de Braintogram: listar recordatorios
   pendientes y renovarlos por Telegram, hablando solo con `recordatorios`.

## Semántica de "renovar": ciclo flexible vs fecha fija (pendiente de discutir)

Detectado al revisar el botón "Renovar" de Coche/Hogar (2026-08-10). Comportamiento actual
confirmado en código:

- **Coche** (`Coche::renovarRecordatorio`): un único botón, siempre visible, siempre hace lo mismo
  — inserta una `car_action` con la fecha (hoy o la elegida) y el próximo vencimiento se recalcula
  como `fecha_accion + interval_days`. No hay estado "hecho" ni gating por vencimiento: da igual si
  está vencido, a punto de vencer o le quedan meses, siempre desplaza el ciclo entero desde la
  fecha dada.
- **Hogar** (`Hogar::marcarTarea` / `renovarTarea`): sí hay estado (`estado=1`). El botón "Renovar"
  solo aparece tras marcar hecha y deshace la marca reiniciando `ultima_vez` a ahora — un "vuelvo a
  hacerlo ya" sin gating por vencimiento tampoco.

Problema: el botón mezcla dos acciones distintas ("cumplí el plazo" vs "lo hago antes de tiempo")
sin restricción temporal, y no hay ningún estado que represente "ya hecho, esperando al próximo
vencimiento" (se infiere solo del cálculo de fechas).

Propuesta en discusión: separar la acción en dos según si el recordatorio está vencido o no —
"marcar" normal si ya venció, otra acción (renombrada, no "renovar") si se adelanta antes de plazo
— y bloquear cualquier botón mientras ya está al día (hecho y aún no vencido).

Matiz importante que puede romper esa regla tal cual: hay dos tipos de periodo y no es lo mismo
adelantarlos.

- **Ciclo flexible / "mínimo"** (cambiar el aceite, limpiar un filtro): el plazo es "no antes de X
  tiempo desde la última vez". Adelantarlo es legítimo y el próximo vencimiento SÍ debe desplazarse
  desde la nueva fecha de ejecución — es lo que hace hoy "renovar".
- **Fecha fija / "máximo"** (ITV, seguro): el próximo vencimiento es una fecha de calendario que no
  depende de cuándo actúes. Adelantarlo NO debería mover el vencimiento, solo dejar constancia de
  que ya está hecho; el próximo ciclo sigue en su fecha original.

`interval_days` (y el futuro `intervalo_cantidad`/`periodo_meses`+`periodo_dias`) asume
implícitamente que todo es tipo "ciclo flexible". Falta decidir si añadir un flag tipo `tipo_ciclo`
('flexible' | 'fijo') a la tabla `recordatorios` ampliada (ver esquema arriba) para que "adelantar"
sepa si debe mover el vencimiento o no — sin esto, la regla de "antes/después del plazo decide el
botón" no basta por sí sola.

Sin resolver todavía: nombre final del botón "adelantar", si aplica igual a Hogar (que hoy no tiene
el concepto de vencimiento por delante, solo `frecuencia_dias` desde `ultima_vez`), y si merece la
pena modelarlo ahora o esperar a la migración de esquema del resto de este documento.

## Estado

Solo diseño, sin ningún cambio de código todavía. Próximo paso cuando se retome: migración de
esquema (paso 1) + mapeo exacto de campos de Coche/Lentillas antes de tocar nada más, y decidir la
semántica de "adelantar" (ver sección anterior) antes de tocar los botones de Coche/Hogar.
