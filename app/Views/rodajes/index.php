<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Rodajes</h1>
        <a class="btn btn-primary" href="<?= site_url('rodajes/create') ?>">Nuevo proyecto</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($proyectos ?? []) as $p): ?>
                    <tr>
                        <td><?= esc($p->id ?? $p['id']) ?></td>
                        <td><?= esc($p->titulo ?? $p['titulo']) ?></td>
                        <td><?= esc($p->descripcion ?? $p['descripcion']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('rodajes/edit/' . ($p->id ?? $p['id'])) ?>">Editar</a>
                            <a class="btn btn-sm btn-outline-danger" href="<?= site_url('rodajes/delete/' . ($p->id ?? $p['id'])) ?>" onclick="return confirm('¿Eliminar proyecto?')">Eliminar</a>
                            <a class="btn btn-sm btn-success" href="<?= site_url('rodajes/' . ($p->id ?? $p['id']) . '/escenas') ?>">Escenas</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>