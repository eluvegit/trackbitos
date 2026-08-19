<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<!-- Token para los fetch() de la placa (añadir/quitar/vaciar sin recargar,
     así no se pierde el filtro de la galería — fase 32). Con
     $config->regenerate = false el hash vale para toda la sesión: basta
     leerlo una vez aquí. -->
<input type="hidden" id="piezasCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>"
    data-carrito-base="<?= site_url('piezas/carrito/') ?>">

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-grid-3x3-gap text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Galería</strong>

    <div class="ms-auto d-flex gap-2 <?= empty($carrito) ? 'd-none' : '' ?>" id="cabeceraCarrito">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="botonVaciarPlaca">Vaciar placa</button>
        <a href="<?= site_url('piezas/carrito/descargar') ?>" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-zip"></i> Descargar placa (<span id="contadorPlaca"><?= count($carrito) ?></span>)
        </a>
    </div>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Piezas validadas, y también las que ya tienen una versión "para imprimir" o "impresa, sin
    validar" — esta pantalla es para meter STL en placas, y esas dos ya pueden tener uno adjunto
    aunque el resultado físico no esté juzgado todavía. Añade a la placa las que quieras imprimir
    juntas y descarga todos los STL de golpe en un .zip para el laminador.
</p>

<?php
/**
 * Qué le falta a la versión que se ofrece, cuando no es la validada — mismo
 * vocabulario y estilo que `$badgeMadurez` del índice, para no decir dos
 * cosas distintas de lo mismo según la pantalla.
 */
$badgeEstadoVersion = static function (array $version): string {
    if ($version['estado'] === 'impresa') {
        return '<span class="badge text-bg-primary" title="Impresa, pendiente de juzgar el resultado">'
            . '<i class="bi bi-printer-fill"></i> sin validar</span>';
    }
    if ($version['estado'] === 'borrador') {
        return '<span class="badge text-bg-secondary" title="Promocionada, pendiente de imprimir de prueba">'
            . '<i class="bi bi-printer"></i> para imprimir</span>';
    }

    return '';
};

/** Todas las tarjetas en un único array, para contar sin recorrer $grupos dos veces distinto cada vez. */
$piezasTodas = array_merge(...array_map(fn($g) => $g['piezas'], $grupos));

$cuentaEstado = ['validada' => 0, 'impresa' => 0, 'borrador' => 0];
$cuentaStl    = ['con' => 0, 'sin' => 0];
foreach ($piezasTodas as $p) {
    $cuentaEstado[$p['version']['estado']] = ($cuentaEstado[$p['version']['estado']] ?? 0) + 1;
    $cuentaStl[$p['stls'] > 0 ? 'con' : 'sin']++;
}
?>

<?php $total = count($piezasTodas); ?>

<?php if ($total === 0): ?>
    <p class="text-muted">Todavía no hay ninguna versión validada, ni ninguna "para imprimir". En cuanto promociones o valides una aparecerá aquí.</p>
