<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4"><i class="bi bi-bell fs-4 me-2 text-primary"></i>Coche <span class="text-muted">/</span> <span class="text-secondary">Recordatorios</h2>
<a href="<?= site_url('coche/') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver</a>
<a href="<?= site_url('coche/recordatorios/nuevo') ?>" class="btn btn-outline-success mb-3">Nuevo recordatorio</a>

<div class="row">
    <?php foreach ($recordatorios as $r): ?>
        <?php
        $info = $estado[$r['id']] ?? null;
        $vencido = $info['vencido'] ?? false;
        $dias = $info['dias_pasados'] ?? null;
        ?>
        <div class="col-md-6 col-lg-4 d-flex align-items-stretch mb-3">
            <div class="card w-100 h-100 border-<?= $vencido ? 'danger' : 'secondary' ?>">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title mb-1"><?= esc($r['title']) ?></h5>
                        <p class="mb-1">Cada <?= esc($r['interval_days']) ?> días / <?= esc($r['interval_km']) ?> km</p>
                        <p class="text-muted small"><?= esc($r['notes']) ?></p>

                        <?php if ($dias !== null): ?>
                            <p class="small <?= $vencido ? 'text-danger' : 'text-success' ?>">
                                Última acción hace <?= $dias ?> días
                                <?= $vencido ? '⚠️' : '✔️' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <a href="<?= site_url('coche/recordatorios/editar/' . $r['id']) ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <a href="<?= site_url('coche/recordatorios/borrar/' . $r['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Borrar este recordatorio?')">Borrar</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>


<?= $this->endSection() ?>