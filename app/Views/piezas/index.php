<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    /* Dos tonos alternos por pieza: una pieza y sus variantes comparten
       fondo, y la siguiente pieza cae en el tono alterno — el mismo fondo
       un punto más oscuro. Así se ve de un vistazo dónde acaba una y
       empieza otra. */
    #tablaPiezas tr.pieza-alt > td {
        background-color: rgba(0, 0, 0, .18);
    }

    /* Todas las filas de piezas a la misma altura y con más aire arriba y
       abajo que el que trae table-sm: `height` en una celda actúa como
       mínimo, así que la fila mide igual haya foto (34px) o no. Solo los
       <tbody> con id son los de piezas; la cabecera de categoría queda
       fuera y conserva su py-1. */
    #tablaPiezas > tbody[id] > tr > td {
        padding-top: .6rem;
        padding-bottom: .6rem;
        height: 2.9rem;
    }

    /* Modo enfoque: vista limpia de un vistazo. No guarda nada ni cambia
       datos — solo esconde las columnas de apoyo (ojo, SKU, y todo lo que
       hay entre la descarga de .blend/STL y las tareas: medidas, revisión
       de malla y aviso) y deja los badges de estado a color + icono, sin su
       texto (el <span class="et">). El título de cada badge sigue
       explicándolo al pasar el ratón. */
    #tablaPiezas.modo-focus .col-ojo,
    #tablaPiezas.modo-focus .col-sku,
    #tablaPiezas.modo-focus .col-medidas,
    #tablaPiezas.modo-focus .col-malla,
    #tablaPiezas.modo-focus .col-aviso {
        display: none;
    }
    #tablaPiezas.modo-focus .col-estado .et {
        display: none;
    }
    #tablaPiezas.modo-focus .col-estado .badge {
        padding-inline: .45em;
    }

    /* Vista en cuadrícula: tarjeta estrecha, foto arriba y debajo la tira de
       info escueta (misma que deja el modo Enfoque). El texto de los badges
       de estado (.et) se esconde siempre aquí — solo color + icono. */
    #galeriaPiezas .galeria-tarjeta {
        width: 9rem;
    }
    #galeriaPiezas .galeria-tarjeta .et {
        display: none;
    }
    #galeriaPiezas .galeria-tarjeta .badge {
        padding-inline: .45em;
    }

    /* En móvil el buscador se queda fijo abajo de la pantalla, siempre a
       mano para saltar a una pieza concreta sin volver arriba. En
       escritorio se queda donde estaba, sobre el listado. */
    @media (max-width: 767.98px) {
        #buscadorBarra {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1030;
            margin: 0 !important;
            padding: .6rem .75rem calc(.6rem + env(safe-area-inset-bottom));
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            box-shadow: 0 -.25rem .75rem rgba(0, 0, 0, .35);
        }
        /* Hueco para que la última fila / el pie no queden tapados. */
        body {
            padding-bottom: 4.5rem;
        }
    }
</style>

<?php
/**
 * Quién está trabajando en qué, ahora mismo — arriba del todo, antes que
 * cualquier otra cosa. Función compartida por el pintado inicial (PHP) y el
 * refresco parcial cada 20s (JS más abajo, mismo aspecto en los dos casos).
 */
$filaSesionActiva = static function (array $s): string {
    $dias = (int) $s['dias'];

    return '<div class="alert alert-info py-2 mb-2 d-flex align-items-center gap-2">'
        . '<i class="bi bi-circle-fill text-success" style="font-size:.5rem;"></i>'
        . '<div class="flex-grow-1">'
        . '<strong>' . esc($s['maquina']) . '</strong> está trabajando en '
        . '<a href="' . site_url('piezas/variante/' . (int) $s['variante_id']) . '" class="alert-link">'
        . esc($s['familia']) . ' / ' . esc($s['variante']) . '</a>'
        . ($dias > 0 ? ' <span class="text-muted small">(desde hace ' . $dias . ' día(s))</span>' : '')
        . '</div></div>';
};
?>

<div id="sesionesActivas">
    <?php foreach ($sesionesActivas as $s): ?>
        <?= $filaSesionActiva($s) ?>
    <?php endforeach; ?>
</div>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-box text-primary"></i>
    <?php // Sin "Dashboard" delante: galería y ficha empiezan por "Piezas", y tenerlo
          // en unas vistas sí y en otras no movía de sitio el mismo enlace. Para volver
          // al dashboard está el logo de la barra de arriba, que sí está en todas. ?>
    <strong class="fw-semibold">Piezas</strong>

    <?php
        /**
         * Backup, Pendientes, Placas, Pedidos, Galería y "+ Pieza" fuera,
         * sueltos — son los que se usan a diario. El resto (Organizar,
         * Categorías, Máquinas, Estadísticas, Papelera) es de uso ocasional
         * y va agrupado en el desplegable, en vez de sumar más botones
         * sueltos a la cabecera. "+ Variante" no va ni ahí: se quita —
         * crear una variante nace de una pieza concreta, así que su sitio
         * natural es la ficha, no un selector suelto de "elige la pieza"
         * aquí en el índice.
         *
         * Solo icono, sin texto: son muchos para llevar etiqueta cada uno
         * sin saturar la cabecera, y el título (tooltip) sigue diciendo qué
         * es cada uno al pasar el ratón. Los del desplegable si llevan
         * texto — ahí no hay problema de espacio.
         */
    ?>
    <div class="dropdown ms-auto">
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php if (!empty($familias)): ?>
                <?php // Colocar piezas es una tarea aparte de mirarlas: los selectores solo estorban el resto del tiempo. ?>
                <li>
                    <button type="button" class="dropdown-item" id="btnOrganizar">
                        <i class="bi bi-arrows-move"></i> Organizar
                    </button>
                </li>
            <?php endif; ?>
            <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCategorias">
                    <i class="bi bi-folder"></i> Categorías
                </button>
            </li>
            <li><a class="dropdown-item" href="<?= site_url('piezas/maquinas') ?>"><i class="bi bi-pc-display"></i> Máquinas</a></li>
            <li><a class="dropdown-item" href="<?= site_url('piezas/estadisticas') ?>"><i class="bi bi-hdd-stack"></i> Estadísticas</a></li>
            <?php // Checklist recordatorio que aparece antes de promocionar una variante. ?>
            <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalPautas">
                    <i class="bi bi-check2-square"></i> Pautas
                </button>
            </li>
            <?php // Solo aparece cuando hay algo dentro: no tiene sentido un enlace a una papelera vacía. ?>
            <?php if (!empty($papeleraCount)): ?>
                <li>
                    <a class="dropdown-item" href="<?= site_url('piezas/papelera') ?>">
                        <i class="bi bi-trash"></i> Papelera
                        <span class="badge text-bg-secondary"><?= (int) $papeleraCount ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php // Copia de seguridad: mismo destino que "Estadísticas > Backup" en el
          // desplegable, pero de un clic — es de las cosas que se hacen sin pensar,
          // no algo de uso ocasional que merezca estar escondido. ?>
    <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="btn btn-sm btn-outline-secondary"
        title="Copia de seguridad: el .blend de referencia de cada pieza más su historial en texto. Sin STL ni fotos.">
        <i class="bi bi-download"></i>
    </a>
    <div class="btn-group">
        <a href="<?= site_url('piezas/pendientes') ?>" class="btn btn-sm btn-outline-secondary" title="Pendientes">
            <i class="bi bi-list-check"></i>
        </a>
        <?php // Mismo anexo que en Pedidos: vistazo rápido sin entrar a la pantalla completa. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
            data-bs-target="#modalPendientes" title="Ver pendientes de crear">
            <i class="bi bi-eye"></i>
        </button>
    </div>
    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary" title="Placas">
        <i class="bi bi-printer"></i>
    </a>
    <div class="btn-group">
        <a href="<?= site_url('piezas/pedidos') ?>" class="btn btn-sm btn-outline-secondary" title="Pedidos">
            <i class="bi bi-cart-check"></i>
        </a>
        <?php // Vistazo rápido al último pedido sin entrar al tablero — mismo
              // hueco que ya usan otros botones "..." de detalle suelto. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
            data-bs-target="#modalUltimoPedido" title="Ver el último pedido entrante">
            <i class="bi bi-eye"></i>
        </button>
    </div>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary" title="Galería">
        <i class="bi bi-grid-3x3-gap"></i>
        <?php if (!empty($carritoCount)): ?>
            <span class="badge text-bg-primary"><?= (int) $carritoCount ?></span>
        <?php endif; ?>
    </a>
    <?php // Calculadora: cuánto tarda una placa a partir de su número de capas. ?>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCalcTiempo" title="Calcular tiempo estimado por capas">
        <i class="bi bi-stopwatch"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalFamilia" title="Pieza nueva">
        <i class="bi bi-plus-lg"></i>
    </button>
</h5>

<?php
/**
 * Vista simple del último pedido: solo qué se pide y con qué notas, sin
 * fotos ni botones de estado — para eso está la ficha completa
 * (piezas/pedido/N), a la que lleva "Ir al pedido". Aparte del tablero
 * (piezas/pedidos) porque este es un vistazo de "¿ha entrado algo nuevo?",
 * no un sitio para trabajar el pedido.
 */
?>
<div class="modal fade" id="modalUltimoPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-cart-check"></i> Último pedido entrante
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!$ultimoPedido): ?>
                    <p class="text-muted small mb-0">Todavía no ha entrado ningún pedido.</p>
                <?php else: ?>
                    <p class="text-muted small">
                        Pedido #<?= (int) $ultimoPedido['id'] ?> · <?= esc($ultimoPedido['origen']) ?> ·
                        <?= esc($ultimoPedido['creado_en']) ?>
                        <?php if ($ultimoPedido['referencia_externa']): ?>
                            · Ref: <?= esc($ultimoPedido['referencia_externa']) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($ultimoPedido['notas']): ?>
                        <p class="small"><i class="bi bi-sticky"></i> <?= esc($ultimoPedido['notas']) ?></p>
                    <?php endif; ?>

                    <?php if (empty($ultimoPedido['lineas'])): ?>
                        <p class="text-muted small mb-0">Sin líneas todavía.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($ultimoPedido['lineas'] as $linea): ?>
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span><?= esc($linea['descripcionPieza']) ?></span>
                                        <span class="text-muted small text-nowrap">× <?= (int) $linea['cantidad'] ?></span>
                                    </div>
                                    <?php if ($linea['notas']): ?>
                                        <div class="text-muted small mt-1"><i class="bi bi-sticky"></i> <?= esc($linea['notas']) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php if ($ultimoPedido): ?>
                    <a href="<?= site_url('piezas/pedido/' . (int) $ultimoPedido['id']) ?>" class="btn btn-sm btn-primary">
                        Ir al pedido
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Vista simple de pendientes: solo el título de cada subtarea sin marcar,
 * sin los verbos "Ya existe"/"Crear pieza" — para eso está la pantalla
 * completa (piezas/pendientes), a la que lleva "Ir a pendientes".
 */
