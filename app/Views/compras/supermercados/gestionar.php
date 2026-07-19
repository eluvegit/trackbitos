<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-1 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart3 text-primary"></i>
    <span class="text-muted fw-normal">Compras</span>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Gestionar supermercados</strong>
</h5>
<p class="text-muted small mb-3">Arrastra <i class="bi bi-grip-vertical"></i> para reordenar. Usa el interruptor para ocultar del menú principal.</p>

<a href="<?= site_url('compras/supermercados') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<div class="super-list" id="superList">
    <?php foreach ($supermercados as $s): ?>
        <div class="super-item <?= $s['visible'] ? '' : 'is-hidden' ?>" data-id="<?= (int)$s['id'] ?>">
            <span class="super-handle" title="Arrastrar para reordenar">
                <i class="bi bi-grip-vertical"></i>
            </span>

            <span class="super-name"><?= esc($s['nombre']) ?></span>

            <a href="<?= site_url('compras/supermercados/editar/' . $s['id']) ?>" class="super-edit" title="Editar">
                <i class="bi bi-pencil"></i>
            </a>

            <div class="form-check form-switch super-switch mb-0">
                <input class="form-check-input js-toggle-visible" type="checkbox" role="switch"
                       data-id="<?= (int)$s['id'] ?>"
                       <?= $s['visible'] ? 'checked' : '' ?>
                       aria-label="Mostrar en el menú">
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($supermercados)): ?>
    <p class="text-muted">Todavía no hay supermercados creados.</p>
<?php endif; ?>

<style>
.super-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.super-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    transition: opacity .15s ease, background-color .15s ease;
}

.super-item.is-hidden {
    opacity: .5;
}

.super-item.sortable-ghost {
    opacity: .3;
}

.super-item.sortable-chosen {
    background: var(--bs-tertiary-bg);
}

.super-handle {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    color: var(--bs-secondary-color);
    cursor: grab;
    touch-action: none;
}
.super-handle:active { cursor: grabbing; }

.super-name {
    flex: 1 1 auto;
    min-width: 0;
    font-weight: 600;
    font-size: .95rem;
    color: var(--bs-emphasis-color);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.super-edit {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.super-edit:hover {
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
}

.super-switch {
    flex: 0 0 auto;
}
.super-switch .form-check-input {
    width: 2.4rem;
    height: 1.3rem;
    cursor: pointer;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (() => {
        const list = document.getElementById('superList');
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

        // Drag & drop (con soporte táctil) para reordenar
        Sortable.create(list, {
            handle: '.super-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: () => {
                const orden = [...list.querySelectorAll('.super-item')].map(item => item.dataset.id);
                post('<?= site_url('compras/supermercados/reordenar') ?>', { orden });
            },
        });

        // Ocultar / mostrar
        list.addEventListener('change', async (e) => {
            const input = e.target.closest('.js-toggle-visible');
            if (!input) return;
            const item = input.closest('.super-item');
            const id = input.dataset.id;

            const res = await post('<?= site_url('compras/supermercados') ?>/' + id + '/visibilidad');
            if (!res.ok) {
                input.checked = !input.checked; // revertir si falla
                return;
            }
            item.classList.toggle('is-hidden', !input.checked);
        });
    })();
</script>

<?= $this->endSection() ?>
