<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Lentillas
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Stock</span>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-box-seam"></i>
    </div>
    <div>
        <h2 class="mb-0">Stock</h2>
        <small class="text-muted">Pares de lentillas, líquidos y materiales</small>
    </div>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <div><?= session('message') ?></div>
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('lentillas/stock/actualizar') ?>">
    <?= csrf_field() ?>

    <div class="row g-4">
        <?php foreach ($items as $item):
            $cantidad = (int) $item['cantidad'];
            $unidadTexto = $cantidad === 1 ? 'unidad' : 'unidades';
            $nombre = strtolower($item['item']);

            $tipo = match (true) {
                str_contains($nombre, 'izquierda') => ['icon' => 'bi-eye', 'color' => 'primary', 'badge' => 'OI', 'badgeClass' => 'ojo-badge-izq'],
                str_contains($nombre, 'derecha')   => ['icon' => 'bi-eye', 'color' => 'info', 'badge' => 'OD', 'badgeClass' => 'ojo-badge-der'],
                str_contains($nombre, 'líquido')   => ['icon' => 'bi-droplet', 'color' => 'success', 'badge' => null, 'badgeClass' => null],
                str_contains($nombre, 'estuche')   => ['icon' => 'bi-briefcase', 'color' => 'warning', 'badge' => null, 'badgeClass' => null],
                default                             => ['icon' => 'bi-box-seam', 'color' => 'secondary', 'badge' => null, 'badgeClass' => null],
            };

            $stockBajo = $cantidad <= 2;
        ?>
            <div class="col-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm lentillas-card">
                    <div class="lentillas-card-accent bg-<?= $tipo['color'] ?>"></div>
                    <div class="card-body p-3 p-md-4 text-center">
                        <div class="d-flex justify-content-center mb-3">
                            <div class="datos-icon-wrap position-relative">
                                <div class="datos-icon bg-<?= $tipo['color'] ?> bg-opacity-10 text-<?= $tipo['color'] ?>">
                                    <i class="bi <?= $tipo['icon'] ?>"></i>
                                </div>
                                <?php if ($stockBajo): ?>
                                    <span class="stock-alert-dot" title="Stock bajo">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 class="stock-title mb-1"><?= esc($item['item']) ?></h6>
                        <?php if ($tipo['badge']): ?>
                            <span class="badge rounded-pill ojo-badge <?= $tipo['badgeClass'] ?> mb-1"><?= $tipo['badge'] ?></span>
                        <?php endif; ?>

                        <div class="stock-qty fw-bold mt-2 <?= $stockBajo ? 'text-warning' : '' ?>"><?= $cantidad ?></div>
                        <div class="text-muted small mb-3"><?= $unidadTexto ?></div>

                        <div class="input-group input-group-sm stock-stepper mx-auto" style="max-width: 140px;">
                            <button type="button" class="btn btn-outline-secondary" data-action="decrement">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input
                                type="number"
                                name="items[<?= $item['id'] ?>]"
                                class="form-control text-center stock-input"
                                value="<?= $cantidad ?>"
                                min="0">
                            <button type="button" class="btn btn-outline-secondary" data-action="increment">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i>Actualizar inventario
        </button>
    </div>
</form>

<style>
    .stock-title {
        font-size: clamp(.85rem, 3vw, 1.1rem);
    }

    .stock-qty {
        font-size: clamp(1.75rem, 8vw, 3rem);
    }

    @media (max-width: 575.98px) {
        .datos-icon {
            width: 40px;
            height: 40px;
            font-size: 1.05rem;
        }
    }

    .stock-alert-dot {
        position: absolute;
        top: -2px;
        right: -4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #ffc107;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .6rem;
        border: 2px solid var(--bs-body-bg);
    }

    .stock-stepper .stock-input {
        -moz-appearance: textfield;
    }

    .stock-stepper .stock-input::-webkit-outer-spin-button,
    .stock-stepper .stock-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
</style>

<script>
    document.querySelectorAll('.stock-stepper').forEach(function (group) {
        var input = group.querySelector('.stock-input');
        group.querySelectorAll('button[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var step = btn.dataset.action === 'increment' ? 1 : -1;
                var value = Math.max(0, (parseInt(input.value, 10) || 0) + step);
                input.value = value;
            });
        });
    });
</script>

<?= $this->endSection() ?>
