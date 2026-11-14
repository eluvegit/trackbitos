<?php
/**
 * Cabecera común de las vistas nuevas de Lectura.
 *
 * @var array       $migas    [texto => url|null] tras "Lectura" (opcional)
 * @var string|null $titulo   h1 (opcional)
 * @var string      $sub      HTML bajo el título (opcional)
 * @var string      $acciones HTML a la derecha del título (opcional)
 */
helper('lectura');
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$flash = array_filter([
    'ok'  => session()->getFlashdata('success') ?? session()->getFlashdata('mensaje'),
    'err' => session()->getFlashdata('error'),
]);
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], ['Lectura', site_url('reading')]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
$n = count($niveles);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('lectura.css') ?>">

<div class="lc-cab">
    <div class="lc-top">
        <nav class="fv-crumbs lc-migas">
            <?php foreach ($niveles as $i => [$texto, $url]): ?>
                <?php $lejos = $i < $n - 2 ? ' lejos' : ''; $ultimo = $i === $n - 1 ? ' ult' : ''; ?>
                <?php if ($i > 0): ?><i class="bi bi-chevron-right<?= $i <= $n - 2 ? ' lejos' : '' ?>"></i><?php endif; ?>
                <?php if ($url): ?><a href="<?= $url ?>" class="<?= trim($lejos . $ultimo) ?>"><?= $texto ?></a><?php else: ?><span class="t1<?= $lejos . $ultimo ?>"><?= $texto ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="fv-acciones">
            <a class="fv-ibtn" href="<?= current_url() ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a>
        </div>
    </div>

    <?php if (!empty($titulo)): ?>
        <div>
            <div class="lc-h1">
                <h1><?= $titulo ?></h1>
                <?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?>
            </div>
            <?php if ($sub): ?><div class="lc-sub"><?= $sub ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="lc-flash">
            <?php foreach ($flash as $tipo => $msg): ?>
                <div class="fv-flash fv-flash-<?= $tipo ?>"><i class="bi <?= $tipo === 'ok' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i><?= esc($msg) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
