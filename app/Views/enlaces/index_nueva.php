<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — buscador (estilo fv). Misma lógica que la vista clásica:
 * búsqueda en vivo contra `enlaces/buscar-resultados`, filtros de
 * categorías/etiquetas/estado, chips activos y modo enfoque (E).
 */
$total = is_countable($enlaces) ? count($enlaces) : 0;
$cuenta = !empty($modoReciente) ? 'Últimos ' . $total
    : (!empty($hayMas) ? (int) $topeResultados . '+ resultados' : $total . ' ' . ($total === 1 ? 'resultado' : 'resultados'));

$sub = '<span>' . (int) $totalEnlaces . ' guardados</span><span class="fv-dot"></span><span id="enCount">' . $cuenta . '</span>';
if ($pendientesRevision > 0) {
    $sub .= '<span class="fv-dot"></span><a class="e-warn" href="' . site_url('enlaces/revision') . '">' . (int) $pendientesRevision . ' sin título</a>';
}

ob_start(); ?>
<button type="button" class="fv-ibtn" id="btnEnfoque" title="Modo enfoque: de uno en uno (E)"><i class="bi bi-arrows-fullscreen"></i></button>
<a class="fv-btn" href="<?= site_url('enlaces/repaso') ?>"><i class="bi bi-fire"></i>Repaso</a>
<a class="fv-btn fv-btn-pri" href="<?= site_url('enlaces/crear') ?>"><i class="bi bi-plus-lg"></i>Agregar</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rc">
    <?= view('enlaces/_cabecera', [
        'activa'   => 'inicio',
        'titulo'   => 'Enlaces',
        'sub'      => $sub,
        'acciones' => $acciones,
    ]) ?>

    <form method="get" action="<?= site_url('enlaces') ?>" id="filtrosForm" class="en-busca">
        <div class="en-barra">
            <label class="en-q">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= esc($q) ?>" class="fv-input" autocomplete="off" placeholder="Buscar en título, URL, nota, categoría o etiqueta…">
                <kbd class="en-kbd" id="kbdBarra"<?= $q === '' ? '' : ' hidden' ?>>/</kbd>
                <button type="button" class="en-q-x" id="btnLimpiarTexto"<?= $q === '' ? ' hidden' : '' ?> title="Quitar solo el texto"><i class="bi bi-x-lg"></i></button>
            </label>
            <button type="button" class="fv-btn en-btn-filtros<?= $panelActiveCount ? ' on' : '' ?>" id="btnToggleFiltros" aria-expanded="<?= $panelActiveCount ? 'true' : 'false' ?>">
                <i class="bi bi-sliders"></i><span>Filtros</span><b class="en-n"<?= $panelActiveCount ? '' : ' hidden' ?>><?= (int) $panelActiveCount ?></b>
            </button>
        </div>

        <div class="en-chips" id="enlChips"><?= trim(view('enlaces/_chips_nueva', get_defined_vars())) ?></div>

        <div class="fv-panel en-panel" id="panelFiltros"<?= $panelActiveCount ? '' : ' hidden' ?>>
            <div class="rc-campo">
                <span>Categorías</span>
                <div class="en-pills" id="catPills">
                    <?php foreach ($categorias as $c): ?>
                        <button type="button" class="en-chip<?= in_array((int) $c['id'], $cats, true) ? ' on' : '' ?>" data-cat="<?= (int) $c['id'] ?>"><?= esc($c['nombre']) ?> <small><?= (int) ($catCount[$c['id']] ?? 0) ?></small></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="rc-campo">
                <span>Etiquetas <small class="t3">(de los resultados de ahora)</small></span>
                <div class="en-picker" id="pickerEtiquetas"></div>
            </div>
            <div class="en-panel-fila">
                <div class="rc-campo">
                    <span>Estado</span>
                    <div class="fv-seg" id="segEstado">
                        <button type="button" data-value="">Todos</button>
                        <button type="button" data-value="0">No vistos</button>
                        <button type="button" data-value="1">Vistos</button>
                        <?php if (!empty($hayArchivo)): ?><button type="button" data-value="arch">Archivados</button><?php endif; ?>
                    </div>
                    <input type="hidden" name="visto" id="inputVisto" value="<?= esc($visto) ?>">
                </div>
                <div class="rc-campo" id="matchWrap" hidden>
                    <span>Coincidencia</span>
                    <div class="fv-seg" id="segMatch">
                        <button type="button" data-value="any">Cualquiera</button>
                        <button type="button" data-value="all">Todas</button>
                    </div>
                    <input type="hidden" name="match" id="inputMatch" value="<?= esc($match) ?>">
                </div>
                <button type="button" class="fv-btn fv-btn-sm der" id="btnQuitarFiltros"><i class="bi bi-x-lg"></i>Quitar filtros</button>
            </div>
        </div>
    </form>

    <div id="enlResultados"><?= view('enlaces/_resultados_nueva', get_defined_vars()) ?></div>
