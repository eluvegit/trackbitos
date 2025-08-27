<?php $this->extend('comidas/layout');
$this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Recetas</h1>
  <a class="btn btn-primary" href="<?= site_url('comidas/recetas/create') ?>">Nueva</a>
</div>

<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>Nombre</th>
      <th class="text-end">Raciones</th>
      <th class="text-end">Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= esc($r['nombre']) ?></td>
        <td class="text-end"><?= esc($r['raciones']) ?></td>
        <?= /* acciones con iconos */ '' ?>
        <td class="text-end">
          <div class="d-inline-flex gap-1">

            <form action="<?= site_url('comidas/recetas/delete/' . $r['id']) ?>"
              method="post" class="d-inline"
              onsubmit="return confirm('¿Eliminar esta receta y su alimento virtual asociado?');">
              <?= csrf_field() ?>
              <button type="submit"
                class="btn btn-sm btn-outline-danger"
                title="Eliminar" aria-label="Eliminar">
                <i class="bi bi-trash"></i>
                <span class="visually-hidden">Eliminar</span>
              </button>
            </form>

            <a class="btn btn-sm btn-outline-secondary"
              href="<?= site_url('comidas/recetas/edit/' . $r['id']) ?>"
              title="Editar" aria-label="Editar">
              <i class="bi bi-pencil"></i>
              <span class="visually-hidden">Editar</span>
            </a>

          </div>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $this->endSection(); ?>