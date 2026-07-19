<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">
    <i class="bi bi-calendar-heart text-primary"></i>
    <?= isset($recordatorio) ? 'Editar recordatorio' : 'Nuevo recordatorio' ?>
</h5>

<a href="<?= site_url('recordatorios') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<?php $categoriaActual = $recordatorio['categoria'] ?? 'otro'; ?>

<form method="post" action="<?= isset($recordatorio)
    ? site_url('recordatorios/actualizar/' . $recordatorio['id'])
    : site_url('recordatorios/crear') ?>" style="max-width: 480px;">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="titulo" class="form-label">Título</label>
        <input type="text" class="form-control" name="titulo" id="titulo" required
               placeholder="Ej. ITV, Reconocimiento médico, Vacunas del perro..."
               value="<?= esc($recordatorio['titulo'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="categoria" class="form-label">Categoría</label>
        <select class="form-select" name="categoria" id="categoria">
            <?php foreach ($categorias as $value => [$label, $icono]): ?>
                <option value="<?= esc($value) ?>" data-icono="<?= esc($icono) ?>" <?= $categoriaActual === $value ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">
            <?php $iconoActual = $categorias[$categoriaActual][1]; ?>
            <span id="icono-preview" class="fs-4 text-primary">
                <?php if (recordatorio_es_icono_bootstrap($iconoActual)): ?>
                    <i class="bi bi-<?= esc($iconoActual) ?>"></i>
                <?php else: ?>
                    <?= esc($iconoActual) ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-7 mb-3">
            <label for="fecha_evento" class="form-label">Fecha</label>
            <input type="date" class="form-control" name="fecha_evento" id="fecha_evento" required
                   value="<?= esc($recordatorio['fecha_evento'] ?? '') ?>">
        </div>
        <div class="col-sm-5 mb-3">
            <label for="periodo_meses" class="form-label">Se repite cada (meses)</label>
            <input type="number" min="1" class="form-control" name="periodo_meses" id="periodo_meses"
                   placeholder="Ej. 12" value="<?= esc($recordatorio['periodo_meses'] ?? '') ?>">
        </div>
    </div>
    <div class="form-text mb-3 mt-n2">
        Si lo rellenas, podrás usar el botón "Renovar" para poner la fecha a hoy + estos meses sin tener que editarla a mano.
    </div>

    <div class="mb-3">
        <label for="notas" class="form-label">Notas (opcional)</label>
        <textarea class="form-control" name="notas" id="notas" rows="3"><?= esc($recordatorio['notas'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">
        <?= isset($recordatorio) ? 'Guardar cambios' : 'Crear recordatorio' ?>
    </button>
</form>

<?php if (isset($recordatorio)): ?>
    <form method="post" action="<?= site_url('recordatorios/borrar/' . $recordatorio['id']) ?>" class="mt-3"
          onsubmit="return confirm('¿Eliminar este recordatorio?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-trash"></i> Eliminar
        </button>
    </form>
<?php endif; ?>

<script>
    document.getElementById('categoria')?.addEventListener('change', (e) => {
        const icono = e.target.selectedOptions[0].dataset.icono;
        const esBootstrap = /^[a-z0-9-]+$/.test(icono);
        document.getElementById('icono-preview').innerHTML = esBootstrap
            ? '<i class="bi bi-' + icono + '"></i>'
            : icono;
    });
</script>

<?= $this->endSection() ?>
