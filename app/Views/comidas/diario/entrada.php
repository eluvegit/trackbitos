<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>

<style>
    .tipo-card {
        cursor: pointer;
        border: 2px solid #dee2e6;
        border-radius: 1rem;
        padding: .5rem;
        text-align: center;
        font-weight: 600;
        transition: all .2s;
    }

    .tipo-card.active,
    .tipo-card:hover {
        border-color: #0d6efd;
        background: #e7f1ff;
    }

    #resultados .list-group-item {
        cursor: pointer;
    }

    #resultados .list-group-item.active {
        background-color: #0d6efd;
        color: #fff;
    }
</style>


    <!-- Selección de tipo -->
    <h5 class="">
        <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d')) ?>" class="btn btn-outline-dark w-100"><span class="mb-2 h6 text-muted"><?= $fechaSel->format('d/m/Y') ?></span></a>
    </h5>
    <div class="row g-2" id="tipoSelector">
        <?php foreach (['almuerzo' => 'Almuerzo', 'cena' => 'Cena', 'nocturna' => 'Nocturna', 'desayuno' => 'Desayuno', 'merienda' => 'Merienda'] as $k => $v): ?>
            <div class="col-4 col-md-2">
                <div
                    class="card text-center fw-semibold py-2 rounded-4 border user-select-none"
                    role="button"
                    data-tipo="<?= $k ?>">
                    <?= esc($v) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <!-- Buscador -->
    <div id="buscadorWrapper" class="d-none mt-3">
        <div class="input-group mb-1">
            <input type="text" id="buscador" class="form-control" placeholder="Escribe para buscar…" />
            <button type="button" class="btn btn-outline-secondary" id="btnClr">CLR</button>
        </div>

        <ul id="resultados" class="list-group mb-3"></ul>

        <form id="formAdd" class="d-none">
            <?= csrf_field() ?>
            <input type="hidden" name="fecha" value="<?= esc($fechaSel->format('Y-m-d')) ?>">
            <input type="hidden" name="tipo" id="inputTipo">
            <input type="hidden" name="item_id" id="inputItem">

            <!-- Info alimento seleccionado -->
            <div class="alert alert-info py-2 px-3 mb-2 d-flex justify-content-between align-items-start" id="selectedInfo" style="display:none;">
                <div class="me-2">
                    <div class="fw-semibold" id="selectedName"></div>
                    <div class="small text-muted" id="selectedMacros"><!-- macros render --></div>
                </div>
                <button type="button" class="btn-close" id="clearSelected" aria-label="Borrar"></button>
            </div>

            <!-- Cantidad y Porción en la misma fila -->
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="form-label small mb-1">Cantidad (g)</label>
                    <input type="number" step="0.1" name="cantidad_gramos" id="inputGramos" class="form-control form-control-sm" placeholder="Ej. 100">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1">Porción</label>
                    <select name="porcion_id" id="selectPorcion" class="form-select form-select-sm">
                        <option value="">-- Selecciona porción --</option>
                    </select>
                </div>
            </div>

            <!-- Nº porciones y botón en la misma fila -->
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-6">
                    <label class="form-label small mb-1">Nº porciones</label>
                    <input type="number" step="0.25" min="0.25" name="porciones" id="inputPorciones" class="form-control form-control-sm" value="1">
                </div>
                <div class="col-6 d-flex">
                    <button type="submit" class="btn btn-primary flex-fill btn-sm">Agregar</button>
                </div>
            </div>
        </form>




        <div id="listaIngestas" class=""></div>
    </div>

    <!-- Modal editar/eliminar registro -->
    <div class="modal fade" id="modalEditarIngesta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarNombre">Editar registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small mb-1">Cantidad (g)</label>
                    <input type="number" step="0.1" min="0" id="modalEditarCantidad" class="form-control">
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="btnEliminarIngesta">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarIngesta">Guardar</button>
                </div>
            </div>
        </div>
    </div>

