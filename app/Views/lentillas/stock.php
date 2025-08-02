<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-cart-plus fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Lentillas <span class="text-muted">/</span> <span class="text-secondary">Stock</span></h2>
</div>
<a href="<?= site_url('lentillas') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Lentillas</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success"><?= session('message') ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('lentillas/stock/actualizar') ?>">
    <?= csrf_field() ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="row">
                    <?php foreach ($items as $item):
                        $cantidad = (int) $item['cantidad'];
                        $unidadTexto = $cantidad === 1 ? 'unidad' : 'unidades';

                        $icon = match (strtolower($item['item'])) {
                            'lentilla izquierda' => '👁️',
                            'lentilla derecha'   => '👁️',
                            'líquido'            => '💧',
                            'estuche'            => '🧳',
                            default              => '📦',
                        };
                    ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100 border-0">
                                <div class="card-body text-center">
                                    <h3 class="card-title mb-3"><?= esc($item['item']) ?></h3>
                                    <div class="fs-2 fw-bold"><?= $cantidad ?></div>
                                    <div class="display-4 mb-2"><?= $icon ?></div>
                                    <div class="text-muted mb-3"><?= $unidadTexto ?></div>

                                    <div class="d-flex justify-content-center">
                                        <input
                                            type="number"
                                            name="items[<?= $item['id'] ?>]"
                                            class="form-control form-control-sm text-center"
                                            value="<?= $cantidad ?>"
                                            style="max-width: 80px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3">
        <button type="submit" class="btn btn-primary">Actualizar Inventario</button>
    </div>
</form>
                        

<?= $this->endSection() ?>