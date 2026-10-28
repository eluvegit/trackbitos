<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
    /**
     * La bitácora a pantalla completa — la única pantalla de edición desde
     * la fase 50 (se quitó la versión imprimible: solo la editable). Desde
     * el histórico (placas.php) se llega aquí pulsando la tarjeta
     * directamente, sin pasar por ningún modal intermedio.
     */
    $idPlaca = (int) $placa['id'];

    // Atajos a las secciones del formulario (fase 45): en pantalla ancha, un
    // índice fijo a un lado para saltar directo sin hacer scroll a ciegas;
    // en el móvil no cabe ni hace falta, se sigue bajando con el dedo.
    $secciones = [
        'info'    => 'Estado y tiempos',
        'ajustes' => 'Nombre y ajustes',
        'piezas'  => 'Qué llevaba',
        'pruebas' => 'Qué se probaba',
        'notas'   => 'Notas',
        'enlace'  => 'Enlace al Drive',
    ];

    $imagenes = $imagenes ?? [];
    $reparto  = $reparto ?? [];
    $sinMedir = $sinMedir ?? 0;
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-journal-text text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <span class="text-muted fw-normal">#<?= $idPlaca ?></span>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Editar</strong>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" class="btn btn-sm btn-outline-primary ms-auto">
        <i class="bi bi-send"></i> Enviar placa<?= $placa['enviada_en'] ? ' <i class="bi bi-check2" title="Ya enviada"></i>' : '' ?>
    </a>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="row">
    <nav class="col-md-3 col-lg-2 d-none d-md-block">
        <div class="list-group list-group-flush small position-sticky" style="top: 1rem;">
            <?php foreach ($secciones as $ancla => $titulo): ?>
                <a href="#<?= $ancla ?>" class="list-group-item list-group-item-action py-1 px-2 border-0">
                    <?= esc($titulo) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="col-12 col-md-9 col-lg-7">
        <?php // Los datos (placa, piezas, pruebas, enlaces) le llegan solos al
              // trozo incluido: son los mismos que el controlador pasó a esta
              // vista. ?>
        <?= $this->include('piezas/_bitacora_form') ?>
    </div>

    <?php // Segundo sidebar, a la derecha: la foto de la placa y si cabe o no
          // en una placa, consulta rápida mientras se rellena el resto —
          // fuera del formulario, así que alta/baja de fotos siguen siendo
          // inmediatas igual que antes. ?>
    <div class="col-12 col-lg-3" id="fotos">
        <div class="position-sticky" style="top: 1rem;">
            <div class="small fw-semibold text-body-secondary mb-1">
                <i class="bi bi-camera"></i> Fotos de la plataforma (laminador)
            </div>

            <div data-fotos-lista>
            <?php if (empty($imagenes)): ?>
                <p class="text-muted small mb-2">
                    Sin fotos todavía (captura del laminador: orientación, soportes, desde dónde partía).
                </p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2 mb-2">
                    <?php foreach ($imagenes as $img): ?>
                        <div class="position-relative">
                            <a href="<?= imagen_pieza($img, 'placa-imagen', 'v') ?>" target="_blank"
                                title="<?= esc($img['notas'] ?? '') ?>">
                                <img src="<?= imagen_pieza($img, 'placa-imagen') ?>"
                                    class="rounded border d-block" style="width: 100%; height: auto;"
                                    alt="Plataforma del laminador" loading="lazy">
                            </a>
                            <form method="post" action="<?= site_url('piezas/placa-imagen/' . (int) $img['id'] . '/borrar') ?>"
                                onsubmit="return confirm('¿Apartar esta foto a la papelera?');" class="position-absolute top-0 end-0">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .65rem;" title="Borrar">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </div>

            <?php // Se sube sola al elegirla, al soltarla encima de esta columna o al
                  // pegarla (Ctrl+V) en cualquier parte de la página, como las
                  // referencias de un pedido. Va por fetch y sin recargar: la
                  // bitácora de al lado puede tener cambios sin guardar. ?>
            <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center mb-1"
                action="<?= site_url('piezas/placa/' . $idPlaca . '/imagen') ?>" data-fotos-form>
                <?= csrf_field() ?>
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                    class="form-control form-control-sm" style="max-width: 220px;" data-fotos-input>
                <input type="text" name="notas" class="form-control form-control-sm" maxlength="150"
                    placeholder="Nota (opcional)" style="max-width: 180px;">
            </form>
            <p class="small text-body-tertiary mb-3" data-fotos-estado>Elige, arrastra aquí o pega (Ctrl+V) una captura: se guarda sola.</p>

            <?= $this->include('piezas/_bitacora_reparto') ?>
        </div>
    </div>
</div>

