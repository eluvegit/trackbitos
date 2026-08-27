<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pedidos</strong>

    <button type="button" class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido">
        <i class="bi bi-plus-lg"></i> Nuevo pedido
    </button>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary" title="Piezas listas para imprimir">
        <i class="bi bi-grid-3x3-gap"></i> Galería
    </a>
    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary" title="Histórico de placas (guardadas y descargadas)">
        <i class="bi bi-printer"></i> Placas
    </a>
</h5>

<?php
/**
 * Alta manual (a diferencia de los que llegan solos desde sterclicks): nace
 * sin líneas, se rellena en la propia ficha con el mismo formulario que
 * cualquier otro pedido. Referencia externa es opcional a propósito — no
 * todo pedido de mano viene con un número que apuntar.
 */
?>
<div class="modal fade" id="modalNuevoPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/pedido') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pedido nuevo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Referencia (opcional)</label>
                <input type="text" name="referencia_externa" class="form-control form-control-sm mb-2"
                    placeholder="nº de pedido, si viene de fuera" maxlength="50">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace sin líneas — se añaden en la ficha del pedido, buscando cada pieza en el catálogo.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

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
