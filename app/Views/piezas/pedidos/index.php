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
        'nuevo'          => 'primary',
        'en_produccion'  => 'warning',
        'para_impresion' => 'info',
        'completado'     => 'success',
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

    /* Móvil: una fase cada vez, elegida en la barra de pestañas fija. */
    #pestanas-estados { top: 0; z-index: 1020; background: var(--bs-body-bg); margin: 0 -12px; padding: 6px 12px; }
    #pestanas-estados .nav-link { padding: .35rem .25rem; font-size: .8rem; white-space: nowrap; color: var(--bs-body-color); }
    #pestanas-estados .nav-link.active { color: #fff; }
    #pestanas-estados .nav-link .badge { font-size: .65rem; }
    /* Escritorio sin "Hecho": las tres que quedan se reparten el ancho. */
    @media (min-width: 1200px) {
        #tablero-pedidos.sin-hechos > .columna-pedidos { flex: 0 0 auto; width: 33.333%; }
    }
</style>

<?php // Solo en móvil: sustituye a las columnas apiladas. ?>
<nav class="nav nav-pills nav-fill flex-nowrap gap-1 sticky-top d-md-none mb-2" id="pestanas-estados">
    <?php $cortos = ['para_impresion' => 'Para imp.']; ?>
    <?php foreach ($columnas as $estado => $columna): ?>
        <button type="button" class="nav-link" data-pestana="<?= esc($estado) ?>" data-color="<?= $coloresColumna[$estado] ?>">
            <?= esc($cortos[$estado] ?? $columna['titulo']) ?>
            <span class="badge rounded-pill bg-body-secondary text-body-secondary border"><?= $columna['total'] ?></span>
        </button>
    <?php endforeach; ?>
</nav>

<div class="d-flex flex-wrap gap-2 mb-3" id="filtro-estados">
    <button type="button" class="btn btn-sm rounded-pill btn-dark d-none d-md-inline-block" data-filtro="">
        Todos
    </button>
    <?php foreach ($columnas as $estado => $columna): ?>
        <button type="button" class="btn btn-sm rounded-pill btn-outline-<?= $coloresColumna[$estado] ?> d-none d-md-inline-block" data-filtro="<?= esc($estado) ?>">
            <?= esc($columna['titulo']) ?> <span class="badge bg-body-secondary text-body-secondary border"><?= $columna['total'] ?></span>
        </button>
    <?php endforeach; ?>

    <?php // Manual: el orden arrastrado a mano. Por entrega: lo que vence antes arriba (sin tocar el orden guardado). ?>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-md-auto" id="ocultar-hechos" aria-pressed="false"
        title="Quitar la columna Hecho del tablero">
        <i class="bi bi-eye-slash"></i> <span>Ocultar hechos</span>
    </button>

    <div class="btn-group btn-group-sm ms-auto ms-md-0" role="group" id="modo-orden" title="Cómo ordenar dentro de cada columna (las de estrella siempre arriba)">
        <button type="button" class="btn btn-outline-secondary" data-orden="manual"><i class="bi bi-arrows-move"></i> Manual</button>
        <button type="button" class="btn btn-outline-secondary" data-orden="entrega"><i class="bi bi-truck"></i> Por entrega</button>
    </div>
</div>

