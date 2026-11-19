<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * @var array      $proyecto
 * @var array|null $sesion
 * @var array      $escenas
 * @var array      $multimedia [escena_id => ['ruta','es_video','ext']]
 * @var array      $numMedia   [escena_id => n]
 * @var array      $varFotos   [escena_id => [['ruta','texto']]] fotos de las variantes
 * @var array      $ideas      [escena_id => ['texto','enlace']] idea sin foto
 * @var array      $setups     [setup_id => setup]
 */
$varFotos ??= [];
$ideas    ??= [];
$setups   ??= [];
$franjas  = \App\Models\RodajesEscenaModel::FRANJAS;
$pid = (int) $proyecto['id'];
$estadosUi = rod_toma_estados();

// Grupos por bloque, en el orden en que aparece cada bloque por primera vez.
$grupos = [];
$cuenta = array_fill_keys(array_keys($estadosUi), 0);
foreach ($escenas as $e) {
    $nombre = trim((string) $e['escena_bloque']);
    $clave  = mb_strtolower($nombre);
    $grupos[$clave] ??= ['nombre' => $nombre !== '' ? $nombre : ($sesion ? 'Sin temática' : 'Sin bloque'), 'escenas' => []];
    $grupos[$clave]['escenas'][] = $e;
    $cuenta[rod_estado_escena($e)]++;
}
// Con sesión, los bloques son las temáticas: mismo orden que en el paso Temáticas; lo suelto, al final.
$ordenBloques ??= [];
if ($ordenBloques) {
    $pos = array_flip(array_keys($grupos));
    uksort($grupos, static fn ($a, $b) => [$a === '', $ordenBloques[$a] ?? PHP_INT_MAX, $pos[$a]] <=> [$b === '', $ordenBloques[$b] ?? PHP_INT_MAX, $pos[$b]]);
}
// Orden de rodaje: los grupos del Plan (franja · zona · setup), en su orden.
if (($ordenLista ?? '') === 'rodaje') $grupos = $gruposRodaje ?? [];
$ordenLista ??= 'narrativo';
$total = count($escenas);

$sub = ['<span>' . $total . ' ' . ($total === 1 ? 'escena' : 'escenas') . '</span>'];
if ($total) $sub[] = '<span>' . $cuenta['hecha'] . ' hechas</span>';
if ($sesion) $sub[] = '<a class="rd-ses" href="' . site_url('sesiones/' . $sesion['id']) . '"><i class="bi bi-camera"></i>' . esc($sesion['titulo']) . '</a>';

