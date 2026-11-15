<dialog class="rc-dialogo" id="cmEditIng">
    <form method="dialog" class="fv">
        <h3 id="cmEditIngNombre">Editar registro</h3>
        <label class="rc-campo"><span>Cantidad (g)</span><input type="number" step="0.1" min="0" inputmode="decimal" class="fv-input rc-grande cm-num" id="cmEditIngCant"></label>
        <p class="rc-ayuda">Al cambiar los gramos se olvida la porción con la que se apuntó.</p>
        <div class="rc-dialogo-pie">
            <button type="button" class="fv-btn fv-btn-peligro" id="cmEditIngBorrar" style="margin-right:auto"><i class="bi bi-trash"></i>Eliminar</button>
            <button type="button" class="fv-btn" id="cmEditIngCancelar">Cancelar</button>
            <button type="submit" class="fv-btn fv-btn-pri" id="cmEditIngOk"><i class="bi bi-check-lg"></i>Guardar</button>
        </div>
    </form>
</dialog>
<script>
(() => {
    const dlg = document.getElementById('cmEditIng');
    const cant = document.getElementById('cmEditIngCant');
    const CSRF = [<?= json_encode(csrf_token()) ?>, <?= json_encode(csrf_hash()) ?>];
    let id = null, alTerminar = null;
    const post = async (url, data = {}) => {
        data[CSRF[0]] = CSRF[1];
        const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams(data) });
        return r.json();
    };
    window.cmEditarIngesta = (d, fin) => {
        id = d.id; alTerminar = fin;
        document.getElementById('cmEditIngNombre').textContent = d.nombre || 'Editar registro';
        cant.value = d.cantidad || '';
        dlg.showModal();
        cant.select();
    };
    document.getElementById('cmEditIngCancelar').addEventListener('click', () => dlg.close());
    dlg.querySelector('form').addEventListener('submit', async ev => {
        ev.preventDefault();
        const g = parseFloat(String(cant.value).replace(',', '.'));
        if (!g || g <= 0) { cant.focus(); return; }
        try {
            const r = await post('<?= site_url('api/edit') ?>/' + id, { cantidad_gramos: String(g) });
            if (!r.ok) return alert('No se pudo actualizar: ' + (r.error || 'error desconocido'));
            dlg.close(); alTerminar && alTerminar();
        } catch (e) { alert('Error de red al actualizar.'); }
    });
    document.getElementById('cmEditIngBorrar').addEventListener('click', async () => {
        if (!confirm('¿Eliminar este registro?')) return;
        try {
            const r = await post('<?= site_url('api/delete') ?>/' + id);
            if (!r.ok) return alert('No se pudo eliminar.');
            dlg.close(); alTerminar && alTerminar();
        } catch (e) { alert('Error de red al eliminar.'); }
    });
})();
</script>
