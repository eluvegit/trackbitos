<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">📅 Entrenamiento del <?= date('d/m/Y', strtotime($fecha)) ?></h2>
<div class="mb-2">
    <a href="<?= site_url('gimnasio/entrenamientos') ?>" class="btn btn-outline-secondary">← Volver a entrenamientos</a>
</div>



<div class="alert alert-primary text-center" id="registro-construccion">
    Completa los pasos para construir tu registro...
</div>

<div class="mb-2 text-center">
    <button id="btn-toggle-add" class="btn btn-primary">+ Añadir serie</button>
</div>





<div class="row justify-content-center">
    <div class="col-md-8">
        <!-- Preview oculta -->
        <div id="pasos" class="d-none">

            <span id="preview-grupo" class="d-none"></span>
            <span id="preview-ejercicio" class="d-none"></span>
            <span id="preview-series" class="d-none"></span>
            <span id="preview-reps" class="d-none"></span>
            <span id="preview-peso" class="d-none"></span>
            <span id="preview-rpe" class="d-none"></span>
            <span id="preview-nota" class="d-none"></span>

            <div id="pasos">
                <!-- Paso 1: Grupo -->
                <div id="paso-1" class="paso d-none">
                    <h5>1️⃣ Elige un grupo muscular</h5>
                    <div class="row row-cols-2 row-cols-md-4 g-2">
                        <?php
                        $grupos = [
                            'biceps' => 'Bíceps',
                            'triceps' => 'Tríceps',
                            'hombros' => 'Hombros',
                            'espalda' => 'Espalda',
                            'pecho' => 'Pecho',
                            'abdominales' => 'Abdominales',
                            'piernas' => 'Piernas',
                            'maquinas' => 'Máquinas',
                            'calentamientos' => 'Calentamientos',
                            'movilidad' => 'Movilidad',
                            'cardio' => 'Cardio',
                            'especificos' => 'Específicos',
                            'recuperacion' => 'Recuperación'
                        ];
                        foreach ($grupos as $clave => $nombre): ?>
                            <div class="col">
                                <button class="btn btn-outline-secondary w-100 grupo-muscular" data-grupo="<?= $clave ?>"><?= $nombre ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Paso 2: Ejercicio -->
                <div id="paso-2" class="paso d-none mt-4">
                    <h5>2️⃣ Elige un ejercicio</h5>
                    <div id="contenedor-ejercicios" class="row g-2"></div>
                    <div class="text-end mt-3">
                        <button class="btn btn-outline-secondary" onclick="mostrarPaso(1)">⬅ Volver</button>
                    </div>
                </div>

                <!-- Paso 3: Series -->
                <div id="paso-3" class="paso d-none mt-4">
                    <h5>3️⃣ Número de series</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <button class="btn btn-outline-primary btn-serie" data-serie="<?= $i ?>"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-outline-secondary" onclick="mostrarPaso(2)">⬅ Volver</button>
                    </div>
                </div>

                <!-- Paso 4: Reps -->
                <div id="paso-4" class="paso d-none mt-4">
                    <h5>4️⃣ Repeticiones</h5>

                    <!-- Reps 1–20 (visibles) -->
                    <div class="d-flex flex-wrap gap-2" id="reps-basicas">
                        <?php for ($i = 1; $i <= 20; $i++): ?>
                            <button class="btn btn-outline-primary btn-reps" data-reps="<?= $i ?>"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>

                    <!-- Botón mostrar más/menos -->
                    <div class="mt-2">
                        <button type="button" id="btn-toggle-reps" class="btn btn-sm btn-link p-0">
                            Mostrar más…
                        </button>
                    </div>

                    <!-- Reps 21–60 (ocultas al inicio) -->
                    <div class="d-flex flex-wrap gap-2 d-none" id="reps-extra">
                        <?php for ($i = 21; $i <= 60; $i++): ?>
                            <button class="btn btn-outline-primary btn-reps" data-reps="<?= $i ?>"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>

                    <div class="text-end mt-3">
                        <button class="btn btn-outline-secondary" onclick="mostrarPaso(3)">⬅ Volver</button>
                    </div>
                </div>


                <!-- Paso 5: Peso -->
                <div id="paso-5" class="paso d-none mt-4">
                    <h5>5️⃣ Peso</h5>
                    <div class="mb-2">Parte entera:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php for ($i = 0; $i <= 10; $i++) echo "<button class='btn btn-outline-secondary btn-peso-entero' data-valor='$i'>$i</button>"; ?>
                        <?php for ($i = 20; $i <= 200; $i += 10) echo "<button class='btn btn-outline-secondary btn-peso-entero' data-valor='$i'>$i</button>"; ?>
                    </div>
                    <div class="my-2">+Incremento:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php for ($i = 0; $i <= 9; $i++) echo "<button class='btn btn-outline-info btn-peso-entero-incremento' data-incremento='$i'>+$i</button>"; ?>
                    </div>
                    <div class="my-2">Decimal:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-danger btn-peso-decimal" data-valor="0">0 (borrar)</button>
                        <?php foreach ([0.125, 0.25, 0.5, 0.75] as $d): ?>
                            <button class="btn btn-outline-secondary btn-peso-decimal" data-valor="<?= $d ?>"><?= $d ?></button>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-2">Peso total: <strong id="peso-total">0</strong> kg</p>
                    <button class="btn btn-primary mt-2" onclick="mostrarPaso(6)">Confirmar peso</button>
                    <div class="text-end mt-3">
                        <button class="btn btn-outline-secondary" onclick="mostrarPaso(4)">⬅ Volver</button>
                    </div>
                </div>

                <!-- Paso 6: Nota y RPE -->
                <div id="paso-6" class="paso d-none mt-4">
                    <h5>6️⃣ Nota (opcional)</h5>
                    <input type="text" id="rpe" class="form-control mb-3" placeholder="RPE (opcional)">
                    <textarea id="nota" class="form-control mb-3" rows="2"></textarea>
                    <form id="form-registro-serie" method="post" action="<?= site_url('gimnasio/entrenamientos/guardar-serie') ?>">
                        <input type="hidden" name="fecha" value="<?= $fecha ?>">
                        <input type="hidden" name="grupo" id="input-grupo">
                        <input type="hidden" name="entrenamiento_id" value="<?= $entrenamiento['id'] ?>">
                        <input type="hidden" name="ejercicio_id" id="input-ejercicio">
                        <input type="hidden" name="series" id="input-series">
                        <input type="hidden" name="repeticiones" id="input-reps">
                        <input type="hidden" name="peso" id="input-peso">
                        <input type="hidden" name="rpe" id="input-rpe">
                        <input type="hidden" name="nota" id="input-nota">

                    </form>

                    <button class="btn btn-success" onclick="guardarSerie()">✅ Guardar serie</button>

                    <div class="text-end mt-3">
                        <button class="btn btn-outline-secondary" onclick="mostrarPaso(5)">⬅ Volver</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 row justify-content-center">
    <div class="col-md-8">

        <!-- Enlace para mostrar/ocultar centrado -->
        <div class="text-center mb-2">
            <a href="#" id="toggle-datos-entrenamiento">
                📋 Mostrar datos del entrenamiento
            </a>
        </div>

        <!-- Bloque oculto por defecto -->
        <div id="bloque-datos-entrenamiento" class="card mb-4 d-none">
            <div class="card-header">📝 Datos del entrenamiento</div>
            <form method="post" action="<?= site_url('gimnasio/entrenamientos/actualizar-datos/' . $entrenamiento_id) ?>">
                <div class="card-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="tipo_sesion" class="form-label">Tipo de sesión</label>
                        <select name="tipo_sesion" id="tipo_sesion" class="form-control">
                            <option value="">-- Seleccionar --</option>
                            <option value="Cardio" <?= old('tipo_sesion', $entrenamiento['tipo_sesion'] ?? '') === 'Cardio' ? 'selected' : '' ?>>Cardio</option>
                            <option value="HIIT" <?= old('tipo_sesion', $entrenamiento['tipo_sesion'] ?? '') === 'HIIT' ? 'selected' : '' ?>>HIIT</option>
                            <option value="Hipertrofia" <?= old('tipo_sesion', $entrenamiento['tipo_sesion'] ?? '') === 'Hipertrofia' ? 'selected' : '' ?>>Hipertrofia</option>
                            <option value="Fuerza" <?= old('tipo_sesion', $entrenamiento['tipo_sesion'] ?? '') === 'Fuerza' ? 'selected' : '' ?>>Fuerza</option>
                            <option value="Movilidad" <?= old('tipo_sesion', $entrenamiento['tipo_sesion'] ?? '') === 'Movilidad' ? 'selected' : '' ?>>Movilidad</option>
                            <option value="Recuperación" <?= old('tipo_sesion', $entrenamiento['tipo_sesion'] ?? '') === 'Recuperación' ? 'selected' : '' ?>>Recuperación</option>
                        </select>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas generales</label>
                        <textarea name="notas_generales" class="form-control" rows="2"><?= esc($entrenamiento['notas_generales'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lesiones / molestias</label>
                        <textarea name="lesiones" class="form-control" rows="2"><?= esc($entrenamiento['lesiones'] ?? '') ?></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sin_molestias" name="sin_molestias"
                            <?= !empty($entrenamiento['sin_molestias']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sin_molestias">Sin molestias</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary btn-sm">Guardar datos</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    document.getElementById('toggle-datos-entrenamiento').addEventListener('click', function(e) {
        e.preventDefault();
        let bloque = document.getElementById('bloque-datos-entrenamiento');
        bloque.classList.toggle('d-none');
        this.textContent = bloque.classList.contains('d-none') ?
            '📋 Mostrar datos del entrenamiento' :
            '📋 Ocultar datos del entrenamiento';
    });
</script>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h4 class="mt-2 mb-4">📋 Series registradas</h4>
        <?php if (!empty($seriesGuardadas)): ?>
            <ul class="list-group">
                <?php foreach ($seriesGuardadas as $s): ?>
                    <li class="list-group-item d-flex flex-column">

                        <!-- Línea 1: Grupo muscular -->
                        <div class="fw-bold"><?= esc($s['grupo_nombre'] ?? '') ?></div>

                        <!-- Línea 2: Nombre del ejercicio -->
                        <div><?= esc($s['ejercicio_nombre'] ?? 'Ejercicio') ?></div>

                        <!-- Línea 3: Series x repeticiones (y peso si hay) -->
                        <div>
                            <?php
                            $textoSerie = (int)$s['series'] . 'x' . (int)$s['repeticiones'];
                            $peso = (float)($s['peso'] ?? 0);
                            if ($peso > 0) {
                                // si es entero (80.0) muestra 80; si no, recorta decimales innecesarios
                                $pesoMostrar = (floor($peso) == $peso)
                                    ? (string)(int)$peso
                                    : rtrim(rtrim(number_format($peso, 3, '.', ''), '0'), '.');

                                $textoSerie .= 'x' . $pesoMostrar . ' kg';
                            }
                            echo $textoSerie;

                            ?>
                        </div>

                        <!-- Línea 4: RPE y nota si existen -->
                        <?php if (!empty($s['rpe']) || !empty($s['nota'])): ?>
                            <div>
                                <?php if (!empty($s['rpe'])): ?>RPE: <?= esc($s['rpe']) ?><?php endif; ?>
                                <?php if (!empty($s['nota'])): ?>
                                    <?php if (!empty($s['rpe'])): ?> · <?php endif; ?>
                                    “<?= esc($s['nota']) ?>”
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Línea 5: Botones (editar, badge y eliminar) -->
                        <div class="text-end mt-1">
                            <!--<?php if (!empty($s['orden'])): ?>
        <span class="badge bg-secondary me-1">#<?= (int)$s['orden'] ?></span>
    <?php endif; ?>-->
                            <a href="<?= site_url('gimnasio/ejercicios/estadisticas/' . (int)$s['ejercicio_id']) ?>"
                                class="btn btn-sm"
                                title="Ver estadísticas (fechas y series)">
                                📈
                            </a>

                            <a href="#"
                                class="btn btn-sm btn-editar-serie me-1"
                                data-id="<?= $s['id'] ?>"
                                data-series="<?= (int)$s['series'] ?>"
                                data-reps="<?= (int)$s['repeticiones'] ?>"
                                data-peso="<?= (float)$s['peso'] ?>"
                                data-rpe="<?= esc($s['rpe'] ?? '') ?>"
                                data-nota="<?= esc($s['nota'] ?? '') ?>">✏️</a>
                            <a href="<?= site_url('gimnasio/entrenamientos/eliminar-serie/' . $s['id']) ?>"
                                class="btn btn-sm"
                                title="Eliminar"
                                onclick="return confirm('¿Eliminar esta serie?')">🗑</a>
                        </div>


                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-light border mt-3">
                Aún no hay series registradas para este entrenamiento.
            </div>
        <?php endif; ?>
    </div>
</div>





<!-- Modal editar serie -->
<div class="modal fade" id="modalEditarSerie" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="formEditarSerie">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar serie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Series</label>
                            <input type="number" name="series" class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Repeticiones</label>
                            <input type="number" name="repeticiones" class="form-control" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" step="0.001" name="peso" class="form-control" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">RPE</label>
                            <input type="number" name="rpe" class="form-control" min="0" max="10">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nota</label>
                            <textarea name="nota" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-editar-serie').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const id = btn.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('modalEditarSerie'));
            const form = document.getElementById('formEditarSerie');
            form.action = "<?= site_url('gimnasio/entrenamientos/actualizar-serie') ?>/" + id;
            form.series.value = btn.dataset.series;
            form.repeticiones.value = btn.dataset.reps;
            form.peso.value = btn.dataset.peso;
            form.rpe.value = btn.dataset.rpe || '';
            form.nota.value = btn.dataset.nota || '';
            modal.show();
        });
    });
