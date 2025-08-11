<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">
    🛒 Compras /
    <strong><?= esc($supermercado_nombre) ?></strong>
    <a href="<?= site_url('compras/supermercados/editar/' . $supermercado_id) ?>" class="text-decoration-none ms-2" title="Editar supermercado"> <i class="bi bi-pencil-square fs-6"></i>
    </a>
</h2>

<div class="mb-4">
    <a href="<?= site_url('compras/supermercados') ?>" class="btn btn-outline-secondary">← Volver a supermercados</a>
</div>


<!-- Accesos rápidos a listas -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 mb-4">
    <div class="col">
        <a href="<?= site_url('compras/' . $supermercado_id . '/faltantes') ?>" class="text-decoration-none text-dark">
            <div class="card shadow-sm h-100 border-warning border-2">
                <div class="card-body text-center">
                    <h5 class="card-title">📝 Apuntar lo que falta</h5>
                    <p class="card-text text-muted">Lista para apuntar lo que falta.</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="<?= site_url('compras/' . $supermercado_id . '/comprados') ?>" class="text-decoration-none text-dark">
            <div class="card shadow-sm h-100 border-success border-2">
                <div class="card-body text-center">
                    <h5 class="card-title">🛍️ Ir a comprar</h5>
                    <p class="card-text text-muted">Lista de productos que has comprado.</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Formulario para nuevo producto -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= site_url('compras/productos/nuevo') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">

            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="nombre" class="form-label">Nombre del producto</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label for="imagen" class="form-label">URL de imagen (opcional)</label>
                    <input type="url" name="imagen" id="imagen" class="form-control">
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-success w-100">+ Añadir producto</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de productos -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php foreach ($productos as $producto): ?>
        <div class="col d-flex">
            <div class="card shadow-sm w-100">
                <?php if (!empty($producto['imagen'])): ?>
                    <img src="<?= esc($producto['imagen']) ?>" class="card-img-top" style="object-fit: cover; height: 180px;">
                <?php endif; ?>
                <div class="card-body d-flex flex-column justify-content-between">
                    <h5 class="card-title"><?= esc($producto['nombre']) ?></h5>

                    <div class="mb-2">
                        <?php if ($producto['faltante']): ?>
                            <span class="badge bg-warning text-dark">FALTA</span>
                        <?php endif; ?>
                        <?php if ($producto['comprado']): ?>
                            <span class="badge bg-success">Comprado</span>
                        <?php endif; ?>
                    </div>

                    <div class="text-end">
                        <a href="<?= site_url('compras/productos/editar/' . $producto['id']) ?>" class="btn btn-sm btn-link text-decoration-none text-muted">✏️ Editar producto</a>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>