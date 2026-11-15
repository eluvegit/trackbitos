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

// ---- Vista "Trabajo": una tarjeta por PARTE (foto/vídeo) pendiente ----
// Columnas = lo que toca hacer. Idea y completado no son trabajo pendiente.
$columnas = [
    'planificacion' => ['Preparar', 'bi-pencil-square', 'Pasar a edición'],
    'edicion'       => ['Editar', 'bi-sliders', 'Pasar a subir'],
    'subiendo'      => ['Subir', 'bi-cloud-arrow-up', 'Completar'],
];
$trabajo = array_fill_keys(array_keys($columnas), []);
foreach ($sesiones as $s) {
    foreach (array_keys($partesUi) as $parte) {
        $est = $s['estado_' . $parte];
        if (!isset($columnas[$est])) continue;
        $desde = $resumen[(int) $s['id']]['desde_' . $parte] ?? $s['creada_at'];
        $trabajo[$est][] = [
            's'      => $s,
            'parte'  => $parte,
            'dias'   => $desde ? max(0, (int) floor((time() - strtotime($desde)) / 86400)) : null,
        ];
    }
}
// Dentro de cada columna: lo pausado al final y, si no, lo que más lleva esperando arriba.
foreach ($trabajo as &$col) {
    usort($col, static fn ($a, $b) => [(int) $a['s']['pausada'], -($a['dias'] ?? 0)] <=> [(int) $b['s']['pausada'], -($b['dias'] ?? 0)]);
}
unset($col);
$siguiente = ['planificacion' => 'edicion', 'edicion' => 'subiendo', 'subiendo' => 'completado'];

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

        <div class="ss-modo">
            <div class="ss-seg" id="ssModo">
                <button type="button" data-modo="sesiones"><i class="bi bi-collection"></i>Por sesión</button>
                <button type="button" data-modo="trabajo"><i class="bi bi-kanban"></i>Por trabajo <small><?= array_sum(array_map('count', $trabajo)) ?></small></button>
            </div>
            <input type="search" class="fv-input" id="ssBuscar" placeholder="Buscar…" autocomplete="off">
        </div>

        <!-- POR TRABAJO -->
        <div class="ss-trabajo" id="ssTrabajo" data-vista="trabajo" hidden>
            <div class="ss-seg ss-filtro-parte" id="ssParte">
                <button type="button" data-parte="">Todo</button>
                <?php foreach ($partesUi as $p => [$nombre, $ico, $colorP]): ?>
                    <button type="button" data-parte="<?= $p ?>" style="--p: <?= $colorP ?>"><i class="bi <?= $ico ?>"></i><?= $p === 'foto' ? 'Solo foto' : 'Solo vídeo' ?></button>
                <?php endforeach; ?>
            </div>
            <div class="ss-columnas">
                <?php foreach ($columnas as $est => [$titCol, $icoCol]): ?>
                    <section class="ss-col" data-col="<?= $est ?>" style="--c: <?= $estadosUi[$est][2] ?>">
                        <header><i class="bi <?= $icoCol ?>"></i><?= $titCol ?><span class="n" data-n>0</span></header>
                        <div class="ss-tarjetas">
                            <?php foreach ($trabajo[$est] as $t): ?>
                                <?php
                                $s = $t['s']; $sid = (int) $s['id']; $parte = $t['parte'];
                                $dias = $t['dias'];
                                $claseDias = $dias === null ? '' : ($dias > 30 ? 'e-bad' : ($dias > 14 ? 'e-warn' : ''));
                                ?>
                                <article class="ss-tarjeta<?= (int) $s['pausada'] === 1 ? ' pausada' : '' ?>" data-id="<?= $sid ?>" data-parte="<?= $parte ?>" data-estado="<?= $est ?>"
                                         data-buscar="<?= esc(mb_strtolower($s['titulo']), 'attr') ?>" style="--p: <?= $partesUi[$parte][2] ?>">
                                    <div class="ss-tarjeta-parte" title="<?= $partesUi[$parte][0] ?>">
                                        <i class="bi <?= $partesUi[$parte][1] ?>"></i>
                                        <small><?= $parte === 'foto' ? 'Foto' : 'Vídeo' ?></small>
                                    </div>
                                    <div class="ss-tarjeta-cuerpo">
                                    <a class="t" href="<?= site_url('sesiones/' . $sid) ?>"><?= esc($s['titulo']) ?></a>
                                    <div class="m">
                                        <?php if ($dias !== null): ?><span class="ss-dias <?= $claseDias ?>" title="Tiempo en esta etapa"><i class="bi bi-hourglass-split"></i><b><?= $dias === 0 ? 'hoy' : $dias . ' d en ' . mb_strtolower($estadosUi[$est][0]) ?></b></span><?php endif; ?>
                                        <?php if ($s['fecha_sesion']): ?><span><i class="bi bi-calendar3"></i><?= esc(ses_fecha_relativa($s['fecha_sesion'])) ?></span><?php endif; ?>
                                        <?php if ((int) $s['pausada'] === 1): ?><span class="e-warn"><i class="bi bi-pause-fill"></i>Pausada</span><?php endif; ?>
                                    </div>
                                    <button type="button" class="ss-hecho" data-siguiente="<?= $siguiente[$est] ?>"><i class="bi bi-check-lg"></i><span><?= $columnas[$est][2] ?></span></button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="ss-col-vacia">Nada por aquí</div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- POR SESIÓN -->
        <div class="ss-porsesion" data-vista="sesiones">
        <div class="ss-herr">
            <nav class="ss-nav" id="ssPestanas">
                <button type="button" class="on" data-grupo="activa">Activas <small><?= $grupos['activa'] ?></small></button>
                <button type="button" data-grupo="idea">Ideas <small><?= $grupos['idea'] ?></small></button>
                <button type="button" data-grupo="completada">Completadas <small><?= $grupos['completada'] ?></small></button>
                <button type="button" data-grupo="">Todas <small><?= count($sesiones) ?></small></button>
            </nav>
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
                        <?php foreach ($partesUi as $parte => [$nombreParte, $icoParte, $colorParte]): ?>
                            <?php $est = $s['estado_' . $parte]; if ($est === null) continue; $idx = array_search($est, $orden, true); ?>
                            <div class="ss-parte" data-parte="<?= $parte ?>" style="--c: <?= $estadosUi[$est][2] ?>; --p: <?= $colorParte ?>">
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
    const BOTON = <?= json_encode(array_map(static fn ($c) => $c[2], $columnas), JSON_UNESCAPED_UNICODE) ?>;
    const SIGUIENTE = <?= json_encode($siguiente) ?>;
    const buscar = document.getElementById('ssBuscar');
    const nav = document.getElementById('ssPestanas');
    const $$ = (s, el = document) => [...el.querySelectorAll(s)];
    const leer = (k, d) => { try { return localStorage.getItem(k) ?? d; } catch (e) { return d; } };
    const guardar = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };

    let grupo = leer('ss_tab', 'activa');
    let modo = leer('ss_modo', 'sesiones');
    let parteFiltro = leer('ss_parte', '');

    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    function filtrar() {
        const q = norm(buscar.value.trim());
        // Por sesión
        let n = 0;
        $$('.ss-fila', lista).forEach(f => {
            const ok = (!grupo || f.dataset.grupo === grupo) && (!q || norm(f.dataset.buscar).includes(q));
            f.hidden = !ok;
            f.classList.toggle('primera', ok && n === 0);
            if (ok) n++;
        });
        document.getElementById('ssNada').hidden = n > 0;
        $$('button', nav).forEach(b => b.classList.toggle('on', b.dataset.grupo === grupo));
        // Por trabajo
        $$('.ss-col').forEach(col => {
            let v = 0;
            $$('.ss-tarjeta', col).forEach(t => {
                const ok = (!parteFiltro || t.dataset.parte === parteFiltro) && (!q || norm(t.dataset.buscar).includes(q));
                t.hidden = !ok;
                if (ok) v++;
            });
            col.querySelector('[data-n]').textContent = v;
            col.classList.toggle('vacia', v === 0);
        });
        $$('#ssParte button').forEach(b => b.classList.toggle('on', b.dataset.parte === parteFiltro));
        // Modo
        $$('[data-vista]', raiz).forEach(v => { v.hidden = v.dataset.vista !== modo; });
        $$('#ssModo button').forEach(b => b.classList.toggle('on', b.dataset.modo === modo));
    }

    nav.addEventListener('click', ev => {
        const b = ev.target.closest('button'); if (!b) return;
        grupo = b.dataset.grupo; guardar('ss_tab', grupo); filtrar();
    });
    document.getElementById('ssModo').addEventListener('click', ev => {
        const b = ev.target.closest('button'); if (!b) return;
        modo = b.dataset.modo; guardar('ss_modo', modo); filtrar();
    });
    document.getElementById('ssParte').addEventListener('click', ev => {
        const b = ev.target.closest('button'); if (!b) return;
        parteFiltro = b.dataset.parte; guardar('ss_parte', parteFiltro); filtrar();
    });
    buscar.addEventListener('input', filtrar);

    async function cambiarEstado(id, parte, estado) {
        const r = await fetch(raiz.dataset.base + '/' + id + '/estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ parte, estado }),
        }).catch(() => null);
        if (!r || !r.ok) { alert('No se ha podido cambiar la etapa.'); return false; }
        pintarFila(id, parte, estado);
        return true;
    }

    // Refleja un cambio de etapa en la fila de "Por sesión".
    function pintarFila(id, parteNom, estado) {
        const fila = lista.querySelector(`.ss-fila[data-id="${id}"]`);
        const parte = fila?.querySelector(`.ss-parte[data-parte="${parteNom}"]`);
        if (!parte) return;
        const idx = ORDEN.indexOf(estado);
        $$('.ss-pasos button', parte).forEach((b, i) => b.classList.toggle('on', i <= idx));
        parte.style.setProperty('--c', ESTADOS[estado].c);
        parte.querySelector('.lbl').textContent = ESTADOS[estado].t;
        const est = $$('.ss-parte', fila).map(p => ORDEN[$$('.ss-pasos .on', p).length - 1]);
        fila.dataset.grupo = est.every(e => e === 'idea') ? 'idea' : (est.every(e => e === 'completado') ? 'completada' : 'activa');
    }

    // Refleja un cambio de etapa en "Por trabajo": mueve la tarjeta de columna o
    // la quita si la parte sale del trabajo pendiente (idea / completado).
    // Si una parte ENTRA desde idea/completado no hay tarjeta que mover:
    // aparecerá al recargar.
    function pintarTarjeta(id, parte, estado) {
        const t = document.querySelector(`.ss-tarjeta[data-id="${id}"][data-parte="${parte}"]`);
        if (!t) return;
        const col = document.querySelector(`.ss-col[data-col="${estado}"]`);
        if (!col) { t.remove(); return; }
        t.dataset.estado = estado;
        const btn = t.querySelector('.ss-hecho');
        btn.dataset.siguiente = SIGUIENTE[estado];
        btn.querySelector('span').textContent = BOTON[estado];
        const dias = t.querySelector('.ss-dias');
        if (dias) { dias.className = 'ss-dias'; dias.querySelector('b').textContent = 'hoy'; }
        col.querySelector('.ss-tarjetas').prepend(t);
    }

    // Barra de etapas en "Por sesión"
    lista.addEventListener('click', async ev => {
        const seg = ev.target.closest('.ss-pasos button');
        if (!seg) return;
        const fila = seg.closest('.ss-fila');
        const parte = seg.closest('.ss-parte').dataset.parte;
        // Pulsar la etapa en la que ya está no es un cambio (no ensuciar el historial).
        if ($$('.ss-pasos .on', seg.closest('.ss-parte')).length - 1 === ORDEN.indexOf(seg.dataset.estado)) return;
        if (await cambiarEstado(fila.dataset.id, parte, seg.dataset.estado)) {
            pintarTarjeta(fila.dataset.id, parte, seg.dataset.estado);
            filtrar();
        }
    });

    // Botón de avance en "Por trabajo": la parte pasa a la siguiente etapa.
    document.getElementById('ssTrabajo').addEventListener('click', async ev => {
        const b = ev.target.closest('.ss-hecho');
        if (!b) return;
        const t = b.closest('.ss-tarjeta');
        b.disabled = true;
        const nuevo = b.dataset.siguiente;
        if (await cambiarEstado(t.dataset.id, t.dataset.parte, nuevo)) {
            t.classList.add('sale');
            setTimeout(() => { pintarTarjeta(t.dataset.id, t.dataset.parte, nuevo); t.classList.remove('sale'); b.disabled = false; filtrar(); }, 350);
        } else {
            b.disabled = false;
        }
    });

    filtrar();
})();
</script>
<?= $this->endSection() ?>
