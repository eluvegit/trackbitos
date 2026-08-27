<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/pedidos') ?>" class="text-decoration-none text-muted fw-normal">Pedidos</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Pedido #<?= (int) $pedido['id'] ?></strong>

    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Piezas listas para imprimir">
        <i class="bi bi-grid-3x3-gap"></i> Galería
    </a>
    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary" title="Histórico de placas (guardadas y descargadas)">
        <i class="bi bi-printer"></i> Placas
    </a>
    <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/cargar-placa') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-success" title="Añade a la placa actual el STL de cada pieza de este pedido">
            <i class="bi bi-file-earmark-arrow-down"></i> Cargar piezas a la placa
        </button>
    </form>
    <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/borrar') ?>"
        onsubmit="return confirm('¿Borrar el pedido #<?= (int) $pedido['id'] ?> y todas sus líneas? No se puede deshacer.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar este pedido">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small d-flex align-items-center gap-1 flex-wrap">
    <span>
        Origen: <?= esc($pedido['origen']) ?> · Creado: <?= esc($pedido['creado_en']) ?>
        <?php if ($pedido['referencia_externa']): ?> · Ref: <?= esc($pedido['referencia_externa']) ?><?php endif; ?>
        <?php if ($pedido['notas']): ?> · Notas: <?= esc($pedido['notas']) ?><?php endif; ?>
    </span>
    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Editar referencia y notas"
        data-bs-toggle="modal" data-bs-target="#modalDatosPedido">
        <i class="bi bi-pencil"></i>
    </button>
</p>

<div class="modal fade" id="modalDatosPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/datos') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Editar pedido #<?= (int) $pedido['id'] ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Referencia (opcional)</label>
                <input type="text" name="referencia_externa" class="form-control form-control-sm mb-2"
                    value="<?= esc($pedido['referencia_externa'] ?? '', 'attr') ?>"
                    placeholder="nº de pedido, si viene de fuera" maxlength="50">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"><?= esc($pedido['notas'] ?? '') ?></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
    $etiquetas = ['nuevo' => 'Pendiente', 'en_produccion' => 'Produciendo', 'completado' => 'Hecho', 'cancelado' => 'Cancelado'];
    $colores   = ['nuevo' => 'primary', 'en_produccion' => 'warning', 'completado' => 'success', 'cancelado' => 'secondary'];
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($estados as $estado): ?>
        <?php $activo = $estado === $pedido['estado']; ?>
        <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/estado') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="estado" value="<?= esc($estado) ?>">
            <button type="submit" class="btn btn-sm rounded-pill <?= $activo ? 'btn-' . $colores[$estado] : 'btn-outline-' . $colores[$estado] ?>" <?= $activo ? 'disabled' : '' ?>>
                <?= $activo ? '<i class="bi bi-check-lg me-1"></i>' : '' ?><?= esc($etiquetas[$estado] ?? $estado) ?>
            </button>
        </form>
    <?php endforeach; ?>
</div>

<?php // Aparte y no con la utilidad table-success de Bootstrap: esa deja el fondo
      // en un verde lavado que combinado con los grises de "· variante", el SKU
      // y las notas se volvía casi ilegible. Aquí se fuerza texto oscuro sobre
      // verde sólido en toda la fila, botones incluidos. ?>
