<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-clock-history text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/galeria') ?>" class="text-decoration-none text-muted fw-normal">Galería</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Placas</strong>
</h5>

<p class="text-muted small">
    Cada vez que descargas o guardas una placa desde la galería queda anotada aquí sola, con fecha
    y qué piezas llevaba. Pulsa una para abrir su bitácora y anotar cómo salió sin salir de esta
    pantalla: cuánto tardó, qué pesaba el tanque, qué querías probar y qué aprendiste. Desde el
    mismo sitio puedes volver a descargarla, cargarla otra vez en la placa actual para reimprimir
    la misma combinación, o borrar la entrada — no borra ningún STL ni versión, solo la anotación.
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

<?php if (empty($placas)): ?>
    <p class="text-muted">
        Todavía no hay ninguna placa descargada. En cuanto bajes el zip de alguna desde la
        galería, aparecerá aquí.
    </p>
<?php else: ?>
    <?php // Tarjetas de 4 de ancho en pantalla normal — menos que la galería (6) a
          // propósito: aquí cada tarjeta lleva nombre, fecha y contadores, y a 6 el
          // nombre se truncaba casi siempre. Al pulsar se abre el cuaderno de esa
          // placa en un modal — así se puede escanear el histórico entero de un
          // vistazo y anotar en cualquiera sin cambiar de pantalla. ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-2">
        <?php foreach ($placas as $placa): ?>
            <?php
                $lista = $piezas[$placa['id']] ?? [];
                $resumen = $resumenes[$placa['id']] ?? ['anotada' => false, 'sinResponder' => 0, 'enlaces' => 0, 'veredicto' => null];
                $disponibles = count(array_filter($lista, static fn($p) => $p['disponible']));
                $idPlaca = (int) $placa['id'];
                $idDetalle = 'detalle-placa-' . $idPlaca;
                $fecha = strtotime($placa['creado_en']);
                // Portada en tira baja (no cuadrada): la tarjeta es un lomo de
                // archivador, no una foto de catálogo — con reconocerla basta.
                // Hasta 4 miniaturas en fila; el detalle se ve en el modal.
                $fotos = array_values(array_filter(array_column($lista, 'miniatura')));
            ?>
            <div class="col" data-tarjeta-placa="<?= $idPlaca ?>">
                <div class="card shadow-sm h-100 user-select-none lomo-placa <?= $resumen['veredicto'] ? 'lomo-' . esc($resumen['veredicto'], 'attr') : '' ?>"
                    style="cursor: pointer" data-abrir-placa="<?= $idDetalle ?>" data-placa="<?= $idPlaca ?>"
                    title="Abrir la bitácora de esta placa">
                    <?php if ($fotos): ?>
                        <div class="d-flex" data-foto-placa="tarjeta"
                            style="gap: 2px; height: 72px; overflow: hidden; background: rgba(127,127,127,.15);">
                            <?php foreach (array_slice($fotos, 0, 4) as $foto): ?>
                                <img src="<?= $foto ?>" loading="lazy" alt=""
                                    style="flex: 1 1 0; min-width: 0; height: 100%; object-fit: cover; display: block;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-body p-2">
                        <div class="small fw-semibold text-truncate" data-nombre-tarjeta
                            title="<?= esc($placa['nombre'], 'attr') ?>"><?= esc($placa['nombre']) ?></div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: .75rem;">
                            <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
                            <span class="ms-auto"><?= count($lista) ?> pieza<?= count($lista) === 1 ? '' : 's' ?></span>
                            <?php if ($disponibles < count($lista)): ?>
                                <i class="bi bi-exclamation-triangle text-warning"
                                    title="Algún STL de esta placa ya no está disponible"></i>
                            <?php endif; ?>
                        </div>

                        <?php // Segunda línea: en qué punto está el cuaderno. Lo que
                              // se busca aquí es qué queda por cerrar —una placa sin
                              // juzgar, o con preguntas escritas antes de imprimir a
                              // las que nadie volvió— sin tener que abrirlas una a una. ?>
                        <div class="d-flex align-items-center gap-1 flex-wrap mt-1" style="font-size: .7rem;"
                            data-estado-tarjeta>
                            <?php
                                $veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;
                                $colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
                            ?>
                            <?php if ($resumen['veredicto'] && isset($veredictos[$resumen['veredicto']])): ?>
                                <span class="badge text-bg-<?= $colorVeredicto[$resumen['veredicto']] ?? 'secondary' ?>">
                                    <?= esc($veredictos[$resumen['veredicto']]) ?>
                                </span>
                            <?php elseif (!$resumen['anotada']): ?>
                                <span class="badge bg-body-secondary text-body-secondary border">sin anotar</span>
                            <?php else: ?>
                                <span class="badge bg-body-secondary text-body-secondary border">sin juzgar</span>
                            <?php endif; ?>

                            <?php if ($resumen['sinResponder'] > 0): ?>
                                <span class="badge bg-body-secondary text-warning-emphasis border"
                                    title="Preguntas que escribiste antes de imprimir y siguen sin respuesta">
                                    <i class="bi bi-question-circle"></i> <?= (int) $resumen['sinResponder'] ?> sin responder
                                </span>
                            <?php endif; ?>

                            <?php if ($resumen['enlaces'] > 0): ?>
                                <span class="badge bg-body-secondary text-body-secondary border" title="Tiene enlaces guardados">
                                    <i class="bi bi-link-45deg"></i> <?= (int) $resumen['enlaces'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php // Solo los botones viajan al modal, y siguen renderizados aquí
                          // (ocultos) para que sus formularios lleven el CSRF de siempre
                          // sin duplicar un modal por placa. El contenido —la bitácora—
                          // se pide al abrir: son muchas placas y meter treinta
                          // formularios completos en la página costaría más que todo lo
                          // demás junto. ?>
                    <div id="<?= $idDetalle ?>" class="d-none" data-nombre-placa="<?= esc($placa['nombre'], 'attr') ?>"
                        data-montada="<?= esc(date('d/m/Y H:i', $fecha ?: time()), 'attr') ?>">
                        <div class="d-flex flex-wrap gap-2" data-acciones-placa>
                            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" target="_blank" rel="noopener"
                                class="btn btn-sm btn-outline-info" title="La bitácora entera, para leerla de corrido">
                                <i class="bi bi-journal-text"></i> Ver limpio
                            </a>
                            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/descargar') ?>"
                                class="btn btn-sm btn-outline-primary" title="Volver a generar el zip con lo que haya ahora mismo">
                                <i class="bi bi-download"></i> Descargar de nuevo
                            </a>
                            <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/cargar') ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Sustituye lo que haya ahora en la placa actual">
                                    <i class="bi bi-arrow-return-left"></i> Cargar en la placa actual
                                </button>
                            </form>
                            <form method="post"
                                action="<?= site_url('piezas/placa/' . $idPlaca . '/borrar') ?>"
                                onsubmit="return confirm('¿Borrar «<?= esc($placa['nombre'], 'attr') ?>» del histórico? Los STL y versiones no se tocan, solo esta anotación.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Borrar del histórico">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

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
