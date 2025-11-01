<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>
<?php helper(['form', 'security']); ?>

<h2><?= esc(old('nombre', $row['nombre'] ?? '')) ?> <span class="text-muted h5"><?= esc(old('marca', $row['marca'] ?? '')) ?></span></h2>
<?= form_open($action ?? current_url(), ['id' => 'alimentoForm']) ?>
<?= csrf_field() ?>

<style>
  /* Botones plegables uniformes */
  #toggleAccesoRapido.btn-outline-secondary,
  #toggleIdentidad.btn-outline-secondary {
    background-color: transparent !important;
    color: var(--bs-secondary-color) !important;
    border-color: rgba(0, 0, 0, 0.1);
    transition: none;
  }

  #toggleAccesoRapido.btn-outline-secondary:hover,
  #toggleAccesoRapido.btn-outline-secondary:focus,
  #toggleAccesoRapido.btn-outline-secondary:active,
  #toggleIdentidad.btn-outline-secondary:hover,
  #toggleIdentidad.btn-outline-secondary:focus,
  #toggleIdentidad.btn-outline-secondary:active {
    background-color: transparent !important;
    color: var(--bs-secondary-color) !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
    box-shadow: none !important;
  }

  /* Modo oscuro */
  .text-bg-dark #toggleAccesoRapido.btn-outline-secondary,
  .bg-dark #toggleAccesoRapido.btn-outline-secondary,
  .text-bg-dark #toggleIdentidad.btn-outline-secondary,
  .bg-dark #toggleIdentidad.btn-outline-secondary {
    color: rgba(255, 255, 255, .75) !important;
    border-color: rgba(255, 255, 255, .15) !important;
  }

  .text-bg-dark #toggleAccesoRapido.btn-outline-secondary:hover,
  .bg-dark #toggleAccesoRapido.btn-outline-secondary:hover,
  .text-bg-dark #toggleIdentidad.btn-outline-secondary:hover,
  .bg-dark #toggleIdentidad.btn-outline-secondary:hover {
    color: rgba(255, 255, 255, .75) !important;
    border-color: rgba(255, 255, 255, .15) !important;
  }
</style>



