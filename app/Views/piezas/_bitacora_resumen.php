<?php
/**
 * Vistazo rápido de la bitácora para el modal del histórico (fase 48,
 * afinado fase 49): solo lectura — piezas impresas, foto, fecha, tiempo,
 * estado, notas y conclusiones, condensado para enterarse de un vistazo.
 * Editar de verdad es "Ver completa", en la botonera del pie del modal
 * (prestada de la tarjeta, ver _placa_tarjeta.php).
 */
$idPlaca = (int) $placa['id'];
$piezas = $piezas ?? [];
$imagenes = $imagenes ?? [];

$colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
$veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;

$duracion = static function ($minutos): ?string {
    if ($minutos === null) {
        return null;
    }
    $minutos = (int) $minutos;
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;

    return $h ? ($m ? "{$h}h {$m}min" : "{$h}h") : "{$m}min";
};
$tiempo = $duracion($placa['minutos_reales']);
?>

<div class="d-flex flex-column gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <?php if ($placa['veredicto'] && isset($veredictos[$placa['veredicto']])): ?>
                <span class="badge text-bg-<?= $colorVeredicto[$placa['veredicto']] ?? 'secondary' ?>">
                    <?= esc($veredictos[$placa['veredicto']]) ?>
                </span>
            <?php else: ?>
                <span class="badge bg-body-secondary text-body-secondary border">Sin juzgar</span>
            <?php endif; ?>
            <span class="fs-6 fw-semibold"><?= esc($placa['nombre']) ?></span>
        </div>
        <div class="small text-muted">
            <?php if ($placa['impresa_en']): ?>
                <i class="bi bi-calendar-check"></i> Impresa el <?= esc(date('d/m/Y H:i', strtotime($placa['impresa_en']))) ?>
            <?php else: ?>
                <i class="bi bi-calendar"></i> Sin imprimir todavía
            <?php endif; ?>
            <?php if ($tiempo): ?> · <i class="bi bi-clock"></i> <?= esc($tiempo) ?><?php endif; ?>
        </div>
    </div>

    <?php if ($imagenes !== []): ?>
        <img src="<?= imagen_pieza($imagenes[0], 'placa-imagen') ?>" class="rounded d-block"
            style="width: 100%; height: auto;" alt="Plataforma del laminador" loading="lazy">
    <?php endif; ?>

    <?php if ($piezas !== []): ?>
        <div>
            <div class="small fw-semibold text-body-secondary mb-2"><i class="bi bi-box"></i> Piezas impresas</div>
            <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                <?php foreach ($piezas as $p): ?>
                    <li class="d-flex align-items-center gap-2">
                        <?php if ($p['miniatura']): ?>
                            <img src="<?= $p['miniatura'] ?>" loading="lazy" alt=""
                                class="rounded border flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                        <?php endif; ?>
                        <span>
                            <?php if ($p['variante'] && $p['familia']): ?>
                                <?= esc($p['familia']['nombre']) ?> - <?= esc($p['variante']['nombre']) ?>
                            <?php else: ?>
                                <span class="text-muted">(esa pieza ya no existe)</span>
                            <?php endif; ?>
                            <?php if ((int) $p['fila']['cantidad'] > 1): ?>
                                <span class="text-muted">×<?= (int) $p['fila']['cantidad'] ?></span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (trim((string) $placa['notas']) !== ''): ?>
        <div>
            <div class="small fw-semibold text-body-secondary mb-1"><i class="bi bi-chat-left-text"></i> Notas</div>
            <p class="small bg-body-tertiary rounded p-2 mb-0" style="max-width: 60ch; line-height: 1.6;">
                <?= nl2br(esc((string) $placa['notas'])) ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (trim((string) $placa['conclusiones']) !== ''): ?>
        <div>
            <div class="small fw-semibold text-body-secondary mb-1"><i class="bi bi-flag"></i> Conclusiones</div>
            <p class="small bg-body-tertiary rounded p-2 mb-0" style="max-width: 60ch; line-height: 1.6;">
                <?= nl2br(esc((string) $placa['conclusiones'])) ?>
            </p>
        </div>
    <?php endif; ?>
</div>
