<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-3">🗂️ <?= esc($plantilla['nombre']) ?></h2>
<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="<?= site_url('gimnasio/plantillas') ?>" class="btn btn-sm btn-outline-secondary">← Volver a plantillas</a>
</div>

<?php if (session()->getFlashdata('mensaje')): ?>
    <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('mensaje')) ?></div>
<?php endif; ?>

<!-- Renombrar / notas (colapsado) -->
<div class="text-center mb-2">
    <a href="#" id="toggle-datos-plantilla">📋 Renombrar / notas</a>
</div>
<div id="bloque-datos-plantilla" class="card mb-4 d-none">
    <div class="card-header">📝 Datos de la plantilla</div>
    <form method="post" action="<?= site_url('gimnasio/plantillas/renombrar/' . $plantilla_id) ?>">
        <div class="card-body">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= esc($plantilla['nombre']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control" rows="2"><?= esc($plantilla['notas'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary btn-sm">Guardar</button>
        </div>
    </form>
</div>
<script>
document.getElementById('toggle-datos-plantilla').addEventListener('click', function(e) {
    e.preventDefault();
    let bloque = document.getElementById('bloque-datos-plantilla');
    bloque.classList.toggle('d-none');
    this.textContent = bloque.classList.contains('d-none') ? '📋 Renombrar / notas' : '📋 Ocultar';
});
</script>

<!-- Lista de ejercicios / series -->
<h4 class="mb-3">🏋️ Ejercicios de la plantilla</h4>
<div id="listaEjercicios">
    <?php foreach ($ejerciciosAgrupados as $idx => $g): ?>
        <div class="ej-block" data-pe-id="<?= $g['pe_id'] ?>">
            <div class="ej-block-header">
                <div class="ej-block-titulo">
                    <div class="fw-bold"><?= esc($g['ejercicio_nombre']) ?></div>
                    <div class="text-muted small"><?= esc($g['grupo_nombre']) ?></div>
                </div>
                <div class="ej-block-actions">
                    <a href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $g['ejercicio_id']) ?>" class="ej-orden-btn" title="Ver estadísticas">📈</a>
                    <button type="button" class="ej-orden-btn ej-orden-up" data-pe="<?= $g['pe_id'] ?>" title="Subir" <?= $idx === 0 ? 'disabled' : '' ?>>⬆️</button>
                    <button type="button" class="ej-orden-btn ej-orden-down" data-pe="<?= $g['pe_id'] ?>" title="Bajar" <?= $idx === count($ejerciciosAgrupados) - 1 ? 'disabled' : '' ?>>⬇️</button>
                </div>
            </div>
            <ul class="list-group ej-series-list">
                <?php foreach ($g['series'] as $s): ?>
                    <?php
                    $textoSerie = (int)$s['series'] . 'x' . (int)$s['repeticiones'];
                    $peso = (float)($s['peso'] ?? 0);
                    if ($peso > 0) {
                        $pesoMostrar = (floor($peso) == $peso) ? (string)(int)$peso : rtrim(rtrim(number_format($peso, 3, '.', ''), '0'), '.');
                        $textoSerie .= 'x' . $pesoMostrar . ' kg';
                    }
                    ?>
                    <li class="list-group-item d-flex flex-column" data-serie-id="<?= $s['id'] ?>">
                        <div class="serie-texto"><?= esc($textoSerie) ?></div>
                        <?php if (!empty($s['rpe']) || !empty($s['nota'])): ?>
                            <div class="serie-rpenota">
                                <?php if (!empty($s['rpe'])): ?>RPE: <?= esc($s['rpe']) ?><?php endif; ?>
                                <?php if (!empty($s['nota'])): ?>
                                    <?php if (!empty($s['rpe'])): ?> · <?php endif; ?>
                                    “<?= esc($s['nota']) ?>”
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="text-end mt-1">
                            <a href="#" class="btn btn-sm btn-editar-serie me-1"
                                data-id="<?= $s['id'] ?>"
                                data-series="<?= (int)$s['series'] ?>"
                                data-reps="<?= (int)$s['repeticiones'] ?>"
                                data-peso="<?= (float)$s['peso'] ?>"
                                data-rpe="<?= esc($s['rpe'] ?? '') ?>"
                                data-nota="<?= esc($s['nota'] ?? '') ?>">✏️</a>
                            <a href="#" class="btn btn-sm btn-eliminar-serie" data-id="<?= $s['id'] ?>" title="Eliminar">🗑</a>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($g['series'])): ?>
                    <li class="list-group-item ej-sin-series text-muted small">Sin series todavía — usa el botón "+" para añadir.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>
