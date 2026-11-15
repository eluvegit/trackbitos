<?php
/**
 * Marcador del repaso (estilo fv). Lo grande es «hoy» y la racha; el total
 * va pequeño a propósito (ver 1600 pendientes en grande agobia).
 */
?>
<div class="en-rep-c">
    <span class="v" data-rep-hoy><?= (int) $stats['hoy'] ?></span>
    <span class="k">hoy</span>
</div>
<div class="en-rep-c<?= $stats['racha'] > 0 && !$stats['rachaHoy'] ? ' apagada' : '' ?>">
    <?php if ($stats['racha'] > 0): ?>
        <span class="v">🔥 <?= (int) $stats['racha'] ?></span>
        <span class="k"><?= $stats['racha'] === 1 ? 'día' : 'días' ?> seguidos<?= $stats['rachaHoy'] ? '' : ' · uno hoy y sigue' ?></span>
    <?php else: ?>
        <span class="v">🔥</span>
        <span class="k">empieza una racha</span>
    <?php endif; ?>
</div>
<div class="en-rep-mini">
    <?php if ($stats['record'] > 0): ?><span>Récord <?= (int) $stats['record'] ?> d</span><?php endif; ?>
    <?php if ($stats['diasTotales'] > 0): ?><span><?= (int) $stats['diasTotales'] ?> días repasando</span><?php endif; ?>
    <span><?= (int) $stats['repasados'] ?> repasados · <?= (int) $stats['porRepasar'] ?> por repasar</span>
</div>