?>
<div class="modal fade" id="modalPendientes" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-list-check"></i> Pendientes de crear
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($pendientesResumen)): ?>
                    <p class="text-muted small mb-0">No queda ninguna pendiente.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendientesResumen as $p): ?>
                            <li class="list-group-item px-0"><?= esc($p['title']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="<?= site_url('piezas/pendientes') ?>" class="btn btn-sm btn-primary">
                    Ir a pendientes
                </a>
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

<?php if (!empty($familias)): ?>
    <?php // En escritorio va aquí, sobre el listado. En móvil (CSS de arriba)
          // pasa a barra fija abajo de la pantalla, para saltar a una pieza
          // concreta sin subir a la cabecera. ?>
    <div id="buscadorBarra" class="mb-3">
        <input type="search" id="buscadorPiezas" class="form-control form-control-sm"
            placeholder="Buscar por nombre o SKU..." autocomplete="off">
    </div>
<?php endif; ?>

<?php if (empty($familias)): ?>
    <p class="text-muted">
        Todavía no hay ninguna pieza. Crea una (pincel, casco, silla...) y nacerá lista para
        trabajar: no hace falta decidir nada más. Si algún día esa pieza tiene varias líneas de
        diseño (una silla alta y otra baja), se añaden como <strong>variantes</strong>, cada una
        con su propia numeración de versiones.
    </p>
<?php endif; ?>

<?php
/**
 * Tabla, no tarjetas con badges que se envuelven: con quince piezas de
 * un vistazo hay que poder leer columna por columna (nombre, SKU, estado,
 * STL, aviso), no perseguir cada dato en un sitio distinto de cada fila.
 * Sin columna de "cuántas versiones": el número de la última ya sale en el
 * propio badge de estado (p. ej. "v005 ✓"), así que era un dato repetido.
 * Estos cuatro devuelven el HTML de una celda (nunca texto de usuario sin
 * `esc()`), para no repetir la misma condición en la fila de pieza única y
 * en cada subfila de variante.
 */
/**
 * Qué tiene la pieza terminado. Antes esto era validada-o-no, y todo lo
 * demás ("nunca se promocionó nada", "promocionada sin imprimir",
 * "impresa, pendiente de juzgar", "la última no sirve") se veía igual.
 */
$badgeMadurez = static function (array $v): string {
    if ($v['validada']) {
        $numValidada = (int) $v['validada']['numero'];
        $html = '<span class="badge text-bg-success"><i class="bi bi-check-circle-fill"></i> <span class="et">v'
            . sprintf('%03d', $numValidada) . '</span></span>';

        // Hay un borrador posterior a la validada, esperando prueba: se
        // anexa al lado ("v001 v003") para no tener que entrar a la ficha
        // a ver que la buena ya tiene relevo pendiente de imprimir.
        if ($v['ultima_version_estado'] === 'borrador'
            && ($v['ultima_version_numero'] ?? 0) > $numValidada) {
            $html .= ' <span class="badge text-bg-secondary" title="Hay una versión más nueva pendiente de imprimir">'
                . '<i class="bi bi-printer"></i> <span class="et">para imprimir v' . sprintf('%03d', (int) $v['ultima_version_numero']) . '</span></span>';
        }

        return $html;
    }
    if ($v['ultima_version_estado'] === 'impresa') {
        return '<span class="badge text-bg-primary" title="Impresa, pendiente de juzgar el resultado">'
            . '<i class="bi bi-printer-fill"></i> <span class="et">sin validar</span></span>';
    }
    if ($v['ultima_version_estado'] === 'descartada') {
        return '<span class="badge text-bg-danger" title="La última versión se descartó: no sirve">'
            . '<i class="bi bi-x-circle-fill"></i> <span class="et">no sirve</span></span>';
    }
    // Sin ninguna versión promocionada no hay "versión" de la que hablar:
    // el trabajo aún está en la sesión, no ha llegado al historial. Pero eso
    // son dos sitios muy distintos que antes se veían igual: la pieza que se
    // dio de alta y sigue siendo un hueco vacío, y la que ya tiene .blend
    // encima esperando a promocionarse. La primera no está "pendiente de"
    // nada tuyo en la web: está sin abrir Blender.
    if ($v['ultima_version_estado'] === null) {
        if (empty($v['trabajo_en_curso'])) {
            return '<span class="badge text-bg-dark border" title="La pieza está dada de alta, pero todavía no se ha subido ningún .blend: no hay nada dentro">'
                . '<i class="bi bi-circle"></i> <span class="et">sin empezar</span></span>';
        }

        return '<span class="badge text-bg-secondary" title="Todavía no se ha promocionado ninguna versión">'
            . '<i class="bi bi-dash-circle"></i> <span class="et">sin versión</span></span>';
    }
    // "Para imprimir", no "sin imprimir": lo mismo por fuera, pero uno nombra
    // una carencia y el otro lo siguiente que hay que hacer — que es lo que se
    // viene a mirar aquí. Además es la misma palabra que el filtro de arriba.
    // Sin el "versión" delante: en una columna que solo habla de versiones no
    // añadía nada y era la etiqueta más larga de todas.
    if ($v['ultima_version_estado'] === 'borrador') {
        return '<span class="badge text-bg-secondary" title="Promocionada, pendiente de imprimir de prueba">'
            . '<i class="bi bi-printer"></i> <span class="et">para imprimir</span></span>';
    }

    // Solo llega aquí una "superada" como última sin haber ninguna validada
    // (posible únicamente si se descartó la validada después). Raro, pero
    // "sin validar" sigue siendo cierto y no se inventa nada.
    return '<span class="badge text-bg-secondary" title="Sin validar">'
        . '<i class="bi bi-dash-circle"></i> <span class="et">sin validar</span></span>';
};

/**
 * Dos ejes distintos en la misma celda, y en este orden a propósito:
 * primero qué hay terminado, después si encima hay trabajo en marcha. Una
 * pieza puede estar validada Y modificándose a la vez — es el ciclo normal
 * — y si "modificando" sustituyera al otro badge se perdería de vista qué
 * versión buena hay justo en las piezas que se están tocando.
 *
 * Con borde en vez de color sólido: modificar es lo normal, no algo que
 * reclame atención. Los sólidos quedan para lo que sí la reclama.
 */
$colEstado = static function (array $v) use ($badgeMadurez): string {
    $html = $badgeMadurez($v);

    // Advertencia: la pieza vale (funciona y sirve) pero no es perfecta.
    // Triángulo amarillo translúcido pegado al número de versión; el porqué
    // va en el tooltip. Se pone y se quita desde el modal de tareas.
    $advertencia = trim((string) ($v['advertencia'] ?? ''));
    if ($advertencia !== '') {
        $html .= ' <i class="bi bi-exclamation-triangle-fill text-warning align-baseline" style="opacity: .6;"'
            . ' title="' . esc($advertencia, 'attr') . '"></i>';
    }

    if (!empty($v['trabajo_en_curso'])) {
        $html .= ' <span class="badge border text-body-secondary fw-normal"'
            . ' title="Hay trabajo en la rama abierta que todavía no se ha promocionado">'
            . '<i class="bi bi-pencil"></i> <span class="et">modificando</span></span>';
    }

    return $html;
};

/**
 * ¿Está lista para mandar a imprimir? Adjuntar el STL es un paso aparte de
 * promocionar, así que se olvida — y sin esto había que entrar pieza por
 * pieza a comprobarlo. Azul (mismo primary que el STL en la ficha): hay STL,
 * y el propio icono ES la descarga, no un icono aparte al lado — con un solo
 * STL baja directo; con varios trozos manda a la ficha a elegir cuál (o
 * bajarlos todos), en vez de intentar adivinar aquí cuál hace falta. Naranja
 * (igual que el .blend en la ficha): el máster, siempre descargable si hay
 * versión, tenga o no STL — sirve para coger piezas sueltas para montar en
 * otra escena, no solo para exportar.
 */
$colStl = static function (array $v): string {
    $stl = $v['stl'] ?? ['aplica' => false, 'trozos' => 0, 'version_id' => null, 'stl_id' => null];

    // Sin ninguna versión promocionada no falta el STL: falta la versión, y
    // tampoco hay .blend de esa versión que ofrecer.
    if (empty($stl['aplica'])) {
        return '';
    }

    $trozos = (int) $stl['trozos'];

    // El .blend va primero, no el STL: es siempre el mismo icono de ancho
    // fijo, así que la columna arranca en el mismo sitio en todas las
    // filas. El STL detrás cambia de ancho según el caso (icono suelto,
    // icono con número si hay varios trozos, o el badge más ancho de "sin
    // STL"), y puesto en segundo lugar ese vaivén ya no descuadra la
    // columna de un vistazo.
    $html = '';

    // El fichero llega con el sufijo "solo-lectura" en el nombre: esta
    // descarga no pasa por el cliente, así que nadie registra esa copia y lo
    // que se edite ahí no vuelve (spec 8).
    if (!empty($stl['version_id'])) {
        $html .= '<a href="' . site_url('piezas/version/' . (int) $stl['version_id'] . '/blend/descargar') . '"'
            . ' class="text-orange text-decoration-none"'
            . ' title="Bajar el .blend de esta versión (copia de solo lectura)">'
            . '<i class="bi bi-download"></i></a> ';
    }

    if ($trozos === 0) {
        $html .= '<span class="badge border border-warning text-warning-emphasis fw-normal"'
            . ' title="Esta versión no tiene STL: no se puede imprimir ni añadir a la placa">'
            . '<i class="bi bi-file-earmark-x"></i> sin STL</span>';
    } elseif ($trozos === 1 && !empty($stl['stl_id'])) {
        $html .= '<a href="' . site_url('piezas/stl/' . (int) $stl['stl_id'] . '/descargar') . '"'
            . ' class="text-primary text-decoration-none" title="Bajar el STL">'
            . '<i class="bi bi-file-earmark-check-fill"></i></a>';
    } elseif (!empty($stl['version_id'])) {
        // Varios trozos: se bajan todos juntos en un zip, sin pasar por la
        // ficha. El icono de zip avisa de que no es un STL suelto.
        $html .= '<a href="' . site_url('piezas/version/' . (int) $stl['version_id'] . '/stl/descargar') . '"'
            . ' class="text-primary text-decoration-none"'
            . ' title="Bajar los ' . $trozos . ' STL de esta pieza (se imprime en trozos), juntos en un zip">'
            . '<i class="bi bi-file-earmark-zip-fill"></i> <span class="small">' . $trozos . '</span></a>';
    } else {
        $html .= '<a href="' . site_url('piezas/variante/' . (int) $v['id']) . '"'
            . ' class="text-primary text-decoration-none"'
            . ' title="' . $trozos . ' STL adjuntos (se imprime en trozos) — bájalos desde la ficha">'
            . '<i class="bi bi-file-earmark-check-fill"></i> <span class="small">' . $trozos . '</span></a>';
    }

    return $html;
};

/**
 * Medidas de placa (fase 53) de la versión vigente: cuánto ocupa en la
 * plataforma, la caja de ocupación de Chitubox. Con varios trozos se suma
 * el área de todos — una pieza a trozos ocupa la suma de sus partes. Solo
 * cuenta como medida cuando TODOS los trozos lo están; si falta alguno, un
 * icono de regla atenuado (pendiente de medir): esa pieza no entra en el
 * cálculo de cuántas placas hacen falta hasta que se mida. Vacío del todo
 * cuando no hay ningún STL: no es que falte la medida, es que aún no hay
 * nada que medir.
 */
$colMedidas = static function (array $v): string {
    $m = $v['medidas_placa'] ?? ['aplica' => false];
    if (empty($m['aplica'])) {
        return '';
    }
    if (!empty($m['completas'])) {
        $cm2 = (float) $m['area_mm2'] / 100;
        $txt = $cm2 >= 10 ? number_format($cm2, 0) : number_format($cm2, 1);
        $detalle = (int) $m['total'] > 1 ? ' (suma de ' . (int) $m['total'] . ' trozos)' : '';

        return '<span class="badge border text-body-secondary fw-normal text-nowrap"'
            . ' title="Ocupa ' . $txt . ' cm² en la placa' . $detalle . '">' . $txt . ' cm²</span>';
    }

    return '<i class="bi bi-rulers text-body-tertiary"'
        . ' title="Sin medir (' . (int) $m['medidos'] . '/' . (int) $m['total']
        . ' trozos): no entra en el reparto de placas"></i>';
};

/**
 * Revisión de malla (fase 54) de la versión vigente — manifold, normales
 * invertidas, agujeros: lo que aparece al abrirla en el laminador. Cruz
 * roja = tiene fallos por arreglar antes de imprimir; check verde = ya
 * revisada y limpia; interrogación = nadie la ha mirado todavía. Vacío
 * cuando aún no hay ninguna versión.
 */
$colMalla = static function (array $v): string {
    if (empty($v['tiene_vigente'])) {
        return '';
    }
    switch ($v['revision_malla'] ?? null) {
        case 'fallos':
            return '<i class="bi bi-x-circle-fill text-danger"'
                . ' title="Malla con fallos por arreglar (manifold, normales invertidas...)"></i>';
        case 'ok':
            return '<i class="bi bi-check-circle-fill text-success" title="Malla revisada y limpia"></i>';
        default:
            return '<i class="bi bi-question-circle text-body-tertiary" title="Malla sin comprobar"></i>';
    }
};

$colAviso = static function (array $v): string {
    if ($v['bloqueo']) {
        return '<span class="badge text-bg-warning"><i class="bi bi-lock-fill"></i> '
            . esc($v['bloqueo']['maquina']) . '</span>';
    }
    if (!empty($v['pendientes'])) {
        return '<span class="badge text-bg-warning"><i class="bi bi-download"></i> '
            . count($v['pendientes']) . ' sin cerrar</span>';
    }

    return '';
};

$colSku = static function (array $v): string {
    // Sin color de fondo: text-bg-light sobre el tema oscuro deja el código casi ilegible.
    return empty($v['sku'])
        ? ''
        : '<span class="badge border text-body-secondary font-monospace fw-normal">' . esc($v['sku']) . '</span>';
};

/**
 * El ojo de visibilidad en sterclicks, ahora en su propia columna delante
 * de la foto (antes vivía suelto en la celda del nombre). Mismo formulario
 * AJAX de siempre — data-toggle-visibilidad, ver el script de abajo —, solo
 * que extraído a una función para pintarlo igual en la fila de la pieza y
 * en cada subfila de variante.
 */
$botonOjo = static function (string $accion, bool $visible): string {
    return '<form method="post" action="' . $accion . '" class="d-inline"'
        . ' data-toggle-visibilidad data-clase-oculta="text-muted" data-clase-visible="text-primary">'
        . csrf_field()
        . '<button type="submit" class="btn btn-sm py-0 px-1 border-0 ' . ($visible ? 'text-primary' : 'text-muted') . '"'
        . ' title="' . ($visible ? 'Ocultar de sterclicks' : 'Mostrar en sterclicks') . '">'
        . '<i class="bi ' . ($visible ? 'bi-eye' : 'bi-eye-slash') . '"></i>'
        . '</button></form>';
};

/**
 * El icono que abre el modal de tareas/advertencia de una pieza, en su
 * propia columna al final de la línea. Azul si tiene algo apuntado
 * —tareas o advertencia—, apagado si no; el número es cuántas tareas
 * (líneas no vacías) hay. Los data-* llevan lo que ya está escrito para
 * rellenar el modal sin ir al servidor. `$conVariante` añade el nombre de
 * la línea al título cuando la pieza tiene varias.
 */
$botonTareas = static function (array $v, array $familia, bool $conVariante): string {
    $lineas = array_filter(
        array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($v['tareas'] ?? ''))),
        static fn($l) => $l !== ''
    );
    $n         = count($lineas);
    $tieneAdv  = trim((string) ($v['advertencia'] ?? '')) !== '';
    $nombre    = $familia['nombre'] . ($conVariante ? ' / ' . $v['nombre'] : '');

    return '<button type="button" class="btn btn-sm py-0 px-1 border-0 '
        . ($n || $tieneAdv ? 'text-primary' : 'text-body-tertiary') . '"'
        . ' data-bs-toggle="modal" data-bs-target="#modalTareas"'
        . ' data-accion="' . site_url('piezas/variante/' . (int) $v['id'] . '/tareas') . '"'
        . ' data-nombre="' . esc($nombre, 'attr') . '"'
        . ' data-tareas="' . esc((string) ($v['tareas'] ?? ''), 'attr') . '"'
        . ' data-advertencia="' . esc((string) ($v['advertencia'] ?? ''), 'attr') . '"'
        . ' title="Tareas pendientes y advertencia de esta pieza">'
        . ($n ? '<span class="small">' . $n . '</span> ' : '') . '<i class="bi bi-card-checklist"></i>'
        . '</button>';
};

