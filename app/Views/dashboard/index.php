<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php if ($mostrarAlerta): ?>
    <div class="alert alert-warning">
        🔔 Han pasado <?= $dias ?> días desde la última sustitución de lentillas. ¡Es hora de cambiarlas!
    </div>
<?php endif; ?>

<h1 class="mb-4">Bienvenido a Trackbitos</h1>
<p class="mb-4">Selecciona una sección para comenzar:</p>

<div class="row row-cols-1 row-cols-md-3 g-4">

    <?php
    $secciones = [
        ['ruta' => 'comidas/diario/hoy', 'icono' => '🍽️', 'titulo' => 'Comida (BETA)', 'texto' => 'Planifica tus menús, dieta y seguimiento alimenticio.'],
        ['ruta' => 'gimnasio', 'icono' => '🏋️', 'titulo' => 'Gimnasio', 'texto' => 'Registra tus entrenamientos, progresos y objetivos físicos.'],
        ['ruta' => 'compras', 'icono' => '🛒', 'titulo' => 'Compras', 'texto' => 'Lleva control de tus compras, listas y gastos.'],
        ['ruta' => 'lentillas', 'icono' => '👁️', 'titulo' => 'Lentillas', 'texto' => 'Lleva un registro de cambios, limpieza y reemplazos.'],
        ['ruta' => 'coche', 'icono' => '🚗', 'titulo' => 'Coche', 'texto' => 'Controla cambios de aceite, revisiones, neumáticos y más.'],
        ['ruta' => 'workflow', 'icono' => '🗂️', 'titulo' => 'Workflow (PROXIMAMENTE)', 'texto' => 'Gestión de flujo de trabajo en la edición de fotos.'],
        ['ruta' => 'youtube', 'icono' => '▶️', 'titulo' => 'YouTube (PRÓXIMAMENTE)', 'texto' => 'Permite revisar los vídeos guardados como interesantes.'],
        ['ruta' => 'notion', 'icono' => '📒', 'titulo' => 'Notion (PRÓXIMAMENTE)', 'texto' => 'Permite revisar los enlaces registrados en Notion.'],
        ['ruta' => 'telegram', 'icono' => '📨', 'titulo' => 'Telegram (PRÓXIMAMENTE)', 'texto' => 'Permite revisar los enlaces registrados en Telegram.'],
    ];
    ?>

    <?php foreach ($secciones as $sec): ?>
        <div class="col d-flex">
            <a href="<?= site_url($sec['ruta']) ?>" class="text-decoration-none text-dark w-100 h-100">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= $sec['icono'] . ' ' . $sec['titulo'] ?></h5>
                        <p class="card-text flex-grow-1"><?= $sec['texto'] ?></p>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

</div>

<?= $this->endSection() ?>