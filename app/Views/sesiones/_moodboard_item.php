<?php
// $item: fila de moodboard_items (origen=archivo|enlace)
$esArchivo = $item['origen'] === 'archivo';
$src       = $esArchivo ? base_url($item['ruta_archivo']) : $item['url_externa'];
?>
<div class="gallery-item" data-id="<?= (int) $item['id'] ?>" title="<?= esc($item['nota'] ?? '') ?>">
    <a href="<?= esc($src, 'attr') ?>" target="_blank" rel="noopener">
        <?php if ($esArchivo): ?>
            <img src="<?= esc($src, 'attr') ?>" alt="Referencia moodboard" loading="lazy">
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100 text-center p-2">
                <span><i class="bi bi-link-45deg d-block fs-3"></i><small class="text-muted text-break"><?= esc($item['url_externa']) ?></small></span>
            </div>
        <?php endif; ?>
    </a>
    <button type="button" class="item-borrar" title="Borrar"><i class="bi bi-x"></i></button>
</div>
