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

    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Histórico de placas (guardadas y descargadas)">
        <i class="bi bi-clock-history"></i> Placas
    </a>

    <div class="d-flex gap-2 <?= empty($carrito) ? 'd-none' : '' ?>" id="cabeceraCarrito">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="botonVaciarPlaca">Vaciar placa</button>
        <button type="button" class="btn btn-sm btn-outline-primary" id="botonGuardarPlaca"
            title="Anota qué llevaba, sin descargar nada — como una lista de la compra, para retomarla más adelante">
            <i class="bi bi-bookmark-plus"></i> Guardar para después
        </button>
        <?php // Descargar deja la placa anotada en el histórico, así que antes de
              // bajar el zip se pregunta con qué nombre la quieres encontrar luego. ?>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalNombrePlaca">
            <i class="bi bi-file-earmark-zip"></i> Descargar placa (<span id="contadorPlaca"><?= count($carrito) ?></span>)
        </button>
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
$cuentaPlaca  = ['en' => 0, 'fuera' => 0];
foreach ($piezasTodas as $p) {
    $cuentaEstado[$p['version']['estado']] = ($cuentaEstado[$p['version']['estado']] ?? 0) + 1;
    $cuentaStl[$p['stls'] > 0 ? 'con' : 'sin']++;
    $cuentaPlaca[in_array((int) $p['version']['id'], $carrito, true) ? 'en' : 'fuera']++;
}
?>

<?php $total = count($piezasTodas); ?>

<?php if ($total === 0): ?>
    <p class="text-muted">Todavía no hay ninguna versión validada, ni ninguna "para imprimir". En cuanto promociones o valides una aparecerá aquí.</p>
