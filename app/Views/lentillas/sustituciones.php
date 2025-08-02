<?php

use CodeIgniter\I18n\Time; ?>
<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-4">
    <i class="bi bi-arrow-repeat fs-4 me-2 text-primary"></i>
    <h2 class="mb-0">Lentillas <span class="text-muted">/</span> <span class="text-secondary">Cambios y revisiones</span></h2>
</div>
<a href="<?= site_url('lentillas') ?>" class="btn btn-outline-secondary mb-3">&larr; Volver a Lentillas</a>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success"><?= session('message') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session('error') ?></div>
<?php endif; ?>


<!-- Botones rápidos -->
<div class="mb-4">
    <form method="post" action="<?= site_url('lentillas/sustituciones') ?>" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="elemento" value="lentillas">
        <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
        <button type="submit" class="btn btn-outline-primary me-2 mb-2">Hoy cambié las Lentillas 👁️👁️</button>
    </form>

    <form method="post" action="<?= site_url('lentillas/sustituciones') ?>" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="elemento" value="líquido">
        <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
        <button type="submit" class="btn btn-outline-info me-2 mb-2">Hoy cambié el Líquido 💧</button>
    </form>

    <form method="post" action="<?= site_url('lentillas/sustituciones') ?>" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="elemento" value="estuche">
        <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
        <button type="submit" class="btn btn-outline-warning mb-2">Hoy cambié el Estuche 🧳</button>
    </form>
</div>

<!-- Formulario personalizado -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="<?= site_url('lentillas/sustituciones') ?>">
            <?= csrf_field() ?>
            <div class="row">
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
                <button type="submit" class="btn btn-primary">Registrar Sustitución</button>
            </div>
        </form>
    </div>
</div>

<!-- Histórico -->
<h4 class="my-5 text-center">Historial de Sustituciones</h4>

<?php if (!empty($sustituciones)): ?>
    <div class="row g-4">
        <?php foreach ($sustituciones as $sust):
            $tipo = strtolower($sust['elemento']);

            // Color e icono según tipo
            $bg = match ($tipo) {
                'lentilla izquierda' => 'primary',
                'lentilla derecha'   => 'primary',
                'estuche'            => 'warning',
                'líquido'            => 'info',
                default              => 'secondary'
            };

            $icon = match ($tipo) {
                'lentilla izquierda' => '👁️‍🗨️',
                'lentilla derecha'   => '👁️',
                'estuche'            => '🧳',
                'líquido'            => '💧',
                default              => '🔁'
            };
        ?>
            <div class="col-md-6 offset-md-3">
                <div class="card border-start border-1 border-<?= $bg ?> shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="display-6 me-2"><?= $icon ?></div>
                                <h5 class="card-title mb-0"><?= ucfirst($sust['elemento']) ?></h5>
                            </div>
                            <p class="text-muted mb-1">
                                <?= date('d/m/Y', strtotime($sust['fecha'])) ?> ·
                                <small class="fst-italic"><?= \CodeIgniter\I18n\Time::parse($sust['fecha'])->humanize() ?></small>
                            </p>

                            <?php if (!empty($sust['notas'])): ?>
                                <p class="mb-2"><?= esc($sust['notas']) ?></p>
                            <?php endif ?>
                        </div>

                        <div class="text-end">
                            <a href="<?= site_url('lentillas/sustituciones/editar/' . $sust['id']) ?>" class="btn btn-sm btn-outline-secondary me-2">Editar</a>

                            <form action="<?= site_url('lentillas/sustituciones/eliminar/' . $sust['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta sustitución?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-muted">No hay sustituciones registradas aún.</p>
<?php endif; ?>


<?= $this->endSection() ?>