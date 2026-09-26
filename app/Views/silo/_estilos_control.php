<?php
/**
 * Estilos compartidos de la interfaz "panel de control" del módulo Silo:
 * cabecera con breadcrumb en mayúsculas + título grande, encabezados de
 * sección numerados (01/02/03...) con línea horizontal, la tarjeta base
 * reutilizada por unidades.php / mi_pc.php, y el pie de página.
 *
 * Se incluye una vez por página, junto a _estilos_nivel.php cuando la
 * vista también pinta contenido agrupado por nivel. Nació en unidades.php
 * (2026-09-26) y se extrajo aquí para no duplicar las reglas al llevar el
 * mismo aspecto al resto de vistas del módulo — si algún día no gusta,
 * basta con dejar de incluir este parcial y las vistas vuelven a los
 * `<h5>` con iconos sueltos que tenían antes (están en el historial git).
 */
?>
<style>
    .silo-control-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .45rem;
        font-size: .68rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        margin-bottom: .35rem;
    }

    .silo-control-breadcrumb a {
        color: inherit;
        text-decoration: none;
    }

    .silo-control-breadcrumb a:hover {
        color: var(--bs-emphasis-color);
    }

    /* Iconos de navegación rápida (vocabulario/unidades/mi-pc/ranking...),
       pegados al margen derecho y SIN el tratamiento en mayúsculas del
       resto del breadcrumb — son iconos, no texto. */
    .silo-control-iconos {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: .7rem;
        font-size: .95rem;
        text-transform: none;
        letter-spacing: normal;
    }

    .silo-control-titulo {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        font-size: 1.9rem;
        font-weight: 300;
        margin: 0 0 1rem;
        color: var(--bs-emphasis-color);
    }

    .silo-control-titulo strong {
        font-weight: 700;
    }

    .silo-btn-accent {
        text-transform: uppercase;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        color: #fbbf24;
        border-color: #fbbf24;
    }

    .silo-btn-accent:hover {
        background: #fbbf24;
        border-color: #fbbf24;
        color: #1a1305;
    }

    .silo-btn-ghost {
        text-transform: uppercase;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .06em;
        color: var(--bs-secondary-color);
        border-color: var(--bs-border-color);
    }

    .silo-btn-ghost:hover {
        color: var(--bs-emphasis-color);
        border-color: var(--bs-emphasis-color);
    }

    .silo-seccion-header-row {
        display: flex;
        align-items: center;
        gap: .6rem;
        margin-bottom: 1rem;
    }

    .silo-seccion-num {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        font-size: .78rem;
        color: var(--silo-accent, #fbbf24);
    }

    .silo-seccion-titulo {
        margin: 0;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-emphasis-color);
    }

    .silo-seccion-sub {
        font-size: .66rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    .silo-seccion-linea {
        flex: 1 1 auto;
        height: 1px;
        background: var(--bs-border-color);
        margin-left: .4rem;
    }

    .silo-control-footer {
        border-top: 1px solid var(--bs-border-color);
        padding-top: .85rem;
        font-size: .66rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    .silo-mono {
        font-family: var(--bs-font-monospace);
    }

    /* Barra de progreso ámbar — uso de una unidad, tamaño relativo de un
       fichero en un ranking, cualquier "cuánto ocupa esto del total". */
    .progress-bar.silo-progress-fill {
        background-color: #fbbf24;
    }

    .silo-tabla-control thead th {
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        border-bottom-color: var(--bs-border-color);
    }

    /* ── Tarjeta base — la usan unidades.php (unidad+modal) y mi_pc.php ─────
       (unidad+enlace). Cada vista añade encima solo lo que le hace falta
       (barra de uso, tarjetas dashed, etc). */
    .silo-fila-unidades {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2.5rem;
    }

    .silo-tarjeta {
        width: 12rem;
        border-radius: 1rem;
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-tertiary-bg);
        padding: 1rem;
        display: flex;
        flex-direction: column;
        text-align: left;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .silo-tarjeta-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .4rem;
        margin-bottom: .5rem;
    }

    .silo-tarjeta-idbadge {
        font-family: var(--bs-font-monospace);
        font-size: .62rem;
        color: var(--bs-secondary-color);
        opacity: .8;
        white-space: nowrap;
        margin-top: .15rem;
    }

    .silo-tarjeta-capacidad {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        font-size: 1.3rem;
        line-height: 1;
        color: var(--bs-emphasis-color);
    }

    .silo-tarjeta-capacidad small {
        font-size: .65rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
        margin-left: .15rem;
    }

    .silo-tarjeta-nombre-linea {
        display: flex;
        align-items: center;
        gap: .4rem;
    }

    .silo-tarjeta-nombre-linea .silo-icono-unidad,
    .silo-tarjeta-nombre-linea .bi {
        width: 1.1rem;
        height: 1.1rem;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .silo-tarjeta-nombre {
        font-size: .82rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .silo-tarjeta-contenido {
        margin-top: .2rem;
        line-height: 1.5;
    }

    .silo-tarjeta-contenido .badge {
        font-size: .58rem;
        font-weight: 400;
    }

    .silo-tarjeta-ruta,
    .silo-tarjeta-detalle {
        font-size: .68rem;
        color: var(--bs-secondary-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: .15rem;
    }

    .silo-tarjeta-ruta {
        font-family: var(--bs-font-monospace);
    }

    .silo-tarjeta-escaneo {
        font-size: .68rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: .15rem;
    }

    .silo-tarjeta-escaneo.text-warning,
    .silo-tarjeta-escaneo.text-danger {
        font-weight: 600;
    }

    /* Estadística suelta (ranking, totales) — mismo lenguaje que la
       tarjeta de unidad pero sin las partes específicas de unidad física. */
    .silo-stat {
        border-radius: 1rem;
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-tertiary-bg);
        padding: 1rem;
    }

    .silo-stat-num {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        font-size: 1.3rem;
        line-height: 1;
        color: var(--bs-emphasis-color);
    }

    .silo-stat-label {
        font-size: .66rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        margin-top: .35rem;
    }
</style>
