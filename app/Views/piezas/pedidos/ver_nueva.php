<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=10">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-pedidos.css') ?>?v=8">
<?php
    $etiquetas = ['idea' => 'Idea', 'nuevo' => 'Nuevo', 'en_produccion' => 'Diseñando', 'para_impresion' => 'Para impresión', 'completado' => 'Hecho'];
    $tonos     = ['idea' => 'e-neu', 'nuevo' => 'e-info', 'en_produccion' => 'e-warn', 'para_impresion' => 'e-acc', 'completado' => 'e-ok'];
    $estadoActual = $pedido['estado'];
    $posActual = array_search($estadoActual, $estados, true);
    $creado = strtotime($pedido['creado_en']);
    $prioritario = (bool) (int) $pedido['prioritario'];
    $entrega = \App\Models\PiezaPedidoModel::entrega($pedido);
    $tonoEntrega = $entrega ? (str_contains($entrega['clase'], 'danger') ? 'e-bad' : (str_contains($entrega['clase'], 'warning') ? 'e-warn' : 'e-neu')) : '';
?>

<div class="fv pp pp-ficha" id="pp">
    <div data-avisos><?php if (session('success')): ?><div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div><?php endif; ?><?php if (session('error')): ?><div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div><?php endif; ?></div>

    <div class="fv-top">
        <nav class="fv-crumbs">
            <a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i>
            <a href="<?= site_url('piezas/pedidos') ?>">Pedidos</a><i class="bi bi-chevron-right"></i>
            <span class="fv-mono">#<?= (int) $pedido['id'] ?></span>
        </nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/pedidos') ?>" class="fv-gbtn" title="Volver al tablero de pedidos"><i class="bi bi-arrow-left"></i> Volver</a>
            <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/prioridad') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="fv-gbtn<?= $prioritario ? ' prio' : '' ?>" title="<?= $prioritario ? 'Quitar prioridad' : 'Marcar como prioritario (sale arriba en el tablero)' ?>">
                    <i class="bi <?= $prioritario ? 'bi-star-fill' : 'bi-star' ?>"></i> <span class="lbl-m"><?= $prioritario ? 'Prioritario' : 'Estrella' ?></span>
                </button>
            </form>
            <button type="button" class="fv-gbtn" data-bs-toggle="modal" data-bs-target="#modalDatosPedido"><i class="bi bi-pencil"></i> <span class="lbl-m">Editar datos</span></button>
            <a href="<?= site_url('piezas/galeria') ?>" class="fv-gbtn"><i class="bi bi-grid-3x3-gap"></i> <span class="lbl-m">Galería</span></a>
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn"><i class="bi bi-printer"></i> <span class="lbl-m">Placas</span></a>
            <span class="fv-vsep"></span>
            <button type="submit" class="fv-gbtn peligro" form="form-borrar-pedido"><i class="bi bi-trash"></i> <span class="lbl-m">Borrar</span></button>
            <span class="fv-vsep"></span>
            <a href="<?= current_url() ?>?nueva=0" class="fv-gbtn" title="Volver a la ficha de siempre"><i class="bi bi-arrow-left-right"></i> <span class="lbl-m">Vista clásica</span></a>
        </div>
    </div>

    <div class="fv-hero">
        <div class="pp-hero-fila">
            <?php if (!empty($pedido['referencia_externa'])): ?>
                <h1 data-bs-toggle="modal" data-bs-target="#modalDatosPedido" title="Editar referencia"><?= esc($pedido['referencia_externa']) ?></h1>
            <?php else: ?>
                <h1 class="pp-sin-ref" data-bs-toggle="modal" data-bs-target="#modalDatosPedido">Ponle referencia</h1>
            <?php endif; ?>
            <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/cargar-placa') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="fv-btn fv-btn-pri" title="Suma a la placa actual el STL de cada pieza de este pedido, sin quitar lo que ya lleve">
                    <i class="bi bi-printer"></i> Cargar a la placa
                </button>
            </form>
        </div>
        <div class="fv-meta">
            <span class="fv-estado <?= $tonos[$estadoActual] ?? 'e-neu' ?>"><?= esc($etiquetas[$estadoActual] ?? $estadoActual) ?></span>
            <?php if ($prioritario): ?><span class="fv-estado e-warn">Prioritario</span><?php endif; ?>
            <span class="fv-dot"></span>
            <span>Entró por <b><?= esc($pedido['origen']) ?></b></span>
            <span><?= $creado ? esc(date('d/m/Y H:i', $creado)) : '' ?></span>
            <span class="fv-dot"></span>
            <?php if ($entrega): ?>
                <span class="fv-estado <?= $tonoEntrega ?> pp-editable" data-bs-toggle="modal" data-bs-target="#modalDatosPedido" title="Fecha de entrega: <?= esc(date('d/m/Y', strtotime($pedido['fecha_entrega'])), 'attr') ?>">Entrega <?= esc($entrega['texto']) ?></span>
            <?php else: ?>
                <span class="pp-editable" data-bs-toggle="modal" data-bs-target="#modalDatosPedido"><i class="bi bi-truck"></i> Sin fecha de entrega</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="fv-grid pp-grid">
        <div class="fv-col">
            <section class="fv-panel pp-piezas" data-m="2">
                <div class="fv-ph">
                    <h3>Piezas</h3>
                    <span class="cuenta" data-resumen-lineas><?= count($pedido['lineas']) ?></span>
                    <div class="der d-flex gap-1">
                        <button type="button" class="fv-gbtn" data-modo="ordenar"><i class="bi bi-arrow-down-up"></i> Ordenar</button>
                        <button type="button" class="fv-gbtn" data-modo="seleccion"><i class="bi bi-check2-square"></i> Seleccionar</button>
                    </div>
                </div>

                <div id="lista-lineas">
                    <?php foreach ($pedido['lineas'] as $linea): ?>
                        <?= view('piezas/pedidos/_linea_fila_nueva', ['linea' => $linea]) ?>
                    <?php endforeach; ?>
                </div>
                <div class="lista-vacia d-none" data-sin-lineas>
                    <i class="bi bi-inbox"></i>
                    <span>Aún no hay piezas en este pedido. Búscalas abajo para añadirlas.</span>
                </div>

                <div class="pp-anadir">
                    <div data-barra-anadir class="position-relative">
                        <label class="pp-buscar">
                            <i class="bi bi-plus-lg"></i>
                            <input type="text" data-buscador-anadir autocomplete="off" enterkeyhint="done"
                                placeholder="Añadir pieza…" title="Busca en el catálogo o escribe una pieza nueva (futura)">
                        </label>
                        <div class="list-group resultados-busqueda d-none" data-resultados-anadir></div>
                    </div>

                    <form id="form-copiar-lineas" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/copiar-lineas') ?>"
                        class="d-none pp-copiar" data-barra-copiar>
                        <?= csrf_field() ?>
                        <span class="text-nowrap"><b data-contador-copiar>0</b> sel. · a</span>
                        <select name="destino" class="fv-input">
                            <option value="nuevo">un pedido nuevo</option>
                            <?php foreach ($pedidosAbiertos as $abierto): ?>
                                <option value="<?= (int) $abierto['id'] ?>">
                                    #<?= (int) $abierto['id'] ?><?= $abierto['referencia_externa'] ? ' · ' . esc($abierto['referencia_externa']) : '' ?><?= $abierto['notas'] ? ' · ' . esc(mb_strimwidth($abierto['notas'], 0, 30, '…')) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri" data-boton-copiar disabled
                            title="Copia solo lo que falta (cantidad − completado); este pedido no cambia">
                            <i class="bi bi-files"></i> Copiar
                        </button>
                        <button type="submit" class="fv-btn fv-btn-sm" data-boton-mover disabled
                            formaction="<?= site_url('piezas/pedido/' . $pedido['id'] . '/mover-lineas') ?>"
                            onclick="return confirm('¿Sacar las líneas marcadas de este pedido y pasarlas al destino?');"
                            title="Saca las líneas de este pedido y las pasa al destino tal cual (con lo ya completado)">
                            <i class="bi bi-arrow-right-square"></i> Mover
                        </button>
                        <button type="button" class="fv-gbtn" data-seleccionar-todas>Todas</button>
                    </form>
                </div>

                <?php if (!empty($sugerencias)): ?>
                    <div class="pp-sug" data-sugerencias>
                        <span class="eti">En marcha y fuera del pedido · toca para añadir</span>
                        <div class="sugerencias">
                            <?php foreach ($sugerencias as $s): ?>
                                <button type="button" class="sugerencia sugerencia-<?= esc($s['tipo'], 'attr') ?>"
                                    data-sugerencia data-variante-id="<?= (int) $s['variante_id'] ?>"
                                    title="<?= $s['tipo'] === 'modificando' ? 'Modificando' : 'Sin empezar' ?> — toca para añadirla al pedido">
                                    <?php if ($s['foto']): ?>
                                        <img src="<?= esc($s['foto'], 'attr') ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <i class="bi <?= $s['tipo'] === 'modificando' ? 'bi-pencil' : 'bi-circle' ?>"></i>
                                    <?php endif; ?>
                                    <span><?= esc($s['texto']) ?></span>
                                    <i class="bi bi-plus-lg sugerencia-mas"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="fv-col">
            <section class="fv-panel" data-m="1">
                <div class="fv-ph"><h3>Progreso</h3></div>
                <div class="pp-grande"><span data-resumen-piezas>–</span><small>piezas hechas</small></div>
                <div class="fv-nivel" aria-hidden="true"><i data-resumen-barra style="width: 0%"></i></div>
                <dl class="fv-kv">
                    <div><dt>Listas para imprimir</dt><dd class="fv-mono" data-resumen-hechas>–</dd></div>
                </dl>
            </section>

            <section class="fv-panel" data-m="3">
                <div class="fv-ph"><h3>Fase</h3></div>
                <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/estado') ?>">
                    <?= csrf_field() ?>
                    <ol class="pp-pasos">
                        <?php foreach ($estados as $i => $estado): ?>
                            <?php $clase = $i < $posActual ? 'hecho' : ($i === $posActual ? 'actual' . ($estado === 'completado' ? ' fin' : '') : ''); ?>
                            <li class="<?= $clase ?>">
                                <button type="<?= $i === $posActual ? 'button' : 'submit' ?>" name="estado" value="<?= esc($estado, 'attr') ?>"
                                    title="<?= $i === $posActual ? 'Fase actual' : esc('Pasar a «' . ($etiquetas[$estado] ?? $estado) . '»', 'attr') ?>"><?= esc($etiquetas[$estado] ?? $estado) ?></button>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </form>
            </section>

            <?php if ($pedido['notas']): ?>
                <section class="fv-panel" data-m="5">
                    <div class="fv-ph"><h3>Notas</h3></div>
                    <p class="pp-notas" data-bs-toggle="modal" data-bs-target="#modalDatosPedido" title="Editar notas"><?= esc($pedido['notas']) ?></p>
                </section>
            <?php endif; ?>

            <?= view('piezas/pedidos/_referencias_nueva', ['pedido' => $pedido, 'imagenes' => $imagenes]) ?>

            <?php if (!empty($placas)): ?>
                <section class="fv-panel" data-m="6">
                    <div class="fv-ph"><h3>Placas de este pedido</h3><span class="cuenta"><?= count($placas) ?></span></div>
                    <div class="pp-plista">
                        <?php foreach ($placas as $placa): ?>
                            <a href="<?= site_url('piezas/placa/' . (int) $placa['id'] . '/bitacora/editar') ?>" target="_blank" rel="noopener">
                                <span class="nom"><?= esc($placa['nombre']) ?></span>
                                <span class="f"><?= esc(date('d/m H:i', strtotime($placa['creado_en']))) ?></span>
                                <?php if ($placa['impresa_en']): ?>
                                    <span class="fv-estado e-ok">Impresa</span>
                                <?php elseif ($placa['descargada_en']): ?>
                                    <span class="fv-estado e-info">Lista</span>
                                <?php else: ?>
                                    <span class="fv-estado e-neu">Guardada</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<form id="form-borrar-pedido" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/borrar') ?>" class="d-none"
    onsubmit="return confirm('¿Borrar el pedido #<?= (int) $pedido['id'] ?> y todas sus líneas? No se puede deshacer.');">
    <?= csrf_field() ?>
