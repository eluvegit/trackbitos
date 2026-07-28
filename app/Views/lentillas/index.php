<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-eye"></i>
    </div>
    <div>
        <h2 class="mb-0">Lentillas</h2>
        <small class="text-muted">Cuidado ocular, stock y seguimiento de sustituciones</small>
    </div>
</div>

<?php if (isset($ultimoCambio)): ?>
    <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
        <i class="bi bi-calendar-check me-2"></i>
        <div>
            Último cambio de lentillas: <strong><?= (new \CodeIgniter\I18n\Time($ultimoCambio['fecha']))->toLocalizedString('d MMMM y') ?></strong>
            (hace <?= $ultimoCambio['dias'] ?> días)
        </div>
    </div>
<?php endif; ?>
<?php if (isset($mostrarAlerta) && $mostrarAlerta): ?>
    <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div>
            Han pasado <strong><?= $dias ?></strong> días desde la última sustitución de lentillas. ¡Toca cambiarlas!
        </div>
    </div>
<?php endif; ?>

<?php
$stockBajoCount = 0;
foreach ($stockItems ?? [] as $si) {
    if ((int) $si['cantidad'] <= 2) {
        $stockBajoCount++;
    }
}

$items = [
    ['ruta' => 'sustituciones', 'icono' => 'bi-arrow-repeat', 'titulo' => 'Cambios y revisiones', 'texto' => 'Lentillas, estuche, líquidos y presión del ojo.', 'count' => !empty($mostrarAlerta) ? 1 : 0],
    ['ruta' => 'stock', 'icono' => 'bi-box-seam', 'titulo' => 'Stock', 'texto' => 'Pares de lentillas, líquidos y materiales.', 'count' => $stockBajoCount],
    ['ruta' => 'compras', 'icono' => 'bi-cart3', 'titulo' => 'Compras', 'texto' => 'Registro de lentillas, gafas y líquidos.'],
    ['ruta' => 'avisos', 'icono' => 'bi-bell', 'titulo' => 'Avisos', 'texto' => 'Notificaciones para reemplazos.'],
];
?>

<?php
$accionesRapidas = [
    ['elemento' => 'lentillas', 'texto' => 'Hoy cambié las Lentillas', 'icono' => 'bi-eye', 'color' => 'primary'],
    ['elemento' => 'líquido', 'texto' => 'Hoy cambié el Líquido', 'icono' => 'bi-droplet', 'color' => 'info'],
    ['elemento' => 'estuche', 'texto' => 'Hoy cambié el Estuche', 'icono' => 'bi-briefcase', 'color' => 'warning'],
];
?>

<div class="lentillas-grid mt-4">
    <?php foreach ($accionesRapidas as $accion): ?>
        <button type="button" class="lentillas-tile-link js-sustitucion-rapida"
                data-elemento="<?= esc($accion['elemento']) ?>" data-texto="<?= esc($accion['texto']) ?>">
            <div class="lentillas-tile">
                <div class="lentillas-tile-icon text-<?= $accion['color'] ?>"><i class="bi <?= $accion['icono'] ?>"></i></div>
                <div class="lentillas-tile-title"><?= esc($accion['texto']) ?></div>
            </div>
        </button>
    <?php endforeach; ?>
    <?php foreach ($items as $item): ?>
        <a href="<?= site_url('lentillas/' . $item['ruta']) ?>" class="lentillas-tile-link">
            <div class="lentillas-tile">
                <?php if (!empty($item['count'])): ?>
                    <span class="lentillas-tile-count"><?= (int) $item['count'] ?></span>
                <?php endif; ?>
                <div class="lentillas-tile-icon"><i class="bi <?= $item['icono'] ?>"></i></div>
                <div class="lentillas-tile-title"><?= esc($item['titulo']) ?></div>
                <div class="lentillas-tile-text d-none d-md-block"><?= esc($item['texto']) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Modal de confirmación para las acciones rápidas -->
<div class="modal fade" id="modalSustitucionRapida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= site_url('lentillas/sustituciones') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="elemento" id="rapidaElemento">
                <div class="modal-header">
                    <h5 class="modal-title" id="rapidaTitulo">Confirmar cambio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="rapidaFecha" class="form-label">¿Qué día se hizo?</label>
                    <input type="date" name="fecha" id="rapidaFecha" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($stockItems)): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam text-primary"></i>
                    <h6 class="mb-0">Estado del stock</h6>
                    <?php if ($stockBajoCount > 0): ?>
                        <span class="badge rounded-pill text-bg-warning"><?= $stockBajoCount ?> bajo<?= $stockBajoCount > 1 ? 's' : '' ?></span>
                    <?php else: ?>
                        <span class="badge rounded-pill text-bg-success">Todo OK</span>
                    <?php endif; ?>
                </div>
                <a href="<?= site_url('lentillas/stock') ?>" class="small text-decoration-none">
                    Ver todo <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            <div class="row g-2">
                <?php foreach ($stockItems as $item):
                    $cantidad = (int) $item['cantidad'];
                    $stockBajo = $cantidad <= 2;
                    $nombre = strtolower($item['item']);
                    $icono = match (true) {
                        str_contains($nombre, 'izquierda') => 'bi-eye',
                        str_contains($nombre, 'derecha')   => 'bi-eye',
                        str_contains($nombre, 'líquido')   => 'bi-droplet',
                        str_contains($nombre, 'estuche')   => 'bi-briefcase',
                        default                             => 'bi-box-seam',
                    };
                ?>
                    <div class="col-6 col-md-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded <?= $stockBajo ? 'bg-warning bg-opacity-10' : 'bg-body-tertiary' ?>">
                            <i class="bi <?= $icono ?> <?= $stockBajo ? 'text-warning' : 'text-muted' ?>"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-truncate"><?= esc($item['item']) ?></div>
                                <div class="fw-bold <?= $stockBajo ? 'text-warning' : '' ?>"><?= $cantidad ?></div>
                            </div>
                            <?php if ($stockBajo): ?>
                                <i class="bi bi-exclamation-triangle-fill text-warning small" title="Stock bajo"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<hr class="my-5">
