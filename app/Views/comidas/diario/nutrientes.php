<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>

<?php
$fmt = function ($n, $d = 0) {
    $s = number_format((float)$n, $d, '.', '');
    if (strpos($s, '.') !== false) $s = rtrim(rtrim($s, '0'), '.');
    return $s;
};
$grupo = function(array $items, string $cat) {
    return array_values(array_filter($items, fn($x) => ($x['categoria'] ?? '') === $cat));
};
$macros = $grupo($items, 'macro');
$micros = $grupo($items, 'micro');
?>

<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">Nutrientes · <?= esc($fechaSel->format('d/m/Y')) ?></h4>
        <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d')) ?>" class="btn btn-outline-secondary">
            ← Volver al día
        </a>
    </div>

    <?php foreach ([['Macros',$macros], ['Micros',$micros]] as [$titulo, $lista]): ?>
    <div class="card mb-3">
        <div class="card-header"><?= esc($titulo) ?></div>
        <div class="card-body">
            <?php if (empty($lista)): ?>
                <div class="text-muted">Sin datos.</div>
            <?php else: ?>
                <?php foreach ($lista as $n): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div><strong><?= esc($n['nombre']) ?></strong></div>
                        <small class="text-muted">
                            <?= $n['min'] !== null ? $fmt($n['min'], 0) : '—' ?>
                            –
                            <?= $n['max'] !== null ? $fmt($n['max'], 0) : '—' ?>
                            <?= esc($n['unidad']) ?>
                        </small>
                    </div>
                    <div class="progress mb-1" style="height:8px;">
                        <div class="progress-bar <?= esc($n['cls']) ?>" style="width: <?= $fmt($n['pct'],0) ?>%"></div>
                    </div>
                    <small class="text-muted d-block mb-2">
                        Ingeridas: <strong><?= $fmt($n['valor'], ($n['unidad']==='g'?1:0)) ?> <?= esc($n['unidad']) ?></strong>
                    </small>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<?= $this->endSection(); ?>
