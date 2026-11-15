<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Avisos del coche (estilo fv): los recordatorios de categoría «vehiculo»
 * de la lista común.
 *
 * @var array $avisos filas de RecordatorioServicio::lista('vehiculo')
 */
$n = count($avisos);
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'   => 'coche',
        'activa'   => 'avisos',
        'titulo'   => 'Avisos del coche',
        'sub'      => '<span>' . $n . ' ' . ($n === 1 ? 'aviso' : 'avisos') . '</span><span class="fv-dot"></span><a href="' . site_url('recordatorios') . '">Son los mismos que en Recordatorios <i class="bi bi-arrow-right"></i></a>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('coche/recordatorios/nuevo') . '"><i class="bi bi-plus-lg"></i>Nuevo</a>',
    ]) ?>

    <?= view('recordatorios/_lista', ['recordatorios' => $avisos, 'volver' => 'coche', 'vacio' => 'No hay avisos del coche todavía.']) ?>
</div>
<?= $this->endSection() ?>
