<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
  <h1>Nuevo bloque para <?= esc($plan['nombre']) ?></h1>
  <form method="post" action="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/bloque/nuevo') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-2">
        <label class="form-label">Orden</label>
        <input name="orden" type="number" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select name="bloque_tipo" class="form-select">
          <option value="push">Push</option>
          <option value="stabilization">Stabilization</option>
          <option value="deload">Deload</option>
          <option value="pico">Pico</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Top % min (0.65 = 65%)</label>
        <input name="top_pct_min" type="number" step="0.01" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Top % max (opcional)</label>
        <input name="top_pct_max" type="number" step="0.01" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Top reps min</label>
        <input name="top_reps_min" type="number" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Top reps max (opcional)</label>
        <input name="top_reps_max" type="number" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Back-off % relativo (−0.10)</label>
        <input name="bo_pct_rel_top" type="number" step="0.01" class="form-control" value="-0.10">
      </div>
      <div class="col-md-2">
        <label class="form-label">Back-off sets</label>
        <input name="bo_sets" type="number" class="form-control" value="3">
      </div>
      <div class="col-md-2">
        <label class="form-label">Back-off reps</label>
        <input name="bo_reps" type="number" class="form-control" value="5">
      </div>
      <div class="col-12">
        <label class="form-label">Notas</label>
        <input name="notas" type="text" class="form-control" placeholder="Opcional">
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-outline-secondary" href="<?= site_url('gimnasio/mesociclos/'.$plan['id']) ?>">Cancelar</a>
    </div>
  </form>
</div>
<?= $this->endSection() ?>
