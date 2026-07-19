<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
if (!function_exists('qh_tiempo_relativo')) {
    function qh_tiempo_relativo(int $dias): string
    {
        if ($dias < 1) return 'hoy';
        if ($dias < 7) return "hace {$dias} días";
        if ($dias < 30) return 'hace ' . floor($dias / 7) . ' sem';
        if ($dias < 365) return 'hace ' . floor($dias / 30) . ' meses';
        return 'hace ' . floor($dias / 365) . ' años';
    }
}
?>

<div class="qh-header mb-3">
    <a href="<?= site_url('journal') ?>" class="qh-back"><i class="bi bi-chevron-left"></i> Journal</a>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-1">
        <h2 class="qh-title mb-0"><i class="bi bi-shuffle text-primary"></i> ¿Qué hago ahora?</h2>
        <a href="<?= site_url('journal/que-hacer') ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-clockwise"></i> Otra ronda
        </a>
    </div>
    <p class="text-muted small mb-0 mt-1">
        Categorías elegidas al azar, dando más papeletas a las que llevan más tiempo sin tocarse y a las que más te importan.
    </p>
</div>

<?php if (empty($sugeridos)): ?>
    <p class="text-muted">No hay categorías disponibles para sugerir (revisa que alguna tenga peso &gt; 0 y tareas).</p>
<?php endif; ?>

<div class="qh-grid">
    <?php foreach ($sugeridos as $s): ?>
        <?php $cat = $s['categoria']; ?>
        <div class="qh-card">
            <div class="qh-card-header" style="border-left: 4px solid <?= esc($cat['color'] ?: '#7c3aed') ?>;">
                <div class="qh-card-title-row">
                    <strong class="qh-card-title"><?= esc($cat['name']) ?></strong>
                    <span class="qh-card-tiempo"><?= qh_tiempo_relativo($s['dias']) ?></span>
                </div>

                <div class="qh-peso" data-cat-id="<?= (int)$cat['id'] ?>">
                    <span class="qh-peso-label">Peso</span>
                    <button type="button" class="qh-peso-btn" data-delta="-1">−</button>
                    <span class="qh-peso-value"><?= (int)$cat['peso'] ?></span>
                    <button type="button" class="qh-peso-btn" data-delta="1">+</button>
                </div>
            </div>

            <div class="qh-task-list">
                <?php foreach ($s['tareas_muestra'] as $t): ?>
                    <div class="qh-task" data-task-id="<?= (int)$t['id'] ?>">
                        <button type="button" class="qh-star js-toggle-current" data-task-id="<?= (int)$t['id'] ?>">
                            <i class="bi <?= !empty($t['is_current']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                        </button>
                        <a href="<?= site_url('journal/edit/' . $t['id']) ?>" class="qh-task-title">
                            <?= esc($t['title']) ?>
                        </a>
                        <span class="qh-task-time"><?= number_format(($t['time_spent'] ?? 0) / 60, 1) ?>h</span>
                    </div>
                <?php endforeach; ?>

                <?php if ($s['tareas_total'] > count($s['tareas_muestra'])): ?>
                    <a href="<?= site_url('journal') ?>" class="qh-task-more">
                        + <?= $s['tareas_total'] - count($s['tareas_muestra']) ?> más en esta categoría
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Panel: todas las categorías (para poder excluir/ajustar aunque no salgan sugeridas) -->
<div class="qh-all mt-4">
    <button class="qh-all-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#qhAllList">
        <i class="bi bi-sliders"></i> Ajustar pesos de todas las categorías
        <i class="bi bi-chevron-down qh-all-chevron"></i>
    </button>

    <div class="collapse" id="qhAllList">
        <div class="qh-all-list mt-2">
            <?php foreach ($categorias as $cat): ?>
                <div class="qh-all-item">
                    <span class="qh-all-dot" style="background: <?= esc($cat['color'] ?: '#7c3aed') ?>;"></span>
                    <span class="qh-all-name"><?= esc($cat['name']) ?></span>

                    <div class="qh-peso" data-cat-id="<?= (int)$cat['id'] ?>">
                        <button type="button" class="qh-peso-btn" data-delta="-1">−</button>
                        <span class="qh-peso-value"><?= (int)$cat['peso'] ?></span>
                        <button type="button" class="qh-peso-btn" data-delta="1">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.qh-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.qh-back:hover { color: var(--bs-emphasis-color); }