<?php if (empty($ejerciciosAgrupados)): ?>
    <div class="alert alert-light border" id="listaVacia">Esta plantilla todavía no tiene ejercicios. Usa el botón "+" para empezar.</div>
<?php endif; ?>

<div style="height:90px;"></div> <!-- espacio para que el FAB no tape el último elemento -->

<!-- FAB: añadir serie -->
<button id="btnAbrirAdd" class="qa-fab" title="Añadir serie"><i class="bi bi-plus-lg"></i></button>

<div id="qaOverlay" class="qa-overlay d-none"></div>
<div id="qaSheet" class="qa-sheet d-none">
    <div class="qa-sheet-handle"></div>
    <div class="qa-sheet-header">
        <span>Añadir serie</span>
        <button type="button" id="qaCerrar" class="qa-cerrar">✕</button>
    </div>
    <div class="qa-sheet-body">

        <!-- Paso: elegir ejercicio -->
        <div id="qaPickExercise">
            <div class="qa-search mb-2">
                <input type="text" id="qaBuscarEjercicio" class="form-control" placeholder="Buscar ejercicio…">
            </div>

            <?php if (!empty($recientes)): ?>
                <div class="text-muted small mb-1">Recientes</div>
                <div class="qa-chips" id="qaRecientes">
                    <?php foreach ($recientes as $r): ?>
                        <button type="button" class="qa-chip" data-id="<?= $r['id'] ?>" data-nombre="<?= esc($r['nombre']) ?>" data-grupo-nombre="<?= esc($grupos[$r['grupo_muscular']] ?? $r['grupo_muscular']) ?>"><?= esc($r['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center my-2">
                <button type="button" id="qaTogglePorGrupo" class="btn btn-sm btn-link">Buscar por grupo muscular ▾</button>
            </div>
            <div id="qaPorGrupo" class="d-none">
                <div class="qa-grupos">
                    <?php foreach ($grupos as $clave => $nombre): ?>
                        <button type="button" class="qa-grupo-btn" data-grupo="<?= $clave ?>"><?= $nombre ?></button>
                    <?php endforeach; ?>
                </div>
                <div id="qaEjerciciosDeGrupo" class="qa-chips mt-2"></div>
            </div>
        </div>

        <!-- Paso: registro rápido -->
        <div id="qaLogCard" class="d-none">
            <div class="qa-log-header">
                <div>
                    <div class="qa-log-ejercicio" id="qaEjNombre"></div>
                    <div class="qa-log-grupo" id="qaEjGrupo"></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="qaCambiarEjercicio">Cambiar</button>
            </div>

            <div class="qa-stepper" data-field="series">
                <label>Series</label>
                <div class="qa-stepper-row">
                    <button type="button" class="qa-btn-menos">−</button>
                    <span class="qa-valor" id="qaVal-series">3</span>
                    <button type="button" class="qa-btn-mas">+</button>
                </div>
            </div>
            <div class="qa-stepper" data-field="reps">
                <label>Repeticiones</label>
                <div class="qa-stepper-row">
                    <button type="button" class="qa-btn-menos">−</button>
                    <span class="qa-valor" id="qaVal-reps">10</span>
                    <button type="button" class="qa-btn-mas">+</button>
                </div>
            </div>
            <div class="qa-stepper" data-field="peso">
                <label>Peso (kg)</label>
                <div class="qa-stepper-row">
                    <button type="button" class="qa-btn-menos">−</button>
                    <span class="qa-valor" id="qaVal-peso">0</span>
                    <button type="button" class="qa-btn-mas">+</button>
                </div>
                <button type="button" id="qaTogglePasoFino" class="qa-paso-fino">Paso: 2.5 kg</button>
            </div>

            <div class="text-center">
                <button type="button" id="qaToggleDetalles" class="btn btn-sm btn-link">+ RPE / nota</button>
            </div>
            <div id="qaDetalles" class="d-none">
                <div class="qa-stepper" data-field="rpe">
                    <label>RPE</label>
                    <div class="qa-stepper-row">
                        <button type="button" class="qa-btn-menos">−</button>
                        <span class="qa-valor" id="qaVal-rpe">–</span>
                        <button type="button" class="qa-btn-mas">+</button>
                    </div>
                </div>
                <textarea id="qaNota" class="form-control form-control-sm mt-2" rows="2" placeholder="Nota (opcional)"></textarea>
            </div>

            <button type="button" id="qaGuardar" class="qa-btn-guardar">✅ Guardar serie</button>
            <div id="qaFeedback" class="qa-feedback"></div>
        </div>

    </div>
</div>

<!-- Modal editar serie -->
<div class="modal fade" id="modalEditarSerie" tabindex="-1">
    <div class="modal-dialog">
        <form id="formEditarSerie">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar serie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="qeStepperGroup">
                        <div class="qa-stepper" data-field="series">
                            <label>Series</label>
                            <div class="qa-stepper-row">
                                <button type="button" class="qa-btn-menos">−</button>
                                <span class="qa-valor">3</span>
                                <button type="button" class="qa-btn-mas">+</button>
                            </div>
                        </div>
                        <div class="qa-stepper" data-field="reps">
                            <label>Repeticiones</label>
                            <div class="qa-stepper-row">
                                <button type="button" class="qa-btn-menos">−</button>
                                <span class="qa-valor">10</span>
                                <button type="button" class="qa-btn-mas">+</button>
                            </div>
                        </div>
                        <div class="qa-stepper" data-field="peso">
                            <label>Peso (kg)</label>
                            <div class="qa-stepper-row">
                                <button type="button" class="qa-btn-menos">−</button>
                                <span class="qa-valor">0</span>
                                <button type="button" class="qa-btn-mas">+</button>
                            </div>
                            <button type="button" class="qa-paso-fino">Paso: 2.5 kg</button>
                        </div>
                        <div class="qa-stepper" data-field="rpe">
                            <label>RPE</label>
                            <div class="qa-stepper-row">
                                <button type="button" class="qa-btn-menos">−</button>
                                <span class="qa-valor">–</span>
                                <button type="button" class="qa-btn-mas">+</button>
                            </div>
                        </div>
                    </div>
                    <label class="form-label mt-1">Nota</label>
                    <textarea id="qeNota" class="form-control form-control-sm" rows="2" placeholder="Nota (opcional)"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* ---- FAB + bottom sheet ---- */
.qa-fab {
    position: fixed; right: 18px; bottom: 24px;
    width: 58px; height: 58px; border-radius: 50%;
    border: none; background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: #fff; font-size: 1.5rem;
    box-shadow: 0 6px 18px rgba(124, 58, 237, .45);
    z-index: 1050; display: grid; place-items: center;
}
.qa-fab:active { transform: scale(.95); }
.qa-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .45); z-index: 1055; }
.qa-sheet {
    position: fixed; left: 0; right: 0; bottom: 0; max-height: 88vh;
    background: var(--bs-body-bg); border-radius: 20px 20px 0 0;
    box-shadow: 0 -8px 30px rgba(0, 0, 0, .35);
    z-index: 1056; display: flex; flex-direction: column; overflow: hidden;
}
.qa-sheet-handle { width: 42px; height: 5px; background: var(--bs-border-color); border-radius: 999px; margin: 10px auto 4px; flex: 0 0 auto; }
.qa-sheet-header { display: flex; align-items: center; justify-content: space-between; padding: 4px 16px 10px; font-weight: 700; border-bottom: 1px solid var(--bs-border-color); flex: 0 0 auto; }
.qa-cerrar { border: none; background: transparent; font-size: 1.2rem; color: var(--bs-secondary-color); }
.qa-sheet-body { padding: 14px 16px 26px; overflow-y: auto; }

