<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-geo-alt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="text-decoration-none text-muted fw-normal">Ubicaciones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($codigo) ?></strong>

    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/borrar') ?>" class="ms-auto"
        onsubmit="return confirm('¿Borrar el hueco «<?= esc($codigo, 'js') ?>»? Lo que había aquí queda sin ubicación, no se pierde.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Borrar hueco</button>
    </form>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if ($estuche): ?>
    <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
        <span><i class="bi bi-archive"></i> Estuche <?= esc($estuche['codigo']) ?></span>
        <?php if ($estuche['zona']): ?><span><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span><?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($hueco['notas']): ?>
    <p class="text-muted small"><?= nl2br(esc($hueco['notas'])) ?></p>
<?php endif; ?>

<div class="small fw-semibold text-body-secondary mb-1"><i class="bi bi-boxes"></i> Contenido</div>
<?php if (empty($filas)): ?>
    <p class="text-muted small">Vacío por ahora.</p>
<?php else: ?>
    <div class="list-group list-group-flush mb-1" style="max-width: 26rem;">
        <?php foreach ($filas as $f): ?>
            <a href="<?= site_url('piezas/existencias/' . (int) $f['variante']['id']) ?>"
                class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0 py-2">
                <i class="bi bi-box-seam text-muted"></i>
                <?= esc($f['nombre']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($destinos)): ?>
    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/mover') ?>"
        class="d-flex flex-wrap gap-2 align-items-center mt-3">
        <?= csrf_field() ?>
        <span class="text-muted small"><i class="bi bi-arrow-right-circle"></i> Mover todo lo de aquí a</span>
        <select name="destino_id" required class="form-select form-select-sm" style="max-width: 12rem;">
            <option value="">Elige hueco…</option>
            <?php foreach ($destinos as $destId => $destCodigo): ?>
                <option value="<?= $destId ?>"><?= esc($destCodigo) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Mover</button>
        <span class="text-muted small">Para fusionar dos huecos: mueve lo de aquí y luego borra el que quede vacío.</span>
    </form>
<?php endif; ?>

<?= $this->endSection() ?>
