<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Lentillas
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Avisos</span>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-bell"></i>
        </div>
        <div>
            <h2 class="mb-0">Avisos</h2>
            <small class="text-muted">Notificaciones para reemplazos</small>
        </div>
    </div>
    <a href="<?= site_url('lentillas/avisos/crear') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Nuevo aviso
    </a>
</div>

<?php if (empty($avisos)): ?>
    <p class="text-muted text-center">No hay avisos configurados aún.</p>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($avisos as $a):
        $tipo = strtolower($a['item']);
        $meta = match (true) {
            str_contains($tipo, 'izquierda') => ['color' => 'primary', 'icon' => 'bi-eye', 'badge' => 'OI', 'badgeClass' => 'ojo-badge-izq'],
            str_contains($tipo, 'derecha')   => ['color' => 'info', 'icon' => 'bi-eye', 'badge' => 'OD', 'badgeClass' => 'ojo-badge-der'],
            str_contains($tipo, 'lentilla')  => ['color' => 'primary', 'icon' => 'bi-record-circle', 'badge' => null, 'badgeClass' => null],
            str_contains($tipo, 'estuche')   => ['color' => 'warning', 'icon' => 'bi-briefcase', 'badge' => null, 'badgeClass' => null],
            str_contains($tipo, 'líquido')   => ['color' => 'success', 'icon' => 'bi-droplet', 'badge' => null, 'badgeClass' => null],
            str_contains($tipo, 'presion')   => ['color' => 'danger', 'icon' => 'bi-activity', 'badge' => null, 'badgeClass' => null],
            default                          => ['color' => 'secondary', 'icon' => 'bi-bell', 'badge' => null, 'badgeClass' => null],
        };

        $vencido = $a['dias_pasados'] !== null && $a['dias_pasados'] > $a['dias_maximos'];
    ?>
        <div class="col-lg-8 offset-lg-2">
            <div class="card border-0 shadow-sm lentillas-card lentillas-entry">
                <div class="d-flex">
                    <div class="lentillas-card-accent-start bg-<?= $vencido ? 'danger' : $meta['color'] ?>"></div>
                    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="datos-icon bg-<?= $meta['color'] ?> bg-opacity-10 text-<?= $meta['color'] ?>">
                                <i class="bi <?= $meta['icon'] ?>"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h6 class="mb-0"><?= ucfirst($a['item']) ?></h6>
                                    <?php if ($meta['badge']): ?>
                                        <span class="badge rounded-pill ojo-badge <?= $meta['badgeClass'] ?>"><?= $meta['badge'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($a['dias_pasados'] !== null): ?>
                                        <span class="badge rounded-pill <?= $vencido ? 'text-bg-danger' : 'text-bg-success' ?>">
                                            <i class="bi <?= $vencido ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?> me-1"></i>
                                            <?= $vencido ? 'Toca cambiar' : 'Al día' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mt-1">
                                    <?php if ($a['dias_pasados'] !== null): ?>
                                        Último cambio: <?= date('d/m/Y', strtotime($a['fecha'])) ?>
                                        — hace <?= $a['dias_pasados'] ?> días (máximo <?= $a['dias_maximos'] ?> días)
                                    <?php else: ?>
                                        Aún no se ha registrado ninguna sustitución.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="<?= site_url('lentillas/avisos/editar/' . $a['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="<?= site_url('lentillas/avisos/eliminar/' . $a['id']) ?>" method="post" onsubmit="return confirm('¿Seguro que quieres eliminar este aviso?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .lentillas-entry .lentillas-card-accent-start {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
</style>

<?= $this->endSection() ?>
