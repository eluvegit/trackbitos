<?php
/**
 * Miniatura de moodboard (estilo fv). El mismo marcado se genera en JS al
 * subir (assets/js/sesiones.js → htmlItem): si cambias uno, cambia el otro.
 *
 * @var array $item fila de moodboard_items
 */
$esArchivo = $item['origen'] === 'archivo';
$esTexto = $item['origen'] === 'texto';
$src = $esArchivo ? base_url($item['ruta_archivo']) : $item['url_externa'];
$host = $esArchivo ? '' : (parse_url((string) $item['url_externa'], PHP_URL_HOST) ?: $item['url_externa']);
?>
<div class="ss-item<?= $esArchivo ? '' : ' enlace' ?><?= ($item['estado'] ?? '') === 'descartada' ? ' descartada' : '' ?>" data-id="<?= (int) $item['id'] ?>" title="<?= esc($item['nota'] ?? '', 'attr') ?>">
    <?php if ($esTexto): ?>
        <a class="ss-texto"><i class="bi bi-chat-left-text"></i><small><?= esc($item['nota']) ?></small></a>
    <?php else: ?>
    <a href="<?= esc($src, 'attr') ?>" target="_blank" rel="noopener">
        <?php if ($esArchivo): ?>
            <img src="<?= esc($src, 'attr') ?>" alt="" loading="lazy">
        <?php else: ?>
            <i class="bi bi-link-45deg"></i><small><?= esc(preg_replace('/^www\./', '', $host)) ?></small>
        <?php endif; ?>
    </a>
    <?php endif; ?>
    <?php if (!$esTexto && trim((string) ($item['nota'] ?? '')) !== ''): ?><span class="nota"><?= esc($item['nota']) ?></span><?php endif; ?>
    <div class="ss-item-acc">
        <button type="button" class="ss-mover" title="Mover a otra situación"><i class="bi bi-arrows-move"></i></button>
        <button type="button" class="ss-item-borrar" title="Borrar"><i class="bi bi-x-lg"></i></button>
    </div>
</div>
