<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-exclamation-triangle text-danger"></i>
    <a href="<?= site_url('coche') ?>" class="text-decoration-none text-muted fw-normal">Coche</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Averías</strong>

    <a href="<?= site_url('coche/averias/nueva') ?>"
        class="text-decoration-none ms-1 text-success"
        title="Nueva avería">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle"></i><div><?= esc(session('success')) ?></div>
    </div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle"></i><div><?= esc(session('error')) ?></div>
    </div>
<?php endif; ?>

<?php if (empty($averias)): ?>
    <p class="text-muted text-center">No hay averías registradas aún.</p>
<?php endif; ?>

<div class="row">
    <?php foreach ($averias as $a): ?>
        <?php
            $fecha = \CodeIgniter\I18n\Time::parse($a['date']);
            $formateada = $fecha->toLocalizedString('d MMMM y');
            $hace = $fecha->humanize();
        ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1"><?= esc($formateada) ?> (<?= esc($hace) ?>)</h5>
                            <p class="mb-1">Km: <?= esc($a['kilometers']) ?></p>
                            <p class="text-muted small mb-0"><?= esc($a['notes']) ?></p>
                        </div>
                        <div class="d-flex">
                            <a href="<?= site_url('coche/averias/editar/' . $a['id']) ?>" class="coche-btn" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="<?= site_url('coche/averias/borrar/' . $a['id']) ?>" method="post" class="m-0"
                                  onsubmit="return confirm('¿Borrar esta avería?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="coche-btn coche-btn-danger" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
