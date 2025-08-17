<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">➕ Nuevo ejercicio</h2>
<div class="mb-4">
    <a href="<?= site_url('gimnasio/ejercicios/') ?>" class="btn btn-outline-secondary">← Volver a ejercicios</a>
</div>
<div class="row justify-content-center">
    <div class="col-md-6">

        <form action="<?= site_url('gimnasio/ejercicios/store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del ejercicio</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>

            <?php $grupoSeleccionado = $_GET['grupo'] ?? ''; ?>

            <div class="mb-3">
                <label for="grupo_muscular" class="form-label">Grupo muscular</label>
                <select name="grupo_muscular" id="grupo_muscular" class="form-select" required>
                    <option value="">Selecciona uno...</option>
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
                    foreach ($grupos as $valor => $etiqueta): ?>
                        <option value="<?= $valor ?>" <?= $grupoSeleccionado === $valor ? 'selected' : '' ?>>
                            <?= $etiqueta ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= site_url('gimnasio/ejercicios') ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">Guardar ejercicio</button>
            </div>
        </form>

        <div id="ejerciciosExistentes" class="mt-5" style="display:none;">
            <h5 class="text-center">Ejercicios ya registrados</h5>
            <ul class="list-group" id="listaEjercicios"></ul>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectGrupo = document.getElementById('grupo_muscular');
        const listaEjercicios = document.getElementById('listaEjercicios');
        const contenedor = document.getElementById('ejerciciosExistentes');

        function cargarEjercicios(grupo) {
            if (!grupo) {
                contenedor.style.display = 'none';
                listaEjercicios.innerHTML = '';
                return;
            }

            fetch("<?= site_url('gimnasio/ejercicios/por-grupo') ?>/" + grupo)
                .then(res => res.json())
                .then(data => {
                    listaEjercicios.innerHTML = '';
                    if (data.length === 0) {
                        listaEjercicios.innerHTML = '<li class="list-group-item text-muted">No hay ejercicios registrados</li>';
                    } else {
                        data.forEach(e => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            li.textContent = e.nombre;
                            listaEjercicios.appendChild(li);
                        });
                    }
                    contenedor.style.display = 'block';
                });
        }

        // Carga automática si ya hay grupo en URL (al venir desde el botón "+")
        if (selectGrupo.value) {
            cargarEjercicios(selectGrupo.value);
        }

        selectGrupo.addEventListener('change', function() {
            cargarEjercicios(this.value);
        });
    });
</script>

<?= $this->endSection() ?>