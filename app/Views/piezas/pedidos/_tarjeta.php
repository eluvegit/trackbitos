<?php
/**
 * Tarjeta de un pedido en el tablero (pedidos/index). También la usa
 * PedidosController::masHechos para las tandas de "Hecho" que se cargan al bajar.
 */
?>
<?php
    $fecha = strtotime($pedido['creado_en']);
    $prioritario = (bool) (int) $pedido['prioritario'] && $pedido['estado'] !== 'completado';
    $entrega = \App\Models\PiezaPedidoModel::entrega($pedido);
?>
<div class="tarjeta-pedido <?= $prioritario ? 'prioritario' : '' ?>" data-id="<?= (int) $pedido['id'] ?>"
    data-prioritario="<?= $prioritario ? 1 : 0 ?>" data-entrega="<?= esc($pedido['fecha_entrega'] ?? '', 'attr') ?>">
<?php // Prioridad y orden solo pintan mientras el pedido está abierto. ?>
<?php if ($pedido['estado'] !== 'completado'): ?>
<button type="button" class="btn-estrella" data-estrella
    title="<?= $prioritario ? 'Quitar prioridad' : 'Marcar como prioritario' ?>">
    <i class="bi <?= $prioritario ? 'bi-star-fill' : 'bi-star' ?>"></i>
</button>
<?php endif; ?>
<a href="<?= site_url('piezas/pedido/' . $pedido['id']) ?>" class="card shadow-sm text-decoration-none text-body"
    style="cursor: pointer" title="Ver pedido #<?= $pedido['id'] ?>">
    <?php if ($pedido['fotos']): ?>
        <div class="d-flex" style="gap: 2px; height: 64px; overflow: hidden; background: rgba(127,127,127,.15);">
            <?php foreach (array_slice($pedido['fotos'], 0, 4) as $foto): ?>
                <img src="<?= $foto ?>" loading="lazy" alt=""
                    style="flex: 1 1 0; min-width: 0; height: 100%; object-fit: cover; display: block;">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="card-body p-2">
        <?php if (!empty($pedido['referencia_externa'])): ?>
            <div class="fw-semibold text-truncate pe-4" title="<?= esc($pedido['referencia_externa'], 'attr') ?>"><?= esc($pedido['referencia_externa']) ?></div>
            <div class="text-muted" style="font-size: .75rem;">Pedido #<?= (int) $pedido['id'] ?></div>
        <?php else: ?>
            <div class="small fw-semibold pe-4">Pedido #<?= (int) $pedido['id'] ?></div>
        <?php endif; ?>
        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: .75rem;">
            <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
            <?php if ($pedido['numLineas']): ?>
                <span class="ms-auto <?= $pedido['lineasListas'] === $pedido['numLineas'] ? 'text-success' : '' ?>"
                    title="Piezas listas para imprimir del total del pedido">
                    <strong><?= $pedido['lineasListas'] ?>/<?= $pedido['numLineas'] ?></strong> listas
                </span>
            <?php else: ?>
                <span class="ms-auto">sin piezas</span>
            <?php endif; ?>
        </div>
        <?php if ($entrega): ?>
            <div class="<?= $entrega['clase'] ?>" style="font-size: .75rem;" title="Fecha de entrega: <?= esc(date('d/m/Y', strtotime($pedido['fecha_entrega'])), 'attr') ?>">
                <i class="bi <?= $entrega['vencido'] ? 'bi-exclamation-triangle' : 'bi-truck' ?>"></i> Entrega <?= esc($entrega['texto']) ?>
            </div>
        <?php endif; ?>
        <?php if ($pedido['totalPiezas']): ?>
            <?php $pct = (int) round(min($pedido['piezasHechas'], $pedido['totalPiezas']) * 100 / $pedido['totalPiezas']); ?>
            <div class="d-flex align-items-center gap-2 mt-1" title="Unidades hechas del total">
                <div class="progress flex-grow-1" style="height: 4px;"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
                <span class="text-muted text-nowrap" style="font-size: .7rem;"><?= $pedido['piezasHechas'] ?>/<?= $pedido['totalPiezas'] ?> ud.</span>
            </div>
        <?php endif; ?>
        <?php if ($pedido['notas']): ?>
            <div class="text-muted text-truncate mt-1" style="font-size: .75rem;" title="<?= esc($pedido['notas'], 'attr') ?>">
                <?= esc($pedido['notas']) ?>
            </div>
        <?php endif; ?>
    </div>
</a>
</div>
