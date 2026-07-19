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

    /* ==== Menú tipo lista (usado en index) ==== */
    .lentillas-menu {
        border-radius: 10px;
        overflow: hidden;
    }

    .lentillas-menu .list-group-item {
        border-left: 0;
        border-right: 0;
    }

    .lentillas-menu .list-group-item:first-child {
        border-top: 0;
    }

    .lentillas-menu .list-group-item:last-child {
        border-bottom: 0;
    }
</style>
