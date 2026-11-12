<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-index.css') ?>?v=6">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-existencias.css') ?>?v=2">
<?php
    $tono = ['cero' => 'e-bad', 'bajo' => 'e-warn', 'ok' => 'e-ok'];

    $buscable = static fn (array $f): string => trim(mb_strtolower(
        $f['nombre'] . ' ' . ($f['variante']['sku'] ?? '')
    ));

    $ojoSterclicks = static function (array $f): string {
        $propia = !empty($f['variante']['visible_sterclicks']);
        [$icono, $color, $titulo] = !$propia
            ? ['bi-eye-slash', 'text-warning', 'Oculta de sterclicks. Pulsa para mostrarla']
            : ($f['visibleSterclicks']
                ? ['bi-eye', 'text-body-tertiary', 'Visible en sterclicks. Pulsa para ocultarla']
                : ['bi-eye-slash', 'text-body-tertiary', 'Oculta por su pieza o categoría. Pulsa para ocultar también esta variante']);

        return '<button type="button" class="ex-ojo ' . $color . '" data-ojo-sterclicks="' . (int) $f['variante']['id'] . '"'
            . ' data-padres="' . (empty($f['padresVisibles']) ? '0' : '1') . '" title="' . esc($titulo, 'attr') . '"><i class="bi ' . $icono . '"></i></button>';
    };

    $url = static fn (array $extra) => site_url('piezas/existencias') . '?' . http_build_query(array_filter(
        array_merge([
            'filtro'     => $filtro,
            'vista'      => $vista,
            'categoria'  => $categoriaSel,
            'sterclicks' => $soloSterclicks ? '' : '0',
        ], $extra),
        static fn ($v) => $v !== '' && $v !== null
    ));

    $filtros = [
        'todas'       => ['Todas', ''],
        'existencias' => ['Con existencias', 'e-ok'],
        'bajo-minimo' => ['Bajo mínimo', 'e-warn'],
        'sin-stock'   => ['Sin stock', 'e-bad'],
    ];
    $donde = static function (int $idVar, array $f) use ($ubicaciones): string {
        $d      = $f['desglose'];
        $partes = [];
        if (!empty($ubicaciones[$idVar])) {
            $partes[] = '<i class="bi bi-geo-alt"></i> <span class="fv-mono">' . esc(implode(', ', $ubicaciones[$idVar])) . '</span>'
                . ($d['impresas'] || $d['pedido'] ? ' ' . (int) $d['hueco'] : '');
        } elseif ($d['hueco'] > 0) {
            $partes[] = '<span class="sin"><i class="bi bi-geo-alt"></i> Sin ubicación ' . (int) $d['hueco'] . '</span>';
        }
        if ($d['impresas']) {
            $partes[] = '<i class="bi bi-box"></i> impresas ' . (int) $d['impresas'];
        }
        if ($d['pedido']) {
            $partes[] = '<i class="bi bi-box"></i> pedido ' . (int) $d['pedido'];
        }

        return '<span class="ex-donde">' . implode(' · ', $partes) . '</span>';
    };
?>

