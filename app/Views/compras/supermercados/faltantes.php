<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">📝 Compras / <strong><?= esc($supermercado_nombre) ?></strong> / FALTA</h2>

<div class="mb-4">
    <a href="<?= site_url('compras/productos/' . $supermercado_id) ?>" class="btn btn-outline-secondary">← Volver a productos</a>
    <button id="toggle-imagenes" class="btn btn-sm btn-outline-secondary ms-3">Ocultar imágenes</button>
</div>

<!-- Accesos rápidos a listas -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 mb-4">
    <div class="col">
        <a href="<?= site_url('compras/' . $supermercado_id . '/comprados') ?>" class="text-decoration-none text-dark">
            <div class="card shadow-sm h-100 border-success border-2">
                <div class="card-body text-center">
                    <h5 class="card-title">🛍️ Ir a comprar</h5>
                    <p class="card-text text-muted">Lista de productos que has comprado.</p>
                </div>
            </div>
        </a>
    </div>
</div>

<form class="text-end mb-2" action="<?= site_url('compras/limpiar/faltantes/' . $supermercado_id) ?>" method="post" class="mb-3" onsubmit="return confirm('¿Seguro que deseas reiniciar todos los faltantes?')">
    <?= csrf_field() ?>
    <button class="btn btn-outline-danger">🧹 Reiniciar faltantes</button>
</form>

<!-- Lista de productos -->
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
    <?php foreach ($productos as $producto): ?>
        <div class="col">
            <div
                class="card h-100 shadow-sm text-center producto-card <?= $producto['faltante'] ? 'border border-warning border-3' : '' ?>"
                data-producto-id="<?= $producto['id'] ?>"
                data-faltante="<?= $producto['faltante'] ? '1' : '0' ?>"
                style="cursor: pointer;">
                <?php if (!empty($producto['imagen'])): ?>
                    <img src="<?= esc($producto['imagen']) ?>" class="card-img-top imagen-producto" style="object-fit: cover; height: 150px;">
                <?php endif; ?>
                <div class="card-body">
                    <h6 class="card-title mb-0"><?= esc($producto['nombre']) ?></h6>
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
                    card.classList.toggle('border-warning');
                    card.classList.toggle('border-3');
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