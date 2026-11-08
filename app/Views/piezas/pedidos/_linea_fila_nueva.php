<?php
$cantidad    = (int) $linea['cantidad'];
$completadas = (int) $linea['cantidad_completada'];
$completa    = $completadas >= $cantidad;
$hecha       = !empty($linea['hecha']);
$pct         = $cantidad ? (int) round(min($completadas, $cantidad) * 100 / $cantidad) : 0;

$textoCatalogo = $linea['nombreVariante'] ? $linea['nombreFamilia'] . ' · ' . $linea['nombreVariante'] : '';
$nombre = $textoCatalogo ?: ($linea['descripcion_libre'] ?: 'Pieza borrada');
?>
<div class="linea<?= $completa ? ' linea-completa' : '' ?><?= $hecha ? ' linea-lista' : '' ?>"
    data-linea-id="<?= (int) $linea['id'] ?>"
    data-cantidad="<?= $cantidad ?>" data-completada="<?= $completadas ?>" data-hecha="<?= $hecha ? 1 : 0 ?>"
    data-variante-id="<?= (int) ($linea['variante_id'] ?? 0) ?>"
    data-texto-catalogo="<?= esc($textoCatalogo, 'attr') ?>"
    data-descripcion="<?= esc($linea['descripcion_libre'] ?? '', 'attr') ?>"
    data-notas="<?= esc($linea['notas'] ?? '', 'attr') ?>"
    data-url-editar="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/editar') ?>"
    data-url-borrar="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/borrar') ?>"
    data-url-hecha="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/hecha') ?>"
    data-url-crear="<?= $linea['variante_id'] ? '' : site_url('piezas/pedido-linea/' . $linea['id'] . '/crear-pieza') ?>"
    data-url-variante="<?= $linea['variante_id'] ? site_url('piezas/variante/' . $linea['variante_id']) : '' ?>">

    <span class="linea-asa" title="Arrastra para reordenar"><i class="bi bi-grip-vertical"></i></span>
    <input type="checkbox" class="form-check-input linea-check" name="lineas[]" value="<?= (int) $linea['id'] ?>"
        form="form-copiar-lineas" data-seleccion-copiar aria-label="Seleccionar línea">

    <div class="linea-cuerpo" role="button" tabindex="0" data-abrir-panel title="Editar línea">
        <?php if ($linea['foto']): ?>
            <img src="<?= esc($linea['foto'], 'attr') ?>" alt="" loading="lazy" class="linea-foto">
        <?php else: ?>
            <span class="linea-foto linea-foto-vacia"><i class="bi <?= $linea['variante_id'] ? 'bi-box' : 'bi-lightbulb' ?>"></i></span>
        <?php endif; ?>

        <div class="linea-texto">
            <div class="linea-nombre">
                <?= esc($nombre) ?>
                <?php if (!$linea['nombreVariante'] && !empty($linea['descripcion_libre'])): ?>
                    <span class="fv-estado e-info" title="Aún no existe en el catálogo">futura</span>
                <?php endif; ?>
            </div>
            <?php if ($linea['sku'] || $linea['notas']): ?>
                <div class="linea-meta">
                    <?php if ($linea['sku']): ?><span class="fv-mono"><?= esc($linea['sku']) ?></span><?php endif; ?>
                    <?php if ($linea['sku'] && $linea['notas']): ?> · <?php endif; ?>
                    <?php if ($linea['notas']): ?><span>«<?= esc($linea['notas']) ?>»</span><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="linea-pie">
                <span class="pp-barra-p" aria-hidden="true"><i style="width: <?= $pct ?>%"></i></span>
                <button type="button" class="chip-hecha<?= $hecha ? ' activo' : '' ?>" data-chip-hecha
                    title="Marca tuya: ya está lista para imprimir (no cuenta piezas)">
                    <i class="bi <?= $hecha ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                    <?= $hecha ? 'Lista' : 'Preparar' ?>
                </button>
                <?php if (!$linea['variante_id']): ?>
                    <button type="button" class="chip-hecha chip-crear" data-chip-crear
                        title="Darla de alta en el catálogo (nueva o derivada de otra)">
                        <i class="bi bi-plus-circle"></i> Crear pieza
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="linea-stepper">
        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>" data-form-completada>
            <?= csrf_field() ?>
            <input type="hidden" name="delta" value="-1">
            <button class="stepper-btn" title="Una menos" <?= $completadas <= 0 ? 'disabled' : '' ?>><i class="bi bi-dash-lg"></i></button>
        </form>
        <span class="stepper-valor"><strong><?= $completadas ?></strong><span>/<?= $cantidad ?></span></span>
        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>" data-form-completada>
            <?= csrf_field() ?>
            <input type="hidden" name="delta" value="1">
            <?php if ($completa): ?>
                <span class="stepper-btn stepper-ok" title="Completa"><i class="bi bi-check-lg"></i></span>
            <?php else: ?>
                <button class="stepper-btn" title="Una más"><i class="bi bi-plus-lg"></i></button>
            <?php endif; ?>
        </form>
    </div>
</div>
