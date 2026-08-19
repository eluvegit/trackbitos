<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$badges = [
    'borrador'   => 'text-bg-secondary',
    // Azul, no el cyan de "info": sobre el tema oscuro salía chillón. Mismo
    // color que el badge "sin validar" del listado — es el mismo estado.
    'impresa'    => 'text-bg-primary',
    'validada'   => 'text-bg-success',
    'superada'   => 'text-bg-dark',
    'descartada' => 'text-bg-danger',
];
$etiqueta = fn($v) => 'v' . sprintf('%03d', (int) $v['numero']);

/**
 * El estado, dicho para quien lee el historial. "borrador" es el nombre del
 * ENUM y no dice nada de lo que toca hacer: esa versión está congelada y
 * esperando a que la imprimas y digas si sirve. Los demás ya se explican
 * solos, así que no se tocan — traducir por traducir alejaría el texto de
 * la base de datos sin ganar nada.
 */
$nombreEstado = static fn(string $estado): string => [
    'borrador' => 'para imprimir y evaluar',
][$estado] ?? $estado;

/** KB para lo pequeño, MB con un decimal en cuanto pasa de 1 MB — un .blend de IA en 102400 KB no se lee de un vistazo. */
$tamanoLegible = function (?int $bytes): string {
    if ($bytes === null) {
        return '';
    }

    return $bytes >= 1024 * 1024
        ? number_format($bytes / (1024 * 1024), 1) . ' MB'
        : number_format($bytes / 1024, 0) . ' KB';
};

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

/**
 * "Devolver a trabajo" reabre una rama partiendo de esta versión — pero si
 * la rama ya abierta parte justo de ella (p. ej. recién promocionada, o
 * "devolver" ya usado antes y todavía sin subir nada), pulsarlo otra vez
 * cerraría esa rama vacía para abrir una idéntica: nada que devolver, solo
 * confusión ("¿qué abandono si no hay nada?"). Aparte de $acciones para que
 * $porQueNo pueda decir POR QUÉ, no solo que no se puede.
 */
$puedeDevolver = function (array $v) use ($acciones): bool {
    return $acciones['puede_devolver']
        && (int) ($acciones['rama_desde_version_id'] ?? 0) !== (int) $v['id'];
};

$porQueNo = function (array $v) use ($acciones, $puedeDevolver): array {
    $lineas = [];

    $lineas[] = match ($v['estado']) {
        'borrador'   => 'En borrador: aún no hay pieza física. Solo se puede marcar como impresa o descartar.',
        'impresa'    => 'Impresa y pendiente de juicio: toca validarla o descartarla.',
        'validada'   => 'Es la versión buena: ya no se marca ni se descarta. Para cambiarla, valida otra.',
        'superada'   => 'Fue la buena y la reemplazó otra. Se conserva: se puede retomar o derivar desde ella.',
        'descartada' => 'Descartada, con su motivo. Se conserva para no repetir el error, y se puede retomar.',
        default      => '',
    };

    if (!$puedeDevolver($v)) {
        if ((int) ($acciones['rama_desde_version_id'] ?? 0) === (int) $v['id']) {
            $lineas[] = 'Ya tienes la rama de trabajo abierta a partir de esta misma versión — sigue '
                . 'trabajando ahí (trackbitos bajar), no hace falta volver a abrirla.';
        } elseif (isset($acciones['motivos']['devolver'])) {
            $lineas[] = 'Devolver a trabajo: ' . $acciones['motivos']['devolver'];
        }
    }

    return array_filter($lineas);
};

/**
 * Cómo se llama esta variante para el CLI: familia + variante, entrecomillado.
 * El nombre de la variante solo es único dentro de su familia ("base" se
 * repite en cuanto hay dos piezas) y esto es para copiar y pegar — tiene que
 * funcionar tal cual, no la mitad de las veces. Se usa en dos sitios: la
 * tarjeta "Desde tu máquina" del final y el atajo de cada versión.
 */
