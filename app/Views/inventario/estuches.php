<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Estuches<?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<div class="inv-botones">
    <?php foreach ($estuches as $e): ?>
        <a href="<?= inventario_url('estuches/' . (int) $e['estuche']['id']) ?>" class="inv-boton">
            <span class="inv-boton-codigo"><?= esc($e['estuche']['codigo']) ?></span>
            <?php if (!empty($e['estuche']['zona'])): ?>
                <strong><?= esc($e['estuche']['zona']) ?></strong>
            <?php endif; ?>
            <span><?= (int) $e['huecos'] ?> huecos · <?= (int) $e['piezas'] ?> <?= $e['piezas'] === 1 ? 'pieza' : 'piezas' ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php if ($estuches === []): ?>
    <div class="inv-vacio"><i class="bi bi-archive"></i><p>Todavía no hay estuches.</p></div>
<?php endif; ?>
<?= $this->endSection() ?>
