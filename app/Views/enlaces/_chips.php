<?php
/**
 * Chips de filtros activos (cada uno enlaza a la búsqueda SIN ese filtro).
 * Se re-renderiza en cada búsqueda en vivo — por eso los enlaces llevan
 * `data-enl-nav`: el JS los intercepta y navega sin recargar. Sin JS,
 * siguen siendo enlaces normales.
 *
 * Se incluye dentro de un contenedor `#enlChips` que ya vive en index.php.
 */
?>
<?php foreach (($chipsActivos ?? []) as $chip): ?>
    <a href="<?= esc($chip['url'], 'attr') ?>" class="enl-active-chip" data-enl-nav>
        <?= esc($chip['texto']) ?> <i class="bi bi-x"></i>
    </a>
<?php endforeach; ?>
<?php if (!empty($chipsActivos)): ?>
    <a href="<?= site_url('enlaces') ?>" class="enl-active-chip enl-active-chip-clear" data-enl-nav>Limpiar todo</a>
<?php endif; ?>
