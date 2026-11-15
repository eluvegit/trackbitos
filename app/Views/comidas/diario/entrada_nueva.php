<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$f = $fechaSel->format('Y-m-d');
$orden = ['desayuno', 'almuerzo', 'merienda', 'cena', 'nocturna'];
$titulo = ucfirst(\CodeIgniter\I18n\Time::parse($f)->toLocalizedString("EEEE d 'de' MMMM"));
?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa' => 'diario',
        'migas'  => [$titulo => site_url('comidas/diario/' . $f)],
        'titulo' => 'Añadir a ' . esc(ucfirst($tipo)),
        'sub'    => '<a href="' . site_url('comidas/diario/' . $f) . '"><i class="bi bi-arrow-left"></i> Volver al día</a>',
    ]) ?>

    <nav class="cm-seg">
        <?php foreach ($orden as $t): ?>
            <?php $n = count($ingPorTipo[$t] ?? []); ?>
            <a href="<?= site_url('comidas/diario/' . $f . '/' . $t) ?>" class="<?= $t === $tipo ? 'on' : '' ?>"><?= ucfirst($t) ?><?php if ($n): ?> <small><?= $n ?></small><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>

    <section class="rc-sec">
        <div class="cm-busca">
            <i class="bi bi-search"></i>
            <input type="search" id="cmBuscar" class="fv-input" placeholder="Buscar alimento o receta…" autocomplete="off" enterkeyhint="search">
            <span class="cm-cargando" id="cmCargando" hidden></span>
        </div>
        <div class="cm-res" id="cmRes" hidden></div>

        <form class="cm-sel" id="cmSel" hidden>
            <div class="cm-sel-cab">
                <div style="display:grid;gap:3px;min-width:0">
                    <span class="n" id="cmSelNombre"></span>
                    <span class="s" id="cmSel100"></span>
                </div>
                <button type="button" class="fv-ibtn" id="cmSelQuitar" title="Quitar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="cm-cant">
                <label class="rc-campo"><span>Gramos</span><input type="number" step="0.1" min="0" inputmode="decimal" class="fv-input rc-grande cm-num" id="cmGramos" placeholder="100"></label>
                <label class="rc-campo"><span>o porción</span><select class="fv-input rc-sel" id="cmPorcion"><option value="">—</option></select></label>
                <label class="rc-campo"><span>Nº</span><input type="number" step="0.25" min="0.25" inputmode="decimal" class="fv-input cm-num" id="cmNum" value="1"></label>
            </div>
            <div class="cm-calc" id="cmCalc">
                <div><span class="k">kcal</span><span class="v" data-k="kcal">0</span></div>
                <div><span class="k">Prot.</span><span class="v" data-k="p">0<small> g</small></span></div>
                <div><span class="k">Carb.</span><span class="v" data-k="c">0<small> g</small></span></div>
                <div><span class="k">Grasa</span><span class="v" data-k="g">0<small> g</small></span></div>
            </div>
            <button type="submit" class="fv-btn fv-btn-pri" style="height:44px"><i class="bi bi-plus-lg"></i>Añadir a <?= esc(ucfirst($tipo)) ?></button>
        </form>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3><?= esc(ucfirst($tipo)) ?></h3><span class="cuenta" id="cmCuenta"></span></div>
        <div class="fv-panel" style="padding:0;gap:0;overflow:hidden" id="cmLista"><div class="rc-vacio">Cargando…</div></div>
        <p class="rc-ayuda">Toca un registro para cambiar la cantidad o eliminarlo.</p>
    </section>
</div>

<?= view('comidas/diario/_editar_ingesta') ?>

