<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-pc-display text-primary"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Mi PC</strong>
    <a href="<?= site_url('silo/unidades') ?>" class="text-decoration-none ms-1 text-muted" title="Gestionar unidades">
        <i class="bi bi-gear"></i>
    </a>
</h5>

<?php
$nivelLabel = [1 => 'Maestro', 2 => 'Año', 3 => 'Temática'];
$hayAlguna  = array_sum(array_map('count', $porNivel)) > 0;
?>

<?php if (!$hayAlguna): ?>
    <p class="text-muted">
        No hay unidades dadas de alta todavía.
        <a href="<?= site_url('silo/unidades') ?>">Crear la primera</a>.
    </p>
<?php else: ?>
    <?php foreach ([1, 2, 3] as $nivel): ?>
        <?php if (empty($porNivel[$nivel])) { continue; } ?>
        <h6 class="text-muted mt-4 mb-2">Nivel <?= $nivel ?> — <?= $nivelLabel[$nivel] ?></h6>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
            <?php foreach ($porNivel[$nivel] as $u): ?>
                <?php
                $numPiezas = $piezasPorUnidad[$u['id']] ?? 0;
                $uso       = $usoPorUnidad[$u['id']] ?? 0;
                $cap       = (int) ($u['capacidad_bytes'] ?? 0);
                $pct       = $cap > 0 ? min(100, (int) round($uso / $cap * 100)) : null;
                ?>
                <div class="col">
                    <a href="<?= site_url('silo/unidades/' . $u['id']) ?>"
                       class="text-decoration-none text-body d-block h-100 border rounded p-3 silo-carpeta">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-hdd-fill text-secondary fs-4"></i>
                            <span class="fw-semibold text-truncate">
                                <?= esc($u['etiqueta'] ?: 'Unidad #' . (int) $u['numero']) ?>
                            </span>
                            <?php if ((int) $u['sellada']): ?>
                                <span class="badge text-bg-secondary ms-auto">sellada</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($pct !== null): ?>
                            <div class="progress mb-1" style="height: 6px;">
                                <div class="progress-bar <?= $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : '') ?>"
                                     style="width: <?= $pct ?>%"></div>
                            </div>
                            <div class="small text-muted">
                                <?= esc(silo_formatear_tamano($uso)) ?> de <?= esc(silo_formatear_tamano($cap)) ?>
                                · <?= $numPiezas ?> carpeta<?= $numPiezas === 1 ? '' : 's' ?>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted">
                                <?= $numPiezas ?> carpeta<?= $numPiezas === 1 ? '' : 's' ?>
                                <?php if ($uso): ?> · <?= esc(silo_formatear_tamano($uso)) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($nivel !== 1 && $u['agrupador']): ?>
                            <span class="badge text-bg-light border mt-2"><?= esc($u['agrupador']) ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <style>
        .silo-carpeta { transition: background-color .12s ease, border-color .12s ease; }
        .silo-carpeta:hover { background-color: var(--bs-tertiary-bg); border-color: var(--bs-secondary); }
    </style>
<?php endif; ?>

<?= $this->endSection() ?>
