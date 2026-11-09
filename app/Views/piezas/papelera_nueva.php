<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
    $diasRestantes = static fn (string $borradoEn): int => max(0, 30 - (int) floor((time() - strtotime($borradoEn)) / 86400));
    $proxima = null;
    foreach (array_merge($familias, $variantes) as $x) {
        $d = $diasRestantes($x['borrado_en']);
        $proxima = $proxima === null ? $d : min($proxima, $d);
    }
    $fila = static function (string $nombre, ?string $familia, string $borradoEn, string $accion) use ($diasRestantes): string {
        $dias = $diasRestantes($borradoEn);
        ob_start(); ?>
        <div class="pv-fila">
            <span class="pv-ico"><i class="bi <?= $familia === null ? 'bi-box' : 'bi-diagram-2' ?>"></i></span>
            <div class="pv-cuerpo">
                <span class="pv-nom"><?php if ($familia !== null): ?><span class="fam"><?= esc($familia) ?> /</span> <?php endif; ?><?= esc($nombre) ?></span>
                <div class="pv-sub"><span title="<?= esc($borradoEn, 'attr') ?>">Borrada el <?= esc(date('d/m/Y', strtotime($borradoEn))) ?></span></div>
            </div>
            <div class="pv-fin">
                <span class="pv-purga<?= $dias <= 3 ? ' pronto' : '' ?>" title="Pasados 30 días desde el borrado, piezas:purgar se la lleva de verdad"><?= $dias > 0 ? 'se purga en ' . $dias . ' d' : 'se purga ya' ?></span>
                <form method="post" action="<?= $accion ?>">
                    <?= csrf_field() ?>
                    <button class="fv-btn fv-btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Restaurar</button>
                </form>
            </div>
        </div>
        <?php return ob_get_clean();
    };
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Papelera</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/nevera') ?>" class="fv-gbtn" title="Variantes aparcadas"><i class="bi bi-snow"></i> Nevera</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/papelera?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Papelera</h1>
        <div class="fv-meta">
            <?php if ($familias === [] && $variantes === []): ?>
                <span>Vacía</span>
            <?php else: ?>
                <span><b><?= count($familias) ?></b> pieza<?= count($familias) === 1 ? '' : 's' ?></span>
                <span><b><?= count($variantes) ?></b> variante<?= count($variantes) === 1 ? '' : 's' ?></span>
                <span class="fv-dot"></span>
                <span class="fv-estado <?= $proxima <= 3 ? 'e-bad' : 'e-neu' ?>"><?= $proxima > 0 ? 'La próxima purga, en ' . $proxima . ' día' . ($proxima === 1 ? '' : 's') : 'Hay algo a punto de purgarse' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Cuándo se borra de verdad?</summary>
        <div class="fv-cuerpo">
            <span>Nada se destruye al momento: mientras esté aquí se puede <b>restaurar tal cual estaba</b>. Pasados <b>30 días</b> desde el borrado, <code>piezas:purgar</code> se la lleva de verdad, con la fila y sus ficheros.</span>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if ($familias === [] && $variantes === []): ?>
        <section class="fv-panel pv-estrecha"><p class="fv-vacio">La papelera está vacía.</p></section>
    <?php endif; ?>

    <?php if ($familias !== []): ?>
        <section class="fv-panel pv-estrecha">
            <div class="fv-ph"><h3>Piezas enteras</h3><span class="cuenta"><?= count($familias) ?></span></div>
            <div class="pv-lista">
                <?php foreach ($familias as $f): ?>
                    <?= $fila($f['nombre'], null, $f['borrado_en'], site_url('piezas/familia/' . (int) $f['id'] . '/restaurar')) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($variantes !== []): ?>
        <section class="fv-panel pv-estrecha">
            <div class="fv-ph"><h3>Variantes sueltas</h3><span class="cuenta"><?= count($variantes) ?></span><span class="der fv-vacio" style="font-size:12px">El resto de la pieza sigue intacto</span></div>
            <div class="pv-lista">
                <?php foreach ($variantes as $v): ?>
                    <?= $fila($v['nombre'], $v['familia_nombre'], $v['borrado_en'], site_url('piezas/variante/' . (int) $v['id'] . '/restaurar')) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
