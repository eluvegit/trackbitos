<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Storyboard (estilo fv): oscuro en pantalla, claro y compacto al imprimir.
 * Lo que se oculta con los filtros tampoco se imprime.
 *
 * @var array  $proyecto
 * @var array  $groups     [clave => ['_title', '_first', 'transicion', 'items' => [['escena', 'cover_lugar', 'cover_insp'], ...]]]
 * @var string $agrupar    bloque | clasificacion
 * @var bool   $hayFormato
 */
$pid      = (int) $proyecto['id'];
$estados  = rod_toma_estados();
$formatos = rod_formatos();
$total    = array_sum(array_map(static fn ($g) => count($g['items']), $groups));
$url      = static fn (string $a) => site_url("rodajes/$pid/escenas/storyboard") . ($a === 'clasificacion' ? '?agrupar=clasificacion' : '');
$medio    = static function (?string $ruta, string $alt): string {
    if (!$ruta) return '';
    $src = base_url($ruta);
    return '<figure>' . (rod_es_video($ruta)
        ? '<video src="' . $src . '#t=0.1" muted playsinline preload="metadata"></video>'
        : '<img src="' . $src . '" alt="" loading="lazy">') . '<figcaption>' . $alt . '</figcaption></figure>';
};

ob_start(); ?>
<button type="button" class="fv-btn" onclick="window.print()"><i class="bi bi-printer"></i>Imprimir</button>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/pizarra") ?>"><i class="bi bi-kanban"></i>Pizarra</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rd storyboard">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Storyboard' => null],
        'titulo'   => 'Storyboard',
        'sub'      => '<span>' . esc($proyecto['titulo']) . '</span><span class="fv-dot"></span><span>' . $total . ' ' . ($total === 1 ? 'escena' : 'escenas') . '</span>',
        'acciones' => $acciones,
    ]) ?>

    <?php if (!$groups): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-layout-text-window-reverse"></i>
            <div>Este rodaje no tiene escenas todavía.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
        </div>
    <?php else: ?>
        <div class="rd-herr sb-herr">
            <span class="rd-lbl">Agrupar por</span>
            <div class="fv-seg sb-seg">
                <a href="<?= $url('bloque') ?>" aria-pressed="<?= $agrupar === 'bloque' ? 'true' : 'false' ?>">Bloque</a>
                <a href="<?= $url('clasificacion') ?>" aria-pressed="<?= $agrupar === 'clasificacion' ? 'true' : 'false' ?>">Clasificación</a>
            </div>
            <?php if ($hayFormato): ?>
                <div class="fv-seg sb-seg" id="sbMedio">
                    <button type="button" data-medio="" aria-pressed="true">Todo</button>
                    <button type="button" data-medio="foto" aria-pressed="false"><i class="bi bi-camera"></i>Foto</button>
                    <button type="button" data-medio="video" aria-pressed="false"><i class="bi bi-camera-video"></i>Vídeo</button>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($groups) > 1): ?>
            <nav class="sb-chips sb-herr" id="sbGrupos" title="Pulsa para ocultar o mostrar un grupo (lo oculto no se imprime)">
                <?php $i = 0; foreach ($groups as $g): if ($g['transicion']) continue; ?>
                    <button type="button" class="on" data-g="<?= $i ?>"><?= esc($g['_title']) ?> <small><?= count($g['items']) ?></small></button>
                <?php $i++; endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="sb-grupos">
            <?php $i = 0; foreach ($groups as $g): ?>
                <section class="sb-grupo<?= $g['transicion'] ? ' trans' : '' ?>" style="--n:<?= count($g['items']) ?>"<?= $g['transicion'] ? '' : ' data-g="' . $i++ . '"' ?>>
                    <h2><?= esc($g['_title']) ?><?php if (!$g['transicion']): ?> <small><?= count($g['items']) ?></small><?php endif; ?></h2>
                    <div class="sb-rejilla">
                        <?php foreach ($g['items'] as $it): ?>
                            <?php
                            $e      = $it['escena'];
                            $eid    = (int) $e['id'];
                            $partes = rod_estados_partes($e);
                            $imgs   = $medio($it['cover_lugar'], 'Lugar') . $medio($it['cover_insp'], 'Inspiración');
                            $otro   = $agrupar === 'bloque' ? trim((string) $e['plano_hora_dia']) : trim((string) $e['escena_bloque']);
                            ?>
                            <article class="sb-card" data-medio="<?= esc((string) $e['formato'], 'attr') ?>">
                                <?php if ($imgs): ?><div class="sb-imgs"><?= $imgs ?></div><?php endif; ?>
                                <div class="sb-cuerpo">
                                    <div class="sb-linea">
                                        <a class="sb-num" href="<?= site_url("rodajes/$pid/escenas/show/$eid") ?>">#<?= (int) $e['orden'] ?></a>
                                        <?= rod_formato_chip($e) ?>
                                        <?php foreach ($partes as $p => $est): ?>
                                            <?php if ($est === 'pendiente') continue; ?>
                                            <span class="fv-estado <?= $estados[$est][2] ?>"><?= count($partes) > 1 ? $formatos[$p][0] . ': ' : '' ?><?= $estados[$est][0] ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="sb-t"><?= esc(rod_titulo_escena($e)) ?></div>
                                    <div class="sb-meta">
                                        <?php if (trim((string) $e['escena_tomas']) !== ''): ?><span><i class="bi bi-film"></i><?= esc($e['escena_tomas']) ?></span><?php endif; ?>
                                        <?php if (trim((string) $e['escena_ubicacion']) !== ''): ?><span><i class="bi bi-geo-alt"></i><?= esc($e['escena_ubicacion']) ?></span><?php endif; ?>
                                        <?php if ($otro !== ''): ?><span><i class="bi <?= $agrupar === 'bloque' ? 'bi-tag' : 'bi-collection' ?>"></i><?= esc($otro) ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($plano = rod_resumen_plano($e)): ?><div class="sb-plano"><?= $plano ?></div><?php endif; ?>
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
                c.hidden = medio !== '' && m !== medio && m !== 'ambas';
                if (!c.hidden) vis++;
            });
            sec.hidden = !vis || (sec.dataset.g !== undefined && ocultos.has(sec.dataset.g));
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