</form>

<?php // ================= Panel de edición de una línea =================
      // Uno solo para todas: se rellena con los data-* de la línea tocada.
      // Abajo en móvil (hoja), a la derecha en pantallas anchas. ?>
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="panelLinea" aria-labelledby="panelLineaTitulo">
    <div class="panel-asa-movil"></div>
    <div class="offcanvas-header pb-1">
        <h5 class="offcanvas-title fs-6 fw-bold" id="panelLineaTitulo">Editar pieza</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body pt-2">
        <form id="form-panel-linea" method="post" action="">
            <?= csrf_field() ?>
            <label class="form-label small text-body-secondary mb-1">Pieza del catálogo</label>
            <div class="position-relative mb-2">
                <input type="text" class="form-control" data-buscador-panel autocomplete="off" placeholder="Buscar pieza del catálogo…">
                <input type="hidden" name="variante_id" data-panel-variante value="">
                <div class="list-group shadow resultados-busqueda hacia-abajo d-none" data-resultados-panel></div>
            </div>
            <div data-panel-bloque-descripcion>
                <label class="form-label small text-body-secondary mb-1">…o descripción (pieza futura, aún sin hacer)</label>
                <input type="text" name="descripcion_libre" class="form-control mb-2" maxlength="150" data-panel-descripcion>
            </div>

            <div class="d-flex align-items-center gap-3 my-3">
                <span class="small text-body-secondary">Cantidad</span>
                <div class="panel-cantidad">
                    <button type="button" class="stepper-btn" data-panel-cantidad="-1"><i class="bi bi-dash-lg"></i></button>
                    <input type="number" name="cantidad" min="1" value="1" required data-panel-cantidad-input>
                    <button type="button" class="stepper-btn" data-panel-cantidad="1"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>

            <label class="form-label small text-body-secondary mb-1">Notas</label>
            <textarea name="notas" class="form-control" rows="2" maxlength="150" data-panel-notas placeholder="p. ej. reforzar el cuello"></textarea>

            <div class="d-flex align-items-center gap-2 mt-4">
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
                <a href="#" class="btn btn-outline-secondary rounded-pill" data-panel-ver target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Ver pieza</a>
                <button type="button" class="btn btn-link text-danger ms-auto" data-panel-borrar><i class="bi bi-trash"></i> Borrar</button>
            </div>
        </form>

        <?php // Solo en piezas futuras: darla de alta en el catálogo y que la
              // línea pase a apuntar a ella. Form aparte (no se anidan forms). ?>
        <div class="border-top mt-4 pt-3 d-none" data-panel-crear>
            <button type="button" class="btn btn-success rounded-pill w-100" data-panel-crear-abrir>
                <i class="bi bi-plus-circle"></i> Crear esta pieza en el catálogo
            </button>
            <form id="form-crear-pieza" method="post" action="" class="d-none">
                <?= csrf_field() ?>
                <div class="btn-group btn-group-sm w-100 mb-3" role="group">
                    <input type="radio" class="btn-check" name="modo" id="crearModoNueva" value="nueva" checked>
                    <label class="btn btn-outline-secondary" for="crearModoNueva">Pieza nueva</label>
                    <input type="radio" class="btn-check" name="modo" id="crearModoDerivada" value="derivada">
                    <label class="btn btn-outline-secondary" for="crearModoDerivada">Derivada de otra</label>
                </div>

                <div class="d-none" data-crear-bloque-derivada>
                    <label class="form-label small text-body-secondary mb-1">Deriva de…</label>
                    <div class="position-relative mb-1">
                        <input type="text" class="form-control" data-buscador-origen autocomplete="off" placeholder="Buscar pieza del catálogo…">
                        <input type="hidden" name="origen_variante_id" data-crear-origen value="">
                        <div class="list-group shadow resultados-busqueda hacia-abajo d-none" data-resultados-origen></div>
                    </div>
                    <p class="small text-body-secondary mb-2">Será una variante nueva de esa pieza, arrancando de su última versión.</p>
                </div>

                <label class="form-label small text-body-secondary mb-1" data-crear-etiqueta-nombre>Nombre</label>
                <input type="text" name="nombre" class="form-control mb-2" maxlength="150" required data-crear-nombre>

                <div data-crear-bloque-categoria>
                    <label class="form-label small text-body-secondary mb-1">Categoría</label>
                    <select name="categoria_id" class="form-select mb-2">
                        <option value="">— sin clasificar —</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= esc($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="form-label small text-body-secondary mb-1">Notas de la pieza (opcional)</label>
                <textarea name="notas" class="form-control mb-3" rows="2"></textarea>

                <button type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-check-lg"></i> Crear y enlazar</button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDatosPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/datos') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Pedido #<?= (int) $pedido['id'] ?></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">Referencia (opcional)</label>
                <input type="text" name="referencia_externa" class="form-control mb-2"
                    value="<?= esc($pedido['referencia_externa'] ?? '', 'attr') ?>"
                    placeholder="de qué va (o nº de pedido, si viene de fuera)" maxlength="50">
                <label class="form-label small">Fecha de entrega (opcional)</label>
                <input type="date" name="fecha_entrega" class="form-control mb-2" value="<?= esc($pedido['fecha_entrega'] ?? '', 'attr') ?>">
                <label class="form-label small">Notas</label>
                <textarea name="notas" class="form-control" rows="3"><?= esc($pedido['notas'] ?? '') ?></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var CSRF_NOMBRE = '<?= csrf_token() ?>';
    var CSRF_VALOR = '<?= csrf_hash() ?>';
    var URL_BUSCAR = '<?= site_url('piezas/pedido-variante-buscar') ?>';
    var URL_ANADIR = '<?= site_url('piezas/pedido/' . $pedido['id'] . '/linea') ?>';
    var URL_ORDEN = '<?= site_url('piezas/pedido/' . $pedido['id'] . '/reordenar-lineas') ?>';

    var lista = document.getElementById('lista-lineas');

    // ---- Utilidades ----
    function post(url, campos) {
        var datos = campos instanceof FormData ? campos : new FormData();
        if (!(campos instanceof FormData)) {
            Object.keys(campos || {}).forEach(function (k) {
                [].concat(campos[k]).forEach(function (v) { datos.append(k, v); });
            });
        }
        if (!datos.has(CSRF_NOMBRE)) datos.append(CSRF_NOMBRE, CSRF_VALOR);
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: datos,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); });
    }
    function fallo(d, porDefecto) { alert((d && d.mensaje) || porDefecto); }
    function escapar(t) { return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); }
    function lineaDe(id) { return lista.querySelector('.linea[data-linea-id="' + id + '"]'); }

    function reemplazarLinea(id, html) {
        var vieja = lineaDe(id);
        if (!vieja) return;
        var marcada = vieja.querySelector('[data-seleccion-copiar]').checked;
        vieja.outerHTML = html;
        if (marcada) lineaDe(id).querySelector('[data-seleccion-copiar]').checked = true;
        refrescar();
    }

    // ---- Resumen de la cabecera: sale de las propias líneas ----
    function refrescar() {
        var lineas = lista.querySelectorAll('.linea');
        var total = 0, hechas = 0, listas = 0;
        lineas.forEach(function (l) {
            total += +l.dataset.cantidad || 0;
            hechas += +l.dataset.completada || 0;
            if (l.dataset.hecha === '1') listas++;
        });
        document.querySelector('[data-resumen-barra]').style.width = (total ? Math.round(hechas * 100 / total) : 0) + '%';
        document.querySelector('[data-resumen-piezas]').textContent = hechas + '/' + total;
        document.querySelector('[data-resumen-hechas]').textContent = listas + '/' + lineas.length;
        document.querySelector('[data-resumen-lineas]').textContent = lineas.length;
        document.querySelector('[data-sin-lineas]').classList.toggle('d-none', lineas.length > 0);
        contarSeleccion();
    }

    // ---- Modos: Ordenar / Seleccionar (excluyentes) ----
    var barraAnadir = document.querySelector('[data-barra-anadir]');
    var barraCopiar = document.querySelector('[data-barra-copiar]');
    function ponerModo(modo) {
        var actual = lista.classList.contains('modo-' + modo);
        lista.classList.remove('modo-ordenar', 'modo-seleccion');
        if (!actual) lista.classList.add('modo-' + modo);
        document.querySelectorAll('[data-modo]').forEach(function (b) {
            var activo = lista.classList.contains('modo-' + b.dataset.modo);
            b.classList.toggle('btn-primary', activo);
            b.classList.toggle('btn-outline-secondary', !activo);
            b.innerHTML = activo
                ? '<i class="bi bi-check-lg"></i> Hecho'
                : (b.dataset.modo === 'ordenar' ? '<i class="bi bi-arrow-down-up"></i> Ordenar' : '<i class="bi bi-check2-square"></i> Seleccionar');
        });
        var seleccion = lista.classList.contains('modo-seleccion');
        if (!seleccion) lista.querySelectorAll('[data-seleccion-copiar]').forEach(function (c) { c.checked = false; });
        barraAnadir.classList.toggle('d-none', seleccion);
        var tiraSugerencias = document.querySelector('[data-sugerencias]');
        if (tiraSugerencias) tiraSugerencias.classList.toggle('d-none', seleccion);
        barraCopiar.classList.toggle('d-none', !seleccion);
        barraCopiar.classList.toggle('d-flex', seleccion);
        contarSeleccion();
    }
    document.querySelectorAll('[data-modo]').forEach(function (b) {
        b.addEventListener('click', function () { ponerModo(b.dataset.modo); });
    });

    function contarSeleccion() {
        var n = lista.querySelectorAll('[data-seleccion-copiar]:checked').length;
        document.querySelector('[data-contador-copiar]').textContent = n;
        document.querySelector('[data-boton-copiar]').disabled = n === 0;
        document.querySelector('[data-boton-mover]').disabled = n === 0;
    }
    document.querySelector('[data-seleccionar-todas]').addEventListener('click', function () {
        var casillas = lista.querySelectorAll('[data-seleccion-copiar]');
        var todas = lista.querySelectorAll('[data-seleccion-copiar]:checked').length === casillas.length;
        casillas.forEach(function (c) { c.checked = !todas; });
        contarSeleccion();
    });

    // ---- Ordenar: arrastre por el asa (solo visible en modo Ordenar) ----
    Sortable.create(lista, {
        handle: '.linea-asa',
        draggable: '.linea',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function (evt) {
            if (evt.oldIndex === evt.newIndex) return;
            var orden = [].map.call(lista.querySelectorAll('.linea'), function (l) { return l.dataset.lineaId; });
            post(URL_ORDEN, { 'orden[]': orden })
                .then(function (d) { if (!d.ok) fallo(d, 'No se pudo guardar el orden.'); })
                .catch(function () { alert('No se pudo guardar el orden.'); });
        }
    });

    // ---- Toques en una línea: casilla, chip "Lista", abrir panel ----
    lista.addEventListener('change', function (e) {
        if (e.target.matches('[data-seleccion-copiar]')) contarSeleccion();
    });

    lista.addEventListener('click', function (e) {
        var linea = e.target.closest('.linea');
        if (!linea) return;

        // En modo selección, tocar la línea entera marca/desmarca.
        if (lista.classList.contains('modo-seleccion') && !e.target.matches('[data-seleccion-copiar]')) {
            if (e.target.closest('.linea-stepper')) return;
            var c = linea.querySelector('[data-seleccion-copiar]');
            c.checked = !c.checked;
            contarSeleccion();
            return;
        }
        if (lista.classList.contains('modo-ordenar')) return;

        // "Crear pieza" de una futura: el panel, ya con el alta abierta.
        if (e.target.closest('[data-chip-crear]')) {
            e.stopPropagation();
            abrirPanel(linea, true);
            return;
        }

        // Chip "Lista para imprimir": cambio optimista, se deshace si falla.
        var chip = e.target.closest('[data-chip-hecha]');
        if (chip) {
            e.stopPropagation();
            var marcar = linea.dataset.hecha !== '1';
            pintarChip(linea, chip, marcar);
            chip.disabled = true;
            post(linea.dataset.urlHecha, marcar ? { hecha: 1 } : {})
                .then(function (d) {
                    if (!d.ok) { pintarChip(linea, chip, !marcar); fallo(d, 'No se pudo actualizar.'); return; }
                    // Era la última por preparar: el pedido ha cambiado de estado.
                    if (d.recargar) location.reload();
                })
                .catch(function () { pintarChip(linea, chip, !marcar); alert('No se pudo conectar con el servidor.'); })
                .finally(function () { chip.disabled = false; });
            return;
        }

        if (e.target.closest('[data-abrir-panel]')) abrirPanel(linea);
    });
    lista.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-abrir-panel]')) {
            e.preventDefault();
            abrirPanel(e.target.closest('.linea'));
        }
    });

    function pintarChip(linea, chip, marcado) {
        linea.dataset.hecha = marcado ? '1' : '0';
        linea.classList.toggle('linea-lista', marcado);
        chip.classList.toggle('activo', marcado);
        chip.innerHTML = marcado ? '<i class="bi bi-check-circle-fill"></i> Lista' : '<i class="bi bi-circle"></i> Preparar';
        refrescar();
    }

    // ---- +/− completado: el servidor devuelve la línea ya pintada ----
    lista.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-form-completada]')) return;
        e.preventDefault();
        var linea = form.closest('.linea');
        linea.querySelectorAll('.stepper-btn').forEach(function (b) { b.disabled = true; });
        post(form.action, new FormData(form))
            .then(function (d) { if (!d.ok) { fallo(d, 'No se pudo actualizar.'); return; } reemplazarLinea(linea.dataset.lineaId, d.rowHtml); })
            .catch(function () { alert('No se pudo conectar con el servidor.'); });
    });

    // ---- Buscador de catálogo (lo usan la barra de añadir y el panel) ----
    function buscador(caja, resultados, alElegir, conFutura) {
        var espera = null, ultimo = [];
        function pintar(lista) {
            ultimo = lista;
            var q = caja.value.trim();
            var html = lista.map(function (v, i) {
                return '<button type="button" class="list-group-item list-group-item-action py-2" data-i="' + i + '">' + escapar(v.texto) + '</button>';
            }).join('');
            if (conFutura && q) {
                html += '<button type="button" class="list-group-item list-group-item-action py-2 text-info-emphasis" data-futura>'
                    + '<i class="bi bi-lightbulb"></i> Añadir «' + escapar(q) + '» como pieza futura</button>';
            }
            if (!html) html = '<div class="list-group-item py-2 small text-body-secondary">Sin resultados</div>';
            resultados.innerHTML = html;
            resultados.classList.remove('d-none');
        }
        caja.addEventListener('input', function () {
            clearTimeout(espera);
            var q = caja.value.trim();
            if (caja._alEscribir) caja._alEscribir(q);
            if (q.length < 2) {
                ultimo = [];
                if (conFutura && q) pintar([]); else resultados.classList.add('d-none');
                return;
            }
            espera = setTimeout(function () {
                fetch(URL_BUSCAR + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (caja.value.trim() === q) pintar(d.resultados || []); });
            }, 220);
        });
        resultados.addEventListener('mousedown', function (e) { e.preventDefault(); });
        resultados.addEventListener('click', function (e) {
            var b = e.target.closest('button');
            if (!b) return;
            resultados.classList.add('d-none');
            alElegir(b.hasAttribute('data-futura') ? { futura: caja.value.trim() } : ultimo[+b.dataset.i]);
        });
        caja.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { resultados.classList.add('d-none'); return; }
            if (e.key !== 'Enter') return;
            e.preventDefault();
            var q = caja.value.trim();
            if (!q) return;
            resultados.classList.add('d-none');
            if (ultimo.length) alElegir(ultimo[0]);
            else if (conFutura) alElegir({ futura: q });
        });
        caja.addEventListener('blur', function () { setTimeout(function () { resultados.classList.add('d-none'); }, 150); });
    }

    // ---- Añadir: un solo buscador, cantidad 1 (se ajusta luego con +/−) ----
    var cajaAnadir = document.querySelector('[data-buscador-anadir]');
    var sugerencias = document.querySelector('[data-sugerencias]');

    // Común al buscador y a las sugerencias. Si la pieza estaba sugerida, la
    // sugerencia desaparece: ya está en el pedido.
    function anadir(campos) {
        campos.cantidad = 1;
        return post(URL_ANADIR, campos).then(function (d) {
            if (!d.ok) { fallo(d, 'No se pudo añadir.'); return false; }
            lista.insertAdjacentHTML('beforeend', d.rowHtml);
            refrescar();
            lista.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (sugerencias && campos.variante_id) {
                var s = sugerencias.querySelector('[data-sugerencia][data-variante-id="' + campos.variante_id + '"]');
                if (s) s.remove();
                if (!sugerencias.querySelector('[data-sugerencia]')) sugerencias.remove();
            }
            return true;
        });
    }

    buscador(cajaAnadir, document.querySelector('[data-resultados-anadir]'), function (eleccion) {
        cajaAnadir.disabled = true;
        anadir(eleccion.futura ? { descripcion_libre: eleccion.futura } : { variante_id: eleccion.variante_id })
            .then(function (ok) { if (ok) cajaAnadir.value = ''; })
            .catch(function () { alert('No se pudo conectar con el servidor.'); })
            .finally(function () { cajaAnadir.disabled = false; cajaAnadir.focus(); });
    }, true);

    if (sugerencias) {
        sugerencias.addEventListener('click', function (e) {
            var b = e.target.closest('[data-sugerencia]');
            if (!b) return;
            b.disabled = true;
            anadir({ variante_id: b.dataset.varianteId })
                .then(function (ok) { if (!ok) b.disabled = false; })
                .catch(function () { b.disabled = false; alert('No se pudo conectar con el servidor.'); });
        });
    }

    // ---- Panel de edición ----
    var panelEl = document.getElementById('panelLinea');
    var panel = bootstrap.Offcanvas.getOrCreateInstance(panelEl);
    var formPanel = document.getElementById('form-panel-linea');
    var cajaPanel = formPanel.querySelector('[data-buscador-panel]');
    var variantePanel = formPanel.querySelector('[data-panel-variante]');
    var bloqueDescripcion = formPanel.querySelector('[data-panel-bloque-descripcion]');
    var descripcionPanel = formPanel.querySelector('[data-panel-descripcion]');
    var cantidadPanel = formPanel.querySelector('[data-panel-cantidad-input]');
    var notasPanel = formPanel.querySelector('[data-panel-notas]');
    var verPanel = formPanel.querySelector('[data-panel-ver]');
    var lineaEnPanel = null;

    // La descripción libre solo pinta algo sin pieza del catálogo elegida.
    function ajustarDescripcion() {
        bloqueDescripcion.classList.toggle('d-none', !!variantePanel.value && variantePanel.value !== '0');
    }
    cajaPanel._alEscribir = function () { variantePanel.value = ''; ajustarDescripcion(); };
    buscador(cajaPanel, formPanel.querySelector('[data-resultados-panel]'), function (v) {
        variantePanel.value = v.variante_id;
        cajaPanel.value = v.texto;
        ajustarDescripcion();
    }, false);

    // ---- Crear pieza desde una futura (dentro del mismo panel) ----
    var bloqueCrear = panelEl.querySelector('[data-panel-crear]');
    var botonCrearAbrir = bloqueCrear.querySelector('[data-panel-crear-abrir]');
    var formCrear = document.getElementById('form-crear-pieza');
    var cajaOrigen = formCrear.querySelector('[data-buscador-origen]');
    var origenCrear = formCrear.querySelector('[data-crear-origen]');
    var nombreCrear = formCrear.querySelector('[data-crear-nombre]');

    function pintarModoCrear() {
        var derivada = formCrear.modo.value === 'derivada';
        formCrear.querySelector('[data-crear-bloque-derivada]').classList.toggle('d-none', !derivada);
        formCrear.querySelector('[data-crear-bloque-categoria]').classList.toggle('d-none', derivada);
        formCrear.querySelector('[data-crear-etiqueta-nombre]').textContent = derivada ? 'Nombre de la variante' : 'Nombre';
    }
    formCrear.querySelectorAll('input[name=modo]').forEach(function (r) { r.addEventListener('change', pintarModoCrear); });

    function mostrarFormCrear(abierto) {
        formCrear.classList.toggle('d-none', !abierto);
        botonCrearAbrir.classList.toggle('d-none', abierto);
        formPanel.classList.toggle('d-none', abierto);
        document.getElementById('panelLineaTitulo').textContent = abierto ? 'Crear pieza' : 'Editar pieza';
        if (abierto) nombreCrear.focus();
    }
    botonCrearAbrir.addEventListener('click', function () { mostrarFormCrear(true); });

    cajaOrigen._alEscribir = function () { origenCrear.value = ''; };
    buscador(cajaOrigen, formCrear.querySelector('[data-resultados-origen]'), function (v) {
        origenCrear.value = v.variante_id;
        cajaOrigen.value = v.texto;
    }, false);

    formCrear.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!lineaEnPanel) return;
        if (formCrear.modo.value === 'derivada' && !origenCrear.value) {
            alert('Elige de qué pieza deriva.');
            cajaOrigen.focus();
            return;
        }
        var linea = lineaEnPanel;
        var boton = formCrear.querySelector('[type=submit]');
        boton.disabled = true;
        post(formCrear.action, new FormData(formCrear))
            .then(function (d) {
                if (!d.ok) { fallo(d, 'No se pudo crear la pieza.'); return; }
                reemplazarLinea(linea.dataset.lineaId, d.rowHtml);
                panel.hide();
                avisar(d.mensaje, d.urlVariante);
            })
            .catch(function () { alert('No se pudo conectar con el servidor.'); })
            .finally(function () { boton.disabled = false; });
    });

    // Aviso bajo la cabecera, con enlace a la pieza recién creada.
    function avisar(mensaje, url) {
        var caja = document.createElement('div');
        caja.className = 'fv-flash fv-flash-ok';
        caja.innerHTML = '<i class="bi bi-check-circle"></i> ' + escapar(mensaje)
            + (url ? ' <a href="' + escapar(url) + '">Abrir la pieza</a>' : '');
        document.querySelector('[data-avisos]').append(caja);
        caja.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function abrirPanel(linea, crear) {
        lineaEnPanel = linea;
        var d = linea.dataset;

        bloqueCrear.classList.toggle('d-none', !d.urlCrear);
        formCrear.reset();
        formCrear.action = d.urlCrear || '';
        origenCrear.value = '';
        nombreCrear.value = d.descripcion;
        pintarModoCrear();
        mostrarFormCrear(!!(crear && d.urlCrear));

        formPanel.action = d.urlEditar;
        variantePanel.value = d.varianteId !== '0' ? d.varianteId : '';
        cajaPanel.value = d.textoCatalogo;
        descripcionPanel.value = d.descripcion;
        cantidadPanel.value = d.cantidad;
        notasPanel.value = d.notas;
        verPanel.classList.toggle('d-none', !d.urlVariante);
        verPanel.href = d.urlVariante || '#';
        ajustarDescripcion();

        // Hoja desde abajo en móvil, panel a la derecha en pantallas anchas.
        var ancha = window.matchMedia('(min-width: 768px)').matches;
        panelEl.classList.toggle('offcanvas-end', ancha);
        panelEl.classList.toggle('offcanvas-bottom', !ancha);
        panel.show();
    }

    formPanel.querySelectorAll('[data-panel-cantidad]').forEach(function (b) {
        b.addEventListener('click', function () {
            cantidadPanel.value = Math.max(1, (parseInt(cantidadPanel.value, 10) || 1) + parseInt(b.dataset.panelCantidad, 10));
        });
    });

    formPanel.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!lineaEnPanel) return;
        var boton = formPanel.querySelector('[type=submit]');
        boton.disabled = true;
        post(formPanel.action, new FormData(formPanel))
            .then(function (d) {
                if (!d.ok) { fallo(d, 'No se pudo guardar.'); return; }
                reemplazarLinea(lineaEnPanel.dataset.lineaId, d.rowHtml);
                panel.hide();
            })
            .catch(function () { alert('No se pudo conectar con el servidor.'); })
            .finally(function () { boton.disabled = false; });
    });

    formPanel.querySelector('[data-panel-borrar]').addEventListener('click', function () {
        if (!lineaEnPanel || !confirm('¿Quitar esta pieza del pedido?')) return;
        var linea = lineaEnPanel;
        post(linea.dataset.urlBorrar, {})
            .then(function (d) {
                if (!d.ok) { fallo(d, 'No se pudo borrar.'); return; }
                panel.hide();
                linea.remove();
                refrescar();
            })
            .catch(function () { alert('No se pudo conectar con el servidor.'); });
    });

    refrescar();
});
</script>

<?= $this->endSection() ?>
