<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= site_url('reading') ?>" class="text-decoration-none text-muted fw-normal">
        <i class="bi bi-arrow-left"></i> Lectura
    </a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold text-truncate" style="max-width: 60vw;"><?= esc($libro['title']) ?></strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="rd-summary mb-3">
    <div class="rd-summary-cover">
        <?php if (!empty($libro['cover_url'])): ?>
            <img src="<?= esc($libro['cover_url'], 'attr') ?>" alt="">
        <?php else: ?>
            <i class="bi bi-book"></i>
        <?php endif; ?>
    </div>
    <div class="rd-summary-info">
        <div class="rd-summary-title"><?= esc($libro['title']) ?></div>
        <?php if (!empty($libro['author'])): ?>
            <div class="rd-summary-author"><?= esc($libro['author']) ?></div>
        <?php endif; ?>

        <?php if ($libro['progreso'] !== null): ?>
            <div class="rd-progress mt-2">
                <div class="rd-progress-fill" style="width: <?= (int) $libro['progreso'] ?>%"></div>
            </div>
        <?php endif; ?>

        <div class="rd-summary-pages rd-page-tracker mt-2" data-book-id="<?= (int) $libro['id'] ?>">
            <label for="rdPageTracker">Voy por la pág.</label>
            <input type="number" id="rdPageTracker" class="rd-page-input" min="0"
                   <?= !empty($libro['total_pages']) ? 'max="' . (int) $libro['total_pages'] . '"' : '' ?>
                   value="<?= (int) $libro['current_page'] ?>">
            <?php if (!empty($libro['total_pages'])): ?>
                <span>/ <?= (int) $libro['total_pages'] ?></span>
            <?php endif; ?>
            <i class="bi bi-check2 rd-page-saved" id="rdPageSaved" hidden></i>
        </div>

        <?php if (!empty($libro['anchor_routine'])): ?>
            <div class="rd-anchor"><i class="bi bi-link-45deg"></i> Enganchado a: <?= esc($libro['anchor_routine']) ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Check binario del día (Capa 2: esto es lo primero que se decide, no un contador) -->
<div class="rd-today" id="rdToday">
    <?php if (!$hoy): ?>
        <div class="rd-today-card">
            <div class="rd-today-question">¿Tocaste el libro hoy?</div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary flex-fill" id="rdSiBtn">Sí</button>
                <button type="button" class="btn btn-outline-secondary flex-fill" id="rdNoBtn">No</button>
            </div>

            <div class="rd-session-form d-none mt-3" id="rdSessionForm">
                <div class="row">
                    <div class="col-6 mb-2">
                        <label class="form-label small">Minutos (opcional)</label>
                        <input type="number" class="form-control form-control-sm" id="rdMinutes" min="0">
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label small">Página alcanzada (opcional)</label>
                        <input type="number" class="form-control form-control-sm" id="rdPage" min="0">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Nota rápida (opcional)</label>
                    <input type="text" class="form-control form-control-sm" id="rdNote" maxlength="280" placeholder="una línea, lo que sea">
                </div>

                <div class="rd-capa3 d-flex align-items-center gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="rdLostThreadBtn">
                        <i class="bi bi-signpost-split"></i> Perdí el hilo
                    </button>
                    <span class="text-muted small" id="rdLostThreadCount"></span>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Aparcar un pensamiento (opcional)</label>
                    <input type="text" class="form-control form-control-sm" id="rdParkedThought" maxlength="280" placeholder="lo dejas aquí y sigues leyendo">
                </div>

                <button type="button" class="btn btn-primary w-100" id="rdSaveSessionBtn">Registrar sesión</button>
            </div>
        </div>
    <?php else: ?>
        <div class="rd-today-done">
            <?php if ($hoy['skipped']): ?>
                <i class="bi bi-check-circle"></i> Hoy has marcado "no toca". Está bien.
            <?php else: ?>
                <i class="bi bi-check-circle-fill text-success"></i> Ya has tocado este libro hoy. Eso es lo que cuenta.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->include('reading/partials/streak_widget') ?>

<!-- Estado: acción rápida e independiente de Ajustes, se guarda al toque -->
<div class="rd-section-title">Estado</div>
<div class="rd-status-picker" id="rdStatusPicker">
    <?php
    $estadosIconos = [
        'quiero_leer' => ['bi-bookmark', 'Por leer'],
        'leyendo'     => ['bi-play-fill', 'Leyendo'],
        'pausado'     => ['bi-pause-fill', 'Pausado'],
        'terminado'   => ['bi-check-circle-fill', 'Leído'],
        'abandonado'  => ['bi-x-circle', 'Dejado'],
    ];
    ?>
    <?php foreach ($estadosIconos as $valor => [$icono, $etiqueta]): ?>
        <button type="button"
                class="rd-status-btn <?= $libro['status'] === $valor ? 'active' : '' ?>"
                data-status="<?= $valor ?>" title="<?= esc($etiqueta) ?>">
            <i class="bi <?= $icono ?>"></i>
            <span><?= esc($etiqueta) ?></span>
        </button>
    <?php endforeach; ?>