<?php else: ?>
    <?php // Plegados por defecto, mismo criterio que el índice: consulta puntual,
          // no algo que se mira cada vez que se entra. Se recuerda si se han
          // dejado abiertos. ?>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnFiltrosGaleria"
        aria-controls="panelFiltrosGaleria" aria-expanded="false">
        <i class="bi bi-funnel"></i> Filtros
    </button>

    <div class="d-none" id="panelFiltrosGaleria">
        <div class="border rounded p-2 mb-3">
            <?php // Tres preguntas distintas (qué estado, si tiene STL, si está en la placa) que sí se
                  // combinan entre sí — a diferencia del filtro único del índice, aquí interesa cruzarlas
                  // ("para imprimir" + "sin STL" = qué exportar ya). Todas en gris neutro, sin colorinchis:
                  // el icono ya dice de qué trata cada una, y así no compiten con los badges de las tarjetas. ?>
            <div class="d-flex flex-wrap align-items-center gap-1 mb-2" id="filtrosEstadoGaleria">
                <span class="text-muted small text-uppercase" style="width: 4.5rem;">Estado</span>
                <button type="button" class="btn btn-sm btn-outline-secondary active" data-filtro-estado=""
                    title="Quitar el filtro de estado">
                    Todas <span class="badge text-bg-secondary"><?= $total ?></span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-estado="validada"
                    title="Solo las validadas" <?= $cuentaEstado['validada'] === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-check-circle-fill"></i> Validada
                    <span class="badge text-bg-secondary"><?= $cuentaEstado['validada'] ?></span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-estado="impresa"
                    title="Impresas, pendientes de juzgar el resultado" <?= $cuentaEstado['impresa'] === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-printer-fill"></i> Sin validar
                    <span class="badge text-bg-secondary"><?= $cuentaEstado['impresa'] ?></span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-estado="borrador"
                    title="Promocionadas, pendientes de imprimir de prueba" <?= $cuentaEstado['borrador'] === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-printer"></i> Para imprimir
                    <span class="badge text-bg-secondary"><?= $cuentaEstado['borrador'] ?></span>
                </button>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-1 mb-2" id="filtrosStlGaleria">
                <span class="text-muted small text-uppercase" style="width: 4.5rem;">STL</span>
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
            <div class="d-flex flex-wrap align-items-center gap-1" id="filtrosPlacaGaleria">
                <span class="text-muted small text-uppercase" style="width: 4.5rem;">Placa</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-placa="en"
                    title="Lo que ya está metido en la placa actual" <?= $cuentaPlaca['en'] === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-check-square"></i> En placa
                    <span class="badge text-bg-secondary" data-cuenta-placa-en><?= $cuentaPlaca['en'] ?></span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro-placa="fuera"
                    title="Lo que todavía no está en la placa actual" <?= $cuentaPlaca['fuera'] === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-square"></i> Sin placa
                    <span class="badge text-bg-secondary" data-cuenta-placa-fuera><?= $cuentaPlaca['fuera'] ?></span>
                </button>
            </div>

            <hr class="my-2">

            <?php // Añade a la placa todo lo que quede visible tras cruzar los filtros de arriba
                  // (o todo, con "Todas") — sin tener que ir tarjeta a tarjeta. Las que no tienen
                  // STL se saltan solas: nunca llevan botón de añadir. ?>
            <button type="button" class="btn btn-sm btn-outline-primary" id="botonSeleccionarTodas">
                <i class="bi bi-check2-square"></i> Añadir todas las visibles a la placa
            </button>
        </div>
    </div>

    <p class="text-muted small d-none" id="sinResultadosGaleria">Ninguna pieza coincide con los filtros.</p>

    <?php foreach ($grupos as $grupo): ?>
        <?php if (empty($grupo['piezas'])) continue; // Categoría vacía: aquí no aporta nada, a diferencia del índice. ?>
        <?php $categoria = $grupo['categoria']; ?>
        <?php $idGrupo = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin'; ?>
        <div class="mb-3" data-grupo>
            <?php // Igual que en el índice: pliega toda la línea, no solo la flecha. Fondo
                  // sólido (gris suave, adaptado al tema oscuro) para distinguirla de un
                  // vistazo de las tarjetas que trae debajo, en vez de un simple borde. ?>
            <div class="d-flex align-items-center gap-2 bg-body-secondary rounded px-2 py-2 mb-2 user-select-none"
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
                            data-stl="<?= $tieneStl ? 'con' : 'sin' ?>" data-placa="<?= $enCarrito ? 'en' : 'fuera' ?>"
                            data-version-tarjeta="<?= (int) $version['id'] ?>">
                            <div class="card shadow-sm h-100">
                                <div class="position-relative">
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
                                    <?php if ($p['miniatura']): ?>
                                        <?php // Ojo aparte del enlace a la ficha (que va en la imagen entera): abre la
                                              // foto suelta en una pestaña nueva para verla en grande, sin navegar. ?>
                                        <a href="<?= $p['miniatura'] ?>" target="_blank" rel="noopener"
                                            class="btn btn-sm btn-dark position-absolute top-0 end-0 m-1 py-0 px-1 opacity-75"
                                            style="font-size: .75rem; line-height: 1.4;" title="Ver foto en grande">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-2">
                                    <?php
                                        /**
                                         * La variante va pegada al nombre de la pieza ("Cabeza - calva"),
                                         * no en un renglón aparte: es un apellido, no un dato suelto —
                                         * en dos líneas parecía otra cosa distinta. Se calla cuando la
                                         * variante es la de nacimiento y además es la única, que es
                                         * cuando no distingue nada ("Lupa - base" no dice más que "Lupa").
                                         */
                                        $apellido = ($variante['nombre'] !== \App\Services\PiezaService::VARIANTE_BASE
                                                || $p['variosVariantes'])
                                            ? $variante['nombre']
                                            : null;
                                    ?>
                                    <div class="small fw-semibold text-truncate">
                                        <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>"
                                            class="text-decoration-none text-body"><?= esc($p['familiaNombre']) ?><?php
                                            if ($apellido !== null): ?><span class="text-muted fw-normal"> - <?= esc($apellido) ?></span><?php
                                            endif; ?></a>
                                    </div>
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

<?php // El nombre es opcional a propósito: si lo dejas en blanco se apunta con la
      // fecha, como hacía antes, y siempre se puede cambiar luego desde Placas. ?>
