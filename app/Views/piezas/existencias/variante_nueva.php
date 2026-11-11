<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    helper('piezas_stock');
    $idVar  = (int) $variante['id'];
    $nombre = trim(($familia['nombre'] ?? '') . ' ' . $variante['nombre']);
    $etiquetaMotivo = [
        'alta_manual' => 'Alta a mano',
        'baja_manual' => 'Baja a mano',
        'ajuste'      => 'Ajuste',
        'impresion'   => 'Impresión',
        'guardado'    => 'Guardado',
        'cambio_caja' => 'Cambio de caja',
        'salida'      => 'Salida',
    ];
    $tono = ['cero' => 'e-bad', 'bajo' => 'e-warn', 'ok' => 'e-ok'];
    $color = ['cero' => 'var(--fv-bad)', 'bajo' => 'var(--fv-warn)', 'ok' => 'var(--fv-ok)'];
    $rotulo = [
        'cero' => 'Sin existencias',
        'bajo' => 'Por debajo del mínimo',
        'ok'   => $minimo > 0 ? 'Por encima del mínimo' : 'Con existencias',
    ];
    $tope = max($minimo * 2, $stock, 1);
    $llenado = min(100, (int) round($stock / $tope * 100));
    $marca = $minimo > 0 ? min(100, (int) round($minimo / $tope * 100)) : null;
?>

