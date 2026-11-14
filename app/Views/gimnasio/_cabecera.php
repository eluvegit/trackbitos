<?php
/**
 * Cabecera común de las vistas nuevas de Gimnasio.
 *
 * @var string      $activa   pestaña marcada: inicio|entrenos|stats|ejercicios|plantillas|mesociclos|catalogo
 * @var array       $migas    [texto => url|null] (opcional)
 * @var string|null $titulo   h1 (opcional)
 * @var string      $sub      HTML bajo el título (opcional)
 * @var string      $acciones HTML a la derecha del título (opcional)
 * @var bool        $sinNav   oculta las pestañas (registro en el gimnasio)
 */
helper('gimnasio');
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$activa ??= '';
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$secciones = [
    'inicio'     => ['Inicio', 'bi-house', 'gimnasio'],
    'entrenos'   => ['Entrenos', 'bi-calendar3', 'gimnasio/entrenamientos'],
    'stats'      => ['Estadísticas', 'bi-graph-up', 'gimnasio/ejercicios/principales'],
    'ejercicios' => ['Ejercicios', 'bi-list-ul', 'gimnasio/ejercicios'],
    'plantillas' => ['Plantillas', 'bi-collection', 'gimnasio/plantillas'],
    'mesociclos' => ['Mesociclos', 'bi-diagram-3', 'gimnasio/mesociclos'],
    'catalogo'   => ['Catálogo', 'bi-play-btn', 'gimnasio/catalogo'],
];
$flash = array_filter([
    'ok'  => session()->getFlashdata('mensaje') ?? session()->getFlashdata('success') ?? session()->getFlashdata('message'),
    'err' => session()->getFlashdata('error'),
]);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('gimnasio.css') ?>">

<div class="gm-cab">
    <div class="gm-top">
        <?php
        // En el móvil solo se ven los dos últimos niveles (clase "lejos" en el resto)
        $niveles = array_merge(
            [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], ['Gimnasio', site_url('gimnasio')]],
            array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
        );
        $n = count($niveles);
        ?>
        <nav class="fv-crumbs gm-migas">
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

    <?php if (empty($sinNav)): ?>
        <nav class="gm-nav">
            <?php foreach ($secciones as $k => [$n, $ico, $ruta]): ?>
                <a href="<?= site_url($ruta) ?>" class="<?= $k === $activa ? 'on' : '' ?>"><i class="bi <?= $ico ?>"></i><?= $n ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!empty($titulo)): ?>
        <div>
            <div class="gm-h1">
                <h1><?= $titulo ?></h1>
                <?php if ($acciones): ?><div class="der"><?= $acciones ?></div><?php endif; ?>
            </div>
            <?php if ($sub): ?><div class="gm-sub"><?= $sub ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="gm-flash">
            <?php foreach ($flash as $tipo => $msg): ?>
                <div class="fv-flash fv-flash-<?= $tipo ?>"><i class="bi <?= $tipo === 'ok' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i><?= esc($msg) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
