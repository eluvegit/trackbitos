<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

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

    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-grid-3x3-gap"></i> Galería
        <?php if (!empty($carritoCount)): ?>
            <span class="badge text-bg-primary"><?= (int) $carritoCount ?></span>
        <?php endif; ?>
    </a>
    <?php if (!empty($familias)): ?>
        <?php // Colocar piezas es una tarea aparte de mirarlas: los selectores solo estorban el resto del tiempo. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnOrganizar">
            <i class="bi bi-arrows-move"></i> Organizar
        </button>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCategorias">
        <i class="bi bi-folder"></i> Categorías
    </button>
    <?php // Solo icono: aquí se entra una vez por equipo, el día que se estrena. ?>
    <a href="<?= site_url('piezas/maquinas') ?>" class="btn btn-sm btn-outline-secondary" title="Máquinas">
        <i class="bi bi-pc-display"></i>
    </a>
    <a href="<?= site_url('piezas/estadisticas') ?>" class="btn btn-sm btn-outline-secondary" title="Cuánto ocupa el módulo">
        <i class="bi bi-hdd-stack"></i>
    </a>
    <?php // Solo aparece cuando hay algo dentro: no tiene sentido un icono a una papelera vacía. ?>
    <?php if (!empty($papeleraCount)): ?>
        <a href="<?= site_url('piezas/papelera') ?>" class="btn btn-sm btn-outline-secondary" title="Papelera">
            <i class="bi bi-trash"></i>
            <span class="badge text-bg-secondary"><?= (int) $papeleraCount ?></span>
        </a>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalFamilia">
        <i class="bi bi-plus-lg"></i> Pieza
    </button>
    <?php if (!empty($familias)): ?>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalVariante">
            <i class="bi bi-plus-lg"></i> Variante
        </button>
    <?php endif; ?>
</h5>

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
 * versiones, aviso), no perseguir cada dato en un sitio distinto de cada
 * fila. Estos tres devuelven el HTML de una celda (nunca texto de usuario
 * sin `esc()`), para no repetir la misma condición en la fila de pieza
 * única y en cada subfila de variante.
 */
/**
 * Qué tiene la pieza terminado. Antes esto era validada-o-no, y todo lo
 * demás ("nunca se promocionó nada", "promocionada sin imprimir",
 * "impresa, pendiente de juzgar", "la última no sirve") se veía igual.
 */
