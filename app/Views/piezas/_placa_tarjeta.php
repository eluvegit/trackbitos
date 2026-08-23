<?php
/**
 * Una tarjeta de placa, para el listado agrupado de piezas/placas.php.
 * Aparte y no inline porque el mismo marcado se repite en tres bloques
 * (guardadas / listas / impresas) y dentro de cada uno en varios grupos de
 * fecha — sin esto sería la misma pantalla de HTML copiada tres veces.
 *
 * Espera: $placa, $lista (piezasDeLaPlaca), $resumen (resumenConDatos),
 * $origenNombres (id => nombre de la placa origen), $cuadrosPorPlaca
 * (id => ['usados' => int, 'sinMedir' => int]), $gruposReparto
 * (id => ['raiz' => int, 'hermanas' => list<int>], solo si tiene),
 * $nombresPlacas (id => nombre, para las hermanas).
 */
$disponibles = count(array_filter($lista, static fn($p) => $p['disponible']));
$idPlaca = (int) $placa['id'];
$idDetalle = 'detalle-placa-' . $idPlaca;
$fecha = strtotime($placa['creado_en']);
$cuadros = $cuadrosPorPlaca[$idPlaca] ?? ['usados' => 0, 'sinMedir' => 0];
$grupo = $gruposReparto[$idPlaca] ?? null;
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

            <?php // Vínculo entre placas nacidas de la misma división: un
                  // cuadradito de color (mismo tono para toda la familia,
                  // sacado del id de la raíz) más la lista de hermanas en el
                  // título, para reconocerlas de un vistazo aunque hayan
                  // caído en grupos de fecha distintos. ?>
            <?php if ($grupo): ?>
                <?php
                    $tono = ($grupo['raiz'] * 47) % 360;
                    $nombresHermanas = array_map(
                        static fn($hid) => $nombresPlacas[$hid] ?? 'placa borrada',
                        $grupo['hermanas']
                    );
                    $totalGrupo = count($grupo['hermanas']) + 1;
                ?>
                <div class="text-muted text-truncate" style="font-size: .7rem;"
                    title="Placa dividida — <?= esc(implode(', ', $nombresHermanas), 'attr') ?>">
                    <span class="d-inline-block" style="width:.6em;height:.6em;background:hsl(<?= $tono ?>,65%,45%);border-radius:2px;"></span>
                    Dividida (<?= $totalGrupo ?> placas)
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

                <?php // Cuánto lleva ESTA placa de la plataforma — no un
                      // reparto hipotético, lo que ya tiene puesto. Con esto
                      // no hace falta abrir cada hermana para saber qué le
                      // queda de sitio. ?>
                <?php if ($cuadros['porcentajeUsado'] > 0 || $cuadros['sinMedir'] > 0): ?>
                    <span class="badge bg-body-secondary text-body-secondary border"
                        title="Porcentaje ocupado de la plataforma<?= $cuadros['sinMedir'] > 0 ? ' (' . $cuadros['sinMedir'] . ' pieza(s) sin medir, no entran en la cuenta)' : '' ?>">
                        <i class="bi bi-grid-3x3-gap"></i> <?= round($cuadros['porcentajeUsado']) ?>%<?= $cuadros['sinMedir'] > 0 ? '+' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php // Solo los botones viajan al modal, y siguen renderizados aquí
              // (ocultos, en su propio parcial compartido) para que sus
              // formularios lleven el CSRF de siempre sin duplicar un modal
              // por placa. El contenido —la bitácora— se pide al abrir: son
              // muchas placas y meter treinta formularios completos en la
              // página costaría más que todo lo demás junto. ?>
        <?php include APPPATH . 'Views/piezas/_placa_acciones.php'; ?>
    </div>
</div>
