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

$renderRefs = fn($txt) => nl2br(auto_link(esc($txt), 'both', true));
?>

<style>
    /* Sticky Toolbar y Hero */
    .hero-header {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), .95), rgba(var(--bs-primary-rgb), .65));
        color: #fff;
    }

    .chip {
        background: rgba(var(--bs-primary-rgb), .12);
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        font-size: .75rem;
        padding: .25rem .6rem;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: .75rem;
    }

    .gallery-item {
        aspect-ratio: 4/3;
        overflow: hidden;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color);
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* Print */
    @media print {

        .no-print,
        header,
        footer {
            display: none !important;
        }

        .card {
            break-inside: avoid;
        }
    }
</style>

<style>
    /* Cuadrícula moderna */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }

    .gallery-item {
        aspect-ratio: 1 / 1;
        border-radius: 12px;
        overflow: hidden;
        background: #f0f0f0;
        display: block;
        border: 1px solid #eee;
        transition: transform 0.2s ease;
    }

    .gallery-item:hover {
        transform: scale(1.02);
    }

    .gallery-item img,
    .gallery-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Estilo específico para vídeos */
    .video-item {
        background: #000;
    }

    .video-overlay-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.8);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-size: 1.5rem;
        pointer-events: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }
</style>

<div class="container-fluid px-3 py-2">

    <!-- TOOLBAR STICKY -->
    <div class="bg-body border-bottom py-2 mb-3 no-print">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h1 class="h5 mb-0 text-truncate" style="max-width: 250px;">
                    <?= esc($proyecto['titulo']) ?>
                </h1>
            </div>

            <div class="btn-group shadow-sm">
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>" title="Volver">
                    <i class="bi bi-arrow-left small"></i>
                </a>
                <button class="btn btn-sm btn-light border" onclick="window.print()" title="Imprimir">
                    <i class="bi bi-printer small"></i>
                </button>
                <a class="btn btn-sm btn-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>" title="Editar">
                    <i class="bi bi-pencil small"></i>
                </a>
            </div>
        </div>

        <style>
            /* Forzamos a que el icono sea ligeramente más pequeño que el texto del botón */
            .btn-sm i.small {
                font-size: 0.85rem;
                vertical-align: middle;
            }

            /* Ajuste opcional para que en móviles no se amontone */
            @media (max-width: 576px) {
                .h5 {
                    font-size: 1rem;
                }
            }
        </style>

        <!-- HERO HEADER -->
        <div class="hero-header rounded-3 p-3 p-md-4 mb-4">
            <h2 class="fw-bold mb-2 text-wrap text-break">
                <?= esc($bloque ?: 'Escena sin título') ?>
            </h2>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <?php if ($orden): ?>
                    <span class="badge bg-light text-dark shadow-sm">Orden: <?= $orden ?></span>
                <?php endif; ?>

                <?php if ($tomas): ?>
                    <span class="badge bg-light text-dark shadow-sm text-wrap">
                        Toma: <?= esc($tomas) ?>
                    </span>
                <?php endif; ?>

                <?php if ($ubic): ?>
                    <span class="badge bg-light text-dark shadow-sm">
                        <i class="bi bi-geo-alt-fill me-1"></i><?= esc($ubic) ?>
                    </span>
                <?php endif; ?>

                <?php if ($hora): ?>
                    <span class="badge bg-light text-dark shadow-sm">
                        <i class="bi bi-clock-fill me-1"></i><?= esc($hora) ?>
                    </span>
                <?php endif; ?>

                <?php if ($actores): ?>
                    <span class="badge bg-warning text-dark shadow-sm">Con actores</span>
                <?php endif; ?>

                <?php if ($fx): ?>
                    <span class="badge bg-info text-dark shadow-sm">FX <?= esc($fx) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHIPS DE CÁMARA -->
        <?php if ($plano || $angulo || $mov || $soporte): ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php if ($plano): ?><span class="chip">📐 <?= esc($plano) ?></span><?php endif; ?>
                <?php if ($angulo): ?><span class="chip">📷 <?= esc($angulo) ?></span><?php endif; ?>
                <?php if ($mov): ?><span class="chip">🎥 <?= esc($mov) ?></span><?php endif; ?>
                <?php if ($soporte): ?><span class="chip">🔧 <?= esc($soporte) ?></span><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="row g-4">

        <!-- IZQUIERDA: descripción, objetivo, acción, sonido, notas -->
        <div class="col-lg-8">
            <?php foreach (['Descripción' => 'escena_descripcion', 'Objetivo narrativo' => 'escena_objetivo', 'Acción' => 'escena_accion'] as $label => $field): ?>
                <?php if (!empty($e[$field])): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-body border-start border-3 ps-3">
                            <div class="text-uppercase small text-primary fw-semibold"><?= $label ?></div>
                            <?= nl2br(esc($e[$field])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($e['sonido_dialogo_escrito'])): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Sonido</div>
                        <div class="mb-2 d-flex gap-2">
                            <span class="badge <?= ($e['sonido_ambiente'] ?? 'N') === 'S' ? 'bg-success' : 'bg-secondary' ?>">Ambiente</span>
                            <span class="badge <?= ($e['sonido_antiviento'] ?? 'N') === 'S' ? 'bg-success' : 'bg-secondary' ?>">Antiviento</span>
                        </div>
                        <p class="fst-italic"><?= nl2br(esc($e['sonido_dialogo_escrito'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>



            <!-- CONSTRUCCIÓN DEL PLANO -->

            <?php if (!empty($e['plano_esquema_iluminacion']) || !empty($e['plano_objetos']) || !empty($e['plano_toma_alternativa'])): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Construcción del plano</div>

                        <?php if (!empty($e['plano_esquema_iluminacion'])): ?>
                            <div class="mb-2">
                                <div class="fw-medium text-primary">Esquema de iluminación</div>
                                <?= nl2br(esc($e['plano_esquema_iluminacion'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['plano_objetos'])): ?>
                            <div class="mb-2">
                                <div class="fw-medium text-primary">Objetos en escena</div>
                                <?= nl2br(esc($e['plano_objetos'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($e['plano_toma_alternativa'])): ?>
                            <div class="mb-2">
                                <div class="fw-medium text-primary">Toma alternativa</div>
                                <?= nl2br(esc($e['plano_toma_alternativa'])) ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- DERECHA: cámara -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3 text-primary">Cámara</h6>
                    <div class="row g-2">
                        <?php foreach (['Óptica' => $optica, 'Apertura' => $apertura, 'FPS' => $fps, 'Velocidad' => $vel, 'ISO' => $iso, 'WB' => $wb, 'ND' => $nd] as $label => $value): ?>
                            <?php if ($value): ?>
                                <div class="col-6">
                                    <div class="border rounded p-2 bg-body text-body h-100 d-flex flex-column justify-content-center align-items-start">
                                        <div class="fw-medium text-secondary small"><?= $label ?></div>
                                        <div class="fw-semibold"><?= esc($value) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($e['plano_notas'])): ?>
                <div class="card shadow-sm my-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Notas</div>
                        <?= nl2br(esc($e['plano_notas'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($e['escena_cont_previa']) || !empty($e['escena_cont_posterior'])): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-3">Continuidad</div>

                        <div class="row g-3">
                            <?php if (!empty($e['escena_cont_previa'])): ?>
                                <div class="col-md-6">
                                    <div class="text-uppercase small text-muted">Plano anterior</div>
                                    <?= nl2br(esc($e['escena_cont_previa'])) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($e['escena_cont_posterior'])): ?>
                                <div class="col-md-6">
                                    <div class="text-uppercase small text-muted">Plano posterior</div>
                                    <?= nl2br(esc($e['escena_cont_posterior'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- REFERENCIAS DE TEXTO -->
    <?php if (!empty($e['plano_ref_lugar_texto']) || !empty($e['plano_ref_inspiracion_texto'])): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <div class="row g-3">

                    <?php if (!empty($e['plano_ref_lugar_texto'])): ?><div class="col-md-6">
                            <h6 class="fw-semibold">Referencias visuales: Lugar y objetos</h6><?= $renderRefs($e['plano_ref_lugar_texto']) ?>
                        </div><?php endif; ?>

                    <?php if (!empty($e['plano_ref_inspiracion_texto'])): ?><div class="col-md-6">
                            <h6 class="fw-semibold">Referencias visuales: Inspiración</h6><?= $renderRefs($e['plano_ref_inspiracion_texto']) ?>
                        </div><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // Helper interno para renderizar media (puedes moverlo arriba en la vista)
    $renderMedia = function ($img) {
        $src = base_url($img['ruta']);
        $extension = strtolower(pathinfo($img['ruta'], PATHINFO_EXTENSION));
        $esVideo = in_array($extension, ['mp4', 'webm', 'ogg', 'mov']);

        if ($esVideo): ?>
            <div class="gallery-item video-item position-relative">
                <video class="w-100 h-100" style="object-fit: cover;" muted playsinline>
                    <source src="<?= $src ?>" type="video/<?= $extension ?>">
                </video>
                <div class="video-overlay-icon">
                    <i class="bi bi-play-fill"></i>
                </div>
                <a href="<?= $src ?>" target="_blank" class="stretched-link"></a>
            </div>
        <?php else: ?>
            <a href="<?= $src ?>" target="_blank" rel="noopener" class="gallery-item">
                <img src="<?= $src ?>" alt="Referencia visual" loading="lazy">
            </a>
    <?php endif;
    };
    ?>

    <?php if (!empty($imagenes_lugar)): ?>
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center">
                    <i class="bi bi-geo-alt me-2 text-primary"></i>Lugar y objetos
                </h6>
                <div class="gallery-grid">
                    <?php foreach ($imagenes_lugar as $img) $renderMedia($img); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($imagenes_insp)): ?>
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 d-flex align-items-center">
                    <i class="bi bi-lightbulb me-2 text-warning"></i>Inspiración
                </h6>
                <div class="gallery-grid">
                    <?php foreach ($imagenes_insp as $img) $renderMedia($img); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mt-5 mb-4 no-print">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-dark p-3 rounded-3 border">
                <div>
                    <?php if ($prevId): ?>
                        <a class="btn btn-outline-primary" href="<?= site_url("rodajes/{$proyecto['id']}/escenas/show/$prevId") ?>">
                            <i class="bi bi-arrow-left me-2"></i> Escena Anterior
                        </a>
                    <?php endif; ?>
                </div>

                <div class="text-muted small fw-bold text-uppercase">
                    Fin de Escena <?= $orden ?>
                </div>

                <div>
                    <?php if ($nextId): ?>
                        <a class="btn btn-primary shadow-sm" href="<?= site_url("rodajes/{$proyecto['id']}/escenas/show/$nextId") ?>">
                            Siguiente Escena <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">
                            <i class="bi bi-check-circle me-2"></i> Finalizar Revisión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


</div>

<script>
    (function() {
        const p = new URLSearchParams(location.search);
        if (p.get('print') === '1') setTimeout(() => window.print(), 300);
    })();

    document.addEventListener('keydown', function(e) {
        if (e.key === "ArrowLeft") {
            <?php if ($prevId): ?> location.href = "<?= site_url("rodajes/{$proyecto['id']}/escenas/show/$prevId") ?>";
            <?php endif; ?>
        }
        if (e.key === "ArrowRight") {
            <?php if ($nextId): ?> location.href = "<?= site_url("rodajes/{$proyecto['id']}/escenas/show/$nextId") ?>";
            <?php endif; ?>
        }
    });
</script>

<?= $this->endSection() ?>