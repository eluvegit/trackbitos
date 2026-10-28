<?php $color = $impresora === 'OLD' ? '#d97706' : '#2563eb'; ?>
<div style="font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 16px;">
    <div style="background: <?= $color ?>; color: #fff; padding: 14px 18px; border-radius: 8px; margin: 0 0 20px;">
        <span style="font-size: 13px; letter-spacing: 2px; text-transform: uppercase;">Impresora</span><br>
        <strong style="font-size: 40px; letter-spacing: 4px;"><?= esc($impresora) ?></strong>
    </div>
    <p style="font-style: italic; margin: 0 0 16px;"><?= esc($titulo) ?></p>
    <p style="margin: 0 0 24px;">
        Placa que dura <?= esc($duracion) ?> para ponerlo a las
        <strong style="font-size: 48px; vertical-align: middle;"><?= esc($hora) ?></strong>
    </p>
    <p style="margin: 0 0 24px;"><a href="<?= esc($enlace, 'attr') ?>" style="color: #2e7d32;"><?= esc($enlace) ?></a></p>
    <?php if ($fichero !== ''): ?>
        <p style="margin: 0 0 16px;"><strong><em><?= esc($fichero) ?></em></strong></p>
    <?php endif; ?>
    <?php if ($cid): ?>
        <img src="cid:<?= esc($cid, 'attr') ?>" alt="Placa #<?= (int) $id ?>" style="max-width: 100%; height: auto; display: block;">
    <?php endif; ?>
</div>
