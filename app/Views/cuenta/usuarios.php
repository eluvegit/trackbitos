<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">
    <i class="bi bi-people text-primary"></i>
    Usuarios
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

<div class="table-responsive mb-4">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Email</th>
                <th>Grupo</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $f): ?>
                <?php $u = $f['user']; $id = (int) $u->id; $soyYo = $id === $yo; ?>
                <tr>
                    <td>
                        <strong><?= esc($u->username) ?></strong>
                        <?php if ($soyYo): ?><span class="badge text-bg-light border ms-1">tú</span><?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= esc($u->email) ?></td>
                    <td>
                        <?php if ($soyYo): ?>
                            <span class="badge text-bg-primary"><?= esc($f['grupo'] ?? '—') ?></span>
                        <?php else: ?>
                            <form method="post" action="<?= site_url('cuenta/usuarios/' . $id . '/grupo') ?>" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <select name="grupo" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <?php if ($f['grupo'] === null): ?><option value="">Sin grupo</option><?php endif; ?>
                                    <?php foreach ($grupos as $g): ?>
                                        <option value="<?= esc($g, 'attr') ?>" <?= $f['grupo'] === $g ? 'selected' : '' ?>><?= esc($g) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u->active): ?>
                            <span class="badge text-bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Desactivado</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#pass-<?= $id ?>" title="Cambiar contraseña">
                                <i class="bi bi-key"></i>
                            </button>
                            <?php if (! $soyYo): ?>
                                <form method="post" action="<?= site_url('cuenta/usuarios/' . $id . '/activo') ?>"
                                    <?php if ($u->active): ?>onsubmit="return confirm('¿Desactivar a «<?= esc($u->username, 'js') ?>»? No podrá entrar hasta que lo actives.');"<?php endif; ?>>
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $u->active ? 'Desactivar' : 'Activar' ?>">
                                        <i class="bi <?= $u->active ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= site_url('cuenta/usuarios/' . $id . '/borrar') ?>"
                                    onsubmit="return confirm('¿Borrar a «<?= esc($u->username, 'js') ?>» para siempre?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr class="collapse" id="pass-<?= $id ?>">
                    <td colspan="5" class="bg-body-tertiary">
                        <form method="post" action="<?= site_url('cuenta/usuarios/' . $id . '/password') ?>" class="d-flex flex-wrap gap-2 align-items-center">
                            <?= csrf_field() ?>
                            <span class="small text-muted">Nueva contraseña para <?= esc($u->username) ?>:</span>
                            <input type="password" name="password" class="form-control form-control-sm" style="max-width: 14rem;" placeholder="Contraseña" required autocomplete="new-password">
                            <input type="password" name="pass_confirm" class="form-control form-control-sm" style="max-width: 14rem;" placeholder="Repetir" required autocomplete="new-password">
                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="max-width: 480px;">
    <div class="card-header">Nuevo usuario</div>
    <div class="card-body">
        <form method="post" action="<?= site_url('cuenta/usuarios/crear') ?>" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" class="form-control" name="username" id="username" value="<?= esc(old('username') ?? '', 'attr') ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email" value="<?= esc(old('email') ?? '', 'attr') ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" id="password" required autocomplete="new-password">
            </div>

            <div class="mb-3">
                <label for="pass_confirm" class="form-label">Repetir contraseña</label>
                <input type="password" class="form-control" name="pass_confirm" id="pass_confirm" required autocomplete="new-password">
            </div>

            <div class="mb-3">
                <label for="grupo" class="form-label">Grupo</label>
                <select name="grupo" id="grupo" class="form-select">
                    <option value="inventario" <?= old('grupo') !== 'admin' ? 'selected' : '' ?>>inventario — solo ve el inventario de piezas</option>
                    <option value="admin" <?= old('grupo') === 'admin' ? 'selected' : '' ?>>admin — acceso completo</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Crear usuario</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
