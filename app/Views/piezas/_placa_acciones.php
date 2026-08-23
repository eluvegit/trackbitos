<?php
/**
 * El bloque de botones de una placa (borrar, descargar, cargar, repartir,
 * deshacer reparto, ver completa), oculto y compartido por las dos vistas
 * de tarjeta (_placa_tarjeta.php en grid, _placa_tarjeta_grande.php en el
 * timeline de Impresas) — el modal global de placas.php se lo "presta"
 * al abrirse, así que solo tiene que existir una vez por placa, no una
 * copia por tipo de tarjeta.
 *
 * Espera: $placa, $lista, $idPlaca, $idDetalle, $fecha, $origenNombres,
 * $sugerenciasReparto — las mismas variables que ya están en el scope de
 * quien la incluye.
 */
?>
<div id="<?= $idDetalle ?>" class="d-none" data-nombre-placa="<?= esc($placa['nombre'], 'attr') ?>"
    data-montada="<?= esc(date('d/m/Y H:i', $fecha ?: time()), 'attr') ?>">
    <?php // Borrar, marginado a la izquierda; el resto de acciones a la
          // derecha, con "Ver completa" al final — es la que se usa
          // para editar de verdad, y la última que se pulsa. ?>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center w-100" data-acciones-placa>
        <form method="post"
            action="<?= site_url('piezas/placa/' . $idPlaca . '/borrar') ?>"
            onsubmit="return confirm('¿Borrar «<?= esc($placa['nombre'], 'attr') ?>» del histórico? Los STL y versiones no se tocan, solo esta anotación.');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger" title="Borrar del histórico">
                <i class="bi bi-trash"></i>
            </button>
        </form>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/descargar') ?>"
                class="btn btn-sm btn-outline-primary" title="Volver a generar el zip con lo que haya ahora mismo">
                <i class="bi bi-download"></i> Descargar de nuevo
            </a>
            <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/cargar') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary" title="Sustituye lo que haya ahora en la placa actual">
                    <i class="bi bi-arrow-return-left"></i> Cargar en la placa actual
                </button>
            </form>

            <?php // No cupo entera: mueve las piezas marcadas a una placa nueva,
                  // enlazada a esta como origen. Solo tiene sentido antes de
                  // imprimir — una vez montada, ya no hay nada que repartir. ?>
            <?php if (!$placa['impresa_en'] && count($lista) > 1): ?>
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" title="No cupo entera en la plataforma">
                        <i class="bi bi-signpost-split"></i> Repartir
                    </button>
                    <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/repartir') ?>"
                        class="dropdown-menu p-2" style="min-width: 240px; max-height: 260px; overflow-y: auto;">
                        <?= csrf_field() ?>
                        <?php
                            $copiasFuera = $sugerenciasReparto[$idPlaca] ?? [];
                        ?>
                        <div class="small text-muted mb-1">
                            Cuántas copias de cada una se van a una placa nueva:
                            <?php if ($copiasFuera !== []): ?>
                                <span title="Ya viene puesta la cantidad que sobra de la primera placa según el reparto por cuadrículas — sigue siendo editable">
                                    (cantidad sugerida según el cálculo de reparto)
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php foreach ($lista as $p): ?>
                            <?php
                                $filaId = (int) $p['fila']['id'];
                                $cantidadFila = (int) $p['fila']['cantidad'];
                                $sugerida = $copiasFuera[$filaId] ?? 0;
                            ?>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <input type="number" name="cantidades[<?= $filaId ?>]" min="0" max="<?= $cantidadFila ?>"
                                    value="<?= $sugerida ?>" class="form-control form-control-sm py-0 px-1" style="width: 3.2em;"
                                    title="Cuántas de las <?= $cantidadFila ?> copias se mueven">
                                <label class="form-check-label small">
                                    <?= esc($p['familia']['nombre'] ?? '?') ?> · <?= esc($p['variante']['nombre'] ?? '?') ?><?= $cantidadFila > 1 ? ' (de ' . $cantidadFila . ')' : '' ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-sm btn-primary mt-1 w-100">Repartir seleccionadas</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php // Deshacer un reparto: junta esta placa de vuelta con
                  // la que la originó y la borra. Solo tiene sentido si
                  // nació de un "Repartir en otra placa" (no de "Cargar
                  // en la placa actual") y esa origen sigue existiendo. ?>
            <?php if (!empty($placa['es_reparto']) && $placa['origen_placa_id'] && isset($origenNombres[(int) $placa['origen_placa_id']])): ?>
                <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/deshacer-reparto') ?>"
                    onsubmit="return confirm('¿Deshacer el reparto? Esta placa se borra y sus piezas vuelven a «<?= esc($origenNombres[(int) $placa['origen_placa_id']], 'attr') ?>».');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-warning" title="Volver a juntar con la placa de origen">
                        <i class="bi bi-arrow-counterclockwise"></i> Deshacer reparto
                    </button>
                </form>
            <?php endif; ?>

            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>"
                class="btn btn-sm btn-primary" title="Editar piezas, pruebas, fotos y notas">
                <i class="bi bi-arrows-fullscreen"></i> Ver completa
            </a>
        </div>
    </div>
</div>
