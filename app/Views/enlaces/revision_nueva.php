<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php /** Enlaces — revisión de los que no tienen título (estilo fv). @var int $pendientes */ ?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'activa' => 'revision',
        'titulo' => 'Sin título',
        'sub'    => '<span>Enlaces guardados sin título: ponles uno (y lo que quieras) de uno en uno.</span>',
    ]) ?>

    <?php if ($pendientes > 0): ?>
        <div class="fv-panel en-rep-vacio">
            <div class="en-rep-c" style="justify-items:center"><span class="v"><?= (int) $pendientes ?></span><span class="k"><?= $pendientes === 1 ? 'enlace pendiente' : 'enlaces pendientes' ?></span></div>
            <a href="<?= site_url('enlaces/revision/item') ?>" class="fv-btn fv-btn-pri"><i class="bi bi-play-fill"></i>Empezar</a>
            <div class="t3" style="font-size:12px">Atajos dentro: S guardar · N saltar · D borrar</div>
        </div>
    <?php else: ?>
        <div class="fv-panel en-rep-vacio"><div class="emoji">🎉</div><div>Todos tus enlaces tienen título.</div></div>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
