<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">

    <i class="bi bi-cart3 text-primary"></i>

    <span class="text-muted fw-normal">Compras</span>

    <span class="text-muted">/</span>

    <strong class="fw-semibold">
        <a href="<?= site_url('compras/productos/' . $supermercado_id) ?>"
            class="text-dark text-decoration-none">
            <?= esc($supermercado_nombre) ?>
        </a>
    </strong>

    <span class="text-muted">/</span>

    <span class="fw-semibold text-warning">
        FALTA
    </span>

</h5>

<!-- Accesos rápidos a listas -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">

    <!-- Botón Reiniciar faltantes -->
    <form action="<?= site_url('compras/limpiar/faltantes/' . $supermercado_id) ?>"
        method="post"
        class="m-0"
        onsubmit="return confirm('¿Seguro que deseas reiniciar todos los faltantes?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
            🧹
        </button>
    </form>
    <button id="toggle-imagenes"
        class="btn btn-outline-secondary btn-sm">
        Ocultar imágenes
    </button>
    <!-- Botón COMPRAR -->
    <a href="<?= site_url('compras/' . $supermercado_id . '/comprados') ?>"
        class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart-check"></i>
        COMPRAR
    </a>

</div>
<!-- Lista de productos -->
<div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-2">
    <?php foreach ($productos as $producto): ?>
        <div class="col d-flex">
            <div
                class="card shadow-sm w-100 small text-center producto-card d-flex flex-column justify-content-between"
                data-producto-id="<?= $producto['id'] ?>"
                data-faltante="<?= $producto['faltante'] ? '1' : '0' ?>"
                style="
                    cursor: pointer; 
                    min-height: 200px;
                    border: 2px solid <?= $producto['faltante'] ? '#ffc107' : 'transparent' ?>;
                    transition: border-color 0.2s ease;
                    padding: 0.5rem;
                ">

                <?php if (!empty($producto['imagen'])): ?>
                    <img src="<?= esc($producto['imagen']) ?>"
                         class="card-img-top imagen-producto img-fluid mb-2"
                         style="max-height: 120px; width: auto; margin: 0 auto; object-fit: contain;">
                <?php endif; ?>

                <div class="card-body p-1 flex-grow-1 d-flex align-items-center justify-content-center">
                    <div class="fw-semibold text-center" style="word-wrap: break-word;">
                        <?= esc($producto['nombre']) ?>
                    </div>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
</div>




<script>
    document.querySelectorAll('.producto-card').forEach(card => {
        card.addEventListener('click', async () => {
            const productoId = card.getAttribute('data-producto-id');
            const esFaltante = card.getAttribute('data-faltante') === '1';

            const url = esFaltante ?
                '<?= site_url('compras/producto') ?>/' + productoId + '/desmarcar-faltante' :
                '<?= site_url('compras/producto') ?>/' + productoId + '/marcar-faltante';

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
                    card.style.borderColor = esFaltante ? 'transparent' : '#ffc107';
                    card.setAttribute('data-faltante', esFaltante ? '0' : '1');
                } else {
                    alert('Error al actualizar el estado del producto.');
                }
            } catch (err) {
                console.error(err);
                alert('Fallo en la conexión con el servidor.');
            }
        });
    });


    // Mostrar / ocultar imágenes
    document.getElementById('toggle-imagenes').addEventListener('click', () => {
        const imgs = document.querySelectorAll('.imagen-producto');
        const ocultar = imgs[0]?.style.display !== 'none';
        imgs.forEach(img => img.style.display = ocultar ? 'none' : 'block');
        document.getElementById('toggle-imagenes').textContent = ocultar ? 'Mostrar imágenes' : 'Ocultar imágenes';
    });
</script>

<?= $this->endSection() ?>