<?= $this->extend('comidas/layout') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning">
        <?= session()->getFlashdata('warning') ?>
        <?php if (!empty($dup_fecha) && $dup_id): ?>
            <div class="small mt-1">
                (Fecha existente: <strong><?= esc($dup_fecha) ?></strong>,
                Peso: <strong><?= esc($dup_peso) ?></strong> kg)
                <a class="btn btn-sm btn-outline-danger ms-2"
                    href="<?= site_url('comidas/peso/delete/' . (int)$dup_id) ?>"
                    onclick="return confirm('¿Eliminar el registro existente de esa fecha?')">
                    Eliminar existente
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>


<div class="row g-4">
    <!-- Formulario -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">➕ Registrar peso</div>
            <div class="card-body">
                <form action="<?= site_url('comidas/peso/guardar') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Fecha</label>
                        <?php

                        use CodeIgniter\I18n\Time;

                        $hoyMadrid = $hoy ?? Time::now('Europe/Madrid')->toDateString();
                        ?>
                        <input type="date" name="fecha" value="<?= esc($hoyMadrid) ?>" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" step="0.01" min="0" name="peso" class="form-control" placeholder="Ej: 82.65" required>
                    </div>
                    <button class="btn btn-primary w-100 mt-2">Guardar</button>
                </form>
                <a href="<?= site_url('comidas/peso/importar') ?>" class="btn btn-outline-secondary w-100 mt-2">
                    📤 Importar CSV de báscula
                </a>
            </div>
        </div>
    </div>
    <!-- Gráfico -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">📈 Últimos 60 días</div>
            <div class="card-body chart-body">
                <div class="chart-wrap">
                    <canvas id="pesoChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>


<?php
// Asegura variables disponibles (si no las pasaste desde el controlador)
$altura_cm = $altura_cm ?? 160.5;
$edad      = $edad ?? 40;
$sexo      = $sexo ?? 'm'; // 'm' / 'f'
$altura_m  = $altura_cm ? ($altura_cm / 100) : null;

function fmt($n, $dec = 1)
{
    return number_format((float)$n, $dec, '.', '');
}
?>
<!-- Últimos registros -->
<div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>🗂️ Últimos 60 registros</span>
        <small class="text-muted">Ordenados por fecha desc.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Peso (kg)</th>
                    <th class="text-center">Kcal</th>
                    <th class="d-none d-md-table-cell text-center">% Grasa</th>
                    <th class="d-none d-md-table-cell text-center">% Agua</th>
                    <th class="text-center">Entrenamiento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ultimos)): ?>
                    <?php foreach ($ultimos as $r): ?>
                        <?php
                        $peso = (float)($r['peso'] ?? 0);
                        $bmi  = ($altura_m && $altura_m > 0) ? ($peso / ($altura_m * $altura_m)) : null;

                        $dia  = $r['fecha'];
                        $peso = (float)$r['peso'];
                        $m = $macrosPorDia[$dia] ?? null; // viene del controlador

                        // Deurenberg: %grasa = 1.20*IMC + 0.23*edad − 10.8*sexo − 5.4
                        $bf  = null;
                        if ($bmi !== null && $edad !== null && $sexo !== null) {
                            $sexoFlag = (strtolower($sexo) === 'm') ? 1 : 0;
                            $bf = 1.20 * $bmi + 0.23 * (float)$edad - 10.8 * $sexoFlag - 5.4;
                            $bf = max(3, min(60, $bf));
                        }

                        $dia = $r['fecha']; // YYYY-MM-DD
                        // Buscar entrenos de ese día (si la tabla de entrenos tiene más de uno)
                        $entrenosDia = $mapEntrenos[$dia] ?? null;
                        $tiposDia = [];
                        if (!empty($entrenosGlobal[$dia])) { // <- si en el controlador mandas array con entrenos agrupados por fecha
                            $tiposDia = array_map(fn($e) => $e['tipo_sesion'], $entrenosGlobal[$dia]);
                        }
                        ?>
                        <tr data-id="<?= (int)$r['id'] ?>">
                            <td class="peso-cell" role="button"><?= date('d/m/Y', strtotime($dia)) ?></td>
                            <td class="peso-cell text-center" role="button"><?= esc(number_format((float)$r['peso'], 2, '.', '')) ?></td>
                            <td class="text-center"><?= $m ? (int)$m['kcal'] : '' ?></td>
                            <td class="d-none d-md-table-cell text-center"><?= $r['grasa_corporal_pct'] !== null ? number_format((float)$r['grasa_corporal_pct'], 1, ',', '') . '%' : '' ?></td>
                            <td class="d-none d-md-table-cell text-center"><?= $r['agua_corporal_pct'] !== null ? number_format((float)$r['agua_corporal_pct'], 1, ',', '') . '%' : '' ?></td>
                            <td class="text-center">
                                <?php
                                $tipos = $entrenosTiposPorDia[$dia] ?? [];
                                foreach ($tipos as $tipo):
                                ?>
                                    <span class="badge rounded-pill text-bg-success me-1">💪 <?= esc($tipo) ?></span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted p-3">Sin registros todavía.</td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<!-- Modal detalle Tanita -->
