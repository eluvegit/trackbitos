<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Categorías<?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<div class="inv-botones">
    <?php foreach ($categorias as $c): ?>
        <a href="<?= inventario_url('buscar?cat=' . $c['id']) ?>" class="inv-boton con-foto">
            <span class="inv-boton-foto">
                <?php if ($c['img']): ?>
                    <img src="<?= esc($c['img'], 'attr') ?>" alt="" loading="lazy">
                <?php else: ?>
                    <i class="bi bi-grid"></i>
                <?php endif; ?>
            </span>
            <strong><?= esc($c['nombre']) ?></strong>
            <span><?= (int) $c['total'] ?> <?= $c['total'] === 1 ? 'pieza' : 'piezas' ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php if ($categorias === []): ?>
    <div class="inv-vacio"><i class="bi bi-grid"></i><p>Todavía no hay categorías.</p></div>
<?php endif; ?>
<?= $this->endSection() ?>
