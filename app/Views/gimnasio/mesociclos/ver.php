<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">
            <?= esc($plan['nombre']) ?>
            <small class="text-muted">(<?= esc($plan['ejercicio']) ?>)</small>
        </h1>
    </div>

    <div class="mb-4 d-flex flex-wrap gap-2">
        <a href="<?= site_url('gimnasio/mesociclos') ?>" class="btn btn-outline-secondary">← Volver</a>
        <a class="btn btn-primary" href="<?= site_url('gimnasio/mesociclos/nuevo') ?>">Nuevo plan</a>
    </div>

    <p class="mb-2">
        e1RM base (actual): <strong><?= number_format((float)$plan['e1rm_base'], 1) ?> kg</strong> · Redondeo: <?= (float)$plan['redondeo_kg'] ?> kg
    </p>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-primary" href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/bloque/nuevo') ?>">Añadir bloque</a>

        <?php if ($bloquesPendientes === 0): ?>
            <a href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/ajustar') ?>" class="btn btn-warning">
                Ajustar e1RM y generar nuevo lote
            </a>
            <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="plantilla" value="estandar">
                <button class="btn btn-success">Generar con e1RM actual</button>
            </form>
            <a href="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/generar/bilbo') ?>" class="btn btn-success">
                Generar BILBO con e1RM actual
            </a>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>Generar (bloques pendientes)</button>
        <?php endif; ?>
    </div>

    <!-- PROGRESO MESOCICLO ACTUAL -->
    <?php
    // 0–100% del mesociclo activo (entre el último 'pico' completado y el próximo 'pico')
    $todos = array_merge($hechos, $pendientes);
    usort($todos, fn($a, $b) => $a['orden'] <=> $b['orden']);

    if (empty($todos)) {
        $pct = 0;
    } else {
        $lastPicoOrden = null;
        foreach ($hechos as $h) {
            if (($h['bloque_tipo'] ?? '') === 'pico') {
                $o = (int)$h['orden'];
                if ($lastPicoOrden === null || $o > $lastPicoOrden) $lastPicoOrden = $o;
            }
        }
        $startOrden = $lastPicoOrden !== null 
    ? $lastPicoOrden + 1 
    : (!empty($todos) && isset($todos[0]['orden']) ? (int)$todos[0]['orden'] : 0);

        $endOrden = null;
        foreach ($todos as $b) {
            if ((int)$b['orden'] >= $startOrden && ($b['bloque_tipo'] ?? '') === 'pico') { $endOrden = (int)$b['orden']; break; }
        }
        if ($endOrden === null) $endOrden = (int)max(array_column($todos, 'orden'));
        $totalMeso = max(0, $endOrden - $startOrden + 1);
        $hechosEnEsteMeso = 0;
        if ($totalMeso > 0) {
            foreach ($hechos as $h) {
                $o = (int)$h['orden'];
                if ($o >= $startOrden && $o <= $endOrden) $hechosEnEsteMeso++;
            }
        }
        $pct = ($totalMeso > 0) ? round(($hechosEnEsteMeso / $totalMeso) * 100) : 0;
        if (empty($pendientes)) $pct = 100; // si no hay pendientes, visualmente 100%
        $pct = max(0, min(100, $pct));
    }
    ?>
    <div class="progress mb-4" style="max-width:420px;">
        <div class="progress-bar" role="progressbar"
             style="width: <?= $pct ?>%;"
             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
            <?= $pct ?>%
        </div>
    </div>

    <?php if (empty($pendientes) && empty($hechos)): ?>
        <div class="alert alert-secondary">Aún no hay bloques.</div>
    <?php else: ?>

        <!-- PENDIENTES -->
        <h3 class="mt-2">Bloques pendientes (<?= count($pendientes) ?>)</h3>
        <?php if (empty($pendientes)): ?>
            <div class="alert alert-success">No hay bloques pendientes. Puedes ajustar e1RM y generar el siguiente lote.</div>
        <?php else: ?>
            <p class="text-muted small mb-1">*Kg calculados con el <strong>e1RM actual</strong> del plan.</p>
            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo</th>
                            <th>Top %</th>
                            <th>Top reps</th>
                            <th>Top kg</th>
                            <th>Back-off</th>
                            <th>Back-off kg</th>
                            <th>Notas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientes as $b): ?>
                            <?php $esSiguiente = ($siguienteOrden !== null && $b['orden'] === $siguienteOrden); ?>
                            <tr class="<?= $esSiguiente ? 'table-warning' : '' ?>">
                                <td>
                                    <?= (int)$b['orden'] ?>
                                    <?php if ($esSiguiente): ?>
                                        <span class="badge bg-warning text-dark ms-2">Toca este</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                      $tipo = esc($b['bloque_tipo']);
                                      $badge = $tipo==='pico' ? 'bg-danger' : ($tipo==='deload' ? 'bg-secondary' : 'bg-info text-dark');
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $tipo ?></span>
                                </td>
                                <td>
                                    <?= (float)$b['top_pct_min'] * 100 ?>%
                                    <?php if ($b['top_pct_max']): ?>– <?= (float)$b['top_pct_max'] * 100 ?>%<?php endif; ?>
                                </td>
                                <td>
                                    <?= (int)$b['top_reps_min'] ?>
                                    <?php if ($b['top_reps_max']): ?>– <?= (int)$b['top_reps_max'] ?><?php endif; ?>
                                </td>
                                <td>
                                    <?= number_format($b['_top_min'], 1) ?> kg
                                    <?php if ($b['_top_max']): ?> – <?= number_format($b['_top_max'], 1) ?> kg<?php endif; ?>
                                </td>
                                <td><?= (int)$b['bo_sets'] ?> × <?= (int)$b['bo_reps'] ?></td>
                                <td>
                                    <?= number_format($b['_bo_min'], 1) ?> kg
                                    <?php if ($b['_bo_max']): ?> – <?= number_format($b['_bo_max'], 1) ?> kg<?php endif; ?>
                                </td>
                                <td><?= esc($b['notas']) ?></td>
                                <td class="text-nowrap text-center">
                                    <form method="post" action="<?= site_url('gimnasio/mesociclos/bloque/' . $b['id'] . '/hecho') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-success" style="width:80px" <?= $esSiguiente ? '' : 'disabled' ?>>
                                            <?= $esSiguiente ? '<i class="bi bi-check-lg"></i> Hecho' : 'Pendiente' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- HECHOS (agrupados por mesociclo, incluyendo el pico) -->
        <h3>Bloques completados (<?= count($hechos) ?>)</h3>
        <?php if (empty($hechos)): ?>
            <div class="alert alert-light">Aún no has completado ningún bloque.</div>
        <?php else: ?>
            <?php
            // 1) Orden ASC para agrupar cronológicamente
            $hechosAsc = $hechos;
            usort($hechosAsc, fn($a, $b) => $a['orden'] <=> $b['orden']);

            // 2) Agrupar por mesociclo (cierra cuando encuentra 'pico', incluyéndolo)
            $grupos = [];
            $grupoActual = [];
            foreach ($hechosAsc as $b) {
                $grupoActual[] = $b;
                if (($b['bloque_tipo'] ?? '') === 'pico') {
                    $grupos[] = $grupoActual;
                    $grupoActual = [];
                }
            }
            if (!empty($grupoActual)) $grupos[] = $grupoActual;

            // 3) Más reciente primero
            $grupos = array_reverse($grupos);
            $mesoNum = count($grupos);
            ?>

            <?php foreach ($grupos as $bloquesM): ?>
                <?php
                $ordenMin = $bloquesM[0]['orden'];
                $ordenMax = $bloquesM[count($bloquesM)-1]['orden'];

                // Tomamos snapshot/lote del último bloque del grupo (normalmente el pico)
                $snap   = $bloquesM[count($bloquesM)-1]['e1rm_snapshot'] ?? null;
                $loteId = $bloquesM[count($bloquesM)-1]['lote_id'] ?? null;
                ?>
                <h5 class="mt-4">
                    Mesociclo <?= $mesoNum ?>
                    <small class="text-muted">(#<?= $ordenMin ?>–#<?= $ordenMax ?>)</small>
                    <?php if ($loteId): ?>
                        <span class="badge bg-dark ms-2">Lote #<?= (int)$loteId ?></span>
                    <?php endif; ?>
                    <?php if ($snap): ?>
                        <span class="badge bg-secondary ms-1">e1RM lote: <?= number_format((float)$snap,1) ?> kg</span>
                    <?php endif; ?>
                </h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tipo</th>
                                <th>Top %</th>
                                <th>Top reps</th>
                                <th>Top kg</th>
                                <th>Back-off</th>
                                <th>Back-off kg</th>
                                <th>Notas</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloquesM as $b): ?>
                                <tr class="table-success">
                                    <td><?= (int)$b['orden'] ?></td>
                                    <td>
                                        <?php
                                          $tipo = esc($b['bloque_tipo']);
                                          $badge = $tipo==='pico' ? 'bg-danger' : ($tipo==='deload' ? 'bg-secondary' : 'bg-success');
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= $tipo ?></span>
                                    </td>
                                    <td>
                                        <?= (float)$b['top_pct_min'] * 100 ?>%
                                        <?php if ($b['top_pct_max']): ?>– <?= (float)$b['top_pct_max'] * 100 ?>%<?php endif; ?>
                                    </td>
                                    <td>
                                        <?= (int)$b['top_reps_min'] ?>
                                        <?php if ($b['top_reps_max']): ?>– <?= (int)$b['top_reps_max'] ?><?php endif; ?>
                                    </td>
                                    <td>
                                        <?= number_format($b['_top_min'], 1) ?> kg
                                        <?php if ($b['_top_max']): ?> – <?= number_format($b['_top_max'], 1) ?> kg<?php endif; ?>
                                        <?php if (!empty($b['e1rm_snapshot'])): ?>
                                            <div class="text-muted small">e1RM lote: <?= number_format((float)$b['e1rm_snapshot'],1) ?> kg</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)$b['bo_sets'] ?> × <?= (int)$b['bo_reps'] ?></td>
                                    <td>
                                        <?= number_format($b['_bo_min'], 1) ?> kg
                                        <?php if ($b['_bo_max']): ?> – <?= number_format($b['_bo_max'], 1) ?> kg<?php endif; ?>
                                    </td>
                                    <td><?= esc($b['notas']) ?></td>
                                    <td class="text-center">✅</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php $mesoNum--; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
