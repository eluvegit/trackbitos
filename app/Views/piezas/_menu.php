<?php
$ruta    = uri_string();
$sub     = str_starts_with($ruta, 'piezas/') ? substr($ruta, 7) : '';
$enIndex = $ruta === 'piezas';
$modos   = ['clasica' => 'Clásica', 'a' => 'A · Barra pulida', 'b' => 'B · Áreas', 'c' => 'C · Riel lateral', 'd' => 'D · Mínima con buscador'];
$modo    = $_COOKIE['pz_menu'] ?? 'clasica';
$modo    = isset($modos[$modo]) ? $modo : 'clasica';
$plegado = ($_COOKIE['pz_riel'] ?? '') === '1';
$carritoCount ??= count(array_unique((array) session('piezas_carrito')));
$url = static fn (string $r): string => $r === '@tpv' ? site_url('inventario') : site_url('piezas' . ($r === '' ? '' : '/' . $r));

$areas = [
    'diseno' => ['n' => 'Diseño', 'c' => '#f59f00', 'i' => 'bi-pencil', 'extra' => ['variante', 'familia'], 'items' => [
        ['n' => 'Pendientes de crear', 'r' => 'pendientes', 'i' => 'bi-list-check', 'ojo' => '#modalPendientes'],
        ['n' => 'Revisar', 'r' => 'revisar', 'i' => 'bi-clipboard-check'],
    ]],
    'impresion' => ['n' => 'Impresión', 'c' => '#3d8bfd', 'i' => 'bi-printer', 'extra' => ['placa'], 'items' => [
        ['n' => 'Galería', 'r' => 'galeria', 'i' => 'bi-grid-3x3-gap', 'carrito' => true],
        ['n' => 'Placas', 'r' => 'placas', 'i' => 'bi-printer'],
        ['n' => 'Cuaderno', 'r' => 'cuaderno', 'i' => 'bi-journal-bookmark'],
        ['n' => 'Tiradas', 'r' => 'tiradas', 'i' => 'bi-calendar-range'],
    ]],
    'inventario' => ['n' => 'Inventario', 'c' => '#20c997', 'i' => 'bi-boxes', 'extra' => [], 'items' => [
        ['n' => 'Existencias', 'r' => 'existencias', 'i' => 'bi-boxes'],
        ['n' => 'Ubicaciones', 'r' => 'ubicaciones', 'i' => 'bi-geo-alt'],
        ['n' => 'Cajas', 'r' => 'ubicaciones/cajas', 'i' => 'bi-box-seam'],
        ['n' => 'Albaranes', 'r' => 'salidas', 'i' => 'bi-receipt'],
    ]],
    'pedidos' => ['n' => 'Pedidos', 'c' => '#a370f7', 'i' => 'bi-cart-check', 'extra' => [], 'items' => [
        ['n' => 'Pedidos', 'r' => 'pedidos', 'i' => 'bi-cart-check', 'ojo' => '#modalUltimoPedido'],
    ]],
    'tpv' => ['n' => 'TPV', 'c' => '#ef5b5b', 'i' => 'bi-shop', 'extra' => [], 'items' => [
        ['n' => 'TPV', 'r' => '@tpv', 'i' => 'bi-shop', 'fuera' => true],
        ['n' => 'Guías del TPV', 'r' => 'guias', 'i' => 'bi-signpost-split'],
    ]],
];

$activoRuta = null;
$activoArea = null;
foreach ($areas as $ak => $a) {
    foreach ($a['items'] as $it) {
        if ($it['r'][0] !== '@' && ($sub === $it['r'] || str_starts_with($sub, $it['r'] . '/'))
            && ($activoRuta === null || strlen($it['r']) > strlen($activoRuta))) {
            $activoRuta = $it['r'];
            $activoArea = $ak;
        }
    }
}
if ($activoArea === null) {
    foreach ($areas as $ak => $a) {
        foreach ($a['extra'] as $pre) {
            if ($sub === $pre || str_starts_with($sub, $pre . '/')) {
                $activoArea = $ak;
            }
        }
    }
}

