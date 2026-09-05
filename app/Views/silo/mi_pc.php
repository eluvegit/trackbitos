<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_nivel') ?>

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
        <h6 class="text-muted mt-4 mb-2 silo-nivel silo-n<?= $nivel ?>">
            <span class="silo-nivel-titulo">Nivel <?= $nivel ?> — <?= $nivelLabel[$nivel] ?></span>
        </h6>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 silo-nivel silo-n<?= $nivel ?>">
            <?php foreach ($porNivel[$nivel] as $u): ?>
                <?php $cap = (int) ($u['capacidad_bytes'] ?? 0); ?>
                <div class="col">
                    <a href="<?= site_url('silo/unidades/' . $u['id']) ?>"
                       class="text-decoration-none text-body d-block h-100 border rounded p-3 silo-carpeta">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-hdd-fill silo-hdd fs-4"></i>
                            <span class="fw-semibold text-truncate">
                                <?= esc($u['etiqueta'] ?: 'Unidad #' . (int) $u['numero']) ?>
                            </span>
                            <?php if ($cap > 0): ?>
                                <span class="badge text-bg-light border ms-auto"><?= esc(silo_formatear_tamano($cap)) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($u['identificacion_fisica'])): ?>
                            <div class="small d-flex gap-1">
                                <i class="bi bi-upc-scan flex-shrink-0 mt-1 text-muted"></i>
                                <span style="white-space: pre-line;"><?= esc($u['identificacion_fisica']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="small text-warning-emphasis d-flex gap-1">
                                <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1"></i>
                                <span>Sin identificar qué disco físico es — <span class="text-decoration-underline">añádelo en Unidades</span></span>
                            </div>
                        <?php endif; ?>
                        <?php $bucketsTexto = $bucketsPorUnidad[$u['id']] ?? ''; ?>
                        <?php if ($nivel !== 1 && ($bucketsTexto !== '' || $u['agrupador'])): ?>
                            <span class="badge text-bg-light border mt-2"><?= esc($bucketsTexto !== '' ? $bucketsTexto : $u['agrupador']) ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
