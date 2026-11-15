<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lentillas — stock (estilo fv): cantidades con +/− y un solo Guardar.
 *
 * @var array $items [['id', 'item', 'cantidad', 'updated_at'], ...]
 */
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'  => 'lentillas',
        'activa'  => 'stock',
        'titulo'  => 'Stock',
        'clasica' => true,
        'sub'     => '<span>Se descuenta solo al apuntar un cambio; aquí lo corriges o sumas lo que compres.</span>',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('lentillas/stock/actualizar') ?>">
        <?= csrf_field() ?>
        <div class="rc-stock">
            <?php foreach ($items as $it): ?>
                <?php $c = (int) $it['cantidad']; ?>
                <div class="rc-stock-item <?= $c === 0 ? 'cero' : ($c <= 2 ? 'bajo' : '') ?>">
                    <span class="k"><?= esc($it['item']) ?></span>
                    <div class="rc-paso">
                        <button type="button" data-paso="-1" aria-label="Menos">−</button>
                        <input type="number" class="fv-input" name="items[<?= (int) $it['id'] ?>]" value="<?= $c ?>" min="0" inputmode="numeric">
                        <button type="button" data-paso="1" aria-label="Más">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="rc-pie"><div class="der"><button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar stock</button></div></div>
    </form>
</div>
<script>
document.querySelectorAll('.rc-paso button').forEach(b => b.addEventListener('click', () => {
    const inp = b.parentElement.querySelector('input');
    inp.value = Math.max(0, (parseInt(inp.value, 10) || 0) + +b.dataset.paso);
}));
</script>
<?= $this->endSection() ?>
