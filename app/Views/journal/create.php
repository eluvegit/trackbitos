<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container py-3">

    <h2 class="mb-3">Nuevo registro</h2>

    <form method="post">

        <div class="form-group">
            <label>Categoría</label>
            <select name="category" class="form-control" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Título</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Nota</label>
            <textarea name="note" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group col">
                <label>Fecha</label>
                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group col">
                <label>Tiempo (min)</label>
                <input type="number" name="time_spent" class="form-control" value="0">
            </div>

            <div class="form-group col">
                <label>Progreso (%)</label>
                <input type="number" name="progress" class="form-control" value="0" step="0.1">
            </div>
        </div>

        <button class="btn btn-primary">Guardar</button>
        <a href="<?= site_url('journal') ?>" class="btn btn-secondary">Cancelar</a>

    </form>

</div>

<?= $this->endSection() ?>