<script>
(() => {
    const FECHA = <?= json_encode($f) ?>, TIPO = <?= json_encode($tipo) ?>;
    const API = {
        buscar: '<?= site_url('api/alimentos') ?>',
        porciones: '<?= site_url('comidas/diario/porciones') ?>',
        ingestas: '<?= site_url('api/ingestas') ?>/' + FECHA + '/' + TIPO,
        add: '<?= site_url('api/add') ?>',
    };
    const CSRF = [<?= json_encode(csrf_token()) ?>, <?= json_encode(csrf_hash()) ?>];
    const $ = id => document.getElementById(id);
    const buscar = $('cmBuscar'), res = $('cmRes'), sel = $('cmSel'), gramos = $('cmGramos'), porcion = $('cmPorcion'), num = $('cmNum'), lista = $('cmLista');
    const f0 = n => String(Math.round(+n || 0));
    const f1 = n => { const s = (Math.round((+n || 0) * 10) / 10).toFixed(1); return s.endsWith('.0') ? s.slice(0, -2) : s; };
    const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const getJson = async u => (await fetch(u, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json();
    let elegido = null, temporizador = null, peticion = 0, activo = -1;

    const pintarRes = rows => {
        activo = -1;
        if (!rows.length) { res.innerHTML = '<div class="vacio">Sin resultados</div>'; res.hidden = false; return; }
        res.innerHTML = rows.map(r => `<button type="button" data-id="${r.id}" data-nombre="${esc(r.nombre)}">${esc(r.nombre)}<i class="bi bi-plus-lg"></i></button>`).join('');
        res.hidden = false;
    };
    const lanzarBusqueda = () => {
        const q = buscar.value.trim();
        clearTimeout(temporizador);
        if (!q) { res.hidden = true; res.innerHTML = ''; return; }
        temporizador = setTimeout(async () => {
            const yo = ++peticion;
            $('cmCargando').hidden = false;
            try { const rows = await getJson(API.buscar + '?' + new URLSearchParams({ q })); if (yo === peticion) pintarRes(rows); }
            catch (e) { res.innerHTML = '<div class="vacio">Error buscando…</div>'; res.hidden = false; }
            finally { if (yo === peticion) $('cmCargando').hidden = true; }
        }, 180);
    };
    buscar.addEventListener('input', lanzarBusqueda);
    buscar.addEventListener('keydown', ev => {
        const bs = [...res.querySelectorAll('button')];
        if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
            if (!bs.length) return;
            ev.preventDefault();
            activo = (activo + (ev.key === 'ArrowDown' ? 1 : -1) + bs.length) % bs.length;
            bs.forEach((b, i) => b.classList.toggle('on', i === activo));
            bs[activo].scrollIntoView({ block: 'nearest' });
        } else if (ev.key === 'Enter') {
            ev.preventDefault();
            (bs[activo] || bs[0])?.click();
        } else if (ev.key === 'Escape') {
            buscar.value = ''; lanzarBusqueda();
        }
    });

    const gramosActivos = () => {
        const g = parseFloat(String(gramos.value).replace(',', '.')) || 0;
        if (g > 0) return g;
        const pg = parseFloat(porcion.selectedOptions[0]?.dataset.g || '') || 0;
        const n = parseFloat(String(num.value).replace(',', '.')) || 0;
        return pg * n;
    };
    const calcular = () => {
        if (!elegido) return;
        const m = elegido.m, x = gramosActivos() / 100;
        const v = { kcal: m.kcal * x, p: m.p * x, c: m.c * x, g: m.g * x };
        $('cmCalc').querySelectorAll('[data-k]').forEach(el => { const k = el.dataset.k; el.firstChild.textContent = k === 'kcal' ? f0(v[k]) : f1(v[k]); });
    };
    [gramos, num].forEach(i => i.addEventListener('input', calcular));
    gramos.addEventListener('input', () => { if (gramos.value) porcion.value = ''; });
    porcion.addEventListener('change', () => { if (porcion.value) gramos.value = ''; calcular(); });

    const quitar = () => { elegido = null; sel.hidden = true; gramos.value = ''; num.value = '1'; porcion.innerHTML = '<option value="">—</option>'; };
    $('cmSelQuitar').addEventListener('click', () => { quitar(); buscar.focus(); });

    res.addEventListener('click', async ev => {
        const b = ev.target.closest('button[data-id]');
        if (!b) return;
        const id = b.dataset.id;
        res.hidden = true;
        let d = {};
        try { d = await getJson(API.buscar + '/' + id); } catch (e) {}
        elegido = { id, nombre: b.dataset.nombre, m: { kcal: +d.kcal || 0, p: +d.proteina_g || 0, c: +d.carbohidratos_g || 0, g: +d.grasas_g || 0 } };
        $('cmSelNombre').textContent = elegido.nombre;
        $('cmSel100').textContent = `100 g · ${f0(elegido.m.kcal)} kcal · P ${f1(elegido.m.p)} · C ${f1(elegido.m.c)} · G ${f1(elegido.m.g)}`;
        gramos.value = ''; num.value = '1';
        porcion.innerHTML = '<option value="">Cargando…</option>';
        sel.hidden = false;
        try {
            const ps = await getJson(API.porciones + '/' + id);
            porcion.innerHTML = '<option value="">' + (ps.length ? '—' : 'Sin porciones') + '</option>' + ps.map(p => `<option value="${p.id}" data-g="${p.gramos_equivalentes || ''}"${+p.es_predeterminada ? ' selected' : ''}>${esc(p.descripcion || 'Porción')}${+p.gramos_equivalentes ? ' · ' + f0(p.gramos_equivalentes) + ' g' : ''}</option>`).join('');
        } catch (e) { porcion.innerHTML = '<option value="">—</option>'; }
        calcular();
        if (!porcion.value) gramos.focus();
    });

    sel.addEventListener('submit', async ev => {
        ev.preventDefault();
        if (!elegido) return;
        const g = parseFloat(String(gramos.value).replace(',', '.')) || 0;
        const data = { fecha: FECHA, tipo: TIPO, item_id: elegido.id, cantidad_gramos: '', porcion_id: '', porciones: '' };
        if (g > 0) data.cantidad_gramos = String(g);
        else if (porcion.value) { data.porcion_id = porcion.value; data.porciones = num.value || '1'; }
        else { gramos.focus(); return; }
        data[CSRF[0]] = CSRF[1];
        const btn = sel.querySelector('[type=submit]');
        btn.disabled = true;
        try {
            const r = await (await fetch(API.add, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams(data) })).json();
            if (!r.ok) { alert('No se pudo añadir: ' + (r.error || 'error desconocido')); return; }
            quitar(); buscar.value = ''; res.hidden = true;
            await cargar();
            buscar.focus();
        } catch (e) { alert('Error de red al guardar.'); }
        finally { btn.disabled = false; }
    });

    const cargar = async () => {
        let rows = [];
        try { rows = await getJson(API.ingestas); } catch (e) { lista.innerHTML = '<div class="rc-vacio">Error al cargar.</div>'; return; }
        $('cmCuenta').textContent = rows.length || '';
        if (!rows.length) { lista.innerHTML = '<div class="rc-vacio">Todavía no hay nada apuntado.</div>'; return; }
        const t = { kcal: 0, p: 0, c: 0, g: 0, gr: 0 };
        const filas = rows.map(r => {
            const g = +r.cantidad_gramos || 0, x = g / 100;
            const v = { kcal: (+r.kcal || 0) * x, p: (+r.proteina_g || 0) * x, c: (+r.carbohidratos_g || 0) * x, g: (+r.grasas_g || 0) * x };
            t.kcal += v.kcal; t.p += v.p; t.c += v.c; t.g += v.g; t.gr += g;
            return `<tr data-id="${r.id}" data-nombre="${esc(r.nombre || '—')}" data-cantidad="${g}"><td>${esc(r.nombre || '—')}</td><td>${f1(g)}</td><td>${f0(v.kcal)}</td><td>${f1(v.p)}</td><td class="opc">${f1(v.c)}</td><td class="opc">${f1(v.g)}</td></tr>`;
        }).join('');
        lista.innerHTML = `<table class="cm-tabla"><thead><tr><th>Alimento</th><th>g</th><th>kcal</th><th>P</th><th class="opc">C</th><th class="opc">G</th></tr></thead><tbody>${filas}</tbody>
            <tfoot><tr><td>Total</td><td>${f0(t.gr)}</td><td>${f0(t.kcal)}</td><td>${f1(t.p)}</td><td class="opc">${f1(t.c)}</td><td class="opc">${f1(t.g)}</td></tr></tfoot></table>`;
    };
    lista.addEventListener('click', ev => {
        const tr = ev.target.closest('tbody tr[data-id]');
        if (tr) window.cmEditarIngesta(tr.dataset, cargar);
    });

    cargar();
    if (matchMedia('(hover: hover)').matches) buscar.focus();
})();
</script>
<?= $this->endSection() ?>
