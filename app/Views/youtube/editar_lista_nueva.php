<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * YouTube — editar lista (estilo fv). Los errores llegan por flash
 * (`errors`) y los pinta la cabecera.
 *
 * @var array $lista
 */
$volver = site_url('youtube/' . $lista['slug']);
?>
<div class="fv rc estrecho">
    <?= view('youtube/_cabecera', [
        'migas'  => [$lista['nombre'] => $volver, 'Editar' => null],
        'titulo' => 'Editar lista',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('youtube/' . esc($lista['slug'], 'url') . '/editar') ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <label class="rc-campo">
                <span>Nombre</span>
                <input class="fv-input rc-grande" name="nombre" value="<?= esc((string) old('nombre', $lista['nombre']), 'attr') ?>" required maxlength="150">
            </label>
            <div class="rc-info"><i class="bi bi-info-circle"></i><span>La dirección de la lista cambia con el nombre (ahora <code>youtube/<?= esc($lista['slug']) ?></code>).</span></div>
        </div>
        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= $volver ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
