<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * YouTube — importar vídeos a una lista pegando JSON (estilo fv).
 *
 * @var array|null $lista
 * @var array|null $results
 * @var array      $errors
 * @var string     $oldJson
 */
$slug = $lista['slug'] ?? '';
$ok = $dup = $mal = $omit = 0;
foreach ($results ?? [] as $r) {
    if (!empty($r['duplicado'])) $dup++;
    elseif (!empty($r['ok'])) $ok++;
    elseif (str_starts_with((string) ($r['error'] ?? ''), 'Omitido')) $omit++;
    else $mal++;
}
$cmd = [
    'Instala yt-dlp'                 => 'python3 -m pip install -U yt-dlp',
    'Descarga la lista a JSON'       => 'yt-dlp -J --flat-playlist "https://www.youtube.com/playlist?list=…" > playlist_raw.json',
    'Pásala al formato de Trackbitos' => "jq '[ .entries[] | { titulo: .title, url: (\"https://www.youtube.com/watch?v=\" + .id) } ]' playlist_raw.json > import.json",
];
?>
<div class="fv rc estrecho">
    <?= view('youtube/_cabecera', [
        'migas'  => $lista ? [$lista['nombre'] => site_url('youtube/' . $slug), 'Importar' => null] : ['Importar' => null],
        'titulo' => 'Importar vídeos',
        'sub'    => $lista ? '<span>A la lista <b class="t1">' . esc($lista['nombre']) . '</b></span>' : '',
    ]) ?>

    <?php if (!$lista): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i>No existe la lista de destino. Revisa la dirección.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i><?= esc($e) ?></div>
    <?php endforeach; ?>

    <?php if (is_array($results)): ?>
        <section class="rc-sec">
            <div class="fv-ph"><h3>Resultado</h3><span class="cuenta"><?= count($results) ?> procesados</span></div>
            <div class="rc-cifras yt-res-cifras">
                <div class="rc-cifra"><span class="k e-ok"><i class="bi bi-plus-circle"></i>Importados</span><span class="v"><?= $ok ?></span></div>
                <div class="rc-cifra"><span class="k"><i class="bi bi-files"></i>Ya estaban</span><span class="v"><?= $dup ?></span></div>
                <?php if ($omit): ?><div class="rc-cifra"><span class="k"><i class="bi bi-eye-slash"></i>Borrados / privados</span><span class="v"><?= $omit ?></span></div><?php endif; ?>
                <?php if ($mal): ?><div class="rc-cifra"><span class="k e-bad"><i class="bi bi-x-circle"></i>Con error</span><span class="v"><?= $mal ?></span></div><?php endif; ?>
            </div>
            <?php if ($results): ?>
                <details class="yt-detalle"<?= $mal ? ' open' : '' ?>>
                    <summary>Ver uno a uno</summary>
                    <div class="rc-hist">
                        <?php foreach ($results as $i => $r): ?>
                            <?php
                            [$ico, $cls, $txt] = match (true) {
                                !empty($r['duplicado']) => ['bi-files', 'e-neu', 'Ya estaba'],
                                !empty($r['ok'])        => ['bi-check-lg', 'e-ok', 'Importado'],
                                str_starts_with((string) ($r['error'] ?? ''), 'Omitido') => ['bi-eye-slash', 'e-neu', 'Omitido'],
                                default                 => ['bi-x-lg', 'e-bad', 'Error'],
                            };
                            ?>
                            <div class="rc-h">
                                <span class="rc-ico <?= $cls ?>"><i class="bi <?= $ico ?>"></i></span>
                                <div class="rc-txt">
                                    <span class="t"><?= esc($r['titulo'] ?: '(sin título)') ?></span>
                                    <div class="rc-meta">
                                        <span class="fv-mono">#<?= $i + 1 ?></span>
                                        <?php if (!empty($r['youtubeId'])): ?><a href="https://www.youtube.com/watch?v=<?= esc($r['youtubeId'], 'url') ?>" target="_blank" rel="noopener" class="fv-mono"><?= esc($r['youtubeId']) ?></a><?php endif; ?>
                                        <?php if (!empty($r['error'])): ?><span><?= esc($r['error']) ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <span class="fv-estado <?= $cls ?>"><?= $txt ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
            <?php if ($lista && $ok + $dup): ?>
                <div><a class="fv-btn fv-btn-pri" href="<?= site_url('youtube/' . $slug) ?>"><i class="bi bi-arrow-right"></i>Ir a la lista</a></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <form class="rc-form" method="post" action="<?= $lista ? site_url('youtube/' . $slug . '/importar') : '#' ?>" id="ytImp">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <label class="rc-campo">
                <span>Pega el JSON</span>
                <textarea class="fv-input yt-json" name="json" id="ytJson" rows="10" spellcheck="false" placeholder='[{ "titulo": "…", "url": "https://www.youtube.com/watch?v=…" }, …]'><?= esc($oldJson ?? '') ?></textarea>
            </label>
            <div class="rc-info"><i class="bi bi-info-circle"></i><span>Una lista <code>[ … ]</code> u objetos sueltos separados por comas, cada uno con <code>titulo</code> y <code>url</code>. Los repetidos y los vídeos borrados o privados se saltan.</span></div>
        </div>
        <div class="rc-pie">
            <span class="yt-cuenta" id="ytDetect">0 vídeos detectados</span>
            <div class="der">
                <?php if ($lista): ?><a class="fv-btn" href="<?= site_url('youtube/' . $slug) ?>">Cancelar</a><?php endif; ?>
                <button type="submit" class="fv-btn fv-btn-pri" <?= $lista ? '' : 'disabled' ?>><i class="bi bi-upload"></i>Importar</button>
            </div>
        </div>
    </form>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Cómo sacar el JSON de una lista de YouTube</h3></div>
        <div class="fv-panel yt-pasos">
            <?php $n = 0; foreach ($cmd as $paso => $linea): $n++; ?>
                <div class="yt-paso">
                    <span class="num"><?= $n ?></span>
                    <div class="rc-txt">
                        <span class="t"><?= esc($paso) ?></span>
                        <div class="yt-cmd"><code><?= esc($linea) ?></code><button type="button" class="fv-ibtn" data-copiar="<?= esc($linea, 'attr') ?>" title="Copiar"><i class="bi bi-clipboard"></i></button></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
