<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$total = $pager->getTotal();
$sub = [
    '<span>' . count($usuarios) . ' ' . (count($usuarios) === 1 ? 'usuario' : 'usuarios') . '</span>',
    '<span>' . $total . ' ' . ($total === 1 ? 'telegrama enviado' : 'telegramas enviados') . '</span>',
];
?>
<div class="fv rc">
    <?= view('layouts/_cabecera_bots', [
        'modulo' => 'Buscapp',
        'ruta'   => 'buscapp',
        'titulo' => 'Buscapp',
        'sub'    => implode('<span class="fv-dot"></span>', $sub),
    ]) ?>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Telegramas enviados</h3><span class="cuenta"><?= $total ?></span></div>
        <div class="rc-lista">
            <?php if (empty($telegramas)): ?>
                <div class="rc-vacio">Todavía no se ha enviado ningún telegrama.</div>
            <?php endif; ?>
            <?php foreach ($telegramas as $t): ?>
                <?php $urgente = $t['urgencia'] === 'urgente'; ?>
                <a href="<?= site_url('buscapp/' . $t['id']) ?>" class="rc-fila bo-fila<?= $urgente ? ' n-bad' : '' ?>">
                    <span class="rc-ico"><i class="bi bi-<?= $urgente ? 'exclamation-lg' : 'envelope' ?>"></i></span>
                    <span class="rc-txt">
                        <span class="rc-t"><span class="t"><?= esc($t['tipo']) ?></span><span class="rc-mudo fv-mono"><?= esc($t['enviado_en'] ?? '—') ?></span></span>
                        <?php if ($t['mensaje']): ?><span class="rc-notas"><?= esc(mb_strimwidth($t['mensaje'], 0, 160, '…')) ?></span><?php endif; ?>
                    </span>
                    <span class="rc-estado fv-estado <?= $urgente ? 'e-bad' : 'e-neu' ?>"><?= esc($t['urgencia']) ?></span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($telegramas): ?><div class="bo-pager"><?= $pager->links() ?></div><?php endif; ?>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Usuarios</h3><span class="cuenta"><?= count($usuarios) ?></span></div>
        <div class="rc-lista">
            <?php if (empty($usuarios)): ?>
                <div class="rc-vacio">Todavía no se ha registrado nadie desde la app.</div>
            <?php endif; ?>
            <?php foreach ($usuarios as $u): ?>
                <?php
                if ($u['fcm_token']) { $canal = ['app', 'e-ok', 'phone']; }
                elseif ($u['telegram_chat_id']) { $canal = ['telegram', 'e-info', 'telegram']; }
                else { $canal = ['sin canal', 'e-neu', 'person']; }
                ?>
                <div class="rc-fila">
                    <span class="rc-ico"><i class="bi bi-<?= $canal[2] ?>"></i></span>
                    <span class="rc-txt">
                        <span class="rc-t"><span class="t"><?= esc($u['nombre']) ?></span><?php if ($u['telefono_e164']): ?><span class="rc-mudo fv-mono"><?= esc($u['telefono_e164']) ?></span><?php endif; ?></span>
                        <span class="rc-meta">
                            <span>Alta <span class="fv-mono"><?= esc($u['creado_en'] ?? '—') ?></span></span>
                            <span>Último acceso <span class="fv-mono"><?= esc($u['ultimo_acceso'] ?? '—') ?></span></span>
                        </span>
                    </span>
                    <span class="rc-estado fv-estado <?= $canal[1] ?>"><?= $canal[0] ?></span>
                    <span></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
