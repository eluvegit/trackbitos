<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * @var array  $proyecto
 * @var string $vista
 * @var array  $vistas     [clave => [título, icono, campo, bandeja, fijas]]
 * @var array  $columnas   [['valor', 'nombre', 'tarjetas' => [['e', 'parte'], ...], 'fija'], ...]
 * @var array  $miniaturas [escena_id => ['ruta', 'video']]
 * @var int    $total
 * @var bool   $hayFormato alguna escena tiene formato (foto/vídeo/ambas)
 * @var array  $colores    [valor normalizado => color] (vacío en Producción)
 */
$pid       = (int) $proyecto['id'];
$editables = $vista !== 'produccion';   // columnas que se pueden renombrar, mover y crear
$ayuda     = [
    'narrativa'  => 'El orden de la historia: es el que usan la lista, el storyboard y el plató.',
    'ubicacion'  => 'Agrupa por sitio para rodar todo lo de un lugar seguido. Su orden no toca la narrativa.',
    'montaje'    => 'Agrupa por soporte para no montar y desmontar el equipo. Su orden no toca la narrativa.',
    'produccion' => 'Estado de cada toma (el mismo que marcas en el plató). Las escenas de foto y vídeo salen dos veces, una por parte.',
][$vista];

ob_start(); ?>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas") ?>"><i class="bi bi-list-ul"></i>Lista</a>
<a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/plato") ?>"><i class="bi bi-phone"></i>Plató</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rd pizarra">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Pizarra' => null],
        'titulo'   => 'Pizarra',
        'sub'      => '<span>' . $total . ' ' . ($total === 1 ? 'escena' : 'escenas') . '</span><span class="fv-dot"></span><span>' . esc($ayuda) . '</span>',
        'acciones' => $acciones,
        'clasica'  => false,
    ]) ?>

    <nav class="rd-nav">
        <?php foreach ($vistas as $k => [$txt, $ico]): ?>
            <a href="<?= site_url("rodajes/$pid/pizarra" . ($k === 'narrativa' ? '' : "/$k")) ?>" class="<?= $k === $vista ? 'on' : '' ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="rd-herr">
        <input type="search" class="fv-input" id="rpBuscar" placeholder="Buscar tarjetas…" autocomplete="off">
        <?php if ($hayFormato): ?>
            <div class="fv-seg rp-filtro-medio" id="rpMedio">
                <button type="button" data-medio="" aria-pressed="true">Todo</button>
                <button type="button" data-medio="foto" aria-pressed="false"><i class="bi bi-camera"></i>Foto</button>
                <button type="button" data-medio="video" aria-pressed="false"><i class="bi bi-camera-video"></i>Vídeo</button>
            </div>
        <?php endif; ?>
        <span class="rp-estado" id="rpEstado"></span>
    </div>

    <div class="rp-tablero" id="rpTablero">
        <?php foreach ($columnas as $c): ?>
            <section class="rp-col<?= $c['fija'] ? ' fija' : ' movible' ?><?= $c['valor'] === '' && $editables ? ' bandeja' : '' ?>" data-valor="<?= esc($c['valor'], 'attr') ?>"<?= $colores && $c['valor'] !== '' ? ' style="--c:' . rod_color($colores, $c['valor']) . '"' : '' ?>>
                <header class="rp-cab">
                    <?php if (!$c['fija'] && $editables): ?><i class="bi bi-grip-vertical rp-grip" title="Arrastra para mover la columna"></i><?php endif; ?>
                    <?php if (!$c['fija'] && $editables): ?>
                        <button type="button" class="rp-nombre editable" title="Renombrar"><?= esc($c['nombre']) ?></button>
                    <?php else: ?>
                        <span class="rp-nombre"><?= esc($c['nombre']) ?></span>
                    <?php endif; ?>
                    <small class="rp-cuenta"><?= count($c['tarjetas']) ?></small>
                    <button type="button" class="fv-ibtn rp-mas" title="Escena rápida en esta columna"><i class="bi bi-plus-lg"></i></button>
                </header>
                <form class="rp-nueva" hidden>
                    <input type="text" class="fv-input" placeholder="Descripción y Enter…" maxlength="500">
                </form>
                <div class="rp-lista">
                    <?php foreach ($c['tarjetas'] as $t): ?>
                        <?= view('rodajes/pizarra/_tarjeta', ['e' => $t['e'], 'pid' => $pid, 'vista' => $vista, 'parte' => $t['parte'], 'mini' => $miniaturas[(int) $t['e']['id']] ?? null]) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($editables): ?>
            <div class="rp-col-nueva">
                <button type="button" class="fv-btn" id="rpNuevaCol"><i class="bi bi-plus-lg"></i>Columna</button>
                <form id="rpNuevaColForm" hidden><input type="text" class="fv-input" placeholder="Nombre y Enter…" maxlength="255"></form>
            </div>
        <?php endif; ?>
    </div>

    <div class="rp-pop" id="rpPop" hidden></div>
</div>

