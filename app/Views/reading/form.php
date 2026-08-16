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

<form action="<?= site_url('reading/crear') ?>" method="post" class="rd-form" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="mb-3 rd-buscador">
        <label for="rdBuscarQuery" class="form-label">Buscar libro (opcional)</label>
        <div class="input-group">
            <input type="text" id="rdBuscarQuery" class="form-control" placeholder="título o autor...">
            <button type="button" class="btn btn-outline-secondary" id="rdBuscarBtn">
                <i class="bi bi-search"></i> Buscar
            </button>
        </div>
        <div class="form-text">Rellena título, autor, portada, ISBN y páginas (Open Library).</div>
        <div id="rdBuscarResultados" class="rd-buscador-resultados mt-2 d-none"></div>
    </div>

    <div class="mb-3 rd-cover-preview d-none" id="rdCoverPreviewBox">
        <img id="rdCoverPreview" src="" alt="" class="rd-cover-preview-img">
        <button type="button" class="btn btn-sm btn-outline-danger" id="rdQuitarPortada">Quitar portada</button>
    </div>

    <div class="mb-3">
        <label for="cover_image" class="form-label">¿No aparece en la búsqueda? Sube tú la portada</label>
        <input type="file" id="cover_image" name="cover_image" class="form-control" accept="image/*">
        <div class="form-text">Una imagen subida aquí siempre tiene prioridad sobre la portada de la búsqueda.</div>
    </div>

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

<style>
.rd-buscador-resultados {
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
}
.rd-buscador-item {
    display: flex;
    gap: 10px;
    padding: 8px 10px;
    cursor: pointer;
    border-bottom: 1px solid var(--bs-border-color);
}
.rd-buscador-item:last-child { border-bottom: none; }
.rd-buscador-item:hover { background: var(--bs-tertiary-bg); }
.rd-buscador-cover {
    width: 36px;
    flex: 0 0 auto;
    aspect-ratio: 2 / 3;
    background: var(--bs-tertiary-bg);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-secondary-color);
    overflow: hidden;
}
.rd-buscador-cover img { width: 100%; height: 100%; object-fit: cover; }
.rd-buscador-title { font-size: .85rem; font-weight: 600; }
.rd-buscador-author { font-size: .75rem; color: var(--bs-secondary-color); }
.rd-cover-preview { display: flex; align-items: center; gap: 10px; }
.rd-cover-preview-img { width: 56px; aspect-ratio: 2 / 3; object-fit: cover; border-radius: 4px; background: var(--bs-tertiary-bg); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const queryInput = document.getElementById('rdBuscarQuery');
    const buscarBtn = document.getElementById('rdBuscarBtn');
    if (!queryInput || !buscarBtn) return;

    const resultadosBox = document.getElementById('rdBuscarResultados');
    const coverInput = document.getElementById('cover_url');
    const isbnInput = document.getElementById('isbn');
    const previewBox = document.getElementById('rdCoverPreviewBox');
    const previewImg = document.getElementById('rdCoverPreview');
    const quitarBtn = document.getElementById('rdQuitarPortada');
    const fileInput = document.getElementById('cover_image');

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function mostrarPreview(url) {
        if (!coverInput) return;
        coverInput.value = url || '';
        if (url) {
            previewImg.src = url;
            previewBox.classList.remove('d-none');
        } else {
            previewImg.src = '';
            previewBox.classList.add('d-none');
        }
    }

    async function buscar() {
        const q = queryInput.value.trim();
        if (q.length < 3) return;

        buscarBtn.disabled = true;
        try {
            const res = await fetch('<?= site_url('reading/buscar') ?>?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            pintarResultados(data.resultados || []);
        } catch (e) {
            pintarResultados([]);
        } finally {
            buscarBtn.disabled = false;
        }
    }

    function pintarResultados(items) {
        if (!items.length) {
            resultadosBox.innerHTML = '<div class="text-muted small p-2">Sin resultados.</div>';
            resultadosBox.classList.remove('d-none');
            return;
        }

        resultadosBox.innerHTML = items.map((it, i) => `
            <div class="rd-buscador-item" data-i="${i}">
                <div class="rd-buscador-cover">${it.cover_url ? `<img src="${it.cover_url}" alt="">` : '<i class="bi bi-book"></i>'}</div>
                <div class="rd-buscador-info">
                    <div class="rd-buscador-title">${escapeHtml(it.title)}</div>
                    ${it.author ? `<div class="rd-buscador-author">${escapeHtml(it.author)}</div>` : ''}
                </div>
            </div>
        `).join('');
        resultadosBox.classList.remove('d-none');

        resultadosBox.querySelectorAll('.rd-buscador-item').forEach((el) => {
            el.addEventListener('click', () => elegir(items[+el.dataset.i]));
        });
    }

    function elegir(libro) {
        document.getElementById('title').value = libro.title || document.getElementById('title').value;
        if (libro.author) document.getElementById('author').value = libro.author;
        if (isbnInput && libro.isbn) isbnInput.value = libro.isbn;
        if (libro.total_pages) document.getElementById('total_pages').value = libro.total_pages;
        if (libro.cover_url) mostrarPreview(libro.cover_url);

        resultadosBox.classList.add('d-none');
        resultadosBox.innerHTML = '';
    }

    buscarBtn.addEventListener('click', buscar);
    queryInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); buscar(); }
    });

    if (quitarBtn) {
        quitarBtn.addEventListener('click', () => {
            mostrarPreview('');
            if (fileInput) fileInput.value = '';
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            const f = fileInput.files[0];
            if (!f) return;
            // Solo previsualiza: el cover_url de texto se deja tal cual, el
            // archivo subido gana prioridad igualmente al guardar.
            previewImg.src = URL.createObjectURL(f);
            previewBox.classList.remove('d-none');
        });
    }
});
</script>

<?= $this->endSection() ?>