$refCli = trim(($familia['nombre'] ?? '') . ' ' . $variante['nombre']);
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
    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Editar nombre de la pieza, de la variante, SKU y enlace al original"
        data-bs-toggle="modal" data-bs-target="#modalSku">
        <i class="bi bi-pencil"></i>
    </button>
    <?php // Borra solo esta variante (invariante 6, ahora también suelta): el resto de la pieza sigue intacto. ?>
    <form method="post" action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/borrar') ?>"
        onsubmit="return confirm('¿Mandar «<?= esc($familia['nombre'] . ' / ' . $variante['nombre'], 'attr') ?>» a la papelera? Se puede restaurar durante 30 días.');">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Borrar esta variante">
            <i class="bi bi-trash"></i>
        </button>
    </form>

    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-grid-3x3-gap"></i> Galería
        <?php if (!empty($carrito)): ?>
            <span class="badge text-bg-primary"><?= count($carrito) ?></span>
        <?php endif; ?>
    </a>
</h5>

<?php
/**
 * Nombre de pieza, nombre de variante, SKU y enlace al original en el mismo
 * modal pero cada uno en su propio formulario: son verbos distintos y cada
 * uno puede negarse por su cuenta (un nombre repetido, un SKU que ya tiene
 * otra variante). Con un solo envío, el fallo de uno dejaría a los demás
 * aplicados a medias.
 */
?>
<div class="modal fade" id="modalSku" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Editar <?= esc($familia['nombre']) ?> / <?= esc($variante['nombre']) ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small mb-1">Nombre de la pieza</label>
                <p class="small text-muted mb-2">
                    Cómo se llama la pieza entera (<code>Flores</code>, <code>Lupa</code>…), aparte de
                    cuántas variantes tenga dentro.
                </p>
                <form method="post" class="d-flex gap-1 mb-3"
                    action="<?= site_url('piezas/familia/' . (int) $familia['id'] . '/nombre') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="variante_id" value="<?= (int) $variante['id'] ?>">
                    <input type="text" name="nombre" class="form-control form-control-sm"
                        value="<?= esc($familia['nombre'], 'attr') ?>" maxlength="150" required>
                    <button class="btn btn-sm btn-primary">Guardar</button>
                </form>

                <hr>

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
<?php // Invariante 9: va el primero de todos. Es lo que impide abrir trabajo
      // nuevo desde el cliente, y allí no hay botones que se puedan desactivar
      // — el usuario solo se encontraría un error al escribir "abrir". ?>
