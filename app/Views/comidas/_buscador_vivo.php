<script>
(() => {
    const form = document.getElementById('cmBuscador');
    const input = form.querySelector('input[name="q"]');
    const filas = document.getElementById('cmFilas');
    const cargando = document.getElementById('cmCargando');
    const cuenta = document.getElementById('cmCuenta');
    let t = null, aborter = null;
    const traer = async () => {
        aborter?.abort();
        aborter = new AbortController();
        const url = new URL(form.action, location.origin);
        if (input.value.trim()) url.searchParams.set('q', input.value.trim());
        history.replaceState(null, '', url.search ? url.pathname + url.search : url.pathname);
        url.searchParams.set('partial', '1');
        cargando.hidden = false;
        try {
            const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: aborter.signal });
            if (!r.ok) return;
            filas.innerHTML = await r.text();
            if (cuenta) { const n = filas.querySelectorAll('.rc-fila').length; cuenta.textContent = cuenta.textContent.replace(/^\d+/, n); }
        } catch (e) { if (e.name !== 'AbortError') console.error(e); }
        finally { cargando.hidden = true; }
    };
    form.addEventListener('submit', e => { e.preventDefault(); clearTimeout(t); traer(); });
    input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(traer, 250); });
    input.addEventListener('keydown', e => { if (e.key === 'Escape' && input.value) { input.value = ''; traer(); } });
})();
</script>
