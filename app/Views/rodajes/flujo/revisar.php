<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$grupo = $selId !== null ? $grupos[$selId] : null;
$pend = $grupo ? array_values(array_filter($grupo['items'], static fn ($i) => $i['estado'] === 'pendiente')) : [];
$desc = $grupo ? array_values(array_filter($grupo['items'], static fn ($i) => $i['estado'] === 'descartada')) : [];
$franjas = \App\Models\RodajesEscenaModel::FRANJAS;
?>
<div class="fv rd">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Ve temática a temática. Escribe qué es cada foto y conviértela en toma, o en variante de una toma que ya está abajo.'])) ?>

    <?php if (!$grupos): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-inbox"></i><div>No hay ideas todavía.</div><a class="fv-btn fv-btn-pri" href="<?= $url('ideas') ?>">Ir a la ingesta</a></div>
    <?php else: ?>
        <nav class="rf-temas" aria-label="Temáticas">
            <?php foreach ($grupos as $gid => $g): ?>
                <a href="<?= $url('revisar?tematica=' . $gid) ?>" class="<?= $gid === $selId ? 'on' : '' ?><?= !$g['pend'] ? ' hecha' : '' ?>">
                    <?= esc($g['nombre']) ?><small><?= $g['pend'] ? $g['pend'] . ' por asignar' : '<i class="bi bi-check-lg"></i>' ?></small>
                </a>
            <?php endforeach; ?>
        </nav>

        <section class="rf-sec" id="fotos">
            <div class="rf-sec-h"><h2>Por asignar</h2><span><?= count($pend) ?></span></div>
            <?php if (!$pend): ?>
                <div class="rf-vacio"><i class="bi bi-check2-all"></i> Todas las fotos de esta temática tienen sitio.
                    <?php foreach ($grupos as $gid => $g): if ($g['pend'] && $gid !== $selId): ?> <a class="fv-link" href="<?= $url('revisar?tematica=' . $gid) ?>">Siguiente: «<?= esc($g['nombre']) ?>» →</a><?php break; endif; endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rf-asignar">
                    <?php foreach ($pend as $i): ?>
                        <form method="post" action="<?= $url('revisar/' . $i['id']) ?>" class="fv-panel rf-asig"><?= csrf_field() ?>
                            <?= view('rodajes/flujo/_foto', ['i' => $i, 'clase' => 'xl'], ['saveData' => false]) ?>
                            <div class="rf-asig-der">
                                <?php if (!empty($i['buscar_ref'])): ?><span class="rf-flag">Buscar referencia</span><?php endif; ?>
                                <input class="fv-input" name="texto" value="<?= esc($i['origen'] === 'archivo' ? '' : rod_texto_idea($i), 'attr') ?>"
                                       placeholder="¿Qué es? «Mirando el móvil con edificio atrás» o «plano corto»" autocomplete="off">
                                <div class="rf-asig-btns">
                                    <button class="fv-btn fv-btn-pri" name="destino" value="toma"><i class="bi bi-plus-lg"></i>Nueva toma</button>
                                    <?php foreach ($tomas as $t): ?>
                                        <button class="fv-btn rf-avar" name="destino" value="v:<?= (int) $t['id'] ?>" title="Añadir como variante de esta toma">
                                            <?php $ft = $fotos['t'][(int) $t['id']][0] ?? null; if ($ft && $ft['origen'] === 'archivo'): ?><img src="<?= base_url($ft['ruta_archivo']) ?>" alt=""><?php else: ?><i class="bi bi-arrow-return-right"></i><?php endif; ?>
                                            <span>variante de <b><?= esc(rod_titulo_escena($t)) ?></b></span>
                                        </button>
                                    <?php endforeach; ?>
                                    <button class="fv-btn der rf-descartar" name="destino" value="descartar" title="Descartar" onclick="return confirm('¿Descartar esta foto? La puedes recuperar abajo.')"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <select name="mover" class="form-select rd-sel rf-mover" aria-label="Mover a otra temática" onchange="if (this.value) this.form.requestSubmit()">
                                    <option value="">No es de esta temática: mover a…</option>
                                    <?php foreach ($grupos as $gid => $g): if ($gid === $selId || $gid === 0) continue; ?><option value="m:<?= (int) $gid ?>"><?= esc($g['nombre']) ?></option><?php endforeach; ?>
                                    <?php if ($selId !== 0): ?><option value="m:0">Sin temática (vuelve a la bandeja)</option><?php endif; ?>
                                </select>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="rf-sec">
            <div class="rf-sec-h"><h2>Tomas de «<?= esc($grupo['nombre']) ?>»</h2><span><?= count($tomas) ?></span></div>
            <?php if (!$tomas): ?>
                <div class="rf-vacio">Todavía no hay tomas. Crea la primera desde una foto de arriba.</div>
            <?php else: ?>
                <div class="rf-tomas">
                    <?php foreach ($tomas as $t): ?>
                        <?php $ft = $fotos['t'][(int) $t['id']] ?? []; ?>
                        <article class="fv-panel rf-toma" id="t<?= (int) $t['id'] ?>">
                            <div class="rf-toma-cab">
                                <?php if ($ft): ?><?= view('rodajes/flujo/_foto', ['i' => $ft[0], 'clase' => 'grande'], ['saveData' => false]) ?><?php else: ?><span class="rf-foto grande vacia"><i class="bi bi-image"></i></span><?php endif; ?>
                                <div class="rf-toma-txt">
                                    <span class="t rf-editable" data-url="<?= $url('escenas/' . $t['id'] . '/titulo') ?>" title="Toca para cambiar el nombre"><?= esc(rod_titulo_escena($t)) ?></span>
                                    <div class="rf-toma-meta">
                                        <?= rod_formato_chip($t) ?>
                                        <span class="<?= $t['ubicacion_id'] ? '' : 'falta' ?>"><i class="bi bi-geo-alt"></i><?= $t['ubicacion_id'] ? esc($t['escena_ubicacion']) : 'sin zona' ?></span>
                                        <span class="<?= $t['franja'] ? '' : 'falta' ?>"><i class="bi bi-sun"></i><?= esc($franjas[$t['franja']] ?? 'sin franja') ?></span>
                                        <span class="<?= $t['setup_id'] ? '' : 'falta' ?>"><i class="bi bi-camera"></i><?= $t['setup_id'] ? 'setup' : 'sin setup' ?></span>
                                    </div>
                                    <a class="fv-link" href="<?= $url('escenas/edit/' . $t['id']) ?>"><i class="bi bi-pencil"></i>Completar ficha</a>
                                    <div class="rf-toma-acc">
                                        <?php if ($ft): ?>
                                            <form method="post" action="<?= $url('revisar/' . $ft[0]['id']) ?>" onsubmit="return confirm('¿Soltar la foto de «<?= esc(rod_titulo_escena($t), 'js') ?>»? Vuelve a «Por asignar»<?= !$t['variantes'] && count($ft) === 1 ? ' y la toma, que se queda vacía, se quita' : '' ?>.')"><?= csrf_field() ?>
                                                <button class="fv-btn fv-btn-sm" name="destino" value="deshacer" title="La foto no es de esta toma: vuelve a «Por asignar»"><i class="bi bi-x-circle"></i>Quitar foto de la toma</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= $url('escenas/' . $t['id'] . '/tematica') ?>"><?= csrf_field() ?>
                                            <select name="situacion_id" class="form-select rd-sel rf-mover" aria-label="Mover la toma a otra temática" onchange="if (this.value !== '') this.form.submit()">
                                                <option value="">Mover toma a…</option>
                                                <?php foreach ($grupos as $gid => $g): if ($gid === $selId || $gid === 0) continue; ?><option value="<?= (int) $gid ?>"><?= esc($g['nombre']) ?></option><?php endforeach; ?>
                                                <?php if ($selId !== 0): ?><option value="0">Sin temática</option><?php endif; ?>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php if (count($ft) > 1): ?>
                                <div class="rf-toma-mas"><?php foreach (array_slice($ft, 1) as $f): ?><?= view('rodajes/flujo/_foto', ['i' => $f], ['saveData' => false]) ?><?php endforeach; ?></div>
                            <?php endif; ?>
                            <ul class="rf-tvars" data-orden="<?= $url('escenas/' . $t['id'] . '/variantes/orden') ?>">
                                <?php foreach ($t['variantes'] as $v): ?>
                                    <?php $fv = $v['moodboard_item_id'] ? ($fotos['v'][(int) $v['moodboard_item_id']] ?? null) : null; ?>
                                    <li data-id="<?= (int) $v['id'] ?>">
                                        <i class="bi bi-grip-vertical rf-asa" title="Arrastra para ordenar"></i>
                                        <?php if ($fv): ?><?= view('rodajes/flujo/_foto', ['i' => $fv], ['saveData' => false]) ?><?php endif; ?>
                                        <span class="rf-editable" data-url="<?= $url('variantes/' . $v['id'] . '/texto') ?>" title="Toca para cambiar el nombre"><?= esc($v['texto']) ?></span>
                                        <form method="post" action="<?= $url('variantes/' . $v['id'] . '/principal') ?>" onsubmit="return confirm('¿Hacer principal «<?= esc($v['texto'], 'js') ?>»? La principal actual pasa a variante.')"><?= csrf_field() ?><button class="fv-x" title="Hacer principal"><i class="bi bi-arrow-up-circle"></i></button></form>
                                        <form method="post" action="<?= $url('variantes/' . $v['id'] . '/separar') ?>" onsubmit="return confirm('¿Separar «<?= esc($v['texto'], 'js') ?>» como toma propia?')"><?= csrf_field() ?><button class="fv-x" title="Separar como toma propia"><i class="bi bi-box-arrow-up-right"></i></button></form>
                                        <form method="post" action="<?= $url('variantes/' . $v['id'] . '/borrar') ?>" onsubmit="return confirm('¿Quitar la variante «<?= esc($v['texto'], 'js') ?>»?<?= $fv ? ' Su foto vuelve a «por asignar».' : '' ?>')"><?= csrf_field() ?><button class="fv-x" title="Quitar"><i class="bi bi-x"></i></button></form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="post" action="<?= $url('escenas/' . $t['id'] . '/variante') ?>" class="rf-tvar-nueva"><?= csrf_field() ?>
                                <input class="fv-input" name="texto" placeholder="+ variante sin foto (plano corto, contrapicado…) y Enter" autocomplete="off">
                            </form>
                            <?php if (count($tomas) > 1): ?>
                                <details class="rf-a-var">
                                    <summary><i class="bi bi-arrow-return-right"></i>Convertir esta toma en variante de…</summary>
                                    <form method="post" action="<?= $url('escenas/' . $t['id'] . '/a-variante') ?>" onsubmit="return confirm('¿Convertir «<?= esc(rod_titulo_escena($t), 'js') ?>» en variante? Su foto y sus variantes pasan a la toma elegida.')"><?= csrf_field() ?>
                                        <?php foreach ($tomas as $o): if ((int) $o['id'] === (int) $t['id']) continue; ?>
                                            <button class="fv-btn rf-avar" name="destino" value="<?= (int) $o['id'] ?>">
                                                <?php $fo = $fotos['t'][(int) $o['id']][0] ?? null; if ($fo && $fo['origen'] === 'archivo'): ?><img src="<?= base_url($fo['ruta_archivo']) ?>" alt=""><?php endif; ?>
                                                <span><b><?= esc(rod_titulo_escena($o)) ?></b></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($desc): ?>
            <details class="rf-sec rf-desc">
                <summary>Descartadas (<?= count($desc) ?>)</summary>
                <div class="rf-desc-lista">
                    <?php foreach ($desc as $i): ?>
                        <form method="post" action="<?= $url('revisar/' . $i['id']) ?>"><?= csrf_field() ?>
                            <?= view('rodajes/flujo/_foto', ['i' => $i], ['saveData' => false]) ?>
                            <button class="fv-link" name="destino" value="deshacer"><i class="bi bi-arrow-counterclockwise"></i>Recuperar</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.rf-editable').forEach(el => el.addEventListener('click', () => {
    if (el.querySelector('input')) return;
    const antes = el.textContent.trim();
    const inp = document.createElement('input');
    inp.className = 'fv-input rf-edit-in';
    inp.value = antes;
    el.textContent = '';
    el.appendChild(inp);
    inp.focus();
    inp.select();
    let hecho = false;
    const cerrar = txt => { hecho = true; el.textContent = txt; };
    const guardar = () => {
        if (hecho) return;
        const t = inp.value.trim();
        if (!t || t === antes) return cerrar(antes);
        hecho = true;
        inp.disabled = true;
        fetch(el.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ texto: t }),
        }).then(r => { if (!r.ok) throw 0; return r.json(); })
          .then(d => { el.textContent = d.texto; el.classList.add('rf-guardado'); setTimeout(() => el.classList.remove('rf-guardado'), 900); })
          .catch(() => { el.textContent = antes; alert('No se ha podido guardar el nombre.'); });
    };
    inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); guardar(); }
        if (e.key === 'Escape') cerrar(antes);
    });
    inp.addEventListener('blur', guardar);
}));
document.querySelectorAll('.rf-asig input[name=texto]').forEach(i => i.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); i.form.querySelector('[value=toma]').click(); }
}));
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// Ordenar variantes arrastrando por el asa; se guarda al soltar.
document.querySelectorAll('ul[data-orden]').forEach(ul => {
    if (!window.Sortable || ul.children.length < 2) return;
    Sortable.create(ul, {
        handle: '.rf-asa', animation: 150,
        onEnd: () => fetch(ul.dataset.orden, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ ids: [...ul.children].map(li => li.dataset.id) }),
        }).then(r => { if (!r.ok) throw 0; }).catch(() => alert('No se ha podido guardar el orden.')),
    });
});
</script>
<?= $this->endSection() ?>
