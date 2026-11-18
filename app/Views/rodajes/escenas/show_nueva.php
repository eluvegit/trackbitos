<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * @var array    $proyecto
 * @var array    $escena
 * @var int|null $prevId
 * @var int|null $nextId
 * @var array    $imagenes_lugar
 * @var array    $imagenes_insp
 * @var array    $ideas     items de moodboard de los que salió la toma
 * @var array    $fotosVar  [moodboard_item_id => item] de las variantes
 * @var array|null $zona, $punto, $setup
 */
$ideas    ??= [];
$fotosVar ??= [];
$zona     ??= null;
$punto    ??= null;
$setup    ??= null;
$franjas  = \App\Models\RodajesEscenaModel::FRANJAS;
helper('text');
$e   = $escena;
$pid = (int) $proyecto['id'];
$eid = (int) $e['id'];
$val = static fn (string $k): string => trim((string) ($e[$k] ?? ''));
$partes = rod_estados_partes($e);
$estadosUi = rod_toma_estados();

$titulo = rod_titulo_escena($e);
$sub = ['<span class="fv-mono">#' . (int) $e['orden'] . '</span>'];
if ($val('escena_bloque') !== '' && $val('escena_bloque') !== $titulo) $sub[] = '<span><i class="bi bi-collection"></i> ' . esc($val('escena_bloque')) . '</span>';
if ($f = rod_formato_chip($e)) $sub[] = $f;
if ($val('escena_ubicacion') !== '') $sub[] = '<span><i class="bi bi-geo-alt"></i> ' . esc($val('escena_ubicacion')) . '</span>';
if ($val('plano_hora_dia') !== '')   $sub[] = '<span><i class="bi bi-tag"></i> ' . esc($val('plano_hora_dia')) . '</span>';
if ($val('escena_tomas') !== '')     $sub[] = '<span><i class="bi bi-camera-reels"></i> ' . esc($val('escena_tomas')) . '</span>';
if (($e['plano_actores'] ?? 'N') === 'S') $sub[] = '<span><i class="bi bi-person"></i> actores</span>';
if ($val('escena_efecto_especial') !== '') $sub[] = '<span class="e-bad"><i class="bi bi-stars"></i> FX</span>';

$textos = array_filter([
    'Descripción'         => $val('escena_descripcion') === $titulo ? '' : $val('escena_descripcion'),
    'Objetivo narrativo'  => $val('escena_objetivo'),
    'Acción'              => $val('escena_accion'),
    'Objetos en escena'   => $val('plano_objetos'),
    'Esquema de iluminación' => $val('plano_esquema_iluminacion'),
    'Toma alternativa'    => $val('plano_toma_alternativa'),
    'Efecto especial'     => $val('escena_efecto_especial'),
    'Notas'               => $val('plano_notas'),
    'Continuidad previa'  => $val('escena_cont_previa'),
    'Continuidad posterior' => $val('escena_cont_posterior'),
], static fn ($v) => $v !== '');

$galeria = static function (array $imgs): string {
    $html = '';
    foreach ($imgs as $img) {
        $src = esc(base_url($img['ruta']), 'attr');
        $html .= '<a class="fv-thumb" href="' . $src . '" target="_blank" rel="noopener">'
            . (rod_es_video($img['ruta'])
                ? '<video src="' . $src . '#t=0.1" muted playsinline preload="metadata"></video><i class="bi bi-play-fill"></i>'
                : '<img src="' . $src . '" alt="" loading="lazy">')
            . '</a>';
    }
    return $html;
};
$enlaces = static fn (string $t): string => nl2br(auto_link(esc($t), 'both', true));

ob_start(); ?>
<?php if ($prevId): ?><a class="fv-ibtn" href="<?= site_url("rodajes/$pid/escenas/show/$prevId") ?>" title="Anterior (←)"><i class="bi bi-chevron-left"></i></a><?php else: ?><span class="fv-ibtn" style="opacity:.3"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
<?php if ($nextId): ?><a class="fv-ibtn" href="<?= site_url("rodajes/$pid/escenas/show/$nextId") ?>" title="Siguiente (→)"><i class="bi bi-chevron-right"></i></a><?php else: ?><span class="fv-ibtn" style="opacity:.3"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
<span class="fv-vsep"></span>
<?php $iconos = ob_get_clean(); ?>

