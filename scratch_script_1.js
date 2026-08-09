
    document.addEventListener('DOMContentLoaded', function() {

        // Crear tarea
        document.querySelectorAll('.new-task-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key !== 'Enter') return;
                const title = this.value.trim();
                const categoryId = this.dataset.categoryId;
                if (!title || !categoryId) return;

                fetch('"PHP"', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            title,
                            category_id: categoryId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            alert('No se pudo crear la tarea');
                            return;
                        }

                        const task = data.task;
                        const list = document.getElementById('task-list-' + categoryId);

                        // Eliminar mensaje "No hay tareas"
                        const empty = list.querySelector('.text-muted');
                        if (empty) empty.remove();

                        // Crear nodo de tarea
                        const li = document.createElement('li');
                        li.className = 'list-group-item p-1';

                        li.innerHTML = `
    <div class="d-flex align-items-center gap-2">

        <div class="d-flex align-items-center gap-1 flex-grow-1">

            <span class="current-star btn-toggle-current" data-task-id="${task.id}">
                <button style="all:unset; display:flex; align-items:center; justify-content:center; width:28px; height:28px;">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="16"
                         height="16"
                         fill="#adb5bd"
                         class="bi bi-star-fill"
                         viewBox="0 0 16 16">
                        <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                    </svg>
                </button>
            </span>

            <a href=""PHP"/${task.id}"
               class="text-decoration-none task-title-link">
                ${task.title}
            </a>

            <span class="small text-muted">hoy</span>
        </div>

        <span class="text-muted small ms-auto task-time-trigger"
              data-task-id="${task.id}">
            0.00 h
        </span>
    </div>
`;

                        list.appendChild(li);

                        // Limpiar input
                        input.value = '';
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error al crear la tarea');
                    });

            });
        });

        // Toggle current
        document.querySelector('.container').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-toggle-current');
            if (!btn) return;

            fetch('"PHP"/' + btn.dataset.taskId, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    btn.querySelector('svg').setAttribute('fill', data.is_current ? '#ffc107' : '#adb5bd');
                });
        });

        // Modal tiempo
        const timeModal = new bootstrap.Modal(document.getElementById('timeModal'));
        const timeDateField = document.getElementById('taskDate');
        const timeModalTitle = document.getElementById('timeModalTitle');

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
                const data = await postJSON('"PHP"/' + subtaskEditItem.dataset.id + '/editar', { title });
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
                    const res = await fetch('"PHP"/' + id + '/add-time', {
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
                } else {
                    let totalMinutes = 0;

                    // 1️⃣ Guardar minutos en task
                    if (minutes > 0) {
                        const resTime = await fetch('"PHP"/' + id, {
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
                    }

                    // 2️⃣ Guardar log de fecha si hay
                    if (date) {
                        const resDate = await fetch('"PHP"/' + id, {
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

        function buildSubtaskItem(subtask, taskId) {
            const item = document.createElement('div');
            item.className = 'jt-subtask-item';
            item.dataset.id = subtask.id;
            item.innerHTML = `
                <span class="jt-subtask-handle" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha"><i class="bi bi-circle"></i></button>
                <span class="jt-subtask-title"></span>
                <span class="jt-subtask-time subtask-time-trigger" data-subtask-id="${subtask.id}" data-task-id="${taskId}">0.00 h</span>
                <button type="button" class="jt-subtask-edit js-edit-subtask" title="Renombrar subtarea" aria-label="Renombrar subtarea"><i class="bi bi-pencil"></i></button>
                <button type="button" class="jt-subtask-delete js-delete-subtask" title="Eliminar subtarea" aria-label="Eliminar subtarea"><i class="bi bi-trash"></i></button>
            `;
            item.querySelector('.jt-subtask-title').textContent = subtask.title;
            return item;
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

        document.querySelectorAll('.jt-inline-subtasks').forEach(panel => {
            const list = panel.querySelector('.jt-subtask-list');
            const taskId = list.dataset.taskId;
            const input = panel.querySelector('.jt-subtask-input');
            const addBtn = panel.querySelector('.jt-subtask-add-btn');
            const emptyMsg = panel.querySelector('.jt-subtask-empty');

            async function addSubtask() {
                const title = input.value.trim();
                if (!title) return;

                addBtn.disabled = true;
                try {
                    const data = await postJSON('"PHP"/' + taskId + '/crear', { title });
                    if (!data.success) throw new Error();

                    list.appendChild(buildSubtaskItem(data.subtask, taskId));
                    input.value = '';
                    emptyMsg.classList.add('d-none');
                    updateToggleBadge(taskId);
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
                    const data = await postJSON('"PHP"/' + item.dataset.id + '/toggle');
                    if (!data.success) return;

                    const isDone = !!data.is_done;
                    item.classList.toggle('is-done', isDone);
                    toggleBtn.querySelector('i').className = 'bi ' + (isDone ? 'bi-check-circle-fill' : 'bi-circle');
                    updateToggleBadge(taskId);
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
                    const item = deleteBtn.closest('.jt-subtask-item');
                    const data = await postJSON('"PHP"/' + item.dataset.id + '/borrar');
                    if (!data.success) return;

                    item.remove();
                    if (!list.querySelector('.jt-subtask-item')) {
                        emptyMsg.classList.remove('d-none');
                    }
                    updateToggleBadge(taskId);
                }
            });

            Sortable.create(list, {
                handle: '.jt-subtask-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: () => {
                    const orden = [...list.querySelectorAll('.jt-subtask-item')].map(item => item.dataset.id);
                    postJSON('"PHP"', { orden });
                },
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const toggleAllBtn = document.getElementById('toggleAllBtn');
        let allExpanded = false;

        toggleAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.card > .collapse').forEach(collapseEl => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
                if (allExpanded) {
                    bsCollapse.hide();
                } else {
                    bsCollapse.show();
                }
            });
            allExpanded = !allExpanded;
            toggleAllBtn.querySelector('i').className = allExpanded ? 'bi bi-arrows-collapse' : 'bi bi-arrows-expand';
            toggleAllBtn.title = allExpanded ? 'Cerrar todo' : 'Mostrar todo';
        });
    });
