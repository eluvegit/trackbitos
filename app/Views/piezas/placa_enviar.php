<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
$idPlaca = (int) $placa['id'];
$hm = static fn(?int $m) => $m === null ? '' : intdiv($m, 60) . ':' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
$ultimaImagen = $imagenes ? (int) end($imagenes)['id'] : 0;
$impresoraElegida = (string) old('impresora', (string) ($placa['impresora'] ?? ''));
$enviada = (bool) $placa['enviada_en'];
$bloqueada = $enviada && !old('desbloqueado');
?>

<style>
    :root { --imp-old: #d97706; --imp-new: #2563eb; }
    .imp-boton { border: 2px solid var(--c); color: var(--c); font-weight: 700; letter-spacing: 3px; font-size: 1.4rem; padding: .5rem 0; }
    .btn-check:checked + .imp-boton { background: var(--c); color: #fff; }
    .imp-OLD { --c: var(--imp-old); }
    .imp-NEW { --c: var(--imp-new); }
    .imp-fichero { border-left: 4px solid var(--c, #adb5bd); }
    #modalImpresora .imp-cabecera { background: var(--c); color: #fff; }
    #modalImpresora .imp-grande { font-size: 4rem; font-weight: 800; letter-spacing: .5rem; line-height: 1; }
    #modalImpresora .imp-dato { font-size: .8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--bs-secondary-color); }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-envelope text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="text-decoration-none text-muted fw-normal">#<?= $idPlaca ?></a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Enviar placa</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if ($enviada): ?>
    <div class="alert alert-light border d-flex align-items-start gap-3 py-2">
        <i class="bi bi-lock-fill fs-4 text-success" id="envCandadoIcono"></i>
        <div class="small flex-grow-1">
            <div>
                <b>Enviada</b> el <?= esc(date('d/m/Y H:i', strtotime($placa['enviada_en']))) ?> a <?= esc($placa['enviada_a']) ?>
                <?php if ($placa['impresora']): ?>
                    <span class="badge text-white ms-1" style="background: var(--imp-<?= strtolower($placa['impresora']) ?>);"><?= esc($placa['impresora']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($placa['poner_en']): ?>
                <div>Ponerla el <b><?= esc(date('d/m H:i', strtotime($placa['poner_en']))) ?></b>
                    <?= $placa['acaba_en'] ? '· acaba a las <b>' . esc(date('H:i', strtotime($placa['acaba_en']))) . '</b>' : '' ?></div>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-2 mt-1">
                <code class="text-body"><?= esc($placa['nombre']) ?></code>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0"
                    onclick="var b = this; navigator.clipboard.writeText(<?= esc(json_encode($placa['nombre']), 'attr') ?>).then(function () { b.textContent = 'Copiado'; setTimeout(function () { b.innerHTML = '<i class=&quot;bi bi-clipboard&quot;></i> Copiar'; }, 1200); });">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="envDesbloquear" <?= $bloqueada ? '' : 'disabled' ?>>
            <i class="bi <?= $bloqueada ? 'bi-lock' : 'bi-unlock' ?>"></i> <?= $bloqueada ? 'Desbloquear' : 'Desbloqueada' ?>
        </button>
    </div>
<?php elseif ($guardada): ?>
    <div class="alert alert-secondary py-2 small">
        <i class="bi bi-floppy"></i> Guardada sin enviar: <?= esc($placa['impresora'] ?? 'sin impresora') ?>,
        ponerla el <?= esc(date('d/m H:i', strtotime($placa['poner_en']))) ?>.
    </div>
<?php endif; ?>
<?php if ($destino === ''): ?>
    <div class="alert alert-warning py-2 small">
        Falta <code>PIEZAS_PLACA_EMAIL</code> (y la configuración SMTP) en el <code>.env</code>: se puede preparar, pero no enviar.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" id="formEnviarPlaca">
            <?= csrf_field() ?>
            <input type="hidden" name="confirmado" value="">
            <input type="hidden" name="accion" value="enviar">
            <input type="hidden" name="desbloqueado" value="<?= $enviada && !$bloqueada ? '1' : '' ?>">
            <fieldset id="envBloque" <?= $bloqueada ? 'disabled' : '' ?>>

            <div class="small fw-semibold text-body-secondary mb-1">Impresora</div>
            <div class="row g-2 mb-1">
                <?php foreach (\App\Models\PiezaPlacaModel::IMPRESORAS as $imp): ?>
                    <div class="col-6">
                        <input type="radio" class="btn-check" name="impresora" value="<?= $imp ?>" id="imp<?= $imp ?>"
                            autocomplete="off" <?= $impresoraElegida === $imp ? 'checked' : '' ?>>
                        <label class="btn w-100 imp-boton imp-<?= $imp ?>" for="imp<?= $imp ?>"><?= $imp ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="envAvisoImpresora" class="small mb-3"></div>

            <label class="form-label small mb-1" for="envCapas">Número de capas</label>
            <input type="number" min="1" step="1" inputmode="numeric" name="numero_capas" id="envCapas"
                class="form-control form-control-sm" placeholder="p. ej. 1240"
                value="<?= esc(old('numero_capas', (string) ($placa['numero_capas'] ?? '')), 'attr') ?>">
            <p class="text-muted small mb-3 mt-1" id="envEstimado" data-por-capa="<?= esc((string) $calc['minutosPorCapa'], 'attr') ?>"></p>

            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small mb-1" for="envFecha">Día</label>
                    <input type="date" name="fecha" id="envFecha" class="form-control form-control-sm" required
                        value="<?= esc(old('fecha', $fecha), 'attr') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1" for="envDuracion">Duración</label>
                    <input type="text" name="duracion" id="envDuracion" class="form-control form-control-sm" required
                        placeholder="2:15" value="<?= esc(old('duracion', $hm($minutos)), 'attr') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1" for="envHora">Ponerla a las</label>
                    <input type="time" name="hora" id="envHora" class="form-control form-control-sm fw-semibold" required
                        value="<?= esc(old('hora', $hora), 'attr') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1" for="envFin">Acaba a las</label>
                    <input type="time" id="envFin" class="form-control form-control-sm"
                        title="Cámbiala y se recalcula a qué hora ponerla">
                </div>
            </div>
            <div class="form-text mb-2" id="envAviso">
                Cambia la hora de ponerla o la de acabar y la otra se recalcula.
            </div>
            <div class="alert alert-info py-1 px-2 small mb-2 d-none" id="envDesfaseNota"></div>
            <div class="d-flex align-items-center gap-2 small mb-3 text-body-secondary">
                <i class="bi bi-arrow-left-right"></i>
                <label for="envDesfase" class="mb-0">Desfase entre impresoras</label>
                <input type="number" min="0" step="5" id="envDesfase" class="form-control form-control-sm" style="width: 75px;"
                    value="<?= (int) $desfase ?>">
                <span>min</span>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0" id="envGuardarDesfase">Guardar</button>
            </div>

            <label class="form-label small mb-1" for="envEnlace">Enlace de Drive</label>
            <input type="url" name="enlace" id="envEnlace" class="form-control form-control-sm mb-2" required
                placeholder="https://drive.google.com/file/d/…" value="<?= esc(old('enlace', $enlace), 'attr') ?>">

            <label class="form-label small mb-1" for="envFichero">Nombre de la placa = nombre del fichero en Chitubox</label>
            <div class="input-group input-group-sm mb-1">
                <input type="text" name="nombre" id="envFichero" class="form-control fw-semibold imp-fichero" maxlength="145"
                    value="<?= esc(old('nombre', $placa['nombre']), 'attr') ?>">
                <button type="button" class="btn btn-outline-secondary" id="envCopiar" title="Copiar para pegarlo como nombre del fichero">
                    <i class="bi bi-clipboard"></i> Copiar
                </button>
            </div>
            <div class="form-text mb-3">Se guarda como nombre de la placa, con la impresora detrás del número. En el correo va con <code>.goo</code>.</div>

            <div class="small fw-semibold text-body-secondary mb-1">Imagen</div>
            <?php $elegida = (int) old('imagen_id', (string) $ultimaImagen); ?>
            <div class="d-flex flex-wrap gap-2 mb-1">
                <?php foreach ($imagenes as $img): ?>
                    <label class="border rounded p-1" style="cursor: pointer; width: 120px;">
                        <input type="radio" name="imagen_id" value="<?= (int) $img['id'] ?>" class="form-check-input"
                            data-src="<?= imagen_pieza($img, 'placa-imagen', 'v') ?>" <?= $elegida === (int) $img['id'] ? 'checked' : '' ?>>
                        <img src="<?= imagen_pieza($img, 'placa-imagen') ?>" alt="" class="d-block w-100 rounded mt-1">
                    </label>
                <?php endforeach; ?>
                <label class="border rounded p-1 small d-flex align-items-center gap-1" style="cursor: pointer;">
                    <input type="radio" name="imagen_id" value="0" class="form-check-input" data-src="" <?= $elegida === 0 ? 'checked' : '' ?>>
                    Sin imagen
                </label>
            </div>
            <div class="form-text mb-3">
                La captura de Chitubox se añade en las fotos de la
                <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar#fotos') ?>">bitácora</a>.
            </div>

            <div class="d-flex gap-2">
                <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
                <button type="button" class="btn btn-sm btn-outline-primary" id="envGuardar" title="Guardar sin enviar, para terminar luego">
                    <i class="bi bi-floppy"></i> Guardar
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="envAbrirModal" <?= $destino === '' ? 'disabled' : '' ?>>
                    <i class="bi bi-send"></i> Enviar placa…
                </button>
            </div>
            </fieldset>
        </form>
    </div>

    <div class="col-12 col-lg-7">
        <div class="small fw-semibold text-body-secondary mb-1">Vista previa</div>
        <div class="border rounded p-3 bg-white text-dark" id="envPrevia"></div>
    </div>
</div>

<div class="modal fade" id="modalImpresora" tabindex="-1" aria-hidden="true">
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
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Revisar</button>
                <button type="button" class="btn text-white px-4" style="background: var(--c);" data-m="enviar"></button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('formEnviarPlaca');
    var ENVIADAS = <?= json_encode($enviadas) ?>;
    var YA_ENVIADA = <?= json_encode($placa['enviada_en'] ? date('d/m H:i', strtotime($placa['enviada_en'])) . ($placa['impresora'] ? ' a ' . $placa['impresora'] : '') : null) ?>;
    var f = function (id) { return document.getElementById(id); };
    var MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
        'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    // Mismo criterio que Web::aMinutos(): "2:15", "2h 15", "135".
    function aMinutos(texto) {
        texto = (texto || '').toLowerCase().trim();
        var h = texto.match(/^(\d+)\s*(?::|h)\s*(\d*)/);
        if (h) return parseInt(h[1], 10) * 60 + (h[2] === '' ? 0 : parseInt(h[2], 10));
        var m = texto.match(/(\d+)/);
        return m ? parseInt(m[1], 10) : null;
    }
    function aHora(min) {
        min = ((min % 1440) + 1440) % 1440;
        return String(Math.floor(min / 60)).padStart(2, '0') + ':' + String(min % 60).padStart(2, '0');
    }
    function deHora(t) {
        var p = (t || '').split(':');
        return p.length === 2 ? parseInt(p[0], 10) * 60 + parseInt(p[1], 10) : null;
    }
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function actualizarFin() {
        var poner = deHora(f('envHora').value), dur = aMinutos(f('envDuracion').value);
        f('envFin').value = poner !== null && dur ? aHora(poner + dur) : '';
        previa();
    }
    function actualizarPoner() {
        var fin = deHora(f('envFin').value), dur = aMinutos(f('envDuracion').value);
        if (fin === null || !dur) return;
        var poner = fin - dur;
        f('envHora').value = aHora(poner);
        if (poner < 0) {
            var d = new Date(f('envFecha').value + 'T12:00');
            d.setDate(d.getDate() - 1);
            f('envFecha').value = d.toISOString().slice(0, 10);
        }
        previa();
    }

    function previa() {
        var dur = aMinutos(f('envDuracion').value);
        var fecha = f('envFecha').value ? new Date(f('envFecha').value + 'T12:00') : null;
        var radio = form.querySelector('[name="imagen_id"]:checked');
        var src = radio ? radio.getAttribute('data-src') : '';
        var enlace = f('envEnlace').value.trim();
        var fichero = f('envFichero').value.trim() ? f('envFichero').value.trim() + '.goo' : '';

        var imp = impresora();
        f('envPrevia').innerHTML =
            (imp ? '<div class="mb-3 px-3 py-2 rounded text-white imp-' + imp + '" style="background: var(--c);">'
                + '<span class="small text-uppercase" style="letter-spacing: 2px;">Impresora</span><br>'
                + '<strong style="font-size: 40px; letter-spacing: 4px;">' + imp + '</strong></div>' : '')
            + '<p class="fst-italic mb-3">PLACA #<?= $idPlaca ?> '
                + (fecha ? fecha.getDate() + ' ' + MESES[fecha.getMonth()] + ' ' + fecha.getFullYear() : '') + '</p>'
            + '<p class="mb-4">Placa que dura ' + (dur ? Math.floor(dur / 60) + ':' + String(dur % 60).padStart(2, '0') + 'h' : '—')
                + ' para ponerlo a las <strong style="font-size: 48px; vertical-align: middle;">' + esc(f('envHora').value || '—') + '</strong></p>'
            + '<p class="mb-4"><a href="' + esc(enlace) + '" target="_blank" rel="noopener" style="color: #2e7d32;">' + esc(enlace || '(sin enlace)') + '</a></p>'
            + (fichero ? '<p class="mb-3"><strong><em>' + esc(fichero) + '</em></strong></p>' : '')
            + (src ? '<img src="' + esc(src) + '" alt="" style="max-width: 100%; height: auto; display: block;">' : '');
    }

    // Como la calculadora del índice: capas × min/capa de la referencia,
    // solo impresión. Escribir la duración a mano la deja fija.
    var porCapa = parseFloat(f('envEstimado').getAttribute('data-por-capa')) || 0;
    var manual = false;

    function estimar() {
        var capas = parseInt(f('envCapas').value, 10);
        var t = capas > 0 ? Math.round(capas * porCapa) : null;
        f('envEstimado').textContent = t
            ? '≈ ' + Math.floor(t / 60) + ' h ' + (t % 60) + ' min de impresión (' + capas + ' capas × '
                + porCapa.toLocaleString('es-ES', { maximumFractionDigits: 4 }) + ' min/capa)'
            : '';
        if (t && !manual) f('envDuracion').value = Math.floor(t / 60) + ':' + String(t % 60).padStart(2, '0');
        aplicarDesfase();
        actualizarFin();
    }

    // ---- Desfase: la segunda placa del día acaba X min después que la otra --
    var autoHora = <?= old('hora') || $guardada ? 'false' : 'true' ?>;
    function dosCifras(n) { return String(n).padStart(2, '0'); }
    function aplicarDesfase() {
        var nota = f('envDesfaseNota');
        var imp = impresora(), dur = aMinutos(f('envDuracion').value);
        var previas = imp ? ocupadas(f('envFecha').value).filter(function (p) { return p.impresora === otraDe(imp) && p.acaba; }) : [];
        if (!previas.length || !dur) { nota.classList.add('d-none'); nota.innerHTML = ''; return; }

        var ultima = previas.sort(function (a, b) { return a.acaba < b.acaba ? 1 : -1; })[0];
        var desfase = parseInt(f('envDesfase').value, 10) || 0;
        var acabaOtra = new Date(ultima.acaba);
        var objetivo = new Date(acabaOtra.getTime() + desfase * 60000);
        var hm = function (d) { return dosCifras(d.getHours()) + ':' + dosCifras(d.getMinutes()); };

        if (autoHora) {
            var poner = new Date(objetivo.getTime() - dur * 60000);
            f('envFecha').value = poner.getFullYear() + '-' + dosCifras(poner.getMonth() + 1) + '-' + dosCifras(poner.getDate());
            f('envHora').value = hm(poner);
        }
        nota.innerHTML = '<i class="bi bi-arrow-left-right"></i> ' + otraDe(imp) + ' #' + ultima.id + ' acaba a las <b>' + hm(acabaOtra)
            + '</b>: esta, ' + desfase + ' min después → acaba a las <b>' + hm(objetivo) + '</b>'
            + (autoHora ? '.' : ' <span class="text-muted">(has puesto la hora a mano)</span> '
                + '<a href="#" id="envVolverDesfase">recalcular</a>');
        nota.classList.remove('d-none');
    }
    f('envDesfaseNota').addEventListener('click', function (e) {
        if (e.target.id !== 'envVolverDesfase') return;
        e.preventDefault();
        autoHora = true;
        aplicarDesfase();
        actualizarFin();
        pintarImpresora();
    });
    f('envHora').addEventListener('input', function () { autoHora = false; aplicarDesfase(); });
    f('envFin').addEventListener('change', function () { autoHora = false; aplicarDesfase(); });
    f('envDesfase').addEventListener('input', function () { aplicarDesfase(); actualizarFin(); });
    f('envGuardarDesfase').addEventListener('click', function () {
        var boton = this, datos = new FormData();
        datos.append('desfase', f('envDesfase').value);
        var csrf = form.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrf) datos.append(csrf.name, csrf.value);
        fetch('<?= site_url('piezas/desfase-impresoras') ?>', {
            method: 'POST', body: datos, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (j) {
            boton.textContent = j.ok ? 'Guardado' : 'Error';
            setTimeout(function () { boton.textContent = 'Guardar'; }, 1500);
        });
    });

    f('envCapas').addEventListener('input', function () { manual = false; estimar(); });
    f('envDuracion').addEventListener('input', function () { manual = true; });

    f('envHora').addEventListener('input', actualizarFin);
    f('envDuracion').addEventListener('input', actualizarFin);
    f('envFin').addEventListener('change', actualizarPoner);
    ['envFecha', 'envEnlace', 'envFichero'].forEach(function (id) { f(id).addEventListener('input', previa); });
    form.querySelectorAll('[name="imagen_id"]').forEach(function (r) { r.addEventListener('change', previa); });

    // ---- Impresora OLD / NEW ----------------------------------------------
    function impresora() {
        var r = form.querySelector('[name="impresora"]:checked');
        return r ? r.value : '';
    }
    function otraDe(imp) { return imp === 'OLD' ? 'NEW' : 'OLD'; }
    function ocupadas(fecha) {
        return ENVIADAS.filter(function (p) { return p.fecha === fecha; });
    }
    function avisos() {
        var imp = impresora(), lista = [], dia = ocupadas(f('envFecha').value);
        if (!imp) return lista;
        var misma = dia.filter(function (p) { return p.impresora === imp; });
        var otra = dia.filter(function (p) { return p.impresora === otraDe(imp); });
        if (misma.length) {
            lista.push(misma.map(function (p) { return '#' + p.id + ' (' + p.hora + (p.enviada ? '' : ', sin enviar') + ')'; }).join(', ')
                + ' ya va a ' + imp + ' ese día.' + (otra.length ? ' Y ' + otraDe(imp) + ' también está ocupada.' : ' Mándala como <b>' + otraDe(imp) + '</b>.'));
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
    // Igual que Web::nombreConImpresora(): "#44 OLD Pareja…".
    function ficheroCon(imp) {
        var resto = f('envFichero').value.trim().replace(/\.goo$/i, '').replace(/^#\d+\s*/, '')
            .replace(/\s*\b(OLD|NEW)\b\s*/g, ' ').trim();
        return '#<?= $idPlaca ?> ' + imp + (resto ? ' ' + resto : '');
    }
    function pintarImpresora() {
        var imp = impresora();
        var campo = f('envFichero');
        campo.classList.remove('imp-OLD', 'imp-NEW');
        if (imp) campo.classList.add('imp-' + imp);
        var caja = f('envAvisoImpresora'), lista = avisos();
        var dia = ocupadas(f('envFecha').value);
        if (!imp) {
            caja.className = 'small mb-3 text-danger';
            caja.innerHTML = '<i class="bi bi-exclamation-circle"></i> Elige a qué impresora va.';
        } else if (lista.length) {
            caja.className = 'small mb-3 alert alert-warning py-1 px-2';
            caja.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + lista.join('<br>');
        } else {
            caja.className = 'small mb-3 text-muted';
            caja.innerHTML = dia.length
                ? 'Ese día: ' + dia.map(function (p) { return p.impresora + ' #' + p.id + ' (' + p.hora + (p.enviada ? '' : ', sin enviar') + ')'; }).join(' · ')
                : 'Ese día no hay otras placas mandadas.';
        }
        previa();
    }

    form.querySelectorAll('[name="impresora"]').forEach(function (r) {
        r.addEventListener('change', function () {
            f('envFichero').value = ficheroCon(r.value);
            aplicarDesfase();
            actualizarFin();
            pintarImpresora();
        });
    });
    f('envFecha').addEventListener('input', function () { aplicarDesfase(); actualizarFin(); pintarImpresora(); });
    f('envFichero').addEventListener('input', pintarImpresora);

    f('envCopiar').addEventListener('click', function () {
        navigator.clipboard.writeText(f('envFichero').value).then(function () {
            f('envCopiar').innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(function () { f('envCopiar').innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1200);
        });
    });

    // ---- Modal de confirmación ----------------------------------------------
    var modalEl = f('modalImpresora');
    var m = function (k) { return modalEl.querySelector('[data-m="' + k + '"]'); };
    f('envAbrirModal').addEventListener('click', function () {
        if (!impresora()) { pintarImpresora(); return; }
        if (!form.reportValidity()) return;

        var imp = impresora(), lista = avisos();
        var fecha = new Date(f('envFecha').value + 'T12:00');
        modalEl.querySelector('.modal-content').className = 'modal-content overflow-hidden border-0 shadow imp-' + imp;
        m('impresora').textContent = imp;
        m('fichero').textContent = f('envFichero').value + '.goo';
        m('hora').textContent = f('envHora').value;
        m('dia').textContent = fecha.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
        m('fin').textContent = f('envFin').value || '—';
        m('duracion').textContent = f('envDuracion').value ? 'dura ' + f('envDuracion').value + ' h' : '';
        m('desfase').innerHTML = f('envDesfaseNota').classList.contains('d-none') ? '' : f('envDesfaseNota').innerHTML.replace(/<a [^>]*>.*?<\/a>/, '');
        m('avisos').innerHTML = lista.length
            ? '<div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle-fill"></i> ' + lista.join('<br>') + '</div>'
            : '<div class="alert alert-success py-2 small mb-3"><i class="bi bi-check-circle-fill"></i> Todo cuadra: ' + imp + ' está libre ese día.</div>';
        m('enviar').innerHTML = '<i class="bi bi-send-fill"></i> ' + (lista.length ? 'Enviar igualmente a ' : 'Enviar a ') + imp;
        m('enviar').disabled = false;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
    m('enviar').addEventListener('click', function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando…';
        form.querySelector('[name="confirmado"]').value = '1';
        form.submit();
    });
    var desbloquear = f('envDesbloquear');
    if (desbloquear) desbloquear.addEventListener('click', function () {
        if (!confirm('Esta placa ya se envió. ¿Desbloquear para editarla?\n\nSi cambias la impresora o la hora, avisa a quien la vaya a poner.')) return;
        f('envBloque').disabled = false;
        form.querySelector('[name="desbloqueado"]').value = '1';
        f('envCandadoIcono').className = 'bi bi-unlock-fill fs-4 text-warning';
        this.innerHTML = '<i class="bi bi-unlock"></i> Desbloqueada';
        this.disabled = true;
    });

    f('envGuardar').addEventListener('click', function () {
        this.disabled = true;
        form.querySelector('[name="accion"]').value = 'guardar';
        form.submit();
    });

    <?php if (old('duracion') || $guardada): ?>manual = true;<?php endif; ?>
    porDefecto();
    if (impresora()) f('envFichero').value = ficheroCon(impresora());
    estimar();
    pintarImpresora();
})();
</script>

<?= $this->endSection() ?>
