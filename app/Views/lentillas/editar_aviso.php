<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-pencil-square fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Editar Aviso</h2>
</div>

<a href="<?= site_url('lentillas/avisos') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Avisos</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success"><?= session('message') ?></div>
<?php elseif (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>

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

    <div class="mb-3">
        <label for="periodo_dias" class="form-label">Días máximos sin cambio</label>
        <input type="number" name="periodo_dias" id="periodo_dias" class="form-control"
               value="<?= esc($aviso['periodo_dias']) ?>" required min="1">
    </div>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>

<?= $this->endSection() ?>
