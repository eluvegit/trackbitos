<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">📅 Entrenamiento del <?= date('d/m/Y', strtotime($fecha)) ?></h2>

<div class="row mb-4">
    <div class="col-md-6">
        <form action="#" method="post"> <!-- Luego se hace funcional -->
            <div class="mb-3">
                <label class="form-label">Notas generales del día</label>
                <textarea name="notas_generales" class="form-control" rows="2"><?= esc($entrenamiento['notas_generales'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Lesiones / molestias</label>
                <textarea name="lesiones" class="form-control" rows="2"><?= esc($entrenamiento['lesiones'] ?? '') ?></textarea>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="sin_molestias" value="1" id="sin_molestias"
                    <?= isset($entrenamiento['sin_molestias']) && $entrenamiento['sin_molestias'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="sin_molestias">
                    Día sin molestias
                </label>
            </div>
        </form>
    </div>
</div>



<hr class="my-5">

<h4 class="mb-3">🏋️ Ejercicios registrados en este entrenamiento</h4>

<?php if (empty($ejerciciosEntrenados)): ?>
    <p class="text-muted">No se han añadido ejercicios aún.</p>
<?php else: ?>
    <?php foreach ($ejerciciosEntrenados as $e): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= esc($e['nota_ejercicio'] ? $e['nota_ejercicio'] . ' - ' : '') . esc($e['rpe'] ? 'RPE ' . $e['rpe'] : '') ?></h5>
                <h6 class="card-subtitle mb-2 text-muted">
                    <?= esc($todosLosEjercicios[array_search($e['ejercicio_id'], array_column($todosLosEjercicios, 'id'))]['nombre'] ?? 'Ejercicio') ?>
                </h6>
                <?php if (!empty($series[$e['id']])): ?>
                    <ul class="list-group list-group-flush mt-3">
                        <?php foreach ($series[$e['id']] as $s): ?>
                            <li class="list-group-item">
                                <?= $s['numero_series'] ?>x<?= $s['repeticiones'] ?> x <?= $s['peso'] ?>kg
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mt-2">Sin series registradas.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<hr class="my-5">

<h4 class="mb-4">➕ Añadir nueva serie</h4>
<!-- Spans ocultos para vista previa -->
<span id="preview-grupo" class="d-none"></span>
<span id="preview-ejercicio" class="d-none"></span>
<span id="preview-series" class="d-none"></span>
<span id="preview-reps" class="d-none"></span>
<span id="preview-peso" class="d-none"></span>
<span id="preview-rpe" class="d-none"></span>
<span id="preview-nota" class="d-none"></span>

<div class="mb-4">
    <button class="btn btn-lg btn-primary" onclick="mostrarPaso(1)">+ Añadir serie</button>
</div>

<div class="alert alert-primary fs-5 text-center" id="registro-construccion">
    Completa los pasos para construir tu registro...
</div>


<!-- Paso 1: Grupo muscular -->
<div id="paso-1" class="paso d-none">
    <h5 class="mb-3">1️⃣ Elige un grupo muscular</h5>
    <div class="row row-cols-2 row-cols-md-4 g-3">
        <?php
        $grupos = [
            'biceps' => 'Bíceps',
            'triceps' => 'Tríceps',
            'hombros' => 'Hombros',
            'espalda' => 'Espalda',
            'pecho' => 'Pecho',
            'abdominales' => 'Abdominales',
            'piernas' => 'Piernas',
            'calentamientos' => 'Calentamientos',
            'movilidad' => 'Movilidad',
            'cardio' => 'Cardio',
            'especificos' => 'Específicos',
            'recuperacion' => 'Recuperación'
        ];
        foreach ($grupos as $clave => $nombre): ?>
            <div class="col">
                <div class="card text-center shadow-sm h-100 grupo-muscular-card" data-grupo="<?= $clave ?>">
                    <div class="card-body">
                        <h6 class="card-title"><?= $nombre ?></h6>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Paso 2: Ejercicio -->
<div id="paso-2" class="paso d-none mt-4">
    <h5 class="mb-3">2️⃣ Elige un ejercicio</h5>
    <div id="contenedor-ejercicios" class="row row-cols-2 row-cols-md-3 g-3"></div>

    <div class="text-end mt-4">
        <button type="button" class="btn btn-outline-secondary" onclick="mostrarPaso(pasoActual - 1)">⬅ Volver</button>
    </div>

</div>

<!-- Paso 3: Número de series -->
<div id="paso-3" class="paso d-none mt-4">
    <h5 class="mb-3">3️⃣ Número de series</h5>
    <div class="d-flex flex-wrap gap-2">
        <?php for ($i = 1; $i <= 12; $i++): ?>
            <button type="button" class="btn btn-outline-primary btn-serie" data-serie="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
    </div>
    <div class="text-end mt-4">
        <button type="button" class="btn btn-outline-secondary" onclick="mostrarPaso(pasoActual - 1)">⬅ Volver</button>
    </div>

</div>


<!-- Paso 4: Repeticiones -->
<div id="paso-4" class="paso d-none mt-4">
    <h5 class="mb-3">4️⃣ Número de repeticiones</h5>
    <div class="d-flex flex-wrap gap-2">
        <?php for ($i = 1; $i <= 60; $i++): ?>
            <button type="button" class="btn btn-outline-primary btn-reps" data-reps="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
    </div>
    <div class="text-end mt-4">
        <button type="button" class="btn btn-outline-secondary" onclick="mostrarPaso(pasoActual - 1)">⬅ Volver</button>
    </div>

</div>


<!-- Paso 5: Peso -->
<div id="paso-5" class="paso d-none mt-4">
    <h5 class="mb-3">5️⃣ Peso</h5>
    <div class="row">
        <div class="col-md-6">
            <h6>Parte entera</h6>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php
                // Valores base
                for ($i = 1; $i <= 10; $i++) {
                    echo "<button type='button' class='btn btn-outline-secondary btn-peso-entero' data-valor='{$i}'>{$i}</button>";
                }
                for ($i = 20; $i <= 200; $i += 10) {
                    echo "<button type='button' class='btn btn-outline-secondary btn-peso-entero' data-valor='{$i}'>{$i}</button>";
                }
                ?>
                <div class="w-100"></div>
                <!-- Botones de suma incremental -->
                <?php for ($i = 0; $i <= 9; $i++): ?>
                    <button type="button" class="btn btn-outline-info btn-peso-entero-incremento" data-incremento="<?= $i ?>">+<?= $i ?></button>
                <?php endfor; ?>

            </div>
        </div>
        <div class="col-md-6">
            <h6>Parte decimal</h6>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-danger btn-peso-decimal" data-valor="0">0 (borrar)</button>
                <?php foreach ([0.125, 0.25, 0.5, 0.75] as $d): ?>
                    <button type="button" class="btn btn-outline-secondary btn-peso-decimal" data-valor="<?= $d ?>"><?= $d ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <p class="mt-2">Peso total: <strong id="peso-total">0</strong> kg</p>
    <button class="btn btn-primary mt-2" onclick="mostrarPaso(6)">Confirmar peso</button>
    <div class="text-end mt-4">
        <button type="button" class="btn btn-outline-secondary" onclick="mostrarPaso(pasoActual - 1)">⬅ Volver</button>
    </div>

</div>



<!-- Paso 6: Nota y RPE -->
<div id="paso-6" class="paso d-none mt-4">
    <h5 class="mb-3">6️⃣ Nota (opcional)</h5>
    <div class="mb-3">
        <label for="nota" class="form-label">Nota</label>
        <textarea id="nota" class="form-control" rows="2"></textarea>
    </div>
    <div class="mb-3">
        <label for="rpe" class="form-label">RPE (opcional)</label>
        <input type="text" id="rpe" class="form-control" placeholder="Ej: 7.5, fácil, muy exigente...">
    </div>
    <button class="btn btn-success" onclick="guardarSerie()">✅ Guardar serie</button>
    <div class="text-end mt-4">
        <button type="button" class="btn btn-outline-secondary" onclick="mostrarPaso(pasoActual - 1)">⬅ Volver</button>
    </div>

</div>

<script>
    let pasoActual = 0;
    let grupoSeleccionado = '';
    let ejercicioSeleccionado = '';
    let series = 0, repeticiones = 0;
    let pesoEnteroBase = 0;
    let pesoEnteroIncremento = 0;
    let pesoDecimal = 0;

    function mostrarPaso(n) {
        document.querySelectorAll('.paso').forEach(p => p.classList.add('d-none'));
        const paso = document.getElementById('paso-' + n);
        if (paso) paso.classList.remove('d-none');
        pasoActual = n;
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
        if (seriesTxt && reps && peso) frase += `${seriesTxt}x${reps}x${peso}kg `;
        if (rpe) frase += `RPE: ${rpe} `;
        if (nota) frase += `"${nota}"`;

        document.getElementById('registro-construccion').textContent = frase.trim() || 'Completa los pasos para construir tu registro...';
    }

    // Paso 1: grupo muscular
    document.querySelectorAll('.grupo-muscular-card').forEach(card => {
        card.addEventListener('click', () => {
            grupoSeleccionado = card.dataset.grupo;
            setPreview('preview-grupo', card.textContent.trim());
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

    // Delegación de eventos para series y repeticiones
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-serie')) {
            series = e.target.dataset.serie;
            setPreview('preview-series', series);
            mostrarPaso(4);
        }
        if (e.target.classList.contains('btn-reps')) {
            repeticiones = e.target.dataset.reps;
            setPreview('preview-reps', repeticiones);
            mostrarPaso(5);
        }
    });

    // Paso 5: peso
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
        document.getElementById('peso-total').textContent = total.toFixed(3);
        setPreview('preview-peso', total.toFixed(3) + ' kg');
    }

    document.getElementById('nota').addEventListener('input', (e) => {
        setPreview('preview-nota', e.target.value);
    });

    document.getElementById('rpe').addEventListener('input', (e) => {
        setPreview('preview-rpe', e.target.value);
    });

    function guardarSerie() {
        const nota = document.getElementById('nota').value;
        const rpe = document.getElementById('rpe').value;
        const peso = (pesoEnteroBase + pesoEnteroIncremento) + pesoDecimal;

        setPreview('preview-nota', nota);
        setPreview('preview-rpe', rpe);

        alert(`📌 Registro simulado:
Grupo: ${getPreview('preview-grupo')}
Ejercicio: ${getPreview('preview-ejercicio')}
Series: ${series}
Reps: ${repeticiones}
Peso: ${peso.toFixed(3)} kg
RPE: ${rpe}
Nota: ${nota}`);

        reiniciarFormulario();
        mostrarPaso(1);
    }

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





<?= $this->endSection() ?>