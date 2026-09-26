<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('silo') ?>">Silo</a> / Vocabulario
</div>
<h1 class="silo-control-titulo">Vocabulario</h1>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php $etiquetas = ['categoria' => 'Categorías', 'evento' => 'Eventos', 'lugar' => 'Lugares', 'persona' => 'Personas', 'tema' => 'Temas']; ?>

<?php $indice = 0; ?>
<?php foreach ($porTipo as $tipo => $items): $indice++; ?>
    <div class="mb-4">
        <div class="silo-seccion-header-row">
            <span class="silo-seccion-num"><?= sprintf('%02d', $indice) ?></span>
            <h6 class="silo-seccion-titulo"><?= esc(mb_strtoupper($etiquetas[$tipo] ?? $tipo)) ?></h6>
            <div class="silo-seccion-linea"></div>
        </div>
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