$puedeOrganizar = !$enIndex || !empty($familias);
$mas = [
    'Herramientas' => array_values(array_filter([
        ['n' => 'Ver pendientes de crear', 'm' => '#modalPendientes', 'i' => 'bi-eye'],
        ['n' => 'Ver el último pedido', 'm' => '#modalUltimoPedido', 'i' => 'bi-eye'],
        ['n' => 'Calcular tiempo de placa', 'm' => '#modalCalcTiempo', 'i' => 'bi-stopwatch'],
        $puedeOrganizar ? ['n' => 'Organizar el índice', 'org' => true, 'i' => 'bi-arrows-move'] : null,
        ['n' => 'Categorías', 'm' => '#modalCategorias', 'i' => 'bi-folder'],
    ])),
    'Taller' => [
        ['n' => 'Máquinas', 'r' => 'maquinas', 'i' => 'bi-pc-display'],
        ['n' => 'Resinas', 'r' => 'resinas', 'i' => 'bi-droplet-half'],
        ['n' => 'Precio de la resina', 'm' => '#modalResina', 'i' => 'bi-droplet'],
        ['n' => 'Pautas de promoción', 'm' => '#modalPautas', 'i' => 'bi-check2-square'],
    ],
    'Archivo' => [
        ['n' => 'Estadísticas', 'r' => 'estadisticas', 'i' => 'bi-bar-chart-line'],
        ['n' => 'Copia de seguridad', 'r' => 'estadisticas/backup', 'i' => 'bi-download', 'titulo' => 'El .blend de referencia de cada pieza más su historial en texto. Sin STL ni fotos.'],
        ['n' => 'Nevera', 'r' => 'nevera', 'i' => 'bi-snow'],
        ['n' => 'Papelera', 'r' => 'papelera', 'i' => 'bi-trash'],
        ['n' => 'Ayuda', 'r' => 'ayuda', 'i' => 'bi-question-circle'],
    ],
];

$esActivo = static fn (array $it): bool => $it['r'] === $activoRuta;
$badge = static fn (array $it): string => !empty($it['carrito']) && $carritoCount > 0
    ? '<span class="pzm-badge">' . (int) $carritoCount . '</span>' : '';
$enlace = static function (array $it) use ($url, $esActivo): string {
    return 'href="' . esc($url($it['r']), 'attr') . '"' . (!empty($it['fuera']) ? ' target="_blank"' : '')
        . ($esActivo($it) ? ' aria-current="page"' : '');
};

$masLista = static function (bool $conId, string $clase = 'dropdown-menu-end') use ($mas, $url, $modos, $modo): string {
    $h = '<ul class="dropdown-menu ' . $clase . ' pzm-mas">';
    foreach ($mas as $seccion => $items) {
        $h .= '<li><h6 class="dropdown-header">' . esc($seccion) . '</h6></li>';
        foreach ($items as $it) {
            $ico = '<i class="bi ' . $it['i'] . '"></i> ';
            if (!empty($it['org'])) {
                $h .= $conId
                    ? '<li><button type="button" class="dropdown-item" id="btnOrganizar" data-pz-accion="org">' . $ico . esc($it['n']) . '</button></li>'
                    : '<li><a class="dropdown-item" href="' . site_url('piezas') . '#organizar">' . $ico . esc($it['n']) . '</a></li>';
            } elseif (!empty($it['m'])) {
                $h .= '<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="' . $it['m'] . '">' . $ico . esc($it['n']) . '</button></li>';
            } else {
                $h .= '<li><a class="dropdown-item" href="' . esc($url($it['r']), 'attr') . '"' . (!empty($it['titulo']) ? ' title="' . esc($it['titulo'], 'attr') . '"' : '') . '>' . $ico . esc($it['n']) . '</a></li>';
            }
        }
    }
    $h .= '<li><h6 class="dropdown-header">Estilo del menú</h6></li>';
    foreach ($modos as $clave => $nombre) {
        $h .= '<li><button type="button" class="dropdown-item" data-pz-menu="' . $clave . '"' . ($clave === $modo ? ' aria-current="true"' : '') . '>'
            . '<i class="bi ' . ($clave === $modo ? 'bi-check2' : 'bi-dot') . '"></i> ' . esc($nombre) . '</button></li>';
    }
    return $h . '</ul>';
};

