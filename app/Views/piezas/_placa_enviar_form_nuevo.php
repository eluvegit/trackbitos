<?php
$placa = $e['placa'];
$imagenes = $e['imagenes'];
$guardada = $e['guardada'];
$enviadas = $e['enviadas'];
$idPlaca = (int) $placa['id'];
$p = $paralelo ? 'p' . $idPlaca . '_' : '';
$esEste = (int) old('placa_form') === $idPlaca;
$old = static fn(string $k, $def = null) => $esEste ? old($k, $def) : $def;
$hm = static fn(?int $m) => $m === null ? '' : intdiv($m, 60) . ':' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
$ultimaImagen = $imagenes ? (int) end($imagenes)['id'] : 0;
$impresoraElegida = (string) $old('impresora', (string) ($placa['impresora'] ?? ''));
$enviada = (bool) $placa['enviada_en'];
$bloqueada = $enviada && !$old('desbloqueado');
$elegida = (int) $old('imagen_id', (string) $ultimaImagen);
?>

<?php if ($enviada): ?>
    <div class="en-estado ok">
        <i class="bi bi-lock-fill" id="<?= $p ?>envCandadoIcono"></i>
        <div class="en-estado-t">
            <div><b>Enviada</b> el <?= esc(date('d/m/Y H:i', strtotime($placa['enviada_en']))) ?> <span class="t3">a <?= esc($placa['enviada_a']) ?></span>
                <?php if ($placa['impresora']): ?><span class="bt-maq <?= esc($placa['impresora'], 'attr') ?>"><?= esc($placa['impresora']) ?></span><?php endif; ?></div>
            <?php if ($placa['poner_en']): ?>
                <div class="t2">Ponerla el <b><?= esc(date('d/m H:i', strtotime($placa['poner_en']))) ?></b><?= $placa['acaba_en'] ? ' · acaba a las <b>' . esc(date('H:i', strtotime($placa['acaba_en']))) . '</b>' : '' ?></div>
            <?php endif; ?>
        </div>
        <button type="button" class="fv-btn fv-btn-sm" id="<?= $p ?>envDesbloquear" <?= $bloqueada ? '' : 'disabled' ?>>
            <i class="bi <?= $bloqueada ? 'bi-lock' : 'bi-unlock' ?>"></i> <?= $bloqueada ? 'Desbloquear' : 'Desbloqueada' ?>
        </button>
    </div>
<?php elseif ($guardada): ?>
    <div class="en-estado">
        <i class="bi bi-floppy"></i>
        <div class="en-estado-t">Guardada sin enviar: <b><?= esc($placa['impresora'] ?? 'sin impresora') ?></b>, ponerla el <b><?= esc(date('d/m H:i', strtotime($placa['poner_en']))) ?></b>.</div>
    </div>
<?php endif; ?>

<div class="en-grid">
    <section class="fv-panel">
        <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" id="<?= $p ?>form">
            <?= csrf_field() ?>
            <input type="hidden" name="confirmado" value="">
            <input type="hidden" name="placa_form" value="<?= $idPlaca ?>">
            <input type="hidden" name="volver" value="<?= esc($volver, 'attr') ?>">
            <input type="hidden" name="accion" value="enviar">
            <input type="hidden" name="desbloqueado" value="<?= $enviada && !$bloqueada ? '1' : '' ?>">
            <fieldset id="<?= $p ?>envBloque" class="en-form" <?= $bloqueada ? 'disabled' : '' ?>>

                <div class="bt-campo">
                    <span class="bt-lbl">Impresora</span>
                    <div class="bt-seg en-seg">
                        <?php foreach (\App\Models\PiezaPlacaModel::IMPRESORAS as $imp): ?>
                            <input type="radio" name="impresora" value="<?= $imp ?>" id="<?= $p ?>imp<?= $imp ?>" autocomplete="off" <?= $impresoraElegida === $imp ? 'checked' : '' ?>>
                            <label for="<?= $p ?>imp<?= $imp ?>" class="<?= $imp ?>"><?= $imp ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div id="<?= $p ?>envAvisoImpresora" class="en-nota t3"></div>
                </div>

                <label class="bt-campo"><span class="bt-lbl">Número de capas</span>
                    <input type="number" min="1" step="1" inputmode="numeric" name="numero_capas" id="<?= $p ?>envCapas" class="bt-ed" placeholder="1240"
                        value="<?= esc($old('numero_capas', (string) ($placa['numero_capas'] ?? '')), 'attr') ?>">
                    <span class="en-nota t3" id="<?= $p ?>envEstimado" data-por-capa="<?= esc((string) $calc['minutosPorCapa'], 'attr') ?>"></span>
                </label>

                <div class="en-cuatro">
                    <label class="bt-campo"><span class="bt-lbl">Día</span>
                        <input type="date" name="fecha" id="<?= $p ?>envFecha" class="bt-ed" required value="<?= esc($old('fecha', $e['fecha']), 'attr') ?>"></label>
                    <label class="bt-campo"><span class="bt-lbl">Duración</span>
                        <input type="text" name="duracion" id="<?= $p ?>envDuracion" class="bt-ed fv-mono" required placeholder="2:15" value="<?= esc($old('duracion', $hm($e['minutos'])), 'attr') ?>"></label>
                    <label class="bt-campo"><span class="bt-lbl">Ponerla a las</span>
                        <input type="time" name="hora" id="<?= $p ?>envHora" class="bt-ed en-hora" required value="<?= esc($old('hora', $e['hora']), 'attr') ?>"></label>
                    <label class="bt-campo"><span class="bt-lbl">Acaba a las</span>
                        <input type="time" id="<?= $p ?>envFin" class="bt-ed" title="Cámbiala y se recalcula a qué hora ponerla"></label>
                </div>
                <div class="en-nota t3">Cambia la hora de ponerla o la de acabar y la otra se recalcula.</div>