<?php else: ?>
    <?php // Dos preguntas distintas (qué estado, si tiene STL) que sí se combinan entre sí — a diferencia
          // del filtro único del índice, aquí interesa cruzarlas ("para imprimir" + "sin STL" = qué exportar ya). ?>
    <div class="d-flex flex-wrap gap-1 mb-1" id="filtrosEstadoGaleria">
        <button type="button" class="btn btn-sm btn-outline-secondary active" data-filtro-estado=""
            title="Quitar el filtro de estado">
            Todas <span class="badge text-bg-secondary"><?= $total ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-success" data-filtro-estado="validada"
            title="Solo las validadas" <?= $cuentaEstado['validada'] === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-check-circle-fill"></i> Validada
            <span class="badge text-bg-<?= $cuentaEstado['validada'] === 0 ? 'secondary' : 'success' ?>"><?= $cuentaEstado['validada'] ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-filtro-estado="impresa"
            title="Impresas, pendientes de juzgar el resultado" <?= $cuentaEstado['impresa'] === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-printer-fill"></i> Sin validar
            <span class="badge text-bg-<?= $cuentaEstado['impresa'] === 0 ? 'secondary' : 'primary' ?>"><?= $cuentaEstado['impresa'] ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-estado="borrador"
            title="Promocionadas, pendientes de imprimir de prueba" <?= $cuentaEstado['borrador'] === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-printer"></i> Para imprimir
            <span class="badge text-bg-secondary"><?= $cuentaEstado['borrador'] ?></span>
        </button>
    </div>
    <div class="d-flex flex-wrap gap-1 mb-3" id="filtrosStlGaleria">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-stl="con"
            title="Solo las que ya tienen STL adjunto" <?= $cuentaStl['con'] === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-file-earmark-check"></i> Con STL
            <span class="badge text-bg-secondary"><?= $cuentaStl['con'] ?></span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-stl="sin"
            title="Solo las que todavía no tienen ningún STL" <?= $cuentaStl['sin'] === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-exclamation-circle"></i> Sin STL
            <span class="badge text-bg-secondary"><?= $cuentaStl['sin'] ?></span>
        </button>
    </div>

    <p class="text-muted small d-none" id="sinResultadosGaleria">Ninguna pieza coincide con los filtros.</p>

    <?php foreach ($grupos as $grupo): ?>
        <?php if (empty($grupo['piezas'])) continue; // Categoría vacía: aquí no aporta nada, a diferencia del índice. ?>
        <?php $categoria = $grupo['categoria']; ?>
        <?php $idGrupo = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin'; ?>
        <div class="mb-3" data-grupo>
            <?php // Igual que en el índice: pliega toda la línea, no solo la flecha. ?>
            <div class="d-flex align-items-center gap-2 border-bottom pb-1 mb-2 user-select-none"
                style="cursor: pointer" data-plegar="<?= $idGrupo ?>">
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-body"
                    aria-controls="<?= $idGrupo ?>" aria-expanded="true">
                    <i class="bi bi-chevron-down" data-chevron></i>
                </button>
                <span class="fw-semibold text-uppercase small <?= $categoria ? '' : 'text-muted fst-italic' ?>">
                    <?= $categoria ? esc($categoria['nombre']) : 'Sin clasificar' ?>
                </span>
                <span class="badge border text-body-secondary"><?= count($grupo['piezas']) ?></span>
            </div>

            <div id="<?= $idGrupo ?>">
                <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-2">
                    <?php foreach ($grupo['piezas'] as $p): ?>
                        <?php
                            $variante = $p['variante'];
                            $version  = $p['version'];
                            $esValidada = $version['estado'] === 'validada';
                            $enCarrito = in_array((int) $version['id'], $carrito, true);
                            $stls      = (int) ($p['stls'] ?? 0);
                            $tieneStl  = $stls > 0;
                        ?>
                        <div class="col" data-tarjeta data-estado="<?= esc($version['estado'], 'attr') ?>"
                            data-stl="<?= $tieneStl ? 'con' : 'sin' ?>">
                            <div class="card shadow-sm h-100">
                                <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>">
                                    <?php if ($p['miniatura']): ?>
                                        <img src="<?= $p['miniatura'] ?>" class="card-img-top" style="aspect-ratio: 1; object-fit: cover;"
                                            alt="<?= esc($p['familiaNombre']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center bg-body-secondary text-muted"
                                            style="aspect-ratio: 1;">
                                            <i class="bi bi-box" style="font-size: 1.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="card-body p-2">
                                    <div class="small fw-semibold text-truncate">
                                        <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>"
                                            class="text-decoration-none text-body"><?= esc($p['familiaNombre']) ?></a>
                                    </div>
                                    <?php if ($p['variosVariantes']): ?>
                                        <div class="text-muted small text-truncate"><?= esc($variante['nombre']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted small d-flex align-items-center flex-wrap gap-1">
                                        <?php if ($esValidada): ?>
                                            <span class="badge text-bg-success">
                                                <i class="bi bi-check-circle-fill"></i> v<?= sprintf('%03d', (int) $version['numero']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span>v<?= sprintf('%03d', (int) $version['numero']) ?></span>
                                            <?= $badgeEstadoVersion($version) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($variante['sku'])): ?>
                                            <span><?= esc($variante['sku']) ?></span>
                                        <?php endif; ?>
                                        <?php // Se imprime en trozos: saberlo aquí evita mandar a la placa media pieza creyendo que va entera. ?>
                                        <?php if ($stls > 1): ?>
                                            <span title="Se imprime en <?= $stls ?> trozos"><i class="bi bi-boxes"></i> <?= $stls ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$tieneStl): ?>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-exclamation-circle"></i> sin STL — adjúntalo desde la ficha
                                        </div>
                                    <?php else: ?>
                                        <?php // Botón único con estado, movido por fetch() (fase 32): un
                                              // <form> con recarga completa perdía el filtro en el que
                                              // estabas trabajando cada vez que añadías una pieza. ?>
                                        <button type="button" class="btn btn-sm w-100 py-0 mt-1
                                            <?= $enCarrito ? 'btn-success' : 'btn-outline-primary' ?>"
                                            data-carrito-boton data-version-id="<?= (int) $version['id'] ?>"
                                            data-en-carrito="<?= $enCarrito ? '1' : '0' ?>">
                                            <i class="bi <?= $enCarrito ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                                            <?= $enCarrito ? 'En la placa' : 'Añadir a la placa' ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
