<?php $this->extend('comidas/layout');
$this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Alimentos</h1>
  <a class="btn btn-primary" href="<?= site_url('comidas/alimentos/create') ?>">Nuevo</a>
</div>
<form class="row g-2 mb-3">
  <div class="col">
    <input class="form-control" name="q" value="<?= esc($q ?? '') ?>" placeholder="Buscar por nombre...">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary">Buscar</button>
  </div>
</form>
<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>Nombre</th>
      <th class="text-end">kcal/100g</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= esc($r['nombre']) ?></td>
        <td class="text-end"><?= esc($r['kcal']) ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary"
            href="<?= site_url('comidas/porciones/alimento/' . $r['id']) ?>">
            Porciones
          </a>
          <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('comidas/alimentos/edit/' . $r['id']) ?>">Editar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $this->endSection(); ?>