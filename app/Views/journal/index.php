<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container py-3">

    <!-- CABECERA -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1>Journal</h1>
        <div class="btn-group btn-group-toggle" data-toggle="buttons">
            <a href="<?= site_url('journal?view=portadas') ?>" class="btn btn-sm <?= $view_mode == 'portadas' ? 'btn-primary' : 'btn-outline-primary' ?>">Portadas</a>
            <a href="<?= site_url('journal?view=texto') ?>" class="btn btn-sm <?= $view_mode == 'texto' ? 'btn-primary' : 'btn-outline-primary' ?>">Texto</a>
        </div>
    </div>

    <!-- LISTADO DE REGISTROS -->
    <div class="journal-list">
        <?php if (empty($task_logs)): ?>

            <div class="text-center py-5">

                <h5 class="text-muted mb-3">
                    Aún no has registrado ninguna actividad.
                </h5>

                <a href="<?= site_url('journal/create') ?>"
                    class="btn btn-primary btn-lg mb-3">
                    + Crear primer registro
                </a>

                <div class="mt-3">
                    <small class="text-muted">
                        Primero crea una tarea si todavía no tienes ninguna.
                    </small>
                    <br>
                    <a href="<?= site_url('tasks/create') ?>"
                        class="btn btn-outline-secondary btn-sm mt-2">
                        Crear tarea
                    </a>
                </div>

            </div>

        <?php else: ?>

            <?php foreach ($task_logs as $log): ?>

                <?php if ($view_mode == 'portadas'): ?>
                    <div class="card mb-2" style="border-left: 5px solid <?= $log->color ?>;">
                        <?php if ($log->image): ?>
                            <img src="<?= base_url($log->image) ?>" class="card-img-top" alt="Imagen registro">
                        <?php else: ?>
                            <div class="card-img-top bg-light text-center py-3">
                                <span class="text-muted">Sin imagen</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <h5 class="card-title mb-1"><?= esc($log->task_title) ?></h5>
                            <?php if ($log->subtask_title): ?>
                                <small class="text-muted"><?= esc($log->subtask_title) ?></small>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mt-1">
                                <small><?= $log->date ?> | +<?= $log->progress ?>%</small>
                                <small><?= $log->time_spent ?> min</small>
                            </div>
                            <a href="<?= site_url('journal/view/' . $log->id) ?>" class="stretched-link"></a>
                        </div>
                    </div>

                <?php else: // Vista texto 
                ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                        <div>
                            <small class="text-muted"><?= $log->date ?></small> -
                            <strong><?= esc($log->task_title) ?></strong>
                            <?php if ($log->subtask_title): ?>
                                / <em><?= esc($log->subtask_title) ?></em>
                            <?php endif; ?>
                            | +<?= $log->progress ?>%
                        </div>
                        <div>
                            <small><?= $log->time_spent ?> min</small>
                            <a href="<?= site_url('journal/edit/' . $log->id) ?>" class="btn btn-sm btn-outline-secondary ml-2">Editar</a>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?= $this->endSection() ?>