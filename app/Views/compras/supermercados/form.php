<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/compras.css') ?>?v=4">
<?php
$editar = isset($supermercado);
$volver = $volver ?? site_url('compras');
?>
<div class="fv cp">
    <div class="fv-top">
        <div class="fv-crumbs">
            <a href="<?= site_url('compras') ?>">Compras</a>
            <i class="bi bi-chevron-right"></i>
            <?php if ($editar): ?>
                <a href="<?= $volver ?>"><?= esc($supermercado['nombre']) ?></a>
                <i class="bi bi-chevron-right"></i>
                <span>Editar</span>
            <?php else: ?>
                <span>Nuevo</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="fv-hero">
        <h1><?= $editar ? esc($supermercado['nombre']) : 'Nuevo supermercado' ?></h1>
    </div>

    <div class="fv-panel">
        <form class="cp-form" method="post" action="<?= $editar ? site_url('compras/supermercados/actualizar/' . $supermercado['id']) : site_url('compras/supermercados/guardar') ?>">
            <?= csrf_field() ?>
            <label class="cp-f">
                <span>Nombre</span>
                <input type="text" class="fv-input" name="nombre" required autocomplete="off" value="<?= esc($supermercado['nombre'] ?? '') ?>" <?= $editar ? '' : 'autofocus' ?>>
            </label>
            <label class="cp-f">
                <span>Descripción <small>opcional</small></span>
                <textarea class="fv-input" name="descripcion" rows="3"><?= esc($supermercado['descripcion'] ?? '') ?></textarea>
            </label>
            <div class="cp-botones">
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i><?= $editar ? 'Guardar cambios' : 'Crear supermercado' ?></button>
                <a class="fv-btn" href="<?= $volver ?>">Cancelar</a>
            </div>
        </form>

        <?php if ($editar): ?>
            <form class="cp-borrar" action="<?= site_url('compras/supermercados/' . $supermercado['id'] . '/borrar') ?>" method="post"
                  onsubmit="return confirm('¿Borrar <?= esc($supermercado['nombre'], 'js') ?>? Se pierde su lista. Si solo quieres quitarlo de la portada, ocúltalo en Ordenar y ocultar.')">
                <?= csrf_field() ?>
                <button type="submit" class="fv-btn fv-btn-sm fv-btn-peligro"><i class="bi bi-trash"></i>Borrar supermercado</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
