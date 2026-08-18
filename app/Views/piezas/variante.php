<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$badges = [
    'borrador'   => 'text-bg-secondary',
    'impresa'    => 'text-bg-info',
    'validada'   => 'text-bg-success',
    'superada'   => 'text-bg-dark',
    'descartada' => 'text-bg-danger',
];
$etiqueta = fn($v) => 'v' . sprintf('%03d', (int) $v['numero']);

/**
 * Por qué esta versión no admite ciertos verbos. Va como texto visible bajo
 * los botones y no como title: un botón deshabilitado no recibe eventos de
 * ratón, así que su tooltip no llega a mostrarse nunca — y la spec (7.1)
 * pide justo lo contrario, que se entienda por qué no se puede.
 */
/**
 * Un botón deshabilitado con su color vivo (btn-outline-info, -success...)
 * se lee como disponible: Bootstrap solo le baja la opacidad, y sobre fondo
 * oscuro un cian al 65% sigue cantando más que un botón gris activo. Cuando
 * no aplica se pinta gris, para que la diferencia se vea de un vistazo.
 */
$boton = function (bool $activo, string $color, string $modal, string $texto): string {
    return sprintf(
        '<button class="btn btn-sm btn-outline-%s" data-bs-toggle="modal" data-bs-target="#%s"%s>%s</button>',
        $activo ? $color : 'secondary',
        $modal,
        $activo ? '' : ' disabled',
        esc($texto)
    );
};

$porQueNo = function (array $v) use ($acciones): array {
    $lineas = [];

    $lineas[] = match ($v['estado']) {
        'borrador'   => 'En borrador: aún no hay pieza física. Solo se puede marcar como impresa o descartar.',
        'impresa'    => 'Impresa y pendiente de juicio: toca validarla o descartarla.',
        'validada'   => 'Es la versión buena: ya no se marca ni se descarta. Para cambiarla, valida otra.',
        'superada'   => 'Fue la buena y la reemplazó otra. Se conserva: se puede retomar o derivar desde ella.',
        'descartada' => 'Descartada, con su motivo. Se conserva para no repetir el error, y se puede retomar.',
        default      => '',
    };

    if (!$acciones['puede_devolver'] && isset($acciones['motivos']['devolver'])) {
        $lineas[] = 'Devolver a trabajo: ' . $acciones['motivos']['devolver'];
    }

    return array_filter($lineas);
};
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-box text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <span class="text-muted fw-normal"><?= esc($familia['nombre']) ?></span>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($variante['nombre']) ?></strong>
    <?php if (!empty($variante['sku'])): ?>
        <span class="badge text-bg-light text-muted border font-monospace"><?= esc($variante['sku']) ?></span>
    <?php endif; ?>
    <?php if (!empty($variante['enlace_original'])): ?>
        <?php // El máster de máxima calidad vive fuera del tracker (Drive u otro sitio): esto es solo el enlace. ?>
        <a href="<?= esc($variante['enlace_original'], 'attr') ?>" target="_blank" rel="noopener"
            class="badge text-bg-light text-muted border text-decoration-none" title="Abrir el original de máxima calidad">
            <i class="bi bi-box-arrow-up-right"></i> original
        </a>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Editar nombre, SKU y enlace al original"
        data-bs-toggle="modal" data-bs-target="#modalSku">
        <i class="bi bi-pencil"></i>
    </button>

    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-grid-3x3-gap"></i> Galería
        <?php if (!empty($carrito)): ?>
            <span class="badge text-bg-primary"><?= count($carrito) ?></span>
        <?php endif; ?>
    </a>
</h5>

<?php
/**
 * Nombre y SKU en el mismo modal pero en DOS formularios, cada uno con su
 * botón: son dos verbos distintos y cada uno puede negarse por su cuenta
 * (un nombre repetido, un SKU que ya tiene otra variante). Con un solo
 * envío, el fallo de uno dejaría al otro aplicado a medias.
 */
