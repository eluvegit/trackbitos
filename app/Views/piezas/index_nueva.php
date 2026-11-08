<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=10">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-index.css') ?>?v=6">

<?php
$estadoDe = static function (array $v): array {
    $ult = $v['ultima_version_numero'] ?? null;
    if ($v['validada']) {
        $num = (int) $v['validada']['numero'];
        $nuevo = ($v['ultima_version_estado'] === 'borrador' && ($ult ?? 0) > $num) ? (int) $ult : 0;
        return ['def', $num, $nuevo];
    }
    switch ($v['ultima_version_estado']) {
        case 'impresa':    return ['val', (int) $ult, 0];
        case 'descartada': return ['no', (int) $ult, 0];
        case 'borrador':   return ['imp', (int) $ult, 0];
        case null:         return [empty($v['trabajo_en_curso']) ? 'emp' : 'sinv', 0, 0];
    }
    return ['otra', (int) $ult, 0];
};

$medidasDe = static function (array $v): string {
    $m = $v['medidas_placa'] ?? ['aplica' => false];
    if (empty($m['aplica'])) {
        return '';
    }
    if (empty($m['completas'])) {
        return 'sin medir';
    }
    $cm2 = (float) $m['area_mm2'] / 100;
    return ($cm2 >= 10 ? number_format($cm2, 0, ',', '.') : number_format($cm2, 1, ',', '.')) . ' cm²';
};

$datos = [];
$totalUds = 0;
$totalVars = 0;
foreach ($grupos as $grupo) {
    $c = $grupo['categoria'];
    $fams = [];
    foreach ($grupo['piezas'] as $f) {
        $vars = [];
        foreach ($f['variantes'] as $v) {
            [$e, $ver, $nuevo] = $estadoDe($v);
            $tareas = (string) ($v['tareas'] ?? '');
            $stl = $v['stl'] ?? [];
            $vars[] = [
                'id'    => (int) $v['id'],
                'n'     => $v['nombre'],
                'sku'   => $v['sku'] ?? '',
                'e'     => $e,
                'ver'   => $ver,
                'nuevo' => $nuevo,
                'uds'   => (int) ($v['unidades'] ?? 0),
                'mod'   => !empty($v['trabajo_en_curso']),
                'lock'  => $v['bloqueo']['maquina'] ?? '',
                'pend'  => count($v['pendientes'] ?? []),
                'adv'   => trim((string) ($v['advertencia'] ?? '')),
                'tareas' => $tareas,
                't'     => count(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $tareas)), static fn($l) => $l !== '')),
                'foto'  => $v['miniatura'] ?: '',
                'vis'   => !empty($v['visible_sterclicks']),
                'blend' => (!empty($stl['aplica']) && !empty($stl['version_id'])) ? site_url('piezas/version/' . (int) $stl['version_id'] . '/blend/descargar') : '',
                'med'   => $medidasDe($v),
                'malla' => empty($v['tiene_vigente']) ? '' : ($v['revision_malla'] ?? '?'),
            ];
            $totalUds += (int) ($v['unidades'] ?? 0);
            $totalVars++;
        }
        $fams[] = ['id' => (int) $f['id'], 'n' => $f['nombre'], 'vis' => !empty($f['visible_sterclicks']), 'cat' => (int) ($f['categoria_id'] ?? 0), 'vars' => $vars];
    }
    $datos[] = ['id' => $c ? (int) $c['id'] : 0, 'n' => $c ? $c['nombre'] : 'Sin clasificar', 'vis' => $c ? !empty($c['visible_sterclicks']) : true, 'fams' => $fams];
}
$listaCats = array_map(static fn($c) => ['id' => (int) $c['id'], 'n' => $c['nombre']], $categorias);
?>

<div class="fv pi" id="pi">
    <div class="fv-hero">
        <h1>Piezas</h1>
        <a href="<?= site_url('piezas?nueva=0') ?>" class="fv-gbtn" title="Volver a la tabla de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
    </div>
    <div class="fv-meta">
        <span><b><?= $totalVars ?></b> variantes en <b><?= count($familias) ?></b> piezas</span>
        <span><b><?= count($categorias) ?></b> categorías</span>
        <span><b><?= $totalUds ?></b> unidades en existencias</span>
        <a href="<?= site_url('piezas/nevera') ?>">Nevera <b><?= (int) $neveraCount ?></b></a>
        <a href="<?= site_url('piezas/papelera') ?>">Papelera <b><?= (int) $papeleraCount ?></b></a>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div id="sesionesActivas"></div>

    <?php if (empty($familias)): ?>
        <p class="fv-vacio">Todavía no hay ninguna pieza. Crea una con «Pieza nueva».</p>
    <?php else: ?>
        <div class="pi-busca">
            <label class="pi-buscar">
                <i class="bi bi-search"></i>
                <input id="piBuscar" type="search" placeholder="Buscar pieza, variante o SKU" autocomplete="off" aria-label="Buscar piezas">
                <span class="pi-cuenta" id="piCuenta"></span>
                <span class="pi-kbd">/</span>
            </label>
        </div>
        <div class="pi-toolbar">
            <div class="pi-chips" id="piChips"></div>
            <div class="pi-der">
                <button type="button" class="fv-gbtn" id="piPlegarVar"></button>
                <button type="button" class="fv-gbtn" id="piPlegarTodo"></button>
                <div class="pi-vistas" role="group" aria-label="Vista">
                    <button type="button" data-vista="comp" title="Compacta"><i class="bi bi-grid-3x2-gap"></i></button>
                    <button type="button" data-vista="lista" title="Lista"><i class="bi bi-list-ul"></i></button>
                    <button type="button" data-vista="tabla" title="Tabla"><i class="bi bi-table"></i></button>
                    <button type="button" data-vista="cuad" title="Cuadrícula"><i class="bi bi-grid"></i></button>
                </div>
            </div>
        </div>
        <div id="piVista" style="display:grid;gap:22px"></div>
        <p class="pi-vacio-todo" id="piNada" hidden>Ninguna pieza coincide.</p>
    <?php endif; ?>
</div>

