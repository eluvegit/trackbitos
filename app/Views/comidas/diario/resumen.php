<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content') ?>

<?php
helper('comidas');

/** === NUEVO: límites globales, sin user_id === */
$limites = comidas_limites();

/** Helpers */
// Reemplaza tu $fmt por esto
$fmt = function ($n, $d = 0) {
    $s = number_format((float)$n, $d, '.', '');
    // Solo recorta ceros si hay punto decimal
    if (strpos($s, '.') !== false) {
        $s = rtrim(rtrim($s, '0'), '.');
    }
    return $s;
};

$val = fn($arr, $k) => (float)($arr[$k] ?? 0);

/** Lee min/max literalmente de comidas_limites */
$minMax = function (string $clave) use ($limites) {
    $min = isset($limites[$clave]['falta'])  ? (float)$limites[$clave]['falta']['umbral']  : null;
    $max = isset($limites[$clave]['exceso']) ? (float)$limites[$clave]['exceso']['umbral'] : null;
    // corrige si están invertidos
    if ($min !== null && $max !== null && $min > $max) [$min, $max] = [$max, $min];
    return ['min' => $min, 'max' => $max];
};

/** RANGOS */
$rK = $minMax('kcal');
$rP = $minMax('proteina_g');
$rC = $minMax('carbohidratos_g');
$rG = $minMax('grasas_g');

/** VALORES DEL DÍA */
$kcal = $val($resumen, 'kcal');
$prot = $val($resumen, 'proteina_g');
$carb = $val($resumen, 'carbohidratos_g');
$gras = $val($resumen, 'grasas_g');

/** % barra usando el máximo */
$bar = function (float $value, ?float $min, ?float $max) {
    $pct = $max && $max > 0 ? min(100, max(0, $value / $max * 100)) : 0;
    $state = 'ok';
    if ($min !== null && $value < $min) $state = 'low';
    if ($max !== null && $value > $max) $state = 'high';
    $class = $state === 'ok' ? 'bg-success' : ($state === 'high' ? 'bg-danger' : 'bg-warning');
    return [$pct, $class, $state];
};

[$pctK, $clsK] = $bar($kcal, $rK['min'], $rK['max']);
[$pctP, $clsP] = $bar($prot, $rP['min'], $rP['max']);
[$pctC, $clsC] = $bar($carb, $rC['min'], $rC['max']);
[$pctG, $clsG] = $bar($gras, $rG['min'], $rG['max']);

$rangotxt = fn($r, $u) => sprintf(
    '%s – %s %s',
    $r['min'] !== null ? $fmt($r['min'], 0) : '—',
    $r['max'] !== null ? $fmt($r['max'], 0) : '—',
    $u
);
?>

