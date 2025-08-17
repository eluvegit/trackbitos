<?php $this->extend('comidas/layout'); $this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Recetas</h1>
  <a class="btn btn-primary" href="<?= site_url('comidas/recetas/create') ?>">Nueva</a>
</div>
<table class="table table-striped align-middle">
  <thead><tr><th>Nombre</th><th class="text-end">Raciones</th><th></th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= esc($r['nombre']) ?></td>
        <td class="text-end"><?= esc($r['raciones']) ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('comidas/recetas/edit/'.$r['id']) ?>">Editar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $this->endSection(); ?>
