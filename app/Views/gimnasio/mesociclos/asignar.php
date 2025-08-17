<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
  <h1>Asignar bloque a entrenamiento</h1>
  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$planId.'/asignar/'.$bloqueId) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Entrenamiento existente (ID)</label>
      <input type="number" name="entrenamiento_id" class="form-control" placeholder="Opcional">
    </div>
    <div class="mb-3">
      <label class="form-label">Fecha prevista</label>
      <input type="date" name="fecha_prevista" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Estado</label>
      <select name="estado" class="form-select">
        <option value="pendiente">Pendiente</option>
        <option value="hecho">Hecho</option>
        <option value="saltado">Saltado</option>
      </select>
    </div>
    <button class="btn btn-primary">Asignar</button>
  </form>
</div>
<?= $this->endSection() ?>
