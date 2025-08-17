<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
// --- MAPEO fecha => id para marcar en calendarios ---
$mapFechaId = [];
$mapFechaNota = [];
foreach ($entrenamientos as $e) {
    $mapFechaId[$e['fecha']] = $e['id'];
    if (!empty($e['tipo_sesion'])) {
        $nota = $e['tipo_sesion'];
        /*if (mb_strlen($e['notas_generales'], 'UTF-8') > 5) {
            $nota .= '…';
        }*/
        $mapFechaNota[$e['fecha']] = $nota;
    } else {
        $mapFechaNota[$e['fecha']] = '';
    }
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

function renderCalendario($dt, $mapFechaId, $mapFechaNota, $etiqueta = '')
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
    echo '<div class="card-body p-2"><table class="table table-bordered table-sm text-center mb-0 align-middle calendar-table">';
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

        echo '<div class="cal-cell">';

        if ($tiene) {
            $id  = (int)$mapFechaId[$fecha];
            $url = site_url('gimnasio/entrenamientos/registro/' . $id);
            $notaBreve = $mapFechaNota[$fecha] ?? '';

            echo '<div class="cal-actions">';
            echo    '<a href="' . $url . '" class="btn btn-sm btn-success">' . $dia . '</a>';
            echo    '<button type="button" class="btn btn-sm btn-outline-secondary btn-preview-entrenamiento" data-id="' . $id . '" title="Ver resumen">👁️</button>';
            echo '</div>';

            if ($notaBreve !== '') {
                echo '<div class="cal-note">' . esc($notaBreve) . '</div>';
            }
        } else {
            // MISMA ESTRUCTURA con espaciadores invisibles para mantener alturas
            echo '<div class="cal-actions">';
            echo   '<button class="btn btn-sm cal-spacer">' . $dia . '</button>';
            echo   '<button class="btn btn-sm cal-spacer">.</button>';
            echo '</div>';
            echo '<span class="text-muted">' . $dia . '</span>';
        }

        echo '</div>'; // cal-cell
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
<div class="">
    <a href="<?= site_url('gimnasio/') ?>" class="btn btn-outline-secondary">← Volver al gimnasio</a>
</div>

<div class="row justify-content-center mb-4">
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
<div class="row justify-content-center g-4">
    <div class="col-md-6">
        <?php renderCalendario($actual, $mapFechaId, $mapFechaNota, 'Mes actual'); ?>
    </div>
</div>

<!-- Toggle mes anterior -->
<div class="row justify-content-center g-2 mt-3">
    <div class="col-md-6 d-flex">
        <button id="toggleMesAnteriorBtn"
                class="btn btn-outline-secondary btn-sm ms-auto"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#calMesAnterior"
                aria-expanded="false"
                aria-controls="calMesAnterior">
            ▶ Mostrar mes anterior
        </button>
    </div>
</div>

<!-- Calendario mes anterior (oculto por defecto) -->
<div class="row justify-content-center g-4 mt-2">
    <div class="col-md-6">
        <div id="calMesAnterior" class="collapse">
            <?php renderCalendario($anterior, $mapFechaId, $mapFechaNota, 'Mes anterior'); ?>
        </div>
    </div>
</div>



<!-- Lista de entrenamientos en tarjetas -->
<div class="row justify-content-center mt-4">
    <div class="col-md-10">
        <div class="row g-4">
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
                $fBonita = "$dia de $mesNombre de $anio";
                ?>

                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <span class=" mb-2 align-self-start"><?= $human ?></span>
                            <!-- Tipo de sesión -->
                            <?php if (!empty($e['tipo_sesion'])): ?>
                                <span class="badge bg-warning text-dark mb-2 align-self-start">
                                    <?= esc($e['tipo_sesion']) ?>
                                </span>
                            <?php endif; ?>


                            <!-- Fecha -->
                            <h5 class="card-title mb-3">
                                <a href="<?= site_url('gimnasio/entrenamientos/registro/' . $e['id']) ?>" class="text-decoration-none text-dark">
                                    <?= $fBonita ?>
                                </a>

                            </h5>

                            <!-- Notas -->
                            <?php if (!empty($e['notas_generales'])): ?>
                                <p class="card-text text-muted small mb-3">
                                    <?= esc($e['notas_generales']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Botones -->
                            <div class="mt-auto d-flex gap-2">
                                <a href="<?= site_url('gimnasio/entrenamientos/eliminar/' . $e['id']) ?>"
                                    class="btn  btn-sm flex-fill"
                                    onclick="return confirm('¿Seguro que deseas eliminar este entrenamiento?')">
                                    🗑 Eliminar
                                </a>
                                <button type="button"
                                    class="btn btn-outline-secondary btn-sm flex-fill btn-preview-entrenamiento"
                                    data-id="<?= (int)$e['id'] ?>"
                                    title="Ver resumen">
                                    👁️ Ver
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
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
<script>
(function () {
    // Recordar preferencia en localStorage
    const KEY = 'mostrarMesAnterior';
    const btn = document.getElementById('toggleMesAnteriorBtn');
    const collapseEl = document.getElementById('calMesAnterior');

    if (btn && collapseEl) {
        // Si el usuario lo dejó abierto la última vez, ábrelo
        const shouldShow = localStorage.getItem(KEY) === '1';
        if (shouldShow) {
            collapseEl.classList.add('show');
            btn.setAttribute('aria-expanded', 'true');
            btn.textContent = '▼ Ocultar mes anterior';
        }

        collapseEl.addEventListener('shown.bs.collapse', () => {
            btn.textContent = '▼ Ocultar mes anterior';
            localStorage.setItem(KEY, '1');
        });

        collapseEl.addEventListener('hidden.bs.collapse', () => {
            btn.textContent = '▶ Mostrar mes anterior';
            localStorage.setItem(KEY, '0');
        });
    }
})();
</script>


<?= $this->endSection() ?>