</div>

<!-- Ajustes -->
<details class="rd-settings mt-4">
    <summary class="rd-settings-toggle">
        <span><i class="bi bi-gear"></i> Ajustes del libro</span>
        <i class="bi bi-chevron-down rd-settings-chevron"></i>
    </summary>
    <form action="<?= site_url('reading/libro/' . (int) $libro['id'] . '/actualizar') ?>" method="post" class="mt-3" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="mb-3 rd-buscador">
            <label for="rdBuscarQuery" class="form-label small">Buscar portada / datos (opcional)</label>
            <div class="input-group input-group-sm">
                <input type="text" id="rdBuscarQuery" class="form-control" placeholder="título o autor..." value="<?= esc($libro['title']) ?>">
                <button type="button" class="btn btn-outline-secondary" id="rdBuscarBtn"><i class="bi bi-search"></i></button>
            </div>
            <div id="rdBuscarResultados" class="rd-buscador-resultados mt-2 d-none"></div>
        </div>

        <div class="mb-3 rd-cover-preview <?= empty($libro['cover_url']) ? 'd-none' : '' ?>" id="rdCoverPreviewBox">
            <img id="rdCoverPreview" src="<?= esc($libro['cover_url'] ?? '', 'attr') ?>" alt="" class="rd-cover-preview-img">
            <button type="button" class="btn btn-sm btn-outline-danger" id="rdQuitarPortada">Quitar portada</button>
        </div>
        <input type="hidden" name="cover_url" id="cover_url" value="<?= esc($libro['cover_url'] ?? '', 'attr') ?>">
        <input type="hidden" name="isbn" id="isbn" value="<?= esc($libro['isbn'] ?? '', 'attr') ?>">

        <div class="mb-3">
            <label for="cover_image" class="form-label small">¿No aparece en la búsqueda? Sube tú la portada</label>
            <input type="file" id="cover_image" name="cover_image" class="form-control form-control-sm" accept="image/*">
        </div>

        <input type="hidden" name="status" value="<?= esc($libro['status']) ?>">

        <div class="mb-2">
            <label class="form-label small">Páginas totales</label>
            <input type="number" name="total_pages" class="form-control form-control-sm" min="1" value="<?= esc((string) ($libro['total_pages'] ?? '')) ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small">Título</label>
            <input type="text" name="title" class="form-control form-control-sm" required maxlength="255" value="<?= esc($libro['title']) ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small">Autor</label>
            <input type="text" name="author" class="form-control form-control-sm" maxlength="255" value="<?= esc($libro['author'] ?? '') ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small">Día satisfactorio (páginas mínimas)</label>
            <input type="number" name="min_goal_pages" class="form-control form-control-sm" min="1" value="<?= (int) $libro['min_goal_pages'] ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small">Rutina ancla</label>
            <input type="text" name="anchor_routine" class="form-control form-control-sm" maxlength="255" value="<?= esc($libro['anchor_routine'] ?? '') ?>">
        </div>
        <div class="mb-2 <?= $libro['status'] !== 'terminado' ? 'd-none' : '' ?>" id="rdRatingBox">
            <label class="form-label small">Valoración (1-5, opcional)</label>
            <input type="number" name="rating" class="form-control form-control-sm" min="1" max="5" value="<?= esc((string) ($libro['rating'] ?? '')) ?>">
        </div>

        <button type="submit" class="btn btn-outline-primary btn-sm mt-1">Guardar ajustes</button>
    </form>

    <form action="<?= site_url('reading/libro/' . (int) $libro['id'] . '/borrar') ?>" method="post" class="mt-3"
          onsubmit="return confirm('¿Eliminar este libro y todo su historial de lectura?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-trash"></i> Eliminar libro
        </button>
    </form>
</details>

