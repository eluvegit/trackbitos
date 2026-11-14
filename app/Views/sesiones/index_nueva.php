<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Tablero de sesiones (estilo fv). Una fila por sesión; las pestañas filtran
 * en cliente: Activas (por defecto) / Ideas / Completadas / Todas. Las ideas
 * no salen por defecto (regla del módulo), solo en su pestaña o en Todas.
 *
 * @var array $sesiones
 * @var array $resumen [sesion_id => contadores]
 */
$estadosUi = ses_estados();
$orden     = array_keys($estadosUi);
$partesUi  = ses_partes();
$hoy       = date('Y-m-d');

$grupos = ['activa' => 0, 'idea' => 0, 'completada' => 0];
$proxima = null;
$pendEntrega = 0;
foreach ($sesiones as &$s) {
    $s['_grupo'] = ses_grupo($s);
    $grupos[$s['_grupo']]++;
    if ($s['entrega_modelos'] === 'pendiente') $pendEntrega++;
    if ($s['fecha_sesion'] && $s['fecha_sesion'] >= $hoy && $s['_grupo'] !== 'completada'
        && (!$proxima || $s['fecha_sesion'] < $proxima['fecha_sesion'])) {
        $proxima = $s;
    }
}
unset($s);

// Primero las que tienen fecha por delante (la más cercana arriba); después
// el resto, por última actividad.
usort($sesiones, static function ($a, $b) use ($hoy) {
    $fa = $a['fecha_sesion'] && $a['fecha_sesion'] >= $hoy ? $a['fecha_sesion'] : null;
    $fb = $b['fecha_sesion'] && $b['fecha_sesion'] >= $hoy ? $b['fecha_sesion'] : null;
    if ($fa && $fb) return strcmp($fa, $fb);
    if ($fa xor $fb) return $fa ? -1 : 1;
    return strcmp((string) $b['actualizada_at'], (string) $a['actualizada_at']);
});

