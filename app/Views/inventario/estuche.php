<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>
<a href="<?= site_url('inventario/estuches') ?>" class="inv-volver" aria-label="Volver a estuches"><i class="bi bi-arrow-left"></i></a>
Estuche <?= esc($estuche['codigo']) ?>
<?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<?php if (!empty($estuche['zona'])): ?>
    <p class="inv-subtitulo"><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></p>
<?php endif; ?>

<div class="inv-huecos">
    <?php foreach ($huecos as $h): ?>
        <section class="inv-hueco <?= $h['piezas'] === [] ? 'vacio' : '' ?>">
            <header>
                <span class="inv-codigo grande"><?= esc($h['codigo']) ?></span>
                <?php if ($h['piezas'] !== []): ?>
                    <span class="inv-hueco-total"><?= count($h['piezas']) ?> <?= count($h['piezas']) === 1 ? 'pieza' : 'piezas' ?></span>
                <?php endif; ?>
            </header>
            <?php if ($h['piezas'] === []): ?>
                <p class="inv-hueco-vacio">Vacío</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($h['piezas'] as $p): ?>
                        <li>
                            <button type="button" class="inv-mini" data-foto
                                data-grande="<?= esc($p['img_grande'] ?? '', 'attr') ?>"
                                data-nombre="<?= esc($p['nombre'], 'attr') ?>"
                                data-codigo="<?= esc($h['codigo'], 'attr') ?>"
                                data-stock="<?= (int) $p['stock'] ?>"
                                <?= $p['img'] ? '' : 'disabled' ?>>
                                <?php if ($p['img']): ?>
                                    <img src="<?= esc($p['img'], 'attr') ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-image"></i>
                                <?php endif; ?>
                            </button>
                            <span class="inv-hueco-nombre"><?= esc($p['nombre']) ?></span>
                            <span class="inv-stock <?= $p['stock'] > 0 ? '' : 'cero' ?>"><strong><?= (int) $p['stock'] ?></strong> uds.</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
