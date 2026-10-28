<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
use App\Models\PiezaPlacaModel;

$colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
$fecha = static fn (?string $f) => $f ? date('d/m/Y', strtotime($f)) : '';
$texto = static fn (string $t) => nl2br(esc($t));
?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-journal-bookmark text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Cuaderno</strong>
    <span class="text-muted small fw-normal ms-2"><?= count($entradas) ?> placas con anotaciones</span>
</h5>

<style>
    .cu { max-width: 900px; }
    .cu-filtros { position: sticky; top: 0; z-index: 5; background: var(--bs-body-bg); padding: .5rem 0; }
    .cu-pend li { padding: .45rem 0; border-bottom: 1px solid var(--bs-border-color); }
    .cu-pend li:last-child { border-bottom: 0; }
    .cu-entrada { border: 1px solid var(--bs-border-color); border-left: 4px solid var(--cu-c, var(--bs-border-color)); border-radius: 8px; padding: .8rem 1rem; margin-bottom: .9rem; }
    .cu-entrada[data-veredicto="buena"] { --cu-c: var(--bs-success); }
    .cu-entrada[data-veredicto="regular"] { --cu-c: var(--bs-warning); }
    .cu-entrada[data-veredicto="repetir"] { --cu-c: var(--bs-danger); }
    .cu-bloque { margin-top: .55rem; }
    .cu-bloque > .cu-et { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bs-secondary-color); font-weight: 600; }
    .cu-conclusion { background: color-mix(in srgb, var(--bs-info) 12%, transparent); border-radius: 6px; padding: .45rem .7rem; }
    .cu-pregunta { margin: .25rem 0; }
    .cu-pregunta .r { color: var(--bs-secondary-color); margin-left: 1.3rem; }
    .cu-pregunta .r.sin { color: var(--bs-warning); font-style: italic; }
    .cu-notas { color: var(--bs-secondary-color); }
    .cu-fotos { display: flex; flex-wrap: wrap; gap: 6px; margin-top: .3rem; }
    .cu-fotos img { width: 64px; height: 64px; object-fit: cover; border-radius: 4px; cursor: zoom-in; border: 1px solid var(--bs-border-color); }
    .cu-param { font-size: .8rem; color: var(--bs-secondary-color); }
    .cu-lleva { font-size: .8rem; color: var(--bs-secondary-color); }
    mark { padding: 0 .1em; }
    .cu-sin-pruebas [data-sec="pruebas"], .cu-sin-conclusiones [data-sec="conclusiones"], .cu-sin-notas [data-sec="notas"],
    .cu-sin-piezas [data-sec="piezas"], .cu-sin-fotos [data-sec="fotos"], .cu-sin-fotos [data-sec-fotos] { display: none !important; }
</style>

