<?php $this->extend('comidas/layout'); $this->section('content'); ?>
<h1 class="h4 mb-3"><?= isset($row)?'Editar':'Nuevo' ?> alimento</h1>
<form method="post" action="<?= isset($row)?site_url('comidas/alimentos/update/'.$row['id']):site_url('comidas/alimentos/store') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nombre</label>
      <input class="form-control" name="nombre" value="<?= esc($row['nombre'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Marca</label>
      <input class="form-control" name="marca" value="<?= esc($row['marca'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Descripción</label>
      <textarea class="form-control" name="descripcion" rows="2"><?= esc($row['descripcion'] ?? '') ?></textarea>
    </div>
    <hr>
    <div class="col-md-3"><label class="form-label">kcal</label><input type="number" step="0.01" name="kcal" class="form-control" value="<?= esc($row['kcal'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Proteína (g)</label><input type="number" step="0.01" name="proteina_g" class="form-control" value="<?= esc($row['proteina_g'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Carbs (g)</label><input type="number" step="0.01" name="carbohidratos_g" class="form-control" value="<?= esc($row['carbohidratos_g'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Grasas (g)</label><input type="number" step="0.01" name="grasas_g" class="form-control" value="<?= esc($row['grasas_g'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Azúcares (g)</label><input type="number" step="0.01" name="azucares_g" class="form-control" value="<?= esc($row['azucares_g'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Fibra (g)</label><input type="number" step="0.01" name="fibra_g" class="form-control" value="<?= esc($row['fibra_g'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Saturadas (g)</label><input type="number" step="0.01" name="grasas_saturadas_g" class="form-control" value="<?= esc($row['grasas_saturadas_g'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Sodio (mg)</label><input type="number" step="0.01" name="sodio_mg" class="form-control" value="<?= esc($row['sodio_mg'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Omega3 (mg)</label><input type="number" step="0.01" name="omega3_mg" class="form-control" value="<?= esc($row['omega3_mg'] ?? 0) ?>"></div>
    <div class="col-md-3"><label class="form-label">Omega6 (mg)</label><input type="number" step="0.01" name="omega6_mg" class="form-control" value="<?= esc($row['omega6_mg'] ?? 0) ?>"></div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= site_url('comidas/alimentos') ?>">Volver</a>
  </div>
</form>
<?php $this->endSection(); ?>
