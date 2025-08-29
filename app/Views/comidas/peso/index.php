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
            <div class="card-body">
                <canvas id="pesoChart" height="110"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Últimos registros -->
<div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>🗂️ Últimos registros (30)</span>
        <small class="text-muted">Ordenados por fecha desc.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
            <tr>
                <th style="width: 140px;">Fecha</th>
                <th style="width: 140px;">Peso (kg)</th>
                <th>IMC (opcional)</th>
                <th class="text-end" style="width: 120px;">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($ultimos)): ?>
                <?php foreach ($ultimos as $r): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                        <td><?= esc(number_format((float)$r['peso'], 2, '.', '')) ?></td>
                        <td class="text-muted">—</td>
                        <td class="text-end">
                            <a href="<?= site_url('comidas/peso/eliminar/' . $r['id']) ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar registro?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-center text-muted p-3">Sin registros todavía.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const labels = <?= json_encode($labels ?? []) ?>;
    const values = <?= json_encode($values ?? []) ?>;

    const ctx = document.getElementById('pesoChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Peso (kg)',
                data: values,
                borderWidth: 2,
                fill: false,
                tension: 0.2
            }]
        },
        options: {
            animation: false,
            scales: {
                y: {
                    beginAtZero: false
                }
            },
            plugins: {
                legend: { display: true }
            }
        }
    });
})();
</script>

<?= $this->endSection() ?>
