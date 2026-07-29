<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<!-- Header / breadcrumb -->
<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart3 text-info"></i>

    <span class="text-secondary fw-normal">Compras</span>
    <span class="text-secondary">/</span>

    <strong class="fw-semibold">
        <a href="<?= site_url('compras/productos/' . $supermercado_id) ?>"
            class="text-decoration-none text-body">
            <?= esc($supermercado_nombre) ?>
        </a>
    </strong>

    <span class="text-secondary">/</span>

    <span class="fw-semibold text-success">
        COMPRAR
    </span>
</h5>

<!-- Acciones -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <button id="toggle-imagenes" class="btn btn-outline-secondary btn-sm">
        Ocultar imágenes
    </button>

    <a href="<?= site_url('compras/' . $supermercado_id . '/faltantes') ?>"
        class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil-square"></i>
        FALTA
    </a>
</div>

<!-- Productos, agrupados por zona en el orden del recorrido -->
<div id="productos-container">
    <?php foreach ($grupos as $grupo): ?>
        <h6 class="text-muted small text-uppercase mt-3 mb-2">
            <?= $grupo['zona'] ? esc($grupo['zona']['nombre']) : 'Sin zona' ?>
        </h6>

        <div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-2 mb-2">
            <?php foreach ($grupo['productos'] as $producto): ?>
                <div class="col d-flex">
                    <div class="card producto-card w-100 small text-center d-flex flex-column justify-content-between
                                border-2 <?= $producto['comprado'] ? 'border-success' : 'border-transparent' ?>"
                        data-producto-id="<?= $producto['id'] ?>"
                        data-comprado="<?= $producto['comprado'] ? '1' : '0' ?>">

                        <?php if (!empty($producto['imagen'])): ?>
                            <img src="<?= esc($producto['imagen']) ?>"
                                class="producto-imagen img-fluid mb-2 mx-auto"
                                style="max-height:120px; object-fit:contain;">
                        <?php endif; ?>

                        <div class="card-body p-2 d-flex align-items-center justify-content-center">
                            <div class="fw-semibold">
                                <?= esc($producto['nombre']) ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
    const container = document.getElementById('productos-container');
    const toggleBtn = document.getElementById('toggle-imagenes');

    /* ===== Toggle imágenes / lista ===== */
    toggleBtn.addEventListener('click', () => {
        const imgs = document.querySelectorAll('.producto-imagen');
        const ocultar = imgs[0]?.style.display !== 'none';

        imgs.forEach(img => img.style.display = ocultar ? 'none' : '');
        toggleBtn.textContent = ocultar ? 'Mostrar imágenes' : 'Ocultar imágenes';

        container.classList.toggle('lista', ocultar);
    });

    /* ===== Estado visual inicial ===== */
    document.querySelectorAll('.producto-card').forEach(card => {
        if (card.dataset.comprado === '1') {
            card.classList.add('bg-success-subtle');
        }
    });

    /* ===== Toggle comprado ===== */
    document.querySelectorAll('.producto-card').forEach(card => {

        let startX = 0;
        let startY = 0;
        let moved = false;

        const toggleComprado = async () => {
            const productoId = card.dataset.productoId;
            const esComprado = card.dataset.comprado === '1';

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

                if (!response.ok) return;

                card.dataset.comprado = esComprado ? '0' : '1';

                card.classList.toggle('bg-success-subtle', !esComprado);
                card.classList.toggle('border-success', !esComprado);
                card.classList.toggle('border-transparent', esComprado);

            } catch (err) {
                console.error(err);
            }
        };

        /* Touch */
        card.addEventListener('touchstart', e => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            moved = false;
        }, {
            passive: true
        });

        card.addEventListener('touchmove', e => {
            if (Math.abs(e.touches[0].clientX - startX) > 10 ||
                Math.abs(e.touches[0].clientY - startY) > 10) {
                moved = true;
            }
        }, {
            passive: true
        });

        card.addEventListener('touchend', () => {
            if (!moved) toggleComprado();
        });

        /* Click */
        card.addEventListener('click', toggleComprado);
    });
</script>

<?= $this->endSection() ?>