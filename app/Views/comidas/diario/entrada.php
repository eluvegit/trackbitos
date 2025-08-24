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

        .keyboard {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            /* 10 teclas en la fila superior */
            gap: .3rem;
            margin-bottom: 1rem;
        }

        .keyboard .kb-key {
            padding: .6rem .4rem;
            font-size: 1.1rem;
            text-align: center;
        }

        /* segunda fila: 10 teclas */
        .keyboard .row-2 {
            grid-column: span 10;
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: .3rem;
        }

        /* tercera fila: 9 teclas + borrar/CLR */
        .keyboard .row-3 {
            grid-column: span 10;
            display: grid;
            grid-template-columns: repeat(9, 1fr) auto auto;
            gap: .3rem;
        }

        /* cuarta fila: espacio ocupa todo */
        .keyboard .row-4 {
            grid-column: span 10;
        }

        .keyboard .kb-space {
            width: 100%;
            height: 50px;
            font-size: 1.2rem;
        }


        #resultados .list-group-item {
            cursor: pointer;
        }

        #resultados .list-group-item.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>

    <div class="container pb-3">

        <!-- Selección de tipo -->
        <h5 class="mb-3">Selecciona el tipo de comida</h5>
        <div class="row g-2 mb-2" id="tipoSelector">
            <?php foreach (['almuerzo' => 'Almuerzo', 'cena' => 'Cena', 'nocturna' => 'Nocturna', 'desayuno' => 'Desayuno', 'merienda' => 'Merienda'] as $k => $v): ?>
                <div class="col-6 col-md-4">
                    <div class="tipo-card" data-tipo="<?= $k ?>"><?= esc($v) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Buscador -->
        <div id="buscadorWrapper" class="d-none mt-3">
            <h5 class="mb-3">Buscar alimento</h5>

            <div class="keyboard">
                <!-- Fila 1 -->
                <?php foreach (['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'] as $l): ?>
                    <button type="button" class="btn btn-outline-secondary kb-key"><?= $l ?></button>
                <?php endforeach; ?>

                <!-- Fila 2 -->
                <div class="row-2">
                    <?php foreach (['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Ñ'] as $l): ?>
                        <button type="button" class="btn btn-outline-secondary kb-key"><?= $l ?></button>
                    <?php endforeach; ?>
                </div>

                <!-- Fila 3 -->
                <div class="row-3">
                    <?php foreach (['Z', 'X', 'C', 'V', 'B', 'N', 'M'] as $l): ?>
                        <button type="button" class="btn btn-outline-secondary kb-key"><?= $l ?></button>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-outline-dark kb-key">←</button>
                    <button type="button" class="btn btn-outline-danger kb-key">CLR</button>
                </div>

                <!-- Fila 4 -->
                <div class="row-4">
                    <button type="button" class="btn btn-outline-dark kb-key kb-space">ESP</button>
                </div>
            </div>


            <input type="text" id="buscador" class="form-control mb-3" placeholder="Escribe o pulsa letras..." />

            <ul id="resultados" class="list-group mb-3"></ul>

            <form id="formAdd" class="d-none">
                <?= csrf_field() ?>
                <input type="hidden" name="fecha" value="<?= esc($fechaSel->format('Y-m-d')) ?>">
                <input type="hidden" name="tipo" id="inputTipo">
                <input type="hidden" name="item_id" id="inputItem">

                <div class="alert alert-info py-2 px-3 mb-3 d-flex justify-content-between align-items-center" id="selectedInfo" style="display:none;">
                    <span id="selectedName"></span>
                    <button type="button" class="btn-close" id="clearSelected" aria-label="Borrar"></button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cantidad (g)</label>
                    <input type="number" name="cantidad_gramos" id="inputGramos" class="form-control" placeholder="Ej. 100">
                </div>

                <div class="mb-3" id="porcionesWrapper">
                    <label class="form-label">Porción</label>
                    <select name="porcion_id" id="selectPorcion" class="form-select">
                        <option value="">-- Selecciona porción --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nº porciones</label>
                    <input type="number" step="0.25" min="0.25" name="porciones" id="inputPorciones" class="form-control" value="1">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Agregar</button>
                </div>

            </form>


            <div id="listaIngestas" class="mt-4"></div>
        </div>

        <div class="mt-4">
            <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d')) ?>" class="btn btn-outline-dark w-100">← Volver al día</a>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // === Endpoints ===
            const API = {
                buscar: '<?= site_url('api/alimentos') ?>',
                porcionesBase: '<?= site_url('comidas/diario/porciones') ?>',
                ingestasBase: '<?= site_url('api/ingestas') ?>', // /{fecha}/{tipo}
                add: '<?= site_url('api/add') ?>',
                delBase: '<?= site_url('api/delete') ?>', // /{id}
            };

            // === Elements ===
            const tipoSelector = document.getElementById('tipoSelector');
            const buscadorWrapper = document.getElementById('buscadorWrapper');
            const teclado = document.querySelector('.keyboard');
            const inputBuscar = document.getElementById('buscador');
            const listaResultados = document.getElementById('resultados');

            const formAdd = document.getElementById('formAdd');
            const inputFecha = formAdd.querySelector('input[name="fecha"]');
            const inputTipo = document.getElementById('inputTipo');
            const inputItem = document.getElementById('inputItem');
            const inputGramos = document.getElementById('inputGramos');
            const selectPorcion = document.getElementById('selectPorcion');
            const inputPorciones = document.getElementById('inputPorciones');
            const porcionesWrapper = document.getElementById('porcionesWrapper');

            const selectedInfo = document.getElementById('selectedInfo');
            const selectedName = document.getElementById('selectedName');
            const clearSelected = document.getElementById('clearSelected');

            const listaIngestas = document.getElementById('listaIngestas');

            // CSRF (desde el hidden que pone <?= csrf_field() ?>)
            const csrfInput = formAdd.querySelector('input[name="<?= csrf_token() ?>"]') || formAdd.querySelector('input[type="hidden"]');

            // === Estado ===
            let tipoActual = null;
            let alimentoSeleccionado = null; // { id, nombre }

            // === Utils ===
            const fmt1 = n => (Math.round(n * 10) / 10).toFixed(1);
            const toQuery = params => new URLSearchParams(params).toString();
            const postForm = async (url, data) => {
                // añade CSRF
                if (csrfInput && csrfInput.name && csrfInput.value) {
                    data[csrfInput.name] = csrfInput.value;
                }
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
                document.querySelectorAll('.tipo-card').forEach(c => {
                    c.classList.toggle('active', c.dataset.tipo === tipo);
                });
            };

            const resetSeleccionAlimento = () => {
                alimentoSeleccionado = null;
                inputItem.value = '';
                selectedInfo.style.display = 'none';
                selectedName.textContent = '';
                formAdd.classList.add('d-none');
                selectPorcion.innerHTML = '<option value="">-- Selecciona porción --</option>';
                inputPorciones.value = '1';
                inputGramos.value = '';
                inputGramos.disabled = false;
            };

            const activarForm = () => {
                formAdd.classList.remove('d-none');
                selectedInfo.style.display = 'flex';
            };

            // Antes deshabilitaba gramos si había porción seleccionada.
            // Ahora no bloqueamos nada: el usuario decide qué rellenar.
            const toggleGramosPorcion = () => {};


            // === Render listado de ingestas ===
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
            <tr>
            <td>${r.nombre || '—'}</td>
            <td class="text-end">${fmt1(g)} g</td>
            <td class="text-end">${fmt1(kcal)}</td>
            <td class="text-end">${fmt1(pr)}</td>
            <td class="text-end">${fmt1(ch)}</td>
            <td class="text-end">${fmt1(gr)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger btn-del" data-id="${r.id}">X</button>
            </td>
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

            // === Búsqueda ===
            const pintarResultados = (rows) => {
                if (!rows || rows.length === 0) {
                    listaResultados.innerHTML = `<li class="list-group-item">Sin resultados…</li>`;
                    return;
                }
                listaResultados.innerHTML = rows.map(r =>
                    `<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${r.id}" data-name="${r.nombre}">
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

            // === Cargar porciones del alimento seleccionado ===
            const cargarPorciones = async (alimentoId) => {
                selectPorcion.innerHTML = '<option value="">Cargando…</option>';
                try {
                    const rows = await getJson(`${API.porcionesBase}/${alimentoId}`);
                    if (!rows || rows.length === 0) {
                        selectPorcion.innerHTML = '<option value="">(Sin porciones)</option>';
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
                    if (defaultId) selectPorcion.value = defaultId;

                    // Ya no llamamos a toggleGramosPorcion porque no queremos bloquear gramos.
                } catch (e) {
                    console.error(e);
                    selectPorcion.innerHTML = '<option value="">Error cargando porciones</option>';
                }
            };

            // === Autoselección de tipo desde la URL ===
            const tiposValid = new Set(['desayuno', 'almuerzo', 'merienda', 'cena', 'nocturna']);

            // último segmento del path (lowercase)
            const pathParts = window.location.pathname.toLowerCase().split('/').filter(Boolean);
            const tail = pathParts[pathParts.length - 1] || '';

            // o bien ?tipo=xxx como fallback
            const qp = new URLSearchParams(window.location.search);
            const qTipo = (qp.get('tipo') || '').toLowerCase();

            const initialTipo = tiposValid.has(tail) ? tail : (tiposValid.has(qTipo) ? qTipo : null);

            if (initialTipo) {
                // refleja estado en UI y estado interno, como si hubieras hecho click en la tarjeta
                tipoActual = initialTipo;
                inputTipo.value = initialTipo;
                setTipoActivoUI(initialTipo);
                buscadorWrapper.classList.remove('d-none');
                resetSeleccionAlimento();
                listaResultados.innerHTML = '';
                inputBuscar.value = '';
                cargarIngestas();

                // opcional: scroll al buscador
                // document.getElementById('buscador')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            // === Eventos ===

            // Selección de tipo
            tipoSelector.addEventListener('click', (e) => {
                const card = e.target.closest('.tipo-card');
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

            // Teclado en pantalla
            teclado.addEventListener('click', (e) => {
                const btn = e.target.closest('.kb-key');
                if (!btn) return;
                const label = btn.textContent.trim();
                if (label === 'CLR') {
                    inputBuscar.value = '';
                } else if (label === '←') {
                    inputBuscar.value = inputBuscar.value.slice(0, -1);
                } else if (label === 'ESP') {
                    inputBuscar.value += ' ';
                } else {
                    inputBuscar.value += label;
                }
                inputBuscar.focus();
                buscarDebounced(inputBuscar.value);
            });

            // Búsqueda por teclado físico
            inputBuscar.addEventListener('input', (e) => buscarDebounced(e.target.value));

            // Elegir un resultado
            listaResultados.addEventListener('click', async (e) => {
                const li = e.target.closest('.list-group-item');
                if (!li || !li.dataset.id) return;

                // Marcar activo (opcional)
                listaResultados.querySelectorAll('.list-group-item').forEach(x => x.classList.remove('active'));
                li.classList.add('active');

                // Guardar selección
                alimentoSeleccionado = {
                    id: parseInt(li.dataset.id, 10),
                    nombre: li.dataset.name
                };
                inputItem.value = String(alimentoSeleccionado.id);
                selectedName.textContent = alimentoSeleccionado.nombre;
                activarForm();

                // Cargar porciones del alimento
                await cargarPorciones(alimentoSeleccionado.id);

                // ✅ Limpiar la lista de búsqueda para que no estorbe
                listaResultados.innerHTML = '';
                // (opcional) Ocultar completamente el UL si prefieres:
                // listaResultados.classList.add('d-none');
            });


            // Limpiar selección
            clearSelected.addEventListener('click', () => {
                resetSeleccionAlimento();
                listaResultados.querySelectorAll('.list-group-item').forEach(x => x.classList.remove('active'));
            });

            // Cambiar porción => deshabilita/activa gramos
            selectPorcion.addEventListener('change', toggleGramosPorcion);


            // Enviar alta
            formAdd.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!tipoActual) {
                    alert('Selecciona un tipo de comida.');
                    return;
                }
                if (!inputItem.value) {
                    alert('Selecciona un alimento.');
                    return;
                }

                // Normaliza gramos (admite coma)
                const gramosNum = (() => {
                    const v = (inputGramos.value || '').toString().replace(',', '.').trim();
                    const n = parseFloat(v);
                    return isNaN(n) ? 0 : n;
                })();

                // Lógica de elección:
                // - Si el usuario escribe gramos > 0, usamos gramos y anulamos porciones.
                // - Si no hay gramos pero hay porción seleccionada, usamos porciones.
                // - Si no hay ninguno, pedimos que elija.
                let data = {
                    fecha: inputFecha.value,
                    tipo: inputTipo.value,
                    item_id: inputItem.value,
                    cantidad_gramos: '',
                    porcion_id: '',
                    porciones: ''
                };

                if (gramosNum > 0) {
                    data.cantidad_gramos = String(gramosNum);
                    data.porcion_id = '';
                    data.porciones = '';
                } else if (selectPorcion.value) {
                    data.cantidad_gramos = '';
                    data.porcion_id = selectPorcion.value;
                    data.porciones = inputPorciones.value || '1';
                } else {
                    alert('Indica una cantidad en gramos o selecciona una porción.');
                    return;
                }

                try {
                    const r = await postForm(API.add, data);
                    if (!r.ok) {
                        console.error(r);
                        alert('No se pudo agregar: ' + (r.error || 'Error desconocido'));
                        return;
                    }
                    await cargarIngestas();
                    // Reset suave
                    inputGramos.value = '';
                    // Si quieres, también puedes limpiar la porción seleccionada:
                    // selectPorcion.value = '';
                    inputPorciones.value = '1';
                } catch (err) {
                    console.error(err);
                    alert('Error de red al guardar.');
                }
            });


            // Borrar registro
            listaIngestas.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-del');
                if (!btn) return;
                const id = btn.dataset.id;
                if (!confirm('¿Eliminar este registro?')) return;
                try {
                    const r = await postForm(`${API.delBase}/${id}`, {});
                    if (r.ok) {
                        await cargarIngestas();
                    } else {
                        alert('No se pudo eliminar.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Error de red al eliminar.');
                }
            });

            // (Opcional) Si quieres arrancar con un tipo por defecto, descomenta:
            // document.querySelector('.tipo-card[data-tipo="almuerzo"]')?.click();
        });
    </script>



    <?= $this->endSection(); ?>