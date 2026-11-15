<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $n = count($rows); ?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa'   => 'recetas',
        'titulo'   => 'Recetas',
        'sub'      => '<span id="cmCuenta">' . $n . ' ' . ($n === 1 ? 'receta' : 'recetas') . '</span>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('comidas/recetas/create') . '"><i class="bi bi-plus-lg"></i>Nueva</a>',
    ]) ?>

    <form class="cm-busca" id="cmBuscador" action="<?= site_url('comidas/recetas') ?>" method="get">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="fv-input" value="<?= esc($q ?? '') ?>" placeholder="Buscar por nombre…" autocomplete="off">
        <span class="cm-cargando" id="cmCargando" hidden></span>
    </form>

    <div class="rc-lista cm-lista" id="cmFilas">
        <?= view('comidas/recetas/_rows_nueva', ['rows' => $rows]) ?>
    </div>
</div>
<?= view('comidas/_buscador_vivo') ?>
<?= $this->endSection() ?>
