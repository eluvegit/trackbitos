<?php
    /**
     * Línea de stock de una pieza del TPV: el total en grande (lo que hay
     * físicamente) y, en pequeño, lo que aún no está en su hueco y las
     * unidades a las que les falta alguna parte.
     *
     * @var array $p pieza de InventarioCatalogo::piezas()
     */
    $incompletas = (int) ($p['incompletas'] ?? 0);
    $sueltas     = $p['sueltas'] ?? [];
?>
<span class="inv-stock <?= $p['stock'] > 0 ? '' : 'cero' ?>"><strong><?= (int) $p['stock'] ?></strong> uds.<?php if (($p['en_cajas'] ?? 0) > 0): ?> <small style="font-weight: 400; opacity: .75;" title="Aún no están guardadas en su hueco">(<?= (int) $p['en_cajas'] ?> en caja)</small><?php endif; ?>
<?php if ($incompletas > 0): ?>
    <small class="inv-partes" title="<?= esc('Les falta: ' . implode(', ', $p['faltan']) . ($sueltas !== [] ? '. Sueltas: ' . implode(', ', $sueltas) : ''), 'attr') ?>"><i class="bi bi-puzzle"></i> <?= $incompletas ?> incompleta<?= $incompletas === 1 ? '' : 's' ?> · falta <?= esc(implode(', ', $p['faltan'])) ?></small>
<?php elseif ($sueltas !== []): ?>
    <small class="inv-partes suelta" title="Partes sueltas, sin pieza completa"><i class="bi bi-boxes"></i> sueltas: <?= esc(implode(', ', $sueltas)) ?></small>
<?php endif; ?>
</span>
