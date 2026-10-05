<?php
/**
 * Marcador del repaso. Lo grande es "hoy" y la racha; el total queda pequeño
 * a propósito (ver 1600 pendientes en grande agobia más que motiva).
 * Lo pinta repaso.php y lo devuelve `Enlaces::repasoRespuesta()` tras cada decisión.
 */
?>
<div class="rep-stat">
    <div class="rep-stat-num" data-rep-hoy><?= (int) $stats['hoy'] ?></div>
    <div class="rep-stat-label">hoy</div>
</div>
<div class="rep-stat <?= $stats['racha'] > 0 ? 'is-racha' : '' ?> <?= $stats['racha'] > 0 && !$stats['rachaHoy'] ? 'is-pendiente' : '' ?>">
    <?php if ($stats['racha'] > 0): ?>
        <div class="rep-stat-num">🔥 <?= (int) $stats['racha'] ?></div>
        <div class="rep-stat-label">
            <?= $stats['racha'] === 1 ? 'día' : 'días' ?> seguidos<?= $stats['rachaHoy'] ? '' : ' · uno hoy y sigue' ?>
        </div>
    <?php else: ?>
        <div class="rep-stat-num">🔥</div>
        <div class="rep-stat-label">empieza una racha</div>
    <?php endif; ?>
</div>
<div class="rep-stat-mini">
    <?php if ($stats['record'] > 0): ?><span>Récord <?= (int) $stats['record'] ?> d</span><?php endif; ?>
    <?php if ($stats['diasTotales'] > 0): ?><span><?= (int) $stats['diasTotales'] ?> días repasando</span><?php endif; ?>
    <span><?= (int) $stats['repasados'] ?> repasados · <?= (int) $stats['porRepasar'] ?> por repasar</span>
</div>
