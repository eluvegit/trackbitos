<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.95);
    }
    .scene-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid #e9ecef !important;
    }
    .scene-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
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
        border-radius: 6px;
        padding: 8px 12px;
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
    .btn-group-sm .btn {
        padding: 0.4rem 0.8rem;
    }
</style>

<div class="container py-4">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= site_url('rodajes') ?>" class="text-decoration-none">Proyectos</a></li>
                    <li class="breadcrumb-item active"><?= esc($proyecto['titulo']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 fw-bold">Escenas</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a class="btn btn-primary shadow-sm" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/create') ?>">
                    <i class="bi bi-plus-lg me-1"></i> Nueva escena
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Vistas
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/storyboard') ?>">🎬 Storyboard</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/ordenrodaje') ?>">📊 Clasificado</a></li>
                        <li><a class="dropdown-item" href="<?= site_url('rodajes/' . $proyecto['id'] . '/dialogos') ?>">💬 Guion de Diálogos</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($escenas)): ?>
        <div class="text-center py-5 border rounded-4 bg-white shadow-sm">
            <i class="bi bi-camera-reels text-muted display-1"></i>
            <h4 class="mt-3">Aún no hay escenas</h4>
            <p class="text-muted">Empieza a planificar el rodaje añadiendo tu primera escena.</p>
            <a class="btn btn-primary mt-2" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/create') ?>">Crear Escena #1</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($escenas as $e): ?>
                <?php
                    $chips = array_filter([$e['camara_tipo_plano'] ?? '', $e['camara_angulo'] ?? '', $e['camara_movimiento'] ?? '']);
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 scene-card shadow-sm">
                        <div class="card-body">
                            
                            <div class="d-flex justify-content-between mb-3">
                                <div class="order-badge">
                                    <?= (int)$e['orden'] ?>
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    <?php if ($e['plano_hora_dia']): ?>
                                        <span class="badge rounded-pill bg-dark text-light border"><i class="bi bi-brightness-high me-1"></i><?= esc($e['plano_hora_dia']) ?></span>
                                    <?php endif; ?>
                                    <?php if (($e['plano_actores'] ?? 'N') === 'S'): ?>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-people-fill"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-1 text-truncate">
                                <?= esc($e['escena_bloque'] ?: 'Sin título') ?>
                            </h3>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-geo-alt-fill text-danger"></i> <?= esc($e['escena_ubicacion'] ?: 'Localización pendiente') ?>
                                <?php if ($e['escena_tomas']): ?>
                                    <span class="ms-2">| <strong>T:</strong> <?= esc($e['escena_tomas']) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($chips)): ?>
                                <div class="mb-3">
                                    <?php foreach ($chips as $c): ?>
                                        <span class="tech-pill me-1"><?= esc($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="camera-box small mb-3 d-flex justify-content-between">
                                <div><i class="bi bi-camera-video me-1"></i> <?= esc($e['camara_optica'] ?: '-') ?></div>
                                <div><i class="bi bi-aperture me-1"></i> ƒ/<?= esc($e['camara_apertura'] ?: '-') ?></div>
                                <div><i class="bi bi-speedometer2 me-1"></i> <?= esc($e['camara_fps'] ?: '-') ?> fps</div>
                            </div>

                            <?php if (!empty($e['escena_accion'])): ?>
                                <p class="action-snippet mb-4">
                                    <?= esc(mb_strimwidth($e['escena_accion'], 0, 100, '...')) ?>
                                </p>
                            <?php else: ?>
                                <div style="height: 54px;"></div> <?php endif; ?>

                        </div>

                        <div class="card-footer bg-transparent border-top-0 pb-3 px-3">
                            <div class="d-flex gap-2">
                                <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $e['id']) ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye"></i> Leer
                                </a>
                                <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/delete/' . $e['id']) ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('¿Eliminar escena?')">
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