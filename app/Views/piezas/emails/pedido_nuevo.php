<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nuevo pedido de piezas</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.06);">

                <tr>
                    <td style="padding:32px 32px 8px 32px;">
                        <p style="margin:0 0 4px 0; font-size:12px; letter-spacing:0.06em; text-transform:uppercase; color:#94a3b8; font-weight:600;">Trackbitos · Piezas</p>
                        <h1 style="margin:0; font-size:20px; line-height:1.4; color:#0f172a; font-weight:600;">Tienes un nuevo pedido para revisar</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 32px 0 32px;">
                        <p style="margin:0; font-size:14px; line-height:1.6; color:#475569;">
                            Pedido <strong style="color:#0f172a;">#<?= esc($pedido['id']) ?></strong>
                            <?php if ($pedido['origen'] === 'sterclicks'): ?>
                                llegado desde sterclicks
                            <?php else: ?>
                                dado de alta a mano
                            <?php endif; ?>
                            <?php if (!empty($pedido['referencia_externa'])): ?>
                                · ref. <?= esc($pedido['referencia_externa']) ?>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 32px 0 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <?php foreach ($pedido['lineas'] as $linea): ?>
                                <tr>
                                    <td style="padding:10px 0; border-top:1px solid #eef0f3; font-size:14px; color:#0f172a;">
                                        <?= esc($linea['nombreVariante'] ?? 'Pieza sin nombre') ?>
                                        <?php if (!empty($linea['nombreFamilia'])): ?>
                                            <br><span style="font-size:12px; color:#94a3b8;"><?= esc($linea['nombreFamilia']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:10px 0; border-top:1px solid #eef0f3; font-size:14px; color:#475569; text-align:right; white-space:nowrap;">
                                        &times;&nbsp;<?= (int) $linea['cantidad'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:14px 32px 0 32px;">
                        <p style="margin:0; font-size:13px; color:#94a3b8;">
                            <?= count($pedido['lineas']) ?> línea<?= count($pedido['lineas']) === 1 ? '' : 's' ?> · <?= (int) $totalPiezas ?> pieza<?= $totalPiezas === 1 ? '' : 's' ?> en total
                        </p>
                    </td>
                </tr>

                <?php if (!empty($pedido['notas'])): ?>
                <tr>
                    <td style="padding:16px 32px 0 32px;">
                        <p style="margin:0; padding:12px 14px; background-color:#f8fafc; border-radius:8px; font-size:13px; line-height:1.5; color:#475569;">
                            <?= nl2br(esc($pedido['notas'])) ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>

                <tr>
                    <td style="padding:28px 32px 32px 32px;">
                        <a href="<?= esc($urlPedido) ?>" style="display:inline-block; background-color:#0f172a; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600; padding:12px 22px; border-radius:8px;">
                            Ver pedido
                        </a>
                    </td>
                </tr>

            </table>

            <p style="max-width:480px; margin:20px 0 0 0; font-size:12px; color:#b0b8c4; text-align:center;">
                Aviso automático de trackbitos.host — no hace falta responder a este correo.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
