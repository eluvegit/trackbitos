<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-box text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Piezas</strong>

    <button type="button" class="btn btn-sm btn-outline-success ms-auto" data-bs-toggle="modal" data-bs-target="#modalFamilia">
        <i class="bi bi-plus-lg"></i> Familia
    </button>
    <?php if (!empty($familias)): ?>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalVariante">
            <i class="bi bi-plus-lg"></i> Variante
        </button>
    <?php endif; ?>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (empty($familias)): ?>
    <p class="text-muted">
        Todavía no hay ninguna familia. Una familia es la pieza conceptual (cuerpo, brazo, casco);
        dentro van las variantes, que son cada línea de diseño con su propia numeración de versiones.
    </p>
<?php else: ?>
    <?php foreach ($familias as $familia): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-collection text-secondary"></i>
                    <?= esc($familia['nombre']) ?>
                    <span class="text-muted small fw-normal"><?= count($familia['variantes']) ?> variante(s)</span>
                </h6>
                <?php if (!empty($familia['notas'])): ?>
                    <p class="text-muted small mb-2"><?= esc($familia['notas']) ?></p>
                <?php endif; ?>

                <?php if (empty($familia['variantes'])): ?>
                    <p class="text-muted small mb-0">Sin variantes todavía.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($familia['variantes'] as $v): ?>
                            <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>"
                                class="list-group-item list-group-item-action px-0 bg-transparent">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong><?= esc($v['nombre']) ?></strong>

                                    <?php if ($v['validada']): ?>
                                        <span class="badge text-bg-success">
                                            <i class="bi bi-check-circle-fill"></i> v<?= sprintf('%03d', (int) $v['validada']['numero']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">sin versión buena</span>
                                    <?php endif; ?>

                                    <span class="text-muted small"><?= (int) $v['versiones'] ?> versión(es)</span>

                                    <?php if ($v['bloqueo']): ?>
                                        <span class="badge text-bg-warning ms-auto">
                                            <i class="bi bi-lock-fill"></i> sesión abierta en <?= esc($v['bloqueo']['maquina']) ?>
                                        </span>
                                    <?php elseif (!empty($v['pendientes'])): ?>
                                        <span class="badge text-bg-warning ms-auto">
                                            <i class="bi bi-download"></i> <?= count($v['pendientes']) ?> descarga(s) sin cerrar
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Alta de familia -->
<div class="modal fade" id="modalFamilia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/familia') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Familia nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="cuerpo, brazo, casco..." maxlength="150" required>
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-success">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Alta de variante -->
<div class="modal fade" id="modalVariante" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/variante') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Variante nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Familia</label>
                <select name="familia_id" class="form-select form-select-sm mb-2" required>
                    <?php foreach ($familias as $familia): ?>
                        <option value="<?= (int) $familia['id'] ?>"><?= esc($familia['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="torso-recto, pose-futbolista..." maxlength="150" required>
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace con su rama de trabajo abierta y numeración propia desde v001.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
