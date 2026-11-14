<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$colTipo = ['push' => '#ff6a3d', 'stabilization' => '#8e8cff', 'deload' => '#3ecf8e', 'pico' => '#f5a524'];
$nomTipo = ['push' => 'Empuje', 'stabilization' => 'Estabilizar', 'deload' => 'Descarga', 'pico' => 'Pico'];

$siguiente = null;
foreach ($pendientes as $pb) {
    if ($siguienteOrden !== null && (int) $pb['orden'] === (int) $siguienteOrden) {
        $siguiente = $pb;
        break;
    }
}

// Progreso del mesociclo en curso: desde el último pico hecho hasta el próximo pico
$todos = array_merge($hechos, $pendientes);
usort($todos, fn ($a, $b) => $a['orden'] <=> $b['orden']);
$pct = 0;
if ($todos) {
    $ultimoPico = null;
    foreach ($hechos as $h) {
        if ($h['bloque_tipo'] === 'pico' && ($ultimoPico === null || (int) $h['orden'] > $ultimoPico)) {
            $ultimoPico = (int) $h['orden'];
        }
    }
    $inicio = $ultimoPico !== null ? $ultimoPico + 1 : (int) $todos[0]['orden'];
    $fin = null;
    foreach ($todos as $b) {
        if ((int) $b['orden'] >= $inicio && $b['bloque_tipo'] === 'pico') { $fin = (int) $b['orden']; break; }
    }
    $fin ??= (int) max(array_column($todos, 'orden'));
    $total = max(0, $fin - $inicio + 1);
    $hechosAqui = count(array_filter($hechos, fn ($h) => (int) $h['orden'] >= $inicio && (int) $h['orden'] <= $fin));
    $pct = $pendientes ? ($total ? (int) round($hechosAqui / $total * 100) : 0) : 100;
}

$rango = fn ($min, $max, $dec = 1) => gim_kg(round($min, $dec)) . ($max ? '–' . gim_kg(round($max, $dec)) : '');
$bloque = function (array $b, bool $esSig, bool $hecho) use ($colTipo, $nomTipo, $rango) {
    $pct = round($b['top_pct_min'] * 100) . ($b['top_pct_max'] ? '–' . round($b['top_pct_max'] * 100) : '') . '%';
    $reps = (int) $b['top_reps_min'] . ($b['top_reps_max'] ? '–' . (int) $b['top_reps_max'] : '');
    ob_start(); ?>
    <div class="gm-bloque<?= $esSig ? ' sig' : '' ?><?= $hecho ? ' hecho' : '' ?>">
        <div class="gm-bloque-h">
            <span class="o">#<?= (int) $b['orden'] ?></span>
            <span class="tp" style="--c:<?= $colTipo[$b['bloque_tipo']] ?? '#aaa' ?>"><?= esc($nomTipo[$b['bloque_tipo']] ?? $b['bloque_tipo']) ?></span>
            <?php if ($esSig): ?><span class="der">Siguiente</span><?php elseif ($hecho): ?><i class="bi bi-check-circle-fill der" style="color:var(--fv-ok)"></i><?php endif; ?>
        </div>
        <dl>
            <div><dt>Top</dt><dd><?= $rango($b['_top_min'], $b['_top_max']) ?> kg × <?= $reps ?> <small><?= $pct ?></small></dd></div>
            <div><dt>Back-off</dt><dd><?= (int) $b['bo_sets'] ?>×<?= (int) $b['bo_reps'] ?> @ <?= $rango($b['_bo_min'], $b['_bo_max']) ?> kg</dd></div>
        </dl>
        <?php if (!empty($b['notas'])): ?><div class="nt"><?= esc($b['notas']) ?></div><?php endif; ?>
        <?php if ($esSig): ?>
            <form method="post" action="<?= site_url('gimnasio/mesociclos/bloque/' . $b['id'] . '/hecho') ?>">
                <button class="fv-btn fv-btn-pri fv-btn-sm" style="width:100%"><i class="bi bi-check-lg"></i> Marcar hecho</button>
            </form>
        <?php endif; ?>
    </div>
    <?php return ob_get_clean();
};

