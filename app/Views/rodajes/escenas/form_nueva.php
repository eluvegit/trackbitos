<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * @var array      $proyecto
 * @var array|null $escena
 * @var array      $bloques         nombres de bloque ya usados
 * @var int        $ordenSiguiente  (solo al crear)
 * @var array      $imagenes_lugar  (solo al editar)
 * @var array      $imagenes_insp   (solo al editar)
 * @var int|null   $prevId, $nextId (solo al editar)
 */
$e      = $escena ?? [];
$editar = (bool) $escena;
$pid    = (int) $proyecto['id'];
$eid    = (int) ($e['id'] ?? 0);
$action = $editar ? site_url("rodajes/$pid/escenas/update/$eid") : site_url("rodajes/$pid/escenas/store");

$v = static function (string $campo, $defecto = '') use ($e) {
    return old($campo, $e[$campo] ?? $defecto);
};
$attr = static fn (string $campo, $defecto = '') => esc((string) $v($campo, $defecto), 'attr');
$lleno = static function (array $campos) use ($e): int {
    $n = 0;
    foreach ($campos as $c) if (trim((string) ($e[$c] ?? '')) !== '') $n++;
    return $n;
};
$input = static function (string $campo, string $label, string $ph = '', string $defecto = '') use ($attr): string {
    return '<div class="rd-campo"><label for="f_' . $campo . '">' . $label . '</label>'
        . '<input class="fv-input" id="f_' . $campo . '" name="' . $campo . '" value="' . $attr($campo, $defecto) . '"' . ($ph !== '' ? ' placeholder="' . esc($ph, 'attr') . '"' : '') . '></div>';
};
$area = static function (string $campo, string $label, int $filas = 2, string $ph = '') use ($v): string {
    return '<div class="rd-campo"><label for="f_' . $campo . '">' . $label . '</label>'
        . '<textarea class="fv-input" id="f_' . $campo . '" name="' . $campo . '" rows="' . $filas . '"' . ($ph !== '' ? ' placeholder="' . esc($ph, 'attr') . '"' : '') . '>' . esc((string) $v($campo)) . '</textarea></div>';
};
$select = static function (string $campo, string $label, array $opciones) use ($v): string {
    $actual = (string) $v($campo);
    if ($actual !== '' && !in_array($actual, $opciones, true)) $opciones[] = $actual;
    $html = '<div class="rd-campo"><label for="f_' . $campo . '">' . $label . '</label><select class="form-select rd-sel" id="f_' . $campo . '" name="' . $campo . '"><option value="">—</option>';
    foreach ($opciones as $o) $html .= '<option' . ($actual === $o ? ' selected' : '') . '>' . esc($o) . '</option>';
    return $html . '</select></div>';
};
$check = static function (string $campo, string $label) use ($e): string {
    $marcado = old($campo) !== null ? old($campo) === 'S' : (($e[$campo] ?? 'N') === 'S');
    return '<label><input type="checkbox" name="' . $campo . '" value="S"' . ($marcado ? ' checked' : '') . '>' . $label . '</label>';
};
$sec = static function (string $icono, string $titulo, int $n): string {
    return '<summary><i class="bi ' . $icono . '"></i>' . $titulo . ($n ? ' <span class="cuenta">' . $n . '</span>' : '') . '<i class="bi bi-chevron-down"></i></summary>';
};
$miniaturas = static function (array $imgs) use ($pid, $eid): string {
    if (!$imgs) return '';
    $html = '<div class="rd-galeria">';
    foreach ($imgs as $img) {
        $src = esc(base_url($img['ruta']), 'attr');
        $html .= '<div class="fv-thumb">'
            . (rod_es_video($img['ruta']) ? '<video src="' . $src . '#t=0.1" muted playsinline preload="metadata"></video><i class="bi bi-play-fill"></i>' : '<img src="' . $src . '" alt="" loading="lazy">')
            . '<a class="fv-x" href="' . site_url("rodajes/$pid/escenas/$eid/imagen/delete/" . $img['id']) . '" title="Quitar" onclick="return confirm(\'¿Eliminar este archivo?\')"><i class="bi bi-x"></i></a></div>';
    }
    return $html . '</div>';
};

