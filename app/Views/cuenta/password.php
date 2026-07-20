<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">
    <i class="bi bi-person-lock text-primary"></i>
    Mi cuenta
</h5>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif; ?>

<?php if (session('error')): ?>
    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (session('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 480px;">
    <div class="card-header">Cambiar contraseña</div>
    <div class="card-body">
        <p class="text-muted">Usuario: <strong><?= esc($user->username ?? $user->email) ?></strong></p>

        <form method="post" action="<?= site_url('cuenta/password') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="current_password" class="form-label">Contraseña actual</label>
                <input type="password" class="form-control" name="current_password" id="current_password" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>

            <div class="mb-3">
                <label for="pass_confirm" class="form-label">Confirmar nueva contraseña</label>
                <input type="password" class="form-control" name="pass_confirm" id="pass_confirm" required>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
