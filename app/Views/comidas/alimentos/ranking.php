<?php $this->extend('comidas/layout');
$this->section('content'); ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h1 class="h5 mb-0"><?= esc($title) ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('comidas/alimentos') ?>">
    <i class="bi bi-arrow-left"></i> Volver
  </a>
</div>

<div class="mb-3">
  <?= view('comidas/alimentos/_botonera') ?>
</div>

<?php
$fmtValor = function ($v) {
    if (!is_numeric($v)) return $v;
    $s = number_format((float) $v, 2, '.', '');
    return strpos($s, '.') !== false ? rtrim(rtrim($s, '0'), '.') : $s;
};
$esConsumo = $campo === 'veces';

// Orden de macros: el campo por el que se ordena el ranking va primero
$macros = [
    'carbohidratos_g' => ['label' => 'C',  'color' => 'text-primary'],
    'proteina_g'      => ['label' => 'P',  'color' => 'text-danger'],
    'grasas_g'        => ['label' => 'G',  'color' => 'text-success'],
    'kcal'            => ['label' => 'KC', 'color' => 'text-warning'],
];
if (isset($macros[$campo])) {
    $macros = [$campo => $macros[$campo]] + $macros;
}
?>

<div class="list-group ranking-list">
  <?php if (empty($rows)): ?>
    <div class="list-group-item text-muted text-center py-4">Sin resultados</div>
  <?php else: ?>
    <?php foreach ($rows as $i => $r): ?>
      <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center gap-2">
          <div class="d-flex align-items-center gap-2 min-w-0">
            <span class="badge rounded-pill text-bg-secondary flex-shrink-0"><?= $i + 1 ?></span>
            <span class="fw-semibold text-truncate"><?= esc($r['nombre']) ?></span>
            <?php if (!empty($r['es_receta'])): ?>
              <span class="badge text-bg-info flex-shrink-0">receta</span>
            <?php endif; ?>
          </div>
          <a class="btn btn-sm btn-outline-secondary flex-shrink-0"
            href="<?= site_url('comidas/alimentos/edit/' . $r['id']) ?>"
            title="Editar" aria-label="Editar">
            <i class="bi bi-pencil"></i>
            <span class="visually-hidden">Editar</span>
          </a>
        </div>
        <div class="ranking-valores mt-1">
          <?php if ($esConsumo): ?>
            <span class="text-muted">Total</span> <strong><?= esc($fmtValor($r['veces'] ?? 0)) ?></strong>
            <span class="text-muted ms-2">Directo</span> <strong><?= esc($fmtValor($r['directo'] ?? 0)) ?></strong>
            <span class="text-muted ms-2">Recetas</span> <strong><?= esc($fmtValor($r['via_recetas'] ?? 0)) ?></strong>
          <?php else: ?>
            <?php foreach ($macros as $campoMacro => $m): ?>
              <span class="<?= esc($m['color']) ?><?= $campoMacro !== array_key_first($macros) ? ' ms-2' : '' ?>"><?= esc($m['label']) ?></span>
              <?= esc($fmtValor($r[$campoMacro] ?? 0)) ?><?= $campoMacro !== 'kcal' ? 'g' : '' ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<style>
  .ranking-list .list-group-item {
    padding: 0.4rem 0.6rem;
  }
  .ranking-valores {
    font-size: 0.75rem;
  }
</style>

<?php $this->endSection(); ?>
