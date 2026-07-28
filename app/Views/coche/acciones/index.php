<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-gear text-primary"></i>
    <a href="<?= site_url('coche') ?>" class="text-decoration-none text-muted fw-normal">Coche</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Acciones</strong>

    <a href="<?= site_url('coche/acciones/nueva') ?>"
        class="text-decoration-none ms-1 text-success"
        title="Nueva acción manual">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle"></i><div><?= esc(session('success')) ?></div>
    </div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle"></i><div><?= esc(session('error')) ?></div>
    </div>
<?php endif; ?>

<!-- Acciones rápidas -->
<h4 class="mb-3 fs-6 text-muted">Acciones rápidas para hoy</h4>
<div class="row mb-5">
    <?php
    $accionesRapidas = [
        ['title' => 'Cambio de aceite', 'icon' => 'gear-fill'],
        ['title' => 'Limpiar coche por fuera', 'icon' => 'stars'],
        ['title' => 'Limpiar coche por dentro', 'icon' => 'wind']
    ];
    ?>
    <?php foreach ($accionesRapidas as $accion): ?>
        <?php
            $url = site_url('coche/acciones/rapida/' . urlencode(str_replace(' ', '-', $accion['title'])));
            $confirmMsg = '¿Registrar "' . $accion['title'] . '" como acción para hoy?';
        ?>
        <div class="col-md-4 mb-3">
            <form method="post" action="<?= $url ?>" onsubmit="return confirm('<?= esc($confirmMsg) ?>')">
                <?= csrf_field() ?>
                <button type="submit" class="coche-rapida-btn">
                    <i class="bi bi-<?= esc($accion['icon']) ?> mb-2 fs-2 text-primary d-block"></i>
                    <span class="fw-semibold"><?= esc($accion['title']) ?></span>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<hr>
<!-- Historial de acciones -->
<h4 class="text-muted text-center fs-6">Historial</h4>
<p class="mb-3 text-muted text-center small">(solo se muestra el último de cada tipo)</p>

<?php if (empty($acciones)): ?>
    <p class="text-muted text-center">No hay acciones registradas aún.</p>
<?php endif; ?>

<div class="coche-acciones-lista">
<?php foreach ($acciones as $accion): ?>
    <?php
    $fecha = \CodeIgniter\I18n\Time::parse($accion['date']);
    $formateada = $fecha->toLocalizedString('d MMMM y');
    $hace = $fecha->humanize();
    $tituloLower = strtolower($accion['title']);
    $icono = match (true) {
        str_contains($tituloLower, 'aceite')  => 'bi-gear-fill',
        str_contains($tituloLower, 'fuera')   => 'bi-stars',
        str_contains($tituloLower, 'dentro')  => 'bi-wind',
        default                                => 'bi-gear',
    };
    ?>
    <div class="coche-rec-card js-detalle-accion" role="button" tabindex="0"
         data-titulo="<?= esc($accion['title']) ?>"
         data-icono="<?= $icono ?>"
         data-fecha="<?= esc($formateada) ?>"
         data-hace="<?= esc($hace) ?>"
         data-km="<?= esc($accion['kilometers'] ?? '') ?>"
         data-notas="<?= esc($accion['notes'] ?? '') ?>"
         data-editar="<?= site_url('coche/acciones/editar/' . $accion['id']) ?>">
        <div class="coche-rec-icono">
            <i class="bi <?= $icono ?>"></i>
        </div>

        <div class="coche-rec-main">
            <div class="coche-rec-row-top">
                <div class="coche-rec-titulo"><?= esc($accion['title']) ?></div>
                <span class="coche-badge coche-badge-neutro"><?= esc($hace) ?></span>
            </div>

            <div class="coche-rec-row-bottom">
                <div class="coche-rec-meta">
                    <?= esc($formateada) ?>
                    <?php if (!empty($accion['kilometers'])): ?>
                        · <?= esc($accion['kilometers']) ?> km
                    <?php endif; ?>
                </div>

                <div class="coche-rec-actions">
                    <a href="<?= site_url('coche/acciones/editar/' . $accion['id']) ?>" class="coche-btn" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="<?= site_url('coche/acciones/borrar/' . $accion['id']) ?>" method="post" class="m-0"
                          onsubmit="return confirm('¿Borrar esta acción?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="coche-btn coche-btn-danger" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($accion['notes'])): ?>
                <div class="coche-rec-meta"><?= esc($accion['notes']) ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?= $this->include('coche/_modal_detalle') ?>

<?= $this->endSection() ?>
