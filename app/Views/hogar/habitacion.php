<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php $hayHechas = count(array_filter($tareas, fn($t) => (int) $t['estado'] === 1)) > 0; ?>

<div class="hogar-header mb-3">
    <a href="<?= site_url('hogar') ?>" class="hogar-back"><i class="bi bi-chevron-left"></i> Hogar</a>
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mt-1">
        <h2 class="hogar-title mb-0">
            <i class="bi bi-<?= esc($habitacion['icono'] ?: 'house') ?> text-primary"></i>
            <?= esc($habitacion['nombre']) ?>
        </h2>

        <?php if ($hayHechas): ?>
            <button type="button" id="btnRenovarTodo" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-clockwise"></i> Renovar todo
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="tarea-list" id="tareaList" data-habitacion-id="<?= (int)$habitacion['id'] ?>">
    <?php foreach ($tareas as $t): ?>
        <?php $isHecha = (int) $t['estado'] === 1; ?>
        <div class="tarea-item <?= $isHecha ? 'is-hecha' : '' ?> <?= $t['atrasada'] ? 'is-atrasada' : '' ?>"
             data-id="<?= (int)$t['id'] ?>">

            <span class="tarea-handle" title="Arrastrar para reordenar">
                <i class="bi bi-grip-vertical"></i>
            </span>

            <button type="button" class="tarea-check js-marcar" data-id="<?= (int)$t['id'] ?>"
                    <?= $isHecha ? 'disabled' : '' ?> aria-label="Marcar como hecha">
                <i class="bi <?= $isHecha ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
            </button>

            <div class="tarea-body">
                <div class="tarea-nombre"><?= esc($t['nombre']) ?></div>
                <div class="tarea-meta">
                    <span class="tarea-tiempo"><?= esc($t['tiempo_relativo']) ?></span>
                    <?php if ($t['frecuencia_dias']): ?>
                        <span class="tarea-frecuencia">· cada <?= (int)$t['frecuencia_dias'] ?> día<?= (int)$t['frecuencia_dias'] === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                    <?php if ($t['atrasada']): ?>
                        <span class="tarea-atrasada-tag"><i class="bi bi-exclamation-triangle-fill"></i> Toca hacerla</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tarea-actions">
                <?php if ($isHecha): ?>
                    <button type="button" class="tarea-btn js-renovar" data-id="<?= (int)$t['id'] ?>" title="Renovar (volver a pendiente)">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                <?php endif; ?>
                <a href="<?= site_url('hogar/tareas/' . $t['id'] . '/historial') ?>" class="tarea-btn" title="Ver historial">
                    <i class="bi bi-clock-history"></i>
                </a>
                <a href="<?= site_url('hogar/tareas/editar/' . $t['id']) ?>" class="tarea-btn" title="Editar">
                    <i class="bi bi-pencil"></i>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($tareas)): ?>
    <p class="text-muted">Todavía no hay tareas en esta habitación. Añade la primera abajo.</p>
<?php endif; ?>

<!-- Añadir tarea -->
<form method="post" action="<?= site_url('hogar/tareas/crear') ?>" class="tarea-nueva mt-3">
    <?= csrf_field() ?>
    <input type="hidden" name="habitacion_id" value="<?= (int)$habitacion['id'] ?>">

    <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nueva tarea..." required style="max-width: 220px;">
    <input type="number" name="frecuencia_dias" min="1" class="form-control form-control-sm" placeholder="cada X días" style="max-width: 130px;">
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i> Añadir
    </button>
</form>

<style>
.hogar-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.hogar-back:hover { color: var(--bs-emphasis-color); }

.hogar-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.tarea-list { display: flex; flex-direction: column; gap: 8px; }

.tarea-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 14px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    transition: opacity .15s ease, border-color .15s ease, background-color .15s ease;
}
.tarea-item.sortable-ghost { opacity: .3; }
.tarea-item.sortable-chosen { background: var(--bs-tertiary-bg); }

.tarea-item.is-hecha { opacity: .6; }

.tarea-item.is-atrasada {
    border-color: rgba(220,53,69,.4);
    background: rgba(220,53,69,.06);
}

.tarea-handle {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 30px;
    height: 38px;
    color: var(--bs-secondary-color);
    cursor: grab;
    touch-action: none;
}
.tarea-handle:active { cursor: grabbing; }

