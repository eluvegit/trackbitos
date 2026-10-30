<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
    $titulos = [
        'impresas' => ['Caja de impresas', 'bi-box-seam', 'Recién impresas, pendientes de guardar en su hueco.'],
        'pedido'   => ['Caja de pedido', 'bi-bag', 'Apartadas para lo que ha pedido Esther: se las llevan o vuelven a su hueco.'],
    ];
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-box-seam text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="text-decoration-none text-muted fw-normal">Ubicaciones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Cajas</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($cajas as $caja => $filas): ?>
        <?php
            [$titulo, $icono, $ayuda] = $titulos[$caja];
            $total = array_sum(array_column($filas, 'cantidad'));
            $conHueco = count(array_filter($filas, static fn ($f) => $f['hueco'] !== null && $f['cantidad'] > 0));
        ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi <?= $icono ?>"></i>
                    <strong><?= esc($titulo) ?></strong>
                    <span class="badge rounded-pill text-bg-secondary"><?= (int) $total ?> uds</span>
                    <?php if ($conHueco > 0): ?>
                        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar-todo') ?>" class="ms-auto"
                            onsubmit="return confirm('¿Guardar en su hueco todo lo de la <?= esc(mb_strtolower($titulo), 'js') ?> que ya tiene hueco?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-arrow-down-square"></i> Guardar todo</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <p class="text-muted small px-3 pt-2 mb-2"><?= esc($ayuda) ?></p>
                    <?php if ($filas === []): ?>
                        <p class="text-muted small px-3">Vacía.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($filas as $f): ?>
                                <li class="list-group-item d-flex align-items-center gap-2 flex-wrap">
                                    <?php if ($f['imagen']): ?>
                                        <img src="<?= esc($f['imagen'], 'attr') ?>" alt="" width="40" height="40" class="rounded object-fit-cover">
                                    <?php else: ?>
                                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-body-secondary text-muted" style="width:40px;height:40px;"><i class="bi bi-image"></i></span>
                                    <?php endif; ?>
                                    <div class="flex-grow-1" style="min-width: 8rem;">
                                        <a href="<?= site_url('piezas/existencias/' . (int) $f['id']) ?>" class="text-decoration-none"><?= esc($f['nombre']) ?></a>
                                        <div class="small">
                                            <?php if ($f['hueco'] !== null): ?>
                                                <span class="text-muted"><i class="bi bi-geo-alt"></i> <?= esc($f['hueco']) ?></span>
                                            <?php else: ?>
                                                <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Sin hueco</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge <?= $f['cantidad'] < 0 ? 'text-bg-danger' : 'text-bg-light border' ?>"
                                        <?= $f['cantidad'] < 0 ? 'title="Han salido más de las que entraron en esta caja"' : '' ?>>
                                        <?= (int) $f['cantidad'] ?>
                                    </span>
                                    <?php if ($f['cantidad'] > 0): ?>
                                        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar') ?>" class="d-flex gap-1 align-items-center">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                                            <input type="hidden" name="variante_id" value="<?= (int) $f['id'] ?>">
                                            <input type="number" name="cantidad" value="<?= (int) $f['cantidad'] ?>" min="1" max="<?= (int) $f['cantidad'] ?>"
                                                class="form-control form-control-sm" style="width: 4.5rem;">
                                            <?php if ($f['hueco'] === null): ?>
                                                <select name="hueco_id" class="form-select form-select-sm" style="width: 7rem;" required>
                                                    <option value="">Hueco…</option>
                                                    <?php foreach ($huecos as $hid => $codigo): ?>
                                                        <option value="<?= (int) $hid ?>"><?= esc($codigo) ?><?= isset($huecosLibres[$hid]) ? ' (libre)' : '' ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar en su hueco">
                                                <i class="bi bi-arrow-down-square"></i>
                                            </button>
                                            <button type="submit" formaction="<?= site_url('piezas/ubicaciones/cajas/cambiar') ?>" formnovalidate
                                                class="btn btn-sm btn-outline-secondary" title="Pasar a la caja de <?= $caja === 'impresas' ? 'pedido' : 'impresas' ?>">
                                                <i class="bi <?= $caja === 'impresas' ? 'bi-bag' : 'bi-box-seam' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
