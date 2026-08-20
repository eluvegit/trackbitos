<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
/** KB para lo pequeño, MB con un decimal en cuanto pasa de 1 MB — mismo criterio que la ficha de variante. */
$tamanoLegible = function (int $bytes): string {
    return $bytes >= 1024 * 1024
        ? number_format($bytes / (1024 * 1024), 1) . ' MB'
        : number_format($bytes / 1024, 0) . ' KB';
};
$maximo = $piezas === [] ? 0 : max(array_column($piezas, 'bytes'));
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-hdd-stack text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Estadísticas</strong>

    <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="btn btn-sm btn-outline-secondary ms-auto"
        title="Fotografía de ahora mismo: el .blend de referencia de cada pieza (validada, o si no hay la más reciente) más su historial en texto. Sin STL ni fotos — se pueden rehacer.">
        <i class="bi bi-download"></i> Copia de seguridad
    </a>
</h5>

<p class="text-muted small">
    Lo que ocupa <code>writable/piezas</code> de verdad en disco — ficheros, no filas de la base
    de datos. Incluye la papelera de ficheros (invariante 6): lo que ya se apartó pero todavía no
    se ha purgado.
</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <div class="text-muted small">Almacén completo</div>
                <div class="fs-4 fw-semibold"><?= $tamanoLegible($total) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <div class="text-muted small">En la papelera</div>
                <div class="fs-4 fw-semibold <?= $totalPapelera > 0 ? 'text-warning' : '' ?>">
                    <?= $tamanoLegible($totalPapelera) ?>
                </div>
                <?php if ($totalPapelera > 0): ?>
                    <div class="small text-muted mt-1">
                        Se liberaría ejecutando <code>php spark piezas:purgar</code> en el servidor
                        (no hay botón en la web a propósito: es una acción de mantenimiento, no del
                        día a día).
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<h6 class="mb-2">Piezas que más pesan</h6>
<p class="text-muted small">
    Suma de sus versiones (.blend + .stl) y sus sesiones, apartadas o no. Si una pieza destaca
    mucho sobre las demás, esa es la candidata a aligerar — desde su ficha, con "liberar sitio"
    en la sesión que sobre, o revisando si el .blend necesita decimarse.
</p>

<?php if (empty($piezas)): ?>
    <p class="text-muted">Todavía no hay ningún fichero que pese algo.</p>
<?php else: ?>
    <div class="list-group list-group-flush">
        <?php foreach ($piezas as $fila): ?>
            <?php $porcentaje = $maximo > 0 ? (int) round($fila['bytes'] / $maximo * 100) : 0; ?>
            <a href="<?= site_url('piezas') ?>" class="list-group-item px-0 py-2 text-decoration-none text-body">
                <div class="d-flex justify-content-between align-items-baseline gap-2">
                    <strong class="text-truncate"><?= esc($fila['familia']['nombre']) ?></strong>
                    <span class="text-muted small flex-shrink-0"><?= $tamanoLegible($fila['bytes']) ?></span>
                </div>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar" style="width: <?= $porcentaje ?>%;"></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