?>
<div class="modal fade" id="modalSku" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Editar <?= esc($variante['nombre']) ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small mb-1">Nombre de la variante</label>
                <p class="small text-muted mb-2">
                    Cómo se llama esta línea de diseño dentro de <strong><?= esc($familia['nombre']) ?></strong>
                    (<code>pequeña</code>, <code>grande</code>, <code>calva</code>…). Es también el nombre por el
                    que la pides desde el script, así que al cambiarlo cambia el comando.
                </p>
                <form method="post" class="d-flex gap-1 mb-3"
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/nombre') ?>">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" class="form-control form-control-sm"
                        value="<?= esc($variante['nombre'], 'attr') ?>" maxlength="150" required>
                    <button class="btn btn-sm btn-primary">Guardar</button>
                </form>

                <hr>

                <label class="form-label small mb-1">SKU</label>
                <p class="small text-muted mb-2">Referencia manual, para buscar la pieza cuando alguien te la pida por su código.</p>
                <form method="post" class="d-flex gap-1 mb-3"
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/sku') ?>">
                    <?= csrf_field() ?>
                    <input type="text" name="sku" class="form-control form-control-sm"
                        value="<?= esc($variante['sku'] ?? '', 'attr') ?>" placeholder="p. ej. FLOR-001" maxlength="50">
                    <button class="btn btn-sm btn-primary">Guardar</button>
                </form>

                <hr>

                <label class="form-label small mb-1">Enlace al original</label>
                <p class="small text-muted mb-2">
                    Dónde vive el máster de máxima calidad (p. ej. la malla en bruto de una
                    generación por IA, antes de decimar y limpiar de texturas) — normalmente
                    fuera de aquí, en Drive o similar. Esto solo guarda el enlace, no el fichero.
                </p>
                <form method="post" class="d-flex gap-1"
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/enlace-original') ?>">
                    <?= csrf_field() ?>
                    <input type="url" name="enlace_original" class="form-control form-control-sm"
                        value="<?= esc($variante['enlace_original'] ?? '', 'attr') ?>"
                        placeholder="https://drive.google.com/..." maxlength="500">
                    <button class="btn btn-sm btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<!--
    Los avisos de trabajo vivo van los primeros, antes que cualquier dato o
    acción (spec 7.1): son los que evitan ponerse a trabajar aquí sobre algo
    que quedó a medias en la otra máquina.
