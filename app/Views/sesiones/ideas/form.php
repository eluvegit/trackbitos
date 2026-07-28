<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-lightbulb text-primary"></i>
    <a href="<?= site_url('sesiones') ?>" class="text-decoration-none text-muted fw-normal">Sesiones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= isset($idea) ? 'Editar' : 'Nueva' ?> idea</strong>
</h5>

<?php if (session('errors')): ?>
    <div class="alert alert-danger py-2">
        <ul class="mb-0">
            <?php foreach (session('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="<?= isset($idea) ? site_url('sesiones/ideas/' . $idea['id'] . '/actualizar') : site_url('sesiones/ideas/guardar') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="titulo" class="form-control" value="<?= esc(old('titulo', $idea['titulo'] ?? '')) ?>" required maxlength="150">
                    </div>

                    <?php if (!isset($idea)): ?>
                        <div class="mb-3">
                            <label class="form-label d-block">¿Qué partes te imaginas?</label>
                            <div class="btn-group" role="group">
                                <input type="checkbox" class="btn-check" name="partes[]" value="foto" id="parteFoto" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="parteFoto"><i class="bi bi-camera"></i> Fotografía</label>

                                <input type="checkbox" class="btn-check" name="partes[]" value="video" id="parteVideo" autocomplete="off">
                                <label class="btn btn-outline-primary" for="parteVideo"><i class="bi bi-camera-video"></i> Vídeo</label>
                            </div>
                            <div class="form-text">Al promover la idea a sesión, esas partes arrancan en "Planificación".</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label d-block">Partes de esta idea</label>
                            <?php if ((int) $idea['tiene_foto'] === 1): ?>
                                <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera"></i> Fotografía</span>
                            <?php endif; ?>
                            <?php if ((int) $idea['tiene_video'] === 1): ?>
                                <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera-video"></i> Vídeo</span>
                            <?php endif; ?>
                            <div class="form-text">Las partes se fijan al crear la idea y no se pueden cambiar después.</div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="4"><?= esc(old('notas', $idea['notas'] ?? '')) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                        <a href="<?= isset($idea) ? site_url('sesiones/ideas/' . $idea['id']) : site_url('sesiones') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
