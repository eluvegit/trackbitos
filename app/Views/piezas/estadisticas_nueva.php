<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
$tamanoLegible = static fn (int $bytes): string => $bytes >= 1024 * 1024
    ? number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB'
    : number_format($bytes / 1024, 0, ',', '.') . ' KB';
$plural = static fn (int $n, string $uno, string $varios): string => $n . ' ' . ($n === 1 ? $uno : $varios);
$fecha  = static fn (?string $f): string => $f ? date('d/m/Y', strtotime($f)) : '';
$textoPeriodo = $periodo === 'todo' ? 'desde siempre' : 'en los últimos ' . $periodos[$periodo];
$num = static fn (int $n): string => number_format($n, 0, ',', '.');

$rankingsPeso = [
    'variante' => ['filas' => $pesanVariante, 'ayuda' => 'Por variante, sumando sus versiones (.blend + .stl) y sesiones, apartadas o no.'],
    'pieza'    => ['filas' => $pesanFamilia, 'ayuda' => 'Por pieza, con todas sus variantes juntas.'],
];
?>

<div class="fv pv es">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Estadísticas</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="fv-gbtn" title="El .blend de referencia de cada pieza más su historial en texto. Sin STL ni fotos."><i class="bi bi-download"></i> Copia de seguridad</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/estadisticas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <div class="pv-hero-fila">
            <h1>Estadísticas</h1>
            <nav class="es-periodos" aria-label="Periodo">
                <?php foreach ($periodos as $clave => $etiqueta): ?>
                    <a href="<?= site_url('piezas/estadisticas') . ($clave === 'todo' ? '' : '?periodo=' . $clave) ?>"
                        class="estad-periodo" aria-pressed="<?= $clave === $periodo ? 'true' : 'false' ?>"><?= $etiqueta ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="fv-meta"><span>Movimiento <?= $textoPeriodo ?>; el stock y el disco son de ahora mismo.</span></div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>

    <div class="es-kpis">
        <a href="#gastadas" data-estad-ir="gastadas"><span class="k">Gastadas</span><span class="v e-bad"><?= $num($totalGastadas) ?></span><span class="s"><?= esc($textoPeriodo) ?></span></a>
        <a href="#impresas" data-estad-ir="impresas"><span class="k">Impresas</span><span class="v e-ok"><?= $num($totalImpresas) ?></span><span class="s"><?= esc($textoPeriodo) ?></span></a>
        <a href="<?= site_url('piezas/existencias') ?>"><span class="k">En stock</span><span class="v"><?= $num($stockTotal) ?></span><span class="s">ahora mismo</span></a>
        <a href="#disco" data-estad-ir="disco"><span class="k">Disco</span><span class="v"><?= $tamanoLegible($total) ?></span><span class="s"><?= $totalPapelera > 0 ? $tamanoLegible($totalPapelera) . ' en papelera' : 'sin papelera' ?></span></a>
    </div>

    <?php if ($sinInventario['placas'] !== []): ?>
        <details class="fv-aviso es-aviso">
            <summary>
                <i class="bi bi-exclamation-triangle"></i>
                <span><b><?= $plural($sinInventario['unidades'], 'unidad', 'unidades') ?></b> de <?= $plural(count($sinInventario['placas']), 'placa impresa', 'placas impresas') ?> sin pasar al inventario: no cuentan en el stock.</span>
                <i class="bi bi-chevron-right ch"></i>
            </summary>
            <div class="es-aviso-c">
                <?php foreach ($sinInventario['placas'] as $p): ?>
                    <div class="fv-linea">
                        <a href="<?= site_url('piezas/placa/' . (int) $p['id'] . '/bitacora/editar') ?>"><?= esc($p['nombre']) ?></a>
                        <span class="der fv-mono t3"><?= $fecha($p['impresa_en']) ?> · <?= $plural($p['unidades'], 'ud.', 'uds.') ?></span>
                    </div>
                <?php endforeach; ?>
                <form method="post" action="<?= site_url('piezas/estadisticas/recuento') ?>"
                    onsubmit="return confirm('¿Marcar estas <?= count($sinInventario['placas']) ?> placas como ya contadas en el recuento? No se sumarán al stock.');">
                    <?= csrf_field() ?>
                    <button class="fv-btn fv-btn-sm"><i class="bi bi-clipboard-check"></i> Marcar todas como contadas en el recuento</button>
                </form>
            </div>
        </details>
    <?php endif; ?>

    <?php if (!empty($evolucion['etiquetas'])): ?>
        <?php $porCubo = ['dia' => 'por día', 'semana' => 'por semana', 'mes' => 'por mes'][$evolucion['agrupado']]; ?>
        <div class="es-graficos">
            <?php foreach (['placas' => 'Placas impresas', 'piezas' => 'Piezas impresas'] as $id => $titulo): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3><?= $titulo ?> <?= $porCubo ?></h3><span class="der fv-estado e-neu" id="tendencia-<?= $id ?>"></span></div>
                    <div class="es-total" id="total-<?= $id ?>"></div>
                    <div class="es-grafico"><canvas id="grafico-<?= $id ?>"></canvas></div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <nav class="fv-secs" id="esSecs">
        <button type="button" data-sec="gastadas" aria-pressed="true">Más gastadas <span><?= count($topGastadas) ?></span></button>
        <button type="button" data-sec="impresas" aria-pressed="false">Más impresas <span><?= count($topImpresas) ?></span></button>
        <button type="button" data-sec="disco" aria-pressed="false">Disco <span><?= $tamanoLegible($total) ?></span></button>
    </nav>

    <section class="fv-panel pv-estrecha es-pane" id="gastadas">
        <p class="es-ayuda">Bajas de existencias <?= $textoPeriodo ?>, frente a lo que entró en ese tiempo (impresiones buenas y altas a mano). La barra es qué parte de lo que entró se ha gastado. Top 20.</p>
        <?php if (empty($topGastadas)): ?>
            <p class="fv-vacio">No hay bajas de existencias <?= $textoPeriodo ?>.</p>
        <?php else: ?>
            <div class="pv-lista">
                <?php foreach ($topGastadas as $i => $fila): ?>
                    <?php $consumo = $fila['entradas'] > 0 ? min(100, (int) round($fila['gastadas'] / $fila['entradas'] * 100)) : 100; ?>
                    <a href="<?= esc($fila['url'], 'attr') ?>" class="pv-fila es-fila">
                        <span class="pos"><?= $i + 1 ?></span>
                        <div class="pv-cuerpo">
                            <span class="pv-nom"><?= esc($fila['nombre']) ?></span>
                            <span class="es-barra" title="<?= $consumo ?>% de lo que entró"><i class="e-bad" style="width: <?= $consumo ?>%"></i></span>
                            <div class="pv-sub">
                                <span>de <?= $plural($fila['entradas'], 'entrada', 'entradas') ?> (<?= $fila['entradas'] > 0 ? $consumo . '%' : '—' ?>)</span>
                                <span class="fv-estado <?= $fila['stock'] <= 0 ? 'e-bad' : 'e-neu' ?>"><?= $fila['stock'] <= 0 ? 'Sin stock' : 'Quedan ' . $fila['stock'] ?></span>
                                <?php if ($fila['ultima']): ?><span>Última baja el <?= $fecha($fila['ultima']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <span class="pv-n"><?= $fila['gastadas'] ?><small>gast.</small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="fv-panel pv-estrecha es-pane" id="impresas" hidden>
        <p class="es-ayuda">Copias mandadas a la impresora <?= $textoPeriodo ?>, sumando la cantidad de cada versión en placas marcadas como impresas (fallidas incluidas). Top 20.</p>
        <?php if (empty($topImpresas)): ?>
            <p class="fv-vacio">No hay placas impresas <?= $textoPeriodo ?>.</p>
        <?php else: ?>
            <?php $maxUnidades = max(array_column($topImpresas, 'unidades')); ?>
            <div class="pv-lista">
                <?php foreach ($topImpresas as $i => $fila): ?>
                    <a href="<?= esc($fila['url'], 'attr') ?>" class="pv-fila es-fila">
                        <span class="pos"><?= $i + 1 ?></span>
                        <div class="pv-cuerpo">
                            <span class="pv-nom"><?= esc($fila['nombre']) ?></span>
                            <span class="es-barra"><i class="e-ok" style="width: <?= (int) round($fila['unidades'] / $maxUnidades * 100) ?>%"></i></span>
                            <div class="pv-sub">
                                <span>En <?= $plural($fila['placas'], 'placa', 'placas') ?></span>
                                <?php if ($fila['ultima']): ?><span>Última el <?= $fecha($fila['ultima']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <span class="pv-n"><?= $fila['unidades'] ?><small>ud.</small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="fv-panel pv-estrecha es-pane" id="disco" hidden>
        <div class="fv-ph">
            <p class="es-ayuda">Lo que ocupa <code>writable/piezas</code> en disco (siempre total, no depende del periodo).</p>
            <div class="pv-chips der" id="esPeso">
                <button type="button" class="pv-chip" data-peso-btn="variante" aria-pressed="true">Por variante</button>
                <button type="button" class="pv-chip" data-peso-btn="pieza" aria-pressed="false">Por pieza</button>
            </div>
        </div>
        <?php if ($totalPapelera > 0): ?>
            <div class="fv-aviso"><i class="bi bi-trash"></i><span><b><?= $tamanoLegible($totalPapelera) ?></b> en la papelera. Se liberan con <code>php spark piezas:purgar</code> en el servidor.</span></div>
        <?php endif; ?>
        <?php foreach ($rankingsPeso as $clave => $seccion): ?>
            <div data-peso="<?= $clave ?>" <?= $clave === 'variante' ? '' : 'hidden' ?> class="es-peso">
                <p class="es-ayuda"><?= $seccion['ayuda'] ?> Top 20.</p>
                <?php if (empty($seccion['filas'])): ?>
                    <p class="fv-vacio">Todavía no hay ningún fichero que pese algo.</p>
                <?php else: ?>
                    <?php $maxBytes = max(array_column($seccion['filas'], 'bytes')); ?>
                    <div class="pv-lista">
                        <?php foreach ($seccion['filas'] as $i => $fila): ?>
                            <a href="<?= esc($fila['url'], 'attr') ?>" class="pv-fila es-fila">
                                <span class="pos"><?= $i + 1 ?></span>
                                <div class="pv-cuerpo">
                                    <span class="pv-nom"><?= esc($fila['nombre']) ?></span>
                                    <span class="es-barra"><i class="e-info" style="width: <?= $maxBytes > 0 ? (int) round($fila['bytes'] / $maxBytes * 100) : 0 ?>%"></i></span>
                                </div>
                                <span class="es-mb"><?= $tamanoLegible($fila['bytes']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<script>
(function () {
    var botones = document.querySelectorAll('#esSecs [data-sec]');
    var anclarPeriodos = function (hash) {
        document.querySelectorAll('.estad-periodo').forEach(function (a) { a.href = a.href.split('#')[0] + hash; });
    };
    var mostrar = function (id, guardar) {
        if (!document.getElementById(id)) return;
        botones.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.sec === id); });
        document.querySelectorAll('.es-pane').forEach(function (p) { p.hidden = p.id !== id; });
        if (guardar) history.replaceState(null, '', '#' + id);
        anclarPeriodos('#' + id);
    };
    botones.forEach(function (b) { b.addEventListener('click', function () { mostrar(b.dataset.sec, true); }); });
    document.querySelectorAll('[data-estad-ir]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            mostrar(a.dataset.estadIr, true);
            document.getElementById('esSecs').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    if (location.hash) mostrar(location.hash.slice(1), false);

    document.querySelectorAll('[data-peso-btn]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-peso-btn]').forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
            document.querySelectorAll('[data-peso]').forEach(function (el) { el.hidden = el.dataset.peso !== b.dataset.pesoBtn; });
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
        var el = document.getElementById('tendencia-' + id);
        document.getElementById('total-' + id).textContent = suma(serie).toLocaleString('es-ES');
        var mitad = Math.floor(serie.length / 2);
        if (mitad < 1) { el.remove(); return; }
        var antes = suma(serie.slice(0, mitad)) / mitad;
        var ahora = suma(serie.slice(serie.length - mitad)) / mitad;
        var pct = antes > 0 ? Math.round((ahora - antes) / antes * 100) : (ahora > 0 ? 100 : 0);
        var estable = Math.abs(pct) < 10;
        el.className = 'der fv-estado ' + (estable ? 'e-neu' : pct > 0 ? 'e-ok' : 'e-warn');
        el.textContent = estable ? 'Se mantiene' : (pct > 0 ? '+' : '') + pct + '% frente a la primera mitad';
        el.title = 'Media de la segunda mitad del periodo frente a la primera';
    };

    Chart.defaults.color = 'rgba(255,255,255,.45)';
    Chart.defaults.borderColor = 'rgba(255,255,255,.06)';
    Chart.defaults.font.family = '"Geist", ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.font.size = 11;

    var opciones = function (apilado) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', align: 'start', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, padding: 14 } },
                tooltip: { padding: 10, cornerRadius: 8, backgroundColor: 'rgba(20,22,25,.95)', borderColor: 'rgba(255,255,255,.12)', borderWidth: 1 }
            },
            scales: {
                x: { stacked: apilado, grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkip: true, autoSkipPadding: 12 } },
                y: { stacked: apilado, beginAtZero: true, border: { display: false }, ticks: { precision: 0 } }
            }
        };
    };

    var lineaTendencia = function (serie) {
        return {
            type: 'line', label: 'Tendencia (media ' + ventana + ')', data: media(serie), stack: 'tendencia',
            borderColor: 'rgba(255,255,255,.75)', backgroundColor: 'rgba(255,255,255,.75)', borderWidth: 2, borderDash: [4, 4], tension: .4,
            pointRadius: 0, pointHoverRadius: 4, order: 0
        };
    };
    var barra = { borderRadius: 4, maxBarThickness: 26, order: 1 };

    tendencia('placas', ev.placas);
    new Chart(document.getElementById('grafico-placas'), {
        type: 'bar',
        data: { labels: ev.etiquetas, datasets: [
            Object.assign({ label: 'Placas', data: ev.placas, backgroundColor: 'rgba(142,140,255,.75)', hoverBackgroundColor: '#8e8cff' }, barra),
            lineaTendencia(ev.placas)
        ] },
        options: opciones(false)
    });

    tendencia('piezas', piezas);
    new Chart(document.getElementById('grafico-piezas'), {
        type: 'bar',
        data: { labels: ev.etiquetas, datasets: [
            Object.assign({ label: 'Buenas', data: ev.buenas, stack: 'p', backgroundColor: 'rgba(62,207,142,.75)', hoverBackgroundColor: '#3ecf8e' }, barra),
            Object.assign({ label: 'Fallidas', data: ev.fallidas, stack: 'p', backgroundColor: 'rgba(240,85,63,.75)', hoverBackgroundColor: '#f0553f' }, barra),
            lineaTendencia(piezas)
        ] },
        options: opciones(true)
    });
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
