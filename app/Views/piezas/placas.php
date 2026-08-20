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
    y qué piezas llevaba. Desde aquí puedes volver a descargarla, cargarla de nuevo en la placa
    actual para reimprimir la misma combinación, ponerle un nombre que la reconozcas, o borrar la
    entrada si solo era una prueba — no borra ningún STL ni versión, solo esta anotación.
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
</style>

<?php // Dos interruptores, no uno: en las tarjetas la foto es para reconocer la placa
      // de un vistazo y en el listado del modal es solo un apoyo al texto, así que cada
      // sitio se apaga por su cuenta. Cada uno recuerda su estado. ?>
<div class="btn-group btn-group-sm mb-2" role="group">
    <button type="button" class="btn btn-outline-secondary" data-fotos="tarjetas">
        <i class="bi bi-image"></i> Ocultar portadas
    </button>
    <button type="button" class="btn btn-outline-secondary" data-fotos="lista">
        <i class="bi bi-list-ul"></i> Ocultar miniaturas
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
          // nombre se truncaba casi siempre. Al pulsar se despliegan a todo el ancho
          // con la lista completa y las acciones — así se puede escanear el histórico
          // entero de un vistazo sin que cada placa ocupe una fila propia. ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-2">
        <?php foreach ($placas as $placa): ?>
            <?php
                $lista = $piezas[$placa['id']] ?? [];
                $disponibles = count(array_filter($lista, static fn($p) => $p['disponible']));
                $idPlaca = (int) $placa['id'];
                $idDetalle = 'detalle-placa-' . $idPlaca;
                $fecha = strtotime($placa['creado_en']);
                // Portada en tira baja (no cuadrada): la tarjeta es un lomo de
                // archivador, no una foto de catálogo — con reconocerla basta.
                // Hasta 4 miniaturas en fila; el detalle se ve en el modal.
                $fotos = array_values(array_filter(array_column($lista, 'miniatura')));
            ?>
            <div class="col" data-tarjeta-placa>
                <div class="card shadow-sm h-100 user-select-none" style="cursor: pointer"
                    data-abrir-placa="<?= $idDetalle ?>" title="Ver el contenido de la placa">
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
                        <div class="small fw-semibold text-truncate" title="<?= esc($placa['nombre'], 'attr') ?>">
                            <?= esc($placa['nombre']) ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: .75rem;">
                            <span><?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?></span>
                            <span class="ms-auto"><?= count($lista) ?> pieza<?= count($lista) === 1 ? '' : 's' ?></span>
                            <?php if ($disponibles < count($lista)): ?>
                                <i class="bi bi-exclamation-triangle text-warning"
                                    title="Algún STL de esta placa ya no está disponible"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php // Se renderiza aquí dentro, oculto, y el JS lo mueve al modal al
                          // abrirlo: así los formularios (con su CSRF) son los mismos de
                          // siempre, sin duplicar un modal por placa en el HTML. ?>
                    <div id="<?= $idDetalle ?>" class="d-none" data-nombre-placa="<?= esc($placa['nombre'], 'attr') ?>">
                        <?php // Dos bloques con destino distinto dentro del modal: lo que se lee
                              // (fecha, nombre, listado) va al cuerpo y los botones al pie, que es
                              // donde se espera encontrarlos. El JS los reparte al abrir. ?>
                        <div data-cuerpo-placa>
                            <div class="text-muted small mb-2"><?= esc($placa['creado_en']) ?></div>

                            <form method="post" class="d-flex gap-1 mb-2"
                                action="<?= site_url('piezas/placa/' . $idPlaca . '/renombrar') ?>">
                                <?= csrf_field() ?>
                                <input type="text" name="nombre" class="form-control form-control-sm"
                                    value="<?= esc($placa['nombre'], 'attr') ?>" maxlength="150" required>
                                <button class="btn btn-sm btn-outline-secondary flex-shrink-0">Renombrar</button>
                            </form>

                            <?php if (empty($lista)): ?>
                                <p class="text-muted small mb-0">Ninguna de esas versiones existe ya.</p>
                            <?php else: ?>
                                <?php // Listado plano y en letra pequeña: aquí se viene a leer qué
                                      // llevaba la placa, no a mirar fotos — la miniatura es solo
                                      // para reconocer la pieza de reojo. ?>
                                <ul class="list-unstyled mb-0" style="font-size: .8rem;">
                                    <?php foreach ($lista as $p): ?>
                                        <li class="d-flex align-items-center gap-2 py-1 border-bottom border-secondary-subtle">
                                            <?php if ($p['miniatura']): ?>
                                                <img src="<?= $p['miniatura'] ?>" loading="lazy" alt="" data-foto-placa="lista"
                                                    class="rounded border flex-shrink-0" style="width: 26px; height: 26px; object-fit: cover;">
                                            <?php endif; ?>
                                            <?php if ($p['variante'] && $p['familia']): ?>
                                                <a href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) ?>"
                                                    class="text-decoration-none text-body flex-grow-1 text-truncate">
                                                    <?= esc($p['familia']['nombre']) ?> / <?= esc($p['variante']['nombre']) ?>
                                                    <span class="text-muted">· v<?= sprintf('%03d', (int) $p['version']['numero']) ?></span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted flex-grow-1">(esa pieza ya no existe)</span>
                                            <?php endif; ?>
                                            <?php if (!$p['disponible']): ?>
                                                <span class="text-warning flex-shrink-0" title="El STL ya no está en el almacén">
                                                    <i class="bi bi-exclamation-triangle"></i> sin STL ya
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap justify-content-end gap-2" data-acciones-placa>
                            <?php // A la izquierda del todo y separada del resto: la bitácora es
                                  // otra pantalla, no una acción sobre el zip como las demás. ?>
                            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>"
                                class="btn btn-sm btn-outline-info me-auto" title="Qué se probó en esta placa y cómo salió">
                                <i class="bi bi-journal-text"></i> Ver bitácora
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

    <?php // Un único modal para todas las placas: al abrirlo se le mete dentro el
          // bloque de detalle que ya venía renderizado (oculto) en su tarjeta, y al
          // cerrarlo se devuelve. Mover el nodo en vez de duplicarlo mantiene los
          // formularios y sus tokens CSRF intactos, y el HTML no crece con un modal
          // por placa. ?>
    <div class="modal fade" id="modalPlaca" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modalPlacaTitulo"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="modalPlacaCuerpo"></div>
                <div class="modal-footer py-2" id="modalPlacaPie"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    // ---- Contenido de una placa, en modal -----------------------------------
    var modalEl = document.getElementById('modalPlaca');
    var cuerpo = document.getElementById('modalPlacaCuerpo');
    var pie = document.getElementById('modalPlacaPie');
    var titulo = document.getElementById('modalPlacaTitulo');
    var detalleAbierto = null;   // el nodo prestado, para saber a quién devolverlo
    var cunaDelDetalle = null;   // su tarjeta de origen
    var accionesAbiertas = null; // los botones, que van al pie y no al cuerpo

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

            detalleAbierto = detalle;
            cunaDelDetalle = detalle.parentNode;
            titulo.textContent = detalle.getAttribute('data-nombre-placa') || 'Placa';

            detalle.classList.remove('d-none');
            cuerpo.appendChild(detalle);

            // Los botones salen del bloque prestado y se van al pie del modal.
            accionesAbiertas = detalle.querySelector('[data-acciones-placa]');
            if (accionesAbiertas) pie.appendChild(accionesAbiertas);

            modal.show();
        });
    });

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (!detalleAbierto || !cunaDelDetalle) return;
            // Primero los botones de vuelta a su bloque, y el bloque a su tarjeta:
            // al revés dejaría los botones huérfanos en el pie del modal.
            if (accionesAbiertas) {
                detalleAbierto.appendChild(accionesAbiertas);
                accionesAbiertas = null;
            }
            detalleAbierto.classList.add('d-none');
            cunaDelDetalle.appendChild(detalleAbierto);
            detalleAbierto = null;
            cunaDelDetalle = null;
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