/**
 * La foto de la fila, o un hueco de la misma medida cuando la pieza aún no
 * tiene ninguna: así las filas no cambian de alto según haya foto o no, que
 * en una tabla de treinta líneas se nota más que la propia foto.
 *
 * `contain` y no `cover` como en la galería: ahí el recorte cuadra una
 * cuadrícula de tarjetas grandes, pero a este tamaño recortar una pieza por
 * los lados la deja irreconocible, que es justo lo contrario de para lo que
 * está puesta.
 */
$colFoto = static function (array $v, int $px = 48): string {
    if (empty($v['miniatura'])) {
        return '<span class="d-inline-flex align-items-center justify-content-center rounded border text-body-tertiary"'
            . ' style="width: ' . $px . 'px; height: ' . $px . 'px;">'
            . '<i class="bi bi-box" style="font-size: ' . ($px >= 96 ? '2rem' : '1rem') . ';"></i></span>';
    }

    return '<img src="' . esc($v['miniatura'], 'attr') . '" alt="" loading="lazy" class="rounded border"'
        . ' style="width: ' . $px . 'px; height: ' . $px . 'px; object-fit: contain;">';
};

/** Todo lo que debe encontrar el buscador de una pieza: su nombre y el de sus variantes con sus SKU. */
$textoBuscable = static function (array $familia): string {
    $partes = [$familia['nombre']];
    foreach ($familia['variantes'] as $v) {
        $partes[] = $v['nombre'];
        $partes[] = $v['sku'] ?? '';
    }

    return mb_strtolower(implode(' ', $partes));
};

/**
 * Los filtros son las preguntas que uno se hace de verdad mirando esto
 * ("¿qué me falta exportar?", "¿qué tengo pendiente de imprimir?"), no una
 * lista de los estados internos. Por eso cada uno mira a la vez la madurez y
 * el STL, y por eso "por imprimir" y "falta STL" son dos: la respuesta —
 * exportar o encender la impresora — es distinta.
 *
 * Cada entrada: etiqueta, icono, color (el mismo que su badge en la tabla,
 * para que el filtro se reconozca en la columna) y para qué sirve.
 */
$filtros = [
    'definitiva'  => ['Definitivas', 'bi-check-circle-fill', 'success', 'Tienen una versión validada: la buena'],
    // Marca de posición: aquí va el desplegable de abajo, no un chip suelto.
    '@imprimir'   => null,
    'falta-stl'   => ['Sin STL', 'bi-file-earmark-x', 'warning', 'Todo lo que no tiene STL adjunto, esté en el estado que esté — incluye las de «Imprimir · falta STL» y también una validada a la que se le olvidó'],
    'sin-validar' => ['Sin validar', 'bi-printer-fill', 'primary', 'Impresas y sin decir todavía si sirven'],
    'no-sirve'    => ['No sirven', 'bi-x-circle-fill', 'danger', 'La última versión se descartó'],
    'modificando' => ['Modificando', 'bi-pencil', 'secondary', 'Con trabajo encima todavía sin promocionar'],
    // Secondary y no dark: sobre el tema oscuro, btn-outline-dark pinta negro
    // sobre negro y del chip solo se veía flotando su contador.
    'sin-empezar' => ['Sin empezar', 'bi-circle', 'secondary', 'Dadas de alta y sin ningún .blend'],
    // No tienen foto de miniatura (ni render ni referencia): para localizarlas
    // y ponerles una. Mira lo mismo que la columna de foto de la izquierda.
    'sin-imagen'  => ['Sin imagen', 'bi-image', 'secondary', 'No tienen ninguna foto de miniatura todavía — para añadírsela'],
];

/**
 * "Pendiente de imprimir" en un desplegable en vez de dos chips sueltos:
 * unas veces la pregunta es "¿qué me queda por imprimir?" (da igual el STL) y
 * otras "¿cuál puedo mandar hoy?" o "¿cuál tengo que exportar?". Con dos
 * chips separados la primera pregunta no tenía respuesta, y con uno solo
 * faltaban las otras dos.
 */
$filtrosImprimir = [
    'imprimir'         => ['Todas', 'Pendientes de sacar la prueba, tengan STL o no'],
    'imprimir-con-stl' => ['Con STL', 'Con el STL ya puesto: se pueden mandar a la impresora hoy'],
    'imprimir-sin-stl' => ['Falta STL', 'Antes hay que exportar el STL desde Blender'],
];

