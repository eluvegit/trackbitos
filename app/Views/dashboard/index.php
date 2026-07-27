<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php if ($mostrarAlerta): ?>
    <div class="alert alert-warning">
        🔔 Han pasado <?= $dias ?> días desde la última sustitución de lentillas. ¡Es hora de cambiarlas!
    </div>
<?php endif; ?>

<?php if (!empty($recordatoriosUrgentes)): ?>
    <?php $hayCaducado = !empty(array_filter($recordatoriosUrgentes, fn($r) => $r['nivel'] === 'caducado')); ?>
    <div class="alert <?= $hayCaducado ? 'alert-danger' : 'alert-warning' ?>">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-calendar-heart"></i>
            <strong>Recordatorios próximos</strong>
        </div>
        <ul class="mb-2 ps-3">
            <?php foreach ($recordatoriosUrgentes as $r): ?>
                <li>
                    <?= esc($r['titulo']) ?>
                    — <span class="fw-semibold"><?= esc($r['texto']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="<?= site_url('recordatorios') ?>" class="alert-link small">Ver todos los recordatorios →</a>
    </div>
<?php endif; ?>

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
    ['ruta' => 'hogar', 'icono' => '🏠', 'titulo' => 'Hogar', 'texto' => 'Checklist rutinario de limpieza y tareas del hogar por habitación.'],
    ['ruta' => 'recordatorios', 'icono' => '📅', 'titulo' => 'Recordatorios', 'texto' => 'ITV, revisiones médicas, vacunas, DNI, carnet... y cuándo tocan.'],
    ['ruta' => 'braintogram', 'icono' => '🧠', 'titulo' => 'Braintogram', 'texto' => 'Log de ingesta del bot de Telegram: segundo cerebro en construcción.'],
    ['ruta' => 'rodajes', 'icono' => '🎬', 'titulo' => 'Rodajes', 'texto' => 'Permite gestionar las escenas de un rodaje.'],
    ['ruta' => 'sesiones', 'icono' => '📸', 'titulo' => 'Sesiones', 'texto' => 'Kanban de sesiones de foto/vídeo: moodboard, equipo y model releases.'],
];
?>

<div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-3">
    <?php foreach ($secciones as $sec): ?>
        <div class="col d-flex">
            <?php if (!empty($sec['disabled'])): ?>
                <div class="card shadow-sm w-100" style="aspect-ratio: 1 / 1; cursor: not-allowed;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-2">
                        <div class="mb-2" style="font-size: 2rem; line-height: 1;">
                            <?= $sec['icono'] ?>
                        </div>
                        <h6 class="card-title mb-1"><?= $sec['titulo'] ?></h6>
                        <p class="mb-0 small text-muted d-none d-md-block">
                            <?= $sec['texto'] ?>
                        </p>

                    </div>
                </div>
            <?php else: ?>
                <a href="<?= site_url($sec['ruta']) ?>" class="text-decoration-none  w-100">
                    <div class="card shadow-sm w-100" style="aspect-ratio: 1 / 1;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-2">
                            <div class="mb-2" style="font-size: 2rem; line-height: 1;">
                                <?= $sec['icono'] ?>
                            </div>
                            <h6 class="card-title mb-1"><?= $sec['titulo'] ?></h6>
                            <p class="mb-0 small text-muted d-none d-md-block">
                                <?= $sec['texto'] ?>
                            </p>

                        </div>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>