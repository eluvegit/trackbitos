<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>
<?php helper(['form','security']); ?>

<h1 class="h4 mb-3"><?= isset($row) ? 'Editar' : 'Nuevo' ?> alimento</h1>
<!-- app/Views/comidas/alimentos/form.php -->
<?= form_open($action ?? current_url(), ['id' => 'alimentoForm']) ?>
<?= csrf_field() ?>

<div class="row g-3">

  <div class="mb-3">
    <label class="form-label">Pegado rápido (por 100 g)</label>
    <textarea name="bulk" rows="10" class="form-control"
      placeholder="Pega aquí el bloque con nutrientes (g, mg, mcg, kcal). Ignora los %; se detectan automáticamente."></textarea>
    <div class="form-text">
      Ej.: “Proteína 11.29 g”, “Sodio 544.50 mg”, “Ácido octadecatrienoico 8.49 g”…
      Calcularemos Omega-3/6 y saturadas automáticamente.
    </div>
  </div>

  <div class="d-flex gap-2 mt-2">
    <button type="button" id="btnPreview" class="btn btn-outline-secondary">
      Simular cambios
    </button>
    <button type="button" id="btnApplySelected" class="btn btn-primary" disabled>
      Aplicar selección
    </button>
  </div>

  <div id="previewBox" class="mt-3 d-none">
    <h6 class="mb-2">Cambios detectados</h6>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="chkAll"></th>
            <th>Campo</th>
            <th class="text-end">Actual</th>
            <th class="text-end">Nuevo</th>
          </tr>
        </thead>
        <tbody id="previewTbody"></tbody>
      </table>
    </div>
  </div>


  <!-- Identidad -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">Identidad</div>
      <div class="card-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input name="nombre" class="form-control" required
            value="<?= esc(old('nombre', $row['nombre'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Marca</label>
          <input name="marca" class="form-control"
            value="<?= esc(old('marca', $row['marca'] ?? '')) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="2"><?= esc(old('descripcion', $row['descripcion'] ?? '')) ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Propiedades / Receta -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">Propiedades</div>
      <div class="card-body row g-3">
        <div class="col-md-3 form-check form-switch">
          <input class="form-check-input" type="checkbox" id="es_liquido" name="es_liquido"
            <?= old('es_liquido', $row['es_liquido'] ?? 0) ? 'checked' : '' ?>>
          <label class="form-check-label" for="es_liquido">Es líquido</label>
        </div>
        <div class="col-md-3">
          <label class="form-label">Densidad (g/ml)</label>
          <input type="number" step="0.0001" name="densidad_g_ml" class="form-control"
            value="<?= esc(old('densidad_g_ml', $row['densidad_g_ml'] ?? '')) ?>">
        </div>
        <div class="col-md-3 form-check form-switch">
          <input class="form-check-input" type="checkbox" id="es_receta" name="es_receta"
            <?= old('es_receta', $row['es_receta'] ?? 0) ? 'checked' : '' ?>>
          <label class="form-check-label" for="es_receta">Es receta</label>
        </div>
        <div class="col-md-3">
          <label class="form-label">Receta ID</label>
          <input type="number" name="receta_id" class="form-control"
            value="<?= esc(old('receta_id', $row['receta_id'] ?? '')) ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Macros -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">Macros (por 100 g)</div>
      <div class="card-body row g-3">
        <?php
        $macros = [
          ['kcal', 'kcal', 0.01],
          ['proteina_g', 'Proteína (g)', 0.01],
          ['carbohidratos_g', 'Carbohidratos (g)', 0.01],
          ['azucares_g', 'Azúcares (g)', 0.01],
          ['fibra_g', 'Fibra (g)', 0.01],
          ['grasas_g', 'Grasas (g)', 0.01],
          ['grasas_saturadas_g', 'Saturadas (g)', 0.01],
          ['omega3_mg', 'Omega-3 (mg)', 0.01],
          ['omega6_mg', 'Omega-6 (mg)', 0.01],
          ['sodio_mg', 'Sodio (mg)', 0.01],
        ];
        foreach ($macros as [$name, $label, $step]): ?>
          <div class="col-md-3">
            <label class="form-label"><?= esc($label) ?></label>
            <input type="number" step="<?= $step ?>" name="<?= $name ?>" class="form-control"
              value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Minerales -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">Minerales (por 100 g)</div>
      <div class="card-body row g-3">
        <?php
        $mins = [
          ['calcio_mg', 'Calcio (mg)'],
          ['hierro_mg', 'Hierro (mg)'],
          ['magnesio_mg', 'Magnesio (mg)'],
          ['fosforo_mg', 'Fósforo (mg)'],
          ['potasio_mg', 'Potasio (mg)'],
          ['zinc_mg', 'Zinc (mg)'],
          ['selenio_ug', 'Selenio (µg)'],
          ['cobre_mg', 'Cobre (mg)'],
          ['manganeso_mg', 'Manganeso (mg)'],
          ['yodo_ug', 'Yodo (µg)'],
        ];
        foreach ($mins as [$name, $label]): ?>
          <div class="col-md-3">
            <label class="form-label"><?= esc($label) ?></label>
            <input type="number" step="0.01" name="<?= $name ?>" class="form-control"
              value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Vitaminas -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">Vitaminas (por 100 g)</div>
      <div class="card-body row g-3">
        <?php
        $vits = [
          ['vitamina_a_rae_ug', 'Vit. A (µg RAE)'],
          ['vitamina_c_mg', 'Vit. C (mg)'],
          ['vitamina_d_ug', 'Vit. D (µg)'],
          ['vitamina_e_mg', 'Vit. E (mg)'],
          ['vitamina_k_ug', 'Vit. K (µg)'],
        ];
        foreach ($vits as [$name, $label]): ?>
          <div class="col-md-3">
            <label class="form-label"><?= esc($label) ?></label>
            <input type="number" step="0.01" name="<?= $name ?>" class="form-control"
              value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">Guardar</button>
    <a href="<?= site_url('comidas/alimentos') ?>" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</div>

