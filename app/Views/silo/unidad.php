<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php $nivelLabel = [1 => 'Maestro', 2 => 'Año', 3 => 'Temática']; ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-hdd text-primary"></i>
    <a href="<?= site_url('silo/unidades') ?>" class="text-decoration-none text-muted fw-normal">Unidades</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">
        Nivel <?= (int) $unidad['nivel'] ?> (<?= $nivelLabel[(int) $unidad['nivel']] ?? '' ?>) #<?= (int) $unidad['numero'] ?>
        <?php if ($unidad['etiqueta']): ?> — <?= esc($unidad['etiqueta']) ?><?php endif; ?>
    </strong>
    <?php if ((int) $unidad['sellada']): ?>
        <span class="badge text-bg-secondary">sellada</span>
    <?php endif; ?>
</h5>

<?= $this->include('silo/_listado_piezas') ?>

<?= $this->endSection() ?>
