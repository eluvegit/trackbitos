<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4"><?= isset($supermercado) ? 'Editar' : 'Nuevo' ?> Supermercado</h2>

<a href="<?= site_url('compras/supermercados') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver</a>

<form method="post" action="<?= isset($supermercado) ? site_url('compras/supermercados/actualizar/' . $supermercado['id']) : site_url('compras/supermercados/nuevo') ?>">
    <?= csrf_field() ?>
    
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre del supermercado</label>
        <input type="text" class="form-control" name="nombre" id="nombre" required value="<?= esc($supermercado['nombre'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción (opcional)</label>
        <textarea class="form-control" name="descripcion" id="descripcion"><?= esc($supermercado['descripcion'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">
        <?= isset($supermercado) ? 'Guardar cambios' : 'Crear supermercado' ?>
    </button>
</form>


<?= $this->endSection() ?>
