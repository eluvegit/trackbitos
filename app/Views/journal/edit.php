<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="jt-header mb-3">
    <a href="<?= site_url('journal') ?>" class="jt-back"><i class="bi bi-chevron-left"></i> Journal</a>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if (!empty($task['image'])): ?>
    <div class="jt-image mb-3">
        <img src="<?= base_url($task['image']) ?>" alt="Imagen actual">
        <form action="<?= site_url('journal/delete-image/' . $task['id']) ?>" method="post"
              onsubmit="return confirm('¿Eliminar esta imagen?')" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="jt-image-remove" title="Eliminar imagen">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    </div>
<?php endif; ?>

<form action="<?= site_url('journal/edit/' . $task['id']) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Título + estrella -->
    <div class="jt-title-row mb-3">
        <button type="button" class="jt-star" id="starBtn" aria-pressed="<?= !empty($task['is_current']) ? 'true' : 'false' ?>" title="Marcar como actual">
            <i class="bi <?= !empty($task['is_current']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
        </button>
        <input type="text" name="title" id="title" class="jt-title-input"
               value="<?= esc($task['title'] ?? '') ?>" placeholder="Título" required>
        <input type="checkbox" name="is_current" id="is_current" value="1" class="d-none"
               <?= !empty($task['is_current']) ? 'checked' : '' ?>>
    </div>

    <!-- Periodo -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Periodo</div>
        <div class="row g-2">
            <div class="col-6">
                <label for="start_time" class="jt-label">Inicio</label>
                <input type="date" name="start_time" id="start_time" class="form-control"
                       value="<?= !empty($task['start_time']) && $task['start_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['start_time'])) : '' ?>">
            </div>
            <div class="col-6">
                <label for="end_time" class="jt-label">Fin</label>
                <input type="date" name="end_time" id="end_time" class="form-control"
                       value="<?= !empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['end_time'])) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Progreso -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Progreso</div>
        <div class="row g-2">
            <div class="col-6">
                <label for="amplitude" class="jt-label">Amplitud (total)</label>
                <input type="number" name="amplitude" id="amplitude" class="form-control"
                       min="1" required value="<?= esc($task['amplitude'] ?? '') ?>" placeholder="Ej. 10">
            </div>
            <div class="col-6">
                <label for="completed" class="jt-label">Completados</label>
                <input type="number" name="completed" id="completed" class="form-control"
                       min="0" value="<?= esc($task['completed'] ?? '') ?>" placeholder="Ej. 4">
            </div>
        </div>
        <div class="jt-progress mt-2">
            <div class="jt-progress-bar" id="jtProgressBar" style="width:0%"></div>
        </div>
        <div class="jt-hint" id="jtProgressLabel">0%</div>
    </div>

    <!-- Tiempo invertido -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Tiempo invertido</div>
        <div class="input-group">
            <input type="number" name="time_spent" id="time_spent" class="form-control" min="0"
                   value="<?= esc($task['time_spent'] ?? '') ?>">
            <span class="input-group-text">min</span>
        </div>
        <div class="jt-hint" id="timeHint">= 0.00 h</div>
    </div>

    <!-- Nota -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Nota</div>
        <textarea name="note" id="note" class="form-control" rows="3" placeholder="Nota"><?= esc($task['note'] ?? '') ?></textarea>
    </div>

    <!-- Imagen opcional -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Imagen</div>
        <input type="file" name="image" id="image" class="form-control">
    </div>

    <div class="d-flex gap-2 mb-3">
        <a href="<?= site_url('journal') ?>" class="btn btn-outline-secondary flex-fill">Cancelar</a>
        <button type="submit" class="btn btn-primary flex-fill">Guardar</button>
    </div>
</form>

<form action="<?= site_url('journal/delete/' . $task['id']) ?>" method="post" class="mb-4"
      onsubmit="return confirm('¿Seguro que quieres eliminar esta tarea?');">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-trash"></i> Eliminar tarea
    </button>
</form>

<!-- Historial de fechas -->
<div class="jt-section">
    <div class="jt-section-title">Historial de fechas</div>

    <?php if (empty($logs)): ?>
        <p class="text-muted small mb-0">Sin registros todavía.</p>
    <?php else: ?>
        <div class="jt-log-list">
            <?php foreach ($logs as $log): ?>
                <button type="button" class="jt-log-item js-edit-log"
                        data-id="<?= (int)$log['id'] ?>"
                        data-date="<?= esc($log['log_date']) ?>"
                        data-minutes="<?= (int)$log['minutes'] ?>">
                    <span class="jt-log-date"><?= date('d/m/Y', strtotime($log['log_date'])) ?></span>
                    <span class="jt-log-minutes"><?= (int)$log['minutes'] ?> min</span>
                    <i class="bi bi-pencil jt-log-edit-icon"></i>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: editar registro del historial -->
<div class="modal fade" id="modalEditLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="logId">
                <div class="mb-2">
                    <label for="logDate" class="form-label">Fecha</label>
                    <input type="date" id="logDate" class="form-control">
                </div>
                <div class="mb-2">
                    <label for="logMinutes" class="form-label">Minutos</label>
                    <input type="number" id="logMinutes" class="form-control" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveLogBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<style>
.jt-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.jt-back:hover { color: var(--bs-emphasis-color); }

