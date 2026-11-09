<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<input type="hidden" id="pendientesCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<?php
    // Agrupadas por categoría de Journal, para un <select> con optgroups —
    // mismas categorías que ya usa Journal, no una lista propia.
    $tareasAgrupadas = [];
    foreach ($tareasPorCategoria as $t) {
        $tareasAgrupadas[$t['category']][] = $t;
    }

    // Mismo motivo para "Pieza existente" en el modal de abajo: con el
    // catálogo creciendo, un <select> plano se vuelve un scroll interminable.
    // No hace falta un segundo paso (categoría y luego pieza) — agrupar el
    // mismo selector con <optgroup> ya lo resuelve en un único control.
    // Recorre $categorias (ya vienen ordenadas) para que los grupos salgan en
    // ese orden, y deja "Sin clasificar" al final con lo que sobre.
    $familiasPorCategoria = [];
    foreach ($categorias as $c) {
        $familiasPorCategoria[$c['nombre']] = [];
    }
    $familiasSinClasificar = [];
    foreach ($familias as $f) {
        $nombreCategoria = null;
        foreach ($categorias as $c) {
            if ((int) $c['id'] === (int) ($f['categoria_id'] ?? 0)) {
                $nombreCategoria = $c['nombre'];
                break;
            }
        }
        if ($nombreCategoria !== null) {
            $familiasPorCategoria[$nombreCategoria][] = $f;
        } else {
            $familiasSinClasificar[] = $f;
        }
    }
    $familiasPorCategoria = array_filter($familiasPorCategoria);
    if ($familiasSinClasificar !== []) {
        $familiasPorCategoria['Sin clasificar'] = $familiasSinClasificar;
    }
?>

