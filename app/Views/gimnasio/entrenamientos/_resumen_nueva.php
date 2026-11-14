<?php helper('gimnasio'); ?>
<div class="gm-res">
    <?php if ($principales): ?>
        <div class="gm-chips">
            <?php foreach ($principales as $p): ?>
                <span class="gm-chip<?= $p['mejor'] ? ' on' : '' ?>" style="--c:var(--fv-accent,#ff6a3d)" title="<?= $p['mejor'] ? '1RM est. ' . gim_kg($p['mejor']['e1rm']) . ' kg' : 'No entrenado' ?>">
                    <?= esc($p['nombre']) ?>
                    <small><?= $p['mejor'] ? gim_kg($p['mejor']['peso']) . '×' . $p['mejor']['reps'] : '—' ?></small>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="gm-cifras">
        <div class="gm-cifra"><span class="k">Ejercicios</span><span class="v"><?= count($porEjercicio) ?></span></div>
        <div class="gm-cifra"><span class="k">Series</span><span class="v"><?= (int) ($global['total_series'] ?? 0) ?></span></div>
        <div class="gm-cifra"><span class="k">Reps</span><span class="v"><?= (int) ($global['total_reps'] ?? 0) ?></span></div>
        <div class="gm-cifra"><span class="k">Volumen</span><span class="v"><?= gim_num($global['total_volumen'] ?? 0) ?><small>kg</small></span></div>
    </div>

    <?php if (!empty($ent['tipo_sesion']) || !empty($ent['notas_generales']) || !empty($ent['lesiones'])): ?>
        <div class="gm-res-nota">
            <?php if (!empty($ent['tipo_sesion'])): ?><span class="gm-tipo" style="--c:<?= gim_tipo_color($ent['tipo_sesion']) ?>"><?= esc($ent['tipo_sesion']) ?></span> <?php endif; ?>
            <?= esc($ent['notas_generales'] ?? '') ?>
            <?php if (!empty($ent['lesiones'])): ?><div style="color:#f5a524;margin-top:4px"><i class="bi bi-bandaid"></i> <?= esc($ent['lesiones']) ?></div><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($porEjercicio as $r): ?>
        <div class="gm-res-ej">
            <h4><?= esc($r['ejercicio']) ?> <span><?= (int) $r['sets'] ?> series<?= $r['volumen_total'] > 0 ? ' · ' . gim_num($r['volumen_total']) . ' kg' : '' ?></span></h4>
            <div class="ss">
                <?php foreach ($detallePorEj[$r['ejercicio_id']] ?? [] as $d): ?>
                    <span title="<?= esc(trim(($d['rpe'] !== null && $d['rpe'] !== '' ? 'RPE ' . $d['rpe'] . ' ' : '') . ($d['nota'] ?? ''))) ?>"><?= (int) $d['series'] ?>×<?= (int) $d['repeticiones'] ?><?= (float) $d['peso'] > 0 ? ' · ' . gim_kg($d['peso']) : '' ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$porEjercicio): ?><p class="gm-res-nota">Sin series registradas.</p><?php endif; ?>
</div>
