<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pedidos</strong>

    <button type="button" class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido">
        <i class="bi bi-plus-lg"></i> Nuevo pedido
    </button>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary" title="Piezas listas para imprimir">
        <i class="bi bi-grid-3x3-gap"></i> Galería
    </a>
    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary" title="Histórico de placas (guardadas y descargadas)">
        <i class="bi bi-printer"></i> Placas
    </a>
</h5>

<?php
/**
 * Alta manual (a diferencia de los que llegan solos desde sterclicks): nace
 * sin líneas, se rellena en la propia ficha con el mismo formulario que
 * cualquier otro pedido. Referencia externa es opcional a propósito — no
 * todo pedido de mano viene con un número que apuntar.
 */
?>
<div class="modal fade" id="modalNuevoPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/pedido') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pedido nuevo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Referencia (opcional)</label>
                <input type="text" name="referencia_externa" class="form-control form-control-sm mb-2"
                    placeholder="de qué va (o nº de pedido, si viene de fuera)" maxlength="50">
                <label class="form-label small">Fecha de entrega (opcional)</label>
                <input type="date" name="fecha_entrega" class="form-control form-control-sm mb-2">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace sin líneas — se añaden en la ficha del pedido, buscando cada pieza en el catálogo.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php
    $coloresColumna = [
        'nuevo'         => 'primary',
        'en_produccion' => 'warning',
        'completado'    => 'success',
        'cancelado'     => 'secondary',
    ];
?>

<style>
    .tarjeta-pedido { position: relative; }
    .tarjeta-pedido.prioritario > .card { border-color: var(--bs-warning); box-shadow: 0 0 0 1px var(--bs-warning) !important; }
    .btn-estrella {
        position: absolute; top: 4px; right: 4px; z-index: 2;
        width: 28px; height: 28px; padding: 0; border: 0; border-radius: 999px;
        background: rgba(var(--bs-body-bg-rgb), .85); color: var(--bs-secondary-color);
        display: inline-flex; align-items: center; justify-content: center;
    }
    .tarjeta-pedido.prioritario .btn-estrella { color: var(--bs-warning); }
    .btn-estrella:hover { color: var(--bs-warning); }
    .lista-pedidos .sortable-ghost { opacity: .35; }
    .lista-pedidos .sortable-chosen > .card { box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important; }
    .lista-pedidos.arrastrable .card { cursor: grab; }
</style>

<div class="d-flex flex-wrap gap-2 mb-3" id="filtro-estados">
    <button type="button" class="btn btn-sm rounded-pill btn-dark" data-filtro="">
        Todos
    </button>
    <?php foreach ($columnas as $estado => $columna): ?>
        <button type="button" class="btn btn-sm rounded-pill btn-outline-<?= $coloresColumna[$estado] ?>" data-filtro="<?= esc($estado) ?>">
            <?= esc($columna['titulo']) ?> <span class="badge bg-body-secondary text-body-secondary border"><?= count($columna['pedidos']) ?></span>
        </button>
    <?php endforeach; ?>

    <?php // Manual: el orden arrastrado a mano. Por entrega: lo que vence antes arriba (sin tocar el orden guardado). ?>
    <div class="btn-group btn-group-sm ms-auto" role="group" id="modo-orden" title="Cómo ordenar dentro de cada columna (las de estrella siempre arriba)">
        <button type="button" class="btn btn-outline-secondary" data-orden="manual"><i class="bi bi-arrows-move"></i> Manual</button>
        <button type="button" class="btn btn-outline-secondary" data-orden="entrega"><i class="bi bi-truck"></i> Por entrega</button>
    </div>
</div>

