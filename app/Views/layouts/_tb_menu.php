<?php
use App\Libraries\MenuPrincipal;

$modulos = MenuPrincipal::modulos();
$porRuta = array_column($modulos, null, 'ruta');
$favs    = MenuPrincipal::favoritos();
$activo  = MenuPrincipal::activo();
$enHoy   = uri_string() === 'dashboard';
$plegado = ($_COOKIE['tb_riel'] ?? '') === '1';
$marca   = '<a class="tb-marca" href="' . site_url('dashboard') . '"><img src="' . base_url('assets/images/logo-trackbitos-icon.png') . '" alt=""><span>Trackbitos</span></a>';
$buscar  = static fn (string $clase = '', string $txt = 'Ir a un módulo o tarea…') => '<button type="button" class="tb-buscar ' . $clase . '" data-tb-buscar><i class="bi bi-search"></i><span class="t">' . $txt . '</span><span class="tb-kbd">/</span></button>';
$cuentaLista = static function (string $clase = 'dropdown-menu-end') use ($modo) {
    $h = '<ul class="dropdown-menu ' . $clase . ' tb-menu-cuenta">';
    $h .= '<li><a class="dropdown-item" href="' . site_url('cuenta') . '"><i class="bi bi-person"></i>Mi cuenta</a></li>';
    if (in_groups('admin')) {
        $h .= '<li><a class="dropdown-item" href="' . site_url('cuenta/usuarios') . '"><i class="bi bi-people"></i>Usuarios</a></li>';
    }
    $h .= '<li><hr class="dropdown-divider"></li><li><h6 class="dropdown-header">Estilo del menú</h6></li>';
    foreach (MenuPrincipal::MODOS as $k => $n) {
        $h .= '<li><button type="button" class="dropdown-item" data-tb-menu="' . $k . '"><i class="bi ' . ($k === $modo ? 'bi-check2' : 'bi-dot') . '"></i>' . esc($n) . '</button></li>';
    }
    return $h . '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="' . site_url('logout') . '"><i class="bi bi-box-arrow-right"></i>Cerrar sesión</a></li></ul>';
};
$cuenta = static fn () => '<div class="dropdown"><button type="button" class="tb-ibtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Cuenta" title="Cuenta"><i class="bi bi-person-circle"></i></button>' . $cuentaLista() . '</div>';
$ri = static function (array $m, bool $fav) use ($activo) {
    return '<a class="tb-ri' . ($m['ruta'] === $activo ? ' on' : '') . '" href="' . site_url($m['ruta']) . '" title="' . esc($m['titulo'], 'attr') . '"><i class="bi ' . $m['icono'] . '"></i><span class="t">' . esc($m['titulo']) . '</span>'
        . '<button type="button" class="tb-fav" data-tb-fav="' . esc($m['ruta'], 'attr') . '" title="' . ($fav ? 'Quitar de favoritos' : 'Añadir a favoritos') . '"><i class="bi ' . ($fav ? 'bi-pin-angle-fill' : 'bi-pin-angle') . '"></i></button></a>';
};
?>
<?php if ($modo === 'a' || $modo === 'b'): ?>
    <nav class="tb tb-nav">
        <?= $marca ?>
        <?= $modo === 'b' ? $buscar('compacto') : '' ?>
        <div class="tb-nav-der"><?= $cuenta() ?></div>
    </nav>
