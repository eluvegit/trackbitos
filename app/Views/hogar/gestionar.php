<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-1 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-house-heart text-primary"></i>
    <a href="<?= site_url('hogar') ?>" class="text-decoration-none text-muted fw-normal">Hogar</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Gestionar habitaciones</strong>
</h5>
<p class="text-muted small mb-3">Arrastra <i class="bi bi-grip-vertical"></i> para reordenar.</p>

<a href="<?= site_url('hogar') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<div class="hab-list" id="habList">
    <?php foreach ($habitaciones as $h): ?>
        <div class="hab-item" data-id="<?= (int)$h['id'] ?>">
            <span class="hab-handle" title="Arrastrar para reordenar">
                <i class="bi bi-grip-vertical"></i>
            </span>

            <i class="bi bi-<?= esc($h['icono'] ?: 'house') ?> hab-icon"></i>

            <span class="hab-name"><?= esc($h['nombre']) ?></span>

            <a href="<?= site_url('hogar/habitaciones/editar/' . $h['id']) ?>" class="hab-action" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>

            <form action="<?= site_url('hogar/habitaciones/borrar/' . $h['id']) ?>" method="post" class="m-0"
                  onsubmit="return confirm('¿Eliminar esta habitación y todas sus tareas?')">
                <?= csrf_field() ?>
                <button type="submit" class="hab-action hab-action-danger" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<style>
.hab-list { display: flex; flex-direction: column; gap: 8px; }

.hab-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}
.hab-item.sortable-ghost { opacity: .3; }
.hab-item.sortable-chosen { background: var(--bs-tertiary-bg); }

.hab-handle {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    color: var(--bs-secondary-color);
    cursor: grab;
    touch-action: none;
}
.hab-handle:active { cursor: grabbing; }

.hab-icon { flex: 0 0 auto; color: var(--bs-primary); font-size: 1.1rem; }

.hab-name {
    flex: 1 1 auto;
    min-width: 0;
    font-weight: 600;
    font-size: .95rem;
    color: var(--bs-emphasis-color);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hab-action {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    color: var(--bs-secondary-color);
    background: transparent;
    text-decoration: none;
}
.hab-action:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }
.hab-action-danger:hover { color: #dc3545; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (() => {
        const list = document.getElementById('habList');
        if (!list) return;

        Sortable.create(list, {
            handle: '.hab-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: () => {
                const orden = [...list.querySelectorAll('.hab-item')].map(item => item.dataset.id);
                fetch('<?= site_url('hogar/habitaciones/reordenar') ?>', {
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
