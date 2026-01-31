<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>

<div class="container mt-3">
    <h4>Editar control de <?= esc($alimento['nombre']) ?></h4>

    <form method="POST" action="<?= site_url('comidas/alimentos-control/edit/' . $control['id']) ?>">
        <div class="row g-2 mb-3">
            <div class="col">
                <label>Periodo (días)</label>
                <input type="number" name="periodo_dias" class="form-control" value="<?= esc($control['periodo_dias']) ?>" min="1" required>
            </div>
            <div class="col">
                <label>Mín. veces</label>
                <input type="number" name="min_veces" class="form-control" value="<?= esc($control['min_veces']) ?>" min="0" required>
            </div>
            <div class="col">
                <label>Máx. veces</label>
                <input type="number" name="max_veces" class="form-control" value="<?= esc($control['max_veces']) ?>" min="1" required>
            </div>
        </div>

        <div class="mb-3">
            <label>Unidad</label>
            <input type="text" name="unidad" class="form-control" value="<?= esc($control['unidad']) ?>">
        </div>

        <button class="btn btn-primary">Guardar cambios</button>
        <a href="<?= site_url('comidas/alimentos-control') ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?= $this->endSection(); ?>
