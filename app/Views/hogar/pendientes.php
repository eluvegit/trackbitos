<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="hogar-header mb-3">
    <a href="<?= site_url('hogar') ?>" class="hogar-back"><i class="bi bi-chevron-left"></i> Hogar</a>
    <h2 class="hogar-title mb-0 mt-1">
        <i class="bi bi-list-check text-primary"></i>
        Qué toca hacer
    </h2>
    <p class="text-muted small mb-0" id="pend-counter">
        <?= count($tareas) ?> tarea<?= count($tareas) === 1 ? '' : 's' ?> pendiente<?= count($tareas) === 1 ? '' : 's' ?>, ordenadas por prioridad
    </p>
</div>

<div class="pend-list" id="pendList">
    <?php foreach ($tareas as $t): ?>
        <div class="pend-item <?= $t['diff_dias'] !== null && $t['diff_dias'] >= 0 ? 'is-atrasada' : '' ?> <?= $t['nunca'] && $t['tiene_frecuencia'] ? 'is-atrasada' : '' ?>"
             data-id="<?= (int)$t['id'] ?>">

            <button type="button" class="pend-check js-marcar" data-id="<?= (int)$t['id'] ?>" aria-label="Marcar como hecha">
                <i class="bi bi-circle"></i>
            </button>

            <div class="pend-body">
                <div class="pend-nombre"><?= esc($t['nombre']) ?></div>

                <div class="pend-meta">
                    <?php if ($t['habitacion']): ?>
                        <a href="<?= site_url('hogar/' . $t['habitacion']['id']) ?>" class="pend-habitacion">
                            <i class="bi bi-<?= esc($t['habitacion']['icono'] ?: 'house') ?>"></i>
                            <?= esc($t['habitacion']['nombre']) ?>
                        </a>
                    <?php endif; ?>

                    <span class="pend-tiempo"><?= esc($t['tiempo_relativo']) ?></span>

                    <?php if ($t['tiene_frecuencia']): ?>
                        <span class="pend-frecuencia">cada <?= (int)$t['frecuencia_dias'] ?> día<?= (int)$t['frecuencia_dias'] === 1 ? '' : 's' ?></span>
                    <?php endif; ?>

                    <?php if ($t['nunca'] && $t['tiene_frecuencia']): ?>
                        <span class="pend-badge pend-badge-danger"><i class="bi bi-arrow-repeat"></i> Nunca hecha</span>
                    <?php elseif ($t['diff_dias'] !== null && $t['diff_dias'] > 0): ?>
                        <span class="pend-badge pend-badge-danger"><i class="bi bi-arrow-repeat"></i> Atrasada <?= (int)$t['diff_dias'] ?> día<?= (int)$t['diff_dias'] === 1 ? '' : 's' ?></span>
                    <?php elseif ($t['diff_dias'] === 0): ?>
                        <span class="pend-badge pend-badge-danger"><i class="bi bi-arrow-repeat"></i> Toca hoy</span>
                    <?php elseif ($t['diff_dias'] !== null): ?>
                        <span class="pend-badge pend-badge-info">Faltan <?= (int)abs($t['diff_dias']) ?> día<?= (int)abs($t['diff_dias']) === 1 ? '' : 's' ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="pend-empty <?= empty($tareas) ? '' : 'd-none' ?>" id="pendEmpty">
    <i class="bi bi-emoji-sunglasses"></i>
    <p>¡Todo al día! No tienes tareas pendientes.</p>
</div>

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

.pend-list { display: flex; flex-direction: column; gap: 8px; }

.pend-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px;
    border-radius: 14px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}
/* Aviso suave (no alarmante): ámbar cálido en vez de rojo intenso. */
.pend-item.is-atrasada {
    border-color: rgba(217,173,107,.35);
    background: rgba(217,173,107,.07);
}

.pend-check {
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    font-size: 1.05rem;
    cursor: pointer;
    margin-top: 2px;
}
.pend-check:hover { filter: brightness(1.2); }

.pend-body { flex: 1 1 auto; min-width: 0; }

.pend-nombre {
    font-weight: 600;
    font-size: .95rem;
    color: var(--bs-emphasis-color);
}

.pend-meta {
    margin-top: 3px;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
    font-size: .78rem;
    color: var(--bs-secondary-color);
}

.pend-habitacion {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .15rem .5rem;
    border-radius: 999px;
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    text-decoration: none;
    font-weight: 600;
}
.pend-habitacion:hover { filter: brightness(1.15); }

.pend-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-weight: 600;
}
.pend-badge-danger { color: #d9ad6b; }
.pend-badge-info { color: #6366f1; }

.pend-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--bs-secondary-color);
}
.pend-empty i { font-size: 2rem; opacity: .6; }
.pend-empty p { margin-top: .5rem; margin-bottom: 0; }

@media (max-width: 575.98px) {
    .hogar-title { font-size: 1.15rem; }
}
</style>

<script>
(() => {
    const list = document.getElementById('pendList');
    const empty = document.getElementById('pendEmpty');
    const counter = document.getElementById('pend-counter');
    if (!list) return;

    list.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-marcar');
        if (!btn) return;

        const item = btn.closest('.pend-item');
        btn.disabled = true;

        const res = await fetch('<?= site_url('hogar/tareas') ?>/' + btn.dataset.id + '/marcar', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
            },
        });

        if (!res.ok) {
            btn.disabled = false;
            return;
        }

        item.style.transition = 'opacity .2s ease';
        item.style.opacity = '0';
        setTimeout(() => {
            item.remove();
            const restantes = list.querySelectorAll('.pend-item').length;
            if (counter) {
                counter.textContent = restantes + (restantes === 1 ? ' tarea pendiente, ordenadas por prioridad' : ' tareas pendientes, ordenadas por prioridad');
            }
            if (restantes === 0) {
                empty.classList.remove('d-none');
            }
        }, 200);
    });
})();
</script>

<?= $this->endSection() ?>
