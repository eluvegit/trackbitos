<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php $activas = count(array_filter($filas, static fn ($f) => !empty($f['guia']['activa']))); ?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Guías del TPV</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('inventario') ?>" target="_blank" class="fv-gbtn" title="Abrir el TPV inventario"><i class="bi bi-box-arrow-up-right"></i> TPV</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/guias?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Guías del TPV</h1>
        <div class="fv-meta">
            <span><b><?= $activas ?></b> activa<?= $activas === 1 ? '' : 's' ?></span>
            <span><?= count($filas) - $activas ?> oculta<?= count($filas) - $activas === 1 ? '' : 's' ?></span>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Qué son?</summary>
        <div class="fv-cuerpo">
            <span>Asistentes paso a paso que salen en el lateral del <a href="<?= site_url('inventario') ?>" target="_blank">TPV inventario</a> para elegir la pieza correcta (p. ej. cogida → manga → talla). Solo se ven las guías <b>activas</b>, y en ellas solo las combinaciones con una pieza asignada.</span>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <section class="fv-panel pv-estrecha">
        <?php if ($filas === []): ?>
            <p class="fv-vacio">Todavía no hay guías.</p>
        <?php else: ?>
            <div class="pv-lista">
                <?php foreach ($filas as $f): ?>
                    <a href="<?= site_url('piezas/guias/' . (int) $f['guia']['id']) ?>" class="pv-fila">
                        <span class="pv-ico"><?= icono_guia($f['guia']['icono']) ?></span>
                        <div class="pv-cuerpo">
                            <span class="pv-nom"><?= esc($f['guia']['nombre']) ?></span>
                            <div class="pv-sub">
                                <span class="fv-estado <?= $f['guia']['activa'] ? 'e-ok' : 'e-neu' ?>"><?= $f['guia']['activa'] ? 'Activa' : 'Oculta' ?></span>
                                <span><?= (int) $f['pasos'] ?> pasos</span>
                            </div>
                        </div>
                        <span class="pv-n"><?= (int) $f['asignadas'] ?><small><?= $f['asignadas'] === 1 ? 'pieza' : 'piezas' ?></small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="fv-panel pv-estrecha">
        <div class="fv-ph"><h3>Guía nueva</h3></div>
        <form method="post" action="<?= site_url('piezas/guias/crear') ?>" class="fv-campo">
            <?= csrf_field() ?>
            <input type="text" name="nombre" class="fv-input" style="flex: 2 1 200px" placeholder="Nombre" required>
            <input type="text" name="icono" class="fv-input fv-mono" style="flex: 1 1 160px" placeholder="Icono (bi-… o svg-brazo)" value="bi-signpost-split">
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i> Crear guía</button>
        </form>
    </section>
</div>

<?= $this->endSection() ?>
