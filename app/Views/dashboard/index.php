<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$hayAvisos = $mostrarAlerta || !empty($recordatoriosUrgentes);
$hayCaducado = !empty(array_filter($recordatoriosUrgentes, fn($r) => $r['nivel'] === 'caducado'));
?>

<div class="row g-4 mt-1">
    <aside class="col-12 col-lg-3" id="dashboard-sidebar">
        <div class="d-flex justify-content-end mb-1">
            <button type="button" class="btn-close" id="dashboard-sidebar-close" aria-label="Ocultar panel lateral"></button>
        </div>

        <?php if ($hayAvisos): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted small mb-2">Avisos</h6>

                <?php if ($mostrarAlerta): ?>
                    <div class="alert alert-warning py-2 px-3 small">
                        🔔 Han pasado <?= $dias ?> días desde la última sustitución de lentillas. ¡Es hora de cambiarlas!
                    </div>
                <?php endif; ?>

                <?php if (!empty($recordatoriosUrgentes)): ?>
                    <div class="alert <?= $hayCaducado ? 'alert-danger' : 'alert-warning' ?> py-2 px-3 small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-calendar-heart"></i>
                            <strong>Recordatorios próximos</strong>
                        </div>
                        <ul class="mb-2 ps-3">
                            <?php foreach ($recordatoriosUrgentes as $r): ?>
                                <li>
                                    <?= esc($r['titulo']) ?>
                                    — <span class="fw-semibold"><?= esc($r['texto']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= site_url('recordatorios') ?>" class="alert-link">Ver todos los recordatorios →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($enlacesRapidos)): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted small mb-2">Enlaces rápidos</h6>
                <div class="list-group list-group-flush">
                    <?php foreach ($enlacesRapidos as $enlace): ?>
                        <a href="<?= site_url($enlace['ruta']) ?>" class="list-group-item list-group-item-action bg-transparent d-flex align-items-center gap-2 px-0">
                            <i class="bi <?= esc($enlace['icono']) ?>"></i>
                            <?= esc($enlace['titulo']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php // Libros en curso: lista mínima (título · autor · %), enlace a su ficha. ?>
        <?php if (!empty($librosLeyendo)): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted small mb-2">Leyendo</h6>
                <div class="list-group list-group-flush">
                    <?php foreach ($librosLeyendo as $libro): ?>
                        <a href="<?= site_url('reading/libro/' . $libro['id']) ?>"
                            class="list-group-item list-group-item-action bg-transparent d-flex align-items-center gap-2 px-0">
                            <i class="bi bi-book text-muted"></i>
                            <span class="flex-grow-1 text-truncate">
                                <?= esc($libro['title']) ?>
                                <?php if ($libro['author'] !== ''): ?>
                                    <span class="text-muted small">· <?= esc($libro['author']) ?></span>
                                <?php endif; ?>
                            </span>
                            <?php if ($libro['progreso'] !== null): ?>
                                <span class="text-muted small"><?= (int) $libro['progreso'] ?>%</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        /**
         * Tareas de Journal fijadas a mano por el usuario — aparte de
         * "Enlaces rápidos" de arriba, que es una lista fija en código. Van
         * directas a las subtareas de la tarea (#subtaskList en
         * journal/edit.php), no a la cabecera: es lo que se viene a mirar
         * al fijar una tarea de referencia.
         */
        ?>
        <input type="hidden" id="dashboardCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
        <div>
            <h6 class="text-uppercase text-muted small mb-2">Tareas fijadas</h6>
            <div class="list-group list-group-flush mb-2" id="tareasFijadasList">
                <?php foreach ($tareasFijadas as $f): ?>
                    <div class="list-group-item bg-transparent d-flex align-items-center gap-2 px-0" data-task-id="<?= (int) $f['task_id'] ?>">
                        <a href="<?= site_url('journal/edit/' . (int) $f['task_id']) ?>#subtaskList"
                            class="flex-grow-1 text-decoration-none d-flex align-items-center gap-2">
                            <i class="bi bi-pin-angle"></i>
                            <span>
                                <?= esc($f['tarea']['title']) ?>
                                <?php if (!empty($f['tarea']['category'])): ?>
                                    <span class="text-muted small">· <?= esc($f['tarea']['category']) ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 border-0" data-desfijar title="Quitar de fijadas">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($tareasFijadas)): ?>
                    <p class="text-muted small mb-0" id="tareasFijadasVacio">Ninguna tarea fijada todavía.</p>
                <?php endif; ?>
            </div>

            <div class="position-relative">
                <input type="text" class="form-control form-control-sm" id="buscarTareaFijar" autocomplete="off"
                    placeholder="Fijar una tarea…">
                <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;" id="resultadosTareaFijar"></div>
            </div>
        </div>
    </aside>

    <div class="col-12 col-lg-9" id="dashboard-main">
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3 align-items-center gap-1" id="dashboard-sidebar-show" style="display: none;">
            <i class="bi bi-layout-sidebar-inset"></i> Mostrar panel
        </button>

        <div id="dashboard-grid" class="row row-cols-3 row-cols-sm-4 row-cols-md-4 row-cols-lg-5 g-3">
            <?php foreach ($secciones as $sec): ?>
                <div class="col d-flex position-relative" data-key="<?= esc($sec['ruta']) ?>">
                    <span class="db-drag-handle" title="Arrastrar para reordenar">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
                    <a href="<?= site_url($sec['ruta']) ?>" class="text-decoration-none w-100">
                        <div class="card shadow-sm w-100" style="aspect-ratio: 1 / 1;">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-2">
                                <div class="mb-2" style="font-size: 2rem; line-height: 1;">
                                    <?= $sec['icono'] ?>
                                </div>
                                <h6 class="card-title mb-1"><?= $sec['titulo'] ?></h6>
                                <p class="mb-0 small text-muted d-none d-md-block">
                                    <?= $sec['texto'] ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.db-drag-handle {
    position: absolute;
    top: 4px;
    right: 4px;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    color: rgba(0, 0, 0, .45);
    cursor: grab;
    touch-action: none;
}
#dashboard-grid.db-sorting .db-drag-handle { cursor: grabbing; }
.db-ghost { opacity: .4; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Ocultar/mostrar el panel lateral (avisos + enlaces rápidos)
    var sidebar = document.getElementById('dashboard-sidebar');
    var main = document.getElementById('dashboard-main');
    var sidebarCloseBtn = document.getElementById('dashboard-sidebar-close');
    var sidebarShowBtn = document.getElementById('dashboard-sidebar-show');
    var SIDEBAR_KEY = 'trackbitos_dashboard_sidebar_hidden';

    if (sidebar && main && sidebarCloseBtn && sidebarShowBtn) {
        var setSidebarHidden = function (hidden) {
            sidebar.classList.toggle('d-none', hidden);
            main.classList.toggle('col-lg-9', !hidden);
            main.classList.toggle('col-lg-12', hidden);
            sidebarShowBtn.style.display = hidden ? 'inline-flex' : 'none';
            try { localStorage.setItem(SIDEBAR_KEY, hidden ? '1' : '0'); } catch (e) {}
        };

        var hiddenStored = false;
        try { hiddenStored = localStorage.getItem(SIDEBAR_KEY) === '1'; } catch (e) {}
        setSidebarHidden(hiddenStored);

        sidebarCloseBtn.addEventListener('click', function () { setSidebarHidden(true); });
        sidebarShowBtn.addEventListener('click', function () { setSidebarHidden(false); });
    }

    var grid = document.getElementById('dashboard-grid');
    if (!grid) return;

    var STORAGE_KEY = 'trackbitos_dashboard_order';

    function getStoredOrder() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function applyStoredOrder() {
        var saved = getStoredOrder();
        if (!saved.length) return;

        var items = Array.from(grid.children);
        var byKey = {};
        items.forEach(function (el) { byKey[el.dataset.key] = el; });

        saved.forEach(function (key) {
            if (byKey[key]) {
                grid.appendChild(byKey[key]);
                delete byKey[key];
            }
        });

        // Secciones nuevas que no estaban guardadas: se añaden al final,
        // manteniendo el orden original entre ellas.
        items.forEach(function (el) {
            if (byKey[el.dataset.key]) {
                grid.appendChild(el);
            }
        });
    }

    function saveOrder() {
        var order = Array.from(grid.children).map(function (el) { return el.dataset.key; });
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(order));
        } catch (e) {}
    }

    applyStoredOrder();

    new Sortable(grid, {
        handle: '.db-drag-handle',
        animation: 150,
        ghostClass: 'db-ghost',
        onStart: function () { grid.classList.add('db-sorting'); },
        onEnd: function () {
            grid.classList.remove('db-sorting');
            saveOrder();
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tokenCampo = document.getElementById('dashboardCsrfToken');
    var lista = document.getElementById('tareasFijadasList');
    var caja = document.getElementById('buscarTareaFijar');
    var resultados = document.getElementById('resultadosTareaFijar');
    if (!lista || !caja || !resultados) return;

    function llamada(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenCampo ? tokenCampo.value : ''
            },
            body: formData || null
        }).then(function (r) { return r.json(); });
    }

    function quitarVacio() {
        var vacio = document.getElementById('tareasFijadasVacio');
        if (vacio) vacio.remove();
    }

    function mostrarVacioSiToca() {
        if (!lista.querySelector('[data-task-id]')) {
            var p = document.createElement('p');
            p.className = 'text-muted small mb-0';
            p.id = 'tareasFijadasVacio';
            p.textContent = 'Ninguna tarea fijada todavía.';
            lista.appendChild(p);
        }
    }

    function filaTarea(tarea) {
        var div = document.createElement('div');
        div.className = 'list-group-item bg-transparent d-flex align-items-center gap-2 px-0';
        div.dataset.taskId = tarea.id;

        var enlace = document.createElement('a');
        enlace.href = '<?= site_url('journal/edit/') ?>' + tarea.id + '#subtaskList';
        enlace.className = 'flex-grow-1 text-decoration-none d-flex align-items-center gap-2';
        enlace.innerHTML = '<i class="bi bi-pin-angle"></i><span></span>';

        var span = enlace.querySelector('span');
        span.appendChild(document.createTextNode(tarea.title));
        if (tarea.category) {
            var cat = document.createElement('span');
            cat.className = 'text-muted small';
            cat.textContent = ' · ' + tarea.category;
            span.appendChild(cat);
        }

        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'btn btn-sm btn-outline-secondary py-0 px-1 border-0';
        boton.setAttribute('data-desfijar', '');
        boton.title = 'Quitar de fijadas';
        boton.innerHTML = '<i class="bi bi-x"></i>';

        div.appendChild(enlace);
        div.appendChild(boton);

        return div;
    }

    // ---- Buscador para fijar una tarea nueva ----
    var espera = null;
    caja.addEventListener('input', function () {
        clearTimeout(espera);
        var q = caja.value.trim();
        if (q.length < 2) {
            resultados.style.display = 'none';
            resultados.innerHTML = '';
            return;
        }

        espera = setTimeout(function () {
            fetch('<?= site_url('dashboard/tarea-buscar') ?>?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var encontradas = d.resultados || [];
                    resultados.innerHTML = encontradas.length
                        ? encontradas.map(function (t) {
                            var textoHtml = t.texto.replace(/&/g, '&amp;').replace(/</g, '&lt;');
                            return '<button type="button" class="list-group-item list-group-item-action py-1 small" '
                                + 'data-elegir-tarea data-id="' + t.id + '"' + (t.fijada ? ' disabled' : '') + '>'
                                + textoHtml + (t.fijada ? ' <span class="text-muted">(ya fijada)</span>' : '')
                                + '</button>';
                        }).join('')
                        : '<div class="list-group-item py-1 small text-muted">Sin resultados</div>';
                    resultados.style.display = 'block';
                });
        }, 250);
    });

    resultados.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-elegir-tarea]');
        if (!boton || boton.disabled) return;

        var fd = new FormData();
        fd.append('task_id', boton.getAttribute('data-id'));
        llamada('<?= site_url('dashboard/tarea-fijada') ?>', fd).then(function (datos) {
            if (!datos.ok) { alert(datos.mensaje || 'No se pudo fijar.'); return; }
            quitarVacio();
            lista.appendChild(filaTarea(datos.tarea));
            caja.value = '';
            resultados.style.display = 'none';
            resultados.innerHTML = '';
        }).catch(function () {
            alert('No se pudo conectar con el servidor.');
        });
    });

    caja.addEventListener('focusout', function () {
        setTimeout(function () { resultados.style.display = 'none'; }, 200);
    });

    // ---- Quitar una tarea fijada ----
    lista.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-desfijar]');
        if (!boton) return;
        var fila = boton.closest('[data-task-id]');
        if (!fila) return;

        llamada('<?= site_url('dashboard/tarea-fijada') ?>/' + fila.dataset.taskId + '/quitar').then(function (datos) {
            if (!datos.ok) { alert('No se pudo quitar.'); return; }
            fila.remove();
            mostrarVacioSiToca();
        }).catch(function () {
            alert('No se pudo conectar con el servidor.');
        });
    });
});
</script>

<?= $this->endSection() ?>
