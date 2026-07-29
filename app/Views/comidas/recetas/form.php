<?php $this->extend('comidas/layout');
$this->section('content'); ?>
<h1 class="h4 mb-1">🍳 <?= isset($row) ? 'Editar receta' : 'Nueva receta' ?></h1>
<p class="text-muted mb-3">
  <?= isset($row)
      ? 'Ajusta los datos, añade ingredientes y mira cómo cambian las calorías al momento.'
      : 'Ponle un nombre para empezar. Después podrás añadir los ingredientes uno a uno.' ?>
</p>

<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<?php if (session('msg')): ?>
  <div class="alert alert-success py-2"><?= esc(session('msg')) ?></div>
<?php endif; ?>

<style>
  #resultados .list-group-item {
    cursor: pointer;
  }
  .ingrediente-row {
    cursor: pointer;
  }
  .ingrediente-row:hover {
    background-color: var(--bs-tertiary-bg);
  }
  .nutri-tile {
    background: var(--bs-tertiary-bg);
    border-radius: .75rem;
    padding: .6rem .25rem;
  }
  .nutri-tile .valor {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.1;
  }
  .nutri-tile .etiqueta {
    font-size: .72rem;
  }
</style>

<!-- Paso 1: datos básicos -->
<div class="card mb-3">
  <div class="card-header">📝 Nombre y descripción</div>
  <div class="card-body">
    <form method="post" action="<?= isset($row) ? site_url('comidas/recetas/update/' . $row['id']) : site_url('comidas/recetas/store') ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Nombre</label>
          <input class="form-control" name="nombre" required placeholder="Ej. Tortitas de avena"
                 value="<?= esc(old('nombre', $row['nombre'] ?? '')) ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Descripción <span class="text-muted small">(opcional)</span></label>
          <textarea class="form-control" name="descripcion" rows="2"
                    placeholder="Notas, modo de preparación…"><?= esc(old('descripcion', $row['descripcion'] ?? '')) ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
          <button class="btn btn-primary">Guardar</button>
          <a class="btn btn-outline-secondary" href="<?= site_url('comidas/recetas') ?>">Volver</a>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (isset($row)): ?>
  <!-- Paso 2: ingredientes -->
  <div class="card mb-3">
    <div class="card-header">🧺 Ingredientes</div>
    <div class="card-body">
      <p class="text-muted small">
        Busca un alimento, indica los gramos y pulsa añadir. Las calorías y macros de abajo se
        actualizan al momento con cada ingrediente.
      </p>

      <?= csrf_field() ?>

      <div class="input-group mb-1">
        <input type="text" id="buscador" class="form-control" placeholder="Escribe para buscar un alimento…" autocomplete="off">
        <button type="button" class="btn btn-outline-secondary" id="btnClr">CLR</button>
      </div>
      <ul id="resultados" class="list-group mb-3"></ul>

      <div class="alert alert-info py-2 px-3 mb-2 d-flex justify-content-between align-items-start" id="selectedInfo" style="display:none;">
        <div class="me-2">
          <div class="fw-semibold" id="selectedName"></div>
          <div class="small text-muted" id="selectedMacros"></div>
        </div>
        <button type="button" class="btn-close" id="clearSelected" aria-label="Borrar"></button>
      </div>

      <div class="row g-2 mb-3 align-items-end" id="addRow" style="display:none;">
        <div class="col-6">
          <label class="form-label small mb-1">Gramos</label>
          <input type="number" step="0.1" id="inputGramos" class="form-control form-control-sm" placeholder="Ej. 100">
        </div>
        <div class="col-6 d-flex">
          <button type="button" id="btnAgregar" class="btn btn-primary flex-fill btn-sm">
            <i class="bi bi-plus-lg"></i> Añadir
          </button>
        </div>
      </div>

      <hr>

      <!-- Resumen nutricional en vivo -->
      <div class="row row-cols-5 g-2 text-center mb-3">
        <div class="col">
          <div class="nutri-tile">
            <div class="valor" id="totGramos">0</div>
            <div class="etiqueta text-muted">⚖️ g total</div>
          </div>
        </div>
        <div class="col">
          <div class="nutri-tile">
            <div class="valor" id="totKcal">0</div>
            <div class="etiqueta text-muted">🔥 kcal</div>
          </div>
        </div>
        <div class="col">
          <div class="nutri-tile">
            <div class="valor" id="totProt">0</div>
            <div class="etiqueta text-muted">🥩 prot. g</div>
          </div>
        </div>
        <div class="col">
          <div class="nutri-tile">
            <div class="valor" id="totCarb">0</div>
            <div class="etiqueta text-muted">🍞 carb. g</div>
          </div>
        </div>
        <div class="col">
          <div class="nutri-tile">
            <div class="valor" id="totGrasa">0</div>
            <div class="etiqueta text-muted">🥑 gras. g</div>
          </div>
        </div>
      </div>

      <div id="listaIngredientes"></div>

      <div class="mt-2 small text-muted">
        *Se calcula a partir de la información por 100&nbsp;g de cada alimento. Toca un ingrediente
        de la lista para cambiar su cantidad o eliminarlo.
      </div>
    </div>
  </div>

  <!-- Paso 3: proporciones / unidades (opcional) -->
  <?php if (!empty($aliVirtId)): ?>
    <div class="card mb-3">
      <div class="card-header">📐 Proporciones / unidades <span class="text-muted small">(opcional)</span></div>
      <div class="card-body">
        <p class="text-muted small">
          Peso total de la receta: <strong id="propTotalGramos">0</strong>&nbsp;g.
          ¿Se sirve por raciones, tazas u otra unidad? Añádelo aquí para poder
          registrarla así en el diario, además de por gramos.
        </p>

        <div class="row g-2 mb-3 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label small mb-1">Nombre</label>
            <input type="text" id="propDescripcion" class="form-control form-control-sm" placeholder="Ej. ración, taza…">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Gramos</label>
            <input type="number" step="1" min="1" id="propGramos" class="form-control form-control-sm" placeholder="Ej. 250">
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

  <!-- Modal editar/eliminar ingrediente -->
  <div class="modal fade" id="modalEditarIngrediente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarNombre">Editar ingrediente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <label class="form-label small mb-1">Gramos</label>
          <input type="number" step="0.1" min="0" id="modalEditarGramos" class="form-control">
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnEliminarIngrediente">
            <i class="bi bi-trash"></i> Eliminar
          </button>
          <button type="button" class="btn btn-primary" id="btnGuardarIngrediente">Guardar</button>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-light border">
    ✨ En cuanto guardes el nombre podrás buscar y añadir ingredientes aquí mismo, viendo las
    calorías totales en vivo.
  </div>
