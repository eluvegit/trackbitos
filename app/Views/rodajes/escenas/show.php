<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('text'); ?>

<?php
$e = $escena ?? [];
$orden     = (int)($e['orden'] ?? 0);
$bloque    = trim($e['escena_bloque'] ?? '');
$tomas     = trim($e['escena_tomas'] ?? '');
$ubic      = trim($e['escena_ubicacion'] ?? '');
$hora      = trim($e['plano_hora_dia'] ?? '');
$plano     = trim($e['camara_tipo_plano'] ?? '');
$angulo    = trim($e['camara_angulo'] ?? '');
$mov       = trim($e['camara_movimiento'] ?? '');
$soporte   = trim($e['camara_soporte'] ?? '');
$optica    = trim($e['camara_optica'] ?? '');
$apertura  = trim($e['camara_apertura'] ?? '');
$fps       = trim($e['camara_fps'] ?? '');
$vel       = trim($e['camara_velocidad'] ?? '');
$iso       = trim($e['camara_iso'] ?? '');
$wb        = trim($e['camara_wb'] ?? '');
$nd        = trim($e['camara_nd'] ?? '');
$fx        = trim($e['escena_efecto_especial'] ?? '');
$actores   = ($e['plano_actores'] ?? 'N') === 'S';

$renderRefs = function (string $txt) {
    return nl2br(auto_link(esc($txt), 'both', true));
};
?>
<style>
    /* ============================================================
   RODAJE UI — Compatible con Bootstrap 5 + Dark Mode
   Todo está encapsulado en .rodaje-ui para no romper Bootstrap
   ============================================================ */

    .rodaje-ui {
        /* Variables conectadas al sistema Bootstrap */
        --r-primary: var(--bs-primary);
        --r-primary-rgb: var(--bs-primary-rgb);
        --r-bg: var(--bs-body-bg);
        --r-surface: var(--bs-tertiary-bg);
        --r-border: var(--bs-border-color);
        --r-text: var(--bs-body-color);
        --r-muted: var(--bs-secondary-color);
        --r-radius: var(--bs-border-radius);
        --r-radius-lg: var(--bs-border-radius-lg);
    }

    /* Ajuste específico dark mode si el tema no define tertiary */
    [data-bs-theme="dark"] .rodaje-ui {
        --r-surface: #1e1e1e;
    }

    /* ================= HEADER ================= */

    .rodaje-ui .hero-header {
        background: linear-gradient(135deg,
                rgba(var(--r-primary-rgb), .95),
                rgba(var(--r-primary-rgb), .65));
        color: #fff;
        padding: 1.5rem;
        border-radius: var(--r-radius-lg);
        margin-bottom: 1.5rem;
    }

    .rodaje-ui .hero-title {
        font-size: 1.6rem;
        font-weight: 600;
        margin: 0;
    }

    .rodaje-ui .hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        margin-top: .5rem;
    }

    .rodaje-ui .hero-badge {
        background: rgba(255, 255, 255, .15);
        padding: .25rem .6rem;
        border-radius: 999px;
        font-size: .75rem;
    }

    /* ================= CARDS ================= */

    .rodaje-ui .info-card {
        background: var(--r-surface);
        border: 1px solid var(--r-border);
        border-radius: var(--r-radius);
        padding: 1rem;
        margin-bottom: 1rem;
        transition: .2s ease;
    }

    .rodaje-ui .info-card:hover {
        box-shadow: var(--bs-box-shadow-sm);
    }

    .rodaje-ui .card-header {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .75rem;
        padding-bottom: .4rem;
        border-bottom: 1px solid var(--r-border);
    }

    .rodaje-ui .card-title {
        font-size: .95rem;
        font-weight: 600;
        margin: 0;
    }

    /* ================= GRID ESPECIFICACIONES ================= */

    .rodaje-ui .specs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: .5rem;
    }

    .rodaje-ui .spec-item {
        background: var(--r-bg);
        border: 1px solid var(--r-border);
        border-radius: var(--r-radius);
        padding: .5rem .6rem;
    }

    .rodaje-ui .spec-label {
        font-size: .65rem;
        text-transform: uppercase;
        color: var(--r-muted);
    }

    .rodaje-ui .spec-value {
        font-size: .85rem;
        font-weight: 500;
    }

    /* ================= CHIPS ================= */

    .rodaje-ui .chips-container {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }

    .rodaje-ui .chip {
        background: rgba(var(--r-primary-rgb), .15);
        color: var(--r-primary);
        padding: .2rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 500;
    }

    /* ================= GALERÍA ================= */

    .rodaje-ui .gallery-section {
        background: var(--r-surface);
        border: 1px solid var(--r-border);
        border-radius: var(--r-radius);
        padding: 1rem;
    }

    .rodaje-ui .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: .6rem;
    }

    .rodaje-ui .gallery-item {
        border-radius: var(--r-radius);
        overflow: hidden;
        border: 1px solid var(--r-border);
        aspect-ratio: 4/3;
    }

    .rodaje-ui .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ================= TEXTO ================= */

    .rodaje-ui .text-section {
        background: var(--r-surface);
        border: 1px solid var(--r-border);
        border-left: 3px solid var(--r-primary);
        border-radius: var(--r-radius);
        padding: .75rem;
        margin-bottom: .75rem;
    }

    .rodaje-ui .text-label {
        font-size: .7rem;
        text-transform: uppercase;
        color: var(--r-primary);
        margin-bottom: .25rem;
    }

    .rodaje-ui .text-content {
        font-size: .85rem;
    }

    /* ================= TOOLBAR ================= */

    .rodaje-ui .toolbar {
        position: sticky;
        top: 0;
        background: var(--r-bg);
        border-bottom: 1px solid var(--r-border);
        padding: .5rem 0;
        margin-bottom: 1rem;
        z-index: 10;
    }

    /* ================= LAYOUT ================= */

    .rodaje-ui .content-grid {
        display: grid;
        gap: 1rem;
    }

    @media (min-width: 992px) {
        .rodaje-ui .content-grid {
            grid-template-columns: 2fr 1fr;
        }
    }

    /* ================= PRINT ================= */

    @media print {

        .rodaje-ui .toolbar,
        .rodaje-ui .no-print,
        header,
        footer {
            display: none !important;
        }

        .rodaje-ui {
            font-size: 10px;
        }

        .rodaje-ui .info-card {
            break-inside: avoid;
            border: 1px solid #000;
            background: #fff !important;
            color: #000 !important;
        }

        .rodaje-ui .hero-header {
            background: #000 !important;
            color: #fff !important;
        }

        .rodaje-ui .gallery-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>
<div class="rodaje-ui">

    <div class="container-fluid px-3 py-2">

        <!-- Toolbar -->
        <div class="toolbar no-print">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-1"><?= esc($proyecto['titulo']) ?></h1>
                    <div class="text-muted small">Proyecto #<?= esc($proyecto['id']) ?> · Escena #<?= esc($e['id']) ?></div>
                </div>
                <div class="btn-group">
                    <a class="btn btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">
                        ← Volver
                    </a>
                    <button class="btn btn-primary" onclick="window.print()">
                        🖨️ Imprimir
                    </button>
                    <a class="btn btn-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">
                        ✏️ Editar
                    </a>
                </div>
            </div>


            <!-- Hero Header -->
            <div class="hero-header">
                <h1 class="hero-title"><?= esc($bloque ?: 'Escena sin título') ?></h1>
                <div class="hero-meta">
                    <?php if ($orden): ?><span class="hero-badge">📋 Orden: <?= esc($orden) ?></span><?php endif; ?>
                    <?php if ($tomas): ?><span class="hero-badge">🎬 Toma/s: <?= esc($tomas) ?></span><?php endif; ?>
                    <?php if ($ubic): ?><span class="hero-badge">📍 <?= esc($ubic) ?></span><?php endif; ?>
                    <?php if ($hora): ?><span class="hero-badge">🕐 <?= esc($hora) ?></span><?php endif; ?>
                    <?php if ($actores): ?><span class="hero-badge">👥 Con actores</span><?php endif; ?>
                    <?php if ($fx): ?><span class="hero-badge">✨ FX: <?= esc($fx) ?></span><?php endif; ?>
                </div>
            </div>

            <!-- Chips de configuración visual -->
            <?php if ($plano || $angulo || $mov || $soporte): ?>
                <div class="chips-container">
                    <?php if ($plano): ?><span class="chip">📐 <?= esc($plano) ?></span><?php endif; ?>
                    <?php if ($angulo): ?><span class="chip chip-accent">📷 <?= esc($angulo) ?></span><?php endif; ?>
                    <?php if ($mov): ?><span class="chip">🎥 <?= esc($mov) ?></span><?php endif; ?>
                    <?php if ($soporte): ?><span class="chip chip-success">🔧 <?= esc($soporte) ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Grid principal -->
        <div class="content-grid">

            <!-- Columna izquierda -->
            <div>
                <!-- Escena -->
                <?php if (!empty($e['escena_descripcion']) || !empty($e['escena_objetivo']) || !empty($e['escena_accion'])): ?>
                    <div class="info-card">
                        <div class="card-header">
                            <div class="card-icon">🎬</div>
                            <h2 class="card-title">Escena</h2>
                        </div>

                        <?php if (!empty($e['escena_descripcion'])): ?>
                            <div class="text-section">
                                <div class="text-label">Descripción</div>
                                <div class="text-content"><?= nl2br(esc($e['escena_descripcion'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['escena_objetivo'])): ?>
                            <div class="text-section">
                                <div class="text-label">Objetivo narrativo</div>
                                <div class="text-content"><?= nl2br(esc($e['escena_objetivo'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['escena_accion'])): ?>
                            <div class="text-section">
                                <div class="text-label">Acción</div>
                                <div class="text-content"><?= nl2br(esc($e['escena_accion'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['escena_cont_previa']) || !empty($e['escena_cont_posterior'])): ?>
                            <div class="row g-2 mt-2">
                                <?php if (!empty($e['escena_cont_previa'])): ?>
                                    <div class="col-md-6">
                                        <div class="text-section">
                                            <div class="text-label">← Continuidad previa</div>
                                            <div class="text-content"><?= nl2br(esc($e['escena_cont_previa'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($e['escena_cont_posterior'])): ?>
                                    <div class="col-md-6">
                                        <div class="text-section">
                                            <div class="text-label">Continuidad posterior →</div>
                                            <div class="text-content"><?= nl2br(esc($e['escena_cont_posterior'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Construcción del plano -->
                <?php if (!empty($e['plano_esquema_iluminacion']) || !empty($e['plano_objetos']) || !empty($e['plano_toma_alternativa'])): ?>
                    <div class="info-card">
                        <div class="card-header">
                            <div class="card-icon">💡</div>
                            <h2 class="card-title">Construcción del plano</h2>
                        </div>

                        <?php if (!empty($e['plano_esquema_iluminacion'])): ?>
                            <div class="text-section">
                                <div class="text-label">Esquema de iluminación</div>
                                <div class="text-content"><?= nl2br(esc($e['plano_esquema_iluminacion'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['plano_objetos'])): ?>
                            <div class="text-section">
                                <div class="text-label">Objetos en escena</div>
                                <div class="text-content"><?= nl2br(esc($e['plano_objetos'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['plano_toma_alternativa'])): ?>
                            <div class="text-section">
                                <div class="text-label">Toma alternativa</div>
                                <div class="text-content"><?= nl2br(esc($e['plano_toma_alternativa'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Sonido -->
                <div class="info-card">
                    <div class="card-header">
                        <div class="card-icon">🎙️</div>
                        <h2 class="card-title">Sonido</h2>
                    </div>

                    <div class="chips-container">
                        <span class="chip <?= ($e['sonido_ambiente'] ?? 'N') === 'S' ? 'chip-success' : '' ?>">
                            <?= ($e['sonido_ambiente'] ?? 'N') === 'S' ? '✓' : '✗' ?> Ambiente
                        </span>
                        <span class="chip <?= ($e['sonido_antiviento'] ?? 'N') === 'S' ? 'chip-success' : '' ?>">
                            <?= ($e['sonido_antiviento'] ?? 'N') === 'S' ? '✓' : '✗' ?> Antiviento
                        </span>
                    </div>

                    <?php if (!empty($e['sonido_dialogo_escrito'])): ?>
                        <div class="text-section">
                            <div class="text-label">Diálogo escrito</div>
                            <div class="text-content"><?= nl2br(esc($e['sonido_dialogo_escrito'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($e['plano_notas'])): ?>
                    <div class="info-card">
                        <div class="card-header">
                            <div class="card-icon">📝</div>
                            <h2 class="card-title">Notas</h2>
                        </div>
                        <div class="text-content"><?= nl2br(esc($e['plano_notas'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna derecha -->
            <div>
                <!-- Especificaciones de cámara -->
                <div class="info-card">
                    <div class="card-header">
                        <div class="card-icon">📹</div>
                        <h2 class="card-title">Cámara</h2>
                    </div>

                    <div class="specs-grid">
                        <?php if (!empty($e['camara_modelo'])): ?>
                            <div class="spec-item">
                                <div class="spec-label">Modelo</div>
                                <div class="spec-value"><?= esc($e['camara_modelo']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($optica): ?>
                            <div class="spec-item">
                                <div class="spec-label">Óptica</div>
                                <div class="spec-value"><?= esc($optica) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($apertura): ?>
                            <div class="spec-item">
                                <div class="spec-label">Apertura</div>
                                <div class="spec-value"><?= esc($apertura) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($fps): ?>
                            <div class="spec-item">
                                <div class="spec-label">FPS</div>
                                <div class="spec-value"><?= esc($fps) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($vel): ?>
                            <div class="spec-item">
                                <div class="spec-label">Velocidad</div>
                                <div class="spec-value"><?= esc($vel) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($iso): ?>
                            <div class="spec-item">
                                <div class="spec-label">ISO</div>
                                <div class="spec-value"><?= esc($iso) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($wb): ?>
                            <div class="spec-item">
                                <div class="spec-label">Balance blancos</div>
                                <div class="spec-value"><?= esc($wb) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($nd): ?>
                            <div class="spec-item">
                                <div class="spec-label">Filtro ND</div>
                                <div class="spec-value"><?= esc($nd) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Referencias de texto -->
        <?php if (!empty($e['plano_ref_lugar_texto']) || !empty($e['plano_ref_inspiracion_texto'])): ?>
            <div class="info-card">
                <div class="card-header">
                    <div class="card-icon">🔗</div>
                    <h2 class="card-title">Referencias (enlaces)</h2>
                </div>

                <div class="row g-3">
                    <?php if (!empty($e['plano_ref_lugar_texto'])): ?>
                        <div class="col-md-6">
                            <div class="text-label">Lugar / Objetos</div>
                            <div class="text-content"><?= $renderRefs((string)$e['plano_ref_lugar_texto']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($e['plano_ref_inspiracion_texto'])): ?>
                        <div class="col-md-6">
                            <div class="text-label">Inspiración</div>
                            <div class="text-content"><?= $renderRefs((string)$e['plano_ref_inspiracion_texto']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Galerías de imágenes -->
        <?php if (!empty($imagenes_lugar)): ?>
            <div class="gallery-section">
                <div class="card-header">
                    <div class="card-icon">🖼️</div>
                    <h2 class="card-title">Referencias visuales: Lugar y objetos</h2>
                </div>
                <div class="gallery-grid">
                    <?php foreach ($imagenes_lugar as $img): $src = base_url($img['ruta']); ?>
                        <a href="<?= $src ?>" target="_blank" rel="noopener" class="gallery-item">
                            <img src="<?= $src ?>" alt="Referencia de lugar" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($imagenes_insp)): ?>
            <div class="gallery-section">
                <div class="card-header">
                    <div class="card-icon">✨</div>
                    <h2 class="card-title">Referencias visuales: Inspiración</h2>
                </div>
                <div class="gallery-grid">
                    <?php foreach ($imagenes_insp as $img): $src = base_url($img['ruta']); ?>
                        <a href="<?= $src ?>" target="_blank" rel="noopener" class="gallery-item">
                            <img src="<?= $src ?>" alt="Referencia de inspiración" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div class="no-print mt-4 pb-4 d-flex justify-content-center gap-2">
            <a class="btn-custom btn-secondary-custom" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">
                ← Volver al listado
            </a>
            <button class="btn-custom btn-primary-custom" onclick="window.print()">
                🖨️ Imprimir / Guardar PDF
            </button>
            <a class="btn-custom btn-primary-custom" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">
                ✏️ Editar escena
            </a>
        </div>
    </div>
</div>

<script>
    // Auto-print si llega ?print=1
    (function() {
        const p = new URLSearchParams(location.search);
        if (p.get('print') === '1') setTimeout(() => window.print(), 300);
    })();
</script>

<?= $this->endSection() ?>