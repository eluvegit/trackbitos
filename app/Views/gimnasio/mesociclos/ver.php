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

    <?php
    // NUEVO: localizar el siguiente bloque pendiente
    $siguienteBloque = null;
    if (!empty($pendientes) && isset($siguienteOrden)) {
        foreach ($pendientes as $pb) {
            if ((int)$pb['orden'] === (int)$siguienteOrden) {
                $siguienteBloque = $pb;
                break;
            }
        }
    }
    ?>

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
            <a href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar/bilbo') ?>" class="btn btn-success">
                Generar BILBO con e1RM actual
            </a>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>Generar (bloques pendientes)</button>
        <?php endif; ?>

        <!-- NUEVO: botón vista minimalista de la serie actual -->
        <button
            class="btn btn-outline-dark ms-auto"
            data-bs-toggle="modal"
            data-bs-target="#modalSerieActual"
            <?= $siguienteBloque ? '' : 'disabled' ?>
            title="<?= $siguienteBloque ? 'Abrir vista de la serie que toca' : 'No hay bloque siguiente' ?>">
            ▶ Ver serie actual
        </button>
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
            if ((int)$b['orden'] >= $startOrden && ($b['bloque_tipo'] ?? '') === 'pico') {
                $endOrden = (int)$b['orden'];
                break;
            }
        }
        if ($endOrden === null) {
            $ordenes = array_column($todos, 'orden');
            $endOrden = !empty($ordenes) ? (int)max($ordenes) : (int)$startOrden;
        }

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
                            
                            <th>Top %</th>
                            <th>Top reps</th>
                            <th>Top kg</th>
                            <th>Back-off</th>
                            <th>Back-off kg</th>
                            <th>Tipo</th>
                            <th>Notas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientes as $b): ?>
                            <?php $esSiguiente = ($siguienteOrden !== null && $b['orden'] === $siguienteOrden); ?>
                            <tr class="<?= $esSiguiente ? 'table-warning' : '' ?>">
                                <td>
                                    <?php if ($esSiguiente): ?>
                                        <span class="badge bg-warning text-dark"><?= (int)$b['orden'] ?></span>
                                    <?php else: ?>
                                       <span class="badge bg-light text-dark"> <?= (int)$b['orden'] ?></span>
                                    <?php endif; ?>
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
                                <td>
                                    <?php
                                    $tipo = esc($b['bloque_tipo']);
                                    $badge = $tipo === 'pico' ? 'bg-danger' : ($tipo === 'deload' ? 'bg-success' : 'bg-secondary');
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $tipo ?></span>
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
                $ordenMax = $bloquesM[count($bloquesM) - 1]['orden'];

                // Tomamos snapshot/lote del último bloque del grupo (normalmente el pico)
                $snap   = $bloquesM[count($bloquesM) - 1]['e1rm_snapshot'] ?? null;
                $loteId = $bloquesM[count($bloquesM) - 1]['lote_id'] ?? null;
                ?>
                <h5 class="mt-4">
                    Mesociclo <?= $mesoNum ?>
                    <small class="text-muted">(#<?= $ordenMin ?>–#<?= $ordenMax ?>)</small>
                    <?php if ($loteId): ?>
                        <span class="badge bg-dark ms-2">Lote #<?= (int)$loteId ?></span>
                    <?php endif; ?>
                    <?php if ($snap): ?>
                        <span class="badge bg-secondary ms-1">e1RM lote: <?= number_format((float)$snap, 1) ?> kg</span>
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
                                        $badge = $tipo === 'pico' ? 'bg-danger' : ($tipo === 'deload' ? 'bg-secondary' : 'bg-success');
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
                                                <div class="text-muted small">e1RM lote: <?= number_format((float)$b['e1rm_snapshot'], 1) ?> kg</div>
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

<!-- NUEVO: Modal Vista Minimalista de la Serie Actual -->
<div class="modal fade" id="modalSerieActual" tabindex="-1" aria-labelledby="modalSerieActualLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header border-0 py-2">
        <h5 class="modal-title" id="modalSerieActualLabel">
            Serie actual · <?= esc($plan['ejercicio']) ?>
            <?php if ($siguienteBloque): ?>
                <small class="text-muted">#<?= (int)$siguienteBloque['orden'] ?></small>
            <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body py-0">

        <?php if ($siguienteBloque): ?>
            <?php
                $topSets = 1; // por defecto 1 set de top
                $boSets  = (int)($siguienteBloque['bo_sets'] ?? 0);
                $boReps  = (int)($siguienteBloque['bo_reps'] ?? 0);
                $topRepsMin = (int)($siguienteBloque['top_reps_min'] ?? 0);
                $topRepsMax = (int)($siguienteBloque['top_reps_max'] ?? 0);
                $topKgMin = (float)($siguienteBloque['_top_min'] ?? 0);
                $topKgMax = (float)($siguienteBloque['_top_max'] ?? 0);
                $boKgMin  = (float)($siguienteBloque['_bo_min'] ?? 0);
                $boKgMax  = (float)($siguienteBloque['_bo_max'] ?? 0);
            ?>

            <div class="row g-2">

              <!-- TOP SET -->
              <div class="col-12 col-lg-6">
                <div class="px-4 py-3 rounded-4 shadow-sm h-100" style="background:#0d6efd0d;">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span class="badge bg-primary">TOP SET</span>
                    </div>
                    <span class="text-muted small"><?= (float)$siguienteBloque['top_pct_min'] * 100 ?>%<?php if ($siguienteBloque['top_pct_max']): ?>–<?= (float)$siguienteBloque['top_pct_max'] * 100 ?>%<?php endif; ?></span>
                  </div>

                  <div class="display-6 fw-bold mb-1">
                    <?= number_format($topKgMin, 1) ?><?php if ($topKgMax): ?>–<?= number_format($topKgMax, 1) ?><?php endif; ?> kg
                  </div>
                  <div class="fs-5 text-muted">
                    <?= $topRepsMin ?><?php if ($topRepsMax): ?>–<?= $topRepsMax ?><?php endif; ?> reps
                  </div>

                  <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted">Series completadas</div>
                    <div class="d-flex align-items-center gap-2">
                      <button class="btn btn-outline-secondary btn-sm" id="topMinus">−</button>
                      <div class="fs-4 fw-bold" id="topCount">0</div>
                      <button class="btn btn-dark btn-sm" id="topPlus">＋</button>
                    </div>
                  </div>
                  <div class="text-end text-muted small mt-1">Objetivo: <?= $topSets ?></div>
                </div>
              </div>

              <!-- BACK-OFF -->
              <div class="col-12 col-lg-6">
                <div class="px-4 py-3 rounded-4 shadow-sm h-100" style="background:#1987540d;">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success">BACK-OFF</span>
                    <span class="text-muted small"><?= $boSets ?> × <?= $boReps ?> reps</span>
                  </div>

                  <div class="display-6 fw-bold mb-1">
                    <?= number_format($boKgMin, 1) ?><?php if ($boKgMax): ?>–<?= number_format($boKgMax, 1) ?><?php endif; ?> kg
                  </div>
                  <div class="fs-5 text-muted"><?= $boReps ?> reps por serie</div>

                  <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted">Series completadas</div>
                    <div class="d-flex align-items-center gap-2">
                      <button class="btn btn-outline-secondary btn-sm" id="boMinus">−</button>
                      <div class="fs-4 fw-bold" id="boCount">0</div>
                      <button class="btn btn-dark btn-sm" id="boPlus">＋</button>
                    </div>
                  </div>
                  <div class="text-end text-muted small mt-1">Objetivo: <?= $boSets ?></div>
                </div>
              </div>

              <!-- CRONÓMETRO -->
              <div class="col-12">
                <div class="px-4 py-3 rounded-4 shadow-sm d-flex flex-wrap align-items-center justify-content-between" style="background:#00000008;">
                  <div class="d-flex align-items-center gap-3 mb-2 mb-sm-0">
                    <span class="badge bg-dark">DESCANSO</span>
                    <div class="display-6 fw-bold mb-0" id="timer">00:00</div>
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-outline-dark" id="timerStart">Iniciar</button>
                    <button class="btn btn-outline-secondary" id="timerPause">Pausar</button>
                    <button class="btn btn-outline-danger" id="timerReset">Reiniciar</button>
                  </div>
                </div>
              </div>

            </div>
        <?php else: ?>
            <div class="alert alert-secondary">No hay bloque siguiente disponible.</div>
        <?php endif; ?>

      </div>
      <div class="modal-footer border-0 py-2">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- NUEVO: JS mínimo para contadores y cronómetro -->
<script>
(function() {
  const topTarget = <?= (int)($siguienteBloque ? 1 : 0) ?>;
  const boTarget  = <?= (int)($siguienteBloque['bo_sets'] ?? 0) ?>;

  const topCountEl = document.getElementById('topCount');
  const boCountEl  = document.getElementById('boCount');

  const topPlus  = document.getElementById('topPlus');
  const topMinus = document.getElementById('topMinus');
  const boPlus   = document.getElementById('boPlus');
  const boMinus  = document.getElementById('boMinus');

  function clamp(val, min, max){ return Math.max(min, Math.min(max, val)); }

  let topCount = 0;
  let boCount  = 0;

  if (topPlus) topPlus.addEventListener('click', () => {
    topCount = clamp(topCount + 1, 0, Math.max(1, topTarget || 1));
    topCountEl.textContent = topCount;
  });
  if (topMinus) topMinus.addEventListener('click', () => {
    topCount = clamp(topCount - 1, 0, Math.max(1, topTarget || 1));
    topCountEl.textContent = topCount;
  });

  if (boPlus) boPlus.addEventListener('click', () => {
    boCount = clamp(boCount + 1, 0, Math.max(1, boTarget || 1));
    boCountEl.textContent = boCount;
  });
  if (boMinus) boMinus.addEventListener('click', () => {
    boCount = clamp(boCount - 1, 0, Math.max(1, boTarget || 1));
    boCountEl.textContent = boCount;
  });

  // Cronómetro
  const timerEl = document.getElementById('timer');
  const btnStart = document.getElementById('timerStart');
  const btnPause = document.getElementById('timerPause');
  const btnReset = document.getElementById('timerReset');

  let interval = null;
  let elapsedMs = 0;
  let lastTick = null;

  function fmt(ms){
    const totalSec = Math.floor(ms / 1000);
    const m = String(Math.floor(totalSec / 60)).padStart(2,'0');
    const s = String(totalSec % 60).padStart(2,'0');
    return `${m}:${s}`;
  }

  function render(){
    timerEl.textContent = fmt(elapsedMs);
  }

  function tick(){
    const now = performance.now();
    if (lastTick != null) {
      elapsedMs += (now - lastTick);
      render();
    }
    lastTick = now;
  }

  function start(){
    if (interval) return;
    lastTick = performance.now();
    interval = setInterval(tick, 200);
  }

  function pause(){
    if (!interval) return;
    clearInterval(interval);
    interval = null;
    lastTick = null;
  }

  function reset(){
    pause();
    elapsedMs = 0;
    render();
  }

  if (btnStart) btnStart.addEventListener('click', start);
  if (btnPause) btnPause.addEventListener('click', pause);
  if (btnReset) btnReset.addEventListener('click', reset);

  // Reset al cerrar modal
  const modal = document.getElementById('modalSerieActual');
  if (modal) {
    modal.addEventListener('hidden.bs.modal', () => {
      reset();
      topCount = 0; boCount = 0;
      if (topCountEl) topCountEl.textContent = '0';
      if (boCountEl)  boCountEl.textContent  = '0';
    });
  }

  render();
})();
</script>
<?= $this->endSection() ?>
