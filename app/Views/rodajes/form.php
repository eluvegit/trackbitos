<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h1><?= $proyecto ? 'Editar proyecto' : 'Nuevo proyecto' ?></h1>
    <?php if (session('errors')): ?>
        <div class="alert alert-danger">
            <pre class="mb-0"><?= print_r(session('errors'), true) ?></pre>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= site_url($proyecto ? 'rodajes/update/' . $proyecto['id'] : 'rodajes/store') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" name="titulo" class="form-control" value="<?= old('titulo', $proyecto['titulo'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Código</label>
            <input type="text" name="codigo" class="form-control" value="<?= old('codigo', $proyecto['codigo'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3"><?= old('descripcion', $proyecto['descripcion'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Sesión de stock vinculada <span class="text-muted small">(opcional)</span></label>
            <?php $sesSel = (int) old('sesion_id', $proyecto['sesion_id'] ?? 0); ?>
            <select name="sesion_id" class="form-select">
                <option value="">— Ninguna —</option>
                <?php foreach ($sesiones ?? [] as $sid => $s): ?>
                    <option value="<?= $sid ?>" <?= $sesSel === $sid ? 'selected' : '' ?>>
                        <?= esc($s['titulo']) ?><?= $s['fecha_sesion'] ? ' · ' . esc($s['fecha_sesion']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Permite traer las situaciones de la sesión como escenas, enviar los bloques del rodaje como situaciones y ver su moodboard en el modo plató.</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="<?= site_url('rodajes') ?>">Volver</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>