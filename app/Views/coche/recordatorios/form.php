
<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4"><i class="bi bi-bell fs-4 me-2 text-primary"></i>Coche <span class="text-muted">/</span> <span class="text-muted">Recordatorios / </span> <span class="text-secondary"><?= isset($recordatorio) ? 'Editar' : 'Nuevo' ?> Recordatorio</span></h2>
<a href="<?= site_url('coche/recordatorios') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver</a>
<form action="<?= site_url('coche/recordatorios/guardar') ?>" method="post">
    <?php if (isset($recordatorio)): ?>
        <input type="hidden" name="id" value="<?= esc($recordatorio['id']) ?>">
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="title" class="form-control" value="<?= esc($recordatorio['title'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Intervalo (días)</label>
        <input type="number" name="interval_days" class="form-control" value="<?= esc($recordatorio['interval_days'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Intervalo (km)</label>
        <input type="number" name="interval_km" class="form-control" value="<?= esc($recordatorio['interval_km'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Notas</label>
        <textarea name="notes" class="form-control" rows="3"><?= esc($recordatorio['notes'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-outline-primary">Guardar</button>
    <a href="<?= site_url('coche/recordatorios') ?>" class="btn btn-outline-secondary">Cancelar</a>
</form>
<?= $this->endSection() ?>
