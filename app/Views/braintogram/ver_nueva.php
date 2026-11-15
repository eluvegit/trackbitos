<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$m = $mensaje;
$tipo = $m['tipo'] ?? 'otro';
$pasos = [
    ['Secret token', $m['secret_valido'], true, 'Válido', 'Inválido — cortado aquí', 'Sin configurar (no se filtra)'],
    ['Chat autorizado', $m['chat_autorizado'], true, 'Autorizado', 'No autorizado — cortado aquí', 'Sin whitelist configurada (no se filtra)'],
    ['Rate limit', $m['rate_limited'], false, 'Dentro del límite', 'Bloqueado — cortado aquí', 'No evaluado (cortado en un paso anterior)'],
];
$cortado = false;
foreach ($pasos as $p) {
    if ($p[1] !== null && ((int) $p[1] === 1) !== $p[2]) $cortado = true;
}
$bonito = $m['raw_json'];
$decoded = json_decode((string) $m['raw_json'], true);
if ($decoded !== null) {
    $bonito = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$sub = [
    $cortado ? '<span class="fv-estado e-bad">Cortado en los filtros</span>' : '<span class="fv-estado e-ok">Ha pasado los filtros</span>',
    '<span>' . esc($tipo) . '</span>',
    '<span class="fv-mono">' . esc($m['fecha_telegram'] ?? $m['created_at']) . '</span>',
];
$kv = [
    'Recibido (servidor)' => $m['created_at'],
    'Fecha Telegram'      => $m['fecha_telegram'] ?? null,
    'Update ID'           => $m['update_id'] ?? null,
    'Chat'                => $m['chat_id'] ? $m['chat_id'] . ($m['chat_type'] ? ' (' . $m['chat_type'] . ')' : '') : null,
    'De'                  => trim(($m['from_nombre'] ?? '') . ($m['from_username'] ? ' @' . $m['from_username'] : '')) ?: null,
    'ID remitente'        => $m['from_id'] ?? null,
    'IP origen'           => $m['ip_origen'] ?? null,
];
?>
<div class="fv rc">
    <?= view('layouts/_cabecera_bots', [
        'modulo' => 'Braintogram',
        'ruta'   => 'braintogram',
        'migas'  => ['Mensaje #' . (int) $m['id'] => null],
        'titulo' => 'Mensaje <span class="t3">#' . (int) $m['id'] . '</span>',
        'sub'    => implode('<span class="fv-dot"></span>', $sub),
    ]) ?>

    <div class="bo-grid">
        <div class="bo-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Texto</h3></div>
                <?php if ($m['texto']): ?><div class="bo-texto"><?= esc($m['texto']) ?></div><?php else: ?><div class="fv-vacio">Sin texto</div><?php endif; ?>
            </section>
            <section class="fv-panel">
                <div class="fv-ph"><h3>JSON crudo</h3><button type="button" class="fv-btn fv-btn-sm der" id="btCopyRaw"><i class="bi bi-clipboard"></i>Copiar</button></div>
                <pre class="bo-raw" id="btRaw"><?= esc($bonito) ?></pre>
            </section>
        </div>
        <div class="bo-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Filtros</h3></div>
                <ol class="bo-pasos">
                    <?php foreach ($pasos as $n => [$nombre, $valor, $okEsUno, $tOk, $tMal, $tNull]): ?>
                        <?php
                        if ($valor === null) { $c = 'e-neu'; $t = $tNull; }
                        elseif (((int) $valor === 1) === $okEsUno) { $c = 'e-ok'; $t = $tOk; }
                        else { $c = 'e-bad'; $t = $tMal; }
                        ?>
                        <li><span class="n"><?= $n + 1 ?></span><span><b><?= $nombre ?></b><span class="fv-estado <?= $c ?>"><?= $t ?></span></span></li>
                    <?php endforeach; ?>
                </ol>
            </section>
            <section class="fv-panel">
                <div class="fv-ph"><h3>Datos</h3></div>
                <dl class="fv-kv bo-kv">
                    <?php foreach ($kv as $k => $v): ?>
                        <div><dt><?= $k ?></dt><dd class="<?= $v === null ? 't3' : 'fv-mono' ?>"><?= $v === null ? '—' : esc($v) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </section>
        </div>
    </div>
</div>

<script>
document.getElementById('btCopyRaw').addEventListener('click', function () {
    var btn = this;
    navigator.clipboard.writeText(document.getElementById('btRaw').textContent).then(function () {
        btn.innerHTML = '<i class="bi bi-check2"></i>Copiado';
        setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>Copiar'; }, 1200);
    });
});
</script>
<?= $this->endSection() ?>
