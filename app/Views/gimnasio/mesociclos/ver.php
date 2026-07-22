<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<style>
.meso-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.meso-back:hover { color: var(--bs-emphasis-color); }
.meso-title { font-size: 1.3rem; font-weight: 700; display: flex; align-items: baseline; gap: .5rem; flex-wrap: wrap; }
.meso-title-sub { font-size: .85rem; font-weight: 400; color: var(--bs-secondary-color); }

.meso-cta {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1.1rem;
    border-radius: 999px;
    border: none;
    background: #7c3aed;
    color: #fff;
    font-weight: 600;
    font-size: .9rem;
}
.meso-cta:disabled { background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); }
.meso-cta:not(:disabled):hover { filter: brightness(1.1); }

.meso-actions { display: flex; flex-wrap: wrap; gap: 6px; }
.meso-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .8rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-size: .8rem;
    text-decoration: none;
    cursor: pointer;
}
.meso-pill:hover { filter: brightness(1.15); }
.meso-pill-accent { border-color: rgba(124,58,237,.4); color: #a78bfa; background: rgba(124,58,237,.1); }
.meso-pill-disabled { opacity: .6; cursor: not-allowed; }

.meso-progress {
    position: relative;
    max-width: 420px;
    height: 22px;
    border-radius: 999px;
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    overflow: hidden;
}
.meso-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
    transition: width .2s ease;
}
.meso-progress-label {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .68rem;
    font-weight: 600;
    color: var(--bs-emphasis-color);
    text-shadow: 0 1px 2px rgba(0,0,0,.4);
}

