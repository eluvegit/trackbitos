<?php
/**
 * Chips de filtros activos (estilo fv): cada uno lleva a la búsqueda SIN ese
 * filtro. `data-enl-nav` → el JS navega sin recargar.
 */
?>
<?php foreach (($chipsActivos ?? []) as $chip): ?>
    <a href="<?= esc($chip['url'], 'attr') ?>" class="en-chip on" data-enl-nav><?= esc($chip['texto']) ?><i class="bi bi-x"></i></a>
<?php endforeach; ?>
<?php if (!empty($chipsActivos)): ?>
    <a href="<?= site_url('enlaces') ?>" class="en-chip en-chip-limpiar" data-enl-nav>Limpiar todo</a>
<?php endif; ?>
