<?php
/**
 * Tarjeta grande para el timeline de Impresas (fase 52): a diferencia de
 * _placa_tarjeta.php (compacta, en grid), esta vive en una sola columna
 * centrada con sitio de sobra, así que enseña más de un vistazo —foto
 * grande, qué llevaba, un adelanto de las conclusiones— sin tener que
 * abrir el modal. El modal se sigue pudiendo abrir igual (mismo mecanismo
 * de botones prestados), para el vistazo con notas completas o para editar.
 *
 * Espera las mismas variables que _placa_tarjeta.php: $placa, $lista,
 * $resumen, $origenNombres, $cuadrosPorPlaca, $gruposReparto,
 * $nombresPlacas, $sugerenciasReparto.
 */
$disponibles = count(array_filter($lista, static fn($p) => $p['disponible']));
$idPlaca = (int) $placa['id'];
$idDetalle = 'detalle-placa-' . $idPlaca;
$fecha = strtotime($placa['creado_en']);
$grupo = $gruposReparto[$idPlaca] ?? null;
// Con la cantidad de cada una a mano, no solo la URL: para el badge de
// unidades que lleva encima cada foto.
$fotos = array_values(array_filter($lista, static fn($p) => $p['miniatura']));
$veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;
$colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];

$totalPiezas = array_sum(array_map(static fn($p) => (int) $p['fila']['cantidad'], $lista));
$totalFallidas = array_sum(array_map(static fn($p) => min((int) $p['fila']['cantidad'], (int) ($p['fila']['fallidas'] ?? 0)), $lista));
$duracion = static function ($minutos): ?string {
    if ($minutos === null) {
        return null;
    }
    $minutos = (int) $minutos;
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;

    return $h ? ($m ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
};
$pesoFmt = static fn($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
$tiempoReal = $duracion($placa['minutos_reales']);
$resinaEstimada = $pesoFmt($placa['resina_estimada']);
?>
<div class="card shadow-sm mb-3 user-select-none lomo-placa <?= $resumen['veredicto'] ? 'lomo-' . esc($resumen['veredicto'], 'attr') : '' ?>"
    style="cursor: pointer" data-abrir-placa="<?= $idDetalle ?>" data-placa="<?= $idPlaca ?>"
    data-tarjeta-placa="<?= $idPlaca ?>" title="Abrir la bitácora de esta placa">
    <?php if ($fotos): ?>
        <div data-foto-placa="tarjeta"
            style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px; overflow: hidden; background: rgba(127,127,127,.15);">
            <?php foreach (array_slice($fotos, 0, 8) as $p): ?>
                <div class="position-relative">
                    <img src="<?= $p['miniatura'] ?>" loading="lazy" alt=""
                        style="width: 100%; aspect-ratio: 1 / 1; object-fit: cover; display: block;">
                    <?php if ((int) $p['fila']['cantidad'] > 1): ?>
                        <span class="badge bg-dark position-absolute bottom-0 end-0 m-1 opacity-75"
                            style="font-size: .65rem;">×<?= (int) $p['fila']['cantidad'] ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="card-body">
        <div class="d-flex align-items-start gap-2">
            <div class="flex-grow-1">
                <div class="fw-semibold" data-nombre-tarjeta title="<?= esc($placa['nombre'], 'attr') ?>">
                    <?= esc($placa['nombre']) ?>
                </div>
                <div class="text-muted small">
                    <?= $placa['impresa_en'] ? esc(date('d/m/Y H:i', strtotime($placa['impresa_en']))) : esc(date('d/m/Y H:i', $fecha ?: time())) ?>
                    <?php if ($disponibles < count($lista)): ?>
                        <i class="bi bi-exclamation-triangle text-warning ms-1"
                            title="Algún STL de esta placa ya no está disponible"></i>
                    <?php endif; ?>
                </div>
                <?php // Muy pequeños a propósito: son de apoyo, no el dato
                      // principal de la tarjeta — para eso está el veredicto. ?>
                <div class="d-flex gap-1 flex-wrap mt-1">
                    <?php if (count($lista) > 0): ?>
                        <span class="badge bg-body-secondary text-body-secondary border" style="font-size: .65rem;"
                            title="Piezas distintas en esta placa">
                            <i class="bi bi-boxes"></i> <?= count($lista) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($totalPiezas > 0): ?>
                        <span class="badge bg-body-secondary text-body-secondary border" style="font-size: .65rem;"
                            title="Piezas en total en esta placa">
                            <i class="bi bi-box"></i> <?= $totalPiezas ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($totalFallidas > 0): ?>
                        <span class="badge bg-body-secondary text-body-secondary border" style="font-size: .65rem;"
                            title="<?= $totalFallidas ?> fallidas · <?= max(0, $totalPiezas - $totalFallidas) ?> servibles">
                            <i class="bi bi-x-circle text-danger"></i> <?= $totalFallidas ?>
                            <i class="bi bi-check2-circle ms-1"></i> <?= max(0, $totalPiezas - $totalFallidas) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($tiempoReal !== null): ?>
                        <span class="badge bg-body-secondary text-body-secondary border" style="font-size: .65rem;"
                            title="Tiempo real de impresión">
                            <i class="bi bi-clock"></i> <?= esc($tiempoReal) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($resinaEstimada !== null): ?>
                        <span class="badge bg-body-secondary text-body-secondary border" style="font-size: .65rem;"
                            title="Resina estimada">
                            <i class="bi bi-droplet"></i> <?= esc($resinaEstimada) ?> g
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div data-estado-tarjeta class="d-flex gap-1 flex-wrap justify-content-end flex-shrink-0">
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
                        <i class="bi bi-question-circle"></i> <?= (int) $resumen['sinResponder'] ?>
                    </span>
                <?php endif; ?>
                <?php if ($resumen['enlaces'] > 0): ?>
                    <span class="badge bg-body-secondary text-body-secondary border" title="Tiene enlaces guardados">
                        <i class="bi bi-link-45deg"></i> <?= (int) $resumen['enlaces'] ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php // Procedencia y familia de reparto: igual que en la tarjeta compacta. ?>
        <?php if ($placa['origen_placa_id'] || $placa['pedido_id'] || $grupo): ?>
            <div class="text-muted small mt-1">
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
                <?php if ($grupo): ?>
                    <?php
                        $tono = ($grupo['raiz'] * 47) % 360;
                        $nombresHermanas = array_map(
                            static fn($hid) => $nombresPlacas[$hid] ?? 'placa borrada',
                            $grupo['hermanas']
                        );
                    ?>
                    <span title="Placa dividida — <?= esc(implode(', ', $nombresHermanas), 'attr') ?>">
                        <span class="d-inline-block" style="width:.6em;height:.6em;background:hsl(<?= $tono ?>,65%,45%);border-radius:2px;"></span>
                        Dividida (<?= count($grupo['hermanas']) + 1 ?> placas)
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php // Un adelanto de las conclusiones, si las hay: es lo que uno
              // quiere tener delante antes de decidir si repite la placa. ?>
        <?php if (trim((string) $placa['conclusiones']) !== ''): ?>
            <div class="small text-muted mt-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                <i class="bi bi-flag"></i> <?= esc($placa['conclusiones']) ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include APPPATH . 'Views/piezas/_placa_acciones.php'; ?>
</div>