$botonMas = static fn (bool $conId, string $clase = 'pzm-ibtn', string $menu = 'dropdown-menu-end', string $extra = ''): string =>
    '<div class="dropdown' . $extra . '"><button type="button" class="' . $clase . '" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Más" title="Más"><i class="bi bi-three-dots"></i></button>' . $masLista($conId, $menu) . '</div>';
$botonNueva = '<button type="button" class="pzm-nueva" data-bs-toggle="modal" data-bs-target="#modalFamilia" title="Pieza nueva"><i class="bi bi-plus-lg"></i><span>Pieza nueva</span></button>';
$botonBuscar = static fn (string $clase = ''): string =>
    '<button type="button" class="pzm-buscar ' . $clase . '" data-pz-buscar><i class="bi bi-search"></i><span class="t">Ir a, buscar pieza…</span><kbd>Ctrl K</kbd></button>';
$casa = '<a href="' . site_url('piezas') . '" class="pzm-casa"' . ($enIndex ? ' aria-current="page"' : '') . '><i class="bi bi-box"></i><span>Piezas</span></a>';

$pintarB = static function (bool $conId, string $clase) use ($areas, $activoArea, $enlace, $badge, $url, $botonMas, $botonNueva, $botonBuscar, $casa, $esActivo): string {
    $h = '<nav class="pzm pzm-b ' . $clase . '" aria-label="Piezas"><div class="pzm-b-fila">' . $casa . '<div class="pzm-b-areas">';
    foreach ($areas as $ak => $a) {
        $h .= '<a href="' . esc($url($a['items'][0]['r']), 'attr') . '"' . (!empty($a['items'][0]['fuera']) ? ' target="_blank"' : '')
            . ' class="pzm-area' . ($ak === $activoArea ? ' on' : '') . '" style="--pzm-c: ' . $a['c'] . '">'
            . '<i class="bi ' . $a['i'] . '"></i>' . esc($a['n']) . '</a>';
    }
    $h .= '</div><div class="pzm-der">' . $botonBuscar('compacto')
        . '<a href="' . site_url('piezas/ayuda') . '" class="pzm-ibtn" title="Ayuda" aria-label="Ayuda"><i class="bi bi-question-circle"></i></a>'
        . $botonMas($conId) . $botonNueva . '</div></div>';
    if ($activoArea !== null && count($areas[$activoArea]['items']) > 1) {
        $h .= '<div class="pzm-b-sub">';
        foreach ($areas[$activoArea]['items'] as $it) {
            $h .= '<a ' . $enlace($it) . ' class="pzm-subitem' . ($esActivo($it) ? ' on' : '') . '"><i class="bi ' . $it['i'] . '"></i>' . esc($it['n']) . $badge($it) . '</a>';
        }
        $h .= '</div>';
    }
    return $h . '</nav>';
};
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-menu.css') ?>?v=3">

<?php if ($modo === 'clasica'): ?>
    <div class="d-flex align-items-center gap-2 flex-wrap mb-3 pzm-escritorio">
        <a href="<?= site_url('piezas') ?>" class="btn btn-sm btn-outline-secondary" title="Piezas"><i class="bi bi-box"></i></a>
        <?= $this->include('piezas/_barra') ?>
    </div>