<div class="modal fade" id="modalDetallePeso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallePesoTitulo">Detalle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 text-center mb-2">
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-body-secondary">
                            <div class="fs-5 fw-semibold" id="dpPeso">—</div>
                            <div class="small text-muted">⚖️ kg</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-body-secondary">
                            <div class="fs-5 fw-semibold" id="dpImc">—</div>
                            <div class="small text-muted">📐 IMC</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-body-secondary">
                            <div class="fs-5 fw-semibold" id="dpGrasa">—</div>
                            <div class="small text-muted">🩸 % grasa</div>
                        </div>
                    </div>
                </div>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th>Grasa visceral</th><td class="text-end" id="dpViscFat">—</td></tr>
                        <tr><th>Masa muscular</th><td class="text-end" id="dpMasaMuscular">—</td></tr>
                        <tr><th>Masa ósea</th><td class="text-end" id="dpMasaOsea">—</td></tr>
                        <tr><th>Metabolismo basal</th><td class="text-end" id="dpBmr">—</td></tr>
                        <tr><th>Edad metabólica</th><td class="text-end" id="dpEdadMetab">—</td></tr>
                        <tr><th>Agua corporal</th><td class="text-end" id="dpAgua">—</td></tr>
                        <tr><th>Valoración física</th><td class="text-end" id="dpFisica">—</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <a id="dpEliminar" href="#" class="btn btn-outline-danger"
                    onclick="return confirm('¿Eliminar este registro?')">
                    <i class="bi bi-trash"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* El card se expande y no recorta tooltips/leyendas */
    .chart-body {
        overflow: visible;
    }

    /* Contenedor responsivo: alto cómodo en móvil y escritorio */
    .chart-wrap {
        position: relative;
        height: clamp(260px, 40vh, 420px);
        /* min 260px, ideal 40vh, máx 420px */
        width: 100%;
    }

    /* Por si algún estilo externo fuerza altura del canvas */
    #pesoChart {
        width: 100% !important;
        height: 100% !important;
    }
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (() => {
        const labels = <?= json_encode($labels ?? []) ?>;
        const values = <?= json_encode($values ?? []) ?>;
        // <- nuevo: flags (true si hubo entreno ese día)
        const flags = <?= json_encode($flagsEntreno ?? []) ?>;

        // === NUEVO DATASET: puntos de entreno ===
        // Colocamos un punto sólo donde flags[i] es true (mismo valor Y que 'values')
        const trainingPoints = (values || []).map((v, i) => (flags && flags[i]) ? v : null);

        // Helpers (igual que tenías) ...
        const movingAverage = (arr, window = 7) => {
            if (!arr || arr.length === 0) return [];
            const out = new Array(arr.length).fill(null);
            let sum = 0,
                q = [];
            for (let i = 0; i < arr.length; i++) {
                const v = Number(arr[i]);
                if (!Number.isFinite(v)) {
                    q.length = 0;
                    sum = 0;
                    continue;
                }
                q.push(v);
                sum += v;
                if (q.length > window) sum -= q.shift();
                if (q.length === window) out[i] = +(sum / window).toFixed(2);
            }
            return out;
        };
        const median = (arr) => {
            const nums = arr.filter(Number.isFinite).slice().sort((a, b) => a - b);
            if (!nums.length) return null;
            const mid = Math.floor(nums.length / 2);
            return nums.length % 2 ? nums[mid] : (nums[mid - 1] + nums[mid]) / 2;
        };

        const sma7 = movingAverage(values, 7);
        const medVal = median(values);
        const medLine = medVal !== null ? values.map(() => +medVal.toFixed(2)) : [];

        const vmin = values.length ? Math.min(...values) : 0;
        const vmax = values.length ? Math.max(...values) : 1;
        const span = Math.max(1e-6, vmax - vmin);
        const pad = Math.max(0.5, span * 0.05);

        const ctx = document.getElementById('pesoChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                        label: 'Peso (kg)',
                        data: values,
                        borderWidth: 2,
                        tension: 0.2,
                        pointRadius: 2,
                        fill: true,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.12)',
                        order: 3
                    },
                    {
                        label: 'Tendencia (SMA 7)',
                        data: sma7,
                        borderWidth: 3,
                        tension: 0.25,
                        pointRadius: 0,
                        spanGaps: true,
                        fill: false,
                        borderColor: '#198754',
                        order: 2
                    },
                    ...(medVal !== null ? [{
                        label: `Mediana (${medVal.toFixed(2)} kg)`,
                        data: medLine,
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                        borderDash: [6, 6],
                        borderColor: '#6c757d',
                        order: 1
                    }] : []),
                    // === AQUÍ VA EL PUNTO AMARILLO DE ENTRENAMIENTO ===
                    {
                        label: 'Entrenamiento',
                        data: trainingPoints,
                        type: 'line', // seguimos en línea pero sin trazarla
                        showLine: false, // sólo puntos
                        pointRadius: 6, // tamaño del punto
                        pointHoverRadius: 8,
                        pointStyle: 'circle',
                        borderWidth: 2,
                        // amarillo Bootstrap
                        pointBackgroundColor: '#ffc107',
                        pointBorderColor: '#ffc107',
                        order: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                layout: {
                    padding: {
                        top: 8,
                        bottom: 8,
                        left: 4,
                        right: 4
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: vmin - pad,
                        max: vmax + pad,
                        ticks: {
                            padding: 6
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        intersect: false,
                        mode: 'index',
                        // Oculta el tooltip de "Entrenamiento" cuando sea null
                        filter: (item) => item.raw !== null
                    }
                }
            }
        });
    })();
