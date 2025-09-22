<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-3">⚖️ Peso corporal</h2>
<div class="mb-3">
    <a href="<?= site_url('comidas/diario/hoy') ?>" class="btn btn-outline-secondary">← Volver</a>
</div>

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
            </div>
        </div>
    </div>
<!-- Gráfico -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">📈 Últimos 30 días</div>
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
        <span>🗂️ Últimos registros</span>
        <small class="text-muted">Ordenados por fecha desc.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 140px;">Fecha</th>
                    <th style="width: 140px;">Peso (kg)</th>
                    <th style="width: 140px;">IMC</th>
                    <th style="width: 140px;">% grasa</th>
                    <th class="text-end" style=""></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ultimos)): ?>
                    <?php foreach ($ultimos as $r): ?>
                        <?php
                        $peso = (float)($r['peso'] ?? 0);
                        $bmi  = ($altura_m && $altura_m > 0) ? ($peso / ($altura_m * $altura_m)) : null;

                        // Deurenberg: %grasa = 1.20*IMC + 0.23*edad − 10.8*sexo − 5.4
                        // sexoFlag: 1 = hombre, 0 = mujer
                        $bf  = null;
                        if ($bmi !== null && $edad !== null && $sexo !== null) {
                            $sexoFlag = (strtolower($sexo) === 'm') ? 1 : 0;
                            $bf = 1.20 * $bmi + 0.23 * (float)$edad - 10.8 * $sexoFlag - 5.4;
                            // Limita a un rango razonable para evitar salidas raras
                            $bf = max(3, min(60, $bf));
                        }
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                            <td><?= esc(number_format((float)$r['peso'], 2, '.', '')) ?></td>
                            <td>
                                <?= $bmi !== null ? fmt($bmi, 1) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <?= $bf !== null ? fmt($bf, 1) . ' %' : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('comidas/peso/eliminar/' . $r['id']) ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('¿Eliminar registro?')">X</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted p-3">Sin registros todavía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
  /* El card se expande y no recorta tooltips/leyendas */
  .chart-body { overflow: visible; }

  /* Contenedor responsivo: alto cómodo en móvil y escritorio */
  .chart-wrap {
    position: relative;
    height: clamp(260px, 40vh, 420px); /* min 260px, ideal 40vh, máx 420px */
    width: 100%;
  }

  /* Por si algún estilo externo fuerza altura del canvas */
  #pesoChart { width: 100% !important; height: 100% !important; }
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
  const labels = <?= json_encode($labels ?? []) ?>;
  const values = <?= json_encode($values ?? []) ?>;

  // Helpers
  const movingAverage = (arr, window = 7) => {
    if (!arr || arr.length === 0) return [];
    const out = new Array(arr.length).fill(null);
    let sum = 0, q = [];
    for (let i = 0; i < arr.length; i++) {
      const v = Number(arr[i]);
      if (!Number.isFinite(v)) { q.length = 0; sum = 0; continue; }
      q.push(v); sum += v;
      if (q.length > window) sum -= q.shift();
      if (q.length === window) out[i] = +(sum / window).toFixed(2);
    }
    return out;
  };

  const median = (arr) => {
    const nums = arr.filter(Number.isFinite).slice().sort((a,b)=>a-b);
    if (!nums.length) return null;
    const mid = Math.floor(nums.length/2);
    return nums.length % 2 ? nums[mid] : (nums[mid-1] + nums[mid]) / 2;
  };

  // Datos derivados
  const sma7   = movingAverage(values, 7);
  const medVal = median(values);
  const medLine = medVal !== null ? values.map(()=>+medVal.toFixed(2)) : [];

  // Padding dinámico eje Y
  const vmin = values.length ? Math.min(...values) : 0;
  const vmax = values.length ? Math.max(...values) : 1;
  const span = Math.max(1e-6, vmax - vmin);
  const pad  = Math.max(0.5, span * 0.05);

  const ctx = document.getElementById('pesoChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Peso (kg)',
          data: values,
          borderWidth: 2,
          tension: 0.2,
          pointRadius: 2,
          fill: true,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,0.12)'
        },
        {
          label: 'Tendencia (SMA 7)',
          data: sma7,
          borderWidth: 3,
          tension: 0.25,
          pointRadius: 0,
          spanGaps: true,
          fill: false,
          borderColor: '#198754' // verde Bootstrap
        },
        ...(medVal !== null ? [{
          label: `Mediana (${medVal.toFixed(2)} kg)`,
          data: medLine,
          borderWidth: 2,
          pointRadius: 0,
          fill: false,
          borderDash: [6,6],
          borderColor: '#6c757d' // gris
        }] : [])
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      layout: { padding: { top: 8, bottom: 8, left: 4, right: 4 } },
      scales: {
        y: {
          beginAtZero: false,
          min: vmin - pad,
          max: vmax + pad,
          ticks: { padding: 6 }
        },
        x: {
          ticks: { maxRotation: 0, minRotation: 0 },
          grid: { display: false }
        }
      },
      plugins: {
        legend: { display: true },
        tooltip: { intersect: false, mode: 'index' }
      }
    }
  });
})();

</script>

<?= $this->endSection() ?>