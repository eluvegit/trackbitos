<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">Lentillas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('lentillas/avisos') ?>" class="text-muted text-decoration-none">Avisos</a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Editar</span>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-pencil-square"></i>
    </div>
    <div>
        <h2 class="mb-0">Editar aviso</h2>
        <small class="text-muted">Ajusta el elemento o el periodo de aviso</small>
    </div>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <div><?= session('message') ?></div>
    </div>
<?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div><?= session('error') ?></div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm lentillas-card">
    <div class="card-body p-4">
        <form method="post" action="<?= site_url('lentillas/avisos/actualizar/' . $aviso['id']) ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="item" class="form-label">Elemento</label>
                <select name="item" id="item" class="form-select" required>
                    <option value="">Seleccionar</option>
                    <?php
                    $opciones = ['lentillas', 'lentilla izquierda', 'lentilla derecha', 'estuche', 'líquido', 'presion'];
                    foreach ($opciones as $opcion): ?>
                        <option value="<?= $opcion ?>" <?= $aviso['item'] === $opcion ? 'selected' : '' ?>>
                            <?= ucfirst($opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="periodo_dias" class="form-label">Días máximos sin cambio</label>
                <input type="number" name="periodo_dias" id="periodo_dias" class="form-control"
                    value="<?= esc($aviso['periodo_dias']) ?>" required min="1">
            </div>

            <div class="text-end">
                <a href="<?= site_url('lentillas/avisos') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