<style>
    /* Ajustes compactos para móvil */
    @media (max-width: 576px) {
        #formAdd .form-label {
            font-size: 0.8rem;
        }

        #formAdd .form-control,
        #formAdd .form-select {
            padding: 0.35rem 0.5rem;
            font-size: 0.85rem;
        }

        #formAdd .alert {
            padding: 0.3rem 0.5rem;
            font-size: 0.85rem;
        }
    }
    table tr{
        font-size:0.8em;
    }
    .ingesta-row {
        cursor: pointer;
    }
    .ingesta-row:hover {
        background-color: var(--bs-tertiary-bg);
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // === Endpoints ===
        const API = {
            buscar: '<?= site_url('api/alimentos') ?>', // GET ?q=
            alimentoBase: '<?= site_url('api/alimentos') ?>', // GET /{id}
            porcionesBase: '<?= site_url('comidas/diario/porciones') ?>',
            ingestasBase: '<?= site_url('api/ingestas') ?>', // /{fecha}/{tipo}
            add: '<?= site_url('api/add') ?>',
            delBase: '<?= site_url('api/delete') ?>', // /{id}
            editBase: '<?= site_url('api/edit') ?>', // /{id}
        };

        // === Elements ===
        const tipoSelector = document.getElementById('tipoSelector');
        const buscadorWrapper = document.getElementById('buscadorWrapper');
        const inputBuscar = document.getElementById('buscador');
        const btnClr = document.getElementById('btnClr');
        const listaResultados = document.getElementById('resultados');

        const formAdd = document.getElementById('formAdd');
        const inputFecha = formAdd.querySelector('input[name="fecha"]');
        const inputTipo = document.getElementById('inputTipo');
        const inputItem = document.getElementById('inputItem');
        const inputGramos = document.getElementById('inputGramos');
        const selectPorcion = document.getElementById('selectPorcion');
        const inputPorciones = document.getElementById('inputPorciones');

        const selectedInfo = document.getElementById('selectedInfo');
        const selectedName = document.getElementById('selectedName');
        const selectedMacros = document.getElementById('selectedMacros');
        const clearSelected = document.getElementById('clearSelected');

        const listaIngestas = document.getElementById('listaIngestas');

        // Modal editar/eliminar
        const modalEditarEl = document.getElementById('modalEditarIngesta');
        const modalEditar = new bootstrap.Modal(modalEditarEl);
        const modalEditarNombre = document.getElementById('modalEditarNombre');
        const modalEditarCantidad = document.getElementById('modalEditarCantidad');
        const btnGuardarIngesta = document.getElementById('btnGuardarIngesta');
        const btnEliminarIngesta = document.getElementById('btnEliminarIngesta');
        let ingestaActualId = null;

        // CSRF (desde el hidden de <?= csrf_field() ?>)
        const csrfInput = formAdd.querySelector('input[name="<?= csrf_token() ?>"]') || formAdd.querySelector('input[type="hidden"]');

        // === Estado ===
        let tipoActual = null;
        let alimentoSeleccionado = null; // { id, nombre, macros:{kcal,p,c,g} }
        let porcionEquivGr = null; // gramos equivalentes de la porción activa (si la hay)

        // === Utils ===
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
        const getJson = async (url) => (await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })).json();

        const debounce = (fn, ms = 250) => {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), ms);
            };
        };

        const setTipoActivoUI = (tipo) => {
            document.querySelectorAll('[data-tipo]').forEach(c => {
                const activo = c.dataset.tipo === tipo;
                c.classList.toggle('border-primary', activo);
                c.classList.toggle('bg-primary-subtle', activo);
                c.classList.toggle('text-primary', activo);
            });
        };


        const activarForm = () => {
            formAdd.classList.remove('d-none');
            selectedInfo.style.display = 'flex';
        };

        // === Render macros en la cajita ===
        function renderSelectedMacrosPreview() {
            if (!alimentoSeleccionado || !alimentoSeleccionado.macros) {
                selectedMacros.innerHTML = '';
                return;
            }
            const {
                kcal,
                p,
                c,
                g
            } = alimentoSeleccionado.macros; // por 100 g

            // Cantidad activa: o bien gramos, o porción
            let gramosActivos = 0;
            const gramosInput = parseFloat((inputGramos.value || '').toString().replace(',', '.')) || 0;

            if (gramosInput > 0) {
                gramosActivos = gramosInput;
            } else if (porcionEquivGr && parseFloat(inputPorciones.value || '1') > 0) {
                gramosActivos = parseFloat(porcionEquivGr) * parseFloat(inputPorciones.value || '1');
            }

            let html = `<div><strong>Por 100 g:</strong> ${fmt0(kcal)} kcal · ${fmt1(p)} g P · ${fmt1(c)} g C · ${fmt1(g)} g G</div>`;

            if (gramosActivos > 0) {
                const factor = gramosActivos / 100;
                html += `<div><strong>Para ${fmt1(gramosActivos)} g:</strong> ${fmt0(kcal * factor)} kcal · ${fmt1(p * factor)} g P · ${fmt1(c * factor)} g C · ${fmt1(g * factor)} g G</div>`;
            }

            selectedMacros.innerHTML = html;
        }

        const resetSeleccionAlimento = () => {
            alimentoSeleccionado = null;
            inputItem.value = '';
            selectedInfo.style.display = 'none';
            selectedName.textContent = '';
            selectedMacros.innerHTML = '';

            formAdd.classList.add('d-none');
            selectPorcion.innerHTML = '<option value="">-- Selecciona porción --</option>';
            inputPorciones.value = '1';
            inputGramos.value = '';
            inputGramos.disabled = false;
            porcionEquivGr = null;
        };

        const renderIngestas = (rows) => {
            if (!rows || rows.length === 0) {
                listaIngestas.innerHTML = `<div class="alert alert-light border">No hay registros para este periodo.</div>`;
                return;
            }
            let tot = {
                kcal: 0,
                p: 0,
                c: 0,
                g: 0
            };
            const filas = rows.map(r => {
                const g = parseFloat(r.cantidad_gramos || 0) || 0;
                const factor = g / 100;
                const kcal = (parseFloat(r.kcal || 0) * factor) || 0;
                const pr = (parseFloat(r.proteina_g || 0) * factor) || 0;
                const ch = (parseFloat(r.carbohidratos_g || 0) * factor) || 0;
                const gr = (parseFloat(r.grasas_g || 0) * factor) || 0;
                tot.kcal += kcal;
                tot.p += pr;
                tot.c += ch;
                tot.g += gr;
                return `
                <tr class="ingesta-row" role="button" data-id="${r.id}" data-nombre="${(r.nombre || '—').replace(/"/g, '&quot;')}" data-cantidad="${g}">
                    <td>${r.nombre || '—'}</td>
                    <td class="text-end">${fmt1(g)} g</td>
                    <td class="text-end">${fmt1(kcal)}</td>
                    <td class="text-end">${fmt1(pr)}</td>
                    <td class="text-end">${fmt1(ch)}</td>
                    <td class="text-end">${fmt1(gr)}</td>
                    <td class="text-end"><i class="bi bi-pencil text-muted"></i></td>
                </tr>`;
            }).join('');
            listaIngestas.innerHTML = `
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Alimento</th>
                            <th class="text-end">Gr</th>
                            <th class="text-end">KC</th>
                            <th class="text-end">Pr</th>
                            <th class="text-end">Ca</th>
                            <th class="text-end">Gr</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>Totales</th>
                            <th></th>
                            <th class="text-end">${fmt1(tot.kcal)}</th>
                            <th class="text-end">${fmt1(tot.p)}</th>
                            <th class="text-end">${fmt1(tot.c)}</th>
                            <th class="text-end">${fmt1(tot.g)}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>`;
        };

        const cargarIngestas = async () => {
            if (!tipoActual) return;
            const fecha = inputFecha.value;
            const url = `${API.ingestasBase}/${encodeURIComponent(fecha)}/${encodeURIComponent(tipoActual)}`;
            try {
                const rows = await getJson(url);
                renderIngestas(rows);
            } catch (e) {
                console.error(e);
                listaIngestas.innerHTML = `<div class="alert alert-danger">Error al cargar ingestas.</div>`;
            }
        };

        const pintarResultados = (rows) => {
            if (!rows || rows.length === 0) {
                listaResultados.innerHTML = `<li class="list-group-item">Sin resultados…</li>`;
                return;
            }
            listaResultados.innerHTML = rows.map(r =>
                `<li class="list-group-item d-flex justify-content-between align-items-center"
                 data-id="${r.id}" data-name="${r.nombre}">
                <span>${r.nombre}</span>
                <span class="badge text-bg-light">#${r.id}</span>
            </li>`
            ).join('');
        };

        const buscar = async (q) => {
            q = (q || '').trim();
            if (q.length < 1) {
                listaResultados.innerHTML = '';
                return;
            }
            const url = `${API.buscar}?${toQuery({ q })}`;
            try {
                const rows = await getJson(url);
                pintarResultados(rows);
            } catch (e) {
                console.error(e);
                listaResultados.innerHTML = `<li class="list-group-item text-danger">Error buscando…</li>`;
            }
        };
        const buscarDebounced = debounce(buscar, 200);

        const cargarPorciones = async (alimentoId) => {
            selectPorcion.innerHTML = '<option value="">Cargando…</option>';
            porcionEquivGr = null;
            try {
                const rows = await getJson(`${API.porcionesBase}/${alimentoId}`);
                if (!rows || rows.length === 0) {
                    selectPorcion.innerHTML = '<option value="">(Sin porciones)</option>';
                    renderSelectedMacrosPreview();
                    return;
                }
                let html = '<option value="">-- Selecciona porción --</option>';
                let defaultId = '';
                rows.forEach(p => {
                    const desc = p.descripcion || 'Porción';
                    const g = (p.gramos_equivalentes ?? null);
                    const extra = (g && g > 0) ? ` (${g} g)` : '';
                    html += `<option value="${p.id}" data-g="${g||''}" ${p.es_predeterminada ? 'data-default="1"' : ''}>${desc}${extra}</option>`;
                    if (p.es_predeterminada) defaultId = String(p.id);
                });
                selectPorcion.innerHTML = html;
                if (defaultId) {
                    selectPorcion.value = defaultId;
                    const opt = selectPorcion.selectedOptions[0];
                    porcionEquivGr = opt?.dataset?.g || null;
                }
            } catch (e) {
                console.error(e);
                selectPorcion.innerHTML = '<option value="">Error cargando porciones</option>';
            } finally {
                renderSelectedMacrosPreview();
            }
        };

        // Autoselección de tipo por URL (?tipo= o última parte del path)
        const tiposValid = new Set(['desayuno', 'almuerzo', 'merienda', 'cena', 'nocturna']);
        const pathParts = window.location.pathname.toLowerCase().split('/').filter(Boolean);
        const tail = pathParts[pathParts.length - 1] || '';
        const qp = new URLSearchParams(window.location.search);
        const qTipo = (qp.get('tipo') || '').toLowerCase();
        const initialTipo = tiposValid.has(tail) ? tail : (tiposValid.has(qTipo) ? qTipo : null);

        if (initialTipo) {
            tipoActual = initialTipo;
            inputTipo.value = initialTipo;
            setTipoActivoUI(initialTipo);
            buscadorWrapper.classList.remove('d-none');
            resetSeleccionAlimento();
            listaResultados.innerHTML = '';
            inputBuscar.value = '';
            cargarIngestas();
        }

        // Eventos UI tipo
        tipoSelector.addEventListener('click', (e) => {
            const card = e.target.closest('[data-tipo]');
            if (!card) return;
            tipoActual = card.dataset.tipo;
            inputTipo.value = tipoActual;
            setTipoActivoUI(tipoActual);
            buscadorWrapper.classList.remove('d-none');
            resetSeleccionAlimento();
            listaResultados.innerHTML = '';
            inputBuscar.value = '';
            cargarIngestas();
        });

        // CLR buscador
        btnClr.addEventListener('click', () => {
            inputBuscar.value = '';
            listaResultados.innerHTML = '';
            resetSeleccionAlimento();
            inputBuscar.focus();
        });

        // Búsqueda
        inputBuscar.addEventListener('input', (e) => buscarDebounced(e.target.value));

        // Elegir resultado → carga detalle con macros
        listaResultados.addEventListener('click', async (e) => {
            const li = e.target.closest('.list-group-item');
            if (!li || !li.dataset.id) return;

            listaResultados.querySelectorAll('.list-group-item').forEach(x => x.classList.remove('active'));
            li.classList.add('active');

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

            inputItem.value = String(alimentoSeleccionado.id);
            selectedName.textContent = alimentoSeleccionado.nombre;
            activarForm();
            renderSelectedMacrosPreview();

            await cargarPorciones(alimentoSeleccionado.id);
            listaResultados.innerHTML = '';
        });

        // Recalcular preview al cambiar gramos / porción / nº porciones
        inputGramos.addEventListener('input', renderSelectedMacrosPreview);
        selectPorcion.addEventListener('change', () => {
            const opt = selectPorcion.selectedOptions[0];
            porcionEquivGr = opt?.dataset?.g || null;
            renderSelectedMacrosPreview();
        });
        inputPorciones.addEventListener('input', renderSelectedMacrosPreview);

        // Limpiar selección
        clearSelected.addEventListener('click', () => {
            resetSeleccionAlimento();
            listaResultados.querySelectorAll('.list-group-item').forEach(x => x.classList.remove('active'));
        });

        // Enviar alta
        formAdd.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!tipoActual) return alert('Selecciona un tipo de comida.');
            if (!inputItem.value) return alert('Selecciona un alimento.');

            const gramosNum = (() => {
                const v = (inputGramos.value || '').toString().replace(',', '.').trim();
                const n = parseFloat(v);
                return isNaN(n) ? 0 : n;
            })();

            const tiposValidos = ['desayuno', 'almuerzo', 'merienda', 'cena', 'nocturna'];
            const tipoPost = (tipoActual || inputTipo.value || '').toLowerCase();
            if (!tiposValidos.includes(tipoPost)) {
                alert('Tipo de comida inválido.');
                return;
            }

            let data = {
                fecha: inputFecha.value,
                tipo: tipoPost,
                item_id: inputItem.value,
                cantidad_gramos: '',
                porcion_id: '',
                porciones: ''
            };

            if (gramosNum > 0) {
                data.cantidad_gramos = String(gramosNum);
            } else if (selectPorcion.value) {
                data.porcion_id = selectPorcion.value;
                data.porciones = inputPorciones.value || '1';
            } else {
                return alert('Indica una cantidad en gramos o selecciona una porción.');
            }

            try {
                const r = await postForm(API.add, data);
                if (!r.ok) {
                    console.error(r);
                    alert('No se pudo agregar: ' + (r.error || 'Error desconocido'));
                    return;
                }
                await cargarIngestas();
                inputGramos.value = '';
                inputPorciones.value = '1';
                renderSelectedMacrosPreview();
            } catch (err) {
                console.error(err);
                alert('Error de red al guardar.');
            }
        });

        // Abrir modal de edición/eliminación al pulsar una fila
        listaIngestas.addEventListener('click', (e) => {
            const row = e.target.closest('.ingesta-row');
            if (!row) return;
            ingestaActualId = row.dataset.id;
            modalEditarNombre.textContent = row.dataset.nombre;
            modalEditarCantidad.value = row.dataset.cantidad;
            modalEditar.show();
        });

        // Guardar cantidad editada
        btnGuardarIngesta.addEventListener('click', async () => {
            if (!ingestaActualId) return;
            const cantidad = parseFloat((modalEditarCantidad.value || '').toString().replace(',', '.'));
            if (!cantidad || cantidad <= 0) {
                alert('Introduce una cantidad válida.');
                return;
            }
            try {
                const r = await postForm(`${API.editBase}/${ingestaActualId}`, {
                    cantidad_gramos: String(cantidad)
                });
                if (r.ok) {
                    modalEditar.hide();
                    await cargarIngestas();
                } else {
                    alert('No se pudo actualizar: ' + (r.error || 'Error desconocido'));
                }
            } catch (err) {
                console.error(err);
                alert('Error de red al actualizar.');
            }
        });

        // Eliminar registro
        btnEliminarIngesta.addEventListener('click', async () => {
            if (!ingestaActualId) return;
            if (!confirm('¿Eliminar este registro?')) return;
            try {
                const r = await postForm(`${API.delBase}/${ingestaActualId}`, {});
                if (r.ok) {
                    modalEditar.hide();
                    await cargarIngestas();
                } else {
                    alert('No se pudo eliminar.');
                }
            } catch (err) {
                console.error(err);
                alert('Error de red al eliminar.');
            }
        });
    });
</script>

<?= $this->endSection(); ?>