/**
 * En qué cajones cae una variante. Varios a la vez a propósito: una pieza
 * validada que además se está retocando sale en "Definitivas" y en
 * "Modificando", que es justo lo que pasa.
 *
 * El STL se lee tal cual lo pinta $colStl (el de la versión vigente: la
 * última promocionada que no sea «superada»): filtrar por algo distinto de
 * lo que se ve en la columna daría resultados que parecen un error.
 */
$tokensDe = static function (array $v): array {
    $tokens = [];
    $estado = $v['ultima_version_estado'];
    $stl    = $v['stl'] ?? ['aplica' => false, 'trozos' => 0];
    $conStl = !empty($stl['aplica']) && (int) $stl['trozos'] > 0;

    if ($v['validada']) {
        $tokens[] = 'definitiva';
    }
    // Pendiente de imprimir se parte en dos porque lo siguiente que hay que
    // hacer es distinto: una va a la impresora, la otra hay que exportarla
    // antes. Verlas juntas obligaba a abrir pieza por pieza para saber cuál
    // de las dos cosas tocaba.
    if ($estado === 'borrador') {
        $tokens[] = 'imprimir';
        $tokens[] = $conStl ? 'imprimir-con-stl' : 'imprimir-sin-stl';
    }
    if ($estado === 'impresa') {
        $tokens[] = 'sin-validar';
    }
    if (!empty($stl['aplica']) && (int) $stl['trozos'] === 0) {
        $tokens[] = 'falta-stl';
    }
    if ($estado === 'descartada') {
        $tokens[] = 'no-sirve';
    }
    if (!empty($v['trabajo_en_curso'])) {
        $tokens[] = 'modificando';
    }
    if ($estado === null && empty($v['trabajo_en_curso'])) {
        $tokens[] = 'sin-empezar';
    }
    // Sin foto de miniatura: la misma condición que usa $colFoto para pintar
    // el hueco vacío en la columna de la izquierda.
    if (empty($v['miniatura'])) {
        $tokens[] = 'sin-imagen';
    }

    return $tokens;
};

/** Los de todas sus variantes juntos: la fila de la pieza representa a las de dentro. */
$tokensDeFamilia = static function (array $familia) use ($tokensDe): array {
    $tokens = [];
    foreach ($familia['variantes'] as $v) {
        $tokens = array_merge($tokens, $tokensDe($v));
    }

    return array_values(array_unique($tokens));
};

/**
 * Una tarjeta de la vista en cuadrícula: foto grande arriba, nombre debajo
 * y solo la información que sobrevive al modo Enfoque (estado a color +
 * icono, STL, aviso y tareas). Nada de ojo, SKU, medidas ni malla — para
 * eso está la tabla. Lleva data-buscar/data-tokens para que el buscador y
 * los filtros recorten también aquí (ver aplicarFiltros en el script).
 */
$tarjetaGaleria = static function (array $v, array $familia, bool $conVariante, string $buscar)
    use ($colFoto, $colEstado, $colStl, $colAviso, $botonTareas, $tokensDe): string {
    $nombre = esc($familia['nombre'] . ($conVariante ? ' · ' . $v['nombre'] : ''));
    $tira   = trim($colEstado($v) . ' ' . $colStl($v) . ' ' . $colAviso($v));

    return '<div class="galeria-tarjeta text-center" data-tarjeta'
        . ' data-buscar="' . $buscar . '" data-tokens="' . implode(' ', $tokensDe($v)) . '">'
        . '<a href="' . site_url('piezas/variante/' . (int) $v['id']) . '" class="d-block text-decoration-none text-body">'
        . $colFoto($v, 132)
        . '<div class="small fw-medium mt-1 text-truncate">' . $nombre . '</div>'
        . '</a>'
        . '<div class="d-flex flex-wrap justify-content-center align-items-center gap-1 mt-1">'
        . $tira . ' ' . $botonTareas($v, $familia, $conVariante)
        . '</div>'
        . '</div>';
};

// El número de cada chip: se cuentan piezas, no variantes, porque es lo que
// se ve en la tabla y lo que uno tiene en la cabeza ("me faltan 6 STL").
$cuentaFiltros = array_fill_keys(array_merge(array_keys($filtros), array_keys($filtrosImprimir)), 0);
$totalPiezas   = 0;
foreach ($grupos as $grupo) {
    foreach ($grupo['piezas'] as $familia) {
        $totalPiezas++;
        foreach ($tokensDeFamilia($familia) as $token) {
            $cuentaFiltros[$token]++;
        }
    }
}
?>

