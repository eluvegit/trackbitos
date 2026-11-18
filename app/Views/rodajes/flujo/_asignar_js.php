<?php
/**
 * JS de la bandeja de asignar (_asignar.php). Va aparte para poder cargarlo
 * aunque la bandeja no esté al abrir la página y aparezca después sin recargar
 * (p. ej. al dejar una toma «Sin momento»). Se protege contra doble carga.
 */
?>
<script>
// Delegado en document (una sola vez aunque haya varias bandejas) para que
// siga funcionando cuando la página cambia su contenido sin recargar
// (window.rfRefrescar en Ubicaciones). Sin rfRefrescar, recarga como siempre.
(() => {
    if (window.rfAsigna) return;
    const H = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' };
    const cajas = sec => [...sec.querySelectorAll('.rf-ideas input[type=checkbox]')];
    const marcadas = sec => cajas(sec).filter(c => c.checked).map(c => c.value);
    const contar = sec => {
        const k = marcadas(sec).length;
        const n = sec.querySelector('[data-n]');
        if (n) n.textContent = k;
        sec.querySelectorAll('button.rf-destino').forEach(b => b.disabled = !k);
        sec.querySelectorAll('[data-grupo-todas]').forEach(b => {
            const cs = [...b.closest('[data-grupo]').querySelectorAll('input[type=checkbox]')];
            b.textContent = cs.every(c => c.checked) ? 'Desmarcar' : 'Marcar las ' + cs.length;
        });
    };
    // Tras asignar: refresca sin recargar y dice a dónde han ido (con enlace).
    const terminar = async (sec, n, valor, nombre) => {
        if (!window.rfRefrescar) { location.reload(); return; }
        await window.rfRefrescar('.rf-asigna');
        const id = sec.dataset.prefijo ? sec.dataset.prefijo + valor : '';
        const dest = id && document.getElementById(id);
        if (dest) { dest.classList.remove('rf-hecho'); void dest.offsetWidth; dest.classList.add('rf-hecho'); }
        if (n) aviso((n === 1 ? '1 toma' : n + ' tomas') + ' → ' + nombre, dest ? '#' + id : null);
    };
    const aviso = (txt, ir) => {
        document.querySelector('.rf-aviso')?.remove();
        const a = document.createElement('div');
        a.className = 'rf-aviso';
        a.setAttribute('role', 'status');
        a.textContent = txt;
        if (ir) {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = 'Ver';
            b.addEventListener('click', () => { document.querySelector(ir)?.scrollIntoView({ behavior: 'smooth', block: 'start' }); a.remove(); });
            a.appendChild(b);
        }
        document.body.appendChild(a);
        setTimeout(() => a.remove(), 5000);
    };
    const asignar = (sec, ids, valor) => Promise.all(ids.map(id => fetch(sec.dataset.url + '/' + id + '/asignar', {
        method: 'POST',
        headers: { ...H, 'Content-Type': 'application/json' },
        body: JSON.stringify({ campo: sec.dataset.campo, valor }),
    }).then(r => { if (!r.ok) throw 0; })));
    const alternar = cs => { const todas = cs.every(c => c.checked); cs.forEach(c => c.checked = !todas); };

    document.addEventListener('change', ev => { const sec = ev.target.closest('.rf-asigna'); if (sec) contar(sec); });
    document.addEventListener('click', ev => {
        const sec = ev.target.closest('.rf-asigna');
        if (!sec) return;
        if (ev.target.closest('[data-todas]')) { alternar(cajas(sec)); contar(sec); return; }
        const g = ev.target.closest('[data-grupo-todas]');
        if (g) { alternar([...g.closest('[data-grupo]').querySelectorAll('input[type=checkbox]')]); contar(sec); return; }
        const b = ev.target.closest('button.rf-destino');
        if (!b || b.disabled) return;
        const ids = marcadas(sec);
        if (!ids.length) return;
        sec.querySelectorAll('button.rf-destino').forEach(x => x.disabled = true);
        b.classList.add('on');
        asignar(sec, ids, b.dataset.valor)
            .then(() => terminar(sec, ids.length, b.dataset.valor, b.querySelector('.t')?.textContent.trim() || ''))
            .catch(() => { alert('No se ha podido guardar alguna.'); location.reload(); });
    });
    // Nuevo destino: lo crea y, si hay tomas marcadas, se las asigna.
    document.addEventListener('submit', ev => {
        const nuevo = ev.target.closest('.rf-destino-nuevo');
        if (!nuevo) return;
        ev.preventDefault();
        const sec = nuevo.closest('.rf-asigna');
        const nombre = nuevo.nombre.value.trim();
        if (!nombre) { nuevo.nombre.focus(); return; }
        const ids = marcadas(sec);
        const fd = new FormData();
        fd.append('nombre', nombre);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        nuevo.classList.add('on');
        fetch(nuevo.dataset.crear, { method: 'POST', headers: H, body: fd })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => (ids.length ? asignar(sec, ids, j.id) : null).then(() => terminar(sec, ids.length, j.id, nombre)))
            .catch(() => { alert('No se ha podido crear.'); nuevo.classList.remove('on'); });
    });
    window.rfAsigna = { contar };
    document.querySelectorAll('.rf-asigna').forEach(contar);
})();
</script>
