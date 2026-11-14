<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$acciones = '<a class="fv-btn fv-btn-pri" href="' . site_url('gimnasio/mesociclos/nuevo') . '"><i class="bi bi-plus-lg"></i> Nuevo plan</a>';
?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos', 'titulo' => 'Mesociclos', 'acciones' => $acciones,
        'sub' => '<span>Progresión por bloques: top set + back-off a partir de tu 1RM estimado</span>']) ?>

    <?php if (!$planes): ?>
        <div class="gm-vacio"><i class="bi bi-diagram-3"></i>No hay planes aún. Crea el primero con «Nuevo plan».</div>
    <?php endif; ?>

    <?php foreach ($planes as $p): ?>
        <?php $pct = $p['_pendientes'] === 0 ? 100 : (int) $p['_progreso']; ?>
        <a class="fv-panel" href="<?= site_url('gimnasio/mesociclos/' . $p['id']) ?>" style="gap:10px">
            <div class="fv-ph">
                <h3 style="font-size:15px;color:var(--fv-text);font-weight:600"><?= esc($p['nombre']) ?></h3>
                <span class="cuenta"><?= esc(ucfirst($p['ejercicio'])) ?></span>
                <span class="der fv-estado <?= $p['_pendientes'] ? 'e-acc' : 'e-ok' ?>"><?= $p['_pendientes'] ? 'Toca #' . (int) $p['_siguiente'] : 'Lote terminado' ?></span>
            </div>
            <div class="gm-progreso"><i style="width:<?= $pct ?>%"></i></div>
            <div class="gm-sub" style="margin:0">
                <span>e1RM <b class="t1"><?= gim_kg($p['e1rm_base']) ?> kg</b></span><span class="fv-dot"></span>
                <span><?= (int) $p['_pendientes'] ?> pendientes</span><span class="fv-dot"></span>
                <span><?= (int) $p['_hechos'] ?> hechos</span><span class="fv-dot"></span>
                <span><?= $pct ?>% del lote</span>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
