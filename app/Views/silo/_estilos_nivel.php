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

    /* Galería: la temática puede ocupar hasta dos líneas (los demás
       bloques siguen en una sola, recortados por el ancho de la card). */
    .silo-carpeta-badges .silo-bloque-tema .badge {
        white-space: normal;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-align: left;
    }

    /* ── Etiqueta de contenido de una temática: (Fotos + Vídeos + Montajes) ──
       Tinte flojo + texto vivo — pensado para el tema oscuro fijo de la app,
       donde los `*-subtle` de Bootstrap salían casi negros y no se leían.
       Verde Fotos · naranja Vídeos · azul Montajes. */
    .silo-badge-contenido {
        border: 1px solid transparent;
        font-weight: 400;
        vertical-align: baseline;
    }
    .silo-badge-fotos    { background: rgba(34, 197, 94, .16);  color: #4ade80; border-color: rgba(34, 197, 94, .40); }
    .silo-badge-videos   { background: rgba(249, 115, 22, .16); color: #fb923c; border-color: rgba(249, 115, 22, .40); }
    .silo-badge-montajes { background: rgba(59, 130, 246, .16); color: #60a5fa; border-color: rgba(59, 130, 246, .40); }

    /* Marca #ID de la unidad, justo tras la capacidad — chip pequeño casi
       negro para localizarla de un vistazo / buscarla rápido. */
    .silo-badge-id {
        background: rgba(0, 0, 0, .38);
        color: var(--bs-secondary-color);
        border: 1px solid rgba(255, 255, 255, .08);
        font-weight: 400;
        font-size: .58rem;
        letter-spacing: .02em;
        vertical-align: middle;
    }

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
