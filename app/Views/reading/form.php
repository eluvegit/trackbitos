<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= site_url('reading') ?>" class="text-decoration-none text-muted fw-normal">
        <i class="bi bi-arrow-left"></i> Lectura
    </a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Añadir libro</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<form action="<?= site_url('reading/crear') ?>" method="post" class="rd-form">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="title" class="form-label">Título</label>
        <input type="text" id="title" name="title" class="form-control" required maxlength="255"
               value="<?= esc(old('title') ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="author" class="form-label">Autor (opcional)</label>
        <input type="text" id="author" name="author" class="form-control" maxlength="255"
               value="<?= esc(old('author') ?? '') ?>">
    </div>

    <div class="row">
        <div class="col-6 mb-3">
            <label for="total_pages" class="form-label">Páginas totales (opcional)</label>
            <input type="number" id="total_pages" name="total_pages" class="form-control" min="1"
                   value="<?= esc(old('total_pages') ?? '') ?>">
        </div>
        <div class="col-6 mb-3">
            <label for="status" class="form-label">¿Dónde está ahora?</label>
            <select id="status" name="status" class="form-select">
                <option value="quiero_leer">Quiero leer</option>
                <option value="leyendo">Ya lo estoy leyendo</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label for="min_goal_pages" class="form-label">¿Qué es un día de lectura satisfactorio para este libro?</label>
        <input type="number" id="min_goal_pages" name="min_goal_pages" class="form-control" min="1"
               value="<?= esc(old('min_goal_pages') ?? '1') ?>">
        <div class="form-text">Puede ser 1 página. En serio. Aquí no hay mínimo "correcto".</div>
    </div>

    <div class="mb-3">
        <label for="anchor_routine" class="form-label">¿A qué rutina lo enganchas? (opcional)</label>
        <input type="text" id="anchor_routine" name="anchor_routine" class="form-control" maxlength="255"
               placeholder="ej: gotas oftálmicas, café de la mañana, antes de dormir..."
               value="<?= esc(old('anchor_routine') ?? '') ?>">
        <div class="form-text">Si ya tienes un hueco fijo en el día, engancharlo ahí hace que no haya que decidir "¿leo hoy?" cada vez.</div>
    </div>

    <details class="mb-3">
        <summary class="text-muted small">Más datos (opcional)</summary>
        <div class="mt-2">
            <div class="mb-2">
                <label for="isbn" class="form-label">ISBN</label>
                <input type="text" id="isbn" name="isbn" class="form-control" maxlength="20" value="<?= esc(old('isbn') ?? '') ?>">
            </div>
            <div class="mb-2">
                <label for="cover_url" class="form-label">URL de la portada</label>
                <input type="url" id="cover_url" name="cover_url" class="form-control" maxlength="500" value="<?= esc(old('cover_url') ?? '') ?>">
            </div>
        </div>
    </details>

    <div class="d-flex gap-2">
        <a href="<?= site_url('reading') ?>" class="btn btn-outline-secondary flex-fill">Cancelar</a>
        <button type="submit" class="btn btn-primary flex-fill">Añadir libro</button>
    </div>
</form>

<?= $this->endSection() ?>
