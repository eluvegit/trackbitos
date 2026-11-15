<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $n = count($rows); ?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa'   => 'alimentos',
        'titulo'   => 'Alimentos',
        'sub'      => '<span id="cmCuenta">' . $n . ' ' . ($n === 1 ? 'alimento' : 'alimentos') . '</span><span class="fv-dot"></span><span>valores por 100 g</span>',
        'acciones' => '<a class="fv-btn" href="' . site_url('comidas/alimentos/ranking/proteinas') . '"><i class="bi bi-trophy"></i>Rankings</a><a class="fv-btn fv-btn-pri" href="' . site_url('comidas/alimentos/create') . '"><i class="bi bi-plus-lg"></i>Nuevo</a>',
    ]) ?>

    <form class="cm-busca" id="cmBuscador" action="<?= site_url('comidas/alimentos') ?>" method="get">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="fv-input" value="<?= esc($q ?? '') ?>" placeholder="Buscar por nombre…" autocomplete="off">
        <span class="cm-cargando" id="cmCargando" hidden></span>
    </form>

    <div class="rc-lista cm-lista" id="cmFilas">
        <?= view('comidas/alimentos/_rows_nueva', ['rows' => $rows]) ?>
    </div>
</div>
<?= view('comidas/_buscador_vivo') ?>
<?= $this->endSection() ?>