(function () {
    // Plegar categorías, igual que en el índice pero con su propia clave de
    // localStorage: son dos pantallas distintas y no tiene por qué coincidir
    // qué categorías tienes plegadas en cada una.
    var CERRADAS = 'piezas_galeria_categorias_cerradas';

    function cerradas() {
        try { return JSON.parse(localStorage.getItem(CERRADAS)) || []; } catch (e) { return []; }
    }

    function pintar(id, abierta) {
        var cuerpo = document.getElementById(id);
        if (!cuerpo) return;
        cuerpo.classList.toggle('d-none', !abierta);

        var cabecera = document.querySelector('[data-plegar="' + id + '"]');
        if (!cabecera) return;

        var chevron = cabecera.querySelector('[data-chevron]');
        if (chevron) {
            chevron.classList.toggle('bi-chevron-down', abierta);
            chevron.classList.toggle('bi-chevron-right', !abierta);
        }
        var boton = cabecera.querySelector('[aria-controls]');
        if (boton) boton.setAttribute('aria-expanded', abierta ? 'true' : 'false');
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

    // ---- Filtros: estado y STL, se combinan entre sí ---------------------
    // A diferencia del filtro único del índice, aquí interesa cruzarlos:
    // "para imprimir" + "sin STL" es justo la pregunta de "qué me falta
    // exportar ya".
    var cajaEstado = document.getElementById('filtrosEstadoGaleria');
    var cajaStl = document.getElementById('filtrosStlGaleria');
    var sinResultados = document.getElementById('sinResultadosGaleria');
    var filtroEstado = '';
    var filtroStl = '';

    function aplicarFiltrosGaleria() {
        var recortando = filtroEstado !== '' || filtroStl !== '';
        var encontradas = 0;

        document.querySelectorAll('[data-tarjeta]').forEach(function (tarjeta) {
            var visible = (filtroEstado === '' || tarjeta.getAttribute('data-estado') === filtroEstado)
                && (filtroStl === '' || tarjeta.getAttribute('data-stl') === filtroStl);
            tarjeta.classList.toggle('d-none', !visible);
            if (visible) encontradas++;
        });

        // Igual que el plegado: la cabecera de cada categoría vive en el
        // mismo <div data-grupo> que el cuerpo con el id "cat-N".
        document.querySelectorAll('[data-grupo]').forEach(function (grupo) {
            var cuerpo = grupo.querySelector('[id^="cat-"]');
            if (!cuerpo) return;
            var visibles = cuerpo.querySelectorAll('[data-tarjeta]:not(.d-none)').length;
            var contador = grupo.querySelector('.badge');

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
    engancharFacet(cajaStl, 'data-filtro-stl',
        function () { return filtroStl; }, function (v) { filtroStl = v; });

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
        boton.classList.toggle('btn-success', enCarrito);
        boton.classList.toggle('btn-outline-primary', !enCarrito);
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
        }).catch(function () {
            boton.disabled = false;
            alert('No se pudo hablar con el servidor.');
        });
    });

    var botonVaciar = document.getElementById('botonVaciarPlaca');
    if (botonVaciar) {
        botonVaciar.addEventListener('click', function () {
            if (!confirm('¿Vaciar la placa?')) return;

            llamadaPlaca(baseCarrito + 'vaciar').then(function (datos) {
                if (!datos.ok) return;
                document.querySelectorAll('[data-carrito-boton]').forEach(function (b) {
                    pintarBotonPlaca(b, false);
                });
                actualizarContadorPlaca(0);
            });
        });
    }
})();
</script>

<?= $this->endSection() ?>
