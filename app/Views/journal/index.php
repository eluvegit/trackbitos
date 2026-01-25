<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .card {
        margin-bottom: 0.25rem;
        font-size: 0.8rem;
    }

    .card-header {
        padding: 0.25rem 0.35rem;
        font-size: 0.85rem;
        cursor: pointer;
        color: #fff;
    }

    .card-body {
        padding: 0.25rem 0.35rem;
    }

    .list-group-item {
        padding: 0.4rem 0.35rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        min-height: 40px;
        /* espacio para barra de progreso */
    }

    .new-task-input {
        padding: 0.2rem 0.35rem;
        font-size: 0.8rem;
        margin-top: 0.2rem;
    }

    .container {
        padding: 0.2rem;
    }

    .task-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 6px;
        display: grid;
        grid-template-columns: repeat(10, 1fr);
        gap: 2px;
        pointer-events: none;
    }

    .task-progress-segment {
        background-color: #e9ecef;
        border-radius: 2px;
    }

    .task-progress-segment.filled {
        background-color: #198754;
    }

    .task-time-trigger {
        cursor: pointer;
        white-space: nowrap;
    }

    .task-time-trigger:hover {
        text-decoration: underline;
    }

    .task-title-link {
        display: inline-block;
        max-width: 100%;
    }

    .current-star {
        display: flex;
        align-items: center;
    }

    .task-title {
        flex-grow: 1;
        margin-right: 0.5rem;
    }
</style>