<?php elseif ($modo === 'a'): ?>
    <nav class="pzm pzm-a pzm-escritorio" aria-label="Piezas">
        <?= $casa ?>
        <div class="pzm-a-grupos">
            <?php foreach ($areas as $a): ?>
                <div class="pzm-a-grupo" style="--pzm-c: <?= $a['c'] ?>">
                    <span class="pzm-a-et"><i></i><?= esc($a['n']) ?></span>
                    <div class="pzm-a-btns">
                        <?php foreach ($a['items'] as $it): ?>
                            <a <?= $enlace($it) ?> class="pzm-a-btn<?= $esActivo($it) ? ' on' : '' ?>" title="<?= esc($it['n'], 'attr') ?>" aria-label="<?= esc($it['n'], 'attr') ?>"><i class="bi <?= $it['i'] ?>"></i><?= $badge($it) ?></a>
                            <?php if (!empty($it['ojo'])): ?>
                                <button type="button" class="pzm-a-ojo" data-bs-toggle="modal" data-bs-target="<?= $it['ojo'] ?>" title="Vistazo rápido" aria-label="Vistazo rápido a <?= esc($it['n'], 'attr') ?>"><i class="bi bi-chevron-down"></i></button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="pzm-a-grupo" style="--pzm-c: #0dcaf0">
                <span class="pzm-a-et"><i></i>Ayuda</span>
                <div class="pzm-a-btns"><a href="<?= site_url('piezas/ayuda') ?>" class="pzm-a-btn<?= $sub === 'ayuda' ? ' on' : '' ?>" title="Ayuda" aria-label="Ayuda"><i class="bi bi-question-circle"></i></a></div>
            </div>
        </div>
        <button type="button" class="pzm-ibtn" data-pz-buscar title="Buscar (Ctrl K)" aria-label="Buscar"><i class="bi bi-search"></i></button>
        <?= $botonMas(true, 'pzm-ibtn pzm-ibtn-borde') ?>
        <?= $botonNueva ?>
    </nav>
<?php elseif ($modo === 'b'): ?>
    <?= $pintarB(true, '') ?>
<?php elseif ($modo === 'c'): ?>
    <aside class="pzm pzm-c pzm-escritorio<?= $plegado ? ' plegado' : '' ?>" aria-label="Piezas">
        <div class="pzm-c-cab">
            <?= $casa ?>
            <button type="button" class="pzm-nueva solo" data-bs-toggle="modal" data-bs-target="#modalFamilia" title="Pieza nueva" aria-label="Pieza nueva"><i class="bi bi-plus-lg"></i></button>
        </div>
        <?= $botonBuscar() ?>
        <div class="pzm-c-secs">
            <?php foreach ($areas as $a): ?>
                <div class="pzm-c-sec" style="--pzm-c: <?= $a['c'] ?>">
                    <span class="pzm-c-et"><i></i><span><?= esc($a['n']) ?></span></span>
                    <?php foreach ($a['items'] as $it): ?>
                        <a <?= $enlace($it) ?> class="pzm-c-item<?= $esActivo($it) ? ' on' : '' ?>" title="<?= esc($it['n'], 'attr') ?>"><i class="bi <?= $it['i'] ?>"></i><span class="t"><?= esc($it['n']) ?></span><?= $badge($it) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="pzm-c-pie">
            <button type="button" class="pzm-ibtn" data-bs-toggle="modal" data-bs-target="#modalCalcTiempo" title="Calcular tiempo de placa" aria-label="Calcular tiempo de placa"><i class="bi bi-stopwatch"></i></button>
            <?= $botonMas(true, 'pzm-ibtn', 'dropdown-menu-start', ' dropup') ?>
            <a href="<?= site_url('piezas/ayuda') ?>" class="pzm-ibtn" title="Ayuda" aria-label="Ayuda"><i class="bi bi-question-circle"></i></a>
            <button type="button" class="pzm-ibtn" data-pz-plegar title="Plegar o desplegar el menú" aria-label="Plegar o desplegar el menú"><i class="bi bi-layout-sidebar"></i></button>
        </div>
    </aside>
