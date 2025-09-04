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

    <?php
  // Indexamos alimentos por id
  $alimentosIndex = [];
  foreach (($alimentos ?? []) as $a) {
      $alimentosIndex[$a['id']] = $a;
  }

  // Totales
  $totalGr = 0;
  $totKcal = 0; $totProt = 0; $totCarb = 0; $totFat = 0;
?>

<?php if (!empty($ingredientes)): ?>
  <div class="list-group mt-3">
    <?php foreach ($ingredientes as $ing): ?>
      <?php
        $gr = (float)($ing['gramos'] ?? 0);
        $totalGr += $gr;

        $a = $alimentosIndex[$ing['alimento_id']] ?? [];

        // Macros por 100 g
        $kcal100 = (float)($a['kcal'] ?? 0);
        $p100    = (float)($a['proteina_g'] ?? 0);
        $c100    = (float)($a['carbohidratos_g'] ?? 0);
        $g100    = (float)($a['grasas_g'] ?? 0);

        // Escalar a gramos
        $kcal = $kcal100 * $gr / 100.0;
        $prot = $p100    * $gr / 100.0;
        $carb = $c100    * $gr / 100.0;
        $fat  = $g100    * $gr / 100.0;

        $totKcal += $kcal;
        $totProt += $prot;
        $totCarb += $carb;
        $totFat  += $fat;
      ?>
      <div class="list-group-item d-flex justify-content-between align-items-start">
        <div>
          <div class="fw-semibold"><?= esc($ing['alimento_nombre']) ?> — <?= number_format($gr, 1, ',', '.') ?> g</div>
          <div class="text-muted small">
            <?= number_format($kcal, 0, ',', '.') ?> kcal ·
            <?= number_format($prot, 1, ',', '.') ?> g proteína ·
            <?= number_format($carb, 1, ',', '.') ?> g carbohidratos ·
            <?= number_format($fat, 1, ',', '.') ?> g grasas
          </div>
        </div>
        <div>
          <a class="btn btn-sm btn-outline-danger"
             href="<?= site_url('comidas/recetas/removeIngrediente/' . $ing['id']) ?>">Eliminar</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Totales -->
  <div class="card mt-3">
    <div class="card-body">
      <strong>Totales receta:</strong><br>
      <?= number_format($totalGr, 1, ',', '.') ?> g ·
      <?= number_format($totKcal, 0, ',', '.') ?> kcal ·
      <?= number_format($totProt, 1, ',', '.') ?> g proteína ·
      <?= number_format($totCarb, 1, ',', '.') ?> g carbohidratos ·
      <?= number_format($totFat, 1, ',', '.') ?> g grasas
    </div>
  </div>
<?php endif; ?>


<div class="mt-2 small text-muted">
  *Los valores se calculan a partir de la información por 100&nbsp;g del alimento. Si el alimento no
  tiene macros cargados, se consideran 0.
</div>

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