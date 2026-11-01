<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-droplet-half text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Resinas</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Define cada resina (o mezcla) una vez y elígela en la bitácora de cada placa. Una placa nueva
    hereda la de la anterior; en el selector se marca cuál sigue en la cubeta de cada impresora.
</p>

<form method="post" action="<?= site_url('piezas/resinas/crear') ?>" class="d-flex gap-2 mb-3" style="max-width: 40rem;">
    <?= csrf_field() ?>
    <input type="text" name="nombre" class="form-control form-control-sm" maxlength="120" required
        placeholder="Elegoo ABS-like standard Grey + 66 g RESIONE F80">
    <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> Añadir</button>
</form>

<?php if (empty($resinas)): ?>
    <p class="text-muted">Todavía no hay resinas.</p>
<?php endif; ?>

<?php foreach ($resinas as $r): ?>
    <?php $id = (int) $r['id']; $uso = $usos[$id] ?? null; ?>
    <div class="card shadow-sm mb-2<?= $r['archivada'] ? ' opacity-50' : '' ?>">
        <div class="card-body p-3">
            <form method="post" action="<?= site_url('piezas/resinas/' . $id . '/actualizar') ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-6">
                    <input type="text" name="nombre" class="form-control form-control-sm fw-semibold" maxlength="120" required
                        value="<?= esc($r['nombre'], 'attr') ?>">
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <input type="text" name="notas" class="form-control form-control-sm" placeholder="Notas (proporciones, lote, exposición…)"
                        value="<?= esc((string) $r['notas'], 'attr') ?>">
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-lg"></i></button>
                </div>
            </form>
            <div class="small text-muted d-flex gap-3 flex-wrap align-items-center mt-2">
                <?php foreach ($enMaquina[$id] ?? [] as $imp): ?>
                    <span class="badge text-bg-info"><i class="bi bi-printer"></i> en <?= esc($imp) ?></span>
                <?php endforeach; ?>
                <span><i class="bi bi-grid-3x3"></i> <?= $uso ? (int) $uso['n'] . ' placa(s)' : 'sin placas' ?></span>
                <?php if ($uso): ?>
                    <span><i class="bi bi-clock"></i> última <?= esc(date('d/m/Y', strtotime($uso['ultima']))) ?></span>
                <?php endif; ?>
                <span class="ms-auto d-flex gap-2">
                    <form method="post" action="<?= site_url('piezas/resinas/' . $id . '/archivar') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-link p-0 text-decoration-none"><?= $r['archivada'] ? 'Desarchivar' : 'Archivar' ?></button>
                    </form>
                    <?php if (!$uso): ?>
                        <form method="post" action="<?= site_url('piezas/resinas/' . $id . '/borrar') ?>" onsubmit="return confirm('¿Borrar esta resina?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-link p-0 text-danger text-decoration-none">Borrar</button>
                        </form>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
