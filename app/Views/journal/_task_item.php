<?php
/**
 * Partial de una fila de tarea en modo listado. Se usa tanto dentro del
 * bucle de journal/_grid como en la respuesta AJAX de Journal::create, para
 * que una tarea recién creada salga con exactamente el mismo marcado (estrella,
 * botón de completar, subtareas, barra de progreso) que las demás sin recargar.
 *
 * Variables esperadas:
 * - $task: fila de la tarea (array)
 * - $subs: subtareas de la tarea (array, puede venir vacío)
 * - $lastActivity: fecha de última actividad de la tarea (string|null)
 */

$amplitude = (int) ($task['amplitude'] ?? 0);
$completed = (int) ($task['completed'] ?? 0);
$percentage = $amplitude > 0 ? min(100, round(($completed / $amplitude) * 100)) : 0;
$filled = (int) floor($percentage / 10);

$isDone = (!empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00');

$subs = $subs ?? [];
$subsTotal = count($subs);
$subsDone = count(array_filter($subs, fn($s) => !empty($s['is_done'])));
?>
<li class="list-group-item p-1 <?= $isDone ? 'opacity-50' : '' ?>" data-task-id="<?= $task['id'] ?>">

    <div class="d-flex align-items-center gap-2">

        <!-- Estrella -->
        <span class="current-star btn-toggle-current order-1"
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

        <!-- Subtareas -->
        <button type="button" class="jt-subtask-toggle order-3 order-lg-2 <?= $subsTotal > 0 ? 'has-subtasks' : '' ?>"
            data-bs-toggle="collapse" data-bs-target="#subtasks-<?= $task['id'] ?>"
            title="Subtareas">
            <i class="bi bi-list-check"></i>
            <?php if ($subsTotal > 0): ?>
                <span class="jt-subtask-count"><?= $subsDone ?>/<?= $subsTotal ?></span>
            <?php endif; ?>
        </button>

        <!-- Título + fecha -->
        <div class="d-flex align-items-center gap-1 flex-grow-1 order-2 order-lg-3">
            <a href="<?= site_url('journal/edit/' . $task['id']) ?>"
                class="text-decoration-none task-title-link <?= $isDone ? 'text-decoration-line-through' : '' ?>">
                <?= esc($task['title']) ?>
            </a>

            <!-- Fecha -->
            <span class="small text-muted">
                <?= time_ago($lastActivity ?? null) ?>
            </span>
        </div>

        <!-- Terminar / resumen -->
        <button type="button" class="jt-task-complete-btn js-task-complete order-4 <?= $isDone ? 'is-done' : '' ?>"
            data-task-id="<?= $task['id'] ?>"
            data-title="<?= esc($task['title'], 'attr') ?>"
            data-start="<?= !empty($task['start_time']) && $task['start_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['start_time'])) : '' ?>"
            data-end="<?= !empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['end_time'])) : '' ?>"
            data-time="<?= (int) ($task['time_spent'] ?? 0) ?>"
            data-note="<?= esc($task['note'] ?? '', 'attr') ?>"
            data-done="<?= $isDone ? '1' : '0' ?>"
            title="<?= $isDone ? 'Ver resumen / reabrir' : 'Marcar como terminada' ?>">
            <i class="bi <?= $isDone ? 'bi-check-circle-fill' : 'bi-check2-circle' ?>"></i>
        </button>

        <!-- Tiempo -->
        <span class="text-muted small ms-auto order-5 task-time-trigger"
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
        <div class="jt-subtask-filters">
            <button type="button" class="jt-subtask-filter-btn js-hide-done" title="Ocultar/mostrar subtareas hechas">
                <i class="bi bi-eye-slash"></i>
            </button>
            <button type="button" class="jt-subtask-filter-btn js-push-done" title="Empujar las hechas al final">
                <i class="bi bi-arrow-down-square"></i>
            </button>
        </div>
        <div class="jt-subtask-list" data-task-id="<?= $task['id'] ?>">
            <?php foreach ($subs as $s): ?>
                <?php $sDone = !empty($s['is_done']); ?>
                <div class="jt-subtask-item <?= $sDone ? 'is-done' : '' ?>" data-id="<?= (int) $s['id'] ?>">
                    <span class="jt-subtask-handle" title="Arrastrar para reordenar">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
                    <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha">
                        <i class="bi <?= $sDone ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                    </button>
                    <span class="jt-subtask-title"><?= esc($s['title']) ?></span>
                    <div class="jt-subtask-actions">
                        <span class="jt-subtask-time subtask-time-trigger"
                            data-subtask-id="<?= (int) $s['id'] ?>"
                            data-task-id="<?= $task['id'] ?>">
                            <?= number_format(($s['time_spent'] ?? 0) / 60, 2) ?> h
                        </span>
                        <button type="button" class="jt-subtask-move js-move-top-subtask" title="Mover al principio" aria-label="Mover al principio">
                            <i class="bi bi-arrow-bar-up"></i>
                        </button>
                        <button type="button" class="jt-subtask-move js-move-bottom-subtask" title="Mover al final" aria-label="Mover al final">
                            <i class="bi bi-arrow-bar-down"></i>
                        </button>
                        <button type="button" class="jt-subtask-edit js-edit-subtask" title="Renombrar subtarea" aria-label="Renombrar subtarea">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="jt-subtask-delete js-delete-subtask" title="Eliminar subtarea" aria-label="Eliminar subtarea">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
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
