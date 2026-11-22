/*
 * Ordenar las categorías de Piezas arrastrándolas en el modal «Categorías».
 *
 * El modal vive en tres sitios (índice clásico, índice nuevo y el que el
 * resto de pantallas trae por fetch desde piezas/modales-barra), y en este
 * último un <script> dentro del HTML insertado no llegaría a ejecutarse: por
 * eso va aparte y se engancha al abrir el modal, no al cargar la página.
 * SortableJS se pide solo la primera vez que hace falta.
 */
(function () {
    var SORTABLE_URL = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
    var cargandoSortable = null;
    var cambiado = false;

    function conSortable() {
        if (window.Sortable) return Promise.resolve(window.Sortable);
        if (!cargandoSortable) {
            cargandoSortable = new Promise(function (ok, mal) {
                var s = document.createElement('script');
                s.src = SORTABLE_URL;
                s.onload = function () { ok(window.Sortable); };
                s.onerror = function () { cargandoSortable = null; mal(); };
                document.head.appendChild(s);
            });
        }
        return cargandoSortable;
    }

    function guardar(lista) {
        var datos = new FormData();
        lista.querySelectorAll(':scope > [data-cat-id]').forEach(function (fila) {
            datos.append('orden[]', fila.getAttribute('data-cat-id'));
        });
        datos.append(lista.dataset.csrfNombre, lista.dataset.csrfValor);

        return fetch(lista.dataset.url, {
            method: 'POST', body: datos, credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) throw d;
            cambiado = true;
        }).catch(function () {
            alert('No se pudo guardar el orden de las categorías.');
        });
    }

    document.addEventListener('shown.bs.modal', function (e) {
        if (e.target.id !== 'modalCategorias') return;
        var lista = e.target.querySelector('[data-cat-lista]');
        if (!lista || lista.dataset.sortableListo) return;
        lista.dataset.sortableListo = '1';

        conSortable().then(function (Sortable) {
            Sortable.create(lista, {
                draggable: '[data-cat-id]',
                handle: '.cat-asa',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function (evt) {
                    if (evt.oldIndex !== evt.newIndex) guardar(lista);
                }
            });
        }).catch(function () {
            lista.dataset.sortableListo = '';
        });
    });

    // Al cerrar, si se movió algo, se recarga para que el listado (que
    // agrupa por categoría) salga ya en el orden nuevo.
    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target.id === 'modalCategorias' && cambiado) location.reload();
    });
})();
