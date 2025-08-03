<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle fs-4 me-2 text-danger"></i>Coche <span class="text-muted">/</span> <span class="text-secondary">Averías</span> <span class="text-muted">/</span> <span class="text-secondary"><?= isset($averia) ? 'Editar' : 'Nueva' ?> avería</span></h2>
</div>
<a href="<?= site_url('coche/averias') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver</a>

<div class="row justify-content-center">
    <div class="col-md-6">
        

        <form action="<?= site_url('coche/averias/guardar') ?>" method="post">
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

            <button type="submit" class="btn btn-outline-primary">Guardar</button>
            <a href="<?= site_url('coche/averias') ?>" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
