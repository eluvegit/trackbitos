<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    $titulos = [
        'impresas' => ['Caja de impresas', 'bi-box-seam', 'Recién impresas, pendientes de guardar en su hueco.'],
        'pedido'   => ['Caja de pedido', 'bi-bag', 'Apartadas para lo que ha pedido Esther: se las llevan o vuelven a su hueco.'],
    ];
    $negativas = 0;
    foreach ($cajas as $filas) { $negativas += count(array_filter($filas, static fn ($f) => $f['cantidad'] < 0)); }
?>

<div class="fv pv cj">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/ubicaciones') ?>">Ubicaciones</a><i class="bi bi-chevron-right"></i><span>Cajas</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/salidas') ?>" class="fv-gbtn" title="Albaranes de salida"><i class="bi bi-receipt"></i> Albaranes</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/ubicaciones/cajas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Cajas</h1>
        <div class="fv-meta">
            <?php foreach ($cajas as $caja => $filas): ?>
                <span><b><?= (int) array_sum(array_column($filas, 'cantidad')) ?></b> ud. en la <?= esc(mb_strtolower($titulos[$caja][0])) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if ($negativas > 0): ?>
        <div class="fv-aviso">
            <i class="bi bi-exclamation-triangle"></i>
            <span><b><?= $negativas ?> pieza<?= $negativas === 1 ? '' : 's' ?> en negativo</b>: salieron de la caja piezas cuya alta se quitó después. Al cuadrar, la caja queda a 0 y se restan de su hueco (el total no cambia).</span>
            <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/cuadrar') ?>" onsubmit="return confirm('¿Poner a 0 las piezas en negativo y restarlas de su hueco?');">
                <?= csrf_field() ?>
                <button type="submit" class="fv-btn fv-btn-sm"><i class="bi bi-check2-square"></i> Cuadrar</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="es-graficos">
        <?php foreach ($cajas as $caja => $filas): ?>
            <?php
                [$titulo, $icono, $ayuda] = $titulos[$caja];
                $conHueco = count(array_filter($filas, static fn ($f) => $f['hueco'] !== null && $f['cantidad'] > 0));
            ?>
            <section class="fv-panel">
                <div class="fv-ph">
                    <i class="bi <?= $icono ?> t3"></i><h3><?= esc($titulo) ?></h3><span class="cuenta"><?= (int) array_sum(array_column($filas, 'cantidad')) ?> ud.</span>
                    <?php if ($conHueco > 0): ?>
                        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar-todo') ?>" class="der"
                            onsubmit="return confirm('¿Guardar en su hueco todo lo de la <?= esc(mb_strtolower($titulo), 'js') ?> que ya tiene hueco?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                            <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri"><i class="bi bi-arrow-down-square"></i> Guardar todo</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p class="es-ayuda"><?= esc($ayuda) ?></p>
                <?php if ($filas === []): ?>
                    <p class="fv-vacio">Vacía.</p>
                <?php else: ?>
                    <div class="pv-lista">
                        <?php foreach ($filas as $f): ?>
                            <div class="pv-fila cj-fila">
                                <span class="pv-ico"><?php if ($f['imagen']): ?><img src="<?= esc($f['imagen'], 'attr') ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-image"></i><?php endif; ?></span>
                                <div class="pv-cuerpo">
                                    <a href="<?= site_url('piezas/existencias/' . (int) $f['id']) ?>" class="pv-nom"><?= esc($f['nombre']) ?></a>
                                    <div class="pv-sub">
                                        <?php if ($f['hueco'] !== null): ?>
                                            <span><i class="bi bi-geo-alt"></i> <span class="fv-mono"><?= esc($f['hueco']) ?></span></span>
                                        <?php else: ?>
                                            <span class="fv-estado e-warn">Sin hueco</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="pv-n<?= $f['cantidad'] < 0 ? ' e-bad' : '' ?>" <?= $f['cantidad'] < 0 ? 'title="Han salido más de las que entraron en esta caja"' : '' ?>><?= (int) $f['cantidad'] ?></span>
                                <?php if ($f['cantidad'] > 0): ?>
                                    <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar') ?>" class="cj-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                                        <input type="hidden" name="variante_id" value="<?= (int) $f['id'] ?>">
                                        <input type="number" name="cantidad" value="<?= (int) $f['cantidad'] ?>" min="1" max="<?= (int) $f['cantidad'] ?>" class="fv-input" title="Cuántas">
                                        <?php if ($f['hueco'] === null): ?>
                                            <select name="hueco_id" class="fv-input hueco" required>
                                                <option value="">Hueco…</option>
                                                <?php foreach ($huecos as $hid => $codigo): ?>
                                                    <option value="<?= (int) $hid ?>"><?= esc($codigo) ?><?= isset($huecosLibres[$hid]) ? ' (libre)' : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                        <button type="submit" class="fv-ibtn" title="Guardar en su hueco"><i class="bi bi-arrow-down-square"></i></button>
                                        <button type="submit" formaction="<?= site_url('piezas/ubicaciones/cajas/cambiar') ?>" formnovalidate class="fv-ibtn" title="Pasar a la caja de <?= $caja === 'impresas' ? 'pedido' : 'impresas' ?>"><i class="bi <?= $caja === 'impresas' ? 'bi-bag' : 'bi-box-seam' ?>"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->endSection() ?>
