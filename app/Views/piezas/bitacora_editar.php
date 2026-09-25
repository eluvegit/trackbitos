<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
    /**
     * La bitácora a pantalla completa — la única pantalla de edición desde
     * la fase 50 (se quitó la versión imprimible: solo la editable). Desde
     * el histórico (placas.php) se llega aquí pulsando la tarjeta
     * directamente, sin pasar por ningún modal intermedio.
     */
    $idPlaca = (int) $placa['id'];

    // Atajos a las secciones del formulario (fase 45): en pantalla ancha, un
    // índice fijo a un lado para saltar directo sin hacer scroll a ciegas;
    // en el móvil no cabe ni hace falta, se sigue bajando con el dedo.
    $secciones = [
        'info'    => 'Estado y tiempos',
        'ajustes' => 'Nombre y ajustes',
        'piezas'  => 'Qué llevaba',
        'pruebas' => 'Qué se probaba',
        'notas'   => 'Notas',
        'enlace'  => 'Enlace al Drive',
    ];

    $imagenes = $imagenes ?? [];
    $reparto  = $reparto ?? [];
    $sinMedir = $sinMedir ?? 0;
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-journal-text text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <span class="text-muted fw-normal">#<?= $idPlaca ?></span>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Editar</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="row">
    <nav class="col-md-3 col-lg-2 d-none d-md-block">
        <div class="list-group list-group-flush small position-sticky" style="top: 1rem;">
            <?php foreach ($secciones as $ancla => $titulo): ?>
                <a href="#<?= $ancla ?>" class="list-group-item list-group-item-action py-1 px-2 border-0">
                    <?= esc($titulo) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="col-12 col-md-9 col-lg-7">
        <?php // Los datos (placa, piezas, pruebas, enlaces) le llegan solos al
              // trozo incluido: son los mismos que el controlador pasó a esta
              // vista. ?>
        <?= $this->include('piezas/_bitacora_form') ?>
    </div>

    <?php // Segundo sidebar, a la derecha: la foto de la placa y si cabe o no
          // en una placa, consulta rápida mientras se rellena el resto —
          // fuera del formulario, así que alta/baja de fotos siguen siendo
          // inmediatas igual que antes. ?>
    <div class="col-12 col-lg-3" id="fotos">
        <div class="position-sticky" style="top: 1rem;">
            <div class="small fw-semibold text-body-secondary mb-1">
                <i class="bi bi-camera"></i> Fotos de la plataforma (laminador)
            </div>

            <?php if (empty($imagenes)): ?>
                <p class="text-muted small mb-2">
                    Sin fotos todavía (captura del laminador: orientación, soportes, desde dónde partía).
                </p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2 mb-2">
                    <?php foreach ($imagenes as $img): ?>
                        <div class="position-relative">
                            <a href="<?= imagen_pieza($img, 'placa-imagen', 'v') ?>" target="_blank"
                                title="<?= esc($img['notas'] ?? '') ?>">
                                <img src="<?= imagen_pieza($img, 'placa-imagen') ?>"
                                    class="rounded border d-block" style="width: 100%; height: auto;"
                                    alt="Plataforma del laminador" loading="lazy">
                            </a>
                            <form method="post" action="<?= site_url('piezas/placa-imagen/' . (int) $img['id'] . '/borrar') ?>"
                                onsubmit="return confirm('¿Apartar esta foto a la papelera?');" class="position-absolute top-0 end-0">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .65rem;" title="Borrar">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center mb-3"
                action="<?= site_url('piezas/placa/' . $idPlaca . '/imagen') ?>">
                <?= csrf_field() ?>
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                    class="form-control form-control-sm" style="max-width: 220px;" required>
                <input type="text" name="notas" class="form-control form-control-sm" maxlength="150"
                    placeholder="Nota (opcional)" style="max-width: 180px;">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-upload"></i> Subir foto</button>
            </form>

            <?= $this->include('piezas/_bitacora_reparto') ?>
        </div>
    </div>
</div>

<?= $this->include('piezas/_bitacora_js') ?>

<?= $this->endSection() ?>
