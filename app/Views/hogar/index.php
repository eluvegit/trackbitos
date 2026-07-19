<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-house-heart text-primary"></i>

    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Hogar</strong>

    <a href="<?= site_url('hogar/habitaciones/nueva') ?>"
        class="text-decoration-none ms-1 text-success"
        title="Nueva habitación">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>

    <a href="<?= site_url('hogar/gestionar') ?>"
        class="text-decoration-none text-muted"
        title="Reordenar y editar habitaciones">
        <i class="bi bi-sliders fs-6"></i>
    </a>
</h5>

<a href="<?= site_url('hogar/pendientes') ?>" class="btn btn-outline-primary btn-sm rounded-pill mb-3 d-inline-flex align-items-center gap-2">
    <i class="bi bi-list-check"></i>
    Qué toca hacer
    <?php if ($totalPendientes > 0): ?>
        <span class="badge rounded-pill bg-primary"><?= (int)$totalPendientes ?></span>
    <?php endif; ?>
</a>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-sm-3 row-cols-lg-4 g-3">
    <?php foreach ($habitaciones as $h): ?>
        <div class="col d-flex">
            <a href="<?= site_url('hogar/' . $h['id']) ?>" class="text-decoration-none w-100 hogar-card-link">
                <div class="card shadow-sm w-100 h-100 hogar-card">
                    <?php if ($h['atrasadas'] > 0): ?>
                        <span class="hogar-badge" title="<?= (int)$h['atrasadas'] ?> tarea(s) atrasada(s)">
                            <?= (int)$h['atrasadas'] ?>
                        </span>
                    <?php endif; ?>

                    <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-<?= esc($h['icono'] ?: 'house') ?> fs-2 text-primary mb-2"></i>
                        <h6 class="card-title mb-1"><?= esc($h['nombre']) ?></h6>
                        <div class="small text-muted"><?= (int)$h['hechas'] ?>/<?= (int)$h['total'] ?> hechas</div>
                        <div class="hogar-progress mt-2">
                            <div class="hogar-progress-bar" style="width: <?= (int)$h['pct'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($habitaciones)): ?>
    <p class="text-muted mt-3">Todavía no hay habitaciones. Crea la primera con el botón "+".</p>
<?php endif; ?>

<style>
.hogar-card {
    position: relative;
    border-radius: 14px;
    transition: transform .15s ease, box-shadow .2s ease;
}
.hogar-card-link:hover .hogar-card {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,.15);
}

.hogar-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    background: #dc3545;
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    display: grid;
    place-items: center;
    box-shadow: 0 2px 6px rgba(220,53,69,.5);
}

.hogar-progress {
    width: 100%;
    height: 5px;
    border-radius: 999px;
    background: rgba(124,58,237,.12);
    overflow: hidden;
}
.hogar-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
}
</style>

<?= $this->endSection() ?>
