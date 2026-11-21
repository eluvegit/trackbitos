<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lista de tomas: lo mismo que enseña el plató (setups con lente, props,
 * variantes con su estado, momento del día) en uno de los dos órdenes.
 *
 * @var array      $proyecto
 * @var array|null $sesion
 * @var array      $escenas    con 'variantes'
 * @var array      $multimedia [escena_id => ['ruta','es_video','ext','guia']] portada
 * @var array      $varFotos   [escena_id => [variante_id => ruta]]
 * @var array      $ideas      [escena_id => ['texto','enlace']] idea sin foto
 * @var array      $setups     [setup_id => setup]
 * @var array      $puntos     [punto_id => nombre]
 */
$varFotos ??= [];
$ideas    ??= [];
$setups   ??= [];
$puntos   ??= [];
$pid = (int) $proyecto['id'];
$estadosUi = rod_toma_estados();
$ordenLista ??= 'narrativo';
$qOrden = $ordenLista === 'rodaje' ? '?orden=rodaje' : '';

// Grupos por temática (= bloque), en el orden del paso Temáticas; lo suelto, al final.
$grupos = [];
$cuenta = array_fill_keys(array_keys($estadosUi), 0);
foreach ($escenas as $e) {
    $nombre = trim((string) $e['escena_bloque']);
    $clave  = mb_strtolower($nombre);
    $grupos[$clave] ??= ['nombre' => $nombre !== '' ? $nombre : ($sesion ? 'Sin temática' : 'Sin bloque'), 'dn' => '', 'escenas' => []];
    $grupos[$clave]['escenas'][] = $e;
    $cuenta[rod_estado_escena($e)]++;
}
$ordenBloques ??= [];
if ($ordenBloques) {
    $pos = array_flip(array_keys($grupos));
    uksort($grupos, static fn ($a, $b) => [$a === '', $ordenBloques[$a] ?? PHP_INT_MAX, $pos[$a]] <=> [$b === '', $ordenBloques[$b] ?? PHP_INT_MAX, $pos[$b]]);
}
// Orden de rodaje: los grupos del Plan (franja · zona · setup), en su orden.
if ($ordenLista === 'rodaje') $grupos = $gruposRodaje ?? [];
$total = count($escenas);
$plato = rod_cuenta_tomas($escenas);   // mismo recuento que el plató (tomas + variantes, por parte)

$hayDN      = (bool) array_filter($escenas, static fn ($e) => !empty($e['franja']));
$hayFormato = (bool) array_filter($escenas, static fn ($e) => !empty($e['formato']));
$hayOpc     = (bool) array_filter($escenas, static fn ($e) => ($e['prioridad'] ?? 'imp') === 'opc' || array_filter($e['variantes'] ?? [], static fn ($v) => $v['prioridad'] === 'opc'));

$sub = ['<span>' . $total . ' ' . ($total === 1 ? 'toma' : 'tomas') . '</span>'];
if ($total) $sub[] = '<span title="Tomas y variantes, como en el plató">' . $plato['hecha'] . '/' . $plato['utiles'] . ' hechas</span>';
if ($sesion) $sub[] = '<a class="rd-ses" href="' . site_url('sesiones/' . $sesion['id']) . '"><i class="bi bi-camera"></i>' . esc($sesion['titulo']) . '</a>';

ob_start(); ?>
<a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/plato") ?>"><i class="bi bi-phone"></i>Plató</a>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/pizarra") ?>"><i class="bi bi-kanban"></i>Pizarra</a>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Toma</a>
<details class="rd-menu">
    <summary class="fv-btn" title="Más"><i class="bi bi-three-dots"></i></summary>
    <div class="rd-menu-pop">
        <a href="<?= site_url("rodajes/$pid/escenas/storyboard$qOrden") ?>"><i class="bi bi-layout-text-window-reverse"></i>Storyboard</a>
        <a href="<?= site_url("rodajes/$pid/dialogos") ?>"><i class="bi bi-chat-quote"></i>Diálogos</a>
        <a href="<?= site_url("rodajes/edit/$pid") ?>"><i class="bi bi-pencil"></i>Editar proyecto</a>
        <?php if ($sesion): ?>
            <a href="<?= site_url("rodajes/$pid/tematicas") ?>"><i class="bi bi-collection"></i>Temáticas</a>
        <?php endif; ?>
    </div>
