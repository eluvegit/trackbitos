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

function pintarBloqueLista(array $lista, callable $fmt) {
    // variables para detectar omega3 -> omega6 consecutivos
    $prevClave = null;
    $prevOmega3Val = null;

    foreach ($lista as $n): ?>
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

        <?php
        // Si el anterior fue omega3 y este es omega6, mostramos ratio ω6:ω3
        if ($prevClave === 'omega3_mg' && ($n['clave'] ?? '') === 'omega6_mg') {
            $omega3 = (float)($prevOmega3Val ?? 0);
            $omega6 = (float)($n['valor'] ?? 0);
            $ratio  = ($omega3 > 0) ? ($omega6 / $omega3) : null;
            ?>
            <div class="d-flex justify-content-end mb-2">
                <span class="badge rounded-pill text-bg-light">
                    Ratio Omega-6 : Omega-3&nbsp;
                    <strong class="fs-6"><?= $ratio !== null ? $fmt($ratio, 2) . '</strong> : 1' : '—' ?>
                </span>
            </div>
            <?php
        }

        // actualizar “prev” para la siguiente vuelta
        if (($n['clave'] ?? '') === 'omega3_mg') {
            $prevClave = 'omega3_mg';
            $prevOmega3Val = (float)$n['valor'];
        } else {
            $prevClave = $n['clave'] ?? null;
            $prevOmega3Val = null;
        }
    endforeach;
}
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
                    <?php pintarBloqueLista($lista, $fmt); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<?= $this->endSection(); ?>
