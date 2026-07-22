<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
.gym-ent-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.gym-ent-actions { display: flex; align-items: center; gap: 8px; }
.gym-ent-nuevo { display: flex; gap: 8px; max-width: 320px; flex: 1 1 auto; }
.gym-ent-nuevo input { flex: 1 1 auto; }

.gym-ent-back-icon {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    text-decoration: none;
    font-size: 1.1rem;
}
.gym-ent-back-icon:hover { background: var(--bs-body-bg); }

.gym-cal-toggle {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .85rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-size: .8rem;
}
.gym-cal-toggle:hover { filter: brightness(1.15); }

/* Reestilo del calendario (generado en PHP con clases de Bootstrap) */
.gym-cal .card {
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    border-radius: 14px;
}
.gym-cal .card-header {
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
    color: var(--bs-emphasis-color);
}
.gym-cal .calendar-table th {
    color: var(--bs-secondary-color);
    font-weight: 600;
    font-size: .72rem;
    border-color: var(--bs-border-color);
    background: transparent;
}
.gym-cal .calendar-table td {
    border-color: var(--bs-border-color);
    background: transparent;
}
.gym-cal .bg-light-subtle { background: transparent !important; }
.gym-cal .btn-warning {
    background: #7c3aed !important;
    border-color: #7c3aed !important;
    color: #fff !important;
}
.gym-cal .table-primary { background: rgba(124, 58, 237, .12) !important; }
.gym-cal .cal-note { font-size: .62rem; color: var(--bs-secondary-color); }
.gym-cal .badge.bg-secondary { background: var(--bs-border-color) !important; color: var(--bs-secondary-color) !important; }

/* "Los 3 grandes" en el modal de resumen del entrenamiento */
.rs-grandes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.rs-grande {
    display: flex;
    flex-direction: column;
    gap: 1px;
    padding: 8px 10px;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    text-align: center;
}
.rs-grande.is-hecho { border-color: rgba(124, 58, 237, .4); background: rgba(124, 58, 237, .08); }
.rs-grande-titulo { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); font-weight: 700; }
.rs-grande-valor { font-size: 1rem; font-weight: 700; color: var(--bs-emphasis-color); }
.rs-grande-valor.rs-grande-vacio { color: var(--bs-secondary-color); }
.rs-grande-sub { font-size: .68rem; color: var(--bs-secondary-color); }