<div class="fv pi ex" id="ex">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Existencias</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/ubicaciones') ?>" class="fv-gbtn" title="Estuches y huecos de guardado"><i class="bi bi-geo-alt"></i> Ubicaciones</a>
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn" title="Histórico de placas"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/existencias?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Existencias</h1>
        <div class="fv-trio ex-cifras">
            <a href="<?= $url(['filtro' => 'todas']) ?>" class="<?= $filtro === 'todas' ? 'on' : '' ?>" title="Ver todas las piezas">
                <span class="k">Unidades</span><span class="v"><?= (int) $totales['unidades'] ?></span>
            </a>
            <a href="<?= $url(['filtro' => 'existencias']) ?>" class="<?= $filtro === 'existencias' ? 'on' : '' ?>" title="Ver solo las piezas con existencias">
                <span class="k">Con stock</span><span class="v e-ok"><?= (int) $totales['conStock'] ?></span>
            </a>
            <a href="<?= $url(['filtro' => 'bajo-minimo']) ?>" class="<?= $filtro === 'bajo-minimo' ? 'on' : '' ?>" title="Ver solo las piezas por debajo de su mínimo">
                <span class="k">Bajo mínimo</span><span class="v <?= $totales['bajo'] > 0 ? 'e-warn' : '' ?>"><?= (int) $totales['bajo'] ?></span>
            </a>
        </div>
    </div>

    <details class="fv-mas ex-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Cómo entran las piezas aquí?</summary>
        <div class="fv-cuerpo">
            <p>Inventario de piezas físicas producidas, por pieza. El alta automática de lo impreso se dispara desde el botón <em>«Dar de alta en inventario»</em> de cada placa (bitácora, sección «Qué llevaba»), que cuenta <b>copias − fallidas</b>.</p>
            <p>El mínimo de cada pieza se fija en su ficha; por debajo aparece en ámbar, a cero en rojo.</p>
        </div>
    </details>

    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>
    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>

    <div class="pi-busca">
        <label class="pi-buscar">
            <i class="bi bi-search"></i>
            <input type="search" id="buscadorExistencias" placeholder="Buscar por nombre o SKU" autocomplete="off" aria-label="Buscar en el inventario">
            <span class="pi-cuenta" id="exCuenta"></span>
        </label>
    </div>

    <div class="pi-toolbar">
        <div class="pi-chips">
            <?php foreach ($filtros as $clave => [$texto, $t]): ?>
                <a href="<?= $url(['filtro' => $clave]) ?>" class="pi-chip<?= $t ? ' fv-estado ' . $t : '' ?>" aria-pressed="<?= $filtro === $clave ? 'true' : 'false' ?>"><span><?= $texto ?></span></a>
            <?php endforeach; ?>
            <span class="fv-vsep"></span>
            <a href="<?= $url(['sterclicks' => $soloSterclicks ? '0' : '1']) ?>" class="pi-chip"
                aria-pressed="<?= $soloSterclicks ? 'true' : 'false' ?>"
                title="Solo las piezas que llegan al catálogo de sterclicks (pieza, familia y categoría visibles)">
                <span><i class="bi <?= $soloSterclicks ? 'bi-eye-fill' : 'bi-eye' ?>"></i> Solo sterclicks</span>
            </a>
        </div>
        <div class="pi-der">
            <select class="fv-input ex-cat" aria-label="Categoría" onchange="location.href = this.value">
                <option value="<?= esc($url(['categoria' => '']), 'attr') ?>" <?= $categoriaSel === '' ? 'selected' : '' ?>>Todas las categorías</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= esc($url(['categoria' => (string) $c['id']]), 'attr') ?>" <?= $categoriaSel === (string) $c['id'] ? 'selected' : '' ?>><?= esc($c['nombre']) ?></option>
                <?php endforeach; ?>
                <option value="<?= esc($url(['categoria' => 'sin']), 'attr') ?>" <?= $categoriaSel === 'sin' ? 'selected' : '' ?>>Sin categoría</option>
            </select>
            <div class="pi-vistas ex-vistas" role="group" aria-label="Vista">
                <a href="<?= $url(['vista' => 'galeria']) ?>" aria-pressed="<?= $vista === 'galeria' ? 'true' : 'false' ?>" title="Galería"><i class="bi bi-grid-3x3-gap"></i></a>
                <a href="<?= $url(['vista' => 'lista']) ?>" aria-pressed="<?= $vista === 'lista' ? 'true' : 'false' ?>" title="Lista"><i class="bi bi-list-ul"></i></a>
            </div>
        </div>
    </div>

    <?php if (empty($filas)): ?>
        <p class="fv-vacio">
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
        <div class="ex-galeria">
            <?php foreach ($filas as $f): ?>
                <?php $idVar = (int) $f['variante']['id']; ?>
                <div class="ex-card ex-<?= $f['estado'] ?>" data-buscar="<?= esc($buscable($f), 'attr') ?>">
                    <a href="<?= site_url('piezas/existencias/' . $idVar) ?>" class="ex-card-a">
                        <span class="ex-img">
                            <i class="bi bi-image"></i>
                            <?php if (!empty($imagenes[$idVar])): ?>
                                <img src="<?= $imagenes[$idVar]['t'] ?>" alt="<?= esc($f['nombre'], 'attr') ?>" loading="lazy" onerror="this.remove()">
                            <?php endif; ?>
                        </span>
                        <span class="ex-card-b">
                            <span class="ex-nom"><?= esc($f['nombre']) ?></span>
                            <?php if (!empty($f['variante']['sku'])): ?><span class="ex-sku fv-mono"><?= esc($f['variante']['sku']) ?></span><?php endif; ?>
                            <span class="ex-stock">
                                <span class="fv-estado <?= $tono[$f['estado']] ?>"><b><?= (int) $f['stock'] ?></b></span>
                                <?php if ($f['minimo'] > 0): ?><span class="ex-min">mín <?= (int) $f['minimo'] ?></span><?php endif; ?>
                            </span>
                            <?= $donde($idVar, $f) ?>
                        </span>
                    </a>
                    <?= $ojoSterclicks($f) ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="ex-lista">
            <div class="ex-cab"><span></span><span>Pieza</span><span>Dónde</span><span class="d">Stock</span><span></span></div>
            <?php foreach ($filas as $f): ?>
                <?php $idVar = (int) $f['variante']['id']; ?>
                <div class="ex-fila ex-<?= $f['estado'] ?>" data-buscar="<?= esc($buscable($f), 'attr') ?>">
                    <a href="<?= site_url('piezas/existencias/' . $idVar) ?>" class="ex-fila-a" title="Ver movimientos y dar de alta / baja">
                        <span class="ex-img"><i class="bi bi-image"></i><?php if (!empty($imagenes[$idVar])): ?><img src="<?= $imagenes[$idVar]['t'] ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></span>
                        <span class="ex-txt">
                            <span class="ex-nom"><?= esc($f['nombre']) ?></span>
                            <?php if (!empty($f['variante']['sku'])): ?><span class="ex-sku fv-mono"><?= esc($f['variante']['sku']) ?></span><?php endif; ?>
                        </span>
                        <?= $donde($idVar, $f) ?>
                        <span class="ex-stock d">
                            <span class="fv-estado <?= $tono[$f['estado']] ?>"><b><?= (int) $f['stock'] ?></b></span>
                            <?php if ($f['minimo'] > 0): ?><span class="ex-min">/ <?= (int) $f['minimo'] ?></span><?php endif; ?>
                        </span>
                    </a>
                    <?= $ojoSterclicks($f) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="fv-vacio d-none" id="sinResultadosBuscador">Ninguna pieza coincide con la búsqueda.</p>
