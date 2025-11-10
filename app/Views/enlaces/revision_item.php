<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>

<div class="container py-3">

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form action="<?= site_url('enlaces/revision/guardar/' . $item['id']) ?>" method="post" class="border rounded p-3">
        <?= csrf_field() ?>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0">Revisar enlace #<?= (int)$item['id'] ?></h5>
            <div class="d-flex gap-2">
                <?php if ($nextId): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/revision/item/' . $nextId) ?>">Siguiente</a>
                <?php endif; ?>

                <button type="submit"
                    class="btn btn-outline-danger btn-sm"
                    formaction="<?= site_url('enlaces/revision/borrar/' . $item['id']) ?>"
                    formnovalidate
                    data-action="borrar"
                    onclick="return confirm('¿Eliminar este enlace?')"
                    title="D">Borrar</button>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/revision') ?>">Salir</a>
            </div>
        </div>

        <div class="mb-2">
            <div class="small text-muted">URL</div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?= esc($item['url']) ?>" target="_blank" class="text-break flex-grow-1"><?= esc($item['url']) ?></a>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-sm-8">
                <label class="form-label mb-1">Título <span class="text-danger">*</span></label>
                <input type="text" name="titulo" class="form-control form-control-sm" required value="<?= esc($item['titulo'] ?? '') ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label mb-1">Relev.</label>
                <select name="relevancia" class="form-select form-select-sm">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= (int)($item['relevancia'] ?? 3) === $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label mb-1">Fecha</label>
                <input type="date" name="fecha" class="form-control form-control-sm" value="<?= esc(substr($item['fecha'] ?? date('Y-m-d'), 0, 10)) ?>">
            </div>
        </div>

        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="visto" id="visto" <?= !empty($item['visto']) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="visto">Marcado como visto</label>
        </div>

        <div class="mt-2">
            <label class="form-label mb-1">Notas</label>
            <textarea name="extra" class="form-control form-control-sm" rows="2"><?= esc($item['extra'] ?? '') ?></textarea>
        </div>

        <div class="mt-2">
            <label class="form-label mb-1">Categorías</label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($categorias as $c): ?>
                    <label class="border rounded px-2 py-1 small mb-0">
                        <input type="checkbox" name="categorias[]" value="<?= (int)$c['id'] ?>"> <?= esc($c['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-2">
            <label class="form-label mb-1">Etiquetas (coma-separado)</label>
            <input type="text" name="etiquetas" class="form-control form-control-sm" placeholder="ej: ia, lectura, dev">
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-success btn-sm" data-action="guardar" title="S">Guardar y siguiente</button>
            <button type="submit"
                class="btn btn-secondary btn-sm"
                formaction="<?= site_url('enlaces/revision/saltar/' . $item['id']) ?>"
                formnovalidate
                data-action="saltar"
                title="N">Saltar</button>
            <button type="submit"
                class="btn btn-outline-danger btn-sm"
                formaction="<?= site_url('enlaces/revision/borrar/' . $item['id']) ?>"
                formnovalidate
                data-action="borrar"
                onclick="return confirm('¿Eliminar este enlace?')"
                title="D">Borrar</button>
        </div>
    </form>
</div>

<script>
    // Atajos: S (guardar), D (borrar), N (saltar)
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        const btnGuardar = document.querySelector('button[data-action="guardar"]');
        const btnBorrar = document.querySelector('button[data-action="borrar"]');
        const btnSaltar = document.querySelector('button[data-action="saltar"]');

        if (e.key.toLowerCase() === 's' && btnGuardar) {
            e.preventDefault();
            btnGuardar.click();
        } else if (e.key.toLowerCase() === 'd' && btnBorrar) {
            e.preventDefault();
            btnBorrar.click();
        } else if (e.key.toLowerCase() === 'n' && btnSaltar) {
            e.preventDefault();
            btnSaltar.click();
        }
    });
</script>

<?php $this->endSection(); ?>