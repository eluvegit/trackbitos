<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\MenuPrincipal;
$h = (int) date('G');
$titulo = $h < 7 ? 'Buenas noches' : ($h < 14 ? 'Buenos días' : ($h < 21 ? 'Buenas tardes' : 'Buenas noches'));
$nAhora = ($mostrarAlerta ? 1 : 0) + count($recordatoriosUrgentes) + count($tareasFijadas);
$porArea = [];
foreach (MenuPrincipal::modulos() as $m) {
    $porArea[$m['area']][] = $m;
}
?>
<div class="tb-dash">
    <?php $this->setVar('titulo', $titulo) ?><?= $this->include('dashboard/_tb_cabecera') ?>
    <div class="tb-b-top">
        <div class="tb-panel">
            <div class="tb-ph"><h3>Ahora toca</h3><span class="cuenta"><?= $nAhora ?></span></div>
            <?php if ($mostrarAlerta || !empty($recordatoriosUrgentes)): ?>
                <div style="margin-bottom:10px"><?= $this->include('dashboard/_tb_avisos') ?></div>
            <?php endif; ?>
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
    <div class="tb-areas">
        <?php foreach (MenuPrincipal::areas() as $ak => $an): ?>
            <div class="tb-area">
                <h4><?= esc($an) ?></h4>
                <div class="tb-lista">
                    <?php foreach ($porArea[$ak] ?? [] as $m): $v = $vivos[$m['ruta']] ?? null; ?>
                        <a class="tb-mod" href="<?= site_url($m['ruta']) ?>" title="<?= esc($m['texto'], 'attr') ?>">
                            <i class="bi <?= $m['icono'] ?>"></i>
                            <span class="tw">
                                <span class="n"><?= esc($m['titulo']) ?></span>
                                <?php if ($v): ?>
                                    <span class="s<?= $v['e'] ? ' tb-estado e-' . $v['e'] : '' ?>"><?= esc($v['t']) ?></span>
                                <?php else: ?>
                                    <span class="s"><?= esc($m['corto']) ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
