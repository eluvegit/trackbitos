<?php
/**
 * Cabecera común de las vistas nuevas (estilo fv) de Hogar. Reutiliza los
 * estilos de listas y formularios de recordatorios.css (prefijo rc-) y añade
 * los propios en hogar.css (prefijo hg-).
 *
 * @var string      $activa   inicio | pendientes | gestionar | '' (pestaña activa)
 * @var array       $migas    [texto => url|null] tras «Hogar» (opcional)
 * @var string|null $titulo   h1 (HTML)
 * @var string      $sub      HTML bajo el título (opcional)
 * @var string      $acciones HTML a la derecha del título (opcional)
 */
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$activa ??= '';
$flash = array_filter(['ok' => session()->getFlashdata('success'), 'err' => session()->getFlashdata('error')]);
$errores = session('errors');
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], ['Hogar', $migas ? site_url('hogar') : null]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
$pestanas = ['inicio' => ['Habitaciones', 'hogar', 'bi-grid'], 'pendientes' => ['Qué toca', 'hogar/pendientes', 'bi-list-check'], 'gestionar' => ['Gestionar', 'hogar/gestionar', 'bi-sliders']];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('recordatorios.css') ?>">
<link rel="stylesheet" href="<?= $css('hogar.css') ?>">

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

    <?php if (!empty($titulo)): ?>
        <div>
            <div class="rc-h1"><h1><?= $titulo ?></h1><?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?></div>
            <?php if ($sub): ?><div class="rc-sub"><?= $sub ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($activa !== ''): ?>
        <nav class="rc-nav">
            <?php foreach ($pestanas as $k => [$txt, $r, $ico]): ?>
                <a href="<?= site_url($r) ?>" class="<?= $k === $activa ? 'on' : '' ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($flash || $errores): ?>
        <div class="rc-flash">
            <?php foreach ($flash as $tipo => $msg): ?>
                <div class="fv-flash fv-flash-<?= $tipo ?>"><i class="bi <?= $tipo === 'ok' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i><?= esc($msg) ?></div>
            <?php endforeach; ?>
            <?php if ($errores): ?><div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i><?= esc(implode(' · ', (array) $errores)) ?></div><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
