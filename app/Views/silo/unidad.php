<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_nivel') ?>

<?php $nivelLabel = [1 => 'Maestro', 2 => 'Año', 3 => 'Temática']; ?>

<div class="silo-nivel silo-n<?= (int) $unidad['nivel'] ?>">

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-hdd silo-hdd"></i>
    <a href="<?= site_url('silo/unidades') ?>" class="text-decoration-none text-muted fw-normal">Unidades</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">
        Nivel <?= (int) $unidad['nivel'] ?> (<?= $nivelLabel[(int) $unidad['nivel']] ?? '' ?>) #<?= (int) $unidad['numero'] ?>
        <?php if ($unidad['etiqueta']): ?> — <?= esc($unidad['etiqueta']) ?><?php endif; ?>
    </strong>
</h5>

<?php if (!empty($unidad['identificacion_fisica'])): ?>
    <p class="text-muted small d-flex gap-1 mb-3">
        <i class="bi bi-upc-scan flex-shrink-0 mt-1"></i>
        <span style="white-space: pre-line;"><?= esc($unidad['identificacion_fisica']) ?></span>
    </p>
<?php endif; ?>

<?php
$vista   = $vista ?? 'lista';
$vistaQs = static fn ($v) => site_url('silo/unidades/' . $unidad['id']) . '?vista=' . $v;
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= site_url('silo/mi-pc') ?>" class="btn btn-sm btn-outline-secondary" title="Subir">
            <i class="bi bi-arrow-90deg-up"></i> Subir
        </a>
        <span class="text-muted small d-flex align-items-center gap-1">
            a <i class="bi bi-pc-display"></i> Mi PC
        </span>
    </div>
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

</div>

<?= $this->endSection() ?>
