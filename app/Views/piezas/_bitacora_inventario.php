<?php
/**
 * Panel de inventario + "dónde colocar esto" de la bitácora de una placa.
 * Vista propia (antes vivía embebida en _bitacora_form.php) para poder
 * repintarla entera por AJAX tras dar de alta/quitar del inventario o fijar
 * un hueco, sin recargar la página — recargar aquí desplazaba la pantalla al
 * principio en cada acción, un coñazo con lo larga que es la bitácora.
 *
 * data-url-huecos-base: base para el enlace del badge de cada hueco, que el
 * JS construye a mano tras fijar/asignar sin tener que repintar la fila
 * entera desde el servidor.
 */
$idPlaca = (int) $placa['id'];
$colocacion = $colocacion ?? [];
$colocacionHuecos = $colocacionHuecos ?? [];
?>
<div data-panel-inventario data-placa="<?= $idPlaca ?>"
    data-url-huecos-base="<?= site_url('piezas/ubicaciones/huecos') ?>">

    <?php // Alta en existencias (fase 60): da de alta / recuadra en el
          // inventario lo servible de cada línea (copias − fallidas).
          // Re-pulsable: si luego cambian copias o fallidas y se vuelve a
          // pulsar, se reajusta lo ya dado de alta, no se añade otro
          // movimiento. Formulario propio, fuera del <form> de la
          // bitácora — mismo patrón que la subida de fotos. ?>
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center gap-2">
        <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/inventario') ?>"
            data-ajax-form="inventario-sincronizar">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-success">
                <i class="bi bi-boxes"></i>
                <?= $placa['inventario_sincronizado_en'] ? 'Actualizar inventario con esta placa' : 'Dar de alta en inventario' ?>
            </button>
        </form>
        <?php if ($placa['inventario_sincronizado_en']): ?>
            <span class="text-muted small">
                <i class="bi bi-check2-circle text-success"></i>
                Sincronizado el <?= esc(substr((string) $placa['inventario_sincronizado_en'], 0, 16)) ?>
            </span>
            <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/inventario/quitar') ?>"
                data-ajax-form="inventario-quitar"
                data-confirmar="¿Quitar del inventario todo lo que aportaba esta placa?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-lg"></i> Quitar del inventario
                </button>
            </form>
        <?php endif; ?>
        <a href="<?= site_url('piezas/existencias') ?>" class="btn btn-sm btn-outline-secondary" title="Ver existencias">
            <i class="bi bi-box-arrow-up-right"></i> Existencias
        </a>
    </div>

    <?php // Chuleta para ir a colocarlo físicamente (una vez sincronizado):
          // a qué hueco va cada pieza servible. Fijo si la pieza ya tiene
          // hueco por defecto, inferido si no pero todo su stock vive en
          // un único sitio, o a elegir si es la primera vez que sale.
          // Ver Web::colocacionDePlaca(). ?>
    <?php if ($placa['inventario_sincronizado_en'] && !empty($colocacion)): ?>
        <div class="mt-3 pt-2 border-top">
            <div class="small fw-semibold text-body-secondary mb-2"><i class="bi bi-signpost-2"></i> Dónde colocar esto</div>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($colocacion as $c): ?>
                    <div class="d-flex align-items-center gap-2 flex-wrap" data-fila-colocacion data-variante-id="<?= (int) $c['varianteId'] ?>">
                        <?php if ($c['miniatura']): ?>
                            <img src="<?= esc($c['miniatura'], 'attr') ?>" alt="" loading="lazy"
                                style="width: 1.8rem; height: 1.8rem; object-fit: cover; border-radius: .35rem;">
                        <?php endif; ?>
                        <span class="flex-grow-1 small">
                            <?= esc($c['nombre']) ?> <span class="text-muted">· <?= (int) $c['servible'] ?> uds.</span>
                        </span>

                        <span class="d-flex align-items-center gap-1" data-colocacion-controles>
                            <?php if ($c['codigo']): ?>
                                <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $c['huecoId']) ?>"
                                    class="badge rounded-pill text-decoration-none <?= $c['inferido'] ? 'text-bg-warning' : 'text-bg-primary' ?>"
                                    title="<?= $c['inferido'] ? 'Inferido: todo su stock actual vive aquí' : 'Hueco por defecto de esta pieza' ?>">
                                    <i class="bi bi-geo-alt"></i> <?= esc($c['codigo']) ?>
                                </a>
                                <?php if ($c['inferido']): ?>
                                    <form method="post" action="<?= site_url('piezas/existencias/' . $c['varianteId'] . '/hueco-defecto') ?>"
                                        data-ajax-form="hueco-defecto">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hueco_id" value="<?= (int) $c['huecoId'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                            title="Fijar este hueco como destino por defecto de esta pieza">
                                            Fijar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif (empty($colocacionHuecos)): ?>
                                <span class="text-muted small">
                                    Sin ubicaciones todavía —
                                    <a href="<?= site_url('piezas/ubicaciones') ?>" target="_blank">crea un estuche y huecos</a>.
                                </span>
                            <?php else: ?>
                                <form method="post" action="<?= site_url('piezas/existencias/' . $c['varianteId'] . '/hueco-defecto') ?>"
                                    data-ajax-form="hueco-defecto" class="d-flex gap-1 align-items-center">
                                    <?= csrf_field() ?>
                                    <select name="hueco_id" required class="form-select form-select-sm" style="max-width: 12rem;">
                                        <option value="">¿Dónde va?</option>
                                        <?php $estucheActual = null; ?>
                                        <?php foreach ($colocacionHuecos as $hd): ?>
                                            <?php if ($estucheActual !== (int) $hd['estuche']['id']): ?>
                                                <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                                                <optgroup label="<?= esc($hd['estuche']['codigo']) ?>">
                                                <?php $estucheActual = (int) $hd['estuche']['id']; ?>
                                            <?php endif; ?>
                                            <option value="<?= (int) $hd['hueco']['id'] ?>"><?= esc($hd['codigo']) ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Asignar</button>
                                </form>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size: .72rem;">
                Elegir un hueco aquí también lo fija como destino por defecto de esa pieza: la próxima vez
                que salga de una placa, caerá ahí solo.
            </p>
        </div>
    <?php endif; ?>
</div>
