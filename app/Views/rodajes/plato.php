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
// Día / noche: dos botones (uno, otro o los dos) si hay tomas con franja.
$hayDN = (bool) array_filter($escenas, static fn ($e) => !empty($e['franja']));
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
        'acciones' => '<a class="fv-btn" href="' . site_url("rodajes/$pid/plan") . '"><i class="bi bi-arrow-left"></i>Plan</a>',
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
            <?php if ($hayDN): ?>
                <div class="pt-seg pl-dn" role="group" aria-label="Día y noche">
                    <button type="button" data-dn="dia" aria-pressed="true"><i class="bi bi-sun"></i>Día</button>
                    <button type="button" data-dn="noche" aria-pressed="true"><i class="bi bi-moon-stars"></i>Noche</button>
                </div>
            <?php endif; ?>
            <div class="pt-aj" id="plAj" hidden>
                <span class="pt-lbl">Vista</span>
                <div class="pt-seg pt-chips" id="plVistas">
                    <button type="button" data-vista="B">Foto al lado</button>
                    <button type="button" data-vista="A">Foto arriba</button>
                </div>
                <span class="pt-lbl">Orden</span>
                <div class="pt-seg pt-chips">
                    <?php foreach (['plan' => 'Rodaje', 'narrativa' => 'Historia'] as $k => $txt): ?>
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
                    // Lo técnico que no tenga la toma lo pone su setup (lente, soporte, luz).
                    $stp   = $setups[(int) ($e['setup_id'] ?? 0)] ?? null;
                    $focal = trim((string) ($e['camara_optica'] ?? '')) ?: trim((string) ($stp['optica'] ?? ''));
                    $sopor = trim((string) ($e['camara_soporte'] ?? '')) ?: trim((string) ($stp['soporte'] ?? ''));
                    $luz   = trim((string) ($e['plano_esquema_iluminacion'] ?? '')) ?: trim((string) ($stp['luz'] ?? ''));
                    $fmt   = $formatos[$e['formato'] ?? ''][0] ?? '';

                    // Visor: fotos de la toma, sus referencias y las fotos de sus variantes.
                    // (El moodboard de la temática no: son fotos de otras tomas y confundían.)
                    $lb = [];
                    foreach ($fotos['t'][$eid] ?? [] as $m) if ($s = $img($m)) $lb[] = ['s' => $s, 'c' => 'Foto de la toma'];
                    foreach ($media[$eid] ?? [] as $r) if (!$r['video']) $lb[] = ['s' => $r['src'], 'c' => 'Referencia'];
                    $fotoPrin = $img($fotos['t'][$eid][0] ?? null);
                    $varFoto = [];
                    foreach ($vars as $va) {
                        $s = $va['moodboard_item_id'] ? $img($fotos['v'][(int) $va['moodboard_item_id']] ?? null) : null;
                        if ($s) { $varFoto[(int) $va['id']] = count($lb); $lb[] = ['s' => $s, 'c' => 'Variante · ' . $va['texto']]; }
                    }
                    if (!$fotoPrin && $lb && !$varFoto) $fotoPrin = $lb[0]['s'];
                    $idxPrin = $fotoPrin ? (int) array_search($fotoPrin, array_column($lb, 's'), true) : -1;

                    // Ficha técnica: siempre a la vista (con «—» si falta) para saber qué hay que rellenar.
                    $tecnica = [
                        'Setup'      => $stp['nombre'] ?? '',
                        'Lente'      => $focal,
                        'Soporte'    => $sopor,
                        'Parámetros' => $params,
                        'Luz'        => $luz,
                    ];
                    $detalle = array_filter([
                        'Plano'               => ($e['camara_tipo_plano'] ?? ''),
                        'Ángulo'              => ($e['camara_angulo'] ?? ''),
                        'Movimiento'          => ($e['camara_movimiento'] ?? ''),
                        'Balance'             => ($e['camara_wb'] ?? ''),
                        'Hora / luz'          => ($e['plano_hora_dia'] ?? ''),
                        'Punto'               => $esPlan ? ($puntos[($e['punto_id'] ?? '')] ?? '') : '',
                        'Ubicación'           => $esPlan ? '' : ($e['escena_ubicacion'] ?? ''),
                        'Acción'              => ($e['escena_accion'] ?? ''),
                        'Toma/s'              => ($e['escena_tomas'] ?? ''),
                        'Objetos'             => ($e['plano_objetos'] ?? ''),
                        'Toma alternativa'    => ($e['plano_toma_alternativa'] ?? ''),
                        'Efecto especial'     => ($e['escena_efecto_especial'] ?? ''),
                        'Continuidad antes'   => ($e['escena_cont_previa'] ?? ''),
                        'Continuidad después' => ($e['escena_cont_posterior'] ?? ''),
                        'Notas'               => ($e['plano_notas'] ?? ''),
                        'Diálogo'             => ($e['sonido_dialogo_escrito'] ?? ''),
                    ], static fn ($v) => trim((string) $v) !== '');
                    $ed = [
                        'titulo' => (string) ($e['escena_descripcion'] ?? ''), 'setup_id' => (int) ($e['setup_id'] ?? 0),
                        'optica' => (string) ($e['camara_optica'] ?? ''), 'soporte' => (string) ($e['camara_soporte'] ?? ''),
                        'apertura' => (string) ($e['camara_apertura'] ?? ''), 'velocidad' => (string) ($e['camara_velocidad'] ?? ''),
                        'iso' => (string) ($e['camara_iso'] ?? ''), 'fps' => (string) ($e['camara_fps'] ?? ''), 'nd' => (string) ($e['camara_nd'] ?? ''),
                        'luz' => (string) ($e['plano_esquema_iluminacion'] ?? ''), 'notas' => (string) ($e['plano_notas'] ?? ''),
                        'props' => $props, 'video' => $esVideo,
                        'variantes' => array_map(static fn ($va) => ['id' => (int) $va['id'], 'texto' => $va['texto'], 'props' => rod_props_extra($e, $va)], $vars),
                    ];
                    ?>
                    <article class="pt-toma" data-dn="<?= rod_dia_noche($e['franja'] ?? null) ?>" data-ed="<?= esc(json_encode($ed, JSON_UNESCAPED_UNICODE), 'attr') ?>" data-id="<?= $eid ?>" data-g="<?= $gi ?>" style="--t:var(--pt-c<?= $ti++ % 4 ?>)" data-fotos="<?= esc(json_encode($lb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'attr') ?>" data-titulo="<?= esc((int) $e['orden'] . '. ' . rod_titulo_escena($e), 'attr') ?>">
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
                                    <span class="pt-tit"><?php if ($hayDN && empty($e['franja'])): ?><span class="pt-sinfranja" title="Sin momento del día: sale en día y en noche">Sin momento</span> <?php endif; ?><?php if ($hayOpc && !$opcE): ?><span class="rf-prio imp">Imp.</span> <?php endif; ?><?= esc(rod_titulo_escena($e)) ?></span>
                                    <span class="pt-res"><span<?= $stp ? '' : ' class="falta"' ?>><i class="bi bi-camera"></i><?= $stp ? esc($stp['nombre']) : 'sin setup' ?></span><span<?= $props ? '' : ' class="falta"' ?>><i class="bi bi-box-seam"></i><?= $props ? esc(implode(', ', $props)) : 'sin props' ?></span></span>
                                    <?php if ($focal !== '' || $fmt !== ''): ?>
                                        <span class="pt-tec"><?php if ($focal !== ''): ?><span class="pt-focal"><?= esc($focal) ?></span><?php endif; ?><?php if ($fmt !== ''): ?><span class="pt-fmt"><?= $fmt ?></span><?php endif; ?></span>
                                    <?php endif; ?>
                                    <?php if ($params !== ''): ?><span class="pt-params"><?= esc($params) ?></span><?php endif; ?>
                                    <?php if ($luz !== ''): ?><span class="pt-luz"><i class="bi bi-lightbulb"></i><span><?= esc($luz) ?></span></span><?php endif; ?>
                                    <?php if ($diff !== ''): ?><span class="rf-diff"><?= $diff ?></span><?php endif; ?>
                                    <span class="pt-mas"><span>Más datos</span><i class="bi bi-chevron-down"></i></span>
                                </button>
                            </div>

                            <div class="pt-det" hidden>
                                <dl class="pt-ficha">
                                    <?php foreach ($tecnica as $k => $v): ?><dt><?= $k ?></dt><dd<?= $v === '' ? ' class="falta"' : '' ?>><?= $v === '' ? '—' : esc($v) ?></dd><?php endforeach; ?>
                                    <dt>Props</dt><dd<?= $props ? '' : ' class="falta"' ?>><?= $props ? esc(implode(', ', $props)) : 'ninguno' ?></dd>
                                </dl>
                                <?php
                                // Solo las fotos que no se ven ya en la fila: fotos extra de la toma y referencias.
                                $extra = array_keys(array_filter($lb, static fn ($f, $i) => $i !== $idxPrin && !in_array($i, $varFoto, true), ARRAY_FILTER_USE_BOTH));
                                ?>
                                <?php if ($extra): ?>
                                    <div class="pt-refs-h">Referencias</div>
                                    <div class="pt-refs">
                                        <?php foreach ($extra as $i): ?>
                                            <button type="button" data-foto="<?= $i ?>" aria-label="<?= esc($lb[$i]['c'], 'attr') ?>" style="background-image:url('<?= esc($lb[$i]['s'], 'attr') ?>')"></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?= rod_campos($detalle, ['Acción', 'Notas', 'Diálogo', 'Objetos', 'Toma alternativa', 'Efecto especial', 'Continuidad antes', 'Continuidad después']) ?>
                                <label class="pt-nota">Nota rápida
                                    <input type="text" class="fv-input pl-nota" maxlength="255" placeholder="ISO subido, repetir con luz…" value="<?= esc($e['toma_nota'] ?? '', 'attr') ?>">
                                </label>
                                <div class="pt-edits">
                                    <button type="button" class="fv-btn fv-btn-sm" data-editar><i class="bi bi-pencil"></i>Editar datos</button>
                                    <a class="fv-link" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>"><i class="bi bi-box-arrow-up-right"></i>Escena completa</a>
                                </div>
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
                                        <?php $vx = rod_props_extra($e, $va); $vp = array_merge($props, $vx); ?>
                                        <span class="pt-res"><span<?= $stp ? '' : ' class="falta"' ?>><i class="bi bi-camera"></i><?= $stp ? esc($stp['nombre']) : 'sin setup' ?></span><span<?= $vp ? '' : ' class="falta"' ?>><i class="bi bi-box-seam"></i><?php if ($vp): ?><?= esc(implode(', ', $props)) ?><?php if ($vx): ?><b class="pt-mas-prop"><?= $props ? ' + ' : '' ?><?= esc(implode(', ', $vx)) ?></b><?php endif; ?><?php else: ?>sin props<?php endif; ?></span></span>
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

    <dialog class="pt-ed" id="plEd" data-url="<?= site_url("rodajes/$pid/escenas") ?>">
        <form method="dialog" class="fv">
            <div class="pt-ed-h"><b id="plEdT">Editar toma</b><button type="button" class="pt-lb-x" data-cerrar aria-label="Cerrar"><i class="bi bi-x-lg"></i></button></div>
            <div class="pt-ed-cuerpo">
                <label class="pt-ed-c pt-ed-ancho"><span>Título</span><input class="fv-input" name="titulo" maxlength="255"></label>
                <label class="pt-ed-c pt-ed-ancho"><span>Setup</span>
                    <select class="fv-input" name="setup_id"><option value="">Sin setup</option><?php foreach ($setups as $st): ?><option value="<?= (int) $st['id'] ?>"><?= esc($st['nombre']) ?></option><?php endforeach; ?></select>
                </label>
                <label class="pt-ed-c"><span>Lente</span><input class="fv-input" name="optica" maxlength="100" placeholder="del setup"></label>
                <label class="pt-ed-c"><span>Soporte</span><input class="fv-input" name="soporte" maxlength="100" placeholder="del setup"></label>
                <div class="pt-ed-params pt-ed-ancho">
                    <label class="pt-ed-c"><span>Diafragma</span><input class="fv-input" name="apertura" maxlength="50" inputmode="decimal" placeholder="2.8"></label>
                    <label class="pt-ed-c"><span>Velocidad</span><input class="fv-input" name="velocidad" maxlength="50" placeholder="1/125"></label>
                    <label class="pt-ed-c"><span>ISO</span><input class="fv-input" name="iso" maxlength="50" inputmode="numeric"></label>
                    <label class="pt-ed-c" data-solo-video><span>FPS</span><input class="fv-input" name="fps" maxlength="50" inputmode="numeric"></label>
                    <label class="pt-ed-c"><span>ND</span><input class="fv-input" name="nd" maxlength="50"></label>
                </div>
                <label class="pt-ed-c pt-ed-ancho"><span>Luz</span><textarea class="fv-input" name="luz" rows="2" placeholder="del setup"></textarea></label>
                <div class="pt-ed-c pt-ed-ancho"><span>Props</span><div class="pt-ed-props" data-props="t"></div></div>
                <div class="pt-ed-vars pt-ed-ancho" id="plEdVars"></div>
                <label class="pt-ed-c pt-ed-ancho"><span>Notas</span><textarea class="fv-input" name="notas" rows="2"></textarea></label>
            </div>
            <div class="pt-ed-pie">
                <button type="button" class="fv-btn" data-cerrar>Cancelar</button>
                <button type="button" class="fv-btn fv-btn-pri" id="plEdOk"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </form>
    </dialog>

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
    // Día / noche: lados visibles (se recuerda). Sin franja = sale siempre.
    let dn = new Set(['dia', 'noche']);
    try { const g = JSON.parse(localStorage.getItem('rodajes_dn') || 'null'); if (Array.isArray(g) && g.length) dn = new Set(g); } catch {}
    const dnOk = t => !t.dataset.dn || dn.has(t.dataset.dn);
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
            if (!dnOk(t)) { t.hidden = true; return; }
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
    const dnBtns = [...raiz.querySelectorAll('.pl-dn [data-dn]')];
    const pintarDN = () => dnBtns.forEach(b => b.setAttribute('aria-pressed', dn.has(b.dataset.dn)));
    pintarDN();
    dnBtns.forEach(b => b.addEventListener('click', () => {
        const k = b.dataset.dn;
        // Con los dos: el que tocas queda solo. Con uno: tocar el otro vuelve a los dos.
        if (dn.size === 2) dn = new Set([k]); else if (!dn.has(k)) dn = new Set(['dia', 'noche']); else return;
        try { localStorage.setItem('rodajes_dn', JSON.stringify([...dn])); } catch {}
        pintarDN(); pintar();
    }));
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

    // ---------- edición rápida (modal) ----------
    const LISTA = <?= json_encode(array_values($propsLista ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const dlg = document.getElementById('plEd'), dlgF = dlg.querySelector('form');
    const norm = t => t.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    let edToma = null;
    const chips = (caja, opciones, activos) => {
        const act = activos.map(norm);
        caja.innerHTML = '';
        [...new Map([...opciones, ...activos].map(p => [norm(p), p])).values()].forEach(p => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'pt-ed-chip' + (act.includes(norm(p)) ? ' on' : ''); b.textContent = p; b.dataset.prop = p;
            caja.append(b);
        });
        const i = document.createElement('input');
        i.className = 'pt-ed-otro'; i.placeholder = '+ otro'; i.autocomplete = 'off'; i.enterKeyHint = 'done';
        caja.append(i);
    };
    const marcados = caja => {
        const l = [...caja.querySelectorAll('.pt-ed-chip.on')].map(b => b.dataset.prop);
        caja.querySelector('.pt-ed-otro').value.split(',').map(x => x.trim()).filter(Boolean).forEach(x => l.push(x));
        return l;
    };
    dlg.addEventListener('click', ev => {
        const c = ev.target.closest('.pt-ed-chip');
        if (c) { c.classList.toggle('on'); return; }
        if (ev.target.closest('[data-cerrar]') || ev.target === dlg) dlg.close();
    });
    dlg.addEventListener('keydown', ev => {
        const i = ev.target.closest('.pt-ed-otro');
        if (!i || ev.key !== 'Enter') return;
        ev.preventDefault();
        const caja = i.parentElement;
        i.value.split(',').map(x => x.trim()).filter(Boolean).forEach(x => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'pt-ed-chip on'; b.textContent = x; b.dataset.prop = x;
            caja.insertBefore(b, i);
        });
        i.value = '';
    });
    raiz.addEventListener('click', ev => {
        const b = ev.target.closest('[data-editar]');
        if (!b) return;
        edToma = b.closest('.pt-toma');
        const d = JSON.parse(edToma.dataset.ed);
        document.getElementById('plEdT').textContent = edToma.dataset.titulo;
        ['titulo', 'optica', 'soporte', 'apertura', 'velocidad', 'iso', 'fps', 'nd', 'luz', 'notas'].forEach(k => dlgF.elements[k].value = d[k] || '');
        dlgF.elements.setup_id.value = d.setup_id || '';
        dlg.querySelector('[data-solo-video]').hidden = !d.video;
        chips(dlg.querySelector('[data-props="t"]'), LISTA, d.props);
        const vars = document.getElementById('plEdVars');
        vars.innerHTML = '';
        d.variantes.forEach(v => {
            const w = document.createElement('div');
            w.className = 'pt-ed-c';
            w.innerHTML = '<span></span><div class="pt-ed-props"></div>';
            w.firstChild.textContent = 'Variante · ' + v.texto + ' — además de la toma';
            w.lastChild.dataset.var = v.id;
            chips(w.lastChild, LISTA, v.props);
            vars.append(w);
        });
        dlg.showModal();
    });
    document.getElementById('plEdOk').addEventListener('click', ev => {
        const btn = ev.currentTarget, f = dlgF.elements;
        const datos = { setup_id: f.setup_id.value, props: marcados(dlg.querySelector('[data-props="t"]')), variantes: {} };
        ['titulo', 'optica', 'soporte', 'apertura', 'velocidad', 'iso', 'fps', 'nd', 'luz', 'notas'].forEach(k => datos[k] = f[k].value);
        dlg.querySelectorAll('[data-var]').forEach(c => datos.variantes[c.dataset.var] = marcados(c));
        btn.disabled = true;
        fetch(dlg.dataset.url + '/' + edToma.dataset.id + '/rapida', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(datos),
        }).then(r => {
            if (!r.ok) throw 0;
            // Recarga (el setup puede cambiar la toma de grupo) volviendo al mismo sitio, con los mismos filtros.
            try { sessionStorage.setItem('platoVolver', JSON.stringify({ id: edToma.dataset.id, top: edToma.getBoundingClientRect().top, filtro, medio, soloImp })); } catch {}
            location.reload();
        }).catch(() => { btn.disabled = false; alert('No se ha podido guardar. ¿Hay conexión?'); });
    });

    pintar();

    // Tras guardar en el modal: mismos filtros, la toma abierta y en el mismo sitio de la pantalla.
    let volver = null;
    try { volver = JSON.parse(sessionStorage.getItem('platoVolver') || 'null'); sessionStorage.removeItem('platoVolver'); } catch {}
    if (volver) {
        raiz.querySelector('.pl-filtros [data-filtro="' + volver.filtro + '"]')?.click();
        if (volver.medio) raiz.querySelector('button[data-medio="' + volver.medio + '"]:not(.pt-btns)')?.click();
        if (volver.soloImp) raiz.querySelector('.pl-imp')?.click();
        const t = raiz.querySelector('.pt-toma[data-id="' + volver.id + '"]');
        if (t) {
            if (t.hidden) { raiz.querySelector('.pl-filtros [data-filtro="todas"]')?.click(); }
            const cab = t.querySelector('[data-abrir]');
            if (cab && cab.getAttribute('aria-expanded') !== 'true') cab.click();
            window.scrollTo(0, t.getBoundingClientRect().top + window.scrollY - volver.top);
        }
    }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
