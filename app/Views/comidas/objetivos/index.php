<?php $this->extend('comidas/layout'); $this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Objetivos</h1>
  <a class="btn btn-primary" href="<?= site_url('comidas/objetivos/create') ?>">Nuevo</a>
</div>
<table class="table table-striped align-middle">
  <thead><tr><th>Nombre</th><th>Desde</th><th>Hasta</th><th></th></tr></thead>
  <tbody>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?= esc($r['nombre']) ?></td>
      <td><?= esc($r['fecha_inicio']) ?></td>
      <td><?= esc($r['fecha_fin'] ?? '-') ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= site_url('comidas/objetivos/edit/'.$r['id']) ?>">Editar</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php $this->endSection(); ?>
