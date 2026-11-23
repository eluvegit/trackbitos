<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Alta / edición de sesión (estilo fv). Solo título y fecha: el briefing y
 * las notas se escriben en la ficha (Sesiones::update solo toca los campos
 * que llegan, así que este formulario no los pisa).
 *
 * @var array|null $sesion
 * @var bool       $defaultIdea
 */
$s      = $sesion ?? [];
$editar = (bool) $sesion;
$partesUi = ses_partes();
$partesOld = (array) (old('partes') ?? ['foto']);
$esIdea = old('es_idea') !== null ? (bool) old('es_idea') : !empty($defaultIdea);
?>
<div class="fv ss estrecho">
    <?= view('sesiones/_cabecera', [
        'migas'  => $editar ? [$s['titulo'] => site_url('sesiones/' . $s['id']), 'Editar' => null] : [($esIdea ? 'Nueva idea' : 'Nueva sesión') => null],
        'titulo' => $editar ? 'Editar sesión' : ($esIdea ? 'Nueva idea' : 'Nueva sesión'),
        'sub'    => $editar ? '' : '<span>Con un título basta. Lo demás se completa en la ficha.</span>',
    ]) ?>

    <form class="ss-form" method="post" action="<?= $editar ? site_url('sesiones/' . $s['id'] . '/actualizar') : site_url('sesiones/guardar') ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <div class="ss-campo">
                <label for="ssTitulo">Título</label>
                <input class="fv-input ss-grande" id="ssTitulo" name="titulo" required maxlength="150" value="<?= esc(old('titulo', $s['titulo'] ?? ''), 'attr') ?>" <?= $editar ? '' : 'autofocus' ?> placeholder="Ej.: Café en terraza, otoño">
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap">
                <div class="ss-campo" style="flex: 0 1 220px">
                    <label for="ssFecha">Fecha del shooting</label>
                    <input class="fv-input" id="ssFecha" type="date" name="fecha_sesion" value="<?= esc(old('fecha_sesion', $s['fecha_sesion'] ?? ''), 'attr') ?>">
                </div>
                <div class="ss-campo" style="flex: 0 1 140px">
                    <label for="ssHoraF">Hora</label>
                    <input class="fv-input" id="ssHoraF" type="time" name="hora_sesion" style="color-scheme: dark" value="<?= esc(substr((string) old('hora_sesion', $s['hora_sesion'] ?? ''), 0, 5), 'attr') ?>">
                </div>
            </div>

            <div class="ss-campo">
                <span class="ss-lbl">Partes</span>
                <?php if (!$editar): ?>
                    <div class="ss-seg">
                        <?php foreach ($partesUi as $p => [$nombre, $ico]): ?>
                            <label><input type="checkbox" name="partes[]" value="<?= $p ?>" <?= in_array($p, $partesOld, true) ? 'checked' : '' ?>><span><i class="bi <?= $ico ?>"></i><?= $nombre ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <p class="ss-ayuda">Cada parte lleva su propio flujo: idea → planificación → edición → subiendo → completado. No se pueden cambiar después.</p>
                <?php else: ?>
                    <div class="ss-seg fijo">
                        <?php foreach ($partesUi as $p => [$nombre, $ico]): ?>
                            <?php if ($s['estado_' . $p] !== null): ?><span class="on"><i class="bi <?= $ico ?>"></i><?= $nombre ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <p class="ss-ayuda">Las partes se fijan al crear la sesión.</p>
                <?php endif; ?>
            </div>

            <?php if (!$editar): ?>
                <label class="ss-interruptor">
                    <input type="checkbox" name="es_idea" value="1" <?= $esIdea ? 'checked' : '' ?>>
                    <span><b><i class="bi bi-lightbulb"></i> Es solo una idea</b><small>Empieza en la etapa «Idea» y no sale en Activas hasta que la avances.</small></span>
                </label>
            <?php endif; ?>
        </div>

        <div class="ss-form-pie">
            <a class="fv-btn" href="<?= $editar ? site_url('sesiones/' . $s['id']) : site_url('sesiones') ?>">Cancelar</a>
            <button type="submit" class="fv-btn fv-btn-pri"><?= $editar ? 'Guardar' : 'Crear' ?></button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
