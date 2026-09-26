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
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php $etiquetas = ['categoria' => 'Categorías', 'evento' => 'Eventos', 'lugar' => 'Lugares', 'persona' => 'Personas', 'tema' => 'Temas']; ?>

<?php foreach ($porTipo as $tipo => $items): ?>
    <div class="mb-4">
        <h6 class="text-muted text-uppercase small fw-semibold mb-2"><?= esc($etiquetas[$tipo] ?? $tipo) ?></h6>
        <?php if (empty($items)): ?>
            <p class="text-muted small">Ninguno todavía.</p>
        <?php else: ?>
            <?php $paramFiltro = $tipo === 'categoria' ? 'categoria_id' : 'atributo_id'; ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($items as $item): ?>
                    <?php $sinUso = ($item['usos'] ?? 0) === 0; ?>
                    <?php $enlace = site_url('silo') . '?' . http_build_query([$paramFiltro => $item['id']]); ?>
                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 fw-normal <?= $sinUso ? 'bg-body-secondary text-muted border' : 'text-bg-light border' ?>"
                          title="<?= $sinUso ? 'Sin uso' : $item['usos'] . ' pieza(s) — pinchar para ver' ?>">
                        <a href="<?= esc($enlace, 'attr') ?>" class="text-reset text-decoration-none">
                            <?= esc($item['nombre']) ?>
                            <span class="opacity-50" style="font-size:.75em;"><?= (int) ($item['usos'] ?? 0) ?></span>
                        </a>
                        <?php if ($sinUso): ?>
                            <form method="post" action="<?= site_url('silo/vocabulario/' . $item['id'] . '/borrar') ?>"
                                  onsubmit="return confirm('¿Borrar «<?= esc($item['nombre'], 'js') ?>»? No se puede deshacer.');" class="d-inline lh-1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link btn-sm p-0 text-danger lh-1" title="Borrar (sin uso)">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
