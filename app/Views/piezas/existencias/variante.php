<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    $idVar  = (int) $variante['id'];
    $nombre = trim(($familia['nombre'] ?? '') . ' ' . $variante['nombre']);

    $etiquetaMotivo = [
        'alta_manual' => 'Alta a mano',
        'baja_manual' => 'Baja a mano',
        'ajuste'      => 'Ajuste',
        'impresion'   => 'Impresión',
    ];
    $claseTexto = ['cero' => 'text-danger', 'bajo' => 'text-warning', 'ok' => 'text-success'];
    $claseBorde = ['cero' => 'border-danger',  'bajo' => 'border-warning',  'ok' => 'border-success'];
    $claseBarra = ['cero' => 'bg-danger',      'bajo' => 'bg-warning',      'ok' => 'bg-success'];
    $rotulo     = [
        'cero' => 'Sin existencias',
        'bajo' => 'Por debajo del mínimo',
        'ok'   => $minimo > 0 ? 'Por encima del mínimo' : 'Con existencias',
    ];
    // Barra tipo depósito: cuánto stock hay respecto al mínimo (tope 100%).
    $llenado = $minimo > 0 ? min(100, (int) round($stock / $minimo * 100)) : ($stock > 0 ? 100 : 0);
?>

<style>
    .stepper-min input::-webkit-outer-spin-button,
    .stepper-min input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .stepper-min input { -moz-appearance: textfield; }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-boxes text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/existencias') ?>" class="text-decoration-none text-muted fw-normal">Existencias</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($nombre) ?></strong>

    <a href="<?= site_url('piezas/variante/' . $idVar) ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Ficha de la variante">
        <i class="bi bi-box-arrow-up-right"></i> Ficha
    </a>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-5">
        <div class="card border-start border-4 <?= $claseBorde[$estado] ?> h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-baseline gap-2">
                    <span class="display-5 fw-bold lh-1 <?= $claseTexto[$estado] ?>"><?= (int) $stock ?></span>
                    <span class="text-muted small">en stock</span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge rounded-pill <?= $claseBarra[$estado] ?>">&nbsp;</span>
                    <span class="small <?= $claseTexto[$estado] ?>"><?= esc($rotulo[$estado]) ?></span>
                    <?php if ($minimo > 0): ?>
                        <span class="text-muted small ms-auto">mín <?= (int) $minimo ?></span>
                    <?php endif; ?>
                </div>
                <div class="progress mt-2" style="height: .4rem;" role="progressbar" aria-valuenow="<?= $llenado ?>"
                    aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= $claseBarra[$estado] ?>" style="width: <?= $llenado ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <form method="post" action="<?= site_url('piezas/existencias/' . $idVar . '/minimo') ?>"
            class="card h-100" data-min-form>
            <div class="card-body py-3">
                <?= csrf_field() ?>
                <div class="text-muted small mb-2">
                    <i class="bi bi-bell"></i> Avísame cuando el stock baje de
                </div>
                <div class="input-group input-group-sm stepper-min" style="max-width: 11rem;">
                    <button type="button" class="btn btn-outline-secondary" data-min-step="-1" aria-label="Menos">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" name="stock_minimo" min="0" step="1" value="<?= (int) $minimo ?>"
                        class="form-control text-center fw-semibold" data-min-input>
                    <button type="button" class="btn btn-outline-secondary" data-min-step="1" aria-label="Más">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <button class="btn btn-sm btn-primary" data-min-guardar disabled>Guardar</button>
                    <span class="text-muted small" data-min-eco>
                        <?= $minimo > 0 ? 'Ahora: ' . (int) $minimo : 'Sin mínimo — solo se avisa a cero' ?>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <div class="col-12 col-md-3 d-flex flex-md-column gap-2 justify-content-md-center">
        <button type="button" class="btn btn-success flex-fill" data-bs-toggle="modal" data-bs-target="#modalMovimiento"
            data-sentido="alta">
            <i class="bi bi-plus-lg"></i> Dar de alta
        </button>
        <button type="button" class="btn btn-outline-danger flex-fill" data-bs-toggle="modal" data-bs-target="#modalMovimiento"
            data-sentido="baja">
            <i class="bi bi-dash-lg"></i> Dar de baja
        </button>
    </div>
</div>

<div class="small fw-semibold text-body-secondary mb-1"><i class="bi bi-geo-alt"></i> Dónde está</div>
<?php
    $suelto = 0;
    foreach ($desglose as $d) {
        if ($d['hueco'] === null) {
            $suelto = (int) $d['stock'];
        }
    }
?>
<?php if (empty($desglose)): ?>
    <p class="text-muted small mb-4">Sin ubicación asignada todavía.</p>
