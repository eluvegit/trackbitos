<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-tags text-primary"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Vocabulario</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php $etiquetas = ['categoria' => 'Categorías', 'evento' => 'Eventos', 'lugar' => 'Lugares', 'persona' => 'Personas', 'tema' => 'Temas']; ?>

<div class="row">
    <?php foreach ($porTipo as $tipo => $items): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <h6><?= esc($etiquetas[$tipo] ?? $tipo) ?></h6>
            <?php if (empty($items)): ?>
                <p class="text-muted small">Ninguno todavía.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($items as $item): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 px-0">
                            <form method="post" action="<?= site_url('silo/vocabulario/renombrar/' . $item['id']) ?>"
                                  class="d-flex gap-1 flex-grow-1">
                                <?= csrf_field() ?>
                                <input type="text" name="nombre" class="form-control form-control-sm" value="<?= esc($item['nombre']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
