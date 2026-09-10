<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    // Semáforo por variante: rojo a cero, amarillo entre 1 y el mínimo,
    // verde por encima. Mismas clases en lista y en galería.
    $claseBadge = ['cero' => 'text-bg-danger', 'bajo' => 'text-bg-warning', 'ok' => 'text-bg-success'];
    $claseTexto = ['cero' => 'text-danger', 'bajo' => 'text-warning', 'ok' => 'text-success'];

    // Texto contra el que busca el buscador de cada fila: nombre completo
    // (familia + variante) y SKU, en minúsculas.
    $buscable = static fn (array $f): string => trim(mb_strtolower(
        $f['nombre'] . ' ' . ($f['variante']['sku'] ?? '')
    ));

    // Marca de visibilidad en sterclicks, mismo criterio que el ojo del
    // índice: ojo tenue si la pieza llega al catálogo, ojo tachado en ámbar
    // si algún nivel (pieza, familia o categoría) la deja fuera.
    $ojoSterclicks = static fn (array $f): string => $f['visibleSterclicks']
        ? '<i class="bi bi-eye text-body-tertiary" title="Visible en sterclicks"></i>'
        : '<i class="bi bi-eye-slash text-warning" title="Oculta de sterclicks"></i>';

    // Enlaces de filtro / vista / categoría / sterclicks que conservan los
    // demás parámetros (todos los ejes se combinan).
    $url = static fn (array $extra) => site_url('piezas/existencias') . '?' . http_build_query(array_filter(
        array_merge([
            'filtro'     => $filtro,
            'vista'      => $vista,
            'categoria'  => $categoriaSel,
            // Encendido por defecto: solo se arrastra en la URL cuando está
            // apagado (sterclicks=0).
            'sterclicks' => $soloSterclicks ? '' : '0',
        ], $extra),
        static fn ($v) => $v !== '' && $v !== null
    ));
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-boxes text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Existencias</strong>

    <a href="<?= $url(['filtro' => 'todas']) ?>"
        class="badge rounded-pill text-bg-primary text-decoration-none <?= $filtro === 'todas' ? 'border border-2 border-light' : '' ?>"
        title="Ver todas las piezas">
        <?= (int) $totales['unidades'] ?> uds
    </a>
    <a href="<?= $url(['filtro' => 'existencias']) ?>"
        class="badge rounded-pill text-bg-success text-decoration-none <?= $filtro === 'existencias' ? 'border border-2 border-light' : '' ?>"
        title="Ver solo las piezas con existencias">
        <?= (int) $totales['conStock'] ?> con stock
    </a>
    <?php if ($totales['bajo'] > 0): ?>
        <a href="<?= $url(['filtro' => 'bajo-minimo']) ?>"
            class="badge rounded-pill text-bg-warning text-decoration-none <?= $filtro === 'bajo-minimo' ? 'border border-2 border-light' : '' ?>"
            title="Ver solo las piezas por debajo de su mínimo">
            <?= (int) $totales['bajo'] ?> bajo mínimo
        </a>
    <?php endif; ?>

    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Histórico de placas">
        <i class="bi bi-printer"></i> Placas
    </a>
</h5>

<p class="text-muted small">
    Inventario de piezas físicas producidas, por pieza. El alta automática de lo impreso se
    dispara desde el botón <em>«Dar de alta en inventario»</em> de cada placa (bitácora, sección
    «Qué llevaba»), que cuenta <strong>copias − fallidas</strong>. El mínimo de cada pieza se
    fija en su ficha; por debajo aparece en amarillo, a cero en rojo.
