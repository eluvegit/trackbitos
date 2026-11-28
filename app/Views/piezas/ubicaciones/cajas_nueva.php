<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    helper('piezas_stock');
    $titulos = [
        'impresas' => ['Caja de impresas', 'bi-box-seam', 'Recién impresas, pendientes de guardar en su hueco.'],
        'pedido'   => ['Caja de pedido', 'bi-bag', 'Apartadas para lo que ha pedido Esther: se las llevan o vuelven a su hueco.'],
    ];
?>

<div class="fv pv cj">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/ubicaciones') ?>">Estuches</a><i class="bi bi-chevron-right"></i><span>Impresas y de pedido</span></nav>
        <div class="fv-acciones">
            <div class="cj-vistas" role="group" aria-label="Cómo ver las cajas">
                <button type="button" class="fv-gbtn" data-cj-vista="lista" title="Ver en lista"><i class="bi bi-list-ul"></i> Lista</button>
                <button type="button" class="fv-gbtn" data-cj-vista="rejilla" title="Ver las fotos en grande"><i class="bi bi-grid-3x3-gap"></i> Rejilla</button>
            </div>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/salidas') ?>" class="fv-gbtn" title="Albaranes de salida"><i class="bi bi-receipt"></i> Albaranes</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/ubicaciones/cajas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Impresas y de pedido</h1>
        <div class="fv-meta">
            <?php foreach ($cajas as $caja => $filas): ?>
                <span><b><?= (int) array_sum(array_column($filas, 'cantidad')) ?></b> ud. en la <?= esc(mb_strtolower($titulos[$caja][0])) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php // Buscador en vivo: filtra las filas de las dos cajas a la vez
          // por nombre, SKU o hueco. Solo esconde; no recarga la página. ?>
    <?php $totalFilas = array_sum(array_map('count', $cajas)); ?>
    <?php if ($totalFilas > 0): ?>
        <div class="cj-busca">
            <label class="cj-buscar">
                <i class="bi bi-search"></i>
                <input type="search" id="cjBuscar" placeholder="Buscar en las cajas por nombre, SKU o hueco" autocomplete="off" aria-label="Buscar en las cajas">
                <span class="cj-cuenta" id="cjCuenta"><b><?= (int) $totalFilas ?></b> piezas</span>
            </label>
        </div>
    <?php endif; ?>

    <?php if ($negativas > 0): ?>
        <div class="fv-aviso">
            <i class="bi bi-exclamation-triangle"></i>
            <span><b><?= $negativas ?> pieza<?= $negativas === 1 ? '' : 's' ?> descuadrada<?= $negativas === 1 ? '' : 's' ?></b>: en negativo en una caja o en su hueco mientras quedan en otro sitio. Al cuadrar, se pasan de donde sobran a donde faltan (el total no cambia).</span>
            <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/cuadrar') ?>" onsubmit="return confirm('¿Cuadrar las piezas descuadradas entre su hueco y las cajas?');">
                <?= csrf_field() ?>
                <button type="submit" class="fv-btn fv-btn-sm"><i class="bi bi-check2-square"></i> Cuadrar</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="es-graficos">
        <?php foreach ($cajas as $caja => $filas): ?>
            <?php
                [$titulo, $icono, $ayuda] = $titulos[$caja];
                $conHueco = count(array_filter($filas, static fn ($f) => $f['hueco'] !== null && $f['cantidad'] > 0));
            ?>
            <section class="fv-panel">
                <div class="fv-ph">
                    <i class="bi <?= $icono ?> t3"></i><h3><?= esc($titulo) ?></h3><span class="cuenta"><?= (int) array_sum(array_column($filas, 'cantidad')) ?> ud. en esta caja</span>
                    <?php if ($conHueco > 0): ?>
                        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar-todo') ?>" class="der"
                            onsubmit="return confirm('¿Guardar en su hueco todo lo de la <?= esc(mb_strtolower($titulo), 'js') ?> que ya tiene hueco?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                            <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri"><i class="bi bi-arrow-down-square"></i> Guardar todo</button>
                        </form>
                    <?php endif; ?>
                </div>
                <p class="es-ayuda"><?= esc($ayuda) ?></p>
                <?php if ($filas === []): ?>
                    <p class="fv-vacio">Vacía.</p>
                <?php else: ?>
                    <div class="pv-lista">
                        <?php foreach ($filas as $f): ?>
                            <?php $buscable = mb_strtolower($f['nombre'] . ' ' . ($f['sku'] ?? '') . ' ' . ($f['hueco'] ?? 'sin hueco')); ?>
                            <div class="pv-fila cj-fila" data-buscar="<?= esc($buscable, 'attr') ?>">
                                <span class="pv-ico"><?php if ($f['imagen']): ?><img src="<?= esc($f['imagen'], 'attr') ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-image"></i><?php endif; ?></span>
                                <div class="pv-cuerpo">
                                    <a href="<?= site_url('piezas/existencias/' . (int) $f['id']) ?>" class="pv-nom"><?= esc($f['nombre']) ?></a>
                                    <div class="pv-sub">
                                        <?php if ($f['hueco'] !== null): ?>
                                            <span><i class="bi bi-geo-alt"></i> <span class="fv-mono"><?= esc($f['hueco']) ?></span></span>
                                        <?php else: ?>
                                            <span class="fv-estado e-warn">Sin hueco</span>
                                        <?php endif; ?>
                                        <?= stock_desglose($f['desglose'], $caja, $f['hueco'] !== null ? 'en hueco' : 'sin ubicación') ?>
                                    </div>
                                </div>
                                <span class="pv-n<?= $f['cantidad'] < 0 ? ' e-bad' : '' ?>" title="<?= $f['cantidad'] < 0 ? 'Han salido más de las que entraron en esta caja' : 'Total de la pieza; en esta caja hay ' . (int) $f['cantidad'] ?>"><?= (int) $f['desglose']['total'] ?><small>ud.</small></span>
                                <?php if ($f['cantidad'] > 0): ?>
                                    <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar') ?>" class="cj-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                                        <input type="hidden" name="variante_id" value="<?= (int) $f['id'] ?>">
                                        <input type="number" name="cantidad" value="<?= (int) $f['cantidad'] ?>" min="1" max="<?= (int) $f['cantidad'] ?>" class="fv-input" title="Cuántas">
                                        <?php if ($f['hueco'] === null): ?>
                                            <select name="hueco_id" class="fv-input hueco" required data-hueco data-hueco-para="<?= esc($f['nombre'], 'attr') ?>">
                                                <option value="">Hueco…</option>
                                                <?php foreach ($huecos as $hid => $codigo): ?>
                                                    <option value="<?= (int) $hid ?>"><?= esc($codigo) ?><?= isset($huecosLibres[$hid]) ? ' (libre)' : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                        <button type="submit" class="fv-ibtn" title="Guardar en su hueco"><i class="bi bi-arrow-down-square"></i></button>
                                        <button type="submit" formaction="<?= site_url('piezas/ubicaciones/cajas/cambiar') ?>" formnovalidate class="fv-ibtn" title="Pasar a la caja de <?= $caja === 'impresas' ? 'pedido' : 'impresas' ?>"><i class="bi <?= $caja === 'impresas' ? 'bi-bag' : 'bi-box-seam' ?>"></i></button>
                                        <button type="button" class="fv-ibtn" title="Dar de baja de esta caja (rotura, descuadre…)"<?= stock_mov_attrs((int) $f['id'], $f['nombre'], $f['desglose'], 'baja', $caja, $f['hueco'] !== null) ?>><i class="bi bi-dash-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php // Rejilla: mismas piezas, foto en grande. Las acciones
                          // (guardar, cambiar de caja, baja) siguen en la lista. ?>
                    <div class="cj-rejilla">
                        <?php foreach ($filas as $f): ?>
                            <?php $buscable = mb_strtolower($f['nombre'] . ' ' . ($f['sku'] ?? '') . ' ' . ($f['hueco'] ?? 'sin hueco')); ?>
                            <div class="cj-tesela cj-fila" data-buscar="<?= esc($buscable, 'attr') ?>">
                                <?php if ($f['imagen']): ?>
                                    <button type="button" class="cj-foto" data-cj-grande="<?= esc($f['imagen_grande'] ?? $f['imagen'], 'attr') ?>" data-cj-titulo="<?= esc($f['nombre'], 'attr') ?>" title="Ver en grande">
                                        <img src="<?= esc($f['imagen'], 'attr') ?>" alt="<?= esc($f['nombre'], 'attr') ?>" loading="lazy">
                                    </button>
                                <?php else: ?>
                                    <span class="cj-foto cj-foto-sin"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                                <span class="cj-n<?= $f['cantidad'] < 0 ? ' e-bad' : '' ?>" title="En esta caja"><?= (int) $f['cantidad'] ?><small>ud.</small></span>
                                <div class="cj-pie">
                                    <a href="<?= site_url('piezas/existencias/' . (int) $f['id']) ?>" class="cj-nom"><?= esc($f['nombre']) ?></a>
                                    <?php if ($f['hueco'] !== null): ?>
                                        <span class="cj-hueco"><i class="bi bi-geo-alt"></i> <span class="fv-mono"><?= esc($f['hueco']) ?></span></span>
                                    <?php else: ?>
                                        <span class="fv-estado e-warn">Sin hueco</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($f['cantidad'] > 0): ?>
                                    <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar') ?>" class="cj-form cj-acc">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="caja" value="<?= esc($caja, 'attr') ?>">
                                        <input type="hidden" name="variante_id" value="<?= (int) $f['id'] ?>">
                                        <?php if ($f['hueco'] === null): ?>
                                            <select name="hueco_id" class="fv-input hueco" required data-hueco data-hueco-para="<?= esc($f['nombre'], 'attr') ?>">
                                                <option value="">Hueco…</option>
                                                <?php foreach ($huecos as $hid => $codigo): ?>
                                                    <option value="<?= (int) $hid ?>"><?= esc($codigo) ?><?= isset($huecosLibres[$hid]) ? ' (libre)' : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                        <div class="cj-acc-fila">
                                            <input type="number" name="cantidad" value="<?= (int) $f['cantidad'] ?>" min="1" max="<?= (int) $f['cantidad'] ?>" class="fv-input" title="Cuántas">
                                            <button type="submit" class="fv-ibtn" title="Guardar en su hueco"><i class="bi bi-arrow-down-square"></i></button>
                                            <button type="submit" formaction="<?= site_url('piezas/ubicaciones/cajas/cambiar') ?>" formnovalidate class="fv-ibtn" title="Pasar a la caja de <?= $caja === 'impresas' ? 'pedido' : 'impresas' ?>"><i class="bi <?= $caja === 'impresas' ? 'bi-bag' : 'bi-box-seam' ?>"></i></button>
                                            <button type="button" class="fv-ibtn" title="Dar de baja de esta caja (rotura, descuadre…)"<?= stock_mov_attrs((int) $f['id'], $f['nombre'], $f['desglose'], 'baja', $caja, $f['hueco'] !== null) ?>><i class="bi bi-dash-lg"></i></button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="fv-vacio d-none" data-cj-sin>Nada de esta caja coincide con la búsqueda.</p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->include('piezas/_modal_mov_stock') ?>