<style>
    .fila-completa, .fila-completa td { background-color: var(--bs-success) !important; color: #052e16 !important; }
    .fila-completa .text-muted,
    .fila-completa .badge { color: #052e16 !important; }
    .fila-completa .badge { background-color: rgba(5, 46, 22, .12) !important; border-color: rgba(5, 46, 22, .35) !important; }
    .fila-completa .btn-outline-secondary { color: #052e16 !important; border-color: rgba(5, 46, 22, .4) !important; }
    .fila-completa .btn-outline-primary { color: #052e16 !important; border-color: rgba(5, 46, 22, .4) !important; }
</style>

<?php
    /**
     * Cada línea es editable in situ, siempre — no hay un estado "viendo" y
     * otro "editando" distintos: eso obligaba a un clic solo para llegar a
     * poder tocar algo, y la fila cambiaba de forma al entrar a editar. El
     * formulario de cada línea vive fuera de la tabla (un <form> no puede
     * envolver <tr> sueltas) y sus campos, repartidos por las celdas, se
     * asocian a él con el atributo `form` — así la fila es una sola, no dos.
     *
     * Guardar, añadir, borrar y ajustar completado van todos por AJAX (ver
     * el <script> más abajo): el servidor sigue siendo quien decide cómo se
     * pinta la fila (misma vista parcial _linea_fila que en la carga
     * inicial), el JS solo la sustituye entera con lo que responde.
     */
?>
<div id="lineas-forms">
    <?php foreach ($pedido['lineas'] as $linea): ?>
        <?= view('piezas/pedidos/_linea_form', ['linea' => $linea]) ?>
    <?php endforeach; ?>
</div>

<table class="table table-sm align-middle">
    <thead>
        <tr>
            <th></th>
            <th>Pieza</th>
            <th>Cantidad</th>
            <?php // A mano, sin cuadrar contra ninguna placa (spec: una pieza puede
                  // salir mal aunque esté impresa, y eso es un juicio que no le
                  // corresponde adivinar al sistema) — solo un contador que subes tú. ?>
            <th>Completado</th>
            <th>Notas</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="lineas-tbody">
        <?php foreach ($pedido['lineas'] as $linea): ?>
            <?= view('piezas/pedidos/_linea_fila', ['linea' => $linea]) ?>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="card card-body mb-3">
    <h6 class="mb-2"><i class="bi bi-plus-lg"></i> Añadir línea</h6>
    <form id="form-nueva-linea" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/linea') ?>" class="row g-1 align-items-center">
        <?= csrf_field() ?>
        <div class="col-md-2 position-relative">
            <input type="text" class="form-control form-control-sm" data-buscar-variante autocomplete="off"
                placeholder="Buscar pieza del catálogo…">
            <input type="hidden" name="variante_id" data-variante-id value="">
            <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;" data-resultados-variante></div>
        </div>
        <div class="col-md-2">
            <input type="text" name="descripcion_libre" class="form-control form-control-sm"
                placeholder="…o descripción (pieza futura, aún sin hacer)">
        </div>
        <div class="col-auto">
            <input type="number" name="cantidad" class="form-control form-control-sm" min="1" value="1" required style="width: 4.5em;">
        </div>
        <div class="col">
            <textarea name="notas" class="form-control form-control-sm" maxlength="150" rows="2" placeholder="Notas"></textarea>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-success" title="Añadir línea">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var espera = null;
    document.addEventListener('input', function (e) {
        if (!e.target.matches('[data-buscar-variante]')) return;
        var caja = e.target;
        var contenedor = caja.closest('.position-relative');
        var resultados = contenedor.querySelector('[data-resultados-variante]');
        var hidden = contenedor.querySelector('[data-variante-id]');

        clearTimeout(espera);
        hidden.value = '';
        var q = caja.value.trim();
        if (q.length < 2) {
            resultados.style.display = 'none';
            resultados.innerHTML = '';
            return;
        }

        espera = setTimeout(function () {
            fetch('<?= site_url('piezas/pedido-variante-buscar') ?>?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var lista = d.resultados || [];
                    resultados.innerHTML = lista.length
                        ? lista.map(function (v) {
                            var textoHtml = v.texto.replace(/&/g, '&amp;').replace(/</g, '&lt;');
                            var textoAttr = textoHtml.replace(/"/g, '&quot;');
                            return '<button type="button" class="list-group-item list-group-item-action py-1 small" '
                                + 'data-elegir-variante data-variante-id="' + v.variante_id + '" data-texto="' + textoAttr + '">' + textoHtml + '</button>';
                        }).join('')
                        : '<div class="list-group-item py-1 small text-muted">Sin resultados</div>';
                    resultados.style.display = 'block';
                });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-elegir-variante]');
        if (!boton) return;
        var contenedor = boton.closest('.position-relative');
        var caja = contenedor.querySelector('[data-buscar-variante]');
        var resultados = contenedor.querySelector('[data-resultados-variante]');
        var hidden = contenedor.querySelector('[data-variante-id]');

        hidden.value = boton.getAttribute('data-variante-id');
        caja.value = boton.getAttribute('data-texto');
        resultados.style.display = 'none';
    });

    document.addEventListener('focusout', function (e) {
        if (!e.target.matches('[data-buscar-variante]')) return;
        var contenedor = e.target.closest('.position-relative');
        setTimeout(function () {
            contenedor.querySelector('[data-resultados-variante]').style.display = 'none';
        }, 200);
    });

    // ---- Guardar / añadir / borrar / ajustar completado, sin recargar ----
    // El servidor sigue siendo quien decide cómo se pinta cada fila (misma
    // vista parcial _linea_fila que en la carga inicial); el JS solo manda
    // el formulario por fetch y sustituye la fila entera por lo que responde.
    var tbody = document.getElementById('lineas-tbody');
    var formsOcultos = document.getElementById('lineas-forms');

    function enviar(form) {
        return fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); });
    }

    function reemplazarFila(id, html) {
        var fila = tbody.querySelector('tr[data-linea-id="' + id + '"]');
        if (fila) fila.outerHTML = html;
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        var boton = e.submitter;

        // Añadir línea: el servidor devuelve el <form> oculto y la <tr> de
        // la línea nueva, listos para insertar tal cual.
        if (form.id === 'form-nueva-linea') {
            e.preventDefault();
            if (boton) boton.disabled = true;
            enviar(form).then(function (datos) {
                if (!datos.ok) { alert(datos.mensaje || 'No se pudo añadir.'); return; }
                formsOcultos.insertAdjacentHTML('beforeend', datos.formHtml);
                tbody.insertAdjacentHTML('beforeend', datos.rowHtml);
                form.reset();
                form.querySelector('[data-variante-id]').value = '';
                form.querySelector('[data-buscar-variante]').focus();
            }).catch(function () {
                alert('No se pudo conectar con el servidor.');
            }).finally(function () {
                if (boton) boton.disabled = false;
            });
            return;
        }

        // Guardar cambios de una línea existente.
        if (form.id.indexOf('form-linea-') === 0) {
            e.preventDefault();
            var idGuardar = form.id.replace('form-linea-', '');
            if (boton) boton.disabled = true;
            enviar(form).then(function (datos) {
                if (!datos.ok) { alert(datos.mensaje || 'No se pudo guardar.'); return; }
                reemplazarFila(idGuardar, datos.rowHtml);
            }).catch(function () {
                alert('No se pudo conectar con el servidor.');
                if (boton) boton.disabled = false;
            });
            return;
        }

        // Borrar línea.
        if (form.matches('[data-form-borrar-linea]')) {
            e.preventDefault();
            if (!confirm('¿Borrar esta línea del pedido?')) return;
            var filaBorrar = form.closest('tr');
            var idBorrar = filaBorrar ? filaBorrar.getAttribute('data-linea-id') : null;
            enviar(form).then(function (datos) {
                if (!datos.ok) { alert(datos.mensaje || 'No se pudo borrar.'); return; }
                if (filaBorrar) filaBorrar.remove();
                var formLinea = idBorrar ? document.getElementById('form-linea-' + idBorrar) : null;
                if (formLinea) formLinea.remove();
            }).catch(function () {
                alert('No se pudo conectar con el servidor.');
            });
            return;
        }

        // Ajustar completado (+/-).
        if (form.matches('[data-form-completada]')) {
            e.preventDefault();
            var filaCompletada = form.closest('tr');
            var idCompletada = filaCompletada ? filaCompletada.getAttribute('data-linea-id') : null;
            form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
            enviar(form).then(function (datos) {
                if (!datos.ok) { alert(datos.mensaje || 'No se pudo actualizar.'); return; }
                reemplazarFila(idCompletada, datos.rowHtml);
            }).catch(function () {
                alert('No se pudo conectar con el servidor.');
            });
            return;
        }
    });
});
</script>

