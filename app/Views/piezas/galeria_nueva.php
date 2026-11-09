<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-index.css') ?>?v=6">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-galeria.css') ?>?v=4">

<input type="hidden" id="piezasCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>"
    data-carrito-base="<?= site_url('piezas/carrito/') ?>">

<?php
$estados = [
    'validada' => ['Definitiva', 'e-ok'],
    'impresa'  => ['Sin validar', 'e-info'],
    'borrador' => ['Para imprimir', 'e-warn'],
];

$piezasTodas = array_merge(...array_map(fn($g) => $g['piezas'], $grupos));

$cuentaEstado = ['validada' => 0, 'impresa' => 0, 'borrador' => 0];
$cuentaSinImagen = 0;
$cuentaPlaca  = ['en' => 0, 'fuera' => 0];
foreach ($piezasTodas as $p) {
    $cuentaEstado[$p['version']['estado']] = ($cuentaEstado[$p['version']['estado']] ?? 0) + 1;
    if (!$p['miniatura']) {
        $cuentaSinImagen++;
    }
    $enPlaca = in_array((int) $p['version']['id'], $carrito, true) || isset($desfasadas[(int) $p['variante']['id']]);
    $cuentaPlaca[$enPlaca ? 'en' : 'fuera']++;
}
$total = count($piezasTodas);
?>

