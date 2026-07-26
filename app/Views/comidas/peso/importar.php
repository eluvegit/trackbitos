<?= $this->extend('comidas/layout') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3">📤 Importar CSV de báscula</h1>

<?php if (session('error')): ?>
  <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <p class="text-muted small">
      Sube el CSV exportado de tu báscula Tanita. Se leerán la fecha, el peso y el resto de
      métricas disponibles (IMC, % grasa, masa muscular, etc.). Si un día tiene varias pesadas,
      se usa la más cercana al mediodía. Si ya existe un registro para esa fecha, se actualiza
      en vez de duplicarse.
    </p>

    <form method="post" action="<?= site_url('comidas/peso/importar') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Archivo CSV</label>
        <input type="file" name="csv" accept=".csv" class="form-control" required>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary">Importar</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('comidas/peso') ?>">Volver</a>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
