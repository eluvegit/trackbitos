<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php $gid = (int) $guia['id']; ?>

<div class="fv pv gd">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/guias') ?>">Guías del TPV</a><i class="bi bi-chevron-right"></i><span><?= esc($guia['nombre']) ?></span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('inventario') ?>" target="_blank" class="fv-gbtn" title="Abrir el TPV inventario"><i class="bi bi-box-arrow-up-right"></i> TPV</a>
            <span class="fv-vsep"></span>
            <form method="post" action="<?= site_url('piezas/guias/' . $gid . '/borrar') ?>"
                onsubmit="return confirm('¿Borrar la guía «<?= esc($guia['nombre'], 'js') ?>» con todos sus pasos y asignaciones?');">
                <?= csrf_field() ?>
                <button type="submit" class="fv-gbtn peligro"><i class="bi bi-trash"></i> Borrar guía</button>
            </form>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/guias/' . $gid . '?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1><span class="gd-ico"><?= icono_guia($guia['icono']) ?></span> <?= esc($guia['nombre']) ?></h1>
        <div class="fv-meta">
            <span class="fv-estado <?= $guia['activa'] ? 'e-ok' : 'e-neu' ?>"><?= $guia['activa'] ? 'Visible en el TPV' : 'Oculta en el TPV' ?></span>
            <span class="fv-dot"></span>
            <span><b><?= count($pasos) ?></b> pasos</span>
            <span><b><?= count($filas) ?></b> piezas</span>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <section class="fv-panel">
        <div class="fv-ph"><h3>Datos</h3></div>
        <form method="post" action="<?= site_url('piezas/guias/' . $gid . '/actualizar') ?>" class="fv-campo gd-datos">
            <?= csrf_field() ?>
            <input type="text" name="nombre" class="fv-input" style="flex: 2 1 220px" value="<?= esc($guia['nombre'], 'attr') ?>" required title="Nombre">
            <input type="text" name="icono" class="fv-input fv-mono" style="flex: 1 1 160px" value="<?= esc($guia['icono'], 'attr') ?>" title="Icono de Bootstrap Icons (p. ej. bi-hand-index-thumb) o uno propio: svg-brazo">
            <label class="gd-switch"><input type="checkbox" role="switch" name="activa" value="1" <?= $guia['activa'] ? 'checked' : '' ?>><span></span> Visible en el TPV</label>
            <button type="submit" class="fv-btn fv-btn-pri">Guardar</button>
        </form>
    </section>

    <div>
        <div class="fv-ph" style="margin-bottom: 4px"><h3>Pasos y opciones</h3><span class="cuenta"><?= count($pasos) ?></span></div>
        <p class="es-ayuda" style="margin-bottom: 14px">En el TPV se pregunta un paso tras otro, en este orden. Los nombres se cambian cuando quieras: se guardan al salir del campo.</p>
        <div class="gd-pasos">
            <?php foreach ($pasos as $i => $paso): ?>
                <?php $pid = (int) $paso['id']; ?>
                <section class="fv-panel gd-paso">
                    <div class="gd-paso-h">
                        <span class="gd-n"><?= $i + 1 ?></span>
                        <form method="post" action="<?= site_url('piezas/guias/paso/' . $pid . '/renombrar') ?>">
                            <?= csrf_field() ?>
                            <input type="text" name="nombre" value="<?= esc($paso['nombre'], 'attr') ?>" class="fv-input gd-edit gd-tit" onchange="this.form.submit()">
                        </form>
                        <?php foreach (['subir' => 'bi-arrow-up', 'bajar' => 'bi-arrow-down'] as $sentido => $icono): ?>
                            <form method="post" action="<?= site_url('piezas/guias/paso/' . $pid . '/' . $sentido) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="fv-ibtn" title="<?= ucfirst($sentido) ?> paso"><i class="bi <?= $icono ?>"></i></button>
                            </form>
                        <?php endforeach; ?>
                        <form method="post" action="<?= site_url('piezas/guias/paso/' . $pid . '/borrar') ?>"
                            onsubmit="return confirm('¿Borrar el paso «<?= esc($paso['nombre'], 'js') ?>» con sus opciones? Se pierden las piezas asignadas de esta guía que dependían de él.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="fv-ibtn peligro" title="Borrar paso"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    <div class="gd-ops">
                        <?php foreach ($paso['opciones'] as $o): ?>
                            <?php $oid = (int) $o['id']; ?>
                            <div class="gd-op">
                                <form method="post" action="<?= site_url('piezas/guias/opcion/' . $oid . '/renombrar') ?>">
                                    <?= csrf_field() ?>
                                    <input type="text" name="nombre" value="<?= esc($o['nombre'], 'attr') ?>" class="fv-input gd-edit" onchange="this.form.submit()">
                                </form>
                                <span class="pv-acc">
                                    <?php foreach (['subir' => 'bi-arrow-up', 'bajar' => 'bi-arrow-down'] as $sentido => $icono): ?>
                                        <form method="post" action="<?= site_url('piezas/guias/opcion/' . $oid . '/' . $sentido) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="fv-ibtn" title="<?= ucfirst($sentido) ?>"><i class="bi <?= $icono ?>"></i></button>
                                        </form>
                                    <?php endforeach; ?>
                                    <form method="post" action="<?= site_url('piezas/guias/opcion/' . $oid . '/borrar') ?>"
                                        onsubmit="return confirm('¿Borrar la opción «<?= esc($o['nombre'], 'js') ?>»? Se pierden sus piezas asignadas.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="fv-ibtn peligro" title="Borrar opción"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" action="<?= site_url('piezas/guias/pasos/' . $pid . '/opciones/crear') ?>" class="fv-campo gd-nueva">
                        <?= csrf_field() ?>
                        <input type="text" name="nombre" class="fv-input" placeholder="Nueva opción" required>
                        <button type="submit" class="fv-ibtn" title="Añadir opción"><i class="bi bi-plus-lg"></i></button>
                    </form>
                </section>
            <?php endforeach; ?>

            <section class="fv-panel gd-paso gd-paso-nuevo">
                <div class="gd-paso-h"><span class="gd-n"><i class="bi bi-plus"></i></span><h3>Paso nuevo</h3></div>
                <form method="post" action="<?= site_url('piezas/guias/' . $gid . '/pasos/crear') ?>" class="pv-campos">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" class="fv-input" placeholder="Nombre (p. ej. Acabado)" required>
                    <input type="text" name="por_defecto" class="fv-input" placeholder="Opción por defecto" value="Estándar" required title="Opción por defecto">
                    <button type="submit" class="fv-btn"><i class="bi bi-plus-lg"></i> Añadir paso</button>
                </form>
                <p class="es-ayuda">Las piezas ya asignadas pasan a la opción por defecto del paso nuevo: no se pierde nada.</p>
            </section>
        </div>
    </div>

    <section class="fv-panel">
        <div class="fv-ph"><h3>Piezas de esta guía</h3><span class="cuenta"><?= count($filas) ?></span></div>
