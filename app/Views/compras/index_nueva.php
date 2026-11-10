<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/compras.css') ?>?v=3">
<?php $pendientes = count(array_filter($supermercados, fn ($s) => $s['faltan'] > 0)); ?>
<div class="fv cp">
    <div class="fv-top">
        <div class="fv-crumbs"><span>Compras</span></div>
        <div class="fv-acciones">
            <a class="fv-gbtn" href="<?= site_url('compras/supermercados/nuevo') ?>"><i class="bi bi-plus-lg"></i>Nuevo</a>
            <a class="fv-gbtn" href="<?= site_url('compras/supermercados/gestionar') ?>"><i class="bi bi-sliders"></i>Ordenar y ocultar</a>
            <span class="fv-vsep"></span>
            <a class="fv-gbtn" href="<?= site_url('compras') ?>?nueva=0"><i class="bi bi-arrow-left-right"></i>Clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Compras</h1>
        <div class="fv-meta">
            <?php if ($pendientes): ?>
                <span class="fv-estado e-warn"><?= $pendientes ?> <?= $pendientes === 1 ? 'lista pendiente' : 'listas pendientes' ?></span>
            <?php else: ?>
                <span class="fv-estado e-ok">Nada pendiente</span>
            <?php endif; ?>
            <span class="fv-dot"></span>
            <span><?= count($supermercados) ?> supermercados</span>
        </div>
    </div>

    <div class="cp-supers">
        <?php foreach ($supermercados as $s): ?>
            <a class="cp-super" href="<?= site_url('compras/' . $s['id']) ?>">
                <span class="nom">
                    <?= esc($s['nombre']) ?>
                    <small><?= $s['catalogo'] ?> en catálogo</small>
                </span>
                <?php if ($s['faltan']): ?>
                    <?php if ($s['total'] > 0): ?>
                        <span class="cp-pr"><?= number_format($s['total'], 2, ',', '.') ?> €</span>
                    <?php endif; ?>
                    <span class="fv-estado e-warn"><?= $s['faltan'] ?> <?= $s['faltan'] === 1 ? 'falta' : 'faltan' ?></span>
                <?php else: ?>
                    <span class="fv-estado e-neu">Al día</span>
                <?php endif; ?>
                <i class="bi bi-chevron-right"></i>
            </a>
        <?php endforeach; ?>
        <?php if (!$supermercados): ?>
            <p class="cp-vacio">Todavía no hay supermercados.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
