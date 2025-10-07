<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Categorías</h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/etiquetas') ?>">Etiquetas</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces') ?>">Volver</a>
        </div>
    </div>
    <form class="d-flex gap-2 mb-3" method="post" action="<?= site_url('enlaces/categorias/guardar') ?>">
        <input class="form-control" name="nombre" placeholder="Nueva categoría">
        <button class="btn btn-primary btn-sm">Agregar</button>
    </form>
    <ul class="list-group">
        <?php foreach ($categorias as $c):
            $total = $conteoPorCategoria[$c['id']] ?? 0; ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <?= esc($c['nombre']) ?>
                    <span class="badge bg-light text-dark border ms-2"><?= $total ?></span>
                </span>
                <a class="btn btn-sm btn-outline-danger" href="<?= site_url('enlaces/categorias/borrar/' . $c['id']) ?>" onclick="return confirm('¿Eliminar?')">Borrar</a>
            </li>
        <?php endforeach; ?>
    </ul>

</div>
<?php $this->endSection(); ?>