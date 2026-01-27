<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">

    <i class="bi bi-cart3 text-primary"></i>

    <a href="<?= site_url('compras/supermercados') ?>"
        class="text-decoration-none text-muted fw-normal">
        Compras
    </a>

    <span class="text-muted">/</span>

    <strong class="fw-semibold">
        <?= esc($supermercado_nombre) ?>
    </strong>

    <a href="<?= site_url('compras/supermercados/editar/' . $supermercado_id) ?>"
        class="text-decoration-none ms-1 text-secondary"
        title="Editar supermercado">
        <i class="bi bi-pencil-square fs-6"></i>
    </a>
</h5>

<!-- Accesos rápidos a listas -->
<div class="row row-cols-2 g-2 mb-3">

    <div class="col d-flex">
        <a href="<?= site_url('compras/' . $supermercado_id . '/faltantes') ?>"
            class="text-decoration-none text-dark w-100">

            <div class="">
                <div class="card shadow-sm border-warning border-2 
                            d-flex align-items-center justify-content-center text-center p-2">
                    <div>
                        <div class="fw-semibold small"><i class="bi bi-pencil-square fs-6 text-warning mb-1"></i>
                        FALTA</div>
                    </div>

                </div>
            </div>

        </a>
    </div>

    <div class="col d-flex">
        <a href="<?= site_url('compras/' . $supermercado_id . '/comprados') ?>"
            class="text-decoration-none text-dark w-100">

            <div class="">
                <div class="card shadow-sm border-success border-2 
                            d-flex align-items-center justify-content-center text-center p-2">
                    <div>
                        <div class="fw-semibold small"><i class="bi bi-cart-check fs-6 text-success mb-1"></i>
                        COMPRAR</div>
                    </div>

                </div>
            </div>

        </a>
    </div>

</div>



<!-- Formulario para nuevo producto -->
<div class="card mb-3">
    <div class="card-body p-2">

        <form action="<?= site_url('compras/productos/nuevo') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">

            <div class="row g-1 align-items-end">

                <div class="col-6 col-md-4">
                    <label for="nombre" class="form-label small mb-1">Nombre</label>
                    <input type="text" name="nombre" id="nombre" 
                           class="form-control form-control-sm" required>
                </div>

                <div class="col-6 col-md-5">
                    <label for="imagen" class="form-label small mb-1">Imagen</label>
                    <input type="url" name="imagen" id="imagen" 
                           class="form-control form-control-sm">
                </div>

                <div class="col-12 col-md-3">
                    <button type="submit" 
                            class="btn btn-success btn-sm w-100">
                        + Añadir
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

<!-- Lista de productos -->
<div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-2">
    <?php foreach ($productos as $producto): ?>
        <div class="col d-flex h-100">
            <div class="card shadow-sm w-100 small d-flex flex-column h-100">

                <?php if (!empty($producto['imagen'])): ?>
                    <img src="<?= esc($producto['imagen']) ?>"
                         class="card-img-top"
                         style="object-fit: cover; height: 110px;">
                <?php endif; ?>

                <div class="card-body p-2 d-flex flex-column justify-content-between">

                    <h6 class="card-title mb-1">
                        <?= esc($producto['nombre']) ?>
                    </h6>

                    <div class="mb-1">
                        <?php if ($producto['faltante']): ?>
                            <span class="badge bg-warning text-dark">FALTA</span>
                        <?php endif; ?>
                        <?php if ($producto['comprado']): ?>
                            <span class="badge bg-success">OK</span>
                        <?php endif; ?>
                    </div>

                    <div class="text-end">
                        <a href="<?= site_url('compras/productos/editar/' . $producto['id']) ?>"
                           class="text-decoration-none text-muted small">
                           ✏️
                        </a>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>



<?= $this->endSection() ?>