<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>
<?php helper(['form', 'security']); ?>

<h2><?= esc(old('nombre', $row['nombre'] ?? '')) ?> <span class="text-muted h5"><?= esc(old('marca', $row['marca'] ?? '')) ?></span></h2>
<?= form_open($action ?? current_url(), ['id' => 'alimentoForm']) ?>
<?= csrf_field() ?>

<style>
  /* Botones plegables uniformes */
  .btn-toggle-seccion.btn-outline-secondary {
    background-color: transparent !important;
    color: var(--bs-secondary-color) !important;
    border-color: rgba(0, 0, 0, 0.1);
    transition: none;
  }

  .btn-toggle-seccion.btn-outline-secondary:hover,
  .btn-toggle-seccion.btn-outline-secondary:focus,
  .btn-toggle-seccion.btn-outline-secondary:active {
    background-color: transparent !important;
    color: var(--bs-secondary-color) !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
    box-shadow: none !important;
  }

  /* Modo oscuro */
  .text-bg-dark .btn-toggle-seccion.btn-outline-secondary,
  .bg-dark .btn-toggle-seccion.btn-outline-secondary {
    color: rgba(255, 255, 255, .75) !important;
    border-color: rgba(255, 255, 255, .15) !important;
  }

  .text-bg-dark .btn-toggle-seccion.btn-outline-secondary:hover,
  .bg-dark .btn-toggle-seccion.btn-outline-secondary:hover {
    color: rgba(255, 255, 255, .75) !important;
    border-color: rgba(255, 255, 255, .15) !important;
  }

  /* Campos numéricos: 2 por fila en móvil, 4 en escritorio */
  .campo-num .form-label {
    font-size: .8rem;
    margin-bottom: .2rem;
  }
  .campo-num .form-control {
    padding-top: .35rem;
    padding-bottom: .35rem;
  }

  .macro-principal .form-label {
    font-weight: 600;
  }
</style>