<div class="fv pv exv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/existencias') ?>">Existencias</a><i class="bi bi-chevron-right"></i><span><?= esc($nombre) ?></span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/variante/' . $idVar) ?>" class="fv-gbtn" title="Ficha de la variante"><i class="bi bi-box-arrow-up-right"></i> Ficha</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/existencias/' . $idVar . '?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1><?= esc($nombre) ?></h1>
        <div class="fv-meta">
            <span class="fv-estado <?= $tono[$estado] ?>"><?= esc($rotulo[$estado]) ?></span>
            <?php if (!empty($variante['sku'])): ?><span class="fv-dot"></span><span class="fv-mono"><?= esc($variante['sku']) ?></span><?php endif; ?>
            <span class="fv-dot"></span>
            <?php if ($hueco): ?>
                <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['hueco']['id']) ?>"><i class="bi bi-geo-alt"></i> <span class="fv-mono"><?= esc($hueco['codigo']) ?></span></a>
            <?php else: ?>
                <span class="fv-estado e-warn">Sin hueco</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="al-grid">
        <div class="fv-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Stock</h3></div>
                <div class="exv-cab">
                    <?php if ($imagen): ?>
                        <a href="<?= esc($imagen['vista'], 'attr') ?>" class="exv-img" data-foto-grande title="Ver en grande"><img src="<?= esc($imagen['vista'], 'attr') ?>" alt="<?= esc($nombre, 'attr') ?>"></a>
                    <?php else: ?>
                        <a href="<?= site_url('piezas/variante/' . $idVar) ?>" class="exv-img vacia" title="Sin render: súbelo desde la ficha de la variante"><i class="bi bi-image"></i></a>
                    <?php endif; ?>
                <div class="fv-stock">
                    <div class="grande" style="color: <?= $color[$estado] ?>" title="Todas las unidades que existen de esta pieza"><?= (int) $stock ?><small>ud. en total</small></div>
                    <dl class="fv-kv">
                        <div><dt><?= $hueco ? 'En su hueco <span class="fv-mono">' . esc($hueco['codigo']) . '</span>' : 'Sin ubicación' ?></dt><dd><?= (int) $desglose['hueco'] ?></dd></div>
                        <div><dt>Caja de impresas</dt><dd><?= (int) $desglose['impresas'] ?></dd></div>
                        <div><dt>Caja de pedido</dt><dd><?= (int) $desglose['pedido'] ?></dd></div>
                        <div><dt>Mínimo</dt><dd><?= $minimo > 0 ? (int) $minimo : '—' ?></dd></div>
                    </dl>
                    <div class="fv-nivel" title="<?= $minimo > 0 ? 'La marca es el mínimo' : '' ?>"><i style="width: <?= $llenado ?>%; background: <?= $color[$estado] ?>"></i><?php if ($marca !== null): ?><b style="left: <?= $marca ?>%"></b><?php endif; ?></div>
                </div>
                </div>
                <div class="fv-duo">
                    <button type="button" class="fv-btn"<?= stock_mov_attrs($idVar, $nombre, $desglose, 'alta', null, (bool) $hueco) ?>><i class="bi bi-plus-lg e-ok"></i><span>Dar de alta<small>Entran piezas</small></span></button>
                    <button type="button" class="fv-btn"<?= stock_mov_attrs($idVar, $nombre, $desglose, 'baja', null, (bool) $hueco) ?>><i class="bi bi-dash-lg e-bad"></i><span>Dar de baja<small>Salen o se rompen</small></span></button>
                </div>
            </section>

            <section class="fv-panel">
                <div class="fv-ph"><i class="bi bi-bell t3"></i><h3>Aviso de mínimo</h3></div>
                <form method="post" action="<?= site_url('piezas/existencias/' . $idVar . '/minimo') ?>" class="exv-min" data-min-form>
                    <?= csrf_field() ?>
                    <span class="t3" style="font-size:13px">Avísame cuando baje de</span>
                    <div class="exv-paso">
                        <button type="button" class="fv-ibtn" data-min-step="-1" aria-label="Menos"><i class="bi bi-dash-lg"></i></button>
                        <input type="number" name="stock_minimo" min="0" step="1" value="<?= (int) $minimo ?>" class="fv-input" data-min-input>
                        <button type="button" class="fv-ibtn" data-min-step="1" aria-label="Más"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <button class="fv-btn fv-btn-sm fv-btn-pri" data-min-guardar disabled>Guardar</button>
                    <span class="exv-eco" data-min-eco><?= $minimo > 0 ? 'Ahora: ' . (int) $minimo : 'Sin mínimo — solo se avisa a cero' ?></span>
                </form>
            </section>

            <section class="fv-panel">
                <div class="fv-ph"><i class="bi bi-geo-alt t3"></i><h3>Dónde está</h3></div>
                <?php if (empty($huecosDisponibles)): ?>
                    <p class="fv-vacio">Sin hueco. Crea estuches y huecos en <a href="<?= site_url('piezas/ubicaciones') ?>" target="_blank" class="t2">Ubicaciones</a> para poder colocarla.</p>
                <?php else: ?>
                    <?php
                        $huecoActualId = $hueco ? (int) $hueco['hueco']['id'] : null;
                        $accion = $hueco
                            ? site_url('piezas/ubicaciones/huecos/' . $huecoActualId . '/mover-pieza')
                            : site_url('piezas/existencias/' . $idVar . '/asignar-sueltos');
                    ?>
                    <div class="fv-linea">
                        <?php if ($hueco): ?>
                            <a href="<?= site_url('piezas/ubicaciones/huecos/' . $huecoActualId) ?>" class="exv-hueco"><i class="bi bi-geo-alt"></i> <?= esc($hueco['codigo']) ?></a>
                        <?php else: ?>
                            <span class="fv-estado e-warn">Sin hueco<?= $desglose['hueco'] > 0 ? ' · ' . (int) $desglose['hueco'] . ' sin colocar' : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= $accion ?>" class="fv-campo">
                        <?= csrf_field() ?>
                        <?php if ($hueco): ?>
                            <input type="hidden" name="variante_id" value="<?= $idVar ?>">
                            <input type="hidden" name="volver" value="variante">
                        <?php endif; ?>
                        <select name="<?= $hueco ? 'destino_id' : 'hueco_id' ?>" required class="fv-input" data-hueco data-hueco-enviar data-hueco-para="<?= esc($nombre, 'attr') ?>"<?= $hueco ? ' data-hueco-actual="' . (int) $huecoActualId . '"' : '' ?>>
                            <option value=""><?= $hueco ? 'Mover a…' : 'Colocar en…' ?></option>
                            <?php $estucheActual = null; ?>
                            <?php foreach ($huecosDisponibles as $hd): ?>
                                <?php if ((int) $hd['hueco']['id'] === $huecoActualId) continue; ?>
                                <?php if ($estucheActual !== (int) $hd['estuche']['id']): ?>
                                    <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                                    <optgroup label="<?= esc($hd['estuche']['codigo']) ?>">
                                    <?php $estucheActual = (int) $hd['estuche']['id']; ?>
                                <?php endif; ?>
                                <option value="<?= (int) $hd['hueco']['id'] ?>"><?= esc($hd['codigo']) ?><?= $hd['libre'] ? ' (libre)' : '' ?></option>
                            <?php endforeach; ?>
                            <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                        </select>
                        <button type="submit" class="fv-btn <?= $hueco ? '' : 'fv-btn-pri' ?>"><?= $hueco ? 'Mover' : 'Colocar' ?></button>
                    </form>
                <?php endif; ?>
            </section>
        </div>

        <div class="fv-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Movimientos</h3><span class="cuenta"><?= count($historial) ?></span></div>
                <?php if (empty($historial)): ?>
                    <p class="fv-vacio">Sin movimientos todavía.</p>
                <?php else: ?>
                    <div class="pv-lista exv-movs">
                        <?php foreach ($historial as $m): ?>
                            <?php
                                $fecha = $m['actualizado_en'] ?: $m['creado_en'];
                                $esPlaca = $m['origen'] === 'placa';
                                $d = (int) $m['delta'];
                            ?>
                            <div class="pv-fila">
                                <span class="exv-delta <?= $d < 0 ? 'e-bad' : 'e-ok' ?>"><?= $d > 0 ? '+' : '' ?><?= $d ?></span>
                                <div class="pv-cuerpo">
                                    <span class="exv-tipo"><?= esc($etiquetaMotivo[$m['motivo']] ?? $m['motivo']) ?><?php if (!empty($m['caja'])): ?> <span class="t3">· caja <?= esc($m['caja']) ?></span><?php endif; ?></span>
                                    <div class="pv-sub">
                                        <span class="fv-mono"><?= esc($fecha ? date('d/m/Y H:i', strtotime($fecha)) : '—') ?></span>
                                        <?php if ($esPlaca && $m['placa_id']): ?>
                                            <a href="<?= site_url('piezas/placa/' . (int) $m['placa_id'] . '/bitacora/editar') ?>" class="t2"><i class="bi bi-journal-text"></i> Bitácora de la placa #<?= (int) $m['placa_id'] ?></a>
                                        <?php elseif (trim((string) ($m['nota'] ?? '')) !== ''): ?>
                                            <span class="nota t2" title="<?= esc($m['nota'], 'attr') ?>"><?= esc($m['nota']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!$esPlaca && !in_array($m['motivo'], ['guardado', 'cambio_caja', 'salida'], true)): ?>
                                    <form method="post" action="<?= site_url('piezas/existencias/movimiento/' . (int) $m['id'] . '/borrar') ?>" class="pv-acc"
                                        onsubmit="return confirm('¿Borrar este movimiento (<?= $d > 0 ? '+' : '' ?><?= $d ?>)? No se puede deshacer.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="variante_id" value="<?= $idVar ?>">
                                        <button type="submit" class="fv-ibtn peligro" title="Borrar movimiento"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="es-ayuda">Las líneas de <b class="t2">impresión</b> son un reflejo de la bitácora de su placa: si allí cambian las copias o las fallidas y se vuelve a dar de alta, esta misma línea se recuadra, no se añade otra.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<?= $this->include('piezas/_modal_mov_stock') ?>

<script>
    (function () {
        var form = document.querySelector('[data-min-form]');
        if (form) {
            var input   = form.querySelector('[data-min-input]');
            var guardar = form.querySelector('[data-min-guardar]');
            var eco     = form.querySelector('[data-min-eco]');
            var original = parseInt(input.value, 10) || 0;

            var refrescar = function () {
                var val = Math.max(0, parseInt(input.value, 10) || 0);
                var cambiado = val !== original;
                guardar.disabled = !cambiado;
                eco.textContent = cambiado
                    ? (val > 0 ? 'Nuevo mínimo: ' + val : 'Se quitará el mínimo')
                    : (original > 0 ? 'Ahora: ' + original : 'Sin mínimo — solo se avisa a cero');
            };

            form.querySelectorAll('[data-min-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var paso = parseInt(btn.dataset.minStep, 10);
                    input.value = Math.max(0, (parseInt(input.value, 10) || 0) + paso);
                    refrescar();
                });
            });
            input.addEventListener('input', refrescar);
        }
    })();
</script>

<?= $this->include('piezas/_foto_grande') ?>

<?= $this->endSection() ?>
