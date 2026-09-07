<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-printer text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/galeria') ?>" class="text-decoration-none text-muted fw-normal">Galería</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Placas</strong>

    <span class="badge rounded-pill text-bg-primary" title="Placas registradas desde siempre">
        <i class="bi bi-infinity"></i> <?= (int) $totalPlacasSiempre ?>
    </span>

    <a href="<?= site_url('piezas/pedidos') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Pedidos entrantes desde sterclicks">
        <i class="bi bi-cart-check"></i> Pedidos
    </a>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary " title="Galería de piezas">
        <i class="bi bi-grid-3x3-gap"></i> Galería
    </a>
</h5>

<p class="text-muted small">
    Dos cajones, el mismo camino que sigue una placa de verdad: <strong>Por imprimir</strong> es que
    todavía no se ha montado; <strong>Impresa</strong> es que ya se montó, con o sin veredicto todavía.
    Dentro de cada cajón, agrupadas por cuándo — así se ve de un vistazo por dónde vas. Pulsa una
    tarjeta para entrar directo a su bitácora y anotar cómo salió.
</p>

<?php /**
       * Las reglas van aquí y no en style.css a propósito: el Hostinger sirve los
       * assets con una semana de caché, así que un cambio en la hoja tarda días en
       * llegar al navegador y el botón parece roto sin estarlo (pasó, y costó
       * encontrarlo). El HTML no se cachea, así que embebido siempre está al día.
       */ ?>
<style>
    .ocultar-fotos-tarjetas [data-foto-placa="tarjeta"],
    .ocultar-fotos-lista [data-foto-placa="lista"] {
        display: none !important;
    }

    /* El veredicto, como una pestaña de color en el lomo del archivador: lo
       que permite repasar el histórico sin abrir placa por placa. */
    .lomo-placa {
        border-left: 4px solid transparent;
    }
    .lomo-buena      { border-left-color: var(--bs-success); }
    .lomo-regular    { border-left-color: var(--bs-warning); }
    .lomo-repetir    { border-left-color: var(--bs-danger); }
    .lomo-sin-juzgar { border-left-color: var(--bs-secondary); }

    /* Riel del timeline de Impresas (fase 52): una raya vertical con un
       punto por grupo de fecha, que se resalta con la sección que está
       cruzando la parte de arriba de la pantalla (ver el IntersectionObserver
       más abajo). */
    .tl-rail-item {
        position: relative;
        display: block;
        padding: .35rem 0 .35rem 1.1rem;
        color: var(--bs-secondary-color);
    }
    .tl-rail-item::before {
        content: '';
        position: absolute;
        left: 4px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--bs-border-color);
    }
    .tl-rail-item:first-child::before { top: 50%; }
    .tl-rail-item:last-child::before { bottom: 50%; }
    .tl-rail-item::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--bs-body-bg);
        border: 2px solid var(--bs-border-color);
    }
    .tl-rail-item.activo {
        color: var(--bs-body-color);
        font-weight: 600;
    }
    .tl-rail-item.activo::after {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }
</style>

<?php // Apaga las portadas de las tarjetas; recuerda su estado entre visitas. ?>
<div class="btn-group btn-group-sm mb-2" role="group">
    <button type="button" class="btn btn-outline-secondary" data-fotos="tarjetas">
        <i class="bi bi-image"></i> Ocultar portadas
    </button>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (!$hayPlacas): ?>
    <p class="text-muted">
        Todavía no hay ninguna placa. En cuanto guardes o descargues alguna desde la galería,
        aparecerá aquí.
    </p>
