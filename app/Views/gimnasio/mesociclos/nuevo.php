<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
  <h1>Nuevo mesociclo</h1>
  <div class="mb-4">
        <a href="<?= site_url('gimnasio/mesociclos') ?>" class="btn btn-outline-secondary">← Volver al gimnasio</a>
    </div>
  <form method="post" action="<?= site_url('gimnasio/mesociclos/nuevo') ?>" class="mt-3">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Nombre</label>
      <input type="text" name="nombre" class="form-control" value="Mesociclo Sentadilla">
    </div>
    <div class="mb-3">
      <label class="form-label">Ejercicio</label>
      <input type="text" name="ejercicio" class="form-control" value="sentadilla">
    </div>
    <div class="mb-3">
      <label class="form-label">e1RM base (kg)</label>
      <input type="number" step="0.5" name="e1rm_base" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Redondeo kg</label>
      <input type="number" step="0.5" name="redondeo_kg" class="form-control" value="2.5">
    </div>
    <button class="btn btn-primary">Crear</button>
  </form>
</div>
<?= $this->endSection() ?>
