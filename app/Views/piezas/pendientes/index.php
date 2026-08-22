<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<!-- Mismo patrón que la galería (fase 32): un token de sesión leído una vez,
     reutilizado en todos los fetch() de esta pantalla (crear pieza, marcar
     subtarea hecha) sin recargar la página. -->
<input type="hidden" id="pendientesCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-list-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pendientes de crear</strong>
</h5>

<p class="text-muted small">
    Esta pantalla no guarda nada propio: enseña las subtareas sin marcar de una tarea de
    <a href="<?= site_url('journal') ?>">Journal</a>, que sigue siendo el sitio donde se apuntan las
    ideas. "Crear pieza" da de alta la pieza aquí y, si sale bien, marca la subtarea como hecha en
    Journal — nombre y notas se pueden retocar antes de guardar, para dejar el nombre limpio con el
    resto del catálogo o para no repetir una pieza que ya exista con otro nombre.
</p>

<div id="mensajesPendientes">
    <?php if (session('success')): ?>
        <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
    <?php endif; ?>
</div>

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

<?php if (!$tarea): ?>
    <div class="card">
        <div class="card-body">
            <p class="mb-2">Todavía no hay ninguna tarea de Journal enlazada.</p>
            <form method="post" action="<?= site_url('piezas/pendientes/enlazar') ?>" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?>
                <select name="tarea_id" class="form-select form-select-sm" style="max-width: 400px;" required>
                    <option value="">— elige una tarea —</option>
                    <?php foreach ($tareasAgrupadas as $categoria => $tareas): ?>
                        <optgroup label="<?= esc($categoria) ?>">
                            <?php foreach ($tareas as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= esc($t['title']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-success">Enlazar</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
        <span class="text-muted small">Tomando de:</span>
        <a href="<?= site_url('journal/edit/' . (int) $tarea['id']) ?>" class="text-decoration-none">
            <?= esc($tarea['category']) ?> / <strong><?= esc($tarea['title']) ?></strong>
        </a>
        <span class="badge text-bg-secondary"><?= (int) ($tarea['completed'] ?? 0) ?>/<?= (int) ($tarea['amplitude'] ?? 0) ?></span>
        <button type="button" class="btn btn-sm btn-link text-decoration-none" id="btnCambiarTarea">Cambiar tarea enlazada</button>
    </div>

    <div class="card mb-3 d-none" id="cajaCambiarTarea">
        <div class="card-body">
            <form method="post" action="<?= site_url('piezas/pendientes/enlazar') ?>" class="d-flex gap-2 flex-wrap">
                <?= csrf_field() ?>
                <select name="tarea_id" class="form-select form-select-sm" style="max-width: 400px;" required>
                    <?php foreach ($tareasAgrupadas as $categoria => $tareas): ?>
                        <optgroup label="<?= esc($categoria) ?>">
                            <?php foreach ($tareas as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === (int) $tarea['id'] ? 'selected' : '' ?>>
                                    <?= esc($t['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-success">Cambiar</button>
            </form>
            <form method="post" action="<?= site_url('piezas/pendientes/desenlazar') ?>" class="mt-2"
                onsubmit="return confirm('¿Desenlazar? Esta pantalla se queda vacía hasta que enlaces otra tarea.');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger">Desenlazar</button>
            </form>
        </div>
    </div>

    <?php if (empty($pendientes)): ?>
        <p class="text-muted">No queda ninguna pendiente: todas las subtareas de esta tarea están marcadas.</p>
    <?php else: ?>
        <ul class="list-group" id="listaPendientes">
            <?php foreach ($pendientes as $s): ?>
                <li class="list-group-item d-flex align-items-center gap-2" data-subtask="<?= (int) $s['id'] ?>">
                    <span class="flex-grow-1"><?= esc($s['title']) ?></span>
                    <?php if (!empty($s['ficheros'])): ?>
                        <span class="badge bg-body-secondary text-body-secondary border"
                            title="Tiene <?= (int) $s['ficheros'] ?> material(es) en Journal — las fotos se copian solas al crear la pieza">
                            <i class="bi bi-paperclip"></i> <?= (int) $s['ficheros'] ?>
                        </span>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-marcar-hecha
                        title="Ya existe con otro nombre, o no hace falta crearla: solo marca la subtarea">
                        <i class="bi bi-check-lg"></i> Ya existe
                    </button>
                    <button type="button" class="btn btn-sm btn-success" data-crear-pieza data-nombre="<?= esc($s['title'], 'attr') ?>">
                        <i class="bi bi-plus-lg"></i> Crear pieza
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

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
                    <label class="form-label small">Pieza existente</label>
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

                <label class="form-label small" id="etiquetaNombre">Nombre</label>
                <input type="text" name="nombre" id="pendienteNombre" class="form-control form-control-sm mb-2" maxlength="150" required>

                <div id="campoCategoria" class="mb-2">
                    <label class="form-label small">Categoría</label>
                    <select name="categoria_id" class="form-select form-select-sm">
                        <option value="">— sin clasificar —</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= esc($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-success">Crear</button>
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
            lista.outerHTML = '<p class="text-muted">No queda ninguna pendiente: todas las subtareas de esta tarea están marcadas.</p>';
        }
    }

    // ---- "Ya existe": marca la subtarea hecha en Journal sin crear nada ----
    lista.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-marcar-hecha]');
        if (!boton) return;

        var id = boton.closest('[data-subtask]').getAttribute('data-subtask');
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
