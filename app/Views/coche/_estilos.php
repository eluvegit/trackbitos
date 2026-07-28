<style>
    /* ==== Cabecera / breadcrumb ==== */
    .coche-crumb a:hover {
        text-decoration: underline !important;
    }

    /* ==== Grid de tiles del hub ==== */
    .coche-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    @media (min-width: 576px)  { .coche-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (min-width: 768px)  { .coche-grid { grid-template-columns: repeat(6, 1fr); } }
    @media (min-width: 1200px) { .coche-grid { grid-template-columns: repeat(8, 1fr); } }

    .coche-card-link { text-decoration: none; display: block; position: relative; }
    button.coche-card-link {
        border: 0;
        background: none;
        padding: 0;
        width: 100%;
        font: inherit;
    }

    .coche-card {
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
    .coche-card-link:hover .coche-card {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0,0,0,.18);
        border-color: #0d6efd;
    }

    .coche-card-icon { font-size: 1.3rem; line-height: 1; color: var(--bs-emphasis-color); }
    .coche-card-title {
        font-size: .72rem;
        font-weight: 700;
        color: var(--bs-emphasis-color);
        line-height: 1.15;
    }
    .coche-card-text {
        font-size: .62rem;
        color: var(--bs-secondary-color);
        line-height: 1.15;
    }

    .coche-card-count {
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

    /* ==== Lista de acciones: menos contenido por fila que recordatorios,
       así que se limita el ancho para que no quede rara estirada a lo
       ancho de toda la pantalla ==== */
    .coche-acciones-lista {
        max-width: 700px;
        margin: 0 auto;
    }

    /* ==== Tarjetas de listado (acciones / averías / recordatorios) ==== */
    .coche-rec-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 7px 10px;
        margin-bottom: 6px;
        border-radius: 12px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        transition: background-color .15s ease;
    }
    .coche-rec-card:hover { background: var(--bs-tertiary-bg); }
    .coche-rec-card.js-detalle-accion { cursor: pointer; }
    .coche-rec-card.js-detalle-accion:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: 1px;
    }

    .coche-detalle-dl dt { color: var(--bs-secondary-color); font-weight: 600; }
    .coche-detalle-dl dd { color: var(--bs-emphasis-color); }

    .coche-nivel-caducado { border-color: rgba(220,53,69,.4); background: rgba(220,53,69,.06); }
    .coche-nivel-urgente  { border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.06); }

    .coche-rec-icono {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--bs-tertiary-bg);
        color: var(--bs-emphasis-color);
        font-size: .95rem;
        line-height: 1;
    }
    .coche-rec-icono-danger {
        background: rgba(220, 53, 69, .12);
        color: #dc3545;
    }
    .coche-rec-card-danger {
        border-color: rgba(220, 53, 69, .25);
    }

    .coche-rec-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 1px; }

    .coche-rec-row-top { display: flex; align-items: center; gap: 8px; }
    .coche-rec-titulo {
        flex: 1 1 auto;
        min-width: 0;
        font-weight: 700;
        font-size: .92rem;
        color: var(--bs-emphasis-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .coche-badge {
        flex: 0 0 auto;
        display: inline-block;
        padding: .18rem .55rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .coche-badge-caducado { background: rgba(220,53,69,.15); color: #dc3545; }
    .coche-badge-urgente  { background: rgba(245,158,11,.18); color: #f59e0b; }
    .coche-badge-proximo  { background: rgba(99,102,241,.15); color: #818cf8; }
    .coche-badge-lejano   { background: rgba(16,185,129,.15); color: #10b981; }
    .coche-badge-neutro   { background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); }

    .coche-rec-row-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    .coche-rec-meta {
        flex: 1 1 auto;
        min-width: 0;
        font-size: .72rem;
        color: var(--bs-secondary-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .coche-rec-actions { flex: 0 0 auto; display: flex; align-items: center; gap: 0; }
    .coche-btn {
        width: 26px;
        height: 26px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--bs-secondary-color);
        text-decoration: none;
        cursor: pointer;
        font-size: .82rem;
    }
    .coche-btn:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }
    .coche-btn-danger:hover { color: #dc3545; }

    /* ==== Tiles de "acción rápida" (botón = tarjeta completa) ==== */
    .coche-rapida-btn {
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
        border-radius: 12px;
        width: 100%;
        padding: 1rem;
        text-align: center;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .2s ease;
    }
    .coche-rapida-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0,0,0,.18);
    }
</style>