$sub = ['<span>' . count($sesiones) . ' ' . (count($sesiones) === 1 ? 'sesión' : 'sesiones') . '</span>'];
ob_start(); ?>
<a class="fv-btn" href="<?= site_url('sesiones/crear') ?>?idea=1"><i class="bi bi-lightbulb"></i>Idea</a>
<a class="fv-btn fv-btn-pri" href="<?= site_url('sesiones/crear') ?>"><i class="bi bi-plus-lg"></i>Nueva sesión</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv ss" id="ssTablero" data-base="<?= site_url('sesiones') ?>">
    <?= view('sesiones/_cabecera', [
        'titulo'   => 'Sesiones',
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => $acciones,
    ]) ?>

    <?php if (!$sesiones): ?>
        <div class="fv-panel ss-vacio">
            <i class="bi bi-camera"></i>
            <div>Todavía no hay sesiones.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url('sesiones/crear') ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
        </div>
    <?php else: ?>
        <div class="ss-cifras">
            <?php if ($proxima): ?>
                <a class="ss-cifra acc" href="<?= site_url('sesiones/' . $proxima['id']) ?>">
                    <span class="k"><i class="bi bi-calendar-event"></i>Próxima sesión</span>
                    <span class="v"><?= esc(ses_fecha_relativa($proxima['fecha_sesion'])) ?></span>
                    <span class="s"><?= esc($proxima['titulo']) ?></span>
                </a>
            <?php else: ?>
                <div class="ss-cifra">
                    <span class="k"><i class="bi bi-calendar-event"></i>Próxima sesión</span>
                    <span class="v t3">—</span>
                    <span class="s">sin fecha a la vista</span>
                </div>
            <?php endif; ?>
            <div class="ss-cifra">
                <span class="k"><i class="bi bi-activity"></i>En marcha</span>
                <span class="v"><?= $grupos['activa'] ?></span>
                <span class="s"><?= $grupos['completada'] ?> completadas</span>
            </div>
            <div class="ss-cifra<?= $pendEntrega ? ' warn' : '' ?>">
                <span class="k"><i class="bi bi-send"></i>Entregar a modelos</span>
                <span class="v"><?= $pendEntrega ?></span>
                <span class="s"><?= $pendEntrega ? 'pendientes de entregar' : 'nada pendiente' ?></span>
            </div>
            <div class="ss-cifra">
                <span class="k"><i class="bi bi-lightbulb"></i>Ideas</span>
                <span class="v"><?= $grupos['idea'] ?></span>
                <span class="s">en la recámara</span>
            </div>
        </div>

        <div class="ss-herr">
            <nav class="ss-nav" id="ssPestanas">
                <button type="button" class="on" data-grupo="activa">Activas <small><?= $grupos['activa'] ?></small></button>
                <button type="button" data-grupo="idea">Ideas <small><?= $grupos['idea'] ?></small></button>
                <button type="button" data-grupo="completada">Completadas <small><?= $grupos['completada'] ?></small></button>
                <button type="button" data-grupo="">Todas <small><?= count($sesiones) ?></small></button>
            </nav>
            <input type="search" class="fv-input" id="ssBuscar" placeholder="Buscar…" autocomplete="off">
        </div>

        <div class="ss-lista" id="ssLista">
            <?php foreach ($sesiones as $s): ?>
                <?php
                $sid = (int) $s['id'];
                $r   = $resumen[$sid] ?? [];
                $pausada = (int) $s['pausada'] === 1;
                $pasada  = $s['fecha_sesion'] && $s['fecha_sesion'] < $hoy;
                ?>
                <article class="ss-fila<?= $pausada ? ' pausada' : '' ?>" data-grupo="<?= $s['_grupo'] ?>" data-buscar="<?= esc(mb_strtolower($s['titulo'] . ' ' . strip_tags((string) $s['notas'])), 'attr') ?>" data-id="<?= $sid ?>">
                    <a class="ss-portada<?= empty($r['portada']) ? ' sin' : '' ?>" href="<?= site_url('sesiones/' . $sid) ?>" tabindex="-1">
                        <?php if (!empty($r['portada'])): ?><img src="<?= base_url($r['portada']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-camera"></i><?php endif; ?>
                    </a>
                    <div class="ss-info">
                        <a class="t" href="<?= site_url('sesiones/' . $sid) ?>"><?= esc($s['titulo']) ?></a>
                        <div class="m">
                            <?php if ($s['fecha_sesion']): ?>
                                <span class="<?= $pasada ? '' : 'fecha' ?>" title="<?= esc($s['fecha_sesion']) ?>"><i class="bi bi-calendar3"></i><?= esc(ses_fecha_relativa($s['fecha_sesion'])) ?></span>
                            <?php endif; ?>
                            <?php if ($pausada): ?><span class="e-warn"><i class="bi bi-pause-fill"></i>Pausada</span><?php endif; ?>
                            <?php if ($s['entrega_modelos'] === 'pendiente'): ?><span class="e-warn"><i class="bi bi-send"></i>Entregar</span><?php endif; ?>
                            <?php if (!empty($r['situaciones'])): ?><span title="Situaciones"><i class="bi bi-collection"></i><?= (int) $r['situaciones'] ?></span><?php endif; ?>
                            <?php if (!empty($r['moodboard'])): ?><span title="Moodboard"><i class="bi bi-images"></i><?= (int) $r['moodboard'] ?></span><?php endif; ?>
                            <?php if (!empty($r['equipo'])): ?><span title="Equipo preparado"<?= (int) $r['equipo_ok'] === (int) $r['equipo'] ? ' class="e-ok"' : '' ?>><i class="bi bi-bag-check"></i><?= (int) $r['equipo_ok'] ?>/<?= (int) $r['equipo'] ?></span><?php endif; ?>
                            <?php if (!empty($r['releases'])): ?><span title="Model releases"><i class="bi bi-file-earmark-text"></i><?= (int) $r['releases'] ?></span><?php endif; ?>
                            <?php if (!empty($r['rodaje_total'])): ?><span title="Tomas hechas en el rodaje"><i class="bi bi-film"></i><?= (int) $r['rodaje_hechas'] ?>/<?= (int) $r['rodaje_total'] ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="ss-partes">
                        <?php foreach ($partesUi as $parte => [$nombreParte, $icoParte]): ?>
                            <?php $est = $s['estado_' . $parte]; if ($est === null) continue; $idx = array_search($est, $orden, true); ?>
                            <div class="ss-parte" data-parte="<?= $parte ?>" style="--c: <?= $estadosUi[$est][2] ?>">
                                <i class="bi <?= $icoParte ?>" title="<?= $nombreParte ?>"></i>
                                <div class="ss-pasos" role="group" aria-label="Etapa de <?= $nombreParte ?>">
                                    <?php foreach ($orden as $i => $e): ?>
                                        <button type="button" class="<?= $i <= $idx ? 'on' : '' ?>" data-estado="<?= $e ?>" title="<?= $estadosUi[$e][0] ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <span class="lbl"><?= $estadosUi[$est][0] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <div class="ss-vacio" id="ssNada" hidden>Nada por aquí.</div>
        </div>
    <?php endif; ?>
