<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$nT = 0; $nV = 0; $nS = 0;
$hayOpc = false;
$anclas = [];
// Imprescindible / si da tiempo: botón que alterna (toma o variante).
$prio = static fn (string $tipo, int $id, bool $imp) => '<button type="button" class="rf-prio rf-prio-btn ' . ($imp ? 'imp' : 'opc') . '" data-tipo="' . $tipo . '" data-id="' . $id . '" title="Toca para cambiar">'
    . ($imp ? '<i class="bi bi-star-fill"></i>Imprescindible' : '<i class="bi bi-star"></i>Si da tiempo') . '</button>';
// Cuadrado de foto vacío: tocar para subir, arrastrar una imagen o pegarla (Ctrl+V) con él seleccionado.
$hueco = static fn (int $eid, ?int $vid, string $txt = '', string $clase = '') => '<button type="button" class="rf-foto rf-hueco ' . $clase . '" data-eid="' . $eid . '"' . ($vid ? ' data-vid="' . $vid . '"' : '')
    . ' title="' . esc(($txt !== '' ? $txt . ' — ' : '') . 'Sin foto: toca para subir, arrastra o pega una imagen', 'attr') . '"><i class="bi bi-camera"></i><span>Añadir foto</span></button>';
foreach ($plan as $f) foreach ($f['zonas'] as $z) foreach ($z['setups'] as $s) { $nS++; foreach ($s['escenas'] as $e) { $nT++; $nV += count($e['variantes']); if (($e['prioridad'] ?? 'imp') === 'opc') $hayOpc = true; foreach ($e['variantes'] as $v) if ($v['prioridad'] === 'opc') $hayOpc = true; } }
ob_start(); ?>
<a class="fv-btn" href="<?= $url('plan' . ($soloImp ? '' : '?imp=1')) ?>" aria-pressed="<?= $soloImp ? 'true' : 'false' ?>"><i class="bi <?= $soloImp ? 'bi-check-square' : 'bi-square' ?>"></i>Solo imprescindibles</a>
<a class="fv-btn fv-btn-pri" href="<?= $url('plato?orden=plan') ?>"><i class="bi bi-phone"></i>Ir al plató</a>
<?php $acciones = ob_get_clean(); ?>
<div class="fv rd" id="rfPlan" data-foto="<?= $url('plan/foto') ?>" data-prio="<?= $url('plan/prioridad') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Así queda la jornada: franja, zona en orden de ruta, setup y tomas. Con las flechas de cada «Monta» eliges qué montaje va antes en esa zona; con las de cada toma, cuál se rueda antes dentro de su setup (si no las tocas, se ordenan para cambiar los mínimos props). Sin foto: toca el cuadrado para subirla, arrástrala o pégala.'])) ?>

    <div class="rf-plan-sum fv-mono"><?= $nT ?> tomas · <?= $nV ?> variantes · <?= $nS ?> montajes de setup</div>

    <?php if (!$plan): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-list-ol"></i><div>No hay tomas todavía.</div><a class="fv-btn fv-btn-pri" href="<?= $url('revisar') ?>">Revisar ideas</a></div>
    <?php endif; ?>

    <?php foreach ($plan as $f): ?>
        <section class="rf-franja f-<?= $f['franja'] ?: 'sin' ?>">
            <h2 class="rf-franja-h"><i></i><?= esc($f['nombre']) ?></h2>
            <?php foreach ($f['zonas'] as $z): ?>
                <?php
                $zid = (int) ($z['zona']['id'] ?? 0);
                $ancla = $zid && !isset($anclas[$zid]) ? ' id="z' . $zid . '"' : ''; if ($zid) $anclas[$zid] = true;
                $reales = array_values(array_filter($z['setups'], static fn ($x) => $x['setup'] !== null));
                $si = -1;
                ?>
                <div class="fv-panel rf-pz"<?= $ancla ?>>
                    <div class="rf-pz-h">
                        <?php if ($z['zona']): ?><span class="rf-num"><?= $z['n'] ?></span><b><?= esc($z['zona']['nombre']) ?></b><?php else: ?><b class="t3">Sin zona</b><?php endif; ?>
                        <?php if (!empty($z['zona']['notas'])): ?><p><?= esc($z['zona']['notas']) ?></p><?php endif; ?>
                    </div>
                    <?php foreach ($z['setups'] as $s): if ($s['setup']) $si++; ?>
                        <div class="rf-ps">
                            <div class="rf-ps-h">
                                <span class="rf-monta"><?= $s['setup'] ? 'Monta' : 'Sin setup' ?></span>
                                <?php if ($s['setup']): ?><b><?= esc($s['setup']['nombre']) ?></b><span class="t2"><?= rod_setup_linea($s['setup']) ?></span><?php if ($s['setup']['notas']): ?><span class="rf-ps-notas"><?= esc($s['setup']['notas']) ?></span><?php endif; ?><?php endif; ?>
                                <?php if ($s['setup'] && $zid && count($reales) > 1): ?>
                                    <span class="rf-ps-orden">
                                        <?php foreach ([-1 => ['bi-arrow-up', 'Montar antes en esta zona', $si === 0], 1 => ['bi-arrow-down', 'Montar después en esta zona', $si === count($reales) - 1]] as $dir => [$ico, $tit, $off]): ?>
                                            <form method="post" action="<?= $url("ubicaciones/$zid/setup-orden") ?>"><?= csrf_field() ?>
                                                <input type="hidden" name="setup_id" value="<?= (int) $s['setup']['id'] ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><?php if ($soloImp): ?><input type="hidden" name="imp" value="1"><?php endif; ?>
                                                <button class="fv-ibtn" title="<?= $tit ?>" <?= $off ? 'disabled' : '' ?>><i class="bi <?= $ico ?>"></i></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <ol class="rf-pt">
                                <?php $prev = null; $nE = count($s['escenas']); foreach ($s['escenas'] as $ti => $e): ?>
                                    <?php $pr = rod_props($e); $punto = $puntos[$e['punto_id']]['nombre'] ?? null; ?>
                                    <li id="t<?= (int) $e['id'] ?>">
                                        <div class="rf-pt-1">
                                            <?php
                                            $ft = $fotos['t'][(int) $e['id']] ?? [];
                                            $fp = array_values(array_filter($ft, static fn ($i) => $i['origen'] === 'archivo'))[0] ?? null;
                                            $pp = $portadas[(int) $e['id']] ?? null;
                                            ?>
                                            <?php if ($fp): ?><?= view('rodajes/flujo/_foto', ['i' => $fp], ['saveData' => false]) ?>
                                            <?php elseif ($pp): ?><a class="rf-foto" href="<?= base_url($pp) ?>" target="_blank" rel="noopener"><img src="<?= base_url($pp) ?>" alt="" loading="lazy"></a>
                                            <?php else: ?><?= $hueco((int) $e['id'], null, $ft ? rod_texto_idea($ft[0]) : '') ?><?php endif; ?>
                                            <?= $prio('t', (int) $e['id'], ($e['prioridad'] ?? 'imp') !== 'opc') ?>
                                            <a href="<?= $url('escenas/edit/' . $e['id']) ?>"><?= esc(rod_titulo_escena($e)) ?></a>
                                            <?= rod_formato_chip($e) ?>
                                            <?php if ($punto): ?><small class="t3"><i class="bi bi-pin-map"></i> <?= esc($punto) ?></small><?php endif; ?>
                                            <?php if ($nE > 1 && !$soloImp): ?>
                                                <span class="rf-ps-orden rf-pt-orden">
                                                    <?php foreach ([-1 => ['bi-arrow-up', 'Rodar antes', $ti === 0], 1 => ['bi-arrow-down', 'Rodar después', $ti === $nE - 1]] as $dir => [$ico, $tit, $off]): ?>
                                                        <form method="post" action="<?= $url('escenas/' . (int) $e['id'] . '/rodaje-mover') ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="fv-ibtn" title="<?= $tit ?>" <?= $off ? 'disabled' : '' ?>><i class="bi <?= $ico ?>"></i></button></form>
                                                    <?php endforeach; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php $d = rod_props_diff($prev, $pr); if ($d !== ''): ?><div class="rf-diff"><?= $d ?></div><?php endif; ?>
                                        <?php if ($e['variantes']): ?>
                                            <ul class="rf-pv"><?php foreach ($e['variantes'] as $v): ?><li><?php $fv = $v['moodboard_item_id'] ? ($fotos['v'][(int) $v['moodboard_item_id']] ?? null) : null; if ($fv && $fv['origen'] === 'archivo'): ?><?= view('rodajes/flujo/_foto', ['i' => $fv, 'clase' => 'mini'], ['saveData' => false]) ?><?php else: ?><?= $hueco((int) $e['id'], (int) $v['id'], $fv ? rod_texto_idea($fv) : '', 'mini') ?><?php endif; ?><?= esc($v['texto']) ?><?php $vx = rod_props_extra($e, $v); if ($vx): ?> <span class="rf-mas">+ <?= esc(implode(', ', $vx)) ?></span><?php endif; ?> <?= $prio('v', (int) $v['id'], $v['prioridad'] !== 'opc') ?></li><?php endforeach; ?></ul>
                                        <?php endif; ?>
                                    </li>
                                    <?php $prev = $pr; endforeach; ?>
                            </ol>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
