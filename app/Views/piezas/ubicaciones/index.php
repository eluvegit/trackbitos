<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-geo-alt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Ubicaciones</strong>

    <button type="button" class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalEstuche">
        <i class="bi bi-plus-lg"></i> Nuevo estuche
    </button>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if (empty($filas)): ?>
    <p class="text-muted small">Todavía no hay estuches. Crea el primero con «Nuevo estuche» y luego añade huecos dentro.</p>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($filas as $f): ?>
            <?php $estuche = $f['estuche']; ?>
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-semibold"><i class="bi bi-archive"></i> <?= esc($estuche['codigo']) ?></span>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                    data-bs-toggle="modal" data-bs-target="#modalEditarEstuche<?= (int) $estuche['id'] ?>" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Borrar el estuche «<?= esc($estuche['codigo'], 'js') ?>» y todos sus huecos? El historial de stock que tuvieran queda sin asignar, no se pierde.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Borrar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php if ($estuche['zona']): ?>
                            <div class="text-muted small mb-2"><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></div>
                        <?php endif; ?>

                        <?php if (empty($f['huecos'])): ?>
                            <p class="text-muted small mb-2">Sin huecos todavía.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php foreach ($f['huecos'] as $h): ?>
                                    <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $h['hueco']['id']) ?>"
                                        class="badge rounded-pill text-bg-light border text-decoration-none">
                                        <?= esc($h['codigo']) ?>
                                        <?= $h['unidades'] > 0 ? ' · ' . (int) $h['unidades'] . ' uds.' : '' ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/huecos/crear') ?>"
                            class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="text" name="codigo" required maxlength="20" class="form-control form-control-sm"
                                placeholder="H1" style="max-width: 8rem;" autocomplete="off">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Añadir hueco
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalEditarEstuche<?= (int) $estuche['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/actualizar') ?>" class="modal-content">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h6 class="modal-title">Editar estuche</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small mb-1">Código</label>
                                <input type="text" name="codigo" required maxlength="20" class="form-control"
                                    value="<?= esc($estuche['codigo']) ?>" autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Zona (opcional)</label>
                                <input type="text" name="zona" maxlength="100" class="form-control"
                                    value="<?= esc((string) $estuche['zona']) ?>" autocomplete="off">
                            </div>
                            <div class="mb-1">
                                <label class="form-label small mb-1">Notas (opcional)</label>
                                <textarea name="notas" rows="2" class="form-control"><?= esc((string) $estuche['notas']) ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="modalEstuche" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/ubicaciones/crear') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Nuevo estuche</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small mb-1">Código</label>
                    <input type="text" name="codigo" required maxlength="20" class="form-control"
                        placeholder="E1" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Zona (opcional)</label>
                    <input type="text" name="zona" maxlength="100" class="form-control"
                        placeholder="Taller / Almacén" autocomplete="off">
                </div>
                <div class="mb-1">
                    <label class="form-label small mb-1">Notas (opcional)</label>
                    <textarea name="notas" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
