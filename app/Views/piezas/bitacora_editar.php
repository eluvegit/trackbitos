<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    /**
     * La bitácora a pantalla completa. Desde la fase 39 ya no es el sitio
     * normal de escribir —eso se hace en el modal de Placas, sin salir del
     * histórico— pero sigue viva y enlazada desde "Ver limpio": es la que
     * funciona sin JavaScript, y la que da sitio de sobra cuando toca
     * sentarse a redactar las conclusiones en condiciones.
     */
    $idPlaca = (int) $placa['id'];
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-journal-text text-primary"></i>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" class="text-decoration-none text-muted fw-normal">
        <?= esc($placa['nombre']) ?>
    </a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Editar</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php // Los datos (placa, piezas, pruebas, enlaces) le llegan solos al trozo
      // incluido: son los mismos que el controlador pasó a esta vista. ?>
<?= $this->include('piezas/_bitacora_form') ?>

<?= $this->include('piezas/_bitacora_js') ?>

<?= $this->endSection() ?>
