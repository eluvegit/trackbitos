<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php /** Enlaces — agregar (estilo fv). */ ?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'migas'  => ['Agregar' => null],
        'titulo' => 'Agregar enlace',
    ]) ?>
    <?= view('enlaces/_form_nueva', [
        'categorias' => $categorias,
        'etiquetas'  => $etiquetas,
        'action'     => site_url('enlaces/guardar'),
    ]) ?>
</div>
<?php $this->endSection(); ?>
