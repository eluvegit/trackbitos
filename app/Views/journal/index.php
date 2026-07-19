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

    .task-title-link {
        color: beige;
    }
</style>

<?php
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return 'sin actividad';
    }

    $time = strtotime($datetime);
    $diff = time() - $time;

    $days = floor($diff / 86400); // segundos en un día
    if ($days < 1) {
        return 'hoy';
    }

    if ($days < 7) {
        return "hace {$days} días";
    }

    $weeks = floor($days / 7);
    if ($weeks < 5) {
        return "hace {$weeks} sem";
    }

    $months = floor($days / 30);
    if ($months < 12) {
        return "hace {$months} meses";
    }

    $years = floor($days / 365);
    return "hace {$years} años";
}
?>

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
    <div class="d-flex align-items-center">
        <h3 class="mb-0 me-3" style="line-height: 1;">Journal</h3>
        <button id="toggleAllBtn" class="btn btn-sm" type="button">
            Mostrar todo
        </button>
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

<?php
$taskCounts = [];
foreach ($categories as $category) {
    $catName = $category['name'];
    $catTasksAll = $allTasksByCategory[$catName] ?? [];

    $taskCounts[$catName] = [
        'total'     => count($catTasksAll),
        'current'   => count(array_filter($catTasksAll, fn($t) => !empty($t['is_current']))),
        'completed' => count(array_filter($catTasksAll, fn($t) => !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00')),
    ];
}
?>

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

    <?php
    $totalTasks = count($catTasks); // total de tareas en la categoría
    $currentTasks = count(array_filter($catTasks, fn($t) => !empty($t['is_current']))); // actuales
    $completedTasks = count(array_filter($catTasks, fn($t) => !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00')); // completadas
    ?>
    <?php
    $counts = $taskCounts[$catName] ?? ['total' => 0, 'current' => 0, 'completed' => 0];
    ?>
    <div class="card mb-1">
        <div class="card-header d-flex justify-content-between align-items-center p-0" data-bs-toggle="collapse" href="#cat-<?= $catId ?>">
            <?php
            $current = $counts['current'];
            $completed = $counts['completed'];
            $total = max(1, $counts['total']); // evitar división por 0

            $currentPerc = round(($current / $total) * 100);
            $completedPerc = round(($completed / $total) * 100);
            $remainingPerc = 100 - $currentPerc - $completedPerc;
            ?>
            <!-- Fondo título -->
            <div style="background-color: <?= esc($catColor) ?>; padding: 0.25rem 0.5rem; flex-grow:1; display:flex; align-items:center; gap:0.5rem;">

                <!-- Izquierda -->
                <div class="d-flex align-items-center gap-2">
                    <span style="display:inline-block; width:35px; text-align:right;">
                        <?= $progressByCategory[$catName]['completed'] ?>/<?= max(1, $progressByCategory[$catName]['total']) ?>
                    </span>

                    <strong><?= esc($catName) ?></strong>
                    <?php
                    $lastCategoryDate = $lastCategoryActivity[$catName] ?? null;
                    ?>
                    <span class="small text-muted">
                        <strong><?= time_ago($lastCategoryDate) ?></strong>
                    </span>

                </div>

                <!-- Derecha -->
                <span class="small ms-auto"><?= $totalHours ?> h</span>
            </div>

            <div style="position: relative; display:flex; width:50px; height:16px; margin-left:0.5rem; border-radius:4px; overflow:hidden; border:1px solid #ccc; cursor:pointer;"
                title="Actuales: <?= $progressByCategory[$category['name']]['current'] ?>, Completadas: <?= $progressByCategory[$category['name']]['completed'] ?>, Total: <?= $progressByCategory[$category['name']]['total'] ?>">

                <div style="width:<?= $progressByCategory[$category['name']]['currentPerc'] ?>%; background-color:#ffc107;"></div>
                <div style="width:<?= $progressByCategory[$category['name']]['completedPerc'] ?>%; background-color:#198754;"></div>
                <div style="width:<?= $progressByCategory[$category['name']]['remainingPerc'] ?>%; background-color:#e9ecef;"></div>
            </div>
        </div>

        <div class="collapse" id="cat-<?= $catId ?>">
            <div class="card-body">
                <?php
                usort($catTasks, function ($a, $b) {
                    // 1. Estados booleanos
                    $aDone = (!empty($a['end_time']) && $a['end_time'] !== '0000-00-00 00:00:00');
                    $bDone = (!empty($b['end_time']) && $b['end_time'] !== '0000-00-00 00:00:00');
                    $aCurrent = !empty($a['is_current']);
                    $bCurrent = !empty($b['is_current']);

                    // --- PRIORIDAD 1: GRUPOS (Actuales > Pendientes > Completadas) ---
                    if ($aDone !== $bDone) return $aDone ? 1 : -1;
                    if ($aCurrent !== $bCurrent) return $aCurrent ? -1 : 1;

                    // --- PRIORIDAD 2: PROGRESO (Mayor a Menor) ---
                    // Calculamos el ratio de progreso (completado / amplitud)
                    $aAmp = (int)($a['amplitude'] ?? 0);
                    $bAmp = (int)($b['amplitude'] ?? 0);
                    $aProg = $aAmp > 0 ? (int)($a['completed'] ?? 0) / $aAmp : 0;
                    $bProg = $bAmp > 0 ? (int)($b['completed'] ?? 0) / $bAmp : 0;

                    if ($aProg !== $bProg) {
                        return ($aProg > $bProg) ? -1 : 1;
                    }

                    // --- PRIORIDAD 3: TIEMPO INVERTIDO (Mayor a Menor) ---
                    $aTime = (int)($a['time_spent'] ?? 0);
                    $bTime = (int)($b['time_spent'] ?? 0);

                    if ($aTime === $bTime) return 0;
                    return ($aTime > $bTime) ? -1 : 1;
                });
                ?>
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

                                <?php
                                // Detectar si está completada
                                $isDone = (!empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00');
                                ?>
                                <li class="list-group-item p-1 <?= $isDone ? 'opacity-50' : '' ?>">

                                    <!-- Fila principal -->
                                    <div class="d-flex align-items-center gap-2">

                                        <!-- Bloque izquierdo -->
                                        <div class="d-flex align-items-center gap-1 flex-grow-1">

                                            <!-- Estrella -->
                                            <span class="current-star btn-toggle-current"
                                                data-task-id="<?= $task['id'] ?>">
                                                <button style="all:unset; display:flex; align-items:center; justify-content:center; width:28px; height:28px;">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        width="16"
                                                        height="16"
                                                        fill="<?= !empty($task['is_current']) ? '#ffc107' : '#adb5bd' ?>"
                                                        class="bi bi-star-fill"
                                                        viewBox="0 0 16 16">
                                                        <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73-3.523-3.356c-.329-.314-.158-.888.283-.95l4.898-.696 2.043-4.143c.197-.4.73-.4.927 0l2.043 4.143 4.898.696c.441.062.612.636.282.95l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z" />
                                                    </svg>
                                                </button>
                                            </span>

                                            <!-- Título -->
                                            <a href="<?= site_url('journal/edit/' . $task['id']) ?>"
                                                class="text-decoration-none task-title-link <?= (!empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00') ? 'text-decoration-line-through' : '' ?>">
                                                <?= esc($task['title']) ?>
                                            </a>

                                            <!-- Fecha -->
                                            <span class="small text-muted">
                                                <?= time_ago($lastTaskActivity[$task['id']] ?? null) ?>
                                            </span>
                                        </div>

                                        <!-- Tiempo -->
                                        <span class="text-muted small ms-auto task-time-trigger"
                                            data-task-id="<?= $task['id'] ?>">
                                            <?= number_format(($task['time_spent'] ?? 0) / 60, 2) ?> h
                                        </span>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <?php if ($amplitude > 0): ?>
                                        <div class="task-progress mt-1">
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
                                            <a href="<?= site_url('journal/edit/' . $task['id']) ?>" class="text-decoration-none flex-grow-1 task-title-link">
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

                                        <?php
                                        $lastTaskDate = $lastTaskActivity[$task['id']] ?? null;
                                        ?>

                                        <span class="small text-muted">
                                            <?= time_ago($lastTaskDate) ?>
                                        </span>

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

            <a href="<?= site_url('journal/edit') ?>/${task.id}"
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

    document.addEventListener('DOMContentLoaded', function() {
        const toggleAllBtn = document.getElementById('toggleAllBtn');
        let allExpanded = false;

        toggleAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.card .collapse').forEach(collapseEl => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
                if (allExpanded) {
                    bsCollapse.hide();
                } else {
                    bsCollapse.show();
                }
            });
            allExpanded = !allExpanded;
            toggleAllBtn.textContent = allExpanded ? 'Cerrar todo' : 'Mostrar todo';
        });
    });
</script>

<?= $this->endSection() ?>