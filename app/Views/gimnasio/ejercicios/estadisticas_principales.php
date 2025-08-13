<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
function tituloBonito($clave) {
    return [
        'press banca' => 'Press banca',
        'peso muerto' => 'Peso muerto',
        'sentadillas' => 'Sentadillas',
    ][$clave] ?? ucfirst($clave);
}
?>

<h2 class="mb-4">📈 Estadísticas — Principales (Press banca, Peso muerto, Sentadillas)</h2>
<div class="mb-3">
    <a href="<?= site_url('gimnasio') ?>" class="btn btn-outline-secondary">← Volver a gimnasio</a>
</div>

<?php foreach (['press banca','peso muerto','sentadillas'] as $clave): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><?= tituloBonito($clave) ?></strong>
            <?php if (!empty($ejercicios[$clave])): ?>
                <a href="<?= site_url('gimnasio/ejercicios/estadisticas/'.$ejercicios[$clave]['id']) ?>"
                   class="btn btn-sm">📄 Vista completa</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($ejercicios[$clave])): ?>
                <div class="alert alert-light border mb-0">
                    No se ha encontrado el ejercicio “<?= tituloBonito($clave) ?>” en tu base de datos.
                </div>
            <?php else: ?>
                <?php
                    $resumen = $bloques[$clave]['resumen'] ?? [];
                    $detalle = $bloques[$clave]['detalle'] ?? [];
                ?>

                <?php if (!empty($resumen)): ?>
                    <h5 class="mb-2">Resumen por fecha</h5>
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
                            <?php foreach ($resumen as $r): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                                    <td><?= (int)$r['total_series'] ?></td>
                                    <td><?= (int)$r['total_reps'] ?></td>
                                    <td><?= $r['total_volumen'] ?? 0 ?></td>
                                    <td><?= (int)$r['registros'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h5 class="mt-4 mb-2">Detalle de series</h5>
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
                            <?php foreach ($detalle as $d): ?>
                                <?php
                                    $series = (int)$d['series'];
                                    $reps   = (int)$d['repeticiones'];
                                    $peso   = $d['peso'] ?? 0;
                                    $vol    = $peso > 0 ? $series * $reps * $peso : null;
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
                                    <td><?= $series ?></td>
                                    <td><?= $reps ?></td>
                                    <td><?= $peso > 0 ? $peso . ' kg' : '—' ?></td>
                                    <td><?= $vol !== null ? $vol : '—' ?></td>
                                    <td><?= ($d['rpe'] !== null && $d['rpe'] !== '') ? esc($d['rpe']) : '—' ?></td>
                                    <td><?= !empty($d['nota']) ? esc($d['nota']) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-light border mb-0">
                        No hay registros para “<?= tituloBonito($clave) ?>”.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
