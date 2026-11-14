<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/compras.css') ?>?v=4">
<?php $sid = (int) $supermercado['id']; ?>
<div class="fv cp" id="cp">
    <?php if (session('message')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i><?= esc(session('message')) ?></div>
    <?php endif; ?>

    <div class="fv-top">
        <div class="fv-crumbs">
            <a href="<?= site_url('compras') ?>">Compras</a>
            <i class="bi bi-chevron-right"></i>
            <span><?= esc($supermercado['nombre']) ?></span>
        </div>
        <div class="fv-acciones">
            <button type="button" class="fv-gbtn" id="cpVista"></button>
            <button type="button" class="fv-gbtn" id="cpCat"><i class="bi bi-sliders"></i>Catálogo</button>
            <a class="fv-gbtn" href="<?= site_url('compras/supermercados/' . $sid . '/zonas') ?>"><i class="bi bi-signpost-split"></i>Zonas</a>
            <a class="fv-gbtn" href="<?= site_url('compras/supermercados/editar/' . $sid) ?>"><i class="bi bi-pencil"></i>Editar</a>
            <span class="fv-vsep"></span>
            <form action="<?= site_url('compras/limpiar/faltantes/' . $sid) ?>" method="post" class="m-0"
                  onsubmit="return confirm('¿Vaciar toda la lista de <?= esc($supermercado['nombre'], 'js') ?>? Se quita todo lo marcado y lo fuera de carta.')">
                <?= csrf_field() ?>
                <button class="fv-gbtn peligro"><i class="bi bi-eraser"></i>Vaciar</button>
            </form>
            <a class="fv-gbtn" href="<?= site_url('compras') ?>?nueva=0"><i class="bi bi-arrow-left-right"></i>Clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1><?= esc($supermercado['nombre']) ?></h1>
        <div class="fv-meta">
            <span class="fv-estado" id="cpEstado"></span>
            <span class="fv-dot"></span>
            <span class="cp-mono" id="cpTotalMeta"></span>
            <span class="fv-dot"></span>
            <span id="cpNCat"></span>
        </div>
    </div>

    <div class="cp-barra" id="cpBarra" hidden>
        <label class="cp-campo">
            <i class="bi bi-search"></i>
            <input type="text" id="cpQ" placeholder="Buscar o apuntar algo fuera de carta" autocomplete="off" enterkeyhint="done">
        </label>
        <button type="button" class="cp-chip" id="cpHab"><i class="bi bi-star-fill"></i>Habituales</button>
    </div>

    <form class="cp-alta" id="cpAlta" action="<?= site_url('compras/productos/nuevo') ?>" method="post" hidden>
        <?= csrf_field() ?>
        <input type="hidden" name="supermercado_id" value="<?= $sid ?>">
        <input type="text" name="nombre" class="fv-input" placeholder="Producto nuevo" required>
        <select name="zona_id" class="fv-input">
            <option value="">Sin zona</option>
            <?php foreach ($zonas as $z): ?>
                <option value="<?= $z['id'] ?>"><?= esc($z['n']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="url" name="imagen" class="fv-input" placeholder="URL de la imagen">
        <button class="fv-btn"><i class="bi bi-plus-lg"></i>Añadir</button>
    </form>

    <div class="cp-cuerpo" id="cpCuerpo"></div>

    <div class="cp-pie">
        <div class="cp-total"><span id="cpProgreso"></span><span class="cp-mono" id="cpTotalPie"></span></div>
        <div class="cp-nivel"><i id="cpNivel"></i></div>
        <div class="cp-modo">
            <button type="button" data-modo="casa">En casa<span class="n" id="cpNFalta"></span></button>
            <button type="button" data-modo="tienda">En la tienda<span class="n" id="cpNHecho"></span></button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPrecio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form id="formPrecio">
                <div class="modal-header">
                    <h6 class="modal-title mb-0" id="tituloPrecio">Precio</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="inputPrecio" class="form-label small">Precio (€). Vacío para quitarlo.</label>
                    <input type="number" step="0.01" min="0" inputmode="decimal" class="form-control" id="inputPrecio">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const J = <?= json_encode(['zonas' => $zonas, 'productos' => $productos, 'sueltos' => $sueltos], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    const SID = <?= $sid ?>;
    const BASE = '<?= site_url('compras') ?>';
    const CSRF = '<?= csrf_field() ?>';
    const P = J.productos, ZONAS = J.zonas;
    P.forEach((p, i) => p.o = i);
    let sueltos = J.sueltos;

    const $ = id => document.getElementById(id);
    const cuerpo = $('cpCuerpo'), inputQ = $('cpQ');
    const esc = t => String(t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const eur = n => Number(n).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    const plano = t => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    const porId = id => P.find(p => p.id === id);
    const guarda = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };
    const lee = k => { try { return localStorage.getItem(k); } catch (e) { return null; } };

    const s = { modo: 'casa', vista: lee('cpVista') === 'rejilla' ? 'rejilla' : 'lista', q: '', hab: false, verHechos: true };

    async function post(ruta, datos) {
        try {
            const res = await fetch(BASE + '/' + ruta, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
                body: JSON.stringify(datos || {}),
            });
            if (!res.ok) return null;
            return res.status === 204 ? {} : await res.json();
        } catch (e) {
            return null;
        }
    }

    const cuenta = () => {
        const enLista = P.filter(p => p.falta);
        return {
            nf: enLista.length + sueltos.length,
            nh: enLista.filter(p => p.hecho).length + sueltos.filter(x => x.h).length,
            total: enLista.reduce((t, p) => t + (p.pr ?? 0), 0),
            sin: enLista.filter(p => p.pr === null).length + sueltos.length,
        };
    };

    const mini = p => `<span class="cp-th">${p.img ? `<img src="${esc(p.img)}" alt="" loading="lazy">` : esc(p.n.charAt(0).toUpperCase())}</span>`;
    const precio = p => `<span class="cp-pr${p.pr === null ? ' no' : ''}">${p.pr === null ? '' : eur(p.pr)}</span>`;
    const check = '<span class="cp-ck"><i class="bi bi-check-lg"></i></span>';

    function item(p) {
        if (s.modo === 'catalogo') {
            return `<div class="cp-it" data-id="${p.id}">
                <span class="cp-grip" title="Arrastrar"><i class="bi bi-grip-vertical"></i></span>${mini(p)}
                <a class="cp-nm" href="${BASE}/productos/editar/${p.id}">${esc(p.n)}</a>
                <button type="button" class="cp-pr${p.pr === null ? ' no' : ''}" data-act="precio" data-id="${p.id}">${p.pr === null ? '+ precio' : eur(p.pr)}</button>
                <button type="button" class="cp-star${p.fav ? ' on' : ''}" data-act="star" data-id="${p.id}" title="Habitual"><i class="bi ${p.fav ? 'bi-star-fill' : 'bi-star'}"></i></button>
            </div>`;
        }
        const cls = s.modo === 'casa' ? (p.falta ? ' on' : '') : (p.hecho ? ' hecho' : '');
        return `<button type="button" class="cp-it${cls}" data-act="tog" data-id="${p.id}">${mini(p)}<span class="cp-nm">${esc(p.n)}</span>${precio(p)}${check}</button>`;
    }

    const suelto = x => `<button type="button" class="cp-it${s.modo === 'casa' ? ' on' : (x.h ? ' hecho' : '')}" data-act="sue" data-id="${x.id}">
        <span class="cp-th suelto"><i class="bi bi-plus-lg"></i></span><span class="cp-nm">${esc(x.n)}<small>solo esta vez</small></span>${check}</button>`;

    const rej = () => s.vista === 'rejilla' && s.modo !== 'catalogo' ? ' rej' : '';

    function grupos(lista, vacias) {
        let h = '';
        [...ZONAS, { id: null, n: 'Sin zona' }].forEach(z => {
            const ps = lista.filter(p => p.z === z.id).sort((a, b) => a.o - b.o);
            if (!ps.length && !vacias) return;
            h += `<div class="cp-zona">${esc(z.n)}<span class="n">${ps.length}</span></div><div class="cp-g${rej()}" data-zona="${z.id ?? ''}">${ps.map(item).join('')}</div>`;
        });
        return h;
    }

    const grupoSueltos = xs => xs.length
        ? `<div class="cp-zona">Fuera de carta<span class="n">${xs.length}</span></div><div class="cp-g${rej()}">${xs.map(suelto).join('')}</div>`
        : '';

    function pintar() {
        const q = plano(s.q.trim());
        let h = '';
        if (s.modo === 'catalogo') {
            h = P.length || ZONAS.length ? grupos(P, true) : '<div class="cp-vacio">Todavía no hay productos. Añade el primero arriba.</div>';
        } else if (s.modo === 'casa') {
            const lista = P.filter(p => (!q || plano(p.n).includes(q)) && (!s.hab || p.fav));
            const existe = P.some(p => plano(p.n) === q) || sueltos.some(x => plano(x.n) === q);
            if (q && !existe) h += `<div class="cp-bloque"><button type="button" class="fv-btn cp-suelto-btn" data-act="addsuelto"><i class="bi bi-plus-lg"></i><span>Apuntar <b>${esc(s.q.trim())}</b> solo esta vez</span></button></div>`;
            if (!q) h += grupoSueltos(sueltos);
            h += grupos(lista, false);
            if (!lista.length) h += `<div class="cp-vacio">${q ? 'Nada en el catálogo con ese nombre.' : (s.hab ? 'No hay habituales marcados. Márcalos con la estrella en Catálogo.' : 'El catálogo está vacío. Añade productos en Catálogo.')}</div>`;
        } else {
            const enLista = P.filter(p => p.falta), c = cuenta();
            if (!c.nf) {
                h = '<div class="cp-vacio">No falta nada. Marca productos en En casa.</div>';
            } else {
                h += grupos(enLista.filter(p => !p.hecho), false) + grupoSueltos(sueltos.filter(x => !x.h));
                if (c.nh === c.nf) h += '<div class="cp-vacio">Todo en el carro.</div>';
                if (c.nh) {
                    h += `<button type="button" class="cp-plegar" data-act="verhechos"><i class="bi bi-chevron-${s.verHechos ? 'up' : 'down'}"></i>En el carro · ${c.nh}</button>`;
                    if (s.verHechos) h += `<div class="cp-g${rej()}">${enLista.filter(p => p.hecho).sort((a, b) => a.o - b.o).map(item).join('')}${sueltos.filter(x => x.h).map(suelto).join('')}</div>`;
                    h += `<form class="cp-bloque" action="${BASE}/${SID}/terminar" method="post" data-confirma="${c.nh === c.nf ? '¿Terminar la compra y vaciar la lista?' : '¿Terminar la compra? Lo que está en el carro sale de la lista; lo que no has cogido se queda apuntado.'}">${CSRF}<button class="fv-btn${c.nh === c.nf ? ' fv-btn-pri' : ''}"><i class="bi bi-bag-check"></i>Terminar compra</button></form>`;
                }
            }
        }
        cuerpo.innerHTML = h;
        cuerpo.classList.toggle('cp-cat', s.modo === 'catalogo');
        if (s.modo === 'catalogo') ordenable();
    }

    function contadores() {
        const c = cuenta(), est = $('cpEstado');
        est.className = 'fv-estado ' + (c.nf ? (c.nh === c.nf ? 'e-ok' : 'e-warn') : 'e-neu');
        est.textContent = c.nf ? (c.nh === c.nf ? 'Todo en el carro' : (c.nf - c.nh) + ' por comprar') : 'Nada pendiente';
        const total = eur(c.total) + (c.sin ? ' · ' + c.sin + ' sin precio' : '');
        $('cpTotalMeta').textContent = eur(c.total) + ' estimado';
        $('cpNCat').textContent = P.length + ' en catálogo';
        $('cpProgreso').textContent = c.nf ? c.nh + ' de ' + c.nf + ' en el carro' : 'Lista vacía';
        $('cpTotalPie').textContent = total;
        $('cpNivel').style.width = (c.nf ? c.nh / c.nf * 100 : 0) + '%';
        $('cpNFalta').textContent = c.nf;
        $('cpNHecho').textContent = c.nh + '/' + c.nf;
    }

    function cromo() {
        $('cpBarra').hidden = s.modo !== 'casa';
        $('cpAlta').hidden = s.modo !== 'catalogo';
        $('cpVista').hidden = s.modo === 'catalogo';
        $('cpVista').innerHTML = s.vista === 'lista' ? '<i class="bi bi-grid"></i>Rejilla' : '<i class="bi bi-list"></i>Filas';
        $('cpCat').classList.toggle('on', s.modo === 'catalogo');
        $('cpHab').classList.toggle('on', s.hab);
        document.querySelectorAll('.cp-modo button').forEach(b => b.classList.toggle('on', b.dataset.modo === s.modo));
    }

    function modo(m) {
        s.modo = m;
        s.q = inputQ.value = '';
        history.replaceState(null, '', '#' + m);
        cromo();
        pintar();
        window.scrollTo({ top: 0 });
    }

    function ordenable() {
        cuerpo.querySelectorAll('.cp-g').forEach(g => Sortable.create(g, {
            group: 'cp-zonas', animation: 150, handle: '.cp-grip', ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
            onEnd: async evt => {
                const zona = evt.to.dataset.zona === '' ? null : +evt.to.dataset.zona;
                const ids = [...evt.to.children].map(el => +el.dataset.id);
                const r = await post('productos/reordenar', { zona_id: zona ?? '', orden: ids });
                if (r) ids.forEach((id, i) => { const p = porId(id); p.z = zona; p.o = i; });
                pintar();
            },
        }));
    }

    async function alternar(p, boton) {
        if (s.modo === 'casa') {
            const antes = { falta: p.falta, hecho: p.hecho };
            p.falta = !p.falta;
            if (!p.falta) p.hecho = false;
            boton.classList.toggle('on', p.falta);
            contadores();
            if (!await post('producto/' + p.id + (p.falta ? '/marcar-faltante' : '/desmarcar-faltante'))) {
                Object.assign(p, antes);
                boton.classList.toggle('on', p.falta);
                contadores();
            }
        } else {
            p.hecho = !p.hecho;
            pintar(); contadores();
            if (!await post('producto/' + p.id + (p.hecho ? '/marcar-comprado' : '/desmarcar-comprado'))) {
                p.hecho = !p.hecho;
                pintar(); contadores();
            }
        }
    }

    async function apuntarSuelto() {
        const nombre = s.q.trim();
        if (!nombre) return;
        const r = await post('sueltos/nuevo', { supermercado_id: SID, nombre });
        if (!r) return;
        sueltos.push({ id: r.id, n: nombre, h: false });
        s.q = inputQ.value = '';
        pintar(); contadores();
        $('cpBarra').scrollIntoView({ block: 'start' });
        inputQ.focus({ preventScroll: true });
    }

    let modalPrecio, precioDe = null;
    function abrirPrecio(p) {
        precioDe = p;
        $('tituloPrecio').textContent = p.n;
        $('inputPrecio').value = p.pr ?? '';
        modalPrecio ??= new bootstrap.Modal($('modalPrecio'));
        modalPrecio.show();
        setTimeout(() => $('inputPrecio').focus(), 300);
    }
    $('formPrecio').addEventListener('submit', async e => {
        e.preventDefault();
        if (!precioDe) return;
        const r = await post('productos/' + precioDe.id + '/precio', { precio: $('inputPrecio').value });
        if (!r) return;
        precioDe.pr = r.precio === null ? null : Number(r.precio);
        modalPrecio.hide();
        pintar(); contadores();
    });

    cuerpo.addEventListener('click', async e => {
        const b = e.target.closest('[data-act]');
        if (!b) return;
        const a = b.dataset.act, id = +b.dataset.id;
        if (a === 'tog') return alternar(porId(id), b);
        if (a === 'addsuelto') return apuntarSuelto();
        if (a === 'verhechos') { s.verHechos = !s.verHechos; return pintar(); }
        if (a === 'precio') return abrirPrecio(porId(id));
        if (a === 'star') {
            const r = await post('productos/' + id + '/favorito');
            if (!r) return;
            porId(id).fav = !!r.favorito;
            b.classList.toggle('on', r.favorito);
            b.innerHTML = `<i class="bi ${r.favorito ? 'bi-star-fill' : 'bi-star'}"></i>`;
            return;
        }
        if (a === 'sue') {
            const x = sueltos.find(x => x.id === id);
            if (s.modo === 'casa') {
                if (!confirm('¿Quitar «' + x.n + '» de la lista?')) return;
                if (!await post('sueltos/' + id + '/borrar')) return;
                sueltos = sueltos.filter(y => y.id !== id);
            } else {
                const r = await post('sueltos/' + id + '/comprado');
                if (!r) return;
                x.h = !!r.comprado;
            }
            pintar(); contadores();
        }
    });
    cuerpo.addEventListener('submit', e => {
        const msg = e.target.dataset.confirma;
        if (msg && !confirm(msg)) e.preventDefault();
    });

    inputQ.addEventListener('input', () => { s.q = inputQ.value; pintar(); });
    inputQ.addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        if (cuerpo.querySelector('[data-act="addsuelto"]')) apuntarSuelto();
    });
    $('cpHab').addEventListener('click', () => { s.hab = !s.hab; cromo(); pintar(); });
    $('cpVista').addEventListener('click', () => { s.vista = s.vista === 'lista' ? 'rejilla' : 'lista'; guarda('cpVista', s.vista); cromo(); pintar(); });
    $('cpCat').addEventListener('click', () => modo(s.modo === 'catalogo' ? 'casa' : 'catalogo'));
    document.querySelectorAll('.cp-modo button').forEach(b => b.addEventListener('click', () => modo(b.dataset.modo)));

    window.addEventListener('hashchange', () => {
        const m = location.hash.slice(1);
        if (['casa', 'tienda', 'catalogo'].includes(m) && m !== s.modo) modo(m);
    });

    const inicial = location.hash.slice(1);
    s.modo = ['casa', 'tienda', 'catalogo'].includes(inicial) ? inicial : (cuenta().nf ? 'tienda' : 'casa');
    cromo(); pintar(); contadores();
})();
</script>
<?= $this->endSection() ?>
