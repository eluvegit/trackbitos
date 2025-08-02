<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-cart-plus fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Lentillas <span class="text-muted">/</span> <span class="text-secondary">Registro de Compras</span></h2>
</div>

<a href="<?= site_url('lentillas') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Lentillas</a>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= site_url('lentillas/compras') ?>">
            <?= csrf_field() ?>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <option value="Lentillas">Lentillas</option>
                        <option value="Gafas">Gafas</option>
                        <option value="Líquido">Líquido</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="precio" class="form-label">Precio (€)</label>
                    <input type="number" step="0.01" name="precio" id="precio" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-3">
                    <label for="notas" class="form-label">Notas</label>
                    <input type="text" name="notas" id="notas" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Compra</button>
        </form>
    </div>
</div>

<h4 class="my-5 text-center">Histórico de Compras</h4>

<?php if (!empty($compras)): ?>
    <div class="d-flex flex-column align-items-center gap-3">
    <?php foreach ($compras as $compra): ?>
        <div class="card w-100 shadow-sm" style="max-width: 700px;">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted"><?= esc(date('d/m/Y', strtotime($compra['fecha']))) ?></h6>
                <h5 class="card-title"><?= esc($compra['tipo']) ?></h5>
                <p class="card-text mb-1"><strong>Precio:</strong> <?= esc(number_format($compra['precio'], 2)) ?> €</p>
                <?php if (!empty($compra['notas'])): ?>
                    <p class="card-text"><strong>Notas:</strong> <?= esc($compra['notas']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="<?= site_url('lentillas/compras/editar/' . $compra['id']) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                <form action="<?= site_url('lentillas/compras/eliminar/' . $compra['id']) ?>" method="post" onsubmit="return confirm('¿Eliminar esta compra?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Borrar</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
    <p class="text-muted">No hay compras registradas aún.</p>
<?php endif; ?>

<?= $this->endSection() ?>