<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-camera text-primary"></i>
    <a href="<?= site_url('sesiones') ?>" class="text-decoration-none text-muted fw-normal">Sesiones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= isset($sesion) ? 'Editar' : 'Nueva' ?> sesión</strong>
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
    <div class="col-md-8">
        <div class="ses-card">
            <div class="ses-card-body">
                <form action="<?= isset($sesion) ? site_url('sesiones/' . $sesion['id'] . '/actualizar') : site_url('sesiones/guardar') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="titulo" class="form-control" value="<?= esc(old('titulo', $sesion['titulo'] ?? '')) ?>" required maxlength="150">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha de la sesión (shooting)</label>
                        <input type="date" name="fecha_sesion" class="form-control" value="<?= esc(old('fecha_sesion', $sesion['fecha_sesion'] ?? '')) ?>">
                    </div>

                    <?php if (!isset($sesion)): ?>
                        <div class="mb-3">
                            <label class="form-label d-block">¿Qué partes tiene esta sesión?</label>
                            <div class="btn-group" role="group">
                                <input type="checkbox" class="btn-check" name="partes[]" value="foto" id="parteFoto" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="parteFoto"><i class="bi bi-camera"></i> Fotografía</label>

                                <input type="checkbox" class="btn-check" name="partes[]" value="video" id="parteVideo" autocomplete="off">
                                <label class="btn btn-outline-primary" for="parteVideo"><i class="bi bi-camera-video"></i> Vídeo</label>
                            </div>
                            <div class="form-text">Cada parte lleva su propio flujo de trabajo independiente (planificación → edición → subiendo → completado). ¿Todavía no tiene forma? Apúntala como <a href="<?= site_url('sesiones/ideas/crear') ?>">idea</a> en vez de crear la sesión.</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label d-block">Partes de esta sesión</label>
                            <?php if ($sesion['estado_foto'] !== null): ?>
                                <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera"></i> Fotografía</span>
                            <?php endif; ?>
                            <?php if ($sesion['estado_video'] !== null): ?>
                                <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera-video"></i> Vídeo</span>
                            <?php endif; ?>
                            <div class="form-text">Las partes se fijan al crear la sesión y no se pueden cambiar después.</div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Briefing</label>
                        <textarea name="briefing" class="form-control" rows="8" placeholder="Desarrollo de la idea, descripción de la sesión, detalles a tener en cuenta..."><?= esc(old('briefing', $sesion['briefing'] ?? '')) ?></textarea>
                        <div class="form-text">Se usará para elaborar el informe con toda la información necesaria, por ejemplo para la modelo.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="4"><?= esc(old('notas', $sesion['notas'] ?? '')) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                        <a href="<?= isset($sesion) ? site_url('sesiones/' . $sesion['id']) : site_url('sesiones') ?>" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.ses-card {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 1.25rem;
    box-shadow: 0 10px 30px -12px rgba(0, 0, 0, .45);
}
.ses-card-body {
    padding: 1.5rem;
}
</style>

<?= $this->endSection() ?>
