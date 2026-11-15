<?php
/**
 * Cabecera común (estilo fv) de Recordatorios, Coche y Lentillas: migas,
 * título, acciones y las pestañas del módulo.
 *
 * @var string      $modulo   recordatorios | coche | lentillas
 * @var string      $activa   clave de la pestaña activa (opcional)
 * @var array       $migas    [texto => url|null] tras el módulo (opcional)
 * @var string|null $titulo   h1
 * @var string      $sub      HTML bajo el título (opcional)
 * @var string      $acciones HTML a la derecha del título (opcional)
 * @var bool        $clasica  mostrar el botón «vista clásica» (por defecto no)
 */
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$modulos = [
    'recordatorios' => ['Recordatorios', 'recordatorios', []],
    'coche'         => ['Coche', 'coche', [
        'resumen'   => ['Resumen', 'coche', 'bi-speedometer2'],
        'avisos'    => ['Avisos', 'coche/recordatorios', 'bi-bell'],
        'historial' => ['Historial', 'coche/acciones', 'bi-clock-history'],
        'averias'   => ['Averías', 'coche/averias', 'bi-exclamation-triangle'],
    ]],
    'lentillas'     => ['Lentillas', 'lentillas', [
        'resumen' => ['Resumen', 'lentillas', 'bi-eye'],
        'avisos'  => ['Avisos', 'lentillas/avisos', 'bi-bell'],
        'cambios' => ['Cambios', 'lentillas/sustituciones', 'bi-arrow-repeat'],
        'stock'   => ['Stock', 'lentillas/stock', 'bi-box-seam'],
        'compras' => ['Compras', 'lentillas/compras', 'bi-bag'],
    ]],
];
[$nombre, $ruta, $pestanas] = $modulos[$modulo ?? 'recordatorios'];
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$activa ??= '';
$clasica ??= false;
$flash = array_filter([
    'ok'  => session()->getFlashdata('success') ?: session()->getFlashdata('message'),
    'err' => session()->getFlashdata('error'),
]);
$errores = session('errors');
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], [$nombre, $migas ? site_url($ruta) : null]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
$n = count($niveles);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('recordatorios.css') ?>">

<div class="rc-cab">
    <div class="rc-top">
        <nav class="fv-crumbs rc-migas">
            <?php foreach ($niveles as $i => [$texto, $url]): ?>
                <?php if ($i > 0): ?><i class="bi bi-chevron-right"></i><?php endif; ?>
                <?php if ($url): ?><a href="<?= $url ?>"><?= $texto ?></a><?php else: ?><span class="t1<?= $i === $n - 1 ? ' ult' : '' ?>"><?= $texto ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php if ($clasica): ?>
            <div class="fv-acciones"><a class="fv-ibtn" href="<?= current_url() ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($titulo)): ?>
        <div>
            <div class="rc-h1">
                <h1><?= $titulo ?></h1>
                <?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?>
            </div>
            <?php if ($sub): ?><div class="rc-sub"><?= $sub ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($pestanas): ?>
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
            <?php if ($errores): ?>
                <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i><?= esc(implode(' · ', (array) $errores)) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