<!-- Historial -->
<?php if (!empty($historial)): ?>
    <div class="rd-section-title">Historial reciente</div>
    <div class="rd-history">
        <?php foreach ($historial as $h): ?>
            <div class="rd-history-item">
                <div class="rd-history-date"><?= date('d/m', strtotime($h['session_date'])) ?></div>
                <div class="rd-history-body">
                    <?php if ($h['skipped']): ?>
                        <span class="text-muted">Hoy no toca</span>
                    <?php else: ?>
                        <?php $partes = []; ?>
                        <?php if ($h['page_reached']): $partes[] = 'pág. ' . (int) $h['page_reached']; endif; ?>
                        <?php if ($h['minutes']): $partes[] = (int) $h['minutes'] . ' min'; endif; ?>
                        <?= $partes ? esc(implode(' · ', $partes)) : '<span class="text-muted">Registrado</span>' ?>
                        <?php if (!empty($h['note'])): ?>
                            <div class="text-muted small">"<?= esc($h['note']) ?>"</div>
                        <?php endif; ?>
                        <?php if (!empty($h['lost_thread_count'])): ?>
                            <div class="text-muted small"><i class="bi bi-signpost-split"></i> Perdió el hilo <?= (int) $h['lost_thread_count'] ?>×</div>
                        <?php endif; ?>
                        <?php if (!empty($h['parked_thought'])): ?>
                            <div class="text-muted small fst-italic">Aparcado: "<?= esc($h['parked_thought']) ?>"</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.rd-summary { display: flex; gap: 12px; }
.rd-summary-cover {
    width: 72px;
    flex: 0 0 auto;
    aspect-ratio: 2 / 3;
    background: var(--bs-tertiary-bg);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--bs-secondary-color);
    overflow: hidden;
}
.rd-summary-cover img { width: 100%; height: 100%; object-fit: cover; }
.rd-summary-info { flex: 1 1 auto; min-width: 0; }
.rd-summary-title { font-weight: 600; font-size: 1.05rem; }
.rd-summary-author { color: var(--bs-secondary-color); font-size: .85rem; }
.rd-summary-pages { font-size: .75rem; color: var(--bs-secondary-color); margin-top: 2px; }
.rd-page-tracker { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.rd-page-tracker label { margin: 0; }
.rd-page-input {
    width: 4.5em;
    padding: 1px 6px;
    font-size: .8rem;
    border-radius: 6px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
}
.rd-page-input:focus { outline: none; border-color: var(--bs-primary); }
.rd-page-saved { color: var(--bs-success); font-size: .9rem; }
.rd-anchor { font-size: .8rem; color: var(--bs-secondary-color); margin-top: 6px; }

.rd-progress { height: 5px; border-radius: 3px; background: var(--bs-tertiary-bg); overflow: hidden; }
.rd-progress-fill { height: 100%; background: var(--bs-primary); }

.rd-today-card {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 1.25rem;
}
.rd-today-question { font-size: 1.1rem; font-weight: 600; margin-bottom: .9rem; }
.rd-today-done {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 1rem 1.25rem;
    font-size: .95rem;
}

.rd-section-title { font-weight: 600; margin: 1.25rem 0 .5rem; font-size: .95rem; }
.rd-history { display: flex; flex-direction: column; gap: 6px; }
.rd-history-item {
    display: flex;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    background: var(--bs-tertiary-bg);
}
.rd-history-date { flex: 0 0 auto; font-size: .8rem; color: var(--bs-secondary-color); width: 34px; }
.rd-history-body { font-size: .85rem; }

.rd-settings summary { cursor: pointer; }
.rd-settings-toggle {
    list-style: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: .7rem 1rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-weight: 600;
    font-size: .9rem;
}
.rd-settings-toggle::-webkit-details-marker { display: none; }
.rd-settings-toggle::marker { content: ''; }
.rd-settings-chevron { transition: transform .15s ease; color: var(--bs-secondary-color); }
.rd-settings[open] .rd-settings-toggle {
    border-radius: 12px 12px 0 0;
    border-bottom-color: transparent;
}
.rd-settings[open] .rd-settings-chevron { transform: rotate(180deg); }
.rd-settings > form:first-of-type {
    border: 1px solid var(--bs-border-color);
    border-top: none;
    border-radius: 0 0 12px 12px;
    padding: 1rem;
    margin-top: 0 !important;
}

.rd-status-picker { display: flex; gap: 4px; }
.rd-status-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    flex: 1 1 0;
    padding: 8px 2px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-secondary-color);
    font-size: .65rem;
    cursor: pointer;
}
.rd-status-btn i { font-size: 1.15rem; }
.rd-status-btn.active {
    border-color: var(--bs-primary);
    color: var(--bs-primary);
    background: var(--bs-tertiary-bg);
    font-weight: 600;
}

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
    const siBtn = document.getElementById('rdSiBtn');
    const noBtn = document.getElementById('rdNoBtn');
    if (!siBtn && !noBtn) return; // ya resuelto hoy, no hay nada que enlazar

    const sessionForm = document.getElementById('rdSessionForm');
    const lostThreadBtn = document.getElementById('rdLostThreadBtn');
    const lostThreadCountEl = document.getElementById('rdLostThreadCount');
    const saveBtn = document.getElementById('rdSaveSessionBtn');
    let lostThreadCount = 0;

    siBtn.addEventListener('click', () => {
        sessionForm.classList.remove('d-none');
        siBtn.closest('.d-flex').classList.add('d-none');
    });

    lostThreadBtn.addEventListener('click', () => {
        lostThreadCount++;
        lostThreadCountEl.textContent = lostThreadCount > 0 ? lostThreadCount + '×' : '';
    });

    async function postJSON(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
            },
            body: JSON.stringify(body),
        });
        return res.json();
    }

    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('reading/libro/' . (int) $libro['id'] . '/sesion') ?>', {
                minutes: document.getElementById('rdMinutes').value,
                page_reached: document.getElementById('rdPage').value,
                note: document.getElementById('rdNote').value,
                lost_thread_count: lostThreadCount,
                parked_thought: document.getElementById('rdParkedThought').value,
            });
            if (data.success) {
                location.reload();
            }
        } finally {
            saveBtn.disabled = false;
        }
    });

    noBtn.addEventListener('click', async () => {
        noBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('reading/libro/' . (int) $libro['id'] . '/hoy-no-toca') ?>', {});
            if (data.success) {
                location.reload();
            }
        } finally {
            noBtn.disabled = false;
        }
    });
});
</script>

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
        document.querySelector('input[name="title"]').value = libro.title || document.querySelector('input[name="title"]').value;
        if (libro.author) document.querySelector('input[name="author"]').value = libro.author;
        if (isbnInput && libro.isbn) isbnInput.value = libro.isbn;
        if (libro.total_pages) document.querySelector('input[name="total_pages"]').value = libro.total_pages;
        if (libro.cover_url) mostrarPreview(libro.cover_url);

        resultadosBox.classList.add('d-none');
        resultadosBox.innerHTML = '';
    }

    buscarBtn.addEventListener('click', buscar);
    queryInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); buscar(); }
    });

    const fileInput = document.getElementById('cover_image');
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
            previewImg.src = URL.createObjectURL(f);
            previewBox.classList.remove('d-none');
        });
    }

});
</script>