<div class="fv rd">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Escena #' . (int) $e['orden'] => null],
        'titulo'   => esc($titulo),
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'iconos'   => $iconos,
        'acciones' => '<a class="fv-btn" href="' . site_url("rodajes/$pid/escenas/edit/$eid") . '"><i class="bi bi-pencil"></i>Editar</a>',
    ]) ?>

    <div class="rd-ficha">
        <div class="rd-col">
            <?php if ($ideas): ?>
                <section class="fv-panel rd-idea">
                    <div class="rd-idea-fotos<?= count($ideas) > 1 ? ' varias' : '' ?>">
                        <?php foreach ($ideas as $k => $i): ?><?= view('rodajes/flujo/_foto', ['i' => $i, 'clase' => 'xl'], ['saveData' => false]) ?><?php endforeach; ?>
                    </div>
                    <?php foreach ($ideas as $i): ?>
                        <?php if ($i['origen'] === 'archivo' && trim((string) $i['nota']) !== '' && trim((string) $i['nota']) !== $titulo): ?><div class="rd-idea-nota"><?= esc($i['nota']) ?></div><?php endif; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if ($e['variantes']): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Variantes</h3><span class="cuenta"><?= count($e['variantes']) ?></span></div>
                    <ul class="rd-vars" data-orden="<?= site_url("rodajes/$pid/escenas/$eid/variantes/orden") ?>">
                        <?php foreach ($e['variantes'] as $v): ?>
                            <?php $fv = $v['moodboard_item_id'] ? ($fotosVar[(int) $v['moodboard_item_id']] ?? null) : null; $ev = rod_estado_escena($v + ['formato' => $e['formato'] ?? null]); ?>
                            <li class="<?= $ev ?>" data-id="<?= (int) $v['id'] ?>">
                                <?php if (count($e['variantes']) > 1): ?><i class="bi bi-grip-vertical rf-asa" title="Arrastra para ordenar"></i><?php endif; ?>
                                <?php if ($fv): ?><?= view('rodajes/flujo/_foto', ['i' => $fv, 'clase' => 'grande'], ['saveData' => false]) ?><?php else: ?><span class="rf-foto grande vacia"><i class="bi bi-diagram-2"></i></span><?php endif; ?>
                                <div>
                                    <div class="t"><?= esc($v['texto']) ?></div>
                                    <div class="m">
                                        <?php if ($ev !== 'pendiente'): ?><span class="fv-estado <?= $estadosUi[$ev][2] ?>"><?= $estadosUi[$ev][0] ?></span><?php endif; ?>
                                        <?php if ($v['prioridad'] === 'opc'): ?><span class="e-warn"><i class="bi bi-hourglass-split"></i>si da tiempo</span><?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if ($tec = rod_ficha_tecnica($e, true)): ?>
                <?= str_replace('class="rd-tec"', 'class="rd-tec ancha"', $tec) ?>
            <?php endif; ?>

            <?php if ($textos): ?>
                <section class="fv-panel">
                    <?= rod_campos($textos, ['Descripción', 'Acción']) ?>
                </section>
            <?php endif; ?>

            <?php if ($val('sonido_dialogo_escrito') !== ''): ?>
                <section class="fv-panel">
                    <div class="fv-ph">
                        <h3>Diálogo</h3>
                        <div class="der rd-chips">
                            <?php if (($e['sonido_ambiente'] ?? 'N') === 'S'): ?><span class="rd-chip">ambiente</span><?php endif; ?>
                            <?php if (($e['sonido_antiviento'] ?? 'N') === 'S'): ?><span class="rd-chip">antiviento</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="rd-dialogo"><?= esc($val('sonido_dialogo_escrito')) ?></div>
                </section>
            <?php endif; ?>

            <?php foreach ([['Lugar y objetos', $imagenes_lugar, 'plano_ref_lugar_texto'], ['Inspiración', $imagenes_insp, 'plano_ref_inspiracion_texto']] as [$nombre, $imgs, $campoTxt]): ?>
                <?php if ($imgs || $val($campoTxt) !== ''): ?>
                    <section class="fv-panel">
                        <div class="fv-ph"><h3><?= $nombre ?></h3><?php if ($imgs): ?><span class="cuenta"><?= count($imgs) ?></span><?php endif; ?></div>
                        <?php if ($imgs): ?><div class="rd-galeria"><?= $galeria($imgs) ?></div><?php endif; ?>
                        <?php if ($val($campoTxt) !== ''): ?><div class="rd-texto"><div class="va"><?= $enlaces($val($campoTxt)) ?></div></div><?php endif; ?>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!$ideas && !$e['variantes'] && !$textos && !$imagenes_lugar && !$imagenes_insp && $val('sonido_dialogo_escrito') === ''): ?>
                <div class="fv-panel rd-vacio">
                    <i class="bi bi-pencil-square"></i>
                    <div>Esta escena todavía no tiene contenido.</div>
                    <a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>">Completarla</a>
                </div>
            <?php endif; ?>
        </div>

        <aside class="rd-col">
            <section class="fv-panel" id="rdToma" data-url="<?= site_url("rodajes/$pid/escenas/$eid/toma") ?>">
                <div class="fv-ph"><h3>En el plató</h3><a class="fv-link der" href="<?= site_url("rodajes/$pid/plato") ?>">Abrir plató <i class="bi bi-arrow-right"></i></a></div>
                <?php foreach ($partes as $parte => $estado): ?>
                    <div class="rd-estado-parte">
                        <?php if (count($partes) > 1): ?><div class="t"><i class="bi <?= rod_formatos()[$parte][1] ?>"></i><?= rod_formatos()[$parte][0] ?></div><?php endif; ?>
                        <div class="rd-estado-toma" data-parte="<?= $parte ?>">
                            <?php foreach ($estadosUi as $k => [$txt, $ico, $cls]): ?>
                                <button type="button" class="<?= $cls ?><?= $estado === $k ? ' on' : '' ?>" data-estado="<?= $k ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <input type="text" class="fv-input" id="rdNota" maxlength="255" placeholder="Nota rápida de la toma…" value="<?= esc($e['toma_nota'] ?? '', 'attr') ?>">
            </section>

            <?php $props = rod_props($e); ?>
            <?php if ($zona || $setup || !empty($e['franja']) || $props || ($e['prioridad'] ?? 'imp') === 'opc'): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Plan</h3><a class="fv-link der" href="<?= site_url("rodajes/$pid/plan") ?>">Ver plan <i class="bi bi-arrow-right"></i></a></div>
                    <?php if ($zona): ?>
                        <?php if ($zona['imagen']): ?>
                            <div class="rf-guia rd-guia">
                                <img src="<?= base_url($zona['imagen']) ?>" alt="Imagen guía de <?= esc($zona['nombre'], 'attr') ?>" loading="lazy">
                                <?php if ($punto && $punto['x'] !== null): ?><span class="rf-pin" style="left:<?= (float) $punto['x'] ?>%;top:<?= (float) $punto['y'] ?>%"><b><span><i class="bi bi-camera"></i></span></b><em><?= esc($punto['nombre']) ?></em></span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <dl class="rd-plan">
                        <?php if ($zona): ?><dt><i class="bi bi-geo-alt"></i>Ubicación</dt><dd><a href="<?= site_url("rodajes/$pid/ubicaciones") ?>"><?= esc($zona['nombre']) ?></a><?php if ($punto): ?> · <?= esc($punto['nombre']) ?><?php endif; ?></dd><?php endif; ?>
                        <?php if ($setup): ?><dt><i class="bi bi-camera"></i>Setup</dt><dd><a href="<?= site_url("rodajes/$pid/setups") ?>"><?= esc($setup['nombre']) ?></a><?php if ($l = rod_setup_linea($setup)): ?><small><?= $l ?></small><?php endif; ?><?php if (!empty($setup['imagen'])): ?><img class="rd-setup-img" src="<?= base_url($setup['imagen']) ?>" alt="" loading="lazy"><?php endif; ?></dd><?php endif; ?>
                        <?php if (!empty($e['franja']) && isset($franjas[$e['franja']])): ?><dt><i class="bi bi-sun"></i>Franja</dt><dd><?= $franjas[$e['franja']] ?></dd><?php endif; ?>
                        <?php if (($e['prioridad'] ?? 'imp') === 'opc'): ?><dt><i class="bi bi-hourglass-split"></i>Prioridad</dt><dd>Si da tiempo</dd><?php endif; ?>
                        <?php if ($props): ?><dt><i class="bi bi-box"></i>Props</dt><dd class="rd-chips"><?php foreach ($props as $pr): ?><span class="rd-chip"><?= esc($pr) ?></span><?php endforeach; ?></dd><?php endif; ?>
                    </dl>
                </section>
            <?php endif; ?>

            <div class="rd-pasar">
                <?php if ($prevId): ?><a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/show/$prevId") ?>"><i class="bi bi-arrow-left"></i>Anterior</a><?php else: ?><span></span><?php endif; ?>
                <?php if ($nextId): ?><a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/show/$nextId") ?>">Siguiente<i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<script>