.meso-section-title { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
.meso-section-count {
    font-size: .72rem;
    font-weight: 700;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
    border-radius: 999px;
    padding: .1rem .55rem;
}

.meso-group-title { font-size: .95rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.meso-lote-badge {
    font-size: .68rem;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
    border-radius: 999px;
    padding: .1rem .55rem;
}

/* Tarjetas de bloque (sustituyen a las tablas anchas) */
.meso-blocks { display: flex; flex-direction: column; gap: 8px; }
.meso-block {
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-tertiary-bg);
    padding: 10px 12px;
}
.meso-block.is-next { border-color: rgba(245,158,11,.5); background: rgba(245,158,11,.08); }
.meso-block.is-hecho { opacity: .85; }

.meso-block-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.meso-block-orden { font-weight: 700; font-size: .85rem; color: var(--bs-emphasis-color); }
.meso-block-tipo {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    padding: .1rem .5rem;
    border-radius: 999px;
}
.meso-tipo-pico { background: rgba(220,53,69,.15); color: #f27784; }
.meso-tipo-deload { background: rgba(16,185,129,.15); color: #34d399; }
.meso-tipo-normal { background: var(--bs-body-bg); color: var(--bs-secondary-color); }
.meso-block-next {
    font-size: .68rem;
    font-weight: 700;
    color: #f59e0b;
    margin-left: auto;
}
.meso-block-check { margin-left: auto; color: #34d399; font-size: 1.1rem; }

.meso-block-row { display: flex; gap: 6px; font-size: .84rem; }
.meso-block-label { flex: 0 0 auto; width: 68px; color: var(--bs-secondary-color); }
.meso-block-value { color: var(--bs-emphasis-color); font-weight: 600; }
.meso-block-pct { font-weight: 400; color: var(--bs-secondary-color); }

.meso-block-notas { margin-top: 4px; font-size: .78rem; color: var(--bs-secondary-color); font-style: italic; }

.meso-block-actions { margin-top: 8px; }
.meso-block-btn {
    width: 100%;
    padding: .4rem;
    border-radius: 8px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    font-size: .82rem;
    font-weight: 600;
}
.meso-block-btn-active {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
    cursor: pointer;
}
.meso-block-btn:disabled { cursor: not-allowed; }
</style>
<div class="container">

    <div class="meso-header mb-3">
        <a href="<?= site_url('gimnasio/mesociclos') ?>" class="meso-back"><i class="bi bi-chevron-left"></i> Mesociclos</a>
        <h2 class="meso-title mb-0 mt-1">
            <?= esc($plan['nombre']) ?>
            <span class="meso-title-sub"><?= esc($plan['ejercicio']) ?></span>
        </h2>
        <p class="text-muted small mb-0 mt-1">
            e1RM base: <strong class="text-body"><?= number_format((float)$plan['e1rm_base'], 1) ?> kg</strong> · Redondeo: <?= (float)$plan['redondeo_kg'] ?> kg
        </p>
    </div>

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

    <!-- Acción principal -->
    <button
        class="meso-cta mb-3"
        data-bs-toggle="modal"
        data-bs-target="#modalSerieActual"
        <?= $siguienteBloque ? '' : 'disabled' ?>
        title="<?= $siguienteBloque ? 'Abrir vista de la serie que toca' : 'No hay bloque siguiente' ?>">
        <i class="bi bi-play-fill"></i> Ver serie actual
    </button>

    <!-- Otras acciones -->
    <div class="meso-actions mb-3">
        <a class="meso-pill" href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/bloque/nuevo') ?>">
            <i class="bi bi-plus-lg"></i> Añadir bloque
        </a>

        <?php if ($bloquesPendientes === 0): ?>
            <a href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/ajustar') ?>" class="meso-pill">
                <i class="bi bi-sliders"></i> Ajustar e1RM
            </a>
            <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="plantilla" value="estandar">
                <button class="meso-pill meso-pill-accent"><i class="bi bi-arrow-repeat"></i> Generar lote</button>
            </form>
            <a href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/generar/bilbo') ?>" class="meso-pill meso-pill-accent">
                <i class="bi bi-arrow-repeat"></i> Generar BILBO
            </a>
        <?php else: ?>
            <span class="meso-pill meso-pill-disabled">Generar (bloques pendientes)</span>
        <?php endif; ?>

        <a class="meso-pill" href="<?= site_url('gimnasio/mesociclos/nuevo') ?>">
            <i class="bi bi-file-earmark-plus"></i> Nuevo plan
        </a>
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
    <div class="meso-progress mb-4">
        <div class="meso-progress-bar" style="width: <?= $pct ?>%;"></div>
        <span class="meso-progress-label"><?= $pct ?>% del mesociclo actual</span>
    </div>

    <?php if (empty($pendientes) && empty($hechos)): ?>
        <div class="alert alert-secondary">Aún no hay bloques.</div>
    <?php else: ?>

        <!-- PENDIENTES -->
        <h3 class="meso-section-title mt-2">Bloques pendientes <span class="meso-section-count"><?= count($pendientes) ?></span></h3>
        <?php if (empty($pendientes)): ?>
            <div class="alert alert-success">No hay bloques pendientes. Puedes ajustar e1RM y generar el siguiente lote.</div>
        <?php else: ?>
            <p class="text-muted small mb-2">*Kg calculados con el <strong>e1RM actual</strong> del plan.</p>
            <div class="meso-blocks mb-4">
                <?php foreach ($pendientes as $b): ?>
                    <?php
                    $esSiguiente = ($siguienteOrden !== null && $b['orden'] === $siguienteOrden);
                    $tipo = esc($b['bloque_tipo']);

                    $topPct = number_format((float)$b['top_pct_min'] * 100, 0) . '%';
                    if ($b['top_pct_max']) $topPct .= '–' . number_format((float)$b['top_pct_max'] * 100, 0) . '%';

                    $topReps = (int)$b['top_reps_min'];
                    if ($b['top_reps_max']) $topReps .= '–' . (int)$b['top_reps_max'];

                    $topKg = number_format($b['_top_min'], 1);
                    if ($b['_top_max']) $topKg .= '–' . number_format($b['_top_max'], 1);

                    $boKg = number_format($b['_bo_min'], 1);
                    if ($b['_bo_max']) $boKg .= '–' . number_format($b['_bo_max'], 1);
                    ?>
                    <div class="meso-block <?= $esSiguiente ? 'is-next' : '' ?>">
                        <div class="meso-block-top">
                            <span class="meso-block-orden">#<?= (int)$b['orden'] ?></span>
                            <span class="meso-block-tipo meso-tipo-<?= $tipo ?>"><?= $tipo ?></span>
                            <?php if ($esSiguiente): ?><span class="meso-block-next">Siguiente</span><?php endif; ?>
                        </div>
                        <div class="meso-block-row">
                            <span class="meso-block-label">Top</span>
                            <span class="meso-block-value"><?= $topKg ?>kg × <?= $topReps ?> <span class="meso-block-pct">(<?= $topPct ?>)</span></span>
                        </div>
                        <div class="meso-block-row">
                            <span class="meso-block-label">Back-off</span>
                            <span class="meso-block-value"><?= (int)$b['bo_sets'] ?>×<?= (int)$b['bo_reps'] ?> @ <?= $boKg ?>kg</span>
                        </div>
                        <?php if (!empty($b['notas'])): ?>
                            <div class="meso-block-notas"><?= esc($b['notas']) ?></div>
                        <?php endif; ?>
                        <form method="post" action="<?= site_url('gimnasio/mesociclos/bloque/' . $b['id'] . '/hecho') ?>" class="meso-block-actions">
                            <?= csrf_field() ?>
                            <button class="meso-block-btn <?= $esSiguiente ? 'meso-block-btn-active' : '' ?>" <?= $esSiguiente ? '' : 'disabled' ?>>
                                <?= $esSiguiente ? '<i class="bi bi-check-lg"></i> Marcar hecho' : 'Pendiente' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- HECHOS (agrupados por mesociclo, incluyendo el pico) -->
        <h3 class="meso-section-title">Bloques completados <span class="meso-section-count"><?= count($hechos) ?></span></h3>
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
                <h5 class="meso-group-title mt-4">
                    Mesociclo <?= $mesoNum ?>
                    <span class="text-muted small fw-normal">(#<?= $ordenMin ?>–#<?= $ordenMax ?>)</span>
                    <?php if ($loteId): ?>
                        <span class="meso-lote-badge">Lote #<?= (int)$loteId ?></span>
                    <?php endif; ?>
                    <?php if ($snap): ?>
                        <span class="meso-lote-badge">e1RM lote: <?= number_format((float)$snap, 1) ?> kg</span>
                    <?php endif; ?>
                </h5>

                <div class="meso-blocks mb-2">
                    <?php foreach ($bloquesM as $b): ?>
                        <?php
                        $tipo = esc($b['bloque_tipo']);

                        $topPct = number_format((float)$b['top_pct_min'] * 100, 0) . '%';
                        if ($b['top_pct_max']) $topPct .= '–' . number_format((float)$b['top_pct_max'] * 100, 0) . '%';

                        $topReps = (int)$b['top_reps_min'];
                        if ($b['top_reps_max']) $topReps .= '–' . (int)$b['top_reps_max'];

                        $topKg = number_format($b['_top_min'], 1);
                        if ($b['_top_max']) $topKg .= '–' . number_format($b['_top_max'], 1);

                        $boKg = number_format($b['_bo_min'], 1);
                        if ($b['_bo_max']) $boKg .= '–' . number_format($b['_bo_max'], 1);
                        ?>
                        <div class="meso-block is-hecho">
                            <div class="meso-block-top">
                                <span class="meso-block-orden">#<?= (int)$b['orden'] ?></span>
                                <span class="meso-block-tipo meso-tipo-<?= $tipo ?>"><?= $tipo ?></span>
                                <i class="bi bi-check-circle-fill meso-block-check"></i>
                            </div>
                            <div class="meso-block-row">
                                <span class="meso-block-label">Top</span>
                                <span class="meso-block-value"><?= $topKg ?>kg × <?= $topReps ?> <span class="meso-block-pct">(<?= $topPct ?>)</span></span>
                            </div>
                            <div class="meso-block-row">
                                <span class="meso-block-label">Back-off</span>
                                <span class="meso-block-value"><?= (int)$b['bo_sets'] ?>×<?= (int)$b['bo_reps'] ?> @ <?= $boKg ?>kg</span>
                            </div>
                            <?php if (!empty($b['e1rm_snapshot'])): ?>
                                <div class="meso-block-notas">e1RM lote: <?= number_format((float)$b['e1rm_snapshot'], 1) ?> kg</div>
                            <?php endif; ?>
                            <?php if (!empty($b['notas'])): ?>
                                <div class="meso-block-notas"><?= esc($b['notas']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
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