$camposEscena = ['escena_tomas', 'escena_objetivo', 'escena_accion', 'plano_objetos', 'escena_efecto_especial', 'escena_cont_previa', 'escena_cont_posterior', 'plano_hora_dia', 'escena_bloque'];
$camposCamara = ['camara_optica', 'camara_apertura', 'camara_fps', 'camara_velocidad', 'camara_iso', 'camara_wb', 'camara_nd', 'camara_tipo_plano', 'camara_angulo', 'camara_movimiento', 'camara_soporte'];
$camposPlano  = ['plano_esquema_iluminacion', 'plano_toma_alternativa'];
$nRefs = count($imagenes_lugar ?? []) + count($imagenes_insp ?? []) + $lleno(['plano_ref_lugar_texto', 'plano_ref_inspiracion_texto']);
$nSonido = $lleno(['sonido_dialogo_escrito']) + (($e['sonido_ambiente'] ?? 'N') === 'S') + (($e['sonido_antiviento'] ?? 'N') === 'S');
$hayErrores = (bool) session('errors');

$titulo = $editar ? 'Editar toma #' . (int) $e['orden'] : 'Nueva toma';
ob_start(); ?>
<?php if ($editar): ?>
    <?php if (!empty($prevId)): ?><a class="fv-ibtn" href="<?= site_url("rodajes/$pid/escenas/edit/$prevId") ?>" title="Editar la anterior"><i class="bi bi-chevron-left"></i></a><?php endif; ?>
    <?php if (!empty($nextId)): ?><a class="fv-ibtn" href="<?= site_url("rodajes/$pid/escenas/edit/$nextId") ?>" title="Editar la siguiente"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
    <span class="fv-vsep"></span>
<?php endif; ?>
<?php $iconos = ob_get_clean(); ?>

