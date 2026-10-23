<?php
/**
 * Imágenes de referencia del pedido (lo que manda el cliente, antes de que
 * exista ninguna pieza): tira de miniaturas + visor a pantalla completa.
 * Se añaden con el botón (varias a la vez), arrastrándolas encima de la
 * tira o pegándolas (Ctrl+V) en cualquier parte de la ficha; se envían con
 * un formulario normal, igual que las fotos de una placa.
 *
 * @var array $pedido
 * @var array $imagenes filas de piezas_pedido_imagenes, ya ordenadas
 */
?>
<style>
    .referencias { display: flex; gap: .5rem; overflow-x: auto; padding: .25rem 0 .4rem; scroll-snap-type: x proximity; }
    .referencias.arrastrando { outline: 2px dashed var(--bs-primary); outline-offset: 2px; border-radius: .6rem; }
    .referencia-mini {
        flex: 0 0 auto; width: 84px; height: 84px; padding: 0; scroll-snap-align: start;
        border: 1px solid var(--bs-border-color-translucent); border-radius: .6rem; overflow: hidden;
        background: var(--bs-tertiary-bg);
    }
    .referencia-mini img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .referencia-anadir {
        flex: 0 0 auto; width: 84px; height: 84px; margin: 0; cursor: pointer;
        border: 1px dashed var(--bs-border-color); border-radius: .6rem; color: var(--bs-secondary-color);
        display: inline-flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem;
        font-size: .7rem; text-align: center; line-height: 1.1;
    }
    .referencia-anadir .bi { font-size: 1.3rem; }
    .referencia-anadir:hover { background: var(--bs-secondary-bg); }
    .referencias.subiendo .referencia-anadir { pointer-events: none; opacity: .5; }

    /* Visor: fondo negro y la imagen tan grande como quepa. */
    #visorReferencias .modal-content { background: #000; color: #fff; }
    #visorReferencias .visor-cuerpo { position: relative; flex: 1 1 auto; display: flex; align-items: center; justify-content: center; min-height: 0; touch-action: pan-y pinch-zoom; }
    #visorReferencias .visor-cuerpo img { max-width: 100%; max-height: calc(100vh - 3.5rem); object-fit: contain; user-select: none; }
    #visorReferencias .visor-flecha {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 48px; height: 48px; border: 0; border-radius: 999px; background: rgba(255,255,255,.15); color: #fff; font-size: 1.4rem;
    }
    #visorReferencias .visor-flecha:hover { background: rgba(255,255,255,.3); }
    #visorReferencias .visor-anterior { left: .75rem; }
    #visorReferencias .visor-siguiente { right: .75rem; }
    #visorReferencias .visor-barra { display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; }
</style>

<section id="referencias" class="mt-3">
    <div class="d-flex align-items-center gap-2">
        <h6 class="m-0 fw-bold">Referencias <span class="text-body-secondary fw-normal"><?= count($imagenes) ?: '' ?></span></h6>
        <span class="small text-body-tertiary d-none d-md-inline">arrastra o pega (Ctrl+V) imágenes aquí</span>
    </div>

    <form method="post" enctype="multipart/form-data" action="<?= site_url('piezas/pedido/' . (int) $pedido['id'] . '/imagen') ?>"
        class="referencias" data-referencias>
        <?= csrf_field() ?>
        <?php foreach ($imagenes as $i => $img): ?>
            <button type="button" class="referencia-mini" data-visor-abrir="<?= $i ?>" title="Ver en grande">
                <img src="<?= imagen_pieza($img, 'pedido-imagen') ?>" alt="Referencia <?= $i + 1 ?>" loading="lazy">
            </button>
        <?php endforeach; ?>

        <label class="referencia-anadir" title="Añadir imágenes de referencia (puedes elegir varias)">
            <i class="bi bi-image"></i>
            <span data-referencias-texto>Añadir<br>imágenes</span>
            <input type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple class="d-none" data-referencias-input>
        </label>
    </form>
</section>

