<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-clock-history text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/galeria') ?>" class="text-decoration-none text-muted fw-normal">Galería</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Placas</strong>
</h5>

<p class="text-muted small">
    Cada vez que descargas o guardas una placa desde la galería queda anotada aquí sola, con fecha
    y qué piezas llevaba. Desde aquí puedes volver a descargarla, cargarla de nuevo en la placa
    actual para reimprimir la misma combinación, ponerle un nombre que la reconozcas, o borrar la
    entrada si solo era una prueba — no borra ningún STL ni versión, solo esta anotación.
</p>

<button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnFotosPlacas">
    <i class="bi bi-image"></i> Ocultar fotos
</button>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (empty($placas)): ?>
    <p class="text-muted">
        Todavía no hay ninguna placa descargada. En cuanto bajes el zip de alguna desde la
        galería, aparecerá aquí.
    </p>
<?php else: ?>
    <?php // Tarjetas pequeñas (6 de ancho en pantalla normal, como la galería) con un
          // resumen de una línea; al pulsar se despliegan a todo el ancho con la lista
          // completa y las acciones — así se puede escanear el histórico entero de un
          // vistazo sin que cada placa ocupe una fila propia. ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2">
        <?php foreach ($placas as $placa): ?>
            <?php
                $lista = $piezas[$placa['id']] ?? [];
                $disponibles = count(array_filter($lista, static fn($p) => $p['disponible']));
                $idPlaca = (int) $placa['id'];
                $idDetalle = 'detalle-placa-' . $idPlaca;
                $fecha = strtotime($placa['creado_en']);
                // Mosaico de portada: con una sola foto, cuadrado entero;
                // con varias, hasta 4 en rejilla — así la tarjeta se
                // reconoce de un vistazo sin tener que desplegarla.
                $fotos = array_values(array_filter(array_column($lista, 'miniatura')));
            ?>
            <div class="col" data-tarjeta-placa>
                <div class="card shadow-sm h-100">
                    <?php if (count($fotos) === 1): ?>
                        <img src="<?= $fotos[0] ?>" loading="lazy" alt="" data-foto-placa
                            style="width: 100%; aspect-ratio: 1; object-fit: cover; display: block;">
                    <?php elseif (count($fotos) > 1): ?>
                        <div class="d-grid" data-foto-placa
                            style="grid-template-columns: 1fr 1fr; gap: 2px; aspect-ratio: 1; overflow: hidden; background: rgba(127,127,127,.15);">
                            <?php foreach (array_slice($fotos, 0, 4) as $foto): ?>
                                <img src="<?= $foto ?>" loading="lazy" alt=""
                                    style="width: 100%; height: 100%; object-fit: cover; display: block;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-body p-2 user-select-none" style="cursor: pointer" data-plegar-placa="<?= $idDetalle ?>">
                        <div class="small fw-semibold text-truncate" title="<?= esc($placa['nombre'], 'attr') ?>">
                            <?= esc($placa['nombre']) ?>
                        </div>
                        <div class="text-muted small">
                            <?= $fecha ? esc(date('d/m H:i', $fecha)) : '' ?>
                        </div>
                        <div class="mt-1 d-flex flex-wrap gap-1">
                            <span class="badge text-bg-secondary">
                                <?= count($lista) ?> pieza<?= count($lista) === 1 ? '' : 's' ?>
                            </span>
                            <?php if ($disponibles < count($lista)): ?>
                                <span class="badge text-bg-warning" title="Algún STL de esta placa ya no está disponible">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="<?= $idDetalle ?>" class="d-none border-top">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <div class="flex-grow-1 text-muted small"><?= esc($placa['creado_en']) ?></div>

                                <a href="<?= site_url('piezas/placa/' . $idPlaca . '/descargar') ?>"
                                    class="btn btn-sm btn-outline-primary" title="Volver a generar el zip con lo que haya ahora mismo">
                                    <i class="bi bi-download"></i> Descargar de nuevo
                                </a>
                                <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/cargar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary" title="Sustituye lo que haya ahora en la placa actual">
                                        <i class="bi bi-arrow-return-left"></i> Cargar en la placa actual
                                    </button>
                                </form>
                                <form method="post"
                                    action="<?= site_url('piezas/placa/' . $idPlaca . '/borrar') ?>"
                                    onsubmit="return confirm('¿Borrar «<?= esc($placa['nombre'], 'attr') ?>» del histórico? Los STL y versiones no se tocan, solo esta anotación.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" title="Borrar del histórico">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <form method="post" class="d-flex gap-1 mb-2" style="max-width: 420px;"
                                action="<?= site_url('piezas/placa/' . $idPlaca . '/renombrar') ?>">
                                <?= csrf_field() ?>
                                <input type="text" name="nombre" class="form-control form-control-sm"
                                    value="<?= esc($placa['nombre'], 'attr') ?>" maxlength="150" required>
                                <button class="btn btn-sm btn-outline-secondary">Renombrar</button>
                            </form>

                            <?php if (empty($lista)): ?>
                                <p class="text-muted small mb-0">Ninguna de esas versiones existe ya.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($lista as $p): ?>
                                        <li class="list-group-item px-0 py-1 d-flex align-items-center gap-2">
                                            <?php if ($p['miniatura']): ?>
                                                <img src="<?= $p['miniatura'] ?>" loading="lazy" alt="" data-foto-placa
                                                    class="rounded border flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                                            <?php endif; ?>
                                            <?php if ($p['variante'] && $p['familia']): ?>
                                                <a href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) ?>"
                                                    class="text-decoration-none text-body flex-grow-1">
                                                    <?= esc($p['familia']['nombre']) ?> / <?= esc($p['variante']['nombre']) ?>
                                                    · v<?= sprintf('%03d', (int) $p['version']['numero']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted flex-grow-1">(esa pieza ya no existe)</span>
                                            <?php endif; ?>
                                            <?php if (!$p['disponible']): ?>
                                                <span class="badge text-bg-warning" title="El STL ya no está en el almacén">
                                                    <i class="bi bi-exclamation-triangle"></i> sin STL ya
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function () {
    // Al desplegar, la tarjeta pasa de compartir el ancho de "6 de ancho"
    // con sus hermanas a ocupar toda la fila — así cabe la lista completa
    // sin quedar apretada en una columna estrecha. La clase la define
    // style.css con !important, para ganarle a las de row-cols-* sin
    // pelearse con el orden de las reglas de Bootstrap.
    document.querySelectorAll('[data-plegar-placa]').forEach(function (cabecera) {
        cabecera.addEventListener('click', function (e) {
            if (e.target.closest('form, a')) return;

            var id = cabecera.getAttribute('data-plegar-placa');
            var detalle = document.getElementById(id);
            if (!detalle) return;

            var columna = cabecera.closest('[data-tarjeta-placa]');
            var vaAAbrirse = detalle.classList.contains('d-none');

            detalle.classList.toggle('d-none');
            if (columna) columna.classList.toggle('placa-desplegada', vaAAbrirse);
        });
    });

    // ---- Mostrar/ocultar fotos --------------------------------------------
    // Visibles por defecto (es lo que pediste: más visual) — el botón es
    // para cuando prefieras una lista más densa y de texto. Se recuerda
    // entre visitas.
    var CLAVE_FOTOS_PLACAS = 'piezas_placas_fotos_ocultas';
    var btnFotos = document.getElementById('btnFotosPlacas');

    function pintarFotosPlacas(ocultas) {
        document.body.classList.toggle('ocultar-fotos-placas', ocultas);
        if (btnFotos) {
            btnFotos.innerHTML = '<i class="bi bi-image"></i> ' + (ocultas ? 'Mostrar fotos' : 'Ocultar fotos');
        }
    }

    pintarFotosPlacas(localStorage.getItem(CLAVE_FOTOS_PLACAS) === '1');

    if (btnFotos) {
        btnFotos.addEventListener('click', function () {
            var ocultar = !document.body.classList.contains('ocultar-fotos-placas');
            localStorage.setItem(CLAVE_FOTOS_PLACAS, ocultar ? '1' : '0');
            pintarFotosPlacas(ocultar);
        });
    }
})();
</script>

<?= $this->endSection() ?>
