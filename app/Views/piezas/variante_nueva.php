<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

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

/** Mismo cálculo que $botonTareas en piezas/index.php: cuántas tareas hay y si hay advertencia, para pintar el icono. */
$tareasLineas      = array_filter(
    array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($variante['tareas'] ?? ''))),
    static fn($t) => $t !== ''
);
$nTareasCabecera   = count($tareasLineas);
$tieneAdvertencia  = trim((string) ($variante['advertencia'] ?? '')) !== '';
?>

<?php
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fechaCorta = static function (?string $f) use ($meses): string {
    if (!$f) {
        return '';
    }
    $t = strtotime($f);

    return (int) date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1];
};
$colorEstado = ['borrador' => 'e-acc', 'impresa' => 'e-info', 'validada' => 'e-ok', 'superada' => 'e-neu', 'descartada' => 'e-bad'];
$visible   = !empty($variante['visible_sterclicks']);
$congelado = !empty($variante['congelado_en']);
$verBuena  = $validada ?? ($versiones[0] ?? null);
$vid       = (int) $variante['id'];

$imgPrincipal = null;
$imgTipo = 'render';
$imgPie = '';
if ($validada && !empty($validada['renders'])) {
    $imgPrincipal = $validada['renders'][0];
    $imgPie = $etiqueta($validada) . ' · render';
} else {
    foreach ($versiones as $vv) {
        if (!empty($vv['renders'])) {
            $imgPrincipal = $vv['renders'][0];
            $imgPie = $etiqueta($vv) . ' · render';
            break;
        }
    }
    if (!$imgPrincipal && !empty($rendersSueltos)) {
        $imgPrincipal = $rendersSueltos[0];
        $imgPie = 'render suelto';
    } elseif (!$imgPrincipal && !empty($referencias)) {
        $imgPrincipal = $referencias[0];
        $imgTipo = 'referencia';
        $imgPie = 'referencia';
    }
}

$rendersTodos = [];
foreach ($versiones as $vv) {
    foreach ($vv['renders'] as $r) {
        $rendersTodos[] = $r + ['_de' => $etiqueta($vv)];
    }
}
foreach ($rendersSueltos as $r) {
    $rendersTodos[] = $r + ['_de' => 'suelto'];
}
$nFotos = count($referencias) + count($rendersTodos) + count($capturasDeLaPieza);

$porServir = 0;
$pedidosAbiertos = [];
foreach ($pedidosDeLaPieza as $pl) {
    if (in_array($pl['estado'], \App\Models\PiezaPedidoModel::ABIERTOS, true)) {
        $porServir += max(0, (int) $pl['cantidad'] - (int) $pl['cantidad_completada']);
        $pedidosAbiertos[] = $pl;
    }
}
$etiquetasPedido = ['idea' => 'Idea', 'nuevo' => 'Nuevo', 'en_produccion' => 'Diseñando', 'para_impresion' => 'Para impresión', 'completado' => 'Hecho'];
$coloresPedidoFv = ['idea' => 'e-neu', 'nuevo' => 'e-acc', 'en_produccion' => 'e-warn', 'para_impresion' => 'e-info', 'completado' => 'e-ok'];

$veredictosPlaca = \App\Models\PiezaPlacaModel::VEREDICTOS;
$cuadroPlaca = ['buena' => 'q-ok', 'regular' => 'q-warn', 'repetir' => 'q-bad'];
$colorPlaca  = ['buena' => 'e-ok', 'regular' => 'e-warn', 'repetir' => 'e-bad'];
$coloresResultado = ['bien' => 'success', 'regular' => 'warning', 'mal' => 'danger'];
$resultados = \App\Models\PiezaPlacaVersionImagenModel::RESULTADOS;

$peso = $estadisticasPieza['peso'];
$bytesSesionesDe = static function (array $lista): int {
    $t = 0;
    foreach ($lista as $s) {
        if (empty($s['purgada'])) {
            $t += (int) ($s['tamano_bytes'] ?? 0);
        }
    }

    return $t;
};
$bytesRama = $bytesSesionesDe($sesiones);
$liberable = max(0, (int) $peso['sesiones'] - $bytesRama);
$pct = static fn(int $b) => $peso['total'] > 0 ? round($b * 100 / $peso['total'], 1) : 0;

$primeraVersion = empty($versiones);
$conPautas = $acciones['puede_promocionar'] && !empty($pautas);
$nDescartadas = count(array_filter($versiones, static fn($v) => $v['estado'] === 'descartada'));

$stockMax = max(1, (int) $existencias['stock'], (int) $existencias['minimo'] * 2);
$colorStockFv = ['cero' => 'var(--fv-bad)', 'bajo' => 'var(--fv-warn)', 'ok' => 'var(--fv-ok)'][$existencias['estado']];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">

