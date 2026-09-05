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
$vista = $vista ?? 'lista';
$orden = $orden ?? 'nombre';
// Conserva siempre el otro parámetro (vista/orden son independientes).
$qs = static fn (array $overrides) => site_url('silo/unidades/' . $unidad['id'])
    . '?' . http_build_query(array_merge(['vista' => $vista, 'orden' => $orden], $overrides));
?>
<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= site_url('silo/mi-pc') ?>" class="btn btn-sm btn-outline-secondary" title="Subir">
            <i class="bi bi-arrow-90deg-up"></i> Subir
        </a>
        <span class="text-muted small d-flex align-items-center gap-1">
            a <i class="bi bi-pc-display"></i> Mi PC
        </span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group" aria-label="Orden de las carpetas">
            <a href="<?= $qs(['orden' => 'nombre']) ?>"
               class="btn btn-outline-secondary <?= $orden === 'nombre' ? 'active' : '' ?>" title="Orden de alta (nombre)">
                <i class="bi bi-sort-numeric-down"></i>
            </a>
            <a href="<?= $qs(['orden' => 'fecha']) ?>"
               class="btn btn-outline-secondary <?= $orden === 'fecha' ? 'active' : '' ?>" title="Ordenar por fecha">
                <i class="bi bi-calendar3"></i>
            </a>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Forma de ver las carpetas">
            <a href="<?= $qs(['vista' => 'lista']) ?>"
               class="btn btn-outline-secondary <?= $vista === 'lista' ? 'active' : '' ?>" title="Listado">
                <i class="bi bi-list-ul"></i>
            </a>
            <a href="<?= $qs(['vista' => 'galeria']) ?>"
               class="btn btn-outline-secondary <?= $vista === 'galeria' ? 'active' : '' ?>" title="Galería de carpetas">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>
</div>

<?= $this->include($vista === 'galeria' ? 'silo/_galeria_piezas' : 'silo/_listado_piezas') ?>

</div>

<?= $this->endSection() ?>