@media (max-width: 420px) {
    .rs-grandes { gap: 6px; }
    .rs-grande-titulo { font-size: .6rem; }
    .rs-grande-valor { font-size: .85rem; }
}
</style>
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
            echo    '<a href="' . $url . '" class="btn btn-sm text-dark btn-warning">' . $dia . '</a>';
            //echo    '<button type="button" class="btn btn-sm btn-outline-secondary btn-preview-entrenamiento" data-id="' . $id . '" title="Ver resumen">👁️</button>';
            echo '</div>';

            if ($notaBreve !== '') {
                echo '<div class="cal-note">' . esc(mb_substr((string)$notaBreve, 0, 6, 'UTF-8')) . '</div>';

            }
        } else {
            echo '<button type="button" class="btn btn-sm text-muted cal-dia-vacio" data-fecha="' . $fecha . '" title="Usar esta fecha para crear un entrenamiento">' . $dia . '</button>';
            // MISMA ESTRUCTURA con espaciadores invisibles para mantener alturas
            echo '<div class="cal-actions">';
            //echo   '<button class="btn btn-sm cal-spacer">' . $dia . '</button>';
            //echo   '<button class="btn btn-sm cal-spacer">.</button>';
            echo '</div>';
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

<div class="gym-ent-header mb-3">
    <h2 class="gym-ent-title mb-0"><i class="bi bi-calendar2-week text-primary"></i> Entrenamientos</h2>
</div>

<!-- Nuevo entrenamiento + volver (juntos, a mano para el pulgar derecho en móvil) -->
<div class="gym-ent-actions mb-3">
    <form action="<?= site_url('gimnasio/entrenamientos/crear') ?>" method="post" class="gym-ent-nuevo" id="formNuevoEntrenamiento">
        <?php
        use CodeIgniter\I18n\Time;
        $hoyMadrid = Time::now('Europe/Madrid')->toDateString(); // YYYY-MM-DD
        ?>
        <input type="date" name="fecha" id="inputFechaNuevo" value="<?= $hoyMadrid ?>" class="form-control form-control-sm">
        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-plus-lg"></i></button>
    </form>
    <a href="<?= site_url('gimnasio') ?>" class="gym-ent-back-icon" title="Volver a Gimnasio">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<!-- Calendario del mes actual -->
<div class="gym-cal mb-2">
    <?php renderCalendario($actual, $mapFechaId, $mapFechaNota, 'Mes actual'); ?>
</div>

<div class="text-center mb-2">
    <button id="toggleMesAnteriorBtn"
        class="gym-cal-toggle"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#calMesAnterior"
        aria-expanded="false"
        aria-controls="calMesAnterior">
        <i class="bi bi-chevron-down"></i> Mostrar mes anterior
    </button>
</div>

<!-- Calendario mes anterior (oculto por defecto) -->
<div class="gym-cal collapse" id="calMesAnterior">
    <?php renderCalendario($anterior, $mapFechaId, $mapFechaNota, 'Mes anterior'); ?>
</div>



<!-- Listado de entrenamientos -->
<?php
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$porAnio = [];
foreach ($entrenamientos as $e) {
    $anio = date('Y', strtotime($e['fecha']));
    $porAnio[$anio][] = $e;
}
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <?php if (!empty($entrenamientos)): ?>
            <div class="ent-search mb-2">
                <i class="bi bi-search"></i>
                <input type="text" id="entBuscador" class="form-control" placeholder="Buscar por fecha, tipo de sesión o notas…">
            </div>
            <p class="text-muted small mt-2 d-none" id="entSinResultados">No hay entrenamientos que coincidan con la búsqueda.</p>
        <?php endif; ?>

        <div id="entLista">
            <?php $primero = true; ?>
            <?php foreach ($porAnio as $anio => $lista): ?>
                <div class="ent-group">
                    <div class="ent-group-header" data-bs-toggle="collapse" data-bs-target="#entAnio<?= $anio ?>"
                         aria-expanded="<?= $primero ? 'true' : 'false' ?>" aria-controls="entAnio<?= $anio ?>">
                        <i class="bi bi-chevron-right ent-group-chevron"></i>
                        <span class="ent-group-title"><?= $anio ?></span>
                        <span class="ent-group-count"><?= count($lista) ?></span>
                    </div>

                    <div class="collapse <?= $primero ? 'show' : '' ?>" id="entAnio<?= $anio ?>">
                        <div class="ent-item-list">
                            <?php foreach ($lista as $e): ?>
                                <?php
                                $fISO = $e['fecha'];
                                $human = humanizarFecha($fISO);
                                $fechaObj = new DateTime($fISO);
                                $dia = $fechaObj->format('j');
                                $mesNombre = $meses[(int) $fechaObj->format('n')];
                                $fBonita = "$dia de $mesNombre de $anio";
                                $busqueda = mb_strtolower($fBonita . ' ' . ($e['tipo_sesion'] ?? '') . ' ' . ($e['notas_generales'] ?? ''), 'UTF-8');
                                ?>
                                <div class="ent-item" data-search="<?= esc($busqueda) ?>">
                                    <div class="ent-item-main">
                                        <a href="<?= site_url('gimnasio/entrenamientos/registro/' . $e['id']) ?>" class="ent-item-fecha">
                                            <?= $fBonita ?>
                                        </a>
                                        <span class="ent-item-human"><?= $human ?></span>
                                        <?php if (!empty($e['tipo_sesion'])): ?>
                                            <span class="ent-item-tipo"><?= esc($e['tipo_sesion']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($e['notas_generales'])): ?>
                                        <div class="ent-item-notas"><?= esc(mb_strimwidth($e['notas_generales'], 0, 120, '…')) ?></div>
                                    <?php endif; ?>
                                    <div class="ent-item-actions">
                                        <button type="button" class="ent-icon-btn btn-preview-entrenamiento" data-id="<?= (int) $e['id'] ?>" title="Ver resumen">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="<?= site_url('gimnasio/entrenamientos/eliminar/' . $e['id']) ?>" class="ent-icon-btn ent-icon-btn-danger"
                                           onclick="return confirm('¿Seguro que deseas eliminar este entrenamiento?')" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php $primero = false; ?>
            <?php endforeach; ?>
        </div>

        <?php if (empty($entrenamientos)): ?>
            <p class="text-muted">Todavía no hay entrenamientos registrados.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.ent-search { position: relative; display: flex; align-items: center; }
.ent-search i { position: absolute; left: 12px; color: var(--bs-secondary-color); }
.ent-search input { padding-left: 34px; }

.ent-group {
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    margin-bottom: 8px;
    overflow: hidden;
    background: var(--bs-body-bg);
}
.ent-group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    cursor: pointer;
    background: var(--bs-tertiary-bg);
}
.ent-group-chevron { transition: transform .15s ease; color: var(--bs-secondary-color); }
.ent-group-header[aria-expanded="true"] .ent-group-chevron { transform: rotate(90deg); }
.ent-group-title { font-weight: 700; font-size: .92rem; color: var(--bs-emphasis-color); }
.ent-group-count {
    font-size: .72rem;
    color: var(--bs-secondary-color);
    background: var(--bs-body-bg);
    border-radius: 999px;
    padding: .05rem .5rem;
}

