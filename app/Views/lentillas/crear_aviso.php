<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-bell-plus fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Crear nuevo aviso</h2>
</div>

<a href="<?= site_url('lentillas/avisos') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Avisos</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success"><?= session('message') ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('lentillas/avisos/crear') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="item" class="form-label">Elemento</label>
        <select name="item" id="item" class="form-select" required>
            <option value="">Seleccionar</option>
            <option value="lentillas">Lentillas (ambas)</option>
            <option value="estuche">Estuche</option>
            <option value="líquido">Líquido</option>
            <option value="presion">Presión del ojo</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="dias_maximos" class="form-label">Días máximos sin cambio</label>
        <input type="number" name="dias_maximos" id="dias_maximos" class="form-control" required min="1">
    </div>

    <button type="submit" class="btn btn-primary">Guardar Aviso</button>
</form>

<?= $this->endSection() ?>
