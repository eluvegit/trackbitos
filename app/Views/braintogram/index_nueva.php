<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$filtros = static fn (array $m) => [
    ['Secret', $m['secret_valido'], 'shield-check', 'shield-exclamation', true],
    ['Chat', $m['chat_autorizado'], 'person-check', 'person-x', true],
    ['Rate limit', $m['rate_limited'], 'speedometer2', 'hourglass-split', false],
];
$total = $pager->getTotal();
$auto = service('request')->getGet('auto') === '1';
?>
<div class="fv rc">
    <?= view('layouts/_cabecera_bots', [
        'modulo'   => 'Braintogram',
        'ruta'     => 'braintogram',
        'titulo'   => 'Braintogram',
        'sub'      => '<span>' . $total . ' ' . ($total === 1 ? 'mensaje recibido' : 'mensajes recibidos') . '</span>',
        'acciones' => '<a class="fv-btn' . ($auto ? ' fv-btn-pri' : '') . '" href="' . current_url() . '?auto=' . ($auto ? '0' : '1') . '"><i class="bi bi-arrow-repeat"></i>' . ($auto ? 'Actualizando cada 5 s' : 'Auto-actualizar') . '</a>',
    ]) ?>

    <section class="rc-sec">
        <div class="fv-ph"><h3>URL del webhook</h3><span class="der t3" style="font-size:12px">para <code>setWebhook</code></span></div>
        <div class="fv-cmd"><span><?= esc($webhookUrl) ?></span><button type="button" class="fv-ibtn" data-copiar="<?= esc($webhookUrl) ?>" title="Copiar"><i class="bi bi-clipboard"></i></button></div>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Mensajes</h3><span class="cuenta"><?= $total ?></span></div>
        <?php if (empty($mensajes)): ?>
            <div class="rc-lista"><div class="rc-vacio">Todavía no ha llegado ningún mensaje. Prueba con un <code>curl -X POST</code> contra la URL de arriba.</div></div>
        <?php else: ?>
            <div class="rc-lista">
                <?php foreach ($mensajes as $m): ?>
                    <?php
                    $tipo = $m['tipo'] ?? 'otro';
                    $cortado = (string) $m['secret_valido'] === '0' || (string) $m['chat_autorizado'] === '0' || (string) $m['rate_limited'] === '1';
                    $quien = trim(($m['from_nombre'] ?: '') . ($m['from_username'] ? ' @' . $m['from_username'] : ''));
                    ?>
                    <a href="<?= site_url('braintogram/' . $m['id']) ?>" class="rc-fila bo-fila<?= $cortado ? ' n-bad' : '' ?>">
                        <span class="rc-ico"><i class="bi bi-<?= in_array($tipo, ['invalido', 'otro'], true) ? 'question-lg' : 'chat-left-text' ?>"></i></span>
                        <span class="rc-txt">
                            <span class="rc-t"><span class="t<?= $m['texto'] ? '' : ' vacio' ?>"><?= $m['texto'] ? esc(mb_strimwidth($m['texto'], 0, 160, '…')) : 'Sin texto' ?></span></span>
                            <span class="rc-meta">
                                <span class="fv-mono"><?= date('d/m/Y H:i:s', strtotime($m['fecha_telegram'] ?? $m['created_at'])) ?></span>
                                <span><?= esc($tipo) ?></span>
                                <?php if ($quien): ?><span><i class="bi bi-person"></i><?= esc($quien) ?></span><?php endif; ?>
                                <?php if ($m['chat_id']): ?><span class="fv-mono">chat <?= esc($m['chat_id']) ?></span><?php endif; ?>
                                <span class="fv-mono"><?= esc($m['ip_origen']) ?></span>
                            </span>
                        </span>
                        <span class="bo-filtros">
                            <?php foreach ($filtros($m) as [$nombre, $valor, $icoOk, $icoMal, $okEsUno]): ?>
                                <?php
                                if ($valor === null) { $c = 'e-neu'; $i = 'dash-circle'; $txt = 'sin evaluar'; }
                                elseif (((int) $valor === 1) === $okEsUno) { $c = 'e-ok'; $i = $icoOk; $txt = 'ok'; }
                                else { $c = 'e-bad'; $i = $icoMal; $txt = 'cortado'; }
                                ?>
                                <i class="bi bi-<?= $i ?> <?= $c ?>" title="<?= $nombre ?>: <?= $txt ?>"></i>
                            <?php endforeach; ?>
                        </span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="bo-pager"><?= $pager->links() ?></div>
        <?php endif; ?>
    </section>
</div>

<script>
document.querySelectorAll('[data-copiar]').forEach(function (b) {
    b.addEventListener('click', function () {
        navigator.clipboard.writeText(b.dataset.copiar).then(function () {
            var i = b.querySelector('i'); i.className = 'bi bi-check2';
            setTimeout(function () { i.className = 'bi bi-clipboard'; }, 1200);
        });
    });
});
<?php if ($auto): ?>setTimeout(function () { location.reload(); }, 5000);<?php endif; ?>
</script>
<?= $this->endSection() ?>
