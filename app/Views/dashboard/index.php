<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php if ($mostrarAlerta): ?>
    <div class="alert alert-warning">
        🔔 Han pasado <?= $dias ?> días desde la última sustitución de lentillas. ¡Es hora de cambiarlas!
    </div>
<?php endif; ?>


<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php
    $secciones = [
        ['ruta' => 'comidas/diario/hoy', 'icono' => '🍽️', 'titulo' => 'Comida', 'texto' => 'Planifica tus menús, dieta y seguimiento alimenticio.'],
        ['ruta' => 'gimnasio', 'icono' => '🏋️', 'titulo' => 'Gimnasio', 'texto' => 'Registra tus entrenamientos, progresos y objetivos físicos.'],
        ['ruta' => 'compras', 'icono' => '🛒', 'titulo' => 'Compras', 'texto' => 'Lleva control de tus compras, listas y gastos.'],
        ['ruta' => 'lentillas', 'icono' => '👁️', 'titulo' => 'Lentillas', 'texto' => 'Lleva un registro de cambios, limpieza y reemplazos.'],
        ['ruta' => 'coche', 'icono' => '🚗', 'titulo' => 'Coche', 'texto' => 'Controla cambios de aceite, revisiones, neumáticos y más.'],
        ['ruta' => 'youtube', 'icono' => '▶️', 'titulo' => 'YouTube', 'texto' => 'Permite revisar los vídeos guardados como interesantes.'],
        ['ruta' => 'enlaces', 'icono' => '📒', 'titulo' => 'Enlaces', 'texto' => 'Permite revisar los enlaces registrados interesantes.'],
        ['ruta' => 'journal', 'icono' => '📨', 'titulo' => 'Journal', 'texto' => 'Permite hacer y seguir tareas y bullet journal.'],
        ['ruta' => 'rodajes', 'icono' => '🎬', 'titulo' => 'Rodajes', 'texto' => 'Permite gestionar las escenas de un rodaje.'],
        ['ruta' => 'workflow', 'icono' => '🗂️', 'titulo' => 'Sesiones', 'texto' => 'Gestión de flujo de trabajo en la edición de fotos.', 'disabled' => true],
    ];
    ?>

    <div class="row row-cols-3 g-3">
        <?php foreach ($secciones as $sec): ?>
            <div class="col d-flex">
                <?php if(!empty($sec['disabled'])): ?>
                    <div class="card shadow-sm w-100 bg-light text-muted" style="aspect-ratio: 1 / 1; cursor: not-allowed;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-2">
                            <div class="mb-2" style="font-size: 2rem; line-height: 1;">
                                <?= $sec['icono'] ?>
                            </div>
                            <h6 class="card-title mb-1"><?= $sec['titulo'] ?></h6>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= site_url($sec['ruta']) ?>" class="text-decoration-none text-dark w-100">
                        <div class="card shadow-sm w-100" style="aspect-ratio: 1 / 1;">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-2">
                                <div class="mb-2" style="font-size: 2rem; line-height: 1;">
                                    <?= $sec['icono'] ?>
                                </div>
                                <h6 class="card-title mb-1"><?= $sec['titulo'] ?></h6>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->endSection() ?>
