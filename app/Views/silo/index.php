<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-archive text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Silo</strong>

    <a href="<?= site_url('silo/vocabulario') ?>" class="text-decoration-none ms-1 text-muted" title="Vocabulario">
        <i class="bi bi-tags"></i>
    </a>
    <a href="<?= site_url('silo/unidades') ?>" class="text-decoration-none ms-1 text-muted" title="Unidades">
        <i class="bi bi-hdd-stack"></i>
    </a>
    <a href="<?= site_url('silo/crear') ?>" class="text-decoration-none ms-1 text-success" title="Nueva pieza">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<form method="get" action="<?= site_url('silo') ?>" class="row g-2 mb-3">
    <div class="col-sm-6 col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Buscar por ID o nombre de carpeta..."
               value="<?= esc($filtros['q'] ?? '') ?>">
    </div>
    <div class="col-sm-6 col-md-3">
        <select name="categoria_id" class="form-select">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (string) ($filtros['categoria_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary">Buscar</button>
    </div>
</form>

<?= $this->include('silo/_listado_piezas') ?>

<?= $this->endSection() ?>