<div class="cj-visor" id="cjVisor" hidden>
    <button type="button" class="cj-visor-cerrar" data-cj-cerrar title="Cerrar (Esc)"><i class="bi bi-x-lg"></i></button>
    <button type="button" class="cj-visor-nav ant" data-cj-paso="-1" title="Anterior (←)"><i class="bi bi-chevron-left"></i></button>
    <figure>
        <img id="cjVisorImg" alt="">
        <figcaption id="cjVisorTit"></figcaption>
    </figure>
    <button type="button" class="cj-visor-nav sig" data-cj-paso="1" title="Siguiente (→)"><i class="bi bi-chevron-right"></i></button>
</div>

<style>
    .cj-busca { position: sticky; top: 0; z-index: 8; background: var(--fv-bg); padding: 8px 0 12px; margin: -8px 0 4px; }
    .cj-buscar { display: flex; align-items: center; gap: 12px; height: 50px; padding: 0 16px; margin: 0; border-radius: 14px; background: var(--fv-panel); border: 1px solid var(--fv-border-2); cursor: text; transition: border-color .15s, box-shadow .15s; }
    .cj-buscar:focus-within { border-color: rgb(255 106 61 / .55); box-shadow: 0 0 0 4px rgb(255 106 61 / .14); }
    .cj-buscar > .bi { font-size: 18px; color: var(--fv-text-3); }
    .cj-buscar:focus-within > .bi { color: var(--fv-accent); }
    .cj-buscar input { flex: 1; min-width: 0; background: none !important; border: 0; outline: 0; box-shadow: none; color: var(--fv-text); font-size: 16px; }
    .cj-buscar input::placeholder { color: var(--fv-text-3); }
    .cj-cuenta { font-size: 12.5px; color: var(--fv-text-3); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .cj-cuenta b { color: var(--fv-text); font-weight: 500; }

    /* Lista / Rejilla: el atributo data-vista del contenedor elige. */
    .cj-vistas { display: inline-flex; gap: 4px; }
    .cj-vistas .fv-gbtn.on { color: var(--fv-accent); border-color: rgb(255 106 61 / .45); background: rgb(255 106 61 / .1); }
    .cj .cj-rejilla { display: none; }
    .cj[data-vista="rejilla"] .pv-lista { display: none; }
    .cj[data-vista="rejilla"] .cj-rejilla { display: grid; }
    .cj[data-vista="rejilla"] .es-graficos { grid-template-columns: 1fr; }

    .cj-rejilla { grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; padding: 4px 2px 6px; }
    @media (max-width: 1200px) { .cj-rejilla { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 900px)  { .cj-rejilla { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .cj-tesela { position: relative; display: flex; flex-direction: column; border-radius: 14px; overflow: hidden; background: var(--fv-panel); border: 1px solid var(--fv-border-2); }
    .cj-tesela.d-none { display: none !important; }
    .cj-foto { display: block; width: 100%; aspect-ratio: 1; padding: 0; border: 0; background: rgb(255 255 255 / .03); cursor: zoom-in; }
    .cj-foto img { width: 100%; height: 100%; object-fit: contain; display: block; transition: transform .2s; }
    .cj-foto:hover img { transform: scale(1.04); }
    .cj-foto-sin { display: flex; align-items: center; justify-content: center; font-size: 42px; color: var(--fv-text-3); cursor: default; }
    .cj-n { position: absolute; top: 10px; right: 10px; padding: 3px 10px; border-radius: 999px; background: rgb(0 0 0 / .65); color: #fff; font-weight: 600; font-variant-numeric: tabular-nums; }
    .cj-n small { margin-left: 3px; font-weight: 400; opacity: .75; }
    .cj-n.e-bad { background: rgb(200 40 40 / .85); }
    .cj-pie { display: flex; flex-direction: column; gap: 3px; padding: 8px 10px 10px; font-size: 13px; }
    .cj-nom { color: var(--fv-text); font-weight: 500; text-decoration: none; line-height: 1.25; }
    .cj-nom:hover { color: var(--fv-accent); }
    .cj-hueco { font-size: 12.5px; color: var(--fv-text-3); }
    .cj-acc { display: flex; flex-direction: column; gap: 6px; margin-top: auto; padding: 0 10px 10px; }
    /* En columna, el flex-basis de .cj-form .hueco (120px) se volvía altura. */
    .cj-form.cj-acc .fv-input.hueco { flex: 0 0 auto; width: 100%; height: 30px; }
    .cj-acc-fila { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
    .cj-form.cj-acc .cj-acc-fila input[type="number"] { flex: 0 0 50px; width: 50px; }
    @media (max-width: 575px) { .cj-rejilla { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; } }

    .cj-visor { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: center; justify-content: center; background: rgb(0 0 0 / .9); }
    .cj-visor[hidden] { display: none; }
    .cj-visor figure { margin: 0; max-width: 92vw; max-height: 92vh; display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .cj-visor img { max-width: 92vw; max-height: 84vh; object-fit: contain; }
    .cj-visor figcaption { color: #eee; font-size: 15px; text-align: center; }
    .cj-visor-cerrar, .cj-visor-nav { position: absolute; border: 0; background: rgb(255 255 255 / .12); color: #fff; width: 46px; height: 46px; border-radius: 50%; font-size: 20px; }
    .cj-visor-cerrar { top: 16px; right: 16px; }
    .cj-visor-nav { top: 50%; transform: translateY(-50%); }
    .cj-visor-nav.ant { left: 16px; }
    .cj-visor-nav.sig { right: 16px; }
</style>
<script>
(function () {
    var input = document.getElementById('cjBuscar');
    if (!input) return;
    // Filtra lista y rejilla a la vez; cuenta solo las de la lista (son las mismas).
    var filas  = document.querySelectorAll('.cj-fila[data-buscar]');
    var total  = document.querySelectorAll('.pv-lista .cj-fila[data-buscar]').length;
    var cuenta = document.getElementById('cjCuenta');
    // Sin tildes ni mayúsculas: "munyeco" no, pero "brazo" encuentra "Brazo".
    var plano  = function (t) { return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };

    function aplicar() {
        var terminos = plano(input.value.trim()).split(/\s+/).filter(Boolean);
        var visibles = 0;
        filas.forEach(function (f) {
            var texto = plano(f.dataset.buscar || '');
            var ok = terminos.every(function (t) { return texto.indexOf(t) !== -1; });
            f.classList.toggle('d-none', !ok);
            if (ok && f.closest('.pv-lista')) visibles++;
        });
        // Aviso por caja cuando la búsqueda la deja sin filas.
        document.querySelectorAll('.fv-panel').forEach(function (p) {
            var sin = p.querySelector('[data-cj-sin]');
            if (!sin) return;
            var alguna = p.querySelector('.cj-fila:not(.d-none)');
            sin.classList.toggle('d-none', !!alguna || !terminos.length);
        });
        cuenta.innerHTML = terminos.length ? '<b>' + visibles + '</b> de ' + total : '<b>' + total + '</b> piezas';
    }

    input.addEventListener('input', aplicar);
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { input.value = ''; aplicar(); } });
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && !e.target.closest('input, textarea, select')) { e.preventDefault(); input.focus(); }
    });
})();

(function () {
    // Lista / Rejilla, recordado en este navegador.
    var raiz = document.querySelector('.fv.cj');
    var botones = document.querySelectorAll('[data-cj-vista]');
    function poner(v) {
        raiz.dataset.vista = v;
        botones.forEach(function (b) { b.classList.toggle('on', b.dataset.cjVista === v); });
        try { localStorage.setItem('piezas_cajas_vista', v); } catch (e) {}
    }
    var guardada = 'lista';
    try { guardada = localStorage.getItem('piezas_cajas_vista') || 'lista'; } catch (e) {}
    poner(guardada === 'rejilla' ? 'rejilla' : 'lista');
    botones.forEach(function (b) { b.addEventListener('click', function () { poner(b.dataset.cjVista); }); });

    // Visor: foto grande, con ← → entre las fotos visibles de la rejilla.
    var visor = document.getElementById('cjVisor');
    var img = document.getElementById('cjVisorImg');
    var tit = document.getElementById('cjVisorTit');
    var lista = [], i = 0;
    function mostrar() {
        var b = lista[i];
        img.src = b.dataset.cjGrande;
        img.alt = tit.textContent = b.dataset.cjTitulo || '';
    }
    function abrir(b) {
        lista = Array.prototype.filter.call(document.querySelectorAll('.cj-rejilla [data-cj-grande]'),
            function (x) { return !x.closest('.cj-fila.d-none'); });
        i = Math.max(0, lista.indexOf(b));
        mostrar();
        visor.hidden = false;
    }
    function cerrar() { visor.hidden = true; img.removeAttribute('src'); }
    function paso(n) { if (!lista.length) return; i = (i + n + lista.length) % lista.length; mostrar(); }

    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-cj-grande]');
        if (b) { abrir(b); return; }
        if (visor.hidden) return;
        var p = e.target.closest('[data-cj-paso]');
        if (p) { paso(parseInt(p.dataset.cjPaso, 10)); return; }
        if (e.target.closest('[data-cj-cerrar]') || e.target === visor) cerrar();
    });
    document.addEventListener('keydown', function (e) {
        if (visor.hidden) return;
        if (e.key === 'Escape') { e.stopPropagation(); cerrar(); }
        else if (e.key === 'ArrowLeft') paso(-1);
        else if (e.key === 'ArrowRight') paso(1);
    }, true);
})();
</script>

<?= $this->endSection() ?>