</details>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rd">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => null],
        'titulo'   => esc($proyecto['titulo']),
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => $acciones,
    ]) ?>
    <?= view('rodajes/flujo/_pasos', ['proyecto' => $proyecto, 'pasos' => $pasos, 'paso' => '']) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-film"></i>
            <div>Este rodaje no tiene tomas todavía.</div>
            <div class="rd-herr" style="justify-content:center">
                <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
                <?php if ($sesion): ?>
                    <a class="fv-btn" href="<?= site_url("rodajes/$pid/ideas") ?>"><i class="bi bi-inbox"></i>Empezar por la ingesta</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="rd-herr">
            <input type="search" class="fv-input" id="rdBuscar" placeholder="Buscar en las tomas…" autocomplete="off">
            <div class="rd-vista rd-orden" role="group" aria-label="Orden">
                <a href="<?= site_url("rodajes/$pid/escenas") ?>" class="<?= $ordenLista === 'narrativo' ? 'on' : '' ?>" title="Cómo se cuenta la historia (temáticas en orden)"><i class="bi bi-book"></i> Narrativo</a>
                <a href="<?= site_url("rodajes/$pid/escenas?orden=rodaje") ?>" class="<?= $ordenLista === 'rodaje' ? 'on' : '' ?>" title="Cómo se rueda: el Plan (momento, zona, setup, props)"><i class="bi bi-list-ol"></i> Rodaje</a>
            </div>
            <div class="rd-vista" id="rdVista" role="group" aria-label="Vista">
                <button type="button" data-vista="lista" title="Lista"><i class="bi bi-list-ul"></i></button>
                <button type="button" data-vista="galeria" title="Galería"><i class="bi bi-grid-3x2-gap"></i></button>
            </div>
        </div>
        <?php if ($hayDN || $hayFormato || $hayOpc): ?>
            <div class="rd-herr rd-filtros" id="rdFiltros">
                <?php if ($hayDN): ?>
                    <div class="rd-vista" role="group" aria-label="Día o noche" data-f="dn">
                        <button type="button" class="on" data-v="">Día y noche</button>
                        <button type="button" data-v="dia"><i class="bi bi-sun"></i> Día</button>
                        <button type="button" data-v="noche"><i class="bi bi-moon-stars"></i> Noche</button>
                    </div>
                <?php endif; ?>
                <?php if ($hayFormato): ?>
                    <div class="rd-vista" role="group" aria-label="Foto o vídeo" data-f="medio">
                        <button type="button" class="on" data-v="">Foto y vídeo</button>
                        <button type="button" data-v="foto"><i class="bi bi-camera"></i> Foto</button>
                        <button type="button" data-v="video"><i class="bi bi-camera-video"></i> Vídeo</button>
                    </div>
                <?php endif; ?>
                <?php if ($hayOpc): ?>
                    <div class="rd-vista" role="group" aria-label="Prioridad" data-f="imp">
                        <button type="button" data-v="1" data-conmuta><i class="bi bi-star"></i> Solo imprescindibles</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <nav class="rd-nav" id="rdEstados">
            <button type="button" class="on" data-estado="">Todas <small><?= $total ?></small></button>
            <?php foreach ($estadosUi as $k => [$txt]): ?>
                <?php if (!$cuenta[$k]) continue; ?>
                <button type="button" data-estado="<?= $k ?>"><?= $txt === 'Hecha' ? 'Hechas' : ($txt === 'Pendiente' ? 'Pendientes' : ($txt === 'Descartada' ? 'Descartadas' : $txt)) ?> <small><?= $cuenta[$k] ?></small></button>
            <?php endforeach; ?>
        </nav>

        <div class="rd-escenas" id="rdEscenas">
            <?php $sepVisto = []; ?>
            <?php foreach ($grupos as $g): ?>
                <?php if (($g['dn'] ?? '') !== '' && !isset($sepVisto[$g['dn']])): $sepVisto[$g['dn']] = true; ?>
                    <div class="rd-sep-dn <?= $g['dn'] ?>" data-sep="<?= $g['dn'] ?>"><i class="bi <?= $g['dn'] === 'noche' ? 'bi-moon-stars-fill' : 'bi-sun-fill' ?>"></i><?= $g['dn'] === 'noche' ? 'Noche' : 'Día' ?></div>
                <?php endif; ?>
                <section class="rd-grupo" data-dn-g="<?= esc($g['dn'] ?? '', 'attr') ?>">
                    <?php if (count($grupos) > 1 || !isset($grupos[''])): ?>
                        <div class="rd-bloque"><?= esc($g['nombre']) ?> <small><?= count($g['escenas']) ?></small></div>
                    <?php endif; ?>
                    <div class="rd-filas">
                        <?php foreach ($g['escenas'] as $e): ?>
                            <?php
                            $eid    = (int) $e['id'];
                            $estado = rod_estado_escena($e);
                            $partes = rod_estados_partes($e);
                            $cajas  = rod_cajas_toma($e, $setups);
                            $props  = rod_props($e);
                            $m      = $multimedia[$eid] ?? null;
                            $opcE   = ($e['prioridad'] ?? 'imp') === 'opc';
                            $buscar = mb_strtolower(implode(' ', array_merge([
                                $e['escena_bloque'], $e['escena_descripcion'], $e['escena_accion'], $e['escena_ubicacion'],
                                $e['plano_objetos'], $e['plano_notas'], $e['toma_nota'],
                            ], $props, array_column($e['variantes'], 'texto'), array_map(static fn ($c) => $c['st']['nombre'] ?? '', $cajas))));
                            ?>
                            <div class="rd-fila <?= $estado ?>" data-estado="<?= $estado ?>" data-buscar="<?= esc($buscar, 'attr') ?>"
                                 data-dn="<?= rod_dia_noche($e['franja'] ?? null) ?>" data-medio="<?= esc((string) ($e['formato'] ?? ''), 'attr') ?>" data-opc="<?= $opcE ? '1' : '' ?>">
                                <span class="rd-num"><?= (int) $e['orden'] ?></span>
                                <div class="rd-mini<?= $hayOpc && !$opcE ? ' imp' : '' ?>"<?= $hayOpc && !$opcE ? ' title="Imprescindible"' : '' ?>>
                                    <?php if ($m && $m['es_video']): ?>
                                        <video src="<?= base_url($m['ruta']) ?>#t=0.1" muted playsinline preload="metadata"></video><i class="bi bi-play-fill"></i>
                                    <?php elseif ($m): ?>
                                        <img src="<?= base_url($m['ruta']) ?>" alt="" loading="lazy">
                                        <?php if (!empty($m['guia'])): ?><span class="rd-mini-tag" title="Imagen guía de la zona"><i class="bi bi-geo-alt"></i></span><?php endif; ?>
                                    <?php elseif (isset($ideas[$eid]) && $ideas[$eid]['texto'] !== ''): ?>
                                        <span class="rd-mini-idea"><i class="bi <?= $ideas[$eid]['enlace'] ? 'bi-link-45deg' : 'bi-chat-left-text' ?>"></i><?= esc($ideas[$eid]['texto']) ?></span>
                                    <?php else: ?>
                                        <i class="bi bi-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="rd-fila-txt">
                                    <a class="t" href="<?= site_url("rodajes/$pid/escenas/show/$eid$qOrden") ?>"><?= esc(rod_titulo_escena($e)) ?></a>
                                    <div class="m">
                                        <?= rod_formato_chip($e) ?>
                                        <?php foreach ($partes as $parte => $est): ?>
                                            <?php if ($est === 'pendiente') continue; ?>
                                            <span class="fv-estado <?= $estadosUi[$est][2] ?>"><?= count($partes) > 1 ? rod_formatos()[$parte][0] . ': ' : '' ?><?= $estadosUi[$est][0] ?></span>
                                        <?php endforeach; ?>
                                        <?= rod_momento($e) ?>
                                        <?php if (trim((string) $e['escena_ubicacion']) !== ''): ?><span><i class="bi bi-geo-alt"></i><?= esc($e['escena_ubicacion']) ?><?php if (!empty($e['punto_id']) && isset($puntos[(int) $e['punto_id']])): ?> · <?= esc($puntos[(int) $e['punto_id']]) ?><?php endif; ?></span><?php endif; ?>
                                        <?php if ($opcE): ?><span class="e-warn"><i class="bi bi-hourglass-split"></i>si da tiempo</span><?php endif; ?>
                                        <?php if (trim((string) $e['toma_nota']) !== ''): ?><span class="e-warn"><i class="bi bi-pencil"></i><?= esc($e['toma_nota']) ?></span><?php endif; ?>
                                    </div>
                                    <div class="rd-stps"><?= rod_chips_setups($cajas) ?></div>
                                    <div class="rd-props<?= $props ? '' : ' falta' ?>"><i class="bi bi-box-seam"></i><span><?= $props ? esc(implode(', ', $props)) : 'sin props' ?></span></div>
                                    <?php if ($e['variantes']): ?>
                                        <div class="rd-vmini">
                                            <?php foreach (array_slice($e['variantes'], 0, 8) as $va): ?>
                                                <?php
                                                $vEst  = rod_estado_escena(['formato' => $e['formato'] ?? null] + $va);
                                                $extra = rod_props_extra($e, $va);
                                                $ruta  = $varFotos[$eid][(int) $va['id']] ?? null;
                                                ?>
                                                <span class="rd-vm <?= $vEst ?>" title="<?= esc($va['texto'] . ($extra ? ' (+ ' . implode(', ', $extra) . ')' : '') . ($vEst !== 'pendiente' ? ' · ' . $estadosUi[$vEst][0] : ''), 'attr') ?>">
                                                    <?php if ($ruta): ?><img src="<?= base_url($ruta) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-diagram-2"></i><?php endif; ?>
                                                    <?php if ($vEst !== 'pendiente'): ?><b><i class="bi <?= $estadosUi[$vEst][1] ?>"></i></b><?php endif; ?>
                                                    <?php if ($extra): ?><em>+<?= count($extra) ?></em><?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($e['variantes']) > 8): ?><small>+<?= count($e['variantes']) - 8 ?></small><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (trim((string) $e['sonido_dialogo_escrito']) !== ''): ?>
                                        <div class="dlg"><?= esc(preg_replace('/\s+/', ' ', $e['sonido_dialogo_escrito'])) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="rd-fila-lado">
                                    <a class="fv-ibtn" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a class="fv-ibtn opc" href="<?= site_url("rodajes/$pid/escenas/delete/$eid") ?>" title="Borrar" onclick="return confirm('¿Borrar esta toma y sus variantes?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <div class="fv-vacio rd-vacio" id="rdNada" hidden>Ninguna toma coincide.</div>
        </div>
    <?php endif; ?>
