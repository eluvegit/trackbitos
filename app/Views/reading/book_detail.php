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
            <div class="rd-summary-pages"><?= (int) $libro['current_page'] ?> / <?= (int) $libro['total_pages'] ?> páginas</div>
        <?php endif; ?>

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

<!-- Ajustes -->
<details class="rd-settings mt-4">
    <summary class="text-muted small">Ajustes del libro</summary>
    <form action="<?= site_url('reading/libro/' . (int) $libro['id'] . '/actualizar') ?>" method="post" class="mt-3">
        <?= csrf_field() ?>
        <div class="row">
            <div class="col-6 mb-2">
                <label class="form-label small">Estado</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="quiero_leer" <?= $libro['status'] === 'quiero_leer' ? 'selected' : '' ?>>Quiero leer</option>
                    <option value="leyendo" <?= $libro['status'] === 'leyendo' ? 'selected' : '' ?>>Leyendo</option>
                    <option value="pausado" <?= $libro['status'] === 'pausado' ? 'selected' : '' ?>>Pausado</option>
                    <option value="terminado" <?= $libro['status'] === 'terminado' ? 'selected' : '' ?>>Terminado</option>
                    <option value="abandonado" <?= $libro['status'] === 'abandonado' ? 'selected' : '' ?>>Abandonado</option>
                </select>
            </div>
            <div class="col-6 mb-2">
                <label class="form-label small">Páginas totales</label>
                <input type="number" name="total_pages" class="form-control form-control-sm" min="1" value="<?= esc((string) ($libro['total_pages'] ?? '')) ?>">
            </div>
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
        <?php if ($libro['status'] === 'terminado'): ?>
            <div class="mb-2">
                <label class="form-label small">Valoración (1-5, opcional)</label>
                <input type="number" name="rating" class="form-control form-control-sm" min="1" max="5" value="<?= esc((string) ($libro['rating'] ?? '')) ?>">
            </div>
        <?php endif; ?>

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

<?= $this->endSection() ?>
