<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-trash text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Papelera</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Piezas borradas: nada se destruye al momento (invariante 6), así que mientras estén aquí se
    pueden restaurar tal cual estaban. Pasados 30 días desde el borrado, <code>piezas:purgar</code>
    se las lleva de verdad — la fila y sus ficheros.
</p>

<?php
/** Días hasta la purga automática (30 desde el borrado), listos para pintar. */
$diasRestantes = static function (string $borradoEn): int {
    return max(0, 30 - (int) floor((time() - strtotime($borradoEn)) / 86400));
};
?>

<?php if (empty($familias) && empty($variantes)): ?>
    <p class="text-muted">La papelera está vacía.</p>
<?php endif; ?>

<?php if (!empty($familias)): ?>
    <h6 class="mb-2">Piezas enteras</h6>
    <?php foreach ($familias as $f): ?>
        <?php $dias = $diasRestantes($f['borrado_en']); ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <strong class="flex-grow-1 text-truncate"><?= esc($f['nombre']) ?></strong>

                <span class="text-muted small" title="<?= esc($f['borrado_en'], 'attr') ?>">
                    borrada el <?= esc(date('d/m/Y', strtotime($f['borrado_en']))) ?>
                </span>

                <span class="badge <?= $dias <= 3 ? 'text-bg-danger' : 'text-bg-secondary' ?>">
                    <?= $dias > 0 ? "purga en {$dias} día(s)" : 'purga en cualquier momento' ?>
                </span>

                <form method="post" action="<?= site_url('piezas/familia/' . (int) $f['id'] . '/restaurar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($variantes)): ?>
    <h6 class="mb-2 mt-3">Variantes sueltas</h6>
    <p class="text-muted small">
        El resto de la pieza sigue intacto — solo esta línea de diseño se apartó.
    </p>
    <?php foreach ($variantes as $v): ?>
        <?php $dias = $diasRestantes($v['borrado_en']); ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <strong class="flex-grow-1 text-truncate"><?= esc($v['familia_nombre']) ?> / <?= esc($v['nombre']) ?></strong>

                <span class="text-muted small" title="<?= esc($v['borrado_en'], 'attr') ?>">
                    borrada el <?= esc(date('d/m/Y', strtotime($v['borrado_en']))) ?>
                </span>

                <span class="badge <?= $dias <= 3 ? 'text-bg-danger' : 'text-bg-secondary' ?>">
                    <?= $dias > 0 ? "purga en {$dias} día(s)" : 'purga en cualquier momento' ?>
                </span>

                <form method="post" action="<?= site_url('piezas/variante/' . (int) $v['id'] . '/restaurar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
