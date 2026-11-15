<?php
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], [esc($modulo), $migas ? site_url($ruta) : null]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('recordatorios.css') ?>">
<link rel="stylesheet" href="<?= $css('bots.css') ?>">

<div class="rc-cab">
    <div class="rc-top">
        <nav class="fv-crumbs rc-migas">
            <?php foreach ($niveles as $i => [$texto, $url]): ?>
                <?php if ($i > 0): ?><i class="bi bi-chevron-right"></i><?php endif; ?>
                <?php if ($url): ?><a href="<?= $url ?>"><?= $texto ?></a><?php else: ?><span class="t1 ult"><?= $texto ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="fv-acciones"><a class="fv-ibtn" href="<?= current_url() ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a></div>
    </div>
    <div>
        <div class="rc-h1"><h1><?= $titulo ?></h1><?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?></div>
        <?php if ($sub): ?><div class="rc-sub"><?= $sub ?></div><?php endif; ?>
    </div>
</div>
