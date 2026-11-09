<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-revisar.css') ?>?v=2">

<input type="hidden" id="revisarCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<?php
$botones = static function (array $f): string {
    $id = (int) $f['id'];
    if ($f['estado'] === 'borrador') {
        return '<button type="button" class="fv-btn fv-btn-sm rv-b-imp" data-accion="impresa" data-version="' . $id . '"><i class="bi bi-printer"></i> Marcar impresa</button>'
            . '<button type="button" class="fv-btn fv-btn-sm fv-btn-peligro" data-accion="descartar" data-version="' . $id . '">Descartar</button>';
    }

    return '<button type="button" class="fv-btn fv-btn-sm rv-b-ok" data-accion="validar" data-version="' . $id . '"><i class="bi bi-check-lg"></i> Validar</button>'
        . '<button type="button" class="fv-btn fv-btn-sm fv-btn-peligro" data-accion="descartar" data-version="' . $id . '">Descartar</button>'
        . '<button type="button" class="fv-ibtn" data-accion="deshacer" data-version="' . $id . '" title="Me equivoqué de botón: vuelve a borrador">'
        . '<i class="bi bi-arrow-counterclockwise"></i></button>';
};

$etiquetaFila = static fn(array $f): string => $f['familia'] . ' / ' . $f['variante'] . ' v' . sprintf('%03d', (int) $f['numero']);

$botonTareas = static function (array $f): string {
    $lineas = array_filter(
        array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($f['tareas'] ?? ''))),
        static fn($l) => $l !== ''
    );
    $n        = count($lineas);
    $tieneAdv = trim((string) ($f['advertencia'] ?? '')) !== '';

    return '<button type="button" class="fv-gbtn rv-tareas' . ($n || $tieneAdv ? ' on' : '') . '"'
        . ' data-tareas-boton data-variante="' . (int) $f['variante_id'] . '"'
        . ' data-accion="' . site_url('piezas/variante/' . (int) $f['variante_id'] . '/tareas') . '"'
        . ' data-nombre="' . esc($f['familia'] . ' / ' . $f['variante'], 'attr') . '"'
        . ' data-tareas="' . esc((string) ($f['tareas'] ?? ''), 'attr') . '"'
        . ' data-advertencia="' . esc((string) ($f['advertencia'] ?? ''), 'attr') . '"'
        . ' title="Tareas pendientes y advertencia de esta pieza — se apuntan aquí y salen en el índice">'
        . '<i class="bi bi-card-checklist"></i> Tareas' . ($n ? ' <span class="n">' . $n . '</span>' : '')
        . '</button>';
};

$secciones = ['impresa' => [], 'borrador' => []];
foreach ($grupos as $g) {
    $secciones[$g['estado']][] = $g;
}
$titulos = [
    'impresa'  => ['Impresas · pendientes de juicio', 'e-info'],
    'borrador' => ['En borrador · para imprimir', 'e-warn'],
];
$nImpresas = count(array_filter($filas, static fn($f) => $f['estado'] === 'impresa'));
$nBorrador = count($filas) - $nImpresas;
$nOlvidadas = count(array_filter($filas, static fn($f) => !empty($f['olvidada'])));
?>

