<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php /** YouTube — nueva lista (estilo fv). */ ?>
<div class="fv rc estrecho">
    <?= view('youtube/_cabecera', [
        'migas'  => ['Nueva lista' => null],
        'titulo' => 'Nueva lista',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('youtube/crear') ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <label class="rc-campo">
                <span>Nombre</span>
                <input class="fv-input rc-grande" name="nombre" placeholder="Cine, cámara, cocina…" required maxlength="150" autofocus>
            </label>
            <div class="rc-info"><i class="bi bi-info-circle"></i><span>Después podrás importar los vídeos de una lista de reproducción de YouTube.</span></div>
        </div>
        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= site_url('youtube') ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Crear</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
