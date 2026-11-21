<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];

$cuenta = rod_cuenta_tomas($escenas);
$hayFormato = (bool) array_filter($escenas, static fn ($e) => !empty($e['formato']));
$total  = $cuenta['total'];
$esPlan = $orden === 'plan';
$hayOpc = (bool) array_filter($escenas, static fn ($e) => ($e['prioridad'] ?? 'imp') === 'opc' || array_filter($e['variantes'] ?? [], static fn ($v) => $v['prioridad'] === 'opc'));
$formatos = rod_formatos();
// Día / noche: dos botones (uno, otro o los dos) si hay tomas con franja.
$hayDN = (bool) array_filter($escenas, static fn ($e) => !empty($e['franja']));
$pct = static fn (int $n) => $total ? round($n / $total * 100, 2) : 0;

$img = static fn (?array $m) => $m && $m['origen'] === 'archivo' ? base_url($m['ruta_archivo']) : null;

// Ficha de una fila (toma o variante): foto con los setups debajo y, a la derecha, props, focales y luz.
$ficha = static function (array $f): string {
    $ico = ['foto' => 'bi-camera', 'video' => 'bi-camera-video'];
    $num = $f['num'] . (!empty($f['var']) ? '·' : '');
    ob_start(); ?>
    <div class="pt-par">
        <div class="pt-izq">
            <?php if ($f['foto']): ?>
                <button type="button" class="pt-foto" data-foto="<?= $f['foto'][0] ?>" aria-label="Ver la foto en grande" style="background-image:url('<?= esc($f['foto'][1], 'attr') ?>')">
                    <span class="pt-num"><?= $num ?></span><span class="pt-pill"></span>
                </button>
            <?php else: ?>
                <div class="pt-hueco"><b><?= $num ?></b>sin foto<span class="pt-pill"></span></div>
            <?php endif; ?>
        </div>
        <div class="pt-datos">
            <?php if ($f['pre'] !== '' && !$f['mas']): ?><?= $f['pre'] ?><?php endif; ?>
            <span class="pt-tit"><?= $f['mas'] ? $f['pre'] : '' ?><?= $f['titulo'] ?></span>
            <span class="pt-props-g<?= $f['props'] || $f['extra'] ? '' : ' falta' ?>"><i class="bi bi-box-seam"></i><span><?php if ($f['props'] || $f['extra']): ?><?= esc(implode(', ', $f['props'])) ?><?php if ($f['extra']): ?><b class="pt-mas-prop"><?= $f['props'] ? ' + ' : '' ?><?= esc(implode(', ', $f['extra'])) ?></b><?php endif; ?><?php else: ?>sin props<?php endif; ?></span></span>
            <?php $luces = []; ?>
            <?php foreach ($f['cajas'] as $parte => $c): ?>
                <?php $l = $c['tec'][$parte === 'video' ? 'Foco de luz' : 'Flash / luz'] ?? ''; if ($l !== '') $luces[$l][] = $parte; ?>
                <span class="pt-focal-g<?= isset($c['tec']['Lente']) ? '' : ' falta' ?>"><i class="bi <?= $ico[$parte] ?>"></i><?= esc($c['tec']['Lente'] ?? 'sin lente') ?></span>
            <?php endforeach; ?>
            <?php foreach ($luces as $l => $ps): ?>
                <span class="pt-luz-g"><i class="bi <?= in_array('foto', $ps, true) && count($ps) === 1 ? 'bi-lightning-charge' : 'bi-lightbulb' ?>"></i><span><?= esc($l) ?></span></span>
            <?php endforeach; ?>
            <?php if ($f['diff'] !== ''): ?><span class="rf-diff"><?= $f['diff'] ?></span><?php endif; ?>
        </div>
    </div>
        <div class="pt-stps">
            <?php foreach ($f['cajas'] as $parte => $c): ?>
                <button type="button" class="pt-stp <?= $parte ?><?= $c['st'] ? '' : ' falta' ?>" data-stp="<?= $f['clave'] . '-' . $parte ?>" aria-expanded="false">
                    <i class="bi <?= $ico[$parte] ?>"></i>
                    <b><?= $c['st'] ? esc($c['st']['nombre']) : 'sin setup' ?></b>
                    <i class="bi bi-chevron-down pt-stp-ch"></i>
                </button>
            <?php endforeach; ?>
            <?php if ($f['mas']): ?><button type="button" class="pt-info" data-abrir aria-expanded="false"><span>+ info</span></button><?php endif; ?>
        </div>
    <?php foreach ($f['cajas'] as $parte => $c): ?>
        <div class="pt-stp-det <?= $parte ?>" data-stp-det="<?= $f['clave'] . '-' . $parte ?>" hidden>
            <div class="pt-stp-h"><i class="bi <?= $ico[$parte] ?>"></i><?= $parte === 'video' ? 'Vídeo' : 'Foto' ?><?= $c['st'] ? ' · ' . esc($c['st']['nombre']) : '' ?></div>
            <?php if ($c['tec']): ?>
                <dl class="pt-ficha"><?php foreach ($c['tec'] as $k => $v): ?><dt><?= $k ?></dt><dd><?= esc($v) ?></dd><?php endforeach; ?></dl>
            <?php else: ?><small class="t3">Sin parámetros: complétalos en Setups.</small><?php endif; ?>
            <?php if ($c['st'] && trim((string) $c['st']['notas']) !== ''): ?><p class="pt-stp-n"><?= nl2br(esc($c['st']['notas'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php return ob_get_clean();
};


// Mosaico: cuadrado de foto, solo la imagen; imprescindible = marco rojo.
$cuadro = static function (?array $foto, bool $imp): string {
    $cls = 'pm-foto' . ($imp ? ' pm-imp' : '');
    $tit = $imp ? ' title="Imprescindible"' : '';
    if ($foto) {
        return '<button type="button" class="' . $cls . '"' . $tit . ' data-foto="' . $foto[0] . '" aria-label="Ver la foto en grande" style="background-image:url(\'' . esc($foto[1], 'attr') . '\')"></button>';
    }
    return '<div class="' . $cls . ' pm-hueco"' . $tit . '><span>sin foto</span></div>';
};
// Mosaico: un botón «Hecha» por parte (foto / vídeo); reflejan y mueven los .pt-btns de la fila.
$hechos = static function (array $partes, bool $corto): string {
    $ico = ['foto' => 'bi-camera', 'video' => 'bi-camera-video'];
    $h = '<div class="pm-hechos">';
    foreach (array_keys($partes) as $p) {
        $p = (string) $p;
        $nom = ['foto' => 'foto', 'video' => 'vídeo'][$p] ?? '';
        $h .= '<button type="button" data-mh="' . $p . '" aria-pressed="false" title="Hecha' . ($nom ? " ($nom)" : '') . '">'
            . '<i class="bi ' . ($ico[$p] ?? 'bi-check-circle') . '"></i>' . ($corto ? '' : '<span>Hecha</span>') . '<i class="bi bi-check-lg pm-ok"></i></button>';
    }
    return $h . '</div>';
};

$btns = static function (string $parte, string $est, bool $varias, bool $opc, ?int $var = null) {
    $ico = ['foto' => 'bi-camera', 'video' => 'bi-camera-video'][$parte] ?? 'bi-circle';
    $b = static fn ($st, $i, $t) => '<button type="button" data-estado="' . $st . '"><i class="bi ' . $i . '"></i><span>' . $t . '</span></button>';
    return '<div class="pt-btns" data-parte="' . $parte . '" data-medio="' . $parte . '" data-actual="' . $est . '" data-opc="' . ($opc ? '1' : '') . '"' . ($var ? ' data-var="' . $var . '"' : '') . '>'
        . ($varias ? '<span class="pt-parte"><i class="bi ' . $ico . '"></i></span>' : '')
        . $b('hecha', 'bi-check-lg', 'Hecha') . $b('repetir', 'bi-arrow-repeat', 'Repetir') . $b('descartada', 'bi-x-lg', 'Descartar')
        . '</div>';
};
?>
<div class="fv rd pt pt-B pt-M" id="plato" data-base="<?= site_url("rodajes/$pid/escenas") ?>" data-var="<?= site_url("rodajes/$pid/variantes") ?>">
    <?= view('rodajes/_cabecera', [
        'migas'   => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Plató' => null],
        'titulo'  => null,
        'iconos'  => ($sesion ? '<a class="fv-ibtn" href="' . site_url('sesiones/' . $sesion['id']) . '" title="Sesión: ' . esc($sesion['titulo'], 'attr') . '"><i class="bi bi-camera"></i></a>' : '')
            . '<a class="fv-gbtn" href="' . site_url("rodajes/$pid/plan") . '"><i class="bi bi-arrow-left"></i>Plan</a>',
        'clasica' => false,
    ]) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-film"></i>
            <div>Este rodaje no tiene tomas todavía.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
        </div>
    <?php else: ?>
    <div class="pt-cuerpo">
        <div class="pt-fija">
            <div class="pt-h1">
                <div class="pt-filtros" role="group" aria-label="Filtrar tomas">
                    <button type="button" aria-pressed="false" data-filtro="pendientes" title="Por hacer" aria-label="Por hacer"><i class="bi bi-circle"></i><span data-n="pendientes"><?= $cuenta['pendiente'] + $cuenta['repetir'] ?></span></button>
                    <button type="button" aria-pressed="false" data-filtro="repetir" title="Repetir" aria-label="Repetir"><i class="bi bi-arrow-repeat"></i><span data-n="repetir"><?= $cuenta['repetir'] ?></span></button>
                    <button type="button" aria-pressed="false" data-filtro="hecha" title="Hechas" aria-label="Hechas"><i class="bi bi-check-lg"></i><span data-n="hecha"><?= $cuenta['hecha'] ?></span></button>
                    <button type="button" class="on" aria-pressed="true" data-filtro="todas" title="Todas" aria-label="Todas"><i class="bi bi-list-ul"></i><span data-n="todas"><?= $total ?></span></button>
                </div>
                <div class="pt-der">
                    <button type="button" class="pt-ib" id="plAjBtn" aria-expanded="false" title="Orden, vista y más" aria-label="Orden, vista y más"><i class="bi bi-sliders"></i></button>
                </div>
            </div>
            <div class="pt-prog">
                <?php if ($hayDN): ?>
                    <span class="pl-dn" role="group" aria-label="Día y noche">
                        <button type="button" class="pt-ib" data-dn="dia" aria-pressed="true" title="Día" aria-label="Día"><i class="bi bi-sun"></i></button>
                        <button type="button" class="pt-ib" data-dn="noche" aria-pressed="true" title="Noche" aria-label="Noche"><i class="bi bi-moon-stars"></i></button>
                    </span>
                <?php endif; ?>
                <span class="rd-barra pt-barra" aria-hidden="true"><i class="hecha" style="width:<?= $pct($cuenta['hecha']) ?>%"></i><i class="repetir" style="width:<?= $pct($cuenta['repetir']) ?>%"></i><i class="descartada" style="width:<?= $pct($cuenta['descartada']) ?>%"></i></span>
                <span class="pt-cont" title="Hechas"><b id="plHechas"><?= $cuenta['hecha'] ?></b>/<span id="plUtiles"><?= $total - $cuenta['descartada'] ?></span></span>
                <span class="pt-modo" id="plModo" role="group" aria-label="Lista o mosaico">
                    <button type="button" class="pt-ib" data-modo="lista" aria-pressed="false" title="Lista con datos" aria-label="Lista con datos"><i class="bi bi-view-list"></i></button>
                    <button type="button" class="pt-ib" data-modo="mosaico" aria-pressed="true" title="Solo fotos" aria-label="Solo fotos"><i class="bi bi-grid-3x3-gap"></i></button>
                </span>
            </div>
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
            <?php $ti = 0; $sepVisto = []; ?>
            <?php foreach ($grupos as $gi => $g): ?>
                <?php // Galería (orden de rodaje): un sol grande antes de lo de día y una luna antes de lo de noche.
                $gdn = $esPlan ? rod_dia_noche($g['escenas'][0]['franja'] ?? null) : ''; ?>
                <?php if ($gdn !== '' && !isset($sepVisto[$gdn])): $sepVisto[$gdn] = true; ?>
                    <div class="pm-sep <?= $gdn ?>" data-sep="<?= $gdn ?>" aria-hidden="true"><i class="bi <?= $gdn === 'noche' ? 'bi-moon-stars-fill' : 'bi-sun-fill' ?>"></i><span><?= $gdn === 'noche' ? 'Noche' : 'Día' ?></span></div>
                <?php endif; ?>
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
                    $stp    = $setups[(int) ($e['setup_id'] ?? 0)] ?? null;

                    // Una caja por setup que usa la toma (foto / vídeo), con sus parámetros.
                    $cajas = rod_cajas_toma($e, $setups);

                    $lb = [];
                    foreach ($fotos['t'][$eid] ?? [] as $m) if ($s = $img($m)) $lb[] = ['s' => $s, 'c' => 'Foto de la toma'];
                    foreach ($media[$eid] ?? [] as $r) if (!$r['video']) $lb[] = ['s' => $r['src'], 'c' => 'Referencia'];
                    $fotoPrin = $img($fotos['t'][$eid][0] ?? null);
                    $varFoto = [];
                    foreach ($vars as $va) {
                        $s = $va['moodboard_item_id'] ? $img($fotos['v'][(int) $va['moodboard_item_id']] ?? null) : null;
                        if ($s) { $varFoto[(int) $va['id']] = count($lb); $lb[] = ['s' => $s, 'c' => 'Variante · ' . $va['texto'], 'v' => $va['texto']]; }
                    }
                    if (!$fotoPrin && $lb && !$varFoto) $fotoPrin = $lb[0]['s'];
                    $idxPrin = $fotoPrin ? (int) array_search($fotoPrin, array_column($lb, 's'), true) : -1;

                    // Datos de la escena que no salen en la tarjeta (foto, lente, luz, props y setups ya están fuera).
                    $detalle = array_filter([
                        'Momento'             => \App\Models\RodajesEscenaModel::FRANJAS[$e['franja'] ?? ''] ?? '',
                        'Bloque'              => $esPlan ? ($e['escena_bloque'] ?? '') : '',
                        'Objetivo'            => ($e['escena_objetivo'] ?? ''),
                        'Cámara'              => ($e['camara_modelo'] ?? ''),
                        'Plano'               => ($e['camara_tipo_plano'] ?? ''),
                        'Ángulo'              => ($e['camara_angulo'] ?? ''),
                        'Movimiento'          => ($e['camara_movimiento'] ?? ''),
                        'Balance'             => ($e['camara_wb'] ?? ''),
                        'Hora / luz'          => ($e['plano_hora_dia'] ?? ''),
                        'Punto'               => $esPlan ? ($puntos[($e['punto_id'] ?? '')] ?? '') : '',
                        'Ubicación'           => $esPlan ? '' : ($e['escena_ubicacion'] ?? ''),
                        'Acción'              => ($e['escena_accion'] ?? ''),
                        'Toma/s'              => ($e['escena_tomas'] ?? ''),
                        'Actores'             => ($e['plano_actores'] ?? ''),
                        'Objetos'             => ($e['plano_objetos'] ?? ''),
                        'Ref. del lugar'      => ($e['plano_ref_lugar_texto'] ?? ''),
                        'Inspiración'         => ($e['plano_ref_inspiracion_texto'] ?? ''),
                        'Toma alternativa'    => ($e['plano_toma_alternativa'] ?? ''),
                        'Efecto especial'     => ($e['escena_efecto_especial'] ?? ''),
                        'Continuidad antes'   => ($e['escena_cont_previa'] ?? ''),
                        'Continuidad después' => ($e['escena_cont_posterior'] ?? ''),
                        'Notas'               => ($e['plano_notas'] ?? ''),
                        'Diálogo'             => ($e['sonido_dialogo_escrito'] ?? ''),
                        'Sonido ambiente'     => ($e['sonido_ambiente'] ?? ''),
                        'Antiviento'          => ($e['sonido_antiviento'] ?? ''),
                    ], static fn ($v) => trim((string) $v) !== '');
                    $ed = [
                        'titulo' => (string) ($e['escena_descripcion'] ?? ''), 'setup_id' => (int) ($e['setup_id'] ?? 0),
                        'setup_video_id' => (int) ($e['setup_video_id'] ?? 0), 'formato' => (string) ($e['formato'] ?? ''),
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
                            <div class="pm-titulo"><?= esc(rod_titulo_escena($e)) ?></div>
                            <div class="pm pm-t">
                                <div class="pm-izq">
                                    <?= $cuadro($fotoPrin ? [$idxPrin, $fotoPrin] : null, $hayOpc && !$opcE) ?>
                                    <?= $hechos($partes, false) ?>
                                </div>
                                <div class="pm-datos">
                                    <?php foreach ($cajas as $parte => $c): ?>
                                        <?php $lz = $c['tec'][$parte === 'video' ? 'Foco de luz' : 'Flash / luz'] ?? ''; ?>
                                        <div class="pm-parte <?= $parte ?>">
                                            <span class="pm-focal<?= isset($c['tec']['Lente']) ? '' : ' falta' ?>"><i class="bi <?= $parte === 'video' ? 'bi-camera-video' : 'bi-camera' ?>"></i><?= esc($c['tec']['Lente'] ?? 'sin lente') ?></span>
                                            <span class="pm-luz<?= $lz === '' ? ' falta' : '' ?>"><i class="bi <?= $parte === 'video' ? 'bi-lightbulb' : 'bi-lightning-charge' ?>"></i><span><?= $lz !== '' ? esc($lz) : 'sin luz' ?></span></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="pm-props<?= $props ? '' : ' falta' ?>"><i class="bi bi-box-seam"></i><span><?= $props ? esc(implode(', ', $props)) : 'sin props' ?></span></div>
                                    <?php if ($diff !== ''): ?><span class="rf-diff pm-diff"><?= $diff ?></span><?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="pm-info" data-abrir-m aria-haspopup="dialog"><i class="bi bi-info-circle"></i><span>Más info</span></button>
                            <?= $ficha([
                                'num' => (int) $e['orden'], 'foto' => $fotoPrin ? [$idxPrin, $fotoPrin] : null, 'cajas' => $cajas,
                                'titulo' => esc(rod_titulo_escena($e)), 'props' => $props, 'extra' => [], 'diff' => $diff,
                                'pre' => ($hayDN && empty($e['franja']) ? '<span class="pt-sinfranja" title="Sin momento del día: sale en día y en noche">Sin momento</span> ' : '') . ($hayOpc && !$opcE ? '<span class="rf-prio imp">Imp.</span> ' : ''),
                                'mas' => true, 'clave' => "t$eid",
                            ]) ?>

                            <div class="pt-det" hidden>
                                <?php
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
                                <?= rod_campos($detalle, ['Objetivo', 'Acción', 'Notas', 'Diálogo', 'Actores', 'Objetos', 'Ref. del lugar', 'Inspiración', 'Toma alternativa', 'Efecto especial', 'Continuidad antes', 'Continuidad después', 'Sonido ambiente']) ?>
                                <?php if (!$detalle && !$extra): ?><small class="t3 pt-sin-datos">La toma no tiene más datos.</small><?php endif; ?>
                                <label class="pt-nota">Nota rápida
                                    <input type="text" class="fv-input pl-nota" maxlength="255" placeholder="ISO subido, repetir con luz…" value="<?= esc($e['toma_nota'] ?? '', 'attr') ?>">
                                </label>
                                <div class="pt-edits">
                                    <button type="button" class="fv-btn fv-btn-sm" data-editar><i class="bi bi-pencil"></i>Editar datos</button>
                                    <a class="fv-link" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>"><i class="bi bi-box-arrow-up-right"></i>Toma completa</a>
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
                                <?php $extraV = rod_props_extra($e, $va); ?>
                                <div class="pm pm-v">
                                    <?= $cuadro(isset($varFoto[$vid]) ? [$varFoto[$vid], $lb[$varFoto[$vid]]['s']] : null, $hayOpc && !$vopc) ?>
                                    <?= $hechos(array_fill_keys(array_keys($cols), 1), true) ?>
                                    <div class="pm-pie"><span><?= esc($va['texto']) ?></span><?php if ($extraV): ?><b>+ <?= esc(implode(', ', $extraV)) ?></b><?php endif; ?></div>
                                </div>
                                <?= $ficha([
                                    'num' => (int) $e['orden'], 'var' => true, 'foto' => isset($varFoto[$vid]) ? [$varFoto[$vid], $lb[$varFoto[$vid]]['s']] : null, 'cajas' => $cajas,
                                    'titulo' => esc($va['texto']), 'props' => $props, 'extra' => rod_props_extra($e, $va), 'diff' => '',
                                    'pre' => '<span class="pt-badge"><i class="pt-rombo"></i>Variante' . ($hayOpc && $va['prioridad'] === 'imp' ? ' <span class="rf-prio imp">Imp.</span>' : '') . '</span>',
                                    'mas' => false, 'clave' => "v$vid",
                                ]) ?>
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
                <?php $opts = implode('', array_map(static fn ($st) => '<option value="' . (int) $st['id'] . '" data-tipo="' . esc((string) $st['tipo'], 'attr') . '">' . esc($st['nombre']) . '</option>', $setups)); ?>
                <label class="pt-ed-c" data-ed-setup><span id="plEdSetupT">Setup de foto</span>
                    <select class="fv-input" name="setup_id"><option value="">Sin setup</option><?= $opts ?></select>
                </label>
                <label class="pt-ed-c" data-ed-setup-video><span>Setup de vídeo</span>
                    <select class="fv-input" name="setup_video_id"><option value="">Sin setup</option><?= $opts ?></select>
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

    <dialog class="pt-ed pt-inf" id="plInfo" aria-labelledby="plInfoT">
        <div class="fv">
            <div class="pt-ed-h"><b id="plInfoT"></b><button type="button" class="pt-lb-x" data-cerrar aria-label="Cerrar"><i class="bi bi-x-lg"></i></button></div>
            <div class="pt-ed-cuerpo pt-inf-cuerpo" id="plInfoC"></div>
        </div>
    </dialog>

    <div class="pt-lb" id="plLb" hidden>
        <div class="pt-lb-top"><div class="pt-lb-tit"><b id="plLbT"></b><small id="plLbV" hidden></small></div><span id="plLbN"></span>
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
    let filtro = 'todas', medio = '', soloImp = false;
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
    // Lista con datos / mosaico (solo fotos en cuadrado: toma arriba, variantes debajo).
    const ponerModo = m => {
        raiz.classList.toggle('pt-M', m === 'mosaico');
        raiz.querySelectorAll('#plModo [data-modo]').forEach(b => b.setAttribute('aria-pressed', b.dataset.modo === (m === 'mosaico' ? 'mosaico' : 'lista')));
    };
    // Por defecto mosaico (rejilla); la lista solo si se eligió antes.
    ponerModo(ls.get('rodajes_plato_modo') === 'lista' ? 'lista' : 'mosaico');
    raiz.querySelector('#plModo').addEventListener('click', ev => {
        const b = ev.target.closest('[data-modo]'); if (!b) return;
        ls.set('rodajes_plato_modo', b.dataset.modo); ponerModo(b.dataset.modo);
    });
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
                f.querySelectorAll('.pt-pill').forEach(p => { p.textContent = TXT[e]; p.className = 'pt-pill ' + e; });
                // Botones «Hecha» del mosaico: reflejan su grupo de la fila.
                f.querySelectorAll('[data-mh]').forEach(b => {
                    const g = f.querySelector('.pt-btns[data-parte="' + b.dataset.mh + '"]');
                    b.hidden = !g || g.hidden;
                    b.setAttribute('aria-pressed', !!g && g.dataset.actual === 'hecha');
                    b.dataset.est = g ? g.dataset.actual : '';
                });
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
        raiz.querySelectorAll('[data-sep]').forEach(s => {
            let sig = s.nextElementSibling, alguna = false;
            while (sig && !sig.matches('[data-sep], .pt-fin')) { if (sig.matches('[data-bloque]') && !sig.hidden) { alguna = true; break; } sig = sig.nextElementSibling; }
            s.hidden = !alguna;
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

    raiz.querySelectorAll('.pt-filtros [data-filtro]').forEach(b => b.addEventListener('click', () => {
        raiz.querySelectorAll('.pt-filtros [data-filtro]').forEach(x => { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', x === b); });
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
        if (cab.hidden) raiz.querySelector('.pt-filtros [data-filtro="todas"]').click();
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
        if (ev.target.closest('#plInfo')) return;
        const f = ev.target.closest('[data-foto]');
        if (f) { abrirFoto(f.closest('.pt-toma'), +f.dataset.foto); return; }
        const sb = ev.target.closest('[data-stp]');
        if (sb) {
            const fila = sb.closest('[data-fila]'), det = fila.querySelector('[data-stp-det="' + sb.dataset.stp + '"]');
            det.hidden = !det.hidden;
            sb.setAttribute('aria-expanded', !det.hidden);
            return;
        }
        const mh = ev.target.closest('[data-mh]');
        if (mh) {
            mh.closest('[data-fila]').querySelector('.pt-btns[data-parte="' + mh.dataset.mh + '"] [data-estado="hecha"]')?.click();
            return;
        }
        const cabM = ev.target.closest('[data-abrir-m]');
        if (cabM) { abrirInfo(cabM.closest('.pt-toma')); return; }
        const cab = ev.target.closest('[data-abrir]');
        if (cab) {
            const fila = cab.closest('[data-fila]'), det = fila.querySelector('.pt-det');
            det.hidden = !det.hidden;
            cab.setAttribute('aria-expanded', !det.hidden);
            cab.querySelector('span').textContent = det.hidden ? '+ info' : '− info';
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
            const toma = inp.closest('.pt-toma') || infoToma, valor = inp.value;
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

    // ---------- «Más info» de la galería: modal con todo lo de la toma ----------
    // Solo lo que NO está ya en la tarjeta (foto, lente, luz, props, setups y estado van fuera):
    // referencias extra, el resto de campos de la escena, nota y edición. .pt-det se mueve dentro y vuelve al cerrar.
    const info = document.getElementById('plInfo'), infoC = document.getElementById('plInfoC');
    let infoToma = null, infoDet = null, infoHueco = null, infoDetOculto = true, volverInfo = null;
    function limpiarInfo() {
        if (infoDet && infoHueco) { infoHueco.replaceWith(infoDet); infoDet.hidden = infoDetOculto; }
        infoDet = infoHueco = null; infoC.innerHTML = '';
    }
    function abrirInfo(toma) {
        if (!toma) return;
        if (info.open) info.close();
        limpiarInfo();
        infoToma = toma;
        document.getElementById('plInfoT').textContent = toma.dataset.titulo;
        infoDet = toma.querySelector('.pt-fila:not(.pt-var) .pt-det');
        if (infoDet) {
            infoHueco = document.createComment('pt-det'); infoDet.before(infoHueco);
            infoDetOculto = infoDet.hidden; infoDet.hidden = false;
            infoC.append(infoDet);
        }
        info.showModal();
        infoC.scrollTop = 0;
    }
    info.addEventListener('close', limpiarInfo);
    info.addEventListener('click', ev => {
        if (ev.target === info || ev.target.closest('[data-cerrar]')) { info.close(); return; }
        const f = ev.target.closest('[data-foto]');
        // La foto en grande va por debajo del modal: se cierra y vuelve a abrirse al salir de la foto.
        if (f) { const t = infoToma; volverInfo = t; info.close(); abrirFoto(t, +f.dataset.foto); }
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
        const lbV = document.getElementById('plLbV');
        lbV.textContent = f.v ? 'Variante · ' + f.v : ''; lbV.hidden = !f.v;
        document.getElementById('plLbN').textContent = lbL.length > 1 ? (lbN + 1) + '/' + lbL.length : '';
        document.getElementById('plLbP').hidden = document.getElementById('plLbS').hidden = lbL.length < 2;
    }
    const cerrar = () => {
        lb.hidden = true; document.body.style.overflow = '';
        if (volverInfo) { const t = volverInfo; volverInfo = null; abrirInfo(t); }
    };
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
    let edToma = null, edAmbas = false;
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
        edToma = b.closest('.pt-toma') || infoToma;
        const d = JSON.parse(edToma.dataset.ed);
        document.getElementById('plEdT').textContent = edToma.dataset.titulo;
        ['titulo', 'optica', 'soporte', 'apertura', 'velocidad', 'iso', 'fps', 'nd', 'luz', 'notas'].forEach(k => dlgF.elements[k].value = d[k] || '');
        const ambas = d.formato === 'ambas', tipoP = d.formato === 'video' ? 'video' : 'foto';
        const filtrar = (sel, tipo) => [...sel.options].forEach(o => { o.hidden = !!o.dataset.tipo && o.dataset.tipo !== tipo && !o.selected; });
        dlgF.elements.setup_id.value = d.setup_id || '';
        dlgF.elements.setup_video_id.value = d.setup_video_id || '';
        filtrar(dlgF.elements.setup_id, tipoP); filtrar(dlgF.elements.setup_video_id, 'video');
        document.getElementById('plEdSetupT').textContent = d.formato === 'video' ? 'Setup de vídeo' : (d.formato ? 'Setup de foto' : 'Setup');
        dlg.querySelector('[data-ed-setup]').classList.toggle('pt-ed-ancho', !ambas);
        dlg.querySelector('[data-ed-setup-video]').hidden = !ambas;
        edAmbas = ambas;
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
        if (edAmbas) datos.setup_video_id = f.setup_video_id.value;
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
        raiz.querySelector('.pt-filtros [data-filtro="' + volver.filtro + '"]')?.click();
        if (volver.medio) raiz.querySelector('button[data-medio="' + volver.medio + '"]:not(.pt-btns)')?.click();
        if (volver.soloImp) raiz.querySelector('.pl-imp')?.click();
        const t = raiz.querySelector('.pt-toma[data-id="' + volver.id + '"]');
        if (t) {
            if (t.hidden) { raiz.querySelector('.pt-filtros [data-filtro="todas"]')?.click(); }
            const cab = t.querySelector(raiz.classList.contains('pt-M') ? '[data-abrir-m]' : '[data-abrir]');
            if (cab && cab.getAttribute('aria-expanded') !== 'true') cab.click();
            window.scrollTo(0, t.getBoundingClientRect().top + window.scrollY - volver.top);
        }
    }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
