<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-snow text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Nevera</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Variantes hechas pero incompletas o que no funcionan bien: no están en la papelera (no son
    candidatas a borrarse) ni se purgan nunca, solo se aparcan aquí para no estorbar en el listado,
    la galería ni el selector de "añadir componente". Existencias y Estadísticas las siguen contando.
</p>

<?php if (empty($variantes)): ?>
    <p class="text-muted">La nevera está vacía.</p>
<?php endif; ?>

<?php foreach ($variantes as $v): ?>
    <div class="card shadow-sm mb-2">
        <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
            <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>" class="flex-grow-1 text-truncate fw-semibold text-decoration-none">
                <?= esc($v['familia_nombre']) ?> / <?= esc($v['nombre']) ?>
            </a>

            <span class="text-muted small" title="<?= esc($v['congelado_en'], 'attr') ?>">
                en la nevera desde el <?= esc(date('d/m/Y', strtotime($v['congelado_en']))) ?>
            </span>

            <form method="post" action="<?= site_url('piezas/variante/' . (int) $v['id'] . '/nevera') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-counterclockwise"></i> Descongelar
                </button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