<script>
(function () {
    var DATOS = <?= json_encode($datos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var CATS = <?= json_encode($listaCats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var B = <?= json_encode(rtrim(site_url('piezas'), '/')) ?>;
    var CSRF = { n: <?= json_encode(csrf_token()) ?>, v: <?= json_encode(csrf_hash()) ?> };
    window.piDatos = DATOS;
    if (!document.getElementById('piVista')) return;

    var EST = {
        def:  { t: 'Definitiva', c: 'e-ok', f: 'Definitivas' },
        imp:  { t: 'Para imprimir', c: 'e-warn', f: 'Para imprimir' },
        val:  { t: 'Sin validar', c: 'e-info', f: 'Sin validar' },
        no:   { t: 'No sirve', c: 'e-bad', f: 'No sirven' },
        sinv: { t: 'Sin versión', c: 'e-neu', f: 'Sin versión' },
        emp:  { t: 'Sin empezar', c: 'e-neu', f: 'Sin empezar' },
        otra: { t: 'Sin validar', c: 'e-neu', f: '' }
    };
    var ORDEN = ['def', 'imp', 'val', 'no', 'otra', 'sinv', 'emp'];
    var EXTRA = {
        mod:    { t: 'Modificando', i: 'bi-pencil', p: function (v) { return v.mod; } },
        sinimg: { t: 'Sin imagen', i: 'bi-image', p: function (v) { return !v.foto; } },
        tareas: { t: 'Con tareas', i: 'bi-card-checklist', p: function (v) { return v.t > 0; } },
        oculta: { t: 'Ocultas en sterclicks', i: 'bi-eye-slash', p: function (v) { return !v.st; } }
    };

    function ls(k, def) { try { var x = localStorage.getItem(k); return x === null ? def : x; } catch (e) { return def; } }
    function lsSet(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    var vista = ls('pi_vista', 'comp');
    var varPleg = ls('pi_varpleg', '0') === '1';
    var cerradas = JSON.parse(ls('pi_cerradas', '[]') || '[]');
    var filtro = '';
    var famN = 0;

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function norm(s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
    function pad(n) { return 'v' + String(n).padStart(3, '0'); }
    function csrfInput() { return '<input type="hidden" name="' + esc(CSRF.n) + '" value="' + esc(CSRF.v) + '">'; }
    function fichaUrl(v) { return B + '/variante/' + v.id; }

    var todas = [];
    DATOS.forEach(function (c) {
        c.fams.forEach(function (f) {
            f.vars.forEach(function (v) { v.fam = f; v.catObj = c; todas.push(v); });
        });
    });
    function recalcSt() { todas.forEach(function (v) { v.st = v.catObj.vis && v.fam.vis && v.vis; }); }
    recalcSt();

    function th(v, px) {
        var tam = px ? ' style="width:' + px + 'px;height:' + px + 'px"' : '';
        return v.foto
            ? '<img class="pi-th" src="' + esc(v.foto) + '" alt="" loading="lazy"' + tam + '>'
            : '<span class="pi-th" title="Sin imagen"' + tam + '><i class="bi bi-image"></i></span>';
    }
    function mosaico(f, px) {
        var fotos = f.vars.filter(function (v) { return v.foto; });
        var muchas = f.vars.length > 4;
        var m = (muchas ? fotos.slice(0, 3) : fotos.slice(0, 4)).map(function (v) { return th(v, 0); }).join('');
        if (!fotos.length) m = th(f.vars[0], 0);
        return '<span class="pi-mos"' + (px ? ' style="width:' + px + 'px;height:' + px + 'px"' : '') + '>' + m + (muchas ? '<span class="pi-mas">+' + (f.vars.length - 3) + '</span>' : '') + '</span>';
    }
    function estado(v) {
        var e = EST[v.e], t = e.t;
        if (v.e === 'def') t = 'Definitiva <span class="fv-mono">' + pad(v.ver) + '</span>';
        else if (v.ver) t += ' <span class="fv-mono" style="opacity:.7">' + pad(v.ver) + '</span>';
        return '<span class="fv-estado ' + e.c + '">' + t + '</span>';
    }
    function sub(v, corto) {
        var p = [];
        if (v.lock) p.push('<span class="l" title="Sesión abierta en ' + esc(v.lock) + '"><i class="bi bi-lock-fill"></i>' + (corto ? '' : ' ' + esc(v.lock)) + '</span>');
        else if (v.pend) p.push('<span class="l" title="' + v.pend + ' descargas sin cerrar"><i class="bi bi-download"></i> ' + v.pend + (corto ? '' : ' sin cerrar') + '</span>');
        else if (v.mod) p.push('<span title="Hay trabajo sin promocionar"><i class="bi bi-pencil"></i>' + (corto ? '' : ' modificando') + '</span>');
        if (v.nuevo && !corto) p.push('<span>' + pad(v.nuevo) + ' para imprimir</span>');
        if (v.adv) p.push('<span class="w" title="' + esc(v.adv) + '"><i class="bi bi-exclamation-triangle-fill"></i>' + (corto ? '' : ' ' + esc(v.adv)) + '</span>');
        return p.join(corto ? '' : '<span>·</span>');
    }
    function btnTareas(v) {
        var nombre = v.fam.n + (v.fam.vars.length > 1 ? ' / ' + v.n : '');
        var algo = v.t > 0 || v.adv !== '';
        return '<button type="button" class="fv-ibtn' + (algo ? ' on' : '') + '" data-bs-toggle="modal" data-bs-target="#modalTareas"'
            + ' data-accion="' + esc(B + '/variante/' + v.id + '/tareas') + '" data-nombre="' + esc(nombre) + '"'
            + ' data-tareas="' + esc(v.tareas) + '" data-advertencia="' + esc(v.adv) + '" title="Tareas y advertencia">'
            + '<i class="bi bi-card-checklist"></i>' + (v.t ? '<small>' + v.t + '</small>' : '') + '</button>';
    }
    function btnOjo(tipo, id, visible) {
        return '<button type="button" class="fv-ibtn' + (visible ? ' on' : '') + '" data-ojo="' + tipo + '/' + id + '" title="' + (visible ? 'Ocultar de sterclicks' : 'Mostrar en sterclicks') + '">'
            + '<i class="bi ' + (visible ? 'bi-eye' : 'bi-eye-slash') + '"></i></button>';
    }
    function acciones(v) {
        var sola = v.fam.vars.length === 1;
        return '<div class="pi-acc">'
            + (v.blend ? '<a class="fv-ibtn" href="' + esc(v.blend) + '" title="Bajar el .blend de la versión vigente"><i class="bi bi-download"></i></a>' : '')
            + btnTareas(v)
            + (sola ? btnOjo('familia', v.fam.id, v.fam.vis) : btnOjo('variante', v.id, v.vis))
            + '</div>';
    }
    function formPost(url, cuerpo, confirmar, extra) {
        return '<form method="post" action="' + esc(url) + '"' + (confirmar ? ' data-confirmar="' + esc(confirmar) + '"' : '') + (extra || '') + '>' + csrfInput() + cuerpo + '</form>';
    }
    function orgFam(f) {
        var ops = '<option value="">— sin clasificar —</option>' + CATS.map(function (c) {
            return '<option value="' + c.id + '"' + (c.id === f.cat ? ' selected' : '') + '>' + esc(c.n) + '</option>';
        }).join('');
        return '<span class="pi-orgz" data-org>'
            + formPost(B + '/familia/' + f.id + '/categoria', '<select name="categoria_id" data-autoenvio title="Mover a otra categoría">' + ops + '</select>')
            + formPost(B + '/familia/' + f.id + '/borrar', '<button class="fv-ibtn" title="Mandar la pieza a la papelera"><i class="bi bi-trash"></i></button>',
                '¿Mandar «' + f.n + '» a la papelera? Se puede restaurar durante 30 días.')
            + '</span>';
    }
    function orgVar(v) {
        return '<span class="pi-orgz" data-org>'
            + formPost(B + '/variante/' + v.id + '/borrar', '<button class="fv-ibtn" title="Mandar esta variante a la papelera"><i class="bi bi-trash"></i></button>',
                '¿Mandar «' + v.fam.n + ' / ' + v.n + '» a la papelera? Se puede restaurar durante 30 días.')
            + '</span>';
    }
    function txt(v) { return norm([v.fam.n, v.n, v.sku].join(' ')); }
    function hojaAttr(v, fid) {
        return ' data-v="' + v.id + '"' + (fid ? ' data-fam="' + fid + '"' : '') + ' data-txt="' + esc(txt(v)) + '"';
    }
    function barraFam(vars) {
        var c = {};
        vars.forEach(function (v) { c[v.e] = (c[v.e] || 0) + 1; });
        var ks = ORDEN.filter(function (k) { return c[k]; });
        var res = ks.slice(0, 3).map(function (k) { return '<b class="' + EST[k].c + '">' + c[k] + '</b> ' + EST[k].t.toLowerCase(); }).join(' · ') + (ks.length > 3 ? ' · …' : '');
        return '<span class="pi-barra">' + ks.map(function (k) { return '<i class="' + EST[k].c + '" style="flex:' + c[k] + '" title="' + c[k] + ' ' + EST[k].t.toLowerCase() + '"></i>'; }).join('') + '</span><span class="pi-res">' + res + '</span>';
    }
    function udsFam(f) { return f.vars.reduce(function (s, v) { return s + v.uds; }, 0); }
    function vaciaFam(f) {
        return '<div class="pi-k-card" data-sola><span class="pi-th" style="width:64px;height:64px"><i class="bi bi-trash"></i></span><div style="min-width:0"><div class="pi-k-nom">' + esc(f.n) + '</div>'
            + '<div class="pi-k-lin"><a href="' + B + '/papelera" style="color:var(--fv-bad)">Sin variantes: recuperar en la papelera</a></div></div>' + orgFam(f) + '</div>';
    }
    function cabFam(f, id, extraCls) {
        return ' data-abrir="' + id + '" aria-expanded="true" tabindex="0"' + (extraCls ? '' : '');
    }

    function cuerpoComp(c) {
        var sueltas = '', grupos = '';
        c.fams.forEach(function (f) {
            if (!f.vars.length) { sueltas += vaciaFam(f); return; }
            if (f.vars.length === 1) {
                var v = f.vars[0];
                sueltas += '<div class="pi-k-card hoja"' + hojaAttr(v) + '><a href="' + fichaUrl(v) + '">' + th(v, 64) + '</a><div style="min-width:0"><a class="pi-k-nom pi-nom-a" href="' + fichaUrl(v) + '">' + esc(f.n) + '</a>'
                    + '<div class="pi-k-lin">' + estado(v) + '<span class="uds" title="Unidades en existencias">' + v.uds + '</span>' + sub(v, true) + '</div></div>' + acciones(v) + orgFam(f) + '</div>';
                return;
            }
            var id = 'f' + (++famN);
            grupos += '<div class="pi-k-fh"' + cabFam(f, id) + '><i class="bi bi-chevron-right chev"></i><span class="nom">' + esc(f.n) + '</span><span class="datos">' + f.vars.length + ' variantes · ' + udsFam(f) + ' uds</span>'
                + '<span onclick="event.stopPropagation()">' + btnOjo('familia', f.id, f.vis) + '</span><span onclick="event.stopPropagation()">' + orgFam(f) + '</span><span class="bar">' + barraFam(f.vars) + '</span></div>'
                + '<div class="pi-k-sub" data-de="' + id + '"><div class="pi-filtrado" hidden></div>' + f.vars.map(function (v) {
                    return '<div class="pi-k-card hoja"' + hojaAttr(v, id) + ' title="' + esc(v.sku) + '"><a href="' + fichaUrl(v) + '">' + th(v, 64) + '</a><div style="min-width:0"><a class="pi-k-nom pi-nom-a" href="' + fichaUrl(v) + '">' + esc(v.n) + '</a>'
                        + '<div class="pi-k-lin">' + estado(v) + '<span class="uds">' + v.uds + '</span>' + sub(v, true) + '</div></div>' + acciones(v) + orgVar(v) + '</div>';
                }).join('') + '</div>';
        });
        return '<div class="pi-k-grid">' + sueltas + grupos + '</div>';
    }
    function tile(v, id) {
        var marcas = (v.adv ? '<span class="w" title="' + esc(v.adv) + '"><i class="bi bi-exclamation-triangle-fill"></i></span>' : '')
            + (v.lock ? '<span class="l" title="' + esc(v.lock) + ' trabajando"><i class="bi bi-lock-fill"></i></span>' : v.mod ? '<span title="Modificando"><i class="bi bi-pencil"></i></span>' : '');
        return '<div class="pi-tile hoja"' + hojaAttr(v, id) + '><a href="' + fichaUrl(v) + '">' + th(v, 0) + '</a>' + (marcas ? '<div class="pi-marca">' + marcas + '</div>' : '')
            + '<div class="pi-flot">' + acciones(v) + '</div><a class="t-nom pi-nom-a" href="' + fichaUrl(v) + '">' + esc(v.n) + '</a>' + estado(v) + orgVar(v) + '</div>';
    }
    function cuerpoLista(c) {
        var r = '';
        c.fams.forEach(function (f) {
            if (!f.vars.length) { r += vaciaFam(f); return; }
            if (f.vars.length === 1) {
                var v = f.vars[0];
                r += '<div class="pi-l-fila hoja"' + hojaAttr(v) + '><a href="' + fichaUrl(v) + '">' + th(v, 76) + '</a><div style="min-width:0"><a class="nom pi-nom-a" href="' + fichaUrl(v) + '">' + esc(f.n) + '</a>'
                    + '<div class="datos"><span class="fv-mono">' + esc(v.sku) + '</span><span>' + v.uds + ' uds</span>' + (v.t ? '<span>' + v.t + ' tareas</span>' : '') + '</div>' + orgFam(f) + '</div>'
                    + '<div class="est">' + estado(v) + '<span class="pi-sub">' + sub(v, false) + '</span></div>' + acciones(v) + '</div>';
                return;
            }
            var id = 'f' + (++famN);
            r += '<div class="pi-l-fila fam"' + cabFam(f, id) + '>' + mosaico(f) + '<div style="min-width:0"><div class="nom">' + esc(f.n) + '</div><div class="datos"><span>' + f.vars.length + ' variantes</span><span>' + udsFam(f) + ' uds</span></div>'
                + '<span onclick="event.stopPropagation()">' + orgFam(f) + '</span></div><div class="est">' + barraFam(f.vars) + '</div>'
                + '<div class="fin" style="display:flex;justify-content:flex-end;gap:4px;align-items:center"><span onclick="event.stopPropagation()">' + btnOjo('familia', f.id, f.vis) + '</span><i class="bi bi-chevron-right chev"></i></div></div>'
                + '<div class="pi-vars" data-de="' + id + '"><div class="pi-filtrado" hidden></div><div class="pi-vgrid">' + f.vars.map(function (v) { return tile(v, id); }).join('') + '</div></div>';
        });
        return r;
    }
    function filaTabla(v, id) {
        var malla = { ok: '<span class="fv-estado e-ok">limpia</span>', fallos: '<span class="fv-estado e-bad">fallos</span>' }[v.malla] || (v.malla ? '<span class="fv-estado e-neu">sin mirar</span>' : '');
        return '<tr class="hoja"' + hojaAttr(v, id) + (id ? ' data-de="' + id + '"' : '') + '>'
            + '<td class="t-foto"><a href="' + fichaUrl(v) + '">' + th(v, id ? 36 : 44) + '</a></td>'
            + '<td class="t-nom"><a class="pi-nom-a ' + (id ? 't-var' : '') + '" href="' + fichaUrl(v) + '">' + esc(id ? v.n : v.fam.n) + '</a><div class="pi-sub">' + sub(v, false) + '</div>' + (id ? orgVar(v) : orgFam(v.fam)) + '</td>'
            + '<td class="t-mono">' + esc(v.sku) + '</td><td class="t-mono t-r">' + v.uds + '</td><td>' + estado(v) + '</td>'
            + '<td class="t-mono">' + esc(v.med) + '</td><td>' + malla + '</td><td class="t-acc">' + acciones(v) + '</td></tr>';
    }
    function cuerpoTabla(c) {
        var r = '', vacias = '';
        c.fams.forEach(function (f) {
            if (!f.vars.length) { vacias += vaciaFam(f); return; }
            if (f.vars.length === 1) { r += filaTabla(f.vars[0], ''); return; }
            var id = 'f' + (++famN);
            r += '<tr class="t-fam"' + cabFam(f, id) + '><td class="t-foto">' + mosaico(f, 44) + '</td><td class="t-nom">' + esc(f.n) + '<span class="t-cnt">' + f.vars.length + ' variantes</span>'
                + '<span onclick="event.stopPropagation()">' + orgFam(f) + '</span></td><td></td><td class="t-mono t-r">' + udsFam(f) + '</td>'
                + '<td colspan="3"><div class="t-bar">' + barraFam(f.vars) + '</div></td><td class="t-acc"><div style="display:flex;justify-content:flex-end;align-items:center;gap:4px"><span onclick="event.stopPropagation()">' + btnOjo('familia', f.id, f.vis) + '</span><i class="bi bi-chevron-right chev"></i></div></td></tr>';
            r += f.vars.map(function (v) { return filaTabla(v, id); }).join('');
        });
        return (vacias ? '<div class="pi-k-grid" style="margin-bottom:8px">' + vacias + '</div>' : '')
            + '<div class="pi-twrap"><table class="pi-tabla"><thead><tr><th></th><th>Pieza</th><th>SKU</th><th class="t-r">Uds</th><th>Estado</th><th>Placa</th><th>Malla</th><th></th></tr></thead><tbody>' + r + '</tbody></table></div>';
    }
    function cuerpoCuad(c) {
        var r = '';
        c.fams.forEach(function (f) {
            if (!f.vars.length) { r += vaciaFam(f); return; }
            if (f.vars.length === 1) {
                var v = f.vars[0];
                r += '<article class="pi-tarjeta hoja"' + hojaAttr(v) + '><a href="' + fichaUrl(v) + '">' + th(v, 0) + '</a><div class="pi-flot">' + acciones(v) + '</div>'
                    + '<div class="cuerpo"><a class="nom pi-nom-a" href="' + fichaUrl(v) + '">' + esc(f.n) + '</a>' + estado(v) + '<div class="pie"><span>' + esc(v.sku) + '</span><span>' + v.uds + ' uds</span></div>' + orgFam(f) + '</div></article>';
                return;
            }
            var id = 'f' + (++famN);
            r += '<article class="pi-tarjeta fam"' + cabFam(f, id) + '>' + mosaico(f) + '<span class="pi-capas"><i class="bi bi-layers"></i> ' + f.vars.length + '</span>'
                + '<div class="cuerpo"><div class="nom">' + esc(f.n) + '</div><div style="display:flex;flex-direction:column;gap:4px">' + barraFam(f.vars) + '</div><span onclick="event.stopPropagation()">' + orgFam(f) + '</span></div></article>'
                + '<div class="pi-vars" data-de="' + id + '"><div class="pi-filtrado" hidden></div><div class="pi-vgrid">' + f.vars.map(function (v) { return tile(v, id); }).join('') + '</div></div>';
        });
        return '<div class="pi-c-grid">' + r + '</div>';
    }
    function cabCat(c, i) {
        var n = c.fams.reduce(function (s, f) { return s + f.vars.length; }, 0);
        var org = c.id ? '<span class="pi-orgz" data-org onclick="event.stopPropagation()">'
            + formPost(B + '/categoria/' + c.id + '/mover/subir', '<button class="fv-ibtn mover" title="Subir"' + (i === 0 ? ' disabled' : '') + '><i class="bi bi-arrow-up"></i></button>')
            + formPost(B + '/categoria/' + c.id + '/mover/bajar', '<button class="fv-ibtn mover" title="Bajar"><i class="bi bi-arrow-down"></i></button>') + '</span>' : '';
        var ojo = c.id ? '<button type="button" class="pi-vis' + (c.vis ? ' on' : '') + '" data-ojo="categoria/' + c.id + '" data-con-texto onclick="event.stopPropagation()">'
            + '<i class="bi ' + (c.vis ? 'bi-eye' : 'bi-eye-slash') + '"></i> <span>' + (c.vis ? 'En sterclicks' : 'Oculta en sterclicks') + '</span></button>' : '';
        return '<div class="pi-cat-h" data-plegar><i class="bi bi-chevron-down chev"></i><h2 class="' + (c.id ? '' : 'sin') + '">' + esc(c.n) + '</h2><span class="n">' + n + '</span>' + ojo + org + '</div>';
    }
    function pintar() {
        famN = 0;
        var f = { comp: cuerpoComp, lista: cuerpoLista, tabla: cuerpoTabla, cuad: cuerpoCuad }[vista] || cuerpoComp;
        document.getElementById('piVista').innerHTML = DATOS.map(function (c, i) {
            var clave = 'c' + c.id;
            var cuerpo = c.fams.length ? f(c) : '<p class="fv-vacio">Vacía: mueve piezas aquí desde «Organizar».</p>';
            return '<section class="pi-cat' + (cerradas.indexOf(clave) >= 0 ? ' plegada' : '') + '" data-cat="' + clave + '">' + cabCat(c, i) + '<div class="pi-cuerpo">' + cuerpo + '</div></section>';
        }).join('');
        document.querySelectorAll('[data-vista]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.vista === vista); });
        pintarBotones();
        aplicar();
    }
    function pintarChips() {
        var cuenta = function (p) { return todas.filter(p).length; };
        var h = '<button type="button" class="pi-chip" data-filtro="" aria-pressed="' + (filtro === '') + '"><span>Todas</span><span class="n">' + todas.length + '</span></button>';
        ['imp', 'val', 'no', 'sinv', 'emp', 'def'].forEach(function (k) {
            var n = cuenta(function (v) { return v.e === k; });
            h += '<button type="button" class="pi-chip fv-estado ' + EST[k].c + '" data-filtro="' + k + '" aria-pressed="' + (filtro === k) + '"' + (n ? '' : ' disabled') + '><span>' + EST[k].f + '</span><span class="n">' + n + '</span></button>';
        });
        h += '<span class="fv-vsep"></span>';
        Object.keys(EXTRA).forEach(function (k) {
            var n = cuenta(EXTRA[k].p);
            h += '<button type="button" class="pi-chip" data-filtro="' + k + '" aria-pressed="' + (filtro === k) + '"' + (n || filtro === k ? '' : ' disabled') + '><span><i class="bi ' + EXTRA[k].i + '"></i> ' + EXTRA[k].t + '</span><span class="n">' + n + '</span></button>';
        });
        document.getElementById('piChips').innerHTML = h;
    }
    function pintarBotones() {
        document.getElementById('piPlegarVar').innerHTML = '<i class="bi bi-layers"></i> ' + (varPleg ? 'Desplegar variantes' : 'Plegar variantes');
        var todasCerradas = document.querySelectorAll('.pi-cat:not(.plegada)').length === 0;
        var b = document.getElementById('piPlegarTodo');
        b.innerHTML = '<i class="bi ' + (todasCerradas ? 'bi-arrows-expand' : 'bi-arrows-collapse') + '"></i> ' + (todasCerradas ? 'Desplegar todo' : 'Plegar todo');
        b.dataset.abrir = todasCerradas ? '1' : '0';
    }
    var porId = {};
    todas.forEach(function (v) { porId[v.id] = v; });
    function aplicar() {
        var q = norm(document.getElementById('piBuscar').value.trim());
        var activo = !!(filtro || q);
        var pasaF = function (v) {
            if (!filtro) return true;
            if (EXTRA[filtro]) return EXTRA[filtro].p(v);
            return v.e === filtro;
        };
        var tot = 0;
        document.querySelectorAll('#piVista .hoja:not([data-fam])').forEach(function (el) {
            var v = porId[el.dataset.v];
            el.hidden = !(pasaF(v) && (!q || el.dataset.txt.indexOf(q) >= 0));
            if (!el.hidden) tot++;
        });
        document.querySelectorAll('#piVista [data-de]').forEach(function (el) { el.hidden = true; });
        document.querySelectorAll('#piVista [data-abrir]').forEach(function (cab) {
            var id = cab.dataset.abrir;
            var hojas = Array.prototype.slice.call(document.querySelectorAll('#piVista .hoja[data-fam="' + id + '"]'));
            var ok = function (h) { return pasaF(porId[h.dataset.v]) && (!q || h.dataset.txt.indexOf(q) >= 0); };
            var n = hojas.filter(ok).length;
            tot += n;
            cab.hidden = n === 0;
            var abierta = n > 0 && (activo || !varPleg);
            if (cab.dataset.manual) abierta = n > 0 && cab.dataset.manual === '1';
            cab.setAttribute('aria-expanded', abierta);
            document.querySelectorAll('#piVista [data-de="' + id + '"]').forEach(function (el) {
                if (el.classList.contains('hoja')) { el.hidden = !(abierta && ok(el)); return; }
                el.hidden = !abierta;
                el.querySelectorAll('.hoja').forEach(function (h) { h.hidden = !ok(h); });
                var nota = el.querySelector('.pi-filtrado');
                if (nota) { nota.hidden = !activo; nota.textContent = n + ' de ' + hojas.length + ' variantes'; }
            });
        });
        var alguna = false;
        document.querySelectorAll('#piVista .pi-cat').forEach(function (s) {
            var vis = !activo || Array.prototype.some.call(s.querySelectorAll('.hoja:not([data-fam]), [data-abrir]'), function (x) { return !x.hidden; });
            s.hidden = !vis;
            if (vis) alguna = true;
            s.classList.toggle('abierta-busqueda', activo && vis);
            if (activo && vis) s.classList.remove('plegada');
            else if (!activo) s.classList.toggle('plegada', cerradas.indexOf(s.dataset.cat) >= 0);
        });
        document.getElementById('piNada').hidden = alguna;
        document.getElementById('piCuenta').innerHTML = activo ? '<b>' + tot + '</b> de ' + todas.length : todas.length + ' variantes';
    }

    document.getElementById('piBuscar').addEventListener('input', function () {
        document.querySelectorAll('#piVista [data-manual]').forEach(function (x) { delete x.dataset.manual; });
        aplicar();
    });
    document.addEventListener('keydown', function (e) {
        var q = document.getElementById('piBuscar');
        var enCampo = /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
        if (e.key === '/' && !enCampo && !document.querySelector('.modal.show')) { e.preventDefault(); q.focus(); q.select(); }
        if (e.key === 'Escape' && document.activeElement === q) { q.value = ''; aplicar(); }
        var cab = e.target.closest && e.target.closest('[data-abrir]');
        if (cab && e.target === cab && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); cab.click(); }
    });

    document.getElementById('pi').addEventListener('click', function (e) {
        var ch = e.target.closest('[data-filtro]');
        if (ch) {
            filtro = ch.dataset.filtro;
            document.querySelectorAll('#piVista [data-manual]').forEach(function (x) { delete x.dataset.manual; });
            pintarChips(); aplicar(); return;
        }
        var bv = e.target.closest('[data-vista]');
        if (bv) { vista = bv.dataset.vista; lsSet('pi_vista', vista); pintar(); return; }
        if (e.target.closest('#piPlegarVar')) {
            varPleg = !varPleg; lsSet('pi_varpleg', varPleg ? '1' : '0');
            document.querySelectorAll('#piVista [data-manual]').forEach(function (x) { delete x.dataset.manual; });
            pintarBotones(); aplicar(); return;
        }
        var pt = e.target.closest('#piPlegarTodo');
        if (pt) {
            var abrir = pt.dataset.abrir === '1';
            cerradas = abrir ? [] : DATOS.map(function (c) { return 'c' + c.id; });
            lsSet('pi_cerradas', JSON.stringify(cerradas));
            document.querySelectorAll('#piVista .pi-cat').forEach(function (s) { s.classList.toggle('plegada', !abrir); });
            pintarBotones(); return;
        }
        if (e.target.closest('a, button, select, form, .pi-acc')) return;
        var cab = e.target.closest('[data-abrir]');
        if (cab) {
            var ab = cab.getAttribute('aria-expanded') !== 'true';
            cab.dataset.manual = ab ? '1' : '0';
            aplicar(); return;
        }
        var pc = e.target.closest('[data-plegar]');
        if (pc) {
            var s = pc.parentElement, clave = s.dataset.cat;
            var cerrar = !s.classList.contains('plegada');
            s.classList.toggle('plegada', cerrar);
            cerradas = cerradas.filter(function (x) { return x !== clave; });
            if (cerrar) cerradas.push(clave);
            lsSet('pi_cerradas', JSON.stringify(cerradas));
            pintarBotones();
        }
    });

    document.getElementById('pi').addEventListener('click', function (e) {
        var b = e.target.closest('[data-ojo]');
        if (!b) return;
        e.preventDefault();
        e.stopPropagation();
        var partes = b.dataset.ojo.split('/');
        var fd = new FormData();
        fd.append(CSRF.n, CSRF.v);
        b.disabled = true;
        fetch(B + '/' + partes[0] + '/' + partes[1] + '/visibilidad', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                b.disabled = false;
                if (!d.ok) { alert(d.mensaje || 'No se pudo cambiar.'); return; }
                var vis = !!d.visible, id = +partes[1];
                if (partes[0] === 'categoria') DATOS.forEach(function (c) { if (c.id === id) c.vis = vis; });
                if (partes[0] === 'familia') DATOS.forEach(function (c) { c.fams.forEach(function (f) { if (f.id === id) f.vis = vis; }); });
                if (partes[0] === 'variante' && porId[id]) porId[id].vis = vis;
                recalcSt();
                document.querySelectorAll('[data-ojo="' + b.dataset.ojo + '"]').forEach(function (x) {
                    x.classList.toggle('on', vis);
                    x.title = vis ? 'Ocultar de sterclicks' : 'Mostrar en sterclicks';
                    var i = x.querySelector('i');
                    i.classList.toggle('bi-eye', vis);
                    i.classList.toggle('bi-eye-slash', !vis);
                    var t = x.hasAttribute('data-con-texto') && x.querySelector('span');
                    if (t) t.textContent = vis ? 'En sterclicks' : 'Oculta en sterclicks';
                });
                pintarChips();
                if (filtro === 'oculta') aplicar();
            })
            .catch(function () { b.disabled = false; alert('No se pudo conectar con el servidor.'); });
    }, true);

    document.getElementById('pi').addEventListener('submit', function (e) {
        var f = e.target.closest('form[data-confirmar]');
        if (f && !confirm(f.dataset.confirmar)) e.preventDefault();
    });
    document.getElementById('pi').addEventListener('change', function (e) {
        if (e.target.matches('select[data-autoenvio]')) e.target.form.submit();
    });

    var ORGANIZANDO = 'piezas_organizando';
    var btnOrganizar = document.getElementById('btnOrganizar');
    function pintarOrganizar(on) {
        if (btnOrganizar) btnOrganizar.classList.toggle('active', on);
        document.getElementById('pi').classList.toggle('pi-org', on);
    }
    pintarOrganizar(ls(ORGANIZANDO, '0') === '1');
    if (btnOrganizar) {
        btnOrganizar.addEventListener('click', function () {
            var on = !btnOrganizar.classList.contains('active');
            lsSet(ORGANIZANDO, on ? '1' : '0');
            pintarOrganizar(on);
        });
    }

    var cajaSesiones = document.getElementById('sesionesActivas');
    function pintarSesiones(lista) {
        cajaSesiones.innerHTML = lista.map(function (s) {
            return '<div class="fv-banner"><span class="pi-pulso"></span><span><b>' + esc(s.maquina) + '</b> está trabajando en <a href="' + B + '/variante/' + encodeURIComponent(s.variante_id) + '">'
                + esc(s.familia) + ' / ' + esc(s.variante) + '</a></span>' + (s.dias > 0 ? '<span class="t3">· desde hace ' + (+s.dias) + ' día(s)</span>' : '') + '</div>';
        }).join('');
    }
    pintarSesiones(<?= json_encode(array_values($sesionesActivas), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>);
    setInterval(function () {
        if (document.hidden) return;
        fetch(B + '/sesiones-activas', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d) pintarSesiones(d.sesiones); })
            .catch(function () {});
    }, 20000);

    window.piRefrescarTareas = function (accion, tareas, advertencia) {
        var id = +String(accion).split('/variante/')[1].split('/')[0];
        var v = porId[id];
        if (!v) return;
        v.tareas = tareas;
        v.adv = advertencia.trim();
        v.t = tareas.split(/\r\n|\r|\n/).filter(function (l) { return l.trim() !== ''; }).length;
        pintarChips();
        pintar();
    };

    pintarChips();
    pintar();
    if (!location.hash) document.getElementById('piBuscar').focus({ preventScroll: true });
})();
</script>

<div class="modal fade" id="modalUltimoPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-cart-check"></i> Último pedido entrante
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!$ultimoPedido): ?>
                    <p class="text-muted small mb-0">Todavía no ha entrado ningún pedido.</p>
                <?php else: ?>
                    <p class="text-muted small">
                        Pedido #<?= (int) $ultimoPedido['id'] ?> · <?= esc($ultimoPedido['origen']) ?> ·
                        <?= esc($ultimoPedido['creado_en']) ?>
                        <?php if ($ultimoPedido['referencia_externa']): ?>
                            · Ref: <?= esc($ultimoPedido['referencia_externa']) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($ultimoPedido['notas']): ?>
                        <p class="small"><i class="bi bi-sticky"></i> <?= esc($ultimoPedido['notas']) ?></p>
                    <?php endif; ?>

                    <?php if (empty($ultimoPedido['lineas'])): ?>
                        <p class="text-muted small mb-0">Sin líneas todavía.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($ultimoPedido['lineas'] as $linea): ?>
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span><?= esc($linea['descripcionPieza']) ?></span>
                                        <span class="text-muted small text-nowrap">× <?= (int) $linea['cantidad'] ?></span>
                                    </div>
                                    <?php if ($linea['notas']): ?>
                                        <div class="text-muted small mt-1"><i class="bi bi-sticky"></i> <?= esc($linea['notas']) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php if ($ultimoPedido): ?>
                    <a href="<?= site_url('piezas/pedido/' . (int) $ultimoPedido['id']) ?>" class="btn btn-sm btn-primary">
                        Ir al pedido
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Vista simple de pendientes: solo el título de cada subtarea sin marcar,
 * sin los verbos "Ya existe"/"Crear pieza" — para eso está la pantalla
 * completa (piezas/pendientes), a la que lleva "Ir a pendientes".
 */
?>
<div class="modal fade" id="modalPendientes" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-list-check"></i> Pendientes de crear
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($pendientesResumen)): ?>
                    <p class="text-muted small mb-0">No queda ninguna pendiente.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendientesResumen as $p): ?>
                            <li class="list-group-item px-0"><?= esc($p['title']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="<?= site_url('piezas/pendientes') ?>" class="btn btn-sm btn-primary">
                    Ir a pendientes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Gestión de categorías: las carpetas en las que se reparten las piezas -->
<div class="modal fade" id="modalCategorias" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Categorías</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Las carpetas en las que repartir las piezas: cuerpo, accesorios, dioramas...
                    La idea es reproducir la organización que ya tienes en disco. Borrar una
                    categoría no borra sus piezas: se quedan sin clasificar, al final del listado.
                </p>

                <?php foreach ($categorias as $c): ?>
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <form method="post" class="d-flex gap-1 flex-grow-1"
                            action="<?= site_url('piezas/categoria/' . (int) $c['id'] . '/renombrar') ?>">
                            <?= csrf_field() ?>
                            <input type="text" name="nombre" value="<?= esc($c['nombre'], 'attr') ?>"
                                class="form-control form-control-sm" maxlength="100" required>
                            <button class="btn btn-sm btn-outline-primary" title="Renombrar"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <form method="post" action="<?= site_url('piezas/categoria/' . (int) $c['id'] . '/borrar') ?>"
                            onsubmit="return confirm('¿Borrar la categoría «<?= esc($c['nombre'], 'attr') ?>»? Sus piezas quedan sin clasificar.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($categorias)): ?>
                    <p class="text-muted small fst-italic">Todavía no hay ninguna.</p>
                <?php endif; ?>

                <hr>
                <form method="post" action="<?= site_url('piezas/categoria') ?>" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" class="form-control form-control-sm"
                        placeholder="Categoría nueva (cuerpo, accesorios...)" maxlength="100" required>
                    <button class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i></button>
                </form>
                <p class="text-muted small mt-2 mb-0">
                    Para cambiar el orden en que aparecen, usa las flechas del botón «Organizar».
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Pautas de promoción: checklist recordatorio que aparece antes de promocionar una variante -->
<div class="modal fade" id="modalPautas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/pautas') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pautas de promoción</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Una por línea. Aparecerán como checklist antes de promocionar una variante —
                    solo de recordatorio, marcarlas no cambia nada en la pieza.
                </p>
                <textarea name="texto" class="form-control form-control-sm" rows="6"
                    placeholder="Has metido los objetos importantes en una colección exclusiva&#10;Has cambiado los nombres de los objetos y shapekeys a mayúsculas"><?= esc($pautasTexto) ?></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Ajustes de resina: precio por litro y densidad, para el coste de resina por pieza -->
<div class="modal fade" id="modalResina" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/ajustes-resina') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-droplet"></i> Resina</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    El coste de resina de cada pieza sale de su <strong>volumen con soportes</strong>
                    (el que da Chitubox, apuntado en cada trozo) por este precio. La densidad solo
                    se usa para convertir entre volumen y peso cuando de un trozo falta uno de los dos.
                    Déjalo en blanco y solo se enseñará el volumen y el peso, sin coste.
                </p>
                <label class="form-label small" for="cfgPrecioResina">Precio por litro (€)</label>
                <input type="text" inputmode="decimal" name="precio_resina_eur_litro" id="cfgPrecioResina"
                    class="form-control form-control-sm mb-2" autocomplete="off"
                    value="<?= $resinaConfig['precioLitro'] !== null ? esc(rtrim(rtrim(number_format($resinaConfig['precioLitro'], 2, ',', ''), '0'), ','), 'attr') : '' ?>"
                    placeholder="p. ej. 35">
                <label class="form-label small" for="cfgDensidadResina">Densidad (g/mL)</label>
                <input type="text" inputmode="decimal" name="densidad_resina_g_ml" id="cfgDensidadResina"
                    class="form-control form-control-sm" autocomplete="off"
                    value="<?= esc(rtrim(rtrim(number_format($resinaConfig['densidad'], 3, ',', ''), '0'), ','), 'attr') ?>"
                    placeholder="1,1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * Calculadora de tiempo estimado de una placa a partir de su número de
 * capas. El minuto/capa no se guarda como constante suelta: sale de una
 * medición real (capas de referencia ÷ minutos de referencia), así que
 * recalibrar es reeditar esa medición. A eso se le suman siempre unos
 * minutos fijos de preparación. Todo el cálculo es en el navegador y en
 * vivo; el formulario de abajo solo persiste los tres ajustes.
 */
$calcPorCapa = $calcTiempo['minutosPorCapa'];
?>
<div class="modal fade" id="modalCalcTiempo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content"
            data-capas-ref="<?= esc($calcTiempo['capasReferencia'], 'attr') ?>"
            data-minutos-ref="<?= esc($calcTiempo['minutosReferencia'], 'attr') ?>"
            data-minutos-prep="<?= esc($calcTiempo['minutosPreparacion'], 'attr') ?>">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-stopwatch"></i> Tiempo estimado por capas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small" for="calcCapas">Número de capas de la placa</label>
                <input type="number" min="0" step="1" id="calcCapas" class="form-control form-control-sm"
                    placeholder="p. ej. 600" autocomplete="off" inputmode="numeric">
                <?php // Altura de la placa: 0,5 mm por capa (altura de capa fija del laminador). ?>
                <p class="text-muted small mb-3 mt-1" id="calcAltura"></p>

                <label class="form-label small" for="calcFin">Terminar a las</label>
                <input type="time" id="calcFin" class="form-control form-control-sm mb-3">

                <p id="calcVacio" class="text-muted small mb-0">
                    Escribe el número de capas para ver el tiempo estimado.
                </p>

                <table id="calcResultado" class="table table-sm align-middle mb-0 d-none">
                    <tbody>
                        <tr>
                            <td class="text-muted">Impresión</td>
                            <td class="text-end">
                                <span class="fw-semibold" id="calcImpresionBonito">—</span><br>
                                <span class="text-muted small" id="calcImpresionMin"></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Preparación</td>
                            <td class="text-end">
                                <span class="fw-semibold" id="calcPrepBonito">—</span><br>
                                <span class="text-muted small" id="calcPrepMin"></span>
                            </td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-semibold">Total</td>
                            <td class="text-end">
                                <span class="fw-bold fs-6" id="calcTotalBonito">—</span><br>
                                <span class="text-muted small" id="calcTotalMin"></span>
                            </td>
                        </tr>
                        <tr class="d-none" id="calcInicioFila">
                            <td class="fw-semibold">Iniciar a las</td>
                            <td class="text-end"><span class="fw-bold fs-6 text-primary" id="calcInicio">—</span></td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3">
                    <div class="small fw-semibold mb-1">Según tus placas <span class="text-muted fw-normal">(solo impresión)</span></div>
                    <table class="table table-sm align-middle small mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th class="fw-normal"></th>
                                <th class="fw-normal">Regla</th>
                                <th class="fw-normal text-end">Impresión</th>
                                <th class="fw-normal text-end calc-col-inicio d-none">Iniciar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reglasTiempo as $campo => $r): ?>
                                <tr data-regla data-por-capa="<?= esc((string) $r['porCapa'], 'attr') ?>">
                                    <td><?= esc($r['etiqueta']) ?></td>
                                    <td class="text-muted">
                                        <?php if ($r['porCapa'] === null): ?>
                                            sin datos
                                        <?php else: ?>
                                            <?= number_format($r['porCapa'], 4, ',', '.') ?> min/capa
                                            <br><span class="small"><?= $r['n'] ?> placa<?= $r['n'] === 1 ? '' : 's' ?></span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end fw-semibold" data-total>—</td>
                                    <td class="text-end calc-col-inicio d-none" data-inicio>—</td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

                <hr>

                <?php // Los tres números de calibración están plegados: se
                      // miran/tocan de uvas a peras, el resto del tiempo solo
                      // estorban al cálculo. ?>
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none collapsed" id="calcAjustesToggle"
                    data-bs-toggle="collapse" data-bs-target="#calcAjustes" aria-expanded="false" aria-controls="calcAjustes">
                    <i class="bi bi-chevron-right"></i> Ajustes
                    <span class="text-muted">(<span id="calcRefTexto"><?= (int) $calcTiempo['capasReferencia'] ?> capas =
                    <?= rtrim(rtrim(number_format($calcTiempo['minutosReferencia'], 2, ',', '.'), '0'), ',') ?> min</span>
                    &rarr; <span id="calcPorCapaTexto"><?= number_format($calcPorCapa, 4, ',', '.') ?></span> min/capa)</span>
                </button>

                <form method="post" action="<?= site_url('piezas/calculadora-tiempo') ?>" class="collapse mt-2" id="calcAjustes">
                    <?= csrf_field() ?>
                    <p class="text-muted small mb-2">
                        La referencia fija cuánto tarda cada capa; a partir de ahí se estima la impresión.
                        Los minutos de preparación se suman siempre.
                    </p>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label small" for="cfgCapasRef">Capas de referencia</label>
                            <input type="number" min="1" step="1" name="capas_referencia" id="cfgCapasRef"
                                class="form-control form-control-sm" value="<?= (int) $calcTiempo['capasReferencia'] ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label small" for="cfgMinRef">Minutos de referencia</label>
                            <input type="number" min="0" step="0.01" name="minutos_referencia" id="cfgMinRef"
                                class="form-control form-control-sm" value="<?= rtrim(rtrim(number_format($calcTiempo['minutosReferencia'], 2, '.', ''), '0'), '.') ?>" required>
                        </div>
                    </div>
                    <label class="form-label small mt-2" for="cfgMinPrep">Minutos de preparación (fijos)</label>
                    <input type="number" min="0" step="0.01" name="minutos_preparacion" id="cfgMinPrep"
                        class="form-control form-control-sm" value="<?= rtrim(rtrim(number_format($calcTiempo['minutosPreparacion'], 2, '.', ''), '0'), '.') ?>" required>
                    <div class="text-end mt-3">
                        <button class="btn btn-sm btn-primary">Guardar ajustes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alta de pieza (en el esquema, "familia": ver la nota de vocabulario en SPEC.md) -->
