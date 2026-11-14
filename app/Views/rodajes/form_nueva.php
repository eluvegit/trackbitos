<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * @var array|null $proyecto
 * @var array      $sesiones [id => sesión]
 */
$p      = $proyecto ?? [];
$editar = (bool) $proyecto;
$sesSel = (int) old('sesion_id', $p['sesion_id'] ?? 0);
?>
<div class="fv rd estrecho">
    <?= view('rodajes/_cabecera', [
        'migas'  => $editar ? [$p['titulo'] => site_url('rodajes/' . $p['id'] . '/escenas'), 'Editar' => null] : ['Nuevo proyecto' => null],
        'titulo' => $editar ? 'Editar proyecto' : 'Nuevo proyecto',
        'sub'    => $editar ? '' : '<span>Solo hace falta el título.</span>',
    ]) ?>

    <form class="rd-form" method="post" action="<?= site_url($editar ? 'rodajes/update/' . $p['id'] : 'rodajes/store') ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <div class="rd-campo">
                <label for="rdTitulo">Título</label>
                <input class="fv-input rd-grande" id="rdTitulo" name="titulo" required minlength="3" maxlength="150" value="<?= esc(old('titulo', $p['titulo'] ?? ''), 'attr') ?>" <?= $editar ? '' : 'autofocus' ?>>
            </div>
            <div class="rd-filas-f">
                <div class="rd-campo">
                    <label for="rdCodigo">Código</label>
                    <input class="fv-input" id="rdCodigo" name="codigo" value="<?= esc(old('codigo', $p['codigo'] ?? ''), 'attr') ?>" placeholder="opcional">
                </div>
                <div class="rd-campo">
                    <label for="rdSesion">Sesión de stock vinculada</label>
                    <select class="form-select rd-sel" id="rdSesion" name="sesion_id">
                        <option value="">— Ninguna —</option>
                        <?php foreach ($sesiones as $sid => $s): ?>
                            <option value="<?= $sid ?>" <?= $sesSel === $sid ? 'selected' : '' ?>><?= esc($s['titulo']) ?><?= $s['fecha_sesion'] ? ' · ' . esc($s['fecha_sesion']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="rd-ayuda">Con una sesión vinculada puedes traer sus situaciones como escenas, enviar los bloques como situaciones y ver su moodboard en el plató.</p>
            <div class="rd-campo">
                <label for="rdDesc">Descripción / brief</label>
                <textarea class="fv-input" id="rdDesc" name="descripcion" rows="5"><?= esc(old('descripcion', $p['descripcion'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="rd-form-pie">
            <?php if ($editar): ?>
                <a class="fv-btn fv-btn-peligro izq" href="<?= site_url('rodajes/delete/' . $p['id']) ?>" onclick="return confirm('¿Borrar el proyecto «<?= esc($p['titulo'], 'js') ?>»?')"><i class="bi bi-trash"></i>Borrar</a>
            <?php endif; ?>
            <a class="fv-btn" href="<?= site_url($editar ? 'rodajes/' . $p['id'] . '/escenas' : 'rodajes') ?>">Cancelar</a>
            <button type="submit" class="fv-btn fv-btn-pri"><?= $editar ? 'Guardar' : 'Crear proyecto' ?></button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