</script>

<style>
    .peso-cell {
        cursor: pointer;
    }
    .peso-cell:hover {
        text-decoration: underline;
    }
</style>

<?= $this->endSection() ?>

<?php /* Sección aparte: se renderiza DESPUÉS del bundle de Bootstrap JS en el layout,
         por eso bootstrap.Modal solo está disponible aquí, no dentro de 'content'. */ ?>
<?= $this->section('scripts') ?>
<script>
    (() => {
        const DATOS_PESO = <?= json_encode(array_column(array_map(static function ($r) {
            return [
                'id'                     => (int) $r['id'],
                'fecha'                  => $r['fecha'],
                'peso'                   => $r['peso'],
                'imc'                    => $r['imc'] ?? null,
                'grasa_corporal_pct'     => $r['grasa_corporal_pct'] ?? null,
                'grasa_visceral'         => $r['grasa_visceral'] ?? null,
                'masa_muscular_kg'       => $r['masa_muscular_kg'] ?? null,
                'masa_osea_kg'           => $r['masa_osea_kg'] ?? null,
                'metabolismo_basal_kcal' => $r['metabolismo_basal_kcal'] ?? null,
                'edad_metabolica'        => $r['edad_metabolica'] ?? null,
                'agua_corporal_pct'      => $r['agua_corporal_pct'] ?? null,
                'valoracion_fisica'      => $r['valoracion_fisica'] ?? null,
            ];
        }, $ultimos ?? []), null, 'id')) ?>;

        const modalEl = document.getElementById('modalDetallePeso');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);
        const titulo = document.getElementById('modalDetallePesoTitulo');
        const btnEliminar = document.getElementById('dpEliminar');
        const urlEliminarBase = '<?= site_url('comidas/peso/eliminar') ?>/';

        const val = (v, suf = '') => (v === null || v === undefined || v === '') ? '—' : `${v}${suf}`;

        document.querySelectorAll('td.peso-cell').forEach(td => {
            td.addEventListener('click', () => {
                const tr = td.closest('tr[data-id]');
                const d = tr ? DATOS_PESO[tr.dataset.id] : null;
                if (!d) return;

                btnEliminar.href = urlEliminarBase + d.id;
                titulo.textContent = 'Detalle · ' + new Date(d.fecha + 'T00:00:00').toLocaleDateString('es-ES');
                document.getElementById('dpPeso').textContent = val(d.peso);
                document.getElementById('dpImc').textContent = val(d.imc);
                document.getElementById('dpGrasa').textContent = val(d.grasa_corporal_pct, '%');
                document.getElementById('dpViscFat').textContent = val(d.grasa_visceral);
                document.getElementById('dpMasaMuscular').textContent = val(d.masa_muscular_kg, ' kg');
                document.getElementById('dpMasaOsea').textContent = val(d.masa_osea_kg, ' kg');
                document.getElementById('dpBmr').textContent = val(d.metabolismo_basal_kcal, ' kcal');
                document.getElementById('dpEdadMetab').textContent = val(d.edad_metabolica, ' años');
                document.getElementById('dpAgua').textContent = val(d.agua_corporal_pct, '%');
                document.getElementById('dpFisica').textContent = val(d.valoracion_fisica);

                modal.show();
            });
        });
    })();
</script>
<?= $this->endSection() ?>