<?php
$partes = [];
if ((int) $d['hueco'] !== 0) {
    $partes[] = (int) $d['hueco'] . (empty($conHueco) ? ' sin hueco' : ' en hueco');
}
if ((int) $d['impresas'] !== 0) {
    $partes[] = (int) $d['impresas'] . ' en caja impresas';
}
if ((int) $d['pedido'] !== 0) {
    $partes[] = (int) $d['pedido'] . ' en caja pedido';
}
?>
<?php if ($partes !== []): ?><span class="<?= esc($clase ?? 'small text-muted') ?>"><?= esc(implode(' · ', $partes)) ?></span><?php endif; ?>
