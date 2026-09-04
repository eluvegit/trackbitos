<?php
/**
 * Estilos compartidos del módulo Silo: la paleta por nivel (N1 Maestro /
 * N2 Año / N3 Temática) y las reglas que la aplican a las cards de unidad
 * (Mi PC) y a los listados / galerías / tablas cuando se navega DENTRO de
 * una unidad de ese nivel.
 *
 * Se incluye una vez por página desde las vistas de nivel superior
 * (mi_pc, unidad, index, show). Los parciales _listado_piezas /
 * _galeria_piezas solo usan las clases; no traen estilos propios.
 *
 * La app va SIEMPRE en tema oscuro (layouts/default => data-bs-theme="dark"),
 * por eso los acentos son los tonos vivos.
 */
?>
<style>
    /* ── Paleta por nivel — EDITAR SOLO ESTAS 3 LÍNEAS para recolorear ──────
       --silo-accent : borde izquierdo, icono de disco, subrayado del título
       --silo-tint   : fondo suave de cards, filas y tablas                   */
    .silo-n1 { --silo-accent: #fbbf24; --silo-tint: rgba(245, 158, 11, .10); } /* Maestro  · ámbar   */
    .silo-n2 { --silo-accent: #60a5fa; --silo-tint: rgba(59, 130, 246, .10); } /* Año      · azul    */
    .silo-n3 { --silo-accent: #a78bfa; --silo-tint: rgba(139, 92, 246, .12); } /* Temática · violeta */
    /* ──────────────────────────────────────────────────────────────────────── */

    /* Título "Nivel N — Etiqueta" en Mi PC */
    .silo-nivel-titulo {
        display: inline-block;
        padding-bottom: .15rem;
        border-bottom: 2px solid var(--silo-accent);
    }

    /* Card de carpeta (unidad en Mi PC · pieza en galería) — base neutra */
    .silo-carpeta { transition: background-color .12s ease, border-color .12s ease; }
    .silo-carpeta:hover {
        background-color: var(--bs-tertiary-bg);
        border-color: var(--bs-secondary);
    }
    .silo-carpeta-badges {
        overflow: hidden;
        max-height: 9rem;
        font-size: .62rem;
        line-height: 1.5;
    }
    .silo-carpeta-badges .badge { font-size: .62rem; }

    /* ── Dentro de un nivel (contenedor .silo-nivel.silo-nX): todo se tiñe ─── */
    .silo-nivel .silo-hdd { color: var(--silo-accent); }

    .silo-nivel .silo-carpeta {
        background-color: var(--silo-tint);
        border-left: 3px solid var(--silo-accent) !important;
    }
    .silo-nivel .silo-carpeta:hover {
        background-color: var(--bs-tertiary-bg);
        border-left-color: var(--silo-accent) !important;
    }

    .silo-nivel .list-group-item {
        background-color: var(--silo-tint);
        border-left: 3px solid var(--silo-accent);
    }
    .silo-nivel .list-group-item:hover { background-color: var(--bs-tertiary-bg); }

    .silo-nivel .table {
        --bs-table-bg: var(--silo-tint);
        border-left: 3px solid var(--silo-accent);
    }
</style>
