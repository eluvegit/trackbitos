<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $campo = fn ($id, $lbl, $attrs) => '<div class="gm-campo"><label for="' . $id . '">' . $lbl . '</label><input id="' . $id . '" class="fv-input" ' . $attrs . '></div>'; ?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos',
        'migas' => ['Mesociclos' => site_url('gimnasio/mesociclos'), $plan['nombre'] => site_url('gimnasio/mesociclos/' . $plan['id']), 'Nuevo bloque' => null],
        'titulo' => 'Nuevo bloque']) ?>

    <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/bloque/nuevo') ?>" class="fv-panel gm-form">
        <div class="gm-fila2">
            <?= $campo('bOrden', 'Orden', 'name="orden" type="number" required') ?>
            <div class="gm-campo"><label for="bTipo">Tipo</label>
                <select id="bTipo" name="bloque_tipo" class="gm-sel">
                    <option value="push">Empuje (push)</option>
                    <option value="stabilization">Estabilizar</option>
                    <option value="deload">Descarga (deload)</option>
                    <option value="pico">Pico</option>
                </select>
            </div>
        </div>
        <span class="gm-lbl" style="color:var(--fv-text-2)">Top set</span>
        <div class="gm-fila2">
            <?= $campo('bPmin', '% mínimo <small class="t3">(0,65 = 65%)</small>', 'name="top_pct_min" type="number" step="0.01" required') ?>
            <?= $campo('bPmax', '% máximo <small class="t3">(opcional)</small>', 'name="top_pct_max" type="number" step="0.01"') ?>
            <?= $campo('bRmin', 'Reps mínimas', 'name="top_reps_min" type="number" required') ?>
            <?= $campo('bRmax', 'Reps máximas <small class="t3">(opcional)</small>', 'name="top_reps_max" type="number"') ?>
        </div>
        <span class="gm-lbl" style="color:var(--fv-text-2)">Back-off</span>
        <div class="gm-fila2">
            <?= $campo('bBo', '% relativo al top <small class="t3">(−0,10)</small>', 'name="bo_pct_rel_top" type="number" step="0.01" value="-0.10"') ?>
            <?= $campo('bSets', 'Series', 'name="bo_sets" type="number" value="3"') ?>
            <?= $campo('bReps', 'Reps', 'name="bo_reps" type="number" value="5"') ?>
        </div>
        <?= $campo('bNotas', 'Notas', 'name="notas" type="text" placeholder="Opcional"') ?>
        <div class="gm-form-pie">
            <a class="fv-btn" href="<?= site_url('gimnasio/mesociclos/' . $plan['id']) ?>">Cancelar</a>
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i> Guardar bloque</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