(() => {
    const panel = document.getElementById('rdToma');
    const enviar = datos => fetch(panel.dataset.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
        body: JSON.stringify(datos),
    }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

    panel.querySelectorAll('.rd-estado-toma button').forEach(b => b.addEventListener('click', () => {
        const grupo = b.closest('.rd-estado-toma');
        const prev = grupo.querySelector('.on');
        grupo.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
        enviar({ estado: b.dataset.estado === 'pendiente' ? '' : b.dataset.estado, parte: grupo.dataset.parte })
            .catch(() => { b.classList.remove('on'); prev && prev.classList.add('on'); });
    }));

    const nota = document.getElementById('rdNota');
    let guardada = nota.value;
    nota.addEventListener('blur', () => {
        if (nota.value === guardada) return;
        const v = nota.value;
        enviar({ nota: v }).then(() => { guardada = v; }).catch(() => nota.classList.add('is-invalid'));
    });
    nota.addEventListener('keydown', ev => { if (ev.key === 'Enter') { ev.preventDefault(); nota.blur(); } });

    document.addEventListener('keydown', ev => {
        if (ev.target.closest('input, textarea, select')) return;
        <?php if ($prevId): ?>if (ev.key === 'ArrowLeft') location.href = '<?= site_url("rodajes/$pid/escenas/show/$prevId") ?>';<?php endif; ?>
        <?php if ($nextId): ?>if (ev.key === 'ArrowRight') location.href = '<?= site_url("rodajes/$pid/escenas/show/$nextId") ?>';<?php endif; ?>
    });
})();
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
