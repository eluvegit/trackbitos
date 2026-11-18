<?php
/**
 * Botones de opción por toma (.rf-seg[data-eid][data-campo]) en las listas de
 * Foto o vídeo y Momento: guarda al tocar y refresca sin recargar. El botón
 * activo, tocado otra vez, deja la toma sin valor. Delegado en document y
 * protegido contra doble carga (el contenido se sustituye con rfRefrescar).
 */
?>
<script>
(() => {
    if (window.rfSeg) return;
    window.rfSeg = true;
    document.addEventListener('click', ev => {
        const b = ev.target.closest('.rf-seg button');
        const r0 = b && b.closest('[data-asignar]');
        if (!b || !r0 || b.disabled) return;
        const seg = b.closest('.rf-seg');
        seg.querySelectorAll('button').forEach(x => x.disabled = true);
        fetch(r0.dataset.asignar + '/' + seg.dataset.eid + '/asignar', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'Content-Type': 'application/json' },
            body: JSON.stringify({ campo: seg.dataset.campo, valor: b.classList.contains('on') ? '' : b.dataset.valor }),
        }).then(r => {
            if (!r.ok) throw 0;
            const g = b.closest('[data-ancla]');
            return window.rfRefrescar ? window.rfRefrescar(g && g.id ? '#' + g.id : null) : location.reload();
        }).catch(() => { alert('No se ha podido guardar.'); location.reload(); });
    });
})();
</script>
