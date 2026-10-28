<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
$idPlaca = (int) $placa['id'];
$hm = static fn(?int $m) => $m === null ? '' : intdiv($m, 60) . ':' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
$ultimaImagen = $imagenes ? (int) end($imagenes)['id'] : 0;
?>

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
<?php elseif ($placa['enviada_en']): ?>
    <div class="alert alert-info py-2 small">
        <i class="bi bi-check2-circle"></i> Ya se envió el <?= esc(date('d/m/Y H:i', strtotime($placa['enviada_en']))) ?>
        a <?= esc($placa['enviada_a']) ?>.
    </div>
<?php endif; ?>
<?php if ($destino === ''): ?>
    <div class="alert alert-warning py-2 small">
        Falta <code>PIEZAS_PLACA_EMAIL</code> (y la configuración SMTP) en el <code>.env</code>: se puede preparar, pero no enviar.
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" id="formEnviarPlaca"
            onsubmit="return confirm('¿Enviar la placa #<?= $idPlaca ?> a <?= esc($destino, 'js') ?>?');">
            <?= csrf_field() ?>

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
            <div class="form-text mb-3" id="envAviso">
                Cambia la hora de ponerla o la de acabar y la otra se recalcula.
            </div>

            <label class="form-label small mb-1" for="envEnlace">Enlace de Drive</label>
            <input type="url" name="enlace" id="envEnlace" class="form-control form-control-sm mb-2" required
                placeholder="https://drive.google.com/file/d/…" value="<?= esc(old('enlace', $enlace), 'attr') ?>">

            <label class="form-label small mb-1" for="envFichero">Nombre del fichero</label>
            <input type="text" name="fichero" id="envFichero" class="form-control form-control-sm mb-3" maxlength="200"
                value="<?= esc(old('fichero', $fichero), 'attr') ?>">

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
                <button class="btn btn-sm btn-primary" <?= $destino === '' ? 'disabled' : '' ?>>
                    <i class="bi bi-send"></i> Enviar a <?= esc($destino ?: '…') ?>
                </button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-7">
        <div class="small fw-semibold text-body-secondary mb-1">Vista previa</div>
        <div class="border rounded p-3 bg-white text-dark" id="envPrevia"></div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('formEnviarPlaca');
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
        var fichero = f('envFichero').value.trim();

        f('envPrevia').innerHTML =
            '<p class="fst-italic mb-3">PLACA #<?= $idPlaca ?> '
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
        actualizarFin();
    }

    f('envCapas').addEventListener('input', function () { manual = false; estimar(); });
    f('envDuracion').addEventListener('input', function () { manual = true; });

    f('envHora').addEventListener('input', actualizarFin);
    f('envDuracion').addEventListener('input', actualizarFin);
    f('envFin').addEventListener('change', actualizarPoner);
    ['envFecha', 'envEnlace', 'envFichero'].forEach(function (id) { f(id).addEventListener('input', previa); });
    form.querySelectorAll('[name="imagen_id"]').forEach(function (r) { r.addEventListener('change', previa); });

    <?php if (old('duracion')): ?>manual = true;<?php endif; ?>
    estimar();
})();
</script>

<?= $this->endSection() ?>
