<?php
$ejemplo ??= 'loncha, taza…';
?>
<section class="fv-panel cm-props" id="cmProps">
    <div class="fv-ph"><h3>Porciones</h3><span class="cuenta" id="cmPropsCuenta"></span><span class="der t3" style="font-size:12px">para apuntarlo en el diario sin pesar</span></div>
    <div class="cm-props-lista" id="cmPropsLista"><span class="fv-vacio">Cargando…</span></div>
    <div class="cm-nueva-prop">
        <input type="text" class="fv-input" id="cmPropDesc" placeholder="Nueva: <?= esc($ejemplo) ?>" maxlength="120">
        <input type="number" step="1" min="1" inputmode="numeric" class="fv-input cm-num" id="cmPropG" placeholder="gramos">
        <label class="cm-check"><input type="checkbox" id="cmPropPredet">Predeterminada</label>
        <button type="button" class="fv-btn" id="cmPropAdd"><i class="bi bi-plus-lg"></i>Añadir</button>
    </div>
</section>

<dialog class="rc-dialogo" id="cmPropDlg">
    <form method="dialog" class="fv">
        <h3>Editar porción</h3>
        <label class="rc-campo"><span>Nombre</span><input type="text" class="fv-input" id="cmPropDlgDesc" maxlength="120"></label>
        <label class="rc-campo"><span>Gramos</span><input type="number" step="1" min="1" inputmode="numeric" class="fv-input cm-num" id="cmPropDlgG"></label>
        <label class="cm-check"><input type="checkbox" id="cmPropDlgPredet">Porción predeterminada</label>
        <div class="rc-dialogo-pie">
            <button type="button" class="fv-btn fv-btn-peligro" id="cmPropDlgBorrar" style="margin-right:auto"><i class="bi bi-trash"></i>Eliminar</button>
            <button type="button" class="fv-btn" id="cmPropDlgCancelar">Cancelar</button>
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const ID = <?= (int) $alimentoId ?>;
    const BASE = '<?= site_url('comidas/porciones/ajax') ?>';
    const CSRF = [<?= json_encode(csrf_token()) ?>, <?= json_encode(csrf_hash()) ?>];
    const $ = id => document.getElementById(id);
    const lista = $('cmPropsLista'), dlg = $('cmPropDlg');
    const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const num = v => parseFloat(String(v || '').replace(',', '.')) || 0;
    const post = async (url, data) => {
        data[CSRF[0]] = CSRF[1];
        return (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams(data) })).json();
    };
    let actual = null;

    const cargar = async () => {
        let rows = [];
        try { rows = await (await fetch(BASE + '/' + ID, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json(); }
        catch (e) { lista.innerHTML = '<span class="fv-vacio">Error al cargar las porciones.</span>'; return; }
        $('cmPropsCuenta').textContent = rows.length || '';
        lista.innerHTML = rows.length
            ? rows.map(r => `<button type="button" class="cm-prop${+r.es_predeterminada ? ' predet' : ''}" data-id="${r.id}" data-desc="${esc(r.descripcion)}" data-g="${r.gramos_equivalentes}" data-predet="${+r.es_predeterminada ? 1 : 0}">${+r.es_predeterminada ? '<i class="bi bi-star-fill"></i>' : ''}${esc(r.descripcion || '—')}<span class="g">${Math.round(+r.gramos_equivalentes || 0)} g</span></button>`).join('')
            : '<span class="fv-vacio">Todavía no hay porciones.</span>';
    };

    $('cmPropAdd').addEventListener('click', async () => {
        const descripcion = $('cmPropDesc').value.trim(), g = num($('cmPropG').value);
        if (!descripcion) return $('cmPropDesc').focus();
        if (g <= 0) return $('cmPropG').focus();
        try {
            const r = await post(BASE + '/store', { alimento_id: String(ID), descripcion, gramos_equivalentes: String(g), es_predeterminada: $('cmPropPredet').checked ? '1' : '' });
            if (!r.ok) return alert('No se pudo añadir: ' + (r.error || 'error desconocido'));
            $('cmPropDesc').value = ''; $('cmPropG').value = ''; $('cmPropPredet').checked = false;
            cargar();
        } catch (e) { alert('Error de red al añadir.'); }
    });
    [$('cmPropDesc'), $('cmPropG')].forEach(i => i.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); $('cmPropAdd').click(); } }));

    lista.addEventListener('click', e => {
        const b = e.target.closest('.cm-prop');
        if (!b) return;
        actual = b.dataset.id;
        $('cmPropDlgDesc').value = b.dataset.desc;
        $('cmPropDlgG').value = Math.round(+b.dataset.g || 0);
        $('cmPropDlgPredet').checked = b.dataset.predet === '1';
        dlg.showModal();
    });
    $('cmPropDlgCancelar').addEventListener('click', () => dlg.close());
    dlg.querySelector('form').addEventListener('submit', async e => {
        e.preventDefault();
        const descripcion = $('cmPropDlgDesc').value.trim(), g = num($('cmPropDlgG').value);
        if (!descripcion || g <= 0) return;
        try {
            const r = await post(BASE + '/' + actual + '/update', { descripcion, gramos_equivalentes: String(g), es_predeterminada: $('cmPropDlgPredet').checked ? '1' : '' });
            if (!r.ok) return alert('No se pudo actualizar: ' + (r.error || 'error desconocido'));
            dlg.close(); cargar();
        } catch (e) { alert('Error de red al actualizar.'); }
    });
    $('cmPropDlgBorrar').addEventListener('click', async () => {
        if (!confirm('¿Eliminar esta porción?')) return;
        try {
            const r = await post(BASE + '/' + actual + '/delete', {});
            if (!r.ok) return alert('No se pudo eliminar.');
            dlg.close(); cargar();
        } catch (e) { alert('Error de red al eliminar.'); }
    });
    cargar();
})();
</script>