<div class="fv" id="fv">
    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-circle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <header class="fv-top">
        <nav class="fv-crumbs">
            <a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span><?= esc($familia['nombre']) ?></span>
        </nav>
        <div class="fv-acciones">
            <button class="fv-gbtn" type="button" data-bs-toggle="modal" data-bs-target="#modalSku"><i class="bi bi-pencil"></i><span class="lbl-m">Editar</span></button>
            <?php if (!empty($variante['enlace_original'])): ?>
                <a class="fv-gbtn" href="<?= esc($variante['enlace_original'], 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i><span class="lbl-m">Original</span></a>
            <?php endif; ?>
            <button class="fv-gbtn<?= $congelado ? ' on' : '' ?>" type="submit" form="fvNevera"
                title="<?= $congelado ? 'Sacar de la nevera' : 'Aparcar en la nevera: no aparecerá en el listado, la galería ni el selector de componentes' ?>">
                <i class="bi bi-snow"></i><span class="lbl-m"><?= $congelado ? 'Congelada' : 'Congelar' ?></span></button>
            <?php if ($hermanasVivas > 0): ?>
                <button class="fv-gbtn" type="button" data-bs-toggle="modal" data-bs-target="#modalIndependizar"><i class="bi bi-box-arrow-right"></i><span class="lbl-m">Independizar</span></button>
            <?php endif; ?>
            <span class="fv-vsep"></span>
            <button class="fv-gbtn peligro" type="submit" form="formBorrarVariante"><i class="bi bi-trash3"></i><span class="lbl-m">Borrar</span></button>
            <a class="fv-gbtn" href="<?= current_url() ?>?nueva=0" title="Volver a la ficha clásica"><i class="bi bi-arrow-left-right"></i></a>
        </div>
        <form id="fvNevera" method="post" action="<?= site_url('piezas/variante/' . $vid . '/nevera') ?>" class="d-none"><?= csrf_field() ?></form>
        <form id="fvVisibilidad" method="post" action="<?= site_url('piezas/variante/' . $vid . '/visibilidad') ?>" class="d-none"><?= csrf_field() ?></form>
        <form id="formBorrarVariante" method="post" action="<?= site_url('piezas/variante/' . $vid . '/borrar') ?>" class="d-none"
            onsubmit="return confirm('¿Mandar «<?= esc($familia['nombre'] . ' / ' . $variante['nombre'], 'attr') ?>» a la papelera? Se puede restaurar durante 30 días.');">
            <?= csrf_field() ?>
        </form>
    </header>

    <section class="fv-hero">
        <h1><?= esc($variante['nombre']) ?></h1>
        <div class="fv-meta">
            <?php if ($validada): ?>
                <span class="fv-estado e-ok"><?= $etiqueta($validada) ?> validada</span>
            <?php else: ?>
                <span class="fv-estado e-neu">Sin versión validada</span>
            <?php endif; ?>
            <?php if (!empty($variante['sku'])): ?>
                <span class="fv-dot"></span><span class="fv-mono t3 user-select-all"><?= esc($variante['sku']) ?></span>
            <?php endif; ?>
            <?php if ($origen): ?>
                <span class="fv-dot"></span>
                <a href="<?= site_url('piezas/variante/' . (int) $origen['variante_id']) ?>">Derivada de <span class="t1"><?= esc($origen['variante']['nombre'] ?? '?') ?> · <?= $etiqueta($origen) ?></span></a>
            <?php endif; ?>
            <span class="fv-dot"></span>
            <button type="submit" form="fvVisibilidad" title="Pulsa para cambiarlo">
                <i class="bi <?= $visible ? 'bi-eye' : 'bi-eye-slash' ?>"></i> <?= $visible ? 'Visible en sterclicks' : 'Oculta en sterclicks' ?>
            </button>
        </div>
    </section>

    <?php if ($congelado): ?>
        <div class="fv-banner">
            <i class="bi bi-snow"></i>
            <span><b>En la nevera desde el <?= esc($fechaCorta($variante['congelado_en'])) ?>.</b> No aparece en el listado, la galería ni el selector de componentes.</span>
            <button class="fv-gbtn" type="submit" form="fvNevera">Sacar de la nevera</button>
        </div>
    <?php endif; ?>

    <nav class="fv-secs" id="fvSecs" aria-label="Secciones">
        <button type="button" data-s="todo" aria-pressed="true">Todo</button>
        <button type="button" data-s="trabajo" aria-pressed="false">Trabajo<?php if ($nTareasCabecera): ?> <span><?= $nTareasCabecera ?></span><?php endif; ?></button>
        <button type="button" data-s="pieza" aria-pressed="false">Pieza <span><?= $nFotos ?></span></button>
        <button type="button" data-s="stock" aria-pressed="false">Stock y pedidos <span><?= (int) $existencias['stock'] ?></span></button>
        <button type="button" data-s="versiones" aria-pressed="false">Versiones <span><?= count($versiones) ?></span></button>
        <button type="button" data-s="impresiones" aria-pressed="false">Impresiones <span><?= count($placasDeLaPieza) ?></span></button>
        <button type="button" data-s="espacio" aria-pressed="false">Espacio <span><?= esc($tamanoLegible($peso['total']) ?: '0 KB') ?></span></button>
    </nav>

    <main class="fv-grid">
        <!-- ============ PIEZA ============ -->
        <div class="fv-col fv-col-pieza">
            <section class="fv-panel fv-media" data-m="2" data-sec="pieza">
                <?php if ($imgPrincipal): ?>
                    <a class="fv-escena" href="<?= imagen_pieza($imgPrincipal, $imgTipo, 'v') ?>" target="_blank" data-foto-grande>
                        <img src="<?= imagen_pieza($imgPrincipal, $imgTipo, 'v') ?>" alt="<?= esc($variante['nombre'], 'attr') ?>">
                        <span class="fv-pie"><span class="fv-mono"><?= esc($imgPie) ?></span></span>
                    </a>
                <?php else: ?>
                    <button class="fv-escena vacia" type="button" data-bs-toggle="modal" data-bs-target="#modalRenderSuelto">
                        <i class="bi bi-image"></i><span>Añadir imagen</span>
                    </button>
                <?php endif; ?>
                <div class="fv-galeria">
                    <div class="fv-ph">
                        <div class="fv-subtabs" id="fvSubtabs">
                            <button type="button" data-g="refs" aria-pressed="true">Referencias<span><?= count($referencias) ?></span></button>
                            <button type="button" data-g="renders" aria-pressed="false">Renders<span><?= count($rendersTodos) ?></span></button>
                            <button type="button" data-g="capturas" aria-pressed="false">Capturas<span><?= count($capturasDeLaPieza) ?></span></button>
                        </div>
                    </div>
                    <div class="fv-thumbs" data-g="refs">
                        <?php foreach ($referencias as $r): ?>
                            <div class="fv-thumb">
                                <a href="<?= imagen_pieza($r, 'referencia', 'v') ?>" target="_blank" data-foto-grande title="<?= esc($r['notas'] ?? '', 'attr') ?>">
                                    <img src="<?= imagen_pieza($r, 'referencia') ?>" alt="Referencia" loading="lazy">
                                </a>
                                <form method="post" action="<?= site_url('piezas/referencia/' . (int) $r['id'] . '/borrar') ?>" onsubmit="return confirm('¿Apartar esta referencia a la papelera?');">
                                    <?= csrf_field() ?><input type="hidden" name="volver_a_variante" value="<?= $vid ?>">
                                    <button class="fv-x" title="Borrar"><i class="bi bi-x"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <button class="fv-thumb add" type="button" data-bs-toggle="modal" data-bs-target="#modalReferencia" aria-label="Añadir referencia"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="fv-thumbs" data-g="renders" hidden>
                        <?php foreach ($rendersTodos as $r): ?>
                            <div class="fv-thumb">
                                <a href="<?= imagen_pieza($r, 'render', 'v') ?>" target="_blank" data-foto-grande title="<?= esc($r['_de'] . ($r['notas'] ? ' · ' . $r['notas'] : ''), 'attr') ?>">
                                    <img src="<?= imagen_pieza($r, 'render') ?>" alt="Render" loading="lazy">
                                </a>
                                <span class="fv-tag"><?= esc($r['_de']) ?></span>
                                <form method="post" action="<?= site_url('piezas/render/' . (int) $r['id'] . '/borrar') ?>" onsubmit="return confirm('¿Apartar este render a la papelera?');">
                                    <?= csrf_field() ?>
                                    <button class="fv-x" title="Borrar"><i class="bi bi-x"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <button class="fv-thumb add" type="button" data-bs-toggle="modal" data-bs-target="#modalRenderSuelto" aria-label="Añadir render suelto"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="fv-thumbs" data-g="capturas" hidden>
                        <?php foreach ($capturasDeLaPieza as $img): ?>
                            <div class="fv-thumb">
                                <a href="<?= imagen_pieza($img, 'placa-version-imagen', 'v') ?>" target="_blank" data-foto-grande title="<?= esc($img['notas'] ?? '', 'attr') ?>">
                                    <img src="<?= imagen_pieza($img, 'placa-version-imagen') ?>" alt="Captura" loading="lazy">
                                </a>
                                <?php if (!empty($img['resultado'])): ?>
                                    <span class="fv-tag r-<?= esc($img['resultado'], 'attr') ?>"><?= esc($resultados[$img['resultado']] ?? $img['resultado']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($capturasDeLaPieza)): ?>
                            <p class="fv-vacio">Las capturas se suben desde cada impresión.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="fv-panel" data-m="12" data-sec="espacio">
                <div class="fv-ph"><h3>Espacio en disco</h3><a class="fv-link der solo-todo" href="#" data-ir="espacio">Gestionar <i class="bi bi-chevron-right"></i></a></div>
                <div class="fv-resumen-esp" title="<?= (int) $estadisticasPieza['intentos'] ?> intento(s) de impresión · <?= (int) $estadisticasPieza['sesiones'] ?> sesión(es) de trabajo · <?= (int) $estadisticasPieza['dias_vida'] ?> día(s) desde que se creó"><span class="v"><?= esc($tamanoLegible($peso['total']) ?: '0 KB') ?></span>
                    <span class="t2"><?= $liberable > 0 ? '· ' . esc($tamanoLegible($liberable)) . ' en sesiones cerradas' : '· sin sesiones cerradas que liberar' ?></span></div>
                <?php if ($peso['total'] > 0): ?>
                    <div class="fv-barra-esp" aria-hidden="true">
                        <i class="c-ver" style="width: <?= $pct((int) $peso['versiones']) ?>%"></i>
                        <i class="c-act" style="width: <?= $pct($bytesRama) ?>%"></i>
                        <i class="c-ses" style="width: <?= $pct($liberable) ?>%"></i>
                    </div>
                    <div class="fv-leyenda">
                        <span><i class="c-ver"></i>Versiones <?= esc($tamanoLegible((int) $peso['versiones']) ?: '0 KB') ?></span>
                        <?php if ($bytesRama): ?><span><i class="c-act"></i>Rama abierta <?= esc($tamanoLegible($bytesRama)) ?></span><?php endif; ?>
                        <span><i class="c-ses"></i>Sesiones cerradas <?= esc($tamanoLegible($liberable) ?: '0 KB') ?></span>
                    </div>
                <?php endif; ?>
                <div class="fv-elista solo-sec">
                    <?php if ($rama && $sesiones): ?>
                        <details>
                            <summary><span class="ver">rama</span><span class="txt"><?= esc($ramaNombre) ?> · <?= count($sesiones) ?> sesión(es)</span><span class="mb"><?= esc($tamanoLegible($bytesRama) ?: '0 KB') ?></span><i class="bi bi-chevron-right"></i></summary>
                            <div class="fv-sesiones"><?= $bloqueSesiones($sesiones, $vid) ?></div>
                        </details>
                    <?php endif; ?>
                    <?php foreach ($versiones as $v): ?>
                        <?php $bs = $bytesSesionesDe($v['sesiones']['lista']); ?>
                        <details>
                            <summary><span class="ver"><?= $etiqueta($v) ?></span>
                                <span class="txt">.blend <?= esc($tamanoLegible($v['tamano_blend']) ?: '—') ?> · <?= (int) $v['sesiones']['total'] ?> sesión(es)<?= $v['sesiones']['purgadas'] ? ', ' . (int) $v['sesiones']['purgadas'] . ' purgadas' : '' ?></span>
                                <span class="mb"><?= esc($tamanoLegible(($v['tamano_blend'] ?? 0) + $bs) ?: '0 KB') ?></span><i class="bi bi-chevron-right"></i></summary>
                            <div class="fv-sesiones">
                                <?= $bloqueSesiones($v['sesiones']['lista'], $vid) ?>
                                <?php if ($v['sesiones']['purgadas'] < $v['sesiones']['total']): ?>
                                    <form method="post" action="<?= site_url('piezas/version/' . (int) $v['id'] . '/purgar-sesiones') ?>"
                                        onsubmit="return confirm('¿Purgar todas las sesiones de <?= $etiqueta($v) ?>? Sus .blend se apartan a la papelera (30 días). La versión y su .blend se conservan.');">
                                        <?= csrf_field() ?>
                                        <button class="fv-btn fv-btn-sm"><i class="bi bi-trash3"></i>Purgar sesiones<?= $bs ? ' · ' . esc($tamanoLegible($bs)) : '' ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                    <p class="t3 fv-nota">Purgar quita las sesiones intermedias, no las versiones: el .blend de cada versión se conserva y puedes volver a cualquiera.</p>
                </div>
            </section>



            <section class="fv-panel" data-m="11" data-sec="stock">
                <div class="fv-trio fv-duo-cifras">
                    <a href="#" data-ir="stock"><span class="k">Por hacer</span><span class="v"><?= $porServir ?><small>uds</small></span><span class="s"><?= count($pedidosAbiertos) ?> pedido(s)</span></a>
                    <a href="#" data-ir="impresiones"><span class="k">Placas</span><span class="v"><?= count($placasDeLaPieza) ?></span>
                        <span class="fv-cuadros"><?php foreach (array_slice($placasDeLaPieza, 0, 12) as $entrada): ?><i class="<?= $cuadroPlaca[$entrada['placa']['veredicto'] ?? ''] ?? 'q-pend' ?>"></i><?php endforeach; ?></span></a>
                </div>
            </section>


            <section class="fv-panel" data-m="13" data-sec="pieza">
                <div class="fv-filas">
                    <details class="fv-mas">
                        <summary><i class="bi bi-box"></i>Compuesta de<span class="fv-mono t3 der"><?= count($componentes) ?></span><i class="bi bi-chevron-right ch"></i></summary>
                        <div class="fv-cuerpo">
                            <?php foreach ($componentes as $c): ?>
                                <?php $va = $c['variante']; $fa = $c['familia']; $vig = $c['vigente']; ?>
                                <div class="fv-linea">
                                    <?php if ($va && $fa): ?>
                                        <a href="<?= site_url('piezas/variante/' . (int) $va['id']) ?>"><?= esc($fa['nombre']) ?> / <?= esc($va['nombre']) ?><?= $vig ? ' <span class="fv-mono">v' . sprintf('%03d', (int) $vig['numero']) . '</span>' : '' ?></a>
                                        <?php if ($vig && $vig['estado'] === 'descartada'): ?><span class="fv-estado e-warn">la última se descartó</span><?php endif; ?>
                                    <?php else: ?>
                                        <span class="t3">(esa pieza ya no existe)</span>
                                    <?php endif; ?>
                                    <form method="post" class="der" action="<?= site_url('piezas/componente/' . (int) $c['id'] . '/borrar') ?>" onsubmit="return confirm('¿Quitar este componente?');">
                                        <?= csrf_field() ?><input type="hidden" name="variante_id" value="<?= $vid ?>">
                                        <button class="fv-ibtn" title="Quitar"><i class="bi bi-x"></i></button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!empty($versionesParaComponer)): ?>
                                <button class="fv-link" type="button" data-bs-toggle="modal" data-bs-target="#modalComponente"><i class="bi bi-plus-lg"></i> Añadir componente</button>
                            <?php endif; ?>
                        </div>
                    </details>
                    <details class="fv-mas">
                        <summary><i class="bi bi-terminal"></i>Desde tu máquina<i class="bi bi-chevron-right ch der"></i></summary>
                        <div class="fv-cuerpo plano">
                            <pre class="fv-cmd"><span>trackbitos bajar "<?= esc($refCli) ?>"</span><button class="fv-ibtn" type="button" data-copiar aria-label="Copiar"><i class="bi bi-copy"></i></button></pre>
                            <span class="t3">El trabajo se baja con el script, que es quien identifica la máquina. Aquí solo bajas el .blend de una versión cerrada, para mirarlo.</span>
                        </div>
                    </details>
                </div>
            </section>
        </div>

        <!-- ============ TRABAJO ============ -->
        <div class="fv-col fv-col-trabajo">
            <?php if (!empty($variante['advertencia'])): ?>
                <div class="fv-aviso" data-m="1" data-sec="trabajo">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?= esc($variante['advertencia']) ?></span>
                    <button class="fv-ibtn" type="button" data-bs-toggle="modal" data-bs-target="#modalTareas" aria-label="Editar advertencia"><i class="bi bi-pencil"></i></button>
                </div>
            <?php endif; ?>

            <?php
                $ultima     = $versiones[0] ?? null;
                $borrador   = null;
                $impresas   = [];
                foreach ($versiones as $vv) {
                    if ($vv['estado'] === 'borrador' && !$borrador) { $borrador = $vv; }
                    if ($vv['estado'] === 'impresa') { $impresas[] = $vv; }
                }
                $haySubida  = $rama && !empty($estado['ultima_subida']);
                $cmdBajar   = 'trackbitos bajar "' . $refCli . '"';
                $siguiente  = 'v' . sprintf('%03d', (int) ($ultima['numero'] ?? 0) + 1);

                if ($bloqueo) {
                    $est = 'sesion';
                } elseif (!empty($pendientes)) {
                    $est = 'descarga';
                } elseif ($impresas) {
                    $est = 'juzgar';
                } elseif ($haySubida) {
                    $est = 'promocionar';
                } elseif ($borrador) {
                    $est = 'imprimir';
                } elseif ($validada) {
                    $est = 'lista';
                } elseif ($ultima) {
                    $est = 'modelar';
                } elseif ($rama) {
                    $est = 'empezar';
                } else {
                    $est = 'parada';
                }
                $pasoActual = ['empezar' => 1, 'modelar' => 1, 'sesion' => 1, 'descarga' => 1, 'promocionar' => 2, 'imprimir' => 3, 'juzgar' => 4, 'lista' => 5, 'parada' => -1][$est];
                $tono = ['lista' => 'ok', 'juzgar' => 'juzga', 'sesion' => ($bloqueo['dias'] ?? 0) >= 3 ? 'bloq' : 'curso', 'descarga' => ($pendientes[0]['dias'] ?? 0) >= 3 ? 'bloq' : 'curso', 'parada' => 'calma'][$est] ?? '';
            ?>
            <section class="fv-panel fv-ahora <?= $tono ?>" data-m="3" data-sec="trabajo" id="sesiones-trabajo" data-estado="<?= $est ?>">
                <?php if ($est === 'sesion'): ?>
                    <span class="eti"><?= $tono === 'bloq' ? 'Sesión olvidada' : 'Trabajando' ?></span>
                    <h2>Sesión <?= (int) $bloqueo['numero'] ?> abierta en <?= esc($bloqueo['maquina']) ?></h2>
                    <p><?= $bloqueo['dias'] > 0 ? 'Desde hace ' . (int) $bloqueo['dias'] . ' día(s).' : 'Desde hoy.' ?> Cuando termines, sube y cierra desde esa máquina. Mientras tanto no se puede abrir otra ni promocionar.</p>
                    <pre class="fv-cmd"><span>trackbitos subir</span><button class="fv-ibtn" type="button" data-copiar aria-label="Copiar"><i class="bi bi-copy"></i></button></pre>
                    <?php if ($bloqueo['forzable']): ?>
                        <button class="fv-link" type="button" data-bs-toggle="modal" data-bs-target="#modalForzarSesion"><i class="bi bi-lock"></i> Esa copia ya no existe: forzar cierre</button>
                    <?php endif; ?>

                <?php elseif ($est === 'descarga'): ?>
                    <?php $p = $pendientes[0]; ?>
                    <span class="eti"><?= $tono === 'bloq' ? 'Copia olvidada' : 'Trabajando' ?></span>
                    <h2>Copia abierta en <?= esc($p['maquina']) ?></h2>
                    <p>Bajada el <?= esc($p['fecha']) ?><?= $p['dias'] > 0 ? ' (hace ' . (int) $p['dias'] . ' día(s))' : '' ?>. Ciérrala desde esa máquina con <code>trackbitos subir</code> o <code>trackbitos cerrar --sin-cambios</code>; bajar en otro equipo se llevaría ese trabajo.</p>
                    <?php foreach ($pendientes as $p): ?>
                        <button class="fv-link" type="button" data-bs-toggle="modal" data-bs-target="#modalForzar<?= $p['id'] ?>"><i class="bi bi-lock"></i> La copia de <?= esc($p['maquina']) ?> ya no existe: forzar cierre</button>
                    <?php endforeach; ?>

                <?php elseif ($est === 'juzgar'): ?>
                    <span class="eti">Ahora toca</span>
                    <h2><?= count($impresas) === 1 ? 'La ' . $etiqueta($impresas[0]) . ' está impresa. ¿Sirve?' : 'Hay ' . count($impresas) . ' versiones impresas sin juzgar' ?></h2>
                    <ol class="fv-pasos"><li class="hecho">Modelar</li><li class="hecho">Promocionar</li><li class="hecho">Imprimir</li><li class="actual">Juzgar</li><li>Lista</li></ol>
                    <p>La pieza queda parada hasta que digas si vale: no se puede abrir trabajo nuevo sobre algo que no sabes si funciona.</p>
                    <?php foreach ($impresas as $v): ?>
                        <div class="fv-campo">
                            <button class="fv-btn fv-btn-pri" type="button" data-bs-toggle="modal" data-bs-target="#modalValidar<?= (int) $v['id'] ?>"><i class="bi bi-check-lg"></i>Validar <?= $etiqueta($v) ?></button>
                            <button class="fv-btn" type="button" data-bs-toggle="modal" data-bs-target="#modalDescartar<?= (int) $v['id'] ?>"><i class="bi bi-x-lg"></i>Descartar</button>
                        </div>
                    <?php endforeach; ?>

                <?php elseif ($est === 'promocionar'): ?>
                    <span class="eti">Ahora toca</span>
                    <h2>Promocionar la sesión <?= (int) $estado['ultima_subida']['numero'] ?> como <?= $siguiente ?></h2>
                    <ol class="fv-pasos"><li class="hecho">Modelar</li><li class="actual">Promocionar</li><li>Imprimir</li><li>Juzgar</li><li>Lista</li></ol>
                    <?php if ($ultima): ?>
                        <span class="fv-sube"><i class="bi bi-arrow-up"></i>Mejoras de <b><?= $etiqueta($ultima) ?></b> a <b><?= $siguiente ?></b></span>
                    <?php endif; ?>
                    <?php if ($acciones['puede_promocionar']): ?>
                        <form method="post" id="formPromocionar" class="fv-campo" action="<?= site_url('piezas/variante/' . $vid . '/promocionar') ?>">
                            <?= csrf_field() ?>
                            <input class="fv-input" type="text" name="cambio" required
                                placeholder="<?= $primeraVersion ? 'Qué es esta pieza: primer modelado a partir de la foto…' : 'Qué se modificó: brazo 0.4 mm más grueso…' ?>">
                            <button class="fv-btn fv-btn-pri" type="<?= $conPautas ? 'button' : 'submit' ?>" <?= $conPautas ? 'data-bs-toggle="modal" data-bs-target="#modalPautasPromocion"' : '' ?>>Promocionar</button>
                        </form>
                    <?php else: ?>
                        <p><?= esc($acciones['motivos']['promocionar'] ?? '') ?></p>
                    <?php endif; ?>

                <?php elseif ($est === 'imprimir'): ?>
                    <span class="eti">Ahora toca</span>
                    <h2>Imprimir la <?= $etiqueta($borrador) ?> y ver si sirve</h2>
                    <ol class="fv-pasos"><li class="hecho">Modelar</li><li class="hecho">Promocionar</li><li class="actual">Imprimir</li><li>Juzgar</li><li>Lista</li></ol>
                    <p><?= esc($borrador['cambio']) ?></p>
                    <div class="fv-campo">
                        <button class="fv-btn fv-btn-pri" type="button" data-bs-toggle="modal" data-bs-target="#modalImpresa<?= (int) $borrador['id'] ?>"><i class="bi bi-printer"></i>Marcar impresa</button>
                        <button class="fv-btn" type="button" data-bs-toggle="modal" data-bs-target="#modalDescartar<?= (int) $borrador['id'] ?>"><i class="bi bi-x-lg"></i>Descartar</button>
                    </div>

                <?php elseif ($est === 'lista'): ?>
                    <span class="eti">Al día</span>
                    <h2>La <?= $etiqueta($validada) ?> es la buena. No hay nada pendiente.</h2>
                    <p>Validada <?= esc($fechaCorta($validada['promocionada_en'])) ?><?= $validada['resultado'] ? ' · ' . esc($validada['resultado']) : '' ?></p>
                    <?php if ($ultima && (int) $ultima['id'] !== (int) $validada['id']): ?>
                        <p class="t3">Después hubo intentos (<?= $etiqueta($ultima) ?>, <?= esc($nombreEstado($ultima['estado'])) ?>) que no la mejoraron.</p>
                    <?php endif; ?>
                    <?php if ($rama): ?>
                        <details class="fv-mas">
                            <summary><i class="bi bi-chevron-right ch"></i>¿Quieres mejorarla? Baja la pieza y sube una sesión nueva</summary>
                            <div class="fv-cuerpo plano"><pre class="fv-cmd"><span><?= esc($cmdBajar) ?></span><button class="fv-ibtn" type="button" data-copiar aria-label="Copiar"><i class="bi bi-copy"></i></button></pre></div>
                        </details>
                    <?php endif; ?>

                <?php elseif ($est === 'modelar'): ?>
                    <span class="eti">Ahora toca</span>
                    <h2>Seguir modelando: todavía no hay versión buena</h2>
                    <ol class="fv-pasos"><li class="actual">Modelar</li><li>Promocionar</li><li>Imprimir</li><li>Juzgar</li><li>Lista</li></ol>
                    <p>La última, <?= $etiqueta($ultima) ?>, está <?= esc($nombreEstado($ultima['estado'])) ?><?= $ultima['resultado'] ? ': ' . esc($ultima['resultado']) : '' ?>.<?= $rama ? '' : ' Vuelve a una versión para abrir trabajo.' ?></p>
                    <?php if ($rama): ?>
                        <pre class="fv-cmd"><span><?= esc($cmdBajar) ?></span><button class="fv-ibtn" type="button" data-copiar aria-label="Copiar"><i class="bi bi-copy"></i></button></pre>
                    <?php endif; ?>

                <?php elseif ($est === 'empezar'): ?>
                    <span class="eti">Ahora toca</span>
                    <h2>Primer modelado</h2>
                    <ol class="fv-pasos"><li class="actual">Modelar</li><li>Promocionar</li><li>Imprimir</li><li>Juzgar</li><li>Lista</li></ol>
                    <p>Baja la pieza, modélala y sube la primera sesión. Luego podrás promocionarla como v001.</p>
                    <pre class="fv-cmd"><span><?= esc($cmdBajar) ?></span><button class="fv-ibtn" type="button" data-copiar aria-label="Copiar"><i class="bi bi-copy"></i></button></pre>

                <?php else: ?>
                    <span class="eti">Sin trabajo abierto</span>
                    <h2>No hay rama de trabajo</h2>
                    <p>Para seguir modelando, vuelve a una versión o deriva una variante nueva.</p>
                <?php endif; ?>

                <?php if ($rama && in_array($est, ['sesion', 'descarga', 'promocionar', 'modelar', 'empezar'], true)): ?>
                    <details class="fv-mas">
                        <summary><i class="bi bi-chevron-right ch"></i>Rama <?= esc($ramaNombre) ?> · <?= count($sesiones) ?> sesión(es)</summary>
                        <div class="fv-cuerpo">
                            <div class="fv-linea"><span>Abierta el <?= esc($fechaCorta($rama['abierta_en'])) ?></span>
                                <?php if ($estado['hash_nube']): ?><span class="fv-mono t3 der user-select-all" title="Hash de la nube"><?= esc(substr($estado['hash_nube'], 0, 16)) ?>…</span><?php endif; ?></div>
                            <?php if ($estado['origen_descarga']): ?>
                                <div class="fv-linea"><span>Se parte de</span><span class="fv-mono der"><?= esc($estado['origen_descarga']['tipo']) ?> <?= $estado['origen_descarga']['tipo'] === 'version' ? 'v' . sprintf('%03d', (int) $estado['origen_descarga']['numero']) : (int) $estado['origen_descarga']['numero'] ?></span></div>
                            <?php endif; ?>
                            <div class="fv-sesiones"><?= $bloqueSesiones($sesiones, $vid, 'Sin sesiones en esta rama todavía.') ?></div>
                        </div>
                    </details>
                <?php endif; ?>
            </section>

            <section class="fv-panel" data-m="5" data-sec="trabajo">
                <div class="fv-ph"><h3>Tareas</h3><span class="cuenta" id="fvNTareas"><?= $nTareasCabecera ?></span>
                    <button class="fv-ibtn der" type="button" data-bs-toggle="modal" data-bs-target="#modalTareas" title="Editar tareas y advertencia"><i class="bi bi-pencil"></i></button></div>
                <div id="fvTareas">
                    <?php foreach (array_values($tareasLineas) as $i => $t): ?>
                        <label class="fv-check"><input type="checkbox" data-i="<?= $i ?>"><span><?= esc($t) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <form class="fv-nueva" id="fvNuevaTarea"><i class="bi bi-plus-lg"></i><input id="fvTareaTxt" placeholder="Añadir tarea" autocomplete="off" maxlength="200"></form>
                <form method="post" id="fvTareasForm" class="d-none" action="<?= site_url('piezas/variante/' . $vid . '/tareas') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="volver" value="ficha">
                    <input type="hidden" name="advertencia" value="<?= esc($variante['advertencia'] ?? '', 'attr') ?>">
                    <textarea name="tareas" id="fvTareasTxt"></textarea>
                </form>
                <script type="application/json" id="fvTareasDatos"><?= json_encode(array_values($tareasLineas), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
            </section>

            <section class="fv-panel" data-m="8" data-sec="versiones">
                <div class="fv-ph"><h3>Versiones</h3><span class="cuenta"><?= count($versiones) ?></span>
                    <?php if ($nDescartadas): ?><button class="fv-link der solo-todo" type="button" id="fvVerDesc">Mostrar descartadas (<?= $nDescartadas ?>)</button><?php endif; ?></div>
                <?php if (empty($versiones)): ?>
                    <p class="fv-vacio">Todavía no hay versiones. Sube una sesión desde el cliente y promociónala cuando merezca congelarse.</p>
                <?php else: ?>
                <ul class="fv-vlista" id="fvVersiones">
                    <?php foreach ($versiones as $v): ?>
                        <?php
                            $vId = (int) $v['id'];
                            $bytesSesV = $bytesSesionesDe($v['sesiones']['lista']);
                            $reutil = [];
                            foreach ($versiones as $otra) {
                                if ((int) $otra['id'] !== $vId) {
                                    $reutil = array_merge($reutil, $otra['renders']);
                                }
                            }
                            $reutil = array_merge($reutil, $rendersSueltos);
                        ?>
                        <li class="<?= $v['estado'] === 'descartada' ? 'desc-v' : '' ?><?= in_array($v['estado'], ['descartada', 'superada'], true) ? ' apagada' : '' ?>" id="version-<?= $vId ?>" <?= $v['estado'] === 'descartada' ? 'hidden' : '' ?>>
                            <details <?= $v['estado'] === 'validada' && count($versiones) <= 3 ? 'open' : '' ?>>
                                <summary>
                                    <span class="ver"><?= $etiqueta($v) ?></span>
                                    <span class="fv-estado <?= $colorEstado[$v['estado']] ?? 'e-neu' ?>"><?= esc($nombreEstado($v['estado'])) ?></span>
                                    <span class="lado">
                                        <span class="rap">
                                            <button class="fv-ibtn" type="button" title="Descargar .blend" data-bs-toggle="modal" data-bs-target="#modalDescargarBlend<?= $vId ?>"><i class="bi bi-download"></i></button>
                                            <button class="fv-ibtn" type="button" title="Derivar variante" data-bs-toggle="modal" data-bs-target="#modalDerivar<?= $vId ?>"><i class="bi bi-diagram-2"></i></button>
                                            <?php if ($puedeDevolver($v)): ?>
                                                <button class="fv-ibtn" type="button" title="Volver a trabajar desde esta versión" data-bs-toggle="modal" data-bs-target="#modalDevolver<?= $vId ?>"><i class="bi bi-arrow-counterclockwise"></i></button>
                                            <?php endif; ?>
                                        </span>
                                        <span class="fecha"><?= esc($fechaCorta($v['promocionada_en'])) ?></span>
                                    </span>
                                    <span class="desc"><?= esc($v['cambio']) ?></span>
                                </summary>
                                <div class="fv-vdet">
                                    <?php if ($v['pendiente_de_juicio']): ?>
                                        <div class="fv-alerta"><i class="bi bi-clock"></i>Lleva <?= (int) floor((time() - strtotime($v['promocionada_en'])) / 86400) ?> días sin resolverse. ¿Se imprimió? ¿Sirvió?</div>
                                    <?php endif; ?>
                                    <div class="fv-datos">
                                        <div><span class="k">Coste resina</span><span class="v"><?= !empty($v['resina']['aplica']) && $v['resina']['coste_eur'] !== null ? number_format((float) $v['resina']['coste_eur'], 2, ',', '.') . ' €' : '—' ?></span></div>
                                        <div><span class="k">.blend</span><span class="v"><?= $v['tamano_blend'] !== null ? esc($tamanoLegible($v['tamano_blend'])) : '—' ?></span></div>
                                        <div><span class="k">Sesiones</span><span class="v"><?= (int) $v['sesiones']['total'] ?><?= $bytesSesV ? ' · ' . esc($tamanoLegible($bytesSesV)) : '' ?></span></div>
                                    </div>
                                    <?php if (!empty($v['medidas']) || !empty($v['params_impresion']) || !empty($v['resultado'])): ?>
                                        <div class="fv-ficha">
                                            <?php if (!empty($v['medidas'])): ?><div><i class="bi bi-rulers"></i><?= esc($v['medidas']) ?></div><?php endif; ?>
                                            <?php if (!empty($v['params_impresion'])): ?><div><i class="bi bi-printer"></i><?= esc($v['params_impresion']) ?></div><?php endif; ?>
                                            <?php if (!empty($v['resultado'])): ?><div><i class="bi bi-clipboard-check"></i><?= esc($v['resultado']) ?></div><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="fv-linea">
                                        <div class="fv-mini">
                                            <?php foreach ($v['renders'] as $r): ?>
                                                <a class="fv-thumb" href="<?= imagen_pieza($r, 'render', 'v') ?>" target="_blank" data-foto-grande><img src="<?= imagen_pieza($r, 'render') ?>" alt="Render" loading="lazy"></a>
                                            <?php endforeach; ?>
                                            <button class="fv-thumb add" type="button" data-bs-toggle="modal" data-bs-target="#modalRender<?= $vId ?>" aria-label="Añadir render"><i class="bi bi-plus-lg"></i></button>
                                        </div>
                                        <?php if (empty($v['renders']) && !empty($reutil)): ?>
                                            <button class="fv-link der" type="button" data-bs-toggle="modal" data-bs-target="#modalReutilizarRender<?= $vId ?>">Reutilizar de otra versión</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fv-vbotones">
                                        <?php if ($v['estado'] === 'borrador'): ?>
                                            <button class="fv-btn fv-btn-sm fv-btn-pri" type="button" data-bs-toggle="modal" data-bs-target="#modalImpresa<?= $vId ?>"><i class="bi bi-printer"></i>Marcar impresa</button>
                                        <?php endif; ?>
                                        <?php if ($v['estado'] === 'impresa'): ?>
                                            <button class="fv-btn fv-btn-sm fv-btn-pri" type="button" data-bs-toggle="modal" data-bs-target="#modalValidar<?= $vId ?>"><i class="bi bi-check-lg"></i>Validar</button>
                                        <?php endif; ?>
                                        <?php if (in_array($v['estado'], ['borrador', 'impresa'], true)): ?>
                                            <button class="fv-btn fv-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalDescartar<?= $vId ?>"><i class="bi bi-x-lg"></i>Descartar</button>
                                        <?php endif; ?>
                                        <button class="fv-btn fv-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalDescargarBlend<?= $vId ?>"><i class="bi bi-download"></i>.blend</button>
                                        <button class="fv-btn fv-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalDerivar<?= $vId ?>"><i class="bi bi-diagram-2"></i>Derivar</button>
                                        <?php if ($puedeDevolver($v)): ?>
                                            <button class="fv-btn fv-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalDevolver<?= $vId ?>"><i class="bi bi-arrow-counterclockwise"></i>Volver a esta</button>
                                        <?php endif; ?>
                                        <?php if (in_array($v['estado'], ['impresa', 'descartada', 'validada'], true)): ?>
                                            <button class="fv-btn fv-btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalDeshacer<?= $vId ?>">Deshacer</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ((int) ($acciones['rama_desde_version_id'] ?? 0) === $vId): ?>
                                        <pre class="fv-cmd"><span>trackbitos bajar "<?= esc($refCli) ?>"</span><button class="fv-ibtn" type="button" data-copiar aria-label="Copiar"><i class="bi bi-copy"></i></button></pre>
                                    <?php endif; ?>
                                    <?php $motivos = $porQueNo($v); ?>
                                    <?php if ($motivos): ?>
                                        <details class="fv-porque"><summary>¿Por qué no aparecen otras acciones?</summary>
                                            <?php foreach ($motivos as $linea): ?><p><?= esc($linea) ?></p><?php endforeach; ?>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>



        </div>

        <!-- ============ SEGUIMIENTO ============ -->
        <div class="fv-col fv-col-seg">
            <?php if ($verBuena): ?>
                <div class="fv-duo" data-m="4" data-sec="versiones">
                    <button class="fv-btn fv-btn-pri" type="button" data-bs-toggle="modal" data-bs-target="#modalDescargarBlend<?= (int) $verBuena['id'] ?>">
                        <i class="bi bi-download"></i><span>Descargar .blend<small><?= $etiqueta($verBuena) ?><?= $verBuena['tamano_blend'] !== null ? ' · ' . $tamanoLegible($verBuena['tamano_blend']) : '' ?></small></span>
                    </button>
                    <button class="fv-btn" type="button" data-bs-toggle="modal" data-bs-target="#modalDerivar<?= (int) $verBuena['id'] ?>">
                        <i class="bi bi-diagram-2"></i><span>Derivar variante<small>desde <?= $etiqueta($verBuena) ?></small></span>
                    </button>
                </div>
            <?php endif; ?>
            <?php if (!empty($familia['notas']) || !empty($variante['notas'])): ?>
                <section class="fv-panel" data-m="6" data-sec="trabajo">
                    <div class="fv-ph"><h3>Notas</h3><button class="fv-ibtn der" type="button" data-bs-toggle="modal" data-bs-target="#modalSku" aria-label="Editar notas"><i class="bi bi-pencil"></i></button></div>
                    <div class="fv-notas">
                        <?php if (!empty($familia['notas'])): ?><div><div class="de">De la pieza</div><?= nl2br(esc($familia['notas'])) ?></div><?php endif; ?>
                        <?php if (!empty($variante['notas'])): ?><div><div class="de">De esta variante</div><?= nl2br(esc($variante['notas'])) ?></div><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="fv-panel fv-panel-stock" data-m="7" data-sec="stock">
                <div class="fv-ph"><h3>Stock</h3><a class="fv-link der" href="<?= site_url('piezas/existencias/' . $vid) ?>">Existencias <i class="bi bi-chevron-right"></i></a></div>
                <div class="fv-stock">
                    <div class="grande" style="color: <?= $colorStockFv ?>" title="Todas las unidades que existen de esta pieza"><?= (int) $existencias['stock'] ?><small>uds en total</small></div>
                    <dl class="fv-kv">
                        <div><dt>
                            <?php if ($existencias['hueco']): ?>
                                En su hueco <a class="fv-mono" href="<?= site_url('piezas/ubicaciones/huecos/' . $existencias['hueco']['id']) ?>"><?= esc($existencias['hueco']['codigo']) ?></a>
                            <?php else: ?>Sin ubicación<?php endif; ?>
                        </dt><dd><?= (int) $existencias['desglose']['hueco'] ?></dd></div>
                        <div><dt><a href="<?= site_url('piezas/ubicaciones/cajas') ?>">Caja de impresas</a></dt><dd><?= (int) $existencias['desglose']['impresas'] ?></dd></div>
                        <div><dt><a href="<?= site_url('piezas/ubicaciones/cajas') ?>">Caja de pedido</a></dt><dd><?= (int) $existencias['desglose']['pedido'] ?></dd></div>
                        <div><dt>Mínimo</dt><dd><?= $existencias['minimo'] > 0 ? (int) $existencias['minimo'] : '—' ?></dd></div>
                    </dl>
                    <div class="fv-nivel">
                        <i style="width: <?= min(100, round($existencias['stock'] * 100 / $stockMax)) ?>%; background: <?= $colorStockFv ?>"></i>
                        <?php if ($existencias['minimo'] > 0): ?><b style="left: <?= round($existencias['minimo'] * 100 / $stockMax) ?>%"></b><?php endif; ?>
                    </div>
                    <?php if ($existencias['estado'] === 'bajo'): ?><div class="fv-aviso-mini">Por debajo del stock mínimo.</div><?php endif; ?>
                </div>
                <?php helper('piezas_stock'); $nombreMovStock = trim(($familia['nombre'] ?? '') . ' ' . ($variante['nombre'] ?? '')); ?>
                <div class="fv-duo" style="margin-top: 14px">
                    <button type="button" class="fv-btn"<?= stock_mov_attrs($vid, $nombreMovStock, $existencias['desglose'], 'alta', null, (bool) $existencias['hueco']) ?>><i class="bi bi-plus-lg e-ok"></i><span>Dar de alta</span></button>
                    <button type="button" class="fv-btn"<?= stock_mov_attrs($vid, $nombreMovStock, $existencias['desglose'], 'baja', null, (bool) $existencias['hueco']) ?>><i class="bi bi-dash-lg e-bad"></i><span>Dar de baja</span></button>
                </div>
            </section>


            <section class="fv-panel" data-m="9" data-sec="stock">
                <div class="fv-ph"><h3>Pedidos</h3><span class="cuenta"><?= count($pedidosAbiertos) ?> abiertos</span>
                    <?php if (count($pedidosDeLaPieza) > count($pedidosAbiertos)): ?><a class="fv-link der solo-todo" href="#" data-ir="stock">Todos (<?= count($pedidosDeLaPieza) ?>) <i class="bi bi-chevron-right"></i></a><?php endif; ?></div>
                <?php if (empty($pedidosDeLaPieza)): ?>
                    <p class="fv-vacio">Esta pieza no aparece en ningún pedido.</p>
                <?php else: ?>
                    <?php if (empty($pedidosAbiertos)): ?><p class="fv-vacio solo-todo">Ninguno abierto ahora mismo.</p><?php endif; ?>
                    <div class="fv-plista">
                        <?php foreach ($pedidosDeLaPieza as $pl): ?>
                            <?php $abierto = in_array($pl['estado'], \App\Models\PiezaPedidoModel::ABIERTOS, true); ?>
                            <a href="<?= site_url('piezas/pedido/' . (int) $pl['id']) ?>" class="<?= $abierto ? '' : 'extra' ?>">
                                <span class="q <?= $coloresPedidoFv[$pl['estado']] ?? 'e-neu' ?>"></span>
                                <span class="n"><span class="fv-mono">#<?= (int) $pl['id'] ?></span><?= esc($pl['referencia_externa'] ?: $pl['origen']) ?></span>
                                <span class="r <?= $coloresPedidoFv[$pl['estado']] ?? 'e-neu' ?>"><?= esc($etiquetasPedido[$pl['estado']] ?? $pl['estado']) ?></span>
                                <span class="s"><?= (int) $pl['cantidad_completada'] ?> de <?= (int) $pl['cantidad'] ?> hechas</span>
                                <span class="f"><?= esc($fechaCorta($pl['creado_en'])) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="fv-panel" data-m="10" data-sec="impresiones" id="capturas">
                <div class="fv-ph"><h3>Impresiones</h3><span class="cuenta"><?= count($placasDeLaPieza) ?></span>
                    <?php if (count($placasDeLaPieza) > 4): ?><a class="fv-link der solo-todo" href="#" data-ir="impresiones">Todas <i class="bi bi-chevron-right"></i></a><?php endif; ?></div>
                <?php if (empty($placasDeLaPieza)): ?>
                    <p class="fv-vacio">Todavía no se ha impreso en ninguna placa.</p>
                <?php else: ?>
                    <div class="fv-placas">
                        <?php foreach ($placasDeLaPieza as $i => $entrada): ?>
                            <?php
                                $p = $entrada['placa'];
                                $ver = $p['veredicto'] ?? null;
                                $nImg = array_sum(array_map(static fn($x) => count($x['imagenes']), $entrada['versiones']));
                            ?>
                            <div class="fv-placa<?= $i >= 4 ? ' extra' : '' ?>">
                                <div class="fv-placa-fila">
                                    <span class="q <?= $cuadroPlaca[$ver] ?? 'q-pend' ?>"></span>
                                    <a class="n" href="<?= site_url('piezas/placa/' . (int) $p['id'] . '/bitacora/editar') ?>"><?= esc($p['nombre']) ?>
                                        <span class="fv-mono"><?php foreach ($entrada['versiones'] as $j => $x): ?><?= $j ? ', ' : '' ?>v<?= sprintf('%03d', $x['numero']) ?><?= $x['cantidad'] > 1 ? ' ×' . $x['cantidad'] : '' ?><?php endforeach; ?></span></a>
                                    <span class="r <?= $colorPlaca[$ver] ?? 't3' ?>"><?= esc($ver && isset($veredictosPlaca[$ver]) ? $veredictosPlaca[$ver] : 'Sin juzgar') ?></span>
                                    <button class="fv-ibtn" type="button" data-bs-toggle="collapse" data-bs-target=".capturas-placa-<?= (int) $p['id'] ?>" title="Capturas de esta impresión">
                                        <i class="bi bi-camera"></i><?php if ($nImg): ?><small><?= $nImg ?></small><?php endif; ?></button>
                                    <span class="f"><?= esc($fechaCorta($p['impresa_en'] ?: $p['creado_en'])) ?></span>
                                </div>
                                <?php foreach ($entrada['versiones'] as $v): ?>
                                    <div class="collapse capturas-placa-<?= (int) $p['id'] ?>" id="capturas-<?= (int) $v['fila_id'] ?>">
                                        <div class="fv-capturas">
                                            <?php if (count($entrada['versiones']) > 1): ?><div class="t3">v<?= sprintf('%03d', $v['numero']) ?><?= $v['notas'] ? ' · ' . esc($v['notas']) : '' ?></div><?php endif; ?>
                                            <?php if ($v['imagenes'] !== []): ?>
                                                <div class="fv-mini">
                                                    <?php foreach ($v['imagenes'] as $img): ?>
                                                        <div class="fv-thumb">
                                                            <a href="<?= imagen_pieza($img, 'placa-version-imagen', 'v') ?>" target="_blank" data-foto-grande title="<?= esc($img['notas'] ?? '', 'attr') ?>"><img src="<?= imagen_pieza($img, 'placa-version-imagen') ?>" alt="Captura" loading="lazy"></a>
                                                            <?php if (!empty($img['resultado'])): ?><span class="fv-tag r-<?= esc($img['resultado'], 'attr') ?>"><?= esc($resultados[$img['resultado']] ?? $img['resultado']) ?></span><?php endif; ?>
                                                            <form method="post" action="<?= site_url('piezas/placa-version-imagen/' . (int) $img['id'] . '/borrar') ?>" onsubmit="return confirm('¿Apartar esta foto a la papelera?');">
                                                                <?= csrf_field() ?><button class="fv-x" title="Borrar"><i class="bi bi-x"></i></button>
                                                            </form>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <form method="post" enctype="multipart/form-data" class="fv-subir-captura" action="<?= site_url('piezas/placa-version/' . (int) $v['fila_id'] . '/imagen') ?>">
                                                <?= csrf_field() ?>
                                                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="fv-input" required>
                                                <input type="text" name="notas" class="fv-input" maxlength="150" placeholder="Cómo estaba puesta">
                                                <select name="resultado" class="fv-input">
                                                    <option value="">Sin juzgar</option>
                                                    <?php foreach ($resultados as $clave => $texto): ?><option value="<?= esc($clave, 'attr') ?>"><?= esc($texto) ?></option><?php endforeach; ?>
                                                </select>
                                                <button class="fv-btn fv-btn-sm"><i class="bi bi-upload"></i>Subir</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>
</div>

<script>
(function () {
    var raiz = document.getElementById('fv');
    var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || raiz).querySelectorAll(sel)); };

    $$('#fvSubtabs button').forEach(function (b) {
        b.addEventListener('click', function () {
            $$('#fvSubtabs button').forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
            $$('.fv-thumbs[data-g]').forEach(function (t) { t.hidden = t.dataset.g !== b.dataset.g; });
        });
    });

    $$('.rap .fv-ibtn').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); }); });

    $$('[data-copiar]').forEach(function (b) {
        b.addEventListener('click', function () {
            var txt = b.parentElement.querySelector('span').textContent;
            if (navigator.clipboard) navigator.clipboard.writeText(txt).then(function () {
                b.innerHTML = '<i class="bi bi-check-lg"></i>';
                setTimeout(function () { b.innerHTML = '<i class="bi bi-copy"></i>'; }, 1500);
            });
        });
    });

    var verDesc = false;
    var botonDesc = document.getElementById('fvVerDesc');
    function descartadas(si) {
        verDesc = si;
        $$('#fvVersiones .desc-v').forEach(function (li) { li.hidden = !si; });
        if (botonDesc) botonDesc.textContent = si ? 'Ocultar descartadas' : botonDesc.dataset.txt;
    }
    if (botonDesc) { botonDesc.dataset.txt = botonDesc.textContent; botonDesc.addEventListener('click', function () { descartadas(!verDesc); }); }

    var tareas = JSON.parse(document.getElementById('fvTareasDatos').textContent || '[]');
    var formTareas = document.getElementById('fvTareasForm');
    function guardarTareas(lista) {
        document.getElementById('fvTareasTxt').value = lista.join('\n');
        formTareas.submit();
    }
    var pendiente = null;
    document.getElementById('fvTareas').addEventListener('change', function (e) {
        var cb = e.target;
        if (!cb.checked) return;
        var fila = cb.closest('.fv-check');
        fila.classList.add('hecha');
        var d = document.createElement('button');
        d.type = 'button'; d.className = 'deshacer'; d.textContent = 'Deshacer';
        fila.appendChild(d);
        var n = document.getElementById('fvNTareas');
        n.textContent = Math.max(0, parseInt(n.textContent || '0', 10) - 1);
        if (pendiente) clearTimeout(pendiente);
        pendiente = setTimeout(function () {
            var hechas = $$('#fvTareas .fv-check.hecha input').map(function (x) { return parseInt(x.dataset.i, 10); });
            guardarTareas(tareas.filter(function (_, i) { return hechas.indexOf(i) === -1; }));
        }, 4000);
        d.addEventListener('click', function (ev) {
            ev.preventDefault();
            fila.classList.remove('hecha'); cb.checked = false; d.remove();
            n.textContent = parseInt(n.textContent || '0', 10) + 1;
            if (!$$('#fvTareas .fv-check.hecha').length && pendiente) { clearTimeout(pendiente); pendiente = null; }
        });
    });
    document.getElementById('fvNuevaTarea').addEventListener('submit', function (e) {
        e.preventDefault();
        var v = document.getElementById('fvTareaTxt').value.trim();
        if (!v) return;
        var hechas = $$('#fvTareas .fv-check.hecha input').map(function (x) { return parseInt(x.dataset.i, 10); });
        guardarTareas(tareas.filter(function (_, i) { return hechas.indexOf(i) === -1; }).concat([v]));
    });

    function seccion(s) {
        $$('#fvSecs button').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.s === s); });
        raiz.classList.toggle('sec', s !== 'todo');
        $$('[data-sec]').forEach(function (el) { el.classList.toggle('on', el.dataset.sec === s); });
        var bloques = $$('[data-sec].on').filter(function (el) { return !el.matches('.fv-aviso, .fv-duo'); });
        raiz.classList.toggle('sec-solo', bloques.length <= 1);
        raiz.classList.toggle('sec-tres', bloques.length >= 3);
        if (s === 'versiones') { $$('#fvVersiones .desc-v').forEach(function (li) { li.hidden = false; }); }
        else descartadas(verDesc);
        try { history.replaceState(null, '', s === 'todo' ? location.pathname + location.search : '#' + s); } catch (e) {}
        window.scrollTo({ top: raiz.offsetTop - 12, behavior: 'smooth' });
    }
    $$('#fvSecs button').forEach(function (b) { b.addEventListener('click', function () { seccion(b.dataset.s); }); });
    $$('[data-ir]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); seccion(a.dataset.ir); }); });
    var inicial = location.hash.slice(1);
    if (inicial === 'capturas') inicial = 'impresiones';
    if (inicial === 'sesiones-trabajo') inicial = 'trabajo';
    if (/^version-\d+$/.test(inicial)) {
        var li = document.getElementById(inicial);
        if (li) { li.hidden = false; var det = li.querySelector('details'); if (det) det.open = true; }
    } else if ($$('#fvSecs button').some(function (b) { return b.dataset.s === inicial; })) {
        seccion(inicial);
    }
})();
</script>