<?= form_close() ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form     = document.getElementById('alimentoForm');
  const bulkTa   = form.querySelector('[name="bulk"]');
  const btnPrev  = document.getElementById('btnPreview');
  const btnApply = document.getElementById('btnApplySelected');
  const box      = document.getElementById('previewBox');
  const tbody    = document.getElementById('previewTbody');
  const chkAll   = document.getElementById('chkAll');

  let parsedMap = {}; // field -> new value (desde preview)

  const csrfInput = form.querySelector('input[name="<?= csrf_token() ?>"]');

  const renderChanges = (changes) => {
    if (!changes || changes.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay cambios.</td></tr>';
      btnApply.disabled = true;
      box.classList.remove('d-none');
      return;
    }
    const rows = changes.map(ch => `
      <tr>
        <td><input type="checkbox" class="chg-check" value="${ch.field}" checked></td>
        <td>${ch.label}</td>
        <td class="text-end">${ch.old}</td>
        <td class="text-end fw-semibold">${ch.new}</td>
      </tr>
    `).join('');
    tbody.innerHTML = rows;
    box.classList.remove('d-none');
    btnApply.disabled = false;
    chkAll.checked = true;
  };

  btnPrev.addEventListener('click', async () => {
    const url = '<?= site_url('comidas/alimentos/preview') ?>';
    const fd  = new URLSearchParams();
    fd.set('bulk', (bulkTa?.value || '').toString());
    fd.set('id', '<?= isset($row['id']) ? (int)$row['id'] : 0 ?>');
    if (csrfInput) fd.set(csrfInput.name, csrfInput.value);

    btnPrev.disabled = true;
    btnPrev.textContent = 'Simulando...';
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: fd.toString()
      });
      const json = await res.json();
      parsedMap = json.parsed || {};
      renderChanges(json.changes || []);
    } catch(e) {
      console.error(e);
      tbody.innerHTML = '<tr><td colspan="4" class="text-danger">Error en la simulación.</td></tr>';
      box.classList.remove('d-none');
      btnApply.disabled = true;
    } finally {
      btnPrev.disabled = false;
      btnPrev.textContent = 'Simular cambios';
    }
  });

  chkAll.addEventListener('change', (e) => {
    document.querySelectorAll('.chg-check').forEach(c => c.checked = e.target.checked);
  });

  btnApply.addEventListener('click', () => {
    // Limpia antiguos apply_fields[]
    form.querySelectorAll('input[name="apply_fields[]"]').forEach(el => el.remove());
    // Por cada campo marcado, setea el input del form y añade apply_fields[]
    document.querySelectorAll('.chg-check:checked').forEach(ch => {
      const f = ch.value;
      const val = parsedMap[f];
      // Rellena el input si existe
      const input = form.querySelector(`[name="${f}"]`);
      if (input) input.value = val;
      // Marca que este campo se debe aplicar en backend
      const hid = document.createElement('input');
      hid.type = 'hidden';
      hid.name = 'apply_fields[]';
      hid.value = f;
      form.appendChild(hid);
    });
    // Enviar el formulario normal a store/update
    form.submit();
  });
});
</script>

<?= $this->endSection(); ?>