<div class="cu">
    <div class="cu-filtros d-flex flex-wrap gap-2 align-items-center mb-2">
        <input type="search" id="cuQ" class="form-control form-control-sm" style="max-width: 16rem;" placeholder="Buscar (p. ej. soportes)" autocomplete="off">
        <select id="cuVeredicto" class="form-select form-select-sm" style="max-width: 11rem;">
            <option value="">Cualquier veredicto</option>
            <?php foreach (\App\Models\PiezaPlacaModel::VEREDICTOS as $v => $etiqueta): ?>
                <option value="<?= $v ?>"><?= esc($etiqueta) ?></option>
            <?php endforeach; ?>
            <option value="-">Sin veredicto</option>
        </select>
        <select id="cuPieza" class="form-select form-select-sm" style="max-width: 14rem;">
            <option value="">Cualquier pieza</option>
            <?php foreach ($familias as $id => $nombre): ?>
                <option value="<?= (int) $id ?>"><?= esc($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="cuImpresora" class="form-select form-select-sm" style="max-width: 10rem;">
            <option value="">Cualquier impresora</option>
            <?php foreach (\App\Models\PiezaPlacaModel::IMPRESORAS as $imp): ?>
                <option value="<?= esc($imp) ?>"><?= esc($imp) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="btn-group btn-group-sm" role="group" aria-label="Qué mostrar" id="cuSecciones">
            <button type="button" class="btn btn-outline-secondary" data-todo>Todo</button>
            <button type="button" class="btn btn-outline-secondary active" data-sec-btn="pruebas" aria-pressed="true"><i class="bi bi-question-circle"></i> Preguntas</button>
            <button type="button" class="btn btn-outline-secondary active" data-sec-btn="conclusiones" aria-pressed="true"><i class="bi bi-lightbulb"></i> Conclusiones</button>
            <button type="button" class="btn btn-outline-secondary active" data-sec-btn="notas" aria-pressed="true"><i class="bi bi-sticky"></i> Notas</button>
            <button type="button" class="btn btn-outline-secondary active" data-sec-btn="piezas" aria-pressed="true"><i class="bi bi-puzzle"></i> Piezas</button>
            <button type="button" class="btn btn-outline-secondary active" data-sec-btn="fotos" aria-pressed="true"><i class="bi bi-image"></i> Fotos</button>
        </div>
        <span class="small text-muted ms-auto" id="cuCuenta"></span>
    </div>

    <?php if ($pendientes !== []): ?>
        <div class="card mb-4 border-warning" id="cuPendientes" data-sec="pruebas">
            <div class="card-header py-2 small fw-semibold">
                <i class="bi bi-question-circle text-warning"></i>
                Preguntas sin responder (<span id="cuNumPend"><?= count($pendientes) ?></span>)
            </div>
            <ul class="list-unstyled mb-0 px-3 cu-pend">
                <?php foreach ($pendientes as $p): ?>
                    <li data-pend="<?= (int) $p['prueba']['id'] ?>">
                        <div class="d-flex flex-wrap gap-2 align-items-baseline">
                            <span class="flex-grow-1"><?= esc($p['prueba']['pregunta']) ?></span>
                            <a class="small text-muted text-decoration-none" href="#placa-<?= (int) $p['placa']['id'] ?>"><?= esc($p['placa']['nombre']) ?> · <?= $fecha($p['fecha']) ?></a>
                        </div>
                        <div class="input-group input-group-sm mt-1">
                            <input type="text" class="form-control" data-responder="<?= (int) $p['prueba']['id'] ?>" placeholder="Respuesta… (Intro para guardar)">
                            <button type="button" class="btn btn-outline-success" data-guardar="<?= (int) $p['prueba']['id'] ?>"><i class="bi bi-check-lg"></i></button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($entradas === []): ?>
        <p class="text-muted">Todavía no hay nada anotado en las bitácoras de las placas.</p>
    <?php endif; ?>

    <div id="cuLista">
        <?php foreach ($entradas as $e): ?>
            <?php
            $pl = $e['placa'];
            $param = array_filter([
                $pl['impresora'] ? 'Impresora ' . $pl['impresora'] : null,
                $pl['resina'] ?: null,
                $pl['exposicion'] ? 'exp. ' . $pl['exposicion'] : null,
                $pl['temperatura'] !== null && $pl['temperatura'] !== '' ? rtrim(rtrim((string) $pl['temperatura'], '0'), '.') . ' °C' : null,
                $pl['numero_capas'] ? $pl['numero_capas'] . ' capas' : null,
                $pl['minutos_reales'] ? intdiv((int) $pl['minutos_reales'], 60) . 'h ' . sprintf('%02d', (int) $pl['minutos_reales'] % 60) . ' real' : null,
            ]);
            ?>
            <article class="cu-entrada" id="placa-<?= (int) $pl['id'] ?>"
                data-veredicto="<?= esc($pl['veredicto'] ?: '-') ?>"
                data-impresora="<?= esc((string) $pl['impresora']) ?>"
                data-familias=",<?= implode(',', $e['familias']) ?>,"
>
                <div class="d-flex flex-wrap gap-2 align-items-baseline">
                    <span class="text-muted small"><?= $fecha($e['fecha']) ?></span>
                    <strong><?= esc($pl['nombre']) ?></strong>
                    <?php if ($pl['veredicto']): ?>
                        <span class="badge text-bg-<?= $colorVeredicto[$pl['veredicto']] ?>"><?= esc(\App\Models\PiezaPlacaModel::VEREDICTOS[$pl['veredicto']]) ?></span>
                    <?php endif; ?>
                    <a class="ms-auto small" href="<?= site_url('piezas/placa/' . (int) $pl['id'] . '/bitacora/editar') ?>">bitácora <i class="bi bi-arrow-right"></i></a>
                </div>
                <?php if ($e['resumen'] !== ''): ?>
                    <div class="cu-lleva text-truncate" title="<?= esc($e['resumen']) ?>"><i class="bi bi-box"></i> <?= esc($e['resumen']) ?></div>
                <?php endif; ?>

                <?php if ($e['pruebas'] !== []): ?>
                    <div class="cu-bloque" data-sec="pruebas">
                        <div class="cu-et">Pruebas</div>
                        <?php foreach ($e['pruebas'] as $p): ?>
                            <?php $resp = trim((string) $p['respuesta']); ?>
                            <div class="cu-pregunta" data-prueba="<?= (int) $p['id'] ?>">
                                <div><i class="bi bi-question-circle"></i> <?= esc($p['pregunta']) ?></div>
                                <div class="r<?= $resp === '' ? ' sin' : '' ?>">→ <?= $resp === '' ? 'sin responder' : $texto($resp) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($e['conclusiones'] !== ''): ?>
                    <div class="cu-bloque" data-sec="conclusiones">
                        <div class="cu-et">Conclusiones</div>
                        <div class="cu-conclusion"><i class="bi bi-lightbulb"></i> <?= $texto($e['conclusiones']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($e['notas'] !== ''): ?>
                    <div class="cu-bloque" data-sec="notas">
                        <div class="cu-et">Notas</div>
                        <div class="cu-notas"><?= $texto($e['notas']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($e['piezas'] !== []): ?>
                    <div class="cu-bloque" data-sec="piezas">
                        <div class="cu-et">Por pieza</div>
                        <?php foreach ($e['piezas'] as $p): ?>
                            <div class="mt-1">
                                <i class="bi bi-puzzle"></i> <b><?= esc($p['nombre']) ?></b>
                                <span class="small text-muted">×<?= $p['cantidad'] ?><?= $p['fallidas'] ? ', <span class="text-danger">' . $p['fallidas'] . ' fallida' . ($p['fallidas'] > 1 ? 's' : '') . '</span>' : '' ?></span>
                                <?php if ($p['notas'] !== ''): ?><div class="cu-notas ms-4"><?= $texto($p['notas']) ?></div><?php endif; ?>
                                <?php if ($p['fotos'] !== []): ?>
                                    <div class="cu-fotos ms-4" data-sec-fotos>
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
                    <div class="cu-fotos mt-2" data-sec="fotos">
                        <?php foreach ($e['fotos'] as $f): ?>
                            <img src="<?= esc($f['url'], 'attr') ?>" alt="" loading="lazy" title="<?= esc($f['nota'], 'attr') ?>" data-foto>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($param !== []): ?>
                    <div class="cu-param mt-2"><?= esc(implode(' · ', $param)) ?></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="text-muted d-none" id="cuVacio">Nada coincide con el filtro.</p>
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

    // ---- filtros ----
    var q = document.getElementById('cuQ'), selV = document.getElementById('cuVeredicto'),
        selP = document.getElementById('cuPieza'), selI = document.getElementById('cuImpresora'),
        botonesSec = Array.from(document.querySelectorAll('[data-sec-btn]')),
        contenedor = document.querySelector('.cu');
    var entradas = Array.from(document.querySelectorAll('.cu-entrada'));

    // Quita tildes para que "exposicion" encuentre "exposición".
    function plano(s) { return s.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase(); }
    // Texto buscable por sección, para que la búsqueda solo mire lo que se ve.
    function indexar() {
        entradas.forEach(function (e) {
            e._textos = {};
            e.querySelectorAll('[data-sec]').forEach(function (b) { e._textos[b.dataset.sec] = (e._textos[b.dataset.sec] || '') + ' ' + plano(b.textContent); });
        });
    }
    indexar();

    // Secciones a la vista (se recuerda en este navegador).
    var visibles = ['pruebas', 'conclusiones', 'notas', 'piezas', 'fotos'];
    try { var g = JSON.parse(localStorage.getItem('cuadernoSecciones') || 'null'); if (Array.isArray(g)) visibles = g; } catch (err) {}
    function aplicarSecciones() {
        botonesSec.forEach(function (b) {
            var on = visibles.indexOf(b.dataset.secBtn) >= 0;
            b.classList.toggle('active', on);
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
            // Una placa se ve si le queda alguna sección a la vista (y, si se
            // busca algo, si está en alguna de ellas).
            var secs = Object.keys(e._textos).filter(function (s) { return visibles.indexOf(s) >= 0; });
            var ok = secs.length > 0
                && (!t || secs.some(function (s) { return e._textos[s].indexOf(t) >= 0; }))
                && (!selV.value || e.dataset.veredicto === selV.value)
                && (!selP.value || e.dataset.familias.indexOf(',' + selP.value + ',') >= 0)
                && (!selI.value || e.dataset.impresora === selI.value);
            e.classList.toggle('d-none', !ok);
            if (ok) n++;
        });
        document.getElementById('cuCuenta').textContent = n === entradas.length ? '' : n + ' de ' + entradas.length;
        document.getElementById('cuVacio').classList.toggle('d-none', n > 0 || !entradas.length);
        resaltar(t);
    }

    // Marca lo buscado dentro de las entradas visibles (solo nodos de texto).
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

    q.addEventListener("input", filtrar);
    aplicarSecciones();
    [selV, selP, selI].forEach(function (el) { el.addEventListener('change', filtrar); });
    filtrar();

    // ---- responder pendientes ----
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
                if (r) { r.classList.remove('sin'); r.textContent = '→ ' + d.respuesta; }
                var li = document.querySelector('[data-pend="' + id + '"]');
                li.remove();
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

    // ---- visor de fotos ----
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
