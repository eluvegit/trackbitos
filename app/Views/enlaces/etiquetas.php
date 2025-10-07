<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Etiquetas</h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/categorias') ?>">Categorías</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces') ?>">Volver</a>
        </div>
    </div>
    <form class="d-flex gap-2 mb-3" method="post" action="<?= site_url('enlaces/etiquetas/guardar') ?>">
        <input class="form-control" name="nombre" placeholder="Nueva etiqueta">
        <button class="btn btn-primary btn-sm">Agregar</button>
    </form>
    <ul class="list-group">
        <?php foreach ($etiquetas as $t): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?= esc($t['nombre']) ?></span>
                <a class="btn btn-sm btn-outline-danger" href="<?= site_url('enlaces/etiquetas/borrar/' . $t['id']) ?>" onclick="return confirm('¿Eliminar?')">Borrar</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php $this->endSection(); ?>