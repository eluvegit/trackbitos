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

<div class="container-fluid px-3 py-2">

    <!-- TOOLBAR STICKY -->
    <div class="sticky-top bg-body border-bottom py-2 mb-3 no-print">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1"><?= esc($proyecto['titulo']) ?></h1>
                <div class="text-muted small">
                    Proyecto #<?= esc($proyecto['id']) ?> · Escena #<?= esc($e['id']) ?>
                </div>
            </div>
            <div class="btn-group">
                <a class="btn btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver</a>
                <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir</button>
                <a class="btn btn-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">✏️ Editar</a>
            </div>
        </div>

        <!-- HERO HEADER -->
        <div class="hero-header rounded-3 p-4 mb-4">
            <h2 class="fw-semibold"><?= esc($bloque ?: 'Escena sin título') ?></h2>
            <div class="d-flex flex-wrap gap-2 small mt-2">
                <?php if ($orden): ?><span class="badge bg-light text-dark">Orden <?= $orden ?></span><?php endif; ?>
                <?php if ($tomas): ?><span class="badge bg-light text-dark">Tomas <?= esc($tomas) ?></span><?php endif; ?>
                <?php if ($ubic): ?><span class="badge bg-light text-dark"><?= esc($ubic) ?></span><?php endif; ?>
                <?php if ($hora): ?><span class="badge bg-light text-dark"><?= esc($hora) ?></span><?php endif; ?>
                <?php if ($actores): ?><span class="badge bg-warning text-dark">Con actores</span><?php endif; ?>
                <?php if ($fx): ?><span class="badge bg-info text-dark">FX <?= esc($fx) ?></span><?php endif; ?>
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

    <!-- GALERÍAS DE IMÁGENES -->
    <?php if (!empty($imagenes_lugar)): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Referencias visuales: Lugar y objetos</h6>
                <div class="gallery-grid">
                    <?php foreach ($imagenes_lugar as $img): $src = base_url($img['ruta']); ?>
                        <a href="<?= $src ?>" target="_blank" rel="noopener" class="gallery-item"><img src="<?= $src ?>" alt="Referencia de lugar" loading="lazy"></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($imagenes_insp)): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Referencias visuales: Inspiración</h6>
                <div class="gallery-grid">
                    <?php foreach ($imagenes_insp as $img): $src = base_url($img['ruta']); ?>
                        <a href="<?= $src ?>" target="_blank" rel="noopener" class="gallery-item"><img src="<?= $src ?>" alt="Referencia de inspiración" loading="lazy"></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    (function() {
        const p = new URLSearchParams(location.search);
        if (p.get('print') === '1') setTimeout(() => window.print(), 300);
    })();
</script>

<?= $this->endSection() ?>