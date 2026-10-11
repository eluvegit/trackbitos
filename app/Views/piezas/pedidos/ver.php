<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
    $etiquetas = ['nuevo' => 'Pendiente', 'en_produccion' => 'Produciendo', 'completado' => 'Hecho', 'cancelado' => 'Cancelado'];
    $colores   = ['nuevo' => 'primary', 'en_produccion' => 'warning', 'completado' => 'success', 'cancelado' => 'secondary'];
    $iconos    = ['nuevo' => 'bi-inbox', 'en_produccion' => 'bi-gear', 'completado' => 'bi-check2-circle', 'cancelado' => 'bi-x-circle'];
    $estadoActual = $pedido['estado'];
    $creado = strtotime($pedido['creado_en']);
?>

<style>
    /* ================= Cabecera fija ================= */
    .pedido-cabecera {
        position: sticky; top: 0; z-index: 1020;
        background: var(--bs-body-bg);
        margin-inline: -.75rem; padding: .6rem .75rem .7rem;
        border-bottom: 1px solid var(--bs-border-color-translucent);
    }
    .pedido-migas { font-size: .75rem; }
    .pedido-migas a { color: var(--bs-secondary-color); text-decoration: none; }
    .pedido-titulo { font-size: 1.35rem; font-weight: 700; letter-spacing: -.01em; }
    .chip-estado {
        border: 0; border-radius: 999px; padding: .2rem .65rem; font-size: .8rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .pedido-progreso { height: 6px; border-radius: 99px; }
    .pedido-cifras { font-size: .8rem; color: var(--bs-secondary-color); }
    .pedido-cifras strong { color: var(--bs-body-color); }

    /* ================= Lista de líneas ================= */
    .lista-barra { display: flex; align-items: center; gap: .5rem; padding: .9rem 0 .4rem; }
    .lista-barra h6 { margin: 0; font-weight: 700; }
    .btn-modo { border-radius: 999px; font-size: .8rem; padding: .2rem .7rem; }

    #lista-lineas { border-top: 1px solid var(--bs-border-color-translucent); }
    .linea {
        display: flex; align-items: center; gap: .65rem;
        padding: .7rem .25rem; border-bottom: 1px solid var(--bs-border-color-translucent);
        background: var(--bs-body-bg);
    }
    .linea-cuerpo {
        flex: 1 1 auto; min-width: 0; display: flex; align-items: center; gap: .75rem;
        cursor: pointer; border-radius: .5rem; outline-offset: 2px;
    }
    .linea-foto {
        flex: 0 0 auto; width: 56px; height: 56px; border-radius: .6rem; object-fit: contain;
        background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color-translucent);
    }
    .linea-foto-vacia { display: inline-flex; align-items: center; justify-content: center; color: var(--bs-tertiary-color); font-size: 1.2rem; }
    .linea-texto { min-width: 0; flex: 1 1 auto; }
    .linea-nombre {
        font-weight: 600; line-height: 1.25;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .linea-nombre .badge { font-size: .6rem; font-weight: 600; }
    .linea-meta { font-size: .75rem; color: var(--bs-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .1rem; }
    .linea-pie { display: flex; align-items: center; gap: .5rem; margin-top: .4rem; }
    .linea-progreso { flex: 1 1 auto; max-width: 9rem; height: 4px; border-radius: 99px; }

    /* "Lista para imprimir": es la guía de cómo va la preparación, así que
       botón de verdad (no una etiqueta) y relleno cuando está marcado. */
    .chip-hecha {
        border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-body-color);
        border-radius: 999px; font-size: .8rem; font-weight: 600; padding: .3rem .75rem; min-height: 32px;
        display: inline-flex; align-items: center; gap: .35rem; white-space: nowrap;
    }
    .chip-hecha .bi { font-size: .95rem; }
    .chip-hecha.activo { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }

    .linea-stepper {
        flex: 0 0 auto; display: flex; align-items: center;
        border: 1px solid var(--bs-border-color); border-radius: 999px; background: var(--bs-body-bg);
    }
    .stepper-btn {
        width: 40px; height: 40px; border: 0; background: transparent; color: var(--bs-body-color);
        display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; font-size: 1.05rem;
    }
    .stepper-btn:not(:disabled):active { background: var(--bs-secondary-bg); }
    .stepper-btn:disabled { opacity: .25; }
    .stepper-ok { color: var(--bs-success); }
    .stepper-valor { min-width: 2.6rem; text-align: center; font-size: .95rem; }
    .stepper-valor span { color: var(--bs-secondary-color); font-size: .8rem; }

    /* Tres tramos de un vistazo: sin tinte (por preparar) → azul (lista para
       imprimir) → verde (completa, manda sobre el azul). Suaves, no bloques. */
    .linea-lista { background: var(--bs-primary-bg-subtle); box-shadow: inset 4px 0 0 var(--bs-primary); }
    .linea-completa { background: var(--bs-success-bg-subtle); box-shadow: inset 4px 0 0 var(--bs-success); }
    .linea-completa .chip-hecha.activo { background: transparent; border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
    .linea-completa .linea-stepper { border-color: var(--bs-success-border-subtle); background: transparent; }

    /* Asa y casilla solo existen en su modo. */
    .linea-asa, .linea-check { display: none; flex: 0 0 auto; }
    .linea-asa { color: var(--bs-tertiary-color); font-size: 1.3rem; padding: .4rem .1rem; cursor: grab; touch-action: none; }
    .linea-check { width: 1.35rem; height: 1.35rem; margin: 0; }
    #lista-lineas.modo-ordenar .linea-asa { display: inline-flex; }
    #lista-lineas.modo-seleccion .linea-check { display: inline-block; }
    #lista-lineas.modo-ordenar .linea-stepper,
    #lista-lineas.modo-ordenar .chip-hecha { pointer-events: none; opacity: .35; }
    #lista-lineas .sortable-ghost { opacity: .35; }
    #lista-lineas .sortable-chosen { box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }

    .lista-vacia { text-align: center; color: var(--bs-secondary-color); padding: 2.5rem 1rem; }

    /* ================= Barra inferior (añadir / copiar) ================= */
    .barra-inferior {
        position: sticky; bottom: 0; z-index: 1020;
        margin-inline: -.75rem; padding: .6rem .75rem calc(.6rem + env(safe-area-inset-bottom));
        background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color-translucent);
        box-shadow: 0 -.25rem .75rem rgba(0,0,0,.06);
    }
    .barra-inferior .form-control, .barra-inferior .form-select, .barra-inferior .btn { min-height: 42px; }
    .resultados-busqueda {
        position: absolute; left: 0; right: 0; bottom: calc(100% + .35rem); z-index: 1060;
        max-height: 50vh; overflow-y: auto; border-radius: .6rem;
    }
    .resultados-busqueda.hacia-abajo { bottom: auto; top: calc(100% + .25rem); }

    /* Sugerencias: piezas modificándose / sin empezar fuera del pedido. */
    /* Todas a la vista, saltando de línea (van fuera de la barra fija). */
    .sugerencias { display: flex; flex-wrap: wrap; gap: .4rem; padding: .6rem 0 1rem; }
    .sugerencia {
        flex: 0 0 auto; display: inline-flex; align-items: center; gap: .35rem; max-width: 15rem;
        border: 1px dashed var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-body-color);
        border-radius: 999px; padding: .2rem .55rem .2rem .25rem; font-size: .78rem; min-height: 34px;
    }
    .sugerencia img, .sugerencia > .bi:first-child {
        width: 26px; height: 26px; border-radius: 999px; object-fit: cover; flex: 0 0 auto;
        display: inline-flex; align-items: center; justify-content: center; background: var(--bs-tertiary-bg);
    }
    .sugerencia span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sugerencia-modificando { border-color: var(--bs-warning-border-subtle); }
    .sugerencia-modificando > .bi:first-child { color: var(--bs-warning-text-emphasis); }
    .sugerencia-mas { color: var(--bs-secondary-color); }
    .sugerencia:active { background: var(--bs-secondary-bg); }
    .sugerencia:disabled { opacity: .4; }

    /* ================= Panel de edición ================= */
    #panelLinea.offcanvas-bottom { height: auto; max-height: 88vh; border-radius: 1rem 1rem 0 0; }
    #panelLinea.offcanvas-end { width: min(420px, 100vw); }
    .panel-asa-movil { width: 40px; height: 4px; border-radius: 99px; background: var(--bs-border-color); margin: .5rem auto 0; }
    #panelLinea.offcanvas-end .panel-asa-movil { display: none; }
    .panel-cantidad { display: inline-flex; align-items: center; border: 1px solid var(--bs-border-color); border-radius: 999px; }
    .panel-cantidad input { width: 3.5rem; border: 0; text-align: center; background: transparent; font-weight: 600; -moz-appearance: textfield; }
    .panel-cantidad input::-webkit-inner-spin-button { display: none; }

    .placas-pedido summary { list-style: none; cursor: pointer; }
    .placas-pedido summary::-webkit-details-marker { display: none; }
    .placas-pedido[open] .bi-chevron-right { transform: rotate(90deg); }
    .placas-pedido .bi-chevron-right { transition: transform .15s; display: inline-block; }
