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
<input type="hidden" name="volver" value="">
                            <input type="text" name="nombre" value="<?= esc($c['nombre'], 'attr') ?>"
                                class="form-control form-control-sm" maxlength="100" required>
                            <button class="btn btn-sm btn-outline-primary" title="Renombrar"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <form method="post" action="<?= site_url('piezas/categoria/' . (int) $c['id'] . '/borrar') ?>"
                            onsubmit="return confirm('¿Borrar la categoría «<?= esc($c['nombre'], 'attr') ?>»? Sus piezas quedan sin clasificar.');">
                            <?= csrf_field() ?>
<input type="hidden" name="volver" value="">
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
<input type="hidden" name="volver" value="">
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
<input type="hidden" name="volver" value="">
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

<!-- Ajustes de resina: precio por litro y densidad, para el coste de resina por pieza -->
<div class="modal fade" id="modalResina" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('piezas/ajustes-resina') ?>">
            <?= csrf_field() ?>
<input type="hidden" name="volver" value="">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-droplet"></i> Resina</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    El coste de resina de cada pieza sale de su <strong>volumen con soportes</strong>
                    (el que da Chitubox, apuntado en cada trozo) por este precio. La densidad solo
                    se usa para convertir entre volumen y peso cuando de un trozo falta uno de los dos.
                    Déjalo en blanco y solo se enseñará el volumen y el peso, sin coste.
                </p>
                <label class="form-label small" for="cfgPrecioResina">Precio por litro (€)</label>
                <input type="text" inputmode="decimal" name="precio_resina_eur_litro" id="cfgPrecioResina"
                    class="form-control form-control-sm mb-2" autocomplete="off"
                    value="<?= $resinaConfig['precioLitro'] !== null ? esc(rtrim(rtrim(number_format($resinaConfig['precioLitro'], 2, ',', ''), '0'), ','), 'attr') : '' ?>"
                    placeholder="p. ej. 35">
                <label class="form-label small" for="cfgDensidadResina">Densidad (g/mL)</label>
                <input type="text" inputmode="decimal" name="densidad_resina_g_ml" id="cfgDensidadResina"
                    class="form-control form-control-sm" autocomplete="off"
                    value="<?= esc(rtrim(rtrim(number_format($resinaConfig['densidad'], 3, ',', ''), '0'), ','), 'attr') ?>"
                    placeholder="1,1">
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

                <label class="form-label small" for="calcFin">Terminar a las</label>
                <input type="time" id="calcFin" class="form-control form-control-sm mb-3">

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
                        <tr class="d-none" id="calcInicioFila">
                            <td class="fw-semibold">Iniciar a las</td>
                            <td class="text-end"><span class="fw-bold fs-6 text-primary" id="calcInicio">—</span></td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3">
                    <div class="small fw-semibold mb-1">Según tus placas <span class="text-muted fw-normal">(solo impresión)</span></div>
                    <table class="table table-sm align-middle small mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th class="fw-normal"></th>
                                <th class="fw-normal">Regla</th>
                                <th class="fw-normal text-end">Impresión</th>
                                <th class="fw-normal text-end calc-col-inicio d-none">Iniciar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reglasTiempo as $campo => $r): ?>
                                <tr data-regla data-por-capa="<?= esc((string) $r['porCapa'], 'attr') ?>">
                                    <td><?= esc($r['etiqueta']) ?></td>
                                    <td class="text-muted">
                                        <?php if ($r['porCapa'] === null): ?>
                                            sin datos
                                        <?php else: ?>
                                            <?= number_format($r['porCapa'], 4, ',', '.') ?> min/capa
                                            <br><span class="small"><?= $r['n'] ?> placa<?= $r['n'] === 1 ? '' : 's' ?></span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end fw-semibold" data-total>—</td>
                                    <td class="text-end calc-col-inicio d-none" data-inicio>—</td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

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
<input type="hidden" name="volver" value="">
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
<input type="hidden" name="volver" value="">
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
