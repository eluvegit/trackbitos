<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$id = (int) ($row['id'] ?? 0);
$nombre = old('nombre', $row['nombre'] ?? '');
?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa'   => 'recetas',
        'migas'    => ['Recetas' => site_url('comidas/recetas'), ($id ? $nombre : 'Nueva') => null],
        'titulo'   => $id ? esc($nombre) : 'Nueva receta',
        'sub'      => $id ? '<span>Las calorías se recalculan con cada ingrediente</span>' : '<span>Ponle nombre; después podrás añadir los ingredientes</span>',
        'acciones' => $id ? '<button type="submit" form="cmBorrar" class="fv-btn fv-btn-peligro"><i class="bi bi-trash"></i>Eliminar</button>' : '',
    ]) ?>

    <form method="post" action="<?= $id ? site_url('comidas/recetas/update/' . $id) : site_url('comidas/recetas/store') ?>" class="rc-form">
        <?= csrf_field() ?>
        <details class="cm-plegable"<?= $id ? '' : ' open' ?>>
            <summary><i class="bi bi-tag t3"></i>Nombre y descripción<?php if ($id && !empty($row['descripcion'])): ?><small><?= esc(mb_strimwidth($row['descripcion'], 0, 60, '…')) ?></small><?php endif; ?><i class="bi bi-chevron-right ch"></i></summary>
            <div class="cuerpo">
                <label class="rc-campo"><span>Nombre</span><input class="fv-input" name="nombre" required placeholder="Ej. Tortitas de avena" value="<?= esc($nombre) ?>"></label>
                <label class="rc-campo"><span>Descripción <small>(opcional)</small></span><textarea class="fv-input" name="descripcion" rows="3" placeholder="Notas, modo de preparación…"><?= esc(old('descripcion', $row['descripcion'] ?? '')) ?></textarea></label>
                <div class="rc-pie">
                    <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i><?= $id ? 'Guardar' : 'Crear receta' ?></button>
                    <a class="fv-btn" href="<?= site_url('comidas/recetas') ?>">Volver</a>
                </div>
            </div>
        </details>
    </form>

    <?php if ($id): ?>
        <section class="rc-cifras cm-cifras" id="cmTotales">
            <div class="rc-cifra"><span class="k">Peso</span><span class="v"><span data-t="gr">0</span> <small>g</small></span></div>
            <div class="rc-cifra"><span class="k">kcal</span><span class="v"><span data-t="kcal">0</span></span><span class="s"><span data-t="k100">0</span>/100 g</span></div>
            <div class="rc-cifra"><span class="k">Prot.</span><span class="v"><span data-t="p">0</span> <small>g</small></span></div>
            <div class="rc-cifra"><span class="k">Carb.</span><span class="v"><span data-t="c">0</span> <small>g</small></span></div>
            <div class="rc-cifra"><span class="k">Grasa</span><span class="v"><span data-t="g">0</span> <small>g</small></span></div>
        </section>

        <section class="rc-sec">
            <div class="fv-ph"><h3>Ingredientes</h3><span class="cuenta" id="cmCuentaIng"></span></div>
            <div class="cm-busca">
                <i class="bi bi-search"></i>
                <input type="search" id="cmBuscar" class="fv-input" placeholder="Buscar un alimento para añadir…" autocomplete="off">
            </div>
            <div class="cm-res" id="cmRes" hidden></div>
            <form class="cm-sel" id="cmSel" hidden>
                <div class="cm-sel-cab">
                    <div style="display:grid;gap:3px;min-width:0"><span class="n" id="cmSelNombre"></span><span class="s" id="cmSel100"></span></div>
                    <button type="button" class="fv-ibtn" id="cmSelQuitar" title="Quitar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="cm-nueva-prop" style="grid-template-columns:minmax(0,1fr) auto">
                    <input type="number" step="0.1" min="0" inputmode="decimal" class="fv-input rc-grande cm-num" id="cmGramos" placeholder="Gramos">
                    <button type="submit" class="fv-btn fv-btn-pri" style="height:42px"><i class="bi bi-plus-lg"></i>Añadir</button>
                </div>
                <div class="rc-ayuda" id="cmSelCalc"></div>
            </form>
            <div class="fv-panel" style="padding:0;gap:0;overflow:hidden" id="cmLista"><div class="rc-vacio">Cargando…</div></div>
            <p class="rc-ayuda">Toca un ingrediente para cambiar los gramos o quitarlo.</p>
        </section>

        <?php if (!empty($aliVirtId)): ?>
            <?= view('comidas/_proporciones_nueva', ['alimentoId' => $aliVirtId, 'ejemplo' => 'ración, taza…']) ?>
        <?php endif; ?>

        <form id="cmBorrar" action="<?= site_url('comidas/recetas/delete/' . $id) ?>" method="post" onsubmit="return confirm('¿Eliminar la receta «<?= esc($nombre, 'js') ?>»?');"><?= csrf_field() ?></form>
    <?php endif; ?>
