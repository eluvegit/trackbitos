<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
// --- MAPEO fecha => id para marcar en calendarios ---
$mapFechaId = [];
foreach ($entrenamientos as $e) {
    $mapFechaId[$e['fecha']] = $e['id'];
}

// --- Utilidades ---
function humanizarFecha($fechaStr)
{
    $hoy = new DateTime('today');
    $f = new DateTime($fechaStr);
    $diff = (int)$hoy->diff($f)->format('%r%a');
    if ($diff === 0) return 'Hoy';
    if ($diff === -1) return 'Ayer';
    if ($diff === 1) return 'Mañana';
    if ($diff < 0) return 'Hace ' . abs($diff) . ' días';
    return 'En ' . $diff . ' días';
}

function renderCalendario($dt, $mapFechaId, $etiqueta = '')
{
    $anio = (int)$dt->format('Y');
    $mes  = (int)$dt->format('m');
    $primerDiaSemana = (int)date('N', strtotime("$anio-$mes-01")); // 1=Lu ... 7=Do
    $diasMes = (int)date('t', strtotime("$anio-$mes-01"));
    $hoyStr = (new DateTime('today'))->format('Y-m-d');

    // Nombre de mes (fallback sin locale)
    $nombresMes = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $nombreMes = $nombresMes[$mes] . ' ' . $anio;

    $badge = $etiqueta ? '<span class="badge bg-secondary ms-2">' . $etiqueta . '</span>' : '';

    echo '<div class="card mb-3"><div class="card-header fw-semibold">' . $nombreMes . $badge . '</div>';
    echo '<div class="card-body p-2"><table class="table table-bordered table-sm text-center mb-0 align-middle">';
    echo '<thead><tr><th>Lu</th><th>Ma</th><th>Mi</th><th>Ju</th><th>Vi</th><th>Sá</th><th>Do</th></tr></thead><tbody>';

    $dia = 1;
    $col = 1;
    echo '<tr>';
    for ($i = 1; $i < $primerDiaSemana; $i++) {
        echo '<td class="bg-light-subtle"></td>';
        $col++;
    }

    while ($dia <= $diasMes) {
        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        $esHoy = ($fecha === $hoyStr);
        $tiene = isset($mapFechaId[$fecha]);
        $clase = $esHoy ? 'table-primary' : '';
        echo '<td class="' . $clase . '" style="min-width:38px;">';
        if ($tiene) {
            $url = site_url('gimnasio/entrenamientos/registro/' . $mapFechaId[$fecha]);
            echo '<div class="d-grid gap-1">';
            echo    '<a href="' . $url . '" class="btn btn-sm btn-success">' . $dia . '</a>';
            echo    '<button type="button" class="btn btn-sm btn-outline-secondary btn-preview-entrenamiento" data-id="' . $mapFechaId[$fecha] . '" title="Ver resumen">👁️</button>';
            echo '</div>';
        } else {
            echo '<span class="text-muted">' . $dia . '</span>';
        }
        echo '</td>';

        if ($col === 7) {
            echo '</tr>';
            if ($dia < $diasMes) echo '<tr>';
            $col = 0;
        }
        $dia++;
        $col++;
    }
    if ($col !== 1) {
        for ($i = $col; $i <= 7; $i++) echo '<td class="bg-light-subtle"></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';
}

// Fechas de referencia para calendarios
$actual = new DateTime('first day of this month');
$anterior = (clone $actual)->modify('-1 month');
?>

<h2 class="">📅 Entrenamientos</h2>
<div class="mb-4">
    <a href="<?= site_url('gimnasio/') ?>" class="btn btn-outline-secondary">← Volver al gimnasio</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-3">
        <form action="<?= site_url('gimnasio/entrenamientos/crear') ?>" method="post" class="mb-3">
            <label for="fecha">Fecha:</label>
            <?php

            use CodeIgniter\I18n\Time;

            $hoyMadrid = Time::now('Europe/Madrid')->toDateString(); // YYYY-MM-DD
            ?>
            <input type="date" name="fecha" value="<?= $hoyMadrid ?>" class="form-control mb-2">

            <button class="btn btn-primary w-100">➕ Crear entrenamiento</button>
        </form>
    </div>
</div>

<!-- Calendarios: mes anterior y actual -->
<div class="row g-4 mt-3">
    <div class="col-md-6">
        <?php renderCalendario($anterior, $mapFechaId, 'Mes anterior'); ?>
    </div>
    <div class="col-md-6">
        <?php renderCalendario($actual, $mapFechaId, 'Mes actual'); ?>
    </div>
</div>

<!-- Lista centrada con fecha humanizada -->
<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <ul class="list-group">
            <?php
            $meses = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];
            ?>
            <?php foreach ($entrenamientos as $e): ?>
                <?php
                $fISO = $e['fecha'];
                $human = humanizarFecha($fISO);

                $fechaObj = new DateTime($fISO);
                $dia = $fechaObj->format('j');
                $mesNombre = $meses[(int)$fechaObj->format('n')];
                $anio = $fechaObj->format('Y');
                $semana = $fechaObj->format('W'); // Número de semana ISO

                $fBonita = "$dia de $mesNombre de $anio (S$semana)";
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <a href="<?= site_url('gimnasio/entrenamientos/registro/' . $e['id']) ?>" class="text-decoration-none text-secondary">
                            <?= $fBonita ?>
                        </a>
                        <span class="badge bg-secondary ms-2"><?= $human ?></span>
                    </div>
                    
                    <div class="btn-group btn-group-sm">
                        <button type="button"
                        class="btn btn-preview-entrenamiento"
                        data-id="<?= (int)$e['id'] ?>"
                        title="Ver resumen">
                        👁️
                    </button>
                        <a href="<?= site_url('gimnasio/entrenamientos/eliminar/' . $e['id']) ?>"
                            class="btn" title="Eliminar"
                            onclick="return confirm('¿Seguro que deseas eliminar este entrenamiento?')">🗑</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

    </div>
</div>

<!-- Modal Preview Entrenamiento -->
<div class="modal fade" id="modalPreviewEntrenamiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resumen del entrenamiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewBody">
                <div class="text-center py-4 text-muted">Cargando…</div>
            </div>
            <div class="modal-footer">
                <a id="previewGoBtn" href="#" class="btn btn-primary">Abrir entrenamiento</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        function openPreview(id) {
            const modalEl = document.getElementById('modalPreviewEntrenamiento');
            const modal = new bootstrap.Modal(modalEl);
            const body = document.getElementById('previewBody');
            const goBtn = document.getElementById('previewGoBtn');

            body.innerHTML = '<div class="text-center py-4 text-muted">Cargando…</div>';
            goBtn.href = "<?= site_url('gimnasio/entrenamientos/registro/') ?>" + id;

            modal.show();

            fetch("<?= site_url('gimnasio/entrenamientos/resumen/') ?>" + id, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.ok ? r.text() : Promise.reject())
                .then(html => body.innerHTML = html)
                .catch(() => body.innerHTML = '<div class="alert alert-warning m-0">No se pudo cargar el resumen.</div>');
        }

        // Delegación para clicks en lista y calendario
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-preview-entrenamiento');
            if (!btn) return;
            e.preventDefault();
            const id = btn.dataset.id;
            if (id) openPreview(id);
        });
    })();
</script>


<?= $this->endSection() ?>