<template id="rpColTpl">
    <section class="rp-col movible" data-valor="">
        <header class="rp-cab">
            <i class="bi bi-grip-vertical rp-grip" title="Arrastra para mover la columna"></i>
            <button type="button" class="rp-nombre editable" title="Renombrar"></button>
            <small class="rp-cuenta">0</small>
            <button type="button" class="fv-ibtn rp-mas" title="Escena rápida en esta columna"><i class="bi bi-plus-lg"></i></button>
        </header>
        <form class="rp-nueva" hidden><input type="text" class="fv-input" placeholder="Descripción y Enter…" maxlength="500"></form>
        <div class="rp-lista"></div>
    </section>
</template>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const VISTA = <?= json_encode($vista) ?>;
    const URL_GUARDAR = <?= json_encode(site_url("rodajes/$pid/pizarra/guardar")) ?>;
    const URL_ESCENA = <?= json_encode(site_url("rodajes/$pid/pizarra/escena")) ?>;
    const HEADERS = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': <?= json_encode(csrf_hash()) ?> };
    const tablero = document.getElementById('rpTablero');
    const estado = document.getElementById('rpEstado');
    const pop = document.getElementById('rpPop');
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const cols = () => [...tablero.querySelectorAll('.rp-col')];

    // ---------- guardado ----------
    let temporizador = null;
    function aviso(txt, clase = '') { estado.textContent = txt; estado.className = 'rp-estado ' + clase; }
    function contar() { cols().forEach(c => c.querySelector('.rp-cuenta').textContent = c.querySelectorAll('.rp-tarjeta').length); }
    function guardar() {
        contar();
        clearTimeout(temporizador);
        aviso('Guardando…');
        temporizador = setTimeout(async () => {
            const columnas = cols().map(c => ({ valor: c.dataset.valor, ids: [...c.querySelectorAll('.rp-tarjeta')].map(t => t.dataset.id) }));
            try {
                const r = await fetch(URL_GUARDAR, { method: 'POST', headers: HEADERS, body: JSON.stringify({ vista: VISTA, columnas }) });
                const j = await r.json();
                if (!j.ok) throw 0;
                aviso('Guardado', 'ok');
                if (VISTA === 'narrativa') renumerar();
            } catch (e) { aviso('No se pudo guardar — recarga la página', 'err'); }
        }, 350);
    }
    // En la narrativa el número de la tarjeta es el orden: se repinta igual que en el servidor (bandeja al final).
    function renumerar() {
        let n = 0;
        const orden = cols().filter(c => c.dataset.valor !== '').concat(cols().filter(c => c.dataset.valor === ''));
        orden.forEach(c => c.querySelectorAll('.rp-tarjeta .rp-num').forEach(s => s.textContent = '#' + (++n)));
    }

    // ---------- arrastrar ----------
    const opcTarjetas = {
        group: 'rp', animation: 150, delay: 180, delayOnTouchOnly: true,
        filter: '.rp-mover', preventOnFilter: false,
        ghostClass: 'rp-fantasma', onEnd: guardar,
    };
    const activarLista = l => Sortable.create(l, opcTarjetas);
    tablero.querySelectorAll('.rp-lista').forEach(activarLista);
    if (VISTA !== 'produccion') {
        Sortable.create(tablero, {
            handle: '.rp-grip', draggable: '.rp-col.movible', animation: 150,
            onMove: ev => !ev.related.classList.contains('fija'),
            onEnd: guardar,
        });
    }

    // ---------- columnas: crear y renombrar ----------
    function buscarCol(nombre, salvo = null) { return cols().find(c => c !== salvo && norm(c.dataset.valor) === norm(nombre)); }
    function nuevaCol(nombre) {
        const c = document.getElementById('rpColTpl').content.firstElementChild.cloneNode(true);
        c.dataset.valor = nombre;
        c.querySelector('.rp-nombre').textContent = nombre;
        tablero.insertBefore(c, tablero.querySelector('.rp-col-nueva'));
        activarLista(c.querySelector('.rp-lista'));
        return c;
    }
    const btnNuevaCol = document.getElementById('rpNuevaCol');
    const formNuevaCol = document.getElementById('rpNuevaColForm');
    btnNuevaCol?.addEventListener('click', () => { btnNuevaCol.hidden = true; formNuevaCol.hidden = false; formNuevaCol.querySelector('input').focus(); });
    formNuevaCol?.addEventListener('submit', ev => {
        ev.preventDefault();
        const inp = formNuevaCol.querySelector('input'), nombre = inp.value.trim();
        if (nombre) {
            const c = buscarCol(nombre) || nuevaCol(nombre);
            c.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
            destacar(c);
            aviso('Columna creada: se guarda al soltar la primera tarjeta en ella');
        }
        inp.value = ''; formNuevaCol.hidden = true; btnNuevaCol.hidden = false;
    });
    formNuevaCol?.querySelector('input').addEventListener('keydown', ev => { if (ev.key === 'Escape') { formNuevaCol.hidden = true; btnNuevaCol.hidden = false; } });

    function renombrar(btn) {
        const col = btn.closest('.rp-col');
        const inp = document.createElement('input');
        inp.className = 'fv-input rp-renombrar'; inp.value = col.dataset.valor; inp.maxLength = 255;
        btn.replaceWith(inp); inp.focus(); inp.select();
        let hecho = false;
        const fin = aplicar => {
            if (hecho) return; hecho = true;
            const nombre = inp.value.trim();
            inp.replaceWith(btn);
            if (!aplicar || !nombre || nombre === col.dataset.valor) return;
            const otra = buscarCol(nombre, col);
            if (otra) {   // mismo nombre que otra columna: se juntan
                col.querySelectorAll('.rp-tarjeta').forEach(t => otra.querySelector('.rp-lista').appendChild(t));
                col.remove(); destacar(otra);
            } else {
                col.dataset.valor = nombre; btn.textContent = nombre;
            }
            guardar();
        };
        inp.addEventListener('keydown', ev => { if (ev.key === 'Enter') fin(true); if (ev.key === 'Escape') fin(false); });
        inp.addEventListener('blur', () => fin(true));
    }

    // ---------- escena rápida ----------
    async function crearEscena(col, form) {
        const inp = form.querySelector('input'), descripcion = inp.value.trim();
        if (!descripcion) return;
        inp.disabled = true;
        try {
            const r = await fetch(URL_ESCENA, { method: 'POST', headers: HEADERS, body: JSON.stringify({ vista: VISTA, valor: col.dataset.valor, descripcion }) });
            const j = await r.json();
            if (!j.ok) throw 0;
            col.querySelector('.rp-lista').insertAdjacentHTML('beforeend', j.html);
            inp.value = '';
            guardar();
        } catch (e) { aviso('No se pudo crear la escena', 'err'); }
        inp.disabled = false; inp.focus();
    }

    // ---------- mover a… ----------
    function abrirMover(btn) {
        const tarjeta = btn.closest('.rp-tarjeta'), actual = btn.closest('.rp-col');
        pop.innerHTML = '<small>Mover a…</small>' + cols().filter(c => c !== actual)
            .map(c => `<button type="button" data-i="${cols().indexOf(c)}">${c.querySelector('.rp-nombre').textContent.replace(/[<>&"]/g, '')}</button>`).join('');
        pop.hidden = false;
        const r = btn.getBoundingClientRect();
        const alto = pop.offsetHeight, vh = window.innerHeight;
        pop.style.top = (r.bottom + 6 + alto > vh ? Math.max(8, r.top - alto - 6) : r.bottom + 6) + 'px';
        pop.style.left = Math.max(8, Math.min(r.right - pop.offsetWidth, document.documentElement.clientWidth - pop.offsetWidth - 8)) + 'px';
        pop.onclick = ev => {
            const b = ev.target.closest('button[data-i]');
            if (!b) return;
            const destino = cols()[+b.dataset.i];
            destino.querySelector('.rp-lista').appendChild(tarjeta);
            pop.hidden = true;
            destacar(tarjeta);
            guardar();
        };
    }
    function destacar(el) { el.classList.remove('rp-destaca'); void el.offsetWidth; el.classList.add('rp-destaca'); }

    // ---------- clics ----------
    document.addEventListener('click', ev => {
        const t = ev.target;
        if (t.closest('.rp-mover')) { ev.preventDefault(); abrirMover(t.closest('.rp-mover')); return; }
        if (!t.closest('#rpPop')) pop.hidden = true;
        const nombre = t.closest('.rp-nombre.editable');
        if (nombre) { renombrar(nombre); return; }
        const mas = t.closest('.rp-mas');
        if (mas) {
            const form = mas.closest('.rp-col').querySelector('.rp-nueva');
            form.hidden = !form.hidden;
            if (!form.hidden) form.querySelector('input').focus();
        }
    });
    tablero.addEventListener('submit', ev => {
        const form = ev.target.closest('.rp-nueva');
        if (!form) return;
        ev.preventDefault();
        crearEscena(form.closest('.rp-col'), form);
    });
    tablero.addEventListener('keydown', ev => {
        if (ev.key === 'Escape' && ev.target.closest('.rp-nueva')) ev.target.closest('.rp-nueva').hidden = true;
    });

    window.addEventListener('scroll', () => { pop.hidden = true; }, { passive: true });
    tablero.addEventListener('scroll', () => { pop.hidden = true; }, { passive: true });

    // ---------- buscar ----------
    // ---------- buscar + filtro foto/vídeo ----------
    // «Foto y vídeo» casa con los dos filtros, salvo en Producción, donde cada parte es su propia tarjeta.
    const buscar = document.getElementById('rpBuscar');
    let medio = '';
    function filtrar() {
        const q = norm(buscar.value);
        tablero.querySelectorAll('.rp-tarjeta').forEach(t => {
            const m = t.dataset.medio;
            t.hidden = (q !== '' && !norm(t.dataset.buscar).includes(q)) || (medio !== '' && m !== medio && m !== 'ambas');
        });
    }
    buscar.addEventListener('input', filtrar);
    document.querySelectorAll('#rpMedio button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#rpMedio button').forEach(x => x.setAttribute('aria-pressed', x === b));
        medio = b.dataset.medio;
        filtrar();
    }));
})();
</script>
<?= $this->endSection() ?>