.tarea-check {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    font-size: 1.1rem;
    cursor: pointer;
}
.tarea-check:hover:not(:disabled) { filter: brightness(1.2); }
.tarea-check:disabled {
    color: #10b981;
    border-color: rgba(16,185,129,.4);
    background: rgba(16,185,129,.12);
    cursor: default;
}

.tarea-body { flex: 1 1 auto; min-width: 0; }

.tarea-nombre {
    font-weight: 600;
    font-size: .95rem;
    color: var(--bs-emphasis-color);
}
.tarea-item.is-hecha .tarea-nombre { font-weight: 400; color: var(--bs-secondary-color); }

.tarea-meta { font-size: .75rem; color: var(--bs-secondary-color); display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; }
.tarea-item.is-atrasada .tarea-tiempo { color: #dc3545; font-weight: 600; }
.tarea-atrasada-tag { color: #dc3545; font-weight: 600; display: inline-flex; align-items: center; gap: .25rem; }

.tarea-actions { flex: 0 0 auto; display: flex; align-items: center; gap: 4px; }

.tarea-btn {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    text-decoration: none;
    cursor: pointer;
}
.tarea-btn:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }

.tarea-nueva {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px dashed var(--bs-border-color);
}

@media (max-width: 575.98px) {
    .hogar-title { font-size: 1.15rem; }
    .tarea-item { padding: 8px; gap: 6px; }
    .tarea-check, .tarea-btn { width: 34px; height: 34px; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const list = document.getElementById('tareaList');
    if (!list) return;
    const habitacionId = list.dataset.habitacionId;

    async function post(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                'Content-Type': 'application/json',
            },
        });
    }

    function aplicarEstado(item, data) {
        const esHecha = data.estado === 1;
        item.classList.toggle('is-hecha', esHecha);
        item.classList.toggle('is-atrasada', !!data.atrasada);

        const check = item.querySelector('.tarea-check');
        check.disabled = esHecha;
        check.querySelector('i').className = esHecha ? 'bi bi-check-circle-fill' : 'bi bi-circle';

        const tiempo = item.querySelector('.tarea-tiempo');
        if (tiempo) tiempo.textContent = data.tiempo_relativo;

        let renovarBtn = item.querySelector('.js-renovar');
        if (esHecha && !renovarBtn) {
            renovarBtn = document.createElement('button');
            renovarBtn.type = 'button';
            renovarBtn.className = 'tarea-btn js-renovar';
            renovarBtn.dataset.id = item.dataset.id;
            renovarBtn.title = 'Renovar (volver a pendiente)';
            renovarBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            item.querySelector('.tarea-actions').prepend(renovarBtn);
        } else if (!esHecha && renovarBtn) {
            renovarBtn.remove();
        }
    }

    list.addEventListener('click', async (e) => {
        const marcarBtn = e.target.closest('.js-marcar');
        if (marcarBtn) {
            const item = marcarBtn.closest('.tarea-item');
            const res = await post('<?= site_url('hogar/tareas') ?>/' + marcarBtn.dataset.id + '/marcar');
            if (!res.ok) return;
            const data = await res.json();
            aplicarEstado(item, data);
            return;
        }

        const renovarBtn = e.target.closest('.js-renovar');
        if (renovarBtn) {
            const item = renovarBtn.closest('.tarea-item');
            const res = await post('<?= site_url('hogar/tareas') ?>/' + renovarBtn.dataset.id + '/renovar');
            if (!res.ok) return;
            const data = await res.json();
            aplicarEstado(item, data);
        }
    });

    const btnRenovarTodo = document.getElementById('btnRenovarTodo');
    if (btnRenovarTodo) {
        btnRenovarTodo.addEventListener('click', async () => {
            if (!confirm('¿Renovar todas las tareas marcadas de esta habitación?')) return;

            const res = await post('<?= site_url('hogar') ?>/' + habitacionId + '/renovar-todo');
            if (!res.ok) return;

            list.querySelectorAll('.tarea-item.is-hecha').forEach(item => {
                aplicarEstado(item, { estado: 0, tiempo_relativo: item.querySelector('.tarea-tiempo').textContent, atrasada: item.classList.contains('is-atrasada') });
            });
            btnRenovarTodo.remove();
        });
    }

    // Reordenar
    Sortable.create(list, {
        handle: '.tarea-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: () => {
            const orden = [...list.querySelectorAll('.tarea-item')].map(item => item.dataset.id);
            fetch('<?= site_url('hogar/tareas/reordenar') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ orden }),
            });
        },
    });
})();
</script>

<?= $this->endSection() ?>