<?php if (!$paralelo): ?>
                <div data-sync></div>
<?php endif; ?>

                <label class="bt-campo"><span class="bt-lbl">Enlace de Drive</span>
                    <input type="url" name="enlace" id="<?= $p ?>envEnlace" class="bt-ed fv-mono" required placeholder="https://drive.google.com/file/d/…" value="<?= esc($old('enlace', $e['enlace']), 'attr') ?>"></label>

                <div class="bt-campo">
                    <label class="bt-lbl" for="<?= $p ?>envFichero">Nombre de la placa = nombre del fichero en Chitubox</label>
                    <span class="bt-ed-g">
                        <input type="text" name="nombre" id="<?= $p ?>envFichero" class="bt-ed en-fichero" maxlength="145" value="<?= esc($old('nombre', $placa['nombre']), 'attr') ?>">
                        <button type="button" class="fv-btn" id="<?= $p ?>envCopiar" title="Copiar para pegarlo como nombre del fichero"><i class="bi bi-clipboard"></i></button>
                    </span>
                    <span class="en-nota t3">Se guarda como nombre de la placa, con la impresora detrás del número. En el correo va con <code>.goo</code>.</span>
                </div>

                <div class="bt-campo">
                    <span class="bt-lbl">Imagen</span>
                    <div class="en-imgs">
                        <?php foreach ($imagenes as $img): ?>
                            <label class="en-img">
                                <input type="radio" name="imagen_id" value="<?= (int) $img['id'] ?>" data-src="<?= imagen_pieza($img, 'placa-imagen', 'v') ?>" <?= $elegida === (int) $img['id'] ? 'checked' : '' ?>>
                                <img src="<?= imagen_pieza($img, 'placa-imagen') ?>" alt="">
                            </label>
                        <?php endforeach; ?>
                        <label class="en-img sin">
                            <input type="radio" name="imagen_id" value="0" data-src="" <?= $elegida === 0 ? 'checked' : '' ?>>
                            <span><i class="bi bi-image"></i> Sin imagen</span>
                        </label>
                    </div>
                    <span class="en-nota t3">La captura de Chitubox se añade en las fotos de la <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar#fotos') ?>" class="fv-link">bitácora</a>.</span>
                </div>

                <div class="en-botones">
                    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="fv-gbtn"><i class="bi bi-arrow-left"></i> Volver</a>
                    <span class="fv-estado <?= $guardada ? 'e-ok' : 't3' ?> der" id="<?= $p ?>envGuardado"><?= $guardada ? 'Guardado' : 'Se guarda solo' ?></span>
                    <button type="button" class="fv-btn fv-btn-pri" id="<?= $p ?>envAbrirModal" <?= $destino === '' ? 'disabled' : '' ?>><i class="bi bi-send"></i> Enviar placa…</button>
                </div>
            </fieldset>
        </form>
    </section>

    <section class="fv-panel en-previa">
        <details open class="fv-mas">
            <summary><i class="bi bi-chevron-right ch"></i> <span class="t2">Vista previa del correo</span></summary>
            <iframe id="<?= $p ?>envPrevia" title="Vista previa del correo"></iframe>
        </details>
    </section>