</div>

<script>
(function () {
    var input = document.getElementById('buscadorExistencias');
    if (!input) return;
    var items = document.querySelectorAll('[data-buscar]');
    var vacio = document.getElementById('sinResultadosBuscador');
    var cuenta = document.getElementById('exCuenta');

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
        if (cuenta) cuenta.innerHTML = terminos.length ? '<b>' + visibles + '</b> de ' + items.length : '<b>' + items.length + '</b> piezas';
    }

    input.addEventListener('input', aplicar);
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && !e.target.closest('input, textarea, select')) { e.preventDefault(); input.focus(); }
    });
    aplicar();
})();
</script>

<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-ojo-sterclicks]');
    if (!b) return;
    e.preventDefault();
    e.stopPropagation();
    if (b.disabled) return;
    b.disabled = true;
    var datos = new FormData();
    datos.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    fetch('<?= site_url('piezas/variante') ?>/' + b.dataset.ojoSterclicks + '/visibilidad', {
        method: 'POST', body: datos, headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(function (r) { return r.json(); }).then(function (r) {
        if (!r.ok) { alert(r.mensaje || 'No se pudo cambiar.'); return; }
        var padres = b.dataset.padres !== '0';
        var icono = b.querySelector('i');
        b.classList.remove('text-warning', 'text-body-tertiary');
        if (!r.visible) {
            icono.className = 'bi bi-eye-slash';
            b.classList.add('text-warning');
            b.title = 'Oculta de sterclicks. Pulsa para mostrarla';
        } else {
            icono.className = padres ? 'bi bi-eye' : 'bi bi-eye-slash';
            b.classList.add('text-body-tertiary');
            b.title = padres ? 'Visible en sterclicks. Pulsa para ocultarla' : 'Oculta por su pieza o categoría. Pulsa para ocultar también esta variante';
        }
    }).catch(function () { alert('No se pudo cambiar la visibilidad.'); })
      .finally(function () { b.disabled = false; });
}, true);
</script>

<?= $this->endSection() ?>
