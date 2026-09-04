<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_nivel') ?>

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
    <a href="<?= site_url('silo/mi-pc') ?>" class="text-decoration-none ms-1 text-muted" title="Mi PC">
        <i class="bi bi-pc-display"></i>
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

<?php
$vista   = $vista ?? 'lista';
$vistaQs = static fn ($v) => site_url('silo') . '?' . http_build_query(
    array_filter([
        'q'            => $filtros['q'] ?? null,
        'categoria_id' => $filtros['categoria_id'] ?? null,
    ]) + ['vista' => $v]
);
?>
<div class="d-flex justify-content-end mb-2">
    <div class="btn-group btn-group-sm" role="group" aria-label="Forma de ver las carpetas">
        <a href="<?= esc($vistaQs('lista'), 'attr') ?>"
           class="btn btn-outline-secondary <?= $vista === 'lista' ? 'active' : '' ?>" title="Listado">
            <i class="bi bi-list-ul"></i>
        </a>
        <a href="<?= esc($vistaQs('galeria'), 'attr') ?>"
           class="btn btn-outline-secondary <?= $vista === 'galeria' ? 'active' : '' ?>" title="Galería de carpetas">
            <i class="bi bi-grid-3x3-gap"></i>
        </a>
    </div>
</div>

<?= $this->include($vista === 'galeria' ? 'silo/_galeria_piezas' : 'silo/_listado_piezas') ?>

<?= $this->endSection() ?>
