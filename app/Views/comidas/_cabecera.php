<?php
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$activa ??= '';
$flash = array_filter([
    'ok'   => session()->getFlashdata('success') ?: (session()->getFlashdata('ok') ?: session()->getFlashdata('msg')),
    'err'  => session()->getFlashdata('error') ?: session()->getFlashdata('warning'),
]);
$errores = session('errors');
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], ['Comidas', $migas ? site_url('comidas/diario/hoy') : null]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
$pestanas = [
    'diario'     => ['Diario', 'comidas/diario/hoy', 'bi-calendar2-check'],
    'alimentos'  => ['Alimentos', 'comidas/alimentos', 'bi-basket'],
    'recetas'    => ['Recetas', 'comidas/recetas', 'bi-journal-text'],
    'control'    => ['Frecuencia', 'comidas/alimentos-control', 'bi-arrow-repeat'],
    'objetivos'  => ['Objetivos', 'comidas/objetivos', 'bi-bullseye'],
    'peso'       => ['Peso', 'comidas/peso', 'bi-graph-down'],
];
$clasica = current_url() . '?' . http_build_query(array_merge(service('request')->getGet() ?? [], ['nueva' => '0']));
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('recordatorios.css') ?>">
<link rel="stylesheet" href="<?= $css('comidas.css') ?>">

<div class="rc-cab">
    <div class="rc-top">
        <nav class="fv-crumbs rc-migas">
            <?php foreach ($niveles as $i => [$texto, $url]): ?>
                <?php if ($i > 0): ?><i class="bi bi-chevron-right"></i><?php endif; ?>
                <?php if ($url): ?><a href="<?= $url ?>"><?= $texto ?></a><?php else: ?><span class="t1 ult"><?= $texto ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="fv-acciones"><a class="fv-ibtn" href="<?= esc($clasica) ?>" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a></div>
    </div>

    <?php if ($activa !== ''): ?>
        <nav class="rc-nav">
            <?php foreach ($pestanas as $k => [$txt, $r, $ico]): ?>
                <a href="<?= site_url($r) ?>" class="<?= $k === $activa ? 'on' : '' ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!empty($titulo)): ?>
        <div>
            <div class="rc-h1"><h1><?= $titulo ?></h1><?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?></div>
            <?php if ($sub): ?><div class="rc-sub"><?= $sub ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($flash || $errores): ?>
        <div class="rc-flash">
            <?php foreach ($flash as $tipo => $msg): ?>
                <div class="fv-flash fv-flash-<?= $tipo ?>"><i class="bi <?= $tipo === 'ok' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i><span><?= esc(strip_tags((string) $msg)) ?></span></div>
            <?php endforeach; ?>
            <?php if ($errores): ?><div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i><?= esc(implode(' · ', (array) $errores)) ?></div><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
