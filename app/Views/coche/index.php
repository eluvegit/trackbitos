<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-car-front text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Coche</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle"></i><div><?= esc(session('success')) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($avisosVencidos)): ?>
    <div class="alert alert-warning">
        <h5 class="mb-2"><i class="bi bi-exclamation-triangle-fill"></i> Mantenimientos pendientes:</h5>
        <ul class="mb-0">
            <?php foreach ($avisosVencidos as $a): ?>
                <li>
                    <strong><?= esc($a['title']) ?></strong> —
                    última vez hace <strong><?= esc($a['dias']) ?> días</strong>
                    (intervalo: <?= esc($a['intervalo']) ?> días)
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php
$secciones = [
    ['ruta' => 'coche/acciones',      'icono' => 'bi-gear', 'titulo' => 'Acciones', 'texto' => 'Registra cambios de aceite, filtros, revisiones, etc.'],
    ['ruta' => 'coche/recordatorios', 'icono' => 'bi-bell', 'titulo' => 'Recordatorios', 'texto' => 'Define avisos para mantenimientos periódicos.', 'count' => count($avisosVencidos)],
    ['ruta' => 'coche/averias',       'icono' => 'bi-exclamation-triangle', 'titulo' => 'Averías', 'texto' => 'Registra y consulta averías anteriores del coche.'],
];

$accionesRapidas = [
    ['titulo' => 'Cambio de aceite',        'icono' => 'bi-gear-fill', 'color' => 'primary'],
    ['titulo' => 'Limpiar coche por fuera', 'icono' => 'bi-stars',     'color' => 'info'],
    ['titulo' => 'Limpiar coche por dentro','icono' => 'bi-wind',      'color' => 'warning'],
];
?>

<div class="coche-grid">
    <?php foreach ($accionesRapidas as $accion): ?>
        <button type="button" class="coche-card-link js-accion-rapida" data-titulo="<?= esc($accion['titulo']) ?>">
            <div class="coche-card">
                <div class="coche-card-icon text-<?= $accion['color'] ?>"><i class="bi <?= $accion['icono'] ?>"></i></div>
                <div class="coche-card-title"><?= esc($accion['titulo']) ?></div>
            </div>
        </button>
    <?php endforeach; ?>
    <?php foreach ($secciones as $sec): ?>
        <a href="<?= site_url($sec['ruta']) ?>" class="coche-card-link">
            <div class="coche-card">
                <?php if (!empty($sec['count'])): ?>
                    <span class="coche-card-count"><?= (int) $sec['count'] ?></span>
                <?php endif; ?>
                <div class="coche-card-icon"><i class="bi <?= esc($sec['icono']) ?>"></i></div>
                <div class="coche-card-title"><?= esc($sec['titulo']) ?></div>
                <div class="coche-card-text d-none d-md-block"><?= esc($sec['texto']) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Modal de confirmación para las acciones rápidas -->
<div class="modal fade" id="modalAccionRapida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="formAccionRapida" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="fecha" id="rapidaFecha">
                <div class="modal-header">
                    <h5 class="modal-title" id="rapidaTitulo">Confirmar acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="rapidaFechaVisible" class="form-label">¿Qué día se hizo?</label>
                    <input type="date" id="rapidaFechaVisible" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<hr>
<!-- Últimas acciones realizadas -->
<h3 class="mt-5 text-muted text-center mb-4">Últimas acciones realizadas</h3>

<div class="coche-acciones-lista">
<?php if (!empty($ultimasAcciones)): ?>
    <?php foreach ($ultimasAcciones as $accion): ?>
        <?php
            $fecha = \CodeIgniter\I18n\Time::parse($accion['date']);
            $formateada = $fecha->toLocalizedString('d MMMM y'); // Ej: 2 agosto 2025
            $hace = $fecha->humanize(); // Ej: "hace 3 días"
            $tituloLower = strtolower($accion['title']);
            $icono = match (true) {
                str_contains($tituloLower, 'aceite')  => 'bi-gear-fill',
                str_contains($tituloLower, 'fuera')   => 'bi-stars',
                str_contains($tituloLower, 'dentro')  => 'bi-wind',
                default                                => 'bi-gear',
            };
        ?>
        <div class="coche-rec-card js-detalle-accion" role="button" tabindex="0"
             data-titulo="<?= esc($accion['title']) ?>"
             data-icono="<?= $icono ?>"
             data-fecha="<?= esc($formateada) ?>"
             data-hace="<?= esc($hace) ?>"
             data-km="<?= esc($accion['kilometers'] ?? '') ?>"
             data-notas="<?= esc($accion['notes'] ?? '') ?>"
             data-editar="<?= site_url('coche/acciones/editar/' . $accion['id']) ?>">
            <div class="coche-rec-icono">
                <i class="bi <?= $icono ?>"></i>
            </div>

            <div class="coche-rec-main">
                <div class="coche-rec-row-top">
                    <div class="coche-rec-titulo"><?= esc($accion['title']) ?></div>
                    <span class="coche-badge coche-badge-neutro"><?= esc($hace) ?></span>
                </div>

                <div class="coche-rec-row-bottom">
                    <div class="coche-rec-meta">
                        <?= esc($formateada) ?>
                        <?php if (!empty($accion['kilometers'])): ?>
                            · <?= esc($accion['kilometers']) ?> km
                        <?php endif; ?>
                    </div>

                    <div class="coche-rec-actions">
                        <a href="<?= site_url('coche/acciones/editar/' . $accion['id']) ?>" class="coche-btn" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="<?= site_url('coche/acciones/borrar/' . $accion['id']) ?>" method="post" class="m-0"
                              onsubmit="return confirm('¿Borrar esta acción?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="coche-btn coche-btn-danger" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($accion['notes'])): ?>
                    <div class="coche-rec-meta"><?= esc($accion['notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="row">
        <div class="col-12 text-center text-muted">
            <p>No hay acciones registradas aún.</p>
        </div>
    </div>
<?php endif; ?>
</div>

<?= $this->include('coche/_modal_detalle') ?>

<script>
(() => {
    const modalEl = document.getElementById('modalAccionRapida');
    // bootstrap.bundle.min.js se carga al final del layout, después de este
    // script, así que la instancia del modal se crea perezosamente (en el
    // primer clic) en vez de al cargar la página, para no depender del orden.
    let modal = null;
    const form = document.getElementById('formAccionRapida');
    const inputFecha = document.getElementById('rapidaFecha');
    const inputFechaVisible = document.getElementById('rapidaFechaVisible');
    const tituloEl = document.getElementById('rapidaTitulo');

    function hoyISO() {
        const d = new Date();
        const tz = d.getTimezoneOffset() * 60000;
        return new Date(d - tz).toISOString().slice(0, 10);
    }

    document.querySelectorAll('.js-accion-rapida').forEach(btn => {
        btn.addEventListener('click', () => {
            modal ??= new bootstrap.Modal(modalEl);
            const titulo = btn.dataset.titulo;
            const slug = titulo.replace(/ /g, '-');
            form.action = '<?= site_url('coche/acciones/rapida') ?>/' + encodeURIComponent(slug);
            tituloEl.textContent = 'Confirmar: ' + titulo;
            inputFechaVisible.value = hoyISO();
            modal.show();
        });
    });

    form.addEventListener('submit', () => {
        inputFecha.value = inputFechaVisible.value;
    });
})();
</script>

<?= $this->endSection() ?>
