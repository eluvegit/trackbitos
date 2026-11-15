<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$t = $telegrama;
$urgente = $t['urgencia'] === 'urgente';
$estados = ['enviado' => 'e-neu', 'entregado' => 'e-info', 'visto' => 'e-info', 'respondido' => 'e-ok'];
$respondidos = count(array_filter($destinos, fn ($d) => $d['estado'] === 'respondido'));
$sub = [
    '<span class="fv-estado ' . ($urgente ? 'e-bad' : 'e-neu') . '">' . esc($t['urgencia']) . '</span>',
    '<span>' . esc($t['tipo']) . '</span>',
    '<span>de ' . esc($emisor['nombre'] ?? '—') . '</span>',
];
?>
<div class="fv rc">
    <?= view('layouts/_cabecera_bots', [
        'modulo' => 'Buscapp',
        'ruta'   => 'buscapp',
        'migas'  => ['Telegrama #' . (int) $t['id'] => null],
        'titulo' => 'Telegrama <span class="t3">#' . (int) $t['id'] . '</span>',
        'sub'    => implode('<span class="fv-dot"></span>', $sub),
    ]) ?>

    <div class="bo-grid">
        <div class="bo-col">
            <?php if ($t['mensaje']): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Mensaje</h3></div>
                    <div class="bo-texto"><?= esc($t['mensaje']) ?></div>
                </section>
            <?php endif; ?>
            <section class="rc-sec">
                <div class="fv-ph"><h3>Destinatarios</h3><span class="cuenta"><?= $respondidos ?>/<?= count($destinos) ?> respondidos</span></div>
                <div class="rc-lista">
                    <?php if (empty($destinos)): ?><div class="rc-vacio">Sin destinatarios.</div><?php endif; ?>
                    <?php foreach ($destinos as $d): ?>
                        <div class="rc-fila">
                            <span class="rc-ico"><i class="bi bi-<?= $d['canal'] === 'telegram' ? 'telegram' : 'phone' ?>"></i></span>
                            <span class="rc-txt">
                                <span class="rc-t"><span class="t"><?= esc($usuariosPorId[$d['receptor_id']]['nombre'] ?? $d['receptor_id']) ?></span><span class="rc-mudo"><?= esc($d['canal']) ?></span></span>
                                <span class="rc-meta">
                                    <?php if ($d['respuesta']): ?><span class="t1"><i class="bi bi-reply"></i><?= esc($d['respuesta']) ?></span><?php endif; ?>
                                    <span>Entregado <span class="fv-mono"><?= esc($d['entregado_en'] ?? '—') ?></span></span>
                                    <?php if ($d['respondido_en']): ?><span>Respondido <span class="fv-mono"><?= esc($d['respondido_en']) ?></span></span><?php endif; ?>
                                </span>
                            </span>
                            <span class="rc-estado fv-estado <?= $estados[$d['estado']] ?? 'e-neu' ?>"><?= esc($d['estado']) ?></span>
                            <span></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <div class="bo-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Datos</h3></div>
                <dl class="fv-kv bo-kv">
                    <div><dt>Emisor</dt><dd><?= esc($emisor['nombre'] ?? '—') ?></dd></div>
                    <div><dt>Tipo</dt><dd><?= esc($t['tipo']) ?></dd></div>
                    <div><dt>Enviado</dt><dd class="fv-mono"><?= esc($t['enviado_en'] ?? '—') ?></dd></div>
                    <div><dt>Caduca</dt><dd class="<?= $t['caduca_en'] ? 'fv-mono' : 't3' ?>"><?= esc($t['caduca_en'] ?? 'sin caducidad') ?></dd></div>
                </dl>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
