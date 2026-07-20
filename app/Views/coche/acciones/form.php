<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-gear text-primary"></i>
    <a href="<?= site_url('coche') ?>" class="text-decoration-none text-muted fw-normal">Coche</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('coche/acciones') ?>" class="text-decoration-none text-muted fw-normal">Acciones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= isset($accion) ? 'Editar' : 'Nueva' ?> acción</strong>
</h5>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="<?= site_url('coche/acciones/guardar') ?>" method="post">
                    <?= csrf_field() ?>
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

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
                        <a href="<?= site_url('coche/acciones') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
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