.jt-image {
    position: relative;
    display: inline-block;
}
.jt-image img {
    max-width: 140px;
    max-height: 140px;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    display: block;
}
.jt-image-remove {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: #dc3545;
    color: #fff;
}

.jt-title-row { display: flex; align-items: center; gap: 8px; }
.jt-star {
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: #adb5bd;
    font-size: 1.15rem;
    cursor: pointer;
}
.jt-star[aria-pressed="true"] {
    color: #ffc107;
    border-color: rgba(255,193,7,.4);
    background: rgba(255,193,7,.12);
}
.jt-title-input {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 1.05rem;
    font-weight: 700;
    padding: .55rem .8rem;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
}
.jt-title-input:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 .2rem rgba(124,58,237,.2);
}

.jt-section {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 12px 14px;
}
.jt-section-title {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--bs-secondary-color);
    font-weight: 700;
    margin-bottom: 8px;
}
.jt-label {
    font-size: .74rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.jt-progress {
    height: 8px;
    border-radius: 999px;
    background: rgba(124,58,237,.12);
    overflow: hidden;
}
.jt-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
    transition: width .2s ease;
}
.jt-hint {
    margin-top: 4px;
    font-size: .74rem;
    color: var(--bs-secondary-color);
}

.jt-log-list { display: flex; flex-direction: column; gap: 6px; }
.jt-log-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
    text-align: left;
    cursor: pointer;
}
.jt-log-item:hover { background: var(--bs-tertiary-bg); }
.jt-log-date { font-weight: 600; font-size: .85rem; }
.jt-log-minutes { font-size: .8rem; color: var(--bs-secondary-color); margin-left: auto; }
.jt-log-edit-icon { color: var(--bs-secondary-color); font-size: .8rem; }

@media (max-width: 400px) {
    .jt-title-input { font-size: 1rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Estrella ---
    const starBtn = document.getElementById('starBtn');
    const isCurrentInput = document.getElementById('is_current');
    starBtn.addEventListener('click', () => {
        const nuevo = !isCurrentInput.checked;
        isCurrentInput.checked = nuevo;
        starBtn.setAttribute('aria-pressed', nuevo ? 'true' : 'false');
        starBtn.querySelector('i').className = nuevo ? 'bi bi-star-fill' : 'bi bi-star';
    });

    // --- Amplitud / Completados: máximo sincronizado en vivo + barra de progreso ---
    const amplitudeInput = document.getElementById('amplitude');
    const completedInput = document.getElementById('completed');
    const progressBar = document.getElementById('jtProgressBar');
    const progressLabel = document.getElementById('jtProgressLabel');

    function actualizarProgreso() {
        const amplitude = parseInt(amplitudeInput.value, 10) || 0;
        let completed = parseInt(completedInput.value, 10) || 0;

        completedInput.max = amplitude || '';
        if (amplitude && completed > amplitude) {
            completed = amplitude;
            completedInput.value = amplitude;
        }

        const pct = amplitude > 0 ? Math.min(100, Math.round((completed / amplitude) * 100)) : 0;
        progressBar.style.width = pct + '%';
        progressLabel.textContent = pct + '%';
    }
    amplitudeInput.addEventListener('input', actualizarProgreso);
    completedInput.addEventListener('input', actualizarProgreso);
    actualizarProgreso();

    // --- Tiempo invertido -> horas ---
    const timeInput = document.getElementById('time_spent');
    const timeHint = document.getElementById('timeHint');
    function actualizarHint() {
        const mins = parseInt(timeInput.value, 10) || 0;
        timeHint.textContent = '= ' + (mins / 60).toFixed(2) + ' h';
    }
    timeInput.addEventListener('input', actualizarHint);
    actualizarHint();

    // --- Editar registro del historial ---
    const modalEl = document.getElementById('modalEditLog');
    const modal = new bootstrap.Modal(modalEl);
    const logIdInput = document.getElementById('logId');
    const logDateInput = document.getElementById('logDate');
    const logMinutesInput = document.getElementById('logMinutes');
    const saveLogBtn = document.getElementById('saveLogBtn');

    document.querySelectorAll('.js-edit-log').forEach(btn => {
        btn.addEventListener('click', () => {
            logIdInput.value = btn.dataset.id;
            logDateInput.value = btn.dataset.date;
            logMinutesInput.value = btn.dataset.minutes;
            modal.show();
        });
    });

    saveLogBtn.addEventListener('click', async () => {
        const id = logIdInput.value;
        saveLogBtn.disabled = true;

        try {
            const res = await fetch('<?= site_url('journal/update-log') ?>/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                },
                body: JSON.stringify({
                    log_date: logDateInput.value,
                    minutes: parseInt(logMinutesInput.value, 10) || 0,
                }),
            });
            const data = await res.json();
            if (!data.success) throw new Error();

            const item = document.querySelector('.js-edit-log[data-id="' + id + '"]');
            item.dataset.date = logDateInput.value;
            item.dataset.minutes = logMinutesInput.value;
            const [y, m, d] = logDateInput.value.split('-');
            item.querySelector('.jt-log-date').textContent = d + '/' + m + '/' + y;
            item.querySelector('.jt-log-minutes').textContent = (parseInt(logMinutesInput.value, 10) || 0) + ' min';

            modal.hide();
        } catch (err) {
            alert('No se pudo guardar el cambio.');
        } finally {
            saveLogBtn.disabled = false;
        }
    });
});
</script>

<?= $this->endSection() ?>