/* ---- picker de ejercicio ---- */
.qa-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0; }
.qa-chip {
    border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color);
    border-radius: 999px; padding: 9px 15px; font-size: .85rem; font-weight: 600;
}
.qa-chip:active { background: var(--bs-body-bg); }
.qa-chip.d-none { display: none !important; }
.qa-grupos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.qa-grupo-btn { border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); border-radius: 12px; padding: 10px 6px; font-size: .78rem; font-weight: 600; color: var(--bs-emphasis-color); }

/* ---- tarjeta de registro rápido ---- */
.qa-log-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; gap: 8px; }
.qa-log-ejercicio { font-weight: 700; font-size: 1.05rem; }
.qa-log-grupo { font-size: .75rem; color: var(--bs-secondary-color); }
.qa-stepper { margin-bottom: 14px; }
.qa-stepper label { font-size: .78rem; font-weight: 600; color: var(--bs-secondary-color); display: block; margin-bottom: 4px; }
.qa-stepper-row { display: flex; align-items: center; gap: 10px; }
.qa-btn-menos, .qa-btn-mas {
    width: 48px; height: 48px; border-radius: 14px; border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); font-size: 1.4rem; font-weight: 700;
    display: grid; place-items: center; flex: 0 0 auto; user-select: none;
}
.qa-btn-menos:active, .qa-btn-mas:active { background: var(--bs-body-bg); transform: scale(.96); }
.qa-valor { flex: 1 1 auto; text-align: center; font-size: 1.5rem; font-weight: 800; color: var(--bs-emphasis-color); }
.qa-paso-fino { margin-top: 6px; border: none; background: transparent; font-size: .72rem; color: var(--bs-secondary-color); text-decoration: underline; padding: 0; }
.qa-btn-guardar { width: 100%; padding: 14px; border-radius: 14px; border: none; background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; font-weight: 700; font-size: 1.05rem; margin-top: 6px; }
.qa-btn-guardar:disabled { opacity: .6; }
.qa-feedback { text-align: center; font-size: .85rem; margin-top: 8px; min-height: 1.2em; }
.qa-feedback-ok { color: #16a34a; font-weight: 600; }
.qa-feedback-error { color: #dc3545; font-weight: 600; }

/* ---- lista de ejercicios / series ---- */
.ej-block { border: 1px solid var(--bs-border-color); border-radius: 14px; margin-bottom: 12px; overflow: hidden; background: var(--bs-body-bg); }
.ej-block-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--bs-tertiary-bg); gap: 8px; }
.ej-block-actions { display: flex; gap: 4px; flex: 0 0 auto; }
.ej-orden-btn { width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); display: grid; place-items: center; font-size: .85rem; text-decoration: none; }
.ej-orden-btn:disabled { opacity: .3; }
.ej-series-list .list-group-item { border-left: none; border-right: none; }
.serie-texto { font-weight: 600; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const grupos = <?= json_encode($grupos) ?>;
    const plantillaId = <?= (int) $plantilla_id ?>;

    const urlUltimoValor    = "<?= site_url('gimnasio/entrenamientos/ultimo-valor') ?>/";
    const urlPorGrupo       = "<?= site_url('gimnasio/ejercicios/por-grupo') ?>/";
    const urlGuardarSerie   = "<?= site_url('gimnasio/plantillas/guardar-serie') ?>";
    const urlActualizarSerie = "<?= site_url('gimnasio/plantillas/actualizar-serie') ?>/";
    const urlEliminarSerie  = "<?= site_url('gimnasio/plantillas/eliminar-serie') ?>/";
    const urlReordenar      = "<?= site_url('gimnasio/plantillas/reordenar-ejercicio') ?>";
    const urlEstadisticas   = "<?= site_url('gimnasio/ejercicios/estadisticas') ?>/";

    function normaliza(s) { return s.normalize('NFD').replace(/\p{Mn}/gu, '').toLowerCase(); }
    function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
    function formatearPeso(p) { return Number.isInteger(p) ? String(p) : parseFloat(p.toFixed(3)).toString(); }

    // ---------------- Bottom sheet ----------------
    const fab = document.getElementById('btnAbrirAdd');
    const overlay = document.getElementById('qaOverlay');
    const sheet = document.getElementById('qaSheet');

    function abrirSheet() {
        overlay.classList.remove('d-none');
        sheet.classList.remove('d-none');
        mostrarPickExercise();
    }
    function cerrarSheet() {
        overlay.classList.add('d-none');
        sheet.classList.add('d-none');
    }
    fab.addEventListener('click', abrirSheet);
    overlay.addEventListener('click', cerrarSheet);
    document.getElementById('qaCerrar').addEventListener('click', cerrarSheet);

    function mostrarPickExercise() {
        document.getElementById('qaPickExercise').classList.remove('d-none');
        document.getElementById('qaLogCard').classList.add('d-none');
        document.getElementById('qaBuscarEjercicio').value = '';
        document.querySelectorAll('.qa-chip').forEach(c => c.classList.remove('d-none'));
    }

    // ---------------- Paso 1: elegir ejercicio ----------------
    document.getElementById('qaBuscarEjercicio').addEventListener('input', e => {
        const q = normaliza(e.target.value.trim());
        document.querySelectorAll('#qaRecientes .qa-chip, #qaEjerciciosDeGrupo .qa-chip').forEach(chip => {
            const texto = normaliza(chip.dataset.nombre || '');
            chip.classList.toggle('d-none', !!q && !texto.includes(q));
        });
    });

    document.getElementById('qaTogglePorGrupo').addEventListener('click', function () {
        document.getElementById('qaPorGrupo').classList.toggle('d-none');
    });

    document.querySelectorAll('.qa-grupo-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const grupoKey = btn.dataset.grupo;
            fetch(urlPorGrupo + grupoKey)
                .then(r => r.json())
                .then(data => {
                    const cont = document.getElementById('qaEjerciciosDeGrupo');
                    cont.innerHTML = '';
                    data.forEach(ej => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'qa-chip';
                        b.dataset.nombre = ej.nombre;
                        b.textContent = ej.nombre;
                        b.addEventListener('click', () => seleccionarEjercicio(ej.id, ej.nombre, grupos[grupoKey] || grupoKey));
                        cont.appendChild(b);
                    });
                });
        });
    });

    document.querySelectorAll('#qaRecientes .qa-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            seleccionarEjercicio(btn.dataset.id, btn.dataset.nombre, btn.dataset.grupoNombre);
        });
    });

    // ---------------- Steppers reutilizables (+/- series, reps, peso, rpe) ----------------
    function initStepperGroup(root, inicial) {
        const estado = Object.assign({}, inicial);
        let pasoPeso = 2.5;

        function formatearValor(campo, val) {
            if (campo === 'rpe') return (val === null || val === undefined) ? '–' : val;
            if (campo === 'peso') return formatearPeso(val);
            return val;
        }

        function render() {
            root.querySelectorAll('.qa-stepper').forEach(st => {
                const campo = st.dataset.field;
                st.querySelector('.qa-valor').textContent = formatearValor(campo, estado[campo]);
            });
        }

        function cambiar(campo, dir) {
            if (campo === 'rpe') {
                const actual = (estado.rpe === null || estado.rpe === undefined) ? -1 : estado.rpe;
                estado.rpe = Math.max(0, Math.min(10, actual + dir));
                render();
                return;
            }
            const paso = campo === 'peso' ? pasoPeso : 1;
            const min = campo === 'peso' ? 0 : 1;
            const max = campo === 'series' ? 20 : (campo === 'reps' ? 100 : 999);
            let nuevo = estado[campo] + dir * paso;
            nuevo = Math.max(min, Math.min(max, nuevo));
            nuevo = Math.round(nuevo * 1000) / 1000;
            estado[campo] = nuevo;
            render();
        }

        root.querySelectorAll('.qa-stepper').forEach(st => {
            const campo = st.dataset.field;
            st.querySelector('.qa-btn-menos').addEventListener('click', () => cambiar(campo, -1));
            st.querySelector('.qa-btn-mas').addEventListener('click', () => cambiar(campo, 1));
        });

        const btnPasoFino = root.querySelector('.qa-paso-fino');
        if (btnPasoFino) {
            btnPasoFino.addEventListener('click', function () {
                pasoPeso = (pasoPeso === 2.5) ? 10 : (pasoPeso === 10 ? 1.25 : 2.5);
                this.textContent = 'Paso: ' + pasoPeso + ' kg';
            });
        }

        render();

        return {
            estado: estado,
            set: function (nuevo) { Object.assign(estado, nuevo); render(); }
        };
    }

    // ---------------- Paso 2: registro rápido (añadir) ----------------
    let ejercicioActual = null; // {id, nombre, grupoNombre}
    const addStepper = initStepperGroup(document.getElementById('qaLogCard'), { series: 3, reps: 10, peso: 0, rpe: null });

    function seleccionarEjercicio(id, nombre, grupoNombre) {
        ejercicioActual = { id: id, nombre: nombre, grupoNombre: grupoNombre };
        document.getElementById('qaEjNombre').textContent = nombre;
        document.getElementById('qaEjGrupo').textContent = grupoNombre || '';
        document.getElementById('qaPickExercise').classList.add('d-none');
        document.getElementById('qaLogCard').classList.remove('d-none');
        document.getElementById('qaDetalles').classList.add('d-none');
        document.getElementById('qaNota').value = '';

        addStepper.set({ series: 3, reps: 10, peso: 0, rpe: null });

        fetch(urlUltimoValor + id)
            .then(r => r.json())
            .then(data => {
                if (data && data.series) {
                    addStepper.set({
                        series: parseInt(data.series) || 3,
                        reps: parseInt(data.repeticiones) || 10,
                        peso: data.peso !== null && data.peso !== undefined ? parseFloat(data.peso) : 0,
                    });
                }
            })
            .catch(() => {});
    }

    document.getElementById('qaCambiarEjercicio').addEventListener('click', mostrarPickExercise);

    document.getElementById('qaToggleDetalles').addEventListener('click', function () {
        document.getElementById('qaDetalles').classList.toggle('d-none');
    });

    document.getElementById('qaGuardar').addEventListener('click', () => {
        if (!ejercicioActual) return;

        const v = addStepper.estado;
        const fd = new FormData();
        fd.append('plantilla_id', plantillaId);
        fd.append('ejercicio_id', ejercicioActual.id);
        fd.append('series', v.series);
        fd.append('repeticiones', v.reps);
        fd.append('peso', v.peso);
        fd.append('rpe', v.rpe === null || v.rpe === undefined ? '' : v.rpe);
        fd.append('nota', document.getElementById('qaNota').value);

        const btn = document.getElementById('qaGuardar');
        btn.disabled = true;
        btn.textContent = 'Guardando…';

        fetch(urlGuardarSerie, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    insertarSerieEnDOM(data.serie, data.ejercicio);
                    mostrarFeedback('✅ Serie guardada — sigue con la siguiente o cambia de ejercicio');
                } else {
                    mostrarFeedback('⚠️ No se pudo guardar', true);
                }
            })
            .catch(() => mostrarFeedback('⚠️ Error de conexión', true))
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '✅ Guardar serie';
            });
    });

    function mostrarFeedback(msg, esError) {
        const el = document.getElementById('qaFeedback');
        el.textContent = msg;
        el.className = 'qa-feedback ' + (esError ? 'qa-feedback-error' : 'qa-feedback-ok');
        clearTimeout(window.__qaFeedbackTimeout);
        window.__qaFeedbackTimeout = setTimeout(() => { el.textContent = ''; }, 2500);
    }

    // ---------------- Lista de ejercicios / series ----------------
    function actualizarBotonesOrden() {
        const bloques = document.querySelectorAll('#listaEjercicios .ej-block');
        bloques.forEach((b, i) => {
            b.querySelector('.ej-orden-up').disabled = (i === 0);
            b.querySelector('.ej-orden-down').disabled = (i === bloques.length - 1);
        });
    }

    function crearBloqueEjercicio(ejercicio) {
        const div = document.createElement('div');
        div.className = 'ej-block';
        div.dataset.peId = ejercicio.pe_id;
        div.innerHTML = `
            <div class="ej-block-header">
                <div class="ej-block-titulo">
                    <div class="fw-bold">${escapeHtml(ejercicio.nombre)}</div>
                    <div class="text-muted small">${escapeHtml(ejercicio.grupo_nombre)}</div>
                </div>
                <div class="ej-block-actions">
                    <a href="${urlEstadisticas}${ejercicio.id}" class="ej-orden-btn" title="Ver estadísticas">📈</a>
                    <button type="button" class="ej-orden-btn ej-orden-up" data-pe="${ejercicio.pe_id}" title="Subir">⬆️</button>
                    <button type="button" class="ej-orden-btn ej-orden-down" data-pe="${ejercicio.pe_id}" title="Bajar">⬇️</button>
                </div>
            </div>
            <ul class="list-group ej-series-list"></ul>
        `;
        return div;
    }

    function buildSerieLI(serie) {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex flex-column';
        li.dataset.serieId = serie.id;

        let texto = `${serie.series}x${serie.repeticiones}`;
        if (serie.peso > 0) texto += `x${formatearPeso(serie.peso)} kg`;

        let rpeNotaHtml = '';
        if (serie.rpe || serie.nota) {
            rpeNotaHtml = '<div class="serie-rpenota">';
            if (serie.rpe) rpeNotaHtml += `RPE: ${serie.rpe}`;
            if (serie.nota) rpeNotaHtml += (serie.rpe ? ' · ' : '') + `“${escapeHtml(serie.nota)}”`;
            rpeNotaHtml += '</div>';
        }

        li.innerHTML = `
            <div class="serie-texto">${escapeHtml(texto)}</div>
            ${rpeNotaHtml}
            <div class="text-end mt-1">
                <a href="#" class="btn btn-sm btn-editar-serie me-1"
                    data-id="${serie.id}" data-series="${serie.series}" data-reps="${serie.repeticiones}"
                    data-peso="${serie.peso}" data-rpe="${serie.rpe ?? ''}" data-nota="${escapeHtml(serie.nota ?? '')}">✏️</a>
                <a href="#" class="btn btn-sm btn-eliminar-serie" data-id="${serie.id}" title="Eliminar">🗑</a>
            </div>
        `;
        return li;
    }

    function insertarSerieEnDOM(serie, ejercicio) {
        let bloque = document.querySelector(`.ej-block[data-pe-id="${ejercicio.pe_id}"]`);
        if (!bloque) {
            bloque = crearBloqueEjercicio(ejercicio);
            document.getElementById('listaEjercicios').appendChild(bloque);
            document.getElementById('listaVacia')?.remove();
            actualizarBotonesOrden();
        }
        const lista = bloque.querySelector('.ej-series-list');
        lista.querySelector('.ej-sin-series')?.remove();
        lista.appendChild(buildSerieLI(serie));
    }

    // Editar / eliminar / reordenar (delegación, funciona con elementos añadidos por JS)
    const modalEditarEl = document.getElementById('modalEditarSerie');
    const modalEditar = new bootstrap.Modal(modalEditarEl);
    const formEditar = document.getElementById('formEditarSerie');
    const qeNota = document.getElementById('qeNota');
    const editStepper = initStepperGroup(document.getElementById('qeStepperGroup'), { series: 3, reps: 10, peso: 0, rpe: null });

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-editar-serie');
        if (editBtn) {
            e.preventDefault();
            formEditar.dataset.id = editBtn.dataset.id;
            editStepper.set({
                series: parseInt(editBtn.dataset.series) || 1,
                reps: parseInt(editBtn.dataset.reps) || 1,
                peso: parseFloat(editBtn.dataset.peso) || 0,
                rpe: editBtn.dataset.rpe !== '' ? parseInt(editBtn.dataset.rpe) : null,
            });
            qeNota.value = editBtn.dataset.nota || '';
            modalEditar.show();
            return;
        }

        const delBtn = e.target.closest('.btn-eliminar-serie');
        if (delBtn) {
            e.preventDefault();
            if (!confirm('¿Eliminar esta serie?')) return;
            const li = delBtn.closest('li');
            const bloque = delBtn.closest('.ej-block');
            fetch(urlEliminarSerie + delBtn.dataset.id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) return;
                    li.remove();
                    if (data.ejercicio_eliminado) {
                        bloque.remove();
                        actualizarBotonesOrden();
                        if (!document.querySelector('#listaEjercicios .ej-block')) {
                            document.getElementById('listaEjercicios').insertAdjacentHTML('afterend', '<div class="alert alert-light border" id="listaVacia">Esta plantilla todavía no tiene ejercicios. Usa el botón "+" para empezar.</div>');
                        }
                    }
                });
            return;
        }

        const upBtn = e.target.closest('.ej-orden-up');
        const downBtn = e.target.closest('.ej-orden-down');
        if (upBtn || downBtn) {
            e.preventDefault();
            const btn = upBtn || downBtn;
            if (btn.disabled) return;
            const direction = upBtn ? 'up' : 'down';
            const peId = btn.dataset.pe;
            const bloque = document.querySelector(`.ej-block[data-pe-id="${peId}"]`);
            const vecino = direction === 'up' ? bloque.previousElementSibling : bloque.nextElementSibling;
            if (!vecino) return;

            fetch(urlReordenar, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `plantilla_ejercicio_id=${encodeURIComponent(peId)}&direction=${direction}`
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) return;
                    if (direction === 'up') {
                        bloque.parentNode.insertBefore(bloque, vecino);
                    } else {
                        bloque.parentNode.insertBefore(vecino, bloque);
                    }
                    actualizarBotonesOrden();
                });
        }
    });

    formEditar.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = this.dataset.id;
        const v = editStepper.estado;
        const fd = new FormData();
        fd.append('series', v.series);
        fd.append('repeticiones', v.reps);
        fd.append('peso', v.peso);
        fd.append('rpe', v.rpe === null || v.rpe === undefined ? '' : v.rpe);
        fd.append('nota', qeNota.value);
        fetch(urlActualizarSerie + id, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) return;
                const li = document.querySelector(`li[data-serie-id="${id}"]`);
                if (li) {
                    const s = data.serie;
                    let texto = `${s.series}x${s.repeticiones}`;
                    if (s.peso > 0) texto += `x${formatearPeso(parseFloat(s.peso))} kg`;
                    li.querySelector('.serie-texto').textContent = texto;

                    let rpeNotaDiv = li.querySelector('.serie-rpenota');
                    if (s.rpe || s.nota) {
                        let html = '';
                        if (s.rpe) html += `RPE: ${s.rpe}`;
                        if (s.nota) html += (s.rpe ? ' · ' : '') + `“${escapeHtml(s.nota)}”`;
                        if (!rpeNotaDiv) {
                            rpeNotaDiv = document.createElement('div');
                            rpeNotaDiv.className = 'serie-rpenota';
                            li.querySelector('.serie-texto').after(rpeNotaDiv);
                        }
                        rpeNotaDiv.innerHTML = html;
                    } else if (rpeNotaDiv) {
                        rpeNotaDiv.remove();
                    }

                    const editBtn = li.querySelector('.btn-editar-serie');
                    editBtn.dataset.series = s.series;
                    editBtn.dataset.reps = s.repeticiones;
                    editBtn.dataset.peso = s.peso;
                    editBtn.dataset.rpe = s.rpe ?? '';
                    editBtn.dataset.nota = s.nota ?? '';
                }
                modalEditar.hide();
            });
    });
});
</script>

<?= $this->endSection() ?>
