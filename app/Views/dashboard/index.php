<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$hayAvisos = $mostrarAlerta || !empty($recordatoriosUrgentes);
$hayCaducado = !empty(array_filter($recordatoriosUrgentes, fn($r) => $r['nivel'] === 'caducado'));
?>

<?php if ($hayAvisos): ?>
    <a href="#avisos" class="alert <?= $hayCaducado ? 'alert-danger' : 'alert-warning' ?> db-avisos-pointer mt-3">
        <i class="bi bi-bell"></i>
        <span>Tienes avisos importantes — mira abajo</span>
        <i class="bi bi-arrow-down"></i>
    </a>
<?php endif; ?>

<div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-3 mt-1">
    <?php foreach ($secciones as $sec): ?>
        <div class="col d-flex">
            <a href="<?= site_url($sec['ruta']) ?>" class="text-decoration-none w-100">
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
        </div>
    <?php endforeach; ?>
</div>

<div id="avisos">
    <?php if ($mostrarAlerta): ?>
        <div class="alert alert-warning mt-4">
            🔔 Han pasado <?= $dias ?> días desde la última sustitución de lentillas. ¡Es hora de cambiarlas!
        </div>
    <?php endif; ?>

    <?php if (!empty($recordatoriosUrgentes)): ?>
        <div class="alert <?= $hayCaducado ? 'alert-danger' : 'alert-warning' ?> mt-4">
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
</div>

<style>
.db-avisos-pointer {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-size: .9rem;
}
.db-avisos-pointer:hover { filter: brightness(1.1); }
.db-avisos-pointer .bi-arrow-down { margin-left: auto; }
</style>

<?= $this->endSection() ?>