<input type="file" id="rfHuecoFile" accept="image/*" hidden>
<script>
(() => {
    const raiz = document.getElementById('rfPlan'), input = document.getElementById('rfHuecoFile');
    let destino = null;
    const subir = (h, file) => {
        if (!file || !file.type.startsWith('image/')) return;
        const fd = new FormData();
        fd.append('eid', h.dataset.eid);
        if (h.dataset.vid) fd.append('vid', h.dataset.vid);
        fd.append('foto', file, file.name || 'pegada.png');
        h.classList.add('subiendo'); h.disabled = true;
        fetch(raiz.dataset.foto, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' }, body: fd })
            .then(r => r.json().then(j => { if (!r.ok || !j.ok) throw new Error(j.error || 'No se ha podido subir'); return j; }))
            .then(j => {
                const a = document.createElement('a');
                a.className = h.className.replace(/\b(rf-hueco|subiendo|sel)\b/g, '').trim();
                a.href = j.url; a.target = '_blank'; a.rel = 'noopener';
                a.innerHTML = '<img alt="">'; a.firstChild.src = j.url;
                h.replaceWith(a);
            })
            .catch(err => { h.classList.remove('subiendo'); h.disabled = false; alert(err.message); });
    };
    const seleccionar = h => { raiz.querySelectorAll('.rf-hueco.sel').forEach(x => x.classList.remove('sel')); if (h) { h.classList.add('sel'); h.focus(); } destino = h; };
    raiz.addEventListener('click', ev => {
        const p = ev.target.closest('.rf-prio-btn');
        if (!p || p.disabled) return;
        p.disabled = true;
        fetch(raiz.dataset.prio, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'Content-Type': 'application/json' }, body: JSON.stringify({ tipo: p.dataset.tipo, id: p.dataset.id }) })
            .then(r => r.json()).then(j => {
                if (!j.ok) throw 0;
                const imp = j.prioridad === 'imp';
                p.classList.toggle('imp', imp); p.classList.toggle('opc', !imp);
                p.innerHTML = imp ? '<i class="bi bi-star-fill"></i>Imprescindible' : '<i class="bi bi-star"></i>Si da tiempo';
            })
            .catch(() => alert('No se ha podido guardar.'))
            .finally(() => { p.disabled = false; });
    });
    raiz.addEventListener('click', ev => {
        const h = ev.target.closest('.rf-hueco');
        if (!h) return;
        seleccionar(h);
        input.value = ''; input.click();
    });
    input.addEventListener('change', () => { if (destino && input.files[0]) subir(destino, input.files[0]); });
    // Pegar: va al cuadrado seleccionado (el último que tocaste o con el foco).
    document.addEventListener('paste', ev => {
        const h = document.activeElement && document.activeElement.closest && document.activeElement.closest('.rf-hueco') || destino;
        if (!h || !h.isConnected) return;
        const it = [...(ev.clipboardData?.items || [])].find(i => i.type.startsWith('image/'));
        if (!it) return;
        ev.preventDefault();
        subir(h, it.getAsFile());
    });
    raiz.addEventListener('focusin', ev => { const h = ev.target.closest('.rf-hueco'); if (h) seleccionar(h); });
    raiz.addEventListener('dragover', ev => { const h = ev.target.closest('.rf-hueco'); if (h) { ev.preventDefault(); h.classList.add('sobre'); } });
    raiz.addEventListener('dragleave', ev => { const h = ev.target.closest('.rf-hueco'); if (h) h.classList.remove('sobre'); });
    raiz.addEventListener('drop', ev => {
        const h = ev.target.closest('.rf-hueco');
        if (!h) return;
        ev.preventDefault(); h.classList.remove('sobre');
        subir(h, ev.dataTransfer.files[0]);
    });
})();
</script>
<?= $this->endSection() ?>
