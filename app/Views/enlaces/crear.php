<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">
<div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Agregar enlace</h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/etiquetas') ?>">Etiquetas</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/categorias') ?>">Categorías</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces') ?>">Volver</a>
        </div>
    </div>

<?php echo view('enlaces/form', [
'categorias'=>$categorias,
'etiquetas'=>$etiquetas,
'action'=>site_url('enlaces/guardar')
]); ?>
</div>
<?php $this->endSection(); ?>