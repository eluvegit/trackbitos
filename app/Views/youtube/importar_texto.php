<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<h3>Importar por texto – <?= esc($lista['nombre']) ?></h3>
<form method="post" action="<?= site_url('youtube/'.$lista['slug'].'/importar-texto') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Pega una URL de YouTube por línea</label>
    <textarea name="texto" class="form-control" rows="10" placeholder="https://youtu.be/XXXXXX
https://www.youtube.com/watch?v=YYYYYY"></textarea>
  </div>
  <button class="btn btn-primary">Importar</button>
</form>
<?= $this->endSection() ?>
