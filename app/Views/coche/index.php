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
?>

<div class="coche-grid">
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

<hr>
<!-- Últimas acciones realizadas -->
<h3 class="mt-5 text-muted text-center mb-4">Últimas acciones realizadas</h3>

<?php if (!empty($ultimasAcciones)): ?>
    <?php foreach ($ultimasAcciones as $accion): ?>
        <?php
            $fecha = \CodeIgniter\I18n\Time::parse($accion['date']);
            $formateada = $fecha->toLocalizedString('d MMMM y'); // Ej: 2 agosto 2025
            $hace = $fecha->humanize(); // Ej: "hace 3 días"
        ?>
        <div class="row justify-content-center">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title mb-1"><?= esc($accion['title']) ?></h5>
                                <p class="mb-1">
                                    <?= esc($formateada) ?> (<?= esc($hace) ?>)
                                    <br>Km: <?= esc($accion['kilometers']) ?>
                                </p>
                                <p class="text-muted small mb-0"><?= esc($accion['notes']) ?></p>
                            </div>
                            <div class="d-flex">
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
                    </div>
                </div>
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

<?= $this->endSection() ?>
