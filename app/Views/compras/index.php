<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">🛒 Compras</h2>

<a href="<?= site_url('compras/supermercados/nuevo') ?>" class="btn btn-outline-success mb-4">+ Nuevo supermercado</a>

<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($supermercados as $s): ?>
        <div class="col d-flex">
            <a href="<?= site_url('compras/productos/' . $s['id']) ?>" class="text-decoration-none text-dark w-100 h-100">
                <div class="card shadow-sm w-100 h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title"><?= esc($s['nombre']) ?></h5>
                            <p class="card-text text-muted"><?= esc($s['descripcion'] ?? '') ?></p>
                        </div>
                        <div class="mt-3 text-end">
                            <span class="btn btn-sm btn-outline-primary">Ver productos y listas</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
