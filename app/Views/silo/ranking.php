<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$tipoLabel = ['foto' => 'Fotos', 'video' => 'Vídeos', 'otro' => 'Otros'];
$enlaceFiltro = static fn (?string $t) => site_url('silo/ranking') . '?' . http_build_query(array_filter([
    'tipo' => $t,
    'n'    => $n !== 100 ? $n : null,
]));
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-bar-chart-line text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Lo que más ocupa</strong>
</h5>

<?php $t = $totales['total'] ?? ['ficheros' => 0, 'bytes' => 0]; ?>
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="text-muted small">Total en disco</div>
            <div class="fs-5 fw-semibold"><?= esc(silo_formatear_tamano($t['bytes'] ?: null)) ?></div>
            <div class="text-muted small"><?= number_format($t['ficheros']) ?> ficheros</div>
        </div>
    </div>
    <?php foreach (['video', 'foto', 'otro'] as $tk): ?>
        <?php $tt = $totales[$tk] ?? ['ficheros' => 0, 'bytes' => 0]; ?>
        <div class="col-6 col-md-3">
            <div class="border rounded p-2 h-100">
                <div class="text-muted small">
                    <i class="bi <?= silo_icono_tipo($tk) ?>"></i> <?= esc($tipoLabel[$tk]) ?>
                </div>
                <div class="fs-5 fw-semibold"><?= esc(silo_formatear_tamano($tt['bytes'] ?: null)) ?></div>
                <div class="text-muted small"><?= number_format($tt['ficheros']) ?> ficheros</div>
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
        <table class="table table-sm align-middle">
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
                        <td class="d-flex align-items-center gap-2">
                            <i class="bi <?= silo_icono_tipo($f['tipo']) ?> text-muted"></i>
                            <span class="text-break"><?= esc($f['nombre']) ?></span>
                        </td>
                        <td>
                            <a href="<?= site_url('silo/' . (int) $f['pieza_id']) ?>" class="text-decoration-none small">
                                <?= esc(silo_descripcion_carpeta($f) ?: $f['nombre_carpeta']) ?>
                            </a>
                        </td>
                        <td><span class="badge text-bg-light border"><?= esc($f['tipo']) ?></span></td>
                        <td class="text-end text-nowrap">
                            <?= esc(silo_formatear_tamano($byt ?: null)) ?>
                            <div class="progress mt-1" style="height: 3px;" role="presentation">
                                <div class="progress-bar" style="width: <?= $maxByt > 0 ? round($byt / $maxByt * 100) : 0 ?>%;"></div>
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