<?php if ($imagenes): ?>
    <div class="modal fade" id="visorReferencias" tabindex="-1" aria-label="Imágenes de referencia">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="visor-barra">
                    <span class="small" data-visor-contador></span>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light rounded-pill" data-visor-original title="Abrir el original a tamaño completo">
                            <i class="bi bi-box-arrow-up-right"></i><span class="d-none d-sm-inline"> Original</span>
                        </a>
                        <form method="post" action="" data-visor-borrar onsubmit="return confirm('¿Apartar esta imagen a la papelera?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger rounded-pill" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>
                <div class="visor-cuerpo">
                    <img src="" alt="Referencia" data-visor-img>
                    <?php if (count($imagenes) > 1): ?>
                        <button type="button" class="visor-flecha visor-anterior" data-visor-paso="-1" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="visor-flecha visor-siguiente" data-visor-paso="1" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ---- Subida: botón, arrastrar o pegar ----
    var form = document.querySelector('[data-referencias]');
    var input = form.querySelector('[data-referencias-input]');

    function enviar(ficheros) {
        var imagenes = [].filter.call(ficheros, function (f) { return /^image\//.test(f.type); });
        if (!imagenes.length) return;
        var dt = new DataTransfer();
        imagenes.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        form.classList.add('subiendo');
        form.querySelector('[data-referencias-texto]').innerHTML = 'Subiendo ' + imagenes.length + '…';
        form.submit();
    }

    input.addEventListener('change', function () { enviar(input.files); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        form.addEventListener(ev, function (e) { e.preventDefault(); form.classList.add('arrastrando'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        form.addEventListener(ev, function () { form.classList.remove('arrastrando'); });
    });
    form.addEventListener('drop', function (e) { e.preventDefault(); enviar(e.dataTransfer.files); });

    // Pegar en cualquier parte de la ficha, salvo mientras se escribe en un
    // campo de texto (ahí el Ctrl+V es para el texto).
    document.addEventListener('paste', function (e) {
        var destino = e.target;
        if (destino.closest && destino.closest('input, textarea, [contenteditable]')) return;
        var ficheros = e.clipboardData ? e.clipboardData.files : [];
        if (ficheros.length) { e.preventDefault(); enviar(ficheros); }
    });

    // ---- Visor a pantalla completa ----
    var visor = document.getElementById('visorReferencias');
    if (!visor) return;

    var imagenes = <?= json_encode(array_map(fn($img) => [
        'vista'    => imagen_pieza($img, 'pedido-imagen', \App\Services\PiezaImagenesPublicas::VISTA),
        'original' => site_url('piezas/pedido-imagen/' . (int) $img['id'] . '/imagen'),
        'borrar'   => site_url('piezas/pedido-imagen/' . (int) $img['id'] . '/borrar'),
    ], $imagenes), JSON_UNESCAPED_SLASHES) ?>;
    var modal = bootstrap.Modal.getOrCreateInstance(visor);
    var img = visor.querySelector('[data-visor-img]');
    var actual = 0;

    function mostrar(i) {
        actual = (i + imagenes.length) % imagenes.length;
        img.src = imagenes[actual].vista;
        visor.querySelector('[data-visor-original]').href = imagenes[actual].original;
        visor.querySelector('[data-visor-borrar]').action = imagenes[actual].borrar;
        visor.querySelector('[data-visor-contador]').textContent = (actual + 1) + ' / ' + imagenes.length;
    }

    document.querySelectorAll('[data-visor-abrir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            mostrar(parseInt(boton.dataset.visorAbrir, 10));
            modal.show();
        });
    });
    visor.querySelectorAll('[data-visor-paso]').forEach(function (boton) {
        boton.addEventListener('click', function () { mostrar(actual + parseInt(boton.dataset.visorPaso, 10)); });
    });
    // Tocar el fondo negro (fuera de la imagen) cierra, como en cualquier visor.
    visor.querySelector('.visor-cuerpo').addEventListener('click', function (e) {
        if (e.target === this) modal.hide();
    });
    document.addEventListener('keydown', function (e) {
        if (!visor.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') mostrar(actual - 1);
        if (e.key === 'ArrowRight') mostrar(actual + 1);
    });

    // Deslizar con el dedo para pasar de una a otra en el móvil.
    var inicioX = null;
    visor.addEventListener('touchstart', function (e) {
        inicioX = e.touches.length === 1 ? e.touches[0].clientX : null;
    }, { passive: true });
    visor.addEventListener('touchend', function (e) {
        if (inicioX === null || imagenes.length < 2) return;
        var dx = e.changedTouches[0].clientX - inicioX;
        if (Math.abs(dx) > 50) mostrar(actual + (dx < 0 ? 1 : -1));
        inicioX = null;
    });
});
</script>
