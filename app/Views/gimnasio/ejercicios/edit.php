<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">✏️ Editar ejercicio</h2>
<div class="mb-4">
    <a href="<?= site_url('gimnasio/ejercicios/') ?>" class="btn btn-outline-secondary">← Volver a ejercicios</a>
</div>
<div class="row justify-content-center">
    <div class="col-md-6">

        <form action="<?= site_url('gimnasio/ejercicios/update/' . $ejercicio['id']) ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del ejercicio</label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="<?= esc($ejercicio['nombre']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="grupo_muscular" class="form-label">Grupo muscular</label>
                <select name="grupo_muscular" id="grupo_muscular" class="form-select" required>
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
                        'especificos' => 'Ejercicios específicos',
                        'recuperacion' => 'Recuperación'
                    ];
                    foreach ($grupos as $valor => $etiqueta): ?>
                        <option value="<?= $valor ?>" <?= $ejercicio['grupo_muscular'] === $valor ? 'selected' : '' ?>>
                            <?= $etiqueta ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= site_url('gimnasio/ejercicios') ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar ejercicio</button>
            </div>
        </form>

    </div>
</div>

<?= $this->endSection() ?>
