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
        <i class="bi bi-clock-history"></i> Placas
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

<p class="text-muted small">
    Origen: <?= esc($pedido['origen']) ?> · Creado: <?= esc($pedido['creado_en']) ?>
    <?php if ($pedido['notas']): ?> · Notas: <?= esc($pedido['notas']) ?><?php endif; ?>
</p>

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
    <tbody>
        <?php foreach ($pedido['lineas'] as $linea): ?>
            <?php $completa = (int) $linea['cantidad_completada'] >= (int) $linea['cantidad']; ?>
            <tr class="<?= $completa ? 'fila-completa' : '' ?>">
                <td style="width: 34px;">
                    <?php if ($linea['foto']): ?>
                        <img src="<?= esc($linea['foto'], 'attr') ?>" alt="" loading="lazy" class="rounded border"
                            style="width: 34px; height: 34px; object-fit: contain;">
                    <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center rounded border text-body-tertiary"
                            style="width: 34px; height: 34px;"><i class="bi bi-box" style="font-size: .8rem;"></i></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($linea['nombreVariante']): ?>
                        <?= esc($linea['nombreFamilia']) ?> <span class="text-muted">· <?= esc($linea['nombreVariante']) ?></span>
                    <?php elseif (!empty($linea['descripcion_libre'])): ?>
                        <span class="fst-italic"><?= esc($linea['descripcion_libre']) ?></span>
                        <span class="badge text-bg-info ms-1" title="Aún no existe en el catálogo">futura</span>
                    <?php elseif ($linea['variante_id']): ?>
                        <span class="text-muted small fst-italic">pieza borrada</span>
                    <?php else: ?>
                        <span class="text-muted small fst-italic">sin referencia</span>
                    <?php endif; ?>
                    <?php if ($linea['sku']): ?>
                        <br>
                        <span class="badge border text-body-secondary font-monospace fw-normal"><?= esc($linea['sku']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= (int) $linea['cantidad'] ?></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delta" value="-1">
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Una menos"
                                <?= (int) $linea['cantidad_completada'] <= 0 ? 'disabled' : '' ?>>−</button>
                        </form>
                        <span class="small" style="min-width: 3em; text-align: center;">
                            <?= (int) $linea['cantidad_completada'] ?>/<?= (int) $linea['cantidad'] ?>
                            <?= $completa ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?>
                        </span>
                        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delta" value="1">
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Una más"
                                <?= $completa ? 'disabled' : '' ?>>+</button>
                        </form>
                    </div>
                </td>
                <td class="text-muted small"><?= esc($linea['notas'] ?? '') ?></td>
                <td class="text-nowrap">
                    <?php if ($linea['variante_id']): ?>
                        <a href="<?= site_url('piezas/variante/' . $linea['variante_id']) ?>" class="btn btn-sm btn-outline-primary" title="Ver pieza">
                            <i class="bi bi-eye"></i>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-editar-linea="<?= (int) $linea['id'] ?>" title="Editar línea">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/borrar') ?>" class="d-inline"
                        onsubmit="return confirm('¿Borrar esta línea del pedido?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar línea">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <tr id="editar-linea-<?= (int) $linea['id'] ?>" class="d-none">
                <td></td>
                <td colspan="5">
                    <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/editar') ?>" class="row g-1 align-items-center py-2">
                        <?= csrf_field() ?>
                        <div class="col-md-4 position-relative">
                            <input type="text" class="form-control form-control-sm" data-buscar-variante autocomplete="off"
                                placeholder="Buscar pieza del catálogo…"
                                value="<?= $linea['nombreVariante'] ? esc($linea['nombreFamilia'] . ' · ' . $linea['nombreVariante'], 'attr') : '' ?>">
                            <input type="hidden" name="variante_id" data-variante-id value="<?= (int) ($linea['variante_id'] ?? 0) ?>">
                            <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;" data-resultados-variante></div>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="descripcion_libre" class="form-control form-control-sm"
                                placeholder="…o descripción (pieza futura)" value="<?= esc($linea['descripcion_libre'] ?? '', 'attr') ?>">
                        </div>
                        <div class="col-md-1">
                            <input type="number" name="cantidad" class="form-control form-control-sm" min="1" required
                                value="<?= (int) $linea['cantidad'] ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="notas" class="form-control form-control-sm" maxlength="150"
                                placeholder="Notas" value="<?= esc($linea['notas'] ?? '', 'attr') ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-editar-linea="<?= (int) $linea['id'] ?>">Cancelar</button>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="card card-body mb-3">
    <h6 class="mb-2"><i class="bi bi-plus-lg"></i> Añadir línea</h6>
    <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/linea') ?>" class="row g-1 align-items-center">
        <?= csrf_field() ?>
        <div class="col-md-4 position-relative">
            <input type="text" class="form-control form-control-sm" data-buscar-variante autocomplete="off"
                placeholder="Buscar pieza del catálogo…">
            <input type="hidden" name="variante_id" data-variante-id value="">
            <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;" data-resultados-variante></div>
        </div>
        <div class="col-md-3">
            <input type="text" name="descripcion_libre" class="form-control form-control-sm"
                placeholder="…o descripción (pieza futura, aún sin hacer)">
        </div>
        <div class="col-md-1">
            <input type="number" name="cantidad" class="form-control form-control-sm" min="1" value="1" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="notas" class="form-control form-control-sm" maxlength="150" placeholder="Notas">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-success w-100">Añadir</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-editar-linea]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var fila = document.getElementById('editar-linea-' + boton.getAttribute('data-toggle-editar-linea'));
            if (fila) fila.classList.toggle('d-none');
        });
    });

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
});
</script>

<?php // Qué placas se han marcado como salidas de este pedido (a mano, al pulsar
      // "Cargar piezas a la placa" arriba) — solo un listado, sin intentar cuadrar
      // qué cubre cada una: eso se sigue viendo a ojo abriendo la placa. ?>
<?php if (!empty($placas)): ?>
    <h6 class="mt-4 mb-2 d-flex align-items-center gap-2">
        <i class="bi bi-clock-history"></i> Placas de este pedido
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
