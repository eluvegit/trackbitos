<?php
/**
 * Sidebar de reparto/embalaje de una placa (fase 47): cuántas piezas caben,
 * si hace falta repartir en más de una, y el aviso de STL sin medir. Vista
 * propia (antes embebida en bitacora_editar.php) para poder repintarla por
 * AJAX tras pulsar "Guardar" sin recargar la página entera — ver
 * Web::bitacoraGuardar() y el submit del formulario en _bitacora_js.php.
 */
$idPlaca = (int) $placa['id'];
$reparto = $reparto ?? [];
$repartoBins = $reparto['bins'] ?? [];
$piezasPorSuperficie = $reparto['piezasPrimeraPlacaPorSuperficie'] ?? null;
$piezasConMargen = $reparto['piezasPrimeraPlacaConMargen'] ?? null;
$sinMedir = $sinMedir ?? 0;
$piezas = $piezas ?? [];
$sugerenciaReparto = $sugerenciaReparto ?? [];
?>
<div data-panel-reparto>
<?php if ($repartoBins !== [] || $sinMedir > 0): ?>
    <div data-reparto>
        <?php if ($piezasPorSuperficie !== null && $piezasConMargen !== null): ?>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <div class="card border-success-subtle h-100">
                        <div class="card-body p-2">
                            <div class="text-success-emphasis small fw-semibold mb-1">
                                <i class="bi bi-graph-up-arrow"></i> Optimista
                            </div>
                            <div class="fs-4 fw-bold lh-1"><?= $piezasPorSuperficie ?></div>
                            <div class="text-muted" style="font-size: .72rem;">piezas por placa, solo por superficie</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-warning-subtle h-100">
                        <div class="card-body p-2">
                            <div class="text-warning-emphasis small fw-semibold mb-1">
                                <i class="bi bi-shield-check"></i> Conservadora
                            </div>
                            <div class="fs-4 fw-bold lh-1"><?= $piezasConMargen ?></div>
                            <div class="text-muted" style="font-size: .72rem;">con 10% de margen de seguridad</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 bg-body-secondary mb-2">
                <div class="card-body p-2 small text-muted">
                    <i class="bi bi-info-circle"></i>
                    <strong>Optimista</strong>: superficie de la placa entre superficie de la pieza,
                    sin dejar hueco real entre piezas — el máximo teórico.
                    <strong>Conservadora</strong>: la misma cuenta pero reservando un 10% de la placa
                    para los huecos reales entre piezas. Ninguna de las dos es un anidado real como el
                    del laminador (que puede aprovechar la silueta de cada pieza, no solo su caja
                    rectangular) — la cifra de verdad suele caer entre las dos.
                </div>
            </div>
        <?php endif; ?>

        <div class="alert <?= count($repartoBins) > 1 ? 'alert-info' : 'alert-secondary' ?> py-2 mb-0 small">
            <?php if (count($repartoBins) <= 1): ?>
                <i class="bi bi-grid-3x3"></i> Cabe en <strong>una placa</strong>
                (<?= round($repartoBins[0]['porcentajeUsado'] ?? 0) ?>% ocupada).
            <?php else: ?>
                <i class="bi bi-grid-3x3"></i> No cabe en una placa, pero sí en
                <strong><?= count($repartoBins) ?></strong> (versión conservadora):
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($repartoBins as $i => $bin): ?>
                        <li>
                            Placa <?= $i + 1 ?> (<?= round($bin['porcentajeUsado']) ?>%):
                            <?= implode(', ', array_map(
                                static fn(array $p) => esc($p['etiqueta']) . ($p['cantidad'] > 1 ? ' ×' . $p['cantidad'] : ''),
                                $bin['piezas']
                            )) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($sinMedir > 0): ?>
                <div class="text-warning-emphasis mt-1"><?= $sinMedir ?> STL sin medir, no entran en la cuenta.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php // No cupo entera: mueve las piezas marcadas a una placa
          // nueva, enlazada a esta como origen. Solo tiene sentido
          // antes de imprimir — una vez montada, ya no hay nada
          // que repartir. ?>
    <?php if (count($repartoBins) > 1 && !$placa['impresa_en'] && count($piezas) > 1): ?>
        <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/repartir') ?>"
            class="border rounded p-2 mt-2 small">
            <?= csrf_field() ?>
            <div class="text-muted mb-1">
                <i class="bi bi-signpost-split"></i> Repartir: cuántas copias de cada una se van a una placa nueva
            </div>
            <?php foreach ($piezas as $p): ?>
                <?php
                    $filaId = (int) $p['fila']['id'];
                    $cantidadFila = (int) $p['fila']['cantidad'];
                    $sugerida = $sugerenciaReparto[$filaId] ?? 0;
                ?>
                <div class="d-flex align-items-center gap-1 mb-1">
                    <input type="number" name="cantidades[<?= $filaId ?>]" min="0" max="<?= $cantidadFila ?>"
                        value="<?= $sugerida ?>" class="form-control form-control-sm py-0 px-1" style="width: 3.2em;"
                        title="Cuántas de las <?= $cantidadFila ?> copias se mueven">
                    <label class="form-check-label">
                        <?= esc($p['familia']['nombre'] ?? '?') ?> · <?= esc($p['variante']['nombre'] ?? '?') ?><?= $cantidadFila > 1 ? ' (de ' . $cantidadFila . ')' : '' ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-sm btn-primary mt-1 w-100">Repartir seleccionadas</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
</div>
