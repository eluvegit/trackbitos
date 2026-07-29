<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">

    <i class="bi bi-cart3 text-primary"></i>

    <a href="<?= site_url('compras/supermercados') ?>"
        class="text-decoration-none text-muted fw-normal">
        Compras
    </a>

    <span class="text-muted">/</span>

    <strong class="fw-semibold">
        <?= esc($supermercado_nombre) ?>
    </strong>

    <a href="<?= site_url('compras/supermercados/editar/' . $supermercado_id) ?>"
        class="text-decoration-none ms-1 text-secondary"
        title="Editar supermercado">
        <i class="bi bi-pencil-square fs-6"></i>
    </a>

    <a href="<?= site_url('compras/supermercados/' . $supermercado_id . '/zonas') ?>"
        class="text-decoration-none ms-1 text-secondary"
        title="Zonas y recorrido">
        <i class="bi bi-signpost-split fs-6"></i>
    </a>
</h5>

<!-- Accesos rápidos a listas -->
<div class="row row-cols-2 g-2 mb-3">

    <div class="col d-flex">
        <a href="<?= site_url('compras/' . $supermercado_id . '/faltantes') ?>"
            class="text-decoration-none text-dark w-100">

            <div class="">
                <div class="card shadow-sm border-warning border-2 
                            d-flex align-items-center justify-content-center text-center p-2">
                    <div>
                        <div class="fw-semibold small"><i class="bi bi-pencil-square fs-6 text-warning mb-1"></i>
                        FALTA</div>
                    </div>

                </div>
            </div>

        </a>
    </div>

    <div class="col d-flex">
        <a href="<?= site_url('compras/' . $supermercado_id . '/comprados') ?>"
            class="text-decoration-none text-dark w-100">

            <div class="">
                <div class="card shadow-sm border-success border-2 
                            d-flex align-items-center justify-content-center text-center p-2">
                    <div>
                        <div class="fw-semibold small"><i class="bi bi-cart-check fs-6 text-success mb-1"></i>
                        COMPRAR</div>
                    </div>

                </div>
            </div>

        </a>
    </div>

</div>



<!-- Formulario para nuevo producto -->
<div class="card mb-3">
    <div class="card-body p-2">

        <form action="<?= site_url('compras/productos/nuevo') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">

            <div class="row g-1 align-items-end">

                <div class="col-6 col-md-3">
                    <label for="nombre" class="form-label small mb-1">Nombre</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control form-control-sm" required>
                </div>

                <div class="col-6 col-md-3">
                    <label for="zona_id" class="form-label small mb-1">Zona</label>
                    <select name="zona_id" id="zona_id" class="form-select form-select-sm">
                        <option value="">Sin zona</option>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?= (int)$z['id'] ?>"><?= esc($z['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-4">
                    <label for="imagen" class="form-label small mb-1">Imagen</label>
                    <input type="url" name="imagen" id="imagen"
                           class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-2">
                    <button type="submit"
                            class="btn btn-success btn-sm w-100">
                        + Añadir
                    </button>
                </div>

            </div>
        </form>

    </div>
</div>

<!-- Lista de productos, agrupada por zona. Arrastra una tarjeta a otra zona para reasignarla. -->
<p class="text-muted small mb-2">
    <i class="bi bi-arrows-move"></i> Arrastra un producto a otra zona para reasignarlo.
</p>

<?php foreach ($grupos as $grupo): ?>
    <h6 class="text-muted small text-uppercase mt-3 mb-2">
        <?= $grupo['zona'] ? esc($grupo['zona']['nombre']) : 'Sin zona' ?>
    </h6>

    <div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-2 mb-2 zona-dropzone"
         data-zona-id="<?= $grupo['zona']['id'] ?? '' ?>">
        <?php foreach ($grupo['productos'] as $producto): ?>
            <div class="col d-flex h-100" data-producto-id="<?= (int)$producto['id'] ?>">
                <div class="card shadow-sm w-100 small d-flex flex-column h-100 position-relative">

                    <button type="button"
                            class="btn btn-sm p-0 producto-favorito-toggle position-absolute top-0 end-0 m-1"
                            data-producto-id="<?= (int)$producto['id'] ?>"
                            data-favorito="<?= $producto['favorito'] ? '1' : '0' ?>"
                            title="Marcar como compra habitual">
                        <i class="bi <?= $producto['favorito'] ? 'bi-star-fill text-warning' : 'bi-star text-secondary' ?>"></i>
                    </button>

                    <?php if (!empty($producto['imagen'])): ?>
                        <img src="<?= esc($producto['imagen']) ?>"
                             class="card-img-top producto-imagen-slot"
                             style="object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top producto-imagen-slot d-flex align-items-center justify-content-center bg-body-secondary">
                            <i class="bi bi-image text-secondary fs-3"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body p-2 d-flex flex-column justify-content-between">

                        <h6 class="card-title mb-1 producto-nombre">
                            <?= esc($producto['nombre']) ?>
                        </h6>

                        <div class="mb-1 producto-badges">
                            <?php if ($producto['faltante']): ?>
                                <span class="badge bg-warning text-dark">FALTA</span>
                            <?php endif; ?>
                            <?php if ($producto['comprado']): ?>
                                <span class="badge bg-success">OK</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="producto-precio-toggle text-muted small"
                                  role="button"
                                  data-producto-id="<?= (int)$producto['id'] ?>"
                                  data-precio="<?= $producto['precio'] !== null ? esc($producto['precio']) : '' ?>">
                                <?= $producto['precio'] !== null
                                    ? number_format((float) $producto['precio'], 2, ',', '.') . ' €'
                                    : '+ precio' ?>
                            </span>

                            <a href="<?= site_url('compras/productos/editar/' . $producto['id']) ?>"
                               class="text-decoration-none text-muted small">
                               ✏️
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($grupo['productos'])): ?>
            <div class="zona-empty-hint text-muted small">Arrastra aquí productos de esta zona</div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (empty($grupos)): ?>
    <p class="text-muted">Todavía no hay productos en este supermercado.</p>
