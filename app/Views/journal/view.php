<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container py-3">

    <h2><?= esc($log->task_title) ?> <?= $log->subtask_title ? ' / '.esc($log->subtask_title) : '' ?></h2>

    <ul class="list-group my-3">
        <li class="list-group-item"><strong>Fecha:</strong> <?= $log->date ?></li>
        <li class="list-group-item"><strong>Tiempo invertido:</strong> <?= $log->time_spent ?> min</li>
        <li class="list-group-item"><strong>Progreso:</strong> <?= $log->progress ?>%</li>
        <?php if($log->note): ?>
            <li class="list-group-item"><strong>Nota:</strong> <?= esc($log->note) ?></li>
        <?php endif; ?>
        <?php if($log->image): ?>
            <li class="list-group-item">
                <img src="<?= base_url($log->image) ?>" class="img-fluid" alt="Imagen registro">
            </li>
        <?php endif; ?>
    </ul>

    <a href="<?= site_url('journal/edit/'.$log->id) ?>" class="btn btn-primary">Editar</a>
    <a href="<?= site_url('journal') ?>" class="btn btn-secondary">Volver</a>

</div>

<?= $this->endSection() ?>