<div class="row g-3" id="tablero-pedidos">
    <?php foreach ($columnas as $estado => $columna): ?>
        <div class="col-12 col-md-6 col-xl-3 columna-pedidos" data-columna="<?= esc($estado) ?>">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge text-bg-<?= $coloresColumna[$estado] ?>">&nbsp;</span>
                <span class="fw-semibold"><?= esc($columna['titulo']) ?></span>
                <span class="badge bg-body-secondary text-body-secondary border ms-auto"><?= count($columna['pedidos']) ?></span>
            </div>

            <?php if (empty($columna['pedidos'])): ?>
                <p class="text-muted small">Nada aquí.</p>
            <?php endif; ?>

            <div class="d-flex flex-column gap-2 lista-pedidos" data-columna="<?= esc($estado) ?>">
                <?php foreach ($columna['pedidos'] as $pedido): ?>
                    <?php
                        $fecha = strtotime($pedido['creado_en']);
                        $prioritario = (bool) (int) $pedido['prioritario'];
                        $entrega = \App\Models\PiezaPedidoModel::entrega($pedido);
                    ?>
                    <div class="tarjeta-pedido <?= $prioritario ? 'prioritario' : '' ?>" data-id="<?= (int) $pedido['id'] ?>"
                        data-prioritario="<?= $prioritario ? 1 : 0 ?>" data-entrega="<?= esc($pedido['fecha_entrega'] ?? '', 'attr') ?>">
                    <button type="button" class="btn-estrella" data-estrella
                        title="<?= $prioritario ? 'Quitar prioridad' : 'Marcar como prioritario' ?>">
                        <i class="bi <?= $prioritario ? 'bi-star-fill' : 'bi-star' ?>"></i>
                    </button>
                    <a href="<?= site_url('piezas/pedido/' . $pedido['id']) ?>" class="card shadow-sm text-decoration-none text-body"
                        style="cursor: pointer" title="Ver pedido #<?= $pedido['id'] ?>">
                        <?php if ($pedido['fotos']): ?>
                            <div class="d-flex" style="gap: 2px; height: 64px; overflow: hidden; background: rgba(127,127,127,.15);">
                                <?php foreach (array_slice($pedido['fotos'], 0, 4) as $foto): ?>
                                    <img src="<?= $foto ?>" loading="lazy" alt=""
                                        style="flex: 1 1 0; min-width: 0; height: 100%; object-fit: cover; display: block;">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <?php if (!empty($pedido['referencia_externa'])): ?>
                                <div class="fw-semibold text-truncate pe-4" title="<?= esc($pedido['referencia_externa'], 'attr') ?>"><?= esc($pedido['referencia_externa']) ?></div>
                                <div class="text-muted" style="font-size: .75rem;">Pedido #<?= (int) $pedido['id'] ?></div>
                            <?php else: ?>
                                <div class="small fw-semibold pe-4">Pedido #<?= (int) $pedido['id'] ?></div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center gap-2 text-muted" style="font-size: .75rem;">
                                <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
                                <?php if ($pedido['numLineas']): ?>
                                    <span class="ms-auto <?= $pedido['lineasListas'] === $pedido['numLineas'] ? 'text-success' : '' ?>"
                                        title="Piezas listas para imprimir del total del pedido">
                                        <strong><?= $pedido['lineasListas'] ?>/<?= $pedido['numLineas'] ?></strong> listas
                                    </span>
                                <?php else: ?>
                                    <span class="ms-auto">sin piezas</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($entrega): ?>
                                <div class="<?= $entrega['clase'] ?>" style="font-size: .75rem;" title="Fecha de entrega: <?= esc(date('d/m/Y', strtotime($pedido['fecha_entrega'])), 'attr') ?>">
                                    <i class="bi <?= $entrega['vencido'] ? 'bi-exclamation-triangle' : 'bi-truck' ?>"></i> Entrega <?= esc($entrega['texto']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($pedido['totalPiezas']): ?>
                                <?php $pct = (int) round(min($pedido['piezasHechas'], $pedido['totalPiezas']) * 100 / $pedido['totalPiezas']); ?>
                                <div class="d-flex align-items-center gap-2 mt-1" title="Unidades hechas del total">
                                    <div class="progress flex-grow-1" style="height: 4px;"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
                                    <span class="text-muted text-nowrap" style="font-size: .7rem;"><?= $pedido['piezasHechas'] ?>/<?= $pedido['totalPiezas'] ?> ud.</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($pedido['notas']): ?>
                                <div class="text-muted text-truncate mt-1" style="font-size: .75rem;" title="<?= esc($pedido['notas'], 'attr') ?>">
                                    <?= esc($pedido['notas']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const botones = Array.from(document.querySelectorAll('#filtro-estados [data-filtro]'));
        const columnas = Array.from(document.querySelectorAll('#tablero-pedidos .columna-pedidos'));

        function aplicar(filtro) {
            columnas.forEach(function (col) {
                col.classList.toggle('d-none', filtro !== '' && col.dataset.columna !== filtro);
            });
            botones.forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.filtro === filtro);
            });
        }

        botones.forEach(function (btn) {
            btn.addEventListener('click', function () { aplicar(btn.dataset.filtro); });
        });

        aplicar('');

        // ---- Estrella y orden ----
        var CSRF_NOMBRE = '<?= csrf_token() ?>';
        var CSRF_VALOR = '<?= csrf_hash() ?>';
        var URL_ORDEN = '<?= site_url('piezas/pedidos/reordenar') ?>';
        var URL_PEDIDO = '<?= site_url('piezas/pedido') ?>/';
        var listas = Array.from(document.querySelectorAll('#tablero-pedidos .lista-pedidos'));

        function post(url, campos) {
            var datos = new FormData();
            Object.keys(campos).forEach(function (k) {
                [].concat(campos[k]).forEach(function (v) { datos.append(k, v); });
            });
            datos.append(CSRF_NOMBRE, CSRF_VALOR);
            return fetch(url, {
                method: 'POST', body: datos, credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); });
        }

        function tarjetas(lista) { return Array.from(lista.querySelectorAll(':scope > .tarjeta-pedido')); }

        function guardarOrden(lista) {
            var orden = tarjetas(lista).map(function (t) { return t.dataset.id; });
            post(URL_ORDEN, { 'orden[]': orden })
                .then(function (d) { if (!d.ok) throw d; })
                .catch(function () { alert('No se pudo guardar el orden.'); });
        }

        // Posición en el orden manual de cada tarjeta, para volver a él al
        // salir del modo "Por entrega" sin recargar.
        function recordarManual() {
            listas.forEach(function (lista) {
                tarjetas(lista).forEach(function (t, i) { t.dataset.posManual = i; });
            });
        }

        // Estrella siempre arriba; dentro de cada grupo, por fecha de
        // entrega (las sin fecha al final) o por el orden manual.
        function ordenar(modo) {
            listas.forEach(function (lista) {
                tarjetas(lista).sort(function (a, b) {
                    var p = b.dataset.prioritario - a.dataset.prioritario;
                    if (p) return p;
                    if (modo === 'entrega') {
                        var fa = a.dataset.entrega || '9999-99-99', fb = b.dataset.entrega || '9999-99-99';
                        if (fa !== fb) return fa < fb ? -1 : 1;
                    }
                    return a.dataset.posManual - b.dataset.posManual;
                }).forEach(function (t) { lista.appendChild(t); });
            });
        }

        // Arrastrar para ordenar (en móvil, mantener pulsado un momento:
        // así un toque normal sigue abriendo el pedido).
        var sortables = listas.map(function (lista) {
            return Sortable.create(lista, {
                draggable: '.tarjeta-pedido',
                filter: '[data-estrella]',
                preventOnFilter: false,
                animation: 150,
                delay: 250,
                delayOnTouchOnly: true,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                // Sin mezclar grupos: una sin estrella no puede subir por
                // encima de las de estrella (se recolocaría al recargar).
                onMove: function (evt) {
                    return evt.related.dataset.prioritario === evt.dragged.dataset.prioritario;
                },
                onEnd: function (evt) {
                    if (evt.oldIndex === evt.newIndex) return;
                    recordarManual();
                    guardarOrden(lista);
                }
            });
        });

        var botonesOrden = Array.from(document.querySelectorAll('#modo-orden [data-orden]'));
        var modoActual = 'manual';
        function ponerModo(modo) {
            modoActual = modo;
            botonesOrden.forEach(function (b) { b.classList.toggle('active', b.dataset.orden === modo); });
            sortables.forEach(function (s) { s.option('disabled', modo !== 'manual'); });
            listas.forEach(function (l) { l.classList.toggle('arrastrable', modo === 'manual'); });
            ordenar(modo);
            try { localStorage.setItem('pedidos.orden', modo); } catch (e) {}
        }
        botonesOrden.forEach(function (b) {
            b.addEventListener('click', function () { ponerModo(b.dataset.orden); });
        });

        recordarManual();
        var guardado = 'manual';
        try { guardado = localStorage.getItem('pedidos.orden') || 'manual'; } catch (e) {}
        ponerModo(guardado === 'entrega' ? 'entrega' : 'manual');

        document.getElementById('tablero-pedidos').addEventListener('click', function (e) {
            var boton = e.target.closest('[data-estrella]');
            if (!boton) return;
            e.preventDefault();
            var tarjeta = boton.closest('.tarjeta-pedido');
            var lista = tarjeta.parentElement;
            boton.disabled = true;
            post(URL_PEDIDO + tarjeta.dataset.id + '/prioridad', {})
                .then(function (d) {
                    if (!d.ok) throw d;
                    tarjeta.dataset.prioritario = d.prioritario ? 1 : 0;
                    tarjeta.classList.toggle('prioritario', d.prioritario);
                    boton.querySelector('i').className = 'bi ' + (d.prioritario ? 'bi-star-fill' : 'bi-star');
                    boton.title = d.prioritario ? 'Quitar prioridad' : 'Marcar como prioritario';
                    // Al cambiar de grupo pasa justo tras la última de
                    // estrella (o a encabezar las demás) y se guarda el orden.
                    var orden = tarjetas(lista).sort(function (a, b) { return a.dataset.posManual - b.dataset.posManual; });
                    orden.forEach(function (t) { lista.appendChild(t); });
                    var conEstrella = orden.filter(function (t) { return t !== tarjeta && t.dataset.prioritario === '1'; });
                    var ultima = conEstrella[conEstrella.length - 1];
                    lista.insertBefore(tarjeta, ultima ? ultima.nextSibling : lista.firstChild);
                    recordarManual();
                    ordenar(modoActual);
                    guardarOrden(listaManual(lista));
                })
                .catch(function () { alert('No se pudo cambiar la prioridad.'); })
                .finally(function () { boton.disabled = false; });
        });

        // En modo "Por entrega" la lista está reordenada en pantalla; lo que
        // se guarda es siempre el orden manual.
        function listaManual(lista) {
            var copia = document.createElement('div');
            tarjetas(lista).sort(function (a, b) { return a.dataset.posManual - b.dataset.posManual; })
                .forEach(function (t) { copia.appendChild(t.cloneNode(false)); });
            return copia;
        }
    });
</script>

<?= $this->endSection() ?>
