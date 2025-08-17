<?php $this->extend('comidas/layout'); $this->section('content'); ?>
<h1 class="h4 mb-3"><?= isset($row)?'Editar':'Nuevo' ?> objetivo</h1>
<form method="post" action="<?= isset($row)?site_url('comidas/objetivos/update/'.$row['id']):site_url('comidas/objetivos/store') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nombre</label>
      <input class="form-control" name="nombre" value="<?= esc($row['nombre'] ?? '') ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Fecha inicio</label>
      <input class="form-control" type="date" name="fecha_inicio" value="<?= esc($row['fecha_inicio'] ?? '') ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Fecha fin</label>
      <input class="form-control" type="date" name="fecha_fin" value="<?= esc($row['fecha_fin'] ?? '') ?>">
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= site_url('comidas/objetivos') ?>">Volver</a>
  </div>
</form>
<?php $this->endSection(); ?>