<div class="container py-1">

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <div class="d-flex align-items-center">
            <h3 class="mb-0" style="line-height: 1;">Journal</h3>
        </div>
        <div class="btn-group btn-group-sm">
            <?php
            // Prioridad
            $priorityNext = $filterPriority ? 0 : 1; // Esto está bien, sigue funcionando
            $priorityClass = $filterPriority ? 'btn-primary' : 'btn-outline-primary';

            // Focus
            $focusNext = $filterFocus === 'focus' ? 'todas' : 'focus';
            $focusClass = $filterFocus === 'focus' ? 'btn-primary' : 'btn-outline-primary';

            // Vista
            $portadasNext = $view_mode === 'portadas' ? 'listado' : 'portadas';
            $portadasClass = $view_mode === 'portadas' ? 'btn-primary' : 'btn-outline-primary';
            ?>

            <div class="btn-group btn-group-sm">
                <!-- Prioridad -->
                <a href="<?= site_url("journal?filterFocus={$filterFocus}&view={$view_mode}&priority={$priorityNext}") ?>"
                    class="btn <?= $priorityClass ?>" title="Prioridad">
                    <!-- Icono de exclamación -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0-1A6 6 0 1 1 8 2a6 6 0 0 1 0 12z" />
                        <path d="M7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.93-6.481a.5.5 0 0 1 .538.497v3.967a.5.5 0 0 1-1 0V5.016a.5.5 0 0 1 .462-.497z" />
                    </svg>
                </a>

                <!-- Focus -->
                <a href="<?= site_url("journal?filterFocus={$focusNext}&view={$view_mode}&priority={$filterPriority}") ?>"
                    class="btn <?= $focusClass ?>" title="Focus">
                    <?php if ($filterFocus === 'focus'): ?>
                        <!-- Estrella rellena -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#ffc107" class="bi bi-star-fill" viewBox="0 0 16 16">
                            <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z" />
                        </svg>
                    <?php else: ?>
                        <!-- Estrella vacía -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-star" viewBox="0 0 16 16">
                            <path d="M2.866 14.85c-.078.444.36.791.746.593L8 13.187l4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.523-3.356c.329-.314.158-.888-.283-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.495 4.935l-4.898.696c-.441.062-.612.636-.282.95l3.523 3.356-.83 4.73zM8 12.027l-3.763 1.933.717-4.088-2.97-2.829 4.102-.583L8 2.223l1.914 3.237 4.102.583-2.97 2.828.717 4.089L8 12.027z" />
                        </svg>
                    <?php endif; ?>
                </a>


                <!-- Vista -->
                <a href="<?= site_url("journal?filterFocus={$filterFocus}&view={$portadasNext}&priority={$filterPriority}") ?>"
                    class="btn <?= $portadasClass ?>" title="Vista">
                    <?php if ($view_mode === 'portadas'): ?>
                        <!-- Icono de cuadrícula -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                            <path d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z" />
                        </svg>
                    <?php else: ?>
                        <!-- Icono de lista -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M2.5 12.5a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z" />
                        </svg>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>


    <?php foreach ($categories as $category): ?>
        <?php
        $catId = $category['id'];
        $catName = $category['name'];
        $catColor = $category['color'] ?? '#000000';
        $catTasks = $tasksByCategory[$catName] ?? [];

        // Tiempo total de tareas actuales no completadas
        $totalHours = number_format(($totalTimeByCategory[$catName] ?? 0) / 60, 2);


        // Contar tareas completadas
        $completedCount = 0;
        foreach ($catTasks as $task) {
            if (!empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00') {
                $completedCount++;
            }
        }
        ?>

        <div class="card mb-1">
            <div class="card-header d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse"
                href="#cat-<?= $catId ?>"
                style="background-color: <?= esc($catColor) ?>;">

                <div>
                    <strong><?= esc($catName) ?></strong>

                    <!-- Mini barra de tareas -->
                    <?php
                    $totalTasks = count($catTasks);
                    $currentTasks = 0;
                    foreach ($catTasks as $task) {
                        if (!empty($task['is_current'])) $currentTasks++;
                    }
                    ?>
                    <span class="ms-2 badge bg-light text-dark"
                        title="Tareas actuales">
                        <?= $currentTasks ?> actuales
                    </span>

                    <span class="ms-1 badge bg-light text-dark"
                        title="Total de tareas">
                        <?= $totalTasks ?> total
                    </span>

                    <!-- Tiempo total -->
                    <span class="small ms-2" title="Tiempo total">
                        <?= $totalHours ?> h
                    </span>
                </div>

                <!-- Progreso de completadas -->
                <?php
                $completedCount = 0;
                foreach ($catTasks as $task) {
                    if (!empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00') {
                        $completedCount++;
                    }
                }
                ?>
                <span class="badge bg-light text-dark" title="Completadas">
                    <?= $completedCount ?>/<?= $totalTasks ?>
                </span>
            </div>


            <div class="collapse" id="cat-<?= $catId ?>">
                <div class="card-body">

                    <?php if ($view_mode === 'listado'): ?>
                        <!-- MODO LISTA -->
                        <ul class="list-group mb-2" id="task-list-<?= $catId ?>">
                            <?php if (empty($catTasks)): ?>
                                <li class="list-group-item text-muted">No hay tareas aún.</li>
                            <?php else: ?>
                                <?php foreach ($catTasks as $task): ?>
                                    <?php
                                    $amplitude = (int)($task['amplitude'] ?? 0);
                                    $completed = (int)($task['completed'] ?? 0);
                                    $percentage = $amplitude > 0 ? min(100, round(($completed / $amplitude) * 100)) : 0;
                                    $filled = (int)floor($percentage / 10);
                                    ?>
                                    <li class="list-group-item">
                                        <div class="d-flex align-items-center gap-1 flex-grow-1 my-1">
                                            <!-- Estrella -->
                                            <span class="current-star btn-toggle-current" data-task-id="<?= $task['id'] ?>" title="Marcar como actual">
                                                <button style="all:unset; display:flex; align-items:center; justify-content:center; width:32px; height:32px;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="<?= !empty($task['is_current']) ? '#ffc107' : '#adb5bd' ?>" class="bi bi-star-fill" viewBox="0 0 16 16">
                                                        <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- Título -->
                                            <a href="<?= site_url('journal/edit/' . $task['id']) ?>"
                                                class="text-dark text-decoration-none task-title-link <?= (!empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00') ? 'text-decoration-line-through' : '' ?>">
                                                <?= esc($task['title']) ?>
                                            </a>
                                        </div>

                                        <!-- Tiempo -->
                                        <span class="text-muted small task-time-trigger" data-task-id="<?= $task['id'] ?>">
                                            <?= number_format(($task['time_spent'] ?? 0) / 60, 2) ?> h
                                        </span>

                                        <?php if ($amplitude > 0): ?>
                                            <div class="task-progress">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <div class="task-progress-segment <?= $i <= $filled ? 'filled' : '' ?>"></div>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <input type="text" class="form-control new-task-input" placeholder="Nueva tarea..." data-category-id="<?= $catId ?>">
                    <?php else: ?>
                        <!-- MODO PORTADAS -->
                        <div class="row g-2">
                            <?php foreach ($catTasks as $task): ?>
                                <?php
                                $amplitude = (int)($task['amplitude'] ?? 0);
                                $completed = (int)($task['completed'] ?? 0);
                                $percentage = $amplitude > 0 ? min(100, round(($completed / $amplitude) * 100)) : 0;
                                $filled = (int)floor($percentage / 10);
                                ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card h-100">

                                        <?php if (!empty($task['image'])): ?>
                                            <img src="<?= base_url($task['image']) ?>" class="card-img-top" alt="<?= esc($task['title']) ?>">
                                        <?php else: ?>
                                            <div style="height:150px; background-color:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#6c757d;">
                                                Sin imagen
                                            </div>
                                        <?php endif; ?>

                                        <div class="card-body p-2 d-flex flex-column">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <a href="<?= site_url('journal/edit/' . $task['id']) ?>" class="text-dark text-decoration-none flex-grow-1">
                                                    <h6 class="card-title mb-0"><?= esc($task['title']) ?></h6>
                                                </a>

                                                <!-- Estrella -->
                                                <span class="current-star btn-toggle-current ms-1" data-task-id="<?= $task['id'] ?>" title="Marcar como actual">
                                                    <button style="all:unset; display:flex; align-items:center; justify-content:center; width:24px; height:24px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="<?= !empty($task['is_current']) ? '#ffc107' : '#adb5bd' ?>" class="bi bi-star-fill" viewBox="0 0 16 16">
                                                            <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </div>

                                            <!-- Tiempo (clicable para abrir modal) -->
                                            <span class="text-muted small task-time-trigger mb-1"
                                                data-task-id="<?= $task['id'] ?>"
                                                style="cursor:pointer;">
                                                <?= number_format(($task['time_spent'] ?? 0) / 60, 2) ?> h
                                            </span>

                                            <!-- Barra de progreso -->
                                            <?php if ($amplitude > 0): ?>
                                                <div class="task-progress mt-auto" style="height:6px; display:grid; grid-template-columns:repeat(10,1fr); gap:2px;">
                                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                                        <div class="task-progress-segment <?= $i <= $filled ? 'filled' : '' ?>" style="border-radius:2px; background-color: <?= $i <= $filled ? '#198754' : '#e9ecef' ?>;"></div>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>


                </div>
            </div>
        </div>
    <?php endforeach; ?>


