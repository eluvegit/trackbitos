<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">Lentillas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('lentillas/compras') ?>" class="text-muted text-decoration-none">Compras</a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Editar</span>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-pencil-square"></i>
    </div>
    <div>
        <h2 class="mb-0">Editar compra</h2>
        <small class="text-muted">Actualiza los datos de esta compra</small>
    </div>
</div>

<div class="card border-0 shadow-sm lentillas-card">
    <div class="card-body p-4">
        <form method="post" action="<?= site_url('lentillas/compras/actualizar/' . $compra['id']) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <option value="Lentillas" <?= $compra['tipo'] === 'Lentillas' ? 'selected' : '' ?>>Lentillas</option>
                        <option value="Gafas" <?= $compra['tipo'] === 'Gafas' ? 'selected' : '' ?>>Gafas</option>
                        <option value="Líquido" <?= $compra['tipo'] === 'Líquido' ? 'selected' : '' ?>>Líquido</option>
                        <option value="Otro" <?= $compra['tipo'] === 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="precio" class="form-label">Precio (€)</label>
                    <input type="number" step="0.01" name="precio" id="precio" class="form-control" value="<?= esc($compra['precio']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?= esc($compra['fecha']) ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="notas" class="form-label">Notas</label>
                    <input type="text" name="notas" id="notas" class="form-control" value="<?= esc($compra['notas']) ?>">
                </div>
            </div>

            <div class="mt-4 text-end">
                <a href="<?= site_url('lentillas/compras') ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Actualizar compra
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
