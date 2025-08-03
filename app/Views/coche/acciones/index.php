<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <h2><i class="bi bi-gear fs-4 me-2 text-primary"></i>Coche <span class="text-muted">/</span> <span class="text-secondary">Acciones</span></h2>
</div>

<a href="<?= site_url('coche/') ?>" class="btn btn-outline-secondary mb-4">&larr; Volver</a>
<a href="<?= site_url('coche/acciones/nueva') ?>" class="btn btn-outline-success mb-4">Nueva acción manual</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('message') ?>
    </div>
<?php endif; ?>

<!-- Acciones rápidas -->
<h4 class="mb-4">Acciones rápidas para hoy</h4>
<div class="row mb-5">
    <?php
    $accionesRapidas = [
        ['title' => 'Cambio de aceite', 'icon' => 'gear-fill'],
        ['title' => 'Limpiar coche por fuera', 'icon' => 'stars'],
        ['title' => 'Limpiar coche por dentro', 'icon' => 'wind']
    ];
    ?>
    <?php foreach ($accionesRapidas as $accion): ?>
        <?php 
            $url = site_url('coche/acciones/rapida/' . urlencode(str_replace(' ', '-', $accion['title']))); 
            $confirmMsg = "¿Registrar \"" . $accion['title'] . "\" como acción para hoy?";
        ?>
        <div class="col-md-4 mb-3">
            <a href="<?= $url ?>" 
               onclick="return confirm('<?= esc($confirmMsg) ?>')" 
               class="text-decoration-none text-dark">
                <div class="card h-100 shadow-sm hover-shadow-sm text-center">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h4 class="card-title"><?= esc($accion['title']) ?></h4>
                        <i class="bi bi-<?= esc($accion['icon']) ?> mb-3 fs-2 text-primary"></i>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>


<hr>
<!-- Historial de acciones -->
<h4 class="text-muted text-center">Historial </h4>
<p class="mb-3 text-muted text-center">(solo se muestra el último de cada tipo)</p>

<?php foreach ($acciones as $index => $accion): ?>
    <?php
    $fecha = \CodeIgniter\I18n\Time::parse($accion['date']);
    $formateada = $fecha->toLocalizedString('d MMMM y');
    $hace = $fecha->humanize();
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
                        <a href="<?= site_url('coche/acciones/editar/' . $accion['id']) ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                        <a href="<?= site_url('coche/acciones/borrar/' . $accion['id']) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Borrar esta acción?')">Borrar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>




<?= $this->endSection() ?>