<div class="modal fade" id="modalNombrePlaca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= site_url('piezas/carrito/descargar') ?>" class="modal-content" id="formNombrePlaca">
            <?= csrf_field() ?>
            <div class="modal-header py-2">
                <h6 class="modal-title">Descargar los STL</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php // Guardar es lo normal (una placa que va a la impresora merece
                      // su bitácora), pero se puede desmarcar: esta pantalla también
                      // sirve para bajar STL sueltos de golpe, y eso no es una placa
                      // ni tiene nada que documentar después. ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="guardar" value="1"
                        id="guardarPlaca" checked>
                    <label class="form-check-label small" for="guardarPlaca">
                        Guardar esta placa en el histórico, con su bitácora
                    </label>
                </div>
                <div id="bloqueNombrePlaca">
                    <label class="form-label small mb-1" for="campoNombrePlaca">Nombre de la placa</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" maxlength="150"
                        id="campoNombrePlaca" autocomplete="off"
                        placeholder="Placa <?= esc(date('d/m/Y H:i'), 'attr') ?>">
                    <div class="form-text">
                        Para reconocerla en el histórico. Si lo dejas vacío se guarda con la fecha.
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-zip"></i> Descargar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // El zip se baja en la misma pestaña y la página no navega a ningún sitio,
    // así que el modal se queda abierto encima si no se cierra a mano. Se cierra
    // al enviar, no antes: cancelar no debe disparar la descarga.
    var formNombre = document.getElementById('formNombrePlaca');
    if (formNombre) {
        formNombre.addEventListener('submit', function () {
            var modalEl = document.getElementById('modalNombrePlaca');
            setTimeout(function () {
                if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }, 400);
        });

        // El foco en el campo al abrir: si vienes a ponerle nombre, es lo primero
        // que quieres hacer; y si no, das a Descargar y ya.
        document.getElementById('modalNombrePlaca').addEventListener('shown.bs.modal', function () {
            document.getElementById('campoNombrePlaca').focus();
        });

        // Sin guardar no hay nada que nombrar: el campo se esconde en vez de
        // quedarse ahí pidiendo un dato que no se va a usar.
        var guardar = document.getElementById('guardarPlaca');
        var bloqueNombre = document.getElementById('bloqueNombrePlaca');
        if (guardar && bloqueNombre) {
            guardar.addEventListener('change', function () {
                bloqueNombre.classList.toggle('d-none', !guardar.checked);
            });
        }
    }

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
    var cajaStl = document.getElementById('filtrosStlGaleria');
    var cajaPlaca = document.getElementById('filtrosPlacaGaleria');
    var sinResultados = document.getElementById('sinResultadosGaleria');
    var filtroEstado = '';
    var filtroStl = '';
    var filtroPlaca = '';

    function aplicarFiltrosGaleria() {
        var recortando = filtroEstado !== '' || filtroStl !== '' || filtroPlaca !== '';
        var encontradas = 0;

        document.querySelectorAll('[data-tarjeta]').forEach(function (tarjeta) {
            var visible = (filtroEstado === '' || tarjeta.getAttribute('data-estado') === filtroEstado)
                && (filtroStl === '' || tarjeta.getAttribute('data-stl') === filtroStl)
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
    engancharFacet(cajaPlaca, 'data-filtro-placa',
        function () { return filtroPlaca; }, function (v) { filtroPlaca = v; });

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

    var botonVaciar = document.getElementById('botonVaciarPlaca');
    if (botonVaciar) {
        botonVaciar.addEventListener('click', function () {
            if (!confirm('¿Vaciar la placa?')) return;

            llamadaPlaca(baseCarrito + 'vaciar').then(function (datos) {
                if (!datos.ok) return;
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

    // ---- Guardar para después: anota la placa sin descargar nada ---------
    var botonGuardarPlaca = document.getElementById('botonGuardarPlaca');
    if (botonGuardarPlaca) {
        botonGuardarPlaca.addEventListener('click', function () {
            botonGuardarPlaca.disabled = true;

            llamadaPlaca(baseCarrito + 'guardar').then(function (datos) {
                botonGuardarPlaca.disabled = false;
                if (!datos.ok) {
                    alert(datos.mensaje || 'No se pudo guardar la placa.');
                    return;
                }
                alert('Guardada como «' + datos.nombre + '». La puedes ver en Placas.');
            }).catch(function () {
                botonGuardarPlaca.disabled = false;
                alert('No se pudo hablar con el servidor.');
            });
        });
    }

    // ---- Añadir todas las visibles a la placa -----------------------------
    // Una por una (no en paralelo): así el contador y las tarjetas se van
    // pintando según llegan las respuestas, en vez de todas de golpe al
    // final, y si algo falla a mitad no deja llamadas sueltas por resolver.
    var botonSeleccionarTodas = document.getElementById('botonSeleccionarTodas');
    if (botonSeleccionarTodas) {
        var textoSeleccionarTodas = botonSeleccionarTodas.textContent;

        botonSeleccionarTodas.addEventListener('click', function () {
            // Solo las que llevan botón de añadir (sin STL nunca lo llevan)
            // y todavía no están en la placa.
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
                    botonSeleccionarTodas.textContent = textoSeleccionarTodas;
                    actualizarContadoresFiltroPlaca();
                    if (filtroPlaca !== '') aplicarFiltrosGaleria();
                    return;
                }

                botonSeleccionarTodas.textContent = 'Añadiendo… ' + (i + 1) + '/' + pendientes.length;

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
