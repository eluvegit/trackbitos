<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h3>Importar playlist en: <?= esc($lista['nombre']) ?></h3>

<?php if (session('error')): ?>
  <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('msg')): ?>
  <div class="alert alert-success"><?= esc(session('msg')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('youtube/'.$lista['slug'].'/importar-playlist') ?>" class="card p-3">
  <?= csrf_field() ?>
  <label class="form-label">Playlist ID (lo que va tras <code>list=</code>)</label>
  <input type="text" class="form-control" name="playlist_id" placeholder="PLxxxxxxxxxxxxxxxx" required>
  <div class="form-text">Ej.: https://www.youtube.com/playlist?list=<strong>PLxxxxxxxx</strong></div>
  <button class="btn btn-primary mt-3">Importar</button>
</form>

<?= $this->endSection() ?>
