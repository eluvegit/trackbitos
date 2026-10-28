<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
$tamanoLegible = function (int $bytes): string {
    return $bytes >= 1024 * 1024
        ? number_format($bytes / (1024 * 1024), 1) . ' MB'
        : number_format($bytes / 1024, 0) . ' KB';
};
$plural = static fn(int $n, string $uno, string $varios): string => $n . ' ' . ($n === 1 ? $uno : $varios);
$fecha  = static fn(?string $f): string => $f ? date('d/m/Y', strtotime($f)) : '';
$textoPeriodo = $periodo === 'todo' ? 'desde siempre' : 'en los últimos ' . $periodos[$periodo];

$rankingsPeso = [
    'variante' => ['filas' => $pesanVariante, 'ayuda' => 'Por variante, sumando sus versiones (.blend + .stl) y sesiones, apartadas o no.'],
    'pieza'    => ['filas' => $pesanFamilia, 'ayuda' => 'Por pieza, con todas sus variantes juntas.'],
];
?>

<style>
    .estad-tabs { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
    .estad-tabs .nav-link { white-space: nowrap; }
    .estad-fila { border-radius: .5rem; transition: background-color .15s; }
    .estad-fila:hover { background-color: var(--bs-tertiary-bg); }
    .estad-pos { width: 2rem; flex-shrink: 0; }
    .min-w-0 { min-width: 0; }
    .estad-kpi .fs-4 { line-height: 1.1; }
    .estad-grafico { position: relative; height: 240px; }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-bar-chart-line text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Estadísticas</strong>

    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Periodo">
        <?php foreach ($periodos as $clave => $etiqueta): ?>
            <a href="<?= site_url('piezas/estadisticas') . ($clave === 'todo' ? '' : '?periodo=' . $clave) ?>"
                class="btn <?= $clave === $periodo ? 'btn-primary' : 'btn-outline-secondary' ?> estad-periodo"><?= $etiqueta ?></a>
        <?php endforeach; ?>
    </div>
</h5>

<div class="row g-2 g-md-3 mb-3">
    <?php
    $kpis = [
        ['Gastadas', $totalGastadas, 'bi-box-arrow-right', 'text-danger', 'gastadas', $textoPeriodo],
        ['Impresas', $totalImpresas, 'bi-printer', 'text-success', 'impresas', $textoPeriodo],
        ['En stock', $stockTotal, 'bi-box-seam', 'text-primary', 'gastadas', 'ahora mismo'],
        ['Disco', $tamanoLegible($total), 'bi-hdd', 'text-secondary', 'disco', $totalPapelera > 0 ? $tamanoLegible($totalPapelera) . ' en papelera' : 'sin papelera'],
    ];
    ?>
    <?php foreach ($kpis as [$titulo, $valor, $icono, $color, $pestana, $sub]): ?>
        <div class="col-6 col-lg-3">
            <a href="#<?= $pestana ?>" class="card shadow-sm h-100 text-decoration-none text-body estad-kpi" data-estad-ir="<?= $pestana ?>">
                <div class="card-body p-3">
                    <div class="text-muted small d-flex align-items-center gap-1">
                        <i class="bi <?= $icono ?> <?= $color ?>"></i> <?= $titulo ?>
                    </div>
                    <div class="fs-4 fw-semibold"><?= is_int($valor) ? number_format($valor, 0, ',', '.') : $valor ?></div>
                    <div class="small text-muted"><?= esc($sub) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($evolucion['etiquetas'])): ?>
    <?php $porCubo = ['dia' => 'por día', 'semana' => 'por semana', 'mes' => 'por mes'][$evolucion['agrupado']]; ?>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach (['placas' => ['Placas impresas', 'bi-grid-3x3', 'text-info'], 'piezas' => ['Piezas impresas', 'bi-printer', 'text-success']] as $id => [$titulo, $icono, $color]): ?>
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <i class="bi <?= $icono ?> <?= $color ?>"></i> <?= $titulo ?> <?= $porCubo ?>
                                </div>
                                <div class="fs-4 fw-semibold" id="total-<?= $id ?>"></div>
                            </div>
                            <span class="badge rounded-pill" id="tendencia-<?= $id ?>"></span>
                        </div>
                        <div class="estad-grafico"><canvas id="grafico-<?= $id ?>"></canvas></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<ul class="nav nav-pills estad-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#gastadas" type="button" role="tab"><i class="bi bi-box-arrow-right"></i> Más gastadas</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#impresas" type="button" role="tab"><i class="bi bi-printer"></i> Más impresas</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#disco" type="button" role="tab"><i class="bi bi-hdd"></i> Disco</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="gastadas" role="tabpanel">
        <p class="text-muted small mb-2">
            Bajas de existencias <?= $textoPeriodo ?>, frente a lo que entró en ese tiempo (impresiones
            buenas y altas a mano). La barra es qué parte de lo que entró se ha gastado. Top 20.
        </p>
        <?php if (empty($topGastadas)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No hay bajas de existencias <?= $textoPeriodo ?>.
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-1">
                <?php foreach ($topGastadas as $i => $fila): ?>
                    <?php
                    $consumo  = $fila['entradas'] > 0 ? min(100, (int) round($fila['gastadas'] / $fila['entradas'] * 100)) : 100;
                    $colorStk = $fila['stock'] <= 0 ? 'text-danger' : 'text-body-secondary';
                    ?>
                    <a href="<?= esc($fila['url'], 'attr') ?>" class="estad-fila d-flex gap-2 px-2 py-2 text-decoration-none text-body">
                        <span class="estad-pos text-muted small pt-1">#<?= $i + 1 ?></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-baseline gap-2">
                                <strong class="text-truncate"><?= esc($fila['nombre']) ?></strong>
                                <span class="flex-shrink-0"><strong><?= $fila['gastadas'] ?></strong> <span class="text-muted small">gastadas</span></span>
                            </div>
                            <div class="progress mt-1" style="height: 6px;" title="<?= $consumo ?>% de lo que entró">
                                <div class="progress-bar bg-danger" style="width: <?= $consumo ?>%;"></div>
                            </div>
                            <div class="small text-muted mt-1 d-flex flex-wrap column-gap-2">
                                <span>de <?= $plural($fila['entradas'], 'entrada', 'entradas') ?> (<?= $fila['entradas'] > 0 ? $consumo . '%' : '—' ?>)</span>
                                <span class="<?= $colorStk ?>">· <?= $fila['stock'] <= 0 ? 'sin stock' : 'quedan ' . $fila['stock'] ?></span>
                                <?php if ($fila['ultima']): ?><span>· última baja el <?= $fecha($fila['ultima']) ?></span><?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="impresas" role="tabpanel">
        <p class="text-muted small mb-2">
            Copias mandadas a la impresora <?= $textoPeriodo ?>, sumando la cantidad de cada versión en placas
            marcadas como impresas (fallidas incluidas). Top 20.
        </p>
        <?php if (empty($topImpresas)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-printer fs-1 d-block mb-2"></i>
                No hay placas impresas <?= $textoPeriodo ?>.
            </div>
        <?php else: ?>
            <?php $maxUnidades = max(array_column($topImpresas, 'unidades')); ?>
            <div class="d-flex flex-column gap-1">
                <?php foreach ($topImpresas as $i => $fila): ?>
                    <a href="<?= esc($fila['url'], 'attr') ?>" class="estad-fila d-flex gap-2 px-2 py-2 text-decoration-none text-body">
                        <span class="estad-pos text-muted small pt-1">#<?= $i + 1 ?></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-baseline gap-2">
                                <strong class="text-truncate"><?= esc($fila['nombre']) ?></strong>
                                <span class="flex-shrink-0"><strong><?= $fila['unidades'] ?></strong> <span class="text-muted small"><?= $fila['unidades'] === 1 ? 'unidad' : 'unidades' ?></span></span>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?= (int) round($fila['unidades'] / $maxUnidades * 100) ?>%;"></div>
                            </div>
                            <div class="small text-muted mt-1">
                                en <?= $plural($fila['placas'], 'placa', 'placas') ?>
                                <?php if ($fila['ultima']): ?>· última el <?= $fecha($fila['ultima']) ?><?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="disco" role="tabpanel">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <p class="text-muted small mb-0 me-auto">
                Lo que ocupa <code>writable/piezas</code> en disco (siempre total, no depende del periodo).
            </p>
            <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="btn btn-sm btn-outline-secondary"
                title="El .blend de referencia de cada pieza más su historial en texto. Sin STL ni fotos.">
                <i class="bi bi-download"></i> Copia de seguridad
            </a>
        </div>
        <?php if ($totalPapelera > 0): ?>
            <div class="alert alert-warning small py-2">
                <?= $tamanoLegible($totalPapelera) ?> en la papelera. Se liberan con <code>php spark piezas:purgar</code> en el servidor.
            </div>
        <?php endif; ?>

        <div class="btn-group btn-group-sm mb-2" role="group">
            <input type="radio" class="btn-check" name="estadPeso" id="pesoVariante" value="variante" checked>
            <label class="btn btn-outline-secondary" for="pesoVariante">Por variante</label>
            <input type="radio" class="btn-check" name="estadPeso" id="pesoPieza" value="pieza">
            <label class="btn btn-outline-secondary" for="pesoPieza">Por pieza</label>
        </div>

        <?php foreach ($rankingsPeso as $clave => $seccion): ?>
            <div data-peso="<?= $clave ?>" class="<?= $clave === 'variante' ? '' : 'd-none' ?>">
                <p class="text-muted small mb-2"><?= $seccion['ayuda'] ?> Top 20.</p>
                <?php if (empty($seccion['filas'])): ?>
                    <p class="text-muted">Todavía no hay ningún fichero que pese algo.</p>
                <?php else: ?>
                    <?php $maxBytes = max(array_column($seccion['filas'], 'bytes')); ?>
                    <div class="d-flex flex-column gap-1">
                        <?php foreach ($seccion['filas'] as $i => $fila): ?>
                            <a href="<?= esc($fila['url'], 'attr') ?>" class="estad-fila d-flex gap-2 px-2 py-2 text-decoration-none text-body">
                                <span class="estad-pos text-muted small pt-1">#<?= $i + 1 ?></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-baseline gap-2">
                                        <strong class="text-truncate"><?= esc($fila['nombre']) ?></strong>
                                        <span class="text-muted small flex-shrink-0"><?= $tamanoLegible($fila['bytes']) ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?= $maxBytes > 0 ? (int) round($fila['bytes'] / $maxBytes * 100) : 0 ?>%;"></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('.estad-tabs [data-bs-toggle="pill"]');
    var mostrar = function (id) {
        var btn = document.querySelector('.estad-tabs [data-bs-target="#' + id + '"]');
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    };
    var anclarPeriodos = function (hash) {
        document.querySelectorAll('.estad-periodo').forEach(function (a) {
            a.href = a.href.split('#')[0] + hash;
        });
    };
    tabs.forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            history.replaceState(null, '', btn.dataset.bsTarget);
            anclarPeriodos(btn.dataset.bsTarget);
        });
    });
    document.querySelectorAll('[data-estad-ir]').forEach(function (a) {
        a.addEventListener('click', function (e) { e.preventDefault(); mostrar(a.dataset.estadIr); });
    });
    if (location.hash) { mostrar(location.hash.slice(1)); anclarPeriodos(location.hash); }

    document.querySelectorAll('input[name="estadPeso"]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('[data-peso]').forEach(function (el) {
                el.classList.toggle('d-none', el.dataset.peso !== r.value);
            });
        });
    });
})();
</script>

