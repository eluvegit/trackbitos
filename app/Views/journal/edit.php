<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container py-3">

    <h2>Editar registro</h2>

    <form method="post">
        <div class="form-group">
            <label>Fecha</label>
            <input type="date" class="form-control" name="date" value="<?= esc($log->date) ?>">
        </div>

        <div class="form-group">
            <label>Tiempo invertido (min)</label>
            <input type="number" class="form-control" name="time_spent" value="<?= esc($log->time_spent) ?>">
        </div>

        <div class="form-group">
            <label>Progreso (%)</label>
            <input type="number" class="form-control" name="progress" value="<?= esc($log->progress) ?>">
        </div>

        <div class="form-group">
            <label>Nota</label>
            <textarea class="form-control" name="note"><?= esc($log->note) ?></textarea>
        </div>

        <!-- Imagen: pendiente de implementación -->
        <div class="form-group">
            <label>Imagen</label>
            <?php if($log->image): ?>
                <img src="<?= base_url($log->image) ?>" class="img-fluid mb-2" alt="Imagen registro">
            <?php endif; ?>
            <input type="file" class="form-control-file" name="image">
        </div>

        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="<?= site_url('journal') ?>" class="btn btn-secondary">Cancelar</a>
    </form>

</div>

<?= $this->endSection() ?>
