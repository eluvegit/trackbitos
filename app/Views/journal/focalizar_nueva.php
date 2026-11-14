<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/journal.css') ?>?v=8">
<?php
$hoy = new DateTime('today');
$dias = function ($id) use ($lastTaskActivity, $hoy) {
    $f = $lastTaskActivity[$id] ?? null;
    if (!$f) return 'sin empezar';
    $d = $hoy->diff(new DateTime(date('Y-m-d', strtotime($f))))->days;
    return $d === 0 ? 'hoy' : $d . ' d';
};
$fila = fn ($t, $cat) => [
    'id'    => (int) $t['id'],
    'title' => $t['title'],
    'cat'   => $cat,
    'color' => $colores[$cat] ?? '#666',
    'dias'  => $dias($t['id']),
];
$candidatas = [];
foreach ($starredByCategory as $cat => $ts) {
    foreach ($ts as $t) {
        if (empty($t['en_foco'])) {
            $candidatas[] = $fila($t, $cat);
        }
    }
}
?>
<div class="fv jo" id="jo">
    <div class="jn-top">
        <a class="jf-volver" href="<?= site_url('journal') ?>"><i class="bi bi-chevron-left"></i>Journal</a>
        <span style="margin-right:auto"></span>
        <a class="jn-ib" href="<?= site_url('journal/focalizar') ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a>
    </div>
    <div>
        <h1 class="jq-h1">Foco</h1>
        <div class="jf-meta" style="margin-top:4px"><span><span id="joN"><?= count($enFocoOrdered) ?></span> tareas</span><span class="fv-dot"></span><span>arrastra para ordenar</span></div>
    </div>

    <div class="jo-lista" id="joLista">
        <?php foreach ($enFocoOrdered as $i => $t): $f = $fila($t, $t['cat']); ?>
            <div class="jo-fila" data-id="<?= $f['id'] ?>">
                <span class="jo-grip"><i class="bi bi-grip-vertical"></i></span>
                <span class="jo-num"><?= $i + 1 ?></span>
                <a class="nm" href="<?= site_url('journal/edit/' . $f['id']) ?>"><?= esc($f['title']) ?><small><span class="jn-dot" style="background:<?= esc($f['color'], 'attr') ?>"></span><?= esc($f['cat']) ?> · <?= $f['dias'] ?></small></a>
                <button type="button" class="jq-play js-play" data-title="<?= esc($f['title'], 'attr') ?>" title="Empezar"><i class="bi bi-play-fill"></i></button>
                <button type="button" class="jo-quitar js-quitar" title="Quitar del foco"><i class="bi bi-x-lg"></i></button>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="jn-vacio" id="joVacio" style="<?= $enFocoOrdered ? '' : 'display:block' ?>">No hay tareas en el foco. Añádelas abajo.</p>

    <div class="jo-anadir">
        <div class="jq-ayuda" style="margin:0">Añadir al foco</div>
        <label class="jo-buscar"><i class="bi bi-search"></i><input type="search" id="joQ" placeholder="Buscar entre las <?= count($candidatas) ?> con estrella" autocomplete="off"></label>
        <div class="jo-res" id="joRes"></div>
    </div>
</div>
<?= view('journal/_reloj') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const URL = '<?= site_url('journal') ?>';
    let candidatas = <?= json_encode($candidatas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const lista = document.getElementById('joLista');
    const q = document.getElementById('joQ');
    const res = document.getElementById('joRes');
    const esc = s => String(s).replace(/[&<>"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    const toggle = id => fetch(URL + '/focalizar/toggle/' + id, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());

    function numerar() {
        const filas = [...lista.children];
        filas.forEach((f, i) => f.querySelector('.jo-num').textContent = i + 1);
        document.getElementById('joN').textContent = filas.length;
        document.getElementById('joVacio').style.display = filas.length ? '' : 'block';
    }
    const guardarOrden = () => fetch(URL + '/focalizar/orden', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ order: [...lista.children].map(f => parseInt(f.dataset.id, 10)) }),
    });
    Sortable.create(lista, { handle: '.jo-grip', animation: 150, onEnd: () => { numerar(); guardarOrden(); } });

    const filaHtml = c => `<div class="jo-fila" data-id="${c.id}">
        <span class="jo-grip"><i class="bi bi-grip-vertical"></i></span><span class="jo-num"></span>
        <a class="nm" href="${URL}/edit/${c.id}">${esc(c.title)}<small><span class="jn-dot" style="background:${esc(c.color)}"></span>${esc(c.cat)} · ${esc(c.dias)}</small></a>
        <button type="button" class="jq-play js-play" data-title="${esc(c.title)}" title="Empezar"><i class="bi bi-play-fill"></i></button>
        <button type="button" class="jo-quitar js-quitar" title="Quitar del foco"><i class="bi bi-x-lg"></i></button></div>`;

    function buscar() {
        const t = norm(q.value.trim());
        if (!t) { res.innerHTML = ''; return; }
        const hits = candidatas.filter(c => norm(c.title + ' ' + c.cat).includes(t)).slice(0, 8);
        res.innerHTML = hits.length
            ? hits.map(c => `<button type="button" class="jo-hit" data-id="${c.id}"><i class="bi bi-plus-lg"></i><span class="nm">${esc(c.title)}<small><span class="jn-dot" style="background:${esc(c.color)}"></span>${esc(c.cat)} · ${esc(c.dias)}</small></span></button>`).join('')
            : '<p class="jn-vacio" style="display:block">Nada con estrella que coincida.</p>';
    }
    q.addEventListener('input', buscar);

    res.addEventListener('click', async e => {
        const b = e.target.closest('.jo-hit');
        if (!b) return;
        b.disabled = true;
        const d = await toggle(b.dataset.id);
        if (!d.success) { alert(d.error || 'No se pudo añadir.'); b.disabled = false; return; }
        const c = candidatas.find(x => String(x.id) === b.dataset.id);
        candidatas = candidatas.filter(x => x !== c);
        lista.insertAdjacentHTML('beforeend', filaHtml(c));
        numerar();
        buscar();
    });

    lista.addEventListener('click', async e => {
        const fila = e.target.closest('.jo-fila');
        if (!fila) return;
        if (e.target.closest('.js-play')) {
            window.JReloj && window.JReloj.empezar(fila.dataset.id, e.target.closest('.js-play').dataset.title);
            return;
        }
        if (e.target.closest('.js-quitar')) {
            const nombre = fila.querySelector('.nm').firstChild.textContent.trim();
            if (!confirm('¿Quitar «' + nombre + '» del foco? Sigue con su estrella.')) return;
            const d = await toggle(fila.dataset.id);
            if (!d.success) { alert(d.error || 'No se pudo quitar.'); return; }
            const small = fila.querySelector('small').textContent.split(' · ');
            candidatas.push({ id: parseInt(fila.dataset.id, 10), title: nombre, cat: small[0].trim(), color: fila.querySelector('small .jn-dot').style.background, dias: small[1] || '' });
            fila.remove();
            numerar();
            buscar();
        }
    });
})();
</script>
<?= $this->endSection() ?>