$acciones = '';
if ($siguiente) {
    $acciones .= '<button type="button" class="fv-btn fv-btn-pri" data-bs-toggle="modal" data-bs-target="#gmActual"><i class="bi bi-play-fill"></i> Serie actual</button>';
}
$acciones .= '<div class="dropdown"><button type="button" class="fv-ibtn" data-bs-toggle="dropdown" title="Más"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end">'
    . '<li><a class="dropdown-item" href="' . site_url('gimnasio/mesociclos/' . $plan['id'] . '/bloque/nuevo') . '"><i class="bi bi-plus-lg me-2"></i>Añadir bloque</a></li>'
    . '<li><a class="dropdown-item" href="' . site_url('gimnasio/mesociclos/' . $plan['id'] . '/simplificado') . '"><i class="bi bi-table me-2"></i>Vista simplificada</a></li>';
if ($hechos) {
    $u = $hechos[0];
    $acciones .= '<li><form method="post" action="' . site_url('gimnasio/mesociclos/' . $plan['id'] . '/deshacer') . '" data-confirm="¿Deshacer el bloque #' . (int) $u['orden'] . ' marcado como hecho?" class="m-0"><button class="dropdown-item"><i class="bi bi-arrow-counterclockwise me-2"></i>Deshacer último (#' . (int) $u['orden'] . ')</button></form></li>';
}
$acciones .= '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="' . site_url('gimnasio/mesociclos/nuevo') . '"><i class="bi bi-file-earmark-plus me-2"></i>Nuevo plan</a></li></ul></div>';
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos',
        'migas' => ['Mesociclos' => site_url('gimnasio/mesociclos'), $plan['nombre'] => null],
        'titulo' => esc($plan['nombre']), 'acciones' => $acciones,
        'sub' => '<span>' . esc(ucfirst($plan['ejercicio'])) . '</span><span class="fv-dot"></span><span>e1RM <b class="t1">' . gim_kg($plan['e1rm_base']) . ' kg</b></span><span class="fv-dot"></span><span>redondeo ' . gim_kg($plan['redondeo_kg']) . ' kg</span>']) ?>

    <section class="fv-panel" style="gap:10px">
        <div class="fv-ph"><h3>Mesociclo en curso</h3><span class="cuenta der"><?= $pct ?>%</span></div>
        <div class="gm-progreso"><i style="width:<?= $pct ?>%"></i></div>
    </section>

    <?php if (!$pendientes): ?>
        <section class="fv-panel">
            <div class="fv-ph"><h3>Lote terminado</h3></div>
            <p class="gm-ayuda" style="font-size:13px">Ajusta tu e1RM si ha cambiado y genera el siguiente lote.</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a class="fv-btn" href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/ajustar') ?>"><i class="bi bi-sliders"></i> Ajustar e1RM</a>
                <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar') ?>" class="m-0"><button class="fv-btn fv-btn-pri"><i class="bi bi-arrow-repeat"></i> Generar lote estándar</button></form>
                <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar/bilbo') ?>" class="m-0" data-confirm="¿Generar un mesociclo Bilbo (10 bloques) con e1RM <?= gim_kg($plan['e1rm_base']) ?> kg?"><button class="fv-btn"><i class="bi bi-arrow-repeat"></i> Generar Bilbo</button></form>
            </div>
        </section>
    <?php else: ?>
        <div class="fv-ph"><h3 style="font-size:15px;color:var(--fv-text)">Pendientes</h3><span class="cuenta"><?= count($pendientes) ?></span></div>
        <div class="gm-bloques">
            <?php foreach ($pendientes as $b): ?><?= $bloque($b, $siguiente && $b['id'] === $siguiente['id'], false) ?><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($hechos): ?>
        <?php
        $asc = $hechos;
        usort($asc, fn ($a, $b) => $a['orden'] <=> $b['orden']);
        $grupos = [];
        $actual = [];
        foreach ($asc as $b) {
            $actual[] = $b;
            if ($b['bloque_tipo'] === 'pico') { $grupos[] = $actual; $actual = []; }
        }
        if ($actual) $grupos[] = $actual;
        $grupos = array_reverse($grupos);
        $n = count($grupos);
        ?>
        <div class="fv-ph"><h3 style="font-size:15px;color:var(--fv-text)">Completados</h3><span class="cuenta"><?= count($hechos) ?></span></div>
        <?php foreach ($grupos as $i => $g): $ult = end($g); ?>
            <details class="gm-mas" <?= $i === 0 ? 'open' : '' ?>>
                <summary class="gm-lote"><i class="bi bi-chevron-down"></i> <b>Mesociclo <?= $n - $i ?></b>
                    <span>#<?= (int) $g[0]['orden'] ?>–#<?= (int) $ult['orden'] ?></span>
                    <?php if (!empty($ult['e1rm_snapshot'])): ?><span>e1RM <?= gim_kg($ult['e1rm_snapshot']) ?> kg</span><?php endif; ?>
                </summary>
                <div class="gm-bloques" style="margin-top:10px">
                    <?php foreach ($g as $b): ?><?= $bloque($b, false, true) ?><?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($siguiente): ?>
