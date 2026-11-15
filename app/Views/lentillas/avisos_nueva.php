<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Avisos de Lentillas (estilo fv): los recordatorios de categoría
 * «lentillas» de la lista común.
 *
 * @var array $avisos filas de RecordatorioServicio::lista('lentillas')
 */
$n = count($avisos);
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'   => 'lentillas',
        'activa'   => 'avisos',
        'titulo'   => 'Avisos de lentillas',
        'sub'      => '<span>' . $n . ' ' . ($n === 1 ? 'aviso' : 'avisos') . '</span><span class="fv-dot"></span><a href="' . site_url('recordatorios') . '">Son los mismos que en Recordatorios <i class="bi bi-arrow-right"></i></a>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('lentillas/avisos/crear') . '"><i class="bi bi-plus-lg"></i>Nuevo</a>',
    ]) ?>

    <?= view('recordatorios/_lista', ['recordatorios' => $avisos, 'volver' => 'lentillas', 'vacio' => 'No hay avisos de lentillas todavía.']) ?>

    <p class="rc-info"><i class="bi bi-info-circle"></i><span>Cada aviso se renueva solo al apuntar el cambio de su elemento en «Cambios», y al revés: marcarlo como hecho aquí apunta el cambio y descuenta el stock.</span></p>
</div>
<?= $this->endSection() ?>
