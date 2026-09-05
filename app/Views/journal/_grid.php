<?php
/**
 * Rejilla de categorías del Journal (las "cards" con sus tareas). Vive en su
 * propio partial porque se renderiza en dos sitios:
 * - journal/index, dentro del layout completo (primera carga / sin JS).
 * - Journal::grid, como fragmento AJAX que sustituye #journalGrid al cambiar
 *   de filtro/vista sin recargar la página.
 *
 * Variables esperadas: $categories, $tasksByCategory, $totalTimeByCategory,
 * $lastCategoryActivity, $lastTaskActivity, $progressByCategory,
 * $subtasksByTask, $view_mode.
 */
?>
<?php foreach ($categories as $category): ?>
    <?php
    $catId = $category['id'];
    $catName = $category['name'];
    $catColor = $category['color'] ?? '#000000';
    $catTasks = $tasksByCategory[$catName] ?? [];

    $totalHours = number_format(($totalTimeByCategory[$catName] ?? 0) / 60, 2);

    $progress = $progressByCategory[$catName] ?? ['total' => 0, 'current' => 0, 'completed' => 0, 'currentPerc' => 0, 'completedPerc' => 0, 'remainingPerc' => 100];
    ?>
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center p-0"
            data-bs-toggle="collapse" href="#cat-<?= $catId ?>"
            data-cat-id="<?= $catId ?>">

            <!-- Fondo título -->
            <div style="background-color: <?= esc($catColor) ?>; padding: 0.25rem 0.5rem; flex-grow:1; display:flex; align-items:center; gap:0.5rem;">

                <!-- Izquierda -->
                <div class="d-flex align-items-center gap-2">
                    <span class="cat-progress-counts" style="display:inline-block; width:35px; text-align:right;">
                        <?= $progress['completed'] ?>/<?= max(1, $progress['total']) ?>
                    </span>

                    <strong><?= esc($catName) ?></strong>
                    <span class="small text-muted">
                        <strong><?= time_ago($lastCategoryActivity[$catName] ?? null) ?></strong>
                    </span>

                </div>

                <!-- Derecha -->
                <span class="small ms-auto cat-total-hours"><?= $totalHours ?> h</span>
            </div>

            <div class="cat-progress-bar" style="position: relative; display:flex; width:50px; height:16px; margin-left:0.5rem; border-radius:4px; overflow:hidden; border:1px solid #ccc; cursor:pointer;"
                title="Actuales: <?= $progress['current'] ?>, Completadas: <?= $progress['completed'] ?>, Total: <?= $progress['total'] ?>">

                <div class="cat-progress-current" style="width:<?= $progress['currentPerc'] ?>%; background-color:#ffc107;"></div>
                <div class="cat-progress-completed" style="width:<?= $progress['completedPerc'] ?>%; background-color:#198754;"></div>
                <div class="cat-progress-remaining" style="width:<?= $progress['remainingPerc'] ?>%; background-color:#e9ecef;"></div>
            </div>
        </div>

        <div class="collapse" id="cat-<?= $catId ?>">
            <div class="card-body">
                <?php
                usort($catTasks, function ($a, $b) {
                    $aDone = (!empty($a['end_time']) && $a['end_time'] !== '0000-00-00 00:00:00');
                    $bDone = (!empty($b['end_time']) && $b['end_time'] !== '0000-00-00 00:00:00');
                    $aCurrent = !empty($a['is_current']);
                    $bCurrent = !empty($b['is_current']);

                    if ($aDone !== $bDone) return $aDone ? 1 : -1;
                    if ($aCurrent !== $bCurrent) return $aCurrent ? -1 : 1;

                    $aAmp = (int) ($a['amplitude'] ?? 0);
                    $bAmp = (int) ($b['amplitude'] ?? 0);
                    $aProg = $aAmp > 0 ? (int) ($a['completed'] ?? 0) / $aAmp : 0;
                    $bProg = $bAmp > 0 ? (int) ($b['completed'] ?? 0) / $bAmp : 0;

                    if ($aProg !== $bProg) {
                        return ($aProg > $bProg) ? -1 : 1;
                    }

                    $aTime = (int) ($a['time_spent'] ?? 0);
                    $bTime = (int) ($b['time_spent'] ?? 0);

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
                                <?= view('journal/_task_item', [
                                    'task'         => $task,
                                    'subs'         => $subtasksByTask[$task['id']] ?? [],
                                    'lastActivity' => $lastTaskActivity[$task['id']] ?? null,
                                ]) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <input type="text" class="form-control new-task-input" placeholder="Nueva tarea..." data-category-id="<?= $catId ?>">
                <?php else: ?>
                    <!-- MODO PORTADAS -->
                    <div class="d-flex flex-column gap-2 mx-auto" style="max-width: 480px;">
                        <?php foreach ($catTasks as $task): ?>
                            <?php
                            $amplitude = (int) ($task['amplitude'] ?? 0);
                            $completed = (int) ($task['completed'] ?? 0);
                            $percentage = $amplitude > 0 ? min(100, round(($completed / $amplitude) * 100)) : 0;
                            $filled = (int) floor($percentage / 10);
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

                                    <span class="small text-muted">
                                        <?= time_ago($lastTaskActivity[$task['id']] ?? null) ?>
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
