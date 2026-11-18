<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];

$cuenta = ['pendiente' => 0, 'hecha' => 0, 'repetir' => 0, 'descartada' => 0];
$hayFormato = false;
foreach ($escenas as $e) {
    foreach (rod_estados_partes($e) as $est) $cuenta[$est]++;
    foreach ($e['variantes'] ?? [] as $va) foreach (rod_partes($e) as $col) $cuenta[($va[$col] ?? null) ?: 'pendiente']++;
    if (!empty($e['formato'])) $hayFormato = true;
}
$total  = array_sum($cuenta);
$esPlan = $orden === 'plan';
$hayOpc = (bool) array_filter($escenas, static fn ($e) => ($e['prioridad'] ?? 'imp') === 'opc' || array_filter($e['variantes'] ?? [], static fn ($v) => $v['prioridad'] === 'opc'));
$formatos = rod_formatos();
$pct = static fn (int $n) => $total ? round($n / $total * 100, 2) : 0;

$pre = static function (string $v, string $pref = '', string $suf = ''): string {
    $v = trim($v);
    return $v === '' ? '' : (is_numeric(str_replace(',', '.', $v)) ? $pref . $v . $suf : $v);
};
$img = static fn (?array $m) => $m && $m['origen'] === 'archivo' ? base_url($m['ruta_archivo']) : null;

$sub = [];
if ($sesion) $sub[] = '<a class="rd-ses" href="' . site_url('sesiones/' . $sesion['id']) . '"><i class="bi bi-camera"></i>' . esc($sesion['titulo']) . '</a>';