<div class="d-flex align-items-center gap-2 mb-4">
    <h3 class="mb-0">Tus Datos Médicos</h3>
    <span class="badge rounded-pill text-bg-secondary">Graduación actual</span>
</div>

<div class="row g-4">
    <!-- GAFAS -->
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm lentillas-card">
            <div class="lentillas-card-accent bg-primary"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="datos-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-eyeglasses"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Gafas</h5>
                        <small class="text-muted">Graduación de lejos</small>
                    </div>
                </div>

                <div class="row g-3 text-center">
                    <div class="col-6 border-end">
                        <span class="badge rounded-pill ojo-badge ojo-badge-izq mb-2">OI</span>
                        <div class="fs-3 fw-bold"><?= esc($datos_medicos['graduacion_gaf_izq'] ?? '-6.5') ?></div>
                        <small class="text-muted">Ojo izquierdo</small>
                    </div>
                    <div class="col-6">
                        <span class="badge rounded-pill ojo-badge ojo-badge-der mb-2">OD</span>
                        <div class="fs-3 fw-bold"><?= esc($datos_medicos['graduacion_gaf_der'] ?? '-7') ?></div>
                        <small class="text-muted">Ojo derecho</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LENTILLAS -->
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm lentillas-card">
            <div class="lentillas-card-accent bg-info"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="datos-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-record-circle"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Lentillas</h5>
                        <small class="text-muted">Parámetros de la lente</small>
                    </div>
                </div>

                <table class="table table-sm table-borderless align-middle mb-0 datos-tabla">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="fw-normal"></th>
                            <th class="fw-normal text-center">
                                <span class="badge rounded-pill ojo-badge ojo-badge-izq">OI</span>
                            </th>
                            <th class="fw-normal text-center">
                                <span class="badge rounded-pill ojo-badge ojo-badge-der">OD</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-muted"><i class="bi bi-rulers me-2"></i>Esfera</td>
                            <td class="text-center fw-bold"><?= esc($datos_medicos['graduacion_lent_izq'] ?? '-6.00') ?></td>
                            <td class="text-center fw-bold"><?= esc($datos_medicos['graduacion_lent_der'] ?? '-6.50') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="bi bi-arrow-repeat me-2"></i>Radio</td>
                            <td class="text-center fw-bold"><?= esc($datos_medicos['radio_curvatura_izq'] ?? '8.60') ?></td>
                            <td class="text-center fw-bold"><?= esc($datos_medicos['radio_curvatura_der'] ?? '8.60') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="bi bi-arrows-angle-expand me-2"></i>Diámetro</td>
                            <td class="text-center fw-bold"><?= esc($datos_medicos['diametro_izq'] ?? '14.20') ?></td>
                            <td class="text-center fw-bold"><?= esc($datos_medicos['diametro_der'] ?? '14.20') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($datos_medicos['notas'])): ?>
    <div class="mt-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-2 text-muted">
                    <i class="bi bi-journal-text"></i>
                    <h6 class="mb-0 text-uppercase small">Notas</h6>
                </div>
                <p class="mb-0"><?= nl2br(esc($datos_medicos['notas'])) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .datos-tabla td,
    .datos-tabla th {
        padding-top: .5rem;
        padding-bottom: .5rem;
    }
</style>

<script>
(() => {
    const modalEl = document.getElementById('modalSustitucionRapida');
    // bootstrap.bundle.min.js se carga al final del layout, después de este
    // script, así que la instancia del modal se crea perezosamente (en el
    // primer clic) en vez de al cargar la página, para no depender del orden.
    let modal = null;
    const inputElemento = document.getElementById('rapidaElemento');
    const inputFecha = document.getElementById('rapidaFecha');
    const tituloEl = document.getElementById('rapidaTitulo');

    function hoyISO() {
        const d = new Date();
        const tz = d.getTimezoneOffset() * 60000;
        return new Date(d - tz).toISOString().slice(0, 10);
    }

    document.querySelectorAll('.js-sustitucion-rapida').forEach(btn => {
        btn.addEventListener('click', () => {
            modal ??= new bootstrap.Modal(modalEl);
            inputElemento.value = btn.dataset.elemento;
            inputFecha.value = hoyISO();
            tituloEl.textContent = 'Confirmar: ' + btn.dataset.texto;
            modal.show();
        });
    });
})();
</script>

<?= $this->endSection() ?>
