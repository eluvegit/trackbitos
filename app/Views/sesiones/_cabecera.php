<?php
/**
 * Cabecera común de las vistas nuevas (estilo fv) de Sesiones.
 *
 * @var array       $migas    [texto => url|null] tras "Sesiones" (opcional)
 * @var string|null $titulo   h1 (opcional)
 * @var string      $sub      HTML bajo el título (opcional)
 * @var string      $acciones HTML a la derecha del título (opcional)
 * @var string      $iconos   HTML de botones-icono arriba a la derecha (opcional)
 * @var bool        $clasica  mostrar el botón "vista clásica" (por defecto sí)
 */
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$iconos ??= '';
$clasica ??= true;
$flash = array_filter([
    'ok'  => session()->getFlashdata('success'),
    'err' => session()->getFlashdata('error'),
]);
$errores = session('errors');
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], ['Sesiones', site_url('sesiones')]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
$n = count($niveles);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('sesiones.css') ?>">

<div class="ss-cab">
    <div class="ss-top">
        <nav class="fv-crumbs ss-migas">
            <?php foreach ($niveles as $i => [$texto, $url]): ?>
                <?php $lejos = $i < $n - 2 ? ' lejos' : ''; $ultimo = $i === $n - 1 ? ' ult' : ''; ?>
                <?php if ($i > 0): ?><i class="bi bi-chevron-right<?= $i <= $n - 2 ? ' lejos' : '' ?>"></i><?php endif; ?>
                <?php if ($url): ?><a href="<?= $url ?>" class="<?= trim($lejos . $ultimo) ?>"><?= $texto ?></a><?php else: ?><span class="t1<?= $lejos . $ultimo ?>"><?= $texto ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="fv-acciones">
            <?= $iconos ?>
            <?php if ($clasica): ?>
                <a class="fv-ibtn" href="<?= current_url() ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($titulo)): ?>
        <div>
            <div class="ss-h1">
                <h1><?= $titulo ?></h1>
                <?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?>
            </div>
            <?php if ($sub): ?><div class="ss-sub"><?= $sub ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($flash || $errores): ?>
        <div class="ss-flash">
            <?php foreach ($flash as $tipo => $msg): ?>
                <div class="fv-flash fv-flash-<?= $tipo ?>"><i class="bi <?= $tipo === 'ok' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i><?= esc($msg) ?></div>
            <?php endforeach; ?>
            <?php if ($errores): ?>
                <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i><?= esc(implode(' · ', (array) $errores)) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
