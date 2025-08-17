<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?= esc($plan['nombre']) ?> · Simplificado</h1>
    <a class="btn btn-outline-secondary" href="<?= site_url('gimnasio/mesociclos/'.$plan['id']) ?>">Volver</a>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Bloque</th><th>Top set</th><th>Peso Top</th><th>Back-off</th><th>Peso Back-off</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['orden'] ?> <span class="badge bg-info text-dark ms-2"><?= esc($r['bloque_tipo']) ?></span></td>
          <td><?= esc($r['top']) ?></td>
          <td><strong><?= number_format($r['topKg'],1) ?> kg</strong></td>
          <td><?= esc($r['bo']) ?></td>
          <td><strong><?= number_format($r['boKg'],1) ?> kg</strong></td>
          <td>
            <a class="btn btn-sm btn-primary" href="<?= site_url('gimnasio/mesociclos/'.$plan['id'].'/asignar/'.$r['bloque_id']) ?>">Asignar</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
