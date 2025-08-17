<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Mesociclos</h1>

    </div>

    <div class="mb-4">
        <a href="<?= site_url('gimnasio/') ?>" class="btn btn-outline-secondary">← Volver al gimnasio</a>
        <a class="btn btn-primary" href="<?= site_url('gimnasio/mesociclos/nuevo') ?>">Nuevo plan</a>
    </div>

    <?php if (empty($planes)): ?>
        <div class="alert alert-secondary">No hay planes aún.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Ejercicio</th>
                        <th>e1RM</th>
                        <th>Pend.</th>
                        <th>Hechos</th>
                        <th>Progreso</th>
                        <th>Siguiente</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planes as $p): ?>
                        <tr>
                            <td>#<?= (int)$p['id'] ?></td>
                            <td><?= esc($p['nombre']) ?></td>
                            <td><span class="badge bg-warning text-dark"><?= esc($p['ejercicio']) ?></span></td>
                            <td><?= number_format((float)$p['e1rm_base'], 1) ?> kg</td>
                            <td><?= (int)$p['_pendientes'] ?></td>
                            <td><?= (int)$p['_hechos'] ?></td>
                            <td style="min-width:160px;">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: <?= $p['_pendientes'] === 0 ? 100 : (int)$p['_progreso'] ?>%;"
                                        aria-valuenow="<?= $p['_pendientes'] === 0 ? 100 : (int)$p['_progreso'] ?>"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <?= $p['_pendientes'] === 0 ? 100 : (int)$p['_progreso'] ?>%
                                    </div>
                                </div>
                            </td>

                            <td><?= $p['_siguiente'] !== null ? '#' . (int)$p['_siguiente'] : '-' ?></td>
                            <td class="text-center text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('gimnasio/mesociclos/' . $p['id']) ?>">Ver</a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('gimnasio/mesociclos/' . $p['id'] . '/simplificado') ?>">Simplificado</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>