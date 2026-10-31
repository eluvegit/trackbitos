<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-calendar-range text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Tiradas</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<p class="text-muted small" style="max-width: 720px;">
    Un encargo grande repartido en placas por días y máquinas: qué placa va antes, en qué impresora y con qué
    resina, cuándo se pasa del plazo si algo espera confirmación, y cuántas piezas buenas llevas de cada una frente a lo necesario.
</p>

<?php if ($filas === []): ?>
    <p class="text-muted">Todavía no hay tiradas.</p>
<?php else: ?>
    <div class="list-group mb-4" style="max-width: 720px;">
        <?php foreach ($filas as $f): ?>
            <?php $t = $f['tirada']; $pct = $f['placas'] ? round($f['impresas'] / $f['placas'] * 100) : 0; ?>
            <a href="<?= site_url('piezas/tiradas/' . (int) $t['id']) ?>" class="list-group-item list-group-item-action py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-semibold"><?= esc($t['nombre']) ?></div>
                        <div class="small text-muted">
                            <?= (int) $f['impresas'] ?> / <?= (int) $f['placas'] ?> placas impresas
                            · día <?= (int) $f['dia'] ?> de <?= (int) $t['plazo_dias'] ?>
                            <?php if ($t['fecha_inicio']): ?>· desde <?= esc(date('d/m/Y', strtotime($t['fecha_inicio']))) ?><?php endif; ?>
                        </div>
                    </div>
                    <?php if ($f['placas'] && $f['impresas'] === $f['placas']): ?>
                        <span class="badge text-bg-success">Terminada</span>
                    <?php elseif ($f['dia'] > (int) $t['plazo_dias']): ?>
                        <span class="badge text-bg-danger">Fuera de plazo</span>
                    <?php endif; ?>
                </div>
                <div class="progress mt-2" style="height: 4px;"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('piezas/tiradas/crear') ?>" class="d-flex flex-wrap gap-2 align-items-center" style="max-width: 720px;">
    <?= csrf_field() ?>
    <input type="text" name="nombre" class="form-control form-control-sm" style="max-width: 16rem;" placeholder="Nombre de la tirada nueva" required>
    <input type="date" name="fecha_inicio" class="form-control form-control-sm" style="max-width: 10rem;" value="<?= date('Y-m-d') ?>" title="Día 1 de la tirada">
    <?php if ($filas !== []): ?>
        <select name="copiar_de" class="form-select form-select-sm" style="max-width: 14rem;" title="Trae sus piezas, placas, máquinas y notas, sin nada impreso">
            <option value="">Vacía</option>
            <?php foreach ($filas as $f): ?>
                <option value="<?= (int) $f['tirada']['id'] ?>">Copia de <?= esc($f['tirada']['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Crear tirada</button>
</form>

<?= $this->endSection() ?>