<?php // Qué placas se han marcado como salidas de este pedido (a mano, al pulsar
      // "Cargar piezas a la placa" arriba) — solo un listado, sin intentar cuadrar
      // qué cubre cada una: eso se sigue viendo a ojo abriendo la placa. ?>
<?php if (!empty($placas)): ?>
    <h6 class="mt-4 mb-2 d-flex align-items-center gap-2">
        <i class="bi bi-printer"></i> Placas de este pedido
        <span class="badge text-bg-secondary"><?= count($placas) ?></span>
    </h6>
    <ul class="list-group">
        <?php foreach ($placas as $placa): ?>
            <li class="list-group-item d-flex align-items-center gap-2">
                <a href="<?= site_url('piezas/placa/' . (int) $placa['id'] . '/bitacora/editar') ?>" target="_blank" rel="noopener"
                    class="text-decoration-none flex-grow-1"><?= esc($placa['nombre']) ?></a>
                <span class="text-muted small"><?= esc(date('d/m/Y H:i', strtotime($placa['creado_en']))) ?></span>
                <?php if ($placa['impresa_en']): ?>
                    <span class="badge text-bg-success">Impresa</span>
                <?php elseif ($placa['descargada_en']): ?>
                    <span class="badge text-bg-primary">Lista para imprimir</span>
                <?php else: ?>
                    <span class="badge bg-body-secondary text-body-secondary border">Guardada</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?= $this->endSection() ?>
