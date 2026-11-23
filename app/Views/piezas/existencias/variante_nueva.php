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
            <?php if ($aviso = partes_aviso($partes)): ?><span class="fv-dot"></span><a href="#partes"><?= $aviso ?></a><?php endif; ?>
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

            <section class="fv-panel" id="partes">
                <div class="fv-ph"><i class="bi bi-puzzle t3"></i><h3>Partes</h3>
                    <?php if ($partes): ?><span class="cuenta"><?= (int) $partes['completas'] ?>/<?= (int) $partes['unidades'] ?> completas</span><?php endif; ?>
                </div>
                <?php if (!$partes): ?>
                    <p class="fv-vacio">Sin partes. Si se imprime en trozos (cabeza, torso, brazos…), añádelos para saber cuántas hay completas y qué imprimir.</p>
                <?php else: ?>
                    <?php if ($partes['imprimir']): ?>
                        <p class="pz-imprimir"><i class="bi bi-printer"></i> Imprimir para completar: <b><?= esc(implode(', ', $partes['imprimir'])) ?></b></p>
                    <?php elseif ($partes['unidades'] > 0): ?>
                        <p class="pz-imprimir ok"><i class="bi bi-check-circle"></i> Todas completas.</p>
                    <?php else: ?>
                        <p class="pz-imprimir t3">No hay unidades en stock.</p>
                    <?php endif; ?>
                    <?php if ($partes['sueltas']): ?>
                        <p class="pz-imprimir sueltas"><i class="bi bi-boxes"></i> Sueltas: <b><?= esc(implode(', ', $partes['sueltas'])) ?></b></p>
                    <?php endif; ?>
                    <?php if ($partes['montables'] > 0): ?>
                        <p class="pz-imprimir t3">Con lo suelto sale<?= $partes['montables'] > 1 ? 'n ' . (int) $partes['montables'] . ' unidades' : ' 1 unidad' ?>: se sumará<?= $partes['montables'] > 1 ? 'n' : '' ?> al stock en el próximo apunte.</p>
                    <?php endif; ?>
                    <div class="pv-lista">
                        <?php foreach ($partes['partes'] as $pa): ?>
                            <?php $s = (int) $pa['saldo']; ?>
                            <div class="pv-fila pz-parte">
                                <div class="pv-cuerpo">
                                    <span class="exv-tipo"><?= esc($pa['nombre']) ?><?= (int) $pa['por_unidad'] > 1 ? ' <span class="t3">×' . (int) $pa['por_unidad'] . '</span>' : '' ?></span>
                                    <div class="pv-sub">
                                        <span class="fv-mono"><?= (int) $pa['hay'] ?> hay</span>
                                        <?php if ($pa['faltan'] > 0): ?><span class="fv-estado e-bad">faltan <?= (int) $pa['faltan'] ?></span>
                                        <?php elseif ($pa['sobran'] > 0): ?><span class="fv-estado e-ok">sobran <?= (int) $pa['sobran'] ?></span>
                                        <?php else: ?><span class="t3">cuadra</span><?php endif; ?>
                                    </div>
                                    <form method="post" action="<?= site_url('piezas/existencias/partes/' . (int) $pa['id'] . '/movimiento') ?>" class="pz-mov">
                                        <?= csrf_field() ?>
                                        <input type="number" name="cantidad" min="1" value="1" class="fv-input" aria-label="Cantidad">
                                        <select name="motivo" class="fv-input" aria-label="Qué pasa">
                                            <option value="rota">Rota / falta (−)</option>
                                            <option value="suelta">Impresa suelta (+)</option>
                                            <option value="ajuste_mas">Ajuste (+)</option>
                                            <option value="ajuste_menos">Ajuste (−)</option>
                                        </select>
                                        <button class="fv-btn fv-btn-sm">Apuntar</button>
                                    </form>
                                </div>
                                <details class="pz-edit">
                                    <summary class="fv-ibtn" title="Editar parte"><i class="bi bi-pencil"></i></summary>
                                    <form method="post" action="<?= site_url('piezas/existencias/partes/' . (int) $pa['id']) ?>" class="pz-mov">
                                        <?= csrf_field() ?>
                                        <input type="text" name="nombre" value="<?= esc($pa['nombre'], 'attr') ?>" class="fv-input" maxlength="100" aria-label="Nombre">
                                        <input type="number" name="por_unidad" min="1" max="20" value="<?= (int) $pa['por_unidad'] ?>" class="fv-input" title="Cuántas lleva cada unidad">
                                        <button class="fv-btn fv-btn-sm">Guardar</button>
                                        <button class="fv-ibtn peligro" name="borrar" value="1" title="Quitar parte" onclick="return confirm('¿Quitar la parte «<?= esc($pa['nombre'], 'js') ?>» y su recuento?');"><i class="bi bi-trash"></i></button>
                                    </form>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= site_url('piezas/existencias/' . $idVar . '/partes') ?>" class="pz-mov pz-nueva">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" class="fv-input" maxlength="100" placeholder="+ Parte (cabeza, brazos…)" required>
                    <input type="number" name="por_unidad" min="1" max="20" value="1" class="fv-input" title="Cuántas lleva cada unidad (brazos: 2)">
                    <button class="fv-btn fv-btn-sm">Añadir</button>
                </form>
                <?php if (!empty($historialPartes)): ?>
                    <details class="fv-mas">
                        <summary><i class="bi bi-chevron-right ch"></i> Movimientos de partes (<?= count($historialPartes) ?>)</summary>
                        <div class="pv-lista exv-movs">
                            <?php foreach ($historialPartes as $m): ?>
                                <?php $d = (int) $m['delta']; $deBitacora = $m['placa_version_id'] !== null; ?>
                                <div class="pv-fila">
                                    <span class="exv-delta <?= $d < 0 ? 'e-bad' : 'e-ok' ?>"><?= $d > 0 ? '+' : '' ?><?= $d ?></span>
                                    <div class="pv-cuerpo">
                                        <span class="exv-tipo"><?= esc($m['parte']) ?> <span class="t3">· <?= esc(['rota' => 'rota', 'suelta' => 'impresa suelta', 'ajuste' => 'ajuste', 'placa' => 'bitácora'][$m['motivo']] ?? $m['motivo']) ?></span></span>
                                        <div class="pv-sub">
                                            <span class="fv-mono"><?= esc($m['creado_en'] ? date('d/m/Y H:i', strtotime($m['creado_en'])) : '—') ?></span>
                                            <?php if ($deBitacora): ?>
                                                <a href="<?= site_url('piezas/placa/' . (int) $m['placa_id'] . '/bitacora/editar') ?>" class="t2"><i class="bi bi-journal-text"></i> Placa #<?= (int) $m['placa_id'] ?></a>
                                                <?php if ($m['inventario_sincronizado_en'] === null): ?><span class="t3">(no cuenta: placa sin dar de alta)</span><?php endif; ?>
                                            <?php elseif (trim((string) $m['nota']) !== ''): ?>
                                                <span class="nota t2"><?= esc($m['nota']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!$deBitacora): ?>
                                        <form method="post" action="<?= site_url('piezas/existencias/partes/movimiento/' . (int) $m['id'] . '/borrar') ?>" class="pv-acc" onsubmit="return confirm('¿Borrar este movimiento de parte?');">
                                            <?= csrf_field() ?>
                                            <button class="fv-ibtn peligro" title="Borrar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
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

<style>
    .pz-imprimir { margin: 0 0 .5rem; font-size: .9rem; color: var(--fv-warn); }
    .pz-imprimir.ok, .pz-imprimir.sueltas { color: var(--fv-ok); }
    .pz-imprimir.t3 { color: inherit; }
    .pz-parte { align-items: flex-start; }
    .pz-mov { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; margin-top: .35rem; }
    .pz-mov input[type=number] { width: 4.2rem; }
    .pz-mov select { width: auto; flex: 1 1 9rem; }
    .pz-mov input[type=text] { flex: 1 1 8rem; }
    .pz-nueva { margin-top: .6rem; }
    .pz-edit > summary { list-style: none; cursor: pointer; }
    .pz-edit > summary::-webkit-details-marker { display: none; }
    .pz-edit[open] { flex-basis: 100%; }
</style>

<?= $this->include('piezas/_foto_grande') ?>

<?= $this->endSection() ?>
