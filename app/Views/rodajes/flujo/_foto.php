<?php
$clase ??= '';
$txt = rod_texto_idea($i);
?>
<?php if ($i['origen'] === 'archivo'): ?>
    <a class="rf-foto <?= $clase ?>" href="<?= base_url($i['ruta_archivo']) ?>" target="_blank" rel="noopener" title="<?= esc($txt ?: 'Ver en grande', 'attr') ?>"><img src="<?= base_url($i['ruta_archivo']) ?>" alt="" loading="lazy"></a>
<?php elseif ($i['origen'] === 'enlace'): ?>
    <a class="rf-foto txt <?= $clase ?>" href="<?= esc($i['url_externa'], 'attr') ?>" target="_blank" rel="noopener" title="<?= esc($i['url_externa'], 'attr') ?>"><i class="bi bi-link-45deg"></i><span><?= esc($txt) ?></span></a>
<?php else: ?>
    <span class="rf-foto txt <?= $clase ?>" title="<?= esc($txt, 'attr') ?>"><i class="bi bi-chat-left-text"></i><span><?= esc($txt) ?></span></span>
<?php endif; ?>
