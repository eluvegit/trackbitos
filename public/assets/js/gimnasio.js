/* Gimnasio — comportamiento común de las vistas nuevas */
(function () {
    'use strict';

    const ajax = { 'X-Requested-With': 'XMLHttpRequest' };
    window.gmNormaliza = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    document.addEventListener('DOMContentLoaded', function () {
        // Mapa de calor: arranca mostrando lo más reciente (a la derecha)
        document.querySelectorAll('.gm-hm-scroll').forEach(el => { el.scrollLeft = el.scrollWidth; });

        // Día vacío del mapa → fecha para "nuevo entrenamiento" (si hay formulario en la página)
        const form = document.querySelector('[data-gm-nuevo]');
        document.addEventListener('click', e => {
            const dia = e.target.closest('.gm-hm-col > button[data-fecha]');
            if (!dia || !form) return;
            form.querySelector('input[type=date]').value = dia.dataset.fecha;
            document.querySelectorAll('.gm-hm-col > .elegido').forEach(b => b.classList.remove('elegido'));
            dia.classList.add('elegido');
            const etq = form.querySelector('[data-gm-nuevo-txt]');
            if (etq) etq.textContent = 'Crear el ' + dia.title;
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Estrella de ejercicio principal
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-gm-star]');
        if (!b) return;
        e.preventDefault();
        e.stopPropagation();
        fetch(b.dataset.gmStar, { method: 'POST', headers: ajax })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) return;
                b.classList.toggle('on', !!d.principal);
                b.querySelector('.bi').className = 'bi ' + (d.principal ? 'bi-star-fill' : 'bi-star');
                b.title = d.principal ? 'Quitar de principales' : 'Marcar como principal (sale en Estadísticas)';
            });
    });

    // Formularios con confirmación
    document.addEventListener('submit', e => {
        const f = e.target.closest('form[data-confirm]');
        if (f && !confirm(f.dataset.confirm)) e.preventDefault();
    });

    // Vista previa de una sesión en modal (lista de entrenos)
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-gm-resumen]');
        if (!b) return;
        e.preventDefault();
        const modalEl = document.getElementById('gmResumen');
        if (!modalEl || !window.bootstrap) return;
        const cuerpo = modalEl.querySelector('.modal-body');
        modalEl.querySelector('[data-gm-abrir]').href = b.dataset.gmAbrir;
        modalEl.querySelector('.modal-title').textContent = b.dataset.gmTitulo || 'Sesión';
        cuerpo.innerHTML = '<div class="text-center py-4 text-muted small">Cargando…</div>';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        fetch(b.dataset.gmResumen, { headers: ajax })
            .then(r => r.text())
            .then(html => { cuerpo.innerHTML = html; })
            .catch(() => { cuerpo.innerHTML = '<div class="text-center py-4 text-muted small">No se pudo cargar.</div>'; });
    });
})();
