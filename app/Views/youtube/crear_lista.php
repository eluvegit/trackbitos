<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="  mb-3">
  <h3 class="mb-0">Nueva lista</h3>
  <a href="<?= site_url('youtube') ?>" class="btn btn-sm btn-outline-secondary mt-3">← Volver</a>
</div>
<form method="post" action="<?= site_url('youtube/crear') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" class="form-control" name="nombre" required>
  </div>
  <button class="btn btn-success">Crear</button>
</form>
<?= $this->endSection() ?>
