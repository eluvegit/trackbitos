<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — nueva / editar habitación (estilo fv).
 *
 * @var array|null $habitacion
 */
$h      = $habitacion ?? null;
$editar = (bool) $h;
$icono  = (string) old('icono', $h['icono'] ?? 'house');
?>
<div class="fv rc estrecho">
    <?= view('hogar/_cabecera', [
        'migas'  => $editar ? [$h['nombre'] => site_url('hogar/' . $h['id']), 'Editar' => null] : ['Nueva habitación' => null],
        'titulo' => $editar ? 'Editar habitación' : 'Nueva habitación',
    ]) ?>

    <form class="rc-form" method="post" action="<?= $editar ? site_url('hogar/habitaciones/actualizar/' . $h['id']) : site_url('hogar/habitaciones/crear') ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <label class="rc-campo">
                <span>Nombre</span>
                <input class="fv-input rc-grande" name="nombre" value="<?= esc((string) old('nombre', $h['nombre'] ?? ''), 'attr') ?>" placeholder="Cocina, baño, salón…" required maxlength="100" <?= $editar ? '' : 'autofocus' ?>>
            </label>
            <div class="rc-campo">
                <span>Icono</span>
                <div class="rc-cats">
                    <?php foreach (hogar_iconos() as $k => $txt): ?>
                        <label><input type="radio" name="icono" value="<?= $k ?>" <?= $icono === $k ? 'checked' : '' ?>><span><i class="bi bi-<?= $k ?>"></i><?= esc($txt) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= $editar ? site_url('hogar/' . $h['id']) : site_url('hogar') ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
