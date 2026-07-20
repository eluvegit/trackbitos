<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-bell text-primary"></i>
    <a href="<?= site_url('coche') ?>" class="text-decoration-none text-muted fw-normal">Coche</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('coche/recordatorios') ?>" class="text-decoration-none text-muted fw-normal">Recordatorios</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= isset($recordatorio) ? 'Editar' : 'Nuevo' ?> recordatorio</strong>
</h5>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="<?= site_url('coche/recordatorios/guardar') ?>" method="post">
                    <?= csrf_field() ?>
                    <?php if (isset($recordatorio)): ?>
                        <input type="hidden" name="id" value="<?= esc($recordatorio['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control" value="<?= esc($recordatorio['title'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intervalo (días)</label>
                        <input type="number" name="interval_days" class="form-control" value="<?= esc($recordatorio['interval_days'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intervalo (km)</label>
                        <input type="number" name="interval_km" class="form-control" value="<?= esc($recordatorio['interval_km'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="3"><?= esc($recordatorio['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
                        <a href="<?= site_url('coche/recordatorios') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
