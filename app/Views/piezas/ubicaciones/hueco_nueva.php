<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    $total = array_sum(array_column($filas, 'stock'));
    $opcionesDestino = static function () use ($destinosPorEstuche): string {
        ob_start();
        foreach ($destinosPorEstuche as $grupo): ?>
            <optgroup label="<?= esc($grupo['estuche']['codigo']) ?>">
                <?php foreach ($grupo['huecos'] as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= esc($h['codigo']) ?><?= $h['libre'] ? ' (libre)' : '' ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach;
        return ob_get_clean();
    };
?>

<div class="fv pv hu">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/ubicaciones') ?>">Ubicaciones</a><i class="bi bi-chevron-right"></i><span><?= esc($codigo) ?></span></nav>
        <div class="fv-acciones">
            <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/borrar') ?>"
                onsubmit="return confirm('¿Borrar el hueco «<?= esc($codigo, 'js') ?>»? Lo que había aquí queda sin ubicación, no se pierde.');">
                <?= csrf_field() ?>
                <button type="submit" class="fv-gbtn peligro"><i class="bi bi-trash"></i> Borrar hueco</button>
            </form>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1 class="fv-mono"><?= esc($codigo) ?></h1>
        <div class="fv-meta">
            <?php if ($filas === []): ?>
                <span class="fv-estado e-neu">Vacío</span>
            <?php else: ?>
                <span><b><?= count($filas) ?></b> pieza<?= count($filas) === 1 ? '' : 's' ?></span>
                <span><b><?= (int) $total ?></b> ud.</span>
            <?php endif; ?>
            <?php if ($estuche): ?>
                <span class="fv-dot"></span>
                <span><i class="bi bi-archive"></i> Estuche <?= esc($estuche['codigo']) ?></span>
                <?php if ($estuche['zona']): ?><span><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if ($hueco['notas']): ?>
        <div class="fv-banner"><i class="bi bi-sticky"></i><span><?= nl2br(esc($hueco['notas'])) ?></span></div>
    <?php endif; ?>

    <div class="al-grid hu-grid">
        <div class="fv-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Contenido</h3><span class="cuenta"><?= count($filas) ?></span></div>
                <?php if ($filas === []): ?>
                    <p class="fv-vacio">Vacío por ahora.</p>
                <?php else: ?>
                    <div class="pv-lista">
                        <?php foreach ($filas as $f): ?>
                            <?php $vid = (int) $f['variante']['id']; ?>
                            <div class="pv-fila">
                                <span class="pv-ico g"><?php if ($f['img']): ?><img src="<?= esc($f['img'], 'attr') ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?></span>
                                <div class="pv-cuerpo">
                                    <a href="<?= site_url('piezas/existencias/' . $vid) ?>" class="pv-nom" title="<?= esc($f['nombre'], 'attr') ?>"><?= esc($f['nombre']) ?></a>
                                    <?php if (!empty($destinos)): ?>
                                        <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/mover-pieza') ?>" class="hu-mover" id="mover-<?= $vid ?>" hidden>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="variante_id" value="<?= $vid ?>">
                                            <select name="destino_id" required class="fv-input">
                                                <option value="">Mover a…</option>
                                                <?= $opcionesDestino() ?>
                                            </select>
                                            <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri">Mover</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <span class="pv-n"><?= (int) $f['stock'] ?><small>ud.</small></span>
                                <div class="pv-acc">
                                    <?php if (!empty($destinos)): ?>
                                        <button type="button" class="fv-ibtn" data-mover="mover-<?= $vid ?>" title="Mover a otro hueco"><i class="bi bi-arrow-right-circle"></i></button>
                                    <?php endif; ?>
                                    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/quitar-pieza') ?>"
                                        onsubmit="return confirm('¿Quitar «<?= esc($f['nombre'], 'js') ?>» de este hueco? Queda sin asignar.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="variante_id" value="<?= $vid ?>">
                                        <button type="submit" class="fv-ibtn peligro" title="Quitar del hueco (queda sin asignar)"><i class="bi bi-box-arrow-up"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="fv-col">
            <section class="fv-panel">
                <div class="fv-ph"><i class="bi bi-box-arrow-in-down t3"></i><h3>Traer pieza aquí</h3></div>
                <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/traer-pieza') ?>" class="hu-traer" data-traer-form>
                    <?= csrf_field() ?>
                    <div class="fv-campo">
                        <input type="text" class="fv-input" placeholder="Busca por nombre o SKU…" data-traer-buscar autocomplete="off">
                        <button type="submit" class="fv-btn fv-btn-pri" data-traer-enviar disabled>Traer</button>
                    </div>
                    <div class="al-res" data-traer-resultados></div>
                    <input type="hidden" name="variante_id" data-traer-id>
                </form>
                <p class="es-ayuda">La pieza pasa a vivir aquí, tenga unidades o no. Si estaba en otro hueco, sale de él.</p>
            </section>

            <?php if (!empty($destinos) && $filas !== []): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><i class="bi bi-box-arrow-right t3"></i><h3>Vaciar este hueco</h3></div>
                    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/mover') ?>" class="fv-campo"
                        onsubmit="return confirm('¿Mover todo lo de <?= esc($codigo, 'js') ?> al hueco elegido?');">
                        <?= csrf_field() ?>
                        <select name="destino_id" required class="fv-input">
                            <option value="">Mover todo a…</option>
                            <?= $opcionesDestino() ?>
                        </select>
                        <button type="submit" class="fv-btn">Mover todo</button>
                    </form>
                    <p class="es-ayuda">Para fusionar dos huecos: mueve lo de aquí y luego borra el que quede vacío. Para separar una sola pieza, usa <i class="bi bi-arrow-right-circle"></i> en su fila.</p>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-mover]').forEach(function (b) {
        b.addEventListener('click', function () {
            var f = document.getElementById(b.dataset.mover);
            f.hidden = !f.hidden;
            if (!f.hidden) f.querySelector('select').focus();
        });
    });

    var form = document.querySelector('[data-traer-form]');
    if (!form) return;
    var caja = form.querySelector('[data-traer-buscar]'), resultados = form.querySelector('[data-traer-resultados]'),
        idInput = form.querySelector('[data-traer-id]'), enviar = form.querySelector('[data-traer-enviar]'), espera = null;
    var urlBuscar = '<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/pieza-buscar') ?>';

    caja.addEventListener('input', function () {
        idInput.value = '';
        enviar.disabled = true;
        clearTimeout(espera);
        var q = caja.value.trim();
        if (q.length < 2) { resultados.innerHTML = ''; return; }
        espera = setTimeout(function () {
            fetch(urlBuscar + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var lista = d.resultados || [];
                    resultados.innerHTML = '';
                    if (!lista.length) {
                        var v = document.createElement('div'); v.className = 'vacio'; v.textContent = 'Sin resultados'; resultados.appendChild(v);
                        return;
                    }
                    lista.forEach(function (p) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.innerHTML = '<span></span><span></span>';
                        b.firstChild.textContent = p.nombre;
                        b.lastChild.textContent = p.stock + ' ud.' + (p.hueco ? ' · en ' + p.hueco : '');
                        b.addEventListener('click', function () {
                            idInput.value = p.id;
                            caja.value = p.nombre;
                            enviar.disabled = false;
                            resultados.innerHTML = '';
                        });
                        resultados.appendChild(b);
                    });
                });
        }, 250);
    });
})();
</script>

<?= $this->endSection() ?>
