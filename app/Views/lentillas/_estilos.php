<style>
    /* ==== Cabecera / breadcrumb ==== */
    .lentillas-crumb a:hover {
        text-decoration: underline !important;
    }

    .lentillas-header-icon,
    .datos-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    /* ==== Tarjetas genéricas del módulo ==== */
    .lentillas-card {
        border-radius: 12px;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .lentillas-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .15) !important;
    }

    .lentillas-card-accent {
        height: 4px;
        width: 100%;
    }

    .lentillas-card-accent-start {
        width: 4px;
        flex-shrink: 0;
    }

    /* ==== Badges OI / OD (ojo izquierdo / derecho) ==== */
    .ojo-badge {
        font-size: .7rem;
        letter-spacing: .03em;
        padding: .35em .6em;
    }

    .ojo-badge-izq {
        background-color: rgba(13, 110, 253, .15);
        color: #6ea8fe;
    }

    .ojo-badge-der {
        background-color: rgba(13, 202, 240, .15);
        color: #6edff6;
    }

    /* ==== Menú de tarjetas (usado en index), mismo patrón que coche/gimnasio ==== */
    .lentillas-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    @media (min-width: 576px)  { .lentillas-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (min-width: 768px)  { .lentillas-grid { grid-template-columns: repeat(6, 1fr); } }
    @media (min-width: 1200px) { .lentillas-grid { grid-template-columns: repeat(8, 1fr); } }

    .lentillas-tile-link {
        text-decoration: none;
        display: block;
        position: relative;
    }
    button.lentillas-tile-link {
        border: 0;
        background: none;
        padding: 0;
        width: 100%;
        font: inherit;
    }

    .lentillas-tile {
        aspect-ratio: 1 / 1;
        max-width: 130px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 4px;
        padding: 8px;
        border-radius: 14px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        transition: transform .15s ease, box-shadow .2s ease, border-color .15s ease;
    }
    .lentillas-tile-link:hover .lentillas-tile {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .18);
        border-color: #0d6efd;
    }

    .lentillas-tile-icon {
        font-size: 1.3rem;
        line-height: 1;
        color: var(--bs-emphasis-color);
    }
    .lentillas-tile-title {
        font-size: .72rem;
        font-weight: 700;
        color: var(--bs-emphasis-color);
        line-height: 1.15;
    }
    .lentillas-tile-text {
        font-size: .62rem;
        color: var(--bs-secondary-color);
        line-height: 1.15;
    }

    .lentillas-tile-count {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        background: #dc3545;
        color: #fff;
        font-size: .68rem;
        font-weight: 700;
        display: grid;
        place-items: center;
        padding: 0 4px;
    }
</style>
