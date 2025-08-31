<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<h3>Importar por HTML – <?= esc($lista['nombre']) ?></h3>
<form method="post" action="<?= site_url('youtube/'.$lista['slug'].'/importar-html') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Pega el HTML de la página/lista</label>
    <textarea name="html" class="form-control" rows="12" placeholder="<html>..."></textarea>
  </div>
  <button class="btn btn-primary">Importar</button>
</form>
<h1>Instrucciones</h1>

<p class="text-muted small mt-2">
  El importador extrae enlaces tipo <code>youtube.com/watch?v=...</code> y <code>youtu.be/...</code>.
</p>
<?= $this->endSection() ?>
