<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lentillas — compras (estilo fv): apuntar una y el histórico con totales.
 *
 * @var array $compras de la más reciente a la más antigua
 */
$n = count($compras);
$total = array_sum(array_map(static fn ($c) => (float) $c['precio'], $compras));
$porAnio = [];
foreach ($compras as $c) {
    $y = substr((string) $c['fecha'], 0, 4);
    $porAnio[$y] = ($porAnio[$y] ?? 0) + (float) $c['precio'];
}
$euros = static fn (float $v) => number_format($v, 2, ',', '.') . ' €';
$icono = static fn (string $t) => ['Gafas' => 'bi-eyeglasses', 'Lentillas' => 'bi-eye', 'Líquido' => 'bi-droplet'][$t] ?? 'bi-bag';
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'  => 'lentillas',
        'activa'  => 'compras',
        'titulo'  => 'Compras',
        'clasica' => true,
        'sub'     => '<span>' . $n . ' ' . ($n === 1 ? 'compra' : 'compras') . '</span><span class="fv-dot"></span><span>' . $euros($total) . ' en total</span>',
    ]) ?>

    <form class="rc-form fv-panel" method="post" action="<?= site_url('lentillas/compras') ?>">
        <?= csrf_field() ?>
        <div class="fv-ph"><h3>Apuntar una compra</h3></div>
        <div class="rc-filas">
            <label class="rc-campo">
                <span>Qué</span>
                <select class="rc-sel" name="tipo" required>
                    <?php foreach (['Lentillas', 'Líquido', 'Gafas', 'Otro'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="rc-campo">
                <span>Precio (€)</span>
                <input type="number" class="fv-input" name="precio" step="0.01" min="0" inputmode="decimal" required>
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
            <span class="rc-info"><i class="bi bi-info-circle"></i><span>No suma al stock: actualízalo en «Stock».</span></span>
            <div class="der"><button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Apuntar</button></div>
        </div>
    </form>

    <?php if (!$compras): ?>
        <div class="fv-panel rc-vacio">No hay compras apuntadas todavía.</div>
    <?php else: ?>
        <div class="rc-hist">
            <?php $anio = null; ?>
            <?php foreach ($compras as $c): ?>
                <?php $y = substr((string) $c['fecha'], 0, 4); ?>
                <?php if ($y !== $anio): $anio = $y; ?><div class="rc-anio"><?= $y ?> · <?= $euros($porAnio[$y]) ?></div><?php endif; ?>
                <div class="rc-h">
                    <div class="rc-ico"><i class="bi <?= $icono((string) $c['tipo']) ?>"></i></div>
                    <div class="rc-txt">
                        <span class="t"><?= esc($c['tipo']) ?> <span class="rc-precio">· <?= $euros((float) $c['precio']) ?></span></span>
                        <div class="rc-meta">
                            <span><i class="bi bi-calendar3"></i><?= rec_fecha($c['fecha']) ?></span>
                            <?php if (trim((string) $c['notas']) !== ''): ?><span><?= esc($c['notas']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="rc-btns">
                        <a class="fv-ibtn" href="<?= site_url('lentillas/compras/editar/' . $c['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= site_url('lentillas/compras/eliminar/' . $c['id']) ?>" onsubmit="return confirm('¿Borrar esta compra?')">
                            <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE"><button type="submit" class="fv-ibtn" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
