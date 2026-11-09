<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
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
                    <div class="grande" style="color: <?= $color[$estado] ?>"><?= (int) $stock ?><small>ud.</small></div>
                    <dl class="fv-kv">
                        <div><dt>Mínimo</dt><dd><?= $minimo > 0 ? (int) $minimo : '—' ?></dd></div>
                        <div><dt>Hueco</dt><dd class="fv-mono"><?= $hueco ? esc($hueco['codigo']) : '—' ?></dd></div>
                    </dl>
                    <div class="fv-nivel" title="<?= $minimo > 0 ? 'La marca es el mínimo' : '' ?>"><i style="width: <?= $llenado ?>%; background: <?= $color[$estado] ?>"></i><?php if ($marca !== null): ?><b style="left: <?= $marca ?>%"></b><?php endif; ?></div>
                </div>
                </div>
                <div class="fv-duo">
                    <button type="button" class="fv-btn" data-bs-toggle="modal" data-bs-target="#modalMovimiento" data-sentido="alta"><i class="bi bi-plus-lg e-ok"></i><span>Dar de alta<small>Entran piezas</small></span></button>
                    <button type="button" class="fv-btn" data-bs-toggle="modal" data-bs-target="#modalMovimiento" data-sentido="baja"><i class="bi bi-dash-lg e-bad"></i><span>Dar de baja<small>Salen o se rompen</small></span></button>
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
                            <span class="fv-estado e-warn">Sin hueco<?= $stock > 0 ? ' · ' . (int) $stock . ' sin colocar' : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= $accion ?>" class="fv-campo">
                        <?= csrf_field() ?>
                        <?php if ($hueco): ?>
                            <input type="hidden" name="variante_id" value="<?= $idVar ?>">
                            <input type="hidden" name="volver" value="variante">
                        <?php endif; ?>
                        <select name="<?= $hueco ? 'destino_id' : 'hueco_id' ?>" required class="fv-input">
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

<div class="modal fade" id="modalMovimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/existencias/movimiento') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="variante_id" value="<?= $idVar ?>">
            <input type="hidden" name="sentido" value="alta" data-mov-sentido>
            <div class="modal-header">
                <h6 class="modal-title" data-mov-titulo>Dar de alta</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="number" name="cantidad" min="1" step="1" value="1" required
                        class="form-control" autocomplete="off">
                </div>
                <?php // El hueco solo tiene sentido al dar de alta (dónde se coloca lo
                      // nuevo). Al dar de baja no se pregunta: se descuenta del total y
                      // los huecos se quedan exactamente como estaban — no van de la
                      // mano con las bajas. ?>
                <div class="mb-3" data-mov-hueco-wrap>
                    <label class="form-label">Hueco (opcional)</label>
                    <?php if (empty($huecosDisponibles)): ?>
                        <select name="ubicacion_id" class="form-select" disabled>
                            <option value="">Sin huecos todavía</option>
                        </select>
                        <div class="form-text">
                            Crea estuches y huecos en <a href="<?= site_url('piezas/ubicaciones') ?>" target="_blank">Ubicaciones</a>.
                        </div>
                    <?php else: ?>
                        <select name="ubicacion_id" class="form-select" data-mov-hueco-select>
                            <option value="">Sin asignar</option>
                            <?php $estucheActual = null; ?>
                            <?php foreach ($huecosDisponibles as $hd): ?>
                                <?php if ($estucheActual !== (int) $hd['estuche']['id']): ?>
                                    <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                                    <optgroup label="<?= esc($hd['estuche']['codigo']) ?><?= $hd['estuche']['zona'] ? ' — ' . esc($hd['estuche']['zona']) : '' ?>">
                                    <?php $estucheActual = (int) $hd['estuche']['id']; ?>
                                <?php endif; ?>
                                <option value="<?= (int) $hd['hueco']['id'] ?>"><?= esc($hd['codigo']) ?><?= $hd['libre'] ? ' (libre)' : '' ?></option>
                            <?php endforeach; ?>
                            </optgroup>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="mb-1">
                    <label class="form-label">Motivo</label>
                    <div class="d-flex flex-wrap gap-1 mb-2" data-mov-sugerencias>
                        <?php foreach (['alta' => ['Recuento', 'De vuelta', 'Vuelve de pintura', 'Impresión sin placa', 'Reparada', 'Aparecida al ordenar'], 'baja' => ['Se lo llevó Esther', 'Recuento por descuadre', 'Rotura', 'Se lleva a pintura', 'Regalo', 'Prueba destructiva']] as $sentidoSug => $sugs): foreach ($sugs as $sug): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-mov-sugerencia="<?= $sentidoSug ?>"><?= esc($sug) ?></button>
                        <?php endforeach; endforeach; ?>
                    </div>
                    <textarea name="nota" rows="3" required class="form-control" data-mov-nota
                        placeholder="Por qué se da de alta / baja (rotura, regalo, recuento, prueba destructiva…)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary" data-mov-enviar>Registrar</button>
            </div>
        </form>
    </div>
</div>

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

        var modal = document.getElementById('modalMovimiento');
        if (!modal) return;

        var huecoWrap   = modal.querySelector('[data-mov-hueco-wrap]');
        var huecoSelect = modal.querySelector('[data-mov-hueco-select]');

        modal.addEventListener('show.bs.modal', function (ev) {
            var sentido = (ev.relatedTarget && ev.relatedTarget.dataset.sentido) || 'alta';
            var esAlta = sentido === 'alta';
            modal.querySelector('[data-mov-sentido]').value = sentido;
            modal.querySelector('[data-mov-titulo]').textContent = esAlta ? 'Dar de alta' : 'Dar de baja';
            var boton = modal.querySelector('[data-mov-enviar]');
            boton.textContent = esAlta ? 'Dar de alta' : 'Dar de baja';
            boton.classList.toggle('btn-success', esAlta);
            boton.classList.toggle('btn-danger', !esAlta);
            boton.classList.toggle('btn-primary', false);

            // El hueco solo aplica al dar de alta (dónde se coloca lo
            // nuevo). Al dar de baja se oculta y no se manda: se descuenta
            // del total y los huecos existentes se quedan tal cual.
            if (huecoWrap) huecoWrap.classList.toggle('d-none', !esAlta);
            if (huecoSelect) huecoSelect.disabled = !esAlta;
            modal.querySelectorAll('[data-mov-sugerencia]').forEach(function (b) { b.classList.toggle('d-none', b.dataset.movSugerencia !== sentido); });
        });

        var sugerencias = modal.querySelector('[data-mov-sugerencias]');
        var nota = modal.querySelector('[data-mov-nota]');
        modal.querySelectorAll('[data-mov-sugerencia]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                nota.value = btn.textContent.trim();
                nota.focus();
            });
        });
    })();
</script>

<?= $this->include('piezas/_foto_grande') ?>

<?= $this->endSection() ?>
