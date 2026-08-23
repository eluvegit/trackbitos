<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-clock-history text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/galeria') ?>" class="text-decoration-none text-muted fw-normal">Galería</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Placas</strong>

    
    <a href="<?= site_url('piezas/pedidos') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Pedidos entrantes desde sterclicks">
        <i class="bi bi-cart-check"></i> Pedidos
    </a>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary " title="Galería de piezas">
        <i class="bi bi-cart-check"></i> Galería
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
        $iconoBloque = ['guardada' => 'bi-bookmark', 'lista' => 'bi-file-earmark-zip', 'impresa' => 'bi-check-circle'];
    ?>
    <?php foreach ($bloques as $claveBloque => $bloque): ?>
        <?php $totalBloque = array_sum(array_map('count', $bloque['grupos'])); ?>
        <h6 class="d-flex align-items-center gap-2 mt-4 mb-2">
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
                <div id="<?= $idGrupo ?>" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-2 mb-3">
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

    <?php // Un único modal para todas las placas: al abrirlo se le presta el bloque
          // de botones de su tarjeta (para no duplicar formularios ni tokens CSRF) y
          // se le pide al servidor el formulario de la bitácora. A pantalla completa
          // en el móvil, que es donde se rellena esto — de pie, al lado de la
          // impresora, con la pieza todavía goteando. ?>
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
                <div class="modal-body" id="modalPlacaCuerpo">
                    <div class="text-muted small">Cargando la bitácora…</div>
                </div>
                <div class="modal-footer py-2 gap-2" id="modalPlacaPie">
                    <div class="d-flex flex-wrap gap-2 me-auto" id="modalPlacaAcciones"></div>
                    <span class="small" id="modalPlacaEstado"></span>
                    <button type="button" class="btn btn-sm btn-success" id="modalPlacaGuardar">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->include('piezas/_bitacora_js') ?>