<?php else: ?>
    <nav class="pzm pzm-d pzm-escritorio" aria-label="Piezas">
        <?= $casa ?>
        <?= $botonBuscar('ancho') ?>
        <div class="pzm-der">
            <?php foreach ([['diseno', 0], ['impresion', 0], ['impresion', 1], ['inventario', 0], ['pedidos', 0]] as [$ak, $n]): ?>
                <?php $it = $areas[$ak]['items'][$n]; ?>
                <a <?= $enlace($it) ?> class="pzm-a-btn<?= $esActivo($it) ? ' on' : '' ?>" style="--pzm-c: <?= $areas[$ak]['c'] ?>" title="<?= esc($it['n'], 'attr') ?>" aria-label="<?= esc($it['n'], 'attr') ?>"><i class="bi <?= $it['i'] ?>"></i><?= $badge($it) ?></a>
            <?php endforeach; ?>
            <span class="pzm-sep"></span>
            <?= $botonMas(true) ?>
            <?= $botonNueva ?>
        </div>
    </nav>
<?php endif; ?>

<?php if ($modo !== 'b'): ?>
    <?= $pintarB(false, 'pzm-movil') ?>
<?php endif; ?>

<div class="pzm-paleta" id="pzPaleta" hidden>
    <div class="pzm-paleta-caja" role="dialog" aria-modal="true" aria-label="Buscar">
        <div class="pzm-paleta-q">
            <i class="bi bi-search"></i>
            <input type="text" id="pzPaletaQ" placeholder="Ir a una página, buscar una pieza o hacer algo…" autocomplete="off" aria-label="Buscar">
            <kbd>Esc</kbd>
        </div>
        <div class="pzm-paleta-res" id="pzPaletaRes"></div>
        <div class="pzm-paleta-pie"><span><kbd>↑</kbd><kbd>↓</kbd> moverse</span><span><kbd>Intro</kbd> abrir</span><span><kbd>Esc</kbd> cerrar</span></div>
    </div>
</div>

