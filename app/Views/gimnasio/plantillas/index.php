<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="pl-header mb-3">
    <h2 class="pl-title mb-0"><i class="bi bi-collection text-primary"></i> Plantillas</h2>
</div>

<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="<?= site_url('gimnasio') ?>" class="btn btn-sm btn-outline-secondary">← Volver a Gimnasio</a>
</div>

<?php if (session()->getFlashdata('mensaje')): ?>
    <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('mensaje')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<form action="<?= site_url('gimnasio/plantillas/crear') ?>" method="post" class="pl-nueva mb-4">
    <input type="text" name="nombre" class="form-control" placeholder="Nombre de la nueva plantilla (ej. Empuje A, Full body...)" required>
    <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Crear</button>
</form>

<?php if (empty($plantillas)): ?>
    <div class="alert alert-light border">Todavía no tienes plantillas. Crea una arriba, o guarda un entrenamiento ya registrado como plantilla desde su página.</div>
<?php else: ?>
    <div class="pl-lista">
        <?php foreach ($plantillas as $p): ?>
            <div class="pl-item">
                <a href="<?= site_url('gimnasio/plantillas/editar/' . $p['id']) ?>" class="pl-item-main">
                    <div class="pl-item-nombre"><?= esc($p['nombre']) ?></div>
                    <div class="pl-item-count"><?= (int) $p['num_ejercicios'] ?> ejercicio<?= $p['num_ejercicios'] === 1 ? '' : 's' ?></div>
                </a>
                <div class="pl-item-actions">
                    <a href="<?= site_url('gimnasio/plantillas/editar/' . $p['id']) ?>" class="pl-icon-btn" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="<?= site_url('gimnasio/plantillas/eliminar/' . $p['id']) ?>" class="pl-icon-btn pl-icon-btn-danger"
                       onclick="return confirm('¿Eliminar la plantilla \'<?= esc($p['nombre'], 'js') ?>\'?')" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.pl-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.pl-nueva { display: flex; gap: 8px; }
.pl-nueva input { flex: 1 1 auto; }

.pl-lista { display: flex; flex-direction: column; gap: 8px; }
.pl-item {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-body-bg);
    padding: 4px 4px 4px 14px;
}
.pl-item-main { flex: 1 1 auto; min-width: 0; text-decoration: none; padding: 8px 0; }
.pl-item-nombre { font-weight: 700; font-size: .95rem; color: var(--bs-emphasis-color); }
.pl-item-count { font-size: .78rem; color: var(--bs-secondary-color); }

.pl-item-actions { display: flex; align-items: center; gap: 2px; flex: 0 0 auto; }
.pl-icon-btn {
    width: 36px; height: 36px;
    display: grid; place-items: center;
    border-radius: 50%;
    border: none; background: transparent;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.pl-icon-btn:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }
.pl-icon-btn-danger:hover { color: #dc3545; }
</style>

<?= $this->endSection() ?>