<div class="fv rv" id="rv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Revisar impresiones</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/galeria') ?>" class="fv-gbtn"><i class="bi bi-grid-3x3-gap"></i> Galería</a>
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/revisar?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Revisar impresiones</h1>
        <div class="fv-meta">
            <span class="fv-estado e-info"><?= $nImpresas ?> impresas por juzgar</span>
            <span class="fv-estado e-warn"><?= $nBorrador ?> en borrador</span>
            <?php if ($nOlvidadas): ?><span class="fv-estado e-bad"><?= $nOlvidadas ?> llevan demasiado sin resolverse</span><?php endif; ?>
            <span class="fv-dot"></span>
            <span><b><?= count($grupos) ?></b> piezas</span>
        </div>
    </div>

    <details class="fv-mas rv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Qué sale aquí?</summary>
        <div class="fv-cuerpo">
            <p>Todas las versiones sin juzgar de cualquier pieza: las que están <b>en borrador</b> (para imprimir) y las <b>impresas</b> pendientes de validar o descartar. Los botones son los mismos que en la ficha de cada pieza.</p>
            <p>Las impresas van arriba: mientras una siga sin juzgar bloquea el trabajo nuevo de esa pieza. Al <b>validar</b> una, las demás sin juzgar de la misma pieza se descartan a la vez.</p>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if (!empty($atrasados)): ?>
        <form method="post" action="<?= site_url('piezas/revisar/descartar-atrasados') ?>" class="fv-aviso rv-atrasados"
            onsubmit="return confirm(<?= esc(json_encode(
                "Se descartarán como \"superada en edición\":\n\n" . implode("\n", $atrasados)
                . "\n\nLas que están en alguna placa no se tocan. Se puede deshacer una a una.",
                JSON_UNESCAPED_UNICODE
            ), 'attr') ?>);">
            <?= csrf_field() ?>
            <i class="bi bi-clock-history"></i>
            <span><b><?= count($atrasados) ?></b> borrador(es) atrasado(s): tienen una versión más nueva en su pieza y no están en ninguna placa.</span>
            <button type="submit" class="fv-btn fv-btn-sm fv-btn-peligro"><i class="bi bi-trash3"></i> Descartar atrasados</button>
        </form>
    <?php endif; ?>

    <div id="mensajesRevisar"></div>

    <?php if (empty($filas)): ?>
        <p class="fv-vacio" id="revisarVacio">No queda nada por revisar: ninguna pieza tiene una versión en borrador ni impresa sin juzgar.</p>
    <?php else: ?>
        <div id="barraLote" class="d-none rv-lote">
            <span><b id="loteCuenta">0</b> seleccionada(s)</span>
            <button type="button" class="fv-btn fv-btn-sm fv-btn-pri" id="btnLoteImpresas"><i class="bi bi-printer"></i> Marcar impresas</button>
            <button type="button" class="fv-gbtn" id="btnLoteLimpiar">Quitar selección</button>
        </div>

        <?php foreach ($secciones as $estado => $deLaSeccion): ?>
            <section data-seccion="<?= $estado ?>" class="rv-sec <?= $deLaSeccion === [] ? 'd-none' : '' ?>">
                <div class="rv-sec-h" data-cabecera>
                    <span class="fv-estado <?= $titulos[$estado][1] ?>"><span><?= esc($titulos[$estado][0]) ?></span></span>
                    <?php if ($estado === 'borrador'): ?>
                        <button type="button" class="fv-gbtn der" id="btnLoteTodas" title="Marca todas las versiones en borrador para pasarlas a impresas de una vez"><i class="bi bi-check2-all"></i> <span>Seleccionar todas</span></button>
                    <?php endif; ?>
                </div>
                <div class="rv-grupos" data-cuerpo>
                    <?php foreach ($deLaSeccion as $g): ?>
                        <div class="fv-panel rv-grupo" data-grupo data-variante="<?= (int) $g['variante_id'] ?>">
                            <div class="rv-gh">
                                <?php if (!empty($g['render'])): ?>
                                    <img src="<?= imagen_pieza($g['render'], 'render') ?>" alt="" class="rv-th">
                                <?php else: ?>
                                    <span class="rv-th vacia"><i class="bi bi-box"></i></span>
                                <?php endif; ?>
                                <div class="rv-gt">
                                    <a href="<?= site_url('piezas/variante/' . (int) $g['variante_id']) ?>" class="rv-nom"><?= esc($g['familia']) ?> <span>·</span> <?= esc($g['variante']) ?></a>
                                    <span class="rv-cuenta" data-cuenta-versiones><?= count($g['versiones']) ?> sin juzgar</span>
                                </div>
                                <?= $botonTareas($g['versiones'][0]) ?>
                            </div>
                            <div class="rv-versiones" data-versiones>
                                <?php foreach ($g['versiones'] as $f): ?>
                                    <?php $motivoSugerido = !empty($f['superada_por_validada'])
                                        ? 'La v' . sprintf('%03d', (int) $f['superada_por_validada']) . ' de esta pieza ya está validada; esta impresa se quedó sin juzgar.'
                                        : ''; ?>
                                    <div class="rv-fila<?= !empty($f['superada_por_validada']) ? ' rv-superada' : '' ?>"
                                        data-fila data-version="<?= (int) $f['id'] ?>" data-estado="<?= esc($f['estado']) ?>"
                                        data-variante="<?= (int) $f['variante_id'] ?>" data-numero="<?= (int) $f['numero'] ?>"
                                        data-label="<?= esc($etiquetaFila($f), 'attr') ?>"
                                        <?= $motivoSugerido !== '' ? 'data-motivo-sugerido="' . esc($motivoSugerido, 'attr') . '"' : '' ?>>
                                        <div class="rv-cuerpo-fila" data-cuerpo-fila>
                                            <?php if ($f['estado'] === 'borrador'): ?>
                                                <label class="rv-check"><input type="checkbox" data-marcar value="<?= (int) $f['id'] ?>" aria-label="Seleccionar para marcar impresa en lote"></label>
                                            <?php endif; ?>

                                            <div class="rv-info">
                                                <div class="rv-cab" data-cabeza-fila>
                                                    <a href="<?= site_url('piezas/variante/' . (int) $f['variante_id']) ?>#version-<?= (int) $f['id'] ?>" class="rv-v fv-mono">v<?= sprintf('%03d', (int) $f['numero']) ?></a>
                                                    <span class="fv-estado <?= $f['estado'] === 'impresa' ? 'e-info' : 'e-warn' ?>" data-badge-estado><?= $f['estado'] === 'impresa' ? 'impresa' : 'borrador' ?></span>
                                                    <span class="rv-dias<?= !empty($f['olvidada']) ? ' olvidada' : '' ?>" data-antiguedad title="Promocionada hace <?= (int) $f['dias'] ?> día(s)<?= !empty($f['olvidada']) ? ' · lleva demasiado sin resolverse' : '' ?>">
                                                        <i class="bi bi-clock-history"></i> <?= (int) $f['dias'] ?> d<?= !empty($f['olvidada']) ? ' · olvidada' : '' ?>
                                                    </span>
                                                </div>

                                                <?php if (!empty($f['superada_por_validada'])): ?>
                                                    <div class="rv-aviso" data-aviso-superada>
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                        <span>La <b>v<?= sprintf('%03d', (int) $f['superada_por_validada']) ?></b> de esta pieza ya está validada: otra iteración quedó como la buena y esta se dejó impresa sin juzgar. Puedes descartarla directamente; el motivo viene ya escrito.</span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($f['cambio'])): ?>
                                                    <p class="rv-cambio"><?= esc($f['cambio']) ?></p>
                                                <?php endif; ?>

                                                <div class="rv-meta">
                                                    <?php if (!empty($f['placas'])): ?>
                                                        <span><i class="bi bi-printer"></i> <?= esc(implode(', ', array_map(static fn($p) => $p['nombre'], $f['placas']))) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($f['estado'] === 'borrador' && $f['stls'] > 0): ?>
                                                        <span><i class="bi bi-box"></i> <?= (int) $f['stls'] ?> STL</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($f['medidas'])): ?>
                                                        <span><i class="bi bi-rulers"></i> <?= esc($f['medidas']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($f['params_impresion'])): ?>
                                                        <span><i class="bi bi-sliders"></i> <?= esc($f['params_impresion']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($f['resultado'])): ?>
                                                        <span><i class="bi bi-clipboard-check"></i> <?= esc($f['resultado']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="rv-botones" data-botones>
                                                <?= $botones($f) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalRevisar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formRevisar">
            <div class="modal-header">
                <h6 class="modal-title"><span id="revisarTitulo">Marcar impresa</span> — <span id="revisarLabel" class="text-muted"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2" id="revisarAyuda"></p>

                <div data-campo="params_impresion" class="d-none">
                    <label class="form-label small">Parámetros de impresión</label>
                    <textarea name="params_impresion" class="form-control form-control-sm" rows="2"
                        placeholder="exposición 2.4s, capa 0.05mm, 5 capas base, borde derecho, inclinada 45°"></textarea>
                </div>

                <div data-campo="resultado" class="d-none">
                    <label class="form-label small" id="revisarEtiquetaResultado">Resultado</label>
                    <textarea name="resultado" class="form-control form-control-sm" rows="2"
                        placeholder="Encaja con el clic original, sin holgura"></textarea>
                </div>

                <!-- Solo al validar: las demás versiones sin juzgar de la misma
                     pieza, marcadas para descartarse a la vez. -->
                <div id="revisarHermanas" class="d-none mt-3">
                    <label class="form-label small mb-1">Descartar a la vez las demás de esta pieza</label>
                    <div id="revisarHermanasLista"></div>
                    <p class="small text-muted mb-0 mt-1">
                        Se descartan con un motivo automático ("descartada al validar la vXXX"). Desmarca la que
                        quieras dejar sin juzgar.
                    </p>
                </div>

                <div class="alert alert-danger py-2 mt-2 d-none" id="revisarError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary" id="revisarConfirmar">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalTareasRevisar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formTareasRevisar">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-card-checklist"></i> Tareas — <span id="tareasRevisarNombre" class="fw-normal text-muted"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small mb-1" for="tareasRevisarAdvertencia">Advertencia (opcional)</label>
                <input type="text" id="tareasRevisarAdvertencia" name="advertencia" maxlength="255"
                    class="form-control form-control-sm mb-2"
                    placeholder="Funciona pero no es perfecta: la pega en una línea">
                <label class="form-label small mb-1" for="tareasRevisarTexto">Tareas pendientes</label>
                <textarea id="tareasRevisarTexto" name="tareas" rows="6" class="form-control form-control-sm"
                    placeholder="Una por línea:&#10;rehacer los soportes de la base&#10;bajar la escala un 5%"></textarea>
                <p class="text-muted small mb-0">Una tarea por línea. Salen también en el índice de piezas.</p>
                <div class="alert alert-danger py-2 mt-2 d-none" id="tareasRevisarError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary" id="tareasRevisarConfirmar">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalLoteImpresas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="formLoteImpresas">
            <div class="modal-header">
                <h6 class="modal-title">Marcar impresas <span id="loteTitulo" class="text-muted"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Los mismos parámetros para todas las seleccionadas — lo normal cuando salen de la misma placa.
                    Si alguna llevó algo distinto, márcala aparte desde su fila.
                </p>
                <label class="form-label small">Parámetros de impresión</label>
                <textarea name="params_impresion" class="form-control form-control-sm" rows="2"
                    placeholder="exposición 2.4s, capa 0.05mm, 5 capas base"></textarea>
                <div class="alert alert-danger py-2 mt-2 d-none" id="loteError"></div>
                <div class="small text-muted mt-2 d-none" id="loteProgreso"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-info" id="loteConfirmar">Marcar impresas</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var tokenCampo = document.getElementById('revisarCsrfToken');

    // variante_id -> número de su versión validada. Se rellena en el primer
    // render y se actualiza al validar (sin recargar), para poder pintar el
    // aviso de "ya hay una validada por encima" en las filas hermanas que se
    // quedaron impresas sin juzgar.
    var VALIDADAS = <?= json_encode((object) ($validadasPorVariante ?? [])) ?>;

    function numeroValidadaDe(varianteId) {
        var n = VALIDADAS[String(varianteId)];
        return typeof n === 'number' ? n : null;
    }

    function pad3(n) {
        n = String(n);
        return n.length >= 3 ? n : ('000' + n).slice(-3);
    }

    function llamada(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenCampo ? tokenCampo.value : ''
            },
            body: formData || null
        }).then(function (r) { return r.json(); });
    }

    function aviso(texto, tipo) {
        var caja = document.getElementById('mensajesRevisar');
        var div = document.createElement('div');
        div.className = 'fv-flash ' + (tipo === 'warning' || tipo === 'danger' ? 'fv-flash-err' : 'fv-flash-ok');
        div.textContent = texto;
        caja.prepend(div);
        setTimeout(function () { div.remove(); }, 6000);
    }

    // ---- Config de cada verbo: qué campos enseña el modal y qué texto ----
    var VERBOS = {
        impresa: {
            titulo: 'Marcar impresa',
            ayuda: 'Pasa de borrador a impresa. Cuando la juzgues: validar o descartar.',
            confirmar: 'Marcar impresa', clase: 'btn-info',
            campos: ['params_impresion'], obligatorio: null
        },
        validar: {
            titulo: 'Validar',
            ayuda: 'Pasa a ser la versión buena. Si había otra validada, esa queda superada; las demás sin juzgar de la pieza se descartan.',
            confirmar: 'Validar', clase: 'btn-success',
            campos: [], obligatorio: null
        },
        descartar: {
            titulo: 'Descartar',
            ayuda: 'No se borra: se conserva con el motivo, para no repetir el mismo error dentro de tres meses.',
            confirmar: 'Descartar', clase: 'btn-danger',
            campos: ['resultado'], obligatorio: 'resultado', etiquetaResultado: 'Motivo (obligatorio)'
        },
        deshacer: {
            titulo: 'Deshacer',
            ayuda: 'Vuelve a borrador. Se borran los parámetros de impresión: vuelve a marcarla impresa con los buenos cuando toque.',
            confirmar: 'Deshacer', clase: 'btn-primary',
            campos: [], obligatorio: null
        }
    };

    var modalEl = document.getElementById('modalRevisar');
    var form = document.getElementById('formRevisar');
    var elTitulo = document.getElementById('revisarTitulo');
    var elLabel = document.getElementById('revisarLabel');
    var elAyuda = document.getElementById('revisarAyuda');
    var elError = document.getElementById('revisarError');
    var elConfirmar = document.getElementById('revisarConfirmar');
    var elEtiquetaResultado = document.getElementById('revisarEtiquetaResultado');
    var elHermanas = document.getElementById('revisarHermanas');
    var elHermanasLista = document.getElementById('revisarHermanasLista');
    var accionActual = null;
    var versionActual = null;

    // Al validar: casillas (marcadas) con las demás versiones sin juzgar de
    // la misma pieza, que se descartarán en el mismo golpe.
    function pintarHermanas(accion, version) {
        elHermanasLista.innerHTML = '';
        var fila = filaDe(version);
        var grupo = fila ? fila.closest('[data-grupo]') : null;
        var otras = accion === 'validar' && grupo
            ? Array.prototype.filter.call(grupo.querySelectorAll('[data-fila]'), function (f) { return f !== fila; })
            : [];
        elHermanas.classList.toggle('d-none', otras.length === 0);
        otras.forEach(function (f) {
            var id = f.getAttribute('data-version');
            var div = document.createElement('div');
            div.className = 'form-check small';
            div.innerHTML = '<input class="form-check-input" type="checkbox" checked data-descartar-hermana id="hermana' + id + '">'
                + '<label class="form-check-label" for="hermana' + id + '"></label>';
            div.querySelector('input').value = id;
            div.querySelector('label').textContent = 'v' + pad3(f.getAttribute('data-numero')) + ' (' + f.getAttribute('data-estado') + ')';
            elHermanasLista.appendChild(div);
        });
    }

    function abrirModal(accion, version, label, motivoSugerido) {
        accionActual = accion;
        versionActual = version;
        var cfg = VERBOS[accion];

        elTitulo.textContent = cfg.titulo;
        elLabel.textContent = label || '';
        elConfirmar.textContent = cfg.confirmar;
        elConfirmar.className = 'btn btn-sm ' + cfg.clase;
        elConfirmar.disabled = false;
        elError.classList.add('d-none');

        // Descartar con un motivo ya escrito: la versión estaba impresa sin
        // juzgar y otra por encima ya quedó validada (ver el aviso de la
        // fila). Se rellena, no se envía solo — el usuario revisa y confirma.
        var prefillDescarte = accion === 'descartar' && motivoSugerido ? motivoSugerido : '';
        elAyuda.textContent = prefillDescarte
            ? 'Motivo ya escrito por lo de arriba: revísalo, cámbialo si quieres y confirma.'
            : cfg.ayuda;

        form.querySelectorAll('[data-campo]').forEach(function (bloque) {
            var nombre = bloque.getAttribute('data-campo');
            var visible = cfg.campos.indexOf(nombre) !== -1;
            bloque.classList.toggle('d-none', !visible);
            var campo = bloque.querySelector('textarea');
            campo.value = nombre === 'resultado' ? prefillDescarte : '';
            campo.required = cfg.obligatorio === nombre;
        });
        if (cfg.etiquetaResultado) elEtiquetaResultado.textContent = cfg.etiquetaResultado;
        pintarHermanas(accion, version);

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // Delegación: los botones de todas las filas (y los que se repinten al
    // transformar una fila) caen en este único escuchador.
    document.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-accion][data-version]');
        if (!boton) return;
        var fila = boton.closest('[data-fila]');
        abrirModal(
            boton.getAttribute('data-accion'),
            boton.getAttribute('data-version'),
            fila ? fila.getAttribute('data-label') : '',
            fila ? fila.getAttribute('data-motivo-sugerido') : ''
        );
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!accionActual || !versionActual) return;

        var cfg = VERBOS[accionActual];
        if (cfg.obligatorio) {
            var req = form.querySelector('[data-campo="' + cfg.obligatorio + '"] textarea');
            if (req && req.value.trim() === '') {
                elError.textContent = 'Este campo es obligatorio.';
                elError.classList.remove('d-none');
                return;
            }
        }

        elConfirmar.disabled = true;
        elError.classList.add('d-none');

        var datos = new FormData();
        cfg.campos.forEach(function (nombre) {
            var campo = form.querySelector('[data-campo="' + nombre + '"] textarea');
            if (campo) datos.append(nombre, campo.value);
        });
        if (accionActual === 'validar') {
            elHermanasLista.querySelectorAll('[data-descartar-hermana]:checked').forEach(function (c) {
                datos.append('descartar[]', c.value);
            });
        }

        llamada('<?= site_url('piezas/version/') ?>' + versionActual + '/' + accionActual, datos).then(function (r) {
            if (!r.ok) {
                elError.textContent = r.mensaje || 'No se pudo completar la acción.';
                elError.classList.remove('d-none');
                elConfirmar.disabled = false;
                return;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            aviso(r.mensaje || 'Hecho.');
            aplicarResultado(accionActual, versionActual, r);
        }).catch(function () {
            elError.textContent = 'No se pudo conectar con el servidor.';
            elError.classList.remove('d-none');
            elConfirmar.disabled = false;
        });
    });

    // ---- Efecto de cada verbo sobre su fila ----
    function filaDe(version) {
        return document.querySelector('[data-fila][data-version="' + version + '"]');
    }

    function botonesFila(version, estado) {
        var id = String(version);
        if (estado === 'borrador') {
            return '<button type="button" class="fv-btn fv-btn-sm rv-b-imp" data-accion="impresa" data-version="' + id + '"><i class="bi bi-printer"></i> Marcar impresa</button>'
                + '<button type="button" class="fv-btn fv-btn-sm fv-btn-peligro" data-accion="descartar" data-version="' + id + '">Descartar</button>';
        }
        return '<button type="button" class="fv-btn fv-btn-sm rv-b-ok" data-accion="validar" data-version="' + id + '"><i class="bi bi-check-lg"></i> Validar</button>'
            + '<button type="button" class="fv-btn fv-btn-sm fv-btn-peligro" data-accion="descartar" data-version="' + id + '">Descartar</button>'
            + '<button type="button" class="fv-ibtn" data-accion="deshacer" data-version="' + id + '" title="Me equivoqué de botón: vuelve a borrador"><i class="bi bi-arrow-counterclockwise"></i></button>';
    }

    // Pinta en una fila el aviso de "ya hay una validada por encima" y la
    // sube al principio del grupo de impresas — el mismo estado en que la
    // deja el primer render del servidor, pero hecho a mano tras un AJAX.
    function aplicarAvisoSuperada(fila, numValidada) {
        if (fila.querySelector('[data-aviso-superada]')) return;

        var et = 'v' + pad3(numValidada);
        fila.classList.add('rv-superada');
        fila.setAttribute('data-motivo-sugerido',
            'La ' + et + ' de esta pieza ya está validada; esta impresa se quedó sin juzgar.');

        var alerta = document.createElement('div');
        alerta.className = 'rv-aviso';
        alerta.setAttribute('data-aviso-superada', '');
        alerta.innerHTML = '<i class="bi bi-exclamation-triangle"></i> <span>La <b>' + et + '</b> de esta pieza ya está '
            + 'validada: otra iteración quedó como la buena y esta se dejó impresa sin juzgar. '
            + 'Puedes descartarla directamente; el motivo viene ya escrito.</span>';
        fila.querySelector('[data-cabeza-fila]').insertAdjacentElement('afterend', alerta);

        var grupo = fila.closest('[data-grupo]');
        var cuerpo = grupo ? grupo.closest('[data-cuerpo]') : null;
        if (cuerpo) cuerpo.prepend(grupo);
    }

    // Una pieza va a "impresas" si le queda alguna versión impresa; si no,
    // a "en borrador". Se llama cada vez que cambia el estado de una fila.
    function recolocarGrupo(grupo) {
        if (!grupo) return;
        var destinoEstado = grupo.querySelector('[data-fila][data-estado="impresa"]') ? 'impresa' : 'borrador';
        var destino = document.querySelector('[data-seccion="' + destinoEstado + '"] [data-cuerpo]');
        if (destino && grupo.parentElement !== destino) destino.appendChild(grupo);
    }

    // Tras validar una versión, repasa las hermanas que siguen impresas con
    // número menor: ahora tienen una validada por encima.
    function revisarDescolgadas(varianteId) {
        var n = numeroValidadaDe(varianteId);
        if (n === null) return;
        document.querySelectorAll('[data-fila][data-variante="' + varianteId + '"][data-estado="impresa"]').forEach(function (fila) {
            if (parseInt(fila.getAttribute('data-numero'), 10) < n) aplicarAvisoSuperada(fila, n);
        });
    }

    function aplicarResultado(accion, version, respuesta) {
        var fila = filaDe(version);
        if (!fila) return;
        var grupo = fila.closest('[data-grupo]');

        if (accion === 'impresa' || accion === 'deshacer') {
            // Transforma la fila en su sitio: borrador <-> impresa. No sale
            // de la lista porque todavía queda trabajo con ella.
            var nuevoEstado = accion === 'impresa' ? 'impresa' : 'borrador';
            fila.setAttribute('data-estado', nuevoEstado);
            var badge = fila.querySelector('[data-badge-estado]');
            badge.textContent = nuevoEstado;
            badge.className = 'fv-estado ' + (nuevoEstado === 'impresa' ? 'e-info' : 'e-warn');
            fila.querySelector('[data-botones]').innerHTML = botonesFila(version, nuevoEstado);

            var check = fila.querySelector('[data-marcar]');
            if (nuevoEstado === 'impresa' && check) {
                check.closest('.rv-check').remove();
            } else if (nuevoEstado === 'borrador' && !check) {
                var div = document.createElement('label');
                div.className = 'rv-check';
                div.innerHTML = '<input type="checkbox" data-marcar value="' + version + '" aria-label="Seleccionar para marcar impresa en lote">';
                fila.querySelector('[data-cuerpo-fila]').prepend(div);
            }

            recolocarGrupo(grupo);

            // Recién pasada a impresa y ya tenía una validada por encima
            // (p. ej. se marca impresa una vieja después de haber validado
            // otra): sale con el aviso puesto, sin recargar.
            var nv = nuevoEstado === 'impresa' ? numeroValidadaDe(fila.getAttribute('data-variante')) : null;
            if (nv !== null && parseInt(fila.getAttribute('data-numero'), 10) < nv) {
                aplicarAvisoSuperada(fila, nv);
            } else {
                fila.classList.add('rv-recien');
                setTimeout(function () { fila.classList.remove('rv-recien'); }, 1500);
            }
        } else {
            // validar / descartar: fuera de la lista, ya está juzgada. Al
            // validar, además, las hermanas impresas de número menor quedan
            // descolgadas: se les pinta el aviso ahora mismo.
            var esValidar = accion === 'validar';
            var varianteId = fila.getAttribute('data-variante');
            var numero = parseInt(fila.getAttribute('data-numero'), 10);
            fila.remove();
            // Las hermanas descartadas en el mismo golpe también se van.
            ((respuesta && respuesta.descartadas) || []).forEach(function (id) {
                var h = filaDe(id);
                if (h) h.remove();
            });
            recolocarGrupo(grupo);
            if (esValidar) {
                VALIDADAS[String(varianteId)] = numero;
                revisarDescolgadas(varianteId);
            }
        }

        repintar();
    }

    // ---- Secciones vacías y lista vacía ----
    function repintar() {
        // Piezas sin filas fuera; en las demás, el recuento al día.
        document.querySelectorAll('[data-grupo]').forEach(function (g) {
            var n = g.querySelectorAll('[data-fila]').length;
            if (n === 0) { g.remove(); return; }
            var c = g.querySelector('[data-cuenta-versiones]');
            if (c) c.textContent = n + ' sin juzgar';
        });
        document.querySelectorAll('[data-seccion]').forEach(function (sec) {
            sec.classList.toggle('d-none', sec.querySelectorAll('[data-fila]').length === 0);
        });
        actualizarLote();

        if (document.querySelectorAll('[data-fila]').length === 0 && !document.getElementById('revisarVacio')) {
            var p = document.createElement('p');
            p.className = 'fv-vacio';
            p.id = 'revisarVacio';
            p.textContent = 'No queda nada por revisar: todas las versiones sin juzgar están resueltas.';
            document.getElementById('mensajesRevisar').after(p);
            var barra = document.getElementById('barraLote');
            if (barra) barra.classList.add('d-none');
        }
    }

    // ---- Selección múltiple para "marcar impresas" en lote ----
    var barraLote = document.getElementById('barraLote');
    var loteCuenta = document.getElementById('loteCuenta');

    function seleccionadas() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-marcar]:checked')).map(function (c) { return c.value; });
    }

    function actualizarLote() {
        if (!barraLote) return;
        var n = seleccionadas().length;
        loteCuenta.textContent = n;
        barraLote.classList.toggle('d-none', n === 0);
        barraLote.classList.toggle('d-flex', n > 0);
        var todas = document.getElementById('btnLoteTodas');
        if (todas) {
            var total = document.querySelectorAll('[data-marcar]').length;
            todas.querySelector('span').textContent = total > 0 && n === total ? 'Quitar selección' : 'Seleccionar todas';
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-marcar]')) actualizarLote();
    });

    var btnTodas = document.getElementById('btnLoteTodas');
    if (btnTodas) {
        btnTodas.addEventListener('click', function () {
            var casillas = document.querySelectorAll('[data-marcar]');
            var marcar = document.querySelectorAll('[data-marcar]:checked').length < casillas.length;
            casillas.forEach(function (c) { c.checked = marcar; });
            actualizarLote();
        });
    }

    var btnLimpiar = document.getElementById('btnLoteLimpiar');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            document.querySelectorAll('[data-marcar]:checked').forEach(function (c) { c.checked = false; });
            actualizarLote();
        });
    }

    var modalLoteEl = document.getElementById('modalLoteImpresas');
    var formLote = document.getElementById('formLoteImpresas');
    var loteError = document.getElementById('loteError');
    var loteProgreso = document.getElementById('loteProgreso');
    var loteConfirmar = document.getElementById('loteConfirmar');
    var loteTitulo = document.getElementById('loteTitulo');

    var btnLoteImpresas = document.getElementById('btnLoteImpresas');
    if (btnLoteImpresas) {
        btnLoteImpresas.addEventListener('click', function () {
            if (seleccionadas().length === 0) return;
            loteTitulo.textContent = '(' + seleccionadas().length + ')';
            loteError.classList.add('d-none');
            loteProgreso.classList.add('d-none');
            formLote.querySelector('textarea').value = '';
            loteConfirmar.disabled = false;
            bootstrap.Modal.getOrCreateInstance(modalLoteEl).show();
        });
    }

    formLote.addEventListener('submit', function (e) {
        e.preventDefault();
        var ids = seleccionadas();
        if (ids.length === 0) return;

        var params = formLote.querySelector('textarea').value;
        loteConfirmar.disabled = true;
        loteError.classList.add('d-none');
        loteProgreso.classList.remove('d-none');

        var hechas = 0;
        var fallos = [];

        // En serie, no en paralelo: cada verbo toca la misma variante como
        // mucho una vez, pero lanzar veinte POST a la vez no aporta nada y
        // enreda el manejo de errores.
        function siguiente(i) {
            if (i >= ids.length) {
                loteProgreso.classList.add('d-none');
                if (fallos.length === 0) {
                    bootstrap.Modal.getOrCreateInstance(modalLoteEl).hide();
                    aviso(hechas + ' versión(es) marcada(s) como impresa(s).');
                } else {
                    loteError.textContent = hechas + ' hecha(s), ' + fallos.length + ' con error: ' + fallos.join('; ');
                    loteError.classList.remove('d-none');
                    loteConfirmar.disabled = false;
                }
                repintar();
                return;
            }

            var id = ids[i];
            loteProgreso.textContent = 'Marcando… ' + (i + 1) + ' / ' + ids.length;

            var datos = new FormData();
            datos.append('params_impresion', params);

            llamada('<?= site_url('piezas/version/') ?>' + id + '/impresa', datos).then(function (r) {
                if (r.ok) {
                    hechas++;
                    aplicarResultado('impresa', id);
                } else {
                    var fila = filaDe(id);
                    fallos.push((fila ? fila.getAttribute('data-label') : 'v' + id) + ' (' + (r.mensaje || 'error') + ')');
                }
            }).catch(function () {
                fallos.push('v' + id + ' (sin conexión)');
            }).then(function () {
                siguiente(i + 1);
            });
        }

        siguiente(0);
    });

    // ---- Tareas de la pieza, apuntadas aquí mismo al revisar ----
    var modalTareasEl = document.getElementById('modalTareasRevisar');
    var formTareas = document.getElementById('formTareasRevisar');
    var tareasNombre = document.getElementById('tareasRevisarNombre');
    var tareasTexto = document.getElementById('tareasRevisarTexto');
    var tareasAdv = document.getElementById('tareasRevisarAdvertencia');
    var tareasError = document.getElementById('tareasRevisarError');
    var tareasConfirmar = document.getElementById('tareasRevisarConfirmar');
    var tareasVarianteActual = null;
    var tareasUrlActual = null;

    function contarTareas(texto) {
        return String(texto || '').split(/\r\n|\r|\n/).filter(function (l) { return l.trim() !== ''; }).length;
    }

    // Una variante puede tener varias filas (borrador + impresa a la vez):
    // refleja en todos sus botones lo que se acaba de guardar, sin recargar.
    function pintarBotonesTareas(varianteId, tareas, advertencia) {
        document.querySelectorAll('[data-tareas-boton][data-variante="' + varianteId + '"]').forEach(function (b) {
            b.setAttribute('data-tareas', tareas);
            b.setAttribute('data-advertencia', advertencia);
            var n = contarTareas(tareas);
            var conAlgo = n > 0 || advertencia.trim() !== '';
            b.className = 'fv-gbtn rv-tareas' + (conAlgo ? ' on' : '');
            b.innerHTML = '<i class="bi bi-card-checklist"></i> Tareas' + (n ? ' <span class="n">' + n + '</span>' : '');
        });
    }

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-tareas-boton]');
        if (!boton) return;
        tareasVarianteActual = boton.getAttribute('data-variante');
        tareasUrlActual = boton.getAttribute('data-accion');
        tareasNombre.textContent = boton.getAttribute('data-nombre') || '';
        tareasAdv.value = boton.getAttribute('data-advertencia') || '';
        tareasTexto.value = boton.getAttribute('data-tareas') || '';
        tareasError.classList.add('d-none');
        tareasConfirmar.disabled = false;
        bootstrap.Modal.getOrCreateInstance(modalTareasEl).show();
    });

    formTareas.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!tareasUrlActual) return;
        tareasConfirmar.disabled = true;
        tareasError.classList.add('d-none');

        var datos = new FormData();
        datos.append('tareas', tareasTexto.value);
        datos.append('advertencia', tareasAdv.value);

        llamada(tareasUrlActual, datos).then(function (r) {
            if (!r.ok) {
                tareasError.textContent = r.mensaje || 'No se pudo guardar.';
                tareasError.classList.remove('d-none');
                tareasConfirmar.disabled = false;
                return;
            }
            var tareas = r.tareas == null ? '' : String(r.tareas);
            var advertencia = r.advertencia == null ? '' : String(r.advertencia);
            pintarBotonesTareas(tareasVarianteActual, tareas, advertencia);
            bootstrap.Modal.getOrCreateInstance(modalTareasEl).hide();
            aviso('Tareas de la pieza guardadas.');
        }).catch(function () {
            tareasError.textContent = 'No se pudo conectar con el servidor.';
            tareasError.classList.remove('d-none');
            tareasConfirmar.disabled = false;
        });
    });

    actualizarLote();
})();
</script>

<?= $this->endSection() ?>
