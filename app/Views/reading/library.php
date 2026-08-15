<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-book text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Lectura</strong>

    <a href="<?= site_url('reading/nuevo') ?>" class="text-decoration-none ms-1 text-success" title="Añadir libro">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<ul class="nav nav-tabs rd-tabs mb-3 flex-nowrap overflow-auto">
    <?php foreach ($estados as $valor => $etiqueta): ?>
        <li class="nav-item">
            <a class="nav-link <?= $valor === $tabActual ? 'active' : '' ?>" href="<?= site_url('reading') . '?' . http_build_query(['tab' => $valor]) ?>">
                <?= esc($etiqueta) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (empty($libros)): ?>
    <p class="text-muted">
        <?= $tabActual === 'quiero_leer' ? 'Todavía no hay libros en la lista. Añade el primero con el botón "+".' : 'No hay libros aquí todavía.' ?>
    </p>
<?php else: ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
        <?php foreach ($libros as $libro): ?>
            <div class="col">
                <a href="<?= site_url('reading/libro/' . (int) $libro['id']) ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm rd-book-card">
                        <div class="rd-cover">
                            <?php if (!empty($libro['cover_url'])): ?>
                                <img src="<?= esc($libro['cover_url'], 'attr') ?>" alt="">
                            <?php else: ?>
                                <i class="bi bi-book"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-2">
                            <div class="rd-book-title"><?= esc($libro['title']) ?></div>
                            <?php if (!empty($libro['author'])): ?>
                                <div class="rd-book-author"><?= esc($libro['author']) ?></div>
                            <?php endif; ?>
                            <?php if ($libro['progreso'] !== null): ?>
                                <div class="rd-progress mt-2">
                                    <div class="rd-progress-fill" style="width: <?= (int) $libro['progreso'] ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.rd-tabs .nav-link { color: var(--bs-secondary-color); }
.rd-book-card { border-color: var(--bs-border-color); }
.rd-cover {
    aspect-ratio: 2 / 3;
    background: var(--bs-tertiary-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--bs-secondary-color);
    overflow: hidden;
    border-radius: 6px 6px 0 0;
}
.rd-cover img { width: 100%; height: 100%; object-fit: cover; }
.rd-book-title {
    font-size: .85rem;
    font-weight: 600;
    color: var(--bs-emphasis-color);
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.rd-book-author { font-size: .75rem; color: var(--bs-secondary-color); }
.rd-progress { height: 4px; border-radius: 2px; background: var(--bs-tertiary-bg); overflow: hidden; }
.rd-progress-fill { height: 100%; background: var(--bs-primary); }
</style>

<?= $this->endSection() ?>
