<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=13">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-pedidos.css') ?>?v=8">
<?php
    $tonos = ['idea' => 'e-neu', 'nuevo' => 'e-info', 'en_produccion' => 'e-warn', 'para_impresion' => 'e-acc', 'completado' => 'e-ok'];
    $abiertos = $columnas['nuevo']['total'] + $columnas['en_produccion']['total'] + $columnas['para_impresion']['total'];
    $vencidos = 0;
    $conEstrella = 0;
    foreach (['nuevo', 'en_produccion', 'para_impresion'] as $e) {
        foreach ($columnas[$e]['pedidos'] as $p) {
            $ent = \App\Models\PiezaPedidoModel::entrega($p);
            $vencidos += ($ent && $ent['vencido']) ? 1 : 0;
            $conEstrella += (int) $p['prioritario'] ? 1 : 0;
        }
    }
?>

<div class="fv pp" id="pp">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Pedidos</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/galeria') ?>" class="fv-gbtn" title="Piezas listas para imprimir"><i class="bi bi-grid-3x3-gap"></i> Galería</a>
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn" title="Histórico de placas (guardadas y descargadas)"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/pedidos?nueva=0') ?>" class="fv-gbtn" title="Volver al tablero de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <div class="pp-hero-fila">
            <h1>Pedidos</h1>
            <button type="button" class="fv-btn fv-btn-pri" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido"><i class="bi bi-plus-lg"></i> Nuevo pedido</button>
        </div>
        <div class="fv-meta">
            <span><b><?= $abiertos ?></b> abiertos</span>
            <span class="fv-dot"></span>
            <span class="fv-estado e-acc"><?= $columnas['para_impresion']['total'] ?> para impresión</span>
            <?php if ($vencidos): ?>
                <span class="fv-estado e-bad"><?= $vencidos ?> con la entrega pasada</span>
            <?php endif; ?>
            <?php if ($conEstrella): ?>
                <span class="fv-estado e-warn"><?= $conEstrella ?> con estrella</span>
            <?php endif; ?>
            <span class="fv-dot"></span>
            <span><?= $columnas['idea']['total'] ?> ideas</span>
            <span><?= $columnas['completado']['total'] ?> hechos</span>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="pp-barra">
        <nav class="fv-secs d-none d-md-flex" id="filtro-estados">
            <button type="button" data-filtro="">Todos</button>
            <?php foreach ($columnas as $estado => $columna): ?>
                <button type="button" data-filtro="<?= esc($estado) ?>"><?= esc($columna['titulo']) ?> <span><?= $columna['total'] ?></span></button>
            <?php endforeach; ?>
        </nav>
        <div class="pp-der">
            <button type="button" class="fv-gbtn" id="ocultar-ideas" aria-pressed="false" title="Quitar la columna Ideas del tablero">
                <i class="bi bi-eye-slash"></i> <span>Ocultar ideas</span> <span class="n"><?= $columnas['idea']['total'] ?></span>
            </button>
            <button type="button" class="fv-gbtn" id="ocultar-hechos" aria-pressed="false" title="Quitar la columna Hecho del tablero">
                <i class="bi bi-eye-slash"></i> <span>Ocultar hechos</span>
            </button>
            <span class="fv-vsep"></span>
            <div class="pp-seg" role="group" id="modo-orden" title="Cómo ordenar dentro de cada columna (las de estrella siempre arriba)">
                <button type="button" data-orden="manual"><i class="bi bi-arrows-move"></i> Manual</button>
                <button type="button" data-orden="entrega"><i class="bi bi-truck"></i> Por entrega</button>
            </div>
            <div class="pp-seg" role="group" id="modo-vista" title="Cómo se ven los pedidos">
                <button type="button" data-vista="tarjetas" title="Tarjetas"><i class="bi bi-grid-3x2-gap"></i></button>
                <button type="button" data-vista="lista" title="Lista"><i class="bi bi-list-ul"></i></button>
            </div>
        </div>
    </div>

    <?php $cortos = ['para_impresion' => 'Para imp.']; ?>
    <nav class="fv-secs d-md-none" id="pestanas-estados">
        <?php foreach ($columnas as $estado => $columna): ?>
            <button type="button" data-pestana="<?= esc($estado) ?>"><?= esc($cortos[$estado] ?? $columna['titulo']) ?> <span><?= $columna['total'] ?></span></button>
        <?php endforeach; ?>
    </nav>

    <div class="pp-tablero" id="tablero-pedidos">
        <?php foreach ($columnas as $estado => $columna): ?>
            <section class="pp-col columna-pedidos" data-columna="<?= esc($estado) ?>">
                <div class="pp-col-h d-none d-md-flex">
                    <span class="fv-estado <?= $tonos[$estado] ?>"><span><?= esc($columna['titulo']) ?></span></span>
                    <span class="n"><?= $columna['total'] ?></span>
                </div>
                <?php if (empty($columna['pedidos'])): ?>
                    <p class="fv-vacio">Nada aquí.</p>
                <?php endif; ?>
                <div class="pp-lista <?= $estado === 'completado' ? 'lista-hechos' : 'lista-pedidos' ?>" data-columna="<?= esc($estado) ?>">
                    <?php foreach ($columna['pedidos'] as $pedido): ?>
                        <?= view('piezas/pedidos/_tarjeta_nueva', ['pedido' => $pedido]) ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($estado === 'completado' && $columna['total'] > count($columna['pedidos'])): ?>
                    <button type="button" class="fv-btn fv-btn-sm" id="cargar-mas-hechos" data-desde="<?= count($columna['pedidos']) ?>">
                        <i class="bi bi-arrow-down-circle"></i> Cargar más
                    </button>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>

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
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="idea" value="1" id="nuevoComoIdea">
                    <label class="form-check-label small" for="nuevoComoIdea">Apuntar como idea</label>
                </div>
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const botones = Array.from(document.querySelectorAll('#filtro-estados [data-filtro]'));
        const pestanas = Array.from(document.querySelectorAll('#pestanas-estados [data-pestana]'));
        const columnas = Array.from(document.querySelectorAll('#tablero-pedidos .columna-pedidos'));
        const tablero = document.getElementById('tablero-pedidos');
        const botonOcultar = document.getElementById('ocultar-hechos');
        const botonOcultarIdeas = document.getElementById('ocultar-ideas');
        const movil = window.matchMedia('(max-width: 767.98px)');

        function leer(clave, porDefecto) {
            try { return localStorage.getItem(clave) ?? porDefecto; } catch (e) { return porDefecto; }
        }
        function guardar(clave, valor) {
            try { localStorage.setItem(clave, valor); } catch (e) {}
        }

        // Escritorio: filtro de las píldoras ('' = todas). Móvil: una sola
        // pestaña. "Ocultar hechos/ideas" quita esa columna de "todas" y de
        // las pestañas; pulsar su píldora a propósito la sigue enseñando.
        var filtro = '';
        var pestana = leer('pedidos.pestana', 'en_produccion');
        var ocultarHechos = leer('pedidos.ocultarHechos', '0') === '1';
        var ocultarIdeas = leer('pedidos.ocultarIdeas', '0') === '1';
        var arrastrando = false;

        function pestanasVisibles() {
            return pestanas.filter(function (p) { return !p.classList.contains('d-none'); });
        }

        function oculta(estado) {
            return (ocultarHechos && estado === 'completado') || (ocultarIdeas && estado === 'idea');
        }

        function pintarBotonOcultar(boton, oculto, nombre) {
            boton.classList.toggle('active', oculto);
            boton.setAttribute('aria-pressed', oculto ? 'true' : 'false');
            boton.querySelector('i').className = 'bi ' + (oculto ? 'bi-eye' : 'bi-eye-slash');
            boton.querySelector('span').textContent = (oculto ? 'Mostrar ' : 'Ocultar ') + nombre;
        }

        function aplicar() {
            pestanas.forEach(function (p) {
                p.classList.toggle('d-none', oculta(p.dataset.pestana));
            });
            if (!pestanasVisibles().some(function (p) { return p.dataset.pestana === pestana; })) {
                pestana = 'en_produccion';
            }

            columnas.forEach(function (col) {
                var estado = col.dataset.columna;
                var visible = movil.matches
                    ? estado === pestana
                    : (filtro === '' ? !oculta(estado) : estado === filtro);
                col.classList.toggle('d-none', !visible);
            });
            tablero.classList.toggle('pp-una', columnas.filter(function (c) { return !c.classList.contains('d-none'); }).length === 1);
            botones.forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.filtro === filtro);
            });
            pestanas.forEach(function (p) {
                var activa = p.dataset.pestana === pestana;
                p.classList.toggle('active', activa);
            });

            pintarBotonOcultar(botonOcultar, ocultarHechos, 'hechos');
            pintarBotonOcultar(botonOcultarIdeas, ocultarIdeas, 'ideas');
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
        botonOcultarIdeas.addEventListener('click', function () {
            ocultarIdeas = !ocultarIdeas;
            guardar('pedidos.ocultarIdeas', ocultarIdeas ? '1' : '0');
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

        var botonesVista = Array.from(document.querySelectorAll('#modo-vista [data-vista]'));
        function ponerVista(vista) {
            tablero.classList.toggle('pp-modo-lista', vista === 'lista');
            botonesVista.forEach(function (b) { b.classList.toggle('active', b.dataset.vista === vista); });
            guardar('pedidos.vista', vista);
        }
        botonesVista.forEach(function (b) {
            b.addEventListener('click', function () { ponerVista(b.dataset.vista); });
        });
        ponerVista(leer('pedidos.vista', 'tarjetas') === 'lista' ? 'lista' : 'tarjetas');

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
