<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-clipboard2-pulse text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Gimnasio</strong>
</h5>

<?php
$secciones = [
    ['ruta' => 'gimnasio/entrenamientos',          'icono' => '📆', 'titulo' => 'Entrenamientos', 'texto' => 'Registra y consulta tus entrenamientos diarios.'],
    ['ruta' => 'gimnasio/ejercicios/principales',  'icono' => '📊', 'titulo' => 'Estadísticas',    'texto' => 'Progreso de tus 3 ejercicios principales.'],
    ['ruta' => 'gimnasio/mesociclos',              'icono' => '🧩', 'titulo' => 'Mesociclos',      'texto' => 'Rutinas de progresión por mesociclos.'],
    ['ruta' => 'gimnasio/ejercicios',              'icono' => '📋', 'titulo' => 'Ejercicios',      'texto' => 'Gestiona y clasifica los ejercicios disponibles.'],
    ['ruta' => 'gimnasio/plantillas',              'icono' => '🗂️', 'titulo' => 'Plantillas',      'texto' => 'Guarda tus rutinas frecuentes.'],
];
?>

<div class="gim-grid">
    <?php foreach ($secciones as $sec): ?>
        <?php if (!empty($sec['disabled'])): ?>
            <div class="gim-card gim-card-disabled">
                <div class="gim-card-icon"><?= $sec['icono'] ?></div>
                <div class="gim-card-title"><?= esc($sec['titulo']) ?></div>
                <div class="gim-card-text d-none d-md-block"><?= esc($sec['texto']) ?></div>
                <span class="gim-card-badge">Próximamente</span>
            </div>
        <?php else: ?>
            <a href="<?= site_url($sec['ruta']) ?>" class="gim-card-link">
                <div class="gim-card">
                    <div class="gim-card-icon"><?= $sec['icono'] ?></div>
                    <div class="gim-card-title"><?= esc($sec['titulo']) ?></div>
                    <div class="gim-card-text d-none d-md-block"><?= esc($sec['texto']) ?></div>
                </div>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<style>
.gim-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
@media (min-width: 576px) {
    .gim-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (min-width: 768px) {
    .gim-grid { grid-template-columns: repeat(5, 1fr); }
}

.gim-card-link { text-decoration: none; display: block; }

.gim-card {
    aspect-ratio: 1 / 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 4px;
    padding: 10px;
    border-radius: 16px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    transition: transform .15s ease, box-shadow .2s ease, border-color .15s ease;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
}
.gim-card-link:hover .gim-card {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,.18);
    border-color: #7c3aed;
}

.gim-card-icon { font-size: 1.8rem; line-height: 1; flex-shrink: 0; }
.gim-card-title {
    font-size: .85rem;
    font-weight: 700;
    color: var(--bs-emphasis-color);
    line-height: 1.15;
    word-break: break-word;
    hyphens: auto;
}
.gim-card-text {
    font-size: .72rem;
    color: var(--bs-secondary-color);
    line-height: 1.2;
}

.gim-card-disabled {
    aspect-ratio: 1 / 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 4px;
    padding: 10px;
    border-radius: 16px;
    border: 1px dashed var(--bs-border-color);
    background: transparent;
    opacity: .6;
    cursor: not-allowed;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
}
.gim-card-badge {
    margin-top: 4px;
    font-size: .62rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
    border-radius: 999px;
    padding: .15rem .5rem;
}
</style>

<?= $this->endSection() ?>
