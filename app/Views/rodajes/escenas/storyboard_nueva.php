<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Storyboard (estilo fv): oscuro en pantalla, claro y compacto al imprimir.
 * Lo que se oculta con los filtros tampoco se imprime. Solo los dos órdenes:
 * narrativo (temáticas) o rodaje (grupos del Plan).
 *
 * @var array  $proyecto
 * @var array  $grupos     [['nombre', 'zona', 'dn', 'color', 'escenas' => [...]], ...]
 * @var string $orden      narrativo | rodaje
 * @var bool   $hayFormato
 * @var array  $multimedia [escena_id => ['ruta','es_video','guia']] portada
 * @var array  $varFotos   [escena_id => [variante_id => ruta]]
 * @var array  $ideas      [escena_id => ['texto','enlace']]
 * @var array  $setups     [setup_id => setup]
 */
$pid      = (int) $proyecto['id'];
$estados  = rod_toma_estados();
$formatos = rod_formatos();
$total    = array_sum(array_map(static fn ($g) => count($g['escenas']), $grupos));
$qOrden   = $orden === 'rodaje' ? '?orden=rodaje' : '';

ob_start(); ?>
<button type="button" class="fv-btn" onclick="window.print()"><i class="bi bi-printer"></i>Imprimir</button>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/pizarra") ?>"><i class="bi bi-kanban"></i>Pizarra</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rd storyboard">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas$qOrden"), 'Storyboard' => null],
        'titulo'   => 'Storyboard',
        'sub'      => '<span>' . esc($proyecto['titulo']) . '</span><span class="fv-dot"></span><span>' . $total . ' ' . ($total === 1 ? 'toma' : 'tomas') . '</span><span class="fv-dot"></span><span>Orden ' . ($orden === 'rodaje' ? 'de rodaje' : 'narrativo') . '</span>',
        'acciones' => $acciones,
    ]) ?>

    <?php if (!$grupos): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-layout-text-window-reverse"></i>
            <div>Este rodaje no tiene tomas todavía.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
        </div>
    <?php else: ?>
        <div class="rd-herr sb-herr">
            <span class="rd-lbl">Orden</span>
            <div class="fv-seg sb-seg">
                <a href="<?= site_url("rodajes/$pid/escenas/storyboard") ?>" aria-pressed="<?= $orden === 'narrativo' ? 'true' : 'false' ?>"><i class="bi bi-book"></i>Narrativo</a>
                <a href="<?= site_url("rodajes/$pid/escenas/storyboard?orden=rodaje") ?>" aria-pressed="<?= $orden === 'rodaje' ? 'true' : 'false' ?>"><i class="bi bi-list-ol"></i>Rodaje</a>
            </div>
            <?php if ($hayFormato): ?>
                <div class="fv-seg sb-seg" id="sbMedio">
                    <button type="button" data-medio="" aria-pressed="true">Todo</button>
                    <button type="button" data-medio="foto" aria-pressed="false"><i class="bi bi-camera"></i>Foto</button>
                    <button type="button" data-medio="video" aria-pressed="false"><i class="bi bi-camera-video"></i>Vídeo</button>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($grupos) > 1): ?>
            <nav class="sb-chips sb-herr" id="sbGrupos" title="Pulsa para ocultar o mostrar un grupo (lo oculto no se imprime)">
                <?php foreach ($grupos as $i => $g): ?>
                    <button type="button" class="on" data-g="<?= $i ?>" style="--c:<?= $g['color'] ?>"><i class="pl-punto"></i><?= esc($g['nombre']) ?> <small><?= count($g['escenas']) ?></small></button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="sb-grupos">
            <?php $sepVisto = []; ?>
            <?php foreach ($grupos as $i => $g): ?>
                <?php if ($g['dn'] !== '' && !isset($sepVisto[$g['dn']])): $sepVisto[$g['dn']] = true; ?>
                    <div class="rd-sep-dn sb-sep <?= $g['dn'] ?>"><i class="bi <?= $g['dn'] === 'noche' ? 'bi-moon-stars-fill' : 'bi-sun-fill' ?>"></i><?= $g['dn'] === 'noche' ? 'Noche' : 'Día' ?></div>
                <?php endif; ?>
                <section class="sb-grupo" style="--n:<?= count($g['escenas']) ?>;--c:<?= $g['color'] ?>" data-g="<?= $i ?>">
                    <h2><i class="pl-punto"></i><?= esc($g['nombre']) ?> <small><?= count($g['escenas']) ?></small></h2>
                    <div class="sb-rejilla">
                        <?php foreach ($g['escenas'] as $e): ?>
                            <?php
                            $eid    = (int) $e['id'];
                            $partes = rod_estados_partes($e);
                            $m      = $multimedia[$eid] ?? null;
                            $props  = rod_props($e);
                            ?>
                            <article class="sb-card" data-medio="<?= esc((string) $e['formato'], 'attr') ?>">
                                <div class="sb-imgs">
                                    <figure>
                                        <?php if ($m && $m['es_video']): ?>
                                            <video src="<?= base_url($m['ruta']) ?>#t=0.1" muted playsinline preload="metadata"></video>
                                        <?php elseif ($m): ?>
                                            <img src="<?= base_url($m['ruta']) ?>" alt="" loading="lazy">
                                            <?php if (!empty($m['guia'])): ?><figcaption><i class="bi bi-geo-alt"></i> zona</figcaption><?php endif; ?>
                                        <?php elseif (isset($ideas[$eid]) && $ideas[$eid]['texto'] !== ''): ?>
                                            <span class="sb-idea"><?= esc($ideas[$eid]['texto']) ?></span>
                                        <?php else: ?>
                                            <span class="sb-idea vacia"><i class="bi bi-image"></i></span>
                                        <?php endif; ?>
                                    </figure>
                                </div>
                                <div class="sb-cuerpo">
                                    <div class="sb-linea">
                                        <a class="sb-num" href="<?= site_url("rodajes/$pid/escenas/show/$eid$qOrden") ?>">#<?= (int) $e['orden'] ?></a>
                                        <?= rod_formato_chip($e) ?>
                                        <?php foreach ($partes as $p => $est): ?>
                                            <?php if ($est === 'pendiente') continue; ?>
                                            <span class="fv-estado <?= $estados[$est][2] ?>"><?= count($partes) > 1 ? $formatos[$p][0] . ': ' : '' ?><?= $estados[$est][0] ?></span>
                                        <?php endforeach; ?>
                                        <?php if (($e['prioridad'] ?? 'imp') === 'opc'): ?><span class="e-warn"><i class="bi bi-hourglass-split"></i></span><?php endif; ?>
                                    </div>
                                    <div class="sb-t"><?= esc(rod_titulo_escena($e)) ?></div>
                                    <div class="sb-meta">
                                        <?= rod_momento($e) ?>
                                        <?php if (trim((string) $e['escena_ubicacion']) !== ''): ?><span><i class="bi bi-geo-alt"></i><?= esc($e['escena_ubicacion']) ?></span><?php endif; ?>
                                        <?php if ($orden === 'rodaje' && trim((string) $e['escena_bloque']) !== ''): ?><span><i class="bi bi-collection"></i><?= esc($e['escena_bloque']) ?></span><?php endif; ?>
                                    </div>
                                    <div class="rd-stps"><?= rod_chips_setups(rod_cajas_toma($e, $setups)) ?></div>
                                    <?php if ($props): ?><div class="rd-props"><i class="bi bi-box-seam"></i><span><?= esc(implode(', ', $props)) ?></span></div><?php endif; ?>
                                    <?php if ($e['variantes']): ?>
                                        <div class="rd-vmini">
                                            <?php foreach ($e['variantes'] as $va): ?>
                                                <?php $ruta = $varFotos[$eid][(int) $va['id']] ?? null; $extra = rod_props_extra($e, $va); ?>
                                                <span class="rd-vm" title="<?= esc($va['texto'] . ($extra ? ' (+ ' . implode(', ', $extra) . ')' : ''), 'attr') ?>">
                                                    <?php if ($ruta): ?><img src="<?= base_url($ruta) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-diagram-2"></i><?php endif; ?>
                                                    <?php if ($extra): ?><em>+<?= count($extra) ?></em><?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (trim((string) $e['sonido_dialogo_escrito']) !== ''): ?>
                                        <blockquote class="sb-dlg"><?= nl2br(esc(trim($e['sonido_dialogo_escrito']))) ?></blockquote>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <div class="fv-vacio rd-vacio" id="sbNada" hidden>Nada que mostrar con estos filtros.</div>
        </div>
    <?php endif; ?>
</div>

<script>
(() => {
    let medio = '';
    const ocultos = new Set();
    function filtrar() {
        let alguno = false;
        document.querySelectorAll('.sb-grupo').forEach(sec => {
            let vis = 0;
            sec.querySelectorAll('.sb-card').forEach(c => {
                const m = c.dataset.medio;
                c.hidden = medio !== '' && m !== '' && m !== medio && m !== 'ambas';
                if (!c.hidden) vis++;
            });
            sec.hidden = !vis || ocultos.has(sec.dataset.g);
            if (!sec.hidden) alguno = true;
        });
        const nada = document.getElementById('sbNada');
        if (nada) nada.hidden = alguno;
    }
    document.querySelectorAll('#sbMedio button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#sbMedio button').forEach(x => x.setAttribute('aria-pressed', x === b));
        medio = b.dataset.medio;
        filtrar();
    }));
    document.querySelectorAll('#sbGrupos button').forEach(b => b.addEventListener('click', () => {
        b.classList.toggle('on');
        b.classList.contains('on') ? ocultos.delete(b.dataset.g) : ocultos.add(b.dataset.g);
        filtrar();
    }));
})();
</script>
<?= $this->endSection() ?>
