<?= $this->extend('comidas/layout') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3">
  <?= isset($row) ? 'Editar porción' : 'Nueva porción' ?> ·
  <span class="text-muted"><?= esc($alimento['nombre']) ?></span>
</h1>

<div class="mb-3">
  <a href="<?= site_url('comidas/porciones/alimento/'.($alimento['id'] ?? $row['alimento_id'])) ?>" class="btn btn-light">
    <i class="bi bi-arrow-left"></i> Volver
  </a>
</div>

<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach(session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= isset($row) ? site_url('comidas/porciones/update/'.$row['id']) : site_url('comidas/porciones/store') ?>">
      <?= csrf_field() ?>
      <?php if (!isset($row)): ?>
        <input type="hidden" name="alimento_id" value="<?= esc($alimento['id']) ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Nombre de la porción</label>
        <input type="text" name="descripcion" class="form-control" maxlength="120" required
               value="<?= old('descripcion', $row['descripcion'] ?? '') ?>"
               placeholder="Ej. taza, rebanada, cucharada sopera...">
      </div>

      <div class="mb-3">
        <label class="form-label">Equivalencia en gramos</label>
        <input type="number" name="gramos_equivalentes" step="1" min="1" class="form-control"
               value="<?= old('gramos_equivalentes', $row['gramos_equivalentes'] ?? '') ?>"
               placeholder="Ej. 150">
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="predet" name="es_predeterminada" value="1"
               <?= old('es_predeterminada', $row['es_predeterminada'] ?? 0) ? 'checked':'' ?>>
        <label class="form-check-label" for="predet">Marcar como porción predeterminada</label>
      </div>

      <button class="btn btn-primary"><?= isset($row)?'Guardar cambios':'Crear porción' ?></button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
