<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Escenas — <?= esc($proyecto['titulo']) ?></h1>
        <a class="btn btn-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/create') ?>">Nueva escena</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Bloque</th>
                    <th>Ubicación</th>
                    <th>Tipo de plano</th>
                    <th>Ángulo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($escenas ?? []) as $e): ?>
                    <tr>
                        <td><?= esc($e['orden']) ?></td>
                        <td><?= esc($e['escena_bloque']) ?></td>
                        <td><?= esc($e['escena_ubicacion']) ?></td>
                        <td><?= esc($e['camara_tipo_plano']) ?></td>
                        <td><?= esc($e['camara_angulo']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">Editar</a>
                            <a class="btn btn-sm btn-outline-danger" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/delete/' . $e['id']) ?>" onclick="return confirm('¿Eliminar escena?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a class="btn btn-secondary" href="<?= site_url('rodajes') ?>">Volver a proyectos</a>
</div>
<?= $this->endSection() ?>