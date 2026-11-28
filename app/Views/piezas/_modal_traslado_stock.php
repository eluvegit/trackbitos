<?php
/**
 * Modal común para mover unidades entre el hueco de una pieza y las cajas
 * de impresas / pedido, sin cambiar el total. Lo abre cualquier botón con
 * los atributos de stock_traslado_attrs() (helper piezas_stock).
 *
 * Va a piezas/existencias/traslado (ExistenciasController::traslado), que
 * usa los mismos traslados que la pantalla Cajas, y vuelve a la pantalla
 * de origen. Si la pieza no tiene hueco y van a él, se elige uno de
 * $huecosDisponibles.
 */
$huecosDisponibles = $huecosDisponibles ?? [];
$consulta = (string) service('request')->getUri()->getQuery();
$movVolver = $movVolver ?? (ltrim(uri_string(), '/') . ($consulta !== '' ? '?' . $consulta : ''));
?>
<div class="modal fade" id="modalTrasladoStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/existencias/traslado') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="variante_id" data-mt-variante>
            <input type="hidden" name="volver" value="<?= esc($movVolver, 'attr') ?>">
            <div class="modal-header">
                <h6 class="modal-title">Mover · <span data-mt-nombre></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary mb-3">El total no cambia: solo dónde están.</p>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col">
                        <label class="form-label small mb-1">De</label>
                        <select name="de" class="form-select" data-mt-de>
                            <option value="hueco"></option>
                            <option value="impresas"></option>
                            <option value="pedido"></option>
                        </select>
                    </div>
                    <div class="col-auto pb-2 text-body-secondary"><i class="bi bi-arrow-right"></i></div>
                    <div class="col">
                        <label class="form-label small mb-1">A</label>
                        <select name="a" class="form-select" data-mt-a>
                            <option value="hueco"></option>
                            <option value="impresas"></option>
                            <option value="pedido"></option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Cantidad</label>
                    <div class="input-group">
                        <input type="number" name="cantidad" min="1" step="1" value="1" required class="form-control" autocomplete="off" data-mt-cantidad>
                        <button type="button" class="btn btn-outline-secondary" data-mt-todas>Todas</button>
                    </div>
                </div>
                <?php if ($huecosDisponibles !== []): ?>
                    <div class="mb-1" data-mt-hueco-wrap>
                        <label class="form-label small mb-1">Hueco</label>
                        <select name="ubicacion_id" class="form-select" data-mt-hueco data-hueco>
                            <option value="">Elige hueco…</option>
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
                        <div class="form-text">La pieza no tiene hueco: este pasa a ser el suyo.</div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary" data-mt-enviar>Mover</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('modalTrasladoStock');
    if (!modal) return;
    var q = function (s) { return modal.querySelector(s); };
    var de = q('[data-mt-de]'), a = q('[data-mt-a]'), cantidad = q('[data-mt-cantidad]'),
        huecoWrap = q('[data-mt-hueco-wrap]'), hueco = q('[data-mt-hueco]'), enviar = q('[data-mt-enviar]');
    var hay = { hueco: 0, impresas: 0, pedido: 0 };
    var codigo = '';

    function texto(sitio) {
        return sitio === 'hueco' ? (codigo ? 'Su hueco ' + codigo : 'Sin ubicación / hueco') : (sitio === 'impresas' ? 'Caja de impresas' : 'Caja de pedido');
    }

    // El destino no puede ser el origen; si coincide, el primero que quede.
    function ajustar() {
        Array.prototype.forEach.call(a.options, function (o) { o.disabled = o.value === de.value; });
        if (a.value === de.value) {
            a.value = de.value === 'hueco' ? 'impresas' : 'hueco';
        }
        cantidad.max = Math.max(1, hay[de.value]);
        if ((parseInt(cantidad.value, 10) || 0) > hay[de.value]) cantidad.value = Math.max(1, hay[de.value]);
        q('[data-mt-todas]').textContent = 'Todas (' + hay[de.value] + ')';

        var pedir = a.value === 'hueco' && !codigo;
        if (huecoWrap) {
            huecoWrap.classList.toggle('d-none', !pedir);
            hueco.disabled = !pedir;
            hueco.required = pedir;
        }
        enviar.disabled = hay[de.value] < 1 || (pedir && !huecoWrap);
    }
    de.addEventListener('change', ajustar);
    a.addEventListener('change', ajustar);
    q('[data-mt-todas]').addEventListener('click', function () { cantidad.value = Math.max(1, hay[de.value]); });

    modal.addEventListener('show.bs.modal', function (ev) {
        var d = (ev.relatedTarget && ev.relatedTarget.dataset) || {};
        var num = function (v) { return parseInt(v, 10) || 0; };
        hay = { hueco: num(d.hueco), impresas: num(d.impresas), pedido: num(d.pedido) };
        codigo = d.codigoHueco || '';

        q('[data-mt-variante]').value = d.variante || '';
        q('[data-mt-nombre]').textContent = d.nombre || '';

        Array.prototype.forEach.call(de.options, function (o) {
            o.textContent = texto(o.value) + ' · ' + hay[o.value] + ' ud.';
            o.disabled = hay[o.value] < 1;
        });
        Array.prototype.forEach.call(a.options, function (o) { o.textContent = texto(o.value); });

        // Origen: el que pida el botón; si no, la caja con unidades (lo
        // normal es guardar en el hueco lo que ha llegado a una caja).
        var pre = d.de && hay[d.de] > 0 ? d.de
            : (hay.pedido > 0 ? 'pedido' : (hay.impresas > 0 ? 'impresas' : 'hueco'));
        de.value = pre;
        a.value = pre === 'hueco' ? 'pedido' : 'hueco';
        cantidad.value = 1;
        if (hueco) hueco.value = '';
        ajustar();
    });
    modal.addEventListener('shown.bs.modal', function () { cantidad.select(); });
})();
</script>