<?php else: ?>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <?php foreach ($desglose as $d): ?>
            <?php $h = $d['hueco']; ?>
            <?php if ($h): ?>
                <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $h['hueco']['id']) ?>"
                    class="badge rounded-pill text-bg-primary text-decoration-none">
                    <i class="bi bi-geo-alt"></i> <?= esc($h['codigo']) ?>
                </a>
            <?php else: ?>
                <span class="badge rounded-pill text-bg-warning">
                    <i class="bi bi-exclamation-triangle"></i> Sin asignar
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php // Lo suelto (sin hueco) se puede mover de golpe: al hueco donde ya
          // vive el resto (preseleccionado si todo lo asignado está en uno
          // solo) o a cualquier otro que se elija — sin tener que dar de
          // baja/alta a mano para reubicarlo. ?>
    <?php if ($suelto > 0): ?>
        <?php if (empty($huecosDisponibles)): ?>
            <p class="text-muted small mb-4">
                Hay piezas sin asignar — crea estuches y huecos en
                <a href="<?= site_url('piezas/ubicaciones') ?>" target="_blank">Ubicaciones</a> para poder colocarlas.
            </p>
        <?php else: ?>
            <form method="post" action="<?= site_url('piezas/existencias/' . $idVar . '/asignar-sueltos') ?>"
                class="d-flex flex-wrap gap-2 align-items-center mb-4">
                <?= csrf_field() ?>
                <span class="text-muted small">
                    <i class="bi bi-arrow-right-circle"></i> Mover lo sin asignar a
                </span>
                <select name="hueco_id" required class="form-select form-select-sm" style="max-width: 12rem;">
                    <option value="">Elige hueco…</option>
                    <?php $estucheActual = null; ?>
                    <?php foreach ($huecosDisponibles as $hd): ?>
                        <?php if ($estucheActual !== (int) $hd['estuche']['id']): ?>
                            <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                            <optgroup label="<?= esc($hd['estuche']['codigo']) ?>">
                            <?php $estucheActual = (int) $hd['estuche']['id']; ?>
                        <?php endif; ?>
                        <option value="<?= (int) $hd['hueco']['id'] ?>"
                            <?= $huecoSugerido === (int) $hd['hueco']['id'] ? 'selected' : '' ?>><?= esc($hd['codigo']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($estucheActual !== null): ?></optgroup><?php endif; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Mover</button>
                <?php if ($huecoSugerido !== null): ?>
                    <span class="text-muted small">Preseleccionado: es donde ya está el resto de esta pieza.</span>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <div class="mb-4"></div>
    <?php endif; ?>
<?php endif; ?>

<div class="small fw-semibold text-body-secondary mb-1"><i class="bi bi-clock-history"></i> Movimientos</div>
<?php if (empty($historial)): ?>
    <p class="text-muted small">Sin movimientos todavía.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle" style="font-size: .82rem;">
            <thead>
                <tr class="text-muted">
                    <th style="width: 9rem;">Fecha</th>
                    <th style="width: 8rem;">Tipo</th>
                    <th class="text-end" style="width: 4rem;">Δ</th>
                    <th>Motivo / origen</th>
                    <th style="width: 2rem;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $m): ?>
                    <?php
                        $fecha = $m['actualizado_en'] ?: $m['creado_en'];
                        $esPlaca = $m['origen'] === 'placa';
                    ?>
                    <tr>
                        <td class="text-muted"><?= esc($fecha ? substr($fecha, 0, 16) : '—') ?></td>
                        <td><?= esc($etiquetaMotivo[$m['motivo']] ?? $m['motivo']) ?></td>
                        <td class="text-end fw-semibold <?= (int) $m['delta'] < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= (int) $m['delta'] > 0 ? '+' : '' ?><?= (int) $m['delta'] ?>
                        </td>
                        <td>
                            <?php if ($esPlaca && $m['placa_id']): ?>
                                <a href="<?= site_url('piezas/placa/' . (int) $m['placa_id'] . '/bitacora/editar') ?>"
                                    class="text-decoration-none">
                                    <i class="bi bi-journal-text"></i> Bitácora de la placa #<?= (int) $m['placa_id'] ?>
                                </a>
                                <span class="text-muted"> · reflejo vivo (copias − fallidas)</span>
                            <?php else: ?>
                                <?= nl2br(esc($m['nota'] ?? '')) ?: '<span class="text-muted">—</span>' ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if (!$esPlaca): ?>
                                <form method="post" action="<?= site_url('piezas/existencias/movimiento/' . (int) $m['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Borrar este movimiento (<?= (int) $m['delta'] > 0 ? '+' : '' ?><?= (int) $m['delta'] ?>)? No se puede deshacer.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="variante_id" value="<?= $idVar ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Borrar movimiento">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted" style="font-size: .78rem;">
        Las líneas de <strong>impresión</strong> son un reflejo de la bitácora de su placa: si allí
        cambian las copias o las fallidas y se vuelve a pulsar «Dar de alta», esta misma línea se
        recuadra — no se añade otra.
    </p>
<?php endif; ?>

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
                    <label class="form-label small mb-1">Cantidad</label>
                    <input type="number" name="cantidad" min="1" step="1" value="1" required
                        class="form-control" autocomplete="off">
                </div>
                <?php // El hueco solo tiene sentido al dar de alta (dónde se coloca lo
                      // nuevo). Al dar de baja no se pregunta: se descuenta del total y
                      // los huecos se quedan exactamente como estaban — no van de la
                      // mano con las bajas. ?>
                <div class="mb-3" data-mov-hueco-wrap>
                    <label class="form-label small mb-1">Hueco (opcional)</label>
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
                                <option value="<?= (int) $hd['hueco']['id'] ?>"><?= esc($hd['codigo']) ?></option>
                            <?php endforeach; ?>
                            </optgroup>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="mb-1">
                    <label class="form-label small mb-1">Motivo</label>
                    <textarea name="nota" rows="3" required class="form-control"
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
        });
    })();
</script>

<?= $this->endSection() ?>
