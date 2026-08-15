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

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
    <h3 class="mb-0" style="line-height: 1;">Journal</h3>
    <div class="btn-group btn-group-sm journal-toolbar">
        <a href="<?= site_url('journal/que-hacer') ?>" class="btn btn-outline-primary" title="¿Qué hago ahora?">
            <i class="bi bi-shuffle"></i>
        </a>
        <button id="toggleAllBtn" class="btn btn-outline-secondary" type="button" title="Mostrar todo">
            <i class="bi bi-arrows-expand"></i>
        </button>
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

        // Hechos
        $hechosNext = $filterHechos === 'ocultar' ? 'mostrar' : 'ocultar';
        $hechosClass = $filterHechos === 'ocultar' ? 'btn-primary' : 'btn-outline-primary';

        // Cada enlace envía solo la clave que cambia; las demás se quedan
        // como estén guardadas en su propia cookie (stickyFilter), para que
        // un valor "congelado" en esta renderización no pise a otro filtro
        // que se haya cambiado después en otra pestaña/carga.
        $qs = fn($overrides) => http_build_query($overrides);
        ?>

            <!-- Prioridad -->
            <a href="<?= site_url('journal') . '?' . $qs(['priority' => $priorityNext]) ?>"
                class="btn <?= $priorityClass ?>" title="Prioridad">
                <!-- Icono de exclamación -->
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0-1A6 6 0 1 1 8 2a6 6 0 0 1 0 12z" />
                    <path d="M7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.93-6.481a.5.5 0 0 1 .538.497v3.967a.5.5 0 0 1-1 0V5.016a.5.5 0 0 1 .462-.497z" />
                </svg>
            </a>

            <!-- Focus -->
            <a href="<?= site_url('journal') . '?' . $qs(['filterFocus' => $focusNext]) ?>"
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

            <!-- Hechos -->
            <a href="<?= site_url('journal') . '?' . $qs(['hechos' => $hechosNext]) ?>"
                class="btn <?= $hechosClass ?>" title="<?= $filterHechos === 'ocultar' ? 'Mostrar hechos' : 'Ocultar hechos' ?>">
                <?php if ($filterHechos === 'ocultar'): ?>
                    <!-- Ojo tachado: los hechos están ocultos -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16">
                        <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z" />
                        <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z" />
                        <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.879 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z" />
                    </svg>
                <?php else: ?>
                    <!-- Ojo abierto: los hechos están visibles -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.133 13.133 0 0 1 1.172 8z" />
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z" />
                    </svg>
                <?php endif; ?>
            </a>

            <!-- Vista -->
            <a href="<?= site_url('journal') . '?' . $qs(['view' => $portadasNext]) ?>"
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
    .jt-subtask-delete {
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

    .jt-subtask-add { display: flex; gap: 6px; }
    .jt-subtask-add .form-control { flex: 1 1 auto; }
</style>

<div class="journal-grid">
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
    <div class="card h-100">
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

                                        <?php
                                        $subs = $subtasksByTask[$task['id']] ?? [];
                                        $subsTotal = count($subs);
                                        $subsDone = count(array_filter($subs, fn($s) => !empty($s['is_done'])));
                                        ?>

                                        <!-- Subtareas -->
                                        <button type="button" class="jt-subtask-toggle <?= $subsTotal > 0 ? 'has-subtasks' : '' ?>"
                                            data-bs-toggle="collapse" data-bs-target="#subtasks-<?= $task['id'] ?>"
                                            title="Subtareas">
                                            <i class="bi bi-list-check"></i>
                                            <?php if ($subsTotal > 0): ?>
                                                <span class="jt-subtask-count"><?= $subsDone ?>/<?= $subsTotal ?></span>
                                            <?php endif; ?>
                                        </button>

                                        <!-- Terminar / resumen -->
                                        <button type="button" class="jt-task-complete-btn js-task-complete <?= $isDone ? 'is-done' : '' ?>"
                                            data-task-id="<?= $task['id'] ?>"
                                            data-title="<?= esc($task['title'], 'attr') ?>"
                                            data-start="<?= !empty($task['start_time']) && $task['start_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['start_time'])) : '' ?>"
                                            data-end="<?= !empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['end_time'])) : '' ?>"
                                            data-time="<?= (int)($task['time_spent'] ?? 0) ?>"
                                            data-note="<?= esc($task['note'] ?? '', 'attr') ?>"
                                            data-done="<?= $isDone ? '1' : '0' ?>"
                                            title="<?= $isDone ? 'Ver resumen / reabrir' : 'Marcar como terminada' ?>">
                                            <i class="bi <?= $isDone ? 'bi-check-circle-fill' : 'bi-check2-circle' ?>"></i>
                                        </button>

                                        <!-- Tiempo -->
                                        <span class="text-muted small ms-auto task-time-trigger"
                                            data-task-id="<?= $task['id'] ?>">
                                            <?= number_format(($task['time_spent'] ?? 0) / 60, 2) ?> h
                                        </span>
                                    </div>

                                    <!-- Barra de progreso -->
                                    <div class="task-progress mt-1" data-task-id="<?= $task['id'] ?>">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <div class="task-progress-segment <?= $i <= $filled ? 'filled' : '' ?>"></div>
                                        <?php endfor; ?>
                                    </div>

                                    <!-- Subtareas (inline, plegable) -->
                                    <div class="collapse jt-inline-subtasks" id="subtasks-<?= $task['id'] ?>">
                                        <div class="jt-subtask-list" data-task-id="<?= $task['id'] ?>">
                                            <?php foreach ($subs as $s): ?>
                                                <?php $sDone = !empty($s['is_done']); ?>
                                                <div class="jt-subtask-item <?= $sDone ? 'is-done' : '' ?>" data-id="<?= (int)$s['id'] ?>">
                                                    <span class="jt-subtask-handle" title="Arrastrar para reordenar">
                                                        <i class="bi bi-grip-vertical"></i>
                                                    </span>
                                                    <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha">
                                                        <i class="bi <?= $sDone ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                                                    </button>
                                                    <span class="jt-subtask-title"><?= esc($s['title']) ?></span>
                                                    <span class="jt-subtask-time subtask-time-trigger"
                                                        data-subtask-id="<?= (int)$s['id'] ?>"
                                                        data-task-id="<?= $task['id'] ?>">
                                                        <?= number_format(($s['time_spent'] ?? 0) / 60, 2) ?> h
                                                    </span>
                                                    <button type="button" class="jt-subtask-edit js-edit-subtask" title="Renombrar subtarea" aria-label="Renombrar subtarea">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="jt-subtask-delete js-delete-subtask" title="Eliminar subtarea" aria-label="Eliminar subtarea">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="text-muted small mb-2 jt-subtask-empty <?= $subsTotal > 0 ? 'd-none' : '' ?>">Sin subtareas todavía.</p>
                                        <div class="jt-subtask-add">
                                            <input type="text" class="form-control form-control-sm jt-subtask-input" placeholder="Nueva subtarea..." maxlength="255">
                                            <button type="button" class="btn btn-sm btn-primary jt-subtask-add-btn">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary jt-subtask-suggest-btn" title="Sugerir subtareas">
                                                <i class="bi bi-stars"></i>
                                            </button>
                                        </div>
                                    </div>

                                </li>

                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <input type="text" class="form-control new-task-input" placeholder="Nueva tarea..." data-category-id="<?= $catId ?>">
                <?php else: ?>
                    <!-- MODO PORTADAS -->
                    <div class="d-flex flex-column gap-2 mx-auto" style="max-width: 480px;">
                        <?php foreach ($catTasks as $task): ?>
                            <?php
                            $amplitude = (int)($task['amplitude'] ?? 0);
                            $completed = (int)($task['completed'] ?? 0);
                            $percentage = $amplitude > 0 ? min(100, round(($completed / $amplitude) * 100)) : 0;
                            $filled = (int)floor($percentage / 10);
                            ?>
                            <div class="card">

                                    <?php if (!empty($task['image'])): ?>
                                        <img src="<?= base_url($task['image']) ?>" class="card-img-top" alt="<?= esc($task['title']) ?>" style="height:150px; object-fit:cover;">
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
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>


            </div>
        </div>
    </div>
<?php endforeach; ?>
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
                onEnd: () => {
                    const orden = [...list.querySelectorAll('.jt-subtask-item')].map(item => item.dataset.id);
                    postJSON('<?= site_url('journal/subtasks/reordenar') ?>', { orden });
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
</script>

<?= $this->endSection() ?>