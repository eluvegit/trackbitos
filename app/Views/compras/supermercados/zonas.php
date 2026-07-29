<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-1 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-signpost-split text-primary"></i>
    <span class="text-muted fw-normal">Compras</span>
    <span class="text-muted">/</span>
    <a href="<?= site_url('compras/productos/' . $supermercado_id) ?>" class="text-decoration-none text-muted fw-normal">
        <?= esc($supermercado_nombre) ?>
    </a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Zonas / recorrido</strong>
</h5>
<p class="text-muted small mb-3">Arrastra <i class="bi bi-grip-vertical"></i> para ordenarlas según el recorrido que sigues por el supermercado. Luego asigna cada producto a su zona.</p>

<a href="<?= site_url('compras/productos/' . $supermercado_id) ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<!-- Añadir zona -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form action="<?= site_url('compras/zonas/nuevo') ?>" method="post" class="d-flex gap-2 flex-wrap align-items-end mb-2">
            <?= csrf_field() ?>
            <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">

            <div class="flex-grow-1" style="min-width: 180px;">
                <label for="nombre" class="form-label small mb-1">Nueva zona</label>
                <input type="text" name="nombre" id="nombre" class="form-control form-control-sm" placeholder="Ej. Frutería" required>
            </div>

            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Añadir
            </button>
        </form>

        <?php if (!empty($sugerencias)): ?>
            <div class="small text-muted mb-1">Sugerencias típicas:</div>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($sugerencias as $sugerencia): ?>
                    <form action="<?= site_url('compras/zonas/nuevo') ?>" method="post" class="m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">
                        <input type="hidden" name="nombre" value="<?= esc($sugerencia) ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill py-0 px-2">
                            + <?= esc($sugerencia) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="zona-list" id="zonaList">
    <?php foreach ($zonas as $z): ?>
        <div class="zona-item" data-id="<?= (int)$z['id'] ?>">
            <span class="zona-handle" title="Arrastrar para reordenar">
                <i class="bi bi-grip-vertical"></i>
            </span>

            <span class="zona-name" data-nombre="<?= esc($z['nombre'], 'attr') ?>"><?= esc($z['nombre']) ?></span>

            <button type="button" class="zona-rename" title="Renombrar" data-id="<?= (int)$z['id'] ?>">
                <i class="bi bi-pencil"></i>
            </button>

            <form action="<?= site_url('compras/zonas/' . $z['id'] . '/borrar') ?>" method="post" class="m-0"
                  onsubmit="return confirm('¿Eliminar esta zona? Los productos asignados se quedarán sin zona.')">
                <?= csrf_field() ?>
                <button type="submit" class="zona-delete" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($zonas)): ?>
    <p class="text-muted">Todavía no hay zonas creadas para este supermercado.</p>
<?php endif; ?>

<style>
.zona-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.zona-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
}

.zona-item.sortable-ghost { opacity: .3; }
.zona-item.sortable-chosen { background: var(--bs-tertiary-bg); }

.zona-handle {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    color: var(--bs-secondary-color);
    cursor: grab;
    touch-action: none;
}
.zona-handle:active { cursor: grabbing; }

.zona-name {
    flex: 1 1 auto;
    min-width: 0;
    font-weight: 600;
    font-size: .95rem;
    color: var(--bs-emphasis-color);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.zona-rename,
.zona-delete {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
}
.zona-rename:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }
.zona-delete:hover { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (() => {
        const list = document.getElementById('zonaList');
        if (!list) return;

        async function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                    'Content-Type': 'application/json',
                },
                body: body ? JSON.stringify(body) : null,
            });
        }

        Sortable.create(list, {
            handle: '.zona-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: () => {
                const orden = [...list.querySelectorAll('.zona-item')].map(item => item.dataset.id);
                post('<?= site_url('compras/zonas/reordenar') ?>', { orden });
            },
        });

        list.addEventListener('click', async (e) => {
            const btn = e.target.closest('.zona-rename');
            if (!btn) return;

            const item = btn.closest('.zona-item');
            const nameEl = item.querySelector('.zona-name');
            const actual = nameEl.dataset.nombre;
            const nuevo = prompt('Nuevo nombre de la zona:', actual);
            if (!nuevo || !nuevo.trim() || nuevo.trim() === actual) return;

            const res = await fetch('<?= site_url('compras/zonas') ?>/' + btn.dataset.id + '/renombrar', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'nombre=' + encodeURIComponent(nuevo.trim()) + '&<?= csrf_token() ?>=<?= csrf_hash() ?>',
            });

            if (!res.ok) return;
            const data = await res.json();
            if (data.ok) {
                nameEl.textContent = data.nombre;
                nameEl.dataset.nombre = data.nombre;
            }
        });
    })();
</script>

<?= $this->endSection() ?>
