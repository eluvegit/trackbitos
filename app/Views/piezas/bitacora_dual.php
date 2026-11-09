<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $idPlaca = (int) $placa['id']; ?>

<style>
    body > .container { max-width: none; }
    body > footer { display: none; }
    .bd-cab { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .75rem; }
    .bd-cols { display: grid; grid-template-columns: repeat(<?= count($placas) ?>, minmax(0, 1fr)); gap: 12px; }
    .bd-col { display: flex; flex-direction: column; min-width: 0; border-top: 4px solid var(--c, var(--bs-border-color)); }
    .bd-col.imp-OLD { --c: #d97706; } .bd-col.imp-NEW { --c: #2563eb; }
    .bd-col-tit { display: flex; align-items: center; gap: .5rem; padding: 4px 2px; font-size: .85rem; }
    .bd-col iframe { width: 100%; height: calc(100vh - 140px); border: 0; border-radius: 6px; background: var(--bs-body-bg); }
    @media (max-width: 900px) {
        .bd-cols { grid-template-columns: minmax(0, 1fr); }
        .bd-col iframe { height: 85vh; }
    }
</style>

<div class="bd-cab small">
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted">Placas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="text-decoration-none text-muted">#<?= $idPlaca ?></a>
    <span class="text-muted">/</span>
    <strong>Vista dual</strong>
    <form method="get" class="d-flex align-items-center gap-2 ms-3">
        <i class="bi bi-layout-split"></i>
        <label for="bdCon" class="mb-0 text-nowrap">En paralelo con</label>
        <select name="con" id="bdCon" class="form-select form-select-sm" style="max-width: 420px;" onchange="this.form.submit()">
            <option value="0">— solo esta placa —</option>
            <?php foreach ($candidatas as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $con ? 'selected' : '' ?>>
                    #<?= (int) $c['id'] ?> <?= esc($c['nombre']) ?><?= $c['impresora'] ? ' · ' . esc($c['impresora']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-x-lg"></i> Salir de la vista dual</a>
</div>

<div class="bd-cols">
    <?php foreach ($placas as $p): ?>
        <div class="bd-col <?= $p['impresora'] ? 'imp-' . esc($p['impresora'], 'attr') : '' ?>">
            <div class="bd-col-tit">
                <strong>#<?= (int) $p['id'] ?></strong>
                <span class="text-truncate text-muted"><?= esc($p['nombre']) ?></span>
                <?php if ($p['impresora']): ?><span class="badge" style="background: var(--c)"><?= esc($p['impresora']) ?></span><?php endif; ?>
                <a href="<?= site_url('piezas/placa/' . (int) $p['id'] . '/bitacora/editar') ?>" class="ms-auto text-muted" title="Abrir sola"><i class="bi bi-box-arrow-up-right"></i></a>
            </div>
            <iframe src="<?= site_url('piezas/placa/' . (int) $p['id'] . '/bitacora/editar') ?>" title="Bitácora #<?= (int) $p['id'] ?>"></iframe>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
