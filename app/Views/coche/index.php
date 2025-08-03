<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">🚗 Coche</h2>
<?php if (!empty($avisosVencidos)): ?>
    <div class="alert alert-warning">
        <h5 class="mb-2">⚠️ Mantenimientos pendientes:</h5>
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

<div class="row">
    <div class="col-md-6 col-lg-4 mb-4">
        <a href="<?= site_url('coche/acciones') ?>" class="text-decoration-none text-dark">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title">Acciones</h5>
                        <p class="card-text">Registra cambios de aceite, filtros, revisiones, etc.</p>
                    </div>
                    <div class="mt-3">
                        <span class="btn btn-outline-primary btn-sm disabled">
                            Ver acciones <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-lg-4 mb-4">
        <a href="<?= site_url('coche/recordatorios') ?>" class="text-decoration-none text-dark">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title">Recordatorios</h5>
                        <p class="card-text">Define avisos para mantenimientos periódicos.</p>
                    </div>
                    <div class="mt-3">
                        <span class="btn btn-outline-warning btn-sm disabled">
                            Ver recordatorios <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-lg-4 mb-4">
        <a href="<?= site_url('coche/averias') ?>" class="text-decoration-none text-dark">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title">Averías</h5>
                        <p class="card-text">Registra y consulta averías anteriores del coche.</p>
                    </div>
                    <div class="mt-3">
                        <span class="btn btn-outline-danger btn-sm disabled">
                            Ver averías <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </a>
    </div>
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
                        <h5 class="card-title mb-1"><?= esc($accion['title']) ?></h5>
                        <p class="mb-1">
                            <?= esc($formateada) ?> (<?= esc($hace) ?>)
                            <br>Km: <?= esc($accion['kilometers']) ?>
                        </p>
                        <p class="text-muted small"><?= esc($accion['notes']) ?></p>
                        <div class="mt-2 d-flex gap-2">
                            <a href="<?= site_url('coche/acciones/editar/' . $accion['id']) ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                            <a href="<?= site_url('coche/acciones/borrar/' . $accion['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Borrar esta acción?')">Borrar</a>
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