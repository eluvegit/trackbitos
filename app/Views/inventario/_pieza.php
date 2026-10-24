<?php
    /** @var array $p pieza de InventarioCatalogo::piezas() */
    $codigo = $p['codigo'] ?? null;
    $enlace = inventario_hueco_url($p);
?>
<article class="inv-pieza" data-pieza
    data-nombre="<?= esc($p['nombre'], 'attr') ?>"
    data-codigo="<?= esc($codigo ?? '', 'attr') ?>"
    data-stock="<?= (int) $p['stock'] ?>"
    data-cat="<?= (int) ($p['categoria_id'] ?? 0) ?>">
    <button type="button" class="inv-pieza-foto" data-foto
        data-grande="<?= esc($p['img_grande'] ?? '', 'attr') ?>"
        data-nombre="<?= esc($p['nombre'], 'attr') ?>"
        data-codigo="<?= esc($codigo ?? 'Sin ubicación', 'attr') ?>"
        data-stock="<?= (int) $p['stock'] ?>"
        data-sin-hueco="<?= $codigo ? '0' : '1' ?>"
        data-hueco-url="<?= esc($enlace ?? '', 'attr') ?>"
        <?= $p['img'] ? '' : 'disabled' ?>>
        <?php if ($p['img']): ?>
            <img src="<?= esc($p['img'], 'attr') ?>" alt="" loading="lazy">
            <span class="inv-lupa"><i class="bi bi-arrows-fullscreen"></i></span>
        <?php else: ?>
            <i class="bi bi-image inv-sin-foto"></i>
        <?php endif; ?>
    </button>
    <div class="inv-pieza-datos">
        <?php if ($enlace): ?>
            <a href="<?= esc($enlace, 'attr') ?>" class="inv-codigo" title="Ver en el estuche"><?= esc($codigo) ?> <i class="bi bi-box-arrow-up-right"></i></a>
        <?php else: ?>
            <span class="inv-codigo sin">Sin ubicación</span>
        <?php endif; ?>
        <h3><?= esc($p['nombre']) ?></h3>
        <span class="inv-stock <?= $p['stock'] > 0 ? '' : 'cero' ?>"><strong><?= (int) $p['stock'] ?></strong> uds.</span>
    </div>
</article>
