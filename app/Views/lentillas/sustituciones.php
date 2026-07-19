<?php

use CodeIgniter\I18n\Time; ?>
<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('lentillas/_estilos') ?>

<div class="d-flex align-items-center gap-2 mb-3 small lentillas-crumb">
    <a href="<?= site_url('lentillas') ?>" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Lentillas
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">Cambios y revisiones</span>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="lentillas-header-icon bg-primary bg-opacity-10 text-primary">
        <i class="bi bi-arrow-repeat"></i>
    </div>
    <div>
        <h2 class="mb-0">Cambios y revisiones</h2>
        <small class="text-muted">Lentillas, estuche, líquidos y presión del ojo</small>
    </div>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <div><?= session('message') ?></div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div><?= session('error') ?></div>
    </div>
<?php endif; ?>

<!-- Tarjetas de acción rápida -->
<div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
    <?php
    $acciones = [
        [
            'elemento' => 'lentillas',
            'texto'    => 'Hoy cambié las Lentillas',
            'color'    => 'primary',
            'icono'    => 'bi-eye'
        ],
        [
            'elemento' => 'líquido',
            'texto'    => 'Hoy cambié el Líquido',
            'color'    => 'info',
            'icono'    => 'bi-droplet'
        ],
        [
            'elemento' => 'estuche',
            'texto'    => 'Hoy cambié el Estuche',
            'color'    => 'warning',
            'icono'    => 'bi-briefcase'
        ]
    ];
    ?>

    <?php foreach ($acciones as $accion): ?>
        <div class="col d-flex">
            <form method="post" action="<?= site_url('lentillas/sustituciones') ?>" class="w-100">
                <?= csrf_field() ?>
                <input type="hidden" name="elemento" value="<?= esc($accion['elemento']) ?>">
                <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">

                <button type="submit" class="card text-start border-0 shadow-sm w-100 h-100 lentillas-card lentillas-quick-btn p-0">
                    <div class="lentillas-card-accent bg-<?= $accion['color'] ?>"></div>
                    <div class="d-flex align-items-center gap-3 p-3">
                        <div class="datos-icon bg-<?= $accion['color'] ?> bg-opacity-10 text-<?= $accion['color'] ?>">
                            <i class="bi <?= $accion['icono'] ?>"></i>
                        </div>
                        <strong><?= esc($accion['texto']) ?></strong>
                    </div>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<!-- Formulario personalizado -->
<div class="card border-0 shadow-sm lentillas-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small text-muted mb-3">Registrar otro cambio</h6>
        <form method="post" action="<?= site_url('lentillas/sustituciones') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="elemento" class="form-label">Elemento</label>
                    <select name="elemento" id="elemento" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <option value="lentillas">Lentillas (ambas)</option>
                        <option value="lentilla izquierda">Lentilla izquierda</option>
                        <option value="lentilla derecha">Lentilla derecha</option>
                        <option value="estuche">Estuche</option>
                        <option value="líquido">Líquido</option>
                        <option value="presión">Presión de ojos</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="<?= date('Y-m-d') ?>" class="form-control" required>
                </div>

                <div class="col-md-5">
                    <label for="notas" class="form-label">Notas</label>
                    <input type="text" name="notas" id="notas" class="form-control">
                </div>
            </div>

            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Registrar sustitución
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Histórico -->
<div class="d-flex align-items-center gap-2 my-5">
    <h4 class="mb-0">Historial de sustituciones</h4>
    <?php if (!empty($sustituciones)): ?>
        <span class="badge rounded-pill text-bg-secondary"><?= count($sustituciones) ?></span>
    <?php endif; ?>
</div>

<?php if (!empty($sustituciones)): ?>
    <div class="row g-3">
        <?php foreach ($sustituciones as $sust):
            $tipo = strtolower($sust['elemento']);

            // Color, icono y lateralidad según tipo
            $meta = match ($tipo) {
                'lentilla izquierda' => ['color' => 'primary', 'icon' => 'bi-eye', 'badge' => 'OI', 'badgeClass' => 'ojo-badge-izq'],
                'lentilla derecha'   => ['color' => 'info', 'icon' => 'bi-eye', 'badge' => 'OD', 'badgeClass' => 'ojo-badge-der'],
                'lentillas'          => ['color' => 'primary', 'icon' => 'bi-record-circle', 'badge' => null, 'badgeClass' => null],
                'estuche'            => ['color' => 'warning', 'icon' => 'bi-briefcase', 'badge' => null, 'badgeClass' => null],
                'líquido'            => ['color' => 'success', 'icon' => 'bi-droplet', 'badge' => null, 'badgeClass' => null],
                default              => ['color' => 'secondary', 'icon' => 'bi-arrow-repeat', 'badge' => null, 'badgeClass' => null],
            };
        ?>
            <div class="col-lg-8 offset-lg-2">
                <div class="card border-0 shadow-sm lentillas-card lentillas-entry">
                    <div class="d-flex">
                        <div class="lentillas-card-accent-start bg-<?= $meta['color'] ?>"></div>
                        <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="datos-icon bg-<?= $meta['color'] ?> bg-opacity-10 text-<?= $meta['color'] ?>">
                                    <i class="bi <?= $meta['icon'] ?>"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0"><?= ucfirst($sust['elemento']) ?></h6>
                                        <?php if ($meta['badge']): ?>
                                            <span class="badge rounded-pill ojo-badge <?= $meta['badgeClass'] ?>"><?= $meta['badge'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= date('d/m/Y', strtotime($sust['fecha'])) ?> ·
                                        <span class="fst-italic"><?= \CodeIgniter\I18n\Time::parse($sust['fecha'])->humanize() ?></span>
                                    </div>
                                    <?php if (!empty($sust['notas'])): ?>
                                        <div class="small mt-1"><?= esc($sust['notas']) ?></div>
                                    <?php endif ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="<?= site_url('lentillas/sustituciones/editar/' . $sust['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?= site_url('lentillas/sustituciones/eliminar/' . $sust['id']) ?>" method="post" onsubmit="return confirm('¿Eliminar esta sustitución?');">
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
<?php else: ?>
    <p class="text-muted text-center">No hay sustituciones registradas aún.</p>
<?php endif; ?>

<style>
    .lentillas-quick-btn {
        cursor: pointer;
    }

    .lentillas-entry .lentillas-card-accent-start {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
</style>

<?= $this->endSection() ?>
