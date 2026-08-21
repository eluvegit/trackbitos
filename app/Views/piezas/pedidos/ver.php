<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/pedidos') ?>" class="text-decoration-none text-muted fw-normal">Pedidos</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pedido #<?= (int) $pedido['id'] ?></strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Origen: <?= esc($pedido['origen']) ?> · Creado: <?= esc($pedido['creado_en']) ?>
    <?php if ($pedido['notas']): ?> · Notas: <?= esc($pedido['notas']) ?><?php endif; ?>
</p>

<?php
    $etiquetas = ['nuevo' => 'Pendiente', 'en_produccion' => 'Produciendo', 'completado' => 'Hecho', 'cancelado' => 'Cancelado'];
    $colores   = ['nuevo' => 'primary', 'en_produccion' => 'warning', 'completado' => 'success', 'cancelado' => 'secondary'];
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($estados as $estado): ?>
        <?php $activo = $estado === $pedido['estado']; ?>
        <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/estado') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="estado" value="<?= esc($estado) ?>">
            <button type="submit" class="btn btn-sm rounded-pill <?= $activo ? 'btn-' . $colores[$estado] : 'btn-outline-' . $colores[$estado] ?>" <?= $activo ? 'disabled' : '' ?>>
                <?= $activo ? '<i class="bi bi-check-lg me-1"></i>' : '' ?><?= esc($etiquetas[$estado] ?? $estado) ?>
            </button>
        </form>
    <?php endforeach; ?>
</div>

<table class="table table-sm align-middle">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Cantidad</th>
            <th>Notas</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pedido['lineas'] as $linea): ?>
            <tr>
                <td><?= esc($linea['sku']) ?></td>
                <td><?= (int) $linea['cantidad'] ?></td>
                <td class="text-muted small"><?= esc($linea['notas'] ?? '') ?></td>
                <td>
                    <?php if ($linea['variante_id']): ?>
                        <a href="<?= site_url('piezas/variante/' . $linea['variante_id']) ?>" class="btn btn-sm btn-outline-primary">Ver pieza</a>
                    <?php else: ?>
                        <span class="text-muted small">pieza borrada</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->endSection() ?>
