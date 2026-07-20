<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-exclamation-triangle text-danger"></i>
    <a href="<?= site_url('coche') ?>" class="text-decoration-none text-muted fw-normal">Coche</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('coche/averias') ?>" class="text-decoration-none text-muted fw-normal">Averías</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= isset($averia) ? 'Editar' : 'Nueva' ?> avería</strong>
</h5>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="<?= site_url('coche/averias/guardar') ?>" method="post">
                    <?= csrf_field() ?>
                    <?php if (isset($averia)): ?>
                        <input type="hidden" name="id" value="<?= esc($averia['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="date" class="form-control" value="<?= esc($averia['date'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kilómetros</label>
                        <input type="number" name="kilometers" class="form-control" value="<?= esc($averia['kilometers'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="3" required><?= esc($averia['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
                        <a href="<?= site_url('coche/averias') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
