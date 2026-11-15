<?php
/**
 * Cabecera común de las vistas nuevas (estilo fv) de Enlaces. Reutiliza los
 * estilos de cabecera, listas y formularios de recordatorios.css (prefijo rc-)
 * y añade los propios en enlaces.css (prefijo en-).
 *
 * @var string      $activa   inicio | repaso | revision | categorias | etiquetas | importar | '' (pestaña activa)
 * @var array       $migas    [texto => url|null] tras «Enlaces» (opcional)
 * @var string|null $titulo   h1 (HTML)
 * @var string      $sub      HTML bajo el título (opcional)
 * @var string      $acciones HTML a la derecha del título (opcional)
 * @var bool        $sinMsg   no pintar el flash «msg» (la vista lo pinta a su manera)
 */
$css = static fn (string $f) => base_url('assets/css/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/css/' . $f) ?: 1);
$migas ??= [];
$sub ??= '';
$acciones ??= '';
$activa ??= '';
$flash = array_filter([
    'ok'  => session()->getFlashdata('mensaje') ?: session()->getFlashdata('success'),
    'inf' => empty($sinMsg) ? session()->getFlashdata('msg') : null,
    'err' => session()->getFlashdata('error'),
]);
$errores = session()->getFlashdata('errors');
$niveles = array_merge(
    [['<i class="bi bi-grid" style="font-size:12px"></i>', site_url('dashboard')], ['Enlaces', ($migas || $activa !== 'inicio') ? site_url('enlaces') : null]],
    array_map(fn ($t, $u) => [esc($t), $u], array_keys($migas), array_values($migas))
);
$pestanas = [
    'inicio'     => ['Enlaces', 'enlaces', 'bi-link-45deg'],
    'repaso'     => ['Repaso', 'enlaces/repaso', 'bi-fire'],
    'revision'   => ['Sin título', 'enlaces/revision', 'bi-input-cursor-text'],
    'categorias' => ['Categorías', 'enlaces/categorias', 'bi-folder2'],
    'etiquetas'  => ['Etiquetas', 'enlaces/etiquetas', 'bi-tags'],
    'importar'   => ['Importar', 'enlaces/importar', 'bi-upload'],
];
$iconos = ['ok' => 'bi-check-circle', 'inf' => 'bi-info-circle', 'err' => 'bi-exclamation-triangle'];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= $css('ficha-variante.css') ?>">
<link rel="stylesheet" href="<?= $css('recordatorios.css') ?>">
<link rel="stylesheet" href="<?= $css('enlaces.css') ?>">

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
                <div class="fv-flash fv-flash-<?= $tipo === 'err' ? 'err' : 'ok' ?><?= $tipo === 'inf' ? ' en-flash-inf' : '' ?>"><i class="bi <?= $iconos[$tipo] ?>"></i><?= esc($msg) ?></div>
            <?php endforeach; ?>
            <?php if ($errores): ?><div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i><?= esc(implode(' · ', (array) $errores)) ?></div><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
