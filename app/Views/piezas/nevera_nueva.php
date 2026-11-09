<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
    $hace = static function (string $f): string {
        $d = (int) floor((time() - strtotime($f)) / 86400);
        return $d < 1 ? 'hoy' : ($d === 1 ? 'ayer' : ($d < 60 ? "hace {$d} días" : 'hace ' . (int) round($d / 30) . ' meses'));
    };
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Nevera</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/papelera') ?>" class="fv-gbtn" title="Piezas borradas"><i class="bi bi-trash"></i> Papelera</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/nevera?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Nevera</h1>
        <div class="fv-meta">
            <?php if ($variantes === []): ?>
                <span>Vacía</span>
            <?php else: ?>
                <span><b><?= count($variantes) ?></b> variante<?= count($variantes) === 1 ? '' : 's' ?> aparcada<?= count($variantes) === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Qué va a la nevera?</summary>
        <div class="fv-cuerpo">
            <span>Variantes <b>hechas pero incompletas</b> o que no funcionan bien. No están en la papelera ni se purgan nunca: solo se aparcan para no estorbar en el listado, la galería ni el selector de «añadir componente». Existencias y Estadísticas las siguen contando.</span>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <section class="fv-panel pv-estrecha">
        <?php if ($variantes === []): ?>
            <p class="fv-vacio">La nevera está vacía.</p>
        <?php else: ?>
            <div class="pv-lista">
                <?php foreach ($variantes as $v): ?>
                    <div class="pv-fila">
                        <span class="pv-ico"><i class="bi bi-snow"></i></span>
                        <div class="pv-cuerpo">
                            <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>" class="pv-nom"><span class="fam"><?= esc($v['familia_nombre']) ?> /</span> <?= esc($v['nombre']) ?></a>
                            <div class="pv-sub">
                                <?php if (!empty($v['sku'])): ?><span class="fv-mono"><?= esc($v['sku']) ?></span><span class="fv-dot"></span><?php endif; ?>
                                <span title="<?= esc($v['congelado_en'], 'attr') ?>">En la nevera desde el <?= esc(date('d/m/Y', strtotime($v['congelado_en']))) ?> · <?= $hace($v['congelado_en']) ?></span>
                            </div>
                        </div>
                        <form method="post" action="<?= site_url('piezas/variante/' . (int) $v['id'] . '/nevera') ?>"
                            onsubmit="return confirm('¿Sacar «<?= esc($v['familia_nombre'] . ' / ' . $v['nombre'], 'js') ?>» de la nevera? Vuelve al listado y a la galería.');">
                            <?= csrf_field() ?>
                            <button class="fv-btn fv-btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Descongelar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?= $this->endSection() ?>
