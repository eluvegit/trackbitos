<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">

    <i class="bi bi-cart3 text-primary"></i>

    <span class="text-muted fw-normal">
        Compras
    </span>

    <span class="text-muted">/</span>

    <strong class="fw-semibold">
        Supermercados
    </strong>

    <a href="<?= site_url('compras/supermercados/nuevo') ?>"
        class="text-decoration-none ms-1 text-success"
        title="Nuevo supermercado">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>

</h5>

<div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-3">
    <?php foreach ($supermercados as $s): ?>
        <div class="col d-flex h-100">
            <a href="<?= site_url('compras/productos/' . $s['id']) ?>"
               class="text-decoration-none text-dark w-100 d-flex">
                <div class="card shadow-sm w-100 text-center d-flex flex-column justify-content-center" style="min-height: 140px;">
                    <i class="bi bi-cart fs-3 text-primary mb-2"></i>
                    <h6 class="card-title mb-1"><?= esc($s['nombre']) ?></h6>
                    <p class="d-none d-md-block small text-muted mb-0"><?= esc($s['descripcion'] ?? '') ?></p>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