<?= $this->include('piezas/_bitacora_js') ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // OLD/NEW se guarda al pulsar, y el nombre de la placa vuelve con la
    // impresora ya puesta para que guardar la bitácora no la pise.
    var grupo = document.querySelector('[data-impresora-placa]');
    if (!grupo) return;
    var form = grupo.closest('form');
    var estado = document.querySelector('[data-impresora-estado]');
    var candado = form.querySelector('[data-candado]');
    var desbloqueado = form.querySelector('[data-desbloqueado]');
    if (candado) candado.addEventListener('click', function () {
        if (!confirm('Esta placa ya se envió. ¿Desbloquear la impresora y el nombre?\n\nSi los cambias, avisa a quien la vaya a poner.')) return;
        desbloqueado.value = '1';
        grupo.querySelectorAll('[name="impresora"]').forEach(function (r) { r.disabled = false; });
        form.querySelector('[name="nombre"]').readOnly = false;
        candado.innerHTML = '<i class="bi bi-unlock-fill text-warning"></i> Desbloqueada';
        candado.disabled = true;
    });
    grupo.querySelectorAll('[name="impresora"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var datos = new FormData();
            datos.append('impresora', radio.value);
            datos.append('nombre', form.querySelector('[name="nombre"]').value);
            if (desbloqueado) datos.append('desbloqueado', desbloqueado.value);
            var csrf = form.querySelector('input[name="<?= csrf_token() ?>"]');
            if (csrf) datos.append(csrf.name, csrf.value);
            estado.textContent = 'Guardando…';
            fetch(grupo.getAttribute('data-url'), {
                method: 'POST', body: datos, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (j) {
                if (j.ok) form.querySelector('[name="nombre"]').value = j.nombre;
                estado.textContent = j.mensaje || '';
            }).catch(function () { estado.textContent = 'No se pudo guardar.'; });
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var columna = document.getElementById('fotos');
    var form = columna.querySelector('[data-fotos-form]');
    var input = form.querySelector('[data-fotos-input]');
    var estado = columna.querySelector('[data-fotos-estado]');
    var textoEstado = estado.textContent;
    var subiendo = false;

    // Una a una (el servidor recibe una por petición). Al terminar se
    // repinta solo la lista de fotos, sacada de la propia página recién
    // pedida, para no tocar nada del formulario de la bitácora.
    function subir(ficheros) {
        var imagenes = [].filter.call(ficheros || [], function (f) { return /^image\//.test(f.type); });
        if (!imagenes.length || subiendo) return;
        subiendo = true;

        var errores = [];
        function siguiente(i) {
            if (i >= imagenes.length) {
                fetch(window.location.href, { credentials: 'same-origin' })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        var nueva = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-fotos-lista]');
                        if (nueva) columna.querySelector('[data-fotos-lista]').innerHTML = nueva.innerHTML;
                    })
                    .finally(function () {
                        subiendo = false;
                        input.value = '';
                        form.querySelector('[name="notas"]').value = '';
                        estado.textContent = errores.length ? errores.join(' ') : textoEstado;
                        estado.classList.toggle('text-danger', errores.length > 0);
                    });
                return;
            }

            estado.classList.remove('text-danger');
            estado.textContent = 'Subiendo' + (imagenes.length > 1 ? ' ' + (i + 1) + '/' + imagenes.length : '') + '…';
            var datos = new FormData(form);
            datos.set('imagen', imagenes[i], imagenes[i].name || 'captura.png');
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: datos
            })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (!d.ok) errores.push(d.mensaje || 'No se pudo subir.'); })
                .catch(function () { errores.push('No se pudo hablar con el servidor.'); })
                .finally(function () { siguiente(i + 1); });
        }
        siguiente(0);
    }

    input.addEventListener('change', function () { subir(input.files); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        columna.addEventListener(ev, function (e) {
            if (!e.dataTransfer || [].indexOf.call(e.dataTransfer.types, 'Files') < 0) return;
            e.preventDefault();
            columna.classList.add('bg-body-secondary');
        });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        columna.addEventListener(ev, function () { columna.classList.remove('bg-body-secondary'); });
    });
    columna.addEventListener('drop', function (e) {
        if (!e.dataTransfer || !e.dataTransfer.files.length) return;
        e.preventDefault();
        subir(e.dataTransfer.files);
    });

    // Pegar en cualquier parte de la página, salvo mientras se escribe en un
    // campo (ahí el Ctrl+V es para el texto) — igual que en los pedidos.
    document.addEventListener('paste', function (e) {
        var destino = e.target;
        if (destino.closest && destino.closest('input, textarea, select, [contenteditable]')) return;
        var ficheros = e.clipboardData ? e.clipboardData.files : [];
        if (ficheros.length) { e.preventDefault(); subir(ficheros); }
    });
});
</script>

<?= $this->endSection() ?>
