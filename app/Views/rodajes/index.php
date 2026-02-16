<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h1 class="fw-bold mb-0">Mis Rodajes</h1>
            <p class="text-muted">Gestiona tus producciones y planes de rodaje</p>
        </div>
        <a class="btn btn-primary px-4 shadow-sm" href="<?= site_url('rodajes/create') ?>">
            <i class="bi bi-plus-lg me-2"></i>Nuevo proyecto
        </a>
    </div>

    <div class="row g-4">
        <?php if (!empty($proyectos)): ?>
            <?php foreach ($proyectos as $p):
                $id = $p->id ?? $p['id'];
                $titulo = $p->titulo ?? $p['titulo'];
                $desc = $p->descripcion ?? $p['descripcion'];
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-light text-primary border">#<?= esc($id) ?></span>
                                <a class="text-secondary" href="<?= site_url('rodajes/edit/' . $id) ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </div>

                            <h5 class="card-title fw-bold mb-2"><?= esc($titulo) ?></h5>
                            <p class="card-text text-muted small mb-4 text-truncate-2">
                                <?= esc($desc) ?>
                            </p>

                            <div class="d-grid gap-2">
                                <a class="btn btn-outline-light btn-sm d-flex align-items-center justify-content-center" href="<?= site_url('rodajes/' . $id . '/escenas') ?>">
                                    <i class="bi bi-film me-2"></i> Ver Escenas
                                </a>

                                <div class="btn-group w-100 shadow-sm">
                                    <a class="btn btn-light border btn-sm text-primary" title="Storyboard" href="<?= site_url('rodajes/' . $id . '/escenas/storyboard') ?>">
                                        <i class="bi bi-layout-text-window-reverse me-1"></i> Story
                                    </a>
                                    <a class="btn btn-light border btn-sm text-success" title="Orden de Rodaje" href="<?= site_url('rodajes/' . $id . '/escenas/ordenrodaje') ?>">
                                        <i class="bi bi-sort-down me-1"></i> Plan
                                    </a>
                                </div>

                                <a class="btn btn-dark btn-sm d-flex align-items-center justify-content-center border-secondary"
                                    href="<?= site_url('rodajes/' . $id . '/dialogos') ?>"
                                    style="background-color: #1a1a1a;">
                                    <i class="bi bi-chat-quote me-2 text-info"></i> Diálogos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-camera-reels text-muted" style="font-size: 3rem;"></i>
                <p class="mt-3 text-muted">No hay proyectos registrados aún.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Pequeños ajustes CSS para ese "look" moderno */
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .1) !important;
    }

    .transition {
        transition: all 0.3s ease-in-out;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .card {
        border-radius: 12px;
    }

    .btn {
        border-radius: 8px;
    }
</style>

<?= $this->endSection() ?>