<div class="fv rd estrecho">
    <?= view('rodajes/_cabecera', [
        'migas'  => $editar
            ? [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Escena #' . (int) $e['orden'] => site_url("rodajes/$pid/escenas/show/$eid"), 'Editar' => null]
            : [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Nueva toma' => null],
        'titulo' => $titulo,
        'sub'    => $editar ? '' : '<span>Con el título basta. El resto, cuando quieras.</span>',
        'iconos' => $iconos,
    ]) ?>

    <form class="rd-form" method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <input type="hidden" name="flujo" value="1">
        <?php if (!empty($idea)): ?><input type="hidden" name="idea_id" value="<?= (int) $idea['id'] ?>"><?php endif; ?>
        <?php if (!empty($volver)): ?><input type="hidden" name="volver" value="<?= esc($volver, 'attr') ?>"><?php endif; ?>
        <?php if (!empty($refsIdeas)): ?>
            <div class="rf-refs">
                <?php foreach ($refsIdeas as $r): ?><?= view('rodajes/flujo/_idea', ['i' => $r], ['saveData' => false]) ?><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="fv-panel">
            <div class="rd-campo">
                <label for="f_escena_descripcion">Título</label>
                <input class="fv-input rd-grande" id="f_escena_descripcion" name="escena_descripcion" value="<?= $attr('escena_descripcion', !empty($idea) ? rod_texto_idea($idea) : '') ?>"
                       placeholder="Describe lo que se ve, como lo buscaría un cliente" <?= $editar ? '' : 'autofocus' ?>>
            </div>
            <?php $formato = (string) old('formato', $e['formato'] ?? (service('request')->getGet('formato') ?? '')); ?>
            <?php $prio = (string) old('prioridad', $e['prioridad'] ?? 'imp'); ?>
            <div class="rd-filas-f dos">
                <div class="rd-campo">
                    <span class="rd-lbl">Formato</span>
                    <div class="rd-formatos desel">
                        <input type="hidden" name="formato" value="">
                        <?php foreach (rod_formatos() as $k => [$txt, $ico]): ?>
                            <label><input type="radio" name="formato" value="<?= $k ?>" <?= $formato === $k ? 'checked' : '' ?>><span><i class="bi <?= $ico ?>"></i><?= $txt ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="rd-campo">
                    <span class="rd-lbl">Prioridad</span>
                    <div class="rd-formatos">
                        <?php foreach (\App\Models\RodajesEscenaModel::PRIORIDADES as $k => $txt): ?>
                            <label><input type="radio" name="prioridad" value="<?= $k ?>" <?= $prio === $k ? 'checked' : '' ?>><span><?= $txt ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php $zonaNombre = (string) old('zona_nombre', $zonaInicial ?? ''); $puntoAct = (int) old('punto_id', $e['punto_id'] ?? 0); ?>
            <div class="rd-filas-f dos">
                <?php
                // Desplegable de zonas; «+ Nueva zona…» abre el campo de nombre (como el setup).
                // El servidor sigue recibiendo zona_nombre y crea la zona si no existe.
                $zonaExiste = false;
                foreach ($zonas ?? [] as $z) if (rod_normalizar($z['nombre']) === rod_normalizar($zonaNombre)) { $zonaNombre = $z['nombre']; $zonaExiste = true; }
                $zonaSel = $zonaNombre === '' ? '' : ($zonaExiste ? $zonaNombre : 'nuevo');
                ?>
                <div class="rd-campo">
                    <label for="f_zona_sel">Zona <a class="fv-link rd-lbl-acc" href="<?= site_url('rodajes/' . (int) $proyecto['id'] . '/ubicaciones') ?>"><i class="bi bi-geo-alt"></i>Ubicaciones</a></label>
                    <select class="form-select rd-sel" id="f_zona_sel">
                        <option value="">Sin zona</option>
                        <?php foreach ($zonas ?? [] as $z): ?><option value="<?= esc($z['nombre'], 'attr') ?>" <?= $zonaSel === $z['nombre'] ? 'selected' : '' ?>><?= esc($z['nombre']) ?></option><?php endforeach; ?>
                        <option value="nuevo" <?= $zonaSel === 'nuevo' ? 'selected' : '' ?>>+ Nueva zona…</option>
                    </select>
                    <div id="rfZonaNueva" hidden>
                        <input class="fv-input" id="f_zona" name="zona_nombre" value="<?= esc($zonaNombre, 'attr') ?>" placeholder="Nombre de la zona nueva" autocomplete="off" aria-label="Nombre de la zona nueva">
                    </div>
                    <small class="rd-ayuda" id="rfZonaAyuda" data-nueva="Se creará la zona «%s» al guardar">Una zona amplia. El punto exacto lo marcas en Ubicaciones.</small>
                    <?php if (!empty($zonas)): ?>
                        <div class="rf-destinos rd-pick" id="rfZonaPick">
                            <?php foreach ($zonas as $z): ?>
                                <button type="button" class="rf-destino" data-valor="<?= esc($z['nombre'], 'attr') ?>">
                                    <span class="img"><?php if ($z['imagen']): ?><img src="<?= base_url($z['imagen']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-geo-alt"></i><?php endif; ?></span>
                                    <span class="t"><?= esc($z['nombre']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="rd-campo" id="rfPuntoCampo">
                    <label for="f_punto">Punto <small class="t3">(opcional)</small></label>
                    <select class="form-select rd-sel" id="f_punto" name="punto_id">
                        <option value="">Sin punto</option>
                        <?php foreach ($zonas ?? [] as $z): ?>
                            <?php foreach (array_filter($puntos ?? [], static fn ($p) => (int) $p['ubicacion_id'] === (int) $z['id']) as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" data-zona="<?= esc(rod_normalizar($z['nombre']), 'attr') ?>" <?= $puntoAct === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['nombre']) ?></option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php $franja = (string) old('franja', $e['franja'] ?? ''); ?>
            <div class="rd-campo">
                <span class="rd-lbl">Franja <small>(tócala otra vez para quitarla)</small></span>
                <div class="rd-formatos desel">
                    <input type="hidden" name="franja" value="">
                    <?php foreach (\App\Models\RodajesEscenaModel::FRANJAS as $k => $txt): ?>
                        <label><input type="radio" name="franja" value="<?= $k ?>" <?= $franja === $k ? 'checked' : '' ?>><span><?= $txt ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php $setupAct = (string) old('setup_id', (string) ($e['setup_id'] ?? '')); ?>
            <div class="rd-filas-f dos">
                <div class="rd-campo">
                    <label for="f_setup">Setup</label>
                    <select class="form-select rd-sel" id="f_setup" name="setup_id">
                        <option value="">Sin decidir</option>
                        <?php foreach ($setups ?? [] as $st): ?>
                            <option value="<?= (int) $st['id'] ?>" data-linea="<?= rod_setup_linea($st) ?>" data-optica="<?= esc((string) $st['optica'], 'attr') ?>" data-soporte="<?= esc((string) $st['soporte'], 'attr') ?>" <?= $setupAct === (string) $st['id'] ? 'selected' : '' ?>><?= esc($st['nombre']) ?></option>
                        <?php endforeach; ?>
                        <option value="nuevo" <?= $setupAct === 'nuevo' ? 'selected' : '' ?>>+ Nuevo setup…</option>
                    </select>
                </div>
                <div class="rd-campo" id="rfSetupNuevo" hidden>
                    <label for="f_setup_nuevo">Nombre del setup</label>
                    <input class="fv-input" id="f_setup_nuevo" name="setup_nuevo" value="<?= esc((string) old('setup_nuevo'), 'attr') ?>" placeholder="p. ej. 35mm mano" autocomplete="off">
                    <small class="rd-ayuda">Lente, soporte y fps los completas en Setups.</small>
                </div>
                <div class="rd-campo" id="rfSetupLinea" hidden><span class="rd-lbl">Monta</span><span class="t2"></span></div>
            </div>
            <?php if (!empty($setups)): ?>
                <div class="rf-destinos rd-pick" id="rfSetupPick">
                    <?php foreach ($setups as $st): ?>
                        <button type="button" class="rf-destino" data-valor="<?= (int) $st['id'] ?>">
                            <span class="img"><?php if (!empty($st['imagen'])): ?><img src="<?= base_url($st['imagen']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-camera"></i><?php endif; ?></span>
                            <span class="t"><?= esc($st['nombre']) ?></span>
                            <?php if ($l = rod_setup_linea($st)): ?><small><?= $l ?></small><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            $propsAct = old('props') ?? rod_props($e);
            $propsTodos = $propsLista ?? [];
            foreach ((array) $propsAct as $pr) if (!in_array(rod_normalizar($pr), array_map('rod_normalizar', $propsTodos), true)) $propsTodos[] = $pr;
            $propsAct = array_map('rod_normalizar', (array) $propsAct);
            ?>
            <div class="rd-campo">
                <span class="rd-lbl">Props de esta toma</span>
                <div class="rf-chips">
                    <?php foreach ($propsTodos as $pr): ?>
                        <label><input type="checkbox" name="props[]" value="<?= esc($pr, 'attr') ?>" <?= in_array(rod_normalizar($pr), $propsAct, true) ? 'checked' : '' ?>><span><?= esc($pr) ?></span></label>
                    <?php endforeach; ?>
                    <input class="fv-input" name="props_nuevo" placeholder="+ otros, separados por comas" autocomplete="off">
                </div>
                <small class="rd-ayuda">Salen de «Qué llevar» de la sesión. Los nuevos se añaden allí.</small>
            </div>

            <div class="rd-campo">
                <span class="rd-lbl">Variantes</span>
                <ul class="rf-vars" id="rfVars">
                    <?php foreach ($variantes ?? [] as $n => $va): ?>
                        <li>
                            <i class="bi bi-grip-vertical rf-asa" title="Arrastra para ordenar"></i>
                            <input type="hidden" name="variantes[<?= $n ?>][id]" value="<?= (int) $va['id'] ?>">
                            <input class="fv-input" name="variantes[<?= $n ?>][texto]" value="<?= esc($va['texto'], 'attr') ?>" aria-label="Variante">
                            <input type="hidden" name="variantes[<?= $n ?>][prioridad]" value="<?= $va['prioridad'] === 'opc' ? 'opc' : 'imp' ?>">
                            <button type="button" class="rf-prio <?= $va['prioridad'] === 'opc' ? 'opc' : 'imp' ?>" data-prio><?= $va['prioridad'] === 'opc' ? 'Si da tiempo' : 'Imprescindible' ?></button>
                            <button type="button" class="fv-x" data-quitar title="Quitar"><i class="bi bi-x"></i></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <input class="fv-input" id="rfVarNueva" placeholder="Escribe una variante y pulsa Enter: encuadre, pose, movimiento…" autocomplete="off">
            </div>

            <?= $area('plano_notas', 'Notas') ?>
        </div>

        <h2 class="rf-mas-h">Más detalles</h2>

        <details class="fv-panel rd-sec" <?= $lleno($camposEscena) || $hayErrores ? 'open' : '' ?>>
            <?= $sec('bi-film', 'Escena', $lleno($camposEscena)) ?>
            <div class="rd-filas-f">
                <?= $input('escena_tomas', 'Toma/s') ?>
                <?= $input('plano_hora_dia', 'Clasificación de rodaje', 'con actores, atrezzo…') ?>
            </div>
            <div class="rd-filas-f" style="grid-template-columns: minmax(0, 1fr) 90px">
                <?php
                // Desplegable de temáticas; «+ Nueva…» abre el campo de nombre. El servidor
                // sigue recibiendo escena_bloque y sincronizarBloques crea/asigna la temática.
                $esTema = !empty($proyecto['sesion_id']);
                $bloqueAct = (string) old('escena_bloque', $e['escena_bloque'] ?? (service('request')->getGet('bloque') ?? ''));
                $bloqueSel = '';
                if ($bloqueAct !== '') {
                    $bloqueSel = 'nuevo';
                    foreach ($bloques ?? [] as $b) if (mb_strtolower(trim($b)) === mb_strtolower(trim($bloqueAct))) { $bloqueAct = $b; $bloqueSel = $b; }
                }
                ?>
                <div class="rd-campo">
                    <label for="f_bloque_sel"><?= $esTema ? 'Temática' : 'Bloque' ?><?php if ($esTema): ?> <a class="fv-link rd-lbl-acc" href="<?= site_url("rodajes/$pid/tematicas") ?>"><i class="bi bi-collection"></i>Temáticas</a><?php endif; ?></label>
                    <select class="form-select rd-sel" id="f_bloque_sel">
                        <option value="">Sin <?= $esTema ? 'temática' : 'bloque' ?></option>
                        <?php foreach ($bloques ?? [] as $b): ?><option value="<?= esc($b, 'attr') ?>" <?= $bloqueSel === $b ? 'selected' : '' ?>><?= esc($b) ?></option><?php endforeach; ?>
                        <option value="nuevo" <?= $bloqueSel === 'nuevo' ? 'selected' : '' ?>><?= $esTema ? '+ Nueva temática…' : '+ Nuevo bloque…' ?></option>
                    </select>
                    <div id="rfBloqueNuevo" class="rf-campo-nuevo" <?= $bloqueSel === 'nuevo' ? '' : 'hidden' ?>>
                        <input class="fv-input" id="f_escena_bloque" name="escena_bloque" value="<?= esc($bloqueAct, 'attr') ?>"
                               placeholder="<?= $esTema ? 'Nombre de la temática nueva' : 'Nombre del bloque nuevo' ?>" autocomplete="off" aria-label="Nombre nuevo">
                    </div>
                </div>
                <div class="rd-campo">
                    <label for="f_orden">Orden</label>
                    <input class="fv-input" id="f_orden" type="number" name="orden" value="<?= $attr('orden', (string) ($ordenSiguiente ?? 0)) ?>">
                </div>
            </div>
            <?= $area('escena_accion', 'Acción') ?>
            <div class="rd-filas-f dos">
                <?= $input('escena_objetivo', 'Objetivo narrativo') ?>
                <?= $input('plano_objetos', 'Objetos en escena') ?>
                <?= $input('escena_cont_previa', 'Continuidad previa') ?>
                <?= $input('escena_cont_posterior', 'Continuidad posterior') ?>
                <?= $input('escena_efecto_especial', 'Efecto especial') ?>
            </div>
            <div class="rd-checks"><?= $check('plano_actores', 'Con actores / modelos') ?></div>
        </details>

        <details class="fv-panel rd-sec" <?= $lleno($camposCamara) ? 'open' : '' ?>>
            <?= $sec('bi-camera', 'Cámara', $lleno($camposCamara)) ?>
            <div class="rf-cam-setup" id="rfCamSetup" hidden><i class="bi bi-link-45deg"></i><span></span></div>
            <div class="rd-filas-f cam">
                <?= $input('camara_modelo', 'Cámara', '', 'Sony a7IV') ?>
                <?= $input('camara_optica', 'Óptica', '24mm') ?>
                <?= $input('camara_apertura', 'Apertura', 'f/2.8') ?>
                <?= $input('camara_velocidad', 'Velocidad', '1/50') ?>
                <?= $input('camara_iso', 'ISO', '800') ?>
                <?= $input('camara_fps', 'FPS', '24') ?>
                <?= $input('camara_wb', 'Balance', '5600K') ?>
                <?= $input('camara_nd', 'Filtro ND', 'ND8') ?>
            </div>
            <div class="rd-filas-f cam">
                <?= $select('camara_tipo_plano', 'Tipo de plano', ['General', 'Medio', 'Detalle', 'Primer plano', 'Americano', 'Master']) ?>
                <?= $select('camara_angulo', 'Ángulo', ['Frontal', 'Picado', 'Cenital', 'Lateral', 'Contrapicado', 'Nadir']) ?>
                <?= $select('camara_movimiento', 'Movimiento', ['Fijo', 'Travelling', 'Paneo', 'Giro', 'Seguimiento', 'Dolly', 'Grúa']) ?>
                <?= $select('camara_soporte', 'Soporte', ['Trípode', 'Mano', 'Gimbal', 'Otro']) ?>
            </div>
        </details>

        <details class="fv-panel rd-sec" <?= $lleno($camposPlano) ? 'open' : '' ?>>
            <?= $sec('bi-lightbulb', 'Luz y alternativas', $lleno($camposPlano)) ?>
            <?= $area('plano_esquema_iluminacion', 'Esquema de iluminación', 2, 'Ventana a la izquierda + rebotador…') ?>
            <?= $area('plano_toma_alternativa', 'Toma alternativa', 2) ?>
        </details>

        <details class="fv-panel rd-sec" <?= $nSonido ? 'open' : '' ?>>
            <?= $sec('bi-mic', 'Sonido y diálogo', $nSonido) ?>
            <div class="rd-checks"><?= $check('sonido_ambiente', 'Grabar ambiente') ?><?= $check('sonido_antiviento', 'Antiviento') ?></div>
            <?= $area('sonido_dialogo_escrito', 'Diálogo escrito', 5) ?>
        </details>

        <details class="fv-panel rd-sec" <?= $nRefs ? 'open' : '' ?>>
            <?= $sec('bi-images', 'Referencias', $nRefs) ?>
            <div class="rd-filas-f dos">
                <div class="rd-campo">
                    <span class="rd-lbl">Lugar y objetos</span>
                    <?= $miniaturas($imagenes_lugar ?? []) ?>
                    <label class="rd-subir"><input type="file" name="lugar_objetos[]" accept="image/*,video/*" multiple><i class="bi bi-upload"></i><span>Subir imágenes o vídeos</span></label>
                    <textarea class="fv-input" name="plano_ref_lugar_texto" rows="2" placeholder="Enlaces o notas, uno por línea"><?= esc((string) $v('plano_ref_lugar_texto')) ?></textarea>
                </div>
                <div class="rd-campo">
                    <span class="rd-lbl">Inspiración</span>
                    <?= $miniaturas($imagenes_insp ?? []) ?>
                    <label class="rd-subir"><input type="file" name="inspiracion[]" accept="image/*,video/*" multiple><i class="bi bi-upload"></i><span>Subir imágenes o vídeos</span></label>
                    <textarea class="fv-input" name="plano_ref_inspiracion_texto" rows="2" placeholder="Paletas, planos, referencias de estilo…"><?= esc((string) $v('plano_ref_inspiracion_texto')) ?></textarea>
                </div>
            </div>
        </details>

        <div class="rd-form-pie rd-pie-fijo">
            <?php if ($editar): ?>
                <a class="fv-btn fv-btn-peligro izq" href="<?= site_url("rodajes/$pid/escenas/delete/$eid") ?>" onclick="return confirm('¿Borrar esta escena?')" title="Borrar escena"><i class="bi bi-trash"></i></a>
                <a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/show/$eid") ?>">Ver</a>
            <?php else: ?>
                <a class="fv-btn izq" href="<?= site_url("rodajes/$pid/escenas") ?>">Cancelar</a>
                <button type="submit" class="fv-btn" name="otra" value="1"><i class="bi bi-plus-lg"></i>Guardar y otra</button>
            <?php endif; ?>
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    document.querySelectorAll('.rd-formatos.desel input[type=radio]').forEach(r => {
        r.closest('label').addEventListener('pointerdown', () => { r.dataset.estaba = r.checked ? '1' : ''; });
        r.addEventListener('click', () => { if (r.dataset.estaba) { r.checked = false; r.dataset.estaba = ''; } });
    });

    const norm = s => s.trim().toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const zona = document.getElementById('f_zona');
    const punto = document.getElementById('f_punto');
    const ayuda = document.getElementById('rfZonaAyuda');
    const zonaSel = document.getElementById('f_zona_sel');
    const zonaNueva = document.getElementById('rfZonaNueva');
    const zonas = [...zonaSel.options].filter(o => o.value && o.value !== 'nuevo').map(o => norm(o.value));
    const textoAyuda = ayuda.textContent;
    // El desplegable manda; el campo de texto (zona_nombre) solo se ve con «+ Nueva zona…».
    const desdeSelect = () => {
        const nueva = zonaSel.value === 'nuevo';
        zonaNueva.hidden = !nueva;
        if (!nueva) zona.value = zonaSel.value;
        else if (zonas.includes(norm(zona.value))) zona.value = '';
    };
    zonaSel.addEventListener('change', () => {
        desdeSelect(); pintarZona(); marcarZona();
        if (zonaSel.value === 'nuevo') zona.focus();
    });
    const pintarZona = () => {
        const z = norm(zona.value);
        let alguno = false;
        punto.querySelectorAll('option[data-zona]').forEach(o => { o.hidden = o.dataset.zona !== z; if (!o.hidden) alguno = true; });
        if (punto.selectedOptions[0]?.hidden) punto.value = '';
        document.getElementById('rfPuntoCampo').hidden = !alguno;
        ayuda.textContent = z && !zonas.includes(z) ? ayuda.dataset.nueva.replace('%s', zona.value.trim()) : textoAyuda;
    };
    // Elegir zona / setup tocando su imagen (el campo de texto y el desplegable siguen valiendo).
    const zonaPick = document.getElementById('rfZonaPick');
    const marcarZona = () => zonaPick && zonaPick.querySelectorAll('.rf-destino').forEach(b => b.classList.toggle('on', norm(b.dataset.valor) === norm(zona.value)));
    zonaPick && zonaPick.addEventListener('click', ev => {
        const b = ev.target.closest('.rf-destino'); if (!b) return;
        zonaSel.value = b.classList.contains('on') ? '' : b.dataset.valor;
        desdeSelect(); pintarZona(); marcarZona();
    });
    zona.addEventListener('input', () => { pintarZona(); marcarZona(); });
    zonaNueva.hidden = zonaSel.value !== 'nuevo';
    pintarZona(); marcarZona();

    // Temática / bloque: el desplegable manda; el campo de texto solo con «+ Nueva…».
    const bloqueSel = document.getElementById('f_bloque_sel');
    const bloque = document.getElementById('f_escena_bloque');
    const bloqueNuevo = document.getElementById('rfBloqueNuevo');
    bloqueSel && bloqueSel.addEventListener('change', () => {
        const nuevo = bloqueSel.value === 'nuevo';
        bloqueNuevo.hidden = !nuevo;
        bloque.value = nuevo ? '' : bloqueSel.value;
        if (nuevo) bloque.focus();
    });

    const setup = document.getElementById('f_setup');
    const pintarSetup = () => {
        const o = setup.selectedOptions[0];
        document.getElementById('rfSetupNuevo').hidden = setup.value !== 'nuevo';
        const linea = document.getElementById('rfSetupLinea');
        linea.hidden = !(o && o.dataset.linea);
        linea.querySelector('.t2').textContent = o && o.dataset.linea ? o.dataset.linea : '';
    };
    // Setup y cámara al unísono: elegir setup rellena óptica / soporte (los fps son de cada toma); si
    // luego los cambias aquí, al guardar se cambian en el setup y en sus tomas.
    const camSetup = { optica: 'f_camara_optica', soporte: 'f_camara_soporte' };
    const avisoCam = document.getElementById('rfCamSetup');
    const nTomasSetup = <?= json_encode((object) ($tomasPorSetup ?? [])) ?>;
    const pintarCam = (rellenar) => {
        const o = setup.selectedOptions[0];
        const hay = setup.value !== '';
        Object.entries(camSetup).forEach(([k, id]) => {
            const c = document.getElementById(id);
            if (!c) return;
            c.closest('.rd-campo')?.classList.toggle('rf-de-setup', hay);
            if (!rellenar || !o || !o.dataset[k]) return;
            if (c.tagName === 'SELECT' && ![...c.options].some(x => x.value === o.dataset[k])) c.add(new Option(o.dataset[k], o.dataset[k]));
            c.value = o.dataset[k];
        });
        if (!avisoCam) return;
        avisoCam.hidden = !hay;
        if (!hay) return;
        const otras = Math.max(0, (nTomasSetup[setup.value] || 0) - (<?= (int) ($e['setup_id'] ?? 0) ?> === +setup.value ? 1 : 0));
        avisoCam.querySelector('span').textContent = setup.value === 'nuevo'
            ? 'Óptica y soporte serán los del setup nuevo.'
            : 'Óptica y soporte van con el setup «' + o.textContent.trim() + '»: si los cambias aquí, cambian en el setup' + (otras ? ' y en sus otras ' + otras + (otras === 1 ? ' toma' : ' tomas') : '') + '.';
    };
    setup.addEventListener('change', () => pintarCam(true));

    const setupPick = document.getElementById('rfSetupPick');
    const marcarSetup = () => setupPick && setupPick.querySelectorAll('.rf-destino').forEach(b => b.classList.toggle('on', b.dataset.valor === setup.value));
    setupPick && setupPick.addEventListener('click', ev => {
        const b = ev.target.closest('.rf-destino'); if (!b) return;
        setup.value = b.classList.contains('on') ? '' : b.dataset.valor;
        pintarSetup(); marcarSetup(); pintarCam(true);
    });
    setup.addEventListener('change', () => { pintarSetup(); marcarSetup(); if (setup.value === 'nuevo') document.getElementById('f_setup_nuevo').focus(); });
    pintarSetup(); marcarSetup(); pintarCam(false);

    const lista = document.getElementById('rfVars');
    const nueva = document.getElementById('rfVarNueva');
    let n = lista.children.length + 1000;
    // El orden se guarda tal cual queda en la lista al pulsar Guardar.
    if (window.Sortable) Sortable.create(lista, { handle: '.rf-asa', animation: 150 });
    nueva.addEventListener('keydown', ev => {
        if (ev.key !== 'Enter') return;
        ev.preventDefault();
        const t = nueva.value.trim();
        if (!t) return;
        n++;
        const li = document.createElement('li');
        li.innerHTML = `<i class="bi bi-grip-vertical rf-asa" title="Arrastra para ordenar"></i><input class="fv-input" name="variantes[${n}][texto]" aria-label="Variante"><input type="hidden" name="variantes[${n}][prioridad]" value="imp"><button type="button" class="rf-prio imp" data-prio>Imprescindible</button><button type="button" class="fv-x" data-quitar title="Quitar"><i class="bi bi-x"></i></button>`;
        li.querySelector('.fv-input').value = t;
        lista.appendChild(li);
        nueva.value = '';
    });
    lista.addEventListener('click', ev => {
        const b = ev.target.closest('button');
        if (!b) return;
        const li = b.closest('li');
        if (b.hasAttribute('data-quitar')) {
            if (confirm('¿Quitar esta variante?')) li.remove();
            return;
        }
        const h = li.querySelector('input[name$="[prioridad]"]');
        h.value = h.value === 'opc' ? 'imp' : 'opc';
        b.className = 'rf-prio ' + h.value;
        b.textContent = h.value === 'opc' ? 'Si da tiempo' : 'Imprescindible';
    });
    lista.addEventListener('keydown', ev => { if (ev.key === 'Enter' && ev.target.matches('.fv-input')) ev.preventDefault(); });
    document.querySelector('[name="props_nuevo"]').addEventListener('keydown', ev => { if (ev.key === 'Enter') ev.preventDefault(); });
})();
document.querySelectorAll('.rd-subir input').forEach(inp => inp.addEventListener('change', () => {
    const n = inp.files.length;
    inp.closest('.rd-subir').classList.toggle('con', n > 0);
    inp.nextElementSibling.nextElementSibling.textContent = n ? n + (n === 1 ? ' archivo listo para subir' : ' archivos listos para subir') : 'Subir imágenes o vídeos';
}));
</script>
<?= $this->endSection() ?>
