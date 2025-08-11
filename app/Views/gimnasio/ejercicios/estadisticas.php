<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">📈 Estadísticas de <?= esc($ejercicio['nombre']) ?></h2>
<div class="mb-3">
    <a href="<?= site_url('gimnasio/ejercicios') ?>" class="btn btn-outline-secondary">← Volver a ejercicios</a>
</div>

<?php if (!empty($seriesAgrupadas)): ?>
    <h4>Resumen por fecha</h4>
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr>
                <th>Fecha</th>
                <th>Total series</th>
                <th>Total repeticiones</th>
                <th>Volumen (kg)</th>
                <th>Registros</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($seriesAgrupadas as $fila): ?>
                <?php
                $vol = isset($fila['total_volumen']) ? (float)$fila['total_volumen'] : 0;
                $fmtVol = $vol > 0 ? rtrim(rtrim((string)$vol, '0'), '.') : '—';
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                    <td><?= (int)$fila['total_series'] ?></td>
                    <td><?= (int)$fila['total_reps'] ?></td>
                    <td><?= $fmtVol ?></td>
                    <td><?= (int)$fila['registros'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>


    <h4 class="mt-4">Detalle de series</h4>
    <table class="table table-sm table-striped">
        <thead class="table-light">
            <tr>
                <th>Fecha</th>
                <th>Series</th>
                <th>Reps</th>
                <th>Peso</th>
                <th>Volumen (kg)</th>
                <th>RPE</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($seriesDetalle as $fila): ?>
                <?php
                $series = (int)$fila['series'];
                $reps   = (int)$fila['repeticiones'];
                $peso   = (float)($fila['peso'] ?? 0);
                $vol    = $peso > 0 ? $series * $reps * $peso : null;
                $fmtPeso = $peso > 0 ? rtrim(rtrim((string)$peso, '0'), '.') . ' kg' : '—';
                $fmtVol  = $vol !== null ? rtrim(rtrim((string)$vol, '0'), '.') : '—';
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                    <td><?= $series ?></td>
                    <td><?= $reps ?></td>
                    <td><?= $fmtPeso ?></td>
                    <td><?= $fmtVol ?></td>
                    <td><?= $fila['rpe'] !== null && $fila['rpe'] !== '' ? esc($fila['rpe']) : '—' ?></td>
                    <td><?= !empty($fila['nota']) ? esc($fila['nota']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="alert alert-light border">No hay registros para este ejercicio.</div>
<?php endif; ?>

<?= $this->endSection() ?>