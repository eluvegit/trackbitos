<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">✏️ Editar producto</h2>

<a href="<?= site_url('compras/productos/' . $producto['supermercado_id']) ?>" class="btn btn-outline-secondary mb-4">← Volver a productos</a>

<div class="card">
    <div class="card-body">
        <form action="<?= site_url('compras/productos/' . $producto['id'] . '/actualizar') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del producto</label>
                <input type="text" name="nombre" id="nombre" value="<?= esc($producto['nombre']) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="imagen" class="form-label">URL de imagen</label>
                <input type="url" name="imagen" id="imagen" value="<?= esc($producto['imagen']) ?>" class="form-control">
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
