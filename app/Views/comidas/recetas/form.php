<?php $this->extend('comidas/layout');
$this->section('content'); ?>
<h1 class="h4 mb-3"><?= isset($row) ? 'Editar' : 'Nueva' ?> receta</h1>

<form method="post" action="<?= isset($row) ? site_url('comidas/recetas/update/' . $row['id']) : site_url('comidas/recetas/store') ?>">
  <?= csrf_field() ?>

  <style>
    /* Texto de descripción en 2 líneas con puntos suspensivos (mobile-friendly) */
    .clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .touch-target {
      padding: .5rem 0;
    }

    /* área táctil cómoda */
  </style>

  <!-- Resumen compacto + toggle de edición -->
  <div class="card border-0 mb-2">
    <button type="button"
      class="btn text-start w-100 p-0 touch-target"
      data-bs-toggle="collapse"
      data-bs-target="#metaCollapse"
      aria-expanded="false"
      aria-controls="metaCollapse">
      <div class="d-flex align-items-start justify-content-between gap-2">
        <div class="flex-grow-1">
          <div class="fw-semibold text-body">
            <?= esc(($row['nombre'] ?? '') !== '' ? $row['nombre'] : '— Toca para poner nombre —') ?>
          </div>
          <div class="text-muted small clamp-2">
            <?= esc(($row['descripcion'] ?? '') !== '' ? $row['descripcion'] : '— Toca para añadir una breve descripción —') ?>
          </div>
        </div>
        <div class="text-nowrap ms-2">
          <span class="badge rounded-pill text-bg-light d-inline-flex align-items-center">
            <i class="bi bi-pencil me-1"></i> Editar
          </span>
        </div>
      </div>
    </button>

    <!-- Formulario oculto que se despliega al tocar -->
    <div id="metaCollapse" class="collapse mt-2">
      <div class="row g-2">
        <div class="col-12">
          <label class="form-label mb-1">Nombre</label>
          <input class="form-control" name="nombre" value="<?= esc($row['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label mb-1">Descripción</label>
          <textarea class="form-control" name="descripcion" rows="2"><?= esc($row['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-primary">Guardar</button>
          <a class="btn btn-outline-secondary" href="<?= site_url('comidas/recetas') ?>">Volver</a>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-3">

  <h2 class="h5">Ingredientes</h2>
  <?php $suma = 0; ?>
  <div class="row g-2 align-items-end">
    <div class="col-md-6">
      <div class="form-floating">
        <input class="form-control" list="alimentos-list" name="alimento_nombre" id="alimento_nombre" placeholder="Alimento">
        <label for="alimento_nombre">Alimento</label>
        <datalist id="alimentos-list">
          <?php foreach (($alimentos ?? []) as $a): ?>
            <option value="<?= esc($a['nombre']) ?>" data-id="<?= $a['id'] ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <input type="hidden" name="alimento_id" id="alimento_id">
    </div>

    <!-- Gramos y botones en la misma fila -->
    <div class="col-md-6">
      <div class="row g-2">
        <div class="col-4">
          <div class="form-floating">
            <input type="number" step="0.1" class="form-control" name="gramos" id="gramos" placeholder="Gramos">
            <label for="gramos">Gramos</label>
          </div>
        </div>
        <div class="col-4 d-grid">
          <!-- Botón Clear -->
          <button type="button" id="btn-clear-alimento" class="btn btn-outline-secondary">Clear</button>
        </div>
        <div class="col-4 d-grid">
          <button name="action" value="add_ingrediente" class="btn btn-outline-primary">Añadir</button>
        </div>
      </div>
    </div>

    <?php if (!empty($ingredientes)): ?>
      <div class="table-responsive mt-3">
        <table class="table table-sm table-bordered">
          <thead>
            <tr>
              <th>Alimento</th>
              <th class="text-end">Gramos</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ingredientes as $ing): ?>
              <tr>
                <td><?= esc($ing['alimento_nombre']) ?></td>
                <td class="text-end"><?= esc($ing['gramos']) ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-danger" href="<?= site_url('comidas/recetas/removeIngrediente/' . $ing['id']) ?>">Eliminar</a>
                </td>
              </tr>
              <?php $suma += $ing['gramos']; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div>Gramos de la receta completa: <?= esc($suma) ?> g</div>

    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-outline-secondary" href="<?= site_url('comidas/recetas') ?>">Volver</a>
    </div>
</form>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Enfocar al abrir el colapso para editar más rápido en móvil
    const meta = document.getElementById('metaCollapse');
    if (!meta) return;
    meta.addEventListener('shown.bs.collapse', () => {
      const nombre = meta.querySelector('input[name="nombre"]');
      if (nombre) nombre.focus();
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('alimento_nombre');
    const hidden = document.getElementById('alimento_id');
    const datalist = document.getElementById('alimentos-list');

    input.addEventListener('change', () => {
      const option = [...datalist.options].find(o => o.value === input.value);
      hidden.value = option ? option.dataset.id : '';
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    const btnClear = document.getElementById('btn-clear-alimento');
    const input = document.getElementById('alimento_nombre');
    const hidden = document.getElementById('alimento_id');

    if (btnClear && input && hidden) {
      btnClear.addEventListener('click', () => {
        input.value = '';
        hidden.value = '';
        input.focus();
      });
    }
  });
</script>
<?php $this->endSection(); ?>