<script>
(function () {
    if (window.pzMenuListo) return;
    window.pzMenuListo = true;

    var poner = function (nombre, valor) { document.cookie = nombre + '=' + valor + '; path=/; max-age=31536000; samesite=lax'; };
    document.addEventListener('click', function (e) {
        var m = e.target.closest('[data-pz-menu]');
        if (m) { poner('pz_menu', m.getAttribute('data-pz-menu')); location.reload(); return; }
        var p = e.target.closest('[data-pz-plegar]');
        if (p) {
            var riel = p.closest('.pzm-c');
            var on = riel.classList.toggle('plegado');
            poner('pz_riel', on ? '1' : '0');
            return;
        }
        if (e.target.closest('[data-pz-buscar]')) { abrir(); }
    });

    var ESTATICOS = <?= json_encode(array_merge(
        array_merge(...array_map(static fn (array $a) => array_map(static fn (array $it) => [
            'g' => 'Ir a', 'n' => $it['n'], 'd' => $a['n'], 'i' => $it['i'], 'c' => $a['c'], 'u' => $url($it['r']), 'fuera' => !empty($it['fuera']),
        ], $a['items']), array_values($areas))),
        [['g' => 'Hacer', 'n' => 'Pieza nueva', 'd' => '', 'i' => 'bi-plus-lg', 'c' => '#ff6a3d', 'm' => '#modalFamilia']],
        array_merge(...array_map(static fn (string $s, array $items) => array_map(static fn (array $it) => array_filter([
            'g' => !empty($it['r']) ? 'Ir a' : 'Hacer', 'n' => $it['n'], 'd' => $s, 'i' => $it['i'], 'c' => '#adb5bd',
            'u' => !empty($it['r']) ? $url($it['r']) : null, 'm' => $it['m'] ?? null, 'org' => !empty($it['org']),
        ], static fn ($v) => $v !== null && $v !== false), $items), array_keys($mas), array_values($mas)))
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var URL_BUSCAR = '<?= site_url('piezas/buscar') ?>';
    var URL_INDICE = '<?= site_url('piezas') ?>';

    var caja = document.getElementById('pzPaleta'), q = document.getElementById('pzPaletaQ'), res = document.getElementById('pzPaletaRes');
    var lista = [], sel = 0, espera = null, ultima = '';

    function plano(s) { return String(s).normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase(); }
    function abrir() {
        caja.hidden = false;
        q.value = '';
        pintar([]);
        q.focus();
    }
    function cerrar() { caja.hidden = true; }

    function pintar(piezas) {
        var t = plano(q.value.trim());
        var est = ESTATICOS.filter(function (x) { return !t || plano(x.n + ' ' + x.d).indexOf(t) >= 0; });
        if (!t) est = est.filter(function (x) { return x.g === 'Ir a' && x.d !== 'Archivo' && x.d !== 'Taller'; }).concat(ESTATICOS.filter(function (x) { return x.g === 'Hacer'; }).slice(0, 3));
        lista = piezas.map(function (p) { return { g: 'Piezas', n: p.nombre, d: p.dato, img: p.img, u: p.url, i: 'bi-box' }; }).concat(est.slice(0, 12));
        sel = 0;
        res.innerHTML = '';
        var grupo = null;
        lista.forEach(function (x, idx) {
            if (x.g !== grupo) {
                grupo = x.g;
                var h = document.createElement('div');
                h.className = 'pzm-paleta-g';
                h.textContent = grupo;
                res.appendChild(h);
            }
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'pzm-paleta-item';
            b.dataset.i = idx;
            var ico = document.createElement('span');
            ico.className = 'ico';
            if (x.img) {
                var im = document.createElement('img');
                im.src = x.img;
                im.alt = '';
                ico.appendChild(im);
            } else {
                var ic = document.createElement('i');
                ic.className = 'bi ' + x.i;
                if (x.c) ic.style.color = x.c;
                ico.appendChild(ic);
            }
            var n = document.createElement('span');
            n.className = 'n';
            n.textContent = x.n;
            var d = document.createElement('span');
            d.className = 'd';
            d.textContent = x.d || '';
            b.appendChild(ico); b.appendChild(n); b.appendChild(d);
            res.appendChild(b);
        });
        if (!lista.length) {
            var v = document.createElement('div');
            v.className = 'pzm-paleta-vacio';
            v.textContent = 'Nada con «' + q.value.trim() + '».';
            res.appendChild(v);
        }
        marcar();
    }
    function marcar() {
        res.querySelectorAll('.pzm-paleta-item').forEach(function (b) { b.classList.toggle('on', +b.dataset.i === sel); });
        var on = res.querySelector('.pzm-paleta-item.on');
        if (on) on.scrollIntoView({ block: 'nearest' });
    }
    function ejecutar(x) {
        if (!x) return;
        cerrar();
        if (x.u) {
            if (x.fuera) window.open(x.u, '_blank'); else location.href = x.u;
        } else if (x.org) {
            var o = document.getElementById('btnOrganizar');
            if (o && URL_INDICE === location.href.split(/[?#]/)[0].replace(/\/$/, '')) o.click(); else location.href = URL_INDICE + '#organizar';
        } else if (x.m) {
            var disparador = document.querySelector('[data-bs-target="' + x.m + '"]');
            if (disparador) disparador.click();
        }
    }

    q.addEventListener('input', function () {
        clearTimeout(espera);
        var t = q.value.trim();
        pintar([]);
        if (t.length < 2) return;
        espera = setTimeout(function () {
            ultima = t;
            fetch(URL_BUSCAR + '?q=' + encodeURIComponent(t), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (ultima === q.value.trim() && !caja.hidden) pintar(d.piezas || []); })
                .catch(function () {});
        }, 180);
    });
    q.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(lista.length - 1, sel + 1); marcar(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(0, sel - 1); marcar(); }
        else if (e.key === 'Enter') { e.preventDefault(); ejecutar(lista[sel]); }
        else if (e.key === 'Escape') { e.preventDefault(); cerrar(); }
    });
    res.addEventListener('click', function (e) {
        var b = e.target.closest('.pzm-paleta-item');
        if (b) ejecutar(lista[+b.dataset.i]);
    });
    caja.addEventListener('mousedown', function (e) { if (e.target === caja) cerrar(); });
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (caja.hidden) abrir(); else cerrar();
        }
    });
})();
</script>