<?= $this->include('piezas/_modal_mov_stock') ?>

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
<?php if ($hermanasVivas > 0): ?>
<div class="modal fade" id="modalIndependizar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/independizar') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-box-arrow-right"></i> Independizar <?= esc($familia['nombre']) ?> / <?= esc($variante['nombre']) ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    La variante sale de <strong><?= esc($familia['nombre']) ?></strong> y pasa a ser una pieza propia,
                    con su historial entero (versiones, SKU, existencias). Dentro de ella se llamará <code>base</code>.
                    Sigue constando de qué versión se derivó.
                </p>

                <label class="form-label small mb-1">Nombre de la pieza nueva</label>
                <input type="text" name="nombre" class="form-control form-control-sm mb-3"
                    value="<?= esc(old('nombre', $variante['nombre']), 'attr') ?>" maxlength="150" required>

                <label class="form-label small mb-1">Categoría</label>
                <select name="categoria_id" class="form-select form-select-sm mb-3">
                    <option value="">(sin categoría)</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) ($familia['categoria_id'] ?? 0) ? 'selected' : '' ?>>
                            <?= esc($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($descendientes): ?>
                    <label class="form-label small mb-1">Derivadas de ella que se van también</label>
                    <?php foreach ($descendientes as $d): ?>
                        <div class="form-check small">
                            <input class="form-check-input" type="checkbox" name="arrastrar[]" value="<?= (int) $d['id'] ?>"
                                id="arrastrar<?= (int) $d['id'] ?>" checked>
                            <label class="form-check-label" for="arrastrar<?= (int) $d['id'] ?>"><?= esc($d['nombre']) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <p class="small text-muted mt-1 mb-0">Las que desmarques se quedan en <?= esc($familia['nombre']) ?>.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Independizar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<div class="modal fade" id="modalTareas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-card-checklist"></i> Tareas y advertencia — <?= esc($familia['nombre']) ?> / <?= esc($variante['nombre']) ?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
            </div>
        </div>
    </div>
</div>
<?php if ($bloqueo && $bloqueo['forzable']): ?>
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
<?php foreach ($pendientes as $p): ?>
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
<?php foreach ($versiones as $v): ?>
<?php
    $rendersReutilizables = [];
    foreach ($versiones as $otraVersion) {
        if ((int) $otraVersion['id'] === (int) $v['id']) { continue; }
        foreach ($otraVersion['renders'] as $rReutil) { $rendersReutilizables[] = $rReutil + ['_origen' => $etiqueta($otraVersion)]; }
    }
    foreach ($rendersSueltos as $rReutil) { $rendersReutilizables[] = $rReutil + ['_origen' => 'suelto']; }
?>
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
        <div class="modal fade" id="modalReferencia" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <form method="post" enctype="multipart/form-data"
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

                <?php if (!empty($imagenesDePedidos)): ?>
                    <!-- O copiarla de un pedido donde esta variante va como línea directa:
                         form aparte (sin multipart) para que no lo coja el progreso de subida. -->
                    <form method="post" class="border-top"
                        action="<?= site_url('piezas/variante/' . (int) $variante['id'] . '/referencia-desde-pedido') ?>">
                        <?= csrf_field() ?>
                        <div class="modal-body">
                            <label class="form-label small mb-1">O cópiala de sus pedidos</label>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php foreach ($imagenesDePedidos as $img): ?>
                                    <label class="position-relative <?= $img['ya_copiada'] ? 'opacity-50' : '' ?>" style="width: 72px; cursor: pointer;"
                                        title="<?= $img['ya_copiada'] ? 'Ya está entre las referencias' : esc('Pedido #' . (int) $img['pedido_id'] . ($img['notas'] ? ' — ' . $img['notas'] : '')) ?>">
                                        <img src="<?= imagen_pieza($img, 'pedido-imagen') ?>"
                                            class="rounded border" style="width: 72px; height: 72px; object-fit: cover;"
                                            alt="Imagen del pedido" loading="lazy">
                                        <input type="checkbox" name="imagenes[]" value="<?= (int) $img['id'] ?>"
                                            class="form-check-input position-absolute top-0 start-0 m-1"
                                            <?= $img['ya_copiada'] ? 'disabled' : '' ?>>
                                        <span class="badge text-bg-dark position-absolute bottom-0 end-0 m-1 fw-normal opacity-75" style="font-size: .6rem;">#<?= (int) $img['pedido_id'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-sm btn-outline-primary">Copiar elegidas</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                </div>
            </div>
        </div>
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

<div class="modal fade" id="modalFotoGrande" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-black border-0" data-bs-dismiss="modal" style="cursor: zoom-out;">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" style="z-index: 1;" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            <div class="d-flex align-items-center justify-content-center h-100 p-2">
                <img data-foto-grande-img alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('modalFotoGrande');
    var img = modal.querySelector('[data-foto-grande-img]');
    modal.addEventListener('hidden.bs.modal', function () { img.removeAttribute('src'); });
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-foto-grande]');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        img.src = a.href;
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
})();
</script>

<?= $this->endSection() ?>