<?php if (session('errores_guia')): ?>
    <div class="fv-flash fv-flash-err" style="display:block">
        <div style="font-weight:600">No se ha guardado:</div>
        <ul style="margin:4px 0 0"><?php foreach (session('errores_guia') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($pasos === []): ?>
    <p class="fv-vacio">Añade al menos un paso con sus opciones para poder meter piezas.</p>
<?php else: ?>
    <p class="es-ayuda">Cada pieza con la opción que le toca en cada paso. Cada pieza va una sola vez, y cada combinación lleva a una sola pieza.</p>

    <?php
        $fila = static function ($i, array $f) use ($pasos, $nombres, $fotos): string {
            $vid  = (int) ($f['variante'] ?? 0);
            $foto = $vid ? ($fotos[$vid] ?? '') : '';
            $html = '<tr data-fila><td><div class="gd-pieza">'
                . '<img src="' . esc($foto, 'attr') . '" alt="" data-foto class="gd-foto" style="' . ($foto ? '' : 'visibility:hidden;') . '">'
                . '<select name="filas[' . $i . '][variante]" class="fv-input" style="min-width:14rem;" data-piezas required>'
                . '<option value="">Elige pieza…</option>'
                . ($vid && isset($nombres[$vid]) ? '<option value="' . $vid . '" selected>' . esc($nombres[$vid]) . '</option>' : '')
                . '</select></div></td>';
            foreach ($pasos as $p) {
                $actual = (int) ($f['opciones'][$p['id']] ?? 0);
                $html .= '<td><select name="filas[' . $i . '][opciones][' . (int) $p['id'] . ']" class="fv-input" data-opcion required>'
                    . '<option value="">—</option>';
                foreach ($p['opciones'] as $o) {
                    $html .= '<option value="' . (int) $o['id'] . '"' . ((int) $o['id'] === $actual ? ' selected' : '') . '>' . esc($o['nombre']) . '</option>';
                }
                $html .= '</select></td>';
            }

            return $html . '<td class="t-r"><button type="button" class="fv-ibtn peligro" data-quitar title="Quitar de la guía"><i class="bi bi-x-lg"></i></button></td></tr>';
        };
    ?>

    <form method="post" action="<?= site_url('piezas/guias/' . $gid . '/resultados') ?>" data-form-piezas>
        <?= csrf_field() ?>
        <div class="pi-twrap">
            <table class="pi-tabla gd-tabla">
                <thead>
                    <tr>
                        <th>Pieza</th>
                        <?php foreach ($pasos as $p): ?>
                            <th><?= esc($p['nombre']) ?></th>
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

        <div class="fv-flash fv-flash-err" data-aviso-duplicadas hidden>
            Hay piezas con la misma combinación (en rojo): cambia alguna antes de guardar.
        </div>

        <div class="gd-pie">
            <button type="button" class="fv-btn" data-anadir><i class="bi bi-plus-lg"></i> Añadir pieza</button>
            <button type="submit" class="fv-btn fv-btn-pri" data-guardar><i class="bi bi-check-lg"></i> Guardar piezas</button>
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

    </section>
</div>

<?= $this->endSection() ?>