<div class="modal fade" id="modalFamilia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/familia') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pieza nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="pincel, casco, silla..." maxlength="150" required>
                <label class="form-label small">Categoría</label>
                <select name="categoria_id" class="form-select form-select-sm mb-2">
                    <option value="">— sin clasificar —</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= esc($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace lista para trabajar, con numeración propia desde v001 y su SKU asignado
                    solo. Solo hace falta añadir variantes si esta pieza acaba teniendo varias
                    líneas de diseño.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-success">Crear</button>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * Tareas y advertencia de una pieza. Compartido por todas las filas: el
 * botón que lo abre (ver $botonTareas) trae en sus data-* la acción del
 * formulario y lo que ya está escrito, y el JS rellena el modal en
 * `show.bs.modal`. Al guardar se manda por AJAX (el controlador responde
 * JSON a las peticiones XHR) y se actualizan en el sitio el botón —color,
 * contador y data-*— y el triángulo de advertencia de la fila, sin recargar
 * ni perder la posición del scroll.
 */
?>
<div class="modal fade" id="modalTareas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="formTareas">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-card-checklist"></i> Tareas — <span data-nombre-tareas class="fw-normal text-muted"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small mb-1" for="campoAdvertencia">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="opacity: .6;"></i> Advertencia
                </label>
                <input type="text" id="campoAdvertencia" name="advertencia" maxlength="255"
                    class="form-control form-control-sm" data-advertencia-tareas
                    placeholder="Funciona y sirve, pero no es perfecta: qué falla">
                <p class="text-muted small mt-1">
                    Sale como un triángulo amarillo junto al número de versión en el índice.
                    Déjalo en blanco si no hay nada que advertir.
                </p>

                <label class="form-label small mb-1" for="campoTareas">Tareas pendientes</label>
                <textarea id="campoTareas" name="tareas" rows="6" class="form-control form-control-sm" data-tareas-tareas
                    placeholder="Una por línea:&#10;rehacer los soportes de la base&#10;bajar la escala un 5%&#10;revisar el grosor de las paredes"></textarea>
                <p class="text-muted small mb-0">Una tarea por línea. El icono del índice muestra cuántas quedan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>


<script>
(function () {
    // ---- Modal de tareas / advertencia --------------------------------
    // Uno solo para todas las filas: el botón que lo abre trae en sus
    // data-* la acción del formulario y lo que ya está escrito. Va antes
    // del posible "return" de abajo (cuando no hay piezas) — sin piezas no
    // hay botones que lo abran, pero así el bloque no depende de eso.
    var modalTareas = document.getElementById('modalTareas');
    if (modalTareas) {
        var formTareas = document.getElementById('formTareas');
        modalTareas.addEventListener('show.bs.modal', function (e) {
            var boton = e.relatedTarget;
            if (!boton) return;
            formTareas.setAttribute('action', boton.getAttribute('data-accion') || '');
            modalTareas.querySelector('[data-nombre-tareas]').textContent = boton.getAttribute('data-nombre') || '';
            modalTareas.querySelector('[data-advertencia-tareas]').value = boton.getAttribute('data-advertencia') || '';
            modalTareas.querySelector('[data-tareas-tareas]').value = boton.getAttribute('data-tareas') || '';
        });
        modalTareas.addEventListener('shown.bs.modal', function () {
            var campo = modalTareas.querySelector('[data-tareas-tareas]');
            if (campo) campo.focus();
        });

        // Guardar sin recargar: al recargar, el navegador salta al principio
        // de la página y se pierde de vista la pieza que se estaba tocando.
        // El controlador ya responde JSON a las peticiones XHR (ver
        // Web::ejecutar); aquí solo se refleja el resultado en el sitio.
        var btnGuardarTareas = formTareas.querySelector('.modal-footer .btn-primary');

        formTareas.addEventListener('submit', function (e) {
            e.preventDefault();
            var accion = formTareas.getAttribute('action');
            if (!accion) return;

            btnGuardarTareas.disabled = true;

            fetch(accion, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(formTareas)
            })
                .then(function (resp) { return resp.json(); })
                .then(function (datos) {
                    btnGuardarTareas.disabled = false;
                    if (!datos || !datos.ok) {
                        alert((datos && datos.mensaje) || 'No se pudo guardar.');
                        return;
                    }

                    var tareas = (datos.tareas == null ? '' : String(datos.tareas));
                    var advertencia = (datos.advertencia == null ? '' : String(datos.advertencia));
                    var n = tareas.split(/\r\n|\r|\n/)
                        .map(function (l) { return l.trim(); })
                        .filter(function (l) { return l !== ''; }).length;
                    var conAlgo = n > 0 || advertencia !== '';

                    if (window.piRefrescarTareas) window.piRefrescarTareas(accion, tareas, advertencia);

                    bootstrap.Modal.getOrCreateInstance(modalTareas).hide();
                })
                .catch(function () {
                    btnGuardarTareas.disabled = false;
                    alert('No se pudo conectar con el servidor.');
                });
        });
    }

    // ---- Calculadora de tiempo estimado por capas ---------------------
    // Todo en vivo y en el navegador: al teclear capas (o al retocar los
    // ajustes, aunque aún no se hayan guardado) se recalcula. El
    // minuto/capa sale de la referencia, nunca es una constante suelta.
    var calcModal = document.getElementById('modalCalcTiempo');
    if (calcModal) {
        var calcCaja      = calcModal.querySelector('.modal-content');
        var campoCapas    = document.getElementById('calcCapas');
        var campoCapasRef = document.getElementById('cfgCapasRef');
        var campoMinRef   = document.getElementById('cfgMinRef');
        var campoMinPrep  = document.getElementById('cfgMinPrep');

        var elVacio      = document.getElementById('calcVacio');
        var elTabla      = document.getElementById('calcResultado');
        var elImpBonito  = document.getElementById('calcImpresionBonito');
        var elImpMin     = document.getElementById('calcImpresionMin');
        var elPrepBonito = document.getElementById('calcPrepBonito');
        var elPrepMin    = document.getElementById('calcPrepMin');
        var elTotBonito  = document.getElementById('calcTotalBonito');
        var elTotMin     = document.getElementById('calcTotalMin');
        var elRefTexto   = document.getElementById('calcRefTexto');
        var elPorCapa    = document.getElementById('calcPorCapaTexto');
        var elAltura     = document.getElementById('calcAltura');
        var campoFin     = document.getElementById('calcFin');
        var elInicioFila = document.getElementById('calcInicioFila');
        var elInicio     = document.getElementById('calcInicio');
        var filasRegla   = calcModal.querySelectorAll('[data-regla]');
        var colsInicio   = calcModal.querySelectorAll('.calc-col-inicio');

        // Altura de capa fija del laminador, para pasar capas -> cm.
        var MM_POR_CAPA = 0.05;

        // Número con coma decimal a la española y sin ceros de relleno.
        function nEs(n, decs) {
            var s = n.toLocaleString('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: decs });
            return s;
        }

        // Minutos -> "1 d 2 h 30 min", quitando los tramos a cero.
        function bonito(mins) {
            mins = Math.round(mins);
            var d = Math.floor(mins / 1440);
            var h = Math.floor((mins % 1440) / 60);
            var m = mins % 60;
            var partes = [];
            if (d) partes.push(d + ' d');
            if (h) partes.push(h + ' h');
            if (m || !partes.length) partes.push(m + ' min');
            return partes.join(' ');
        }

        // Hora de fin (HH:MM) menos los minutos; si ya pasó hoy, es mañana.
        function inicioPara(mins) {
            var p = campoFin.value.split(':');
            var fin = new Date();
            fin.setHours(+p[0], +p[1], 0, 0);
            if (fin <= new Date()) fin.setDate(fin.getDate() + 1);
            var ini = new Date(fin.getTime() - Math.round(mins) * 60000);
            var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
            var dias = Math.round((new Date(ini).setHours(0, 0, 0, 0) - hoy) / 86400000);
            var hora = ini.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            var cuando = dias === 0 ? 'hoy' : dias === 1 ? 'mañana' : dias === -1 ? 'ayer' : ini.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
            return hora + ' (' + cuando + ')' + (ini < new Date() ? ' · ya no llegas' : '');
        }

        function leer(campo, porDefectoAttr) {
            var v = parseFloat(String(campo.value).replace(',', '.'));
            if (isFinite(v) && v >= 0) return v;
            return parseFloat(calcCaja.getAttribute(porDefectoAttr)) || 0;
        }

        function recalcular() {
            var capasRef = leer(campoCapasRef, 'data-capas-ref');
            var minRef   = leer(campoMinRef, 'data-minutos-ref');
            var minPrep  = leer(campoMinPrep, 'data-minutos-prep');
            var porCapa  = capasRef > 0 ? minRef / capasRef : 0;

            elRefTexto.textContent = nEs(capasRef, 0) + ' capas = ' + nEs(minRef, 2) + ' min';
            elPorCapa.textContent  = nEs(porCapa, 4);

            var capas = parseFloat(String(campoCapas.value).replace(',', '.'));
            var conFin = campoFin.value !== '';
            colsInicio.forEach(function (c) { c.classList.toggle('d-none', !conFin); });

            if (!isFinite(capas) || capas <= 0) {
                filasRegla.forEach(function (f) {
                    f.querySelector('[data-total]').textContent = '—';
                    f.querySelector('[data-inicio]').textContent = '—';
                });
                elInicioFila.classList.add('d-none');
                elAltura.textContent = '';
                elTabla.classList.add('d-none');
                elVacio.classList.remove('d-none');
                return;
            }

            elAltura.textContent = '≈ ' + nEs(capas * MM_POR_CAPA / 10, 1) + ' cm de alto (' + nEs(MM_POR_CAPA, 2) + ' mm/capa)';

            var impresion = capas * porCapa;
            var total     = impresion + minPrep;

            elImpBonito.textContent  = bonito(impresion);
            elImpMin.textContent     = nEs(impresion, 1) + ' min · ' + nEs(capas, 0) + ' capas × ' + nEs(porCapa, 4);
            elPrepBonito.textContent = bonito(minPrep);
            elPrepMin.textContent    = nEs(minPrep, 1) + ' min fijos';
            elTotBonito.textContent  = bonito(total);
            elTotMin.textContent     = nEs(total, 1) + ' min';

            elInicioFila.classList.toggle('d-none', !conFin);
            if (conFin) elInicio.textContent = inicioPara(impresion);

            filasRegla.forEach(function (f) {
                var pc = parseFloat(f.getAttribute('data-por-capa'));
                var tot = f.querySelector('[data-total]');
                var ini = f.querySelector('[data-inicio]');
                if (!isFinite(pc)) { tot.textContent = '—'; ini.textContent = '—'; return; }
                var t = pc * capas;
                tot.textContent = bonito(t);
                ini.textContent = conFin ? inicioPara(t) : '—';
            });

            elVacio.classList.add('d-none');
            elTabla.classList.remove('d-none');
        }

        [campoCapas, campoCapasRef, campoMinRef, campoMinPrep, campoFin].forEach(function (c) {
            c.addEventListener('input', recalcular);
        });
        calcModal.addEventListener('shown.bs.modal', function () {
            campoCapas.focus();
            recalcular();
        });

        // Chevron del plegable de ajustes: apunta a la derecha plegado, abajo abierto.
        var ajustes = document.getElementById('calcAjustes');
        var ajustesIcono = document.querySelector('#calcAjustesToggle i');
        if (ajustes && ajustesIcono) {
            ajustes.addEventListener('show.bs.collapse', function () {
                ajustesIcono.classList.replace('bi-chevron-right', 'bi-chevron-down');
            });
            ajustes.addEventListener('hide.bs.collapse', function () {
                ajustesIcono.classList.replace('bi-chevron-down', 'bi-chevron-right');
            });
        }
    }
})();
</script>

<script>
window.addEventListener('load', function () {
    var h = location.hash;
    if (h === '#organizar') {
        var b = document.getElementById('btnOrganizar');
        if (b) b.click();
    } else if (/^#modal\w+$/.test(h) && document.querySelector(h)) {
        bootstrap.Modal.getOrCreateInstance(document.querySelector(h)).show();
    }
    if (h) history.replaceState(null, '', location.pathname + location.search);
});
</script>

<?= $this->endSection() ?>