<div class="row g-3" id="tablero-pedidos">
    <?php foreach ($columnas as $estado => $columna): ?>
        <div class="col-12 col-md-6 col-xl-3 columna-pedidos" data-columna="<?= esc($estado) ?>">
            <div class="d-none d-md-flex align-items-center gap-2 mb-2">
                <span class="badge text-bg-<?= $coloresColumna[$estado] ?>">&nbsp;</span>
                <span class="fw-semibold"><?= esc($columna['titulo']) ?></span>
                <span class="badge bg-body-secondary text-body-secondary border ms-auto"><?= $columna['total'] ?></span>
            </div>

            <?php if (empty($columna['pedidos'])): ?>
                <p class="text-muted small">Nada aquí.</p>
            <?php endif; ?>

            <?php // "Hecho" va por fecha de terminado y a trozos: sin arrastre ni estrella. ?>
            <div class="d-flex flex-column gap-2 <?= $estado === 'completado' ? 'lista-hechos' : 'lista-pedidos' ?>" data-columna="<?= esc($estado) ?>">
                <?php foreach ($columna['pedidos'] as $pedido): ?>
                    <?= view('piezas/pedidos/_tarjeta', ['pedido' => $pedido]) ?>
                <?php endforeach; ?>
            </div>

            <?php if ($estado === 'completado' && $columna['total'] > count($columna['pedidos'])): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-2" id="cargar-mas-hechos"
                    data-desde="<?= count($columna['pedidos']) ?>">
                    <i class="bi bi-arrow-down-circle"></i> Cargar más
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const botones = Array.from(document.querySelectorAll('#filtro-estados [data-filtro]'));
        const pestanas = Array.from(document.querySelectorAll('#pestanas-estados [data-pestana]'));
        const columnas = Array.from(document.querySelectorAll('#tablero-pedidos .columna-pedidos'));
        const tablero = document.getElementById('tablero-pedidos');
        const botonOcultar = document.getElementById('ocultar-hechos');
        const movil = window.matchMedia('(max-width: 767.98px)');

        function leer(clave, porDefecto) {
            try { return localStorage.getItem(clave) ?? porDefecto; } catch (e) { return porDefecto; }
        }
        function guardar(clave, valor) {
            try { localStorage.setItem(clave, valor); } catch (e) {}
        }

        // Escritorio: filtro de las píldoras ('' = todas). Móvil: una sola
        // pestaña. "Ocultar hechos" quita Hecho de "todas" y de las pestañas;
        // pulsar la píldora Hecho a propósito la sigue enseñando.
        var filtro = '';
        var pestana = leer('pedidos.pestana', 'en_produccion');
        var ocultarHechos = leer('pedidos.ocultarHechos', '0') === '1';
        var arrastrando = false;

        function pestanasVisibles() {
            return pestanas.filter(function (p) { return !p.classList.contains('d-none'); });
        }

        function aplicar() {
            pestanas.forEach(function (p) {
                p.classList.toggle('d-none', ocultarHechos && p.dataset.pestana === 'completado');
            });
            if (!pestanasVisibles().some(function (p) { return p.dataset.pestana === pestana; })) {
                pestana = 'en_produccion';
            }

            columnas.forEach(function (col) {
                var estado = col.dataset.columna;
                var visible = movil.matches
                    ? estado === pestana
                    : (filtro === '' ? !(ocultarHechos && estado === 'completado') : estado === filtro);
                col.classList.toggle('d-none', !visible);
            });
            botones.forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.filtro === filtro);
            });
            pestanas.forEach(function (p) {
                var activa = p.dataset.pestana === pestana;
                p.classList.toggle('active', activa);
                p.classList.toggle('text-bg-' + p.dataset.color, activa);
            });

            tablero.classList.toggle('sin-hechos', !movil.matches && filtro === '' && ocultarHechos);
            botonOcultar.classList.toggle('active', ocultarHechos);
            botonOcultar.setAttribute('aria-pressed', ocultarHechos ? 'true' : 'false');
            botonOcultar.querySelector('i').className = 'bi ' + (ocultarHechos ? 'bi-eye' : 'bi-eye-slash');
            botonOcultar.querySelector('span').textContent = ocultarHechos ? 'Mostrar hechos' : 'Ocultar hechos';
        }

        function irAPestana(estado) {
            pestana = estado;
            guardar('pedidos.pestana', estado);
            aplicar();
        }

        botones.forEach(function (btn) {
            btn.addEventListener('click', function () { filtro = btn.dataset.filtro; aplicar(); });
        });
        pestanas.forEach(function (p) {
            p.addEventListener('click', function () { irAPestana(p.dataset.pestana); });
        });
        botonOcultar.addEventListener('click', function () {
            ocultarHechos = !ocultarHechos;
            guardar('pedidos.ocultarHechos', ocultarHechos ? '1' : '0');
            aplicar();
        });
        movil.addEventListener('change', aplicar);

        // Deslizar a los lados cambia de pestaña (no mientras se arrastra
        // una tarjeta para reordenar).
        var toque = null;
        tablero.addEventListener('touchstart', function (e) {
            toque = e.touches.length === 1 ? { x: e.touches[0].clientX, y: e.touches[0].clientY } : null;
        }, { passive: true });
        tablero.addEventListener('touchend', function (e) {
            if (!toque || arrastrando || !movil.matches) return;
            var dx = e.changedTouches[0].clientX - toque.x;
            var dy = e.changedTouches[0].clientY - toque.y;
            toque = null;
            if (Math.abs(dx) < 60 || Math.abs(dx) < Math.abs(dy) * 2) return;
            var visibles = pestanasVisibles();
            var i = visibles.findIndex(function (p) { return p.dataset.pestana === pestana; });
            var destino = visibles[i + (dx < 0 ? 1 : -1)];
            if (destino) {
                irAPestana(destino.dataset.pestana);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }, { passive: true });

        aplicar();

        // ---- "Hecho" a trozos: la siguiente tanda llega sola al asomar el
        // botón (o al pulsarlo) ----
        var botonMas = document.getElementById('cargar-mas-hechos');
        if (botonMas) {
            var listaHechos = document.querySelector('#tablero-pedidos .lista-hechos');
            var cargando = false;
            var observador = null;
            function cargarMas() {
                if (cargando) return;
                cargando = true;
                botonMas.disabled = true;
                fetch('<?= site_url('piezas/pedidos/hechos') ?>?desde=' + botonMas.dataset.desde, {
                    credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d.ok) throw d;
                        listaHechos.insertAdjacentHTML('beforeend', d.html);
                        botonMas.dataset.desde = listaHechos.querySelectorAll(':scope > .tarjeta-pedido').length;
                        if (!d.quedan) {
                            if (observador) observador.disconnect();
                            botonMas.remove();
                        }
                    })
                    .catch(function () { alert('No se pudieron cargar más pedidos.'); })
                    .finally(function () { cargando = false; botonMas.disabled = false; });
            }
            botonMas.addEventListener('click', cargarMas);
            if ('IntersectionObserver' in window) {
                observador = new IntersectionObserver(function (entradas) {
                    // Oculto por el filtro de columnas no cuenta como visible.
                    if (entradas[0].isIntersecting && botonMas.offsetParent) cargarMas();
                }, { rootMargin: '200px' });
                observador.observe(botonMas);
            }
        }

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
                onStart: function () { arrastrando = true; },
                onEnd: function (evt) {
                    arrastrando = false;
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