</script>



<script>
    let pasoActual = 0;
    let grupoSeleccionado = '';
    let ejercicioSeleccionado = '';
    let series = 0,
        repeticiones = 0;
    let pesoEnteroBase = 0,
        pesoEnteroIncremento = 0,
        pesoDecimal = 0;

    function guardarSerie() {
        const nota = document.getElementById('nota').value;
        const rpe = document.getElementById('rpe').value;
        const peso = (pesoEnteroBase + pesoEnteroIncremento) + pesoDecimal;

        // Rellenamos los campos del formulario oculto
        document.getElementById('input-grupo').value = grupoSeleccionado;
        document.getElementById('input-ejercicio').value = ejercicioSeleccionado;
        document.getElementById('input-series').value = series;
        document.getElementById('input-reps').value = repeticiones;
        document.getElementById('input-peso').value = peso.toFixed(3);
        document.getElementById('input-rpe').value = rpe;
        document.getElementById('input-nota').value = nota;

        // Enviamos el formulario real
        document.getElementById('form-registro-serie').submit();
    }


    function mostrarPaso(n) {
        document.querySelectorAll('.paso').forEach(p => p.classList.add('d-none'));
        const paso = document.getElementById('paso-' + n);
        if (paso) paso.classList.remove('d-none');
        pasoActual = n;

        if (n === 3) activarSeries(); // Reasigna eventos
        if (n === 4) activarReps();
    }

    function getPreview(id) {
        return document.getElementById(id)?.textContent || '';
    }

    function setPreview(id, valor) {
        const el = document.getElementById(id);
        if (el) el.textContent = valor;
        actualizarFraseRegistro();
    }

    function actualizarFraseRegistro() {
        let frase = '';
        const grupo = getPreview('preview-grupo');
        const ejercicio = getPreview('preview-ejercicio');
        const seriesTxt = getPreview('preview-series');
        const reps = getPreview('preview-reps');
        const peso = getPreview('preview-peso').replace(' kg', '');
        const rpe = getPreview('preview-rpe');
        const nota = getPreview('preview-nota');

        if (grupo) frase += `${grupo}: `;
        if (ejercicio) frase += `${ejercicio} `;

        // Añadir datos parciales en orden
        if (seriesTxt) frase += `${seriesTxt}`;
        if (reps) frase += `x${reps}`;
        if (peso) {
            const pesoNum = parseFloat(peso);
            const pesoMostrar = Number.isInteger(pesoNum) ? pesoNum : peso;
            frase += `x${pesoMostrar} kg `;
        }

        if (rpe) frase += `RPE: ${rpe} `;
        if (nota) frase += `"${nota}"`;

        document.getElementById('registro-construccion').textContent = frase.trim() || 'Completa los pasos para construir tu registro...';
    }


    // Paso 1 → grupo muscular
    document.querySelectorAll('.grupo-muscular').forEach(btn => {
        btn.addEventListener('click', () => {
            grupoSeleccionado = btn.dataset.grupo;
            setPreview('preview-grupo', btn.textContent.trim());
            mostrarPaso(2);

            fetch("<?= site_url('gimnasio/ejercicios/por-grupo') ?>/" + grupoSeleccionado)
                .then(res => res.json())
                .then(data => {
                    const contenedor = document.getElementById('contenedor-ejercicios');
                    contenedor.innerHTML = '';
                    data.forEach(ej => {
                        const div = document.createElement('div');
                        div.className = 'col';
                        div.innerHTML = `
                        <div class="card shadow-sm h-100 ejercicio-card" data-id="${ej.id}">
                            <div class="card-body text-center">
                                <h6>${ej.nombre}</h6>
                            </div>
                        </div>`;
                        contenedor.appendChild(div);
                        div.querySelector('.ejercicio-card').addEventListener('click', () => {
                            ejercicioSeleccionado = ej.id;
                            setPreview('preview-ejercicio', ej.nombre);
                            mostrarPaso(3);
                        });
                    });
                });
        });
    });

    // Paso 3 → series
    function activarSeries() {
        document.querySelectorAll('.btn-serie').forEach(btn => {
            btn.onclick = () => {
                series = btn.dataset.serie;
                setPreview('preview-series', series);
                mostrarPaso(4);
            };
        });
    }

    // Paso 4 → repeticiones
    function activarReps() {
        document.querySelectorAll('.btn-reps').forEach(btn => {
            btn.onclick = () => {
                repeticiones = btn.dataset.reps;
                setPreview('preview-reps', repeticiones);
                mostrarPaso(5);
            };
        });
    }

    // Paso 5 → peso
    document.querySelectorAll('.btn-peso-entero').forEach(btn => {
        btn.addEventListener('click', () => {
            pesoEnteroBase = parseInt(btn.dataset.valor);
            pesoEnteroIncremento = 0;
            actualizarPesoTotal();
        });
    });
    document.querySelectorAll('.btn-peso-entero-incremento').forEach(btn => {
        btn.addEventListener('click', () => {
            pesoEnteroIncremento = parseInt(btn.dataset.incremento);
            actualizarPesoTotal();
        });
    });
    document.querySelectorAll('.btn-peso-decimal').forEach(btn => {
        btn.addEventListener('click', () => {
            pesoDecimal = parseFloat(btn.dataset.valor);
            actualizarPesoTotal();
        });
    });

    function actualizarPesoTotal() {
        const total = (pesoEnteroBase + pesoEnteroIncremento) + pesoDecimal;
        const pesoMostrar = Number.isInteger(total) ? total : total.toFixed(3);
        document.getElementById('peso-total').textContent = pesoMostrar + ' kg';
        setPreview('preview-peso', pesoMostrar + ' kg');
    }


    // Paso 6 → nota y RPE
    document.getElementById('nota').addEventListener('input', (e) => {
        setPreview('preview-nota', e.target.value);
    });
    document.getElementById('rpe').addEventListener('input', (e) => {
        setPreview('preview-rpe', e.target.value);
    });

    function reiniciarFormulario() {
        pasoActual = 0;
        grupoSeleccionado = '';
        ejercicioSeleccionado = '';
        series = 0;
        repeticiones = 0;
        pesoEnteroBase = 0;
        pesoEnteroIncremento = 0;
        pesoDecimal = 0;
        document.getElementById('nota').value = '';
        document.getElementById('rpe').value = '';
        document.getElementById('peso-total').textContent = '0';
        ['grupo', 'ejercicio', 'series', 'reps', 'peso', 'rpe', 'nota'].forEach(id => setPreview('preview-' + id, ''));
    }
</script>

<script>
    document.getElementById('btn-toggle-reps')?.addEventListener('click', () => {
        const extra = document.getElementById('reps-extra');
        const btn = document.getElementById('btn-toggle-reps');
        extra.classList.toggle('d-none');
        btn.textContent = extra.classList.contains('d-none') ? 'Mostrar más…' : 'Mostrar menos…';
        // Reasigna eventos por si entraste a paso 4 antes de expandir
        activarReps();
    });
</script>

<script>
    document.getElementById('btn-toggle-add').addEventListener('click', function(e) {
        e.preventDefault();
        const pasos = document.getElementById('pasos');

        // toggle visibilidad
        const oculto = pasos.classList.toggle('d-none');

        if (!oculto) {
            // al abrir: ir al paso 1
            mostrarPaso(1);
            this.textContent = '❌ Cancelar';
        } else {
            // al cerrar: reiniciar y texto
            reiniciarFormulario();
            document.getElementById('registro-construccion').textContent = 'Completa los pasos para construir tu registro...';
            this.textContent = '+ Añadir serie';
        }
    });
</script>




<?= $this->endSection() ?>