</div>

<script>
(() => {
    const buscar = document.getElementById('rdBuscar');
    if (!buscar) return;
    const nav = document.getElementById('rdEstados');
    const f = { estado: '', dn: '', medio: '', imp: '' };
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    function filtrar() {
        const q = norm(buscar.value.trim());
        let alguna = false;
        const ladoVisible = { dia: false, noche: false };
        document.querySelectorAll('.rd-grupo').forEach(g => {
            let vis = 0;
            g.querySelectorAll('.rd-fila').forEach(r => {
                const d = r.dataset;
                // Sin momento sale en día y en noche; «ambas» sale en foto y en vídeo (como en el plató).
                const ok = (!f.estado || d.estado === f.estado)
                    && (!f.dn || !d.dn || d.dn === f.dn)
                    && (!f.medio || !d.medio || d.medio === f.medio || d.medio === 'ambas')
                    && (!f.imp || !d.opc)
                    && (!q || norm(d.buscar).includes(q));
                r.hidden = !ok;
                if (ok) vis++;
            });
            g.hidden = !vis;
            if (vis) { alguna = true; if (g.dataset.dnG) ladoVisible[g.dataset.dnG] = true; }
        });
        document.querySelectorAll('.rd-sep-dn').forEach(s => { s.hidden = !ladoVisible[s.dataset.sep]; });
        document.getElementById('rdNada').hidden = alguna;
    }
    buscar.addEventListener('input', filtrar);
    document.querySelectorAll('#rdFiltros [data-f]').forEach(grupo => grupo.addEventListener('click', ev => {
        const b = ev.target.closest('button'); if (!b) return;
        if (b.hasAttribute('data-conmuta')) {
            b.classList.toggle('on');
            f[grupo.dataset.f] = b.classList.contains('on') ? b.dataset.v : '';
        } else {
            grupo.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
            f[grupo.dataset.f] = b.dataset.v;
        }
        filtrar();
    }));
    // Lista / galería (se recuerda en este navegador).
    const cont = document.getElementById('rdEscenas');
    const vistaNav = document.getElementById('rdVista');
    function vista(v) {
        cont.classList.toggle('galeria', v === 'galeria');
        vistaNav.querySelectorAll('button').forEach(b => b.classList.toggle('on', b.dataset.vista === v));
        try { localStorage.setItem('rodajes_escenas_vista', v); } catch (e) {}
    }
    let guardada = 'lista';
    try { guardada = localStorage.getItem('rodajes_escenas_vista') || 'lista'; } catch (e) {}
    vista(guardada);
    vistaNav.addEventListener('click', ev => { const b = ev.target.closest('button'); if (b) vista(b.dataset.vista); });
    nav.addEventListener('click', ev => {
        const b = ev.target.closest('button');
        if (!b) return;
        nav.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
        f.estado = b.dataset.estado;
        filtrar();
    });
    // Cerrar el menú "más" al pulsar fuera.
    document.addEventListener('click', ev => {
        document.querySelectorAll('.rd-menu[open]').forEach(d => { if (!d.contains(ev.target)) d.open = false; });
    });
})();
</script>
<?= $this->endSection() ?>