$btns = static function (string $parte, string $est, bool $varias, bool $opc, ?int $var = null) {
    $ico = ['foto' => 'bi-camera', 'video' => 'bi-camera-video'][$parte] ?? 'bi-circle';
    $b = static fn ($st, $i, $t) => '<button type="button" data-estado="' . $st . '"><i class="bi ' . $i . '"></i><span>' . $t . '</span></button>';
    return '<div class="pt-btns" data-parte="' . $parte . '" data-medio="' . $parte . '" data-actual="' . $est . '" data-opc="' . ($opc ? '1' : '') . '"' . ($var ? ' data-var="' . $var . '"' : '') . '>'
        . ($varias ? '<span class="pt-parte"><i class="bi ' . $ico . '"></i></span>' : '')
        . $b('hecha', 'bi-check-lg', 'Hecha') . $b('repetir', 'bi-arrow-repeat', 'Repetir') . $b('descartada', 'bi-x-lg', 'Descartar')
        . '</div>';
};
?>
<div class="fv rd pt pt-B" id="plato" data-base="<?= site_url("rodajes/$pid/escenas") ?>" data-var="<?= site_url("rodajes/$pid/variantes") ?>">
    <?= view('rodajes/_cabecera', [
        'migas'   => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Plató' => null],
        'titulo'  => esc($proyecto['titulo']),
        'sub'     => implode('', $sub),
        'clasica' => false,
    ]) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-film"></i>
            <div>Este rodaje no tiene escenas todavía.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
        </div>
    <?php else: ?>
    <div class="pt-cuerpo">
        <div class="pt-fija">
            <div class="pt-h1">
                <span class="pt-cont"><b id="plHechas"><?= $cuenta['hecha'] ?></b>/<span id="plUtiles"><?= $total - $cuenta['descartada'] ?></span> hechas</span>
                <div class="rd-barra pt-barra" aria-hidden="true">
                    <i class="hecha" style="width:<?= $pct($cuenta['hecha']) ?>%"></i><i class="repetir" style="width:<?= $pct($cuenta['repetir']) ?>%"></i><i class="descartada" style="width:<?= $pct($cuenta['descartada']) ?>%"></i>
                </div>
                <button type="button" class="pt-ajbtn" id="plAjBtn" aria-expanded="false" aria-label="Orden, filtros y vista"><i class="bi bi-sliders"></i></button>
            </div>
            <div class="pt-seg pl-filtros">
                <button type="button" class="on" data-filtro="pendientes">Por hacer<span data-n="pendientes"><?= $cuenta['pendiente'] + $cuenta['repetir'] ?></span></button>
                <button type="button" data-filtro="repetir">Repetir<span data-n="repetir"><?= $cuenta['repetir'] ?></span></button>
                <button type="button" data-filtro="hecha">Hechas<span data-n="hecha"><?= $cuenta['hecha'] ?></span></button>
                <button type="button" data-filtro="todas">Todas<span data-n="todas"><?= $total ?></span></button>
            </div>
            <div class="pt-aj" id="plAj" hidden>
                <span class="pt-lbl">Vista</span>
                <div class="pt-seg pt-chips" id="plVistas">
                    <button type="button" data-vista="B">Foto al lado</button>
                    <button type="button" data-vista="A">Foto arriba</button>
                </div>
                <span class="pt-lbl">Orden</span>
                <div class="pt-seg pt-chips">
                    <?php foreach (['plan' => 'Plan', 'narrativa' => 'Historia', 'ubicacion' => 'Ubicación', 'montaje' => 'Montaje'] as $k => $txt): ?>
                        <a href="<?= site_url("rodajes/$pid/plato?orden=$k") ?>" aria-pressed="<?= $orden === $k ? 'true' : 'false' ?>"><?= $txt ?></a>
                    <?php endforeach; ?>
                </div>
                <?php if ($hayFormato || $hayOpc): ?>
                    <span class="pt-lbl">Mostrar</span>
                    <div class="pt-seg pt-chips">
                        <?php if ($hayFormato): ?>
                            <button type="button" data-medio="" aria-pressed="true">Foto y vídeo</button>
                            <button type="button" data-medio="foto" aria-pressed="false"><i class="bi bi-camera"></i> Foto</button>
                            <button type="button" data-medio="video" aria-pressed="false"><i class="bi bi-camera-video"></i> Vídeo</button>
                        <?php endif; ?>
                        <?php if ($hayOpc): ?><button type="button" class="pl-imp" aria-pressed="false"><i class="bi bi-star"></i> Solo imprescindibles</button><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (count($grupos) > 1): ?>
                    <span class="pt-lbl">Ir a</span>
                    <nav class="pt-indice" id="plIndice">
                        <?php foreach ($grupos as $gi => $g): ?>
                            <a href="#pl-g<?= $gi ?>" data-ir="<?= $gi ?>"><span><?= esc($esPlan ? ($g['franja'] . ' · ' . ($g['nombre'] ?: 'Sin zona')) : ($g['nombre'] !== '' ? $g['nombre'] : 'Sin bloque')) ?></span><small data-pend="<?= $gi ?>"></small></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                <form method="post" action="<?= site_url("rodajes/$pid/plato/reiniciar") ?>"
                      onsubmit="return confirm('¿Volver a dejar todas las tomas como pendientes? Las notas se conservan.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="pt-reini"><i class="bi bi-arrow-counterclockwise"></i>Reiniciar plató</button>
                </form>
            </div>
        </div>

        <div class="pt-lista">
            <?php $ti = 0; ?>
            <?php foreach ($grupos as $gi => $g): ?>
                <div class="pt-grupo" id="pl-g<?= $gi ?>" data-bloque>
                    <b><?= esc($esPlan ? ($g['franja'] . ' · ' . ($g['n'] ? $g['n'] . '. ' : '') . ($g['nombre'] ?: 'Sin zona')) : ($g['nombre'] !== '' ? $g['nombre'] : 'Sin bloque')) ?></b>
                    <?php if ($esPlan): ?>
                        <?php $tx = $g['setup'] ? rod_setup_linea($g['setup']) : ''; ?>
                        <span><?= $g['setup'] ? 'Monta ' . esc($g['setup']['nombre']) . ($tx !== '' ? ' · ' . $tx : '') : 'Sin setup' ?></span>
                    <?php endif; ?>
                </div>
                <?php $propsAnt = null; ?>
                <?php foreach ($g['escenas'] as $e): ?>
                    <?php
                    $eid    = (int) $e['id'];
                    $partes = rod_estados_partes($e);
                    $cols   = rod_partes($e);
                    $varias = count($partes) > 1;
                    $opcE   = ($e['prioridad'] ?? 'imp') === 'opc';
                    $vars   = $e['variantes'] ?? [];
                    $props  = rod_props($e);
                    $diff   = $esPlan ? rod_props_diff($propsAnt, $props) : '';
                    $propsAnt = $props;
                    $esVideo = in_array($e['formato'] ?? '', ['video', 'ambas'], true);
                    $params = implode(' · ', array_filter([
                        $pre((string) ($e['camara_apertura'] ?? ''), 'f/'),
                        $pre((string) ($e['camara_velocidad'] ?? '')),
                        $pre((string) ($e['camara_iso'] ?? ''), 'ISO '),
                        $esVideo ? $pre((string) ($e['camara_fps'] ?? ''), '', ' fps') : '',
                        $pre((string) ($e['camara_nd'] ?? ''), 'ND '),
                    ]));
                    $focal = trim((string) ($e['camara_optica'] ?? ''));
                    $luz   = trim((string) ($e['plano_esquema_iluminacion'] ?? '')) ?: ($esPlan && $g['setup'] ? trim((string) $g['setup']['luz']) : '');
                    $fmt   = $formatos[$e['formato'] ?? ''][0] ?? '';

                    // Visor: principal, referencias, moodboard y fotos de las variantes.
                    $lb = [];
                    foreach ($fotos['t'][$eid] ?? [] as $m) if ($s = $img($m)) $lb[] = ['s' => $s, 'c' => 'Foto de la toma'];
                    foreach ($media[$eid] ?? [] as $r) if (!$r['video']) $lb[] = ['s' => $r['src'], 'c' => 'Referencia'];
                    foreach (($e['situacion_id'] ? ($moodboard[$e['situacion_id']] ?? []) : []) as $m) if ($s = $img($m)) $lb[] = ['s' => $s, 'c' => 'Moodboard'];
                    $fotoPrin = $img($fotos['t'][$eid][0] ?? null);
                    $varFoto = [];
                    foreach ($vars as $va) {
                        $s = $va['moodboard_item_id'] ? $img($fotos['v'][(int) $va['moodboard_item_id']] ?? null) : null;
                        if ($s) { $varFoto[(int) $va['id']] = count($lb); $lb[] = ['s' => $s, 'c' => 'Variante · ' . $va['texto']]; }
                    }
                    if (!$fotoPrin && $lb && !$varFoto) $fotoPrin = $lb[0]['s'];
                    $idxPrin = $fotoPrin ? (int) array_search($fotoPrin, array_column($lb, 's'), true) : -1;

                    $detalle = array_filter([
                        'Plano'               => ($e['camara_tipo_plano'] ?? ''),
                        'Ángulo'              => ($e['camara_angulo'] ?? ''),
                        'Movimiento'          => ($e['camara_movimiento'] ?? ''),
                        'Soporte'             => ($e['camara_soporte'] ?? ''),
                        'Balance'             => ($e['camara_wb'] ?? ''),
                        'Hora / luz'          => ($e['plano_hora_dia'] ?? ''),
                        'Punto'               => $esPlan ? ($puntos[($e['punto_id'] ?? '')] ?? '') : '',
                        'Ubicación'           => $esPlan ? '' : ($e['escena_ubicacion'] ?? ''),
                        'Acción'              => ($e['escena_accion'] ?? ''),
                        'Toma/s'              => ($e['escena_tomas'] ?? ''),
                        'Objetos'             => ($e['plano_objetos'] ?? ''),
                        'Iluminación'         => ($e['plano_esquema_iluminacion'] ?? ''),
                        'Toma alternativa'    => ($e['plano_toma_alternativa'] ?? ''),
                        'Efecto especial'     => ($e['escena_efecto_especial'] ?? ''),
                        'Continuidad antes'   => ($e['escena_cont_previa'] ?? ''),
                        'Continuidad después' => ($e['escena_cont_posterior'] ?? ''),
                        'Notas'               => ($e['plano_notas'] ?? ''),
                        'Diálogo'             => ($e['sonido_dialogo_escrito'] ?? ''),
                    ], static fn ($v) => trim((string) $v) !== '');
                    ?>
                    <article class="pt-toma" data-id="<?= $eid ?>" data-g="<?= $gi ?>" style="--t:var(--pt-c<?= $ti++ % 4 ?>)" data-fotos="<?= esc(json_encode($lb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'attr') ?>" data-titulo="<?= esc((int) $e['orden'] . '. ' . rod_titulo_escena($e), 'attr') ?>">
                        <div class="pt-fila" data-fila>
                            <div class="pt-par">
                                <?php if ($fotoPrin): ?>
                                    <button type="button" class="pt-foto" data-foto="<?= $idxPrin ?>" aria-label="Ver la foto en grande" style="background-image:url('<?= esc($fotoPrin, 'attr') ?>')">
                                        <span class="pt-num"><?= (int) $e['orden'] ?></span><span class="pt-pill"></span>
                                    </button>
                                <?php else: ?>
                                    <div class="pt-hueco"><b><?= (int) $e['orden'] ?></b>sin foto<span class="pt-pill"></span></div>
                                <?php endif; ?>
                                <button type="button" class="pt-datos" data-abrir aria-expanded="false">
                                    <span class="pt-tit"><?php if ($hayOpc && !$opcE): ?><span class="rf-prio imp">Imp.</span> <?php endif; ?><?= esc(rod_titulo_escena($e)) ?></span>
                                    <?php if ($focal !== '' || $fmt !== ''): ?>
                                        <span class="pt-tec"><?php if ($focal !== ''): ?><span class="pt-focal"><?= esc($focal) ?></span><?php endif; ?><?php if ($fmt !== ''): ?><span class="pt-fmt"><?= $fmt ?></span><?php endif; ?></span>
                                    <?php endif; ?>
                                    <?php if ($params !== ''): ?><span class="pt-params"><?= esc($params) ?></span><?php endif; ?>
                                    <?php if ($luz !== ''): ?><span class="pt-luz"><i class="bi bi-lightbulb"></i><span><?= esc($luz) ?></span></span><?php endif; ?>
                                    <?php if ($props): ?><span class="pt-props"><i class="bi bi-box-seam"></i><?php foreach ($props as $p): ?><span><?= esc($p) ?></span><?php endforeach; ?></span><?php endif; ?>
                                    <?php if ($diff !== ''): ?><span class="rf-diff"><?= $diff ?></span><?php endif; ?>
                                    <span class="pt-mas"><span>Más datos</span><i class="bi bi-chevron-down"></i></span>
                                </button>
                            </div>

                            <div class="pt-det" hidden>
                                <?php if (count($lb) > 1): ?>
                                    <div class="pt-refs">
                                        <?php foreach ($lb as $i => $f): ?>
                                            <button type="button" data-foto="<?= $i ?>" aria-label="<?= esc($f['c'], 'attr') ?>" style="background-image:url('<?= esc($f['s'], 'attr') ?>')"></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?= rod_campos($detalle, ['Acción', 'Iluminación', 'Notas', 'Diálogo', 'Objetos', 'Toma alternativa', 'Efecto especial', 'Continuidad antes', 'Continuidad después']) ?>
                                <label class="pt-nota">Nota rápida
                                    <input type="text" class="fv-input pl-nota" maxlength="255" placeholder="ISO subido, repetir con luz…" value="<?= esc($e['toma_nota'] ?? '', 'attr') ?>">
                                </label>
                                <a class="fv-link" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>"><i class="bi bi-pencil-square"></i>Editar escena completa</a>
                            </div>
                            <div class="pl-nota-vista"<?= trim((string) $e['toma_nota']) === '' ? ' hidden' : '' ?>><i class="bi bi-pencil"></i> <span><?= esc($e['toma_nota'] ?? '') ?></span></div>

                            <?php foreach ($partes as $parte => $est): ?>
                                <?= $btns((string) $parte, $est, $varias, $opcE) ?>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($vars as $va): ?>
                            <?php $vid = (int) $va['id']; $vopc = $opcE || $va['prioridad'] === 'opc'; ?>
                            <div class="pt-fila pt-var" data-fila data-opc="<?= $vopc ? '1' : '' ?>">
                                <div class="pt-par">
                                    <?php if (isset($varFoto[$vid])): ?>
                                        <button type="button" class="pt-foto" data-foto="<?= $varFoto[$vid] ?>" aria-label="Ver la foto de la variante" style="background-image:url('<?= esc($lb[$varFoto[$vid]]['s'], 'attr') ?>')">
                                            <span class="pt-num"><?= (int) $e['orden'] ?>·</span><span class="pt-pill"></span>
                                        </button>
                                    <?php else: ?>
                                        <div class="pt-hueco"><b><?= (int) $e['orden'] ?>·</b>sin foto<span class="pt-pill"></span></div>
                                    <?php endif; ?>
                                    <div class="pt-datos">
                                        <span class="pt-badge"><i class="pt-rombo"></i>Variante<?php if ($hayOpc && $va['prioridad'] === 'imp'): ?> <span class="rf-prio imp">Imp.</span><?php endif; ?></span>
                                        <span class="pt-tit"><?= esc($va['texto']) ?></span>
                                        <?php if ($focal !== ''): ?><span class="pt-tec"><span class="pt-focal"><?= esc($focal) ?></span></span><?php endif; ?>
                                    </div>
                                </div>
                                <?php foreach ($cols as $parte => $col): ?>
                                    <?= $btns((string) $parte, ($va[$col] ?? null) ?: 'pendiente', $varias, $vopc, $vid) ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="pt-fin" hidden>
                <i class="bi bi-check2-all"></i>
                <div>Nada por aquí. <span data-fin-txt></span></div>
            </div>
        </div>
    </div>

    <div class="pt-lb" id="plLb" hidden>
        <div class="pt-lb-top"><b id="plLbT"></b><span id="plLbN"></span>
            <button type="button" class="pt-lb-x" id="plLbX" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button></div>
        <div class="pt-lb-img" id="plLbImg">
            <img id="plLbI" alt="">
            <button type="button" class="pt-lb-nav p" id="plLbP" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
            <button type="button" class="pt-lb-nav n" id="plLbS" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="pt-lb-cap" id="plLbC"></div>
    </div>
    <?php endif; ?>
