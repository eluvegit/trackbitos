<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle fs-4 me-2 text-danger"></i>Coche <span class="text-muted">/</span> <span class="text-secondary">Averías</span></h2>
</div>

<a href="<?= site_url('coche/') ?>" class="btn btn-outline-secondary mb-4">&larr; Volver</a>
<a href="<?= site_url('coche/averias/nueva') ?>" class="btn btn-outline-success mb-4">Nueva avería</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('message') ?>
    </div>
<?php endif; ?>

<?php if (empty($averias)): ?>
    <p class="text-muted">No hay averías registradas aún.</p>
<?php endif; ?>

<div class="row">
    <?php foreach ($averias as $a): ?>
        <?php
            $fecha = \CodeIgniter\I18n\Time::parse($a['date']);
            $formateada = $fecha->toLocalizedString('d MMMM y');
            $hace = $fecha->humanize();
        ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-1"><?= esc($formateada) ?> (<?= esc($hace) ?>)</h5>
                    <p class="mb-1">Km: <?= esc($a['kilometers']) ?></p>
                    <p class="text-muted small"><?= esc($a['notes']) ?></p>
                    <div class="mt-2 d-flex gap-2">
                        <a href="<?= site_url('coche/averias/editar/' . $a['id']) ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <a href="<?= site_url('coche/averias/borrar/' . $a['id']) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Borrar esta avería?')">Borrar</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
