<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<a href="<?= site_url('lentillas/compras') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Compras</a>

<h2 class="mb-4">Editar Compra</h2>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= site_url('lentillas/compras/actualizar/' . $compra['id']) ?>">
            <?= csrf_field() ?>

            <div class="row mb-3">
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

            <button type="submit" class="btn btn-success">Actualizar Compra</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