</p>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3 py-2 sticky-top bg-body border-bottom" style="z-index: 3;">
    <div class="btn-group btn-group-sm" role="group" aria-label="Vista">
        <a href="<?= $url(['vista' => 'galeria']) ?>"
            class="btn btn-outline-secondary <?= $vista === 'galeria' ? 'active' : '' ?>" title="Galería">
            <i class="bi bi-grid-3x3-gap"></i>
        </a>
        <a href="<?= $url(['vista' => 'lista']) ?>"
            class="btn btn-outline-secondary <?= $vista === 'lista' ? 'active' : '' ?>" title="Lista">
            <i class="bi bi-list-ul"></i>
        </a>
    </div>

    <div class="btn-group btn-group-sm" role="group" aria-label="Filtro">
        <a href="<?= $url(['filtro' => 'existencias']) ?>"
            class="btn btn-outline-secondary <?= $filtro === 'existencias' ? 'active' : '' ?>">Con existencias</a>
        <a href="<?= $url(['filtro' => 'bajo-minimo']) ?>"
            class="btn btn-outline-secondary <?= $filtro === 'bajo-minimo' ? 'active' : '' ?>"
            title="Por debajo del mínimo fijado, incluidas las que están a cero">Bajo mínimo</a>
        <a href="<?= $url(['filtro' => 'sin-stock']) ?>"
            class="btn btn-outline-secondary <?= $filtro === 'sin-stock' ? 'active' : '' ?>">Sin stock</a>
        <a href="<?= $url(['filtro' => 'todas']) ?>"
            class="btn btn-outline-secondary <?= $filtro === 'todas' ? 'active' : '' ?>">Todas</a>
    </div>

    <?php // Eje transversal, no un filtro de stock: acota cualquiera de los
          // de arriba a lo que de verdad llega al catálogo de sterclicks
          // (pieza + familia + categoría visibles) — lo que hay que llevar
          // controlado. El resto sigue en la lista, con el ojo tachado. ?>
    <a href="<?= $url(['sterclicks' => $soloSterclicks ? '0' : '1']) ?>"
        class="btn btn-sm <?= $soloSterclicks ? 'btn-info' : 'btn-outline-secondary' ?>"
        title="Solo las piezas que llegan al catálogo de sterclicks (pieza, familia y categoría visibles)"
        aria-pressed="<?= $soloSterclicks ? 'true' : 'false' ?>">
        <i class="bi <?= $soloSterclicks ? 'bi-eye-fill' : 'bi-eye' ?>"></i> Solo sterclicks
    </a>

    <select class="form-select form-select-sm" style="width: auto;" aria-label="Categoría"
        onchange="location.href = this.value">
        <option value="<?= esc($url(['categoria' => '']), 'attr') ?>" <?= $categoriaSel === '' ? 'selected' : '' ?>>
            Todas las categorías
        </option>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= esc($url(['categoria' => (string) $c['id']]), 'attr') ?>"
                <?= $categoriaSel === (string) $c['id'] ? 'selected' : '' ?>>
                <?= esc($c['nombre']) ?>
            </option>
        <?php endforeach; ?>
        <option value="<?= esc($url(['categoria' => 'sin']), 'attr') ?>" <?= $categoriaSel === 'sin' ? 'selected' : '' ?>>
            Sin categoría
        </option>
    </select>

    <?php // Buscador en cliente: recorta lo ya pintado por nombre o SKU. Vive
          // en esta barra, que es sticky, así que no se pierde al bajar. ?>
    <input type="search" id="buscadorExistencias" class="form-control form-control-sm"
        style="width: auto; flex: 1 1 12rem; min-width: 10rem;"
        placeholder="Buscar por nombre o SKU..." autocomplete="off" aria-label="Buscar en el inventario">
</div>

<?php if (empty($filas)): ?>
    <p class="text-muted">
        <?php if ($soloSterclicks): ?>
            Ninguna pieza visible en sterclicks encaja con este filtro.
        <?php else: ?>
            <?= match ($filtro) {
                'existencias' => 'Ninguna pieza tiene existencias todavía. Da de alta desde una placa o a mano.',
                'bajo-minimo' => 'Ninguna pieza está por debajo de su mínimo.',
                'sin-stock'   => 'Todas las piezas tienen existencias.',
                default       => 'No hay piezas.',
            } ?>
        <?php endif; ?>
    </p>

