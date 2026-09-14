<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-geo-alt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="text-decoration-none text-muted fw-normal">Ubicaciones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($codigo) ?></strong>

    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/borrar') ?>" class="ms-auto"
        onsubmit="return confirm('¿Borrar el hueco «<?= esc($codigo, 'js') ?>»? El historial de stock que tenía queda sin asignar, no se pierde.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Borrar hueco</button>
    </form>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if ($estuche): ?>
    <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
        <span><i class="bi bi-archive"></i> Estuche <?= esc($estuche['codigo']) ?></span>
        <?php if ($estuche['zona']): ?><span><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span><?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($hueco['notas']): ?>
    <p class="text-muted small"><?= nl2br(esc($hueco['notas'])) ?></p>
<?php endif; ?>

<div class="small fw-semibold text-body-secondary mb-1"><i class="bi bi-boxes"></i> Contenido</div>
<?php if (empty($filas)): ?>
    <p class="text-muted small">Vacío por ahora.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle" style="font-size: .85rem;">
            <thead>
                <tr class="text-muted">
                    <th>Pieza</th>
                    <th class="text-end" style="width: 6rem;">Stock aquí</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $f): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('piezas/existencias/' . (int) $f['variante']['id']) ?>" class="text-decoration-none">
                                <?= esc($f['nombre']) ?>
                            </a>
                        </td>
                        <td class="text-end fw-semibold"><?= (int) $f['stock'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if (!empty($catalogo)): ?>
    <style>
        /* Miniaturas cuadradas en el selector de pieza — a ojo se
           encuentra antes que leyendo nombres, y no crece "en chorizo"
           como una lista de texto según se añaden piezas al catálogo. */
        .pieza-card { border: 0; background: transparent; padding: 0; text-align: left; width: 100%; }
        .pieza-card .miniatura {
            position: relative;
            aspect-ratio: 1;
            border-radius: .5rem;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bs-secondary-color);
        }
        .pieza-card .miniatura img { width: 100%; height: 100%; object-fit: cover; }
        .pieza-card:hover .miniatura { border-color: var(--bs-primary); }
        .pieza-card .etiqueta { font-size: .72rem; line-height: 1.2; margin-top: .25rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .pieza-card .stock-badge {
            position: absolute; top: .25rem; right: .25rem;
            background: var(--bs-primary); color: #fff;
            font-size: .62rem; font-weight: 700; line-height: 1;
            padding: .15rem .35rem; border-radius: 999px;
        }
        .pieza-card .ubicacion-mini {
            font-size: .62rem; color: var(--bs-secondary-color); margin-top: .1rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .pieza-elegida-mini { width: 1.8rem; height: 1.8rem; border-radius: .35rem; object-fit: cover; }

        /* Insignias de ubicación clicables: pulsar una copia su cantidad
           al campo "Cantidad" para no teclearla a mano. */
        .chip-cantidad { cursor: pointer; border: 0; }
        .chip-cantidad:hover { filter: brightness(1.15); }
        .chip-neutro { background: var(--bs-tertiary-bg, var(--bs-secondary-bg)); color: var(--bs-body-color); border: 1px solid var(--bs-border-color); }
    </style>

    <div class="small fw-semibold text-body-secondary mb-1 mt-3"><i class="bi bi-plus-slash-minus"></i> Añadir o quitar pieza aquí</div>
    <form method="post" action="<?= site_url('piezas/existencias/movimiento') ?>" class="row g-2 align-items-end mb-1" id="formMovimientoHueco">
        <?= csrf_field() ?>
        <input type="hidden" name="ubicacion_id" value="<?= (int) $hueco['id'] ?>">
        <input type="hidden" name="volver_hueco" value="<?= (int) $hueco['id'] ?>">
        <input type="hidden" name="variante_id" id="movVarianteId" value="">

        <div class="col-12 col-sm-5">
            <label class="form-label small mb-1">Pieza</label>
            <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center gap-2 text-start"
                data-bs-toggle="modal" data-bs-target="#modalElegirPieza" id="btnElegirPieza">
                <i class="bi bi-search" id="btnElegirPiezaIcono"></i>
                <img src="" alt="" class="pieza-elegida-mini d-none" id="piezaElegidaImg">
                <span id="piezaElegidaTexto" class="text-truncate">Elegir pieza…</span>
            </button>
        </div>
        <div class="col-12 col-sm-2">
            <label class="form-label small mb-1">Cantidad</label>
            <input type="number" name="cantidad" id="movCantidad" min="1" step="1" value="1" required class="form-control form-control-sm">
        </div>
        <div class="col-12 col-sm-3">
            <label class="form-label small mb-1">Motivo</label>
            <input type="text" name="nota" required class="form-control form-control-sm" placeholder="Por qué">
        </div>
        <div class="col-12 col-sm-2 d-flex gap-1">
            <button type="submit" name="sentido" value="alta" class="btn btn-sm btn-success flex-fill" title="Dar de alta aquí">
                <i class="bi bi-plus-lg"></i>
            </button>
            <button type="submit" name="sentido" value="baja" class="btn btn-sm btn-outline-danger flex-fill" title="Dar de baja aquí">
                <i class="bi bi-dash-lg"></i>
            </button>
        </div>

        <div class="col-12 form-check">
            <input type="checkbox" name="fijar_defecto" value="1" class="form-check-input" id="movFijarDefecto">
            <label class="form-check-label small text-muted" for="movFijarDefecto">
                Usar este hueco como destino por defecto de esta pieza (solo aplica al dar de alta) — lo que se imprima de ella a partir de ahora caerá aquí solo.
            </label>
        </div>

        <div class="col-12" id="piezaInfoUbicaciones"></div>
    </form>

    <div class="modal fade" id="modalElegirPieza" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Elegir pieza</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-3">
                        <input type="search" id="piezaModalBuscar" class="form-control form-control-sm"
                            placeholder="Busca por nombre o SKU…" autocomplete="off">
                        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap d-none" id="piezaModalVerTodas"></button>
                    </div>
                    <div id="piezaModalGrid" class="row row-cols-3 row-cols-sm-4 row-cols-md-5 g-3"></div>
                    <p class="text-muted small mt-2 mb-0" id="piezaModalEco"></p>
                </div>
            </div>
        </div>
    </div>

    <?php
        // Buscador hecho a mano en vez de <datalist>: en Safari de iPhone el
        // datalist no despliega ninguna lista en inputs de texto (limitación
        // conocida de WebKit), así que en móvil quedaba inservible. Esto
        // funciona igual en cualquier navegador/táctil, y con miniatura.
    ?>
    <script id="catalogoVariantesHueco" type="application/json"><?= json_encode($catalogo) ?></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formMovimientoHueco');
            if (!form) return;

            var catalogo = JSON.parse(document.getElementById('catalogoVariantesHueco').textContent || '[]');
            var LIMITE = 24;
            var verTodas = false;
            var HUECO_ACTUAL_ID = <?= (int) $hueco['id'] ?>;

            var oculto        = document.getElementById('movVarianteId');
            var btnElegir      = document.getElementById('btnElegirPieza');
            var btnIcono       = document.getElementById('btnElegirPiezaIcono');
            var piezaImg       = document.getElementById('piezaElegidaImg');
            var piezaTexto     = document.getElementById('piezaElegidaTexto');
            var infoUbicaciones = document.getElementById('piezaInfoUbicaciones');
            var modalEl        = document.getElementById('modalElegirPieza');
            var modalBuscar    = document.getElementById('piezaModalBuscar');
            var modalGrid      = document.getElementById('piezaModalGrid');
            var modalEco       = document.getElementById('piezaModalEco');
            var btnVerTodas    = document.getElementById('piezaModalVerTodas');
            var modal          = new bootstrap.Modal(modalEl);

            function iconoVacio() {
                return '<div class="d-flex align-items-center justify-content-center h-100"><i class="bi bi-image"></i></div>';
            }

            function escapar(txt) {
                return String(txt).replace(/</g, '&lt;');
            }

            function textoUbicaciones(c) {
                if (c.ubicaciones.length === 0) {
                    return 'Sin stock';
                }

                return c.ubicaciones.map(function (u) { return u.codigo + ' · ' + u.stock; }).join(', ');
            }

            // Desglose que sale al elegir la pieza: cuánto hay ya AQUÍ (para
            // saber cuánto tiene sentido dar de baja desde este hueco), cuánto
            // sin asignar (lo que se podría venir a colocar aquí físicamente)
            // y cuánto hay en cada otro hueco — para decidir cuánto coger y
            // de dónde antes de rellenar la cantidad.
            function pintarInfoUbicaciones(c) {
                if (!c) {
                    infoUbicaciones.innerHTML = '';
                    return;
                }
                if (c.ubicaciones.length === 0) {
                    infoUbicaciones.innerHTML = '<span class="text-muted small">Sin existencias en ningún sitio todavía — lo que registres aquí será alta nueva.</span>';
                    return;
                }

                var chips = c.ubicaciones.map(function (u) {
                    var esAqui = u.huecoId === HUECO_ACTUAL_ID;
                    var esSinAsignar = u.huecoId === 0;
                    var clase = esAqui ? 'text-bg-primary' : (esSinAsignar ? 'text-bg-warning' : 'chip-neutro');
                    var etiqueta = esAqui ? 'Aquí' : u.codigo;
                    return '<button type="button" class="badge rounded-pill chip-cantidad ' + clase + '" '
                        + 'data-cantidad="' + u.stock + '" title="Usar esta cantidad (' + u.stock + ')">'
                        + escapar(etiqueta) + ': ' + u.stock + '</button>';
                }).join(' ');

                infoUbicaciones.innerHTML = '<span class="text-muted small me-2">Total ' + c.stockTotal + ' uds. — toca una para copiar su cantidad:</span> ' + chips;
            }

            function pintarGrid() {
                var terminos = modalBuscar.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
                var todas = terminos.length === 0
                    ? catalogo
                    : catalogo.filter(function (c) {
                        var texto = c.label.toLowerCase();
                        return terminos.every(function (t) { return texto.indexOf(t) !== -1; });
                    });

                var visibles = verTodas ? todas : todas.slice(0, LIMITE);
                modalGrid.innerHTML = '';

                // El botón "Ver todas" es justo lo que resuelve poder repasar
                // el catálogo entero (cuánto hay de cada pieza y en qué
                // hueco), no solo lo que encaja en la búsqueda del momento.
                if (todas.length > LIMITE) {
                    btnVerTodas.textContent = verTodas ? 'Ver menos' : 'Ver todas (' + todas.length + ')';
                    btnVerTodas.classList.remove('d-none');
                } else {
                    btnVerTodas.classList.add('d-none');
                }

                if (visibles.length === 0) {
                    modalEco.textContent = 'Ninguna pieza coincide.';
                    return;
                }
                modalEco.textContent = '';

                visibles.forEach(function (c) {
                    var col = document.createElement('div');
                    col.className = 'col';

                    var stockBadge = c.stockTotal > 0 ? '<span class="stock-badge">' + c.stockTotal + '</span>' : '';

                    var card = document.createElement('button');
                    card.type = 'button';
                    card.className = 'pieza-card';
                    card.innerHTML = '<div class="miniatura">' + stockBadge + (c.img
                        ? '<img src="' + c.img + '" alt="" loading="lazy">'
                        : iconoVacio()) + '</div>'
                        + '<div class="etiqueta">' + escapar(c.label) + '</div>'
                        + '<div class="ubicacion-mini">' + escapar(textoUbicaciones(c)) + '</div>';
                    card.addEventListener('click', function () {
                        oculto.value = c.id;
                        piezaTexto.textContent = c.label;
                        piezaTexto.classList.remove('text-muted');
                        btnElegir.classList.remove('btn-outline-danger');
                        if (c.img) {
                            piezaImg.src = c.img;
                            piezaImg.classList.remove('d-none');
                            btnIcono.classList.add('d-none');
                        } else {
                            piezaImg.classList.add('d-none');
                            btnIcono.classList.remove('d-none');
                        }
                        pintarInfoUbicaciones(c);
                        modal.hide();
                    });

                    col.appendChild(card);
                    modalGrid.appendChild(col);
                });
            }

            var movCantidad = document.getElementById('movCantidad');
            infoUbicaciones.addEventListener('click', function (ev) {
                var chip = ev.target.closest('[data-cantidad]');
                if (chip) {
                    movCantidad.value = chip.dataset.cantidad;
                }
            });

            btnVerTodas.addEventListener('click', function () {
                verTodas = !verTodas;
                pintarGrid();
            });

            modalBuscar.addEventListener('input', function () {
                verTodas = false;
                pintarGrid();
            });
            modalEl.addEventListener('shown.bs.modal', function () {
                modalBuscar.value = '';
                verTodas = false;
                modalBuscar.focus();
                pintarGrid();
            });

            form.addEventListener('submit', function (ev) {
                if (!oculto.value) {
                    ev.preventDefault();
                    btnElegir.classList.add('btn-outline-danger');
                    modal.show();
                }
            });
        });
    </script>
<?php endif; ?>

<?php if (!empty($destinos)): ?>
    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/mover') ?>"
        class="d-flex flex-wrap gap-2 align-items-center mt-3">
        <?= csrf_field() ?>
        <span class="text-muted small"><i class="bi bi-arrow-right-circle"></i> Mover todo el stock a</span>
        <select name="destino_id" required class="form-select form-select-sm" style="max-width: 12rem;">
            <option value="">Elige hueco…</option>
            <?php foreach ($destinos as $destId => $destCodigo): ?>
                <option value="<?= $destId ?>"><?= esc($destCodigo) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Mover</button>
        <span class="text-muted small">Para fusionar dos huecos: mueve el stock aquí y luego borra el que quede vacío.</span>
    </form>
<?php endif; ?>

<?= $this->endSection() ?>