<script>
(function () {
    // ---- La bitácora de una placa, en un modal -------------------------------
    var modalEl = document.getElementById('modalPlaca');
    var cuerpo = document.getElementById('modalPlacaCuerpo');
    var acciones = document.getElementById('modalPlacaAcciones');
    var titulo = document.getElementById('modalPlacaTitulo');
    var montada = document.getElementById('modalPlacaMontada');
    var estado = document.getElementById('modalPlacaEstado');
    var botonGuardar = document.getElementById('modalPlacaGuardar');

    var accionesPrestadas = null;  // el bloque de botones, y de qué tarjeta salió
    var cunaDeAcciones = null;
    var tarjetaAbierta = null;     // la tarjeta que hay detrás, para repintarla al guardar
    var peticion = 0;              // cuál es la última carga pedida, ver más abajo

    // La instancia se crea al pulsar, no aquí: este <script> va en el cuerpo de
    // la vista y el bundle de Bootstrap se carga al final del layout, así que
    // ahora mismo `bootstrap` todavía no existe — hacerlo aquí tiraba el bloque
    // entero con un ReferenceError y se llevaba por delante hasta el botón de
    // las fotos.
    function modalDePlacas() {
        return (modalEl && window.bootstrap) ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    }

    function formAbierto() {
        return cuerpo.querySelector('[data-bitacora-form]');
    }

    // Añadir/quitar una pieza (ver _bitacora_js.php) cambia el formulario por
    // uno nuevo, así que hay que volver a engancharle lo que solo pone este
    // modal encima del formulario base: aviso de cambios sin guardar y
    // Enter-para-guardar.
    cuerpo.addEventListener('bitacora:recargada', function () {
        var form = formAbierto();
        if (!form) return;
        form.addEventListener('input', function () { form.dataset.sucio = '1'; });
        form.addEventListener('change', function () { form.dataset.sucio = '1'; });
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            guardar(form);
        });
    });

    function decir(texto, clase) {
        estado.textContent = texto || '';
        estado.className = 'small ' + (clase || 'text-muted');
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

            tarjetaAbierta = tarjeta.closest('[data-tarjeta-placa]');
            titulo.textContent = detalle.getAttribute('data-nombre-placa') || 'Placa';
            montada.textContent = 'Montada el ' + (detalle.getAttribute('data-montada') || '');
            decir('');

            // Los botones salen del bloque oculto de la tarjeta y se van al pie.
            accionesPrestadas = detalle.querySelector('[data-acciones-placa]');
            cunaDeAcciones = detalle;
            if (accionesPrestadas) acciones.appendChild(accionesPrestadas);

            cargarBitacora(tarjeta.getAttribute('data-placa'));
            modal.show();
        });
    });

    /**
     * El formulario se pide al abrir. `peticion` va contando: si se abre una
     * placa, se cierra y se abre otra deprisa, la respuesta de la primera
     * puede llegar después — y sin este número pintaría la bitácora
     * equivocada encima de la que se está mirando.
     */
    function cargarBitacora(id) {
        var mia = ++peticion;
        cuerpo.innerHTML = '<div class="text-muted small">Cargando la bitácora…</div>';
        botonGuardar.disabled = true;

        fetch('<?= site_url('piezas/placa') ?>/' + id + '/bitacora/fragmento', {
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
                var form = formAbierto();
                if (form) {
                    window.bitacoraIniciar(form);
                    form.addEventListener('input', function () { form.dataset.sucio = '1'; });
                    form.addEventListener('change', function () { form.dataset.sucio = '1'; });
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        guardar(form);
                    });
                }
                botonGuardar.disabled = false;
            })
            .catch(function () {
                if (mia !== peticion) return;
                cuerpo.innerHTML = '<div class="alert alert-warning py-2 mb-0">'
                    + 'No se pudo cargar la bitácora. Prueba a abrirla con «Ver limpio».</div>';
            });
    }

    function guardar(form) {
        if (!form) return;
        botonGuardar.disabled = true;
        decir('Guardando…');

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: new FormData(form)
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, datos: d }; }); })
            .then(function (r) {
                botonGuardar.disabled = false;
                if (!r.ok || !r.datos.ok) {
                    decir(r.datos.mensaje || 'No se pudo guardar.', 'text-danger');
                    return;
                }

                form.dataset.sucio = '';
                decir('Guardado a las ' + new Date().toLocaleTimeString().slice(0, 5), 'text-success');
                titulo.textContent = r.datos.nombre;
                repintarTarjeta(r.datos);
                repintarReparto(form, r.datos);
            })
            .catch(function () {
                botonGuardar.disabled = false;
                decir('No se pudo guardar — mira la conexión.', 'text-danger');
            });
    }

    /**
     * La tarjeta de detrás se actualiza sola: si guardar obligara a recargar
     * el histórico para ver el veredicto nuevo, volveríamos justo al ir y
     * venir de pantallas que sobraba.
     */
    function repintarTarjeta(datos) {
        if (!tarjetaAbierta) return;

        var nombre = tarjetaAbierta.querySelector('[data-nombre-tarjeta]');
        if (nombre) {
            nombre.textContent = datos.nombre;
            nombre.setAttribute('title', datos.nombre);
        }

        var tarjeta = tarjetaAbierta.querySelector('.lomo-placa');
        if (tarjeta) {
            tarjeta.classList.remove('lomo-buena', 'lomo-regular', 'lomo-repetir');
            if (datos.veredicto) tarjeta.classList.add('lomo-' + datos.veredicto);
        }

        var zona = tarjetaAbierta.querySelector('[data-estado-tarjeta]');
        var resumen = datos.resumen;
        if (!zona || !resumen) return;

        var VEREDICTOS = <?= json_encode(\App\Models\PiezaPlacaModel::VEREDICTOS, JSON_UNESCAPED_UNICODE) ?>;
        var COLORES = { buena: 'success', regular: 'warning', repetir: 'danger' };
        var trozos = [];

        if (resumen.veredicto && VEREDICTOS[resumen.veredicto]) {
            trozos.push('<span class="badge text-bg-' + (COLORES[resumen.veredicto] || 'secondary') + '">'
                + VEREDICTOS[resumen.veredicto] + '</span>');
        } else {
            trozos.push('<span class="badge bg-body-secondary text-body-secondary border">'
                + (resumen.anotada ? 'sin juzgar' : 'sin anotar') + '</span>');
        }
        if (resumen.sinResponder > 0) {
            trozos.push('<span class="badge bg-body-secondary text-warning-emphasis border">'
                + '<i class="bi bi-question-circle"></i> ' + resumen.sinResponder + ' sin responder</span>');
        }
        if (resumen.enlaces > 0) {
            trozos.push('<span class="badge bg-body-secondary text-body-secondary border">'
                + '<i class="bi bi-link-45deg"></i> ' + resumen.enlaces + '</span>');
        }
        zona.innerHTML = trozos.join(' ');
    }

    /**
     * El aviso de "cuántas placas hacen falta" que vive dentro del propio
     * formulario (ver _bitacora_form.php): tras guardar, las cantidades
     * pueden haber cambiado, así que se repinta con lo que devuelve el
     * guardado — sin esto se quedaría enseñando el cálculo de antes de tocar
     * "Copias".
     */
    function escHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function repintarReparto(form, datos) {
        var caja = form.querySelector('[data-reparto]');
        if (!caja || !datos.reparto) return;

        var CAPACIDAD = <?= \App\Services\PiezaEmpaquetadoService::COLUMNAS * \App\Services\PiezaEmpaquetadoService::FILAS ?>;
        var reparto = datos.reparto;
        var html;

        function piezasDeBin(bin) {
            return bin.piezas.map(function (p) {
                return escHtml(p.etiqueta) + (p.cantidad > 1 ? ' ×' + p.cantidad : '');
            }).join(', ');
        }

        if (reparto.length <= 1) {
            caja.className = 'alert alert-secondary mt-3 py-2 mb-2 small';
            html = '<i class="bi bi-grid-3x3"></i> Cabe en <strong>una placa</strong> ('
                + (reparto[0] ? reparto[0].cuadrosUsados : 0) + '/' + CAPACIDAD + ' cuadrículas).';
        } else {
            // "No cabe" a secas suena a que algo ha ido mal — y no es así, es
            // que hacen falta dos o más, tan normal como una: de ahí el color
            // informativo, no de aviso (mismo criterio que _bitacora_form.php).
            caja.className = 'alert alert-info mt-3 py-2 mb-2 small';
            html = '<i class="bi bi-grid-3x3"></i> No cabe en una placa, pero sí en <strong>'
                + reparto.length + '</strong> (cálculo aproximado):'
                + '<ul class="mb-0 mt-1 ps-3">'
                + reparto.map(function (bin, i) {
                    return '<li>Placa ' + (i + 1) + ' (' + bin.cuadrosUsados + '/' + CAPACIDAD + '): ' + piezasDeBin(bin) + '</li>';
                }).join('')
                + '</ul>'
                + '<span class="text-muted">Usa "Repartir en otra placa" desde el histórico para materializarlo.</span>';
        }
        if (datos.sinMedir > 0) {
            html += '<div class="text-warning-emphasis mt-1">' + datos.sinMedir + ' STL sin cuadrícula medida, no entran en la cuenta.</div>';
        }

        caja.innerHTML = html;
    }

    // Sin placas no hay modal en la página: todo lo de arriba queda inerte
    // (los querySelectorAll no encuentran nada) pero estos dos sí hay que
    // guardarlos, o el bloque se cae con un TypeError y se lleva por delante
    // los interruptores de fotos de más abajo.
    if (botonGuardar) {
        botonGuardar.addEventListener('click', function () { guardar(formAbierto()); });
    }

    // Ctrl/Cmd+S guarda sin salir del campo: se escribe a ratos y con las
    // manos sucias, y buscar el botón cada vez es parte de lo que cansa.
    document.addEventListener('keydown', function (e) {
        if (!modalEl || !modalEl.classList.contains('show')) return;
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            guardar(formAbierto());
        }
    });

    if (modalEl) {
        // Cerrar con cosas escritas y sin guardar sería perderlas en silencio:
        // aquí no hay autoguardado a propósito (media bitácora se escribe a
        // medias y no queremos guardar borradores), así que al menos se avisa.
        modalEl.addEventListener('hide.bs.modal', function (e) {
            var form = formAbierto();
            if (form && form.dataset.sucio === '1'
                && !confirm('Has escrito cosas en la bitácora y no las has guardado. ¿Cerrar de todas formas?')) {
                e.preventDefault();
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            // Los botones vuelven a su tarjeta antes de vaciar el modal, o se
            // quedarían huérfanos en el pie y la placa se abriría sin ellos.
            if (accionesPrestadas && cunaDeAcciones) cunaDeAcciones.appendChild(accionesPrestadas);
            accionesPrestadas = null;
            cunaDeAcciones = null;
            tarjetaAbierta = null;
            peticion++;   // lo que llegue tarde ya no es de nadie
            cuerpo.innerHTML = '';
            decir('');
        });
    }

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
