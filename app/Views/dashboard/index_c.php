<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$nAvisos = ($mostrarAlerta ? 1 : 0) + count($recordatoriosUrgentes);
$cifras = [
    ['k' => 'journal', 'u' => 'journal/focalizar', 'lab' => 'tareas en foco'],
    ['k' => 'lentillas', 'u' => 'lentillas', 'lab' => 'días con las lentillas'],
    ['k' => 'hogar', 'u' => 'hogar', 'lab' => 'tareas de casa atrasadas'],
    ['k' => 'recordatorios', 'u' => 'recordatorios', 'lab' => 'recordatorios cerca'],
];
?>
<div class="tb-dash">
    <?php $this->setVar('titulo', 'Hoy') ?><?= $this->include('dashboard/_tb_cabecera') ?>
    <div class="tb-c">
        <div class="tb-panel ancho">
            <div class="tb-cifras">
                <?php foreach ($cifras as $c): $v = $vivos[$c['k']] ?? null; ?>
                    <a href="<?= site_url($c['u']) ?>"><span class="num<?= $v && $v['e'] && $v['e'] !== 'ok' ? ' e-' . $v['e'] : '' ?>"><?= $v ? (int) $v['n'] : '—' ?></span><span class="lab"><?= $c['lab'] ?></span></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="tb-panel">
            <div class="tb-ph"><h3>Avisos</h3><span class="cuenta"><?= $nAvisos ?></span><a class="der" href="<?= site_url('recordatorios') ?>">Recordatorios</a></div>
            <?= $this->include('dashboard/_tb_avisos') ?>
        </div>
        <div class="tb-panel">
            <div class="tb-ph"><h3>Tareas fijadas</h3><span class="cuenta"><?= count($tareasFijadas) ?></span></div>
            <?= $this->include('dashboard/_tb_fijadas') ?>
        </div>
        <div class="tb-panel">
            <?php if (!empty($librosLeyendo)): ?>
                <div class="tb-ph"><h3>Leyendo</h3></div>
                <?= $this->include('dashboard/_tb_leyendo') ?>
                <div class="tb-ph" style="margin-top:12px"><h3>Atajos</h3></div>
            <?php else: ?>
                <div class="tb-ph"><h3>Atajos</h3></div>
            <?php endif; ?>
            <?= $this->include('dashboard/_tb_atajos') ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
