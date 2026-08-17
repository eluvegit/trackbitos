<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-box text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Piezas</strong>

    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-grid-3x3-gap"></i> Galería
        <?php if (!empty($carritoCount)): ?>
            <span class="badge text-bg-primary"><?= (int) $carritoCount ?></span>
        <?php endif; ?>
    </a>
    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalFamilia">
        <i class="bi bi-plus-lg"></i> Pieza
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

<?php if (!empty($familias)): ?>
    <input type="search" id="buscadorPiezas" class="form-control form-control-sm mb-3"
        placeholder="Buscar por nombre o SKU..." autocomplete="off">
<?php endif; ?>

<?php if (empty($familias)): ?>
    <p class="text-muted">
        Todavía no hay ninguna pieza. Crea una (pincel, casco, silla...) y nacerá lista para
        trabajar: no hace falta decidir nada más. Si algún día esa pieza tiene varias líneas de
        diseño (una silla alta y otra baja), se añaden como <strong>variantes</strong>, cada una
        con su propia numeración de versiones.
    </p>
<?php else: ?>
    <?php foreach ($familias as $familia): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-collection text-secondary"></i>
                    <?= esc($familia['nombre']) ?>
                    <?php if (count($familia['variantes']) > 1): ?>
                        <?php // Con una sola variante el contador es ruido: la mayoría de piezas son una sola cosa. ?>
                        <span class="text-muted small fw-normal"><?= count($familia['variantes']) ?> variantes</span>
                    <?php endif; ?>
                </h6>
                <?php if (!empty($familia['notas'])): ?>
                    <p class="text-muted small mb-2"><?= esc($familia['notas']) ?></p>
                <?php endif; ?>

                <?php if (empty($familia['variantes'])): ?>
                    <p class="text-muted small mb-0">Sin variantes todavía: añádele una con el botón «Variante».</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($familia['variantes'] as $v): ?>
                            <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>"
                                class="list-group-item list-group-item-action px-0 bg-transparent"
                                data-buscar="<?= esc(strtolower($v['nombre'] . ' ' . ($v['sku'] ?? '')), 'attr') ?>">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <strong><?= esc($v['nombre']) ?></strong>
                                    <?php if (!empty($v['sku'])): ?>
                                        <span class="badge text-bg-light text-muted border font-monospace"><?= esc($v['sku']) ?></span>
                                    <?php endif; ?>

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

                <!-- Referencias del original: comunes a toda la pieza, no por variante (spec 1.1) -->
                <hr class="my-2">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="small text-muted"><i class="bi bi-camera"></i> Referencias</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-auto"
                        data-bs-toggle="modal" data-bs-target="#modalReferencia<?= (int) $familia['id'] ?>">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <?php if (empty($familia['referencias'])): ?>
                    <p class="text-muted small mb-0">
                        Sin fotos de referencia todavía (medidas de calibre, ángulos del original).
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($familia['referencias'] as $r): ?>
                            <div class="position-relative" style="width: 72px;">
                                <a href="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/imagen') ?>" target="_blank"
                                    title="<?= esc($r['notas'] ?? '') ?>">
                                    <img src="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/imagen') ?>"
                                        class="rounded border" style="width: 72px; height: 72px; object-fit: cover;"
                                        alt="Referencia" loading="lazy">
                                </a>
                                <form method="post" action="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Apartar esta referencia a la papelera?');" class="position-absolute top-0 end-0">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .65rem;" title="Borrar">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alta de referencia -->
        <div class="modal fade" id="modalReferencia<?= (int) $familia['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" enctype="multipart/form-data"
                    action="<?= site_url('piezas/familia/' . (int) $familia['id'] . '/referencia') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h6 class="modal-title">Referencia para <?= esc($familia['nombre']) ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Foto</label>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-2" required>
                        <label class="form-label small">Notas (medidas de calibre, qué muestra)</label>
                        <textarea name="notas" class="form-control form-control-sm" rows="2"
                            placeholder="Alto total 78mm con calibre, vista frontal"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-sm btn-primary">Subir</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Alta de pieza (en el esquema, "familia": ver la nota de vocabulario en SPEC.md) -->
<div class="modal fade" id="modalFamilia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/familia') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pieza nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="pincel, casco, silla..." maxlength="150" required>
                <label class="form-label small">SKU (opcional)</label>
                <input type="text" name="sku" class="form-control form-control-sm mb-2" placeholder="el código de tu tienda, si ya lo tienes" maxlength="50">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace lista para trabajar, con numeración propia desde v001. Solo hace falta
                    añadir variantes si esta pieza acaba teniendo varias líneas de diseño.
                </p>
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
                <label class="form-label small">Pieza</label>
                <select name="familia_id" class="form-select form-select-sm mb-2" required>
                    <?php foreach ($familias as $familia): ?>
                        <option value="<?= (int) $familia['id'] ?>"><?= esc($familia['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="torso-recto, pose-futbolista..." maxlength="150" required>
                <label class="form-label small">SKU (opcional)</label>
                <input type="text" name="sku" class="form-control form-control-sm mb-2" placeholder="el código de tu tienda, si ya lo tienes" maxlength="50">
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

<script>
(function () {
    var buscador = document.getElementById('buscadorPiezas');
    if (!buscador) return;
    buscador.addEventListener('input', function () {
        var q = buscador.value.trim().toLowerCase();
        document.querySelectorAll('[data-buscar]').forEach(function (fila) {
            fila.style.display = fila.getAttribute('data-buscar').indexOf(q) === -1 ? 'none' : '';
        });
    });
})();
</script>

<?= $this->endSection() ?>
