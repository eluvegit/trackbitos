<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php /** Enlaces — editar (estilo fv). */ ?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'migas'  => ['Editar' => null],
        'titulo' => 'Editar enlace',
        'acciones' => '<a class="fv-btn" href="' . site_url('enlaces/pagina/' . (int) $enlace['id']) . '"><i class="bi bi-journal-text"></i>Página</a>',
    ]) ?>
    <?= view('enlaces/_form_nueva', [
        'enlace'      => $enlace,
        'categorias'  => $categorias,
        'etiquetas'   => $etiquetas,
        'selCats'     => $selCats,
        'selTagNames' => $selTagNames,
        'action'      => site_url('enlaces/actualizar/' . $enlace['id']),
    ]) ?>
</div>
<?php $this->endSection(); ?>
