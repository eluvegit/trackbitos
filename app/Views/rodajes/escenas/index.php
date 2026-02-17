<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .scene-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid #e9ecef !important;
    }

    .scene-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
    }

    .order-badge {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-weight: bold;
        background: #0d6efd;
        color: white;
    }

    .tech-pill {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        font-size: 0.75rem;
        color: #495057;
        padding: 2px 8px;
        border-radius: 4px;
    }

    .camera-box {
        background-color: #111;
        color: #fff;
        border-radius: 6px;
        padding: 10px;
    }

    .dialogue-snippet {
        font-family: 'Courier New', Courier, monospace;
        background-color: #f8f9fa;
        border-left: 3px solid #0dcaf0;
        padding: 10px;
        font-size: 0.85rem;
        color: #333;
        margin-bottom: 1rem;
        border-radius: 0 4px 4px 0;
    }

    .action-snippet {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-style: italic;
        color: #6c757d;
        font-size: 0.9rem;
    }
</style>

<style>
    .media-wrapper {
        height: 180px;
        background: #000;
        margin: -1rem -1rem 1rem -1rem;
        position: relative;
        overflow: hidden;
    }

    .media-wrapper video,
    .media-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .video-overlay-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        pointer-events: none;
    }
</style>

<div class="container py-4">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h1 class="h3 mb-0 fw-bold"><?= esc($proyecto['titulo']) ?></h1>
            <p class="text-muted mb-0">Listado de Escenas</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a class="btn btn-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/create') ?>">
                    <i class="bi bi-plus-lg"></i> Nueva escena
                </a>
                <a class="btn btn-secondary shadow-sm" href="<?= site_url('rodajes/' . $proyecto['id'] . '/dialogos') ?>">
                    <i class="bi bi-chat-left-quote me-1"></i> Diálogos
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">Vistas</button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/storyboard') ?>">🎬 Storyboard</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/ordenrodaje') ?>">📊 Clasificado</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('rodajes') ?>">💬 Proyectos</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($escenas)): ?>
        <div class="alert alert-info border-0 shadow-sm">No hay escenas registradas en este proyecto todavía.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($escenas as $e): ?>
                <?php
                // Chips de cámara activos
                $chips = array_filter([$e['camara_tipo_plano'] ?? '', $e['camara_angulo'] ?? '', $e['camara_movimiento'] ?? '']);
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 scene-card shadow-sm">
                        <div class="card-body">

                            <div class="d-flex justify-content-between mb-3">
                                <div class="order-badge">#<?= (int)$e['orden'] ?></div>
                                <div class="d-flex gap-1">
                                    <?php if (!empty($e['escena_efecto_especial'])): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">FX</span>
                                    <?php endif; ?>
                                    <span class="badge bg-dark border small"><?= esc($e['plano_hora_dia'] ?: 'N/D') ?></span>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-1 text-truncate"><?= esc($e['escena_bloque'] ?: 'Sin título') ?></h5>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-geo-alt-fill text-danger"></i> <?= esc($e['escena_ubicacion'] ?: 'Localización no definida') ?>
                            </div>

                            <?php if (!empty($chips)): ?>
                                <div class="mb-3">
                                    <?php foreach ($chips as $c): ?>
                                        <span class="tech-pill me-1"><?= esc($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($e['escena_tomas'])): ?>
                                <span class="badge bg-light text-dark border">
                                    <strong>Toma:</strong> <?= esc($e['escena_tomas']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($e['escena_descripcion'])): ?>
                                <div class="mb-2">
                                    <small class="text-uppercase fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.5px;">Descripción Técnica</small>
                                    <p class="small text-light mb-2" style="line-height: 1.4;">
                                        <?= esc(mb_strimwidth($e['escena_descripcion'], 0, 100, '...')) ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="camera-box small mb-3">
                                <div class="d-flex justify-content-between  border-secondary mb-1">
                                    <span><i class="bi bi-camera-video me-1"></i> <?= esc($e['camara_optica'] ?: '-') ?></span>
                                    <span>ƒ/<?= esc($e['camara_apertura'] ?: '-') ?></span>
                                    <span><?= esc($e['camara_fps'] ?: '24') ?>fps</span>
                                </div>
                            </div>

                            <?php if (!empty($e['sonido_dialogo_escrito'])): ?>
                                <div class="dialogue-snippet">
                                    <i class="bi bi-chat-quote-fill text-info small"></i>
                                    <?= esc(mb_strimwidth($e['sonido_dialogo_escrito'], 0, 85, '...')) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($e['escena_accion'])): ?>
                                <p class="action-snippet mb-0" style="min-height: 40px;">
                                    <strong>Acción:</strong> <?= esc(mb_strimwidth($e['escena_accion'], 0, 90, '...')) ?>
                                </p>
                            <?php else: ?>
                                <div style="height: 40px;"></div>
                            <?php endif; ?>
                            <div class="gallery-item <?= (isset($multimedia[$e['id']]) && $multimedia[$e['id']]['es_video']) ? 'video-item' : '' ?> mb-3"
                                style="height: 180px; margin: -1rem -1rem 1rem -1rem; border-radius: 0;">

                                <?php if (isset($multimedia[$e['id']])):
                                    $m = $multimedia[$e['id']];
                                    $src = base_url($m['ruta']);
                                ?>
                                    <?php if ($m['es_video']): ?>
                                        <div class="position-relative h-100">
                                            <video class="w-100 h-100" style="object-fit: cover;" muted playsinline onmouseover="this.play()" onmouseout="this.pause()">
                                                <source src="<?= $src ?>" type="video/<?= $m['ext'] ?>">
                                            </video>
                                            <div class="video-overlay-icon" style="width: 30px; height: 30px; font-size: 1rem;">
                                                <i class="bi bi-play-fill"></i>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= $src ?>" class="w-100 h-100" style="object-fit: cover;" alt="Referencia">
                                    <?php endif; ?>

                                <?php else: ?>
                                    <div class="d-flex h-100 align-items-center justify-content-center bg-light text-muted small">
                                        <i class="bi bi-camera-video-off me-2"></i> Sin media
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-top-0 pb-3">
                            <div class="d-flex gap-2">
                                <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $e['id']) ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye"></i> Detalles
                                </a>
                                <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/delete/' . $e['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Borrar escena?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>