<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$franjas = \App\Models\RodajesEscenaModel::FRANJAS;
$porZona = [];
$sinZona = [];
foreach ($escenas as $e) {
    if ($e['ubicacion_id']) $porZona[(int) $e['ubicacion_id']][] = $e;
    else $sinZona[] = $e;
}
?>
<div class="fv rd" id="rfUb" data-asignar="<?= $url('escenas') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Las zonas que han salido de las tomas. Ordena la ruta, sube una imagen guía de cada zona y toca sobre ella para marcar los puntos.'])) ?>

    <?php if ($zonas): ?>
        <div class="rf-ruta fv-panel">
            <b>Ruta</b>
            <?php foreach ($zonas as $k => $z): ?><a href="#z<?= (int) $z['id'] ?>"><span class="rf-num"><?= $k + 1 ?></span><?= esc($z['nombre']) ?></a><?php if ($k < count($zonas) - 1): ?><i class="bi bi-arrow-right"></i><?php endif; ?><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($sinZona): ?>
        <div class="rf-aviso">
            <b><?= count($sinZona) ?> <?= count($sinZona) === 1 ? 'toma sin zona' : 'tomas sin zona' ?></b>
            <?php foreach ($sinZona as $e): ?>
                <span><?= esc(rod_titulo_escena($e)) ?>
                    <select class="form-select rd-sel" data-eid="<?= (int) $e['id'] ?>" data-campo="ubicacion_id" data-recargar aria-label="Zona">
                        <option value="">Elegir zona…</option>
                        <?php foreach ($zonas as $z): ?><option value="<?= (int) $z['id'] ?>"><?= esc($z['nombre']) ?></option><?php endforeach; ?>
                    </select>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($zonas as $k => $z): ?>
        <?php $zid = (int) $z['id']; $ts = $porZona[$zid] ?? []; $ps = $puntos[$zid] ?? []; ?>
        <section class="fv-panel rf-zona" id="z<?= $zid ?>">
            <div class="rf-zona-h">
                <span class="rf-num"><?= $k + 1 ?></span>
                <h2><?= esc($z['nombre']) ?></h2>
                <small><?= count($ts) ?> <?= count($ts) === 1 ? 'toma' : 'tomas' ?><?php
                    $cuenta = [];
                    foreach ($ts as $t) if ($t['franja']) $cuenta[$franjas[$t['franja']] ?? $t['franja']] = ($cuenta[$franjas[$t['franja']] ?? $t['franja']] ?? 0) + 1;
                    foreach ($cuenta as $f => $n) echo ' · ' . esc($f) . ' ' . $n;
                ?></small>
                <div class="der rf-ord">
                    <form method="post" action="<?= $url("ubicaciones/$zid/mover") ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="-1"><button class="fv-ibtn" <?= $k === 0 ? 'disabled' : '' ?> title="Antes en la ruta"><i class="bi bi-arrow-up"></i></button></form>
                    <form method="post" action="<?= $url("ubicaciones/$zid/mover") ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="1"><button class="fv-ibtn" <?= $k === count($zonas) - 1 ? 'disabled' : '' ?> title="Después en la ruta"><i class="bi bi-arrow-down"></i></button></form>
                </div>
            </div>

            <div class="rf-zona-b">
                <div class="rf-guia-col">
                    <?php if ($z['imagen']): ?>
                        <div class="rf-guia" data-punto="<?= $url("ubicaciones/$zid/punto") ?>">
                            <img src="<?= base_url($z['imagen']) ?>" alt="Imagen guía de <?= esc($z['nombre'], 'attr') ?>">
                            <?php foreach ($ps as $pi => $p): ?>
                                <?php if ($p['x'] !== null): ?><span class="rf-pin" style="left:<?= (float) $p['x'] ?>%;top:<?= (float) $p['y'] ?>%"><b><span><?= $pi + 1 ?></span></b><em><?= esc($p['nombre']) ?></em></span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="rf-guia-pie"><small>Toca la imagen para marcar un punto</small>
                            <form method="post" action="<?= $url("ubicaciones/$zid/imagen") ?>" enctype="multipart/form-data"><?= csrf_field() ?>
                                <label class="fv-link"><input type="file" name="imagen" accept="image/*" hidden onchange="this.form.submit()">Cambiar imagen</label>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post" action="<?= $url("ubicaciones/$zid/imagen") ?>" enctype="multipart/form-data" class="rf-guia-vacia"><?= csrf_field() ?>
                            <label><input type="file" name="imagen" accept="image/*" hidden onchange="this.form.submit()">
                                <i class="bi bi-map"></i><b>Sube una imagen guía</b><span>Captura de mapa, plano o foto del sitio. Luego tocas sobre ella para marcar los puntos.</span></label>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="rf-zona-datos">
                    <form method="post" action="<?= $url("ubicaciones/$zid/guardar") ?>" class="rd-form rf-zform"><?= csrf_field() ?>
                        <div class="rd-campo"><label for="zn<?= $zid ?>">Nombre</label><input class="fv-input" id="zn<?= $zid ?>" name="nombre" value="<?= esc($z['nombre'], 'attr') ?>"></div>
                        <div class="rd-campo"><label for="zt<?= $zid ?>">Notas</label><textarea class="fv-input" id="zt<?= $zid ?>" name="notas" rows="2" placeholder="Mejor luz por la tarde, pedir permiso, mucha gente a mediodía…"><?= esc((string) $z['notas']) ?></textarea></div>
                        <button class="fv-btn fv-btn-sm"><i class="bi bi-check-lg"></i>Guardar</button>
                    </form>

                    <div class="rd-campo">
                        <span class="rd-lbl">Puntos</span>
                        <?php if ($ps): ?>
                            <ul class="rf-puntos">
                                <?php foreach ($ps as $pi => $p): ?>
                                    <li><span class="rf-pin-n"><?= $pi + 1 ?></span><?= esc($p['nombre']) ?><?php if ($p['x'] === null && $z['imagen']): ?><small>sin marcar</small><?php endif; ?>
                                        <form method="post" action="<?= $url('puntos/' . $p['id'] . '/borrar') ?>" class="der" onsubmit="return confirm('¿Quitar el punto «<?= esc($p['nombre'], 'js') ?>»?')"><?= csrf_field() ?><button class="fv-x" title="Quitar"><i class="bi bi-x"></i></button></form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <form method="post" action="<?= $url("ubicaciones/$zid/punto") ?>"><?= csrf_field() ?>
                            <input class="fv-input rf-mini-in" name="nombre" placeholder="<?= $z['imagen'] ? 'Añadir punto sin marcar + Enter' : 'Añadir punto + Enter (banco, escaparate…)' ?>" autocomplete="off">
                        </form>
                    </div>

                    <div class="rd-campo">
                        <span class="rd-lbl">Tomas aquí</span>
                        <?php if ($ts): ?>
                            <ul class="rf-tz">
                                <?php foreach ($ts as $t): ?>
                                    <li>
                                        <a href="<?= $url('escenas/edit/' . $t['id']) ?>"><?= esc(rod_titulo_escena($t)) ?></a>
                                        <small><?= esc($franjas[$t['franja']] ?? 'Sin franja') ?></small>
                                        <select class="form-select rd-sel" data-eid="<?= (int) $t['id'] ?>" data-campo="punto_id" aria-label="Punto">
                                            <option value="">Sin punto</option>
                                            <?php foreach ($ps as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $t['punto_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['nombre']) ?></option><?php endforeach; ?>
                                        </select>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?><small class="t3">Ninguna todavía.</small><?php endif; ?>
                    </div>

                    <div class="rd-herr">
                        <a class="fv-btn fv-btn-sm" href="<?= $url('escenas/create?ubicacion=' . $zid) ?>"><i class="bi bi-plus-lg"></i>Toma aquí</a>
                        <?php if (count($zonas) > 1): ?>
                            <form method="post" action="<?= $url("ubicaciones/$zid/fusionar") ?>" class="rd-herr" onsubmit="return confirm('¿Pasar todas las tomas y puntos de «<?= esc($z['nombre'], 'js') ?>» a la zona elegida y quitar esta?')"><?= csrf_field() ?>
                                <select name="destino" class="form-select rd-sel" required aria-label="Fusionar con">
                                    <option value="">Fusionar con…</option>
                                    <?php foreach ($zonas as $o): if ((int) $o['id'] === $zid) continue; ?><option value="<?= (int) $o['id'] ?>"><?= esc($o['nombre']) ?></option><?php endforeach; ?>
                                </select>
                                <button class="fv-btn fv-btn-sm">Fusionar</button>
                            </form>
                        <?php endif; ?>
                        <?php if (!$ts): ?>
                            <form method="post" action="<?= $url("ubicaciones/$zid/borrar") ?>" onsubmit="return confirm('¿Quitar la zona «<?= esc($z['nombre'], 'js') ?>»?')"><?= csrf_field() ?><button class="fv-btn fv-btn-sm fv-btn-peligro"><i class="bi bi-trash"></i></button></form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

    <form method="post" action="<?= $url('ubicaciones/crear') ?>" class="rd-herr"><?= csrf_field() ?>
        <input class="fv-input" name="nombre" placeholder="Nueva zona (cuando ves un sitio antes que la idea)" autocomplete="off" required>
        <button class="fv-btn"><i class="bi bi-plus-lg"></i>Crear zona</button>
    </form>

    <form method="post" id="rfPin" class="rf-pin-form" hidden><?= csrf_field() ?>
        <input type="hidden" name="x"><input type="hidden" name="y">
        <input class="fv-input" name="nombre" placeholder="Nombre del punto + Enter" autocomplete="off" required>
    </form>
</div>
<script>
(() => {
    const raiz = document.getElementById('rfUb');
    const pin = document.getElementById('rfPin');
    raiz.querySelectorAll('.rf-guia').forEach(g => g.addEventListener('click', ev => {
        if (ev.target.closest('.rf-pin-form')) return;
        const r = g.getBoundingClientRect();
        const x = ((ev.clientX - r.left) / r.width * 100).toFixed(2);
        const y = ((ev.clientY - r.top) / r.height * 100).toFixed(2);
        pin.action = g.dataset.punto;
        pin.x.value = x; pin.y.value = y;
        pin.style.left = x + '%'; pin.style.top = y + '%';
        g.appendChild(pin);
        pin.hidden = false;
        pin.nombre.value = '';
        pin.nombre.focus();
    }));
    pin.nombre.addEventListener('keydown', e => { if (e.key === 'Escape') pin.hidden = true; });
    raiz.addEventListener('change', ev => {
        const s = ev.target.closest('select[data-eid]');
        if (!s) return;
        fetch(raiz.dataset.asignar + '/' + s.dataset.eid + '/asignar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ campo: s.dataset.campo, valor: s.value }),
        }).then(r => { if (!r.ok) throw 0; if (s.hasAttribute('data-recargar')) location.reload(); else s.classList.add('rf-ok'); })
          .catch(() => alert('No se ha podido guardar.'));
    });
})();
</script>
<?= $this->endSection() ?>