</style>

<?php // ================= Cabecera ================= ?>
<header class="pedido-cabecera">
    <nav class="pedido-migas">
        <a href="<?= site_url('piezas') ?>">Piezas</a> <span class="text-body-tertiary">/</span>
        <a href="<?= site_url('piezas/pedidos') ?>">Pedidos</a>
    </nav>

    <div class="d-flex align-items-center gap-2 mt-1">
        <h1 class="pedido-titulo mb-0">Pedido #<?= (int) $pedido['id'] ?></h1>

        <?php // Estado detrás de un toque: se mira mucho, se cambia poco. ?>
        <div class="dropdown">
            <button type="button" class="chip-estado text-bg-<?= $colores[$estadoActual] ?>" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar estado">
                <i class="bi <?= $iconos[$estadoActual] ?>"></i> <?= esc($etiquetas[$estadoActual] ?? $estadoActual) ?> <i class="bi bi-chevron-down" style="font-size: .65rem;"></i>
            </button>
            <form class="dropdown-menu shadow" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/estado') ?>">
                <?= csrf_field() ?>
                <?php foreach ($estados as $estado): ?>
                    <button type="submit" name="estado" value="<?= esc($estado, 'attr') ?>"
                        class="dropdown-item d-flex align-items-center gap-2 <?= $estado === $estadoActual ? 'active' : '' ?>">
                        <i class="bi <?= $iconos[$estado] ?> text-<?= $colores[$estado] ?>"></i> <?= esc($etiquetas[$estado] ?? $estado) ?>
                    </button>
                <?php endforeach; ?>
            </form>
        </div>

        <div class="ms-auto d-flex gap-1">
            <form method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/cargar-placa') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3" title="Añade a la placa actual el STL de cada pieza de este pedido">
                    <i class="bi bi-printer"></i><span class="d-none d-sm-inline"> Cargar a la placa</span>
                </button>
            </form>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 32px; height: 32px; padding: 0;" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalDatosPedido"><i class="bi bi-pencil me-2"></i>Referencia y notas</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= site_url('piezas/galeria') ?>"><i class="bi bi-grid-3x3-gap me-2"></i>Galería</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('piezas/placas') ?>"><i class="bi bi-printer me-2"></i>Placas</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="submit" class="dropdown-item text-danger" form="form-borrar-pedido"><i class="bi bi-trash me-2"></i>Borrar pedido</button></li>
                </ul>
            </div>
        </div>
    </div>

    <?php // Rellenado por resumen() en el script, a partir de las propias líneas. ?>
    <div class="progress pedido-progreso mt-2"><div class="progress-bar bg-success" data-resumen-barra style="width: 0%"></div></div>
    <div class="pedido-cifras d-flex gap-3 mt-1">
        <span><strong data-resumen-piezas>–</strong> piezas hechas</span>
        <span><strong data-resumen-hechas>–</strong> listas para imprimir</span>
    </div>
