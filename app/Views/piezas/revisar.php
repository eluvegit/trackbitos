<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<!-- Mismo patrón que "Pendientes de crear" y la galería: un token de sesión
     leído una vez y reutilizado en todos los fetch() de esta pantalla
     (marcar impresa, validar, descartar, deshacer) sin recargar. -->
<input type="hidden" id="revisarCsrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-clipboard-check text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Revisar impresiones</strong>
</h5>

<p class="text-muted small">
    Todas las versiones sin juzgar de cualquier pieza, en una sola lista: las que están
    <strong>en borrador</strong> (para imprimir) y las <strong>impresas</strong> pendientes de validar o
    descartar. Los botones son los mismos que en la ficha de cada pieza y hacen exactamente lo mismo,
    solo que aquí se van pulsando en fila sin entrar una a una. Las impresas van arriba: mientras una
    siga sin juzgar bloquea el trabajo nuevo de esa pieza.
</p>

<div id="mensajesRevisar"></div>

<?php
/**
 * Botonera de una fila, según su estado. Se pinta en PHP para el primer
 * render y se rehace en JS igual (ver botonesFila()) cuando un borrador
 * pasa a impresa sin recargar — por eso las dos tienen que dar el mismo
 * HTML.
 */
$botones = static function (array $f): string {
    $id = (int) $f['id'];
    if ($f['estado'] === 'borrador') {
        return '<button type="button" class="btn btn-sm btn-outline-info rounded-pill" data-accion="impresa" data-version="' . $id . '">Marcar impresa</button>'
            . '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-accion="descartar" data-version="' . $id . '">Descartar</button>';
    }

    return '<button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-accion="validar" data-version="' . $id . '">Validar</button>'
        . '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-accion="descartar" data-version="' . $id . '">Descartar</button>'
        . '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-accion="deshacer" data-version="' . $id . '" title="Me equivoqué de botón: vuelve a borrador">'
        . '<i class="bi bi-arrow-counterclockwise"></i></button>';
};

$etiquetaFila = static fn(array $f): string => $f['familia'] . ' / ' . $f['variante'] . ' v' . sprintf('%03d', (int) $f['numero']);

// Partida en dos grupos, en el orden en que ya vienen (impresa primero).
$grupos = ['impresa' => [], 'borrador' => []];
foreach ($filas as $f) {
    $grupos[$f['estado']][] = $f;
}
$titulos = [
    'impresa'  => ['Impresas · pendientes de juicio', 'bi-hourglass-split'],
    'borrador' => ['En borrador · para imprimir', 'bi-hammer'],
];
?>

<?php if (empty($filas)): ?>
    <p class="text-muted" id="revisarVacio">No queda nada por revisar: ninguna pieza tiene una versión en borrador ni impresa sin juzgar.</p>