<div class="row g-3">

  <?php if (!empty($row['id'])): // ⬅️ Solo mostrar en modo editar
  ?>
    <div class="mb-3">
      <!-- Botón de despliegue -->
      <button type="button" class="btn btn-outline-secondary w-100 text-start mb-2 d-flex justify-content-between align-items-center btn-toggle-seccion"
        data-target="accesoRapidoBox">
        <span>Pegado rápido</span>
        <span class="d-flex align-items-center gap-1">
          <span class="small text-muted toggle-label">Mostrar</span>
          <span class="toggle-icon">▸</span>
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
        <button type="button" class="btn btn-outline-secondary w-100 text-start mb-0 d-flex justify-content-between align-items-center btn-toggle-seccion"
          data-target="identidadBox">
          <span>Identidad</span>
          <span class="d-flex align-items-center gap-1">
            <span class="small text-muted toggle-label">Mostrar</span>
            <span class="toggle-icon">▸</span>
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
        </div>
      </div>
    </div>

    <!-- Macros principales (siempre visibles) -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">Macros principales (por 100 g)</div>
        <div class="card-body row g-3">
          <?php
          $macrosPrincipales = [
            ['kcal', 'kcal', 0.01],
            ['proteina_g', 'Proteína (g)', 0.01],
            ['carbohidratos_g', 'Carbohidratos (g)', 0.01],
            ['grasas_g', 'Grasas (g)', 0.01],
          ];
          foreach ($macrosPrincipales as [$name, $label, $step]): ?>
            <div class="col-6 col-md-3 campo-num macro-principal">
              <label class="form-label"><?= esc($label) ?></label>
              <input type="number" step="<?= $step ?>" name="<?= $name ?>" class="form-control"
                value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Más macros (opcional, plegado) -->
    <div class="col-12">
      <div class="card">
        <button type="button" class="btn btn-outline-secondary w-100 text-start mb-0 d-flex justify-content-between align-items-center btn-toggle-seccion"
          data-target="masMacrosBox">
          <span>Más macros <span class="text-muted small">(azúcares, fibra, saturadas…)</span></span>
          <span class="d-flex align-items-center gap-1">
            <span class="small text-muted toggle-label">Mostrar</span>
            <span class="toggle-icon">▸</span>
          </span>
        </button>
        <div class="card-body row g-3 d-none" id="masMacrosBox">
          <?php
          $macrosSecundarios = [
            ['azucares_g', 'Azúcares (g)', 0.01],
            ['fibra_g', 'Fibra (g)', 0.01],
            ['grasas_saturadas_g', 'Saturadas (g)', 0.01],
            ['omega3_mg', 'Omega-3 (mg)', 0.01],
            ['omega6_mg', 'Omega-6 (mg)', 0.01],
            ['sodio_mg', 'Sodio (mg)', 0.01],
          ];
          foreach ($macrosSecundarios as [$name, $label, $step]): ?>
            <div class="col-6 col-md-3 campo-num">
              <label class="form-label"><?= esc($label) ?></label>
              <input type="number" step="<?= $step ?>" name="<?= $name ?>" class="form-control"
                value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Minerales (opcional, plegado) -->
    <div class="col-12">
      <div class="card">
        <button type="button" class="btn btn-outline-secondary w-100 text-start mb-0 d-flex justify-content-between align-items-center btn-toggle-seccion"
          data-target="mineralesBox">
          <span>Minerales <span class="text-muted small">(opcional)</span></span>
          <span class="d-flex align-items-center gap-1">
            <span class="small text-muted toggle-label">Mostrar</span>
            <span class="toggle-icon">▸</span>
          </span>
        </button>
        <div class="card-body row g-3 d-none" id="mineralesBox">
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
            <div class="col-6 col-md-3 campo-num">
              <label class="form-label"><?= esc($label) ?></label>
              <input type="number" step="0.01" name="<?= $name ?>" class="form-control"
                value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Vitaminas (opcional, plegado) -->
    <div class="col-12">
      <div class="card">
        <button type="button" class="btn btn-outline-secondary w-100 text-start mb-0 d-flex justify-content-between align-items-center btn-toggle-seccion"
          data-target="vitaminasBox">
          <span>Vitaminas <span class="text-muted small">(opcional)</span></span>
          <span class="d-flex align-items-center gap-1">
            <span class="small text-muted toggle-label">Mostrar</span>
            <span class="toggle-icon">▸</span>
          </span>
        </button>
        <div class="card-body row g-3 d-none" id="vitaminasBox">
          <?php
          $vits = [
            ['vitamina_a_rae_ug', 'Vit. A (µg RAE)'],
            ['vitamina_c_mg', 'Vit. C (mg)'],
            ['vitamina_d_ug', 'Vit. D (µg)'],
            ['vitamina_e_mg', 'Vit. E (mg)'],
            ['vitamina_k_ug', 'Vit. K (µg)'],
          ];
          foreach ($vits as [$name, $label]): ?>
            <div class="col-6 col-md-3 campo-num">
              <label class="form-label"><?= esc($label) ?></label>
              <input type="number" step="0.01" name="<?= $name ?>" class="form-control"
                value="<?= esc(old($name, $row[$name] ?? '0')) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Proporciones / unidades (opcional, plegado; solo si el alimento ya existe) -->
    <?php if (!empty($row['id'])): ?>
      <div class="col-12">
        <div class="card">
          <button type="button" class="btn btn-outline-secondary w-100 text-start mb-0 d-flex justify-content-between align-items-center btn-toggle-seccion"
            data-target="proporcionesBox">
            <span>📐 Proporciones / unidades <span class="text-muted small">(opcional)</span></span>
            <span class="d-flex align-items-center gap-1">
              <span class="small text-muted toggle-label">Mostrar</span>
              <span class="toggle-icon">▸</span>
            </span>
          </button>
          <div class="card-body d-none" id="proporcionesBox">
            <p class="text-muted small">
              ¿Se sirve por lonchas, tazas u otra unidad? Añádelo aquí para poder
              registrarla así en el diario, además de por gramos.
            </p>

            <div class="row g-2 mb-3 align-items-end">
              <div class="col-12 col-md-5">
                <label class="form-label small mb-1">Nombre</label>
                <input type="text" id="propDescripcion" class="form-control form-control-sm" placeholder="Ej. loncha, taza…">
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Gramos</label>
                <input type="number" step="1" min="1" id="propGramos" class="form-control form-control-sm" placeholder="Ej. 25">
              </div>
              <div class="col-6 col-md-2 form-check pb-1">
                <input class="form-check-input" type="checkbox" id="propPredeterminada">
                <label class="form-check-label small" for="propPredeterminada">Predet.</label>
              </div>
              <div class="col-12 col-md-2 d-flex">
                <button type="button" id="btnAgregarProp" class="btn btn-primary btn-sm flex-fill">
                  <i class="bi bi-plus-lg"></i> Añadir
                </button>
              </div>
            </div>

            <div id="listaProporciones"></div>

            <div class="mt-2 small text-muted">
              Toca una proporción de la lista para cambiar su nombre, sus gramos o eliminarla.
            </div>
          </div>
        </div>
      </div>

      <!-- Modal editar/eliminar proporción -->
      <div class="modal fade" id="modalEditarProporcion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Editar proporción</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2">
                <label class="form-label small mb-1">Nombre</label>
                <input type="text" id="modalPropDescripcion" class="form-control">
              </div>
              <div class="mb-2">
                <label class="form-label small mb-1">Gramos</label>
                <input type="number" step="1" min="1" id="modalPropGramos" class="form-control">
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="modalPropPredeterminada">
                <label class="form-check-label small" for="modalPropPredeterminada">Porción predeterminada</label>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-outline-danger" id="btnEliminarProp">
                <i class="bi bi-trash"></i> Eliminar
              </button>
              <button type="button" class="btn btn-primary" id="btnGuardarProp">Guardar</button>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="col-12 d-flex gap-2 flex-wrap mt-3 mb-5 pb-3">
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

      // Botones plegables (Identidad, Pegado rápido, Más macros, Minerales, Vitaminas...)
      document.querySelectorAll('.btn-toggle-seccion').forEach((btn) => {
        const box = document.getElementById(btn.dataset.target);
        const label = btn.querySelector('.toggle-label');
        const icon = btn.querySelector('.toggle-icon');
        if (!box) return;

        // Estado inicial del texto/icono según si arranca visible u oculto
        const startsHidden = box.classList.contains('d-none');
        label.textContent = startsHidden ? 'Mostrar' : 'Ocultar';
        icon.textContent = startsHidden ? '▸' : '▾';

        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const isHidden = box.classList.contains('d-none');
          box.classList.toggle('d-none');
          label.textContent = isHidden ? 'Ocultar' : 'Mostrar';
          icon.textContent = isHidden ? '▾' : '▸';
        });
      });

      <?php if (!empty($row['id'])): ?>
      // Proporciones / unidades (inline, sin salir de la pantalla)
      (() => {
        const ALIMENTO_ID = <?= (int) $row['id'] ?>;
        const API = {
          listar: '<?= site_url('comidas/porciones/ajax') ?>/' + ALIMENTO_ID,
          add: '<?= site_url('comidas/porciones/ajax/store') ?>',
          editBase: '<?= site_url('comidas/porciones/ajax') ?>',   // /{id}/update
          delBase: '<?= site_url('comidas/porciones/ajax') ?>',    // /{id}/delete
        };

        const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        const fmt0 = n => (Math.round(+n || 0)).toString();

        function escapeHtml(str) {
          const div = document.createElement('div');
          div.textContent = str ?? '';
          return div.innerHTML;
        }

        const postForm = async (url, data) => {
          if (csrfInput && csrfInput.name && csrfInput.value) data[csrfInput.name] = csrfInput.value;
          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(data).toString()
          });
          return res.json();
        };
        const getJson = async (url) => (await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json();

        const propDescripcion = document.getElementById('propDescripcion');
        const propGramos = document.getElementById('propGramos');
        const propPredeterminada = document.getElementById('propPredeterminada');
        const btnAgregarProp = document.getElementById('btnAgregarProp');
        const listaProporciones = document.getElementById('listaProporciones');

        const modalPropEl = document.getElementById('modalEditarProporcion');
        // bootstrap.bundle.min.js puede no estar listo todavía en el momento
        // en que se parsea este script, así que la instancia del modal se
        // crea perezosamente (en el primer clic) en vez de al cargar la página.
        let modalProp = null;
        const modalPropDescripcion = document.getElementById('modalPropDescripcion');
        const modalPropGramos = document.getElementById('modalPropGramos');
        const modalPropPredeterminada = document.getElementById('modalPropPredeterminada');
        const btnGuardarProp = document.getElementById('btnGuardarProp');
        const btnEliminarProp = document.getElementById('btnEliminarProp');
        let proporcionActualId = null;

        const renderProporciones = (rows) => {
          if (!rows || rows.length === 0) {
            listaProporciones.innerHTML = `<div class="alert alert-light border py-2 mb-0">Aún no hay proporciones definidas para este alimento.</div>`;
            return;
          }
          const filas = rows.map(r => {
            const desc = escapeHtml(r.descripcion || '—');
            const predet = +r.es_predeterminada === 1;
            return `
              <div class="list-group-item proporcion-row d-flex justify-content-between align-items-center"
                   role="button" data-id="${r.id}" data-descripcion="${desc}"
                   data-gramos="${r.gramos_equivalentes}" data-predeterminada="${predet ? 1 : 0}">
                <div>
                  <div class="fw-semibold">${desc} ${predet ? '<span class="badge text-bg-primary ms-1">Predet.</span>' : ''}</div>
                  <div class="small text-muted">${fmt0(r.gramos_equivalentes)} g</div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
              </div>`;
          }).join('');
          listaProporciones.innerHTML = `<div class="list-group">${filas}</div>`;
        };

        const cargarProporciones = async () => {
          try {
            const rows = await getJson(API.listar);
            renderProporciones(rows);
          } catch (e) {
            console.error(e);
            listaProporciones.innerHTML = `<div class="alert alert-danger py-2 mb-0">Error al cargar las proporciones.</div>`;
          }
        };

        btnAgregarProp.addEventListener('click', async () => {
          const descripcion = (propDescripcion.value || '').trim();
          const gramos = parseFloat((propGramos.value || '').toString().replace(',', '.'));
          if (!descripcion) return alert('Indica un nombre para la proporción.');
          if (!gramos || gramos <= 0) return alert('Indica los gramos.');

          try {
            const r = await postForm(API.add, {
              alimento_id: String(ALIMENTO_ID),
              descripcion,
              gramos_equivalentes: String(gramos),
              es_predeterminada: propPredeterminada.checked ? '1' : '',
            });
            if (!r.ok) {
              alert('No se pudo añadir: ' + (r.error || 'Error desconocido'));
              return;
            }
            propDescripcion.value = '';
            propGramos.value = '';
            propPredeterminada.checked = false;
            await cargarProporciones();
          } catch (err) {
            console.error(err);
            alert('Error de red al añadir.');
          }
        });

        listaProporciones.addEventListener('click', (e) => {
          const row = e.target.closest('.proporcion-row');
          if (!row) return;
          modalProp ??= new bootstrap.Modal(modalPropEl);
          proporcionActualId = row.dataset.id;
          modalPropDescripcion.value = row.dataset.descripcion;
          modalPropGramos.value = row.dataset.gramos;
          modalPropPredeterminada.checked = row.dataset.predeterminada === '1';
          modalProp.show();
        });

        btnGuardarProp.addEventListener('click', async () => {
          if (!proporcionActualId) return;
          const descripcion = (modalPropDescripcion.value || '').trim();
          const gramos = parseFloat((modalPropGramos.value || '').toString().replace(',', '.'));
          if (!descripcion) return alert('Indica un nombre para la proporción.');
          if (!gramos || gramos <= 0) return alert('Introduce una cantidad válida.');
          try {
            const r = await postForm(`${API.editBase}/${proporcionActualId}/update`, {
              descripcion,
              gramos_equivalentes: String(gramos),
              es_predeterminada: modalPropPredeterminada.checked ? '1' : '',
            });
            if (r.ok) {
              modalProp.hide();
              await cargarProporciones();
            } else {
              alert('No se pudo actualizar: ' + (r.error || 'Error desconocido'));
            }
          } catch (err) {
            console.error(err);
            alert('Error de red al actualizar.');
          }
        });

        btnEliminarProp.addEventListener('click', async () => {
          if (!proporcionActualId) return;
          if (!confirm('¿Eliminar esta proporción?')) return;
          try {
            const r = await postForm(`${API.delBase}/${proporcionActualId}/delete`, {});
            if (r.ok) {
              modalProp.hide();
              await cargarProporciones();
            } else {
              alert('No se pudo eliminar.');
            }
          } catch (err) {
            console.error(err);
            alert('Error de red al eliminar.');
          }
        });

        cargarProporciones();
      })();
      <?php endif; ?>
    </script>

    <?= $this->endSection(); ?>