$badgeMadurez = static function (array $v): string {
    if ($v['validada']) {
        return '<span class="badge text-bg-success"><i class="bi bi-check-circle-fill"></i> v'
            . sprintf('%03d', (int) $v['validada']['numero']) . '</span>';
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
    // el trabajo aún está en la sesión, no ha llegado al historial.
    if ($v['ultima_version_estado'] === null) {
        return '<span class="badge text-bg-secondary" title="Todavía no se ha promocionado ninguna versión">'
            . '<i class="bi bi-dash-circle"></i> sin versión</span>';
    }
    if ($v['ultima_version_estado'] === 'borrador') {
        return '<span class="badge text-bg-secondary" title="Promocionada, pendiente de imprimir de prueba">'
            . '<i class="bi bi-printer"></i> versión sin imprimir</span>';
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
 * pieza a comprobarlo. Verde: hay STL. Naranja: falta, y es lo único que
 * separa a esa pieza de la impresora.
 */
$colStl = static function (array $v): string {
    $stl = $v['stl'] ?? ['aplica' => false, 'trozos' => 0];

    // Sin ninguna versión promocionada no falta el STL: falta la versión.
    if (empty($stl['aplica'])) {
        return '';
    }

    if ((int) $stl['trozos'] === 0) {
        return '<span class="badge border border-warning text-warning-emphasis fw-normal"'
            . ' title="Esta versión no tiene STL: no se puede imprimir ni añadir a la placa">'
            . '<i class="bi bi-file-earmark-x"></i> sin STL</span>';
    }

    $trozos = (int) $stl['trozos'];

    return '<span class="text-success" title="' . ($trozos === 1 ? 'STL adjunto' : $trozos . ' STL adjuntos (se imprime en trozos)') . '">'
        . '<i class="bi bi-file-earmark-check-fill"></i>'
        . ($trozos > 1 ? ' <span class="small">' . $trozos . '</span>' : '')
        . '</span>';
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

/** Todo lo que debe encontrar el buscador de una pieza: su nombre y el de sus variantes con sus SKU. */
$textoBuscable = static function (array $familia): string {
    $partes = [$familia['nombre']];
    foreach ($familia['variantes'] as $v) {
        $partes[] = $v['nombre'];
        $partes[] = $v['sku'] ?? '';
    }

    return mb_strtolower(implode(' ', $partes));
};
?>

<table class="table table-sm align-middle mb-2" id="tablaPiezas">
    <?php foreach ($grupos as $indice => $grupo): ?>
        <?php $categoria = $grupo['categoria']; ?>
        <?php $idGrupo = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin'; ?>
        <tbody class="table-group-divider">
            <tr>
                <td colspan="7" class="py-1">
                    <?php // Toda la línea pliega, no solo la flecha: es el objetivo grande y
                          // obvio, y acertar en un icono de 16px para algo que se hace a diario
                          // es un peaje sin motivo. El botón sigue existiendo para el teclado —
                          // su clic burbujea hasta aquí, así que la acción se ejecuta una vez. ?>
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
                <tr><td colspan="7" class="text-muted small ps-4">Vacía: mueve piezas aquí desde «Organizar».</td></tr>
            <?php endif; ?>

            <?php foreach ($grupo['piezas'] as $familia): ?>
                <?php $variantes = $familia['variantes']; $buscar = esc($textoBuscable($familia), 'attr'); ?>
                <tr data-pieza data-buscar="<?= $buscar ?>">
                    <td>
                        <?php if (count($variantes) === 1): ?>
                            <?php // Lo normal: una pieza es una sola cosa, así que la fila lleva directa a su ficha. ?>
                            <a href="<?= site_url('piezas/variante/' . (int) $variantes[0]['id']) ?>"
                                class="text-decoration-none text-body"><?= esc($familia['nombre']) ?></a>
                        <?php else: ?>
                            <?= esc($familia['nombre']) ?>
                            <span class="text-muted small">
                                (<?= count($variantes) > 0 ? count($variantes) . ' variantes' : 'sin variantes' ?>)
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= count($variantes) === 1 ? $colSku($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colEstado($variantes[0]) : '' ?></td>
                    <td><?= count($variantes) === 1 ? $colStl($variantes[0]) : '' ?></td>
                    <td class="text-muted small text-end">
                        <?= count($variantes) === 1 ? (int) $variantes[0]['versiones'] . ' vers.' : '' ?>
                    </td>
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
                        <tr data-subpieza data-buscar="<?= $buscar ?>">
                            <td class="ps-4">
                                <a href="<?= site_url('piezas/variante/' . (int) $v['id']) ?>"
                                    class="text-decoration-none text-body">↳ <?= esc($v['nombre']) ?></a>
                            </td>
                            <td><?= $colSku($v) ?></td>
                            <td><?= $colEstado($v) ?></td>
                            <td><?= $colStl($v) ?></td>
                            <td class="text-muted small text-end"><?= (int) $v['versiones'] ?> vers.</td>
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

<p class="text-muted small d-none" id="sinResultados">Ninguna pieza coincide con la búsqueda.</p>

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
                <label class="form-label small">SKU (opcional)</label>
                <input type="text" name="sku" class="form-control form-control-sm mb-2" placeholder="el código de tu tienda, si ya lo tienes" maxlength="50">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace lista para trabajar, con numeración propia desde v001. Solo hace falta
                    añadir variantes si esta pieza acaba teniendo varias líneas de diseño.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-success">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Alta de variante -->
<div class="modal fade" id="modalVariante" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/variante') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Variante nueva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Pieza</label>
                <select name="familia_id" class="form-select form-select-sm mb-2" required>
                    <?php foreach ($familias as $familia): ?>
                        <option value="<?= (int) $familia['id'] ?>"><?= esc($familia['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-2" placeholder="torso-recto, pose-futbolista..." maxlength="150" required>
                <label class="form-label small">SKU (opcional)</label>
                <input type="text" name="sku" class="form-control form-control-sm mb-2" placeholder="el código de tu tienda, si ya lo tienes" maxlength="50">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control form-control-sm" rows="2"></textarea>
                <p class="text-muted small mt-2 mb-0">
                    Nace con su rama de trabajo abierta y numeración propia desde v001.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Crear</button>
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

    pintarOrganizar(localStorage.getItem(ORGANIZANDO) === '1');

    if (btnOrganizar) {
        btnOrganizar.addEventListener('click', function () {
            var encendido = !btnOrganizar.classList.contains('active');
            localStorage.setItem(ORGANIZANDO, encendido ? '1' : '0');
            pintarOrganizar(encendido);
        });
    }

    // ---- Buscador ------------------------------------------------------
    var buscador = document.getElementById('buscadorPiezas');
    if (!buscador) return;
    var sinResultados = document.getElementById('sinResultados');

    buscador.addEventListener('input', function () {
        var q = buscador.value.trim().toLowerCase();
        var encontradas = 0;

        // Filas de pieza (una por familia) y de variante (subfilas, cuando
        // hay más de una): comparten el mismo data-buscar, así que se
        // muestran/ocultan juntas sin más que iterar las dos.
        document.querySelectorAll('[data-pieza], [data-subpieza]').forEach(function (fila) {
            var visible = fila.getAttribute('data-buscar').indexOf(q) !== -1;
            fila.classList.toggle('d-none', !visible);
            if (fila.hasAttribute('data-pieza') && visible) encontradas++;
        });

        // Un grupo cerrado escondería resultados sin decirlo: mientras se
        // busca se abren todos, y al vaciar el campo se restaura el plegado
        // que el usuario había dejado. La cabecera de cada categoría es el
        // <tbody> justo antes del que lleva el id "cat-N" (mismo orden en
        // que los pinta el bucle de PHP).
        document.querySelectorAll('tbody[id]').forEach(function (cuerpo) {
            var cabecera = cuerpo.previousElementSibling;
            var visibles = cuerpo.querySelectorAll('[data-pieza]:not(.d-none)').length;
            var contador = cabecera ? cabecera.querySelector('[data-contador]') : null;

            if (q === '') {
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

        if (sinResultados) sinResultados.classList.toggle('d-none', q === '' || encontradas > 0);
    });
})();
</script>

<?= $this->endSection() ?>