<div class="modal fade" id="gmActual" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= esc(ucfirst($plan['ejercicio'])) ?> <span class="text-muted fw-normal">· bloque #<?= (int) $siguiente['orden'] ?></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="--fv-raised:var(--bs-secondary-bg);--fv-border-2:rgb(255 255 255 / .12);--fv-text:var(--bs-emphasis-color);--fv-text-2:var(--bs-secondary-color);--fv-text-3:var(--bs-tertiary-color);--fv-accent:#ff6a3d;--fv-info:#8e8cff;--fv-panel:var(--bs-tertiary-bg);--fv-r-lg:16px;font-family:Geist,system-ui,sans-serif">
                <div class="gm-actual">
                    <div>
                        <span class="et">Top set · <?= round($siguiente['top_pct_min'] * 100) ?><?= $siguiente['top_pct_max'] ? '–' . round($siguiente['top_pct_max'] * 100) : '' ?>%</span>
                        <span class="kg"><?= $rango($siguiente['_top_min'], $siguiente['_top_max']) ?> <small>kg</small></span>
                        <span class="rp"><?= (int) $siguiente['top_reps_min'] ?><?= $siguiente['top_reps_max'] ? '–' . (int) $siguiente['top_reps_max'] : '' ?> reps · 1 serie</span>
                        <div class="gm-cuenta" data-max="1"><span>Hechas</span><button type="button" data-d="-1">−</button><b>0</b><button type="button" data-d="1">+</button></div>
                    </div>
                    <div class="bo">
                        <span class="et">Back-off</span>
                        <span class="kg"><?= $rango($siguiente['_bo_min'], $siguiente['_bo_max']) ?> <small>kg</small></span>
                        <span class="rp"><?= (int) $siguiente['bo_sets'] ?> × <?= (int) $siguiente['bo_reps'] ?> reps</span>
                        <div class="gm-cuenta" data-max="<?= max(1, (int) $siguiente['bo_sets']) ?>"><span>Hechas</span><button type="button" data-d="-1">−</button><b>0</b><button type="button" data-d="1">+</button><span class="der">de <?= (int) $siguiente['bo_sets'] ?></span></div>
                    </div>
                </div>
                <div class="gm-cuenta" style="margin-top:16px;justify-content:center">
                    <span>Descanso</span><b id="gmCrono" style="font-family:'Geist Mono',monospace;min-width:70px">0:00</b>
                    <button type="button" id="gmCronoBtn" style="width:auto;padding:0 14px;font-size:13px">Iniciar</button>
                    <button type="button" id="gmCronoReset" style="width:auto;padding:0 14px;font-size:13px">0</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <form method="post" action="<?= site_url('gimnasio/mesociclos/bloque/' . $siguiente['id'] . '/hecho') ?>" class="m-0"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Marcar bloque hecho</button></form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    document.querySelectorAll('#gmActual .gm-cuenta[data-max]').forEach(c => {
        const b = c.querySelector('b'), max = +c.dataset.max;
        c.querySelectorAll('[data-d]').forEach(btn => btn.addEventListener('click', () => {
            b.textContent = Math.max(0, Math.min(max, +b.textContent + +btn.dataset.d));
        }));
    });
    const el = document.getElementById('gmCrono'), btn = document.getElementById('gmCronoBtn');
    let inicio = 0, acumulado = 0, iv = null;
    const pinta = () => { const s = Math.floor((acumulado + (iv ? Date.now() - inicio : 0)) / 1000); el.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
    btn.addEventListener('click', () => {
        if (iv) { acumulado += Date.now() - inicio; clearInterval(iv); iv = null; btn.textContent = 'Seguir'; }
        else { inicio = Date.now(); iv = setInterval(pinta, 250); btn.textContent = 'Pausar'; }
        pinta();
    });
    document.getElementById('gmCronoReset').addEventListener('click', () => { acumulado = 0; inicio = Date.now(); pinta(); });
})();
</script>
<?php endif; ?>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