<?php if (!empty($acciones['sin_juzgar'])): ?>
    <?php // Ojo con el nombre: $pendientes ya está cogido más abajo, por las descargas sin cerrar. ?>
    <?php $sinJuzgar = $acciones['sin_juzgar']; ?>
    <div class="alert alert-primary py-2 mb-2">
        <i class="bi bi-printer-fill"></i>
        <strong>
            <?= count($sinJuzgar) === 1 ? 'La v' . sprintf('%03d', $sinJuzgar[0]) . ' está impresa y sin juzgar' : 'Hay ' . count($sinJuzgar) . ' versiones impresas y sin juzgar' ?>
        </strong>
        <div class="small mt-1">
            Hasta que digas si esa impresión sirve, esta pieza está parada: no se puede abrir sesión desde el
            cliente ni devolverla a trabajo. Es a propósito — seguir modelando encima sería partir de algo que
            no sabes si funciona, y el juicio que no se hace con la pieza en la mano no se hace nunca.
            <strong>Si ya sabes que no vale, descártala con el motivo y sigue desde ahí.</strong>
        </div>
        <div class="mt-2 d-flex flex-wrap gap-2">
            <?php foreach ($versiones as $v): ?>
                <?php if ($v['estado'] !== 'impresa') continue; ?>
                <a href="#version-<?= (int) $v['id'] ?>" class="btn btn-sm btn-outline-primary">
                    Juzgar <?= $etiqueta($v) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

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

        <!-- Historial de versiones, en orden inverso -->
        <h6 class="mb-2"><i class="bi bi-clock-history"></i> Historial</h6>

        <?php if (empty($versiones)): ?>
            <p class="text-muted small">
                Todavía no se ha promocionado ninguna versión. Sube una sesión desde el cliente y promociónala
                cuando el modelo esté en un punto que merezca congelarse.
            </p>
        <?php else: ?>
            <?php foreach ($versiones as $v): ?>
                <div class="card shadow-sm mb-2" id="version-<?= (int) $v['id'] ?>">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="badge <?= $badges[$v['estado']] ?? 'text-bg-secondary' ?>"><?= $etiqueta($v) ?></span>
                            <span class="text-muted small"><?= esc($nombreEstado($v['estado'])) ?></span>
                            <span class="text-muted small ms-auto"><?= esc($v['promocionada_en']) ?></span>
                        </div>

                        <div class="mb-1"><?= esc($v['cambio']) ?></div>

                        <?php if ($v['pendiente_de_juicio']): ?>
                            <div class="alert alert-secondary py-1 px-2 small my-2">
                                <i class="bi bi-hourglass-split"></i>
                                <?php // Sin el nombre del estado incrustado en la frase: con la etiqueta
                                      // larga ("para imprimir y evaluar") la oración dejaba de leerse. ?>
                                Lleva <?= (int) floor((time() - strtotime($v['promocionada_en'])) / 86400) ?> días
                                sin resolverse. ¿Se imprimió? ¿Sirvió?
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
                            <?php
                                /**
                                 * Antes era un enlace directo con el aviso escrito debajo, a
                                 * secas, en cada versión del historial — fácil de saltarse sin
                                 * leerlo. Ahora hace falta pasar por el modal para llegar al
                                 * enlace de verdad: no evita nada (spec 0, "se niega y explica",
                                 * no "pregunta ¿estás seguro?"), pero sí obliga a ver la
                                 * advertencia una vez antes de descargar, no solo a convivir con
                                 * ella de fondo.
                                 */
                            ?>
                            <button type="button" class="btn btn-sm btn-primary py-0 px-2"
                                data-bs-toggle="modal" data-bs-target="#modalDescargarBlend<?= $v['id'] ?>">
                                <i class="bi bi-file-earmark-arrow-down"></i> Descargar .blend
                                <?php if ($v['tamano_blend'] !== null): ?>
                                    <span class="opacity-75">(<?= $tamanoLegible($v['tamano_blend']) ?>)</span>
                                <?php endif; ?>
                            </button>
                            <?php
                                /*
                                 * Un STL por trozo a imprimir, no uno por versión: los dos brazos
                                 * van por separado aunque compartan .blend, y una pieza más alta
                                 * que la placa se corta y se monta. El .blend sigue siendo uno
                                 * solo — ahí están todas las partes juntas.
                                 */
                            ?>
                            <?php foreach ($v['stls'] as $stl): ?>
                                <?php
                                    /*
                                     * El botón tiene que decir QUÉ se descarga, no solo de qué
                                     * trozo es: junto a "Descargar .blend", un botón que ponga
                                     * solo "completo" no se lee como una descarga de nada.
                                     *
                                     * El nombre solo se intercala cuando aporta: con un único
                                     * STL llamado "completo" (el nombre que se pone solo) sería
                                     * repetir que la pieza entera es la pieza entera.
                                     */
                                    $soloUno = count($v['stls']) === 1;
                                    $queTrozo = ($soloUno && mb_strtolower($stl['nombre']) === 'completo')
                                        ? ''
                                        : ' ' . $stl['nombre'];
                                ?>
                                <div class="btn-group" role="group">
                                    <a href="<?= site_url('piezas/stl/' . (int) $stl['id'] . '/descargar') ?>"
                                        class="btn btn-sm btn-primary py-0 px-2">
                                        <i class="bi bi-file-earmark-arrow-down"></i>
                                        Descargar<?= esc($queTrozo) ?> .STL
                                        <?php if ($stl['tamano'] !== null): ?>
                                            <span class="opacity-75">(<?= $tamanoLegible($stl['tamano']) ?>)</span>
                                        <?php endif; ?>
                                    </a>
                                    <form method="post" action="<?= site_url('piezas/stl/' . (int) $stl['id'] . '/quitar') ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1 h-100"
                                            title="Quitar este STL (va a la papelera, 30 días)">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>

                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                                data-bs-toggle="modal" data-bs-target="#modalStl<?= $v['id'] ?>">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                                <?= empty($v['stls']) ? 'Adjuntar STL' : 'Añadir otro STL' ?>
                            </button>

                            <?php // La placa se lleva TODOS los trozos de la versión: media pieza no se imprime. ?>
                            <?php if (!empty($v['stls']) && $v['estado'] === 'validada'): ?>
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
                                            <?php if (count($v['stls']) > 1): ?>
                                                <span class="opacity-75">(<?= count($v['stls']) ?> trozos)</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
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
                            <?php // Solo aparece donde sirve de algo: un botón "Deshacer" apagado en
                                  // todas las tarjetas invitaría a leerlo como que se puede deshacer todo. ?>
                            <?php if (in_array($v['estado'], ['impresa', 'descartada'], true)): ?>
                                <?= $boton(true, 'warning', 'modalDeshacer' . $v['id'], 'Deshacer') ?>
                            <?php endif; ?>
                            <?= $boton($puedeDevolver($v), 'light', 'modalDevolver' . $v['id'], 'Devolver a trabajo') ?>
                            <?= $boton(true, 'primary', 'modalDerivar' . $v['id'], 'Derivar variante') ?>
                        </div>

                        <?php foreach ($porQueNo($v) as $linea): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-info-circle"></i> <?= esc($linea) ?></div>
                        <?php endforeach; ?>

                        <?php // Cuando la rama abierta ya parte de esta versión, todos los botones de
                              // estado se apagan y la ficha parece un callejón sin salida. No lo es: el
                              // camino sigue en la terminal, así que el comando va aquí mismo, listo
                              // para copiar, en vez de en una frase que remite a otra caja de la página. ?>
                        <?php if ((int) ($acciones['rama_desde_version_id'] ?? 0) === (int) $v['id']): ?>
                            <div class="mt-2 p-2 rounded border bg-body-tertiary">
                                <div class="small text-body-secondary mb-1">
                                    <i class="bi bi-terminal"></i> Sigue desde tu máquina:
                                </div>
                                <pre class="small mb-0 user-select-all"><code>trackbitos bajar "<?= esc($refCli) ?>"</code></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!--
                    Descargar .blend: esta descarga no abre asiento (no pasa por el
                    cliente, que es el único que sabe identificar la máquina), así que
                    el sistema no se entera de que esa copia existe. El modal obliga a
                    ver el porqué una vez antes de descargar, en vez de convivir con un
                    aviso de fondo que se acaba dejando de leer. Explicación completa en
                    la tarjeta "Desde tu máquina", más abajo.
                -->
                <div class="modal fade" id="modalDescargarBlend<?= $v['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title">Descargar <?= $etiqueta($v) ?></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-warning-emphasis small">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Ese <code>.blend</code> <strong>no queda registrado</strong>: sirve para mirarlo o
                                    para añadirlo como referencia a otra escena, no para trabajar sobre él. El
                                    sistema no sabe que esta copia existe, así que lo que hagas y subas desde
                                    aquí no cuadraría con ningún asiento — y en algún momento la carpeta donde la
                                    guardes será la que borres tú mismo, no algo que el módulo purgue por su cuenta.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <a href="<?= site_url('piezas/version/' . $v['id'] . '/blend/descargar') ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar
                                </a>
                            </div>
                        </div>
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
                                <?php if (!empty($sugerenciaImpresion)): ?>
                                    <p class="small text-muted mb-1">
                                        Precargado con lo que se usó la última vez que se imprimió esta pieza —
                                        ajusta lo que cambie y deja lo demás igual.
                                    </p>
                                <?php endif; ?>
                                <textarea name="params_impresion" class="form-control form-control-sm" rows="2"
                                    placeholder="exposición 2.4s, capa 0.05mm, 5 capas base, posición en la placa: borde derecho, inclinada 45°"><?= esc($sugerenciaImpresion ?? '') ?></textarea>
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

                <?php if (in_array($v['estado'], ['impresa', 'descartada'], true)): ?>
                    <div class="modal fade" id="modalDeshacer<?= $v['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form class="modal-content" method="post" action="<?= site_url('piezas/version/' . $v['id'] . '/deshacer') ?>">
                                <?= csrf_field() ?>
                                <div class="modal-header">
                                    <h6 class="modal-title">Deshacer <?= $etiqueta($v) ?></h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small mb-2">
                                        Vuelve a <strong>borrador</strong>, como estaba recién promocionada. Esto es para
                                        arreglar un botón mal pulsado o un texto mal escrito, no para cambiar de opinión
                                        sobre una pieza que ya imprimiste.
                                    </p>
                                    <?php if ($v['estado'] === 'descartada'): ?>
                                        <div class="alert alert-warning py-2 small mb-0">
                                            Se pierde el motivo del descarte:
                                            <em><?= esc($v['resultado'] ?: '(sin motivo)') ?></em>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning py-2 small mb-0">
                                            Se pierden los parámetros de impresión<?= empty($v['params_impresion']) ? '' : ': <em>' . esc($v['params_impresion']) . '</em>' ?>.
                                            Vuelve a marcarla impresa con los buenos.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button class="btn btn-sm btn-warning">Volver a borrador</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

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
                                    una versión nueva y sube el STL ahí. Puedes adjuntar <strong>varios</strong>:
                                    los brazos por separado, o una pieza alta partida en trozos que luego se montan.
                                </p>
                                <label class="form-label small">Qué trozo es</label>
                                <input type="text" name="nombre" maxlength="150" class="form-control form-control-sm mb-2"
                                    placeholder="<?= empty($v['stls']) ? 'completo' : 'brazo izquierdo' ?>"
                                    <?= empty($v['stls']) ? '' : 'required' ?>>
                                <?php if (!empty($v['stls'])): ?>
                                    <div class="form-text small mb-2">
                                        Ya hay: <?= esc(implode(', ', array_column($v['stls'], 'nombre'))) ?>.
                                    </div>
                                <?php endif; ?>

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

        <!-- Estadísticas de esta pieza: tamaño en disco y un par de datos que solo importan aquí, no en el listado. -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <h6 class="mb-2"><i class="bi bi-hdd-stack"></i> Estadísticas</h6>
                <div class="row row-cols-2 row-cols-md-4 g-2 text-center">
                    <div class="col">
                        <div class="fs-6 fw-semibold"><?= $tamanoLegible($estadisticasPieza['peso']['total']) ?></div>
                        <div class="text-muted small">en disco</div>
                    </div>
                    <div class="col">
                        <div class="fs-6 fw-semibold"><?= (int) $estadisticasPieza['intentos'] ?></div>
                        <div class="text-muted small">intento(s) de impresión</div>
                    </div>
                    <div class="col">
                        <div class="fs-6 fw-semibold"><?= (int) $estadisticasPieza['sesiones'] ?></div>
                        <div class="text-muted small">sesión(es) de trabajo</div>
                    </div>
                    <div class="col">
                        <div class="fs-6 fw-semibold"><?= (int) $estadisticasPieza['dias_vida'] ?></div>
                        <div class="text-muted small">día(s) desde que se creó</div>
                    </div>
                </div>
                <?php if ($estadisticasPieza['peso']['sesiones'] > 0): ?>
                    <div class="small text-muted mt-2">
                        De eso, <?= $tamanoLegible($estadisticasPieza['peso']['sesiones']) ?> son sesiones de
                        trabajo (no las versiones en sí) — mira más abajo si alguna sigue sin purgar y
                        conviene <a href="#" onclick="event.preventDefault(); document.getElementById('sesiones-trabajo')?.scrollIntoView({behavior:'smooth'});">liberar sitio</a>.
                    </div>
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
                                        · v<?= sprintf('%03d', (int) $v['numero']) ?> (<?= esc($nombreEstado($v['estado'])) ?>)
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
    </div>

    <div class="col-lg-5">

        <!-- Trabajo en curso -->
        <div class="card shadow-sm mb-3" id="sesiones-trabajo">
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
                                                · <?= $tamanoLegible((int) $s['tamano_bytes']) ?>
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
                <pre class="small mb-0 user-select-all"><code>trackbitos bajar "<?= esc($refCli) ?>"

trackbitos estado
trackbitos subir
trackbitos cerrar</code></pre>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
