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

$camposEscena = ['escena_tomas', 'escena_ubicacion', 'escena_objetivo', 'escena_accion', 'plano_objetos', 'escena_efecto_especial', 'escena_cont_previa', 'escena_cont_posterior', 'plano_notas', 'plano_hora_dia'];
$camposCamara = ['camara_optica', 'camara_apertura', 'camara_fps', 'camara_velocidad', 'camara_iso', 'camara_wb', 'camara_nd', 'camara_tipo_plano', 'camara_angulo', 'camara_movimiento', 'camara_soporte'];
$camposPlano  = ['plano_esquema_iluminacion', 'plano_toma_alternativa'];
$nRefs = count($imagenes_lugar ?? []) + count($imagenes_insp ?? []) + $lleno(['plano_ref_lugar_texto', 'plano_ref_inspiracion_texto']);
$nSonido = $lleno(['sonido_dialogo_escrito']) + (($e['sonido_ambiente'] ?? 'N') === 'S') + (($e['sonido_antiviento'] ?? 'N') === 'S');
$hayErrores = (bool) session('errors');

$titulo = $editar ? 'Editar escena #' . (int) $e['orden'] : 'Nueva escena';
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
            : [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Nueva escena' => null],
        'titulo' => $titulo,
        'sub'    => $editar ? '' : '<span>Con la descripción basta. El resto, cuando quieras.</span>',
        'iconos' => $iconos,
    ]) ?>

    <form class="rd-form" method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="fv-panel">
            <div class="rd-campo">
                <label for="f_escena_descripcion">Descripción</label>
                <input class="fv-input rd-grande" id="f_escena_descripcion" name="escena_descripcion" value="<?= $attr('escena_descripcion') ?>"
                       placeholder="Qué se ve: «Manos sirviendo café, plano cenital»" <?= $editar ? '' : 'autofocus' ?>>
            </div>
            <div class="rd-filas-f" style="grid-template-columns: minmax(0, 1fr) 90px">
                <div class="rd-campo">
                    <label for="f_escena_bloque">Bloque</label>
                    <input class="fv-input" id="f_escena_bloque" name="escena_bloque" list="rdBloques"
                           value="<?= esc((string) old('escena_bloque', $e['escena_bloque'] ?? (service('request')->getGet('bloque') ?? '')), 'attr') ?>"
                           placeholder="Situación / localización">
                    <datalist id="rdBloques"><?php foreach ($bloques ?? [] as $b): ?><option value="<?= esc($b, 'attr') ?>"><?php endforeach; ?></datalist>
                </div>
                <div class="rd-campo">
                    <label for="f_orden">Orden</label>
                    <input class="fv-input" id="f_orden" type="number" name="orden" value="<?= $attr('orden', (string) ($ordenSiguiente ?? 0)) ?>">
                </div>
            </div>
        </div>

        <details class="fv-panel rd-sec" <?= $lleno($camposEscena) || $hayErrores ? 'open' : '' ?>>
            <?= $sec('bi-film', 'Escena', $lleno($camposEscena)) ?>
            <div class="rd-filas-f">
                <?= $input('escena_tomas', 'Toma/s') ?>
                <?= $input('escena_ubicacion', 'Ubicación') ?>
                <?= $input('plano_hora_dia', 'Clasificación de rodaje', 'con actores, atrezzo…') ?>
            </div>
            <?= $area('escena_accion', 'Acción') ?>
            <div class="rd-filas-f dos">
                <?= $input('escena_objetivo', 'Objetivo narrativo') ?>
                <?= $input('plano_objetos', 'Objetos en escena') ?>
                <?= $input('escena_cont_previa', 'Continuidad previa') ?>
                <?= $input('escena_cont_posterior', 'Continuidad posterior') ?>
                <?= $input('escena_efecto_especial', 'Efecto especial') ?>
            </div>
            <?= $area('plano_notas', 'Notas') ?>
            <div class="rd-checks"><?= $check('plano_actores', 'Con actores / modelos') ?></div>
        </details>

        <details class="fv-panel rd-sec" <?= $lleno($camposCamara) ? 'open' : '' ?>>
            <?= $sec('bi-camera', 'Cámara', $lleno($camposCamara)) ?>
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

<script>
document.querySelectorAll('.rd-subir input').forEach(inp => inp.addEventListener('change', () => {
    const n = inp.files.length;
    inp.closest('.rd-subir').classList.toggle('con', n > 0);
    inp.nextElementSibling.nextElementSibling.textContent = n ? n + (n === 1 ? ' archivo listo para subir' : ' archivos listos para subir') : 'Subir imágenes o vídeos';
}));
</script>
<?= $this->endSection() ?>
