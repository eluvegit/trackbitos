<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
  <h1>Generar bloques</h1>
  <p>Este plan ya tiene bloques. ¿Quieres sobrescribirlos?</p>
  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/generar') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="plantilla" value="<?= esc($tplKey) ?>">
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="overwrite" name="overwrite" value="1">
      <label class="form-check-label" for="overwrite">Sí, sobrescribir los bloques existentes</label>
    </div>
    <button class="btn btn-danger">Generar</button>
    <a class="btn btn-outline-secondary" href="<?= site_url('gimnasio/mesociclos/'.$plan['id']) ?>">Cancelar</a>
  </form>
</div>
<?= $this->endSection() ?>
