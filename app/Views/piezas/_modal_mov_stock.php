<?php
/**
 * Modal común de alta / baja de existencias, el mismo en todas las pantallas
 * de Piezas. Lo abre cualquier botón con los atributos de stock_mov_attrs()
 * (helper piezas_stock): pieza, sentido y cuánto hay en cada sitio.
 *
 * Una baja elige de dónde sale (hueco / caja de impresas / caja de pedido),
 * con el hueco preseleccionado. Un alta va al hueco de la pieza; si no tiene
 * y la vista pasa $huecosDisponibles, se puede elegir uno ahí mismo.
 *
 * Al enviar vuelve a la pantalla desde la que se abrió (campo 'volver',
 * validado en ExistenciasController::movimiento()).
 */
$huecosDisponibles = $huecosDisponibles ?? [];
$consulta = (string) service('request')->getUri()->getQuery();
$movVolver = $movVolver ?? (ltrim(uri_string(), '/') . ($consulta !== '' ? '?' . $consulta : ''));
?>
<?= $this->include('piezas/_stock_css') ?>
<div class="modal fade" id="modalMovStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/existencias/movimiento') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="variante_id" data-ms-variante>
            <input type="hidden" name="sentido" value="alta" data-ms-sentido>
            <input type="hidden" name="volver" value="<?= esc($movVolver, 'attr') ?>">
            <div class="modal-header">
                <h6 class="modal-title"><span data-ms-titulo>Dar de alta</span> · <span data-ms-nombre></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary mb-3">
                    Ahora hay <b class="text-body" data-ms-total></b> en total<span data-ms-reparto></span>.
                </p>
                <div class="mb-3">
                    <label class="form-label small mb-1">Cantidad</label>
                    <input type="number" name="cantidad" min="1" step="1" value="1" required
                        class="form-control" autocomplete="off" data-ms-cantidad>
                </div>
                <div class="mb-3" data-ms-origen-wrap>
                    <label class="form-label small mb-1">De dónde salen</label>
                    <select name="origen" class="form-select" data-ms-origen>
                        <option value="hueco"></option>
                        <option value="impresas"></option>
                        <option value="pedido"></option>
                    </select>
                </div>
                <?php if ($huecosDisponibles !== []): ?>
                    <div class="mb-3" data-ms-hueco-wrap>
                        <label class="form-label small mb-1">Hueco (opcional)</label>
                        <select name="ubicacion_id" class="form-select" data-ms-hueco data-hueco>
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
                        <div class="form-text">Pasa a ser el hueco de la pieza.</div>
                    </div>
                <?php endif; ?>
                <div class="mb-1">
                    <label class="form-label small mb-1">Motivo</label>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach (['alta' => ['Recuento', 'De vuelta', 'Vuelve de pintura', 'Impresión sin placa', 'Reparada', 'Aparecida al ordenar'], 'baja' => ['Se lo llevó Esther', 'Recuento por descuadre', 'Rotura', 'Se lleva a pintura', 'Regalo', 'Prueba destructiva']] as $sentidoSug => $sugs): foreach ($sugs as $sug): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-ms-sugerencia="<?= $sentidoSug ?>"><?= esc($sug) ?></button>
                        <?php endforeach; endforeach; ?>
                    </div>
                    <textarea name="nota" rows="3" required class="form-control" data-ms-nota
                        placeholder="Por qué se da de alta / baja (rotura, regalo, recuento, prueba destructiva…)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-success" data-ms-enviar>Dar de alta</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('modalMovStock');
    if (!modal) return;
    var q = function (s) { return modal.querySelector(s); };
    var cantidad = q('[data-ms-cantidad]'), origen = q('[data-ms-origen]'), origenWrap = q('[data-ms-origen-wrap]'),
        huecoWrap = q('[data-ms-hueco-wrap]'), hueco = q('[data-ms-hueco]'), nota = q('[data-ms-nota]'), enviar = q('[data-ms-enviar]');
    var hay = { hueco: 0, impresas: 0, pedido: 0 };
    var esBaja = false;

    // En una caja solo se puede dar de baja lo que hay; el hueco no se
    // limita (un recuento puede descubrir que había menos de lo apuntado).
    function topar() {
        if (esBaja && origen.value !== 'hueco') { cantidad.max = hay[origen.value]; } else { cantidad.removeAttribute('max'); }
    }
    origen.addEventListener('change', topar);

    modal.addEventListener('show.bs.modal', function (ev) {
        var d = (ev.relatedTarget && ev.relatedTarget.dataset) || {};
        var num = function (v) { return parseInt(v, 10) || 0; };
        esBaja = d.sentido === 'baja';
        hay = { hueco: num(d.hueco), impresas: num(d.impresas), pedido: num(d.pedido) };
        var tieneHueco = d.tieneHueco !== '0';
        var nombreHueco = tieneHueco ? 'en su hueco' : 'sin ubicación';

        q('[data-ms-variante]').value = d.variante || '';
        q('[data-ms-sentido]').value = esBaja ? 'baja' : 'alta';
        q('[data-ms-titulo]').textContent = esBaja ? 'Dar de baja' : 'Dar de alta';
        q('[data-ms-nombre]').textContent = d.nombre || '';
        q('[data-ms-total]').textContent = num(d.total);

        var partes = [];
        if (hay.hueco) partes.push(hay.hueco + ' ' + nombreHueco);
        if (hay.impresas) partes.push(hay.impresas + ' en la caja de impresas');
        if (hay.pedido) partes.push(hay.pedido + ' en la caja de pedido');
        q('[data-ms-reparto]').textContent = partes.length ? ': ' + partes.join(', ') : '';

        enviar.textContent = esBaja ? 'Dar de baja' : 'Dar de alta';
        enviar.classList.toggle('btn-success', !esBaja);
        enviar.classList.toggle('btn-danger', esBaja);

        // Origen de la baja: el hueco por defecto; la caja si la pantalla
        // lo pide (Cajas) o si es lo único donde quedan unidades.
        origenWrap.classList.toggle('d-none', !esBaja);
        origen.disabled = !esBaja;
        var textos = { hueco: (tieneHueco ? 'Su hueco' : 'Sin ubicación'), impresas: 'Caja de impresas', pedido: 'Caja de pedido' };
        Array.prototype.forEach.call(origen.options, function (o) {
            o.textContent = textos[o.value] + ' · ' + hay[o.value] + ' ud.';
            o.disabled = o.value !== 'hueco' && hay[o.value] < 1;
        });
        var pre = 'hueco';
        if (d.origen && d.origen !== 'hueco' && hay[d.origen] > 0) { pre = d.origen; }
        else if (hay.hueco < 1) { pre = hay.impresas > 0 ? 'impresas' : (hay.pedido > 0 ? 'pedido' : 'hueco'); }
        origen.value = pre;

        // Elegir hueco solo al dar de alta una pieza que no tiene ninguno.
        if (huecoWrap) {
            var pedir = !esBaja && !tieneHueco;
            huecoWrap.classList.toggle('d-none', !pedir);
            hueco.disabled = !pedir;
            hueco.value = '';
        }

        cantidad.value = 1;
        topar();
        nota.value = '';
        modal.querySelectorAll('[data-ms-sugerencia]').forEach(function (b) {
            b.classList.toggle('d-none', b.dataset.msSugerencia !== (esBaja ? 'baja' : 'alta'));
        });
    });
    modal.addEventListener('shown.bs.modal', function () { cantidad.select(); });

    modal.querySelectorAll('[data-ms-sugerencia]').forEach(function (b) {
        b.addEventListener('click', function () { nota.value = b.textContent.trim(); nota.focus(); });
    });
})();
</script>