</header>

<form id="form-borrar-pedido" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/borrar') ?>" class="d-none"
    onsubmit="return confirm('¿Borrar el pedido #<?= (int) $pedido['id'] ?> y todas sus líneas? No se puede deshacer.');">
    <?= csrf_field() ?>
</form>

<?php if (session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 small mt-2 mb-0">
        <?= esc(session('success')) ?><button type="button" class="btn-close btn-sm py-2" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning alert-dismissible fade show py-2 small mt-2 mb-0">
        <?= esc(session('error')) ?><button type="button" class="btn-close btn-sm py-2" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php // Datos del pedido: discretos, bajo la cabecera (no viajan con ella). ?>
<div class="small text-body-secondary mt-2 d-flex flex-wrap gap-x-3 column-gap-3">
    <span class="text-capitalize"><i class="bi bi-box-arrow-in-down"></i> <?= esc($pedido['origen']) ?></span>
    <span><i class="bi bi-calendar3"></i> <?= $creado ? esc(date('d/m/Y H:i', $creado)) : '' ?></span>
    <?php if ($pedido['referencia_externa']): ?>
        <span class="font-monospace"><i class="bi bi-hash"></i><?= esc($pedido['referencia_externa']) ?></span>
    <?php endif; ?>
</div>
<?php if ($pedido['notas']): ?>
    <div class="small mt-1" style="white-space: pre-line;" role="button" data-bs-toggle="modal" data-bs-target="#modalDatosPedido" title="Editar notas">
        <i class="bi bi-sticky text-body-secondary"></i> <?= esc($pedido['notas']) ?>
    </div>
<?php endif; ?>

<?php // ================= Líneas ================= ?>
<div class="lista-barra">
    <h6>Piezas <span class="text-body-secondary fw-normal" data-resumen-lineas><?= count($pedido['lineas']) ?></span></h6>
    <div class="ms-auto d-flex gap-1">
        <button type="button" class="btn btn-outline-secondary btn-modo" data-modo="ordenar"><i class="bi bi-arrow-down-up"></i> Ordenar</button>
        <button type="button" class="btn btn-outline-secondary btn-modo" data-modo="seleccion"><i class="bi bi-check2-square"></i> Seleccionar</button>
    </div>
</div>

<div id="lista-lineas">
    <?php foreach ($pedido['lineas'] as $linea): ?>
        <?= view('piezas/pedidos/_linea_fila', ['linea' => $linea]) ?>
    <?php endforeach; ?>
</div>
<div class="lista-vacia d-none" data-sin-lineas>
    <i class="bi bi-inbox fs-2 d-block mb-1 text-body-tertiary"></i>
    Aún no hay piezas en este pedido.<br>Búscalas abajo para añadirlas.
</div>

<?php // Placas: plegadas, es consulta ocasional. Solo listado, sin cuadrar qué
      // cubre cada una: eso se sigue viendo a ojo abriendo la placa. ?>
<?php if (!empty($placas)): ?>
    <details class="placas-pedido mt-3">
        <summary class="small fw-semibold text-body-secondary py-2">
            <i class="bi bi-chevron-right"></i> <?= count($placas) ?> placa<?= count($placas) === 1 ? '' : 's' ?> de este pedido
        </summary>
        <div class="list-group list-group-flush">
            <?php foreach ($placas as $placa): ?>
                <a href="<?= site_url('piezas/placa/' . (int) $placa['id'] . '/bitacora/editar') ?>" target="_blank" rel="noopener"
                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 small px-1">
                    <span class="flex-grow-1 text-truncate"><?= esc($placa['nombre']) ?></span>
                    <span class="text-body-secondary"><?= esc(date('d/m H:i', strtotime($placa['creado_en']))) ?></span>
                    <?php if ($placa['impresa_en']): ?>
                        <span class="badge rounded-pill text-bg-success">Impresa</span>
                    <?php elseif ($placa['descargada_en']): ?>
                        <span class="badge rounded-pill text-bg-primary">Lista</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-body-secondary text-body-secondary border">Guardada</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </details>
<?php endif; ?>

<div style="height: 1rem;"></div>

<?php // ================= Barra inferior: añadir (o copiar, en modo selección) ================= ?>
<div class="barra-inferior">
    <div data-barra-anadir class="position-relative">
        <div class="input-group">
            <span class="input-group-text bg-body"><i class="bi bi-plus-lg"></i></span>
            <input type="text" class="form-control" data-buscador-anadir autocomplete="off" enterkeyhint="done"
                placeholder="Añadir pieza…" title="Busca en el catálogo o escribe una pieza nueva (futura)">
        </div>
        <div class="list-group shadow resultados-busqueda d-none" data-resultados-anadir></div>
    </div>

    <?php // Las casillas viven en cada línea y se asocian a este form con el
          // atributo `form`. Envío normal: al copiar se salta al pedido destino. ?>
    <form id="form-copiar-lineas" method="post" action="<?= site_url('piezas/pedido/' . $pedido['id'] . '/copiar-lineas') ?>"
        class="d-none align-items-center gap-2 flex-wrap" data-barra-copiar>
        <?= csrf_field() ?>
        <span class="small text-nowrap"><strong data-contador-copiar>0</strong> sel. · copiar a</span>
        <select name="destino" class="form-select form-select-sm w-auto flex-grow-1" style="max-width: 16rem;">
            <option value="nuevo">un pedido nuevo</option>
            <?php foreach ($pedidosAbiertos as $abierto): ?>
                <option value="<?= (int) $abierto['id'] ?>">
                    #<?= (int) $abierto['id'] ?><?= $abierto['referencia_externa'] ? ' · ' . esc($abierto['referencia_externa']) : '' ?><?= $abierto['notas'] ? ' · ' . esc(mb_strimwidth($abierto['notas'], 0, 30, '…')) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3" data-boton-copiar disabled
            title="Copia solo lo que falta (cantidad − completado); este pedido no cambia">
            <i class="bi bi-files"></i> Copiar
        </button>
        <button type="button" class="btn btn-sm btn-link text-body-secondary px-1" data-seleccionar-todas>Todas</button>
    </form>
</div>

<?php // Piezas en marcha que aún no están en el pedido (sugerenciasParaPedido):
      // un toque y se añaden con cantidad 1. Fuera de la barra fija y justo
      // debajo de ella: así salen TODAS sin tope de alto y sin tapar las
      // líneas mientras se hace scroll; al llegar al final quedan bajo el
      // buscador. ?>
<?php if (!empty($sugerencias)): ?>
    <div class="sugerencias" data-sugerencias>
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
<?php endif; ?>

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
                    placeholder="nº de pedido, si viene de fuera" maxlength="50">
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

        // Chip "Lista para imprimir": cambio optimista, se deshace si falla.
        var chip = e.target.closest('[data-chip-hecha]');
        if (chip) {
            e.stopPropagation();
            var marcar = linea.dataset.hecha !== '1';
            pintarChip(linea, chip, marcar);
            chip.disabled = true;
            post(linea.dataset.urlHecha, marcar ? { hecha: 1 } : {})
                .then(function (d) { if (!d.ok) { pintarChip(linea, chip, !marcar); fallo(d, 'No se pudo actualizar.'); } })
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

    function abrirPanel(linea) {
        lineaEnPanel = linea;
        var d = linea.dataset;
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
