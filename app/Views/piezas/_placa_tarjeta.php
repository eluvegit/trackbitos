<?php
/**
 * Una tarjeta de placa, para el listado agrupado de piezas/placas.php.
 * Aparte y no inline porque el mismo marcado se repite en tres bloques
 * (guardadas / listas / impresas) y dentro de cada uno en varios grupos de
 * fecha — sin esto sería la misma pantalla de HTML copiada tres veces.
 *
 * Espera: $placa, $lista (piezasDeLaPlaca), $resumen (resumenConDatos),
 * $origenNombres (id => nombre de la placa origen).
 */
$disponibles = count(array_filter($lista, static fn($p) => $p['disponible']));
$idPlaca = (int) $placa['id'];
$idDetalle = 'detalle-placa-' . $idPlaca;
$fecha = strtotime($placa['creado_en']);
// Portada en tira baja (no cuadrada): la tarjeta es un lomo de archivador, no
// una foto de catálogo — con reconocerla basta. Hasta 4 miniaturas en fila;
// el detalle se ve en el modal.
$fotos = array_values(array_filter(array_column($lista, 'miniatura')));
?>
<div class="col" data-tarjeta-placa="<?= $idPlaca ?>">
    <div class="card shadow-sm h-100 user-select-none lomo-placa <?= $resumen['veredicto'] ? 'lomo-' . esc($resumen['veredicto'], 'attr') : '' ?>"
        style="cursor: pointer" data-abrir-placa="<?= $idDetalle ?>" data-placa="<?= $idPlaca ?>"
        title="Abrir la bitácora de esta placa">
        <?php if ($fotos): ?>
            <div class="d-flex" data-foto-placa="tarjeta"
                style="gap: 2px; height: 72px; overflow: hidden; background: rgba(127,127,127,.15);">
                <?php foreach (array_slice($fotos, 0, 4) as $foto): ?>
                    <img src="<?= $foto ?>" loading="lazy" alt=""
                        style="flex: 1 1 0; min-width: 0; height: 100%; object-fit: cover; display: block;">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="card-body p-2">
            <div class="small fw-semibold text-truncate" data-nombre-tarjeta
                title="<?= esc($placa['nombre'], 'attr') ?>"><?= esc($placa['nombre']) ?></div>
            <div class="d-flex align-items-center gap-2 text-muted" style="font-size: .75rem;">
                <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
                <span class="ms-auto"><?= count($lista) ?> pieza<?= count($lista) === 1 ? '' : 's' ?></span>
                <?php if ($disponibles < count($lista)): ?>
                    <i class="bi bi-exclamation-triangle text-warning"
                        title="Algún STL de esta placa ya no está disponible"></i>
                <?php endif; ?>
            </div>

            <?php // Procedencia: de qué placa se repite/reparte, y de qué pedido salió,
                  // si sale de alguno — solo dato, ningún cálculo detrás. ?>
            <?php if ($placa['origen_placa_id'] || $placa['pedido_id']): ?>
                <div class="text-muted text-truncate" style="font-size: .7rem;">
                    <?php if ($placa['origen_placa_id']): ?>
                        <i class="bi bi-signpost-split"></i>
                        <?= esc($origenNombres[(int) $placa['origen_placa_id']] ?? 'placa borrada') ?>
                    <?php endif; ?>
                    <?php if ($placa['pedido_id']): ?>
                        <a href="<?= site_url('piezas/pedido/' . (int) $placa['pedido_id']) ?>"
                            class="text-decoration-none text-muted" onclick="event.stopPropagation()">
                            <i class="bi bi-cart-check"></i> Pedido #<?= (int) $placa['pedido_id'] ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php // Segunda línea: en qué punto está el cuaderno. Lo que
                  // se busca aquí es qué queda por cerrar —una placa sin
                  // juzgar, o con preguntas escritas antes de imprimir a
                  // las que nadie volvió— sin tener que abrirlas una a una. ?>
            <div class="d-flex align-items-center gap-1 flex-wrap mt-1" style="font-size: .7rem;"
                data-estado-tarjeta>
                <?php
                    $veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;
                    $colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
                ?>
                <?php if ($resumen['veredicto'] && isset($veredictos[$resumen['veredicto']])): ?>
                    <span class="badge text-bg-<?= $colorVeredicto[$resumen['veredicto']] ?? 'secondary' ?>">
                        <?= esc($veredictos[$resumen['veredicto']]) ?>
                    </span>
                <?php elseif (!$resumen['anotada']): ?>
                    <span class="badge bg-body-secondary text-body-secondary border">sin anotar</span>
                <?php else: ?>
                    <span class="badge bg-body-secondary text-body-secondary border">sin juzgar</span>
                <?php endif; ?>

                <?php if ($resumen['sinResponder'] > 0): ?>
                    <span class="badge bg-body-secondary text-warning-emphasis border"
                        title="Preguntas que escribiste antes de imprimir y siguen sin respuesta">
                        <i class="bi bi-question-circle"></i> <?= (int) $resumen['sinResponder'] ?> sin responder
                    </span>
                <?php endif; ?>

                <?php if ($resumen['enlaces'] > 0): ?>
                    <span class="badge bg-body-secondary text-body-secondary border" title="Tiene enlaces guardados">
                        <i class="bi bi-link-45deg"></i> <?= (int) $resumen['enlaces'] ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php // Solo los botones viajan al modal, y siguen renderizados aquí
              // (ocultos) para que sus formularios lleven el CSRF de siempre
              // sin duplicar un modal por placa. El contenido —la bitácora—
              // se pide al abrir: son muchas placas y meter treinta
              // formularios completos en la página costaría más que todo lo
              // demás junto. ?>
        <div id="<?= $idDetalle ?>" class="d-none" data-nombre-placa="<?= esc($placa['nombre'], 'attr') ?>"
            data-montada="<?= esc(date('d/m/Y H:i', $fecha ?: time()), 'attr') ?>">
            <div class="d-flex flex-wrap gap-2" data-acciones-placa>
                <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" target="_blank" rel="noopener"
                    class="btn btn-sm btn-outline-info" title="La bitácora entera, para leerla de corrido">
                    <i class="bi bi-journal-text"></i> Ver limpio
                </a>
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

                <form method="post"
                    action="<?= site_url('piezas/placa/' . $idPlaca . '/borrar') ?>"
                    onsubmit="return confirm('¿Borrar «<?= esc($placa['nombre'], 'attr') ?>» del histórico? Los STL y versiones no se tocan, solo esta anotación.');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" title="Borrar del histórico">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