<div class="row g-3">

  <?php if (!empty($row['id'])): // ⬅️ Solo mostrar en modo editar 
  ?>
    <div class="mb-3">
      <!-- Botón de despliegue -->
      <button type="button" id="toggleAccesoRapido"
        class="btn btn-outline-secondary w-100 text-start mb-2 d-flex justify-content-between align-items-center">
        <span>Pegado rápido</span>
        <span class="d-flex align-items-center gap-1">
          <span id="toggleAccesoRapidoLabel" class="small text-muted">Mostrar</span>
          <span id="toggleAccesoRapidoIcon">▸</span>
        </span>
      </button>



      <div id="accesoRapidoBox" class="d-none">
        <textarea name="bulk" rows="5" class="form-control mb-2"
          placeholder="Pega aquí el bloque con nutrientes (g, mg, µg, kcal). Ignora los %; se detectan automáticamente."></textarea>
        <div class="form-text mb-2">
          Ej.: “Proteína 11.29 g”, “Sodio 544.50 mg”, “Ácido octadecatrienoico 8.49 g”… Calcularemos Omega-3/6 y saturadas automáticamente.
        </div>

        <div class="d-flex gap-2 my-2">
          <button type="button" id="btnPreviewBulk" class="btn btn-outline-secondary">
            Simular
          </button>
          <button type="button" id="btnApplySelected" class="btn btn-primary" disabled>
            Aplicar
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
      </div>

    <?php endif; ?>


    <!-- Identidad -->
    <div class="col-12 mb-2">
      <div class="card">
        <!-- Botón de despliegue con el mismo estilo -->
        <button type="button" id="toggleIdentidad"
          class="btn btn-outline-secondary w-100 text-start mb-0 d-flex justify-content-between align-items-center">
          <span>Identidad</span>
          <span class="d-flex align-items-center gap-1">
            <span id="toggleIdentidadLabel" class="small text-muted">Mostrar</span>
            <span id="toggleIdentidadIcon">▸</span>
          </span>
        </button>

        <div class="card-body row g-3 <?= !empty($row['id']) ? 'd-none' : '' ?>" id="identidadBox">
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
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
            <a href="<?= site_url('comidas/alimentos') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <?php if (!empty($row['id'])): ?>
              <a href="<?= site_url('comidas/porciones/alimento/' . $row['id']) ?>" class="btn btn-outline-primary">Proporciones</a>
            <?php endif; ?>

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
            ['grasas_g', 'Grasas (g)', 0.01],
            ['azucares_g', 'Azúcares (g)', 0.01],
            ['fibra_g', 'Fibra (g)', 0.01],
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
        const form = document.getElementById('alimentoForm');
        if (!form) return;

        // Si NO existe el importador (estamos en CREATE), no hacemos nada de la parte de preview.
        const btnUrl = document.getElementById('btnPreviewUrl');
        const btnBulk = document.getElementById('btnPreviewBulk');
        const importerPresent = !!(btnUrl || btnBulk);
        if (!importerPresent) return;

        const bulkTa = form.querySelector('[name="bulk"]');
        const urlInput = form.querySelector('[name="url_nutrionio"]');
        const btnApply = document.getElementById('btnApplySelected');
        const box = document.getElementById('previewBox');
        const tbody = document.getElementById('previewTbody');
        const chkAll = document.getElementById('chkAll');
        const csrfInput = form.querySelector('input[name="<?= csrf_token() ?>"]');

        let parsedMap = {};

        function renderChanges(changes) {
          if (!changes || changes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay cambios.</td></tr>';
            if (btnApply) btnApply.disabled = true;
            box.classList.remove('d-none');
            return;
          }
          tbody.innerHTML = changes.map(ch => `
      <tr>
        <td><input type="checkbox" class="chg-check" value="${ch.field}" checked></td>
        <td>${ch.label}</td>
        <td class="text-end">${ch.old}</td>
        <td class="text-end fw-semibold">${ch.new}</td>
      </tr>
    `).join('');
          if (btnApply) btnApply.disabled = false;
          box.classList.remove('d-none');
          if (chkAll) chkAll.checked = true;
        }

        async function runPreview(payload, btn) {
          const endpoint = '<?= site_url('comidas/alimentos/preview') ?>';
          const fd = new URLSearchParams();
          fd.set('id', '<?= isset($row['id']) ? (int)$row['id'] : 0 ?>');
          if (payload.bulk !== undefined) fd.set('bulk', payload.bulk);
          if (payload.url !== undefined) fd.set('url', payload.url);
          if (csrfInput) fd.set(csrfInput.name, csrfInput.value);

          const prevTxt = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Simulando...';
          try {
            const res = await fetch(endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
              },
              credentials: 'same-origin',
              body: fd.toString()
            });
            const json = await res.json();
            parsedMap = json.parsed || {};
            renderChanges(json.changes || []);
          } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="4" class="text-danger">Error en la simulación.</td></tr>';
            box.classList.remove('d-none');
            if (btnApply) btnApply.disabled = true;
          } finally {
            btn.disabled = false;
            btn.textContent = prevTxt;
          }
        }

        if (btnUrl) {
          btnUrl.addEventListener('click', () => {
            const urlVal = (urlInput?.value || '').trim();
            if (!urlVal) {
              alert('Introduce una URL de nutrionio.com');
              return;
            }
            runPreview({
              url: urlVal,
              bulk: ''
            }, btnUrl);
          });
        }

        if (btnBulk) {
          btnBulk.addEventListener('click', () => {
            runPreview({
              bulk: (bulkTa?.value || '').toString(),
              url: ''
            }, btnBulk);
          });
        }

        if (chkAll) {
          chkAll.addEventListener('change', (e) => {
            tbody.querySelectorAll('.chg-check').forEach(c => c.checked = e.target.checked);
          });
        }

        if (btnApply) {
          btnApply.addEventListener('click', () => {
            // limpiar antiguos apply_fields[]
            form.querySelectorAll('input[name="apply_fields[]"]').forEach(el => el.remove());
            // aplicar seleccionados
            tbody.querySelectorAll('.chg-check:checked').forEach(ch => {
              const f = ch.value;
              const val = parsedMap[f];
              const input = form.querySelector(`[name="${f}"]`);
              if (input) input.value = val;
              const hid = document.createElement('input');
              hid.type = 'hidden';
              hid.name = 'apply_fields[]';
              hid.value = f;
              form.appendChild(hid);
            });
            form.submit();
          });
        }
      });

      // Botón de identidad
      const toggleIdentidadBtn = document.getElementById('toggleIdentidad');
      if (toggleIdentidadBtn) {
        const box = document.getElementById('identidadBox');
        const label = document.getElementById('toggleIdentidadLabel');
        const icon = document.getElementById('toggleIdentidadIcon');
        toggleIdentidadBtn.addEventListener('click', (e) => {
          e.preventDefault();
          const isHidden = box.classList.contains('d-none');
          box.classList.toggle('d-none');
          label.textContent = isHidden ? 'Ocultar' : 'Mostrar';
          icon.textContent = isHidden ? '▾' : '▸';
        });
      }

      // Botón de acceso rápido (toggle sin cambio de color)
      const toggleAccesoBtn = document.getElementById('toggleAccesoRapido');
      if (toggleAccesoBtn) {
        const box = document.getElementById('accesoRapidoBox');
        const label = document.getElementById('toggleAccesoRapidoLabel');
        const icon = document.getElementById('toggleAccesoRapidoIcon');

        toggleAccesoBtn.addEventListener('click', (e) => {
          e.preventDefault();
          const isHidden = box.classList.contains('d-none');
          box.classList.toggle('d-none');
          label.textContent = isHidden ? 'Ocultar' : 'Mostrar';
          icon.textContent = isHidden ? '▾' : '▸';
        });
      }
    </script>

    <?= $this->endSection(); ?>