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
    <input type="search" id="buscadorPiezas" class="form-control form-control-sm mb-3"
        placeholder="Buscar por nombre o SKU..." autocomplete="off">
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
        $html = '<span class="badge text-bg-success"><i class="bi bi-check-circle-fill"></i> v'
            . sprintf('%03d', $numValidada) . '</span>';

        // Hay un borrador posterior a la validada, esperando prueba: se
        // anexa al lado ("v001 v003") para no tener que entrar a la ficha
        // a ver que la buena ya tiene relevo pendiente de imprimir.
        if ($v['ultima_version_estado'] === 'borrador'
            && ($v['ultima_version_numero'] ?? 0) > $numValidada) {
            $html .= ' <span class="badge text-bg-secondary" title="Hay una versión más nueva pendiente de imprimir">'
                . '<i class="bi bi-printer"></i> para imprimir v' . sprintf('%03d', (int) $v['ultima_version_numero']) . '</span>';
        }

        return $html;
    }
    if ($v['ultima_version_estado'] === 'impresa') {
        return '<span class="badge text-bg-primary" title="Impresa, pendiente de juzgar el resultado">'
            . '<i class="bi bi-printer-fill"></i> sin validar</span>';
    }
    if ($v['ultima_version_estado'] === 'descartada') {
        return '<span class="badge text-bg-danger" title="La última versión se descartó: no sirve">'
            . '<i class="bi bi-x-circle-fill"></i> no sirve</span>';
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
                . '<i class="bi bi-circle"></i> sin empezar</span>';
        }

        return '<span class="badge text-bg-secondary" title="Todavía no se ha promocionado ninguna versión">'
            . '<i class="bi bi-dash-circle"></i> sin versión</span>';
    }
    // "Para imprimir", no "sin imprimir": lo mismo por fuera, pero uno nombra
    // una carencia y el otro lo siguiente que hay que hacer — que es lo que se
    // viene a mirar aquí. Además es la misma palabra que el filtro de arriba.
    // Sin el "versión" delante: en una columna que solo habla de versiones no
    // añadía nada y era la etiqueta más larga de todas.
    if ($v['ultima_version_estado'] === 'borrador') {
        return '<span class="badge text-bg-secondary" title="Promocionada, pendiente de imprimir de prueba">'
            . '<i class="bi bi-printer"></i> para imprimir</span>';
    }

    // Solo llega aquí una "superada" como última sin haber ninguna validada
    // (posible únicamente si se descartó la validada después). Raro, pero
    // "sin validar" sigue siendo cierto y no se inventa nada.
    return '<span class="badge text-bg-secondary">sin validar</span>';
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

    if (!empty($v['trabajo_en_curso'])) {
        $html .= ' <span class="badge border text-body-secondary fw-normal"'
            . ' title="Hay trabajo en la rama abierta que todavía no se ha promocionado">'
            . '<i class="bi bi-pencil"></i> modificando</span>';
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
 * La foto de la fila, o un hueco de la misma medida cuando la pieza aún no
 * tiene ninguna: así las filas no cambian de alto según haya foto o no, que
 * en una tabla de treinta líneas se nota más que la propia foto.
 *
 * `contain` y no `cover` como en la galería: ahí el recorte cuadra una
 * cuadrícula de tarjetas grandes, pero a 34 px recortar una pieza por los
 * lados la deja irreconocible, que es justo lo contrario de para lo que
 * está puesta.
 */
$colFoto = static function (array $v): string {
    if (empty($v['miniatura'])) {
        return '<span class="d-inline-flex align-items-center justify-content-center rounded border text-body-tertiary"'
            . ' style="width: 34px; height: 34px;"><i class="bi bi-box" style="font-size: .8rem;"></i></span>';
    }

    return '<img src="' . esc($v['miniatura'], 'attr') . '" alt="" loading="lazy" class="rounded border"'
        . ' style="width: 34px; height: 34px; object-fit: contain;">';
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
                <td colspan="9" class="py-1 bg-body-secondary">
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
                <tr><td colspan="9" class="text-muted small ps-4">Vacía: mueve piezas aquí desde «Organizar».</td></tr>
            <?php endif; ?>

            <?php $filaAlterna = false; ?>
            <?php foreach ($grupo['piezas'] as $familia): ?>
                <?php $variantes = $familia['variantes']; $buscar = esc($textoBuscable($familia), 'attr'); ?>
                <?php $filaAlterna = !$filaAlterna; $claseAlterna = $filaAlterna ? '' : ' pieza-alt'; ?>
                <tr class="pieza<?= $claseAlterna ?>" data-pieza data-buscar="<?= $buscar ?>" data-tokens="<?= implode(' ', $tokensDeFamilia($familia)) ?>">
                    <?php // Misma regla que el resto de columnas: la fila de la pieza solo
                          // habla de una variante cuando hay una sola. Con varias, cada una
                          // trae su foto en su propia subfila. ?>
                    <td style="width: 34px;"><?= count($variantes) === 1 ? $colFoto($variantes[0]) : '' ?></td>
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
                        <form method="post" action="<?= site_url('piezas/familia/' . (int) $familia['id'] . '/visibilidad') ?>" class="d-inline"
                            data-toggle-visibilidad data-clase-oculta="text-muted" data-clase-visible="text-primary">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm py-0 px-1 border-0 <?= empty($familia['visible_sterclicks']) ? 'text-muted' : 'text-primary' ?>"
                                title="<?= empty($familia['visible_sterclicks']) ? 'Mostrar en sterclicks' : 'Ocultar de sterclicks' ?>">
                                <i class="bi <?= empty($familia['visible_sterclicks']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                            </button>
                        </form>
                    </td>
                    <td><?= count($variantes) === 1 ? $colSku($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colEstado($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colStl($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colMedidas($variantes[0]) : '' ?></td>
                    <td class="text-center"><?= count($variantes) === 1 ? $colMalla($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colAviso($variantes[0]) : '' ?></td>
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
                            <td style="width: 34px;"><?= $colFoto($v) ?></td>
                            <td class="ps-4">
                                <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>"
                                    class="text-decoration-none text-body">↳ <?= esc($v['nombre']) ?></a>
                                <form method="post" action="<?= site_url('piezas/variante/' . (int) $v['id'] . '/visibilidad') ?>" class="d-inline"
                                    data-toggle-visibilidad data-clase-oculta="text-muted" data-clase-visible="text-primary">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm py-0 px-1 border-0 <?= empty($v['visible_sterclicks']) ? 'text-muted' : 'text-primary' ?>"
                                        title="<?= empty($v['visible_sterclicks']) ? 'Mostrar en sterclicks' : 'Ocultar de sterclicks' ?>">
                                        <i class="bi <?= empty($v['visible_sterclicks']) ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                    </button>
                                </form>
                            </td>
                            <td><?= $colSku($v) ?></td>
                            <td><?= $colEstado($v) ?></td>
                            <td><?= $colStl($v) ?></td>
                            <td><?= $colMedidas($v) ?></td>
                            <td class="text-center"><?= $colMalla($v) ?></td>
                            <td><?= $colAviso($v) ?></td>
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

    cerradas().forEach(function (id) { pintar(id, false); });

    document.querySelectorAll('[data-plegar]').forEach(function (cabecera) {
        cabecera.addEventListener('click', function (e) {
            // Los botones de «Organizar» viven en esta misma línea: mover una
            // categoría de sitio no debe plegarla de paso.
            if (e.target.closest('form, a')) return;

            var id = cabecera.getAttribute('data-plegar');
            var lista = cerradas();
            var estabaCerrada = lista.indexOf(id) !== -1;

            lista = estabaCerrada ? lista.filter(function (x) { return x !== id; }) : lista.concat([id]);
            localStorage.setItem(CERRADAS, JSON.stringify(lista));
            pintar(id, estabaCerrada);
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
        document.querySelectorAll('[data-pieza], [data-subpieza]').forEach(function (fila) {
            var visible = fila.getAttribute('data-buscar').indexOf(q) !== -1;

            if (visible && filtro !== '') {
                visible = (fila.getAttribute('data-tokens') || '').split(' ').indexOf(filtro) !== -1;
            }

            fila.classList.toggle('d-none', !visible);
            if (fila.hasAttribute('data-pieza') && visible) encontradas++;
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
})();
</script>

<?= $this->endSection() ?>
