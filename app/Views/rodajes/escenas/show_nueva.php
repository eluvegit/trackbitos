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
 */
helper('text');
$e   = $escena;
$pid = (int) $proyecto['id'];
$eid = (int) $e['id'];
$val = static fn (string $k): string => trim((string) ($e[$k] ?? ''));
$estado = $e['toma_estado'] ?: 'pendiente';
$estadosUi = rod_toma_estados();

$titulo = $val('escena_bloque') ?: 'Escena #' . (int) $e['orden'];
$sub = ['<span class="fv-mono">#' . (int) $e['orden'] . '</span>'];
if ($val('escena_ubicacion') !== '') $sub[] = '<span><i class="bi bi-geo-alt"></i> ' . esc($val('escena_ubicacion')) . '</span>';
if ($val('plano_hora_dia') !== '')   $sub[] = '<span><i class="bi bi-tag"></i> ' . esc($val('plano_hora_dia')) . '</span>';
if ($val('escena_tomas') !== '')     $sub[] = '<span><i class="bi bi-camera-reels"></i> ' . esc($val('escena_tomas')) . '</span>';
if (($e['plano_actores'] ?? 'N') === 'S') $sub[] = '<span><i class="bi bi-person"></i> actores</span>';
if ($val('escena_efecto_especial') !== '') $sub[] = '<span class="e-bad"><i class="bi bi-stars"></i> FX</span>';

$textos = array_filter([
    'Descripción'         => $val('escena_descripcion'),
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

            <?php if (!$textos && !$imagenes_lugar && !$imagenes_insp && $val('sonido_dialogo_escrito') === ''): ?>
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
                <div class="rd-estado-toma">
                    <?php foreach ($estadosUi as $k => [$txt, $ico, $cls]): ?>
                        <button type="button" class="<?= $cls ?><?= $estado === $k ? ' on' : '' ?>" data-estado="<?= $k ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></button>
                    <?php endforeach; ?>
                </div>
                <input type="text" class="fv-input" id="rdNota" maxlength="255" placeholder="Nota rápida de la toma…" value="<?= esc($e['toma_nota'] ?? '', 'attr') ?>">
            </section>

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
        const prev = panel.querySelector('.rd-estado-toma .on');
        panel.querySelectorAll('.rd-estado-toma button').forEach(x => x.classList.toggle('on', x === b));
        enviar({ estado: b.dataset.estado === 'pendiente' ? '' : b.dataset.estado })
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
<?= $this->endSection() ?>
