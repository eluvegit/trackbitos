<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>
<?= $this->include('silo/_estilos_nivel') ?>

<style>
    .silo-tarjeta-mipc {
        cursor: pointer;
    }

    .silo-tarjeta-mipc:hover {
        transform: translateY(-3px);
        box-shadow: 0 .6rem 1.2rem rgba(0, 0, 0, .18);
        border-color: var(--silo-accent, var(--bs-primary));
    }

    .silo-tarjeta-aviso {
        display: flex;
        align-items: flex-start;
        gap: .35rem;
        font-size: .68rem;
        color: var(--bs-warning-text-emphasis);
        margin-top: .3rem;
    }

    .silo-tarjeta-identificacion {
        display: flex;
        align-items: flex-start;
        gap: .35rem;
        font-size: .68rem;
        color: var(--bs-secondary-color);
        white-space: pre-line;
        margin-top: .3rem;
    }
</style>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('silo') ?>">Silo</a> / Mi PC
    <span class="silo-control-iconos">
        <a href="<?= site_url('silo/unidades') ?>" title="Gestionar unidades"><i class="bi bi-gear"></i></a>
    </span>
</div>
<h1 class="silo-control-titulo">Mi <strong>PC</strong></h1>

<?php
$nivelInfo = [
    1 => ['titulo' => 'Nivel Maestro',  'sub' => 'Archivo principal'],
    2 => ['titulo' => 'Nivel Año',      'sub' => 'Archivo cronológico'],
    3 => ['titulo' => 'Nivel Temática', 'sub' => 'Archivo temático'],
];
$hayAlguna = array_sum(array_map('count', $porNivel)) > 0;
?>

<?php if (!$hayAlguna): ?>
    <p class="text-muted">
        No hay unidades dadas de alta todavía.
        <a href="<?= site_url('silo/unidades') ?>">Crear la primera</a>.
    </p>
<?php else: ?>
    <?php foreach ([1, 2, 3] as $nivel): ?>
        <?php if (empty($porNivel[$nivel])) { continue; } ?>
        <div class="silo-seccion-header-row silo-nivel silo-n<?= $nivel ?>">
            <span class="silo-seccion-num"><?= sprintf('%02d', $nivel) ?></span>
            <h6 class="silo-seccion-titulo"><?= esc(mb_strtoupper($nivelInfo[$nivel]['titulo'])) ?></h6>
            <span class="silo-seccion-sub"><?= esc(mb_strtoupper($nivelInfo[$nivel]['sub'])) ?></span>
            <div class="silo-seccion-linea"></div>
        </div>
        <div class="silo-fila-unidades silo-nivel silo-n<?= $nivel ?>">
            <?php foreach ($porNivel[$nivel] as $u): ?>
                <?php $cap = (int) ($u['capacidad_bytes'] ?? 0); ?>
                <a href="<?= site_url('silo/unidades/' . $u['id']) ?>"
                   class="silo-tarjeta silo-tarjeta-mipc text-decoration-none text-body">
                    <div class="silo-tarjeta-top">
                        <div class="silo-tarjeta-capacidad">
                            <?= $cap > 0 ? esc(silo_formatear_tamano($cap)) : '—' ?>
                        </div>
                        <span class="silo-tarjeta-idbadge">#<?= (int) $u['id'] ?></span>
                    </div>
                    <div class="silo-tarjeta-nombre-linea">
                        <i class="bi bi-hdd-fill silo-hdd"></i>
                        <span class="silo-tarjeta-nombre">
                            <?= esc(silo_nombre_sin_contenido($u['etiqueta'] ?: 'Unidad #' . (int) $u['numero'])) ?>
                        </span>
                    </div>
                    <?php $badgesEtq = silo_badges_contenido($u['etiqueta'] ?? ''); ?>
                    <?php if ($badgesEtq !== ''): ?>
                        <div class="silo-tarjeta-contenido"><?= $badgesEtq ?></div>
                    <?php endif; ?>
                    <?php if (!empty($u['identificacion_fisica'])): ?>
                        <div class="silo-tarjeta-identificacion">
                            <i class="bi bi-upc-scan flex-shrink-0 mt-1"></i>
                            <span><?= esc($u['identificacion_fisica']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="silo-tarjeta-aviso">
                            <i class="bi bi-exclamation-triangle flex-shrink-0 mt-1"></i>
                            <span>Sin identificar qué disco físico es — <span class="text-decoration-underline">añádelo en Unidades</span></span>
                        </div>
                    <?php endif; ?>
                    <?php $bucketsTexto = $bucketsPorUnidad[$u['id']] ?? ''; ?>
                    <?php if ($nivel !== 1 && ($bucketsTexto !== '' || $u['agrupador'])): ?>
                        <div class="silo-tarjeta-detalle"><?= esc($bucketsTexto !== '' ? $bucketsTexto : $u['agrupador']) ?></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
