<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    /**
     * La bitácora a pantalla completa — la única pantalla de edición desde
     * la fase 50 (se quitó la versión imprimible: solo la editable). El
     * modal del histórico solo enseña un resumen; editar de verdad es
     * siempre aquí, con "Ver completa".
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
    $repartoBins = $reparto['bins'] ?? [];
    $piezasPorSuperficie = $reparto['piezasPrimeraPlacaPorSuperficie'] ?? null;
    $piezasConMargen     = $reparto['piezasPrimeraPlacaConMargen'] ?? null;
    $sinMedir = $sinMedir ?? 0;
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-journal-text text-primary"></i>
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

            <?php if ($repartoBins !== [] || $sinMedir > 0): ?>
                <div data-reparto>
                    <?php if ($piezasPorSuperficie !== null && $piezasConMargen !== null): ?>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <div class="card border-success-subtle h-100">
                                    <div class="card-body p-2">
                                        <div class="text-success-emphasis small fw-semibold mb-1">
                                            <i class="bi bi-graph-up-arrow"></i> Optimista
                                        </div>
                                        <div class="fs-4 fw-bold lh-1"><?= $piezasPorSuperficie ?></div>
                                        <div class="text-muted" style="font-size: .72rem;">piezas por placa, solo por superficie</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card border-warning-subtle h-100">
                                    <div class="card-body p-2">
                                        <div class="text-warning-emphasis small fw-semibold mb-1">
                                            <i class="bi bi-shield-check"></i> Conservadora
                                        </div>
                                        <div class="fs-4 fw-bold lh-1"><?= $piezasConMargen ?></div>
                                        <div class="text-muted" style="font-size: .72rem;">con 10% de margen de seguridad</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 bg-body-secondary mb-2">
                            <div class="card-body p-2 small text-muted">
                                <i class="bi bi-info-circle"></i>
                                <strong>Optimista</strong>: superficie de la placa entre superficie de la pieza,
                                sin dejar hueco real entre piezas — el máximo teórico.
                                <strong>Conservadora</strong>: la misma cuenta pero reservando un 10% de la placa
                                para los huecos reales entre piezas. Ninguna de las dos es un anidado real como el
                                del laminador (que puede aprovechar la silueta de cada pieza, no solo su caja
                                rectangular) — la cifra de verdad suele caer entre las dos.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="alert <?= count($repartoBins) > 1 ? 'alert-info' : 'alert-secondary' ?> py-2 mb-0 small">
                        <?php if (count($repartoBins) <= 1): ?>
                            <i class="bi bi-grid-3x3"></i> Cabe en <strong>una placa</strong>
                            (<?= round($repartoBins[0]['porcentajeUsado'] ?? 0) ?>% ocupada).
                        <?php else: ?>
                            <i class="bi bi-grid-3x3"></i> No cabe en una placa, pero sí en
                            <strong><?= count($repartoBins) ?></strong> (versión conservadora):
                            <ul class="mb-0 mt-1 ps-3">
                                <?php foreach ($repartoBins as $i => $bin): ?>
                                    <li>
                                        Placa <?= $i + 1 ?> (<?= round($bin['porcentajeUsado']) ?>%):
                                        <?= implode(', ', array_map(
                                            static fn(array $p) => esc($p['etiqueta']) . ($p['cantidad'] > 1 ? ' ×' . $p['cantidad'] : ''),
                                            $bin['piezas']
                                        )) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($sinMedir > 0): ?>
                            <div class="text-warning-emphasis mt-1"><?= $sinMedir ?> STL sin medir, no entran en la cuenta.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php // No cupo entera: mueve las piezas marcadas a una placa
                      // nueva, enlazada a esta como origen. Solo tiene sentido
                      // antes de imprimir — una vez montada, ya no hay nada
                      // que repartir. ?>
                <?php if (count($repartoBins) > 1 && !$placa['impresa_en'] && count($piezas) > 1): ?>
                    <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/repartir') ?>"
                        class="border rounded p-2 mt-2 small">
                        <?= csrf_field() ?>
                        <div class="text-muted mb-1">
                            <i class="bi bi-signpost-split"></i> Repartir: cuántas copias de cada una se van a una placa nueva
                        </div>
                        <?php $sugerenciaReparto = $sugerenciaReparto ?? []; ?>
                        <?php foreach ($piezas as $p): ?>
                            <?php
                                $filaId = (int) $p['fila']['id'];
                                $cantidadFila = (int) $p['fila']['cantidad'];
                                $sugerida = $sugerenciaReparto[$filaId] ?? 0;
                            ?>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <input type="number" name="cantidades[<?= $filaId ?>]" min="0" max="<?= $cantidadFila ?>"
                                    value="<?= $sugerida ?>" class="form-control form-control-sm py-0 px-1" style="width: 3.2em;"
                                    title="Cuántas de las <?= $cantidadFila ?> copias se mueven">
                                <label class="form-check-label">
                                    <?= esc($p['familia']['nombre'] ?? '?') ?> · <?= esc($p['variante']['nombre'] ?? '?') ?><?= $cantidadFila > 1 ? ' (de ' . $cantidadFila . ')' : '' ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-sm btn-primary mt-1 w-100">Repartir seleccionadas</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->include('piezas/_bitacora_js') ?>

<?= $this->endSection() ?>
