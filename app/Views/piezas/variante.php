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
 * Lista de sesiones de una rama — la actualmente abierta ("Trabajo en
 * curso") o una ya cerrada por una versión antigua (historial) — con sus
 * subidas, descarga de solo lectura y "liberar sitio" para las que están
 * cerradas y sin purgar todavía. Misma lista en los dos sitios: una sesión
 * de una versión vieja que nunca llegó a validarse (p. ej. descartada) no
 * se purga sola, y sin esto no había manera de verla ni de vaciarla.
 */
$bloqueSesiones = function (array $sesiones, int $varianteId, string $vacioTexto = 'Sin sesiones.') use ($tamanoLegible): string {
    if (empty($sesiones)) {
        return '<p class="text-muted small mb-0">' . esc($vacioTexto) . '</p>';
    }

    ob_start();
    ?>
    <ul class="list-unstyled small mb-0">
        <?php foreach ($sesiones as $s): ?>
            <li class="border-top py-1">
                <span class="fw-semibold">Sesión <?= (int) $s['numero'] ?></span>
                <span class="text-muted">· <?= esc($s['maquina'] ?? '?') ?></span>
                <?php if (empty($s['cerrada_en'])): ?>
                    <span class="badge text-bg-warning">abierta</span>
                <?php endif; ?>
                <?php if (!empty($s['purgada'])): ?>
                    <span class="badge text-bg-dark" title="Su fichero se apartó a la papelera a mano">purgada</span>
                <?php endif; ?>
                <?php if (!empty($s['subida_en'])): ?>
                    <div class="text-muted">
                        subida <?= esc($s['subida_en']) ?>
                        <?php if (!empty($s['tamano_bytes'])): ?>
                            · <?= $tamanoLegible((int) $s['tamano_bytes']) ?>
                        <?php endif; ?>
                        <?php if (empty($s['purgada']) && !empty($s['ruta_blend'])): ?>
                            <a href="<?= site_url('piezas/sesion/' . (int) $s['id'] . '/blend/descargar') ?>"
                                class="text-orange text-decoration-none"
                                title="Bajar el .blend de esta sesión (copia de solo lectura, para revisar que la subida llegó bien — no cuenta como descarga de trabajo)">
                                <i class="bi bi-download"></i></a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">sin subir</div>
                <?php endif; ?>
                <?php if (!empty($s['log'])): ?>
                    <div class="text-muted fst-italic"><?= esc($s['log']) ?></div>
                <?php endif; ?>

                <?php // Histórico de subidas (fase 41): cada subida dentro de esta sesión, no solo la última que sobrevive en $s['ruta_blend']. ?>
                <?php if (!empty($s['subidas']) && count($s['subidas']) > 1): ?>
                    <ul class="list-unstyled ms-3 mt-1 mb-0">
                        <?php foreach ($s['subidas'] as $sub): ?>
                            <li class="text-muted">
                                subida #<?= (int) $sub['numero'] ?> ·
                                <?= esc($sub['subida_en']) ?>
                                <?php if (!empty($sub['tamano_bytes'])): ?>
                                    · <?= $tamanoLegible((int) $sub['tamano_bytes']) ?>
                                <?php endif; ?>
                                <?php if (empty($sub['purgada'])): ?>
                                    <a href="<?= site_url('piezas/subida/' . (int) $sub['id'] . '/blend/descargar') ?>"
                                        class="text-orange text-decoration-none"
                                        title="Bajar el .blend de esta subida concreta (copia de solo lectura, para revisar — no cuenta como descarga de trabajo)">
                                        <i class="bi bi-download"></i></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php // Cerrada, sin promocionar todavía y sin purgar ya: candidata a liberar sitio a mano (p.ej. una subida de prueba demasiado pesada, o una sesión de una versión que nunca llegó a validarse). ?>
                <?php if (!empty($s['cerrada_en']) && empty($s['purgada']) && !empty($s['ruta_blend'])): ?>
                    <form method="post" class="mt-1"
                        action="<?= site_url('piezas/sesion/' . (int) $s['id'] . '/descartar-fichero') ?>"
                        onsubmit="return confirm('¿Apartar el .blend de la sesión <?= (int) $s['numero'] ?> a la papelera? Sigue en el historial, pero deja de ocupar sitio.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="variante_id" value="<?= $varianteId ?>">
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Apartar el fichero a la papelera y liberar sitio">
                            <i class="bi bi-trash"></i> liberar sitio
                        </button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return ob_get_clean();
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
 *
 * Paleta reducida a propósito (antes cada botón tenía su propio color —
 * cian, verde, rojo, amarillo, blanco, azul — y quedaba ruidoso): gris
 * neutro salvo en las dos acciones que de verdad son "bien"/"mal" — validar
 * y descartar —, que se quedan con su color para no perder esa distinción.
 * `rounded-pill` y más aire entre botones (gap-2 en el contenedor, en vez
 * de gap-1) para que la fila se vea menos apretada.
 */
/**
 * $icono: cuando se pasa, el botón es solo símbolo (sin texto visible) — el
 * texto pasa a title, como tooltip. Pensado para "Deshacer": entre Marcar
 * impresa/Validar/Descartar/Devolver/Derivar ya hay bastante texto en la
 * fila, y "deshacer" se lee de sobra con la flecha de retroceso sola.
 */
$boton = function (bool $activo, string $color, string $modal, string $texto, ?string $icono = null): string {
    return sprintf(
        '<button class="btn btn-sm btn-outline-%s rounded-pill %s" data-bs-toggle="modal" data-bs-target="#%s" title="%s"%s>%s</button>',
        $activo ? $color : 'secondary',
        $icono ? 'px-2' : 'px-3',
        $modal,
        esc($texto, 'attr'),
        $activo ? '' : ' disabled',
        $icono ? '<i class="bi bi-' . esc($icono, 'attr') . '"></i>' : esc($texto)
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
        'validada'   => 'Es la versión buena: ya no se marca ni se descarta. Para cambiarla de verdad, valida otra — "Deshacer" es solo para arreglar un error de anotación.',
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
    <?php // Entre familia y variante NO va "/": no hay pantalla intermedia (no
          // existe una ficha "solo familia"), así que una barra ahí sugiere una
          // navegación que no existe. Es un guion porque es una sola pieza, con
          // familia y variante como dos partes de su nombre. ?>
    <span class="text-muted fw-normal"><?= esc($familia['nombre']) ?></span>
    <span class="text-muted">-</span>
    <strong class="fw-semibold"><?= esc($variante['nombre']) ?></strong>
    <?php // Sin color de fondo: text-bg-light sobre el tema oscuro deja el código
          // casi ilegible (mismo arreglo que colSku() en piezas/index.php). ?>
    <?php if (!empty($variante['sku'])): ?>
        <span class="badge border text-body-secondary font-monospace fw-normal"><?= esc($variante['sku']) ?></span>
    <?php endif; ?>
    <form method="post" action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/visibilidad') ?>" class="d-inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm py-0 px-1 <?= empty($variante['visible_sterclicks']) ? 'btn-outline-secondary' : 'btn-outline-primary' ?>"
            title="<?= empty($variante['visible_sterclicks']) ? 'Mostrar en sterclicks' : 'Ocultar de sterclicks' ?>">
            <i class="bi <?= empty($variante['visible_sterclicks']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
            <?= empty($variante['visible_sterclicks']) ? 'oculta en sterclicks' : 'visible en sterclicks' ?>
        </button>
    </form>
    <?php if (!empty($variante['enlace_original'])): ?>
        <?php // El máster de máxima calidad vive fuera del tracker (Drive u otro sitio): esto es solo el enlace. ?>
        <a href="<?= esc($variante['enlace_original'], 'attr') ?>" target="_blank" rel="noopener"
            class="badge border text-body-secondary text-decoration-none" title="Abrir el original de máxima calidad">
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
 * Nombre de pieza, nombre de variante y enlace al original en el mismo
 * modal pero cada uno en su propio formulario: son verbos distintos y cada
 * uno puede negarse por su cuenta (p. ej. un nombre repetido). Con un solo
 * envío, el fallo de uno dejaría a los demás aplicados a medias. El SKU se
 * enseña aquí mismo pero ya no es un formulario: es autogenerado (fase 44).
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

                <label class="form-label small mb-1">Notas de la pieza</label>
                <p class="small text-muted mb-2">
                    Válidas para todas sus variantes (material, escala, uso previsto...).
                </p>
                <form method="post" class="d-flex gap-1 mb-3"
                    action="<?= site_url('piezas/familia/' . (int) $familia['id'] . '/notas') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="variante_id" value="<?= (int) $variante['id'] ?>">
                    <textarea name="notas" class="form-control form-control-sm" rows="2"><?= esc($familia['notas'] ?? '') ?></textarea>
                    <button class="btn btn-sm btn-primary align-self-start">Guardar</button>
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

                <label class="form-label small mb-1">Notas de la variante</label>
                <p class="small text-muted mb-2">
                    Propias de esta línea de diseño (qué la distingue de las demás variantes de la pieza).
                </p>
                <form method="post" class="d-flex gap-1 mb-3"
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/notas') ?>">
                    <?= csrf_field() ?>
                    <textarea name="notas" class="form-control form-control-sm" rows="2"><?= esc($variante['notas'] ?? '') ?></textarea>
                    <button class="btn btn-sm btn-primary align-self-start">Guardar</button>
                </form>

                <hr>

                <label class="form-label small mb-1">
                    <i class="bi bi-card-checklist"></i> Tareas y advertencia
                </label>
                <p class="small text-muted mb-2">
                    Lo que queda por hacerle a la pieza (una tarea por línea) y, si la pieza vale
                    pero tiene alguna pega, un aviso corto. Es lo mismo que se edita desde el
                    icono de tareas del índice.
                </p>
                <form method="post" action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/tareas') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="volver" value="ficha">
                    <input type="text" name="advertencia" class="form-control form-control-sm mb-1" maxlength="255"
                        value="<?= esc($variante['advertencia'] ?? '', 'attr') ?>"
                        placeholder="Advertencia (opcional)">
                    <textarea name="tareas" class="form-control form-control-sm mb-1" rows="4"
                        placeholder="Una tarea por línea"><?= esc($variante['tareas'] ?? '') ?></textarea>
                    <button class="btn btn-sm btn-primary">Guardar</button>
                </form>

                <hr>

                <label class="form-label small mb-1">SKU</label>
                <p class="small text-muted mb-2">
                    <code><?= esc($variante['sku'] ?? '—') ?></code> — asignado solo al crear la
                    variante, no se edita: es lo que la identifica de forma única para pedirla
                    desde fuera, y cambiarlo rompería esa referencia.
                </p>

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
                    <?php $imagenValidada = $validada['renders'][0] ?? null; ?>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($imagenValidada): ?>
                            <img src="<?= imagen_pieza($imagenValidada, 'render') ?>"
                                class="rounded border flex-shrink-0" style="width: 110px; height: 110px; object-fit: cover;"
                                alt="<?= esc($etiqueta($validada), 'attr') ?>">
                        <?php endif; ?>
                        <div>
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
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-muted">
                        <i class="bi bi-hourglass"></i>
                        Ninguna versión validada todavía: pendiente de imprimir y llegar a una versión válida.
                    </div>
                <?php endif; ?>

                <?php if ($origen): ?>
                    <div class="small text-muted mt-2">
                        Derivada de <?= esc($origen['variante']['nombre'] ?? '?') ?> ·
                        <?= $etiqueta($origen) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($familia['notas'])): ?>
                    <div class="small text-muted mt-2"><i class="bi bi-sticky"></i> <?= esc($familia['notas']) ?></div>
                <?php endif; ?>
                <?php if (!empty($variante['notas'])): ?>
                    <div class="small text-muted mt-2"><i class="bi bi-sticky"></i> <?= esc($variante['notas']) ?></div>
                <?php endif; ?>
                <?php if (!empty($variante['advertencia'])): ?>
                    <div class="small text-warning-emphasis mt-2">
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i> <?= esc($variante['advertencia']) ?>
                    </div>
                <?php endif; ?>
                <?php
                    $tareasPendientes = array_values(array_filter(array_map(
                        'trim',
                        preg_split('/\r\n|\r|\n/', (string) ($variante['tareas'] ?? ''))
                    ), static fn($t) => $t !== ''));
                ?>
                <?php if ($tareasPendientes !== []): ?>
                    <div class="small mt-2">
                        <div class="text-muted"><i class="bi bi-card-checklist"></i> Tareas pendientes</div>
                        <ul class="mb-0 ps-4">
                            <?php foreach ($tareasPendientes as $tarea): ?>
                                <li><?= esc($tarea) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
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
                <?php
                    /**
                     * Renders de las OTRAS versiones (más los sueltos): lo que
                     * se puede copiar a esta versión con "Reutilizar imagen"
                     * cuando no tiene ninguno propio. Se calcula una vez aquí
                     * y lo usan el botón y su modal, más abajo.
                     */
                    $rendersReutilizables = [];
                    foreach ($versiones as $otraVersion) {
                        if ((int) $otraVersion['id'] === (int) $v['id']) {
                            continue;
                        }
                        foreach ($otraVersion['renders'] as $rReutil) {
                            $rendersReutilizables[] = $rReutil + ['_origen' => $etiqueta($otraVersion)];
                        }
                    }
                    foreach ($rendersSueltos as $rReutil) {
                        $rendersReutilizables[] = $rReutil + ['_origen' => 'suelto'];
                    }
                ?>
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

                        <?php
                            /**
                             * Contador siempre visible, lista plegada por defecto: desplegar
                             * la lista entera en cada tarjeta del historial lo haría
                             * ilegible. Ninguna versión purga sus sesiones sola (validar y
                             * descartar ya no lo hacen: se guardan enteras, a propósito,
                             * hasta que se decida liberar el sitio a mano) — "Purgar
                             * sesiones" hace de una vez lo que si no habría que hacer
                             * sesión por sesión con "liberar sitio".
                             */
                        ?>
                        <?php if ($v['sesiones']['total'] > 0): ?>
                            <div class="small text-muted mt-1">
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                                    data-bs-toggle="collapse" data-bs-target="#sesiones-v<?= (int) $v['id'] ?>">
                                    <i class="bi bi-layers"></i>
                                    <?= (int) $v['sesiones']['total'] ?> sesión(es) de trabajo detrás
                                    <?php if ($v['sesiones']['purgadas'] > 0): ?>
                                        · <?= (int) $v['sesiones']['purgadas'] ?> con el .blend ya purgado
                                    <?php endif; ?>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div class="collapse mt-1" id="sesiones-v<?= (int) $v['id'] ?>">
                                <?= $bloqueSesiones($v['sesiones']['lista'], (int) $variante['id']) ?>
                                <?php if ($v['sesiones']['purgadas'] < $v['sesiones']['total']): ?>
                                    <form method="post" class="mt-1"
                                        action="<?= site_url('piezas/version/' . $v['id'] . '/purgar-sesiones') ?>"
                                        onsubmit="return confirm('¿Purgar todas las sesiones de <?= $etiqueta($v) ?>? Sus .blend se apartan a la papelera (30 días). El registro de cada sesión se conserva.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Aparta a la papelera el .blend de todas las sesiones sin purgar de esta versión">
                                            <i class="bi bi-trash"></i> Purgar sesiones
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Renders de esta versión: el resultado visual de esta iteración concreta -->
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <?php foreach ($v['renders'] as $r): ?>
                                <div class="position-relative" style="width: 56px;">
                                    <a href="<?= imagen_pieza($r, 'render', 'v') ?>" target="_blank"
                                        title="<?= esc($r['notas'] ?? '') ?>">
                                        <img src="<?= imagen_pieza($r, 'render') ?>"
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
                            <?php // Sin imagen propia pero hay otras versiones que sí tienen: copiar una aquí de un clic. ?>
                            <?php if (empty($v['renders']) && !empty($rendersReutilizables)): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                    data-bs-toggle="modal" data-bs-target="#modalReutilizarRender<?= $v['id'] ?>"
                                    title="Copiar aquí la imagen de otra versión">
                                    <i class="bi bi-copy"></i> Reutilizar imagen
                                </button>
                            <?php endif; ?>
                        </div>

                        <!--
                            .blend (el máster) en naranja, STL (lo que va a imprimir) en azul: dos
                            ficheros de naturaleza distinta, y colores distintos para no leerlos
                            como la misma cosa. Cada uno en su propia columna, no seguidos, para
                            que la separación se note también en el espacio y no solo en el color.
                            Texto en negro (btn-texto-negro) en vez del blanco por defecto de
                            Bootstrap: sobre naranja y azul claro se lee mejor en negro.
                            "Adjuntar STL" se queda en gris outline a propósito: es una acción
                            pendiente, no una descarga.
                        -->
                        <div class="row g-2 mt-2">
                            <div class="col-6 d-flex flex-wrap align-content-start gap-1">
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
                                <button type="button" class="btn btn-sm btn-orange btn-texto-negro py-0 px-2"
                                    data-bs-toggle="modal" data-bs-target="#modalDescargarBlend<?= $v['id'] ?>">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar .blend
                                    <?php if ($v['tamano_blend'] !== null): ?>
                                        <span class="opacity-75">(<?= $tamanoLegible($v['tamano_blend']) ?>)</span>
                                    <?php endif; ?>
                                </button>
                            </div>

                            <div class="col-6 d-flex flex-wrap align-content-start gap-1">
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
                                        <?php if (!empty($stl['ruta_stl'])): ?>
                                            <a href="<?= site_url('piezas/stl/' . (int) $stl['id'] . '/descargar') ?>"
                                                class="btn btn-sm btn-primary btn-texto-negro py-0 px-2">
                                                <i class="bi bi-file-earmark-arrow-down"></i>
                                                Descargar<?= esc($queTrozo) ?> .STL
                                                <?php if ($stl['tamano'] !== null): ?>
                                                    <span class="opacity-75">(<?= $tamanoLegible($stl['tamano']) ?>)</span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <?php // Trozo apuntado solo con medidas (fase 55): el .stl llega
                                                  // después. En gris y sin enlace — no hay nada que descargar. ?>
                                            <span class="btn btn-sm btn-outline-secondary py-0 px-2 disabled">
                                                <i class="bi bi-rulers"></i>
                                                <?= esc(trim($queTrozo) !== '' ? trim($queTrozo) : 'completo') ?> · sin .stl
                                            </span>
                                        <?php endif; ?>
                                        <form method="post" action="<?= site_url('piezas/stl/' . (int) $stl['id'] . '/quitar') ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 h-100"
                                                title="Quitar este STL (va a la papelera, 30 días)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <?php if (empty($stl['ruta_stl'])): ?>
                                        <?php // Añadirle el fichero cuando se genere, sin perder las medidas. ?>
                                        <form method="post" enctype="multipart/form-data"
                                            action="<?= site_url('piezas/stl/' . (int) $stl['id'] . '/fichero') ?>"
                                            class="d-inline-flex align-items-center gap-1">
                                            <?= csrf_field() ?>
                                            <input type="file" name="stl" accept=".stl" required
                                                class="form-control form-control-sm py-0 px-1" style="width: 12em;">
                                            <button class="btn btn-sm btn-outline-primary py-0 px-1" title="Añadir el .stl a este trozo">
                                                <i class="bi bi-file-earmark-arrow-up"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php // Cuánto ocupa en la placa (spec: reparto de piezas en placas),
                                          // en mm — la caja de ocupación que da Chitubox con la pieza ya
                                          // orientada como se va a imprimir. Vacío = "sin medir": esa
                                          // pieza se queda fuera del cálculo hasta que alguien la mida. ?>
                                    <form method="post" action="<?= site_url('piezas/stl/' . (int) $stl['id'] . '/medidas') ?>"
                                        class="d-flex flex-wrap align-items-center gap-1">
                                        <?= csrf_field() ?>
                                        <input type="text" inputmode="decimal" name="ancho"
                                            max="<?= \App\Services\PiezaEmpaquetadoService::PLACA_ANCHO_MM ?>"
                                            value="<?= esc($stl['ancho_mm'] !== null ? rtrim(rtrim($stl['ancho_mm'], '0'), '.') : '', 'attr') ?>"
                                            class="form-control form-control-sm py-0 px-1" style="width: 3.6em;"
                                            title="Ancho en mm (caja de ocupación de Chitubox)" placeholder="anc">
                                        <span class="text-muted small">×</span>
                                        <input type="text" inputmode="decimal" name="fondo"
                                            max="<?= \App\Services\PiezaEmpaquetadoService::PLACA_FONDO_MM ?>"
                                            value="<?= esc($stl['fondo_mm'] !== null ? rtrim(rtrim($stl['fondo_mm'], '0'), '.') : '', 'attr') ?>"
                                            class="form-control form-control-sm py-0 px-1" style="width: 3.6em;"
                                            title="Fondo en mm (caja de ocupación de Chitubox)" placeholder="fon">
                                        <span class="text-muted small">mm</span>
                                        <?php // Volumen y peso CON SOPORTES del laminador (Chitubox), para
                                              // el coste de resina — en su propia línea, que si no la fila
                                              // se sale del sitio. Vacío = "sin apuntar": ese trozo se queda
                                              // fuera del coste hasta que alguien lo mida. ?>
                                        <div class="w-100"></div>
                                        <input type="text" inputmode="decimal" name="volumen_soportes"
                                            value="<?= esc(($stl['volumen_soportes_ml'] ?? null) !== null ? rtrim(rtrim($stl['volumen_soportes_ml'], '0'), '.') : '', 'attr') ?>"
                                            class="form-control form-control-sm py-0 px-1" style="width: 4em;"
                                            title="Volumen con soportes en mL (Chitubox)" placeholder="vol">
                                        <span class="text-muted small">mL</span>
                                        <input type="text" inputmode="decimal" name="peso_soportes"
                                            value="<?= esc(($stl['peso_soportes_g'] ?? null) !== null ? rtrim(rtrim($stl['peso_soportes_g'], '0'), '.') : '', 'attr') ?>"
                                            class="form-control form-control-sm py-0 px-1" style="width: 4em;"
                                            title="Peso con soportes en g (Chitubox)" placeholder="peso">
                                        <span class="text-muted small">g</span>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Guardar medida">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                <?php endforeach; ?>

                                <?php // Resina de la versión: suma del volumen/peso con soportes de sus
                                      // trozos y, si hay precio puesto y están todos apuntados, el coste. ?>
                                <?php if (!empty($v['resina']['aplica'])): ?>
                                    <?php $r = $v['resina']; ?>
                                    <div class="w-100 small text-muted mt-1">
                                        <i class="bi bi-droplet"></i>
                                        <?php if ($r['completos'] === 0): ?>
                                            Sin volumen con soportes apuntado — apúntalo en cada trozo para el coste de resina.
                                        <?php else: ?>
                                            <?= esc(rtrim(rtrim(number_format($r['volumen_ml'], 2, ',', '.'), '0'), ',')) ?> mL
                                            · <?= esc(rtrim(rtrim(number_format($r['peso_g'], 2, ',', '.'), '0'), ',')) ?> g con soportes
                                            <?php if ($r['coste_eur'] !== null): ?>
                                                · <strong><?= esc(number_format($r['coste_eur'], 2, ',', '.')) ?> €</strong> de resina
                                            <?php elseif ($r['completos'] < $r['total']): ?>
                                                · faltan <?= (int) ($r['total'] - $r['completos']) ?> de <?= (int) $r['total'] ?> trozos por apuntar
                                            <?php else: ?>
                                                · pon el precio de la resina (menú «Resina» del índice) para ver el coste
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

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
                        </div>

                        <?php
                            /*
                             * Revisión de malla (fase 54): lo que se ve al abrir el STL en el
                             * laminador y hay que arreglar antes de imprimir — no manifold,
                             * normales invertidas, agujeros. Por versión, no por trozo: es el
                             * "¿lista para el laminador?" de un vistazo, y es lo que sale en el
                             * índice. El botón de "sin comprobar" solo aparece si hay algo que
                             * borrar — de partida ya está en ese estado.
                             */
                            $rm = $v['revision_malla'] ?? null;
                        ?>
                        <div class="d-flex align-items-center flex-wrap gap-1 mt-3 small">
                            <span class="text-muted me-1"><i class="bi bi-bounding-box-circles"></i> Malla:</span>
                            <form method="post" action="<?= site_url('piezas/version/' . (int) $v['id'] . '/revision-malla') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="estado" value="ok">
                                <button class="btn btn-sm <?= $rm === 'ok' ? 'btn-success' : 'btn-outline-secondary' ?> py-0 px-2"
                                    title="Revisada en el laminador: sin fallos">
                                    <i class="bi bi-check-circle-fill"></i> Limpia
                                </button>
                            </form>
                            <form method="post" action="<?= site_url('piezas/version/' . (int) $v['id'] . '/revision-malla') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="estado" value="fallos">
                                <button class="btn btn-sm <?= $rm === 'fallos' ? 'btn-danger' : 'btn-outline-secondary' ?> py-0 px-2"
                                    title="Tiene fallos por arreglar antes de imprimir (manifold, normales invertidas...)">
                                    <i class="bi bi-x-circle-fill"></i> Con fallos
                                </button>
                            </form>
                            <?php if ($rm !== null): ?>
                                <form method="post" action="<?= site_url('piezas/version/' . (int) $v['id'] . '/revision-malla') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="">
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" title="Volver a «sin comprobar»">
                                        <i class="bi bi-question-circle"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4 mb-3">
                            <!--
                                Botones siempre visibles, deshabilitados con explicación cuando
                                no aplican (spec 7.1): ocultarlos dejaría al usuario sin saber
                                qué le falta para poder.
                            -->
                            <?= $boton($v['estado'] === 'borrador', 'primary', 'modalImpresa' . $v['id'], 'Marcar impresa') ?>
                            <?= $boton($v['estado'] === 'impresa', 'success', 'modalValidar' . $v['id'], 'Validar') ?>
                            <?= $boton(in_array($v['estado'], ['borrador', 'impresa'], true), 'danger', 'modalDescartar' . $v['id'], 'Descartar') ?>
                            <?php // Solo aparece donde sirve de algo: un botón "Deshacer" apagado en
                                  // todas las tarjetas invitaría a leerlo como que se puede deshacer todo.
                                  // "validada" entra aquí igual que "descartada": es para arreglar un
                                  // error de anotación (botón mal pulsado, resultado mal escrito), no
                                  // para cambiar de opinión sobre una pieza que ya imprimiste. ?>
                            <?php if (in_array($v['estado'], ['impresa', 'descartada', 'validada'], true)): ?>
                                <?= $boton(true, 'primary', 'modalDeshacer' . $v['id'], 'Deshacer', 'arrow-counterclockwise') ?>
                            <?php endif; ?>
                            <?= $boton($puedeDevolver($v), 'primary', 'modalDevolver' . $v['id'], 'Devolver a trabajo') ?>
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

                <?php if (in_array($v['estado'], ['impresa', 'descartada', 'validada'], true)): ?>
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
                                    <?php if (in_array($v['estado'], ['descartada', 'validada'], true)): ?>
                                        <div class="alert alert-warning py-2 small mb-0">
                                            Se pierde <?= $v['estado'] === 'validada' ? 'el resultado de la validación' : 'el motivo del descarte' ?>:
                                            <em><?= esc($v['resultado'] ?: '(sin motivo)') ?></em>
                                        </div>
                                        <?php if ($v['estado'] === 'validada' && array_filter($versiones, fn($vv) => $vv['estado'] === 'superada')): ?>
                                            <div class="alert alert-warning py-2 small mt-2 mb-0">
                                                Esta pieza se queda <strong>sin ninguna versión validada</strong>: la que esta
                                                reemplazó (marcada "superada") no vuelve a validarse sola — eso no se deshace
                                                solo. Si esa era la buena, retómala o derívala y vuelve a validar desde ahí.
                                            </div>
                                        <?php endif; ?>
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
                            action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/render') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="version_id" value="<?= (int) $v['id'] ?>">
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
                                <div class="progress d-none mt-2" style="height: 18px;" data-progreso>
                                    <div class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button class="btn btn-sm btn-primary">Subir</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php // Reutilizar la imagen de otra versión: una miniatura por
                      // cada render de otra versión (o suelto); al pulsar, se
                      // copia a esta versión. Solo si esta no tiene imagen propia. ?>
                <?php if (empty($v['renders']) && !empty($rendersReutilizables)): ?>
                    <div class="modal fade" id="modalReutilizarRender<?= $v['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title">Reutilizar imagen en <?= $etiqueta($v) ?></h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small">
                                        Se copia a <?= $etiqueta($v) ?>; la original se queda donde está.
                                        Pulsa la que quieras usar.
                                    </p>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php foreach ($rendersReutilizables as $rReutil): ?>
                                            <form method="post" class="text-center m-0"
                                                action="<?= site_url('piezas/version/' . (int) $v['id'] . '/render/reutilizar') ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="render_id" value="<?= (int) $rReutil['id'] ?>">
                                                <button class="btn btn-link p-0 border-0" title="Usar esta imagen">
                                                    <img src="<?= imagen_pieza($rReutil, 'render') ?>"
                                                        class="rounded border" style="width: 84px; height: 84px; object-fit: cover;"
                                                        alt="Render" loading="lazy">
                                                </button>
                                                <div class="small text-muted"><?= esc($rReutil['_origen']) ?></div>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

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
                                    El fichero es <strong>opcional</strong>: apunta ahora las medidas del trozo para
                                    el reparto en placa y añádele el .stl cuando lo generes.
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

                                <label class="form-label small">Medidas en la placa (mm, opcional)</label>
                                <div class="d-flex align-items-center gap-1 mb-2">
                                    <input type="text" inputmode="decimal" name="ancho" placeholder="ancho"
                                        max="<?= \App\Services\PiezaEmpaquetadoService::PLACA_ANCHO_MM ?>"
                                        class="form-control form-control-sm" style="width: 6em;">
                                    <span class="text-muted small">×</span>
                                    <input type="text" inputmode="decimal" name="fondo" placeholder="fondo"
                                        max="<?= \App\Services\PiezaEmpaquetadoService::PLACA_FONDO_MM ?>"
                                        class="form-control form-control-sm" style="width: 6em;">
                                    <span class="text-muted small">mm — caja de ocupación de Chitubox</span>
                                </div>

                                <label class="form-label small">Fichero .stl (opcional)</label>
                                <input type="file" name="stl" accept=".stl" class="form-control form-control-sm">
                                <?php // Los STL suelen pesar bastante más que las fotos — sin esto no había
                                      // ninguna pista de que la subida estuviera avanzando. ?>
                                <div class="progress d-none mt-2" style="height: 18px;" data-progreso>
                                    <div class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
                                </div>
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

                    <?php // Solo cuando la pieza se ha modificado desde su promoción:
                          // la rama abierta ya tiene una sesión subida ($estado['ultima_subida']).
                          // Una rama recién abierta sin cambios aún no "mejora" nada.
                          // $versiones viene ordenado por numero DESC: [0] es la última. ?>
                    <?php if (!empty($versiones) && !empty($estado['ultima_subida'])): ?>
                        <?php $numEnCurso = (int) $versiones[0]['numero']; ?>
                        <div class="alert alert-warning py-1 px-2 small mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                            <span>Mejoras de versión
                                <strong>v<?= sprintf('%03d', $numEnCurso) ?></strong> a la
                                <strong>v<?= sprintf('%03d', $numEnCurso + 1) ?></strong></span>
                        </div>
                    <?php endif; ?>

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

                    <?= $bloqueSesiones($sesiones, (int) $variante['id'], 'Sin sesiones en esta rama todavía.') ?>
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
                <?php
                    // Con pautas configuradas, el botón no envía el formulario
                    // directo: abre el aviso con el checklist primero (spec:
                    // recordatorio antes de promocionar). Sin pautas, no hay
                    // nada que mostrar y se promociona como siempre.
                    $conPautas = $acciones['puede_promocionar'] && !empty($pautas);
                ?>
                <form method="post" id="formPromocionar"
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/promocionar') ?>">
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
                    <button type="<?= $conPautas ? 'button' : 'submit' ?>"
                        class="btn btn-sm w-100 <?= $acciones['puede_promocionar'] ? 'btn-primary' : 'btn-secondary' ?>"
                        <?= $conPautas ? 'data-bs-toggle="modal" data-bs-target="#modalPautasPromocion"' : '' ?>
                        <?= $acciones['puede_promocionar'] ? '' : 'disabled' ?>>
                        Promocionar
                    </button>
                </form>
            </div>
        </div>

        <?php if ($conPautas): ?>
            <!-- Aviso de pautas antes de promocionar: solo recordatorio, los
                 checks no se guardan ni afectan a la promoción. -->
            <div class="modal fade" id="modalPautasPromocion" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title"><i class="bi bi-award"></i> Antes de promocionar...</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">Repasa el checklist. Marcar es solo para ti, no cambia nada.</p>
                            <?php foreach ($pautas as $i => $pauta): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="pauta<?= $i ?>">
                                    <label class="form-check-label small" for="pauta<?= $i ?>"><?= esc($pauta) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" form="formPromocionar" class="btn btn-sm btn-primary">Promocionar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Referencias del original: de esta variante concreta -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h6 class="mb-0"><i class="bi bi-camera"></i> Referencias</h6>
                    <span class="text-muted small">de <?= esc($variante['nombre'] ?? 'esta variante') ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-auto"
                        data-bs-toggle="modal" data-bs-target="#modalReferencia">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <?php if (empty($referencias)): ?>
                    <p class="text-muted small mb-0">
                        Sin fotos de referencia todavía (medidas de calibre, ángulos del original).
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($referencias as $r): ?>
                            <div class="position-relative" style="width: 72px;">
                                <a href="<?= imagen_pieza($r, 'referencia', 'v') ?>" target="_blank"
                                    title="<?= esc($r['notas'] ?? '') ?>">
                                    <img src="<?= imagen_pieza($r, 'referencia') ?>"
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
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/referencia') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="volver_a_variante" value="<?= (int) $variante['id'] ?>">
                    <div class="modal-header">
                        <h6 class="modal-title">Referencia para <?= esc($variante['nombre'] ?? 'esta variante') ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Foto</label>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-2" required>
                        <label class="form-label small">Notas (medidas de calibre, qué muestra)</label>
                        <textarea name="notas" class="form-control form-control-sm" rows="2"
                            placeholder="Alto total 78mm con calibre, vista frontal"></textarea>
                        <div class="progress d-none mt-2" style="height: 18px;" data-progreso>
                            <div class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-sm btn-primary">Subir</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Renders sueltos: de esta variante pero sin versión concreta todavía
             (fase 31) -- útil sobre todo antes de la primera promoción, cuando
             no hay ninguna versión a la que colgar una foto de progreso. Los
             renders que sí son de una versión se ven en su historial, arriba. -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h6 class="mb-0"><i class="bi bi-image"></i> Renders</h6>
                    <span class="text-muted small">sin versión concreta</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-auto"
                        data-bs-toggle="modal" data-bs-target="#modalRenderSuelto">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <?php if (empty($rendersSueltos)): ?>
                    <p class="text-muted small mb-0">
                        Sin renders sueltos. Sirve para subir una foto de progreso del modelo
                        aunque todavía no hayas promocionado ninguna versión.
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($rendersSueltos as $r): ?>
                            <div class="position-relative" style="width: 72px;">
                                <a href="<?= imagen_pieza($r, 'render', 'v') ?>" target="_blank"
                                    title="<?= esc($r['notas'] ?? '') ?>">
                                    <img src="<?= imagen_pieza($r, 'render') ?>"
                                        class="rounded border" style="width: 72px; height: 72px; object-fit: cover;"
                                        alt="Render" loading="lazy">
                                </a>
                                <form method="post" action="<?= site_url('piezas/render/' . (int) $r['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Apartar este render a la papelera?');" class="position-absolute top-0 end-0">
                                    <?= csrf_field() ?>
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

        <!-- Alta de render suelto -->
        <div class="modal fade" id="modalRenderSuelto" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" enctype="multipart/form-data"
                    action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/render') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h6 class="modal-title">Render suelto de <?= esc($variante['nombre']) ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Imagen</label>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-2" required>
                        <label class="form-label small">Notas</label>
                        <textarea name="notas" class="form-control form-control-sm" rows="2"
                            placeholder="Vista frontal, viewport de Blender"></textarea>
                        <div class="progress d-none mt-2" style="height: 18px;" data-progreso>
                            <div class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-sm btn-primary">Subir</button>
                    </div>
                </form>
            </div>
        </div>

        <!--
            "Compuesta de" (spec 11.1 ampliado): qué otras piezas estaban en
            la escena de esta variante. Puramente informativo — no toca
            origen_version_id ni la sincronización. En su propia tarjeta,
            igual que el resto de bloques de esta columna.
        -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Compuesta de</h6>
                    <?php if (!empty($versionesParaComponer)): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-auto"
                            data-bs-toggle="modal" data-bs-target="#modalComponente" title="Añadir">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (empty($componentes)): ?>
                    <p class="text-muted small mb-0">
                        Nada anotado. Si esta pieza incluye otras en la misma escena — un ensamblaje, o
                        una que dejaste al lado para partir de ella — añádelas aquí.
                    </p>
                <?php else: ?>
                    <?php
                        // La versión VIGENTE de cada componente es la que cuenta —
                        // siempre la última de esa pieza (validada, o la de número
                        // más alto). La versión con la que se anotó queda como
                        // nota secundaria ("se añadió con vNNN"), solo si ya no
                        // coincide. Aviso si la propia vigente está descartada: la
                        // última de esa pieza no sirve.
                    ?>
                    <ul class="list-group list-group-flush mb-0">
                        <?php foreach ($componentes as $c): ?>
                            <?php $va = $c['variante']; $fa = $c['familia']; $vig = $c['vigente']; $orig = $c['version']; ?>
                            <li class="list-group-item px-0 py-1 d-flex align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <?php if ($vig && $va && $fa): ?>
                                        <a href="<?= site_url('piezas/variante/' . (int) $va['id']) ?>" class="text-decoration-none text-body">
                                            <?= esc($fa['nombre']) ?> / <?= esc($va['nombre']) ?> · v<?= sprintf('%03d', (int) $vig['numero']) ?>
                                        </a>
                                        <span class="text-muted small">(vigente)</span>
                                        <?php if ($vig['estado'] === 'descartada'): ?>
                                            <span class="badge text-bg-warning ms-1">la última se descartó</span>
                                        <?php endif; ?>
                                        <?php if ($orig && (int) $orig['id'] !== (int) $vig['id']): ?>
                                            <div class="small text-muted">se añadió con v<?= sprintf('%03d', (int) $orig['numero']) ?></div>
                                        <?php endif; ?>
                                    <?php elseif ($va && $fa): ?>
                                        <a href="<?= site_url('piezas/variante/' . (int) $va['id']) ?>" class="text-decoration-none text-body">
                                            <?= esc($fa['nombre']) ?> / <?= esc($va['nombre']) ?>
                                        </a>
                                        <span class="text-muted small">(sin versiones aún)</span>
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
            </div>
        </div>

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

        <!--
            En qué placas ha ido esta pieza (fase 39). Es el otro extremo del
            hilo de la bitácora: lo que se aprendió imprimiéndola se apunta en
            la placa, pero se necesita aquí — cuando uno está mirando la pieza
            y decide si vuelve a mandarla a la impresora.
        -->
        <?php if (!empty($placasDeLaPieza)): ?>
            <?php
                $veredictosPlaca = \App\Models\PiezaPlacaModel::VEREDICTOS;
                $coloresPlaca = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
                $coloresResultado = ['bien' => 'success', 'regular' => 'warning', 'mal' => 'danger'];
                $resultados = \App\Models\PiezaPlacaVersionImagenModel::RESULTADOS;
            ?>
            <?php
                /**
                 * Punto de anclaje para el enlace "Ver histórico de capturas"
                 * de la tabla de piezas de una placa (fase 44): la gestión de
                 * las fotos vive aquí, no allí, así que allí solo hay un
                 * enlace a este sitio.
                 */
            ?>
            <div class="card shadow-sm mb-3" id="capturas">
                <div class="card-body p-3">
                    <h6 class="mb-2"><i class="bi bi-journal-text"></i> Se imprimió en</h6>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($placasDeLaPieza as $entrada): ?>
                            <?php $p = $entrada['placa']; ?>
                            <li class="py-1 border-bottom border-secondary-subtle">
                                <div class="d-flex align-items-center gap-2">
                                    <a href="<?= site_url('piezas/placa/' . (int) $p['id'] . '/bitacora/editar') ?>"
                                        class="text-decoration-none text-body flex-grow-1 text-truncate"
                                        title="<?= esc($p['nombre'], 'attr') ?>">
                                        <?= esc($p['nombre']) ?>
                                        <span class="text-muted">
                                            ·
                                            <?php foreach ($entrada['versiones'] as $i => $v): ?>
                                                <?= $i ? ', ' : '' ?>v<?= sprintf('%03d', $v['numero']) ?><?= $v['cantidad'] > 1 ? ' ×' . $v['cantidad'] : '' ?>
                                            <?php endforeach; ?>
                                        </span>
                                    </a>
                                    <span class="text-muted flex-shrink-0" style="font-size: .75rem;">
                                        <?= esc(date('d/m/y', strtotime($p['impresa_en'] ?: $p['creado_en']))) ?>
                                    </span>
                                    <?php if ($p['veredicto'] && isset($veredictosPlaca[$p['veredicto']])): ?>
                                        <span class="badge text-bg-<?= $coloresPlaca[$p['veredicto']] ?? 'secondary' ?> flex-shrink-0"
                                            title="<?= esc($veredictosPlaca[$p['veredicto']], 'attr') ?>">
                                            <?= esc(mb_substr($veredictosPlaca[$p['veredicto']], 0, 1)) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-body-secondary text-body-secondary border flex-shrink-0"
                                            title="Sin juzgar todavía">·</span>
                                    <?php endif; ?>
                                    <?php // Capturas de esta impresión concreta: la mejor
                                          // posición, cómo estaba puesta, si salió bien. Se
                                          // gestionan aquí, en la ficha de la pieza (fase 44). ?>
                                    <?php // Un id por placa (no por fila): una plaqueta puede
                                          // llevar dos versiones de la misma pieza, y el botón
                                          // despliega las capturas de ambas a la vez, con un
                                          // selector de clase en vez de un único id de destino. ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="collapse" data-bs-target=".capturas-placa-<?= (int) $p['id'] ?>"
                                        title="Capturas de esta impresión">
                                        <i class="bi bi-camera"></i>
                                        <?php $totalImagenes = array_sum(array_map(static fn($v) => count($v['imagenes']), $entrada['versiones'])); ?>
                                        <?php if ($totalImagenes > 0): ?>
                                            <span class="badge rounded-pill bg-secondary position-absolute top-0 start-100 translate-middle"
                                                style="font-size: .55rem;"><?= $totalImagenes ?></span>
                                        <?php endif; ?>
                                    </button>
                                </div>

                                <?php foreach ($entrada['versiones'] as $v): ?>
                                    <div class="collapse mt-2 capturas-placa-<?= (int) $p['id'] ?>" id="capturas-<?= (int) $v['fila_id'] ?>">
                                        <div class="border rounded p-2">
                                            <?php if (count($entrada['versiones']) > 1): ?>
                                                <div class="text-muted mb-1" style="font-size: .72rem;">
                                                    v<?= sprintf('%03d', $v['numero']) ?>
                                                    <?php if ($v['notas']): ?> · <?= esc($v['notas']) ?><?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($v['imagenes'] !== []): ?>
                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    <?php foreach ($v['imagenes'] as $img): ?>
                                                        <div class="position-relative" style="width: 88px;">
                                                            <a href="<?= imagen_pieza($img, 'placa-version-imagen', 'v') ?>" target="_blank"
                                                                title="<?= esc($img['notas'] ?? '') ?>">
                                                                <img src="<?= imagen_pieza($img, 'placa-version-imagen') ?>"
                                                                    class="rounded border" style="width: 88px; height: 88px; object-fit: cover;"
                                                                    alt="Captura de la pieza en placa" loading="lazy">
                                                            </a>
                                                            <?php if (!empty($img['resultado'])): ?>
                                                                <span class="badge bg-<?= $coloresResultado[$img['resultado']] ?? 'secondary' ?> position-absolute bottom-0 start-0 m-1"
                                                                    style="font-size: .6rem;">
                                                                    <?= esc($resultados[$img['resultado']] ?? $img['resultado']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <form method="post" action="<?= site_url('piezas/placa-version-imagen/' . (int) $img['id'] . '/borrar') ?>"
                                                                onsubmit="return confirm('¿Apartar esta foto a la papelera?');" class="position-absolute top-0 end-0">
                                                                <?= csrf_field() ?>
                                                                <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .65rem;" title="Borrar">
                                                                    <i class="bi bi-x"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted small mb-2">
                                                    Sin capturas todavía (la mejor posición impresa, cómo estaba puesta, si salió bien).
                                                </p>
                                            <?php endif; ?>

                                            <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center"
                                                action="<?= site_url('piezas/placa-version/' . (int) $v['fila_id'] . '/imagen') ?>">
                                                <?= csrf_field() ?>
                                                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                                                    class="form-control form-control-sm" style="max-width: 190px;" required>
                                                <input type="text" name="notas" class="form-control form-control-sm" maxlength="150"
                                                    placeholder="Cómo estaba puesta" style="max-width: 190px;">
                                                <select name="resultado" class="form-select form-select-sm" style="max-width: 120px;">
                                                    <option value="">Sin juzgar</option>
                                                    <?php foreach ($resultados as $clave => $texto): ?>
                                                        <option value="<?= esc($clave, 'attr') ?>"><?= esc($texto) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-upload"></i> Subir</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <?php if (!empty($capturasDeLaPieza)): ?>
                <?php
                    // Agrupadas por resultado, no por placa: lo que se viene a
                    // buscar aquí es "¿cuál es la posición que funciona?", y
                    // eso se ve poniendo lo bueno y lo malo uno al lado del
                    // otro, no repasando placa por placa.
                    $porResultado = ['bien' => [], 'regular' => [], 'mal' => [], '' => []];
                    foreach ($capturasDeLaPieza as $img) {
                        $porResultado[$img['resultado'] ?? ''][] = $img;
                    }
                ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="mb-1"><i class="bi bi-images"></i> Capturas: qué posición funciona</h6>
                        <p class="text-muted small mb-2">
                            Todas las capturas de todas las placas juntas, para comparar de un vistazo
                            cuál es la óptima y cómo no volver a ponerla.
                        </p>
                        <div class="row g-2">
                            <?php foreach (['bien' => 'Bien', 'regular' => 'Regular', 'mal' => 'Mal', '' => 'Sin juzgar'] as $clave => $titulo): ?>
                                <?php if ($porResultado[$clave] === []) continue; ?>
                                <div class="col-md-3 col-6">
                                    <div class="small fw-semibold text-<?= $coloresResultado[$clave] ?? 'muted' ?> mb-1">
                                        <?= esc($titulo) ?> (<?= count($porResultado[$clave]) ?>)
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($porResultado[$clave] as $img): ?>
                                            <a href="<?= imagen_pieza($img, 'placa-version-imagen', 'v') ?>" target="_blank"
                                                title="<?= esc($img['notas'] ?? '') ?>">
                                                <img src="<?= imagen_pieza($img, 'placa-version-imagen') ?>"
                                                    class="rounded border" style="width: 64px; height: 64px; object-fit: cover;"
                                                    alt="Captura" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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

<script>
(function () {
    // ---- Progreso al subir ficheros (renders, referencias, STL) ----------
    // Un <form> normal no da ninguna pista de que la subida esté avanzando
    // — con un STL de varios MB, la única señal era el icono de carga de la
    // pestaña. Se manda por XMLHttpRequest (fetch no expone progreso de
    // subida) y se pinta en la barra que cada modal ya lleva en su cuerpo.
    // Al terminar se recarga la página entera: es la forma más simple de
    // que salga el aviso de éxito/error y la lista actualizada, igual que
    // haría el <form> normal tras el redirect del servidor.
    document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
        var progreso = form.querySelector('[data-progreso]');
        var barra = progreso ? progreso.querySelector('.progress-bar') : null;
        var boton = form.querySelector('.modal-footer button:not([data-bs-dismiss])');
        var textoOriginal = boton ? boton.textContent : '';

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var datos = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);

            if (progreso) progreso.classList.remove('d-none');
            if (boton) boton.disabled = true;
            form.querySelectorAll('input, textarea').forEach(function (campo) { campo.disabled = true; });

            xhr.upload.addEventListener('progress', function (ev) {
                if (!ev.lengthComputable) return;
                var pct = Math.round((ev.loaded / ev.total) * 100);
                if (barra) {
                    barra.style.width = pct + '%';
                    barra.textContent = pct + '%';
                }
                if (boton) boton.textContent = 'Subiendo… ' + pct + '%';
            });

            xhr.addEventListener('load', function () {
                // La barra llega al 100% en cuanto el navegador termina de
                // mandar los bytes, pero el servidor todavía tiene que
                // procesar (recomprimir la imagen, calcular hashes...) —
                // "Terminando…" evita que parezca colgado en ese hueco.
                if (barra) { barra.style.width = '100%'; barra.textContent = '100%'; }
                if (boton) boton.textContent = 'Terminando…';
                window.location.reload();
            });

            xhr.addEventListener('error', function () {
                if (boton) { boton.disabled = false; boton.textContent = textoOriginal; }
                form.querySelectorAll('input, textarea').forEach(function (campo) { campo.disabled = false; });
                if (progreso) progreso.classList.add('d-none');
                alert('Fallo de red al subir el fichero. Inténtalo otra vez.');
            });

            xhr.send(datos);
        });
    });

    // ---- Intro para aceptar el modal abierto ------------------------------
    // Bootstrap ya cierra con Escape; lo que falta es el simétrico: en los
    // modales de solo confirmar (validar, descartar, forzar cierre…) el foco
    // cae en el contenedor y no hay campo donde el Intro nativo dispare el
    // envío. Aquí, con un modal abierto, Intro pulsa su botón de acción —
    // salvo dentro de un textarea (donde es salto de línea; Ctrl/Cmd+Intro
    // sí lo fuerza) o si el modal tiene varios botones y no está claro cuál.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || e.isComposing || e.defaultPrevented || e.shiftKey || e.altKey) return;

        var modal = e.target.closest && e.target.closest('.modal.show');
        if (!modal) return;

        var t = e.target;
        if (t.tagName === 'TEXTAREA' && !(e.ctrlKey || e.metaKey)) return;
        if (t.tagName === 'BUTTON' || t.tagName === 'A' || t.tagName === 'SELECT') return;

        var candidatos = modal.querySelectorAll(
            '.modal-footer button:not([data-bs-dismiss]):not([type="button"]), .modal-footer a.btn:not([data-bs-dismiss])'
        );
        if (candidatos.length === 0) {
            candidatos = modal.querySelectorAll('form button:not([data-bs-dismiss]):not([type="button"])');
        }
        if (candidatos.length !== 1) return;

        var boton = candidatos[0];
        if (boton.disabled) return;
        e.preventDefault();
        boton.click();
    });
})();
</script>

<?= $this->endSection() ?>