ob_start(); ?>
<a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/plato") ?>"><i class="bi bi-phone"></i>Plató</a>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/pizarra") ?>"><i class="bi bi-kanban"></i>Pizarra</a>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Toma</a>
<details class="rd-menu">
    <summary class="fv-btn" title="Más"><i class="bi bi-three-dots"></i></summary>
    <div class="rd-menu-pop">
        <a href="<?= site_url("rodajes/$pid/escenas/storyboard") ?>"><i class="bi bi-layout-text-window-reverse"></i>Storyboard</a>
        <a href="<?= site_url("rodajes/$pid/escenas/storyboard?agrupar=clasificacion") ?>"><i class="bi bi-sort-down"></i>Storyboard por clasificación</a>
        <a href="<?= site_url("rodajes/$pid/dialogos") ?>"><i class="bi bi-chat-quote"></i>Diálogos</a>
        <a href="<?= site_url("rodajes/edit/$pid") ?>"><i class="bi bi-pencil"></i>Editar proyecto</a>
        <?php if ($sesion): ?>
            <a href="<?= site_url("rodajes/$pid/tematicas") ?>"><i class="bi bi-collection"></i>Temáticas (= bloques)</a>
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
            <div>Este rodaje no tiene escenas todavía.</div>
            <div class="rd-herr" style="justify-content:center">
                <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
                <?php if ($sesion): ?>
                    <a class="fv-btn" href="<?= site_url("rodajes/$pid/ideas") ?>"><i class="bi bi-inbox"></i>Empezar por la ingesta</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="rd-herr">
            <input type="search" class="fv-input" id="rdBuscar" placeholder="Buscar en las escenas…" autocomplete="off">
            <div class="rd-vista rd-orden" role="group" aria-label="Orden">
                <a href="<?= site_url("rodajes/$pid/escenas") ?>" class="<?= $ordenLista === 'narrativo' ? 'on' : '' ?>" title="Cómo se cuenta la historia (temáticas en orden)"><i class="bi bi-book"></i> Narrativo</a>
                <a href="<?= site_url("rodajes/$pid/escenas?orden=rodaje") ?>" class="<?= $ordenLista === 'rodaje' ? 'on' : '' ?>" title="Cómo se rueda: el Plan (luz, ubicación, setup, props)"><i class="bi bi-list-ol"></i> Rodaje</a>
            </div>
            <div class="rd-vista" id="rdVista" role="group" aria-label="Vista">
                <button type="button" data-vista="lista" title="Lista"><i class="bi bi-list-ul"></i></button>
                <button type="button" data-vista="galeria" title="Galería"><i class="bi bi-grid-3x2-gap"></i></button>
            </div>
        </div>
        <nav class="rd-nav" id="rdEstados">
            <button type="button" class="on" data-estado="">Todas <small><?= $total ?></small></button>
            <?php foreach ($estadosUi as $k => [$txt]): ?>
                <?php if (!$cuenta[$k]) continue; ?>
                <button type="button" data-estado="<?= $k ?>"><?= $txt === 'Hecha' ? 'Hechas' : ($txt === 'Pendiente' ? 'Pendientes' : ($txt === 'Descartada' ? 'Descartadas' : $txt)) ?> <small><?= $cuenta[$k] ?></small></button>
            <?php endforeach; ?>
        </nav>

        <div class="rd-escenas" id="rdEscenas">
            <?php foreach ($grupos as $g): ?>
                <section class="rd-grupo">
                    <?php if (count($grupos) > 1 || !isset($grupos[''])): ?>
                        <div class="rd-bloque"><?= esc($g['nombre']) ?> <small><?= count($g['escenas']) ?></small></div>
                    <?php endif; ?>
                    <div class="rd-filas">
                        <?php foreach ($g['escenas'] as $e): ?>
                            <?php
                            $eid    = (int) $e['id'];
                            $estado = rod_estado_escena($e);
                            $partes = rod_estados_partes($e);
                            $m      = $multimedia[$eid] ?? null;
                            $buscar = mb_strtolower(implode(' ', [
                                $e['escena_bloque'], $e['escena_descripcion'], $e['escena_accion'], $e['escena_ubicacion'],
                                $e['plano_hora_dia'], $e['plano_objetos'], $e['plano_notas'], $e['escena_tomas'], $e['toma_nota'],
                            ]));
                            ?>
                            <div class="rd-fila <?= $estado ?>" data-estado="<?= $estado ?>" data-buscar="<?= esc($buscar, 'attr') ?>">
                                <span class="rd-num"><?= (int) $e['orden'] ?></span>
                                <div class="rd-mini">
                                    <?php if ($m && $m['es_video']): ?>
                                        <video src="<?= base_url($m['ruta']) ?>#t=0.1" muted playsinline preload="metadata"></video><i class="bi bi-play-fill"></i>
                                    <?php elseif ($m): ?>
                                        <img src="<?= base_url($m['ruta']) ?>" alt="" loading="lazy">
                                        <?php if (!empty($m['guia'])): ?><span class="rd-mini-tag" title="Imagen guía de la ubicación"><i class="bi bi-geo-alt"></i></span><?php endif; ?>
                                    <?php elseif (isset($ideas[$eid]) && $ideas[$eid]['texto'] !== ''): ?>
                                        <span class="rd-mini-idea"><i class="bi <?= $ideas[$eid]['enlace'] ? 'bi-link-45deg' : 'bi-chat-left-text' ?>"></i><?= esc($ideas[$eid]['texto']) ?></span>
                                    <?php else: ?>
                                        <i class="bi bi-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="rd-fila-txt">
                                    <a class="t" href="<?= site_url("rodajes/$pid/escenas/show/$eid") ?>"><?= esc(rod_titulo_escena($e)) ?></a>
                                    <?php if ($plano = rod_resumen_plano($e)): ?><div class="rd-plano"><?= $plano ?></div><?php endif; ?>
                                    <div class="m">
                                        <?= rod_formato_chip($e) ?>
                                        <?php foreach ($partes as $parte => $est): ?>
                                            <?php if ($est === 'pendiente') continue; ?>
                                            <span class="fv-estado <?= $estadosUi[$est][2] ?>"><?= count($partes) > 1 ? rod_formatos()[$parte][0] . ': ' : '' ?><?= $estadosUi[$est][0] ?></span>
                                        <?php endforeach; ?>
                                        <?php if (trim((string) $e['escena_ubicacion']) !== ''): ?><span><i class="bi bi-geo-alt"></i><?= esc($e['escena_ubicacion']) ?></span><?php endif; ?>
                                        <?php if (!empty($e['franja']) && isset($franjas[$e['franja']])): ?><span><i class="bi bi-sun"></i><?= $franjas[$e['franja']] ?></span><?php endif; ?>
                                        <?php if (!empty($e['setup_id']) && isset($setups[(int) $e['setup_id']])): ?><span><i class="bi bi-camera"></i><?= esc($setups[(int) $e['setup_id']]['nombre']) ?></span><?php endif; ?>
                                        <?php if (($e['prioridad'] ?? 'imp') === 'opc'): ?><span class="e-warn"><i class="bi bi-hourglass-split"></i>si da tiempo</span><?php endif; ?>
                                        <?php if ($e['variantes']): ?><span><i class="bi bi-diagram-2"></i><?= count($e['variantes']) ?> <?= count($e['variantes']) === 1 ? 'variante' : 'variantes' ?></span><?php endif; ?>
                                        <?php if (trim((string) $e['plano_hora_dia']) !== ''): ?><span><i class="bi bi-tag"></i><?= esc($e['plano_hora_dia']) ?></span><?php endif; ?>
                                        <?php if (($e['plano_actores'] ?? 'N') === 'S'): ?><span><i class="bi bi-person"></i>actores</span><?php endif; ?>
                                        <?php if (trim((string) $e['escena_efecto_especial']) !== ''): ?><span class="e-bad"><i class="bi bi-stars"></i>FX</span><?php endif; ?>
                                        <?php if (!empty($numMedia[$eid])): ?><span><i class="bi bi-images"></i><?= $numMedia[$eid] ?></span><?php endif; ?>
                                        <?php if (trim((string) $e['toma_nota']) !== ''): ?><span class="e-warn"><i class="bi bi-pencil"></i><?= esc($e['toma_nota']) ?></span><?php endif; ?>
                                    </div>
                                    <?php if (!empty($varFotos[$eid])): ?>
                                        <div class="rd-varf">
                                            <?php foreach (array_slice($varFotos[$eid], 0, 6) as $vf): ?>
                                                <img src="<?= base_url($vf['ruta']) ?>" alt="" loading="lazy" title="<?= esc($vf['texto'], 'attr') ?>">
                                            <?php endforeach; ?>
                                            <?php if (count($varFotos[$eid]) > 6): ?><small>+<?= count($varFotos[$eid]) - 6 ?></small><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (trim((string) $e['sonido_dialogo_escrito']) !== ''): ?>
                                        <div class="dlg"><?= esc(preg_replace('/\s+/', ' ', $e['sonido_dialogo_escrito'])) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="rd-fila-lado">
                                    <a class="fv-ibtn" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a class="fv-ibtn opc" href="<?= site_url("rodajes/$pid/escenas/delete/$eid") ?>" title="Borrar" onclick="return confirm('¿Borrar esta escena?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <div class="fv-vacio rd-vacio" id="rdNada" hidden>Ninguna escena coincide.</div>
        </div>
    <?php endif; ?>
</div>

<script>
(() => {
    const buscar = document.getElementById('rdBuscar');
    if (!buscar) return;
    const nav = document.getElementById('rdEstados');
    let estado = '';
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    function filtrar() {
        const q = norm(buscar.value.trim());
        let alguna = false;
        document.querySelectorAll('.rd-grupo').forEach(g => {
            let vis = 0;
            g.querySelectorAll('.rd-fila').forEach(f => {
                const ok = (!estado || f.dataset.estado === estado) && (!q || norm(f.dataset.buscar).includes(q));
                f.hidden = !ok;
                if (ok) vis++;
            });
            g.hidden = !vis;
            if (vis) alguna = true;
        });
        document.getElementById('rdNada').hidden = alguna;
    }
    buscar.addEventListener('input', filtrar);
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
        estado = b.dataset.estado;
        filtrar();
    });
    // Cerrar el menú "más" al pulsar fuera.
    document.addEventListener('click', ev => {
        document.querySelectorAll('.rd-menu[open]').forEach(d => { if (!d.contains(ev.target)) d.open = false; });
    });
})();
</script>
<?= $this->endSection() ?>