<?php else: ?>
    <aside class="tb tb-riel<?= $plegado ? ' plegado' : '' ?>" aria-label="Menú principal">
        <div class="tb-riel-cab">
            <?= $marca ?>
            <button type="button" class="tb-ibtn" data-tb-plegar title="Plegar o desplegar" style="width:30px;height:30px;font-size:15px"><i class="bi bi-layout-sidebar"></i></button>
        </div>
        <?= $buscar('', 'Buscar…') ?>
        <a class="tb-ri<?= $enHoy ? ' on' : '' ?>" href="<?= site_url('dashboard') ?>" title="Hoy"><i class="bi bi-sun"></i><span class="t">Hoy</span></a>
        <?php if ($favs): ?>
            <div class="tb-grp">Favoritos</div><span class="tb-grp-sep"></span>
            <?php foreach ($favs as $r): ?><?= $ri($porRuta[$r], true) ?><?php endforeach; ?>
        <?php endif; ?>
        <div class="tb-grp">Todo</div><span class="tb-grp-sep"></span>
        <?php foreach ($modulos as $m): ?>
            <?php if (!in_array($m['ruta'], $favs, true)): ?><?= $ri($m, false) ?><?php endif; ?>
        <?php endforeach; ?>
        <div class="tb-riel-pie">
            <div class="dropup">
                <button type="button" class="tb-ri w-100 border-0 bg-transparent" data-bs-toggle="dropdown" aria-expanded="false" title="Cuenta"><i class="bi bi-person-circle"></i><span class="t text-start">Cuenta y estilo</span></button>
                <?= $cuentaLista('') ?>
            </div>
        </div>
    </aside>

    <nav class="tb tb-nav tb-movil">
        <?= $marca ?>
        <div class="tb-nav-der"><?= $buscar('compacto') ?><?= $cuenta() ?></div>
    </nav>

    <nav class="tb-tabs" aria-label="Menú principal">
        <a href="<?= site_url('dashboard') ?>"<?= $enHoy ? ' class="on"' : '' ?>><i class="bi bi-sun"></i><span class="t">Hoy</span></a>
        <?php foreach (array_slice($favs, 0, 3) as $r): $m = $porRuta[$r]; ?>
            <a href="<?= site_url($m['ruta']) ?>"<?= $m['ruta'] === $activo ? ' class="on"' : '' ?>><i class="bi <?= $m['icono'] ?>"></i><span class="t"><?= esc($m['titulo']) ?></span></a>
        <?php endforeach; ?>
        <?php for ($i = count(array_slice($favs, 0, 3)); $i < 3; $i++): ?><span></span><?php endfor; ?>
        <button type="button" data-tb-hoja<?= $activo && !in_array($activo, array_slice($favs, 0, 3), true) ? ' class="on"' : '' ?>><i class="bi bi-grid-3x3-gap"></i><span class="t">Todo</span></button>
    </nav>
    <div class="tb-velo" data-tb-hoja-cerrar></div>
    <div class="tb-hoja" role="dialog" aria-label="Todos los módulos">
        <div class="tb-asa"></div>
        <?= $buscar('', 'Buscar módulo o tarea…') ?>
        <h3 data-tb-hoja-titulo>Todos los módulos</h3>
        <div class="tb-mtiles">
            <?php foreach ($modulos as $m): ?>
                <a class="tb-mtile" href="<?= site_url($m['ruta']) ?>" data-tb-ruta="<?= esc($m['ruta'], 'attr') ?>"><?= in_array($m['ruta'], $favs, true) ? '<i class="bi bi-pin-angle-fill tb-pin" style="position:absolute;top:8px;right:8px;font-size:11px;color:var(--tb-acc)"></i>' : '' ?><i class="bi <?= $m['icono'] ?>"></i><?= esc($m['titulo']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="tb-hoja-pie">
            <button type="button" data-tb-editar-favs><i class="bi bi-pin-angle"></i><span>Elegir favoritos</span></button>
        </div>
    </div>
<?php endif; ?>

<div class="tb-paleta" role="dialog" aria-label="Buscar">
    <div class="tb-paleta-caja">
        <div class="tb-paleta-q"><i class="bi bi-search"></i><input type="text" id="tbPaletaQ" autocomplete="off" placeholder="Módulo o tarea…"><span class="tb-kbd">esc</span></div>
        <div class="tb-paleta-lista" id="tbPaletaLista"></div>
    </div>
</div>

<script>
(function () {
    var MODS = <?= json_encode(array_map(static fn ($m) => ['n' => $m['titulo'], 'i' => $m['icono'], 'u' => site_url($m['ruta']), 'r' => $m['ruta']], $modulos), JSON_UNESCAPED_UNICODE) ?>;
    var FAVS = <?= json_encode($favs) ?>;
    var URL_TAREAS = '<?= site_url('dashboard/tarea-buscar') ?>';
    var URL_TAREA = '<?= site_url('journal/edit/') ?>';
    var poner = function (n, v) { document.cookie = n + '=' + encodeURIComponent(v) + '; path=/; max-age=31536000; samesite=lax'; };
    var body = document.body;
    var paleta = document.querySelector('.tb-paleta');
    var q = document.getElementById('tbPaletaQ');
    var lista = document.getElementById('tbPaletaLista');
    var sel = 0, espera = null, editando = false;

    var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); };
    var norm = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); };
    function pintar(items) {
        sel = 0;
        lista.innerHTML = items.length ? items.map(function (it, i) {
            return '<a href="' + esc(it.u) + '"' + (i === 0 ? ' class="sel"' : '') + '><i class="bi ' + it.i + '"></i><span class="t">' + esc(it.n) + '</span><span class="k">' + it.k + '</span></a>';
        }).join('') : '<div class="tb-paleta-vacio">Sin resultados</div>';
    }
    function buscar() {
        var t = norm(q.value.trim());
        var mods = MODS.filter(function (m) { return !t || norm(m.n).indexOf(t) !== -1; }).map(function (m) { return { n: m.n, i: m.i, u: m.u, k: 'módulo' }; });
        pintar(mods);
        clearTimeout(espera);
        if (t.length < 2) return;
        espera = setTimeout(function () {
            fetch(URL_TAREAS + '?q=' + encodeURIComponent(q.value.trim()), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var tareas = (d.resultados || []).map(function (x) { return { n: x.texto, i: 'bi-journal-check', u: URL_TAREA + x.id + '#subtaskList', k: 'tarea' }; });
                    pintar(mods.concat(tareas));
                }).catch(function () {});
        }, 220);
    }
    function abrir() { body.classList.remove('tb-hoja-abierta'); paleta.classList.add('abierta'); q.value = ''; buscar(); setTimeout(function () { q.focus(); }, 10); }
    function cerrar() { paleta.classList.remove('abierta'); }
    function mover(d) {
        var as = lista.querySelectorAll('a'); if (!as.length) return;
        as[sel].classList.remove('sel'); sel = (sel + d + as.length) % as.length; as[sel].classList.add('sel'); as[sel].scrollIntoView({ block: 'nearest' });
    }
    q.addEventListener('input', buscar);
    q.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); mover(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); mover(-1); }
        else if (e.key === 'Enter') { var a = lista.querySelectorAll('a')[sel]; if (a) { e.preventDefault(); location.href = a.href; } }
    });
    paleta.addEventListener('click', function (e) { if (e.target === paleta) cerrar(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { cerrar(); body.classList.remove('tb-hoja-abierta'); return; }
        if (e.key === '/' && !paleta.classList.contains('abierta') && !e.target.closest('input, textarea, select, [contenteditable]')) { e.preventDefault(); abrir(); }
    });

    function toggleFav(r) {
        var i = FAVS.indexOf(r);
        if (i === -1) FAVS.push(r); else FAVS.splice(i, 1);
        poner('tb_favs', FAVS.join(','));
    }
    document.addEventListener('click', function (e) {
        var m = e.target.closest('[data-tb-menu]');
        if (m) { poner('tb_menu', m.getAttribute('data-tb-menu')); location.reload(); return; }
        if (e.target.closest('[data-tb-buscar]')) { abrir(); return; }
        var f = e.target.closest('[data-tb-fav]');
        if (f) { e.preventDefault(); e.stopPropagation(); toggleFav(f.getAttribute('data-tb-fav')); location.reload(); return; }
        var p = e.target.closest('[data-tb-plegar]');
        if (p) {
            var on = p.closest('.tb-riel').classList.toggle('plegado');
            body.classList.toggle('tb-plegado', on);
            poner('tb_riel', on ? '1' : '0');
            return;
        }
        if (e.target.closest('[data-tb-hoja]')) { body.classList.toggle('tb-hoja-abierta'); return; }
        if (e.target.closest('[data-tb-hoja-cerrar]')) { body.classList.remove('tb-hoja-abierta'); if (editando) location.reload(); return; }
        var ed = e.target.closest('[data-tb-editar-favs]');
        if (ed) {
            if (editando) { location.reload(); return; }
            editando = true;
            ed.querySelector('span').textContent = 'Listo';
            document.querySelector('[data-tb-hoja-titulo]').textContent = 'Toca para fijar o quitar (la barra muestra 3)';
            return;
        }
        var t = e.target.closest('.tb-mtile[data-tb-ruta]');
        if (t && editando) {
            e.preventDefault();
            toggleFav(t.getAttribute('data-tb-ruta'));
            var pin = t.querySelector('.tb-pin');
            if (pin) pin.remove(); else t.insertAdjacentHTML('afterbegin', '<i class="bi bi-pin-angle-fill tb-pin" style="position:absolute;top:8px;right:8px;font-size:11px;color:var(--tb-acc)"></i>');
        }
    });
})();
</script>
