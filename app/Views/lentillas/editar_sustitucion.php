<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-arrow-repeat fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Lentillas <span class="text-muted">/</span> <span class="text-secondary">Cambios y revisiones</span>  <span class="text-muted">/</span>  <span class="text-secondary">Editar</span></h2>
</div>

<a href="<?= site_url('lentillas/sustituciones') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver</a>

<form method="post" action="<?= site_url('lentillas/sustituciones/actualizar/' . $sustitucion['id']) ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="elemento" class="form-label">Elemento</label>
        <select name="elemento" class="form-select" required>
            <option value="lentilla izquierda" <?= $sustitucion['elemento'] === 'lentilla izquierda' ? 'selected' : '' ?>>Lentilla izquierda</option>
            <option value="lentilla derecha" <?= $sustitucion['elemento'] === 'lentilla derecha' ? 'selected' : '' ?>>Lentilla derecha</option>
            <option value="lentillas" <?= $sustitucion['elemento'] === 'lentillas' ? 'selected' : '' ?>>Lentillas (ambas)</option>
            <option value="estuche" <?= $sustitucion['elemento'] === 'estuche' ? 'selected' : '' ?>>Estuche</option>
            <option value="líquido" <?= $sustitucion['elemento'] === 'líquido' ? 'selected' : '' ?>>Líquido</option>
            <option value="presion" <?= $sustitucion['elemento'] === 'presion' ? 'selected' : '' ?>>Presión de ojo</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" name="fecha" value="<?= $sustitucion['fecha'] ?>" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="notas" class="form-label">Notas</label>
        <input type="text" name="notas" value="<?= esc($sustitucion['notas']) ?>" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>

<?= $this->endSection() ?>
