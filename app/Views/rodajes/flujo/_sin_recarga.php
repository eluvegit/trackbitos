<?php
/**
 * Pasos del flujo sin recargar: cada formulario dentro de #$raiz se envía con
 * fetch, la respuesta (la propia página tras la redirección) sustituye el
 * contenido de #$raiz y la vista se queda quieta (el bloque donde actuaste no
 * se mueve en pantalla). Los selects con data-eid guardan con /asignar; con
 * data-recargar además refrescan (la toma cambia de grupo).
 *
 * Expone window.rfRefrescar(ancla, html, seguir), que usa _asignar.php.
 *
 * @var string $raiz id del contenedor (rfUb, rfMom…), con data-asignar = url de escenas
 */
?>
<script>
(() => {
    const ID = <?= json_encode($raiz) ?>;
    const raiz = () => document.getElementById(ID);
    const H = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' };
    // Bloque cuya posición en pantalla se conserva tras refrescar.
    const anclaDe = el => { const b = el.closest('.rf-zona, [data-ancla]'); return b && b.id ? '#' + b.id : null; };

    // Sustituye «viejo» por «nuevo» pero sin tocar el campo donde se escribe ni
    // sus contenedores: en móvil, quitarlo del DOM cierra y reabre el teclado
    // (salto). Se baja por la rama que contiene el campo y se cambia todo lo de
    // alrededor.
    const temporales = ['rf-enviando', 'rf-enviando-suave', 'rf-hecho', 'rf-ok'];
    const firma = el => [el.tagName, el.id, el.getAttribute('action') || '', el.getAttribute('name') || '',
        [...el.classList].filter(c => !temporales.includes(c)).join(' ')].join('|');
    const injertar = (viejo, nuevo, vivo) => {
        if (viejo === vivo) { vivo.value = nuevo.value || ''; return true; }
        const queda = [...viejo.children].find(c => c.contains(vivo));
        const par = queda && [...nuevo.children].find(c => firma(c) === firma(queda));
        if (!par) return false;
        const hijos = [...nuevo.childNodes], i = hijos.indexOf(par);
        [...viejo.childNodes].forEach(n => { if (n !== queda) n.remove(); });
        hijos.slice(0, i).forEach(n => viejo.insertBefore(n, queda));
        queda.after(...hijos.slice(i + 1));
        return injertar(queda, par, vivo);
    };

    /** ancla: selector cuya posición en pantalla se conserva; seguir: mantener el campo de texto activo (y el teclado) */
    window.rfRefrescar = async (ancla = null, html = null, seguir = false) => {
        const r0 = raiz();
        const act = document.activeElement;
        const vivo = seguir && act && r0.contains(act) && act.matches('input:not([type=hidden]):not([type=file]), textarea') ? act : null;
        const a0 = vivo || (ancla && r0.querySelector(ancla));
        const antes = a0 ? a0.getBoundingClientRect().top : null;
        if (html === null) html = await fetch(location.pathname + location.search).then(r => r.text());
        const nuevo = new DOMParser().parseFromString(html, 'text/html').getElementById(ID);
        if (!nuevo) { location.reload(); return; }
        if (!vivo || !injertar(r0, nuevo, vivo)) r0.innerHTML = nuevo.innerHTML;
        const a1 = vivo && r0.contains(vivo) ? vivo : (ancla && r0.querySelector(ancla));
        if (a1 && antes !== null) window.scrollBy(0, a1.getBoundingClientRect().top - antes);
        if (!vivo && a1 && a1.classList.contains('rf-zona')) a1.classList.add('rf-hecho'); // destello: «guardado»
        r0.querySelectorAll('.rf-enviando, .rf-enviando-suave').forEach(f => f.classList.remove('rf-enviando', 'rf-enviando-suave'));
        r0.querySelectorAll('.rf-asigna').forEach(s => window.rfAsigna?.contar(s));
    };

    document.addEventListener('submit', async ev => {
        const f = ev.target, r0 = raiz();
        if (ev.defaultPrevented || !r0 || !r0.contains(f) || !f.getAttribute('action')) return;
        ev.preventDefault();
        if (f.classList.contains('rf-enviando') || f.classList.contains('rf-enviando-suave')) return; // doble Enter
        // Campo de texto de una línea (p. ej. «Añadir punto»): vuelve a quedar listo para el siguiente.
        const act = document.activeElement;
        const foco = !!(act && f.contains(act) && act.tagName === 'INPUT' && !f.hasAttribute('data-cerrar'));
        // Atenuar sin pointer-events:none en el campo activo (algunos móviles lo desenfocan).
        f.classList.add(foco ? 'rf-enviando-suave' : 'rf-enviando');
        try {
            // El botón pulsado manda (formaction de «borrar» dentro del form de «guardar»).
            const b = ev.submitter && ev.submitter.hasAttribute('formaction') ? ev.submitter : null;
            const r = await fetch(b ? b.formAction : f.action, { method: 'POST', body: new FormData(f, ev.submitter || undefined) });
            if (!r.ok) throw 0;
            await window.rfRefrescar(anclaDe(f), await r.text(), foco);
        } catch (e) {
            f.classList.remove('rf-enviando', 'rf-enviando-suave');
            alert('No se ha podido guardar.');
        }
    });

    document.addEventListener('change', ev => {
        const r0 = raiz();
        const s = r0 && ev.target.closest('select[data-eid]');
        if (!s || !r0.contains(s)) return;
        s.classList.add('rf-enviando-suave');
        fetch(r0.dataset.asignar + '/' + s.dataset.eid + '/asignar', {
            method: 'POST',
            headers: { ...H, 'Content-Type': 'application/json' },
            body: JSON.stringify({ campo: s.dataset.campo, valor: s.value }),
        }).then(r => {
            if (!r.ok) throw 0;
            if (s.hasAttribute('data-recargar')) return window.rfRefrescar(anclaDe(s));
            s.classList.remove('rf-enviando-suave');
            s.classList.add('rf-ok');
        }).catch(() => { s.classList.remove('rf-enviando-suave'); alert('No se ha podido guardar.'); });
    });
})();
</script>
