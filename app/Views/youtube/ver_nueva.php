<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * YouTube — vídeos de una lista (estilo fv): marcar visto / relevante /
 * largo al vuelo, ordenar, filtrar y buscar sin recargar.
 *
 * @var array  $lista
 * @var array  $videos
 * @var array  $stats  total, vistos, relevantes, pct_vistos
 * @var string $orden
 */
$req   = service('request');
$orden = $orden ?? (string) $req->getGet('orden');
$nv    = $req->getGet('no_vistos') ? 1 : 0;
$rel   = $req->getGet('relevantes') ? 1 : 0;
$slug  = $lista['slug'];
$total = (int) $stats['total'];
$pend  = $total - (int) $stats['vistos'];
$largos = count(array_filter($videos, static fn ($v) => !empty($v['largo'])));

$ordenes = [
    ''           => 'Por ver, recientes',
    'recientes'  => 'Más recientes',
    'antiguos'   => 'Más antiguos',
    'no_vistos'  => 'No vistos primero',
    'vistos'     => 'Vistos primero',
    'relevantes' => 'Relevantes primero',
];

ob_start(); ?>
<a class="fv-btn" href="<?= site_url('youtube/' . $slug . '/importar') ?>"><i class="bi bi-upload"></i>Importar</a>
<a class="fv-ibtn" href="<?= site_url('youtube/' . $slug . '/editar') ?>" title="Editar lista"><i class="bi bi-pencil"></i></a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rc">
    <?= view('youtube/_cabecera', [
        'migas'    => [$lista['nombre'] => null],
        'titulo'   => esc($lista['nombre']),
        'sub'      => '<span id="ytVistos">' . (int) $stats['vistos'] . ' de ' . $total . ' vistos</span>'
                    . '<span class="fv-dot"></span><span id="ytPend" class="' . ($pend ? 'e-warn' : 'e-ok') . '">' . ($pend ? $pend . ' por ver' : 'Todo visto') . '</span>'
                    . '<span class="fv-dot"></span><span id="ytRel"><i class="bi bi-star-fill e-warn"></i> ' . (int) $stats['relevantes'] . '</span>',
        'acciones' => $acciones,
    ]) ?>

    <div class="rc-barra yt-progreso"><i id="ytBarra" style="width:<?= $total ? round($stats['vistos'] * 100 / $total, 1) : 0 ?>%"></i></div>

    <?php if ($total): ?>
        <div class="yt-herr">
            <label class="yt-buscar">
                <i class="bi bi-search"></i>
                <input type="search" class="fv-input" id="ytQ" placeholder="Buscar en <?= $total ?> vídeos…" autocomplete="off">
            </label>
            <select class="rc-sel yt-orden" id="ytOrden" aria-label="Orden">
                <?php foreach ($ordenes as $val => $txt): ?>
                    <option value="<?= esc($val, 'attr') ?>" <?= $orden === $val ? 'selected' : '' ?>><?= esc($txt) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="rc-cats yt-chips">
                <label><input type="checkbox" id="ytNv" <?= $nv ? 'checked' : '' ?>><span><i class="bi bi-circle"></i>No vistos</span></label>
                <label><input type="checkbox" id="ytRl" <?= $rel ? 'checked' : '' ?>><span><i class="bi bi-star"></i>Relevantes</span></label>
                <label><input type="checkbox" id="ytLg"><span><i class="bi bi-camera-reels"></i>Largos<?= $largos ? ' <small>' . $largos . '</small>' : '' ?></span></label>
            </div>
            <span class="yt-cuenta" id="ytCuenta"></span>
        </div>
    <?php endif; ?>

    <div class="yt-videos" id="ytLista"<?= $videos ? '' : ' hidden' ?>>
        <?php foreach ($videos as $v): ?>
            <?php
            $visto = !empty($v['visto']);
            $esRel = !empty($v['relevante']);
            $largo = !empty($v['largo']);
            $yid   = youtube_id($v['url']);
            $tit   = $v['titulo'] ?: 'Sin título';
            ?>
            <div class="yt-v<?= $visto ? ' visto' : '' ?><?= $esRel ? ' rel' : '' ?>"
                 data-id="<?= (int) $v['id'] ?>" data-pos="<?= (int) $v['posicion'] ?>"
                 data-visto="<?= $visto ? 1 : 0 ?>" data-relevante="<?= $esRel ? 1 : 0 ?>" data-largo="<?= $largo ? 1 : 0 ?>"
                 data-q="<?= esc(mb_strtolower($tit), 'attr') ?>">
                <button type="button" class="yt-check" data-campo="visto" aria-pressed="<?= $visto ? 'true' : 'false' ?>" aria-label="Marcar como visto"><i class="bi <?= $visto ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button>
                <a class="yt-mini" href="<?= esc($v['url'], 'attr') ?>" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
                    <?php if ($yid): ?><img src="https://i.ytimg.com/vi/<?= $yid ?>/mqdefault.jpg" alt="" loading="lazy" decoding="async"><?php else: ?><i class="bi bi-play-btn"></i><?php endif; ?>
                </a>
                <a class="yt-txt" href="<?= esc($v['url'], 'attr') ?>" target="_blank" rel="noopener">
                    <span class="t"><?= esc($tit) ?></span>
                    <span class="rc-meta">
                        <span class="fv-mono">#<?= (int) $v['posicion'] ?></span>
                        <span class="yt-tag-largo"<?= $largo ? '' : ' hidden' ?>><i class="bi bi-camera-reels"></i>Largo</span>
                    </span>
                </a>
                <div class="rc-btns">
                    <button type="button" class="yt-b yt-star" data-campo="relevante" aria-pressed="<?= $esRel ? 'true' : 'false' ?>" title="Relevante"><i class="bi <?= $esRel ? 'bi-star-fill' : 'bi-star' ?>"></i></button>
                    <button type="button" class="yt-b yt-largo" data-campo="largo" aria-pressed="<?= $largo ? 'true' : 'false' ?>" title="Vídeo largo"><i class="bi bi-camera-reels"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="fv-panel rc-vacio" id="ytVacio"<?= $videos ? ' hidden' : '' ?>>
        <?php if ($total): ?>
            <i class="bi bi-funnel" style="font-size:24px"></i><div>Ningún vídeo coincide con estos filtros.</div>
        <?php else: ?>
            <i class="bi bi-collection-play" style="font-size:28px"></i>
            <div>Esta lista aún no tiene vídeos.</div>
            <div style="margin-top:12px"><a class="fv-btn fv-btn-pri" href="<?= site_url('youtube/' . $slug . '/importar') ?>"><i class="bi bi-upload"></i>Importar vídeos</a></div>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const lista = document.getElementById('ytLista');
    if (!lista) return;
    const $ = id => document.getElementById(id);
    const total = <?= $total ?>;
    const urls = {
        visto: '<?= site_url('youtube/toggle-visto') ?>',
        relevante: '<?= site_url('youtube/toggle-relevante') ?>',
        largo: '<?= site_url('youtube/toggle-largo') ?>',
    };
    // El servidor ya filtró por lo que venía en la URL: si se quita ese filtro, hay que recargar.
    const filtradoServidor = { nv: <?= $nv ?>, rl: <?= $rel ?> };
    const filas = () => [...lista.querySelectorAll('.yt-v')];

    function opciones() {
        return {
            q: ($('ytQ')?.value || '').trim().toLowerCase(),
            orden: $('ytOrden')?.value || '',
            nv: $('ytNv')?.checked, rl: $('ytRl')?.checked, lg: $('ytLg')?.checked,
        };
    }

    function aplicarFila(f) {
        const d = f.dataset;
        f.classList.toggle('visto', d.visto === '1');
        f.classList.toggle('rel', d.relevante === '1');
        const c = f.querySelector('.yt-check');
        c.setAttribute('aria-pressed', d.visto === '1');
        c.querySelector('i').className = 'bi ' + (d.visto === '1' ? 'bi-check-circle-fill' : 'bi-circle');
        const s = f.querySelector('.yt-star');
        s.setAttribute('aria-pressed', d.relevante === '1');
        s.querySelector('i').className = 'bi ' + (d.relevante === '1' ? 'bi-star-fill' : 'bi-star');
        f.querySelector('.yt-largo').setAttribute('aria-pressed', d.largo === '1');
        f.querySelector('.yt-tag-largo').hidden = d.largo !== '1';
    }

    // Cifras de toda la lista (el DOM puede no tenerla entera si el servidor filtró): se ajustan por incrementos.
    const cifras = { visto: <?= (int) $stats['vistos'] ?>, relevante: <?= (int) $stats['relevantes'] ?> };
    function resumen() {
        const pend = total - cifras.visto;
        $('ytVistos').textContent = cifras.visto + ' de ' + total + ' vistos';
        const p = $('ytPend');
        p.textContent = pend ? pend + ' por ver' : 'Todo visto';
        p.className = pend ? 'e-warn' : 'e-ok';
        $('ytRel').lastChild.textContent = ' ' + cifras.relevante;
        $('ytBarra').style.width = (total ? cifras.visto * 100 / total : 0) + '%';
    }

    function actualizar() {
        const o = opciones();
        let n = 0;
        filas().forEach(f => {
            const d = f.dataset;
            const ver = !(o.nv && d.visto === '1') && !(o.rl && d.relevante !== '1') && !(o.lg && d.largo !== '1') && (!o.q || d.q.includes(o.q));
            f.hidden = !ver;
            if (ver) n++;
        });
        const num = (f, k) => Number(f.dataset[k]);
        const cmp = {
            recientes:  (a, b) => num(b, 'pos') - num(a, 'pos'),
            antiguos:   (a, b) => num(a, 'pos') - num(b, 'pos'),
            no_vistos:  (a, b) => num(a, 'visto') - num(b, 'visto') || num(a, 'pos') - num(b, 'pos'),
            vistos:     (a, b) => num(b, 'visto') - num(a, 'visto') || num(a, 'pos') - num(b, 'pos'),
            relevantes: (a, b) => num(b, 'relevante') - num(a, 'relevante') || num(a, 'pos') - num(b, 'pos'),
        }[o.orden] || ((a, b) => num(a, 'visto') - num(b, 'visto') || num(b, 'pos') - num(a, 'pos'));
        filas().sort(cmp).forEach(f => lista.appendChild(f));

        $('ytCuenta').textContent = n === total ? '' : n + ' de ' + total;
        lista.hidden = !n;
        $('ytVacio').hidden = !!n;

        const u = new URL(location.href);
        ['orden', 'no_vistos', 'relevantes'].forEach(k => u.searchParams.delete(k));
        if (o.orden) u.searchParams.set('orden', o.orden);
        if (o.nv) u.searchParams.set('no_vistos', 1);
        if (o.rl) u.searchParams.set('relevantes', 1);
        history.replaceState(null, '', u);
    }

    $('ytQ')?.addEventListener('input', actualizar);
    $('ytOrden')?.addEventListener('change', actualizar);
    $('ytLg')?.addEventListener('change', actualizar);
    [['ytNv', 'nv'], ['ytRl', 'rl']].forEach(([id, k]) => $(id)?.addEventListener('change', e => {
        actualizar();
        if (!e.target.checked && filtradoServidor[k]) location.reload();
    }));

    lista.addEventListener('click', async ev => {
        const b = ev.target.closest('[data-campo]');
        if (!b || b.disabled) return;
        const f = b.closest('.yt-v');
        const campo = b.dataset.campo;
        b.disabled = true;
        const r = await fetch(urls[campo] + '/' + f.dataset.id, {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
        });
        b.disabled = false;
        if (!r.ok) return;
        if (navigator.vibrate) navigator.vibrate(15);
        f.dataset[campo] = f.dataset[campo] === '1' ? '0' : '1';
        if (campo in cifras) cifras[campo] += f.dataset[campo] === '1' ? 1 : -1;
        aplicarFila(f);
        resumen();
        // Si el cambio la saca del filtro, que se vea el tic antes de desaparecer.
        setTimeout(actualizar, campo === 'visto' && opciones().nv ? 450 : 0);
    });

    actualizar();
})();
</script>
<?= $this->endSection() ?>
