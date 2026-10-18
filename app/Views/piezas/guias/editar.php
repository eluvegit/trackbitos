<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php $gid = (int) $guia['id']; ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <?= icono_guia($guia['icono'], 'text-primary') ?>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/guias') ?>" class="text-decoration-none text-muted fw-normal">Guías del TPV</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($guia['nombre']) ?></strong>

    <form method="post" action="<?= site_url('piezas/guias/' . $gid . '/borrar') ?>" class="ms-auto"
        onsubmit="return confirm('¿Borrar la guía «<?= esc($guia['nombre'], 'js') ?>» con todos sus pasos y asignaciones?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Borrar guía</button>
    </form>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('piezas/guias/' . $gid . '/actualizar') ?>" class="d-flex flex-wrap gap-2 align-items-center mb-4">
    <?= csrf_field() ?>
    <input type="text" name="nombre" class="form-control form-control-sm" style="max-width: 18rem;" value="<?= esc($guia['nombre'], 'attr') ?>" required>
    <input type="text" name="icono" class="form-control form-control-sm" style="max-width: 12rem;" value="<?= esc($guia['icono'], 'attr') ?>" title="Icono de Bootstrap Icons (p. ej. bi-hand-index-thumb) o uno propio: svg-brazo">
    <div class="form-check form-switch ms-2">
        <input class="form-check-input" type="checkbox" role="switch" name="activa" value="1" id="activa" <?= $guia['activa'] ? 'checked' : '' ?>>
        <label class="form-check-label small" for="activa">Visible en el TPV</label>
    </div>
    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
</form>

<div class="small fw-semibold text-body-secondary mb-2"><i class="bi bi-list-ol"></i> Pasos y opciones</div>
<p class="text-muted small mb-2">En el TPV se pregunta un paso tras otro, en este orden. Los nombres se pueden cambiar cuando quieras: se guardan al salir del campo.</p>

<div class="row g-3 mb-3">
    <?php foreach ($pasos as $i => $paso): ?>
        <?php $pid = (int) $paso['id']; ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-1">
                    <span class="badge text-bg-primary me-1"><?= $i + 1 ?></span>
                    <form method="post" action="<?= site_url('piezas/guias/paso/' . $pid . '/renombrar') ?>" class="flex-grow-1">
                        <?= csrf_field() ?>
                        <input type="text" name="nombre" value="<?= esc($paso['nombre'], 'attr') ?>" class="form-control form-control-sm fw-semibold" onchange="this.form.submit()">
                    </form>
                    <?php foreach (['subir' => 'bi-arrow-up', 'bajar' => 'bi-arrow-down'] as $sentido => $icono): ?>
                        <form method="post" action="<?= site_url('piezas/guias/paso/' . $pid . '/' . $sentido) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="<?= ucfirst($sentido) ?> paso"><i class="bi <?= $icono ?>"></i></button>
                        </form>
                    <?php endforeach; ?>
                    <form method="post" action="<?= site_url('piezas/guias/paso/' . $pid . '/borrar') ?>"
                        onsubmit="return confirm('¿Borrar el paso «<?= esc($paso['nombre'], 'js') ?>» con sus opciones? Se pierden las piezas asignadas de esta guía que dependían de él.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Borrar paso"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($paso['opciones'] as $o): ?>
                        <?php $oid = (int) $o['id']; ?>
                        <li class="list-group-item d-flex align-items-center gap-1 py-1">
                            <form method="post" action="<?= site_url('piezas/guias/opcion/' . $oid . '/renombrar') ?>" class="flex-grow-1">
                                <?= csrf_field() ?>
                                <input type="text" name="nombre" value="<?= esc($o['nombre'], 'attr') ?>" class="form-control form-control-sm border-0 px-1" onchange="this.form.submit()">
                            </form>
                            <?php foreach (['subir' => 'bi-arrow-up', 'bajar' => 'bi-arrow-down'] as $sentido => $icono): ?>
                                <form method="post" action="<?= site_url('piezas/guias/opcion/' . $oid . '/' . $sentido) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-secondary py-0 px-1" title="<?= ucfirst($sentido) ?>"><i class="bi <?= $icono ?>"></i></button>
                                </form>
                            <?php endforeach; ?>
                            <form method="post" action="<?= site_url('piezas/guias/opcion/' . $oid . '/borrar') ?>"
                                onsubmit="return confirm('¿Borrar la opción «<?= esc($o['nombre'], 'js') ?>»? Se pierden sus piezas asignadas.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-link text-danger py-0 px-1" title="Borrar opción"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item py-2">
                        <form method="post" action="<?= site_url('piezas/guias/pasos/' . $pid . '/opciones/crear') ?>" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nueva opción" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i></button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form method="post" action="<?= site_url('piezas/guias/' . $gid . '/pasos/crear') ?>" class="d-flex flex-wrap gap-2 align-items-center mb-1">
    <?= csrf_field() ?>
    <input type="text" name="nombre" class="form-control form-control-sm" style="max-width: 16rem;" placeholder="Nuevo paso (p. ej. Acabado)" required>
    <input type="text" name="por_defecto" class="form-control form-control-sm" style="max-width: 14rem;" placeholder="Opción por defecto" value="Estándar" required>
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Añadir paso</button>
</form>
<p class="text-muted small mb-4">Las piezas ya asignadas pasan a la opción por defecto del paso nuevo: no se pierde nada. Después añade las demás opciones y asígnales sus piezas.</p>