<?php if (!empty($evolucion['etiquetas'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var ev = <?= json_encode($evolucion) ?>;
    var ventana = { dia: 7, semana: 4, mes: 3 }[ev.agrupado];
    var piezas = ev.buenas.map(function (b, i) { return b + ev.fallidas[i]; });

    var media = function (serie) {
        return serie.map(function (_, i) {
            var trozo = serie.slice(Math.max(0, i - ventana + 1), i + 1);
            return Math.round(trozo.reduce(function (a, b) { return a + b; }, 0) / trozo.length * 10) / 10;
        });
    };
    var suma = function (s) { return s.reduce(function (a, b) { return a + b; }, 0); };

    var tendencia = function (id, serie) {
        var badge = document.getElementById('tendencia-' + id);
        document.getElementById('total-' + id).textContent = suma(serie).toLocaleString('es-ES');
        var mitad = Math.floor(serie.length / 2);
        if (mitad < 1) { badge.remove(); return; }
        var antes = suma(serie.slice(0, mitad)) / mitad;
        var ahora = suma(serie.slice(serie.length - mitad)) / mitad;
        var pct = antes > 0 ? Math.round((ahora - antes) / antes * 100) : (ahora > 0 ? 100 : 0);
        var estable = Math.abs(pct) < 10;
        badge.className = 'badge rounded-pill ' + (estable ? 'text-bg-secondary' : pct > 0 ? 'text-bg-warning' : 'text-bg-info');
        badge.innerHTML = '<i class="bi ' + (estable ? 'bi-arrow-right' : pct > 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow') + '"></i> '
            + (estable ? 'Se mantiene' : (pct > 0 ? '+' : '') + pct + '%');
        badge.title = 'Media de la segunda mitad del periodo frente a la primera';
    };

    Chart.defaults.color = 'rgba(255,255,255,.6)';
    Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    var degradado = function (ctx, rgb) {
        var g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
        g.addColorStop(0, 'rgba(' + rgb + ',.9)');
        g.addColorStop(1, 'rgba(' + rgb + ',.25)');
        return g;
    };

    var opciones = function (apilado) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                tooltip: { padding: 10, cornerRadius: 8 }
            },
            scales: {
                x: { stacked: apilado, grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, autoSkipPadding: 12 } },
                y: { stacked: apilado, beginAtZero: true, ticks: { precision: 0 } }
            }
        };
    };

    var lineaTendencia = function (serie) {
        return {
            type: 'line', label: 'Tendencia (media ' + ventana + ')', data: media(serie), stack: 'tendencia',
            borderColor: '#ffc107', backgroundColor: '#ffc107', borderWidth: 2.5, tension: .4,
            pointRadius: 0, pointHoverRadius: 4, order: 0
        };
    };
    var barra = { borderRadius: 4, maxBarThickness: 28, order: 1 };

    tendencia('placas', ev.placas);
    new Chart(document.getElementById('grafico-placas'), {
        type: 'bar',
        data: {
            labels: ev.etiquetas,
            datasets: [
                Object.assign({ label: 'Placas', data: ev.placas, backgroundColor: function (c) { return degradado(c, '13,202,240'); } }, barra),
                lineaTendencia(ev.placas)
            ]
        },
        options: opciones(false)
    });

    tendencia('piezas', piezas);
    new Chart(document.getElementById('grafico-piezas'), {
        type: 'bar',
        data: {
            labels: ev.etiquetas,
            datasets: [
                Object.assign({ label: 'Buenas', data: ev.buenas, stack: 'p', backgroundColor: function (c) { return degradado(c, '25,135,84'); } }, barra),
                Object.assign({ label: 'Fallidas', data: ev.fallidas, stack: 'p', backgroundColor: function (c) { return degradado(c, '220,53,69'); } }, barra),
                lineaTendencia(piezas)
            ]
        },
        options: opciones(true)
    });
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