</div>

<?php if ($id): ?>
<dialog class="rc-dialogo" id="cmIngDlg">
    <form method="dialog" class="fv">
        <h3 id="cmIngDlgNombre">Ingrediente</h3>
        <label class="rc-campo"><span>Gramos</span><input type="number" step="0.1" min="0" inputmode="decimal" class="fv-input rc-grande cm-num" id="cmIngDlgG"></label>
        <div class="rc-dialogo-pie">
            <button type="button" class="fv-btn fv-btn-peligro" id="cmIngDlgBorrar" style="margin-right:auto"><i class="bi bi-trash"></i>Quitar</button>
            <button type="button" class="fv-btn" id="cmIngDlgCancelar">Cancelar</button>
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
        </div>
    </form>
</dialog>
<script>
(() => {
    const RID = <?= $id ?>;
    const API = {
        buscar: '<?= site_url('api/alimentos') ?>',
        listar: '<?= site_url('comidas/recetas/ingredientes') ?>/' + RID,
        add: '<?= site_url('comidas/recetas/ingredientes') ?>/' + RID + '/add',
        ing: '<?= site_url('comidas/recetas/ingrediente') ?>',
    };
    const CSRF = [<?= json_encode(csrf_token()) ?>, <?= json_encode(csrf_hash()) ?>];
    const $ = id => document.getElementById(id);
    const buscar = $('cmBuscar'), res = $('cmRes'), sel = $('cmSel'), gramos = $('cmGramos'), lista = $('cmLista'), dlg = $('cmIngDlg');
    const f0 = n => String(Math.round(+n || 0));
    const f1 = n => { const s = (Math.round((+n || 0) * 10) / 10).toFixed(1); return s.endsWith('.0') ? s.slice(0, -2) : s; };
    const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const getJson = async u => (await fetch(u, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json();
    const post = async (url, data) => { data[CSRF[0]] = CSRF[1]; return (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams(data) })).json(); };
    let elegido = null, t = null, pet = 0, actual = null, activo = -1;

    buscar.addEventListener('input', () => {
        const q = buscar.value.trim();
        clearTimeout(t);
        if (!q) { res.hidden = true; return; }
        t = setTimeout(async () => {
            const yo = ++pet;
            let rows = [];
            try { rows = await getJson(API.buscar + '?' + new URLSearchParams({ q })); } catch (e) {}
            if (yo !== pet) return;
            activo = -1;
            res.innerHTML = rows.length ? rows.map(r => `<button type="button" data-id="${r.id}" data-nombre="${esc(r.nombre)}">${esc(r.nombre)}<i class="bi bi-plus-lg"></i></button>`).join('') : '<div class="vacio">Sin resultados</div>';
            res.hidden = false;
        }, 180);
    });
    buscar.addEventListener('keydown', ev => {
        const bs = [...res.querySelectorAll('button')];
        if ((ev.key === 'ArrowDown' || ev.key === 'ArrowUp') && bs.length) {
            ev.preventDefault();
            activo = (activo + (ev.key === 'ArrowDown' ? 1 : -1) + bs.length) % bs.length;
            bs.forEach((b, i) => b.classList.toggle('on', i === activo));
        } else if (ev.key === 'Enter') { ev.preventDefault(); (bs[activo] || bs[0])?.click(); }
    });

    const calc = () => {
        if (!elegido) return;
        const g = parseFloat(String(gramos.value).replace(',', '.')) || 0, x = g / 100, m = elegido.m;
        $('cmSelCalc').textContent = g > 0 ? `${f1(g)} g → ${f0(m.kcal * x)} kcal · P ${f1(m.p * x)} · C ${f1(m.c * x)} · G ${f1(m.g * x)}` : '';
    };
    gramos.addEventListener('input', calc);
    const quitar = () => { elegido = null; sel.hidden = true; gramos.value = ''; $('cmSelCalc').textContent = ''; };
    $('cmSelQuitar').addEventListener('click', quitar);

    res.addEventListener('click', async ev => {
        const b = ev.target.closest('button[data-id]');
        if (!b) return;
        res.hidden = true; buscar.value = '';
        let d = {};
        try { d = await getJson(API.buscar + '/' + b.dataset.id); } catch (e) {}
        elegido = { id: b.dataset.id, m: { kcal: +d.kcal || 0, p: +d.proteina_g || 0, c: +d.carbohidratos_g || 0, g: +d.grasas_g || 0 } };
        $('cmSelNombre').textContent = b.dataset.nombre;
        $('cmSel100').textContent = `100 g · ${f0(elegido.m.kcal)} kcal · P ${f1(elegido.m.p)} · C ${f1(elegido.m.c)} · G ${f1(elegido.m.g)}`;
        sel.hidden = false; gramos.value = ''; calc(); gramos.focus();
    });

    sel.addEventListener('submit', async ev => {
        ev.preventDefault();
        const g = parseFloat(String(gramos.value).replace(',', '.')) || 0;
        if (!elegido || g <= 0) return gramos.focus();
        try {
            const r = await post(API.add, { alimento_id: String(elegido.id), gramos: String(g) });
            if (!r.ok) return alert('No se pudo añadir: ' + (r.error || 'error desconocido'));
            quitar(); await cargar(); buscar.focus();
        } catch (e) { alert('Error de red al añadir.'); }
    });

    const pon = (k, v) => document.querySelector(`#cmTotales [data-t="${k}"]`).textContent = v;
    const cargar = async () => {
        let rows = [];
        try { rows = await getJson(API.listar); } catch (e) { lista.innerHTML = '<div class="rc-vacio">Error al cargar ingredientes.</div>'; return; }
        const tot = { gr: 0, kcal: 0, p: 0, c: 0, g: 0 };
        const filas = rows.map(r => {
            const g = +r.gramos || 0, x = g / 100;
            const v = { kcal: (+r.kcal || 0) * x, p: (+r.proteina_g || 0) * x, c: (+r.carbohidratos_g || 0) * x, g: (+r.grasas_g || 0) * x };
            tot.gr += g; tot.kcal += v.kcal; tot.p += v.p; tot.c += v.c; tot.g += v.g;
            return `<tr data-id="${r.id}" data-nombre="${esc(r.nombre || '—')}" data-gramos="${g}"><td>${esc(r.nombre || '—')}</td><td>${f1(g)}</td><td>${f0(v.kcal)}</td><td>${f1(v.p)}</td><td>${f1(v.c)}</td><td>${f1(v.g)}</td></tr>`;
        }).join('');
        $('cmCuentaIng').textContent = rows.length || '';
        lista.innerHTML = rows.length
            ? `<table class="cm-tabla"><thead><tr><th>Ingrediente</th><th>g</th><th>kcal</th><th>P</th><th>C</th><th>G</th></tr></thead><tbody>${filas}</tbody></table>`
            : '<div class="rc-vacio">Aún no hay ingredientes. Búscalos arriba.</div>';
        pon('gr', f0(tot.gr)); pon('kcal', f0(tot.kcal)); pon('p', f1(tot.p)); pon('c', f1(tot.c)); pon('g', f1(tot.g));
        pon('k100', tot.gr > 0 ? f0(tot.kcal / tot.gr * 100) : '0');
    };

    lista.addEventListener('click', ev => {
        const tr = ev.target.closest('tbody tr[data-id]');
        if (!tr) return;
        actual = tr.dataset.id;
        $('cmIngDlgNombre').textContent = tr.dataset.nombre;
        $('cmIngDlgG').value = tr.dataset.gramos;
        dlg.showModal(); $('cmIngDlgG').select();
    });
    $('cmIngDlgCancelar').addEventListener('click', () => dlg.close());
    dlg.querySelector('form').addEventListener('submit', async ev => {
        ev.preventDefault();
        const g = parseFloat(String($('cmIngDlgG').value).replace(',', '.')) || 0;
        if (g <= 0) return;
        try {
            const r = await post(API.ing + '/' + actual + '/edit', { gramos: String(g) });
            if (!r.ok) return alert('No se pudo actualizar: ' + (r.error || 'error desconocido'));
            dlg.close(); cargar();
        } catch (e) { alert('Error de red al actualizar.'); }
    });
    $('cmIngDlgBorrar').addEventListener('click', async () => {
        if (!confirm('¿Quitar este ingrediente de la receta?')) return;
        try {
            const r = await post(API.ing + '/' + actual + '/delete', {});
            if (!r.ok) return alert('No se pudo quitar.');
            dlg.close(); cargar();
        } catch (e) { alert('Error de red al quitar.'); }
    });
    cargar();
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
