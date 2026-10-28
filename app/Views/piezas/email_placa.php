<?php
// Tablas y estilos en línea: es lo único que respetan todos los clientes de correo.
$color = $impresora === 'OLD' ? '#b45309' : ($impresora === 'NEW' ? '#1d4ed8' : '#6b7280');
$suave = $impresora === 'OLD' ? '#fef3c7' : ($impresora === 'NEW' ? '#dbeafe' : '#f3f4f6');
$imp   = $impresora ?: '¿OLD o NEW?';
$letra = 'font-family: Arial, Helvetica, sans-serif;';
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin: 0; padding: 0; background: #f3f4f6;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f3f4f6;">
<tr><td align="center" style="padding: 24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background: #ffffff; border-radius: 14px; overflow: hidden; <?= $letra ?> color: #111827;">

    <tr><td style="background: <?= $color ?>; padding: 28px 24px; text-align: center; color: #ffffff;">
        <div style="font-size: 16px; letter-spacing: 3px; text-transform: uppercase; opacity: .9;">Impresora</div>
        <div style="font-size: 64px; font-weight: bold; letter-spacing: 8px; line-height: 1.1;"><?= esc($imp) ?></div>
        <div style="font-size: 18px; margin-top: 6px;">Placa #<?= (int) $id ?> · <?= esc($dia) ?></div>
    </td></tr>

    <tr><td style="padding: 28px 24px 8px; text-align: center;">
        <div style="font-size: 18px; color: #4b5563; text-transform: uppercase; letter-spacing: 2px;">Ponerla a las</div>
        <div style="font-size: 72px; font-weight: bold; line-height: 1.1; color: #111827;"><?= esc($hora) ?></div>
        <div style="font-size: 20px; color: #374151; margin-top: 6px;">
            Tarda <b><?= esc($duracion) ?></b><?= $fin ? ' · termina a las <b>' . esc($fin) . '</b>' : '' ?>
        </div>
    </td></tr>

    <tr><td align="center" style="padding: 24px 24px 8px;">
        <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="background: #15803d; border-radius: 10px;">
                <a href="<?= esc($enlace, 'attr') ?>" target="_blank"
                    style="display: inline-block; padding: 18px 36px; font-size: 22px; font-weight: bold; color: #ffffff; text-decoration: none; <?= $letra ?>">
                    &#11015;&nbsp; Descargar el fichero
                </a>
            </td>
        </tr></table>
    </td></tr>

    <?php if ($fichero !== ''): ?>
    <tr><td style="padding: 16px 24px 8px;">
        <div style="background: <?= $suave ?>; border-left: 6px solid <?= $color ?>; border-radius: 8px; padding: 14px 16px;">
            <div style="font-size: 15px; color: #4b5563;">Nombre del fichero</div>
            <div style="font-size: 20px; font-weight: bold; word-break: break-word; margin-top: 2px;"><?= esc($fichero) ?></div>
            <div style="font-size: 16px; color: <?= $color ?>; font-weight: bold; margin-top: 6px;">Va al USB de la impresora <?= esc($imp) ?></div>
        </div>
    </td></tr>
    <?php endif; ?>

    <tr><td style="padding: 20px 24px 8px;">
        <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">Qué hacer</div>
        <?php foreach ([
            'Pulsa el botón verde <b>Descargar el fichero</b>.',
            'Copia el fichero al <b>USB de la impresora ' . esc($imp) . '</b>.',
            'Pon la placa en la <b>' . esc($imp) . '</b> a las <b>' . esc($hora) . '</b>.',
        ] as $n => $paso): ?>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;"><tr>
                <td valign="top" style="width: 40px;">
                    <div style="width: 32px; height: 32px; line-height: 32px; border-radius: 16px; background: <?= $color ?>; color: #ffffff; text-align: center; font-size: 18px; font-weight: bold;"><?= $n + 1 ?></div>
                </td>
                <td style="font-size: 19px; line-height: 1.5; color: #1f2937;"><?= $paso ?></td>
            </tr></table>
        <?php endforeach; ?>
    </td></tr>

    <?php if ($imagen): ?>
    <tr><td style="padding: 16px 24px 8px;">
        <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">Así es la placa</div>
        <img src="<?= esc($imagen, 'attr') ?>" alt="Placa #<?= (int) $id ?>"
            style="width: 100%; max-width: 552px; height: auto; display: block; border-radius: 8px; border: 1px solid #e5e7eb;">
    </td></tr>
    <?php endif; ?>

    <tr><td style="padding: 20px 24px 24px; font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb;">
        Si el botón no funciona, abre este enlace:<br>
        <a href="<?= esc($enlace, 'attr') ?>" style="color: #15803d; word-break: break-all;"><?= esc($enlace) ?></a>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
