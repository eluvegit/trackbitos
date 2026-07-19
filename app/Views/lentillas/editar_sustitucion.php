<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">Lentillas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('lentillas/sustituciones') ?>" class="text-muted text-decoration-none">Cambios y revisiones</a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Editar</span>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-pencil-square"></i>
    </div>
    <div>
        <h2 class="mb-0">Editar sustitución</h2>
        <small class="text-muted">Actualiza el elemento, la fecha o las notas</small>
    </div>
</div>

<div class="card border-0 shadow-sm lentillas-card">
    <div class="card-body p-4">
        <form method="post" action="<?= site_url('lentillas/sustituciones/actualizar/' . $sustitucion['id']) ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="elemento" class="form-label">Elemento</label>
                <select name="elemento" id="elemento" class="form-select" required>
                    <option value="lentilla izquierda" <?= $sustitucion['elemento'] === 'lentilla izquierda' ? 'selected' : '' ?>>Lentilla izquierda</option>
                    <option value="lentilla derecha" <?= $sustitucion['elemento'] === 'lentilla derecha' ? 'selected' : '' ?>>Lentilla derecha</option>
                    <option value="lentillas" <?= $sustitucion['elemento'] === 'lentillas' ? 'selected' : '' ?>>Lentillas (ambas)</option>
                    <option value="estuche" <?= $sustitucion['elemento'] === 'estuche' ? 'selected' : '' ?>>Estuche</option>
                    <option value="líquido" <?= $sustitucion['elemento'] === 'líquido' ? 'selected' : '' ?>>Líquido</option>
                    <option value="presion" <?= $sustitucion['elemento'] === 'presion' ? 'selected' : '' ?>>Presión de ojo</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" name="fecha" id="fecha" value="<?= $sustitucion['fecha'] ?>" class="form-control" required>
            </div>

            <div class="mb-4">
                <label for="notas" class="form-label">Notas</label>
                <input type="text" name="notas" id="notas" value="<?= esc($sustitucion['notas']) ?>" class="form-control">
            </div>

            <div class="text-end">
                <a href="<?= site_url('lentillas/sustituciones') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