-->
<?php if ($bloqueo): ?>
    <div class="alert alert-warning py-2 mb-2 d-flex flex-wrap align-items-center gap-2">
        <div class="flex-grow-1">
            <i class="bi bi-lock-fill"></i>
            <strong>Sesión abierta en <?= esc($bloqueo['maquina']) ?></strong>
            — sesión <?= $bloqueo['numero'] ?>, desde
            <?= $bloqueo['dias'] > 0 ? 'hace ' . $bloqueo['dias'] . ' día(s)' : 'hoy' ?>
            (<?= esc($bloqueo['desde']) ?>).
            <div class="small mt-1">
                Esa máquina tiene el bloqueo: hasta que cierre la sesión, no se puede abrir otra ni promocionar.
                <?php if ($bloqueo['forzable']): ?>
                    Si esa copia ya no existe (se borró la carpeta sin subir ni cerrar), la única salida es forzar el cierre.
                <?php endif; ?>
            </div>
        </div>
        <?php // Solo si no tiene descarga asociada: si la tiene, se cierra desde el aviso de "Descarga sin cerrar" de abajo, que se lleva la sesión por delante. ?>
        <?php if ($bloqueo['forzable']): ?>
            <button type="button" class="btn btn-sm btn-outline-danger"
                data-bs-toggle="modal" data-bs-target="#modalForzarSesion">
                Forzar cierre
            </button>
        <?php endif; ?>
    </div>

    <?php if ($bloqueo['forzable']): ?>
        <div class="modal fade" id="modalForzarSesion" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="<?= site_url('piezas/sesion/' . $bloqueo['id'] . '/forzar-cierre') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="variante_id" value="<?= (int) $variante['id'] ?>">
                    <div class="modal-header">
                        <h6 class="modal-title">Forzar el cierre de la sesión</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small mb-2">
                            Esto es la válvula de escape para cuando <?= esc($bloqueo['maquina']) ?> ya no puede
                            cerrarla (disco formateado, carpeta borrada, equipo roto). <strong>No hay prueba de que
                            no se perdiera trabajo</strong>, y por eso queda registrado como cierre forzado, distinto
                            de un cierre normal.
                        </p>
                        <label class="form-label small">Motivo (obligatorio)</label>
                        <textarea name="motivo" class="form-control form-control-sm" rows="2"
                            placeholder="Borré la carpeta sin acordarme de subir" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-sm btn-danger">Forzar cierre</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php foreach ($pendientes as $p): ?>
    <div class="alert alert-warning py-2 mb-2 d-flex flex-wrap align-items-center gap-2">
        <div class="flex-grow-1">
            <i class="bi bi-download"></i>
            <strong>Descarga sin cerrar en <?= esc($p['maquina']) ?></strong>
            — motivo <?= esc($p['motivo']) ?>, <?= esc($p['fecha']) ?>
            <?= $p['dias'] > 0 ? '(hace ' . $p['dias'] . ' día(s))' : '' ?>
            <div class="small mt-1">
                Hay una copia viva en ese disco. Si trabajaste ahí y no la subiste, bajar en otro equipo
                se llevaría por delante ese trabajo. Ciérrala desde esa máquina con
                <code>trackbitos subir</code> o <code>trackbitos cerrar --sin-cambios</code>.
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#modalForzar<?= $p['id'] ?>">
            Forzar cierre
        </button>
    </div>

    <div class="modal fade" id="modalForzar<?= $p['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="<?= site_url('piezas/descarga/' . $p['id'] . '/forzar-cierre') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="variante_id" value="<?= (int) $variante['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title">Forzar el cierre de la descarga</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-2">
                        Esto es la válvula de escape para cuando <?= esc($p['maquina']) ?> ya no puede devolver
                        su copia (disco formateado, fichero borrado, equipo roto). <strong>No hay prueba de que
                        no se perdiera trabajo</strong>, y por eso queda registrado como cierre forzado, distinto
                        de los demás.
                    </p>
                    <label class="form-label small">Motivo (obligatorio)</label>
                    <textarea name="motivo" class="form-control form-control-sm" rows="2"
                        placeholder="Se formateó el portátil y ese .blend ya no existe" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm btn-danger">Forzar cierre</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="row g-3">
    <div class="col-lg-7">

        <!-- Cabecera: cuál es la buena -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <?php if ($validada): ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge text-bg-success fs-6">
                            <i class="bi bi-check-circle-fill"></i> <?= $etiqueta($validada) ?>
                        </span>
                        <span class="fw-semibold">es la versión buena</span>
                    </div>
                    <div class="small text-muted"><?= esc($validada['cambio']) ?></div>
                    <?php if (!empty($validada['medidas'])): ?>
                        <div class="small text-muted mt-1"><i class="bi bi-rulers"></i> <?= esc($validada['medidas']) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-muted">
                        <i class="bi bi-hourglass"></i>
                        Ninguna versión validada todavía: aún no hay una "buena" que imprimir a ciegas.
                    </div>
                <?php endif; ?>

                <?php if ($origen): ?>
                    <div class="small text-muted mt-2">
                        Derivada de <?= esc($origen['variante']['nombre'] ?? '?') ?> ·
                        <?= $etiqueta($origen) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($variante['notas'])): ?>
                    <div class="small text-muted mt-2"><?= esc($variante['notas']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!--
            "Compuesta de" (spec 11.1 ampliado): qué otras piezas estaban en
            la escena de esta variante. Puramente informativo — no toca
            origen_version_id ni la sincronización.
        -->
        <div class="d-flex align-items-center gap-2 mt-3 mb-2">
            <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Compuesta de</h6>
            <?php if (!empty($versionesParaComponer)): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-auto"
                    data-bs-toggle="modal" data-bs-target="#modalComponente" title="Añadir">
                    <i class="bi bi-plus-lg"></i>
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($componentes)): ?>
            <p class="text-muted small">
                Nada anotado. Si esta pieza incluye otras en la misma escena — un ensamblaje, o
                una que dejaste al lado para partir de ella — añádelas aquí.
            </p>
        <?php else: ?>
            <?php
                $avisoEstado = ['superada' => 'quedó superada', 'descartada' => 'se descartó'];
            ?>
            <ul class="list-group list-group-flush mb-2">
                <?php foreach ($componentes as $c): ?>
                    <?php $v = $c['version']; $va = $c['variante']; $fa = $c['familia']; ?>
                    <li class="list-group-item px-0 py-1 d-flex align-items-start gap-2">
                        <div class="flex-grow-1">
                            <?php if ($v && $va && $fa): ?>
                                <a href="<?= site_url('piezas/variante/' . (int) $va['id']) ?>" class="text-decoration-none text-body">
                                    <?= esc($fa['nombre']) ?> / <?= esc($va['nombre']) ?> · v<?= sprintf('%03d', (int) $v['numero']) ?>
                                </a>
                                <?php if (isset($avisoEstado[$v['estado']])): ?>
                                    <span class="badge text-bg-warning ms-1"><?= esc($avisoEstado[$v['estado']]) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">(esa pieza ya no existe)</span>
                            <?php endif; ?>
                            <?php if (!empty($c['notas'])): ?>
                                <div class="small text-muted"><?= esc($c['notas']) ?></div>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="<?= site_url('piezas/componente/' . (int) $c['id'] . '/borrar') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="variante_id" value="<?= (int) $variante['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Quitar"><i class="bi bi-x"></i></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($versionesParaComponer)): ?>
            <div class="modal fade" id="modalComponente" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content" method="post"
                        action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/componente') ?>">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h6 class="modal-title">Añadir a "Compuesta de"</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-2">
                                Qué otra pieza (en qué versión) estaba también en esta escena. Es solo
                                para recordarlo — no afecta a nada del sistema.
                            </p>
                            <label class="form-label small">Pieza / versión</label>
                            <select name="version_componente_id" class="form-select form-select-sm mb-2" required>
                                <?php foreach ($versionesParaComponer as $v): ?>
                                    <option value="<?= (int) $v['id'] ?>">
                                        <?= esc($v['familia_nombre']) ?> / <?= esc($v['variante_nombre']) ?>
                                        · v<?= sprintf('%03d', (int) $v['numero']) ?> (<?= esc($v['estado']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label class="form-label small">Notas (opcional)</label>
                            <input type="text" name="notas" class="form-control form-control-sm"
                                placeholder="p. ej. para partir de ahí" maxlength="255">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-sm btn-primary">Añadir</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Historial de versiones, en orden inverso -->
        <h6 class="mb-2"><i class="bi bi-clock-history"></i> Historial</h6>

        <?php if (empty($versiones)): ?>
            <p class="text-muted small">
                Todavía no se ha promocionado ninguna versión. Sube una sesión desde el cliente y promociónala
                cuando el modelo esté en un punto que merezca congelarse.
            </p>
        <?php else: ?>
            <?php foreach ($versiones as $v): ?>
                <div class="card shadow-sm mb-2">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="badge <?= $badges[$v['estado']] ?? 'text-bg-secondary' ?>"><?= $etiqueta($v) ?></span>
                            <span class="text-muted small"><?= esc($v['estado']) ?></span>
                            <span class="text-muted small ms-auto"><?= esc($v['promocionada_en']) ?></span>
                        </div>

                        <div class="mb-1"><?= esc($v['cambio']) ?></div>

                        <?php if ($v['pendiente_de_juicio']): ?>
                            <div class="alert alert-secondary py-1 px-2 small my-2">
                                <i class="bi bi-hourglass-split"></i>
                                Lleva <?= (int) floor((time() - strtotime($v['promocionada_en'])) / 86400) ?> días
                                en <?= esc($v['estado']) ?> sin resolverse. ¿Se imprimió? ¿Sirvió?
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($v['medidas'])): ?>
                            <div class="small text-muted"><i class="bi bi-rulers"></i> <?= esc($v['medidas']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($v['params_impresion'])): ?>
                            <div class="small text-muted"><i class="bi bi-printer"></i> <?= esc($v['params_impresion']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($v['resultado'])): ?>
                            <div class="small text-muted"><i class="bi bi-clipboard-check"></i> <?= esc($v['resultado']) ?></div>
                        <?php endif; ?>

                        <?php if ($v['sesiones']['total'] > 0): ?>
                            <div class="small text-muted">
                                <i class="bi bi-layers"></i>
                                <?= (int) $v['sesiones']['total'] ?> sesión(es) de trabajo detrás
                                <?php if ($v['sesiones']['purgadas'] > 0): ?>
                                    · <?= (int) $v['sesiones']['purgadas'] ?> con el .blend ya purgado
                                    <span class="text-muted">(el fichero bueno es el de esta versión)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Renders de esta versión: el resultado visual de esta iteración concreta -->
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <?php foreach ($v['renders'] as $r): ?>
                                <div class="position-relative" style="width: 56px;">
                                    <a href="<?= site_url('piezas/render/' . (int) $r['id'] . '/imagen') ?>" target="_blank"
                                        title="<?= esc($r['notas'] ?? '') ?>">
                                        <img src="<?= site_url('piezas/render/' . (int) $r['id'] . '/imagen') ?>"
                                            class="rounded border" style="width: 56px; height: 56px; object-fit: cover;"
                                            alt="Render" loading="lazy">
                                    </a>
                                    <form method="post" action="<?= site_url('piezas/render/' . (int) $r['id'] . '/borrar') ?>"
                                        onsubmit="return confirm('¿Apartar este render a la papelera?');" class="position-absolute top-0 end-0">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .6rem;" title="Borrar">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                data-bs-toggle="modal" data-bs-target="#modalRender<?= $v['id'] ?>" title="Añadir render">
                                <i class="bi bi-image"></i> <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>

                        <!--
                            STL para imprimir: aparte del .blend, inmutable una vez adjuntado.
                            Las dos descargas (blend siempre, STL cuando ya existe) comparten el
                            mismo azul sólido — es el color de "aquí hay un fichero listo para
                            bajar". "Adjuntar STL" se queda en gris outline a propósito: es una
                            acción pendiente, no una descarga, y el contraste de color es lo que
                            dice de un vistazo si la versión ya tiene STL o todavía no.
                        -->
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <a href="<?= site_url('piezas/version/' . $v['id'] . '/blend/descargar') ?>"
                                class="btn btn-sm btn-primary py-0 px-2">
                                <i class="bi bi-file-earmark-arrow-down"></i> Descargar .blend
                            </a>
                            <?php if (!empty($v['ruta_stl'])): ?>
                                <a href="<?= site_url('piezas/version/' . $v['id'] . '/stl/descargar') ?>"
                                    class="btn btn-sm btn-primary py-0 px-2">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar STL
                                </a>
                                <?php if ($v['estado'] === 'validada'): ?>
                                    <?php if (in_array((int) $v['id'], $carrito, true)): ?>
                                        <form method="post" action="<?= site_url('piezas/carrito/quitar/' . $v['id']) ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-success py-0 px-2">
                                                <i class="bi bi-check-lg"></i> En la placa
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= site_url('piezas/carrito/agregar/' . $v['id']) ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="bi bi-plus-lg"></i> Añadir a la placa
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                                    data-bs-toggle="modal" data-bs-target="#modalStl<?= $v['id'] ?>">
                                    <i class="bi bi-file-earmark-arrow-up"></i> Adjuntar STL
                                </button>
                            <?php endif; ?>
                        </div>

                        <!--
                            Aviso, no tooltip (spec 7.1): esta descarga no abre asiento,
                            así que el sistema no sabe que esa copia existe. Va en una
                            sola línea a propósito — se repite en cada versión del
                            historial, y el porqué completo está en "Desde tu máquina".
                        -->
                        <div class="text-warning-emphasis small mt-1">
                            <i class="bi bi-exclamation-triangle"></i>
                            Ese <code>.blend</code> <strong>no queda registrado</strong>: para mirar, no para trabajar.
                        </div>

                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <!--
                                Botones siempre visibles, deshabilitados con explicación cuando
                                no aplican (spec 7.1): ocultarlos dejaría al usuario sin saber
                                qué le falta para poder.
                            -->
                            <?= $boton($v['estado'] === 'borrador', 'info', 'modalImpresa' . $v['id'], 'Marcar impresa') ?>
                            <?= $boton($v['estado'] === 'impresa', 'success', 'modalValidar' . $v['id'], 'Validar') ?>
                            <?= $boton(in_array($v['estado'], ['borrador', 'impresa'], true), 'danger', 'modalDescartar' . $v['id'], 'Descartar') ?>
                            <?= $boton($acciones['puede_devolver'], 'light', 'modalDevolver' . $v['id'], 'Devolver a trabajo') ?>
                            <?= $boton(true, 'primary', 'modalDerivar' . $v['id'], 'Derivar variante') ?>
                        </div>

                        <?php foreach ($porQueNo($v) as $linea): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-info-circle"></i> <?= esc($linea) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modales de los verbos de esta versión -->
                <div class="modal fade" id="modalImpresa<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" action="<?= site_url('piezas/version/' . $v['id'] . '/impresa') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title"><?= $etiqueta($v) ?> impresa</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label small">Parámetros de impresión</label>
                                <textarea name="params_impresion" class="form-control form-control-sm" rows="2"
                                    placeholder="exposición 2.4s, capa 0.05mm, 5 capas base"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-info">Marcar impresa</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="modalValidar<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" action="<?= site_url('piezas/version/' . $v['id'] . '/validar') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">Validar <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    Pasa a ser la versión buena.
                                    <?php if ($validada): ?>
                                        La <?= $etiqueta($validada) ?>, que lo era hasta ahora, pasará a superada.
                                    <?php endif; ?>
                                </p>
                                <label class="form-label small">Resultado</label>
                                <textarea name="resultado" class="form-control form-control-sm" rows="2"
                                    placeholder="Encaja con el clic original, sin holgura"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-success">Validar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="modalDescartar<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" action="<?= site_url('piezas/version/' . $v['id'] . '/descartar') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">Descartar <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    No se borra: se conserva con el motivo, para no repetir el mismo error dentro de tres meses.
                                </p>
                                <label class="form-label small">Motivo (obligatorio)</label>
                                <textarea name="resultado" class="form-control form-control-sm" rows="2"
                                    placeholder="El eje quedó 0.2mm holgado, la pieza baila" required></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-danger">Descartar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="modalDevolver<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" action="<?= site_url('piezas/version/' . $v['id'] . '/devolver-a-trabajo') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">Volver a trabajar desde <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    Abre una rama nueva partiendo de esta versión. <strong>La versión no se toca</strong>:
                                    nunca se edita una existente, se apila encima.
                                </p>
                                <?php if ($rama): ?>
                                    <div class="alert alert-warning py-2 small mb-2">
                                        Ahora mismo está abierta la rama <strong><?= esc($ramaNombre) ?></strong>.
                                        Como solo puede haber una abierta, volver aquí la cerraría sin promocionarla:
                                        sus sesiones quedan en el historial, pero no llegan a ser versión.
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="abandonar_rama" value="1"
                                            id="abandonar<?= $v['id'] ?>" required>
                                        <label class="form-check-label small" for="abandonar<?= $v['id'] ?>">
                                            Lo entiendo: abandona <?= esc($ramaNombre) ?> y empieza desde <?= $etiqueta($v) ?>.
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-primary">Abrir rama</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="modalDerivar<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" action="<?= site_url('piezas/version/' . $v['id'] . '/derivar') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">Derivar variante desde <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    Una línea de diseño nueva que parte de aquí, con su propia numeración desde v001.
                                    No copia ficheros ni referencias.
                                </p>
                                <label class="form-label small">Nombre de la variante</label>
                                <input type="text" name="nombre" class="form-control form-control-sm mb-2"
                                    placeholder="pose-futbolista" maxlength="150" required>
                                <label class="form-label small">Notas</label>
                                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-primary">Derivar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="modalRender<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" enctype="multipart/form-data"
                            action="<?= site_url('piezas/version/' . $v['id'] . '/render') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">Render de <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label small">Imagen</label>
                                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-2" required>
                                <label class="form-label small">Notas</label>
                                <textarea name="notas" class="form-control form-control-sm" rows="2"
                                    placeholder="Vista frontal, viewport de Blender"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-primary">Subir</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal fade" id="modalStl<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" method="post" enctype="multipart/form-data"
                            action="<?= site_url('piezas/version/' . $v['id'] . '/stl') ?>">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h6 class="modal-title">STL de <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    Una vez adjuntado no se puede reemplazar: si el modelo cambia, promociona
                                    una versión nueva y sube el STL ahí.
                                </p>
                                <label class="form-label small">Fichero .stl</label>
                                <input type="file" name="stl" accept=".stl" class="form-control form-control-sm" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-primary">Adjuntar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">

        <!-- Trabajo en curso -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="mb-2"><i class="bi bi-tools"></i> Trabajo en curso</h6>

                <?php if (!$rama): ?>
                    <p class="text-muted small mb-0">No hay ninguna rama de trabajo abierta.</p>
                <?php else: ?>
                    <div class="small mb-2">
                        Rama <strong><?= esc($ramaNombre) ?></strong>
                        <span class="text-muted">· abierta el <?= esc($rama['abierta_en']) ?></span>
                    </div>

                    <?php if ($estado['hash_nube']): ?>
                        <!-- Spec 7.2: mostrar el hash de la nube para que el cliente pueda contrastar. -->
                        <div class="small text-muted mb-2">
                            Hash de la nube:
                            <code class="user-select-all"><?= esc(substr($estado['hash_nube'], 0, 16)) ?></code><span class="text-muted">…</span>
                            <?php if ($estado['origen_descarga']): ?>
                                <br>Se parte de <?= esc($estado['origen_descarga']['tipo']) ?>
                                <?= $estado['origen_descarga']['tipo'] === 'version'
                                        ? 'v' . sprintf('%03d', (int) $estado['origen_descarga']['numero'])
                                        : (int) $estado['origen_descarga']['numero'] ?>.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($sesiones)): ?>
                        <p class="text-muted small mb-0">Sin sesiones en esta rama todavía.</p>
                    <?php else: ?>
                        <ul class="list-unstyled small mb-0">
                            <?php foreach ($sesiones as $s): ?>
                                <li class="border-top py-1">
                                    <span class="fw-semibold">Sesión <?= (int) $s['numero'] ?></span>
                                    <span class="text-muted">· <?= esc($s['maquina'] ?? '?') ?></span>
                                    <?php if (empty($s['cerrada_en'])): ?>
                                        <span class="badge text-bg-warning">abierta</span>
                                    <?php endif; ?>
                                    <?php if (!empty($s['purgada'])): ?>
                                        <span class="badge text-bg-dark" title="Su fichero se apartó a la papelera al validarse la versión">purgada</span>
                                    <?php endif; ?>
                                    <?php if (!empty($s['subida_en'])): ?>
                                        <div class="text-muted">
                                            subida <?= esc($s['subida_en']) ?>
                                            <?php if (!empty($s['tamano_bytes'])): ?>
                                                · <?= number_format((int) $s['tamano_bytes'] / 1024, 0) ?> KB
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">sin subir</div>
                                    <?php endif; ?>
                                    <?php if (!empty($s['log'])): ?>
                                        <div class="text-muted fst-italic"><?= esc($s['log']) ?></div>
                                    <?php endif; ?>

                                    <?php // Cerrada, sin promocionar todavía y sin purgar ya: candidata a liberar sitio a mano (p.ej. una subida de prueba demasiado pesada). ?>
                                    <?php if (!empty($s['cerrada_en']) && empty($s['purgada']) && !empty($s['ruta_blend'])): ?>
                                        <form method="post" class="mt-1"
                                            action="<?= site_url('piezas/sesion/' . (int) $s['id'] . '/descartar-fichero') ?>"
                                            onsubmit="return confirm('¿Apartar el .blend de la sesión <?= (int) $s['numero'] ?> a la papelera? Sigue en el historial, pero deja de ocupar sitio.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="variante_id" value="<?= (int) $variante['id'] ?>">
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Apartar el fichero a la papelera y liberar sitio">
                                                <i class="bi bi-trash"></i> liberar sitio
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Promocionar -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="mb-2"><i class="bi bi-award"></i> Promocionar</h6>

                <?php if (!$acciones['puede_promocionar']): ?>
                    <p class="text-muted small mb-2"><?= esc($acciones['motivos']['promocionar']) ?></p>
                <?php else: ?>
                    <p class="text-muted small mb-2">
                        Congela la sesión <?= (int) $estado['ultima_subida']['numero'] ?> como versión nueva,
                        cierra esta rama y abre la siguiente.
                    </p>
                <?php endif; ?>

                <?php
                    // En la v001 no hay nada anterior que modificar, así que
                    // "qué se modificó" no aplica: lo que da valor al historial
                    // ahí es de dónde sale la pieza. El campo es el mismo
                    // (version.cambio, obligatorio por el invariante 7); lo que
                    // cambia es la pregunta, para que se pueda responder.
                    $primeraVersion = empty($versiones);
                ?>
                <form method="post" action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/promocionar') ?>">
                    <?= csrf_field() ?>
                    <label class="form-label small">
                        <?= $primeraVersion ? 'Qué es esta pieza (obligatorio)' : 'Qué se modificó (obligatorio)' ?>
                    </label>
                    <input type="text" name="cambio" class="form-control form-control-sm mb-2"
                        placeholder="<?= $primeraVersion ? 'Primer modelado a partir de la foto del original' : 'Brazo 0.4mm más grueso en la unión' ?>"
                        <?= $acciones['puede_promocionar'] ? 'required' : 'disabled' ?>>
                    <label class="form-label small">Medidas</label>
                    <input type="text" name="medidas" class="form-control form-control-sm mb-2"
                        placeholder="eje 4.9mm, pared 1.2mm"
                        <?= $acciones['puede_promocionar'] ? '' : 'disabled' ?>>
                    <button class="btn btn-sm w-100 <?= $acciones['puede_promocionar'] ? 'btn-primary' : 'btn-secondary' ?>"
                        <?= $acciones['puede_promocionar'] ? '' : 'disabled' ?>>
                        Promocionar
                    </button>
                </form>
            </div>
        </div>

        <!-- Referencias del original: de la PIEZA, no de esta variante (spec 1.1) -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h6 class="mb-0"><i class="bi bi-camera"></i> Referencias</h6>
                    <span class="text-muted small">de <?= esc($familia['nombre'] ?? 'la pieza') ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-auto"
                        data-bs-toggle="modal" data-bs-target="#modalReferencia">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <?php if (empty($referencias)): ?>
                    <p class="text-muted small mb-0">
                        Sin fotos de referencia todavía (medidas de calibre, ángulos del original).
                        Son de la pieza entera: las verás igual desde cualquiera de sus variantes.
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($referencias as $r): ?>
                            <div class="position-relative" style="width: 72px;">
                                <a href="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/imagen') ?>" target="_blank"
                                    title="<?= esc($r['notas'] ?? '') ?>">
                                    <img src="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/imagen') ?>"
                                        class="rounded border" style="width: 72px; height: 72px; object-fit: cover;"
                                        alt="Referencia" loading="lazy">
                                </a>
                                <form method="post" action="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Apartar esta referencia a la papelera?');" class="position-absolute top-0 end-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="volver_a_variante" value="<?= (int) $variante['id'] ?>">
                                    <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .65rem;" title="Borrar">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alta de referencia -->
        <div class="modal fade" id="modalReferencia" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" enctype="multipart/form-data"
                    action="<?= site_url('piezas/familia/' . (int) $variante['familia_id'] . '/referencia') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="volver_a_variante" value="<?= (int) $variante['id'] ?>">
                    <div class="modal-header">
                        <h6 class="modal-title">Referencia para <?= esc($familia['nombre'] ?? 'la pieza') ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Foto</label>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-2" required>
                        <label class="form-label small">Notas (medidas de calibre, qué muestra)</label>
                        <textarea name="notas" class="form-control form-control-sm" rows="2"
                            placeholder="Alto total 78mm con calibre, vista frontal"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-sm btn-primary">Subir</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cómo se toca el fichero -->
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <h6 class="mb-2"><i class="bi bi-terminal"></i> Desde tu máquina</h6>
                <p class="text-muted small mb-2">
                    El <strong>trabajo</strong> no se baja desde aquí: quien toca el disco es el script, que es el único
                    que puede identificar la máquina y cuadrar la descarga. Esta web puede abrirse desde el móvil.
                    Lo que sí puedes bajar del navegador es el <code>.blend</code> de una versión ya cerrada,
                    pero solo para mirarlo: esa copia no queda registrada.
                </p>
                <?php
                    // Familia + variante, entrecomillado: el nombre de la variante
                    // solo es único dentro de su familia ("estandar" se repite en
                    // cuanto hay dos piezas) y este comando es para copiar y pegar
                    // — tiene que funcionar tal cual, no la mitad de las veces.
                    $refCli = trim(($familia['nombre'] ?? '') . ' ' . $variante['nombre']);
                ?>
                <pre class="small mb-0 user-select-all"><code>trackbitos bajar "<?= esc($refCli) ?>"

trackbitos estado
trackbitos subir
trackbitos cerrar</code></pre>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
