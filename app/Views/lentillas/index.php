<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">Gestión de Lentillas</h2>
<?php if (isset($mostrarAlerta) && $mostrarAlerta): ?>
    <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div>
            🔔 Han pasado <strong><?= $dias ?></strong> días desde la última sustitución de lentillas. ¡Toca cambiarlas!
        </div>
    </div>
<?php endif; ?>
<div class="row row-cols-1 row-cols-md-3 g-4">

    <?php
    $items = [
        ['ruta' => 'compras', 'titulo' => 'Compras', 'desc' => 'Registro de lentillas, gafas y líquidos.'],
        ['ruta' => 'sustituciones', 'titulo' => 'Cambios y revisiones', 'desc' => 'Control de cambios de lentillas, estuche, líquidos y presión del ojo.'],
        ['ruta' => 'avisos', 'titulo' => 'Avisos', 'desc' => 'Notificaciones para reemplazos.'],
        ['ruta' => 'stock', 'titulo' => 'Stock', 'desc' => 'Pares de lentillas, líquidos y materiales.'],
    ];
    ?>

    <?php foreach ($items as $item): ?>
        <div class="col d-flex">
            <a href="<?= site_url('lentillas/' . $item['ruta']) ?>" class="text-decoration-none text-dark w-100 h-100">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= esc($item['titulo']) ?></h5>
                        <p class="card-text flex-grow-1"><?= esc($item['desc']) ?></p>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

</div>

<hr class="my-5">
<h3 class="mb-4">Tus Datos Médicos</h3>

<div class="row g-4">
    <!-- GAFAS -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
                <img src="https://www.svgrepo.com/show/530604/glasses.svg" alt="Gafas" width="200" class="mb-3">
                <h5 class="mb-3">Gafas</h5>
                <div class="d-flex justify-content-between px-4">
                    <div class="text-start">
                        <div><small class="text-muted">Izquierdo</small></div>
                        <div class="fw-bold"><?= esc($datos_medicos['graduacion_gaf_izq'] ?? '-6.5') ?></div>
                    </div>
                    <div class="text-end">
                        <div><small class="text-muted">Derecho</small></div>
                        <div class="fw-bold"><?= esc($datos_medicos['graduacion_gaf_der'] ?? '-7') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LENTILLAS -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
                <img src="https://www.svgrepo.com/show/530604/glasses.svg" alt="Gafas" width="200" class="mb-3">


                <h5 class="mb-3">Lentillas</h5>
                <div class="row text-start px-3">
                    <div class="col-6 mb-2">
                        <small class="text-muted">Esféra Izq</small><br>
                        <span class="fw-bold"><?= esc($datos_medicos['graduacion_lent_izq'] ?? '-6.00') ?></span>
                    </div>
                    <div class="col-6 mb-2 text-end">
                        <small class="text-muted">Esféra Der</small><br>
                        <span class="fw-bold"><?= esc($datos_medicos['graduacion_lent_der'] ?? '-6.50') ?></span>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">Radio Izq</small><br>
                        <span class="fw-bold"><?= esc($datos_medicos['radio_curvatura_izq'] ?? '8.60') ?></span>
                    </div>
                    <div class="col-6 mb-2 text-end">
                        <small class="text-muted">Radio Der</small><br>
                        <span class="fw-bold"><?= esc($datos_medicos['radio_curvatura_der'] ?? '8.60') ?></span>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">Diámetro Izq</small><br>
                        <span class="fw-bold"><?= esc($datos_medicos['diametro_izq'] ?? '14.20') ?></span>
                    </div>
                    <div class="col-6 mb-2 text-end">
                        <small class="text-muted">Diámetro Der</small><br>
                        <span class="fw-bold"><?= esc($datos_medicos['diametro_der'] ?? '14.20') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($datos_medicos['notas'])): ?>
    <div class="mt-4">
        <h6 class="text-muted">Notas</h6>
        <div class="border rounded p-3 bg-light text-secondary">
            <?= nl2br(esc($datos_medicos['notas'])) ?>
        </div>
    </div>
<?php endif; ?>


<?= $this->endSection() ?>