<?php else: ?>
    <!-- Barra de lote: solo "marcar impresas", que es lo que de verdad se
         hace en tanda (los mismos parámetros para toda una placa). Validar
         y descartar piden un texto propio de cada pieza, así que van fila a
         fila. Aparece al marcar la primera casilla. -->
    <div id="barraLote" class="d-none align-items-center gap-2 flex-wrap mb-2 p-2 border rounded bg-body-tertiary sticky-top" style="top: .5rem; z-index: 3;">
        <span class="small"><strong id="loteCuenta">0</strong> seleccionada(s)</span>
        <button type="button" class="btn btn-sm btn-info" id="btnLoteImpresas">
            <i class="bi bi-printer"></i> Marcar impresas
        </button>
        <button type="button" class="btn btn-sm btn-link text-decoration-none" id="btnLoteLimpiar">Quitar selección</button>
    </div>

    <?php foreach ($grupos as $estado => $delGrupo): ?>
        <section data-seccion="<?= $estado ?>" class="<?= $delGrupo === [] ? 'd-none' : '' ?>">
            <h6 class="text-muted small text-uppercase mt-3 mb-2" data-cabecera>
                <i class="bi <?= esc($titulos[$estado][1]) ?>"></i> <?= esc($titulos[$estado][0]) ?>
            </h6>
            <div data-cuerpo>
                <?php foreach ($delGrupo as $f): ?>
                    <?php $motivoSugerido = !empty($f['superada_por_validada'])
                        ? 'La v' . sprintf('%03d', (int) $f['superada_por_validada']) . ' de esta pieza ya está validada; esta impresa se quedó sin juzgar.'
                        : ''; ?>
                    <div class="card shadow-sm mb-2 <?= !empty($f['superada_por_validada']) ? 'border-warning' : '' ?>"
                        data-fila data-version="<?= (int) $f['id'] ?>" data-estado="<?= esc($f['estado']) ?>"
                        data-variante="<?= (int) $f['variante_id'] ?>" data-numero="<?= (int) $f['numero'] ?>"
                        data-label="<?= esc($etiquetaFila($f), 'attr') ?>"
                        <?= $motivoSugerido !== '' ? 'data-motivo-sugerido="' . esc($motivoSugerido, 'attr') . '"' : '' ?>>
                        <div class="card-body p-2 d-flex gap-2 align-items-start">
                            <?php if ($f['estado'] === 'borrador'): ?>
                                <div class="form-check pt-1">
                                    <input class="form-check-input" type="checkbox" data-marcar value="<?= (int) $f['id'] ?>"
                                        aria-label="Seleccionar para marcar impresa en lote">
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($f['render'])): ?>
                                <img src="<?= imagen_pieza($f['render'], 'render') ?>" alt=""
                                    class="rounded flex-shrink-0" style="width: 44px; height: 44px; object-fit: cover;">
                            <?php endif; ?>

                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="<?= site_url('piezas/variante/' . (int) $f['variante_id']) ?>#version-<?= (int) $f['id'] ?>"
                                        class="text-decoration-none fw-semibold">
                                        <?= esc($f['familia']) ?> <span class="text-muted">/</span> <?= esc($f['variante']) ?>
                                    </a>
                                    <span class="badge text-bg-secondary">v<?= sprintf('%03d', (int) $f['numero']) ?></span>
                                    <span class="badge <?= $f['estado'] === 'impresa' ? 'text-bg-primary' : 'text-bg-secondary' ?>" data-badge-estado>
                                        <?= $f['estado'] === 'impresa' ? 'impresa' : 'borrador' ?>
                                    </span>
                                </div>

                                <?php if (!empty($f['superada_por_validada'])): ?>
                                    <div class="alert alert-warning py-1 px-2 small my-2 mb-0 mt-2" data-aviso-superada>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        La <strong>v<?= sprintf('%03d', (int) $f['superada_por_validada']) ?></strong> de esta pieza ya está
                                        validada: se siguió trabajando y otra iteración quedó como la buena, y esta se dejó impresa sin juzgar.
                                        Puedes descartarla directamente — el motivo viene ya escrito, revísalo y confirma.
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($f['cambio'])): ?>
                                    <div class="small mt-1"><?= esc($f['cambio']) ?></div>
                                <?php endif; ?>

                                <div class="small text-muted mt-1 d-flex flex-column gap-1">
                                    <span data-antiguedad>
                                        <i class="bi bi-clock-history"></i>
                                        Promocionada hace <?= (int) $f['dias'] ?> día(s)
                                        <?php if (!empty($f['olvidada'])): ?>
                                            <span class="badge text-bg-warning ms-1">lleva demasiado sin resolverse</span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (!empty($f['placas'])): ?>
                                        <span>
                                            <i class="bi bi-printer"></i>
                                            <?= esc(implode(', ', array_map(static fn($p) => $p['nombre'], $f['placas']))) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($f['estado'] === 'borrador'): ?>
                                        <span>
                                            <i class="bi bi-box"></i>
                                            <?= $f['stls'] > 0 ? (int) $f['stls'] . ' STL adjunto(s)' : 'sin STL adjunto' ?>
                                        </span>
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

                            <div class="d-flex flex-column gap-1 flex-shrink-0" data-botones>
                                <?= $botones($f) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modales compartidos: uno por verbo, no uno por fila — el contenido es el
     mismo que en la ficha, solo cambia sobre qué versión actúa. -->
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

                <div class="alert alert-danger py-2 mt-2 d-none" id="revisarError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary" id="revisarConfirmar">Confirmar</button>
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
        div.className = 'alert alert-' + (tipo || 'success') + ' py-2';
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
            ayuda: 'Pasa a ser la versión buena. Si había otra validada, esa queda superada.',
            confirmar: 'Validar', clase: 'btn-success',
            campos: ['resultado'], obligatorio: null, etiquetaResultado: 'Resultado'
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
    var accionActual = null;
    var versionActual = null;

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

        llamada('<?= site_url('piezas/version/') ?>' + versionActual + '/' + accionActual, datos).then(function (r) {
            if (!r.ok) {
                elError.textContent = r.mensaje || 'No se pudo completar la acción.';
                elError.classList.remove('d-none');
                elConfirmar.disabled = false;
                return;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            aviso(r.mensaje || 'Hecho.');
            aplicarResultado(accionActual, versionActual);
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
            return '<button type="button" class="btn btn-sm btn-outline-info rounded-pill" data-accion="impresa" data-version="' + id + '">Marcar impresa</button>'
                + '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-accion="descartar" data-version="' + id + '">Descartar</button>';
        }
        return '<button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-accion="validar" data-version="' + id + '">Validar</button>'
            + '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-accion="descartar" data-version="' + id + '">Descartar</button>'
            + '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-accion="deshacer" data-version="' + id + '" title="Me equivoqué de botón: vuelve a borrador"><i class="bi bi-arrow-counterclockwise"></i></button>';
    }

    // Pinta en una fila el aviso de "ya hay una validada por encima" y la
    // sube al principio del grupo de impresas — el mismo estado en que la
    // deja el primer render del servidor, pero hecho a mano tras un AJAX.
    function aplicarAvisoSuperada(fila, numValidada) {
        if (fila.querySelector('[data-aviso-superada]')) return;

        var et = 'v' + pad3(numValidada);
        fila.classList.add('border-warning');
        fila.setAttribute('data-motivo-sugerido',
            'La ' + et + ' de esta pieza ya está validada; esta impresa se quedó sin juzgar.');

        var alerta = document.createElement('div');
        alerta.className = 'alert alert-warning py-1 px-2 small my-2 mb-0 mt-2';
        alerta.setAttribute('data-aviso-superada', '');
        alerta.innerHTML = '<i class="bi bi-exclamation-triangle"></i> La <strong>' + et + '</strong> de esta pieza ya está '
            + 'validada: se siguió trabajando y otra iteración quedó como la buena, y esta se dejó impresa sin juzgar. '
            + 'Puedes descartarla directamente — el motivo viene ya escrito, revísalo y confirma.';
        fila.querySelector('[data-badge-estado]').closest('.d-flex').insertAdjacentElement('afterend', alerta);

        var cuerpo = fila.closest('[data-cuerpo]');
        if (cuerpo) cuerpo.prepend(fila);
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

    function aplicarResultado(accion, version) {
        var fila = filaDe(version);
        if (!fila) return;

        if (accion === 'impresa' || accion === 'deshacer') {
            // Transforma la fila en su sitio: borrador <-> impresa. No sale
            // de la lista porque todavía queda trabajo con ella.
            var nuevoEstado = accion === 'impresa' ? 'impresa' : 'borrador';
            fila.setAttribute('data-estado', nuevoEstado);
            var badge = fila.querySelector('[data-badge-estado]');
            badge.textContent = nuevoEstado;
            badge.className = 'badge ' + (nuevoEstado === 'impresa' ? 'text-bg-primary' : 'text-bg-secondary');
            fila.querySelector('[data-botones]').innerHTML = botonesFila(version, nuevoEstado);

            var check = fila.querySelector('[data-marcar]');
            if (nuevoEstado === 'impresa' && check) {
                check.closest('.form-check').remove();
            } else if (nuevoEstado === 'borrador' && !check) {
                var div = document.createElement('div');
                div.className = 'form-check pt-1';
                div.innerHTML = '<input class="form-check-input" type="checkbox" data-marcar value="' + version + '" aria-label="Seleccionar para marcar impresa en lote">';
                fila.querySelector('.card-body').prepend(div);
            }

            var destino = document.querySelector('[data-seccion="' + nuevoEstado + '"] [data-cuerpo]');
            if (destino && fila.parentElement !== destino) destino.appendChild(fila);

            // Recién pasada a impresa y ya tenía una validada por encima
            // (p. ej. se marca impresa una vieja después de haber validado
            // otra): sale con el aviso puesto, sin recargar.
            var nv = nuevoEstado === 'impresa' ? numeroValidadaDe(fila.getAttribute('data-variante')) : null;
            if (nv !== null && parseInt(fila.getAttribute('data-numero'), 10) < nv) {
                aplicarAvisoSuperada(fila, nv);
            } else {
                fila.classList.add('border-info');
                setTimeout(function () { fila.classList.remove('border-info'); }, 1500);
            }
        } else {
            // validar / descartar: fuera de la lista, ya está juzgada. Al
            // validar, además, las hermanas impresas de número menor quedan
            // descolgadas: se les pinta el aviso ahora mismo.
            var esValidar = accion === 'validar';
            var varianteId = fila.getAttribute('data-variante');
            var numero = parseInt(fila.getAttribute('data-numero'), 10);
            fila.remove();
            if (esValidar) {
                VALIDADAS[String(varianteId)] = numero;
                revisarDescolgadas(varianteId);
            }
        }

        repintar();
    }

    // ---- Secciones vacías y lista vacía ----
    function repintar() {
        document.querySelectorAll('[data-seccion]').forEach(function (sec) {
            sec.classList.toggle('d-none', sec.querySelectorAll('[data-fila]').length === 0);
        });
        actualizarLote();

        if (document.querySelectorAll('[data-fila]').length === 0 && !document.getElementById('revisarVacio')) {
            var p = document.createElement('p');
            p.className = 'text-muted';
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
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-marcar]')) actualizarLote();
    });

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

    actualizarLote();
})();
</script>

<?= $this->endSection() ?>
