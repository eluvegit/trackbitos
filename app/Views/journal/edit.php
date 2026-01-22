<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<style>
    /* Placeholder más pequeño dentro del input */
    .form-floating input::placeholder,
    .form-floating textarea::placeholder {
        font-size: 0.6rem;
        /* ajustar tamaño a tu gusto */
    }

    /* Etiqueta flotante más pequeña */
    .form-floating>label {
        font-size: 0.65rem;
        /* tamaño de la etiqueta cuando flota */
    }
</style>
<div class="container py-2">
    <h1>Editar</h1>

    <?php if (!empty($task['image'])): ?>
        <div class="mb-3 d-flex align-items-center">
            <!-- Imagen -->
            <img
                src="<?= base_url($task['image']) ?>"
                style="max-width: 75px; border-radius: 6px; border: 1px solid #ddd; margin-right: 10px;"
                alt="Imagen actual">

            <!-- Botón de borrar -->
            <form
                action="<?= site_url('journal/delete-image/' . $task['id']) ?>"
                method="post"
                onsubmit="return confirm('¿Seguro que deseas eliminar esta imagen?');"
                class="mb-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    <?php endif; ?>


    <form action="<?= site_url('journal/edit/' . $task['id']) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Título -->
        <div class="form-floating mb-2">
            <input
                type="text"
                name="title"
                id="title"
                class="form-control"
                value="<?= esc($task['title'] ?? '') ?>"
                placeholder="Título"
                required>
            <label for="title">Título</label>
        </div>

        <div class="row g-2 mb-2">
            <div class="col-4 d-flex align-items-center">
                <div class="form-check mb-0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        value="1"
                        id="is_current"
                        name="is_current"
                        <?= !empty($task['is_current']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_current">
                        Actual
                    </label>
                </div>
            </div>

            <div class="col-8">
                <div class="form-floating mb-0">
                    <input
                        type="date"
                        name="start_time"
                        id="start_time"
                        class="form-control"
                        value="<?= !empty($task['start_time']) ? date('Y-m-d', strtotime($task['start_time'])) : '' ?>"
                        placeholder="Inicio">
                    <label for="start_time">Inicio</label>
                </div>
            </div>

        </div>


        <!-- Tres columnas: Tiempo / Completados / Amplitud -->
        <div class="row g-2 mb-2">
            <div class="col-4">
                <div class="form-floating">
                    <input
                        type="number"
                        name="time_spent"
                        id="time_spent"
                        class="form-control"
                        value="<?= esc($task['time_spent'] ?? '') ?>"
                        placeholder="Tiempo">
                    <label for="time_spent">Tiempo (min)</label>
                </div>
            </div>

            <div class="col-4">
                <div class="form-floating">
                    <input
                        type="number"
                        name="completed"
                        id="completed"
                        class="form-control"
                        value="<?= esc($task['completed'] ?? '') ?>"
                        min="0" max="<?= esc($task['amplitude'] ?? 0) ?>"
                        placeholder="Completados">
                    <label for="completed">Completados</label>
                </div>
            </div>

            <div class="col-4">
                <div class="form-floating">
                    <input
                        type="number"
                        name="amplitude"
                        id="amplitude"
                        class="form-control"
                        value="<?= esc($task['amplitude'] ?? '') ?>"
                        min="1" required
                        placeholder="Amplitud">
                    <label for="amplitude">Amplitud</label>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="d-flex justify-content-end mb-2">
            <a href="<?= site_url('journal') ?>" class="btn btn-light me-2">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>


        <!-- Nota -->
        <div class="form-floating mb-2">
            <textarea
                name="note"
                id="note"
                class="form-control"
                placeholder="Nota"
                style="height: 70px"><?= esc($task['note'] ?? '') ?></textarea>
            <label for="note">Nota</label>
        </div>

        <!-- Fin -->
        <div class="form-floating mb-2">
            <input
                type="date"
                name="end_time"
                id="end_time"
                class="form-control"
                value="<?= !empty($task['end_time']) ? date('Y-m-d', strtotime($task['end_time'])) : '' ?>"
                placeholder="Fin">
            <label for="end_time">Fin</label>
        </div>


        <!-- Imagen opcional -->
        <div class="mb-2">
            <label for="image" class="form-label">Imagen opcional</label>
            <input type="file" name="image" id="image" class="form-control">
        </div>


    </form>
</div>

<h5>Historial de fechas</h5>
<div id="calendar" class="d-flex flex-wrap"></div>

<script>
fetch('<?= site_url('journal/get-logs/' . $task['id']) ?>')
    .then(res => res.json())
    .then(logs => {
        const calendar = document.getElementById('calendar');
        calendar.innerHTML = ''; // Limpiar antes de añadir

        logs.forEach(l => {
            const span = document.createElement('span');

            // Formatear fecha: yyyy-mm-dd → dd/mm
            const parts = l.log_date.split('-');
            const formattedDate = parts[2] + '/' + parts[1];

            // Mostrar fecha + minutos
            span.textContent = `${formattedDate} - ${l.minutes ?? 0} min`;

            span.className = 'badge bg-secondary me-1 mb-1';
            calendar.appendChild(span);
        });
    });
</script>



<?= $this->endSection() ?>