<div class="container my-3">

    <!-- Calendario -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= site_url('comidas/diario/' . (clone $fechaSel)->modify('-1 day')->format('Y-m-d')) ?>" class="btn btn-outline-secondary">&lt;</a>
            <h4 class="m-0"><?= $fechaSel->format('d/m/Y') ?></h4>
            <a href="<?= site_url('comidas/diario/' . (clone $fechaSel)->modify('+1 day')->format('Y-m-d')) ?>" class="btn btn-outline-secondary">&gt;</a>
        </div>
    </div>

    <!-- Botón Añadir alimento -->
    <div class="d-grid mb-3">
        <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/seleccionar-tipo') ?>"
            class="btn btn-lg btn-primary">
            ➕ Añadir alimento
        </a>
    </div>

    <!-- Resumen del día (con límites) -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Resumen del día</h5>
                <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/nutrientes') ?>"
                    class="btn btn-sm btn-outline-secondary">Ver más</a>
            </div>

            <!-- Kcal -->
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div><strong>Calorías</strong></div>
                <small class="text-muted"><?= $rangotxt($rK, 'kcal') ?></small>
            </div>
            <div class="progress mb-2" style="height: 12px;">
                <div class="progress-bar <?= $clsK ?>" role="progressbar" style="width: <?= $fmt($pctK, 0) ?>%">
                    <?= $fmt($pctK, 0) ?>%
                </div>
            </div>
            <small class="text-muted d-block mb-3">
                Ingeridas: <strong><?= $fmt($kcal, 0) ?> calorias</strong>
            </small>

            <?php
            $rows = [
                ['Proteínas', 'proteina_g', $prot, $rP, $pctP, $clsP, 'g'],
                ['Carbohidratos', 'carbohidratos_g', $carb, $rC, $pctC, $clsC, 'g'],
                ['Grasas', 'grasas_g', $gras, $rG, $pctG, $clsG, 'g'],
            ];
            ?>
            <?php foreach ($rows as [$label, $clave, $valNow, $range, $pct, $cls, $unit]): ?>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div><?= esc($label) ?></div>
                    <small class="text-muted">
                        <?= $range['min'] !== null ? $fmt($range['min'], 0) : '—' ?> – <?= $range['max'] !== null ? $fmt($range['max'], 0) : '—' ?> <?= $unit ?>
                    </small>
                </div>
                <div class="progress mb-1" style="height: 8px;">
                    <div class="progress-bar <?= $cls ?>" role="progressbar" style="width: <?= $fmt($pct, 0) ?>%"></div>
                </div>
                <small class="text-muted d-block mb-2">
                    Ingeridas: <strong><?= $fmt($valNow, 0) ?> <?= $unit ?></strong>
                </small>
            <?php endforeach; ?>

        </div>
    </div>

    <?php
    /** === NUEVO: sin user_id === */
    $alertasDia = comidas_alertas_eval_dia($resumen);

    $tot7d     = comidas_totales_7d($fechaSel->format('Y-m-d'));
    $alertas7d = comidas_alertas_eval_7d($tot7d);

    // helpers visuales
    $badgeNivel = function (string $nivel) {
        return $nivel === 'critical' ? 'danger' : ($nivel === 'warning' ? 'warning' : 'info');
    };
    $badgeTipo = function (string $tipo) {
        return $tipo === 'exceso' ? 'text-bg-danger' : 'text-bg-secondary';
    };
    $iconTipo = function (string $tipo) {
        return $tipo === 'exceso' ? 'bi-arrow-up-short' : 'bi-arrow-down-short';
    };
    ?>


    <!-- Ingestas del día agrupadas -->
<?php foreach ($tiposLista as $tipo): ?>
  <?php
    $totK = $fmt($resumenTipos[$tipo]['kcal']            ?? 0, 0);
    $totP = $fmt($resumenTipos[$tipo]['proteina_g']      ?? 0, 0);
    $totC = $fmt($resumenTipos[$tipo]['carbohidratos_g'] ?? 0, 0);
    $totG = $fmt($resumenTipos[$tipo]['grasas_g']        ?? 0, 0);
  ?>
  <div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span><?= ucfirst(str_replace('_', ' ', $tipo)) ?></span>
        <span class="bg-light text-dark"><?= $totK ?> kcal</span>
        <span class="">P <?= $totP ?> g</span>
        <span class="">C <?= $totC ?> g</span>
        <span class="">G <?= $totG ?> g</span>
      </div>

      <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $tipo) ?>"
         class="btn btn-sm btn-outline-primary"
         title="Añadir a <?= esc(ucfirst(str_replace('_', ' ', $tipo))) ?>">
        <i class="bi bi-plus"></i>
        <span class="visually-hidden">Añadir</span>
      </a>
    </div>

    <ul class="list-group list-group-flush">
      <?php if (!empty($ingPorTipo[$tipo])): ?>
        <?php foreach ($ingPorTipo[$tipo] as $i): ?>
          <?php
            $k = $fmt($i['macros']['kcal']            ?? 0, 0);
            $p = $fmt($i['macros']['proteina_g']      ?? 0, 1);
            $c = $fmt($i['macros']['carbohidratos_g'] ?? 0, 1);
            $g = $fmt($i['macros']['grasas_g']        ?? 0, 1);
          ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?= esc($i['nombre']) ?></strong>
              <br><small class="text-muted"><?= esc($i['cantidad_label']) ?></small>
            </div>

            <div class="text-end">
              <div class="fw-semibold"><?= $k ?> kcal</div>
              <small class="text-muted">P <?= $p ?> g · C <?= $c ?> g · G <?= $g ?> g</small>
            </div>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="list-group-item text-muted">Sin registros</li>
      <?php endif; ?>
    </ul>
  </div>
<?php endforeach; ?>


</div>

<?= $this->endSection() ?>