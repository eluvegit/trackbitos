<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-printer text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/galeria') ?>" class="text-decoration-none text-muted fw-normal">Galería</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Placas</strong>


    <a href="<?= site_url('piezas/pedidos') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Pedidos entrantes desde sterclicks">
        <i class="bi bi-cart-check"></i> Pedidos
    </a>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary " title="Galería de piezas">
        <i class="bi bi-grid-3x3-gap"></i> Galería
    </a>
</h5>

<p class="text-muted small">
    Tres cajones, el mismo camino que sigue una placa de verdad: <strong>Guardada</strong> es solo una
    idea apuntada sin bajar nada todavía; <strong>Lista para imprimir</strong> es que ya tienes el zip
    de los STL; <strong>Impresa</strong> es que ya se montó, con o sin veredicto todavía. Dentro de cada
    cajón, agrupadas por cuándo — así se ve de un vistazo por dónde vas. Pulsa una tarjeta para abrir su
    bitácora y anotar cómo salió sin salir de esta pantalla.
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
    .lomo-buena   { border-left-color: var(--bs-success); }
    .lomo-regular { border-left-color: var(--bs-warning); }
    .lomo-repetir { border-left-color: var(--bs-danger); }

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

<?php // Dos interruptores, no uno: en las tarjetas la foto es para reconocer la placa
      // de un vistazo y en el listado del modal es solo un apoyo al texto, así que cada
      // sitio se apaga por su cuenta. Cada uno recuerda su estado. ?>
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
        $iconoBloque = ['guardada' => 'bi-bookmark', 'lista' => 'bi-file-earmark-zip'];
        // A la derecha primero Lista, debajo Guardada: es el orden en que
        // avanza una placa hacia Impresas, la columna grande de al lado.
        $bloquesLaterales = ['lista' => $bloques['lista'], 'guardada' => $bloques['guardada']];
        $totalImpresas = array_sum(array_map('count', $bloques['impresa']['grupos']));
    ?>
    <div class="row">
        <?php // Impresas ocupa los dos tercios: es el historial de verdad, lo
              // que se viene a repasar. Línea de tiempo fija a la izquierda de
              // esta columna (Hoy / Ayer / la semana pasada / Julio...) y las
              // placas una debajo de otra — como el historial de una app de
              // fotos o de mensajería, no un archivador de tarjetitas. ?>
        <div class="col-12 col-lg-8">
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

        <?php // El tercio de la derecha, fijo en pantalla: Lista para imprimir
              // y Guardadas, accesibles mientras se baja repasando Impresas —
              // no dos secciones que haya que ir a buscar más abajo. ?>
        <div class="col-12 col-lg-4">
            <?php // max-height + scroll propio: si Lista y Guardadas juntas no
                  // caben en la pantalla, un sticky a secas dejaría lo que
                  // sobra por debajo fuera de la vista sin forma de llegar a
                  // ello (un sticky no se desplaza por dentro solo). Con esto,
                  // en cuanto no cabe, este bloque hace su propio scroll. ?>
            <div class="position-sticky" style="top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto;">
                <?php foreach ($bloquesLaterales as $claveBloque => $bloque): ?>
                    <?php $totalBloque = array_sum(array_map('count', $bloque['grupos'])); ?>
                    <h6 class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi <?= $iconoBloque[$claveBloque] ?? 'bi-inbox' ?>"></i>
                        <?= esc($bloque['titulo']) ?>
                        <span class="badge text-bg-secondary"><?= $totalBloque ?></span>
                    </h6>

                    <?php if ($totalBloque === 0): ?>
                        <p class="text-muted small fst-italic">Nada por aquí.</p>
                    <?php else: ?>
                        <?php foreach ($bloque['grupos'] as $etiqueta => $placasDelGrupo): ?>
                            <?php $idGrupo = 'grupo-' . $claveBloque . '-' . preg_replace('/[^a-z0-9]+/i', '-', $etiqueta); ?>
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
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php // Un único modal para todas las placas (fase 48: solo lectura — al
          // abrirlo se le presta el bloque de botones de su tarjeta, para no
          // duplicarlos, y se le pide al servidor el vistazo rápido de la
          // bitácora. Editar de verdad es "Ver completa", dentro del propio
          // resumen, que lleva a la pantalla completa. ?>
    <div class="modal fade" id="modalPlaca" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <div class="me-auto">
                        <h6 class="modal-title mb-0" id="modalPlacaTitulo"></h6>
                        <div class="text-muted" style="font-size: .72rem;" id="modalPlacaMontada"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-3 p-md-4" id="modalPlacaCuerpo">
                    <div class="text-muted small">Cargando…</div>
                </div>
                <div class="modal-footer py-2" id="modalPlacaPie">
                    <div class="d-flex flex-wrap gap-2 w-100" id="modalPlacaAcciones"></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    // ---- La bitácora de una placa, en un modal de solo lectura (fase 48) -----
    var modalEl = document.getElementById('modalPlaca');
    var cuerpo = document.getElementById('modalPlacaCuerpo');
    var acciones = document.getElementById('modalPlacaAcciones');
    var titulo = document.getElementById('modalPlacaTitulo');
    var montada = document.getElementById('modalPlacaMontada');

    var accionesPrestadas = null;  // el bloque de botones, y de qué tarjeta salió
    var cunaDeAcciones = null;
    var peticion = 0;              // cuál es la última carga pedida, ver más abajo
    var placaActual = null;        // qué placa hay abierta, para el atajo de Enter

    // La instancia se crea al pulsar, no aquí: este <script> va en el cuerpo de
    // la vista y el bundle de Bootstrap se carga al final del layout, así que
    // ahora mismo `bootstrap` todavía no existe — hacerlo aquí tiraba el bloque
    // entero con un ReferenceError y se llevaba por delante hasta el botón de
    // las fotos.
    function modalDePlacas() {
        return (modalEl && window.bootstrap) ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    }

    document.querySelectorAll('[data-abrir-placa]').forEach(function (tarjeta) {
        tarjeta.addEventListener('click', function (e) {
            // La tarjeta entera es el disparador, así que hay que dejar pasar
            // cualquier enlace o formulario que caiga dentro.
            if (e.target.closest('form, a')) return;

            var modal = modalDePlacas();
            if (!modal) return;

            var detalle = document.getElementById(tarjeta.getAttribute('data-abrir-placa'));
            if (!detalle) return;

            titulo.textContent = detalle.getAttribute('data-nombre-placa') || 'Placa';
            montada.textContent = 'Montada el ' + (detalle.getAttribute('data-montada') || '');

            // Los botones salen del bloque oculto de la tarjeta y se van al pie.
            accionesPrestadas = detalle.querySelector('[data-acciones-placa]');
            cunaDeAcciones = detalle;
            if (accionesPrestadas) acciones.appendChild(accionesPrestadas);

            placaActual = tarjeta.getAttribute('data-placa');
            cargarResumen(placaActual);
            modal.show();
        });
    });

    /**
     * El vistazo rápido se pide al abrir. `peticion` va contando: si se abre
     * una placa, se cierra y se abre otra deprisa, la respuesta de la
     * primera puede llegar después — y sin este número pintaría la bitácora
     * equivocada encima de la que se está mirando.
     */
    function cargarResumen(id) {
        var mia = ++peticion;
        cuerpo.innerHTML = '<div class="text-muted small">Cargando…</div>';

        fetch('<?= site_url('piezas/placa') ?>/' + id + '/bitacora/resumen', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) {
                if (!r.ok) throw new Error('respuesta ' + r.status);
                return r.text();
            })
            .then(function (html) {
                if (mia !== peticion) return;
                cuerpo.innerHTML = html;
            })
            .catch(function () {
                if (mia !== peticion) return;
                cuerpo.innerHTML = '<div class="alert alert-warning py-2 mb-0">'
                    + 'No se pudo cargar la bitácora. Prueba a abrirla con «Ver limpio».</div>';
            });
    }

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            // Los botones vuelven a su tarjeta antes de vaciar el modal, o se
            // quedarían huérfanos en el pie y la placa se abriría sin ellos.
            if (accionesPrestadas && cunaDeAcciones) cunaDeAcciones.appendChild(accionesPrestadas);
            accionesPrestadas = null;
            cunaDeAcciones = null;
            placaActual = null;
            peticion++;   // lo que llegue tarde ya no es de nadie
            cuerpo.innerHTML = '';
        });
    }

    // Enter en el modal = "Ver completa": es de solo lectura, así que no hay
    // nada más que hacer con Enter salvo pasar a editar de verdad. Se deja
    // pasar si el foco está en un botón, enlace o campo de un formulario
    // prestado (borrar, cargar, repartir…) para no robarles su propio Enter.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !placaActual) return;
        if (!modalEl || !modalEl.classList.contains('show')) return;
        var activo = document.activeElement;
        if (activo && /^(INPUT|TEXTAREA|SELECT|BUTTON|A)$/.test(activo.tagName)) return;

        window.location.href = '<?= site_url('piezas/placa') ?>/' + placaActual + '/bitacora/editar';
    });

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
