<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<!-- Forzar tema oscuro -->
<style>
    body.bg-light {
        background-color: #1e1e1e !important;
        /* gris oscuro suave */
        color: #e0e0e0 !important;
    }

    .breadcrumb,
    .d-flex.flex-wrap.gap-2.mb-3 {
        background-color: #2a2a2a;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
    }

    .card.producto-card {
        background-color: #4b4b4b;
        color: #e0e0e0;
        border-radius: 0.5rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.5);
        transition: all 0.2s ease;
        padding: 0.5rem;
        cursor: pointer;
    }

    .btn-outline-light,
    .btn-outline-warning {
        color: #e0e0e0;
        border-color: #6c757d;
    }

    .btn-outline-light:hover,
    .btn-outline-warning:hover {
        color: #fff;
        border-color: #adb5bd;
        background-color: #3a3a3a;
    }

    .producto-imagen {
        background-color: #212529;
        border-radius: 0.25rem;
        max-height: 120px;
        object-fit: contain;
    }

    /* Estilo lista */
    .lista .col {
        flex: 1 0 100%;
    }

    .lista .producto-card {
        flex-direction: row;
        align-items: center;
    }

    .lista .producto-card .card-body {
        flex-grow: 1;
        text-align: left;
        padding-left: 1rem;
        display: flex;
        align-items: center;
    }

    .lista .producto-card img {
        width: 80px;
        height: 80px;
        margin: 0;
        margin-right: 1rem;
    }

    .producto-card {
        user-select: none;
        /* Evita seleccionar texto */
        -webkit-user-select: none;
        /* Para Safari/iOS */
        -moz-user-select: none;
        /* Para Firefox */
        -ms-user-select: none;
        /* Para IE/Edge */
    }
</style>

<script>
    // Forzar body a bg-dark al cargar
    document.addEventListener('DOMContentLoaded', () => {
        document.body.classList.remove('bg-light');
        document.body.classList.add('bg-dark');
    });
</script>

<!-- Header / Breadcrumb -->
<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart3 text-info"></i>
    <span class="text-secondary fw-normal">Compras</span>
    <span class="text-secondary">/</span>
    <strong class="fw-semibold">
        <a href="<?= site_url('compras/productos/' . $supermercado_id) ?>"
            class="text-light text-decoration-none">
            <?= esc($supermercado_nombre) ?>
        </a>
    </strong>
    <span class="text-secondary">/</span>
    <span class="fw-semibold text-success">COMPRAR</span>
</h5>

<!-- Acciones rápidas -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <button id="toggle-imagenes" class="btn btn-outline-light btn-sm">
        Ocultar imágenes
    </button>

    <a href="<?= site_url('compras/' . $supermercado_id . '/faltantes') ?>"
        class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil-square"></i> FALTA
    </a>
</div>

<!-- Lista de productos -->
<div id="productos-container" class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-2">
    <?php foreach ($productos as $producto): ?>
        <div class="col d-flex">
            <div class="card shadow-sm w-100 small text-center producto-card d-flex flex-column justify-content-between"
                data-producto-id="<?= $producto['id'] ?>"
                data-comprado="<?= $producto['comprado'] ? '1' : '0' ?>"
                style="border: 2px solid <?= $producto['comprado'] ? '#fff' : 'transparent' ?>;">

                <?php if (!empty($producto['imagen'])): ?>
                    <img src="<?= esc($producto['imagen']) ?>"
                        class="producto-imagen img-fluid mb-2 mx-auto">
                <?php endif; ?>

                <div class="card-body p-2 flex-grow-1 d-flex align-items-center justify-content-center">
                    <div class="fw-semibold text-center" style="word-wrap: break-word;">
                        <?= esc($producto['nombre']) ?>
                    </div>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    const container = document.getElementById('productos-container');
    const toggleBtn = document.getElementById('toggle-imagenes');

    toggleBtn.addEventListener('click', () => {
        const imgs = document.querySelectorAll('.producto-imagen');
        const ocultar = imgs[0]?.style.display !== 'none';

        // Ocultar o mostrar imágenes
        imgs.forEach(img => img.style.display = ocultar ? 'none' : '');
        toggleBtn.textContent = ocultar ? 'Mostrar imágenes' : 'Ocultar imágenes';

        // Activar modo lista
        if (ocultar) {
            container.classList.add('lista');
        } else {
            container.classList.remove('lista');
        }
    });

    // Toggle comprado
    document.querySelectorAll('.producto-card').forEach(card => {
        card.addEventListener('click', async () => {
            const productoId = card.getAttribute('data-producto-id');
            const esComprado = card.getAttribute('data-comprado') === '1';
            const url = esComprado ?
                '<?= site_url('compras/producto') ?>/' + productoId + '/desmarcar-comprado' :
                '<?= site_url('compras/producto') ?>/' + productoId + '/marcar-comprado';
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
                });
                if (response.ok) {
                    card.style.borderColor = esComprado ? 'transparent' : '#fff';
                    card.setAttribute('data-comprado', esComprado ? '0' : '1');
                } else alert('Error al actualizar el estado del producto.');
            } catch (err) {
                console.error(err);
                alert('Fallo en la conexión con el servidor.');
            }
        });
    });
</script>

<?= $this->endSection() ?>