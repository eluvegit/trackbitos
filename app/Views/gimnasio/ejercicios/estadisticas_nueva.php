<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$volverAqui = 'gimnasio/ejercicios/estadisticas/' . $ejercicio['id'];
$esPrinc = !empty($ejercicio['principal']);
$acciones = '<button type="button" class="gm-star' . ($esPrinc ? ' on' : '') . '" data-gm-star="' . site_url('gimnasio/ejercicios/principal/' . $ejercicio['id']) . '" title="' . ($esPrinc ? 'Quitar de principales' : 'Marcar como principal (sale en Estadísticas)') . '"><i class="bi ' . ($esPrinc ? 'bi-star-fill' : 'bi-star') . '"></i></button>'
    . '<a class="fv-gbtn" href="' . site_url('gimnasio/ejercicios/edit/' . $ejercicio['id']) . '"><i class="bi bi-pencil"></i> Editar</a>';

// Sesiones agrupadas (para el historial)
$porSesion = [];
foreach ($seriesDetalle as $f) {
    $porSesion[$f['fecha']]['id'] = $f['entrenamiento_id'];
    $porSesion[$f['fecha']]['series'][] = $f;
}
$prFecha = $pr['fecha'] ?? null;
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'ejercicios',
        'migas' => ['Ejercicios' => site_url('gimnasio/ejercicios'), $ejercicio['nombre'] => null],
        'titulo' => esc($ejercicio['nombre']), 'acciones' => $acciones,
        'sub' => '<span>' . esc(gim_grupo_nombre($ejercicio['grupo_muscular'])) . '</span><span class="fv-dot"></span><span>' . $sesiones . ($sesiones === 1 ? ' sesión' : ' sesiones') . '</span>']) ?>

    <?php if ($progresion): ?>
        <?php $diff = round($ultimo['e1rm'] - $pr['e1rm'], 1); ?>
        <div class="gm-cifras">
            <div class="gm-cifra">
                <span class="k"><i class="bi bi-trophy-fill"></i> Récord · 1RM est.</span>
                <span class="v"><?= gim_kg($pr['e1rm']) ?><small>kg</small></span>
                <span class="s"><?= gim_kg($pr['peso']) ?> kg × <?= $pr['reps'] ?> · <?= esc(gim_fecha_corta($pr['fecha'], false)) ?></span>
            </div>
            <div class="gm-cifra">
                <span class="k">Última sesión</span>
                <span class="v"><?= gim_kg($ultimo['e1rm']) ?><small>kg</small></span>
                <span class="s"><?= gim_kg($ultimo['peso']) ?> kg × <?= $ultimo['reps'] ?> ·
                    <?php if ($diff == 0): ?><span class="gm-delta ok">= récord</span><?php else: ?><span class="gm-delta mal"><?= str_replace('.', ',', (string) $diff) ?> kg</span><?php endif; ?></span>
            </div>
            <div class="gm-cifra">
                <span class="k">Mejor peso</span>
                <?php $maxPeso = max(array_column($progresion, 'peso')); ?>
                <span class="v"><?= gim_kg($maxPeso) ?><small>kg</small></span>
                <span class="s">el más alto movido</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="gm-grid2">
        <div style="display:grid;gap:20px">
            <?php if (count($progresion) >= 2): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>1RM estimado</h3><span class="cuenta">mejor serie de cada sesión · <i class="bi bi-circle-fill gm-pr" style="font-size:7px"></i> récord</span></div>
                    <?= gim_linea($progresion, 'e1rm', $prFecha) ?>
                </section>
            <?php elseif (!$progresion): ?>
                <div class="gm-vacio"><i class="bi bi-graph-up"></i>Todavía no hay series con peso para este ejercicio.</div>
            <?php endif; ?>
            <?php if (count($volumen) >= 2): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Volumen por sesión</h3><span class="cuenta">series × reps × kg</span></div>
                    <?= gim_barras($volumen) ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($catalogo)): ?>
                <?php $pasosCat = json_decode($catalogo['pasos_es'] ?? '[]', true) ?: []; ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Cómo se hace</h3><a class="fv-link der" href="<?= site_url('gimnasio/catalogo/' . $catalogo['id']) ?>"><?= esc(ucfirst($catalogo['nombre'])) ?> <i class="bi bi-chevron-right"></i></a></div>
                    <div style="display:grid;grid-template-columns:120px minmax(0,1fr);gap:14px;align-items:start">
                        <div class="gm-ficha-img"><img src="<?= esc(gim_catalogo_media($catalogo['gif'])) ?>" alt="" loading="lazy"></div>
                        <ol class="gm-pasos" style="font-size:13px"><?php foreach (array_slice($pasosCat, 0, 5) as $p): ?><li><?= esc($p) ?></li><?php endforeach; ?></ol>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button type="button" class="fv-gbtn" data-gm-cat-cambiar><i class="bi bi-arrow-left-right"></i> Cambiar</button>
                        <form method="post" action="<?= site_url('gimnasio/catalogo/desvincular/' . $ejercicio['id']) ?>" data-confirm="¿Quitar el vínculo con «<?= esc(ucfirst($catalogo['nombre'])) ?>»? No borra nada." class="m-0">
                            <input type="hidden" name="volver" value="<?= esc($volverAqui) ?>">
                            <button class="fv-gbtn peligro"><i class="bi bi-x-circle"></i> Desvincular</button>
                        </form>
                    </div>
                </section>
            <?php elseif ($hayCatalogo): ?>
                <button type="button" class="gm-cta sec" data-gm-cat-cambiar>
                    <i class="bi bi-play-btn"></i>
                    <div><b>Vincular con el catálogo</b><small>Animación e instrucciones de cómo se hace</small></div>
                </button>
            <?php endif; ?>

            <?php if ($hayCatalogo): ?>
                <section class="fv-panel" id="gmCatPicker" hidden>
                    <div class="fv-ph"><h3>Elegir del catálogo</h3></div>
                    <?php $sugeridos = array_filter($candidatosCatalogo, fn ($c) => (int) $c['id'] !== (int) ($catalogo['id'] ?? 0)); ?>
                    <?php if ($sugeridos): ?>
                        <div class="gm-opciones">
                            <?php foreach ($sugeridos as $c): ?>
                                <button type="button" class="gm-op" data-cat="<?= $c['id'] ?>"><img src="<?= esc(gim_catalogo_media($c['imagen'])) ?>" alt="" loading="lazy"><span><?= esc(ucfirst($c['nombre'])) ?></span></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="gm-buscar"><i class="bi bi-search"></i><input type="search" class="fv-input" id="gmCatBuscar" placeholder="Buscar en el catálogo (español o inglés)…" autocomplete="off"></div>
                    <div class="gm-opciones" id="gmCatRes"></div>
                    <form method="post" id="gmCatForm" hidden>
                        <input type="hidden" name="ejercicio_id" value="<?= $ejercicio['id'] ?>">
                        <input type="hidden" name="volver" value="<?= esc($volverAqui) ?>">
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <section class="fv-panel">
            <div class="fv-ph"><h3>Historial</h3><span class="cuenta"><?= count($seriesDetalle) ?> registros</span></div>
            <?php if ($seriesDetalle): ?>
                <div class="gm-buscar"><i class="bi bi-search"></i><input type="number" step="any" inputmode="decimal" id="gmPeso" class="fv-input" placeholder="Filtrar por peso (kg)…"></div>
                <p class="gm-ayuda" id="gmPesoRes" hidden></p>
                <div class="gm-filas" id="gmHist">
                    <?php foreach ($porSesion as $fecha => $s): ?>
                        <div class="gm-fila" style="align-items:flex-start" data-pesos="<?= esc(implode(',', array_map(fn ($x) => (float) $x['peso'], $s['series']))) ?>">
                            <a class="gm-fecha" href="<?= site_url('gimnasio/entrenamientos/registro/' . $s['id']) ?>"><b><?= date('j', strtotime($fecha)) ?></b><small><?= gim_mes_corto($fecha) ?><?= date('Y', strtotime($fecha)) !== date('Y') ? ' ' . date('y', strtotime($fecha)) : '' ?></small></a>
                            <div class="pp">
                                <div class="gm-chips">
                                    <?php foreach ($s['series'] as $x): ?>
                                        <?php $esPr = $fecha === $prFecha && (float) $x['peso'] == $pr['peso'] && (int) $x['repeticiones'] === $pr['reps']; ?>
                                        <span class="gm-chip" data-peso="<?= (float) $x['peso'] ?>" data-reps="<?= (int) $x['repeticiones'] ?>" title="<?= esc(trim(($x['rpe'] !== null && $x['rpe'] !== '' ? 'RPE ' . $x['rpe'] . ' · ' : '') . ($x['nota'] ?? ''))) ?>">
                                            <?= (int) $x['series'] ?>×<?= (int) $x['repeticiones'] ?><?= (float) $x['peso'] > 0 ? ' · ' . gim_kg($x['peso']) . ' kg' : '' ?>
                                            <?php if ($esPr): ?><i class="bi bi-trophy-fill gm-pr"></i><?php endif; ?>
                                            <?php if (!empty($x['nota'])): ?><i class="bi bi-chat-left-text" style="font-size:10px"></i><?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="fv-vacio">No hay registros de este ejercicio.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