<div class="fv pv pe">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Pendientes de crear</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('journal') ?>" class="fv-gbtn" title="Donde se apuntan las ideas"><i class="bi bi-journal-text"></i> Journal</a>
            <?php if ($tarea): ?>
                <button type="button" class="fv-gbtn" id="btnCambiarTarea"><i class="bi bi-link-45deg"></i> Cambiar tarea enlazada</button>
            <?php endif; ?>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/pendientes?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Pendientes de crear</h1>
        <div class="fv-meta">
            <?php if ($tarea): ?>
                <span><b><?= count($pendientes) ?></b> por crear</span>
                <span class="fv-dot"></span>
                <span>De <a href="<?= site_url('journal/edit/' . (int) $tarea['id']) ?>"><?= esc($tarea['category']) ?> / <b><?= esc($tarea['title']) ?></b></a></span>
                <span class="fv-mono"><?= (int) ($tarea['completed'] ?? 0) ?>/<?= (int) ($tarea['amplitude'] ?? 0) ?></span>
            <?php else: ?>
                <span class="fv-estado e-warn">Sin tarea de Journal enlazada</span>
            <?php endif; ?>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿De dónde salen?</summary>
        <div class="fv-cuerpo">
            <span>Esta pantalla no guarda nada propio: enseña las subtareas sin marcar de una tarea de <a href="<?= site_url('journal') ?>">Journal</a>, que sigue siendo donde se apuntan las ideas. <b>Crear pieza</b> la da de alta aquí y, si sale bien, marca la subtarea como hecha en Journal. Nombre y notas se pueden retocar antes de guardar. <b>Ya existe</b> solo marca la subtarea.</span>
        </div>
    </details>

    <div id="mensajesPendientes">
        <?php if (session('success')): ?>
            <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
            <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
        <?php endif; ?>
    </div>

    <?php if (!$tarea): ?>
        <section class="fv-panel pv-estrecha">
            <div class="fv-ph"><h3>Enlaza la tarea de Journal donde apuntas las ideas</h3></div>
            <form method="post" action="<?= site_url('piezas/pendientes/enlazar') ?>" class="fv-campo">
                <?= csrf_field() ?>
                <select name="tarea_id" class="fv-input" required>
                    <option value="">— elige una tarea —</option>
                    <?php foreach ($tareasAgrupadas as $categoria => $tareas): ?>
                        <optgroup label="<?= esc($categoria) ?>">
                            <?php foreach ($tareas as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= esc($t['title']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button class="fv-btn fv-btn-pri">Enlazar</button>
            </form>
        </section>
    <?php else: ?>
        <section class="fv-panel pv-estrecha d-none" id="cajaCambiarTarea">
            <div class="fv-ph"><h3>Tarea enlazada</h3></div>
            <form method="post" action="<?= site_url('piezas/pendientes/enlazar') ?>" class="fv-campo">
                <?= csrf_field() ?>
                <select name="tarea_id" class="fv-input" required>
                    <?php foreach ($tareasAgrupadas as $categoria => $tareas): ?>
                        <optgroup label="<?= esc($categoria) ?>">
                            <?php foreach ($tareas as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === (int) $tarea['id'] ? 'selected' : '' ?>><?= esc($t['title']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button class="fv-btn fv-btn-pri">Cambiar</button>
            </form>
            <form method="post" action="<?= site_url('piezas/pendientes/desenlazar') ?>"
                onsubmit="return confirm('¿Desenlazar? Esta pantalla se queda vacía hasta que enlaces otra tarea.');">
                <?= csrf_field() ?>
                <button class="fv-btn fv-btn-sm fv-btn-peligro"><i class="bi bi-x-lg"></i> Desenlazar</button>
            </form>
        </section>

        <section class="fv-panel pv-estrecha">
            <?php if (empty($pendientes)): ?>
                <p class="fv-vacio">No queda ninguna pendiente: todas las subtareas de esta tarea están marcadas.</p>
            <?php else: ?>
                <div class="pv-lista" id="listaPendientes">
                    <?php foreach ($pendientes as $s): ?>
                        <div class="pv-fila" data-subtask="<?= (int) $s['id'] ?>">
                            <span class="pv-ico"><i class="bi bi-lightbulb"></i></span>
                            <div class="pv-cuerpo">
                                <span class="pv-nom"><?= esc($s['title']) ?></span>
                                <?php if (!empty($s['ficheros'])): ?>
                                    <div class="pv-sub"><span title="Las fotos se copian solas al crear la pieza"><i class="bi bi-paperclip"></i> <?= (int) $s['ficheros'] === 1 ? '1 material' : (int) $s['ficheros'] . ' materiales' ?> en Journal</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="pv-fin">
                                <button type="button" class="fv-btn fv-btn-sm pe-ya" data-marcar-hecha title="Ya existe con otro nombre, o no hace falta crearla: solo marca la subtarea"><i class="bi bi-check-lg"></i> Ya existe</button>
                                <button type="button" class="fv-btn fv-btn-sm fv-btn-pri" data-crear-pieza data-nombre="<?= esc($s['title'], 'attr') ?>"><i class="bi bi-plus-lg"></i> Crear pieza</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<!-- Alta de pieza desde una pendiente: mismos campos que el modal de Piezas
     (spec vocabulario "familia"), pero apunta a Web::crearFamilia por
     fetch() para poder encadenar el toggle de la subtarea sin recargar. -->
<div class="modal fade" id="modalCrearDesdePendiente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formCrearDesdePendiente">
            <div class="modal-header">
                <h6 class="modal-title">Pieza nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger py-2 d-none" id="errorCrearDesdePendiente"></div>

                <?php // No todo lo que apuntas en Journal es una pieza nueva: a veces es
                      // otra línea de diseño de una que ya existe (una silla más, un color
                      // distinto...). Elegir aquí evita crear una familia suelta y tener
                      // que fusionarla a mano después. ?>
                <div class="btn-group btn-group-sm mb-2 w-100" role="group">
                    <input type="radio" class="btn-check" name="modo" id="modoNueva" value="nueva" checked>
                    <label class="btn btn-outline-secondary" for="modoNueva">Pieza nueva</label>
                    <input type="radio" class="btn-check" name="modo" id="modoVariante" value="variante"
                        <?= empty($familias) ? 'disabled' : '' ?>>
                    <label class="btn btn-outline-secondary" for="modoVariante">Es una variante de...</label>
                </div>

                <div id="campoFamiliaExistente" class="d-none mb-2">
                    <label class="form-label">Pieza existente</label>
                    <select name="familia_id" class="form-select form-select-sm">
                        <option value="">— elige una pieza —</option>
                        <?php foreach ($familiasPorCategoria as $nombreCategoria => $familiasDeCategoria): ?>
                            <optgroup label="<?= esc($nombreCategoria) ?>">
                                <?php foreach ($familiasDeCategoria as $f): ?>
                                    <option value="<?= (int) $f['id'] ?>"><?= esc($f['nombre']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="form-label" id="etiquetaNombre">Nombre</label>
                <input type="text" name="nombre" id="pendienteNombre" class="form-control form-control-sm mb-2" maxlength="150" required>

                <div id="campoCategoria" class="mb-2">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-select form-select-sm">
                        <option value="">— sin clasificar —</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= esc($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="form-label">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary">Crear pieza</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var tokenCampo = document.getElementById('pendientesCsrfToken');

    function llamada(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenCampo ? tokenCampo.value : ''
            },
            body: formData || null
        }).then(function (r) { return r.json(); });
    }

    var btnCambiarTarea = document.getElementById('btnCambiarTarea');
    var cajaCambiarTarea = document.getElementById('cajaCambiarTarea');
    if (btnCambiarTarea && cajaCambiarTarea) {
        btnCambiarTarea.addEventListener('click', function () {
            cajaCambiarTarea.classList.toggle('d-none');
        });
    }

    var lista = document.getElementById('listaPendientes');
    if (!lista) return;

    function quitarFila(id) {
        var fila = lista.querySelector('[data-subtask="' + id + '"]');
        if (fila) fila.remove();
        if (!lista.querySelector('[data-subtask]')) {
            lista.outerHTML = '<p class="fv-vacio">No queda ninguna pendiente: todas las subtareas de esta tarea están marcadas.</p>';
        }
    }

    // ---- "Ya existe": marca la subtarea hecha en Journal sin crear nada ----
    lista.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-marcar-hecha]');
        if (!boton) return;

        var id = boton.closest('[data-subtask]').getAttribute('data-subtask');
        if (!confirm('¿Marcar como hecha en Journal sin crear ninguna pieza?')) return;
        boton.disabled = true;

        llamada('<?= site_url('journal/subtasks/') ?>' + id + '/toggle').then(function (datos) {
            if (!datos.success) { boton.disabled = false; alert('No se pudo marcar.'); return; }
            quitarFila(id);
        }).catch(function () {
            boton.disabled = false;
            alert('No se pudo conectar con el servidor.');
        });
    });

    // ---- "Crear pieza": abre el modal precargado con el nombre ----
    // La instancia de bootstrap.Modal se crea al vuelo, no aquí arriba: este
    // <script> se pinta antes que bootstrap.bundle.min.js (va al final del
    // body, ver layouts/default.php), así que "new bootstrap.Modal(...)" en
    // este punto revienta con "bootstrap is not defined" y se lleva por
    // delante todo lo que va después en el mismo IIFE — mejor esperar al
    // primer clic, cuando el bundle ya está cargado seguro.
    var modalEl = document.getElementById('modalCrearDesdePendiente');
    var form = document.getElementById('formCrearDesdePendiente');
    var campoNombre = document.getElementById('pendienteNombre');
    var etiquetaNombre = document.getElementById('etiquetaNombre');
    var campoFamiliaExistente = document.getElementById('campoFamiliaExistente');
    var selectFamilia = campoFamiliaExistente.querySelector('select');
    var campoCategoria = document.getElementById('campoCategoria');
    var cajaError = document.getElementById('errorCrearDesdePendiente');
    var subtaskActual = null;

    // Alterna entre "pieza nueva" (categoría) y "variante de..." (elegir la
    // pieza existente) — mismos dos caminos que ya usa el resto de Piezas
    // (crearFamilia / crearVariante), solo que aquí se decide en el momento
    // en vez de crear siempre una familia suelta.
    function pintarModo() {
        var esVariante = form.modo.value === 'variante';
        campoFamiliaExistente.classList.toggle('d-none', !esVariante);
        campoCategoria.classList.toggle('d-none', esVariante);
        selectFamilia.required = esVariante;
        etiquetaNombre.textContent = esVariante ? 'Nombre de la variante' : 'Nombre';
    }
    form.querySelectorAll('input[name=modo]').forEach(function (radio) {
        radio.addEventListener('change', pintarModo);
    });

    lista.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-crear-pieza]');
        if (!boton) return;

        subtaskActual = boton.closest('[data-subtask]').getAttribute('data-subtask');
        cajaError.classList.add('d-none');
        form.reset();
        campoNombre.value = boton.getAttribute('data-nombre');
        pintarModo();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!subtaskActual) return;

        var esVariante = form.modo.value === 'variante';
        var botonGuardar = form.querySelector('button[type=submit]');
        botonGuardar.disabled = true;
        cajaError.classList.add('d-none');

        var url = esVariante ? '<?= site_url('piezas/variante') ?>' : '<?= site_url('piezas/familia') ?>';

        llamada(url, new FormData(form)).then(function (datos) {
            if (!datos.ok) {
                cajaError.textContent = datos.mensaje || 'No se pudo crear la pieza.';
                cajaError.classList.remove('d-none');
                botonGuardar.disabled = false;
                return;
            }

            // Las dos respuestas no tienen la misma forma: crearFamilia() manda
            // {familia:{...}, variante:{...}} y crearVariante() manda la
            // variante suelta (con familia_id ya dentro) — se normalizan aquí
            // para que el resto del flujo no tenga que saber cuál de las dos fue.
            var familiaId = esVariante ? datos.familia_id : datos.familia.id;
            var varianteId = esVariante ? datos.id : datos.variante.id;

            // Copia (no enlaza) las imágenes que colgaban de esta subtarea en
            // Journal como referencias de la pieza recién creada — si falla,
            // no deshace la creación: la pieza ya existe, y las fotos se
            // pueden subir a mano desde su ficha si hiciera falta.
            var datosPieza = new FormData();
            datosPieza.append('familia_id', familiaId);
            datosPieza.append('variante_id', varianteId);

            llamada('<?= site_url('piezas/pendientes/subtarea/') ?>' + subtaskActual + '/copiar-referencias', datosPieza)
                .catch(function () { /* red caída: se calla, no bloquea el resto del flujo */ })
                .then(function () {
                    // La subtarea, aparte: si el toggle falla (red, lo que sea)
                    // tampoco se deshace nada de lo anterior — se puede marcar
                    // "Ya existe" a mano luego.
                    return llamada('<?= site_url('journal/subtasks/') ?>' + subtaskActual + '/toggle');
                })
                .then(function () {
                    quitarFila(subtaskActual);
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    botonGuardar.disabled = false;
                });
        }).catch(function () {
            cajaError.textContent = 'No se pudo conectar con el servidor.';
            cajaError.classList.remove('d-none');
            botonGuardar.disabled = false;
        });
    });
})();
</script>

<?= $this->endSection() ?>