</div>

<!-- MODAL TIEMPO Y FECHA -->
<div class="modal fade" id="timeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir tiempo / Fecha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="number" id="timeMinutes" class="form-control mb-2" placeholder="Minutos" min="1">
                <input type="date" id="taskDate" class="form-control mb-2" value="<?= date('Y-m-d') ?>">
                <input type="hidden" id="timeTaskId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="saveTimeBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Crear tarea
        document.querySelectorAll('.new-task-input').forEach(input => {
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
                        li.className = 'list-group-item';

                        li.innerHTML = `
        <div class="d-flex align-items-center gap-1 flex-grow-1 my-1">
            <span class="current-star btn-toggle-current" data-task-id="${task.id}">
                <button style="all:unset; display:flex; align-items:center; justify-content:center; width:32px; height:32px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-star-fill" viewBox="0 0 16 16">
                        <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                    </svg>
                </button>
            </span>

            <a href="<?= site_url('journal/edit') ?>/${task.id}"
               class="text-dark text-decoration-none task-title-link">
                ${task.title}
            </a>
        </div>

        <span class="text-muted small task-time-trigger" data-task-id="${task.id}">
            0.00 h
        </span>
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

            fetch('<?= site_url('journal/toggle-current') ?>/' + btn.dataset.taskId, {
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
        document.querySelector('.container').addEventListener('click', function(e) {
            const trigger = e.target.closest('.task-time-trigger');
            if (!trigger) return;

            document.getElementById('timeTaskId').value = trigger.dataset.taskId;
            document.getElementById('timeMinutes').value = '';
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
                const taskId = document.getElementById('timeTaskId').value;
                let minutes = parseInt(document.getElementById('timeMinutes').value) || 0;
                const date = document.getElementById('taskDate').value || '';

                if (!taskId) throw new Error('TaskId vacío');

                let totalMinutes = 0;

                // 1️⃣ Guardar minutos en task
                if (minutes > 0) {
                    const resTime = await fetch('<?= site_url('journal/add-time') ?>/' + taskId, {
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
                    const span = document.querySelector(`.task-time-trigger[data-task-id="${taskId}"]`);
                    if (span) span.textContent = (totalMinutes / 60).toFixed(2) + ' h';
                }

                // 2️⃣ Guardar log de fecha si hay
                if (date) {
                    const resDate = await fetch('<?= site_url('journal/add-log') ?>/' + taskId, {
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
</script>

<?= $this->endSection() ?>