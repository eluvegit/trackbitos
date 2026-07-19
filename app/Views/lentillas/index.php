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
$items = [
    ['ruta' => 'sustituciones', 'icono' => 'bi-arrow-repeat', 'titulo' => 'Cambios y revisiones', 'desc' => 'Lentillas, estuche, líquidos y presión del ojo.'],
    ['ruta' => 'stock', 'icono' => 'bi-box-seam', 'titulo' => 'Stock', 'desc' => 'Pares de lentillas, líquidos y materiales.'],
    ['ruta' => 'compras', 'icono' => 'bi-cart3', 'titulo' => 'Compras', 'desc' => 'Registro de lentillas, gafas y líquidos.'],
    ['ruta' => 'avisos', 'icono' => 'bi-bell', 'titulo' => 'Avisos', 'desc' => 'Notificaciones para reemplazos.'],
];
?>

<div class="list-group shadow-sm lentillas-menu mt-4">
    <?php foreach ($items as $item): ?>
        <a href="<?= site_url('lentillas/' . $item['ruta']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2">
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary flex-shrink-0" style="width:40px;height:40px;">
                <i class="bi <?= $item['icono'] ?>"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold"><?= esc($item['titulo']) ?></div>
                <div class="small text-muted text-truncate"><?= esc($item['desc']) ?></div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
    <?php endforeach; ?>
</div>

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

<?= $this->endSection() ?>
