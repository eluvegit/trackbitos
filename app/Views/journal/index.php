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
        padding: 0.2rem 0.35rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
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
        margin-top: 20px;
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 4px;
        display: grid;
        grid-template-columns: repeat(10, 1fr);
        gap: 2px;
        padding: 0 4px 2px 4px;
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
        /* separación desde el texto */
    }

    .task-title {
        flex-grow: 1;
        margin-right: 0.5rem;
        /* separación desde la estrella */
    }
</style>

<div class="container py-1">

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <h1 class="mt-0">Journal</h1>

        <div class="btn-group btn-group-sm">
            <?php
            $filterFocus = $filterFocus ?? 'todas';
            $filterPortadas = $filterPortadas ?? 'texto';

            $focusText = $filterFocus === 'focus' ? 'Ver todas' : 'Focus';
            $focusNext = $filterFocus === 'focus' ? 'todas' : 'focus';
            $focusClass = $filterFocus === 'focus' ? 'btn-primary' : 'btn-outline-primary';

            $portadasText = $filterPortadas === 'portadas' ? 'Texto' : 'Portadas';
            $portadasNext = $filterPortadas === 'portadas' ? 'texto' : 'portadas';
            $portadasClass = $filterPortadas === 'portadas' ? 'btn-primary' : 'btn-outline-primary';
            ?>

            <a href="<?= site_url('journal?filterFocus=' . $focusNext . '&filterPortadas=' . $filterPortadas) ?>"
                class="btn <?= $focusClass ?>"><?= $focusText ?></a>

            <a href="<?= site_url('journal?filterFocus=' . $filterFocus . '&filterPortadas=' . $portadasNext) ?>"
                class="btn <?= $portadasClass ?>"><?= $portadasText ?></a>
        </div>
    </div>

    <?php foreach ($categories as $category): ?>
        <?php
        $catId = $category['id'];
        $catName = $category['name'];
        $catColor = $category['color'] ?? '#000000';
        $catTasks = $tasksByCategory[$catName] ?? [];

        // Tiempo total de tareas actuales no completadas
        $totalCurrentMinutes = 0;
        foreach ($catTasks as $task) {
            $completed = (int)($task['completed'] ?? 0);
            $amplitude = (int)($task['amplitude'] ?? 0);
            $isCurrent = !empty($task['is_current']);
            if ($isCurrent && $completed < $amplitude) {
                $totalCurrentMinutes += (int)($task['time_spent'] ?? 0);
            }
        }
        $totalHours = number_format($totalCurrentMinutes / 60, 2);

        // Contar tareas completadas (las que tienen fecha de finalización)
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
                    <span class="small ms-2"><?= $totalHours ?> h</span>
                </div>

                <span class="badge bg-light text-dark">
                    <?= $completedCount ?> completada<?= $completedCount !== 1 ? 's' : '' ?>
                </span>
            </div>


            <div class="collapse" id="cat-<?= $catId ?>">
                <div class="card-body">

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
                                    <span class="text-muted small task-time-trigger"
                                        data-task-id="<?= $task['id'] ?>">
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

                    <input type="text"
                        class="form-control new-task-input"
                        placeholder="Nueva tarea..."
                        data-category-id="<?= $catId ?>">
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<!-- MODAL TIEMPO -->
<div class="modal fade" id="timeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir tiempo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="number" id="timeMinutes" class="form-control" placeholder="Minutos" min="1">
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
                if (!title) return;

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
                    .then(() => location.reload());
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
                    btn.querySelector('svg').setAttribute(
                        'fill',
                        data.is_current ? '#ffc107' : '#adb5bd'
                    );
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

        document.getElementById('saveTimeBtn').addEventListener('click', function(e) {
            e.preventDefault(); // <--- previene recarga
            const taskId = document.getElementById('timeTaskId').value;
            const minutes = parseInt(document.getElementById('timeMinutes').value);
            if (!minutes || minutes <= 0) return;

            fetch('<?= site_url('journal/add-time') ?>/' + taskId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        minutes
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error al guardar el tiempo');
                        return;
                    }

                    // Actualiza el tiempo directamente en la tarea
                    const li = document.querySelector('li.list-group-item a[href$="/' + taskId + '"]')
                        .closest('li');

                    li.querySelector('.text-muted.small').textContent = data.hours + ' h';
                    timeModal.hide();
                })
                .catch(err => console.error(err));
        });


    });
</script>

<?= $this->endSection() ?>