<script src="<?= $js ?>"></script>
<script>
(function () {
    // Filtro por peso concreto
    const input = document.getElementById('gmPeso');
    if (input) {
        const filas = Array.from(document.querySelectorAll('#gmHist .gm-fila'));
        const res = document.getElementById('gmPesoRes');
        input.addEventListener('input', () => {
            const v = parseFloat(input.value.replace(',', '.'));
            let n = 0, mejor = null;
            filas.forEach(f => {
                const chips = Array.from(f.querySelectorAll('[data-peso]'));
                let hay = false;
                chips.forEach(c => {
                    const ok = isNaN(v) || Math.abs(parseFloat(c.dataset.peso) - v) < 0.01;
                    c.style.opacity = ok ? '' : '.3';
                    if (ok && !isNaN(v)) { hay = true; n++; const r = +c.dataset.reps; if (!mejor || r > mejor.r) mejor = { r, f: f.querySelector('.gm-fecha').textContent.trim() }; }
                });
                f.hidden = !isNaN(v) && !hay;
            });
            res.hidden = isNaN(v);
            res.textContent = n ? n + ' registro' + (n === 1 ? '' : 's') + ' a ' + v + ' kg · mejor: ' + mejor.r + ' reps' : 'Nunca a ' + v + ' kg.';
        });
    }

    // Vincular con el catálogo
    const picker = document.getElementById('gmCatPicker');
    if (!picker) return;
    const form = document.getElementById('gmCatForm'), buscar = document.getElementById('gmCatBuscar'), res = document.getElementById('gmCatRes');
    document.querySelectorAll('[data-gm-cat-cambiar]').forEach(b => b.addEventListener('click', () => {
        picker.hidden = !picker.hidden;
        if (!picker.hidden) picker.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }));
    picker.addEventListener('click', e => {
        const op = e.target.closest('[data-cat]');
        if (!op || !confirm('¿Vincular con «' + op.textContent.trim() + '»?')) return;
        form.action = '<?= site_url('gimnasio/catalogo') ?>/' + op.dataset.cat + '/vincular';
        form.submit();
    });
    let t;
    buscar.addEventListener('input', () => {
        clearTimeout(t);
        const q = buscar.value.trim();
        if (q.length < 2) { res.innerHTML = ''; return; }
        t = setTimeout(async () => {
            const filas = await fetch('<?= site_url('gimnasio/catalogo/buscar') ?>?q=' + encodeURIComponent(q)).then(r => r.json());
            res.innerHTML = '';
            if (!filas.length) { res.innerHTML = '<span class="fv-vacio">Sin resultados.</span>'; return; }
            filas.forEach(f => {
                const b = document.createElement('button');
                b.type = 'button'; b.className = 'gm-op'; b.dataset.cat = f.id; b.title = f.meta;
                const img = document.createElement('img'); img.src = f.imagen; img.alt = ''; img.loading = 'lazy';
                const s = document.createElement('span'); s.textContent = f.nombre;
                b.append(img, s);
                res.append(b);
            });
        }, 250);
    });
})();
</script>
<?= $this->endSection() ?>
