<?php
    $fecha = strtotime($pedido['creado_en']);
    $prioritario = (bool) (int) $pedido['prioritario'] && $pedido['estado'] !== 'completado';
    $entrega = \App\Models\PiezaPedidoModel::entrega($pedido);
    $tonoEntrega = $entrega ? (str_contains($entrega['clase'], 'danger') ? 'e-bad' : (str_contains($entrega['clase'], 'warning') ? 'e-warn' : 'e-neu')) : '';
    $tieneRef = !empty($pedido['referencia_externa']);
?>
<div class="tarjeta-pedido pp-card<?= $prioritario ? ' prioritario' : '' ?><?= $pedido['estado'] === 'completado' ? ' hecho' : '' ?><?= $pedido['estado'] === 'idea' ? ' idea' : '' ?>" data-id="<?= (int) $pedido['id'] ?>"
    data-prioritario="<?= $prioritario ? 1 : 0 ?>" data-entrega="<?= esc($pedido['fecha_entrega'] ?? '', 'attr') ?>">
    <?php if ($pedido['estado'] !== 'completado'): ?>
        <button type="button" class="pp-estrella" data-estrella title="<?= $prioritario ? 'Quitar prioridad' : 'Marcar como prioritario' ?>">
            <i class="bi <?= $prioritario ? 'bi-star-fill' : 'bi-star' ?>"></i>
        </button>
    <?php endif; ?>
    <a href="<?= site_url('piezas/pedido/' . $pedido['id']) ?>" class="pp-card-a" title="Ver pedido #<?= (int) $pedido['id'] ?>">
        <?php if ($pedido['fotos']): ?>
            <div class="pp-fotos">
                <?php foreach (array_slice($pedido['fotos'], 0, 4) as $foto): ?>
                    <img src="<?= $foto ?>" loading="lazy" alt="">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="pp-fotos vacia"><i class="bi bi-box"></i></div>
        <?php endif; ?>
        <div class="pp-card-b">
            <div class="pp-card-t"<?= $tieneRef ? ' title="' . esc($pedido['referencia_externa'], 'attr') . '"' : '' ?>><?= $tieneRef ? esc($pedido['referencia_externa']) : 'Pedido #' . (int) $pedido['id'] ?></div>
            <div class="pp-card-m">
                <?php if ($tieneRef): ?><span class="fv-mono">#<?= (int) $pedido['id'] ?></span><span class="fv-dot"></span><?php endif; ?>
                <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
                <?php if ($pedido['numLineas']): ?>
                    <span class="der<?= $pedido['lineasListas'] === $pedido['numLineas'] ? ' todas' : '' ?>" title="Piezas listas para imprimir del total del pedido"><?= $pedido['lineasListas'] ?>/<?= $pedido['numLineas'] ?> listas</span>
                <?php else: ?>
                    <span class="der">sin piezas</span>
                <?php endif; ?>
            </div>
            <div class="pp-ent"><?php if ($entrega): ?><span class="fv-estado <?= $tonoEntrega ?>" title="Fecha de entrega: <?= esc(date('d/m/Y', strtotime($pedido['fecha_entrega'])), 'attr') ?>">Entrega <?= esc($entrega['texto']) ?></span><?php endif; ?></div>
            <div class="pp-progw"><?php if ($pedido['totalPiezas']): ?><?php $pct = (int) round(min($pedido['piezasHechas'], $pedido['totalPiezas']) * 100 / $pedido['totalPiezas']); ?><div class="pp-prog" title="Unidades hechas del total"><span class="pp-barra-p"><i style="width: <?= $pct ?>%"></i></span><span class="fv-mono"><?= $pedido['piezasHechas'] ?>/<?= $pedido['totalPiezas'] ?> ud.</span></div><?php endif; ?></div>
            <div class="pp-card-n"<?= $pedido['notas'] ? ' title="' . esc($pedido['notas'], 'attr') . '"' : '' ?>><?= $pedido['notas'] ? esc($pedido['notas']) : '' ?></div>
        </div>
    </a>
</div>