<?php endif; ?>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  <?php if (isset($row)): ?>
  document.addEventListener('DOMContentLoaded', () => {
    const RECETA_ID = <?= (int) $row['id'] ?>;
    const ALIVIRT_ID = <?= (int) ($aliVirtId ?? 0) ?>;

    const API = {
      buscar: '<?= site_url('api/alimentos') ?>',            // GET ?q=
      alimentoBase: '<?= site_url('api/alimentos') ?>',      // GET /{id}
      listar: '<?= site_url('comidas/recetas/ingredientes') ?>/' + RECETA_ID,
      add: '<?= site_url('comidas/recetas/ingredientes') ?>/' + RECETA_ID + '/add',
      editBase: '<?= site_url('comidas/recetas/ingrediente') ?>',   // /{id}/edit
      delBase: '<?= site_url('comidas/recetas/ingrediente') ?>',    // /{id}/delete
      propsListar: '<?= site_url('comidas/porciones/ajax') ?>/' + ALIVIRT_ID,
      propsAdd: '<?= site_url('comidas/porciones/ajax/store') ?>',
      propsEditBase: '<?= site_url('comidas/porciones/ajax') ?>',   // /{id}/update
      propsDelBase: '<?= site_url('comidas/porciones/ajax') ?>',    // /{id}/delete
    };

    const buscador = document.getElementById('buscador');
    const btnClr = document.getElementById('btnClr');
    const resultados = document.getElementById('resultados');

    const selectedInfo = document.getElementById('selectedInfo');
    const selectedName = document.getElementById('selectedName');
    const selectedMacros = document.getElementById('selectedMacros');
    const clearSelected = document.getElementById('clearSelected');

    const addRow = document.getElementById('addRow');
    const inputGramos = document.getElementById('inputGramos');
    const btnAgregar = document.getElementById('btnAgregar');

    const listaIngredientes = document.getElementById('listaIngredientes');
    const totGramos = document.getElementById('totGramos');
    const totKcal = document.getElementById('totKcal');
    const totProt = document.getElementById('totProt');
    const totCarb = document.getElementById('totCarb');
    const totGrasa = document.getElementById('totGrasa');
    const propTotalGramos = document.getElementById('propTotalGramos');

    const modalEl = document.getElementById('modalEditarIngrediente');
    const modal = new bootstrap.Modal(modalEl);
    const modalNombre = document.getElementById('modalEditarNombre');
    const modalGramos = document.getElementById('modalEditarGramos');
    const btnGuardarIng = document.getElementById('btnGuardarIngrediente');
    const btnEliminarIng = document.getElementById('btnEliminarIngrediente');
    let ingredienteActualId = null;

    const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');

    let alimentoSeleccionado = null; // { id, nombre, macros:{kcal,p,c,g} }

    const fmt1 = n => (Math.round((+n || 0) * 10) / 10).toFixed(1);
    const fmt0 = n => (Math.round(+n || 0)).toString();
    const toQuery = params => new URLSearchParams(params).toString();

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

    const debounce = (fn, ms = 250) => {
      let t;
      return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
      };
    };

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str ?? '';
      return div.innerHTML;
    }

    const resetSeleccion = () => {
      alimentoSeleccionado = null;
      selectedInfo.style.display = 'none';
      selectedName.textContent = '';
      selectedMacros.innerHTML = '';
      addRow.style.display = 'none';
      inputGramos.value = '';
    };

    function renderMacrosPreview() {
      if (!alimentoSeleccionado) {
        selectedMacros.innerHTML = '';
        return;
      }
      const { kcal, p, c, g } = alimentoSeleccionado.macros;
      let html = `<div><strong>Por 100 g:</strong> ${fmt0(kcal)} kcal · ${fmt1(p)} g P · ${fmt1(c)} g C · ${fmt1(g)} g G</div>`;

      const gramosInput = parseFloat((inputGramos.value || '').toString().replace(',', '.')) || 0;
      if (gramosInput > 0) {
        const factor = gramosInput / 100;
        html += `<div><strong>Para ${fmt1(gramosInput)} g:</strong> ${fmt0(kcal * factor)} kcal · ${fmt1(p * factor)} g P · ${fmt1(c * factor)} g C · ${fmt1(g * factor)} g G</div>`;
      }
      selectedMacros.innerHTML = html;
    }

    const pintarResultados = (rows) => {
      if (!rows || rows.length === 0) {
        resultados.innerHTML = `<li class="list-group-item">Sin resultados…</li>`;
        return;
      }
      resultados.innerHTML = rows.map(r =>
        `<li class="list-group-item" role="button" data-id="${r.id}" data-name="${escapeHtml(r.nombre)}">${escapeHtml(r.nombre)}</li>`
      ).join('');
    };

    const buscar = async (q) => {
      q = (q || '').trim();
      if (q.length < 1) {
        resultados.innerHTML = '';
        return;
      }
      try {
        const rows = await getJson(`${API.buscar}?${toQuery({ q })}`);
        pintarResultados(rows);
      } catch (e) {
        console.error(e);
        resultados.innerHTML = `<li class="list-group-item text-danger">Error buscando…</li>`;
      }
    };
    const buscarDebounced = debounce(buscar, 200);

    buscador.addEventListener('input', (e) => buscarDebounced(e.target.value));

    btnClr.addEventListener('click', () => {
      buscador.value = '';
      resultados.innerHTML = '';
      resetSeleccion();
      buscador.focus();
    });

    resultados.addEventListener('click', async (e) => {
      const li = e.target.closest('li[data-id]');
      if (!li) return;

      const id = parseInt(li.dataset.id, 10);
      let detalle = null;
      try {
        detalle = await getJson(`${API.alimentoBase}/${id}`);
      } catch (err) {
        console.error(err);
      }

      alimentoSeleccionado = {
        id,
        nombre: li.dataset.name || (detalle?.nombre ?? `#${id}`),
        macros: {
          kcal: parseFloat(detalle?.kcal ?? 0) || 0,
          p: parseFloat(detalle?.proteina_g ?? 0) || 0,
          c: parseFloat(detalle?.carbohidratos_g ?? 0) || 0,
          g: parseFloat(detalle?.grasas_g ?? 0) || 0,
        }
      };

      selectedName.textContent = alimentoSeleccionado.nombre;
      selectedInfo.style.display = 'flex';
      addRow.style.display = 'flex';
      renderMacrosPreview();

      resultados.innerHTML = '';
      buscador.value = '';
      inputGramos.focus();
    });

    clearSelected.addEventListener('click', resetSeleccion);
    inputGramos.addEventListener('input', renderMacrosPreview);

    const renderIngredientes = (rows) => {
      let tot = { g: 0, kcal: 0, p: 0, c: 0, gr: 0 };

      if (!rows || rows.length === 0) {
        listaIngredientes.innerHTML = `<div class="alert alert-light border">🍽️ Aún no has añadido ningún ingrediente. Búscalo arriba para empezar.</div>`;
      } else {
        const filas = rows.map(r => {
          const g = parseFloat(r.gramos || 0) || 0;
          const factor = g / 100;
          const kcal = (parseFloat(r.kcal || 0) * factor) || 0;
          const pr = (parseFloat(r.proteina_g || 0) * factor) || 0;
          const ch = (parseFloat(r.carbohidratos_g || 0) * factor) || 0;
          const gr = (parseFloat(r.grasas_g || 0) * factor) || 0;
          tot.g += g;
          tot.kcal += kcal;
          tot.p += pr;
          tot.c += ch;
          tot.gr += gr;

          const nombre = escapeHtml(r.nombre || '—');
          return `
            <div class="list-group-item ingrediente-row d-flex justify-content-between align-items-center"
                 role="button" data-id="${r.id}" data-nombre="${nombre}" data-gramos="${g}">
              <div>
                <div class="fw-semibold">${nombre}</div>
                <div class="small text-muted">${fmt1(g)} g · ${fmt1(pr)} g P · ${fmt1(ch)} g C · ${fmt1(gr)} g G</div>
              </div>
              <div class="text-end text-nowrap">
                <span class="badge text-bg-secondary">${fmt0(kcal)} kcal</span>
                <i class="bi bi-chevron-right text-muted ms-1"></i>
              </div>
            </div>`;
        }).join('');
        listaIngredientes.innerHTML = `<div class="list-group">${filas}</div>`;
      }

      totGramos.textContent = fmt1(tot.g);
      totKcal.textContent = fmt0(tot.kcal);
      totProt.textContent = fmt1(tot.p);
      totCarb.textContent = fmt1(tot.c);
      totGrasa.textContent = fmt1(tot.gr);
      if (propTotalGramos) propTotalGramos.textContent = fmt1(tot.g);
    };

    const cargarIngredientes = async () => {
      try {
        const rows = await getJson(API.listar);
        renderIngredientes(rows);
      } catch (e) {
        console.error(e);
        listaIngredientes.innerHTML = `<div class="alert alert-danger">Error al cargar ingredientes.</div>`;
      }
    };

    btnAgregar.addEventListener('click', async () => {
      if (!alimentoSeleccionado) return alert('Selecciona un alimento.');

      const gramosNum = (() => {
        const v = (inputGramos.value || '').toString().replace(',', '.').trim();
        const n = parseFloat(v);
        return isNaN(n) ? 0 : n;
      })();
      if (gramosNum <= 0) return alert('Indica los gramos.');

      try {
        const r = await postForm(API.add, {
          alimento_id: String(alimentoSeleccionado.id),
          gramos: String(gramosNum)
        });
        if (!r.ok) {
          alert('No se pudo añadir: ' + (r.error || 'Error desconocido'));
          return;
        }
        resetSeleccion();
        await cargarIngredientes();
      } catch (err) {
        console.error(err);
        alert('Error de red al añadir.');
      }
    });

    listaIngredientes.addEventListener('click', (e) => {
      const row = e.target.closest('.ingrediente-row');
      if (!row) return;
      ingredienteActualId = row.dataset.id;
      modalNombre.textContent = row.dataset.nombre;
      modalGramos.value = row.dataset.gramos;
      modal.show();
    });

    btnGuardarIng.addEventListener('click', async () => {
      if (!ingredienteActualId) return;
      const gramos = parseFloat((modalGramos.value || '').toString().replace(',', '.'));
      if (!gramos || gramos <= 0) return alert('Introduce una cantidad válida.');
      try {
        const r = await postForm(`${API.editBase}/${ingredienteActualId}/edit`, { gramos: String(gramos) });
        if (r.ok) {
          modal.hide();
          await cargarIngredientes();
        } else {
          alert('No se pudo actualizar: ' + (r.error || 'Error desconocido'));
        }
      } catch (err) {
        console.error(err);
        alert('Error de red al actualizar.');
      }
    });

    btnEliminarIng.addEventListener('click', async () => {
      if (!ingredienteActualId) return;
      if (!confirm('¿Eliminar este ingrediente?')) return;
      try {
        const r = await postForm(`${API.delBase}/${ingredienteActualId}/delete`, {});
        if (r.ok) {
          modal.hide();
          await cargarIngredientes();
        } else {
          alert('No se pudo eliminar.');
        }
      } catch (err) {
        console.error(err);
        alert('Error de red al eliminar.');
      }
    });

    cargarIngredientes();

    // ---- Proporciones / unidades (inline, sin salir de la pantalla) ----
    if (ALIVIRT_ID > 0) {
      const propDescripcion = document.getElementById('propDescripcion');
      const propGramos = document.getElementById('propGramos');
      const propPredeterminada = document.getElementById('propPredeterminada');
      const btnAgregarProp = document.getElementById('btnAgregarProp');
      const listaProporciones = document.getElementById('listaProporciones');

      const modalPropEl = document.getElementById('modalEditarProporcion');
      const modalProp = new bootstrap.Modal(modalPropEl);
      const modalPropDescripcion = document.getElementById('modalPropDescripcion');
      const modalPropGramos = document.getElementById('modalPropGramos');
      const modalPropPredeterminada = document.getElementById('modalPropPredeterminada');
      const btnGuardarProp = document.getElementById('btnGuardarProp');
      const btnEliminarProp = document.getElementById('btnEliminarProp');
      let proporcionActualId = null;

      const renderProporciones = (rows) => {
        if (!rows || rows.length === 0) {
          listaProporciones.innerHTML = `<div class="alert alert-light border py-2 mb-0">Aún no hay proporciones definidas para esta receta.</div>`;
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
          const rows = await getJson(API.propsListar);
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
          const r = await postForm(API.propsAdd, {
            alimento_id: String(ALIVIRT_ID),
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
          const r = await postForm(`${API.propsEditBase}/${proporcionActualId}/update`, {
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
          const r = await postForm(`${API.propsDelBase}/${proporcionActualId}/delete`, {});
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
    }
  });
  <?php endif; ?>
</script>
<?php $this->endSection(); ?>
