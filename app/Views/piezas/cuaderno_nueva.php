<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-index.css') ?>?v=6">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
use App\Models\PiezaPlacaModel;

$tonoVeredicto = ['buena' => 'e-ok', 'regular' => 'e-warn', 'repetir' => 'e-bad'];
$fecha = static fn (?string $f) => $f ? date('d/m/Y', strtotime($f)) : '';
$texto = static fn (string $t) => nl2br(esc($t));
$secciones = [
    'pruebas'      => ['Preguntas', 'bi-question-circle'],
    'conclusiones' => ['Conclusiones', 'bi-lightbulb'],
    'notas'        => ['Notas', 'bi-sticky'],
    'piezas'       => ['Piezas', 'bi-puzzle'],
    'fotos'        => ['Fotos', 'bi-image'],
];
?>

<div class="fv pv pi cu">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Cuaderno</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn" title="Histórico de placas"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/cuaderno?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Cuaderno</h1>
        <div class="fv-meta">
            <span><b><?= count($entradas) ?></b> placa<?= count($entradas) === 1 ? '' : 's' ?> con anotaciones</span>
            <?php if ($pendientes !== []): ?>
                <span class="fv-dot"></span>
                <span class="fv-estado e-warn"><?= count($pendientes) ?> pregunta<?= count($pendientes) === 1 ? '' : 's' ?> sin responder</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="cu-barra">
        <div class="pi-busca">
            <label class="pi-buscar">
                <i class="bi bi-search"></i>
                <input type="search" id="cuQ" placeholder="Buscar en el cuaderno (p. ej. soportes)" autocomplete="off">
                <span class="pi-cuenta" id="cuCuenta"></span>
            </label>
        </div>
        <div class="pi-toolbar">
            <div class="pv-chips" id="cuSecciones" role="group" aria-label="Qué mostrar">
                <button type="button" class="pv-chip" data-todo>Todo</button>
                <?php foreach ($secciones as $s => [$et, $ico]): ?>
                    <button type="button" class="pv-chip" data-sec-btn="<?= $s ?>" aria-pressed="true"><i class="bi <?= $ico ?>"></i> <?= $et ?></button>
                <?php endforeach; ?>
            </div>
            <div class="pi-der">
                <select id="cuVeredicto" class="fv-input pv-sel" title="Veredicto">
                    <option value="">Cualquier veredicto</option>
                    <?php foreach (PiezaPlacaModel::VEREDICTOS as $v => $etiqueta): ?>
                        <option value="<?= $v ?>"><?= esc($etiqueta) ?></option>
                    <?php endforeach; ?>
                    <option value="-">Sin veredicto</option>
                </select>
                <select id="cuPieza" class="fv-input pv-sel" title="Pieza">
                    <option value="">Cualquier pieza</option>
                    <?php foreach ($familias as $id => $nombre): ?>
                        <option value="<?= (int) $id ?>"><?= esc($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="cuImpresora" class="fv-input pv-sel" title="Impresora">
                    <option value="">Cualquier impresora</option>
                    <?php foreach (PiezaPlacaModel::IMPRESORAS as $imp): ?>
                        <option value="<?= esc($imp) ?>"><?= esc($imp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="pv-dos">
        <div class="cu-lista" id="cuLista">
            <?php if ($entradas === []): ?>
                <p class="fv-vacio">Todavía no hay nada anotado en las bitácoras de las placas.</p>
            <?php endif; ?>
            <?php foreach ($entradas as $e): ?>
                <?php
                $pl = $e['placa'];
                $param = array_filter([
                    $pl['impresora'] ?: null,
                    $pl['resina'] ?: null,
                    $pl['exposicion'] ? 'exp. ' . $pl['exposicion'] : null,
                    $pl['temperatura'] !== null && $pl['temperatura'] !== '' ? rtrim(rtrim((string) $pl['temperatura'], '0'), '.') . ' °C' : null,
                    $pl['numero_capas'] ? $pl['numero_capas'] . ' capas' : null,
                    $pl['minutos_reales'] ? intdiv((int) $pl['minutos_reales'], 60) . 'h ' . sprintf('%02d', (int) $pl['minutos_reales'] % 60) . ' real' : null,
                ]);
                ?>
                <article class="cu-e" id="placa-<?= (int) $pl['id'] ?>"
                    data-veredicto="<?= esc($pl['veredicto'] ?: '-') ?>"
                    data-impresora="<?= esc((string) $pl['impresora']) ?>"
                    data-familias=",<?= implode(',', $e['familias']) ?>,">
                    <div class="cu-e-h">
                        <span class="f"><?= $fecha($e['fecha']) ?></span>
                        <h2><?= esc($pl['nombre']) ?></h2>
                        <?php if ($pl['veredicto']): ?>
                            <span class="fv-estado <?= $tonoVeredicto[$pl['veredicto']] ?>"><?= esc(PiezaPlacaModel::VEREDICTOS[$pl['veredicto']]) ?></span>
                        <?php endif; ?>
                        <a class="fv-link" href="<?= site_url('piezas/placa/' . (int) $pl['id'] . '/bitacora/editar') ?>">Bitácora <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <?php if ($e['resumen'] !== ''): ?>
                        <div class="cu-lleva" title="<?= esc($e['resumen'], 'attr') ?>"><i class="bi bi-box"></i> <?= esc($e['resumen']) ?></div>
                    <?php endif; ?>

                    <?php if ($e['pruebas'] !== []): ?>
                        <div class="cu-b" data-sec="pruebas">
                            <div class="cu-et">Pruebas</div>
                            <?php foreach ($e['pruebas'] as $p): ?>
                                <?php $resp = trim((string) $p['respuesta']); ?>
                                <div class="cu-preg" data-prueba="<?= (int) $p['id'] ?>">
                                    <div><i class="bi bi-question-circle"></i><?= esc($p['pregunta']) ?></div>
                                    <div class="r<?= $resp === '' ? ' sin' : '' ?>"><?= $resp === '' ? 'Sin responder' : $texto($resp) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($e['conclusiones'] !== ''): ?>
                        <div class="cu-b" data-sec="conclusiones">
                            <div class="cu-concl"><i class="bi bi-lightbulb"></i><div><?= $texto($e['conclusiones']) ?></div></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($e['notas'] !== ''): ?>
                        <div class="cu-b" data-sec="notas">
                            <div class="cu-et">Notas</div>
                            <div class="cu-notas"><?= $texto($e['notas']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($e['piezas'] !== []): ?>
                        <div class="cu-b" data-sec="piezas">
                            <div class="cu-et">Por pieza</div>
                            <?php foreach ($e['piezas'] as $p): ?>
                                <div class="cu-pz">
                                    <div class="cu-pz-h">
                                        <b><?= esc($p['nombre']) ?></b>
                                        <span class="x">×<?= $p['cantidad'] ?></span>
                                        <?php if ($p['fallidas']): ?><span class="mal"><?= $p['fallidas'] ?> fallida<?= $p['fallidas'] > 1 ? 's' : '' ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($p['notas'] !== ''): ?><div class="cu-notas"><?= $texto($p['notas']) ?></div><?php endif; ?>
                                    <?php if ($p['fotos'] !== []): ?>
                                        <div class="cu-fotos" data-sec-fotos>
                                            <?php foreach ($p['fotos'] as $f): ?>
                                                <img src="<?= esc($f['url'], 'attr') ?>" alt="" loading="lazy" title="<?= esc($f['nota'], 'attr') ?>" data-foto>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($e['fotos'] !== []): ?>
                        <div class="cu-fotos" data-sec="fotos">
                            <?php foreach ($e['fotos'] as $f): ?>
                                <img src="<?= esc($f['url'], 'attr') ?>" alt="" loading="lazy" title="<?= esc($f['nota'], 'attr') ?>" data-foto>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($param !== []): ?>
                        <div class="cu-param"><?php foreach ($param as $x): ?><span><?= esc($x) ?></span><?php endforeach; ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <p class="fv-vacio d-none" id="cuVacio">Nada coincide con el filtro.</p>
        </div>

        <?php if ($pendientes !== []): ?>
            <aside class="pv-lado" id="cuPendientes" data-sec="pruebas">
                <section class="fv-panel cu-pend">
                    <div class="fv-ph"><i class="bi bi-question-circle"></i><h3>Sin responder</h3><span class="cuenta" id="cuNumPend"><?= count($pendientes) ?></span></div>
                    <ul>
                        <?php foreach ($pendientes as $p): ?>
                            <li data-pend="<?= (int) $p['prueba']['id'] ?>">
                                <span class="q"><?= esc($p['prueba']['pregunta']) ?></span>
                                <a class="de" href="#placa-<?= (int) $p['placa']['id'] ?>"><?= esc($p['placa']['nombre']) ?> · <?= $fecha($p['fecha']) ?></a>
                                <div class="fv-campo">
                                    <input type="text" class="fv-input" data-responder="<?= (int) $p['prueba']['id'] ?>" placeholder="Respuesta… (Intro)">
                                    <button type="button" class="fv-ibtn" data-guardar="<?= (int) $p['prueba']['id'] ?>" title="Guardar respuesta"><i class="bi bi-check-lg"></i></button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </aside>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="cuVisor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <img id="cuVisorImg" src="" alt="" class="img-fluid rounded mx-auto d-block" style="max-height: 85vh;" data-bs-dismiss="modal">
            <div id="cuVisorNota" class="text-center text-light small mt-2"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var CSRF_NOMBRE = '<?= csrf_token() ?>';
    var CSRF_VALOR = '<?= csrf_hash() ?>';
    var URL_RESPONDER = '<?= site_url('piezas/cuaderno/prueba') ?>/';

    var q = document.getElementById('cuQ'), selV = document.getElementById('cuVeredicto'),
        selP = document.getElementById('cuPieza'), selI = document.getElementById('cuImpresora'),
        botonesSec = Array.from(document.querySelectorAll('[data-sec-btn]')),
        contenedor = document.querySelector('.cu');
    var entradas = Array.from(document.querySelectorAll('.cu-e'));

    function plano(s) { return s.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase(); }
    function indexar() {
        entradas.forEach(function (e) {
            e._textos = {};
            e.querySelectorAll('[data-sec]').forEach(function (b) { e._textos[b.dataset.sec] = (e._textos[b.dataset.sec] || '') + ' ' + plano(b.textContent); });
        });
    }
    indexar();

    var visibles = ['pruebas', 'conclusiones', 'notas', 'piezas', 'fotos'];
    try { var g = JSON.parse(localStorage.getItem('cuadernoSecciones') || 'null'); if (Array.isArray(g)) visibles = g; } catch (err) {}
    function aplicarSecciones() {
        botonesSec.forEach(function (b) {
            var on = visibles.indexOf(b.dataset.secBtn) >= 0;
            b.setAttribute('aria-pressed', on);
            contenedor.classList.toggle('cu-sin-' + b.dataset.secBtn, !on);
        });
        try { localStorage.setItem('cuadernoSecciones', JSON.stringify(visibles)); } catch (err) {}
    }
    document.getElementById('cuSecciones').addEventListener('click', function (ev) {
        var b = ev.target.closest('button');
        if (!b) return;
        if (b.hasAttribute('data-todo')) {
            visibles = botonesSec.map(function (x) { return x.dataset.secBtn; });
        } else {
            var s = b.dataset.secBtn, i = visibles.indexOf(s);
            if (i >= 0) visibles.splice(i, 1); else visibles.push(s);
        }
        aplicarSecciones();
        filtrar();
    });

    function filtrar() {
        var t = plano(q.value.trim()), n = 0;
        entradas.forEach(function (e) {
            var secs = Object.keys(e._textos).filter(function (s) { return visibles.indexOf(s) >= 0; });
            var ok = secs.length > 0
                && (!t || secs.some(function (s) { return e._textos[s].indexOf(t) >= 0; }))
                && (!selV.value || e.dataset.veredicto === selV.value)
                && (!selP.value || e.dataset.familias.indexOf(',' + selP.value + ',') >= 0)
                && (!selI.value || e.dataset.impresora === selI.value);
            e.classList.toggle('d-none', !ok);
            if (ok) n++;
        });
        document.getElementById('cuCuenta').innerHTML = n === entradas.length ? '' : '<b>' + n + '</b> de ' + entradas.length;
        document.getElementById('cuVacio').classList.toggle('d-none', n > 0 || !entradas.length);
        resaltar(t);
    }

    function resaltar(t) {
        document.querySelectorAll('#cuLista mark').forEach(function (m) { m.replaceWith(document.createTextNode(m.textContent)); });
        document.getElementById('cuLista').normalize();
        if (t.length < 2) return;
        entradas.forEach(function (e) {
            if (e.classList.contains('d-none')) return;
            var w = document.createTreeWalker(e, NodeFilter.SHOW_TEXT), nodos = [];
            while (w.nextNode()) nodos.push(w.currentNode);
            nodos.forEach(function (n) {
                var i = plano(n.nodeValue).indexOf(t);
                if (i < 0) return;
                var r = document.createRange();
                r.setStart(n, i); r.setEnd(n, i + t.length);
                r.surroundContents(document.createElement('mark'));
            });
        });
    }

    q.addEventListener('input', filtrar);
    aplicarSecciones();
    [selV, selP, selI].forEach(function (el) { el.addEventListener('change', filtrar); });
    filtrar();

    function responder(id) {
        var input = document.querySelector('[data-responder="' + id + '"]');
        var valor = input.value.trim();
        if (!valor) { input.focus(); return; }
        input.disabled = true;
        var datos = new FormData();
        datos.append('respuesta', valor);
        datos.append(CSRF_NOMBRE, CSRF_VALOR);
        fetch(URL_RESPONDER + id + '/respuesta', { method: 'POST', body: datos, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) throw d;
                var r = document.querySelector('[data-prueba="' + id + '"] .r');
                if (r) { r.classList.remove('sin'); r.textContent = d.respuesta; }
                document.querySelector('[data-pend="' + id + '"]').remove();
                var quedan = document.querySelectorAll('[data-pend]').length;
                document.getElementById('cuNumPend').textContent = quedan;
                if (!quedan) document.getElementById('cuPendientes').remove();
                indexar();
            })
            .catch(function () { input.disabled = false; alert('No se pudo guardar la respuesta.'); });
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-guardar]');
        if (b) responder(b.dataset.guardar);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.matches('[data-responder]')) { e.preventDefault(); responder(e.target.dataset.responder); }
    });

    var visor = document.getElementById('cuVisor');
    document.addEventListener('click', function (e) {
        var img = e.target.closest('[data-foto]');
        if (!img) return;
        document.getElementById('cuVisorImg').src = img.src;
        document.getElementById('cuVisorNota').textContent = img.title || '';
        bootstrap.Modal.getOrCreateInstance(visor).show();
    });
})();
</script>

<?= $this->endSection() ?>
