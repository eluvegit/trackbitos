<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Pizarra «Rodaje»: el mismo plan del paso Plan, dibujado como tablero.
 * Columnas = franja · zona (en el orden del plan); dentro, un bloque por setup.
 * Arrastrar una tarjeta a otro setup / columna le pone ese setup, franja y zona;
 * su posición en el bloque es su orden de rodaje (el mismo que las flechas del Plan).
 *
 * @var array $proyecto
 * @var array $vistas
 * @var array $plan       RodajesFlujo::construirPlan
 * @var array $setups     del proyecto, en su orden
 * @var array $miniaturas [escena_id => ['ruta', 'video']]
 * @var int   $total
 */
$pid = (int) $proyecto['id'];
$hayDN = (bool) array_filter($plan, static fn ($f) => $f['franja'] !== '' && $f['franja'] !== null);

ob_start(); ?>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/plan") ?>"><i class="bi bi-list-ol"></i>Plan</a>
<a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/plato?orden=plan") ?>"><i class="bi bi-phone"></i>Plató</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rd pizarra rp-rodaje">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Pizarra' => null],
        'titulo'   => 'Pizarra',
        'sub'      => '<span>' . $total . ' ' . ($total === 1 ? 'escena' : 'escenas') . '</span><span class="fv-dot"></span><span>El plan de rodaje en tablero: franja y zona en columnas, un bloque por setup. Arrastra para cambiar de setup o el orden en que se rueda.</span>',
        'acciones' => $acciones,
        'clasica'  => false,
    ]) ?>

    <nav class="rd-nav">
        <?php foreach ($vistas as $k => [$txt, $ico]): ?>
            <a href="<?= site_url("rodajes/$pid/pizarra" . ($k === 'narrativa' ? '' : "/$k")) ?>" class="<?= $k === 'rodaje' ? 'on' : '' ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="rd-herr">
        <input type="search" class="fv-input" id="rpBuscar" placeholder="Buscar tarjetas…" autocomplete="off">
        <?php if ($hayDN): ?>
            <div class="fv-seg rp-dn" role="group" aria-label="Día y noche">
                <button type="button" data-dn="dia" aria-pressed="true"><i class="bi bi-sun"></i>Día</button>
                <button type="button" data-dn="noche" aria-pressed="true"><i class="bi bi-moon-stars"></i>Noche</button>
            </div>
        <?php endif; ?>
        <span class="rp-estado" id="rpEstado"></span>
    </div>

    <?php if (!$plan): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-list-ol"></i><div>No hay tomas todavía.</div></div>
    <?php endif; ?>

    <div class="rp-tablero" id="rpTablero">
        <?php foreach ($plan as $f): ?>
            <?php foreach ($f['zonas'] as $z): ?>
                <?php
                $zid = (int) ($z['zona']['id'] ?? 0);
                $usados = array_map(static fn ($b) => (int) ($b['setup']['id'] ?? 0), $z['setups']);
                $n = array_sum(array_map(static fn ($b) => count($b['escenas']), $z['setups']));
                ?>
                <section class="rp-col f-<?= $f['franja'] ?: 'sin' ?>" data-dn="<?= rod_dia_noche($f['franja'] ?: null) ?>">
                    <header class="rp-cab rp-cab-rod">
                        <span class="rp-nombre"><small><?= esc($f['nombre']) ?></small><?= $z['zona'] ? $z['n'] . '. ' . esc($z['zona']['nombre']) : 'Sin zona' ?></span>
                        <small class="rp-cuenta"><?= $n ?></small>
                    </header>
                    <?php foreach ($z['setups'] as $b): ?>
                        <div class="rp-setup">
                            <div class="rp-setup-h"><i class="bi bi-camera"></i><?= $b['setup'] ? esc($b['setup']['nombre']) : 'Sin setup' ?></div>
                            <div class="rp-lista" data-franja="<?= esc((string) $f['franja'], 'attr') ?>" data-zona="<?= $zid ?>" data-setup="<?= (int) ($b['setup']['id'] ?? 0) ?>">
                                <?php foreach ($b['escenas'] as $e): ?>
                                    <?= view('rodajes/pizarra/_tarjeta', ['e' => $e, 'pid' => $pid, 'vista' => 'rodaje', 'parte' => null, 'mini' => $miniaturas[(int) $e['id']] ?? null], ['saveData' => false]) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php // Setups que aún no se usan aquí: huecos donde soltar para cambiar la toma a ese montaje. ?>
                    <?php foreach (array_merge($setups, [null]) as $st): if (in_array((int) ($st['id'] ?? 0), $usados, true)) continue; ?>
                        <div class="rp-setup vacio">
                            <div class="rp-setup-h"><i class="bi bi-camera"></i><?= $st ? esc($st['nombre']) : 'Sin setup' ?></div>
                            <div class="rp-lista" data-franja="<?= esc((string) $f['franja'], 'attr') ?>" data-zona="<?= $zid ?>" data-setup="<?= (int) ($st['id'] ?? 0) ?>"></div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const URL_GUARDAR = <?= json_encode(site_url("rodajes/$pid/pizarra/guardar")) ?>;
    const HEADERS = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': <?= json_encode(csrf_hash()) ?> };
    const tablero = document.getElementById('rpTablero');
    const estado = document.getElementById('rpEstado');
    const aviso = (t, c = '') => { estado.textContent = t; estado.className = 'rp-estado ' + c; };
    const contar = () => tablero.querySelectorAll('.rp-col').forEach(c => {
        c.querySelector('.rp-cuenta').textContent = c.querySelectorAll('.rp-tarjeta').length;
        c.querySelectorAll('.rp-setup').forEach(s => s.classList.toggle('vacio', !s.querySelector('.rp-tarjeta')));
    });
    let t = null;
    const guardar = () => {
        contar();
        clearTimeout(t);
        aviso('Guardando…');
        t = setTimeout(async () => {
            // Solo las listas con tarjetas: cada una fija franja, zona, setup y orden de sus tomas.
            const listas = [...tablero.querySelectorAll('.rp-lista')].filter(l => l.querySelector('.rp-tarjeta')).map(l => ({
                franja: l.dataset.franja, zona: l.dataset.zona, setup: l.dataset.setup,
                ids: [...l.querySelectorAll('.rp-tarjeta')].map(x => x.dataset.id),
            }));
            try {
                const r = await fetch(URL_GUARDAR, { method: 'POST', headers: HEADERS, body: JSON.stringify({ vista: 'rodaje', listas }) });
                const j = await r.json();
                if (!j.ok) throw 0;
                aviso('Guardado', 'ok');
            } catch (e) { aviso('No se pudo guardar — recarga la página', 'err'); }
        }, 350);
    };
    tablero.querySelectorAll('.rp-lista').forEach(l => Sortable.create(l, {
        group: 'rp', animation: 150, delay: 180, delayOnTouchOnly: true,
        filter: '.rp-mover', ghostClass: 'rp-fantasma', onEnd: guardar,
    }));
    // Día / noche: columnas visibles (mismo recuerdo que el plató). Sin franja = siempre.
    let dn = new Set(['dia', 'noche']);
    try { const g = JSON.parse(localStorage.getItem('rodajes_dn') || 'null'); if (Array.isArray(g) && g.length) dn = new Set(g); } catch {}
    const dnBtns = [...document.querySelectorAll('.rp-dn [data-dn]')];
    const aplicarDN = () => {
        dnBtns.forEach(b => b.setAttribute('aria-pressed', dn.has(b.dataset.dn)));
        tablero.querySelectorAll('.rp-col').forEach(c => c.hidden = !!c.dataset.dn && !dn.has(c.dataset.dn));
    };
    dnBtns.forEach(b => b.addEventListener('click', () => {
        const k = b.dataset.dn;
        // Con los dos: el que tocas queda solo. Con uno: tocar el otro vuelve a los dos.
        if (dn.size === 2) dn = new Set([k]); else if (!dn.has(k)) dn = new Set(['dia', 'noche']); else return;
        try { localStorage.setItem('rodajes_dn', JSON.stringify([...dn])); } catch {}
        aplicarDN();
    }));
    aplicarDN();
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    document.getElementById('rpBuscar').addEventListener('input', ev => {
        const q = norm(ev.target.value);
        tablero.querySelectorAll('.rp-tarjeta').forEach(x => x.hidden = q !== '' && !norm(x.dataset.buscar || '').includes(q));
    });
})();
</script>
<?= $this->endSection() ?>
