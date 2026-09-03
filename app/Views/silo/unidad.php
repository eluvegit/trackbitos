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

<?php
$vista   = $vista ?? 'lista';
$vistaQs = static fn ($v) => site_url('silo/unidades/' . $unidad['id']) . '?vista=' . $v;
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <a href="<?= site_url('silo/mi-pc') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div class="btn-group btn-group-sm" role="group" aria-label="Forma de ver las carpetas">
        <a href="<?= $vistaQs('lista') ?>"
           class="btn btn-outline-secondary <?= $vista === 'lista' ? 'active' : '' ?>" title="Listado">
            <i class="bi bi-list-ul"></i>
        </a>
        <a href="<?= $vistaQs('galeria') ?>"
           class="btn btn-outline-secondary <?= $vista === 'galeria' ? 'active' : '' ?>" title="Galería de carpetas">
            <i class="bi bi-grid-3x3-gap"></i>
        </a>
    </div>
</div>

<?= $this->include($vista === 'galeria' ? 'silo/_galeria_piezas' : 'silo/_listado_piezas') ?>

<?= $this->endSection() ?>
