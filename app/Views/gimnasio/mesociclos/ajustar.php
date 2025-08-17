<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
  <h1>Ajustar e1RM antes de generar</h1>
  <p>e1RM actual del plan: <strong><?= number_format($plan['e1rm_base'],1) ?> kg</strong></p>

  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/ajustar') ?>" class="mb-4">
    <?= csrf_field() ?>

    <div class="card mb-3">
      <div class="card-header">1) Ajuste rápido por estado</div>
      <div class="card-body">
        <input type="hidden" name="modo" value="estado">
        <div class="row g-3 align-items-center">
          <div class="col-auto">
            <select name="estado" class="form-select">
              <option value="fatigado">Fatigado (sug: <?= number_format($sugFatigado,1) ?> kg)</option>
              <option value="justo" selected>Justo (sug: <?= number_format($sugJusto,1) ?> kg)</option>
              <option value="sobrado">Sobrado (sug: <?= number_format($sugSobrado,1) ?> kg)</option>
            </select>
          </div>
          <div class="col-auto">
            <button class="btn btn-primary">Usar esta sugerencia</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/ajustar') ?>" class="mb-3">
    <?= csrf_field() ?>
    <input type="hidden" name="modo" value="medicion">
    <div class="card mb-3">
      <div class="card-header">2) Ajuste con una medición reciente</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Peso (kg)</label>
            <input type="number" step="0.5" name="peso" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">Reps</label>
            <input type="number" name="reps" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">RPE (opcional)</label>
            <input type="number" step="0.5" name="rpe" class="form-control" placeholder="8.5, 9...">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100">Calcular y usar</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/ajustar') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="modo" value="manual">
    <div class="card">
      <div class="card-header">3) Introducir e1RM manual</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">e1RM nuevo (kg)</label>
            <input type="number" step="0.5" name="e1rm_manual" class="form-control" value="<?= number_format($plan['e1rm_base'],1) ?>">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-outline-primary w-100">Usar este valor</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <hr class="my-4">

  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/generar') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-success">Saltar ajuste y generar con el e1RM actual</button>
  </form>

</div>
<?= $this->endSection() ?>
