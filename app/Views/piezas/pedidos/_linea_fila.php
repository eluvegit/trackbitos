<?php
/**
 * Una línea del pedido, en solo lectura: foto, nombre, progreso y el +/−
 * (lo que se usa de verdad junto a la impresora, en el móvil). Editar
 * pieza/cantidad/notas o borrar va en el panel común de ver.php, que se
 * rellena con los data-* de esta fila al tocar la foto o el nombre.
 *
 * Aparte para reusarla tal cual en las respuestas AJAX (añadir, editar,
 * ajustar completada): el servidor sigue decidiendo cómo se pinta.
 *
 * Espera $linea ya enriquecida (PedidosController::enriquecerLinea): con
 * nombreFamilia, nombreVariante y foto además de las columnas de la tabla.
 */
$cantidad    = (int) $linea['cantidad'];
$completadas = (int) $linea['cantidad_completada'];
$completa    = $completadas >= $cantidad;
$hecha       = !empty($linea['hecha']);
$pct         = $cantidad ? (int) round($completadas * 100 / $cantidad) : 0;

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
    data-url-variante="<?= $linea['variante_id'] ? site_url('piezas/variante/' . $linea['variante_id']) : '' ?>">

    <?php // Solo en modo "Ordenar" / "Seleccionar" (clases en #lista-lineas). ?>
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
                    <span class="badge rounded-pill text-bg-info align-middle" title="Aún no existe en el catálogo">futura</span>
                <?php endif; ?>
            </div>
            <?php if ($linea['sku'] || $linea['notas']): ?>
                <div class="linea-meta">
                    <?php if ($linea['sku']): ?><span class="font-monospace"><?= esc($linea['sku']) ?></span><?php endif; ?>
                    <?php if ($linea['sku'] && $linea['notas']): ?> · <?php endif; ?>
                    <?php if ($linea['notas']): ?><span class="fst-italic">«<?= esc($linea['notas']) ?>»</span><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="linea-pie">
                <div class="progress linea-progreso" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                </div>
                <?php // "Lista para imprimir": nota propia de preparación, distinta de
                      // cantidad_completada (piezas ya impresas y válidas). ?>
                <button type="button" class="chip-hecha<?= $hecha ? ' activo' : '' ?>" data-chip-hecha
                    title="Marca tuya: ya está lista para imprimir (no cuenta piezas)">
                    <i class="bi <?= $hecha ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                    <?= $hecha ? 'Lista' : 'Preparar' ?>
                </button>
            </div>
        </div>
    </div>

    <?php // A mano, sin cuadrar contra ninguna placa: una pieza puede salir mal
          // aunque esté impresa, y eso no le toca adivinarlo al sistema. ?>
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
