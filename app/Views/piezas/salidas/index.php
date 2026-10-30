<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-receipt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Albaranes de salida</strong>
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-geo-alt"></i> Ubicaciones</a>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-lg"></i> Nuevo albarán</div>
            <form method="post" action="<?= site_url('piezas/salidas/crear') ?>" enctype="multipart/form-data" class="card-body">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small mb-1">Foto (opcional)</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
                    <div class="form-text">La que manda Esther. Luego, en el albarán, apuntas qué se lleva.</div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label small mb-1">Quién / adónde</label>
                        <select name="destino_id" class="form-select form-select-sm">
                            <?php foreach ($destinos as $did => $nombre): ?>
                                <option value="<?= (int) $did ?>"><?= esc($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label small mb-1">Fecha</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Nota (opcional)</label>
                    <input type="text" name="nota" maxlength="500" class="form-control form-control-sm" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-receipt"></i> Crear albarán</button>
            </form>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-signpost"></i> Destinos</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($destinos as $did => $nombre): ?>
                    <?php $n = (int) ($usos[$did] ?? 0); ?>
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <form method="post" action="<?= site_url('piezas/salidas/destinos/' . (int) $did . '/renombrar') ?>" class="input-group input-group-sm flex-grow-1">
                            <?= csrf_field() ?>
                            <input type="text" name="nombre" maxlength="60" value="<?= esc($nombre, 'attr') ?>" class="form-control" required>
                            <button type="submit" class="btn btn-outline-secondary" title="Guardar nombre"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <span class="small text-muted text-nowrap" title="Albaranes con este destino"><?= $n ?> alb.</span>
                        <form method="post" action="<?= site_url('piezas/salidas/destinos/' . (int) $did . '/borrar') ?>"
                            onsubmit="return confirm('¿Borrar el destino «<?= esc($nombre, 'js') ?>»?<?= $n > 0 ? ' Sus ' . $n . ' albarán(es) quedarán sin destino.' : '' ?>');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Borrar destino"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                <?php endforeach; ?>
                <li class="list-group-item">
                    <form method="post" action="<?= site_url('piezas/salidas/destinos/crear') ?>" class="input-group input-group-sm">
                        <?= csrf_field() ?>
                        <input type="text" name="nombre" maxlength="60" class="form-control" placeholder="Nuevo destino…" required>
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Añadir</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ($salidas === []): ?>
            <p class="text-muted small">Todavía no hay albaranes.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($salidas as $s): ?>
                    <?php $r = $resumen[$s['id']] ?? null; ?>
                    <a href="<?= site_url('piezas/salidas/' . (int) $s['id']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                        <?php if (!empty($s['ruta_imagen'])): ?>
                            <img src="<?= esc(imagen_pieza($s, 'salidas'), 'attr') ?>" alt="" width="48" height="48" class="rounded object-fit-cover" loading="lazy">
                        <?php else: ?>
                            <span class="d-inline-flex align-items-center justify-content-center rounded bg-body-secondary text-muted" style="width:48px;height:48px;"><i class="bi bi-receipt"></i></span>
                        <?php endif; ?>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold">
                                #<?= (int) $s['id'] ?> · <?= esc($destinos[$s['destino_id']] ?? 'Sin destino') ?>
                                <span class="text-muted fw-normal small"><?= esc(date('d/m/Y', strtotime($s['fecha']))) ?></span>
                            </div>
                            <div class="small text-muted text-truncate">
                                <?= $r ? (int) $r['lineas'] . ' línea(s) · ' . (int) $r['uds'] . ' ud.' : 'Sin líneas' ?>
                                <?= !empty($s['nota']) ? ' · ' . esc($s['nota']) : '' ?>
                            </div>
                        </div>
                        <?php if ($s['estado'] === 'borrador'): ?>
                            <span class="badge text-bg-warning">Borrador</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Confirmado</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
