<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/pedidos') ?>" class="text-decoration-none text-muted fw-normal">Pedidos</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pedido #<?= (int) $pedido['id'] ?></strong>

    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Piezas listas para imprimir">
        <i class="bi bi-grid-3x3-gap"></i> Galería
    </a>
    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary" title="Histórico de placas (guardadas y descargadas)">
        <i class="bi bi-clock-history"></i> Placas
    </a>
    <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/cargar-placa') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-success" title="Añade a la placa actual el STL de cada pieza de este pedido">
            <i class="bi bi-file-earmark-arrow-down"></i> Cargar piezas a la placa
        </button>
    </form>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Origen: <?= esc($pedido['origen']) ?> · Creado: <?= esc($pedido['creado_en']) ?>
    <?php if ($pedido['notas']): ?> · Notas: <?= esc($pedido['notas']) ?><?php endif; ?>
</p>

<?php
    $etiquetas = ['nuevo' => 'Pendiente', 'en_produccion' => 'Produciendo', 'completado' => 'Hecho', 'cancelado' => 'Cancelado'];
    $colores   = ['nuevo' => 'primary', 'en_produccion' => 'warning', 'completado' => 'success', 'cancelado' => 'secondary'];
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($estados as $estado): ?>
        <?php $activo = $estado === $pedido['estado']; ?>
        <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/estado') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="estado" value="<?= esc($estado) ?>">
            <button type="submit" class="btn btn-sm rounded-pill <?= $activo ? 'btn-' . $colores[$estado] : 'btn-outline-' . $colores[$estado] ?>" <?= $activo ? 'disabled' : '' ?>>
                <?= $activo ? '<i class="bi bi-check-lg me-1"></i>' : '' ?><?= esc($etiquetas[$estado] ?? $estado) ?>
            </button>
        </form>
    <?php endforeach; ?>
</div>

<?php // Aparte y no con la utilidad table-success de Bootstrap: esa deja el fondo
      // en un verde lavado que combinado con los grises de "· variante", el SKU
      // y las notas se volvía casi ilegible. Aquí se fuerza texto oscuro sobre
      // verde sólido en toda la fila, botones incluidos. ?>
<style>
    .fila-completa, .fila-completa td { background-color: var(--bs-success) !important; color: #052e16 !important; }
    .fila-completa .text-muted,
    .fila-completa .badge { color: #052e16 !important; }
    .fila-completa .badge { background-color: rgba(5, 46, 22, .12) !important; border-color: rgba(5, 46, 22, .35) !important; }
    .fila-completa .btn-outline-secondary { color: #052e16 !important; border-color: rgba(5, 46, 22, .4) !important; }
    .fila-completa .btn-outline-primary { color: #052e16 !important; border-color: rgba(5, 46, 22, .4) !important; }
</style>

<table class="table table-sm align-middle">
    <thead>
        <tr>
            <th></th>
            <th>Pieza</th>
            <th>Cantidad</th>
            <?php // A mano, sin cuadrar contra ninguna placa (spec: una pieza puede
                  // salir mal aunque esté impresa, y eso es un juicio que no le
                  // corresponde adivinar al sistema) — solo un contador que subes tú. ?>
            <th>Completado</th>
            <th>Notas</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pedido['lineas'] as $linea): ?>
            <?php $completa = (int) $linea['cantidad_completada'] >= (int) $linea['cantidad']; ?>
            <tr class="<?= $completa ? 'fila-completa' : '' ?>">
                <td style="width: 34px;">
                    <?php if ($linea['foto']): ?>
                        <img src="<?= esc($linea['foto'], 'attr') ?>" alt="" loading="lazy" class="rounded border"
                            style="width: 34px; height: 34px; object-fit: contain;">
                    <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center rounded border text-body-tertiary"
                            style="width: 34px; height: 34px;"><i class="bi bi-box" style="font-size: .8rem;"></i></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($linea['nombreVariante']): ?>
                        <?= esc($linea['nombreFamilia']) ?> <span class="text-muted">· <?= esc($linea['nombreVariante']) ?></span>
                    <?php else: ?>
                        <span class="text-muted small fst-italic">pieza borrada</span>
                    <?php endif; ?>
                    <br>
                    <span class="badge border text-body-secondary font-monospace fw-normal"><?= esc($linea['sku']) ?></span>
                </td>
                <td><?= (int) $linea['cantidad'] ?></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delta" value="-1">
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Una menos"
                                <?= (int) $linea['cantidad_completada'] <= 0 ? 'disabled' : '' ?>>−</button>
                        </form>
                        <span class="small" style="min-width: 3em; text-align: center;">
                            <?= (int) $linea['cantidad_completada'] ?>/<?= (int) $linea['cantidad'] ?>
                            <?= $completa ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?>
                        </span>
                        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delta" value="1">
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Una más"
                                <?= $completa ? 'disabled' : '' ?>>+</button>
                        </form>
                    </div>
                </td>
                <td class="text-muted small"><?= esc($linea['notas'] ?? '') ?></td>
                <td>
                    <?php if ($linea['variante_id']): ?>
                        <a href="<?= site_url('piezas/variante/' . $linea['variante_id']) ?>" class="btn btn-sm btn-outline-primary">Ver pieza</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php // Qué placas se han marcado como salidas de este pedido (a mano, al pulsar
      // "Cargar piezas a la placa" arriba) — solo un listado, sin intentar cuadrar
      // qué cubre cada una: eso se sigue viendo a ojo abriendo la placa. ?>
<?php if (!empty($placas)): ?>
    <h6 class="mt-4 mb-2 d-flex align-items-center gap-2">
        <i class="bi bi-clock-history"></i> Placas de este pedido
        <span class="badge text-bg-secondary"><?= count($placas) ?></span>
    </h6>
    <ul class="list-group">
        <?php foreach ($placas as $placa): ?>
            <li class="list-group-item d-flex align-items-center gap-2">
                <a href="<?= site_url('piezas/placa/' . (int) $placa['id'] . '/bitacora') ?>" target="_blank" rel="noopener"
                    class="text-decoration-none flex-grow-1"><?= esc($placa['nombre']) ?></a>
                <span class="text-muted small"><?= esc(date('d/m/Y H:i', strtotime($placa['creado_en']))) ?></span>
                <?php if ($placa['impresa_en']): ?>
                    <span class="badge text-bg-success">Impresa</span>
                <?php elseif ($placa['descargada_en']): ?>
                    <span class="badge text-bg-primary">Lista para imprimir</span>
                <?php else: ?>
                    <span class="badge bg-body-secondary text-body-secondary border">Guardada</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?= $this->endSection() ?>