</div>

<?php if ($escenas): ?>
<script>
(() => {
    const raiz = document.getElementById('plato');
    const base = raiz.dataset.base;
    const varBase = raiz.dataset.var;
    const csrf = '<?= csrf_hash() ?>';
    const TXT = { pendiente: 'Pendiente', hecha: 'Hecha', repetir: 'Repetir', descartada: 'Descartada' };
    let filtro = 'pendientes', medio = '', soloImp = false;
    const keep = new Set();

    const ls = { get: k => { try { return localStorage.getItem(k); } catch { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch {} } };
    const ponerVista = v => {
        raiz.classList.toggle('pt-A', v === 'A'); raiz.classList.toggle('pt-B', v !== 'A');
        raiz.querySelectorAll('#plVistas [data-vista]').forEach(b => b.setAttribute('aria-pressed', b.dataset.vista === (v === 'A' ? 'A' : 'B')));
    };
    ponerVista(ls.get('rodajes_plato_vista'));
    raiz.querySelector('#plVistas').addEventListener('click', ev => {
        const b = ev.target.closest('[data-vista]'); if (!b) return;
        ls.set('rodajes_plato_vista', b.dataset.vista); ponerVista(b.dataset.vista);
    });

    const combinar = ests => {
        if (!ests.length) return 'pendiente';
        if (new Set(ests).size === 1) return ests[0];
        if (ests.includes('repetir')) return 'repetir';
        if (ests.every(e => e === 'hecha' || e === 'descartada')) return 'hecha';
        return 'pendiente';
    };
    const casa = g => (!medio || g.dataset.medio === medio || g.dataset.medio === '') && (!soloImp || g.dataset.opc !== '1');
    const visible = ests => ests.length > 0 && ({
        pendientes: ests.some(e => e === 'pendiente' || e === 'repetir'),
        repetir: ests.includes('repetir'),
        hecha: ests.every(e => e === 'hecha'),
        todas: true,
    })[filtro];

    function pintar() {
        const tomas = [...raiz.querySelectorAll('.pt-toma')];
        const n = { pendiente: 0, hecha: 0, repetir: 0, descartada: 0 };
        const pend = {};
        tomas.forEach(t => {
            const grupos = [...t.querySelectorAll('.pt-btns')];
            grupos.forEach(g => g.hidden = !casa(g));
            t.querySelectorAll('[data-fila]').forEach(f => {
                const gs = [...f.querySelectorAll('.pt-btns')].filter(casa);
                f.hidden = !gs.length;
                const e = combinar(gs.map(g => g.dataset.actual));
                f.classList.toggle('hecha', gs.length > 0 && e === 'hecha');
                const p = f.querySelector('.pt-pill');
                if (p) { p.textContent = TXT[e]; p.className = 'pt-pill ' + e; }
            });
            const vis = grupos.filter(casa), ests = vis.map(g => g.dataset.actual);
            ests.forEach(e => n[e]++);
            t.classList.toggle('hecha', ests.length > 0 && combinar(ests) === 'hecha');
            t.hidden = !(visible(ests) || vis.some(g => keep.has(g)));
            pend[t.dataset.g] ??= [0, 0];
            if (ests.length) { pend[t.dataset.g][1]++; if (ests.some(e => e === 'pendiente' || e === 'repetir')) pend[t.dataset.g][0]++; }
        });
        raiz.querySelectorAll('[data-bloque]').forEach(b => {
            let sig = b.nextElementSibling, alguna = false;
            while (sig && sig.classList.contains('pt-toma')) { if (!sig.hidden) { alguna = true; break; } sig = sig.nextElementSibling; }
            b.hidden = !alguna;
        });
        const vistos = raiz.querySelectorAll('[data-bloque]:not([hidden])');
        raiz.querySelectorAll('[data-bloque]').forEach(b => b.classList.toggle('primero', b === vistos[0]));
        const total = n.pendiente + n.hecha + n.repetir + n.descartada;
        raiz.querySelector('[data-n="todas"]').textContent = total;
        document.getElementById('plHechas').textContent = n.hecha;
        document.getElementById('plUtiles').textContent = total - n.descartada;
        raiz.querySelector('[data-n="pendientes"]').textContent = n.pendiente + n.repetir;
        raiz.querySelector('[data-n="repetir"]').textContent = n.repetir;
        raiz.querySelector('[data-n="hecha"]').textContent = n.hecha;
        ['hecha', 'repetir', 'descartada'].forEach(k => {
            raiz.querySelector('.rd-barra .' + k).style.width = (total ? n[k] / total * 100 : 0) + '%';
        });
        raiz.querySelectorAll('[data-pend]').forEach(s => {
            const [p, t] = pend[s.dataset.pend] || [0, 0];
            s.textContent = !t ? '—' : (p ? p + ' por hacer' : '✓');
        });
        const fin = raiz.querySelector('.pt-fin');
        fin.hidden = tomas.some(t => !t.hidden);
        fin.querySelector('[data-fin-txt]').textContent = filtro === 'pendientes' ? '¡Todas las tomas están hechas!' : '';
    }

    function enviar(toma, datos, grupo) {
        const url = grupo && grupo.dataset.var ? varBase + '/' + grupo.dataset.var + '/toma' : base + '/' + toma.dataset.id + '/toma';
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(datos),
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });
    }

    raiz.querySelectorAll('.pl-filtros [data-filtro]').forEach(b => b.addEventListener('click', () => {
        raiz.querySelectorAll('.pl-filtros [data-filtro]').forEach(x => x.classList.toggle('on', x === b));
        filtro = b.dataset.filtro;
        pintar();
    }));
    const ajBtn = document.getElementById('plAjBtn'), aj = document.getElementById('plAj');
    ajBtn.addEventListener('click', () => { aj.hidden = !aj.hidden; ajBtn.setAttribute('aria-expanded', !aj.hidden); });
    document.getElementById('plIndice')?.addEventListener('click', ev => {
        const a = ev.target.closest('a[data-ir]'); if (!a) return;
        ev.preventDefault();
        aj.hidden = true; ajBtn.setAttribute('aria-expanded', 'false');
        const cab = document.getElementById('pl-g' + a.dataset.ir);
        if (cab.hidden) raiz.querySelector('.pl-filtros [data-filtro="todas"]').click();
        const fija = raiz.querySelector('.pt-fija');
        window.scrollTo({ top: cab.getBoundingClientRect().top + window.scrollY - fija.offsetHeight - 8, behavior: 'smooth' });
    });
    raiz.querySelector('.pl-imp')?.addEventListener('click', ev => {
        soloImp = !soloImp; ev.currentTarget.setAttribute('aria-pressed', soloImp); pintar();
    });
    raiz.querySelectorAll('[data-medio]:not(.pt-btns)').forEach(b => b.addEventListener('click', () => {
        raiz.querySelectorAll('button[data-medio]').forEach(x => x.setAttribute('aria-pressed', x === b));
        medio = b.dataset.medio; pintar();
    }));

    raiz.addEventListener('click', ev => {
        const f = ev.target.closest('[data-foto]');
        if (f) { abrirFoto(f.closest('.pt-toma'), +f.dataset.foto); return; }
        const cab = ev.target.closest('[data-abrir]');
        if (cab) {
            const fila = cab.closest('[data-fila]'), det = fila.querySelector('.pt-det');
            det.hidden = !det.hidden;
            cab.setAttribute('aria-expanded', !det.hidden);
            cab.querySelector('.pt-mas span').textContent = det.hidden ? 'Más datos' : 'Menos';
            return;
        }
        const btn = ev.target.closest('.pt-btns button');
        if (!btn) return;
        const toma = btn.closest('.pt-toma'), grupo = btn.closest('.pt-btns');
        const anterior = grupo.dataset.actual;
        const nuevo = anterior === btn.dataset.estado ? 'pendiente' : btn.dataset.estado;
        grupo.dataset.actual = nuevo;
        keep.add(grupo);
        pintar();
        if (navigator.vibrate) navigator.vibrate(15);
        enviar(toma, { estado: nuevo === 'pendiente' ? '' : nuevo, parte: grupo.dataset.parte }, grupo)
            .catch(() => { grupo.dataset.actual = anterior; alert('No se ha podido guardar. ¿Hay conexión?'); })
            .finally(() => setTimeout(() => { keep.delete(grupo); pintar(); }, 600));
    });

    raiz.querySelectorAll('.pl-nota').forEach(inp => {
        let guardado = inp.value;
        const guardar = () => {
            if (inp.value === guardado) return;
            const toma = inp.closest('.pt-toma'), valor = inp.value;
            enviar(toma, { nota: valor }).then(() => {
                guardado = valor;
                const vista = toma.querySelector('.pl-nota-vista');
                vista.querySelector('span').textContent = valor.trim();
                vista.hidden = valor.trim() === '';
            }).catch(() => inp.classList.add('is-invalid'));
        };
        inp.addEventListener('blur', guardar);
        inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
    });

    const lb = document.getElementById('plLb'), lbI = document.getElementById('plLbI'), lbImg = document.getElementById('plLbImg');
    let lbL = [], lbN = 0;
    function abrirFoto(toma, i) {
        lbL = JSON.parse(toma.dataset.fotos || '[]'); if (!lbL.length) return;
        lbN = Math.max(0, i); document.getElementById('plLbT').textContent = toma.dataset.titulo;
        lb.hidden = false; document.body.style.overflow = 'hidden'; verFoto();
    }
    function verFoto() {
        const f = lbL[lbN];
        lbI.src = f.s; document.getElementById('plLbC').textContent = f.c;
        document.getElementById('plLbN').textContent = lbL.length > 1 ? (lbN + 1) + '/' + lbL.length : '';
        document.getElementById('plLbP').hidden = document.getElementById('plLbS').hidden = lbL.length < 2;
    }
    const cerrar = () => { lb.hidden = true; document.body.style.overflow = ''; };
    const mover = d => { lbN = (lbN + d + lbL.length) % lbL.length; verFoto(); };
    document.getElementById('plLbX').onclick = cerrar;
    document.getElementById('plLbP').onclick = () => mover(-1);
    document.getElementById('plLbS').onclick = () => mover(1);
    lbImg.addEventListener('click', ev => { if (ev.target === lbImg) cerrar(); });
    let x0 = null;
    lbImg.addEventListener('pointerdown', ev => x0 = ev.clientX);
    lbImg.addEventListener('pointerup', ev => { if (x0 !== null && Math.abs(ev.clientX - x0) > 50) mover(ev.clientX < x0 ? 1 : -1); x0 = null; });
    document.addEventListener('keydown', ev => {
        if (lb.hidden) return;
        if (ev.key === 'Escape') cerrar();
        if (ev.key === 'ArrowLeft') mover(-1);
        if (ev.key === 'ArrowRight') mover(1);
    });

    pintar();
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
