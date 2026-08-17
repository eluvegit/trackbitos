<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-grid-3x3-gap text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Galería</strong>

    <?php if (!empty($carrito)): ?>
        <div class="ms-auto d-flex gap-2">
            <form method="post" action="<?= site_url('piezas/carrito/vaciar') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary"
                    onclick="return confirm('¿Vaciar la placa?');">Vaciar placa</button>
            </form>
            <a href="<?= site_url('piezas/carrito/descargar') ?>" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-zip"></i> Descargar placa (<?= count($carrito) ?>)
            </a>
        </div>
    <?php endif; ?>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Solo piezas con versión validada. Añade a la placa las que quieras imprimir juntas y descarga
    todos los STL de golpe en un .zip para el laminador.
</p>

<?php if (empty($piezas)): ?>
    <p class="text-muted">Todavía no hay ninguna versión validada. En cuanto valides una aparecerá aquí.</p>
<?php else: ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
        <?php foreach ($piezas as $p): ?>
            <?php
                $variante = $p['variante'];
                $validada = $p['validada'];
                $enCarrito = in_array((int) $validada['id'], $carrito, true);
                $tieneStl  = !empty($validada['ruta_stl']);
            ?>
            <div class="col">
                <div class="card shadow-sm h-100">
                    <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>">
                        <?php if ($p['miniatura']): ?>
                            <img src="<?= $p['miniatura'] ?>" class="card-img-top" style="aspect-ratio: 1; object-fit: cover;"
                                alt="<?= esc($variante['nombre']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-body-secondary text-muted"
                                style="aspect-ratio: 1;">
                                <i class="bi bi-box" style="font-size: 2rem;"></i>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="card-body p-2">
                        <div class="small fw-semibold text-truncate">
                            <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>"
                                class="text-decoration-none text-body"><?= esc($variante['nombre']) ?></a>
                        </div>
                        <div class="text-muted small">
                            v<?= sprintf('%03d', (int) $validada['numero']) ?>
                            <?php if (!empty($variante['sku'])): ?>
                                · <?= esc($variante['sku']) ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!$tieneStl): ?>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-exclamation-circle"></i> sin STL
                            </div>
                        <?php elseif ($enCarrito): ?>
                            <form method="post" action="<?= site_url('piezas/carrito/quitar/' . (int) $validada['id']) ?>" class="mt-1">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-success w-100 py-0">
                                    <i class="bi bi-check-lg"></i> En la placa
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= site_url('piezas/carrito/agregar/' . (int) $validada['id']) ?>" class="mt-1">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-primary w-100 py-0">
                                    <i class="bi bi-plus-lg"></i> Añadir a la placa
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
