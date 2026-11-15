<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lentillas — resumen (estilo fv): días con las lentillas puestas, cambios
 * rápidos, stock, avisos y la graduación.
 *
 * @var int|null   $dias         días desde el último cambio de lentillas
 * @var bool       $mostrarAlerta
 * @var array|null $ultimoCambio ['fecha', 'dias']
 * @var array      $stockItems
 * @var int|null   $periodo      plazo en días del aviso de lentillas
 * @var array      $avisos       recordatorios de categoría «lentillas»
 * @var array      $ultimos      últimas sustituciones
 */
$pct   = $dias !== null && $periodo ? min(100, round($dias / $periodo * 100)) : 0;
$nivel = $dias === null || !$periodo ? '' : ($dias > $periodo ? 'bad' : ($dias >= $periodo * .85 ? 'warn' : ''));
$quedan = $dias !== null && $periodo ? $periodo - $dias : null;
$rapidas = [
    ['lentillas', 'Lentillas', 'bi-eye'],
    ['líquido', 'Líquido', 'bi-droplet'],
    ['estuche', 'Estuche', 'bi-briefcase'],
];
$iconoEl = static fn (string $e) => match (true) {
    str_contains($e, 'lentilla') => 'bi-eye',
    str_contains($e, 'quido')    => 'bi-droplet',
    str_contains($e, 'estuche')  => 'bi-briefcase',
    str_contains($e, 'presi')    => 'bi-activity',
    default                      => 'bi-arrow-repeat',
};
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'  => 'lentillas',
        'activa'  => 'resumen',
        'titulo'  => 'Lentillas',
        'clasica' => true,
    ]) ?>

    <div class="rc-cifras">
        <div class="rc-cifra">
            <span class="k"><i class="bi bi-eye"></i>Con estas lentillas</span>
            <span class="v <?= $nivel ? 'e-' . $nivel : '' ?>"><?= $dias === null ? '—' : $dias ?> <small><?= $dias === 1 ? 'día' : 'días' ?><?= $periodo ? ' de ' . $periodo : '' ?></small></span>
            <?php if ($periodo && $dias !== null): ?><div class="rc-barra <?= $nivel ?>"><i style="width:<?= $pct ?>%"></i></div><?php endif; ?>
            <span class="s">
                <?php if ($ultimoCambio): ?>
                    Último cambio: <?= rec_fecha($ultimoCambio['fecha']) ?><?= $quedan !== null ? ' · ' . ($quedan >= 0 ? 'quedan ' . $quedan . ' d' : 'pasado ' . abs($quedan) . ' d') : '' ?>
                <?php else: ?>
                    Sin cambios apuntados
                <?php endif; ?>
            </span>
        </div>
        <?php foreach ($stockItems as $s): ?>
            <?php $c = (int) $s['cantidad']; ?>
            <a class="rc-cifra" href="<?= site_url('lentillas/stock') ?>">
                <span class="k"><i class="bi bi-box-seam"></i><?= esc($s['item']) ?></span>
                <span class="v <?= $c === 0 ? 'e-bad' : ($c <= 2 ? 'e-warn' : '') ?>"><?= $c ?></span>
                <span class="s"><?= $c === 0 ? 'Sin stock' : ($c <= 2 ? 'Queda poco' : 'En stock') ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Hoy he cambiado…</h3></div>
        <div class="rc-rapidas">
            <?php foreach ($rapidas as [$el, $txt, $ico]): ?>
                <form method="post" action="<?= site_url('lentillas/sustituciones') ?>" onsubmit="return confirm('¿Apuntar hoy el cambio de <?= mb_strtolower($txt) ?>? Se descuenta del stock.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="elemento" value="<?= esc($el, 'attr') ?>">
                    <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
                    <button type="submit" class="fv-btn"><i class="bi <?= $ico ?>"></i><?= $txt ?></button>
                </form>
            <?php endforeach; ?>
            <a class="fv-btn" href="<?= site_url('lentillas/sustituciones') ?>"><i class="bi bi-calendar-plus"></i>Otro día u otra cosa</a>
        </div>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Avisos</h3><span class="cuenta"><?= count($avisos) ?></span><a class="fv-link der" href="<?= site_url('lentillas/avisos') ?>">Ver todos <i class="bi bi-arrow-right"></i></a></div>
        <?= view('recordatorios/_lista', ['recordatorios' => $avisos, 'volver' => 'lentillas', 'limite' => 4, 'vacio' => 'No hay avisos de lentillas todavía.']) ?>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Últimos cambios</h3><a class="fv-link der" href="<?= site_url('lentillas/sustituciones') ?>">Todos <i class="bi bi-arrow-right"></i></a></div>
        <?php if (!$ultimos): ?>
            <div class="fv-vacio rc-vacio">No hay cambios apuntados todavía.</div>
        <?php else: ?>
            <div class="rc-hist">
                <?php foreach ($ultimos as $s): ?>
                    <div class="rc-h">
                        <div class="rc-ico"><i class="bi <?= $iconoEl((string) $s['elemento']) ?>"></i></div>
                        <div class="rc-txt">
                            <span class="t"><?= esc(mb_strtoupper(mb_substr($s['elemento'], 0, 1)) . mb_substr($s['elemento'], 1)) ?></span>
                            <div class="rc-meta"><span><i class="bi bi-calendar3"></i><?= rec_fecha($s['fecha']) ?> · <?= rec_hace($s['fecha']) ?></span><?php if (trim((string) $s['notas']) !== ''): ?><span><?= esc($s['notas']) ?></span><?php endif; ?></div>
                        </div>
                        <div class="rc-btns"><a class="fv-ibtn" href="<?= site_url('lentillas/sustituciones/editar/' . $s['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Graduación</h3></div>
        <div class="rc-duo">
            <div class="fv-panel">
                <div class="fv-ph"><h3><i class="bi bi-eyeglasses"></i> Gafas</h3></div>
                <table class="rc-tabla">
                    <thead><tr><th></th><th>OI</th><th>OD</th></tr></thead>
                    <tbody><tr><td>Esfera</td><td><?= esc($datos_medicos['graduacion_gaf_izq'] ?? '-6.5') ?></td><td><?= esc($datos_medicos['graduacion_gaf_der'] ?? '-7') ?></td></tr></tbody>
                </table>
            </div>
            <div class="fv-panel">
                <div class="fv-ph"><h3><i class="bi bi-eye"></i> Lentillas</h3></div>
                <table class="rc-tabla">
                    <thead><tr><th></th><th>OI</th><th>OD</th></tr></thead>
                    <tbody>
                        <tr><td>Esfera</td><td><?= esc($datos_medicos['graduacion_lent_izq'] ?? '-6.00') ?></td><td><?= esc($datos_medicos['graduacion_lent_der'] ?? '-6.50') ?></td></tr>
                        <tr><td>Radio</td><td><?= esc($datos_medicos['radio_curvatura_izq'] ?? '8.60') ?></td><td><?= esc($datos_medicos['radio_curvatura_der'] ?? '8.60') ?></td></tr>
                        <tr><td>Diámetro</td><td><?= esc($datos_medicos['diametro_izq'] ?? '14.20') ?></td><td><?= esc($datos_medicos['diametro_der'] ?? '14.20') ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
