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

<?php if (empty($familias)): ?>
    <p class="text-muted">La papelera está vacía.</p>
<?php else: ?>
    <?php foreach ($familias as $f): ?>
        <?php
            $borradaEn = strtotime($f['borrado_en']);
            $diasRestantes = max(0, 30 - (int) floor((time() - $borradaEn) / 86400));
        ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body p-3 d-flex align-items-center gap-3 flex-wrap">
                <strong class="flex-grow-1 text-truncate"><?= esc($f['nombre']) ?></strong>

                <span class="text-muted small" title="<?= esc($f['borrado_en'], 'attr') ?>">
                    borrada el <?= esc(date('d/m/Y', $borradaEn)) ?>
                </span>

                <span class="badge <?= $diasRestantes <= 3 ? 'text-bg-danger' : 'text-bg-secondary' ?>">
                    <?= $diasRestantes > 0 ? "purga en {$diasRestantes} día(s)" : 'purga en cualquier momento' ?>
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

<?= $this->endSection() ?>
