<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>

<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Revisión de enlaces sin título</h5>
    <a href="<?= site_url('enlaces') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
  </div>

  <?php if (session()->getFlashdata('mensaje')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('mensaje')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('msg')): ?>
    <div class="alert alert-info"><?= esc(session()->getFlashdata('msg')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="border rounded p-3">
    <p class="mb-2">
      Pendientes: <span class="badge bg-primary"><?= (int)$pendientes ?></span>
    </p>
    <?php if ($pendientes > 0): ?>
      <a href="<?= site_url('enlaces/revision/item') ?>" class="btn btn-primary">Empezar / Continuar</a>
    <?php else: ?>
      <div class="text-muted">No hay nada que revisar 🎉</div>
    <?php endif; ?>
  </div>
</div>

<?php $this->endSection(); ?>
