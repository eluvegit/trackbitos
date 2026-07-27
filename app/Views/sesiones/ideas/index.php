<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-lightbulb text-primary"></i>
    <a href="<?= site_url('sesiones') ?>" class="text-decoration-none text-muted fw-normal">Sesiones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Ideas</strong>
</h5>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <p class="text-muted mb-0">Apuntes de futuras sesiones sin forma aún. Promociónalas a sesión cuando estés listo para planificarlas.</p>
    <a href="<?= site_url('sesiones/ideas/crear') ?>" class="btn btn-sm btn-primary text-nowrap">
        <i class="bi bi-plus-lg"></i> Nueva idea
    </a>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (empty($ideas)): ?>
    <p class="text-muted">Todavía no hay ideas apuntadas. <a href="<?= site_url('sesiones/ideas/crear') ?>">Crea la primera</a>.</p>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
        <?php foreach ($ideas as $idea): ?>
            <div class="col">
                <a href="<?= site_url('sesiones/ideas/' . $idea['id']) ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title fw-semibold mb-2"><?= esc($idea['titulo']) ?></h6>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php if ((int) $idea['tiene_foto'] === 1): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera"></i> Fotografía</span>
                                <?php endif; ?>
                                <?php if ((int) $idea['tiene_video'] === 1): ?>
                                    <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera-video"></i> Vídeo</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($idea['notas'])): ?>
                                <p class="card-text small text-muted mb-0">
                                    <?= esc(mb_strimwidth($idea['notas'], 0, 120, '…')) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