<div class="fv pi gl" id="gl">
    <div class="fv-hero">
        <h1>Galería</h1>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/pedidos') ?>" class="fv-gbtn"><i class="bi bi-cart-check"></i> Pedidos</a>
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/galeria?nueva=0') ?>" class="fv-gbtn" title="Volver a la galería de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>
    <div class="fv-meta">
        <span><b><?= $total ?></b> piezas listas para meter en placa</span>
        <span class="fv-estado e-ok"><?= $cuentaEstado['validada'] ?> definitivas</span>
        <span class="fv-estado e-info"><?= $cuentaEstado['impresa'] ?> sin validar</span>
        <span class="fv-estado e-warn"><?= $cuentaEstado['borrador'] ?> para imprimir</span>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
        <p class="fv-vacio">Todavía no hay ninguna versión validada ni «para imprimir». En cuanto promociones o valides una aparecerá aquí.</p>
    <?php else: ?>
        <div class="pi-busca">
            <label class="pi-buscar">
                <i class="bi bi-search"></i>
                <input id="buscadorGaleria" type="search" placeholder="Buscar por nombre o SKU" autocomplete="off" aria-label="Buscar piezas">
                <span class="pi-cuenta" id="glCuenta"><?= $total ?> piezas</span>
                <span class="pi-kbd">/</span>
            </label>
        </div>

        <div class="pi-toolbar">
            <div class="pi-chips">
                <span class="pi-chips" id="filtrosEstadoGaleria">
                    <button type="button" class="pi-chip active" data-filtro-estado="">Todas <span class="n"><?= $total ?></span></button>
                    <?php foreach ($estados as $clave => [$texto, $clase]): ?>
                        <button type="button" class="pi-chip fv-estado <?= $clase ?>" data-filtro-estado="<?= $clave ?>" <?= $cuentaEstado[$clave] === 0 ? 'disabled' : '' ?>>
                            <span><?= $texto ?></span> <span class="n"><?= $cuentaEstado[$clave] ?></span>
                        </button>
                    <?php endforeach; ?>
                </span>
                <span class="fv-vsep"></span>
                <span class="pi-chips" id="filtrosImagenGaleria">
                    <button type="button" class="pi-chip" data-filtro-imagen="sin" title="Solo las que todavía no tienen ninguna foto" <?= $cuentaSinImagen === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-image"></i> Sin imagen <span class="n"><?= $cuentaSinImagen ?></span>
                    </button>
                </span>
                <span class="fv-vsep"></span>
                <span class="pi-chips" id="filtrosPlacaGaleria">
                    <button type="button" class="pi-chip" data-filtro-placa="en" <?= $cuentaPlaca['en'] === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-check-square"></i> En placa <span class="n" data-cuenta-placa-en><?= $cuentaPlaca['en'] ?></span>
                    </button>
                    <button type="button" class="pi-chip" data-filtro-placa="fuera" <?= $cuentaPlaca['fuera'] === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-square"></i> Sin placa <span class="n" data-cuenta-placa-fuera><?= $cuentaPlaca['fuera'] ?></span>
                    </button>
                </span>
            </div>
            <div class="pi-der">
                <button type="button" class="fv-gbtn" id="botonSeleccionarTodas" title="Añade a la placa todo lo que se ve con los filtros actuales">
                    <i class="bi bi-check2-all"></i> <span>Añadir visibles a la placa</span>
                </button>
            </div>
        </div>

        <p class="fv-vacio d-none" id="sinResultadosGaleria">Ninguna pieza coincide con los filtros.</p>

        <div class="gl-cats">
            <?php foreach ($grupos as $grupo): ?>
                <?php if (empty($grupo['piezas'])) continue; ?>
                <?php $categoria = $grupo['categoria']; ?>
                <?php $idGrupo = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin'; ?>
                <section class="pi-cat" data-grupo>
                    <div class="pi-cat-h" data-plegar="<?= $idGrupo ?>">
                        <i class="bi bi-chevron-down chev"></i>
                        <h2 class="<?= $categoria ? '' : 'sin' ?>"><?= $categoria ? esc($categoria['nombre']) : 'Sin clasificar' ?></h2>
                        <span class="n" data-cuenta><?= count($grupo['piezas']) ?></span>
                    </div>

                    <div class="gl-grid pi-cuerpo" id="<?= $idGrupo ?>">
                        <?php foreach ($grupo['piezas'] as $p): ?>
                            <?php
                                $variante = $p['variante'];
                                $version  = $p['version'];
                                $enCarrito = in_array((int) $version['id'], $carrito, true);
                                $desfasada = !$enCarrito && isset($desfasadas[(int) $variante['id']]);
                                $stls      = (int) ($p['stls'] ?? 0);
                                $buscable = mb_strtolower(trim(
                                    $p['familiaNombre'] . ' ' . $variante['nombre'] . ' ' . ($variante['sku'] ?? '')
                                ));
                                $apellido = ($variante['nombre'] !== \App\Services\PiezaService::VARIANTE_BASE
                                        || $p['variosVariantes'])
                                    ? $variante['nombre']
                                    : null;
                                $nombrePiezaPlano = $p['familiaNombre'] . ($apellido !== null ? ' - ' . $apellido : '');
                                [$estTexto, $estClase] = $estados[$version['estado']] ?? ['', 'e-neu'];
                                $urlFicha = site_url('piezas/variante/' . (int) $variante['id']);
                            ?>
                            <div class="gl-card" data-tarjeta data-estado="<?= esc($version['estado'], 'attr') ?>"
                                data-imagen="<?= $p['miniatura'] ? 'con' : 'sin' ?>" data-placa="<?= $enCarrito || $desfasada ? 'en' : 'fuera' ?>"
                                data-buscar="<?= esc($buscable, 'attr') ?>"
                                data-nombre-pieza="<?= esc($nombrePiezaPlano, 'attr') ?>"
                                data-version-tarjeta="<?= (int) $version['id'] ?>">
                                <a href="<?= $urlFicha ?>" class="gl-foto">
                                    <?php if ($p['miniatura']): ?>
                                        <img src="<?= $p['miniatura'] ?>" alt="<?= esc($p['familiaNombre']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <i class="bi bi-box"></i>
                                    <?php endif; ?>
                                </a>
                                <span class="gl-ver fv-estado <?= $estClase ?>" title="<?= esc($estTexto, 'attr') ?>">v<?= sprintf('%03d', (int) $version['numero']) ?></span>
                                <span class="gl-en" title="En la placa"><i class="bi bi-check-lg"></i></span>
                                <?php if ($p['miniatura']): ?>
                                    <a href="<?= $p['foto'] ?>" target="_blank" rel="noopener" class="fv-ibtn gl-ojo" title="Ver foto en grande"><i class="bi bi-arrows-angle-expand"></i></a>
                                <?php endif; ?>

                                <div class="gl-cuerpo">
                                    <a href="<?= $urlFicha ?>" class="gl-nom" title="<?= esc($nombrePiezaPlano, 'attr') ?>"><?= esc($p['familiaNombre']) ?><?php
                                        if ($apellido !== null): ?><span> - <?= esc($apellido) ?></span><?php endif; ?></a>
                                    <div class="gl-lin">
                                        <?php if (!empty($variante['sku'])): ?>
                                            <span class="fv-mono"><?= esc($variante['sku']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['stock'] !== null): ?>
                                            <?php $bajo = $p['stock']['stock_actual'] <= $p['stock']['stock_minimo']; ?>
                                            <span class="<?= $bajo ? 'bajo' : '' ?>" title="Stock en sterclicks (mínimo <?= (int) $p['stock']['stock_minimo'] ?>)"><i class="bi bi-box-seam"></i> <?= $p['stock']['stock_actual'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($stls > 1): ?>
                                            <span title="Se imprime en <?= $stls ?> trozos"><i class="bi bi-boxes"></i> <?= $stls ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($desfasada): ?>
                                        <button type="button" class="gl-btn act"
                                            data-actualizar-carrito data-vieja-id="<?= (int) $desfasadas[(int) $variante['id']] ?>"
                                            data-version-id="<?= (int) $version['id'] ?>" data-en-carrito="1"
                                            title="En la placa está una versión anterior">
                                            <i class="bi bi-arrow-up-circle"></i> Actualizar a v<?= sprintf('%03d', (int) $version['numero']) ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="gl-btn <?= $enCarrito ? 'en' : '' ?>"
                                            data-carrito-boton data-version-id="<?= (int) $version['id'] ?>"
                                            data-en-carrito="<?= $enCarrito ? '1' : '0' ?>">
                                            <i class="bi <?= $enCarrito ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i> <?= $enCarrito ? 'En la placa' : 'Añadir a la placa' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="gl-placa <?= empty($carrito) ? 'd-none' : '' ?>" id="cabeceraCarrito">
        <span class="gl-placa-t"><i class="bi bi-printer"></i> Placa <b id="contadorPlaca"><?= count($carrito) ?></b></span>
        <div class="gl-placa-acc">
            <button type="button" class="fv-gbtn peligro" id="botonVaciarPlaca"><i class="bi bi-x-lg"></i> Vaciar</button>
            <?php if (!empty($desfasadas)): ?>
                <button type="button" class="fv-gbtn on" id="botonActualizarVersiones"
                    title="Pasa las piezas de la placa a su última versión para imprimir">
                    <i class="bi bi-arrow-up-circle"></i> Actualizar versiones (<?= count($desfasadas) ?>)
                </button>
            <?php endif; ?>
            <button type="button" class="fv-gbtn" id="botonComandoStl"
                data-url="<?= site_url('piezas/carrito/comando-stl') ?>"
                title="Copia el comando de stl.py que genera los STL de estas piezas, sin crear ninguna placa">
                <i class="bi bi-clipboard"></i> <span>Solo STL</span>
            </button>
            <button type="button" class="fv-btn fv-btn-pri" data-bs-toggle="modal" data-bs-target="#modalNombrePlaca">
                <i class="bi bi-journal-plus"></i> Crear placa
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNombrePlaca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= site_url('piezas/carrito/crear-placa') ?>" class="modal-content" id="formNombrePlaca">
            <?= csrf_field() ?>
            <div class="modal-header py-2">
                <h6 class="modal-title">Crear placa</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small mb-1" for="campoNombrePlaca">Nombre de la placa</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text text-body-secondary" title="Se pone solo: el número de la placa">#nº</span>
                    <input type="text" name="nombre" class="form-control form-control-sm" maxlength="140"
                        id="campoNombrePlaca" autocomplete="off"
                        placeholder="Placa <?= esc(date('d/m/Y H:i'), 'attr') ?>">
                </div>
                <div class="form-text">
                    Para reconocerla en el histórico. Delante se le pone su número (<code>#48 …</code>).
                    Sugerido a partir de lo que lleva — cámbialo si quieres.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-success">
                    <i class="bi bi-journal-plus"></i> Crear placa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Sugerencia del nombre a partir de lo que lleva la placa ahora mismo:
    // las piezas en el carrito, unidas por coma. Se recalcula cada vez que
    // se abre el modal, no solo la primera — si el carrito cambió desde la
    // última vez, la sugerencia también. select() al final para que
    // escribir un nombre propio sea sustituir, no primero borrar a mano.
    var formNombre = document.getElementById('formNombrePlaca');
    if (formNombre) {
        document.getElementById('modalNombrePlaca').addEventListener('shown.bs.modal', function () {
            var campo = document.getElementById('campoNombrePlaca');
            var nombres = [];
            document.querySelectorAll('[data-tarjeta][data-placa="en"]').forEach(function (t) {
                var n = t.getAttribute('data-nombre-pieza');
                if (n) nombres.push(n);
            });
            var sugerido = nombres.join(', ');
            if (sugerido.length > 150) sugerido = sugerido.slice(0, 149) + '…';
            campo.value = sugerido;
            campo.focus();
            campo.select();
        });
    }

    // Plegar categorías, igual que en el índice pero con su propia clave de
    // localStorage: son dos pantallas distintas y no tiene por qué coincidir
    // qué categorías tienes plegadas en cada una.
    var CERRADAS = 'piezas_galeria_categorias_cerradas';

    function cerradas() {
        try { return JSON.parse(localStorage.getItem(CERRADAS)) || []; } catch (e) { return []; }
    }

    function pintar(id, abierta) {
        var cabecera = document.querySelector('[data-plegar="' + id + '"]');
        if (!cabecera) return;
        cabecera.closest('[data-grupo]').classList.toggle('plegada', !abierta);

    }

    cerradas().forEach(function (id) { pintar(id, false); });

    document.querySelectorAll('[data-plegar]').forEach(function (cabecera) {
        cabecera.addEventListener('click', function (e) {
            if (e.target.closest('form, a')) return;

            var id = cabecera.getAttribute('data-plegar');
            var lista = cerradas();
            var estabaCerrada = lista.indexOf(id) !== -1;

            lista = estabaCerrada ? lista.filter(function (x) { return x !== id; }) : lista.concat([id]);
            localStorage.setItem(CERRADAS, JSON.stringify(lista));
            pintar(id, estabaCerrada);
        });
    });

    // ---- Mostrar/ocultar filtros ------------------------------------------
    // Mismo patrón que el índice: plegados por defecto, se recuerda si se
    // han dejado abiertos.
    var FILTROS_ABIERTOS_GALERIA = 'piezas_galeria_filtros_abiertos';
    var btnFiltrosGaleria = document.getElementById('btnFiltrosGaleria');
    var panelFiltrosGaleria = document.getElementById('panelFiltrosGaleria');

    function pintarFiltrosGaleria(abierto) {
        if (btnFiltrosGaleria) {
            btnFiltrosGaleria.classList.toggle('active', abierto);
            btnFiltrosGaleria.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        }
        if (panelFiltrosGaleria) panelFiltrosGaleria.classList.toggle('d-none', !abierto);
    }

    pintarFiltrosGaleria(localStorage.getItem(FILTROS_ABIERTOS_GALERIA) === '1');

    if (btnFiltrosGaleria) {
        btnFiltrosGaleria.addEventListener('click', function () {
            var abrir = !panelFiltrosGaleria || panelFiltrosGaleria.classList.contains('d-none');
            localStorage.setItem(FILTROS_ABIERTOS_GALERIA, abrir ? '1' : '0');
            pintarFiltrosGaleria(abrir);
        });
    }

    // ---- Filtros: estado, STL y placa, se combinan entre sí ---------------
    // A diferencia del filtro único del índice, aquí interesa cruzarlos:
    // "para imprimir" + "sin STL" es justo la pregunta de "qué me falta
    // exportar ya".
    var cajaEstado = document.getElementById('filtrosEstadoGaleria');
    var cajaImagen = document.getElementById('filtrosImagenGaleria');
    var cajaPlaca = document.getElementById('filtrosPlacaGaleria');
    var sinResultados = document.getElementById('sinResultadosGaleria');
    var buscador = document.getElementById('buscadorGaleria');
    var filtroEstado = '';
    var filtroImagen = '';
    var filtroPlaca = '';

    function sinTildes(t) {
        return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function aplicarFiltrosGaleria() {
        // El texto entra en la misma pasada que los tres facetados, no en una
        // suya aparte: dos recorridos independientes se pisarían el uno al
        // otro (el segundo volvería a enseñar lo que el primero escondió).
        var q = buscador ? sinTildes(buscador.value.trim()) : '';
        var recortando = q !== '' || filtroEstado !== '' || filtroImagen !== '' || filtroPlaca !== '';
        var encontradas = 0;

        document.querySelectorAll('[data-tarjeta]').forEach(function (tarjeta) {
            var visible = (q === '' || sinTildes(tarjeta.getAttribute('data-buscar')).indexOf(q) !== -1)
                && (filtroEstado === '' || tarjeta.getAttribute('data-estado') === filtroEstado)
                && (filtroImagen === '' || tarjeta.getAttribute('data-imagen') === filtroImagen)
                && (filtroPlaca === '' || tarjeta.getAttribute('data-placa') === filtroPlaca);
            tarjeta.classList.toggle('d-none', !visible);
            if (visible) encontradas++;
        });

        // Igual que el plegado: la cabecera de cada categoría vive en el
        // mismo <div data-grupo> que el cuerpo con el id "cat-N".
        document.querySelectorAll('[data-grupo]').forEach(function (grupo) {
            var cuerpo = grupo.querySelector('[id^="cat-"]');
            if (!cuerpo) return;
            var visibles = cuerpo.querySelectorAll('[data-tarjeta]:not(.d-none)').length;
            var contador = grupo.querySelector('[data-cuenta]');

            if (!recortando) {
                grupo.classList.remove('d-none');
                if (contador) contador.textContent = cuerpo.querySelectorAll('[data-tarjeta]').length;
                pintar(cuerpo.id, cerradas().indexOf(cuerpo.id) === -1);
                return;
            }

            grupo.classList.toggle('d-none', visibles === 0);
            if (contador) contador.textContent = visibles;
            // Abre el grupo si tiene algo que enseñar mientras se filtra; si
            // no tiene nada, la cabecera entera se esconde y su flecha da igual.
            pintar(cuerpo.id, visibles > 0);
        });

        if (sinResultados) sinResultados.classList.toggle('d-none', !recortando || encontradas > 0);
        var cuenta = document.getElementById('glCuenta');
        var todas = document.querySelectorAll('[data-tarjeta]').length;
        if (cuenta) cuenta.innerHTML = recortando ? '<b>' + encontradas + '</b> de ' + todas : todas + ' piezas';
    }

    function engancharFacet(caja, atributo, obtenerValor, fijarValor) {
        if (!caja) return;
        caja.addEventListener('click', function (e) {
            var boton = e.target.closest('[' + atributo + ']');
            if (!boton || boton.disabled) return;

            var pedido = boton.getAttribute(atributo);
            var nuevo = (pedido === obtenerValor()) ? '' : pedido;
            fijarValor(nuevo);

            caja.querySelectorAll('[' + atributo + ']').forEach(function (b) {
                b.classList.toggle('active', b.getAttribute(atributo) === nuevo);
            });

            aplicarFiltrosGaleria();
        });
    }

    engancharFacet(cajaEstado, 'data-filtro-estado',
        function () { return filtroEstado; }, function (v) { filtroEstado = v; });
    engancharFacet(cajaImagen, 'data-filtro-imagen',
        function () { return filtroImagen; }, function (v) { filtroImagen = v; });
    engancharFacet(cajaPlaca, 'data-filtro-placa',
        function () { return filtroPlaca; }, function (v) { filtroPlaca = v; });

    if (buscador) buscador.addEventListener('input', aplicarFiltrosGaleria);
    document.addEventListener('keydown', function (e) {
        var enCampo = /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName);
        if (e.key === '/' && buscador && !enCampo && !document.querySelector('.modal.show')) { e.preventDefault(); buscador.focus(); buscador.select(); }
    });

    // ---- Placa: añadir/quitar/vaciar sin recargar la página --------------
    // Con <form> normal, cada "Añadir a la placa" recargaba y se perdía el
    // filtro (fase 29) en el que estabas trabajando. Con fetch() la página
    // no se mueve de sitio.
    var tokenCampo = document.getElementById('piezasCsrfToken');
    var baseCarrito = tokenCampo ? tokenCampo.dataset.carritoBase : '';
    var cabeceraCarrito = document.getElementById('cabeceraCarrito');
    var contadorPlaca = document.getElementById('contadorPlaca');

    function pintarBotonPlaca(boton, enCarrito) {
        boton.setAttribute('data-en-carrito', enCarrito ? '1' : '0');
        boton.classList.toggle('en', enCarrito);
        var icono = boton.querySelector('i');
        if (icono) {
            icono.classList.toggle('bi-check-lg', enCarrito);
            icono.classList.toggle('bi-plus-lg', !enCarrito);
        }
        boton.lastChild.textContent = enCarrito ? ' En la placa' : ' Añadir a la placa';
    }

    function actualizarContadorPlaca(total) {
        if (contadorPlaca) contadorPlaca.textContent = total;
        if (cabeceraCarrito) cabeceraCarrito.classList.toggle('d-none', total === 0);
    }

    // La tarjeta necesita su propio data-placa (independiente del botón)
    // para que el filtro "En placa"/"Sin placa" se pueda leer sin recorrer
    // botones — y para que quede al día en cuanto se añade o se quita algo,
    // sin recargar la página (mismo espíritu que el resto de esta fase).
    function pintarTarjetaPlaca(versionId, enCarrito) {
        var tarjeta = document.querySelector('[data-version-tarjeta="' + versionId + '"]');
        if (tarjeta) tarjeta.setAttribute('data-placa', enCarrito ? 'en' : 'fuera');
    }

    function actualizarContadoresFiltroPlaca() {
        var en = document.querySelectorAll('[data-tarjeta][data-placa="en"]').length;
        var fuera = document.querySelectorAll('[data-tarjeta][data-placa="fuera"]').length;
        var badgeEn = document.querySelector('[data-cuenta-placa-en]');
        var badgeFuera = document.querySelector('[data-cuenta-placa-fuera]');
        if (badgeEn) badgeEn.textContent = en;
        if (badgeFuera) badgeFuera.textContent = fuera;
        if (cajaPlaca) {
            cajaPlaca.querySelectorAll('[data-filtro-placa]').forEach(function (b) {
                b.disabled = (b.getAttribute('data-filtro-placa') === 'en' ? en : fuera) === 0;
            });
        }
    }

    function llamadaPlaca(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenCampo ? tokenCampo.value : ''
            }
        }).then(function (r) { return r.json(); });
    }

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-carrito-boton]');
        if (!boton) return;

        var versionId = boton.getAttribute('data-version-id');
        var enCarrito = boton.getAttribute('data-en-carrito') === '1';
        var url = baseCarrito + (enCarrito ? 'quitar/' : 'agregar/') + versionId;

        boton.disabled = true;
        llamadaPlaca(url).then(function (datos) {
            boton.disabled = false;
            if (!datos.ok) {
                alert(datos.mensaje || 'No se pudo actualizar la placa.');
                return;
            }
            pintarBotonPlaca(boton, datos.enCarrito);
            actualizarContadorPlaca(datos.total);
            pintarTarjetaPlaca(versionId, datos.enCarrito);
            actualizarContadoresFiltroPlaca();
            if (filtroPlaca !== '') aplicarFiltrosGaleria();
        }).catch(function () {
            boton.disabled = false;
            alert('No se pudo hablar con el servidor.');
        });
    });

    // ---- Piezas de la placa con una versión más nueva ---------------------
    // La tarjeta enseña la última versión para imprimir; si en la placa está
    // una anterior, su botón la cambia en vez de añadir otra. Una vez
    // cambiada, el botón pasa a ser el normal de "En la placa".
    function convertirEnBotonPlaca(boton) {
        boton.removeAttribute('data-actualizar-carrito');
        boton.removeAttribute('title');
        boton.setAttribute('data-carrito-boton', '');
        boton.classList.remove('act');
        var icono = boton.querySelector('i');
        if (icono) icono.className = 'bi';
    }

    var botonActualizarVersiones = document.getElementById('botonActualizarVersiones');

    function pintarBotonActualizarVersiones() {
        if (!botonActualizarVersiones) return;
        var quedan = document.querySelectorAll('[data-actualizar-carrito]').length;
        botonActualizarVersiones.classList.toggle('d-none', quedan === 0);
        botonActualizarVersiones.lastChild.textContent = ' Actualizar versiones (' + quedan + ')';
    }

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-actualizar-carrito]');
        if (!boton) return;

        boton.disabled = true;
        llamadaPlaca(baseCarrito + 'actualizar/' + boton.getAttribute('data-vieja-id')).then(function (datos) {
            boton.disabled = false;
            if (!datos.ok) {
                alert(datos.mensaje || 'No se pudo actualizar la pieza.');
                return;
            }
            convertirEnBotonPlaca(boton);
            pintarBotonPlaca(boton, true);
            actualizarContadorPlaca(datos.total);
            pintarBotonActualizarVersiones();
        }).catch(function () {
            boton.disabled = false;
            alert('No se pudo hablar con el servidor.');
        });
    });

    if (botonActualizarVersiones) {
        botonActualizarVersiones.addEventListener('click', function () {
            botonActualizarVersiones.disabled = true;
            llamadaPlaca(baseCarrito + 'actualizar-todas').then(function (datos) {
                botonActualizarVersiones.disabled = false;
                if (!datos.ok) return;
                document.querySelectorAll('[data-actualizar-carrito]').forEach(function (b) {
                    convertirEnBotonPlaca(b);
                    pintarBotonPlaca(b, true);
                });
                actualizarContadorPlaca(datos.total);
                pintarBotonActualizarVersiones();
            }).catch(function () {
                botonActualizarVersiones.disabled = false;
                alert('No se pudo hablar con el servidor.');
            });
        });
    }

    var botonComandoStl = document.getElementById('botonComandoStl');
    if (botonComandoStl) {
        botonComandoStl.addEventListener('click', function () {
            var texto = botonComandoStl.querySelector('span');
            fetch(botonComandoStl.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (datos) {
                    if (!datos.ok) return;
                    var copiar = navigator.clipboard
                        ? navigator.clipboard.writeText(datos.comando)
                        : Promise.reject();
                    copiar.then(function () {
                        texto.textContent = 'Copiado (' + datos.total + ')';
                        setTimeout(function () { texto.textContent = 'Solo STL'; }, 2000);
                    }).catch(function () {
                        prompt('Copia el comando:', datos.comando);
                    });
                }).catch(function () {
                    alert('No se pudo hablar con el servidor.');
                });
        });
    }

    var botonVaciar = document.getElementById('botonVaciarPlaca');
    if (botonVaciar) {
        botonVaciar.addEventListener('click', function () {
            if (!confirm('¿Vaciar la placa?')) return;

            llamadaPlaca(baseCarrito + 'vaciar').then(function (datos) {
                if (!datos.ok) return;
                document.querySelectorAll('[data-actualizar-carrito]').forEach(convertirEnBotonPlaca);
                pintarBotonActualizarVersiones();
                document.querySelectorAll('[data-carrito-boton]').forEach(function (b) {
                    pintarBotonPlaca(b, false);
                });
                document.querySelectorAll('[data-tarjeta]').forEach(function (t) {
                    t.setAttribute('data-placa', 'fuera');
                });
                actualizarContadorPlaca(0);
                actualizarContadoresFiltroPlaca();
                if (filtroPlaca !== '') aplicarFiltrosGaleria();
            });
        });
    }

    // ---- Añadir todas las visibles a la placa -----------------------------
    // Una por una (no en paralelo): así el contador y las tarjetas se van
    // pintando según llegan las respuestas, en vez de todas de golpe al
    // final, y si algo falla a mitad no deja llamadas sueltas por resolver.
    var botonSeleccionarTodas = document.getElementById('botonSeleccionarTodas');
    if (botonSeleccionarTodas) {
        var rotuloTodas = botonSeleccionarTodas.querySelector('span');
        var textoSeleccionarTodas = rotuloTodas.textContent;

        botonSeleccionarTodas.addEventListener('click', function () {
            // Todas las tarjetas visibles que no estén ya en la placa —
            // incluidas las que aún no tienen STL (se generan en local luego).
            var pendientes = [];
            document.querySelectorAll('[data-tarjeta]:not(.d-none) [data-carrito-boton]').forEach(function (b) {
                if (b.getAttribute('data-en-carrito') !== '1') pendientes.push(b);
            });

            if (pendientes.length === 0) {
                alert('No hay ninguna pieza visible que añadir (o ya están todas en la placa).');
                return;
            }

            botonSeleccionarTodas.disabled = true;

            function siguiente(i) {
                if (i >= pendientes.length) {
                    botonSeleccionarTodas.disabled = false;
                    rotuloTodas.textContent = textoSeleccionarTodas;
                    actualizarContadoresFiltroPlaca();
                    if (filtroPlaca !== '') aplicarFiltrosGaleria();
                    return;
                }

                rotuloTodas.textContent = 'Añadiendo… ' + (i + 1) + '/' + pendientes.length;

                var boton = pendientes[i];
                var versionId = boton.getAttribute('data-version-id');

                llamadaPlaca(baseCarrito + 'agregar/' + versionId).then(function (datos) {
                    if (datos.ok) {
                        pintarBotonPlaca(boton, true);
                        actualizarContadorPlaca(datos.total);
                        pintarTarjetaPlaca(versionId, true);
                    }
                    siguiente(i + 1);
                }).catch(function () { siguiente(i + 1); });
            }

            siguiente(0);
        });
    }
})();
</script>

<?= $this->endSection() ?>
