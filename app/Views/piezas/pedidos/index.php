<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pedidos</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php
    $coloresColumna = [
        'nuevo'         => 'primary',
        'en_produccion' => 'warning',
        'completado'    => 'success',
        'cancelado'     => 'secondary',
    ];
?>

<div class="d-flex flex-wrap gap-2 mb-3" id="filtro-estados">
    <button type="button" class="btn btn-sm rounded-pill btn-dark" data-filtro="">
        Todos
    </button>
    <?php foreach ($columnas as $estado => $columna): ?>
        <button type="button" class="btn btn-sm rounded-pill btn-outline-<?= $coloresColumna[$estado] ?>" data-filtro="<?= esc($estado) ?>">
            <?= esc($columna['titulo']) ?> <span class="badge bg-body-secondary text-body-secondary border"><?= count($columna['pedidos']) ?></span>
        </button>
    <?php endforeach; ?>
</div>

<div class="row g-3" id="tablero-pedidos">
    <?php foreach ($columnas as $estado => $columna): ?>
        <div class="col-12 col-md-6 col-xl-3 columna-pedidos" data-columna="<?= esc($estado) ?>">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge text-bg-<?= $coloresColumna[$estado] ?>">&nbsp;</span>
                <span class="fw-semibold"><?= esc($columna['titulo']) ?></span>
                <span class="badge bg-body-secondary text-body-secondary border ms-auto"><?= count($columna['pedidos']) ?></span>
            </div>

            <?php if (empty($columna['pedidos'])): ?>
                <p class="text-muted small">Nada aquí.</p>
            <?php endif; ?>

            <div class="d-flex flex-column gap-2">
                <?php foreach ($columna['pedidos'] as $pedido): ?>
                    <?php $fecha = strtotime($pedido['creado_en']); ?>
                    <a href="<?= site_url('piezas/pedido/' . $pedido['id']) ?>" class="card shadow-sm text-decoration-none text-body"
                        style="cursor: pointer" title="Ver pedido #<?= $pedido['id'] ?>">
                        <?php if ($pedido['fotos']): ?>
                            <div class="d-flex" style="gap: 2px; height: 64px; overflow: hidden; background: rgba(127,127,127,.15);">
                                <?php foreach (array_slice($pedido['fotos'], 0, 4) as $foto): ?>
                                    <img src="<?= $foto ?>" loading="lazy" alt=""
                                        style="flex: 1 1 0; min-width: 0; height: 100%; object-fit: cover; display: block;">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <div class="small fw-semibold">Pedido #<?= (int) $pedido['id'] ?></div>
                            <div class="d-flex align-items-center gap-2 text-muted" style="font-size: .75rem;">
                                <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
                                <span class="ms-auto"><?= $pedido['numLineas'] ?> pieza<?= $pedido['numLineas'] === 1 ? '' : 's' ?> · <?= $pedido['totalPiezas'] ?> ud.</span>
                            </div>
                            <?php if ($pedido['notas']): ?>
                                <div class="text-muted text-truncate mt-1" style="font-size: .75rem;" title="<?= esc($pedido['notas'], 'attr') ?>">
                                    <?= esc($pedido['notas']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const botones = Array.from(document.querySelectorAll('#filtro-estados [data-filtro]'));
        const columnas = Array.from(document.querySelectorAll('#tablero-pedidos .columna-pedidos'));

        function aplicar(filtro) {
            columnas.forEach(function (col) {
                col.classList.toggle('d-none', filtro !== '' && col.dataset.columna !== filtro);
            });
            botones.forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.filtro === filtro);
            });
        }

        botones.forEach(function (btn) {
            btn.addEventListener('click', function () { aplicar(btn.dataset.filtro); });
        });

        aplicar('');
    });
</script>

<?= $this->endSection() ?>