<!-- Selector de estado por iconos (play/pausa/leído/abandonado): guarda al
     toque, sin pasar por Ajustes ni por un <select>. -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const picker = document.getElementById('rdStatusPicker');
    if (!picker) return;

    picker.querySelectorAll('.rd-status-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.classList.contains('active')) return;

            picker.querySelectorAll('.rd-status-btn').forEach((b) => b.disabled = true);
            try {
                const res = await fetch('<?= site_url('reading/libro/' . (int) $libro['id'] . '/estado') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                    },
                    body: JSON.stringify({ status: btn.dataset.status }),
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    picker.querySelectorAll('.rd-status-btn').forEach((b) => b.disabled = false);
                }
            } catch (e) {
                picker.querySelectorAll('.rd-status-btn').forEach((b) => b.disabled = false);
            }
        });
    });
});
</script>

<!-- Editor inline de "por qué página voy": guarda al vuelo (blur / Enter) y
     refleja el progreso en la barra y en la task de Journal vinculada. -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tracker = document.getElementById('rdPageTracker');
    if (!tracker) return;

    const bookId = tracker.closest('.rd-page-tracker').dataset.bookId;
    const savedFlag = document.getElementById('rdPageSaved');
    const fill = document.querySelector('.rd-progress-fill');
    let ultimoGuardado = tracker.value;

    async function guardar() {
        const page = Math.max(0, parseInt(tracker.value, 10) || 0);
        if (String(page) === String(ultimoGuardado)) return;

        tracker.disabled = true;
        try {
            const res = await fetch('<?= site_url('reading/libro') ?>/' + bookId + '/pagina', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                },
                body: JSON.stringify({ page }),
            });
            const data = await res.json();
            if (data.success) {
                tracker.value = data.current_page;
                ultimoGuardado = String(data.current_page);
                if (fill && data.progreso !== null && data.progreso !== undefined) {
                    fill.style.width = data.progreso + '%';
                }
                if (savedFlag) {
                    savedFlag.hidden = false;
                    setTimeout(() => { savedFlag.hidden = true; }, 1500);
                }
            }
        } finally {
            tracker.disabled = false;
        }
    }

    tracker.addEventListener('blur', guardar);
    tracker.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); tracker.blur(); }
    });
});
</script>

<?= $this->endSection() ?>
