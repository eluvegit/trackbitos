<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">🏋️ Lista de ejercicios</h2>
<div class="mb-4">
    <a href="<?= site_url('gimnasio/') ?>" class="btn btn-outline-secondary">← Volver al gimnasio</a>
    <a href="<?= site_url('gimnasio/ejercicios/create') ?>" class="btn btn-outline-success">+ Nuevo ejercicio</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<?php
$grupos = [];
foreach ($ejercicios as $e) {
    $grupos[$e['grupo_muscular']][] = $e;
}
?>

<?php foreach ($grupos as $grupo => $ejerciciosDelGrupo): ?>
    <div class="d-flex justify-content-between align-items-center mt-5 mb-1">
        <h4 class="mb-0"><?= ucfirst($grupo) ?></h4>
        <a href="<?= site_url('gimnasio/ejercicios/create?grupo=' . urlencode($grupo)) ?>" class="btn btn-sm btn-outline-success">+ Añadir a <?= ucfirst($grupo) ?></a>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-3">
        <?php foreach ($ejerciciosDelGrupo as $ejercicio): ?>
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= esc($ejercicio['nombre']) ?></h5>
                            <a href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $ejercicio['id']) ?>"
                                class="btn btn-sm"
                                title="Ver estadísticas (fechas y series)">
                                📈
                            </a>
                        </div>

                        <div class="mt-auto d-flex justify-content-between">
                            <form action="<?= site_url('gimnasio/ejercicios/delete/' . $ejercicio['id']) ?>" method="post" onsubmit="return confirm('¿Eliminar este ejercicio?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm">Eliminar</button>
                            </form>
                            <a href="<?= site_url('gimnasio/ejercicios/edit/' . $ejercicio['id']) ?>" class="btn btn-sm">Editar</a>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
    <hr>
<?php endforeach; ?>

<?= $this->endSection() ?>