<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Escenas — <?= esc($proyecto['titulo']) ?></h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/create') ?>">➕ Nueva escena</a>
            <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/storyboard') ?>">🎬 Storyboard</a>
            <a class="btn btn-secondary" href="<?= site_url('rodajes') ?>">← Volver a proyectos</a>
        </div>
    </div>


    <?php if (empty($escenas)): ?>
        <div class="alert alert-info">Todavía no hay escenas registradas.</div>
    <?php else: ?>
        <div class="row g-3">

            <?php foreach ($escenas as $e): ?>
                <?php
                // Helpers de display
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
                $fx        = trim($e['escena_efecto_especial'] ?? '');
                $actores   = ($e['plano_actores'] ?? 'N') === 'S';
                $accion    = trim($e['escena_accion'] ?? '');

                // Snippet de acción
                if (function_exists('mb_strimwidth')) {
                    $accionSnippet = $accion !== '' ? mb_strimwidth($accion, 0, 110, '…', 'UTF-8') : '';
                } else {
                    $accionSnippet = $accion !== '' ? substr($accion, 0, 110) . (strlen($accion) > 110 ? '…' : '') : '';
                }

                // Etiquetas compactas
                $chips = array_filter([$plano, $angulo, $mov, $soporte]);
                ?>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">

                            <!-- Header con orden + estado visual -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-secondary">Orden <?= esc($orden) ?></span>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($hora): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><?= esc($hora) ?></span>
                                    <?php endif; ?>
                                    <?php if ($actores): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Actores</span>
                                    <?php endif; ?>
                                    <?php if ($fx): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">FX</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Título y subtítulo -->
                            <h5 class="card-title mb-1">
                                <?= esc($bloque !== '' ? $bloque : 'Bloque sin título') ?>
                                <?php if ($tomas): ?>
                                    <small class="text-muted">• Toma/s: <?= esc($tomas) ?></small>
                                <?php endif; ?>
                            </h5>
                            <p class="text-muted mb-2">
                                <?= $ubic ? '📍 ' . esc($ubic) : '📍 Ubicación no indicada' ?>
                            </p>

                            <!-- Chips de plano/ángulo/movimiento/soporte -->
                            <?php if (!empty($chips)): ?>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <?php foreach ($chips as $c): ?>
                                        <span class="badge text-bg-light border"><?= esc($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Resumen de cámara -->
                            <div class="small text-muted mb-2">
                                <?php if ($optica): ?><span><strong>Óptica:</strong> <?= esc($optica) ?></span><?php endif; ?>
                                <?php if ($optica && ($apertura || $fps)): ?> · <?php endif; ?>
                                <?php if ($apertura): ?><span><strong>ƒ/</strong><?= esc($apertura) ?></span><?php endif; ?>
                                <?php if ($apertura && $fps): ?> · <?php endif; ?>
                                <?php if ($fps): ?><span><strong>FPS:</strong> <?= esc($fps) ?></span><?php endif; ?>
                            </div>

                            <!-- Snippet de acción -->
                            <?php if ($accionSnippet): ?>
                                <p class="mb-3"><em>Acción:</em> <?= esc($accionSnippet) ?></p>
                            <?php else: ?>
                                <div class="mb-3"></div>
                            <?php endif; ?>

                            <!-- Footer acciones -->
                            <div class="mt-auto d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary"
                                    href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $e['id']) ?>">👁️ Ver</a>
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">✏️ Editar</a>
                                <a class="btn btn-sm btn-outline-danger"
                                    href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/delete/' . $e['id']) ?>"
                                    onclick="return confirm('¿Eliminar escena?')">🗑️ Eliminar</a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a class="btn btn-secondary" href="<?= site_url('rodajes') ?>">← Volver a proyectos</a>
    </div>
</div>
<?= $this->endSection() ?>