</div>

<!-- Modo enfoque: recorre los resultados de uno en uno -->
<div class="fv en-foco" id="enlFocus" hidden>
    <div class="en-foco-barra">
        <span class="fv-mono t3" id="focusPos">1 / 1</span>
        <div class="en-foco-nav">
            <button type="button" class="fv-btn fv-btn-sm" data-focus="prev" title="Anterior (←)"><i class="bi bi-chevron-left"></i></button>
            <button type="button" class="fv-btn fv-btn-sm" data-focus="next" title="Siguiente (→)"><i class="bi bi-chevron-right"></i></button>
            <button type="button" class="fv-btn fv-btn-sm" data-focus="close" title="Salir (Esc)"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    <div class="en-foco-tarjeta" id="focusCard"></div>
    <div class="en-foco-pista">← → moverse · O abrir · V visto · Esc salir</div>
</div>

<script>
(() => {
    const $ = id => document.getElementById(id);
    const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    // ============= Selector de etiquetas: buscador + chips =============
    class ChipPicker {
        constructor(root, options, selectedIds, onChange) {
            this.root = root;
            this.options = options;
            this.selected = new Set(selectedIds.map(String));
            this.meta = new Map();
            options.forEach(o => { if (this.selected.has(String(o.id))) this.meta.set(String(o.id), o); });
            this.onChange = onChange;
            this.root.innerHTML = '<div class="en-pills en-picker-sel"></div><div class="en-picker-in"><input type="text" class="fv-input" placeholder="Buscar etiqueta…" autocomplete="off"><div class="en-picker-lista" hidden></div></div>';
            this.chipsEl = root.querySelector('.en-picker-sel');
            this.inputEl = root.querySelector('input');
            this.listaEl = root.querySelector('.en-picker-lista');
            this.inputEl.addEventListener('input', () => this.renderLista());
            this.inputEl.addEventListener('focus', () => this.renderLista());
            this.inputEl.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); this.listaEl.querySelector('button')?.click(); }
                if (e.key === 'Escape') { this.listaEl.hidden = true; this.inputEl.blur(); }
            });
            document.addEventListener('click', e => { if (!root.contains(e.target)) this.listaEl.hidden = true; });
            this.chipsEl.addEventListener('click', e => {
                const b = e.target.closest('[data-quitar]');
                if (!b) return;
                this.selected.delete(b.dataset.quitar);
                this.renderChips();
                this.onChange();
            });
            this.listaEl.addEventListener('click', e => {
                const b = e.target.closest('[data-poner]');
                if (!b) return;
                const o = this.options.find(x => String(x.id) === b.dataset.poner);
                this.selected.add(b.dataset.poner);
                if (o) this.meta.set(b.dataset.poner, o);
                this.inputEl.value = '';
                this.listaEl.hidden = true;
                this.renderChips();
                this.onChange();
            });
            this.renderChips();
        }
        setOptions(opts) {
            this.options = opts;
            opts.forEach(o => { if (this.selected.has(String(o.id))) this.meta.set(String(o.id), o); });
            this.renderChips();
            if (!this.listaEl.hidden) this.renderLista();
        }
        renderChips() {
            this.chipsEl.innerHTML = [...this.selected].map(id => {
                const o = this.meta.get(id) || this.options.find(x => String(x.id) === id);
                return o ? `<button type="button" class="en-chip on" data-quitar="${esc(id)}">${esc(o.nombre)}<i class="bi bi-x"></i></button>` : '';
            }).join('');
        }
        renderLista() {
            const q = this.inputEl.value.trim().toLowerCase();
            const m = this.options.filter(o => !this.selected.has(String(o.id)) && (!q || o.nombre.toLowerCase().includes(q))).slice(0, 30);
            this.listaEl.innerHTML = m.length
                ? m.map(o => `<button type="button" data-poner="${o.id}"><span>${esc(o.nombre)}</span><small>${o.count}</small></button>`).join('')
                : '<div class="t3" style="padding:8px 10px;font-size:12px">Sin etiquetas que casen</div>';
            this.listaEl.hidden = false;
        }
    }

    const tagPicker = new ChipPicker($('pickerEtiquetas'),
        <?= json_encode(array_map(fn ($t) => ['id' => (int) $t['etiqueta_id'], 'nombre' => $t['nombre'], 'count' => (int) $t['total']], $tagsDisp), JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode($tagIdsSel) ?>, onFilterChange);

    // ============= Categorías: pills =============
    const catSel = new Set(<?= json_encode(array_map('strval', $cats)) ?>);
    const catPills = $('catPills');
    const pintarCats = () => catPills.querySelectorAll('[data-cat]').forEach(b => b.classList.toggle('on', catSel.has(b.dataset.cat)));
    catPills.addEventListener('click', e => {
        const b = e.target.closest('[data-cat]');
        if (!b) return;
        catSel.has(b.dataset.cat) ? catSel.delete(b.dataset.cat) : catSel.add(b.dataset.cat);
        pintarCats();
        onFilterChange();
    });

    function verMatch() { $('matchWrap').hidden = (catSel.size + tagPicker.selected.size) < 2; }
    function onFilterChange() { verMatch(); liveSearch(true); }
    verMatch();

    const form = $('filtrosForm');
    const inputQ = form.querySelector('input[name="q"]');
    const inputVisto = $('inputVisto');
    const inputMatch = $('inputMatch');
    const btnFiltros = $('btnToggleFiltros');
    const panel = $('panelFiltros');
    const btnX = $('btnLimpiarTexto');

    btnFiltros.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        btnFiltros.setAttribute('aria-expanded', String(!panel.hidden));
    });

    // ============= Segmentados =============
    function syncSeg() {
        [['segEstado', inputVisto], ['segMatch', inputMatch]].forEach(([id, h]) =>
            $(id).querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.value === (h.value || (id === 'segMatch' ? 'any' : ''))))));
    }
    ['segEstado', 'segMatch'].forEach(id => $(id).addEventListener('click', e => {
        const b = e.target.closest('button');
        if (!b) return;
        (id === 'segEstado' ? inputVisto : inputMatch).value = b.dataset.value;
        syncSeg();
        liveSearch(true);
    }));
    syncSeg();

    // ============= Búsqueda en vivo =============
    const RES_URL = '<?= site_url('enlaces/buscar-resultados') ?>';
    const BASE_URL = '<?= site_url('enlaces') ?>';
    const elRes = $('enlResultados');
    const elChips = $('enlChips');
    let seq = 0, timer = null;

    function params() {
        const p = new URLSearchParams();
        const q = inputQ.value.trim();
        if (q) p.set('q', q);
        if (['0', '1', 'arch'].includes(inputVisto.value)) p.set('visto', inputVisto.value);
        catSel.forEach(id => p.append('cats[]', id));
        tagPicker.selected.forEach(id => p.append('tag_ids[]', id));
        if ((catSel.size + tagPicker.selected.size) >= 2 && inputMatch.value === 'all') p.set('match', 'all');
        return p;
    }

    function pintarCuenta(d) {
        $('enCount').textContent = d.modoReciente ? 'Últimos ' + d.total
            : d.hayMas ? d.topeResultados + '+ resultados'
            : d.total + (d.total === 1 ? ' resultado' : ' resultados');
        const n = btnFiltros.querySelector('.en-n');
        n.textContent = d.panelActiveCount;
        n.hidden = !d.panelActiveCount;
        btnFiltros.classList.toggle('on', d.panelActiveCount > 0);
    }

    async function liveSearch(push) {
        const mio = ++seq;
        const qs = params().toString();
        const url = qs ? BASE_URL + '?' + qs : BASE_URL;
        if (push) history.pushState(null, '', url); else history.replaceState(null, '', url);
        document.title = inputQ.value.trim() ? 'Enlaces · ' + inputQ.value.trim() : 'Enlaces';
        elRes.classList.add('en-cargando');
        try {
            const r = await fetch(RES_URL + (qs ? '?' + qs : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok || mio !== seq) return;
            const d = await r.json();
            if (mio !== seq) return;
            elRes.innerHTML = d.resultados;
            elChips.innerHTML = (d.chips || '').trim();
            pintarCuenta(d);
            tagPicker.setOptions(d.tagsDisp || []);
            refrescarEnfoque();
        } catch (e) {
            // fallo puntual de red: se reintenta en el siguiente cambio
        } finally {
            elRes.classList.remove('en-cargando');
        }
    }

    // Ids de una lista de la URL, venga como `cats[]=1`, `cats=1` o `cats[0]=1`.
    const lista = (ps, k) => [...ps].filter(([n]) => n === k || /^\w+\[\d*\]$/.test(n) && n.startsWith(k + '[')).map(([, v]) => String(v)).filter(Boolean);

    function applyUrl(href, push) {
        const p = new URL(href, location.origin).searchParams;
        inputQ.value = p.get('q') || '';
        pintarX();
        inputVisto.value = ['0', '1', 'arch'].includes(p.get('visto')) ? p.get('visto') : '';
        inputMatch.value = p.get('match') === 'all' ? 'all' : 'any';
        catSel.clear();
        lista(p, 'cats').forEach(id => catSel.add(id));
        tagPicker.selected = new Set(lista(p, 'tag_ids'));
        pintarCats();
        tagPicker.renderChips();
        syncSeg();
        verMatch();
        liveSearch(push);
    }

    function pintarX() {
        btnX.hidden = inputQ.value === '';
        $('kbdBarra').hidden = inputQ.value !== '';
    }

    form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(timer); liveSearch(true); });
    inputQ.addEventListener('input', () => { pintarX(); clearTimeout(timer); timer = setTimeout(() => liveSearch(false), 300); });
    btnX.addEventListener('click', () => { inputQ.value = ''; pintarX(); inputQ.focus(); clearTimeout(timer); liveSearch(true); });
    $('btnQuitarFiltros').addEventListener('click', () => {
        catSel.clear();
        tagPicker.selected.clear();
        inputVisto.value = '';
        inputMatch.value = 'any';
        pintarCats();
        tagPicker.renderChips();
        syncSeg();
        verMatch();
        liveSearch(true);
    });

    const escribiendo = t => t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
    document.addEventListener('keydown', e => {
        if (!elFocus.hidden || e.metaKey || e.ctrlKey || e.altKey || escribiendo(e.target)) return;
        if (e.key === '/') { e.preventDefault(); inputQ.focus(); inputQ.select(); }
        else if (e.key.toLowerCase() === 'e') { e.preventDefault(); abrirEnfoque(); }
    });

    document.addEventListener('click', e => {
        const a = e.target.closest('a[data-enl-nav]');
        if (!a || e.metaKey || e.ctrlKey) return;
        e.preventDefault();
        applyUrl(a.getAttribute('href'), true);
    });
    window.addEventListener('popstate', () => applyUrl(location.href, false));

    // ============= Visto / desarchivar (delegado: la lista se re-renderiza) =============
    const VISTO_URL = '<?= site_url('enlaces/toggle-visto') ?>';
    const DESARCH_URL = '<?= site_url('enlaces/desarchivar') ?>';
    const post = url => fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });

    function pintarVisto(fila, visto) {
        fila.classList.toggle('visto', visto);
        const b = fila.querySelector('.btn-toggle-visto');
        b.setAttribute('aria-pressed', String(visto));
        b.title = visto ? 'Visto (tocar para desmarcar)' : 'Marcar como visto';
        b.querySelector('i').className = 'bi ' + (visto ? 'bi-check-circle-fill' : 'bi-circle');
    }

    async function toggleVisto(fila) {
        const r = await post(VISTO_URL + '/' + fila.dataset.id);
        if (!r.ok) return false;
        if (navigator.vibrate) navigator.vibrate(15);
        pintarVisto(fila, !fila.classList.contains('visto'));
        // Con el filtro de estado puesto, la fila ya no encaja: se refresca la lista.
        if (inputVisto.value === '0' || inputVisto.value === '1') setTimeout(() => liveSearch(false), 450);
        return true;
    }

    elRes.addEventListener('click', async e => {
        const v = e.target.closest('.btn-toggle-visto');
        if (v) { v.disabled = true; await toggleVisto(v.closest('.en-item')); v.disabled = false; return; }
        const d = e.target.closest('.btn-desarchivar');
        if (d) { d.disabled = true; const r = await post(DESARCH_URL + '/' + d.dataset.id); if (r.ok) liveSearch(false); else d.disabled = false; }
    });

    // ============= Modo enfoque =============
    const elFocus = $('enlFocus');
    const focusCard = $('focusCard');
    let items = [], idx = 0;
    const filas = () => [...elRes.querySelectorAll('.en-item')];

    function pintarEnfoque() {
        const f = items[idx];
        if (!f) return;
        $('focusPos').textContent = (idx + 1) + ' / ' + items.length;
        const t = f.querySelector('.en-titulo');
        const visto = f.classList.contains('visto');
        const html = sel => f.querySelector(sel)?.innerHTML || '';
        focusCard.innerHTML =
            `<div class="rc-meta en-meta">${html('.en-meta')}</div>`
            + `<a class="en-foco-titulo" href="${esc(t.href)}" target="_blank" rel="noopener">${esc(t.textContent.trim())}</a>`
            + (f.querySelector('.en-match') ? `<div class="en-match">${html('.en-match')}</div>` : '')
            + (f.querySelector('.en-nota') ? `<div class="en-foco-nota">${html('.en-nota')}</div>` : '')
            + (f.querySelector('.en-badges') ? `<div class="en-badges">${html('.en-badges')}</div>` : '')
            + `<div class="en-foco-acc">`
            + `<a class="fv-btn fv-btn-pri" href="${esc(t.href)}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>Abrir <kbd>O</kbd></a>`
            + `<button type="button" class="fv-btn${visto ? ' en-ok' : ''}" data-focus="visto"><i class="bi ${visto ? 'bi-check-circle-fill' : 'bi-circle'}"></i>${visto ? 'Visto' : 'Marcar visto'} <kbd>V</kbd></button>`
            + `<a class="fv-btn" href="${esc(f.querySelector('.en-pagina').href)}"><i class="bi bi-journal-text"></i>Página</a>`
            + `<a class="fv-btn" href="${esc(f.querySelector('.en-editar').href)}"><i class="bi bi-pencil"></i>Editar</a>`
            + `</div>`;
    }
    function abrirEnfoque() {
        items = filas();
        if (!items.length) return;
        idx = 0;
        elFocus.hidden = false;
        document.body.style.overflow = 'hidden';
        pintarEnfoque();
    }
    function cerrarEnfoque() { elFocus.hidden = true; document.body.style.overflow = ''; }
    function mover(d) { idx = (idx + d + items.length) % items.length; pintarEnfoque(); }
    function refrescarEnfoque() {
        if (elFocus.hidden) return;
        items = filas();
        if (!items.length) { cerrarEnfoque(); return; }
        idx = Math.min(idx, items.length - 1);
        pintarEnfoque();
    }
    async function vistoEnfoque() {
        const f = items[idx];
        if (f && await toggleVisto(f)) pintarEnfoque();
    }

    $('btnEnfoque').addEventListener('click', abrirEnfoque);
    elFocus.addEventListener('click', e => {
        const a = e.target.closest('[data-focus]')?.dataset.focus;
        if (a === 'prev') mover(-1);
        else if (a === 'next') mover(1);
        else if (a === 'close') cerrarEnfoque();
        else if (a === 'visto') vistoEnfoque();
    });
    document.addEventListener('keydown', e => {
        if (elFocus.hidden || escribiendo(e.target)) return;
        const k = e.key.toLowerCase();
        if (e.key === 'Escape') cerrarEnfoque();
        else if (e.key === 'ArrowLeft') { e.preventDefault(); mover(-1); }
        else if (e.key === 'ArrowRight') { e.preventDefault(); mover(1); }
        else if (k === 'o') { const t = items[idx]?.querySelector('.en-titulo'); if (t) window.open(t.href, '_blank', 'noopener'); }
        else if (k === 'v') vistoEnfoque();
    });
})();
</script>
<?php $this->endSection(); ?>
