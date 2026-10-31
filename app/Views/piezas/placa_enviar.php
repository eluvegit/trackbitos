<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
$idPlaca = (int) $placa['id'];
$paralelo = count($envios) > 1;
$volver = (string) current_url(true);
?>

<style>
    :root { --imp-old: #d97706; --imp-new: #2563eb; }
    .imp-boton { border: 2px solid var(--c); color: var(--c); font-weight: 700; letter-spacing: 3px; font-size: 1.4rem; padding: .5rem 0; }
    .btn-check:checked + .imp-boton { background: var(--c); color: #fff; }
    .imp-OLD { --c: var(--imp-old); }
    .imp-NEW { --c: var(--imp-new); }
    .imp-fichero { border-left: 4px solid var(--c, #adb5bd); }
    .modal-impresora .imp-cabecera { background: var(--c); color: #fff; }
    .modal-impresora .imp-grande { font-size: 4rem; font-weight: 800; letter-spacing: .5rem; line-height: 1; }
    .modal-impresora .imp-dato { font-size: .8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--bs-secondary-color); }
    .env-col { border-top: 4px solid var(--c, var(--bs-border-color)); }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-envelope text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="text-decoration-none text-muted fw-normal">#<?= $idPlaca ?></a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Enviar placa</strong>
    <span class="ms-auto">
        <?= view('piezas/_placa_vecinas', ['vecinas' => $vecinas, 'sufijo' => '/enviar']) ?>
    </span>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if ($destino === ''): ?>
    <div class="alert alert-warning py-2 small">
        Falta <code>PIEZAS_PLACA_EMAIL</code> (y la configuración SMTP) en el <code>.env</code>: se puede preparar, pero no enviar.
    </div>
<?php endif; ?>

<form method="get" class="d-flex align-items-center gap-2 small mb-3">
    <i class="bi bi-layout-split"></i>
    <label for="envCon" class="mb-0 text-nowrap">En paralelo con</label>
    <select name="con" id="envCon" class="form-select form-select-sm" style="max-width: 420px;" onchange="this.form.submit()">
        <option value="0">— solo esta placa —</option>
        <?php foreach ($candidatas as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $con ? 'selected' : '' ?>>
                <?= esc($c['nombre']) ?><?= $c['poner_en'] ? ' · ' . esc(date('d/m H:i', strtotime($c['poner_en']))) : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($paralelo): ?>
    <div class="row g-4">
        <?php foreach ($envios as $e): ?>
            <div class="col-12 col-xl-6">
                <div class="env-col pt-2 <?= $e['placa']['impresora'] ? 'imp-' . esc($e['placa']['impresora']) : '' ?>">
                    <h6 class="mb-2 text-truncate">
                        <a href="<?= site_url('piezas/placa/' . (int) $e['placa']['id'] . '/bitacora/editar') ?>" class="text-decoration-none"><?= esc($e['placa']['nombre']) ?></a>
                    </h6>
                    <?= view('piezas/_placa_enviar_form', compact('e', 'paralelo', 'destino', 'calc', 'desfase', 'volver')) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?= view('piezas/_placa_enviar_form', ['e' => $envios[0]] + compact('paralelo', 'destino', 'calc', 'desfase', 'volver')) ?>
<?php endif; ?>

<?= $this->endSection() ?>
