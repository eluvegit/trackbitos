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
        onsubmit="return confirm('¿Borrar el hueco «<?= esc($codigo, 'js') ?>»? El historial de stock que tenía queda sin asignar, no se pierde.');">
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
    <div class="table-responsive">
        <table class="table table-sm align-middle" style="font-size: .85rem;">
            <thead>
                <tr class="text-muted">
                    <th>Pieza</th>
                    <th class="text-end" style="width: 6rem;">Stock aquí</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('piezas/existencias/' . (int) $f['variante']['id']) ?>" class="text-decoration-none">
                                <?= esc($f['nombre']) ?>
                            </a>
                        </td>
                        <td class="text-end fw-semibold"><?= (int) $f['stock'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