<div class="small fw-semibold text-body-secondary mb-2"><i class="bi bi-puzzle"></i> Piezas de esta guía</div>

<?php if (session('errores_guia')): ?>
    <div class="alert alert-danger py-2">
        <div class="fw-semibold mb-1">No se ha guardado:</div>
        <ul class="mb-0 small"><?php foreach (session('errores_guia') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($pasos === []): ?>
    <p class="text-muted small">Añade al menos un paso con sus opciones para poder meter piezas.</p>
<?php else: ?>
    <p class="text-muted small mb-2">Cada pieza con la opción que le toca en cada paso. Cada pieza va una sola vez, y cada combinación lleva a una sola pieza.</p>

    <?php
        $fila = static function ($i, array $f) use ($pasos, $nombres, $fotos): string {
            $vid  = (int) ($f['variante'] ?? 0);
            $foto = $vid ? ($fotos[$vid] ?? '') : '';
            $html = '<tr data-fila><td><div class="d-flex align-items-center gap-2">'
                . '<img src="' . esc($foto, 'attr') . '" alt="" data-foto class="rounded border" style="width:44px;height:44px;object-fit:cover;' . ($foto ? '' : 'visibility:hidden;') . '">'
                . '<select name="filas[' . $i . '][variante]" class="form-select form-select-sm" style="min-width:14rem;" data-piezas required>'
                . '<option value="">Elige pieza…</option>'
                . ($vid && isset($nombres[$vid]) ? '<option value="' . $vid . '" selected>' . esc($nombres[$vid]) . '</option>' : '')
                . '</select></div></td>';
            foreach ($pasos as $p) {
                $actual = (int) ($f['opciones'][$p['id']] ?? 0);
                $html .= '<td><select name="filas[' . $i . '][opciones][' . (int) $p['id'] . ']" class="form-select form-select-sm" data-opcion required>'
                    . '<option value="">—</option>';
                foreach ($p['opciones'] as $o) {
                    $html .= '<option value="' . (int) $o['id'] . '"' . ((int) $o['id'] === $actual ? ' selected' : '') . '>' . esc($o['nombre']) . '</option>';
                }
                $html .= '</select></td>';
            }

            return $html . '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" data-quitar title="Quitar de la guía"><i class="bi bi-x-lg"></i></button></td></tr>';
        };
    ?>

    <form method="post" action="<?= site_url('piezas/guias/' . $gid . '/resultados') ?>" data-form-piezas>
        <?= csrf_field() ?>
        <div class="table-responsive mb-2">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th class="small">Pieza</th>
                        <?php foreach ($pasos as $p): ?>
                            <th class="small"><?= esc($p['nombre']) ?></th>
                        <?php endforeach; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-filas>
                    <?php foreach (array_values($filas) as $i => $f): ?>
                        <?= $fila($i, $f) ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-danger py-2 small" data-aviso-duplicadas hidden>
            Hay piezas con la misma combinación (en rojo): cambia alguna antes de guardar.
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" data-anadir><i class="bi bi-plus-lg"></i> Añadir pieza</button>
            <button type="submit" class="btn btn-sm btn-primary" data-guardar><i class="bi bi-check-lg"></i> Guardar piezas</button>
        </div>
    </form>

    <template id="fila-nueva"><?= $fila('__i__', []) ?></template>

    <?php // Las piezas se escriben una sola vez y cada <select> las copia al
          // abrirlo: repetirlas en todas las filas haría la página enorme. ?>
    <template id="opciones-piezas">
        <option value="">Elige pieza…</option>
        <?php foreach ($variantes as $cat): ?>
            <optgroup label="<?= esc($cat['nombre'], 'attr') ?>">
                <?php foreach ($cat['variantes'] as $vid => $nombre): ?>
                    <option value="<?= $vid ?>"><?= esc($nombre) ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </template>

    <script>
        (function () {
            var form      = document.querySelector('[data-form-piezas]');
            var cuerpo    = form.querySelector('[data-filas]');
            var aviso     = form.querySelector('[data-aviso-duplicadas]');
            var guardar   = form.querySelector('[data-guardar]');
            var plantilla = document.getElementById('opciones-piezas');
            var nueva     = document.getElementById('fila-nueva');
            var fotos     = <?= json_encode((object) $fotos) ?>;
            var siguiente = <?= count($filas) ?>;

            // Cada vez que se abre, sin las piezas que ya están en otra fila:
            // una pieza solo puede estar una vez en la guía.
            var cargar = function (sel) {
                var valor = sel.value;
                var usadas = {};
                cuerpo.querySelectorAll('[data-piezas]').forEach(function (otro) {
                    if (otro !== sel && otro.value) usadas[otro.value] = true;
                });
                sel.innerHTML = plantilla.innerHTML;
                sel.querySelectorAll('option').forEach(function (o) {
                    if (usadas[o.value]) o.remove();
                });
                sel.querySelectorAll('optgroup').forEach(function (g) {
                    if (!g.children.length) g.remove();
                });
                sel.value = valor;
            };

            var revisar = function () {
                var vistas = {};
                var filas = cuerpo.querySelectorAll('[data-fila]');
                filas.forEach(function (tr) {
                    var ops = Array.prototype.map.call(tr.querySelectorAll('[data-opcion]'), function (s) { return s.value; });
                    tr._clave = ops.every(Boolean) ? ops.slice().sort().join('-') : null;
                    if (tr._clave) vistas[tr._clave] = (vistas[tr._clave] || 0) + 1;
                });
                var hay = false;
                filas.forEach(function (tr) {
                    var dup = tr._clave && vistas[tr._clave] > 1;
                    tr.classList.toggle('table-danger', !!dup);
                    hay = hay || dup;
                });
                aviso.hidden = !hay;
                guardar.disabled = hay;
            };

            form.addEventListener('mousedown', function (e) {
                if (e.target.matches('[data-piezas]')) cargar(e.target);
            });
            form.addEventListener('focusin', function (e) {
                if (e.target.matches('[data-piezas]')) cargar(e.target);
            });
            form.addEventListener('change', function (e) {
                if (e.target.matches('[data-piezas]')) {
                    var img = e.target.closest('tr').querySelector('[data-foto]');
                    img.src = fotos[e.target.value] || '';
                    img.style.visibility = fotos[e.target.value] ? 'visible' : 'hidden';
                }
                revisar();
            });
            form.addEventListener('click', function (e) {
                var b = e.target.closest('[data-quitar]');
                if (!b) return;
                var sel = b.closest('tr').querySelector('[data-piezas]');
                var nombre = sel.selectedIndex > 0 ? sel.options[sel.selectedIndex].text : '';
                if (nombre && !confirm('¿Quitar «' + nombre + '» de la guía? (no se guarda hasta pulsar Guardar)')) return;
                b.closest('tr').remove();
                revisar();
            });
            form.querySelector('[data-anadir]').addEventListener('click', function () {
                cuerpo.insertAdjacentHTML('beforeend', nueva.innerHTML.replace(/__i__/g, siguiente++));
                var sel = cuerpo.lastElementChild.querySelector('[data-piezas]');
                cargar(sel);
                sel.focus();
            });

            revisar();
        })();
    </script>
<?php endif; ?>

<?= $this->endSection() ?>
