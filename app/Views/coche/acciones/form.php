<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <h2><i class="bi bi-gear fs-4 me-2 text-primary"></i>Coche <span class="text-muted">/</span> <span class="text-muted">Acciones</span> <span class="text-muted">/</span> <span class="text-secondary"><?= isset($accion) ? 'Editar' : 'Nueva' ?> Acción</span></h2>
</div>

<a href="<?= site_url('coche/acciones') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver</a>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <form action="<?= site_url('coche/acciones/guardar') ?>" method="post">
                <?php if (isset($accion)): ?>
                    <input type="hidden" name="id" value="<?= esc($accion['id']) ?>">
                <?php endif; ?>

                <!-- Campo oculto de título -->
                <input type="hidden" name="title" id="hiddenTitle" value="<?= esc($accion['title'] ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label">Acción</label>
                    <select name="reminder_id" class="form-select" id="reminderSelect" required>
                        <option value="">-- Selecciona un acción --</option>
                        <?php foreach ($reminders as $r): ?>
                            <option value="<?= $r['id'] ?>" data-title="<?= esc($r['title']) ?>"
                                <?= isset($accion) && $accion['reminder_id'] == $r['id'] ? 'selected' : '' ?>>
                                <?= esc($r['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="date" class="form-control" value="<?= esc($accion['date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kilómetros</label>
                    <input type="number" name="kilometers" class="form-control" value="<?= esc($accion['kilometers'] ?? '') ?>">
                </div>

                

                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="3"><?= esc($accion['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-outline-primary">Guardar</button>
                <a href="<?= site_url('coche/acciones') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </form>

        </div>
    </div>
</div>

<script>
    const reminderSelect = document.getElementById('reminderSelect');
    const hiddenTitle = document.getElementById('hiddenTitle');

    function updateTitleFromReminder() {
        const selectedOption = reminderSelect.options[reminderSelect.selectedIndex];
        hiddenTitle.value = selectedOption.dataset.title || '';
    }

    reminderSelect.addEventListener('change', updateTitleFromReminder);
    window.addEventListener('DOMContentLoaded', updateTitleFromReminder);
</script>

<?= $this->endSection() ?>
