<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lentillas — cambios y revisiones (estilo fv): apuntar uno y el historial.
 *
 * @var array $sustituciones de la más reciente a la más antigua
 */
use App\Libraries\RecordatorioServicio;

$n = count($sustituciones);
$cambiosStock = session()->getFlashdata('stock_cambios') ?: [];
$nombre  = static fn (string $e) => mb_strtoupper(mb_substr($e, 0, 1)) . mb_substr($e, 1);
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
        'activa'  => 'cambios',
        'titulo'  => 'Cambios',
        'clasica' => true,
        'sub'     => '<span>' . $n . ' ' . ($n === 1 ? 'cambio apuntado' : 'cambios apuntados') . '</span>',
    ]) ?>

    <?php if ($cambiosStock): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-box-seam"></i>Stock:
            <?= esc(implode(' · ', array_map(static fn ($c) => $nombre($c['item']) . ' ' . $c['antes'] . ' → ' . $c['despues'], $cambiosStock))) ?>
        </div>
    <?php endif; ?>

    <form class="rc-form fv-panel" method="post" action="<?= site_url('lentillas/sustituciones') ?>">
        <?= csrf_field() ?>
        <div class="fv-ph"><h3>Apuntar un cambio</h3></div>
        <div class="rc-filas">
            <label class="rc-campo">
                <span>Qué</span>
                <select class="rc-sel" name="elemento" required>
                    <?php foreach (RecordatorioServicio::ELEMENTOS as $el): ?>
                        <option value="<?= esc($el, 'attr') ?>"><?= esc($nombre($el)) ?><?= $el === 'lentillas' ? ' (ambas)' : ($el === 'presión' ? ' de ojos' : '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="rc-campo">
                <span>Fecha</span>
                <input type="date" class="fv-input" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </label>
            <label class="rc-campo">
                <span>Notas <small>(opcional)</small></span>
                <input type="text" class="fv-input" name="notas" maxlength="255">
            </label>
        </div>
        <div class="rc-pie">
            <span class="rc-info"><i class="bi bi-info-circle"></i><span>Descuenta del stock y renueva el aviso de ese elemento.</span></span>
            <div class="der"><button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Apuntar</button></div>
        </div>
    </form>

    <?php if (!$sustituciones): ?>
        <div class="fv-panel rc-vacio">No hay cambios apuntados todavía.</div>
    <?php else: ?>
        <div class="rc-hist">
            <?php $anio = null; ?>
            <?php foreach ($sustituciones as $s): ?>
                <?php $y = substr((string) $s['fecha'], 0, 4); ?>
                <?php if ($y !== $anio): $anio = $y; ?><div class="rc-anio"><?= $y ?></div><?php endif; ?>
                <div class="rc-h">
                    <div class="rc-ico"><i class="bi <?= $iconoEl((string) $s['elemento']) ?>"></i></div>
                    <div class="rc-txt">
                        <span class="t"><?= esc($nombre((string) $s['elemento'])) ?></span>
                        <div class="rc-meta">
                            <span><i class="bi bi-calendar3"></i><?= rec_fecha($s['fecha']) ?> · <?= rec_hace($s['fecha']) ?></span>
                            <?php if (trim((string) $s['notas']) !== ''): ?><span><?= esc($s['notas']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="rc-btns">
                        <a class="fv-ibtn" href="<?= site_url('lentillas/sustituciones/editar/' . $s['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= site_url('lentillas/sustituciones/eliminar/' . $s['id']) ?>" onsubmit="return confirm('¿Borrar este cambio? El stock no se repone; el aviso se recalcula.')">
                            <?= csrf_field() ?><button type="submit" class="fv-ibtn" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
