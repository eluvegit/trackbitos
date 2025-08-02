<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-bell fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Lentillas <span class="text-muted">/</span> <span class="text-secondary">Avisos de Sustitución</span></h2>
</div>

<a href="<?= site_url('lentillas') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Lentillas</a>
<a href="<?= site_url('lentillas/avisos/crear') ?>" class="btn btn-primary mb-3">+ Nuevo Aviso</a>

<?php foreach ($avisos as $a): ?>
    <div class="alert <?= $a['dias_pasados'] !== null && $a['dias_pasados'] > $a['dias_maximos'] ? 'alert-danger' : 'alert-success' ?>">
        <div class="d-flex justify-content-between">
            <div>
                <strong><?= ucfirst($a['item']) ?>:</strong>
                <?php if ($a['dias_pasados'] !== null): ?>
                    Último cambio: <?= date('d/m/Y', strtotime($a['fecha'])) ?> —
                    hace <?= $a['dias_pasados'] ?> días (máximo: <?= $a['dias_maximos'] ?> días).
                <?php else: ?>
                    Aún no se ha registrado ninguna sustitución.
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= site_url('lentillas/avisos/editar/' . $a['id']) ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                <form action="<?= site_url('lentillas/avisos/eliminar/' . $a['id']) ?>" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que quieres eliminar este aviso?')">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
