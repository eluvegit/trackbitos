<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .task-progress {
        display: flex;
        gap: 1px;
        margin-top: 4px;
        height: 2px;
    }

    .task-progress-segment {
        flex: 1;
        height: 2px;
        background-color: #e9ecef;
        border-radius: 2px;
    }

    .task-progress-segment.filled {
        background-color: green;
    }

    .task-title-link:hover {
        text-decoration: underline;
    }

    .task-time-trigger {
        cursor: pointer;
    }

    body {
        font-size: 0.75rem;
    }

    .card-body {
        margin: 0 !important;
        padding: 4px !important;
    }

    .card-header[data-bs-toggle="collapse"] {
        cursor: pointer;
    }

    .journal-toolbar .btn {
        padding: .2rem .45rem;
    }
    .journal-toolbar .btn svg,
    .journal-toolbar .btn i {
        font-size: .8rem;
    }

    .task-title-link {
        color: beige;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
    <h3 class="mb-0" style="line-height: 1;">Journal</h3>
    <div class="btn-group btn-group-sm journal-toolbar">
        <a href="<?= site_url('journal/que-hacer') ?>" class="btn btn-outline-primary" title="¿Qué hago ahora?">
            <i class="bi bi-shuffle"></i>
        </a>
        <a href="<?= site_url('journal/focalizar') ?>" class="btn btn-outline-primary" title="Focalizar">
            <i class="bi bi-bullseye"></i>
        </a>
        <button id="toggleAllBtn" class="btn btn-outline-secondary" type="button" title="Mostrar todo">
            <i class="bi bi-arrows-expand"></i>
        </button>
        <span id="journalToolbarFilters" style="display:contents">
            <?= view('journal/_toolbar_filters', [
                'filterPriority' => $filterPriority,
                'filterFocus'    => $filterFocus,
                'filterHechos'   => $filterHechos,
                'view_mode'      => $view_mode,
            ]) ?>
        </span>
    </div>
</div>


<style>
    .journal-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: .2rem;
    }

    /* Subtareas inline en el listado */
    .jt-subtask-toggle {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: 3px;
        border: none;
        background: transparent;
        color: #adb5bd;
        padding: 2px 4px;
        border-radius: 8px;
        font-size: .7rem;
        line-height: 1;
    }
    .jt-subtask-toggle:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }
    .jt-subtask-toggle.has-subtasks { color: var(--bs-emphasis-color); }
    .jt-subtask-toggle[aria-expanded="true"] { color: #0d6efd; }
    .jt-subtask-count { font-weight: 600; }

    .jt-task-complete-btn {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border: none;
        background: transparent;
        color: #adb5bd;
        border-radius: 8px;
        font-size: .95rem;
    }
    .jt-task-complete-btn:hover { background: var(--bs-tertiary-bg); color: #198754; }
    .jt-task-complete-btn.is-done { color: #198754; }

    .jt-inline-subtasks { margin-top: 6px; padding-top: 6px; border-top: 1px dashed var(--bs-border-color); }

    .jt-subtask-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 6px; }

    /* Filtros de subtareas hechas (solo visual, no altera el orden real) */
    .jt-subtask-filters { display: flex; gap: 4px; margin-bottom: 4px; }
    .jt-subtask-filter-btn {
        background: none;
        border: none;
        color: var(--bs-secondary-color);
        opacity: .55;
        font-size: .75rem;
        line-height: 1;
        padding: 2px 5px;
        border-radius: 6px;
    }
    .jt-subtask-filter-btn:hover { opacity: 1; background: var(--bs-tertiary-bg); }
    .jt-subtask-filter-btn.active { opacity: 1; color: #0d6efd; background: rgba(13,110,253,.12); }
    .jt-subtask-list.hide-done .jt-subtask-item.is-done { display: none; }
    .jt-subtask-list.push-done .jt-subtask-item.is-done { order: 1; }

    .jt-subtask-item {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 3px 6px;
        border-radius: 8px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        transition: opacity .15s ease, background-color .15s ease;
    }
    .jt-subtask-item.sortable-ghost { opacity: .3; }
    .jt-subtask-item.sortable-chosen { background: var(--bs-tertiary-bg); }
    .jt-subtask-item.is-done { opacity: .6; }

    .jt-subtask-handle {
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        width: 18px;
        height: 24px;
        color: var(--bs-secondary-color);
        cursor: grab;
        touch-action: none;
    }
    .jt-subtask-handle:active { cursor: grabbing; }

    .jt-subtask-check {
        flex: 0 0 auto;
        width: 24px;
        height: 24px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--bs-secondary-color);
        font-size: .9rem;
        cursor: pointer;
    }
    .jt-subtask-item.is-done .jt-subtask-check { color: #10b981; }

    .jt-subtask-title {
        flex: 1 1 auto;
        min-width: 0;
        font-size: .78rem;
        color: var(--bs-emphasis-color);
        word-break: break-word;
    }
    .jt-subtask-item.is-done .jt-subtask-title {
        text-decoration: line-through;
        color: var(--bs-secondary-color);
    }

    .jt-subtask-time {
        flex: 0 0 auto;
        font-size: .7rem;
        color: var(--bs-secondary-color);
        cursor: pointer;
        white-space: nowrap;
    }
    .jt-subtask-time:hover { color: var(--bs-emphasis-color); text-decoration: underline; }

    .jt-subtask-edit,
    .jt-subtask-delete,
    .jt-subtask-move {
        flex: 0 0 auto;
        width: 22px;
        height: 22px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--bs-secondary-color);
        cursor: pointer;
    }
    .jt-subtask-edit:hover { background: rgba(13,110,253,.12); color: #0d6efd; }
    .jt-subtask-delete:hover { background: rgba(220,53,69,.12); color: #dc3545; }
    .jt-subtask-move:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }

    .jt-subtask-add { display: flex; gap: 6px; }
    .jt-subtask-add .form-control { flex: 1 1 auto; }

    /* Botonera secundaria de la subtarea (mover/renombrar/borrar).
       En escritorio: grupo al final de la misma fila. En móvil: baja a su
       propia línea (ver media query). */
    .jt-subtask-actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 0 0 auto;
    }

    /* Móvil: la subtarea pasa a dos líneas. Línea 1: asa + check + texto.
       Línea 2: horas + resto de botones. El flex-basis:0 del título evita que
       el navegador lo empuje solo a su propia línea al hacer wrap. */
    @media (max-width: 575.98px) {
        .jt-subtask-item { flex-wrap: wrap; align-items: flex-start; }
        .jt-subtask-title { flex: 1 1 0; }
        .jt-subtask-actions {
            flex: 1 1 100%;
            gap: 2px;
            padding-left: 22px;
        }
        /* Ergonomía: borrar a la izquierda, lejos del borde derecho (zona del
           pulgar). El tiempo pasa al extremo derecho, donde antes caía borrar. */
        .jt-subtask-delete { order: -1; }
        .jt-subtask-time { order: 5; margin-left: auto; }
    }
</style>

<div class="journal-grid" id="journalGrid">
<?= view('journal/_grid', [
    'categories'           => $categories,
    'tasksByCategory'      => $tasksByCategory,
    'totalTimeByCategory'  => $totalTimeByCategory,
    'lastCategoryActivity' => $lastCategoryActivity,
    'lastTaskActivity'     => $lastTaskActivity,
    'progressByCategory'   => $progressByCategory,
    'subtasksByTask'       => $subtasksByTask,
    'view_mode'            => $view_mode,
]) ?>
</div>

<!-- MODAL COMPLETAR TAREA -->
<div class="modal fade" id="taskCompleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tcTitle">Completar tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="tcBadge" class="alert alert-success py-1 px-2 small d-none">
                    <i class="bi bi-check-circle-fill"></i> Esta tarea ya está marcada como terminada.
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" for="tcStart">Inicio</label>
                        <input type="date" id="tcStart" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" for="tcEnd">Fin</label>
                        <input type="date" id="tcEnd" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1" for="tcTime">Tiempo invertido (minutos)</label>
                    <input type="number" id="tcTime" class="form-control form-control-sm" min="0">
                    <div class="small text-muted mt-1" id="tcTimeHint">= 0.00 h</div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1" for="tcNote">Nota</label>
                    <textarea id="tcNote" class="form-control form-control-sm" rows="3"></textarea>
                </div>
                <input type="hidden" id="tcTaskId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-sm me-auto d-none" id="tcReopenBtn">Reabrir tarea</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="tcSaveBtn">Marcar como terminada</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TIEMPO Y FECHA -->
<div class="modal fade" id="timeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeModalTitle">Añadir tiempo / Fecha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="number" id="timeMinutes" class="form-control mb-2" placeholder="Minutos" min="1">
                <input type="date" id="taskDate" class="form-control mb-2" value="<?= date('Y-m-d') ?>">
                <input type="hidden" id="timeTaskId">
                <input type="hidden" id="timeMode" value="task">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="saveTimeBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SUGERIR SUBTAREAS -->
<div class="modal fade" id="subtaskSuggestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-stars"></i> Subtareas sugeridas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="subtaskSuggestContexto" class="form-label small mb-1">Contexto extra (opcional)</label>
                    <textarea id="subtaskSuggestContexto" class="form-control form-control-sm" rows="2" placeholder="Algo que ayude a generar mejores subtareas..."></textarea>
                </div>
                <div id="subtaskSuggestLoading" class="text-muted small d-none">Pensando...</div>
                <div id="subtaskSuggestError" class="text-danger small d-none"></div>
                <div id="subtaskSuggestList" class="d-flex flex-column gap-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-outline-primary btn-sm" id="subtaskSuggestGenerateBtn"><i class="bi bi-stars"></i> Generar</button>
                <button class="btn btn-primary btn-sm" id="subtaskSuggestAddBtn" disabled>Añadir seleccionadas</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RENOMBRAR SUBTAREA -->
<div class="modal fade" id="subtaskEditModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Renombrar subtarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="subtaskEditInput" class="form-control" maxlength="255">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="subtaskEditSaveBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ---------------------------------------------------------------
        // Filtros / vista: refrescan solo #journalGrid (+ la barra de
        // filtros) por AJAX, sin recargar la página. journalAllExpanded se
        // declara aquí (no dentro de un solo bloque) porque tanto el botón
        // "mostrar todo" como el refresco de filtros necesitan leerla y
        // tocarla desde closures distintas de este mismo <script>.
        // ---------------------------------------------------------------
        const journalGrid = document.getElementById('journalGrid');
        const journalToolbarFilters = document.getElementById('journalToolbarFilters');
        window.journalAllExpanded = false;

        function refreshJournalGrid(params) {
            // El HTML nuevo siempre nace con las categorías (y cualquier
            // panel de subtareas) cerradas, así que hay que anotar qué
            // estaba abierto antes de sustituir el HTML y reabrirlo después,
            // o el usuario pierde el sitio donde estaba en cada filtro.
            const expandedIds = [...journalGrid.querySelectorAll('.collapse.show')]
                .map(el => el.id)
                .filter(Boolean);

            const url = '<?= site_url('journal/grid') ?>?' + new URLSearchParams(params).toString();
            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    journalGrid.innerHTML = data.html;
                    journalToolbarFilters.innerHTML = data.toolbar;

                    let anyExpanded = false;
                    expandedIds.forEach(id => {
                        const el = document.getElementById(id);
                        // Puede no existir ya (p. ej. la tarea se filtró al
                        // ocultar hechos): se ignora sin más.
                        if (!el) return;
                        el.classList.add('show');
                        if (id.startsWith('cat-')) anyExpanded = true;
                    });

                    // El botón "mostrar todo" refleja si queda alguna
                    // categoría abierta tras restaurar el estado anterior.
                    window.journalAllExpanded = anyExpanded;
                    const toggleAllBtn = document.getElementById('toggleAllBtn');
                    toggleAllBtn.querySelector('i').className = window.journalAllExpanded ? 'bi bi-arrows-collapse' : 'bi bi-arrows-expand';
                    toggleAllBtn.title = window.journalAllExpanded ? 'Cerrar todo' : 'Mostrar todo';

                    window.initJournalNewTaskInputs();
                    window.initJournalSubtaskPanels();
                    window.applyJournalSubtaskFilters();
                })
                .catch(err => console.error(err));
        }

        document.querySelector('.journal-toolbar').addEventListener('click', function(e) {
            const link = e.target.closest('.js-journal-filter');
            if (!link) return;
            e.preventDefault();

            const url = new URL(link.href, window.location.origin);
            refreshJournalGrid(Object.fromEntries(url.searchParams.entries()));
        });

        // Repinta la cabecera de una categoría (horas totales, contador
        // completadas/total y la barra actuales/completadas/resto) con el
        // "category_summary" que devuelven las acciones que pueden cambiar
        // esos números (crear tarea, sumar tiempo, completar, marcar
        // estrella...), sin recargar ni pedir la rejilla entera.
        function applyCategorySummary(summary) {
            if (!summary) return;
            const header = document.querySelector(`.card-header[data-cat-id="${summary.cat_id}"]`);
            if (!header) return;

            const hours = header.querySelector('.cat-total-hours');
            if (hours) hours.textContent = summary.totalHours + ' h';

            const counts = header.querySelector('.cat-progress-counts');
            if (counts) counts.textContent = `${summary.completed}/${Math.max(1, summary.total)}`;

            const bar = header.querySelector('.cat-progress-bar');
            if (bar) {
                bar.title = `Actuales: ${summary.current}, Completadas: ${summary.completed}, Total: ${summary.total}`;
            }

            const current = header.querySelector('.cat-progress-current');
            if (current) current.style.width = summary.currentPerc + '%';

            const completed = header.querySelector('.cat-progress-completed');
            if (completed) completed.style.width = summary.completedPerc + '%';

            const remaining = header.querySelector('.cat-progress-remaining');
            if (remaining) remaining.style.width = summary.remainingPerc + '%';
        }

        // Crear tarea: el HTML de la fila lo genera el servidor (mismo
        // partial que usa el listado), así que sale con estrella, botón de
        // completar, subtareas y barra de progreso funcionales desde el
        // primer momento (antes se montaba un <li> a mano aquí y se
        // quedaba corto). Se puede llamar varias veces: cada input se marca
        // con data-jt-init para no duplicar el listener tras un refresco de
        // filtros (todos los inputs son nuevos) o una creación (el resto de
        // inputs ya estaban cableados).
        window.initJournalNewTaskInputs = function() {
            document.querySelectorAll('.new-task-input:not([data-jt-init])').forEach(input => {
                input.dataset.jtInit = '1';

                input.addEventListener('keypress', function(e) {
                    if (e.key !== 'Enter') return;
                    const title = this.value.trim();
                    const categoryId = this.dataset.categoryId;
                    if (!title || !categoryId) return;

                    fetch('<?= site_url('journal/create') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ title, category_id: categoryId })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                alert('No se pudo crear la tarea');
                                return;
                            }

                            const list = document.getElementById('task-list-' + categoryId);

                            // Eliminar mensaje "No hay tareas"
                            const empty = list.querySelector('.text-muted');
                            if (empty) empty.remove();

                            list.insertAdjacentHTML('beforeend', data.html);
                            window.initJournalSubtaskPanels();
                            window.applyJournalSubtaskFilters();
                            applyCategorySummary(data.category_summary);

                            input.value = '';
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Error al crear la tarea');
                        });
                });
            });
        };
        window.initJournalNewTaskInputs();

        // Toggle current
        document.querySelector('.container').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-toggle-current');
            if (!btn) return;

            fetch('<?= site_url('journal/toggle-current') ?>/' + btn.dataset.taskId, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    btn.querySelector('svg').setAttribute('fill', data.is_current ? '#ffc107' : '#adb5bd');
                    applyCategorySummary(data.category_summary);
                });
        });

        // Modal tiempo
        const timeModal = new bootstrap.Modal(document.getElementById('timeModal'));
        const timeDateField = document.getElementById('taskDate');
        const timeModalTitle = document.getElementById('timeModalTitle');

        document.querySelector('.container').addEventListener('click', function(e) {
            const subtaskTrigger = e.target.closest('.subtask-time-trigger');
            const taskTrigger = e.target.closest('.task-time-trigger');
            const trigger = subtaskTrigger || taskTrigger;
            if (!trigger) return;

            document.getElementById('timeMinutes').value = '';

            if (subtaskTrigger) {
                document.getElementById('timeMode').value = 'subtask';
                document.getElementById('timeTaskId').value = subtaskTrigger.dataset.subtaskId;
                timeModalTitle.textContent = 'Añadir tiempo a subtarea';
                timeDateField.classList.add('d-none');
            } else {
                document.getElementById('timeMode').value = 'task';
                document.getElementById('timeTaskId').value = trigger.dataset.taskId;
                timeModalTitle.textContent = 'Añadir tiempo / Fecha';
                timeDateField.classList.remove('d-none');
            }

            timeModal.show();
        });

        // Guardar tiempo y fecha
        document.getElementById('saveTimeBtn').addEventListener('click', async function(e) {
            e.preventDefault();
            const btn = this;

            if (btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';
            btn.disabled = true;
            btn.textContent = 'Guardando...';

            try {
                const mode = document.getElementById('timeMode').value;
                const id = document.getElementById('timeTaskId').value;
                let minutes = parseInt(document.getElementById('timeMinutes').value) || 0;
                const date = document.getElementById('taskDate').value || '';

                if (!id) throw new Error('Id vacío');
                if (minutes <= 0 && mode === 'subtask') throw new Error('Indica los minutos');

                if (mode === 'subtask') {
                    // Sumar tiempo a la subtarea (y, en el servidor, a la tarea padre)
                    const res = await fetch('<?= site_url('journal/subtasks') ?>/' + id + '/add-time', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ minutes })
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error('Error guardando tiempo');

                    const subtaskSpan = document.querySelector(`.subtask-time-trigger[data-subtask-id="${id}"]`);
                    if (subtaskSpan) subtaskSpan.textContent = (data.subtask_minutes / 60).toFixed(2) + ' h';

                    const taskSpan = document.querySelector(`.task-time-trigger[data-task-id="${data.task_id}"]`);
                    if (taskSpan) taskSpan.textContent = (data.task_minutes / 60).toFixed(2) + ' h';

                    applyCategorySummary(data.category_summary);
                } else {
                    let totalMinutes = 0;

                    // 1️⃣ Guardar minutos en task
                    if (minutes > 0) {
                        const resTime = await fetch('<?= site_url('journal/add-time') ?>/' + id, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                minutes
                            })
                        });
                        const timeData = await resTime.json();
                        if (!timeData.success) throw new Error('Error guardando tiempo');
                        totalMinutes = timeData.minutes;

                        // Actualizar UI
                        const span = document.querySelector(`.task-time-trigger[data-task-id="${id}"]`);
                        if (span) span.textContent = (totalMinutes / 60).toFixed(2) + ' h';

                        applyCategorySummary(timeData.category_summary);
                    }

                    // 2️⃣ Guardar log de fecha si hay
                    if (date) {
                        const resDate = await fetch('<?= site_url('journal/add-log') ?>/' + id, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                date,
                                minutes
                            }) // enviar minutos también para acumular en log
                        });
                        const dateData = await resDate.json();
                        if (!dateData.success) throw new Error(dateData.error || 'Error guardando log');
                    }
                }

                timeModal.hide();

            } catch (err) {
                console.error(err);
                alert(err.message || 'Error inesperado al guardar');
            } finally {
                btn.dataset.loading = '0';
                btn.disabled = false;
                btn.textContent = 'Guardar';
            }
        });

        // Completar / reabrir tarea
        const taskCompleteModal = new bootstrap.Modal(document.getElementById('taskCompleteModal'));
        const tcBadge = document.getElementById('tcBadge');
        const tcTitle = document.getElementById('tcTitle');
        const tcTaskId = document.getElementById('tcTaskId');
        const tcStart = document.getElementById('tcStart');
        const tcEnd = document.getElementById('tcEnd');
        const tcTime = document.getElementById('tcTime');
        const tcTimeHint = document.getElementById('tcTimeHint');
        const tcNote = document.getElementById('tcNote');
        const tcSaveBtn = document.getElementById('tcSaveBtn');
        const tcReopenBtn = document.getElementById('tcReopenBtn');

        function updateTcTimeHint() {
            tcTimeHint.textContent = '= ' + ((parseInt(tcTime.value, 10) || 0) / 60).toFixed(2) + ' h';
        }
        tcTime.addEventListener('input', updateTcTimeHint);

        document.querySelector('.container').addEventListener('click', function(e) {
            const btn = e.target.closest('.js-task-complete');
            if (!btn) return;

            const isDone = btn.dataset.done === '1';

            tcTaskId.value = btn.dataset.taskId;
            tcTitle.textContent = (isDone ? 'Resumen: ' : 'Completar: ') + btn.dataset.title;
            tcStart.value = btn.dataset.start || '';
            tcEnd.value = btn.dataset.end || (isDone ? '' : '<?= date('Y-m-d') ?>');
            tcTime.value = btn.dataset.time || 0;
            tcNote.value = btn.dataset.note || '';
            updateTcTimeHint();

            tcBadge.classList.toggle('d-none', !isDone);
            tcReopenBtn.classList.toggle('d-none', !isDone);
            tcSaveBtn.textContent = isDone ? 'Guardar cambios' : 'Marcar como terminada';

            taskCompleteModal.show();
        });

        async function saveTaskComplete(reopen) {
            const id = tcTaskId.value;
            if (!id) return;

            const payload = {
                start_time: tcStart.value,
                end_time: tcEnd.value,
                time_spent: parseInt(tcTime.value, 10) || 0,
                note: tcNote.value,
                reopen: reopen ? 1 : 0,
            };

            try {
                const res = await fetch('<?= site_url('journal/tasks') ?>/' + id + '/completar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) throw new Error();

                const btn = document.querySelector(`.js-task-complete[data-task-id="${id}"]`);
                if (btn) {
                    btn.dataset.done = data.is_done ? '1' : '0';
                    btn.dataset.start = data.start_time && data.start_time !== '0000-00-00 00:00:00' ? data.start_time.substring(0, 10) : '';
                    btn.dataset.end = data.end_time && data.end_time !== '0000-00-00 00:00:00' ? data.end_time.substring(0, 10) : '';
                    btn.dataset.time = data.time_spent;
                    btn.dataset.note = data.note || '';
                    btn.classList.toggle('is-done', data.is_done);
                    btn.title = data.is_done ? 'Ver resumen / reabrir' : 'Marcar como terminada';
                    btn.querySelector('i').className = 'bi ' + (data.is_done ? 'bi-check-circle-fill' : 'bi-check2-circle');

                    const li = btn.closest('.list-group-item');
                    if (li) li.classList.toggle('opacity-50', data.is_done);

                    const titleLink = li ? li.querySelector('.task-title-link') : null;
                    if (titleLink) titleLink.classList.toggle('text-decoration-line-through', data.is_done);

                    const timeSpan = document.querySelector(`.task-time-trigger[data-task-id="${id}"]`);
                    if (timeSpan) timeSpan.textContent = (data.time_spent / 60).toFixed(2) + ' h';
                }

                applyCategorySummary(data.category_summary);

                taskCompleteModal.hide();
            } catch (err) {
                alert('No se pudo guardar la tarea.');
            }
        }

        tcSaveBtn.addEventListener('click', () => saveTaskComplete(false));
        tcReopenBtn.addEventListener('click', () => saveTaskComplete(true));
    });

    // Subtareas inline en el listado
    document.addEventListener('DOMContentLoaded', function() {
        async function postJSON(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body ? JSON.stringify(body) : undefined
            });
            return res.json();
        }

        // Refleja en la barra de progreso del listado el amplitude/completed
        // que el servidor recalculó a partir de las subtareas.
        function updateTaskProgressSegments(taskId, progress) {
            if (!progress) return;
            const bar = document.querySelector(`.task-progress[data-task-id="${taskId}"]`);
            if (!bar) return;

            const pct = progress.amplitude > 0 ? Math.min(100, Math.round((progress.completed / progress.amplitude) * 100)) : 0;
            const filled = Math.floor(pct / 10);

            bar.querySelectorAll('.task-progress-segment').forEach((seg, i) => {
                seg.classList.toggle('filled', i < filled);
            });
        }

        // Modal renombrar subtarea
        const subtaskEditModal = new bootstrap.Modal(document.getElementById('subtaskEditModal'));
        const subtaskEditInput = document.getElementById('subtaskEditInput');
        const subtaskEditSaveBtn = document.getElementById('subtaskEditSaveBtn');
        let subtaskEditItem = null;

        subtaskEditSaveBtn.addEventListener('click', async () => {
            if (!subtaskEditItem) return;

            const title = subtaskEditInput.value.trim();
            if (!title) return;

            subtaskEditSaveBtn.disabled = true;
            try {
                const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskEditItem.dataset.id + '/editar', { title });
                if (!data.success) throw new Error();

                subtaskEditItem.querySelector('.jt-subtask-title').textContent = data.title;
                subtaskEditModal.hide();
            } catch (err) {
                alert('No se pudo renombrar la subtarea.');
            } finally {
                subtaskEditSaveBtn.disabled = false;
            }
        });

        subtaskEditInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                subtaskEditSaveBtn.click();
            }
        });

        // Modal sugerir subtareas (IA) — compartido por todos los paneles;
        // subtaskSuggestContext guarda a qué tarea/lista se refiere la sugerencia actual.
        const subtaskSuggestModal = new bootstrap.Modal(document.getElementById('subtaskSuggestModal'));
        const subtaskSuggestContexto = document.getElementById('subtaskSuggestContexto');
        const subtaskSuggestLoading = document.getElementById('subtaskSuggestLoading');
        const subtaskSuggestError = document.getElementById('subtaskSuggestError');
        const subtaskSuggestList = document.getElementById('subtaskSuggestList');
        const subtaskSuggestGenerateBtn = document.getElementById('subtaskSuggestGenerateBtn');
        const subtaskSuggestAddBtn = document.getElementById('subtaskSuggestAddBtn');
        let subtaskSuggestContext = null;

        function requestSubtaskSuggestions(taskId, list, emptyMsg, updateToggleBadge) {
            subtaskSuggestContext = { taskId, list, emptyMsg, updateToggleBadge };
            subtaskSuggestContexto.value = '';
            subtaskSuggestList.innerHTML = '';
            subtaskSuggestError.classList.add('d-none');
            subtaskSuggestLoading.classList.add('d-none');
            subtaskSuggestAddBtn.disabled = true;
            subtaskSuggestModal.show();
            subtaskSuggestContexto.focus();
        }

        subtaskSuggestGenerateBtn.addEventListener('click', async () => {
            if (!subtaskSuggestContext) return;
            const { taskId } = subtaskSuggestContext;

            subtaskSuggestList.innerHTML = '';
            subtaskSuggestError.classList.add('d-none');
            subtaskSuggestLoading.classList.remove('d-none');
            subtaskSuggestAddBtn.disabled = true;
            subtaskSuggestGenerateBtn.disabled = true;

            try {
                const data = await postJSON('<?= site_url('journal/tasks') ?>/' + taskId + '/sugerir-subtareas', {
                    contexto: subtaskSuggestContexto.value.trim(),
                });
                if (!data.success || !data.subtareas || !data.subtareas.length) {
                    throw new Error(data.error || 'Sin sugerencias');
                }

                data.subtareas.forEach((titulo, i) => {
                    const label = document.createElement('label');
                    label.className = 'd-flex align-items-center gap-2';
                    label.innerHTML = `
                        <input type="checkbox" class="form-check-input mt-0" checked id="sugerencia${i}">
                        <span>${titulo.replace(/</g, '&lt;')}</span>
                    `;
                    subtaskSuggestList.appendChild(label);
                });
                subtaskSuggestAddBtn.disabled = false;
            } catch (err) {
                subtaskSuggestError.textContent = err.message === 'Sin sugerencias'
                    ? 'No se generaron sugerencias. Prueba de nuevo.'
                    : (err.message || 'No se pudo contactar con la IA.');
                subtaskSuggestError.classList.remove('d-none');
            } finally {
                subtaskSuggestLoading.classList.add('d-none');
                subtaskSuggestGenerateBtn.disabled = false;
            }
        });

        subtaskSuggestAddBtn.addEventListener('click', async () => {
            if (!subtaskSuggestContext) return;
            const { taskId, list, emptyMsg, updateToggleBadge } = subtaskSuggestContext;

            const seleccionadas = [...subtaskSuggestList.querySelectorAll('input:checked')]
                .map(cb => cb.nextElementSibling.textContent);
            if (!seleccionadas.length) return;

            subtaskSuggestAddBtn.disabled = true;
            try {
                let lastProgress = null;
                for (const title of seleccionadas) {
                    const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + taskId + '/crear', { title });
                    if (data.success) {
                        list.appendChild(buildSubtaskItem(data.subtask, taskId));
                        lastProgress = data.progress;
                    }
                }
                emptyMsg.classList.add('d-none');
                updateToggleBadge(taskId);
                updateTaskProgressSegments(taskId, lastProgress);
                subtaskSuggestModal.hide();
            } catch (err) {
                alert('No se pudieron añadir todas las subtareas.');
            } finally {
                subtaskSuggestAddBtn.disabled = false;
            }
        });

        function buildSubtaskItem(subtask, taskId) {
            const item = document.createElement('div');
            item.className = 'jt-subtask-item';
            item.dataset.id = subtask.id;
            item.innerHTML = `
                <span class="jt-subtask-handle" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha"><i class="bi bi-circle"></i></button>
                <span class="jt-subtask-title"></span>
                <div class="jt-subtask-actions">
                    <span class="jt-subtask-time subtask-time-trigger" data-subtask-id="${subtask.id}" data-task-id="${taskId}">0.00 h</span>
                    <button type="button" class="jt-subtask-move js-move-top-subtask" title="Mover al principio" aria-label="Mover al principio"><i class="bi bi-arrow-bar-up"></i></button>
                    <button type="button" class="jt-subtask-move js-move-bottom-subtask" title="Mover al final" aria-label="Mover al final"><i class="bi bi-arrow-bar-down"></i></button>
                    <button type="button" class="jt-subtask-edit js-edit-subtask" title="Renombrar subtarea" aria-label="Renombrar subtarea"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="jt-subtask-delete js-delete-subtask" title="Eliminar subtarea" aria-label="Eliminar subtarea"><i class="bi bi-trash"></i></button>
                </div>
            `;
            item.querySelector('.jt-subtask-title').textContent = subtask.title;
            return item;
        }

        function persistSubtaskOrder(list) {
            const orden = [...list.querySelectorAll('.jt-subtask-item')].map(item => item.dataset.id);
            postJSON('<?= site_url('journal/subtasks/reordenar') ?>', { orden });
        }

        function updateToggleBadge(taskId) {
            const list = document.querySelector(`.jt-subtask-list[data-task-id="${taskId}"]`);
            const toggle = document.querySelector(`.jt-subtask-toggle[data-bs-target="#subtasks-${taskId}"]`);
            if (!list || !toggle) return;

            const total = list.querySelectorAll('.jt-subtask-item').length;
            const done = list.querySelectorAll('.jt-subtask-item.is-done').length;

            let countEl = toggle.querySelector('.jt-subtask-count');
            if (total > 0) {
                if (!countEl) {
                    countEl = document.createElement('span');
                    countEl.className = 'jt-subtask-count';
                    toggle.appendChild(countEl);
                }
                countEl.textContent = `${done}/${total}`;
                toggle.classList.add('has-subtasks');
            } else if (countEl) {
                countEl.remove();
                toggle.classList.remove('has-subtasks');
            }
        }

        // Filtros visuales de subtareas hechas (ocultar / empujar abajo).
        // Solo afectan a la presentación: el orden real en el DOM/BD no
        // cambia. Los botones .js-hide-done/.js-push-done se recrean en
        // cada refresco de la rejilla, así que se delega el click en
        // #journalGrid (que sobrevive a los refrescos) en vez de engancharlo
        // botón a botón.
        const subtaskFilterPrefs = {
            hide: localStorage.getItem('journalSubtaskHideDone') === '1',
            push: localStorage.getItem('journalSubtaskPushDone') === '1',
        };

        window.applyJournalSubtaskFilters = function() {
            document.querySelectorAll('.jt-subtask-list').forEach(list => {
                list.classList.toggle('hide-done', subtaskFilterPrefs.hide);
                list.classList.toggle('push-done', subtaskFilterPrefs.push);
            });
            document.querySelectorAll('.js-hide-done').forEach(btn => {
                btn.classList.toggle('active', subtaskFilterPrefs.hide);
            });
            document.querySelectorAll('.js-push-done').forEach(btn => {
                btn.classList.toggle('active', subtaskFilterPrefs.push);
            });
        };

        document.getElementById('journalGrid').addEventListener('click', (e) => {
            if (e.target.closest('.js-hide-done')) {
                subtaskFilterPrefs.hide = !subtaskFilterPrefs.hide;
                localStorage.setItem('journalSubtaskHideDone', subtaskFilterPrefs.hide ? '1' : '0');
                window.applyJournalSubtaskFilters();
            } else if (e.target.closest('.js-push-done')) {
                subtaskFilterPrefs.push = !subtaskFilterPrefs.push;
                localStorage.setItem('journalSubtaskPushDone', subtaskFilterPrefs.push ? '1' : '0');
                window.applyJournalSubtaskFilters();
            }
        });
        window.applyJournalSubtaskFilters();

        // Cablea cada panel de subtareas (input, botón añadir, sugerir IA,
        // lista con sus acciones y el drag&drop). Se puede llamar varias
        // veces: cada panel se marca con data-jt-init para no duplicar los
        // listeners al crear una tarea nueva (solo su panel es nuevo; tras
        // un refresco completo de la rejilla todos lo son).
        window.initJournalSubtaskPanels = function() {
            document.querySelectorAll('.jt-inline-subtasks:not([data-jt-init])').forEach(panel => {
                panel.dataset.jtInit = '1';

                const list = panel.querySelector('.jt-subtask-list');
                const taskId = list.dataset.taskId;
                const input = panel.querySelector('.jt-subtask-input');
                const addBtn = panel.querySelector('.jt-subtask-add-btn');
                const suggestBtn = panel.querySelector('.jt-subtask-suggest-btn');
                const emptyMsg = panel.querySelector('.jt-subtask-empty');

                suggestBtn.addEventListener('click', () => {
                    requestSubtaskSuggestions(taskId, list, emptyMsg, updateToggleBadge);
                });

                async function addSubtask() {
                    const title = input.value.trim();
                    if (!title) return;

                    addBtn.disabled = true;
                    try {
                        const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + taskId + '/crear', { title });
                        if (!data.success) throw new Error();

                        list.appendChild(buildSubtaskItem(data.subtask, taskId));
                        input.value = '';
                        emptyMsg.classList.add('d-none');
                        updateToggleBadge(taskId);
                        updateTaskProgressSegments(taskId, data.progress);
                    } catch (err) {
                        alert('No se pudo añadir la subtarea.');
                    } finally {
                        addBtn.disabled = false;
                        input.focus();
                    }
                }

                addBtn.addEventListener('click', addSubtask);
                input.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addSubtask();
                    }
                });

                list.addEventListener('click', async e => {
                    const toggleBtn = e.target.closest('.js-toggle-subtask');
                    if (toggleBtn) {
                        const item = toggleBtn.closest('.jt-subtask-item');
                        const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + item.dataset.id + '/toggle');
                        if (!data.success) return;

                        const isDone = !!data.is_done;
                        item.classList.toggle('is-done', isDone);
                        toggleBtn.querySelector('i').className = 'bi ' + (isDone ? 'bi-check-circle-fill' : 'bi-circle');
                        updateToggleBadge(taskId);
                        updateTaskProgressSegments(taskId, data.progress);
                        return;
                    }

                    const moveTopBtn = e.target.closest('.js-move-top-subtask');
                    if (moveTopBtn) {
                        const item = moveTopBtn.closest('.jt-subtask-item');
                        list.prepend(item);
                        persistSubtaskOrder(list);
                        return;
                    }

                    const moveBottomBtn = e.target.closest('.js-move-bottom-subtask');
                    if (moveBottomBtn) {
                        const item = moveBottomBtn.closest('.jt-subtask-item');
                        list.appendChild(item);
                        persistSubtaskOrder(list);
                        return;
                    }

                    const editBtn = e.target.closest('.js-edit-subtask');
                    if (editBtn) {
                        subtaskEditItem = editBtn.closest('.jt-subtask-item');
                        subtaskEditInput.value = subtaskEditItem.querySelector('.jt-subtask-title').textContent;
                        subtaskEditModal.show();
                        return;
                    }

                    const deleteBtn = e.target.closest('.js-delete-subtask');
                    if (deleteBtn) {
                        if (!confirm('¿Eliminar esta subtarea?')) return;

                        const item = deleteBtn.closest('.jt-subtask-item');
                        const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + item.dataset.id + '/borrar');
                        if (!data.success) return;

                        item.remove();
                        if (!list.querySelector('.jt-subtask-item')) {
                            emptyMsg.classList.remove('d-none');
                        }
                        updateToggleBadge(taskId);
                        updateTaskProgressSegments(taskId, data.progress);
                    }
                });

                Sortable.create(list, {
                    handle: '.jt-subtask-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: () => persistSubtaskOrder(list),
                });
            });
        };
        window.initJournalSubtaskPanels();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const toggleAllBtn = document.getElementById('toggleAllBtn');

        toggleAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.card > .collapse').forEach(collapseEl => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
                if (window.journalAllExpanded) {
                    bsCollapse.hide();
                } else {
                    bsCollapse.show();
                }
            });
            window.journalAllExpanded = !window.journalAllExpanded;
            toggleAllBtn.querySelector('i').className = window.journalAllExpanded ? 'bi bi-arrows-collapse' : 'bi bi-arrows-expand';
            toggleAllBtn.title = window.journalAllExpanded ? 'Cerrar todo' : 'Mostrar todo';
        });
    });
</script>

<?= $this->endSection() ?>