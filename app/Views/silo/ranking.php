<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>

<?php
$tipoLabel = ['foto' => 'Fotos', 'video' => 'Vídeos', 'otro' => 'Otros'];
$enlaceFiltro = static fn (?string $t) => site_url('silo/ranking') . '?' . http_build_query(array_filter([
    'tipo' => $t,
    'n'    => $n !== 100 ? $n : null,
]));
?>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('dashboard') ?>">Dashboard</a> / <a href="<?= site_url('silo') ?>">Silo</a> / Lo que más ocupa
</div>
<h1 class="silo-control-titulo">Lo que más <strong>ocupa</strong></h1>

<?php $t = $totales['total'] ?? ['ficheros' => 0, 'bytes' => 0]; ?>
<div class="row g-2 mb-4">
    <div class="col-6 col-md-3">
        <div class="silo-stat h-100">
            <div class="silo-stat-num"><?= esc(silo_formatear_tamano($t['bytes'] ?: null)) ?></div>
            <div class="silo-stat-label">Total en disco</div>
            <div class="text-muted small mt-1"><?= number_format($t['ficheros']) ?> ficheros</div>
        </div>
    </div>
    <?php foreach (['video', 'foto', 'otro'] as $tk): ?>
        <?php $tt = $totales[$tk] ?? ['ficheros' => 0, 'bytes' => 0]; ?>
        <div class="col-6 col-md-3">
            <div class="silo-stat h-100">
                <div class="silo-stat-num"><?= esc(silo_formatear_tamano($tt['bytes'] ?: null)) ?></div>
                <div class="silo-stat-label"><i class="bi <?= silo_icono_tipo($tk) ?>"></i> <?= esc(mb_strtoupper($tipoLabel[$tk])) ?></div>
                <div class="text-muted small mt-1"><?= number_format($tt['ficheros']) ?> ficheros</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div class="btn-group btn-group-sm" role="group" aria-label="Filtrar por tipo">
        <a href="<?= esc($enlaceFiltro(null), 'attr') ?>"
           class="btn btn-outline-secondary <?= $tipo === null ? 'active' : '' ?>">Todos</a>
        <?php foreach (['video', 'foto', 'otro'] as $tk): ?>
            <a href="<?= esc($enlaceFiltro($tk), 'attr') ?>"
               class="btn btn-outline-secondary <?= $tipo === $tk ? 'active' : '' ?>"><?= esc($tipoLabel[$tk]) ?></a>
        <?php endforeach; ?>
    </div>
    <span class="text-muted small">Top <?= (int) $n ?><?= $tipo ? ' · ' . esc($tipoLabel[$tipo]) : '' ?></span>
</div>

<?php if (empty($ficheros)): ?>
    <p class="text-muted">Todavía no hay ficheros con tamaño registrado. El tamaño lo rellena el agente al escanear una unidad Maestro.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle silo-tabla-control">
            <thead>
                <tr>
                    <th class="text-end">#</th>
                    <th>Fichero</th>
                    <th>Carpeta</th>
                    <th>Tipo</th>
                    <th class="text-end">Tamaño</th>
                </tr>
            </thead>
            <tbody>
                <?php $maxByt = (int) ($ficheros[0]['tamano_bytes'] ?? 0); ?>
                <?php foreach ($ficheros as $i => $f): ?>
                    <?php $byt = (int) ($f['tamano_bytes'] ?? 0); ?>
                    <tr>
                        <td class="text-end text-muted"><?= $i + 1 ?></td>
                        <td>
                            <span class="d-flex align-items-center gap-2">
                                <i class="bi <?= silo_icono_tipo($f['tipo']) ?> text-muted"></i>
                                <span class="text-break"><?= esc($f['nombre']) ?></span>
                            </span>
                        </td>
                        <td>
                            <a href="<?= site_url('silo/' . (int) $f['pieza_id']) ?>" class="text-decoration-none small">
                                <?= esc(silo_descripcion_carpeta($f) ?: $f['nombre_carpeta']) ?>
                            </a>
                        </td>
                        <td><span class="badge text-bg-light border"><?= esc($f['tipo']) ?></span></td>
                        <td class="text-end text-nowrap">
                            <span class="silo-mono"><?= esc(silo_formatear_tamano($byt ?: null)) ?></span>
                            <div class="progress mt-1" style="height: 3px;" role="presentation">
                                <div class="progress-bar silo-progress-fill" style="width: <?= $maxByt > 0 ? round($byt / $maxByt * 100) : 0 ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (count($ficheros) >= $n && $n < 500): ?>
        <?php $verMas = site_url('silo/ranking') . '?' . http_build_query(array_filter([
            'tipo' => $tipo,
            'n'    => min($n * 2, 500),
        ])); ?>
        <div class="text-center my-2">
            <a href="<?= esc($verMas, 'attr') ?>" class="btn btn-sm btn-outline-secondary">Ver más</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