</div>

<div class="modal fade modal-impresora en-modal" id="<?= $p ?>modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden border-0 shadow">
            <div class="imp-cabecera text-center py-4">
                <div class="small text-uppercase" style="letter-spacing: 3px; opacity: .85;">Va a la impresora</div>
                <div class="imp-grande" data-m="impresora"></div>
            </div>
            <div class="modal-body">
                <div class="imp-dato">Fichero para el USB</div>
                <div class="fw-semibold mb-3 text-break" data-m="fichero"></div>
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="imp-dato">Ponerla</div>
                        <div class="fs-3 fw-bold lh-1" data-m="hora"></div>
                        <div class="small text-muted" data-m="dia"></div>
                    </div>
                    <div class="col-6">
                        <div class="imp-dato">Acaba</div>
                        <div class="fs-3 fw-bold lh-1 text-muted" data-m="fin"></div>
                        <div class="small text-muted" data-m="duracion"></div>
                    </div>
                </div>
                <div class="small text-info-emphasis mb-2" data-m="desfase"></div>
                <div data-m="avisos"></div>
                <div class="small text-muted">Placa #<?= $idPlaca ?> · a <?= esc($destino) ?></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="fv-btn" data-bs-dismiss="modal">Revisar</button>
                <button type="button" class="fv-btn en-enviar" data-m="enviar"></button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var ENVIADAS = <?= json_encode($enviadas) ?>;
    var YA_ENVIADA = <?= json_encode($placa['enviada_en'] ? date('d/m H:i', strtotime($placa['enviada_en'])) . ($placa['impresora'] ? ' a ' . $placa['impresora'] : '') : null) ?>;
    var f = function (id) { return document.getElementById(<?= json_encode($p) ?> + id); };
    var form = f('form');
    var porCapa = parseFloat(f('envEstimado').getAttribute('data-por-capa')) || 0;
    var manual = <?= $old('duracion') || $guardada ? 'true' : 'false' ?>;

    function aMinutos(texto) {
        texto = (texto || '').toLowerCase().trim();
        var h = texto.match(/^(\d+)\s*(?::|h)\s*(\d*)/);
        if (h) return parseInt(h[1], 10) * 60 + (h[2] === '' ? 0 : parseInt(h[2], 10));
        var m = texto.match(/(\d+)/);
        return m ? parseInt(m[1], 10) : null;
    }
    function dos(n) { return String(n).padStart(2, '0'); }
    function hm(d) { return dos(d.getHours()) + ':' + dos(d.getMinutes()); }
    function ymd(d) { return d.getFullYear() + '-' + dos(d.getMonth() + 1) + '-' + dos(d.getDate()); }
    function poner() {
        var fe = f('envFecha').value, ho = f('envHora').value;
        return fe && /^\d{1,2}:\d{2}$/.test(ho) ? new Date(fe + 'T' + ho) : null;
    }
    function duracion() { return aMinutos(f('envDuracion').value); }
    function fin() {
        var p = poner(), d = duracion();
        return p && d ? new Date(p.getTime() + d * 60000) : null;
    }
    function setPoner(d) {
        f('envFecha').value = ymd(d);
        f('envHora').value = hm(d);
        actualizarFin();
        pintarImpresora();
    }

    function actualizarFin() {
        var x = fin();
        f('envFin').value = x ? hm(x) : '';
        previa();
        avisarSync();
    }
    function actualizarPoner() {
        var p = poner(), d = duracion(), v = f('envFin').value;
        if (!p || !d || !/^\d{1,2}:\d{2}$/.test(v)) return;
        var objetivo = new Date(ymd(p) + 'T' + v);
        if (objetivo.getTime() - d * 60000 < p.getTime() - 12 * 3600000) objetivo.setDate(objetivo.getDate() + 1);
        setPoner(new Date(objetivo.getTime() - d * 60000));
    }

    var previaTimer = null;
    function previa() {
        clearTimeout(previaTimer);
        previaTimer = setTimeout(function () {
            var radio = form.querySelector('[name="imagen_id"]:checked');
            var q = new URLSearchParams({
                impresora: impresora(), fecha: f('envFecha').value, hora: f('envHora').value,
                duracion: f('envDuracion').value, enlace: f('envEnlace').value,
                nombre: f('envFichero').value, imagen_id: radio ? radio.value : '0'
            });
            f('envPrevia').src = '<?= site_url('piezas/placa/' . $idPlaca . '/previa-correo') ?>?' + q.toString();
        }, 300);
    }

    function estimar() {
        var capas = parseInt(f('envCapas').value, 10);
        var t = capas > 0 ? Math.round(capas * porCapa) : null;
        f('envEstimado').textContent = t
            ? '≈ ' + Math.floor(t / 60) + ' h ' + (t % 60) + ' min de impresión (' + capas + ' capas × '
                + porCapa.toLocaleString('es-ES', { maximumFractionDigits: 4 }) + ' min/capa)'
            : '';
        if (t && !manual) f('envDuracion').value = Math.floor(t / 60) + ':' + dos(t % 60);
        actualizarFin();
    }

    function impresora() {
        var r = form.querySelector('[name="impresora"]:checked');
        return r ? r.value : '';
    }
    function otraDe(imp) { return imp === 'OLD' ? 'NEW' : 'OLD'; }
    function ocupadas(fecha) { return ENVIADAS.filter(function (p) { return p.fecha === fecha; }); }
    function avisos() {
        var imp = impresora(), lista = [], dia = ocupadas(f('envFecha').value);
        if (!imp) return lista;
        var misma = dia.filter(function (p) { return p.impresora === imp && !window.enPlacas.some(function (o) { return o.id === p.id; }); });
        if (misma.length) {
            lista.push(misma.map(function (p) { return '#' + p.id + ' (' + p.hora + (p.enviada ? '' : ', sin enviar') + ')'; }).join(', ')
                + ' ya va a ' + imp + ' ese día. Mándala como <b>' + otraDe(imp) + '</b>.');
        }
        if (YA_ENVIADA) lista.push('Esta placa ya se envió el ' + YA_ENVIADA + '. Si la mandas otra vez, se imprimirá dos veces.');
        return lista;
    }
    function porDefecto() {
        if (impresora()) return;
        var dia = ocupadas(f('envFecha').value).map(function (p) { return p.impresora; });
        var libre = ['OLD', 'NEW'].filter(function (i) { return dia.indexOf(i) === -1; });
        if (libre.length === 1) f('imp' + libre[0]).checked = true;
    }
    function ficheroCon(imp) {
        var resto = f('envFichero').value.trim().replace(/\.goo$/i, '').replace(/^#\d+\s*/, '')
            .replace(/\s*\b(OLD|NEW)\b\s*/g, ' ').trim();
        return '#<?= $idPlaca ?> ' + imp + (resto ? ' ' + resto : '');
    }
    function pintarImpresora() {
        var imp = impresora(), campo = f('envFichero'), caja = f('envAvisoImpresora'), lista = avisos();
        campo.classList.remove('imp-OLD', 'imp-NEW');
        if (imp) campo.classList.add('imp-' + imp);
        if (!imp) {
            caja.className = 'en-nota e-bad';
            caja.innerHTML = '<i class="bi bi-exclamation-circle"></i> Elige a qué impresora va.';
        } else if (lista.length) {
            caja.className = 'en-nota en-warn';
            caja.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + lista.join('<br>');
        } else {
            caja.className = 'en-nota t3';
            caja.innerHTML = '';
        }
        previa();
        avisarSync();
    }

    function pareja() {
        var imp = impresora(), fe = f('envFecha').value;
        var c = ENVIADAS.filter(function (p) { return p.fecha === fe && p.acaba && (!imp || p.impresora === otraDe(imp)); })
            .sort(function (a, b) { return a.acaba < b.acaba ? 1 : -1; })[0];
        if (!c) return null;
        var pn = new Date(c.fecha + 'T' + c.hora), fn = new Date(c.acaba);
        return { id: c.id, imp: c.impresora, enviada: c.enviada, poner: function () { return pn; }, fin: function () { return fn; } };
    }

    var yo = {
        id: <?= $idPlaca ?>, enviada: <?= $enviada ? 'true' : 'false' ?>, guardada: <?= $guardada ? 'true' : 'false' ?>,
        imp: impresora, poner: poner, fin: fin, setPoner: function (d) { setPoner(d); guardar(); },
        porCapa: porCapa, pareja: pareja
    };
    window.enPlacas = window.enPlacas || [];
    window.enPlacas.push(yo);
    function avisarSync() { if (window.enSync) window.enSync(); }

    var timerG = null, estadoG = f('envGuardado');
    function marcar(clase, texto) { estadoG.className = 'fv-estado ' + clase; estadoG.textContent = texto; }
    function guardar() {
        if (f('envBloque').disabled) return;
        clearTimeout(timerG);
        marcar('e-warn', 'Guardando…');
        timerG = setTimeout(function () {
            var datos = new FormData(form);
            datos.set('accion', 'guardar');
            datos.set('confirmado', '');
            fetch(form.action, { method: 'POST', body: datos, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (!j.ok) { marcar('e-bad', j.error || 'No se pudo guardar'); return; }
                    yo.guardada = true;
                    marcar('e-ok', 'Guardado');
                })
                .catch(function () { marcar('e-bad', 'No se pudo guardar'); });
        }, 700);
    }
    form.addEventListener('input', function (e) { if (e.target.name && e.target.name !== 'enlace') guardar(); });
    form.addEventListener('change', function (e) { if (e.target.name) guardar(); });

    f('envCapas').addEventListener('input', function () { manual = false; estimar(); });
    f('envDuracion').addEventListener('input', function () { manual = true; actualizarFin(); });
    f('envHora').addEventListener('input', actualizarFin);
    f('envFin').addEventListener('change', function () { actualizarPoner(); guardar(); });
    f('envFecha').addEventListener('input', function () { actualizarFin(); pintarImpresora(); });
    ['envEnlace', 'envFichero'].forEach(function (id) { f(id).addEventListener('input', previa); });
    f('envFichero').addEventListener('input', pintarImpresora);
    form.querySelectorAll('[name="imagen_id"]').forEach(function (r) { r.addEventListener('change', previa); });
    form.querySelectorAll('[name="impresora"]').forEach(function (r) {
        r.addEventListener('change', function () {
            f('envFichero').value = ficheroCon(r.value);
            actualizarFin();
            pintarImpresora();
        });
    });

    f('envCopiar').addEventListener('click', function () {
        navigator.clipboard.writeText(f('envFichero').value).then(function () {
            f('envCopiar').innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(function () { f('envCopiar').innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1200);
        });
    });

    var modalEl = f('modal');
    var m = function (k) { return modalEl.querySelector('[data-m="' + k + '"]'); };
    f('envAbrirModal').addEventListener('click', function () {
        if (!impresora()) { pintarImpresora(); return; }
        if (!form.reportValidity()) return;
        var imp = impresora(), lista = avisos();
        var fecha = new Date(f('envFecha').value + 'T12:00');
        var sync = document.querySelector('[data-sync] .en-sync-txt');
        modalEl.querySelector('.modal-content').className = 'modal-content overflow-hidden border-0 shadow imp-' + imp;
        m('impresora').textContent = imp;
        m('fichero').textContent = f('envFichero').value + '.goo';
        m('hora').textContent = f('envHora').value;
        m('dia').textContent = fecha.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
        m('fin').textContent = f('envFin').value || '—';
        m('duracion').textContent = f('envDuracion').value ? 'dura ' + f('envDuracion').value + ' h' : '';
        m('desfase').innerHTML = sync ? sync.textContent : '';
        m('avisos').innerHTML = lista.length
            ? '<div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle-fill"></i> ' + lista.join('<br>') + '</div>'
            : '<div class="alert alert-success py-2 small mb-3"><i class="bi bi-check-circle-fill"></i> Todo cuadra: ' + imp + ' está libre ese día.</div>';
        m('enviar').innerHTML = '<i class="bi bi-send-fill"></i> ' + (lista.length ? 'Enviar igualmente a ' : 'Enviar a ') + imp;
        m('enviar').disabled = false;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
    m('enviar').addEventListener('click', function () {
        clearTimeout(timerG);
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando…';
        form.querySelector('[name="confirmado"]').value = '1';
        form.querySelector('[name="accion"]').value = 'enviar';
        form.submit();
    });
    var desbloquear = f('envDesbloquear');
    if (desbloquear) desbloquear.addEventListener('click', function () {
        if (!confirm('Esta placa ya se envió. ¿Desbloquear para editarla?\n\nSi cambias la impresora o la hora, avisa a quien la vaya a poner.')) return;
        f('envBloque').disabled = false;
        form.querySelector('[name="desbloqueado"]').value = '1';
        f('envCandadoIcono').className = 'bi bi-unlock-fill e-warn';
        this.innerHTML = '<i class="bi bi-unlock"></i> Desbloqueada';
        this.disabled = true;
    });

    porDefecto();
    if (impresora()) f('envFichero').value = ficheroCon(impresora());
    <?php if (!$paralelo && !$guardada && !$old('hora')): ?>
    (function () { var r = pareja(); if (r) setPoner(r.poner()); })();
    <?php endif; ?>
    estimar();
    pintarImpresora();
})();
</script>

