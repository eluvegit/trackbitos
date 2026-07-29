<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-cart3 text-info"></i>

    <span class="text-secondary">Compras</span>
    <span class="text-secondary">/</span>

    <a href="<?= site_url('compras/productos/' . $supermercado_id) ?>"
        class="fw-semibold text-decoration-none link-light">
        <?= esc($supermercado_nombre) ?>
    </a>

    <span class="text-secondary">/</span>

    <span class="fw-semibold text-warning">
        FALTA
    </span>
</h5>

<!-- Acciones -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">

    <form action="<?= site_url('compras/limpiar/faltantes/' . $supermercado_id) ?>"
        method="post"
        class="m-0"
        onsubmit="return confirm('¿Seguro que deseas reiniciar todos los faltantes?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger btn-sm">
            🧹
        </button>
    </form>

    <button id="toggle-imagenes" class="btn btn-outline-light btn-sm">
        Ocultar imágenes
    </button>

    <a href="<?= site_url('compras/' . $supermercado_id . '/comprados') ?>"
        class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart-check"></i>
        COMPRAR
    </a>
</div>

<!-- Productos, agrupados por zona en el orden del recorrido -->
<div id="productos-container">
    <?php foreach ($grupos as $grupo): ?>
        <div class="zona-grupo">
            <h6 class="text-muted small text-uppercase mb-2">
                <?= $grupo['zona'] ? esc($grupo['zona']['nombre']) : 'Sin zona' ?>
            </h6>

            <div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 g-2">
                <?php foreach ($grupo['productos'] as $producto): ?>
                    <div class="col d-flex">
                        <div class="card producto-card w-100 small text-center d-flex flex-column justify-content-between"
                            data-producto-id="<?= $producto['id'] ?>"
                            data-faltante="<?= $producto['faltante'] ? '1' : '0' ?>">

                            <?php if (!empty($producto['imagen'])): ?>
                                <img src="<?= esc($producto['imagen']) ?>"
                                    class="img-fluid mb-2 mx-auto producto-imagen"
                                    style="max-height:120px; object-fit:contain;">
                            <?php endif; ?>

                            <div class="card-body p-1 d-flex align-items-center justify-content-center">
                                <div class="fw-semibold text-center">
                                    <?= esc($producto['nombre']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.zona-grupo + .zona-grupo {
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--bs-border-color-translucent);
}
</style>

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
        if (card.dataset.faltante === '1') {
            marcarFaltante(card);
        }
    });

    /* ===== Helpers visuales ===== */
    function marcarFaltante(card) {
        card.classList.add('border-warning', 'border-2', 'bg-warning-subtle');
    }

    function desmarcarFaltante(card) {
        card.classList.remove('border-warning', 'border-2', 'bg-warning-subtle');
    }

    /* ===== Toggle faltante ===== */
    document.querySelectorAll('.producto-card').forEach(card => {

        let startX = 0;
        let startY = 0;
        let moved = false;
        let touchHandled = false;

        const toggleFaltante = async () => {
            const productoId = card.dataset.productoId;
            const esFaltante = card.dataset.faltante === '1';

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

                if (!response.ok) return;

                // 🔒 Estado fuente de la verdad
                card.dataset.faltante = esFaltante ? '0' : '1';

                if (esFaltante) {
                    desmarcarFaltante(card);
                } else {
                    marcarFaltante(card);
                }

            } catch (e) {
                console.error(e);
            }
        };

        /* --- Touch --- */
        card.addEventListener('touchstart', e => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            moved = false;
            touchHandled = false;
        }, {
            passive: true
        });

        card.addEventListener('touchmove', e => {
            if (
                Math.abs(e.touches[0].clientX - startX) > 10 ||
                Math.abs(e.touches[0].clientY - startY) > 10
            ) {
                moved = true;
            }
        }, {
            passive: true
        });

        card.addEventListener('touchend', e => {
            if (moved) return;

            touchHandled = true;
            toggleFaltante();
        });

        /* --- Click (solo desktop) --- */
        card.addEventListener('click', () => {
            if (touchHandled) return; // ⛔ evita doble ejecución
            toggleFaltante();
        });
    });
</script>


<?= $this->endSection() ?>