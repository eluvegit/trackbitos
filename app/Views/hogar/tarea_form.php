<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">
    <i class="bi bi-list-check text-primary"></i>
    Editar tarea
</h5>

<a href="<?= site_url('hogar/' . $tarea['habitacion_id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>
<a href="<?= site_url('hogar/tareas/' . $tarea['id'] . '/historial') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-clock-history"></i> Ver historial
</a>

<form method="post" action="<?= site_url('hogar/tareas/actualizar/' . $tarea['id']) ?>" style="max-width: 420px;">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre de la tarea</label>
        <input type="text" class="form-control" name="nombre" id="nombre" required
               value="<?= esc($tarea['nombre']) ?>">
    </div>

    <div class="mb-3">
        <label for="frecuencia_dias" class="form-label">Frecuencia orientativa (días)</label>
        <input type="number" min="1" class="form-control" name="frecuencia_dias" id="frecuencia_dias"
               value="<?= esc($tarea['frecuencia_dias'] ?? '') ?>" placeholder="Ej. 7">
        <div class="form-text">Déjalo vacío si no quieres aviso de "atrasada" para esta tarea.</div>
    </div>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>

<form method="post" action="<?= site_url('hogar/tareas/borrar/' . $tarea['id']) ?>" class="mt-3"
      onsubmit="return confirm('¿Eliminar esta tarea?')">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-trash"></i> Eliminar tarea
    </button>
</form>

<?= $this->endSection() ?>
