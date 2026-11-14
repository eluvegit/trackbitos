<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('gimnasio'); $url = site_url('gimnasio/mesociclos/' . $plan['id'] . '/ajustar'); ?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos',
        'migas' => ['Mesociclos' => site_url('gimnasio/mesociclos'), $plan['nombre'] => site_url('gimnasio/mesociclos/' . $plan['id']), 'Ajustar' => null],
        'titulo' => 'Ajustar e1RM', 'sub' => '<span>Ahora: <b class="t1">' . gim_kg($plan['e1rm_base']) . ' kg</b></span><span class="fv-dot"></span><span>antes de generar el siguiente lote</span>']) ?>

    <section class="fv-panel">
        <div class="fv-ph"><h3>1 · ¿Cómo te has sentido?</h3></div>
        <div class="gm-grid3" style="grid-template-columns:repeat(3,minmax(0,1fr))">
            <?php foreach (['fatigado' => ['Fatigado', $sugFatigado, 'bi-battery-low'], 'justo' => ['Justo', $sugJusto, 'bi-battery-half'], 'sobrado' => ['Sobrado', $sugSobrado, 'bi-battery-full']] as $k => [$n, $v, $ico]): ?>
                <form method="post" action="<?= $url ?>" class="m-0">
                    <input type="hidden" name="modo" value="estado"><input type="hidden" name="estado" value="<?= $k ?>">
                    <button class="gm-acceso" style="width:100%;text-align:left"><i class="bi <?= $ico ?>"></i><div><b><?= $n ?></b><small><?= gim_kg($v) ?> kg</small></div></button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <form method="post" action="<?= $url ?>" class="fv-panel gm-form">
        <input type="hidden" name="modo" value="medicion">
        <div class="fv-ph"><h3>2 · Con una serie reciente</h3></div>
        <div class="gm-grid3" style="grid-template-columns:repeat(3,minmax(0,1fr))">
            <div class="gm-campo"><label for="aP">Peso (kg)</label><input id="aP" type="number" step="0.5" inputmode="decimal" name="peso" class="fv-input" required></div>
            <div class="gm-campo"><label for="aR">Reps</label><input id="aR" type="number" inputmode="numeric" name="reps" class="fv-input" required></div>
            <div class="gm-campo"><label for="aRpe">RPE <small class="t3">opc.</small></label><input id="aRpe" type="number" step="0.5" name="rpe" class="fv-input" placeholder="8,5"></div>
        </div>
        <div class="gm-form-pie"><button class="fv-btn fv-btn-pri">Calcular y usar</button></div>
    </form>

    <form method="post" action="<?= $url ?>" class="fv-panel gm-form">
        <input type="hidden" name="modo" value="manual">
        <div class="fv-ph"><h3>3 · A mano</h3></div>
        <div class="gm-nueva">
            <input type="number" step="0.5" inputmode="decimal" name="e1rm_manual" class="fv-input" value="<?= esc($plan['e1rm_base']) ?>" style="height:40px">
            <button class="fv-btn" style="height:40px">Usar este valor</button>
        </div>
    </form>

    <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar') ?>" class="m-0">
        <button class="gm-cta sec"><i class="bi bi-skip-forward"></i><div><b>Saltar ajuste</b><small>Generar el lote estándar con <?= gim_kg($plan['e1rm_base']) ?> kg</small></div></button>
    </form>
</div>
<?= $this->endSection() ?>