<?php endif; ?>

<!-- Modal edición rápida de precio -->
<div class="modal fade" id="modalPrecio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form id="formPrecio">
                <div class="modal-header">
                    <h6 class="modal-title mb-0">Precio</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label for="inputPrecio" class="form-label small">Precio (€)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="inputPrecio">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.zona-dropzone {
    min-height: 46px;
    padding-top: 4px;
    padding-bottom: 4px;
    border: 1px dashed transparent;
    border-radius: 10px;
}
.zona-dropzone .zona-empty-hint {
    padding: 10px 4px;
}
.zona-dropzone.sortable-drag-over,
.zona-dropzone:has(.sortable-ghost) {
    border-color: var(--bs-primary);
    background: var(--bs-tertiary-bg);
}
.producto-card-item { cursor: grab; }
.producto-card-item:active { cursor: grabbing; }
.producto-card-item.sortable-ghost { opacity: .35; }
.producto-card-item.sortable-chosen .card { box-shadow: 0 0 0 2px var(--bs-primary); }

/* Alturas de tarjeta consistentes: reserva el mismo espacio tenga o no imagen,
   independientemente de si el nombre ocupa una o dos líneas o de cuántas
   etiquetas (FALTA/OK) tenga, para que todas las filas midan igual. */
.producto-imagen-slot {
    height: 110px;
}
.producto-nombre {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.4em;
}
.producto-badges {
    min-height: 1.35rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (() => {
        const dropzones = document.querySelectorAll('.zona-dropzone');
        if (!dropzones.length) return;

        dropzones.forEach(zona => {
            zona.querySelectorAll(':scope > [data-producto-id]').forEach(item => {
                item.classList.add('producto-card-item');
            });
        });

        async function guardarOrden(zona) {
            const ids = [...zona.querySelectorAll(':scope > [data-producto-id]')]
                .map(el => el.dataset.productoId);
            if (!ids.length) return true;

            const res = await fetch('<?= site_url('compras/productos/reordenar') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ zona_id: zona.dataset.zonaId || '', orden: ids }),
            });
            return res.ok;
        }

        function mostrarHintSiVacia(zona) {
            const tieneProductos = [...zona.children].some(el => el.dataset && el.dataset.productoId);
            if (tieneProductos) return;
            if (zona.querySelector('.zona-empty-hint')) return;

            const hint = document.createElement('div');
            hint.className = 'zona-empty-hint text-muted small';
            hint.textContent = 'Arrastra aquí productos de esta zona';
            zona.appendChild(hint);
        }

        /* ===== Favorito (⭐ compra habitual) ===== */
        document.querySelectorAll('.producto-favorito-toggle').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();

                const productoId = btn.dataset.productoId;
                const icon = btn.querySelector('i');

                try {
                    const res = await fetch('<?= site_url('compras/productos') ?>/' + productoId + '/favorito', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                        },
                    });
                    if (!res.ok) return;

                    const data = await res.json();
                    btn.dataset.favorito = data.favorito ? '1' : '0';
                    icon.classList.toggle('bi-star-fill', data.favorito);
                    icon.classList.toggle('text-warning', data.favorito);
                    icon.classList.toggle('bi-star', !data.favorito);
                    icon.classList.toggle('text-secondary', !data.favorito);
                } catch (err) {
                    console.error(err);
                }
            });
        });

        /* ===== Precio (modal de edición rápida) ===== */
        let modalPrecio;
        const modalPrecioEl = document.getElementById('modalPrecio');
        const inputPrecio = document.getElementById('inputPrecio');
        const formPrecio = document.getElementById('formPrecio');
        let precioTarget = null;

        document.querySelectorAll('.producto-precio-toggle').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                precioTarget = el;
                inputPrecio.value = el.dataset.precio || '';
                modalPrecio ??= new bootstrap.Modal(modalPrecioEl);
                modalPrecio.show();
                setTimeout(() => inputPrecio.focus(), 300);
            });
        });

        formPrecio.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!precioTarget) return;

            const productoId = precioTarget.dataset.productoId;

            try {
                const res = await fetch('<?= site_url('compras/productos') ?>/' + productoId + '/precio', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ precio: inputPrecio.value }),
                });
                if (!res.ok) return;

                const data = await res.json();
                precioTarget.dataset.precio = data.precio !== null ? data.precio : '';
                precioTarget.textContent = data.precio !== null
                    ? Number(data.precio).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'
                    : '+ precio';

                modalPrecio.hide();
            } catch (err) {
                console.error(err);
            }
        });

        dropzones.forEach(zona => {
            Sortable.create(zona, {
                group: 'productos-zona',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                filter: '.producto-favorito-toggle, .producto-precio-toggle',
                preventOnFilter: false,
                onEnd: async (evt) => {
                    const item = evt.item;
                    if (!item.dataset.productoId) return;

                    const cambioDeZona = evt.from !== evt.to;
                    if (cambioDeZona) {
                        evt.to.querySelector('.zona-empty-hint')?.remove();
                    }

                    const ok = await guardarOrden(evt.to);
                    if (!ok) {
                        // revertir si falla la petición
                        evt.from.insertBefore(item, evt.from.children[evt.oldIndex] || null);
                        mostrarHintSiVacia(evt.to);
                        return;
                    }

                    if (cambioDeZona) {
                        mostrarHintSiVacia(evt.from);
                    }
                },
            });
        });
    })();
</script>

<?= $this->endSection() ?>