<?php if (!empty($familias)): ?>
    <?php // Plegados por defecto: son de consulta puntual ("¿qué me falta
          // exportar?"), no algo que se mira cada vez que se entra — mejor
          // pedirlos con un clic que tenerlos siempre a la vista. Se recuerda
          // si se han dejado abiertos (localStorage, ver el script de abajo). ?>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnFiltros"
        aria-controls="filtrosPiezas" aria-expanded="false">
        <i class="bi bi-funnel"></i> Filtros
    </button>
    <?php // Enfoque: limpia la vista (oculta ojo, SKU, medidas y malla; los
          // badges de estado quedan a color + icono). No cambia nada, solo lo
          // que se ve; se recuerda entre cargas como "Filtros" y "Organizar". ?>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnFocus"
        title="Vista limpia: solo lo esencial" aria-pressed="false">
        <i class="bi bi-bullseye"></i> Enfoque
    </button>
    <?php // Cuadrícula: las mismas piezas como tarjetas con la foto grande y
          // solo la info escueta del modo Enfoque. Se recuerda entre cargas. ?>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnGaleria"
        title="Ver como cuadrícula de fotos" aria-pressed="false">
        <i class="bi bi-grid"></i> Cuadrícula
    </button>

    <?php // Uno cada vez, no casillas: son preguntas distintas ("qué me falta
          // exportar", "qué hay para imprimir"), no facetas que se sumen. Con
          // el buscador sí se combinan (y = las dos cosas). Colores retirados
          // a propósito (antes cada chip tenía el suyo — verde, amarillo,
          // azul, rojo — y quedaba recargado): todos en gris neutro, el
          // icono ya dice de qué trata cada uno. ?>
    <div class="d-flex flex-wrap gap-1 mb-3 d-none" id="filtrosPiezas">
        <button type="button" class="btn btn-sm btn-outline-secondary active" data-filtro=""
            title="Quitar el filtro">
            Todas <span class="badge text-bg-secondary"><?= (int) $totalPiezas ?></span>
        </button>
        <?php foreach ($filtros as $token => $definicion): ?>
            <?php if ($token === '@imprimir'): ?>
                <?php $nImprimir = (int) $cuentaFiltros['imprimir']; ?>
                <?php // Botón partido: la mitad izquierda filtra "todas las que quedan por
                      // imprimir" de un clic — que es la pregunta más frecuente — y la flecha
                      // abre las dos de detalle. Así lo normal no cuesta dos gestos. ?>
                <div class="btn-group" data-grupo="imprimir">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro="imprimir"
                        title="<?= esc($filtrosImprimir['imprimir'][1], 'attr') ?>" <?= $nImprimir === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-printer"></i> <span data-etiqueta>Imprimir</span>
                        <span class="badge text-bg-secondary" data-cuenta><?= $nImprimir ?></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-expanded="false" <?= $nImprimir === 0 ? 'disabled' : '' ?>>
                        <span class="visually-hidden">Ver por STL</span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach ($filtrosImprimir as $sub => [$etiquetaSub, $ayudaSub]): ?>
                            <?php $nSub = (int) $cuentaFiltros[$sub]; ?>
                            <li>
                                <button type="button" class="dropdown-item small d-flex align-items-center gap-2"
                                    data-filtro="<?= $sub ?>" title="<?= esc($ayudaSub, 'attr') ?>"
                                    <?= $nSub === 0 ? 'disabled' : '' ?>>
                                    <span class="flex-grow-1"><?= esc($etiquetaSub) ?></span>
                                    <span class="badge text-bg-secondary"><?= $nSub ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <?php [$etiqueta, $icono, , $ayuda] = $definicion; ?>
                <?php $n = (int) $cuentaFiltros[$token]; ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-filtro="<?= $token ?>"
                    title="<?= esc($ayuda, 'attr') ?>" <?= $n === 0 ? 'disabled' : '' ?>>
                    <i class="bi <?= $icono ?>"></i> <?= esc($etiqueta) ?>
                    <span class="badge text-bg-secondary"><?= $n ?></span>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<table class="table table-sm align-middle mb-2" id="tablaPiezas">
    <?php foreach ($grupos as $indice => $grupo): ?>
        <?php $categoria = $grupo['categoria']; ?>
        <?php $idGrupo = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin'; ?>
        <tbody class="table-group-divider">
            <tr>
                <td colspan="11" class="py-1 bg-body-secondary">
                    <?php // Toda la línea pliega, no solo la flecha: es el objetivo grande y
                          // obvio, y acertar en un icono de 16px para algo que se hace a diario
                          // es un peaje sin motivo. El botón sigue existiendo para el teclado —
                          // su clic burbujea hasta aquí, así que la acción se ejecuta una vez.
                          //
                          // Fondo sólido (bg-body-secondary, mismo gris que la cabecera de
                          // categoría de la galería) para que se distinga de un vistazo de las
                          // filas de piezas que trae debajo. En el <td>, no en la <tr>: así no
                          // depende de cómo Bootstrap resuelva las variables de color de tabla. ?>
                    <div class="d-flex align-items-center gap-2 user-select-none" style="cursor: pointer"
                        data-plegar="<?= $idGrupo ?>">
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-body"
                            aria-controls="<?= $idGrupo ?>" aria-expanded="true">
                            <i class="bi bi-chevron-down" data-chevron></i>
                        </button>
                        <span class="fw-semibold text-uppercase small <?= $categoria ? '' : 'text-muted fst-italic' ?>">
                            <?= $categoria ? esc($categoria['nombre']) : 'Sin clasificar' ?>
                        </span>
                        <span class="badge border text-body-secondary" data-contador><?= count($grupo['piezas']) ?></span>

                        <?php if ($categoria): ?>
                            <?php // Siempre visible y siempre clicable: no depende del modo Organizar,
                                  // porque ocultar/mostrar una categoría es una acción suelta, no un
                                  // reordenamiento que se haga en tanda. ?>
                            <form method="post" action="<?= site_url('piezas/categoria/' . (int) $categoria['id'] . '/visibilidad') ?>"
                                data-toggle-visibilidad data-clase-oculta="btn-outline-secondary" data-clase-visible="btn-outline-primary" data-con-texto="1">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm py-0 px-1 <?= empty($categoria['visible_sterclicks']) ? 'btn-outline-secondary' : 'btn-outline-primary' ?>"
                                    title="<?= empty($categoria['visible_sterclicks']) ? 'Mostrar en sterclicks' : 'Ocultar de sterclicks' ?>">
                                    <i class="bi <?= empty($categoria['visible_sterclicks']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                    <span data-texto><?= empty($categoria['visible_sterclicks']) ? 'oculta' : 'visible' ?></span>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($categoria): ?>
                            <span class="zona-organizar d-none ms-auto d-flex gap-1">
                                <form method="post" action="<?= site_url('piezas/categoria/' . (int) $categoria['id'] . '/mover/subir') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Subir"
                                        <?= $indice === 0 ? 'disabled' : '' ?>><i class="bi bi-arrow-up"></i></button>
                                </form>
                                <form method="post" action="<?= site_url('piezas/categoria/' . (int) $categoria['id'] . '/mover/bajar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                                </form>
                            </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </tbody>

        <tbody id="<?= $idGrupo ?>">
            <?php if (empty($grupo['piezas'])): ?>
                <tr><td colspan="11" class="text-muted small ps-4">Vacía: mueve piezas aquí desde «Organizar».</td></tr>
            <?php endif; ?>

            <?php $filaAlterna = false; ?>
            <?php foreach ($grupo['piezas'] as $familia): ?>
                <?php $variantes = $familia['variantes']; $buscar = esc($textoBuscable($familia), 'attr'); ?>
                <?php $filaAlterna = !$filaAlterna; $claseAlterna = $filaAlterna ? '' : ' pieza-alt'; ?>
                <tr class="pieza<?= $claseAlterna ?>" data-pieza data-buscar="<?= $buscar ?>" data-tokens="<?= implode(' ', $tokensDeFamilia($familia)) ?>">
                    <?php // Misma regla que el resto de columnas: la fila de la pieza solo
                          // habla de una variante cuando hay una sola. Con varias, cada una
                          // trae su foto en su propia subfila. ?>
                    <td class="pe-0 col-ojo" style="width: 1%;"><?= $botonOjo(site_url('piezas/familia/' . (int) $familia['id'] . '/visibilidad'), !empty($familia['visible_sterclicks'])) ?></td>
                    <td style="width: 48px;"><?php
                        // Con una variante, su foto. Con varias, la pieza es un
                        // contenedor: el icono de las tres cajas (mismo que
                        // "compuesta de" otras piezas), sin marco y a la altura
                        // de la foto, en lugar de dejar el hueco vacío.
                        if (count($variantes) === 1) {
                            echo $colFoto($variantes[0]);
                        } elseif (count($variantes) > 1) {
                            echo '<span class="d-inline-flex align-items-center justify-content-center text-body-secondary"'
                                . ' style="width: 48px; height: 48px;" title="Pieza con ' . count($variantes) . ' variantes">'
                                . '<i class="bi bi-boxes" style="font-size: 1.9rem; line-height: 1;"></i></span>';
                        }
                    ?></td>
                    <td>
                        <?php if (count($variantes) === 1): ?>
                            <?php // Lo normal: una pieza es una sola cosa, así que la fila lleva directa a su ficha. ?>
                            <a href="<?= site_url('piezas/variante/' . (int) $variantes[0]['id']) ?>"
                                class="text-decoration-none text-body"><?= esc($familia['nombre']) ?></a>
                        <?php elseif (count($variantes) > 0): ?>
                            <?= esc($familia['nombre']) ?>
                            <span class="text-muted small">(<?= count($variantes) ?> variantes)</span>
                        <?php else: ?>
                            <?php // Sin ninguna línea de diseño viva: su única variante acabó en la
                                  // papelera. No hay ficha a la que enlazar — se manda a la papelera,
                                  // que es donde se puede recuperar (o borrar la pieza entera). ?>
                            <?= esc($familia['nombre']) ?>
                            <a href="<?= site_url('piezas/papelera') ?>" class="text-danger small text-decoration-none">
                                (sin variantes — recuperar en la papelera)
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="col-sku"><?= count($variantes) === 1 ? $colSku($variantes[0]) : '' ?></td>
                    <td class="col-estado"><?= count($variantes) === 1 ? $colEstado($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colStl($variantes[0]) : '' ?></td>
                    <td class="col-medidas"><?= count($variantes) === 1 ? $colMedidas($variantes[0]) : '' ?></td>
                    <td class="text-center col-malla"><?= count($variantes) === 1 ? $colMalla($variantes[0]) : '' ?></td>
                    <td class="col-aviso"><?= count($variantes) === 1 ? $colAviso($variantes[0]) : '' ?></td>
                    <?php // Tareas/advertencia, al final de la línea: solo en la fila de la
                          // pieza cuando tiene una única variante; con varias van en cada subfila. ?>
                    <td class="text-end"><?= count($variantes) === 1 ? $botonTareas($variantes[0], $familia, false) : '' ?></td>
                    <td class="zona-organizar d-none">
                        <div class="d-flex gap-1 justify-content-end">
                            <?php // Cambiar de categoría: un select que se envía solo, oculto salvo en modo Organizar. ?>
                            <form method="post" action="<?= site_url('piezas/familia/' . (int) $familia['id'] . '/categoria') ?>">
                                <?= csrf_field() ?>
                                <select name="categoria_id" class="form-select form-select-sm py-0"
                                    style="width: auto; font-size: .75rem;" onchange="this.form.submit()">
                                    <option value="" <?= empty($familia['categoria_id']) ? 'selected' : '' ?>>— sin clasificar —</option>
                                    <?php foreach ($categorias as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>"
                                            <?= (int) ($familia['categoria_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                                            <?= esc($c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <?php // Borrar (a papelera): mismo modo Organizar, para no ensuciar la fila el resto del tiempo. ?>
                            <form method="post" action="<?= site_url('piezas/familia/' . (int) $familia['id'] . '/borrar') ?>"
                                onsubmit="return confirm('¿Mandar «<?= esc($familia['nombre'], 'attr') ?>» a la papelera? Se puede restaurar durante 30 días.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Borrar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <?php if (count($variantes) > 1): ?>
                    <?php // Solo cuando hay más de una: con una sola, la fila de arriba ya lo dice todo. ?>
                    <?php foreach ($variantes as $v): ?>
                        <tr class="pieza<?= $claseAlterna ?>" data-subpieza data-buscar="<?= $buscar ?>" data-tokens="<?= implode(' ', $tokensDe($v)) ?>">
                            <td class="pe-0 col-ojo" style="width: 1%;"><?= $botonOjo(site_url('piezas/variante/' . (int) $v['id'] . '/visibilidad'), !empty($v['visible_sterclicks'])) ?></td>
                            <td style="width: 48px;"><?= $colFoto($v) ?></td>
                            <td class="ps-4">
                                <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>"
                                    class="text-decoration-none text-body">– <?= esc($v['nombre']) ?></a>
                            </td>
                            <td class="col-sku"><?= $colSku($v) ?></td>
                            <td class="col-estado"><?= $colEstado($v) ?></td>
                            <td><?= $colStl($v) ?></td>
                            <td class="col-medidas"><?= $colMedidas($v) ?></td>
                            <td class="text-center col-malla"><?= $colMalla($v) ?></td>
                            <td class="col-aviso"><?= $colAviso($v) ?></td>
                            <td class="text-end"><?= $botonTareas($v, $familia, true) ?></td>
                            <td class="zona-organizar d-none">
                                <?php // Borra solo esta variante (invariante 6, ahora también suelta): el resto de la pieza sigue intacta. ?>
                                <form method="post" class="text-end" action="<?= site_url('piezas/variante/' . (int) $v['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Mandar «<?= esc($familia['nombre'] . ' / ' . $v['nombre'], 'attr') ?>» a la papelera? Se puede restaurar durante 30 días.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Borrar variante">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    <?php endforeach; ?>
</table>

<?php
/**
 * Vista en cuadrícula: los mismos grupos y piezas que la tabla, en tarjetas.
 * Oculta por defecto (d-none); el botón "Cuadrícula" la intercambia con la
 * tabla y lo recuerda. Una tarjeta por variante — con varias, el nombre las
 * distingue. Las categorías pliegan igual que en la tabla y comparten el
 * mismo estado guardado (CERRADAS), así que plegar una vale para las dos
 * vistas.
 */
?>
<div id="galeriaPiezas" class="d-none">
    <?php foreach ($grupos as $grupo): ?>
        <?php
            $categoria = $grupo['categoria'];
            $idGrupo   = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin';
            $nTarjetas = array_sum(array_map(static fn($f) => count($f['variantes']), $grupo['piezas']));
        ?>
        <div data-galgrupo class="mb-3">
            <?php // Toda la línea pliega; el botón existe para el teclado (su
                  // clic burbujea hasta aquí). mt-4: separa el título del grupo
                  // anterior. ?>
            <div class="d-flex align-items-center gap-2 user-select-none border-bottom pb-1 mb-3 mt-4"
                style="cursor: pointer" data-galplegar="<?= $idGrupo ?>">
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-body">
                    <i class="bi bi-chevron-down" data-galchevron></i>
                </button>
                <span class="fw-semibold text-uppercase small <?= $categoria ? 'text-body-secondary' : 'text-muted fst-italic' ?>">
                    <?= $categoria ? esc($categoria['nombre']) : 'Sin clasificar' ?>
                </span>
                <span class="badge border text-body-secondary fw-normal" data-galcontador><?= (int) $nTarjetas ?></span>
            </div>
            <div data-galcuerpo="<?= $idGrupo ?>">
                <?php if ($nTarjetas === 0): ?>
                    <p class="text-muted small">Vacía.</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($grupo['piezas'] as $familia): ?>
                            <?php $buscar = esc($textoBuscable($familia), 'attr'); ?>
                            <?php $conVariante = count($familia['variantes']) > 1; ?>
                            <?php foreach ($familia['variantes'] as $v): ?>
                                <?= $tarjetaGaleria($v, $familia, $conVariante, $buscar) ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<p class="text-muted small d-none" id="sinResultados">Ninguna pieza coincide.</p>

<!-- Gestión de categorías: las carpetas en las que se reparten las piezas -->
<div class="modal fade" id="modalCategorias" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Categorías</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Las carpetas en las que repartir las piezas: cuerpo, accesorios, dioramas...
                    La idea es reproducir la organización que ya tienes en disco. Borrar una
                    categoría no borra sus piezas: se quedan sin clasificar, al final del listado.
                </p>

                <?php foreach ($categorias as $c): ?>
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <form method="post" class="d-flex gap-1 flex-grow-1"
                            action="<?= site_url('piezas/categoria/' . (int) $c['id'] . '/renombrar') ?>">
                            <?= csrf_field() ?>
                            <input type="text" name="nombre" value="<?= esc($c['nombre'], 'attr') ?>"
                                class="form-control form-control-sm" maxlength="100" required>
                            <button class="btn btn-sm btn-outline-primary" title="Renombrar"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <form method="post" action="<?= site_url('piezas/categoria/' . (int) $c['id'] . '/borrar') ?>"
                            onsubmit="return confirm('¿Borrar la categoría «<?= esc($c['nombre'], 'attr') ?>»? Sus piezas quedan sin clasificar.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($categorias)): ?>
                    <p class="text-muted small fst-italic">Todavía no hay ninguna.</p>
                <?php endif; ?>

                <hr>
                <form method="post" action="<?= site_url('piezas/categoria') ?>" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" class="form-control form-control-sm"
                        placeholder="Categoría nueva (cuerpo, accesorios...)" maxlength="100" required>
                    <button class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i></button>
                </form>
                <p class="text-muted small mt-2 mb-0">
                    Para cambiar el orden en que aparecen, usa las flechas del botón «Organizar».
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Pautas de promoción: checklist recordatorio que aparece antes de promocionar una variante -->
<div class="modal fade" id="modalPautas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/pautas') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pautas de promoción</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Una por línea. Aparecerán como checklist antes de promocionar una variante —
                    solo de recordatorio, marcarlas no cambia nada en la pieza.
                </p>
                <textarea name="texto" class="form-control form-control-sm" rows="6"
                    placeholder="Has metido los objetos importantes en una colección exclusiva&#10;Has cambiado los nombres de los objetos y shapekeys a mayúsculas"><?= esc($pautasTexto) ?></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * Calculadora de tiempo estimado de una placa a partir de su número de
 * capas. El minuto/capa no se guarda como constante suelta: sale de una
 * medición real (capas de referencia ÷ minutos de referencia), así que
 * recalibrar es reeditar esa medición. A eso se le suman siempre unos
 * minutos fijos de preparación. Todo el cálculo es en el navegador y en
 * vivo; el formulario de abajo solo persiste los tres ajustes.
 */
$calcPorCapa = $calcTiempo['minutosPorCapa'];
?>
<div class="modal fade" id="modalCalcTiempo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content"
            data-capas-ref="<?= esc($calcTiempo['capasReferencia'], 'attr') ?>"
            data-minutos-ref="<?= esc($calcTiempo['minutosReferencia'], 'attr') ?>"
            data-minutos-prep="<?= esc($calcTiempo['minutosPreparacion'], 'attr') ?>">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-stopwatch"></i> Tiempo estimado por capas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small" for="calcCapas">Número de capas de la placa</label>
                <input type="number" min="0" step="1" id="calcCapas" class="form-control form-control-sm"
                    placeholder="p. ej. 600" autocomplete="off" inputmode="numeric">
                <?php // Altura de la placa: 0,5 mm por capa (altura de capa fija del laminador). ?>
                <p class="text-muted small mb-3 mt-1" id="calcAltura"></p>

                <p id="calcVacio" class="text-muted small mb-0">
                    Escribe el número de capas para ver el tiempo estimado.
                </p>

                <table id="calcResultado" class="table table-sm align-middle mb-0 d-none">
                    <tbody>
                        <tr>
                            <td class="text-muted">Impresión</td>
                            <td class="text-end">
                                <span class="fw-semibold" id="calcImpresionBonito">—</span><br>
                                <span class="text-muted small" id="calcImpresionMin"></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Preparación</td>
                            <td class="text-end">
                                <span class="fw-semibold" id="calcPrepBonito">—</span><br>
                                <span class="text-muted small" id="calcPrepMin"></span>
                            </td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-semibold">Total</td>
                            <td class="text-end">
                                <span class="fw-bold fs-6" id="calcTotalBonito">—</span><br>
                                <span class="text-muted small" id="calcTotalMin"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <hr>

                <?php // Los tres números de calibración están plegados: se
                      // miran/tocan de uvas a peras, el resto del tiempo solo
                      // estorban al cálculo. ?>
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none collapsed" id="calcAjustesToggle"
                    data-bs-toggle="collapse" data-bs-target="#calcAjustes" aria-expanded="false" aria-controls="calcAjustes">
                    <i class="bi bi-chevron-right"></i> Ajustes
                    <span class="text-muted">(<span id="calcRefTexto"><?= (int) $calcTiempo['capasReferencia'] ?> capas =
                    <?= rtrim(rtrim(number_format($calcTiempo['minutosReferencia'], 2, ',', '.'), '0'), ',') ?> min</span>
                    &rarr; <span id="calcPorCapaTexto"><?= number_format($calcPorCapa, 4, ',', '.') ?></span> min/capa)</span>
                </button>

                <form method="post" action="<?= site_url('piezas/calculadora-tiempo') ?>" class="collapse mt-2" id="calcAjustes">
                    <?= csrf_field() ?>
                    <p class="text-muted small mb-2">
                        La referencia fija cuánto tarda cada capa; a partir de ahí se estima la impresión.
                        Los minutos de preparación se suman siempre.
                    </p>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label small" for="cfgCapasRef">Capas de referencia</label>
                            <input type="number" min="1" step="1" name="capas_referencia" id="cfgCapasRef"
                                class="form-control form-control-sm" value="<?= (int) $calcTiempo['capasReferencia'] ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label small" for="cfgMinRef">Minutos de referencia</label>
                            <input type="number" min="0" step="0.01" name="minutos_referencia" id="cfgMinRef"
                                class="form-control form-control-sm" value="<?= rtrim(rtrim(number_format($calcTiempo['minutosReferencia'], 2, '.', ''), '0'), '.') ?>" required>
                        </div>
                    </div>
                    <label class="form-label small mt-2" for="cfgMinPrep">Minutos de preparación (fijos)</label>
                    <input type="number" min="0" step="0.01" name="minutos_preparacion" id="cfgMinPrep"
                        class="form-control form-control-sm" value="<?= rtrim(rtrim(number_format($calcTiempo['minutosPreparacion'], 2, '.', ''), '0'), '.') ?>" required>
                    <div class="text-end mt-3">
                        <button class="btn btn-sm btn-primary">Guardar ajustes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alta de pieza (en el esquema, "familia": ver la nota de vocabulario en SPEC.md) -->
<div class="modal fade" id="modalFamilia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/familia') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pieza nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="pincel, casco, silla..." maxlength="150" required>
                <label class="form-label small">Categoría</label>
                <select name="categoria_id" class="form-select form-select-sm mb-2">
                    <option value="">— sin clasificar —</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= esc($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace lista para trabajar, con numeración propia desde v001 y su SKU asignado
                    solo. Solo hace falta añadir variantes si esta pieza acaba teniendo varias
                    líneas de diseño.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-success">Crear</button>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * Tareas y advertencia de una pieza. Compartido por todas las filas: el
 * botón que lo abre (ver $botonTareas) trae en sus data-* la acción del
 * formulario y lo que ya está escrito, y el JS rellena el modal en
 * `show.bs.modal`. Al guardar se recarga el índice (redirige a "piezas").
 */
?>
<div class="modal fade" id="modalTareas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="formTareas">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-card-checklist"></i> Tareas — <span data-nombre-tareas class="fw-normal text-muted"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small mb-1" for="campoAdvertencia">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="opacity: .6;"></i> Advertencia
                </label>
                <input type="text" id="campoAdvertencia" name="advertencia" maxlength="255"
                    class="form-control form-control-sm" data-advertencia-tareas
                    placeholder="Funciona y sirve, pero no es perfecta: qué falla">
                <p class="text-muted small mt-1">
                    Sale como un triángulo amarillo junto al número de versión en el índice.
                    Déjalo en blanco si no hay nada que advertir.
                </p>

                <label class="form-label small mb-1" for="campoTareas">Tareas pendientes</label>
                <textarea id="campoTareas" name="tareas" rows="6" class="form-control form-control-sm" data-tareas-tareas
                    placeholder="Una por línea:&#10;rehacer los soportes de la base&#10;bajar la escala un 5%&#10;revisar el grosor de las paredes"></textarea>
                <p class="text-muted small mb-0">Una tarea por línea. El icono del índice muestra cuántas quedan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // ---- Sesiones activas: refresco parcial cada 20s -------------------
    // Solo repinta esta franja, no la página entera: recargar borraría lo
    // que hay escrito en el buscador o apagaría el modo "Organizar". Se
    // detiene solo si la pestaña está en segundo plano (Page Visibility
    // API) — no tiene sentido gastar peticiones en algo que no se ve.
    var cajaSesiones = document.getElementById('sesionesActivas');

    function pintarSesionActiva(s) {
        var div = document.createElement('div');
        div.className = 'alert alert-info py-2 mb-2 d-flex align-items-center gap-2';

        var punto = document.createElement('i');
        punto.className = 'bi bi-circle-fill text-success';
        punto.style.fontSize = '.5rem';
        div.appendChild(punto);

        var cuerpo = document.createElement('div');
        cuerpo.className = 'flex-grow-1';

        var maquina = document.createElement('strong');
        maquina.textContent = s.maquina;
        cuerpo.appendChild(maquina);
        cuerpo.appendChild(document.createTextNode(' está trabajando en '));

        var enlace = document.createElement('a');
        enlace.href = '<?= site_url('piezas/variante/') ?>' + encodeURIComponent(s.variante_id);
        enlace.className = 'alert-link';
        enlace.textContent = s.familia + ' / ' + s.variante;
        cuerpo.appendChild(enlace);

        if (s.dias > 0) {
            var dias = document.createElement('span');
            dias.className = 'text-muted small';
            dias.textContent = ' (desde hace ' + s.dias + ' día(s))';
            cuerpo.appendChild(dias);
        }

        div.appendChild(cuerpo);
        return div;
    }

    function refrescarSesionesActivas() {
        if (!cajaSesiones || document.hidden) return;

        fetch('<?= site_url('piezas/sesiones-activas') ?>', { headers: { 'Accept': 'application/json' } })
            .then(function (resp) { return resp.ok ? resp.json() : null; })
            .then(function (datos) {
                if (!datos) return;
                cajaSesiones.innerHTML = '';
                datos.sesiones.forEach(function (s) {
                    cajaSesiones.appendChild(pintarSesionActiva(s));
                });
            })
            .catch(function () { /* red caída o lo que sea: se calla y lo intenta en el próximo ciclo */ });
    }

    if (cajaSesiones) {
        setInterval(refrescarSesionesActivas, 20000);
    }

    // ---- Toggle visibilidad sterclicks (categoría/familia/variante) --
    document.querySelectorAll('form[data-toggle-visibilidad]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var boton = form.querySelector('button[type=submit]');
            boton.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
                .then(function (resp) { return resp.json(); })
                .then(function (datos) {
                    boton.disabled = false;
                    if (!datos.ok) { alert(datos.mensaje || 'No se pudo cambiar.'); return; }

                    var visible = !!datos.visible;
                    var claseOculta = form.getAttribute('data-clase-oculta');
                    var claseVisible = form.getAttribute('data-clase-visible');
                    boton.classList.toggle(claseOculta, !visible);
                    boton.classList.toggle(claseVisible, visible);
                    boton.title = visible ? 'Ocultar de sterclicks' : 'Mostrar en sterclicks';

                    var icono = boton.querySelector('i');
                    icono.classList.toggle('bi-eye', visible);
                    icono.classList.toggle('bi-eye-slash', !visible);

                    if (form.hasAttribute('data-con-texto')) {
                        boton.querySelector('[data-texto]').textContent = visible ? 'visible' : 'oculta';
                    }
                })
                .catch(function () {
                    boton.disabled = false;
                    alert('No se pudo conectar con el servidor.');
                });
        });
    });

    // ---- Plegar categorías -------------------------------------------
    // A mano en vez de con Collapse de Bootstrap: el buscador necesita
    // abrirlas y volver a dejarlas como estaban, y con clases propias eso
    // no pelea con el estado interno del componente.
    var CERRADAS = 'piezas_categorias_cerradas';

    function cerradas() {
        try { return JSON.parse(localStorage.getItem(CERRADAS)) || []; } catch (e) { return []; }
    }

    function pintar(id, abierta) {
        var cuerpo = document.getElementById(id);
        if (!cuerpo) return;
        cuerpo.classList.toggle('d-none', !abierta);

        var cabecera = document.querySelector('[data-plegar="' + id + '"]');
        if (!cabecera) return;

        var chevron = cabecera.querySelector('[data-chevron]');
        if (chevron) {
            chevron.classList.toggle('bi-chevron-down', abierta);
            chevron.classList.toggle('bi-chevron-right', !abierta);
        }
        var boton = cabecera.querySelector('[aria-controls]');
        if (boton) boton.setAttribute('aria-expanded', abierta ? 'true' : 'false');
    }

    // Lo mismo para la cuadrícula. Mismo id de categoría ("cat-N"/"cat-sin")
    // y mismo estado guardado (CERRADAS) que la tabla, así que las dos
    // funciones se llaman siempre juntas y las dos vistas quedan igual.
    function pintarGal(id, abierta) {
        var cuerpo = document.querySelector('[data-galcuerpo="' + id + '"]');
        if (cuerpo) cuerpo.classList.toggle('d-none', !abierta);

        var cabecera = document.querySelector('[data-galplegar="' + id + '"]');
        var chevron = cabecera ? cabecera.querySelector('[data-galchevron]') : null;
        if (chevron) {
            chevron.classList.toggle('bi-chevron-down', abierta);
            chevron.classList.toggle('bi-chevron-right', !abierta);
        }
    }

    function pintarAmbas(id, abierta) {
        pintar(id, abierta);
        pintarGal(id, abierta);
    }

    cerradas().forEach(function (id) { pintarAmbas(id, false); });

    function alternarPlegado(id) {
        var lista = cerradas();
        var estabaCerrada = lista.indexOf(id) !== -1;
        lista = estabaCerrada ? lista.filter(function (x) { return x !== id; }) : lista.concat([id]);
        localStorage.setItem(CERRADAS, JSON.stringify(lista));
        pintarAmbas(id, estabaCerrada);
    }

    document.querySelectorAll('[data-plegar]').forEach(function (cabecera) {
        cabecera.addEventListener('click', function (e) {
            // Los botones de «Organizar» viven en esta misma línea: mover una
            // categoría de sitio no debe plegarla de paso.
            if (e.target.closest('form, a')) return;
            alternarPlegado(cabecera.getAttribute('data-plegar'));
        });
    });

    document.querySelectorAll('[data-galplegar]').forEach(function (cabecera) {
        cabecera.addEventListener('click', function () {
            alternarPlegado(cabecera.getAttribute('data-galplegar'));
        });
    });

    // ---- Modo organizar ----------------------------------------------
    // Se recuerda entre cargas a propósito: cada pieza que cambia de
    // categoría recarga la página, y colocar quince seguidas sería volver
    // a encender el modo quince veces.
    var ORGANIZANDO = 'piezas_organizando';
    var btnOrganizar = document.getElementById('btnOrganizar');

    function pintarOrganizar(encendido) {
        if (btnOrganizar) btnOrganizar.classList.toggle('active', encendido);
        document.querySelectorAll('.zona-organizar').forEach(function (zona) {
            zona.classList.toggle('d-none', !encendido);
        });
    }

    // ---- Modal de tareas / advertencia --------------------------------
    // Uno solo para todas las filas: el botón que lo abre trae en sus
    // data-* la acción del formulario y lo que ya está escrito. Va antes
    // del posible "return" de abajo (cuando no hay piezas) — sin piezas no
    // hay botones que lo abran, pero así el bloque no depende de eso.
    var modalTareas = document.getElementById('modalTareas');
    if (modalTareas) {
        var formTareas = document.getElementById('formTareas');
        modalTareas.addEventListener('show.bs.modal', function (e) {
            var boton = e.relatedTarget;
            if (!boton) return;
            formTareas.setAttribute('action', boton.getAttribute('data-accion') || '');
            modalTareas.querySelector('[data-nombre-tareas]').textContent = boton.getAttribute('data-nombre') || '';
            modalTareas.querySelector('[data-advertencia-tareas]').value = boton.getAttribute('data-advertencia') || '';
            modalTareas.querySelector('[data-tareas-tareas]').value = boton.getAttribute('data-tareas') || '';
        });
        modalTareas.addEventListener('shown.bs.modal', function () {
            var campo = modalTareas.querySelector('[data-tareas-tareas]');
            if (campo) campo.focus();
        });
    }

    // ---- Mostrar/ocultar filtros ----------------------------------------
    // Plegados por defecto (spec: consulta puntual, no algo que se mira cada
    // vez). Se recuerda si se han dejado abiertos, igual que "Organizar".
    var FILTROS_ABIERTOS = 'piezas_filtros_abiertos';
    var btnFiltros = document.getElementById('btnFiltros');
    var cajaFiltrosToggle = document.getElementById('filtrosPiezas');

    function pintarFiltros(abierto) {
        if (btnFiltros) btnFiltros.classList.toggle('active', abierto);
        if (cajaFiltrosToggle) cajaFiltrosToggle.classList.toggle('d-none', !abierto);
        if (btnFiltros) btnFiltros.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    pintarFiltros(localStorage.getItem(FILTROS_ABIERTOS) === '1');

    if (btnFiltros) {
        btnFiltros.addEventListener('click', function () {
            var abrir = !cajaFiltrosToggle || cajaFiltrosToggle.classList.contains('d-none');
            localStorage.setItem(FILTROS_ABIERTOS, abrir ? '1' : '0');
            pintarFiltros(abrir);
        });
    }

    // ---- Modo enfoque -------------------------------------------------
    // Solo toca la clase de la tabla; el CSS de arriba hace el resto
    // (oculta ojo/SKU/medidas/malla y quita el texto de los badges de
    // estado). Se recuerda entre cargas, igual que "Filtros" y "Organizar".
    var ENFOQUE = 'piezas_enfoque';
    var btnFocus = document.getElementById('btnFocus');
    var tablaPiezas = document.getElementById('tablaPiezas');

    function pintarEnfoque(encendido) {
        if (tablaPiezas) tablaPiezas.classList.toggle('modo-focus', encendido);
        if (btnFocus) {
            btnFocus.classList.toggle('active', encendido);
            btnFocus.setAttribute('aria-pressed', encendido ? 'true' : 'false');
        }
    }

    pintarEnfoque(localStorage.getItem(ENFOQUE) === '1');

    if (btnFocus) {
        btnFocus.addEventListener('click', function () {
            var encendido = !btnFocus.classList.contains('active');
            localStorage.setItem(ENFOQUE, encendido ? '1' : '0');
            pintarEnfoque(encendido);
        });
    }

    // ---- Vista tabla / cuadrícula -----------------------------------
    // Intercambia la tabla por la rejilla de tarjetas y viceversa. El
    // buscador y los filtros siguen recortando en las dos vistas
    // (aplicarFiltros mira también los [data-tarjeta]). "Enfoque" solo
    // tiene sentido en la tabla, así que se esconde en cuadrícula.
    var VISTA = 'piezas_vista';
    var btnGaleria = document.getElementById('btnGaleria');
    var galeriaPiezas = document.getElementById('galeriaPiezas');

    function pintarVista(galeria) {
        if (tablaPiezas) tablaPiezas.classList.toggle('d-none', galeria);
        if (galeriaPiezas) galeriaPiezas.classList.toggle('d-none', !galeria);
        if (btnFocus) btnFocus.classList.toggle('d-none', galeria);
        if (btnGaleria) {
            btnGaleria.classList.toggle('active', galeria);
            btnGaleria.setAttribute('aria-pressed', galeria ? 'true' : 'false');
        }
    }

    pintarVista(localStorage.getItem(VISTA) === 'galeria');

    if (btnGaleria) {
        btnGaleria.addEventListener('click', function () {
            var galeria = !btnGaleria.classList.contains('active');
            localStorage.setItem(VISTA, galeria ? 'galeria' : 'tabla');
            pintarVista(galeria);
        });
    }

    pintarOrganizar(localStorage.getItem(ORGANIZANDO) === '1');

    if (btnOrganizar) {
        btnOrganizar.addEventListener('click', function () {
            var encendido = !btnOrganizar.classList.contains('active');
            localStorage.setItem(ORGANIZANDO, encendido ? '1' : '0');
            pintarOrganizar(encendido);
        });
    }

    // ---- Buscador y filtros --------------------------------------------
    // Los dos recortan la misma tabla, así que hay un único sitio que decide
    // qué fila se ve: si fueran dos pasadas independientes, la segunda
    // desharía a la primera.
    var buscador = document.getElementById('buscadorPiezas');
    var cajaFiltros = document.getElementById('filtrosPiezas');
    if (!buscador && !cajaFiltros) return;
    var sinResultados = document.getElementById('sinResultados');
    var filtro = '';

    function aplicarFiltros() {
        var q = buscador ? buscador.value.trim().toLowerCase() : '';
        var encontradas = 0;
        var recortando = q !== '' || filtro !== '';

        // Filas de pieza (una por familia) y de variante (subfilas, cuando
        // hay más de una): comparten el mismo data-buscar, así que la
        // búsqueda las muestra/oculta juntas. Los tokens sí son propios de
        // cada fila — la de la pieza lleva la unión de los de sus variantes,
        // para que aparezca la cabecera cuando encaja cualquiera de ellas.
        // También las tarjetas de la cuadrícula ([data-tarjeta]): mismo
        // data-buscar/data-tokens, así el recorte vale para las dos vistas.
        document.querySelectorAll('[data-pieza], [data-subpieza], [data-tarjeta]').forEach(function (fila) {
            var visible = fila.getAttribute('data-buscar').indexOf(q) !== -1;

            if (visible && filtro !== '') {
                visible = (fila.getAttribute('data-tokens') || '').split(' ').indexOf(filtro) !== -1;
            }

            fila.classList.toggle('d-none', !visible);
            if ((fila.hasAttribute('data-pieza') || fila.hasAttribute('data-tarjeta')) && visible) encontradas++;
        });

        // Grupos de categoría de la cuadrícula: mismo trato que los tbody de
        // la tabla — mientras se recorta se abren todos y se esconde el que
        // se quede sin tarjetas; al soltar, se restaura el plegado guardado.
        document.querySelectorAll('[data-galgrupo]').forEach(function (grupo) {
            var cabecera = grupo.querySelector('[data-galplegar]');
            var id = cabecera ? cabecera.getAttribute('data-galplegar') : null;
            var contador = grupo.querySelector('[data-galcontador]');

            if (!recortando) {
                grupo.classList.remove('d-none');
                if (contador) contador.textContent = grupo.querySelectorAll('[data-tarjeta]').length;
                if (id) pintarGal(id, cerradas().indexOf(id) === -1);
                return;
            }

            var visibles = grupo.querySelectorAll('[data-tarjeta]:not(.d-none)').length;
            grupo.classList.toggle('d-none', visibles === 0);
            if (contador) contador.textContent = visibles;
            if (id) pintarGal(id, visibles > 0);
        });

        // Un grupo cerrado escondería resultados sin decirlo: mientras se
        // recorta se abren todos, y al soltar el filtro y el texto se
        // restaura el plegado que el usuario había dejado. La cabecera de
        // cada categoría es el <tbody> justo antes del que lleva el id
        // "cat-N" (mismo orden en que los pinta el bucle de PHP).
        document.querySelectorAll('tbody[id]').forEach(function (cuerpo) {
            var cabecera = cuerpo.previousElementSibling;
            var visibles = cuerpo.querySelectorAll('[data-pieza]:not(.d-none)').length;
            var contador = cabecera ? cabecera.querySelector('[data-contador]') : null;

            if (!recortando) {
                if (cabecera) cabecera.classList.remove('d-none');
                if (contador) contador.textContent = cuerpo.querySelectorAll('[data-pieza]').length;
                pintar(cuerpo.id, cerradas().indexOf(cuerpo.id) === -1);
                return;
            }

            if (cabecera) cabecera.classList.toggle('d-none', visibles === 0);
            if (contador) contador.textContent = visibles;
            // Abre el grupo (y deja flecha y aria coherentes) si tiene algo que
            // enseñar. Con 0 la cabecera se esconde entera, así que cómo quede
            // su flecha da igual.
            pintar(cuerpo.id, visibles > 0);
        });

        if (sinResultados) sinResultados.classList.toggle('d-none', !recortando || encontradas > 0);
    }

    if (buscador) buscador.addEventListener('input', aplicarFiltros);

    if (cajaFiltros) {
        // El botón partido de "Imprimir" tiene que enseñar con el menú cerrado
        // cuál de sus tres opciones está puesta; si no, la mitad de los filtros
        // quedan escondidos detrás de una flecha y no se sabe qué se está viendo.
        var grupos = Array.prototype.slice.call(cajaFiltros.querySelectorAll('[data-grupo]'));

        function pintarGrupos() {
            grupos.forEach(function (grupo) {
                var prefijo = grupo.getAttribute('data-grupo');
                var dentro = filtro.indexOf(prefijo) === 0;

                grupo.querySelectorAll('.btn').forEach(function (boton) {
                    boton.classList.toggle('active', dentro);
                });

                var etiqueta = grupo.querySelector('[data-etiqueta]');
                var cuenta = grupo.querySelector('[data-cuenta]');
                var elegido = dentro ? grupo.querySelector('.dropdown-item[data-filtro="' + filtro + '"]') : null;

                if (!etiqueta || !cuenta) return;

                if (elegido && filtro !== prefijo) {
                    etiqueta.textContent = etiqueta.getAttribute('data-base') + ' · '
                        + elegido.querySelector('span').textContent;
                    cuenta.textContent = elegido.querySelector('.badge').textContent;
                } else {
                    etiqueta.textContent = etiqueta.getAttribute('data-base');
                    cuenta.textContent = cuenta.getAttribute('data-base');
                }
            });
        }

        // El texto y el número de partida, para poder volver a ellos.
        grupos.forEach(function (grupo) {
            var etiqueta = grupo.querySelector('[data-etiqueta]');
            var cuenta = grupo.querySelector('[data-cuenta]');
            if (etiqueta) etiqueta.setAttribute('data-base', etiqueta.textContent.trim());
            if (cuenta) cuenta.setAttribute('data-base', cuenta.textContent.trim());
        });

        cajaFiltros.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-filtro]');
            if (!chip) return;

            // Volver a pulsar el que ya está puesto lo quita: es el gesto que
            // sale solo cuando quieres ver todo otra vez.
            var pedido = chip.getAttribute('data-filtro');
            filtro = (pedido === filtro) ? '' : pedido;

            cajaFiltros.querySelectorAll('[data-filtro]').forEach(function (uno) {
                uno.classList.toggle('active', uno.getAttribute('data-filtro') === filtro);
            });
            pintarGrupos();

            aplicarFiltros();
        });
    }

    // ---- Calculadora de tiempo estimado por capas ---------------------
    // Todo en vivo y en el navegador: al teclear capas (o al retocar los
    // ajustes, aunque aún no se hayan guardado) se recalcula. El
    // minuto/capa sale de la referencia, nunca es una constante suelta.
    var calcModal = document.getElementById('modalCalcTiempo');
    if (calcModal) {
        var calcCaja      = calcModal.querySelector('.modal-content');
        var campoCapas    = document.getElementById('calcCapas');
        var campoCapasRef = document.getElementById('cfgCapasRef');
        var campoMinRef   = document.getElementById('cfgMinRef');
        var campoMinPrep  = document.getElementById('cfgMinPrep');

        var elVacio      = document.getElementById('calcVacio');
        var elTabla      = document.getElementById('calcResultado');
        var elImpBonito  = document.getElementById('calcImpresionBonito');
        var elImpMin     = document.getElementById('calcImpresionMin');
        var elPrepBonito = document.getElementById('calcPrepBonito');
        var elPrepMin    = document.getElementById('calcPrepMin');
        var elTotBonito  = document.getElementById('calcTotalBonito');
        var elTotMin     = document.getElementById('calcTotalMin');
        var elRefTexto   = document.getElementById('calcRefTexto');
        var elPorCapa    = document.getElementById('calcPorCapaTexto');
        var elAltura     = document.getElementById('calcAltura');

        // Altura de capa fija del laminador, para pasar capas -> cm.
        var MM_POR_CAPA = 0.05;

        // Número con coma decimal a la española y sin ceros de relleno.
        function nEs(n, decs) {
            var s = n.toLocaleString('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: decs });
            return s;
        }

        // Minutos -> "1 d 2 h 30 min", quitando los tramos a cero.
        function bonito(mins) {
            mins = Math.round(mins);
            var d = Math.floor(mins / 1440);
            var h = Math.floor((mins % 1440) / 60);
            var m = mins % 60;
            var partes = [];
            if (d) partes.push(d + ' d');
            if (h) partes.push(h + ' h');
            if (m || !partes.length) partes.push(m + ' min');
            return partes.join(' ');
        }

        function leer(campo, porDefectoAttr) {
            var v = parseFloat(String(campo.value).replace(',', '.'));
            if (isFinite(v) && v >= 0) return v;
            return parseFloat(calcCaja.getAttribute(porDefectoAttr)) || 0;
        }

        function recalcular() {
            var capasRef = leer(campoCapasRef, 'data-capas-ref');
            var minRef   = leer(campoMinRef, 'data-minutos-ref');
            var minPrep  = leer(campoMinPrep, 'data-minutos-prep');
            var porCapa  = capasRef > 0 ? minRef / capasRef : 0;

            elRefTexto.textContent = nEs(capasRef, 0) + ' capas = ' + nEs(minRef, 2) + ' min';
            elPorCapa.textContent  = nEs(porCapa, 4);

            var capas = parseFloat(String(campoCapas.value).replace(',', '.'));
            if (!isFinite(capas) || capas <= 0) {
                elAltura.textContent = '';
                elTabla.classList.add('d-none');
                elVacio.classList.remove('d-none');
                return;
            }

            elAltura.textContent = '≈ ' + nEs(capas * MM_POR_CAPA / 10, 1) + ' cm de alto (' + nEs(MM_POR_CAPA, 2) + ' mm/capa)';

            var impresion = capas * porCapa;
            var total     = impresion + minPrep;

            elImpBonito.textContent  = bonito(impresion);
            elImpMin.textContent     = nEs(impresion, 1) + ' min · ' + nEs(capas, 0) + ' capas × ' + nEs(porCapa, 4);
            elPrepBonito.textContent = bonito(minPrep);
            elPrepMin.textContent    = nEs(minPrep, 1) + ' min fijos';
            elTotBonito.textContent  = bonito(total);
            elTotMin.textContent     = nEs(total, 1) + ' min';

            elVacio.classList.add('d-none');
            elTabla.classList.remove('d-none');
        }

        [campoCapas, campoCapasRef, campoMinRef, campoMinPrep].forEach(function (c) {
            c.addEventListener('input', recalcular);
        });
        calcModal.addEventListener('shown.bs.modal', function () {
            campoCapas.focus();
            recalcular();
        });

        // Chevron del plegable de ajustes: apunta a la derecha plegado, abajo abierto.
        var ajustes = document.getElementById('calcAjustes');
        var ajustesIcono = document.querySelector('#calcAjustesToggle i');
        if (ajustes && ajustesIcono) {
            ajustes.addEventListener('show.bs.collapse', function () {
                ajustesIcono.classList.replace('bi-chevron-right', 'bi-chevron-down');
            });
            ajustes.addEventListener('hide.bs.collapse', function () {
                ajustesIcono.classList.replace('bi-chevron-down', 'bi-chevron-right');
            });
        }
    }
})();
</script>

<?= $this->endSection() ?>
