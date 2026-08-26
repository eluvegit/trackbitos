<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$hayAvisos = $mostrarAlerta || !empty($recordatoriosUrgentes);
$hayCaducado = !empty(array_filter($recordatoriosUrgentes, fn($r) => $r['nivel'] === 'caducado'));
?>

<div class="row g-4 mt-1">
    <aside class="col-12 col-lg-3" id="dashboard-sidebar">
        <div class="d-flex justify-content-end mb-1">
            <button type="button" class="btn-close" id="dashboard-sidebar-close" aria-label="Ocultar panel lateral"></button>
        </div>

        <?php if ($hayAvisos): ?>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted small mb-2">Avisos</h6>

                <?php if ($mostrarAlerta): ?>
                    <div class="alert alert-warning py-2 px-3 small">
                        🔔 Han pasado <?= $dias ?> días desde la última sustitución de lentillas. ¡Es hora de cambiarlas!
                    </div>
                <?php endif; ?>

                <?php if (!empty($recordatoriosUrgentes)): ?>
                    <div class="alert <?= $hayCaducado ? 'alert-danger' : 'alert-warning' ?> py-2 px-3 small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-calendar-heart"></i>
                            <strong>Recordatorios próximos</strong>
                        </div>
                        <ul class="mb-2 ps-3">
                            <?php foreach ($recordatoriosUrgentes as $r): ?>
                                <li>
                                    <?= esc($r['titulo']) ?>
                                    — <span class="fw-semibold"><?= esc($r['texto']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= site_url('recordatorios') ?>" class="alert-link">Ver todos los recordatorios →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($enlacesRapidos)): ?>
            <div>
                <h6 class="text-uppercase text-muted small mb-2">Enlaces rápidos</h6>
                <div class="list-group list-group-flush">
                    <?php foreach ($enlacesRapidos as $enlace): ?>
                        <a href="<?= site_url($enlace['ruta']) ?>" class="list-group-item list-group-item-action bg-transparent d-flex align-items-center gap-2 px-0">
                            <i class="bi <?= esc($enlace['icono']) ?>"></i>
                            <?= esc($enlace['titulo']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </aside>

    <div class="col-12 col-lg-9" id="dashboard-main">
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3 align-items-center gap-1" id="dashboard-sidebar-show" style="display: none;">
            <i class="bi bi-layout-sidebar-inset"></i> Mostrar panel
        </button>

        <div id="dashboard-grid" class="row row-cols-3 row-cols-sm-4 row-cols-md-4 row-cols-lg-5 g-3">
            <?php foreach ($secciones as $sec): ?>
                <div class="col d-flex position-relative" data-key="<?= esc($sec['ruta']) ?>">
                    <span class="db-drag-handle" title="Arrastrar para reordenar">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
                    <a href="<?= site_url($sec['ruta']) ?>" class="text-decoration-none w-100">
                        <div class="card shadow-sm w-100" style="aspect-ratio: 1 / 1;">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-2">
                                <div class="mb-2" style="font-size: 2rem; line-height: 1;">
                                    <?= $sec['icono'] ?>
                                </div>
                                <h6 class="card-title mb-1"><?= $sec['titulo'] ?></h6>
                                <p class="mb-0 small text-muted d-none d-md-block">
                                    <?= $sec['texto'] ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.db-drag-handle {
    position: absolute;
    top: 4px;
    right: 4px;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    color: rgba(0, 0, 0, .45);
    cursor: grab;
    touch-action: none;
}
#dashboard-grid.db-sorting .db-drag-handle { cursor: grabbing; }
.db-ghost { opacity: .4; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Ocultar/mostrar el panel lateral (avisos + enlaces rápidos)
    var sidebar = document.getElementById('dashboard-sidebar');
    var main = document.getElementById('dashboard-main');
    var sidebarCloseBtn = document.getElementById('dashboard-sidebar-close');
    var sidebarShowBtn = document.getElementById('dashboard-sidebar-show');
    var SIDEBAR_KEY = 'trackbitos_dashboard_sidebar_hidden';

    if (sidebar && main && sidebarCloseBtn && sidebarShowBtn) {
        var setSidebarHidden = function (hidden) {
            sidebar.classList.toggle('d-none', hidden);
            main.classList.toggle('col-lg-9', !hidden);
            main.classList.toggle('col-lg-12', hidden);
            sidebarShowBtn.style.display = hidden ? 'inline-flex' : 'none';
            try { localStorage.setItem(SIDEBAR_KEY, hidden ? '1' : '0'); } catch (e) {}
        };

        var hiddenStored = false;
        try { hiddenStored = localStorage.getItem(SIDEBAR_KEY) === '1'; } catch (e) {}
        setSidebarHidden(hiddenStored);

        sidebarCloseBtn.addEventListener('click', function () { setSidebarHidden(true); });
        sidebarShowBtn.addEventListener('click', function () { setSidebarHidden(false); });
    }

    var grid = document.getElementById('dashboard-grid');
    if (!grid) return;

    var STORAGE_KEY = 'trackbitos_dashboard_order';

    function getStoredOrder() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function applyStoredOrder() {
        var saved = getStoredOrder();
        if (!saved.length) return;

        var items = Array.from(grid.children);
        var byKey = {};
        items.forEach(function (el) { byKey[el.dataset.key] = el; });

        saved.forEach(function (key) {
            if (byKey[key]) {
                grid.appendChild(byKey[key]);
                delete byKey[key];
            }
        });

        // Secciones nuevas que no estaban guardadas: se añaden al final,
        // manteniendo el orden original entre ellas.
        items.forEach(function (el) {
            if (byKey[el.dataset.key]) {
                grid.appendChild(el);
            }
        });
    }

    function saveOrder() {
        var order = Array.from(grid.children).map(function (el) { return el.dataset.key; });
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(order));
        } catch (e) {}
    }

    applyStoredOrder();

    new Sortable(grid, {
        handle: '.db-drag-handle',
        animation: 150,
        ghostClass: 'db-ghost',
        onStart: function () { grid.classList.add('db-sorting'); },
        onEnd: function () {
            grid.classList.remove('db-sorting');
            saveOrder();
        }
    });
});
</script>

<?= $this->endSection() ?>
