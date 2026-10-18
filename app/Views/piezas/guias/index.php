<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-signpost-split text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Guías del TPV</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Asistentes paso a paso que salen en el lateral del <a href="<?= site_url('inventario') ?>" target="_blank">TPV inventario</a>
    para elegir la pieza correcta (p. ej. cogida → manga → talla). Solo se ven las guías activas, y en ellas
    solo las combinaciones con una pieza asignada.
</p>

<?php if ($filas === []): ?>
    <p class="text-muted">Todavía no hay guías.</p>
<?php else: ?>
    <div class="list-group mb-4" style="max-width: 720px;">
        <?php foreach ($filas as $f): ?>
            <a href="<?= site_url('piezas/guias/' . (int) $f['guia']['id']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                <?= icono_guia($f['guia']['icono'], 'fs-4 text-primary') ?>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= esc($f['guia']['nombre']) ?></div>
                    <div class="small text-muted">
                        <?= (int) $f['pasos'] ?> pasos · <?= (int) $f['asignadas'] ?> <?= $f['asignadas'] === 1 ? 'pieza' : 'piezas' ?>
                    </div>
                </div>
                <?php if ($f['guia']['activa']): ?>
                    <span class="badge text-bg-success">Activa</span>
                <?php else: ?>
                    <span class="badge text-bg-secondary">Oculta</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('piezas/guias/crear') ?>" class="d-flex flex-wrap gap-2 align-items-center" style="max-width: 720px;">
    <?= csrf_field() ?>
    <input type="text" name="nombre" class="form-control form-control-sm" style="max-width: 18rem;" placeholder="Nombre de la guía nueva" required>
    <input type="text" name="icono" class="form-control form-control-sm" style="max-width: 12rem;" placeholder="Icono (bi-… o svg-brazo)" value="bi-signpost-split">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Crear guía</button>
</form>

<?= $this->endSection() ?>
