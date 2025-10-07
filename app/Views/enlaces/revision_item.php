<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Revisar enlace #<?= (int)$item['id'] ?></h5>
        <div class="d-flex gap-2">
            <?php if ($nextId): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/revision/item/' . $nextId) ?>">Siguiente</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/revision') ?>">Salir</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="border rounded p-3">
                <div class="mb-2">
                    <div class="small text-muted">URL</div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= esc($item['url']) ?>" target="_blank" class="text-break"><?= esc($item['url']) ?></a>
                        <a class="btn btn-sm btn-outline-primary" href="<?= esc($item['url']) ?>" target="_blank">Abrir 🔗</a>
                    </div>
                </div>

                <form action="<?= site_url('enlaces/revision/guardar/' . $item['id']) ?>" method="post" class="mt-3">
                    <?= csrf_field() ?>

                    <div class="mb-2">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" class="form-control" required value="<?= esc($item['titulo'] ?? '') ?>">
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Relevancia</label>
                            <select name="relevancia" class="form-select">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int)($item['relevancia'] ?? 3) === $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?= esc(substr($item['fecha'] ?? date('Y-m-d'), 0, 10)) ?>">
                        </div>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="visto" id="visto" <?= !empty($item['visto']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="visto">Marcado como visto</label>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label">Notas</label>
                        <textarea name="extra" class="form-control" rows="3"><?= esc($item['extra'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Categorías</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($categorias as $c): ?>
                                <label class="border rounded px-2 py-1 small">
                                    <input type="checkbox" name="categorias[]" value="<?= (int)$c['id'] ?>"> <?= esc($c['nombre']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Etiquetas (coma-separado)</label>
                        <input type="text" name="etiquetas" class="form-control" placeholder="ej: ia, lectura, dev">
                    </div>

                    <div class="d-flex gap-2">
                        <!-- Guardar: valida (required activo) -->
                        <button type="submit" class="btn btn-success" data-action="guardar" title="S">
                            Guardar y siguiente
                        </button>

                        <!-- Saltar: NO valida -->
                        <button type="submit"
                            class="btn btn-secondary"
                            formaction="<?= site_url('enlaces/revision/saltar/' . $item['id']) ?>"
                            formnovalidate
                            data-action="saltar"
                            title="N">
                            Saltar
                        </button>

                        <!-- Borrar: NO valida -->
                        <button type="submit"
                            class="btn btn-outline-danger"
                            formaction="<?= site_url('enlaces/revision/borrar/' . $item['id']) ?>"
                            formnovalidate
                            data-action="borrar"
                            onclick="return confirm('¿Eliminar este enlace?')"
                            title="D">
                            Borrar
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="border rounded p-2">
                <div class="small text-muted mb-2">Vista previa (iframe)</div>
                <div class="ratio ratio-16x9">
                    <iframe src="<?= esc($item['url']) ?>" loading="lazy" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" referrerpolicy="no-referrer"></iframe>
                </div>
                <div class="small text-muted mt-2">* Puede que algunas webs bloqueen el iframe; usa “Abrir 🔗”.</div>
            </div>
        </div>
    </div>
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
            btnGuardar.click(); // valida
        } else if (e.key.toLowerCase() === 'd' && btnBorrar) {
            e.preventDefault();
            btnBorrar.click(); // NO valida (formnovalidate)
        } else if (e.key.toLowerCase() === 'n' && btnSaltar) {
            e.preventDefault();
            btnSaltar.click(); // NO valida (formnovalidate)
        }
    });
</script>

<?php $this->endSection(); ?>