<?php elseif ($vista === 'galeria'): ?>
    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-2">
        <?php foreach ($filas as $f): ?>
            <?php $idVar = (int) $f['variante']['id']; ?>
            <div class="col" data-buscar="<?= esc($buscable($f), 'attr') ?>">
                <a href="<?= site_url('piezas/existencias/' . $idVar) ?>"
                    class="card h-100 text-decoration-none text-body">
                    <div class="card-img-top d-flex align-items-center justify-content-center bg-body-secondary text-muted position-relative"
                        style="aspect-ratio: 1; overflow: hidden;">
                        <i class="bi bi-image" style="font-size: 1.6rem;"></i>
                        <?php if (!empty($imagenes[$idVar])): ?>
                            <img src="<?= $imagenes[$idVar]['t'] ?>" alt="<?= esc($f['nombre'], 'attr') ?>" loading="lazy"
                                class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover;"
                                onerror="this.remove()">
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-2">
                        <span class="float-end"><?= $ojoSterclicks($f) ?></span>
                        <div class="small fw-semibold lh-sm mb-1"><?= esc($f['nombre']) ?></div>
                        <?php if (!empty($f['variante']['sku'])): ?>
                            <div class="text-muted mb-1" style="font-size: .72rem;"><?= esc($f['variante']['sku']) ?></div>
                        <?php endif; ?>
                        <span class="badge <?= $claseBadge[$f['estado']] ?>"><?= (int) $f['stock'] ?></span>
                        <?php if ($f['minimo'] > 0): ?>
                            <span class="text-muted" style="font-size: .72rem;">/ mín <?= (int) $f['minimo'] ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="table-responsive" style="max-width: 32rem;">
        <table class="table table-sm align-middle" style="font-size: .85rem;">
            <thead>
                <tr class="text-muted">
                    <th class="text-center" style="width: 3.5rem;">Stock</th>
                    <th>Pieza</th>
                    <th style="width: 2rem;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $f): ?>
                    <?php $idVar = (int) $f['variante']['id']; ?>
                    <tr data-buscar="<?= esc($buscable($f), 'attr') ?>">
                        <td class="text-center">
                            <span class="badge <?= $claseBadge[$f['estado']] ?>" style="min-width: 2.6ch;"><?= (int) $f['stock'] ?></span>
                        </td>
                        <td>
                            <a href="<?= site_url('piezas/existencias/' . $idVar) ?>" class="text-decoration-none fw-semibold">
                                <?= esc($f['nombre']) ?>
                            </a>
                            <?php if ($f['minimo'] > 0): ?>
                                <span class="text-muted" style="font-size: .72rem;">· mín <?= (int) $f['minimo'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($f['variante']['sku'])): ?>
                                <span class="text-muted d-block" style="font-size: .72rem;"><?= esc($f['variante']['sku']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <span class="me-2"><?= $ojoSterclicks($f) ?></span>
                            <a href="<?= site_url('piezas/existencias/' . $idVar) ?>"
                                class="btn btn-sm btn-outline-secondary py-0 px-1" title="Ver movimientos y dar de alta / baja">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<p class="text-muted d-none" id="sinResultadosBuscador">Ninguna pieza coincide con la búsqueda.</p>

<script>
(function () {
    var input = document.getElementById('buscadorExistencias');
    if (!input) return;

    var items = document.querySelectorAll('[data-buscar]');
    var vacio = document.getElementById('sinResultadosBuscador');

    function aplicar() {
        var terminos = input.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
        var visibles = 0;

        items.forEach(function (el) {
            var texto = el.getAttribute('data-buscar') || '';
            var ok = terminos.every(function (t) { return texto.indexOf(t) !== -1; });
            el.classList.toggle('d-none', !ok);
            if (ok) visibles++;
        });

        if (vacio) vacio.classList.toggle('d-none', visibles > 0 || items.length === 0);
    }

    input.addEventListener('input', aplicar);
    aplicar();
})();
</script>

<?= $this->endSection() ?>
