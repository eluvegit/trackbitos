<?php
    /** $categorias, $activa (?int), $modo: 'enlace' (van a buscar) o 'filtro' (filtran en la página) */
    $activa = $activa ?? null;
?>
<div class="inv-chips" <?= $modo === 'filtro' ? 'data-chips' : '' ?>>
    <?php if ($modo === 'filtro'): ?>
        <button type="button" class="inv-chip <?= $activa === null ? 'activo' : '' ?>" data-cat="0">Todas</button>
    <?php endif; ?>
    <?php foreach ($categorias as $c): ?>
        <?php if ($modo === 'filtro'): ?>
            <button type="button" class="inv-chip <?= $activa === $c['id'] ? 'activo' : '' ?>" data-cat="<?= $c['id'] ?>">
                <?= esc($c['nombre']) ?>
            </button>
        <?php else: ?>
            <a href="<?= inventario_url('buscar?cat=' . $c['id']) ?>" class="inv-chip"><?= esc($c['nombre']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