.qh-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.qh-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 12px;
}

.qh-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    background: var(--bs-tertiary-bg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.qh-card-header { padding: 10px 12px; background: var(--bs-body-bg); }
.qh-card-title-row { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.qh-card-title { font-size: 1rem; color: var(--bs-emphasis-color); }
.qh-card-tiempo { font-size: .72rem; color: var(--bs-secondary-color); white-space: nowrap; }

.qh-peso { display: flex; align-items: center; gap: 6px; margin-top: 6px; }
.qh-peso-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); margin-right: 2px; }
.qh-peso-btn {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    line-height: 1;
    font-size: 1rem;
    display: grid;
    place-items: center;
}
.qh-peso-btn:hover { filter: brightness(1.2); }
.qh-peso-value { min-width: 1.4em; text-align: center; font-weight: 700; font-size: .85rem; color: var(--bs-emphasis-color); }
.qh-peso-value.is-zero { color: #dc3545; }

.qh-task-list { display: flex; flex-direction: column; padding: 6px; gap: 2px; }
.qh-task {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 6px;
    border-radius: 10px;
}
.qh-task:hover { background: var(--bs-body-bg); }

.qh-star {
    flex: 0 0 auto;
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border: none;
    background: transparent;
    color: #adb5bd;
    border-radius: 50%;
}
.qh-star:hover { background: var(--bs-tertiary-bg); }

.qh-task-title {
    flex: 1 1 auto;
    min-width: 0;
    font-size: .88rem;
    color: var(--bs-emphasis-color);
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qh-task-title:hover { text-decoration: underline; }

.qh-task-time { flex: 0 0 auto; font-size: .74rem; color: var(--bs-secondary-color); }

.qh-task-more {
    font-size: .78rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
    padding: 4px 8px;
}
.qh-task-more:hover { color: var(--bs-emphasis-color); }

.qh-all-toggle {
    display: flex;
    align-items: center;
    gap: .4rem;
    width: 100%;
    text-align: left;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-size: .85rem;
    font-weight: 600;
}
.qh-all-chevron { margin-left: auto; transition: transform .15s ease; }
.qh-all-toggle[aria-expanded="true"] .qh-all-chevron { transform: rotate(180deg); }

.qh-all-list { display: flex; flex-direction: column; gap: 4px; }
.qh-all-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}
.qh-all-dot { flex: 0 0 auto; width: 10px; height: 10px; border-radius: 50%; }
.qh-all-name { flex: 1 1 auto; min-width: 0; font-size: .85rem; color: var(--bs-emphasis-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>

<script>
(() => {
    async function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
            },
            body,
        });
    }

    // Estrella (reutiliza el mismo endpoint que el listado principal)
    document.querySelectorAll('.js-toggle-current').forEach(btn => {
        btn.addEventListener('click', async () => {
            const res = await post('<?= site_url('journal/toggle-current') ?>/' + btn.dataset.taskId, '');
            if (!res.ok) return;
            const data = await res.json();
            btn.querySelector('i').className = data.is_current ? 'bi bi-star-fill' : 'bi bi-star';
        });
    });

    // Peso por categoría
    document.querySelectorAll('.qh-peso').forEach(wrap => {
        const catId = wrap.dataset.catId;
        const valueEl = wrap.querySelector('.qh-peso-value');

        wrap.querySelectorAll('.qh-peso-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                let nuevo = parseInt(valueEl.textContent, 10) + parseInt(btn.dataset.delta, 10);
                nuevo = Math.max(0, Math.min(5, nuevo));

                const res = await post('<?= site_url('journal/categorias') ?>/' + catId + '/peso', 'peso=' + nuevo);
                if (!res.ok) return;
                const data = await res.json();
                if (!data.success) return;

                // Actualiza todas las apariciones de esta categoría en la página
                // (puede estar tanto en la tarjeta sugerida como en el panel "todas")
                document.querySelectorAll('.qh-peso[data-cat-id="' + catId + '"] .qh-peso-value').forEach(el => {
                    el.textContent = data.peso;
                    el.classList.toggle('is-zero', data.peso === 0);
                });
            });
        });
    });
})();
</script>

<?= $this->endSection() ?>
