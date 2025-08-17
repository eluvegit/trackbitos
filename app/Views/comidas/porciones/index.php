<?= $this->extend('comidas/layout') ?>
<?= $this->section('content') ?>

<h1 class="h4 mb-3">Porciones habituales · <?= esc($alimento['nombre']) ?></h1>

<div class="mb-3">
  <a href="<?= site_url('comidas/alimentos') ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
  <a href="<?= site_url('comidas/porciones/create/'.$alimento['id']) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva porción</a>
</div>

<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach(session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<?php if (session('msg')): ?>
  <div class="alert alert-success"><?= esc(session('msg')) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>Descripción</th>
            <th class="text-end">Equivalencia (g)</th>
            <th class="text-end" style="width:160px">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="3" class="text-muted text-center p-4">No hay porciones registradas.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= esc($r['descripcion']) ?></td>
              <td><?= esc($r['descripcion']) ?></td>
              <td class="text-end"><?= esc(number_format($r['gramos_equivalentes'], 0)) ?></td>
              <td class="text-end">
                
                <a href="<?= site_url('comidas/porciones/delete/'.$r['id']) ?>" class="btn btn-sm"
                   onclick="return confirm('¿Eliminar esta porción?');">Eliminar</a>
                   <a href="<?= site_url('comidas/porciones/edit/'.$r['id']) ?>" class="btn btn-sm btn-secondary">Editar</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
