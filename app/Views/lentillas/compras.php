<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Lentillas
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Compras</span>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-cart3"></i>
    </div>
    <div>
        <h2 class="mb-0">Compras</h2>
        <small class="text-muted">Registro de lentillas, gafas y líquidos</small>
    </div>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <div><?= session('message') ?></div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm lentillas-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small text-muted mb-3">Nueva compra</h6>
        <form method="post" action="<?= site_url('lentillas/compras') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <option value="Lentillas">Lentillas</option>
                        <option value="Gafas">Gafas</option>
                        <option value="Líquido">Líquido</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="precio" class="form-label">Precio (€)</label>
                    <input type="number" step="0.01" name="precio" id="precio" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="notas" class="form-label">Notas</label>
                    <input type="text" name="notas" id="notas" class="form-control">
                </div>
            </div>

            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Guardar compra
                </button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex align-items-center gap-2 my-5">
    <h4 class="mb-0">Histórico de compras</h4>
    <?php if (!empty($compras)): ?>
        <span class="badge rounded-pill text-bg-secondary"><?= count($compras) ?></span>
    <?php endif; ?>
</div>

<?php if (!empty($compras)): ?>
    <div class="row g-3">
        <?php foreach ($compras as $compra):
            $tipo = strtolower($compra['tipo']);
            $meta = match ($tipo) {
                'lentillas' => ['color' => 'primary', 'icon' => 'bi-record-circle'],
                'gafas'     => ['color' => 'info', 'icon' => 'bi-eyeglasses'],
                'líquido'   => ['color' => 'success', 'icon' => 'bi-droplet'],
                default     => ['color' => 'secondary', 'icon' => 'bi-bag'],
            };
        ?>
            <div class="col-lg-8 offset-lg-2">
                <div class="card border-0 shadow-sm lentillas-card lentillas-entry">
                    <div class="d-flex">
                        <div class="lentillas-card-accent-start bg-<?= $meta['color'] ?>"></div>
                        <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="datos-icon bg-<?= $meta['color'] ?> bg-opacity-10 text-<?= $meta['color'] ?>">
                                    <i class="bi <?= $meta['icon'] ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= esc($compra['tipo']) ?></h6>
                                    <div class="text-muted small">
                                        <?= esc(date('d/m/Y', strtotime($compra['fecha']))) ?> ·
                                        <span class="fw-semibold text-body"><?= esc(number_format($compra['precio'], 2)) ?> €</span>
                                    </div>
                                    <?php if (!empty($compra['notas'])): ?>
                                        <div class="small mt-1"><?= esc($compra['notas']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="<?= site_url('lentillas/compras/editar/' . $compra['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?= site_url('lentillas/compras/eliminar/' . $compra['id']) ?>" method="post" onsubmit="return confirm('¿Eliminar esta compra?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-muted text-center">No hay compras registradas aún.</p>
<?php endif; ?>

<style>
    .lentillas-entry .lentillas-card-accent-start {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
</style>

<?= $this->endSection() ?>