</div>

<script>
(() => {
    const raiz = document.getElementById('ssTablero');
    const lista = document.getElementById('ssLista');
    if (!lista) return;
    const ESTADOS = <?= json_encode(array_map(static fn ($e) => ['t' => $e[0], 'c' => $e[2]], $estadosUi), JSON_UNESCAPED_UNICODE) ?>;
    const ORDEN = Object.keys(ESTADOS);
    const buscar = document.getElementById('ssBuscar');
    const nav = document.getElementById('ssPestanas');
    let grupo = 'activa';
    try { grupo = sessionStorage.getItem('ss_tab') ?? 'activa'; } catch (e) {}

    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    function filtrar() {
        const q = norm(buscar.value.trim());
        let n = 0;
        lista.querySelectorAll('.ss-fila').forEach(f => {
            const ok = (!grupo || f.dataset.grupo === grupo) && (!q || norm(f.dataset.buscar).includes(q));
            f.hidden = !ok;
            f.classList.toggle('primera', ok && n === 0);
            if (ok) n++;
        });
        document.getElementById('ssNada').hidden = n > 0;
        nav.querySelectorAll('button').forEach(b => b.classList.toggle('on', b.dataset.grupo === grupo));
    }
    nav.addEventListener('click', ev => {
        const b = ev.target.closest('button');
        if (!b) return;
        grupo = b.dataset.grupo;
        try { sessionStorage.setItem('ss_tab', grupo); } catch (e) {}
        filtrar();
    });
    buscar.addEventListener('input', filtrar);

    // Cambiar de etapa tocando un segmento de la barra (sin recargar).
    lista.addEventListener('click', async ev => {
        const seg = ev.target.closest('.ss-pasos button');
        if (!seg) return;
        const parte = seg.closest('.ss-parte');
        const fila = seg.closest('.ss-fila');
        const estado = seg.dataset.estado;
        const r = await fetch(raiz.dataset.base + '/' + fila.dataset.id + '/estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ parte: parte.dataset.parte, estado }),
        }).catch(() => null);
        if (!r || !r.ok) { alert('No se ha podido cambiar la etapa.'); return; }
        const idx = ORDEN.indexOf(estado);
        parte.querySelectorAll('.ss-pasos button').forEach((b, i) => b.classList.toggle('on', i <= idx));
        parte.style.setProperty('--c', ESTADOS[estado].c);
        parte.querySelector('.lbl').textContent = ESTADOS[estado].t;
        // Recalcular la pestaña de la fila (no se mueve hasta cambiar de filtro).
        const est = [...fila.querySelectorAll('.ss-parte')].map(p => ORDEN[p.querySelectorAll('.ss-pasos .on').length - 1]);
        fila.dataset.grupo = est.every(e => e === 'idea') ? 'idea' : (est.every(e => e === 'completado') ? 'completada' : 'activa');
    });

    filtrar();
})();
</script>
<?= $this->endSection() ?>
