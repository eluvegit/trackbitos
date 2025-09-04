<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h1>Editar lista</h1>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= esc($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="<?= site_url('youtube/' . esc($lista['slug']) . '/editar') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label for="nombre" class="form-label">Nombre de la lista</label>
    <input type="text" name="nombre" id="nombre"
           class="form-control"
           value="<?= old('nombre', esc($lista['nombre'])) ?>" required>
  </div>
  <button type="submit" class="btn btn-primary">Guardar cambios</button>
  <a href="<?= site_url('youtube/' . esc($lista['slug'])) ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?= $this->endSection() ?>