(() => {
    const ta = document.getElementById('ytJson');
    const out = document.getElementById('ytDetect');
    const contar = t => {
        t = (t || '').trim();
        if (!t) return 0;
        if (t[0] !== '[') t = '[' + t.replace(/,\s*$/, '') + ']';
        try { const d = JSON.parse(t); return Array.isArray(d) ? d.length : 0; } catch { return -1; }
    };
    const pintar = () => {
        const n = contar(ta.value);
        out.textContent = n < 0 ? 'El JSON no es válido todavía' : n + (n === 1 ? ' vídeo detectado' : ' vídeos detectados');
        out.className = 'yt-cuenta ' + (n < 0 ? 'e-bad' : n ? 'e-ok' : '');
    };
    ta.addEventListener('input', pintar);
    pintar();
    document.getElementById('ytImp').addEventListener('submit', e => {
        if (contar(ta.value) <= 0 && !confirm('No se detectan vídeos válidos. ¿Enviar de todas formas?')) e.preventDefault();
    });
    document.addEventListener('click', async e => {
        const b = e.target.closest('[data-copiar]');
        if (!b) return;
        await navigator.clipboard.writeText(b.dataset.copiar);
        const i = b.querySelector('i');
        i.className = 'bi bi-check-lg';
        setTimeout(() => (i.className = 'bi bi-clipboard'), 1200);
    });
})();
</script>
<?= $this->endSection() ?>
