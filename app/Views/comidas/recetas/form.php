<?php $this->extend('comidas/layout'); $this->section('content'); ?>
<h1 class="h4 mb-3"><?= isset($row)?'Editar':'Nueva' ?> receta</h1>
<form method="post" action="<?= isset($row)?site_url('comidas/recetas/update/'.$row['id']):site_url('comidas/recetas/store') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Nombre</label>
      <input class="form-control" name="nombre" value="<?= esc($row['nombre'] ?? '') ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label">Descripción</label>
      <textarea class="form-control" name="descripcion" rows="2"><?= esc($row['descripcion'] ?? '') ?></textarea>
    </div>
  </div>
  <hr>
  <h2 class="h5">Ingredientes</h2>
  <div class="row g-2 align-items-end">
    <div class="col-md-6">
      <label class="form-label">Alimento</label>
      <select class="form-select" name="alimento_id">
        <?php foreach(($alimentos ?? []) as $a): ?>
          <option value="<?= $a['id'] ?>"><?= esc($a['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Gramos</label>
      <input type="number" step="0.1" class="form-control" name="gramos">
    </div>
    <div class="col-md-3">
      <button name="action" value="add_ingrediente" class="btn btn-outline-primary">Añadir</button>
    </div>
  </div>
  <?php if(!empty($ingredientes)): ?>
    <div class="table-responsive mt-3">
      <table class="table table-sm table-bordered">
        <thead><tr><th>Alimento</th><th class="text-end">Gramos</th><th></th></tr></thead>
        <tbody>
        <?php foreach($ingredientes as $ing): ?>
          <tr>
            <td><?= esc($ing['alimento_nombre']) ?></td>
            <td class="text-end"><?= esc($ing['gramos']) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-danger" href="<?= site_url('comidas/recetas/removeIngrediente/'.$ing['id']) ?>">Eliminar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <div class="mt-3">
    <button class="btn btn-primary">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= site_url('comidas/recetas') ?>">Volver</a>
  </div>
</form>
<?php $this->endSection(); ?>