.ent-item-list { display: flex; flex-direction: column; }
.ent-item {
    padding: 8px 12px;
    border-top: 1px solid var(--bs-border-color);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 10px;
}
.ent-item:hover { background: var(--bs-tertiary-bg); }

.ent-item-main { display: flex; align-items: center; gap: 8px; flex: 1 1 auto; min-width: 0; flex-wrap: wrap; }
.ent-item-fecha {
    font-weight: 600;
    font-size: .88rem;
    color: var(--bs-emphasis-color);
    text-decoration: none;
}
.ent-item-fecha:hover { text-decoration: underline; }
.ent-item-human { font-size: .74rem; color: var(--bs-secondary-color); }
.ent-item-tipo {
    font-size: .7rem;
    font-weight: 600;
    color: #f59e0b;
    background: rgba(245, 158, 11, .12);
    border-radius: 999px;
    padding: .1rem .5rem;
}

.ent-item-notas {
    flex: 1 1 100%;
    font-size: .78rem;
    color: var(--bs-secondary-color);
}

.ent-item-actions { display: flex; align-items: center; gap: 2px; flex: 0 0 auto; margin-left: auto; }
.ent-icon-btn {
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    text-decoration: none;
    cursor: pointer;
}
.ent-icon-btn:hover { background: var(--bs-body-bg); color: var(--bs-emphasis-color); }
.ent-icon-btn-danger:hover { color: #dc3545; }

.cal-dia-vacio.cal-dia-elegido { background: rgba(124, 58, 237, .18); color: var(--bs-emphasis-color) !important; border-radius: 6px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Click en un día vacío del calendario: lo usa como fecha para crear el entrenamiento
    const inputFecha = document.getElementById('inputFechaNuevo');
    const formNuevo = document.getElementById('formNuevoEntrenamiento');

    document.querySelectorAll('.cal-dia-vacio').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!inputFecha) return;
            inputFecha.value = btn.dataset.fecha;

            document.querySelectorAll('.cal-dia-vacio.cal-dia-elegido').forEach(function (b) {
                b.classList.remove('cal-dia-elegido');
            });
            btn.classList.add('cal-dia-elegido');

            formNuevo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buscador = document.getElementById('entBuscador');
    if (!buscador) return;

    const grupos = document.querySelectorAll('.ent-group');
    const sinResultados = document.getElementById('entSinResultados');

    function normaliza(s) {
        return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    buscador.addEventListener('input', () => {
        const q = normaliza(buscador.value.trim());
        let totalVisible = 0;

        grupos.forEach(grupo => {
            const items = grupo.querySelectorAll('.ent-item');
            let visiblesEnGrupo = 0;

            items.forEach(item => {
                const coincide = !q || normaliza(item.dataset.search).includes(q);
                item.style.display = coincide ? '' : 'none';
                if (coincide) visiblesEnGrupo++;
            });

            grupo.style.display = (q && visiblesEnGrupo === 0) ? 'none' : '';
            totalVisible += visiblesEnGrupo;

            const collapseEl = grupo.querySelector('.collapse');
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            if (q && visiblesEnGrupo > 0) {
                bsCollapse.show();
            } else if (!q) {
                bsCollapse.hide();
            }
        });

        sinResultados.classList.toggle('d-none', !(q && totalVisible === 0));
    });
});
</script>


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
    (function() {
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
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Ocultar mes anterior';
            }

            collapseEl.addEventListener('shown.bs.collapse', () => {
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Ocultar mes anterior';
                localStorage.setItem(KEY, '1');
            });

            collapseEl.addEventListener('hidden.bs.collapse', () => {
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> Mostrar mes anterior';
                localStorage.setItem(KEY, '0');
            });
        }
    })();
</script>


<?= $this->endSection() ?>