<?php else: ?>
    <?php
        // Recientes abiertos, el resto plegado por defecto: mismo espíritu que
        // "Organizar" en el índice de Piezas — lo que se mira a diario no debe
        // obligar a desplegar nada, lo antiguo sí puede empezar escondido.
        $abiertosPorDefecto = ['Hoy', 'Ayer', 'Esta semana'];
        $totalImpresas = array_sum(array_map('count', $bloques['impresa']['grupos']));
    ?>

    <?php // Pestañas solo en móvil (fase 53, a dos desde la fase 56 al
          // fusionarse Guardada+Lista): en escritorio las dos secciones se
          // ven en paralelo (columna grande + sidebar), pero apiladas en
          // móvil "Por imprimir" acababa al final del todo, obligando a un
          // scroll larguísimo. Con pestañas se cambia de sección tocando un
          // botón, sin bajar nada — en escritorio ni se muestran, las dos
          // secciones se ven a la vez como siempre (ver [data-panel-placas]
          // más abajo y su regla d-lg-block). ?>
    <ul class="nav nav-pills nav-fill mb-3 d-lg-none" data-tabs-placas>
        <li class="nav-item">
            <button type="button" class="nav-link" data-tab-placas="porImprimir">Por imprimir</button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link active" data-tab-placas="impresa">Impresas</button>
        </li>
    </ul>

    <div class="row">
        <?php // Impresas ocupa los dos tercios: es el historial de verdad, lo
              // que se viene a repasar. Línea de tiempo fija a la izquierda de
              // esta columna (Hoy / Ayer / la semana pasada / Julio...) y las
              // placas una debajo de otra — como el historial de una app de
              // fotos o de mensajería, no un archivador de tarjetitas. ?>
        <div class="col-12 col-lg-8 d-lg-block" data-panel-placas="impresa">
            <h6 class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-check-circle"></i>
                <?= esc($bloques['impresa']['titulo']) ?>
                <span class="badge text-bg-secondary"><?= $totalImpresas ?></span>
            </h6>

            <?php if ($totalImpresas === 0): ?>
                <p class="text-muted small fst-italic">Nada por aquí.</p>
            <?php else: ?>
                <div class="row">
                    <nav class="col-lg-4 d-none d-lg-block">
                        <div class="tl-rail position-sticky" style="top: 1rem;">
                            <?php foreach ($bloques['impresa']['grupos'] as $etiqueta => $placasDelGrupo): ?>
                                <?php $idSeccion = 'tl-' . preg_replace('/[^a-z0-9]+/i', '-', $etiqueta); ?>
                                <a href="#<?= $idSeccion ?>" class="tl-rail-item d-block text-decoration-none"
                                    data-tl-link="<?= $idSeccion ?>">
                                    <span class="tl-rail-label"><?= esc($etiqueta) ?></span>
                                    <span class="text-muted small">(<?= count($placasDelGrupo) ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </nav>

                    <div class="col-12 col-lg-8">
                        <?php foreach ($bloques['impresa']['grupos'] as $etiqueta => $placasDelGrupo): ?>
                            <?php $idSeccion = 'tl-' . preg_replace('/[^a-z0-9]+/i', '-', $etiqueta); ?>
                            <section id="<?= $idSeccion ?>" data-tl-seccion class="mb-4">
                                <div class="small fw-semibold text-uppercase text-muted mb-2"><?= esc($etiqueta) ?></div>
                                <?php foreach ($placasDelGrupo as $placa): ?>
                                    <?php
                                        $lista   = $piezas[(int) $placa['id']] ?? [];
                                        $resumen = $resumenes[(int) $placa['id']] ?? ['anotada' => false, 'sinResponder' => 0, 'enlaces' => 0, 'veredicto' => null];
                                        include APPPATH . 'Views/piezas/_placa_tarjeta_grande.php';
                                    ?>
                                <?php endforeach; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php // El tercio de la derecha, fijo en pantalla: Por imprimir,
              // accesible mientras se baja repasando Impresas — no una
              // sección que haya que ir a buscar más abajo. ?>
        <div class="col-12 col-lg-4">
            <?php
                $bloquePorImprimir = $bloques['porImprimir'];
                $totalPorImprimir  = array_sum(array_map('count', $bloquePorImprimir['grupos']));
            ?>
            <?php // max-height + scroll propio: si no cabe en la pantalla, un
                  // sticky a secas dejaría lo que sobra por debajo fuera de la
                  // vista sin forma de llegar a ello (un sticky no se
                  // desplaza por dentro solo). Con esto, en cuanto no cabe,
                  // este bloque hace su propio scroll. ?>
            <div class="position-sticky" style="top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto;">
                <div class="d-none d-lg-block" data-panel-placas="porImprimir">
                    <h6 class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-hourglass-split"></i>
                        <?= esc($bloquePorImprimir['titulo']) ?>
                        <span class="badge text-bg-secondary"><?= $totalPorImprimir ?></span>
                    </h6>

                    <?php if ($totalPorImprimir === 0): ?>
                        <p class="text-muted small fst-italic">Nada por aquí.</p>
                    <?php else: ?>
                        <?php foreach ($bloquePorImprimir['grupos'] as $etiqueta => $placasDelGrupo): ?>
                            <?php $idGrupo = 'grupo-porImprimir-' . preg_replace('/[^a-z0-9]+/i', '-', $etiqueta); ?>
                            <div class="d-flex align-items-center gap-2 user-select-none mb-1" style="cursor: pointer"
                                data-plegar="<?= $idGrupo ?>">
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-body">
                                    <i class="bi bi-chevron-down" data-chevron></i>
                                </button>
                                <span class="small fw-semibold text-uppercase text-muted"><?= esc($etiqueta) ?></span>
                                <span class="badge border text-body-secondary"><?= count($placasDelGrupo) ?></span>
                            </div>
                            <div id="<?= $idGrupo ?>" class="row row-cols-1 g-2 mb-3">
                                <?php foreach ($placasDelGrupo as $placa): ?>
                                    <?php
                                        // `include` nativo de PHP, no `$this->include()`: este último solo
                                        // repite los datos que ya trajo el controlador (su tercer parámetro
                                        // es de caché, no de datos), así que no sirve para pasar variables
                                        // de cada vuelta del bucle como $placa o $lista. El include nativo
                                        // comparte el scope de aquí, así que $origenNombres también le llega
                                        // sin tener que pasarlo aparte.
                                        $lista   = $piezas[(int) $placa['id']] ?? [];
                                        $resumen = $resumenes[(int) $placa['id']] ?? ['anotada' => false, 'sinResponder' => 0, 'enlaces' => 0, 'veredicto' => null];
                                        include APPPATH . 'Views/piezas/_placa_tarjeta.php';
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
(function () {
    // ---- Pulsar una placa lleva directo a su bitácora completa ---------------
    // Antes esto abría un modal de solo lectura con un botón "Ver completa"
    // dentro; ese paso intermedio se quita, la tarjeta entera navega ya a la
    // pantalla de edición. Se deja pasar el click cuando cae dentro de un
    // enlace, botón o formulario propio de la tarjeta (deshacer reparto,
    // el enlace al pedido…), para no robarles el suyo.
    document.querySelectorAll('[data-abrir-placa]').forEach(function (tarjeta) {
        tarjeta.addEventListener('click', function (e) {
            if (e.target.closest('form, a, button')) return;

            window.location.href = '<?= site_url('piezas/placa') ?>/' + tarjeta.getAttribute('data-placa') + '/bitacora/editar';
        });
    });

    // ---- Pestañas Por imprimir/Impresas, solo en móvil ------------------------
    // En escritorio [data-panel-placas] lleva también la clase d-lg-block,
    // que gana siempre a partir de lg (mismo idioma que Bootstrap para
    // "oculto en móvil, visible en escritorio"), así que estos botones no
    // hacen nada ahí — ni falta que hace, las dos secciones ya se ven a
    // la vez. Se recuerda la última pestaña igual que el resto de
    // interruptores de esta pantalla.
    var TAB_PLACAS = 'piezas_placas_pestana_movil';

    function activarPestana(clave) {
        var boton = document.querySelector('[data-tab-placas="' + clave + '"]');
        if (!boton) return;
        document.querySelectorAll('[data-tab-placas]').forEach(function (b) {
            b.classList.toggle('active', b === boton);
        });
        document.querySelectorAll('[data-panel-placas]').forEach(function (panel) {
            panel.classList.toggle('d-none', panel.getAttribute('data-panel-placas') !== clave);
        });
    }

    document.querySelectorAll('[data-tab-placas]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var clave = boton.getAttribute('data-tab-placas');
            try { localStorage.setItem(TAB_PLACAS, clave); } catch (e) {}
            activarPestana(clave);
        });
    });

    try {
        var pestanaGuardada = localStorage.getItem(TAB_PLACAS);
        if (pestanaGuardada) activarPestana(pestanaGuardada);
    } catch (e) {}

    // ---- Plegar grupos de fecha ----------------------------------------------
    // A mano, mismo patrón que el índice de Piezas con sus categorías. Por
    // defecto abiertos "Hoy/Ayer/Esta semana" y cerrado el resto; solo se
    // guarda en localStorage cuando el usuario toca algo a mano, como una
    // excepción a ese valor de partida — así cambiar de semana no deja
    // grupos "Esta semana" viejos marcados como abiertos para siempre.
    var EXCEPCIONES = 'piezas_placas_grupos_excepciones';

    function excepciones() {
        try { return JSON.parse(localStorage.getItem(EXCEPCIONES)) || {}; } catch (e) { return {}; }
    }

    function pintarGrupo(id, abierto) {
        var cuerpoGrupo = document.getElementById(id);
        if (!cuerpoGrupo) return;
        cuerpoGrupo.classList.toggle('d-none', !abierto);

        var cabecera = document.querySelector('[data-plegar="' + id + '"]');
        var chevron = cabecera ? cabecera.querySelector('[data-chevron]') : null;
        if (chevron) {
            chevron.classList.toggle('bi-chevron-down', abierto);
            chevron.classList.toggle('bi-chevron-right', !abierto);
        }
    }

    var abiertosPorDefecto = <?= json_encode($abiertosPorDefecto, JSON_UNESCAPED_UNICODE) ?>;
    document.querySelectorAll('[data-plegar]').forEach(function (cabecera) {
        var id = cabecera.getAttribute('data-plegar');
        var etiqueta = cabecera.querySelector('span').textContent.trim();
        var abiertoPorDefecto = abiertosPorDefecto.some(function (e) { return e.toLowerCase() === etiqueta.toLowerCase(); });
        var excepcion = excepciones()[id];

        pintarGrupo(id, excepcion !== undefined ? excepcion : abiertoPorDefecto);

        cabecera.addEventListener('click', function (e) {
            if (e.target.closest('form, a')) return;

            var vaAAbrir = document.getElementById(id).classList.contains('d-none');
            var mapa = excepciones();
            if (vaAAbrir === abiertoPorDefecto) {
                delete mapa[id];
            } else {
                mapa[id] = vaAAbrir;
            }
            localStorage.setItem(EXCEPCIONES, JSON.stringify(mapa));
            pintarGrupo(id, vaAAbrir);
        });
    });

    // ---- Timeline de Impresas: qué grupo de fecha resaltar en el riel --------
    // Se marca "activo" el grupo cuya sección va cruzando una franja fina
    // cerca de arriba de la pantalla — el mismo truco que usan los índices
    // fijos de cualquier app con scrollspy: no hace falta calcular alturas
    // a mano, el propio IntersectionObserver avisa cuando el borde de la
    // sección entra o sale de esa franja.
    var seccionesTimeline = document.querySelectorAll('[data-tl-seccion]');
    if (seccionesTimeline.length && window.IntersectionObserver) {
        var enlacesTimeline = {};
        document.querySelectorAll('[data-tl-link]').forEach(function (a) {
            enlacesTimeline[a.getAttribute('data-tl-link')] = a;
        });

        var observadorTimeline = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var enlace = enlacesTimeline[entry.target.id];
                if (!enlace) return;
                Object.keys(enlacesTimeline).forEach(function (id) {
                    enlacesTimeline[id].classList.remove('activo');
                });
                enlace.classList.add('activo');
            });
        }, { rootMargin: '-10% 0px -80% 0px' });

        seccionesTimeline.forEach(function (s) { observadorTimeline.observe(s); });
    }

    // ---- Mostrar/ocultar fotos ----------------------------------------------
    // Todas visibles por defecto; cada interruptor va por su cuenta y recuerda
    // su estado entre visitas.
    var FOTOS = {
        tarjetas: {
            clase: 'ocultar-fotos-tarjetas',
            clave: 'piezas_placas_fotos_tarjetas_ocultas',
            icono: 'bi-image',
            que: 'portadas'
        },
        lista: {
            clase: 'ocultar-fotos-lista',
            clave: 'piezas_placas_fotos_lista_ocultas',
            icono: 'bi-list-ul',
            que: 'miniaturas'
        }
    };

    document.querySelectorAll('[data-fotos]').forEach(function (boton) {
        var cfg = FOTOS[boton.getAttribute('data-fotos')];
        if (!cfg) return;

        function pintar(ocultas) {
            document.body.classList.toggle(cfg.clase, ocultas);
            boton.classList.toggle('active', ocultas);
            boton.innerHTML = '<i class="bi ' + cfg.icono + '"></i> '
                + (ocultas ? 'Mostrar ' : 'Ocultar ') + cfg.que;
        }

        pintar(localStorage.getItem(cfg.clave) === '1');

        boton.addEventListener('click', function () {
            var ocultar = !document.body.classList.contains(cfg.clase);
            localStorage.setItem(cfg.clave, ocultar ? '1' : '0');
            pintar(ocultar);
        });
    });
})();
</script>

<?= $this->endSection() ?>
