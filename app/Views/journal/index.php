<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<style>
    /* Tarjeta de categoría más compacta */
    .card {
        margin-bottom: 0.25rem;
        /* menos espacio entre categorías */
        font-size: 0.8rem;
        /* texto más pequeño */
    }

    /* Cabecera de categoría */
    .card-header {
        padding: 0.25rem 0.35rem;
        /* casi sin padding lateral */
        font-size: 0.85rem;
        cursor: pointer;
        color: #fff;
    }

    /* Cuerpo de la categoría */
    .card-body {
        padding: 0.25rem 0.35rem;
    }

    /* Listado de tareas */
    .list-group-item {
        padding: 0.2rem 0.35rem;
        font-size: 0.8rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Input de nueva tarea */
    .new-task-input {
        padding: 0.2rem 0.35rem;
        font-size: 0.8rem;
        margin-top: 0.2rem;
    }

    /* Encabezado principal */
    h1 {
        font-size: 1.2rem;

    }

    .container {
        padding: 0.2rem;
        margin: 0;
    }
</style>


<div class="container py-1">

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <h1 class="mt-0">Journal</h1>

        <div class="btn-group" role="group" aria-label="Filtros Journal">
            <?php
            // Filtros desde GET o valores por defecto
            $filterFocus = $filterFocus ?? 'todas';        // por defecto 'todas'
            $filterPortadas = $filterPortadas ?? 'texto';  // por defecto 'texto'

            // Texto de los botones y siguiente estado
            $focusText = $filterFocus === 'focus' ? 'Todas' : 'Focus';
            $focusNext = $filterFocus === 'focus' ? 'todas' : 'focus';
            $focusClass = $filterFocus === 'focus' ? 'btn-primary' : 'btn-outline-primary';

            $portadasText = $filterPortadas === 'portadas' ? 'Texto' : 'Portadas';
            $portadasNext = $filterPortadas === 'portadas' ? 'texto' : 'portadas';
            $portadasClass = $filterPortadas === 'portadas' ? 'btn-primary' : 'btn-outline-primary';
            ?>

            <a href="<?= site_url('journal?filterFocus=' . $focusNext . '&filterPortadas=' . $filterPortadas) ?>"
                class="btn <?= $focusClass ?>">
                <?= $focusText ?>
            </a>

            <a href="<?= site_url('journal?filterFocus=' . $filterFocus . '&filterPortadas=' . $portadasNext) ?>"
                class="btn <?= $portadasClass ?>">
                <?= $portadasText ?>
            </a>
        </div>
    </div>



    <!-- Listado de categorías y tareas -->
    <?php foreach ($categories as $category): ?>
        <?php
        $catId = $category['id'];
        $catName = $category['name'];
        $catColor = $category['color'] ?? '#000000';
        $catTasks = $tasksByCategory[$catName] ?? [];
        ?>
        <div class="card mb-1">
            <!-- Cabecera clicable -->
            <div class="card-header" data-bs-toggle="collapse" href="#cat-<?= $catId ?>" style="cursor:pointer; background-color: <?= esc($catColor) ?>; color: #fff;">
                <strong><?= esc($catName) ?></strong>
            </div>

            <!-- Bloque colapsable -->
            <div class="collapse" id="cat-<?= $catId ?>">
                <div class="card-body">

                    <!-- Listado de tareas -->
                    <ul class="list-group mb-2" id="task-list-<?= $catId ?>">
                        <?php if (empty($catTasks)): ?>
                            <li class="list-group-item text-muted">No hay tareas aún.</li>
                        <?php else: ?>
                            <?php foreach ($catTasks as $task): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center"
                                    style="border-left: <?= !empty($task['is_current']) ? '15px' : '5px' ?> solid <?= esc($task['color']) ?>;">
                                    <span><?= esc($task['title']) ?></span>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-delete-task" data-task-id="<?= $task['id'] ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-trash" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 5h4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0V6H6v6.5a.5.5 0 0 1-1 0v-7z" />
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 1 1 0-2H5V1.5A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5V2h2.5a1 1 0 0 1 1 1zM6 2v-.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V2H6z" />
                                            </svg>
                                        </button>
                                        <button class="btn btn-sm btn-edit-task" data-task-id="<?= $task['id'] ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-pencil" viewBox="0 0 16 16">
                                                <path d="M12.146.854a.5.5 0 0 1 .708 0l2.292 2.292a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2L3 10.207V13h2.793L14 4.793 11.207 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </li>

                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>


                    <!-- Caja para añadir nueva tarea -->
                    <input type="text" class="form-control new-task-input" placeholder="Nueva tarea..." data-category-id="<?= $catId ?>" data-category-name="<?= esc($catName) ?>">
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // --- Añadir nueva tarea al pulsar Enter ---
        document.querySelectorAll('.new-task-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
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
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const list = document.getElementById('task-list-' + categoryId);

                                // Quitar mensaje "No hay tareas aún"
                                const emptyItem = list.querySelector('.text-muted');
                                if (emptyItem) emptyItem.remove();

                                // Crear el <li> con botones SVG de borrar y editar
                                const li = document.createElement('li');
                                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                                li.style.borderLeft = '5px solid ' + (data.color || '#000000');

                                li.innerHTML = `
                            <span>${title}</span>
                            <div class="d-flex gap-1">
                                <!-- Botón Borrar -->
                                <button class="btn btn-sm btn-delete-task" data-task-id="${data.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 5h4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0V6H6v6.5a.5.5 0 0 1-1 0v-7z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 1 1 0-2H5V1.5A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5V2h2.5a1 1 0 0 1 1 1zM6 2v-.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V2H6z"/>
                                    </svg>
                                </button>
                                <!-- Botón Editar -->
                                <button class="btn btn-sm btn-edit-task" data-task-id="${data.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#adb5bd" class="bi bi-pencil" viewBox="0 0 16 16">
                                        <path d="M12.146.854a.5.5 0 0 1 .708 0l2.292 2.292a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2L3 10.207V13h2.793L14 4.793 11.207 2z"/>
                                    </svg>
                                </button>
                            </div>
                        `;

                                list.appendChild(li);

                                this.value = '';

                                // Asignar eventos a los nuevos botones
                                li.querySelector('.btn-delete-task').addEventListener('click', deleteTaskHandler);
                                li.querySelector('.btn-edit-task').addEventListener('click', editTaskHandler); // Define editTaskHandler
                            } else {
                                alert('Error al crear la tarea');
                            }
                        })
                        .catch(err => console.error(err));
                }
            });
        });


        // --- Función para borrar tarea ---
        function deleteTaskHandler() {
            const taskId = this.dataset.taskId;
            if (!taskId) return;
            if (!confirm('¿Seguro que quieres borrar esta tarea?')) return;

            fetch('<?= site_url('journal/delete') ?>/' + taskId, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.closest('li').remove();
                    } else {
                        alert('Error al borrar la tarea');
                    }
                })
                .catch(err => console.error(err));
        }

        // --- Asignar evento a todos los botones de borrar existentes ---
        document.querySelectorAll('.btn-delete-task').forEach(btn => {
            btn.addEventListener('click', deleteTaskHandler);
        });

    });

    // --- Función para editar tarea ---
    function editTaskHandler() {
        const taskId = this.dataset.taskId;
        if (!taskId) return;

        // Redirigir a la vista de edición
        window.location.href = '<?= site_url('journal/edit') ?>/' + taskId;
    }

    // Asignar evento a todos los botones de editar existentes
    document.querySelectorAll('.btn-edit-task').forEach(btn => {
        btn.addEventListener('click', editTaskHandler);
    });
</script>

<?= $this->endSection() ?>