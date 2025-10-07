<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Importar enlaces desde HTML de Telegram</h5>
        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces') ?>">Volver</a>
    </div>

    <?php if (session()->getFlashdata('msg')): ?>
        <div class="alert alert-info"><?= esc(session()->getFlashdata('msg')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ((array)session()->getFlashdata('errors') as $e): ?>
                    <li><?= esc($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('enlaces/importar') ?>" method="post" enctype="multipart/form-data" class="border rounded p-3">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label">Archivo HTML exportado de Telegram</label>
            <input type="file" name="html_file" accept=".html,.htm,text/html" class="form-control" required>
            <div class="form-text">
                Sube el archivo principal (normalmente <code>messages.html</code>).
            </div>
        </div>

        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="1" id="dryRun" name="dry_run" checked>
            <label class="form-check-label" for="dryRun">
                Simulación (no guarda en base de datos). Útil para ver cuántos enlaces detecta.
            </label>
        </div>

        <button class="btn btn-primary">Procesar</button>
    </form>
</div>

<?php $this->endSection(); ?>
