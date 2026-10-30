<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<style>
    /* Huecos como casillas cuadradas dentro de su estuche, en vez de la
       lista de badges anterior — se ve de un vistazo cuántos hay y cuáles
       tienen stock, como el propio estuche físico rotulado. */
    .hueco-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); grid-auto-rows: 1fr; gap: .65rem; }

    .hueco-tile-wrap { position: relative; }

    .hueco-tile {
        /* Ya no es una casilla cuadrada fija: crece a lo alto (con tope)
           para que quepan los nombres de las piezas que contiene, en vez
           de esconderlos detrás de un clic. Algo más ancha que antes para
           que quepan también las miniaturas junto al nombre. */
        width: 100%;
        height: 100%;
        min-height: 4.75rem;
        /* Más hueco a la izquierda que a la derecha: ahí es donde se
           monta encima la "x" de borrar (ver .hueco-del-form). */
        padding: .5rem .65rem .5rem 1.4rem;
        border-radius: .85rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
        display: flex;
        flex-direction: column;
        align-items: stretch;
        text-align: left;
        text-decoration: none;
        color: var(--bs-body-color);
        overflow: hidden;
        transition: transform .12s ease, border-color .12s ease, background-color .12s ease;
    }
    .hueco-tile:hover {
        border-color: var(--bs-primary);
        color: var(--bs-body-color);
        transform: translateY(-2px);
    }
    .hueco-tile .cabecera { display: flex; align-items: baseline; justify-content: space-between; gap: .4rem; margin-bottom: .3rem; }
    .hueco-tile .codigo { font-weight: 700; font-size: .95rem; line-height: 1; }
    .hueco-tile.con-stock { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .12); }
    .hueco-tile .piezas { font-size: .75rem; line-height: 1.3; display: flex; flex-direction: column; gap: .3rem; }
    .hueco-tile .piezas .fila { display: flex; align-items: center; gap: .4rem; }
    .hueco-tile .piezas .nombre-grupo { display: flex; align-items: center; gap: .3rem; min-width: 0; flex: 1 1 auto; }
    .hueco-tile .piezas .nombre-grupo { gap: .45rem; }
    .hueco-tile .piezas .miniatura { width: 3rem; height: 3rem; object-fit: cover; border-radius: .4rem; flex: none; }
    .hueco-tile .piezas .nombre { min-width: 0; overflow-wrap: anywhere; }
    .hueco-tile .total { font-size: .7rem; color: var(--bs-secondary-color); white-space: nowrap; }
    .hueco-tile .piezas .cantidad { flex: none; margin-left: auto; font-weight: 700; font-size: .8rem; min-width: 1.6rem; text-align: right; font-variant-numeric: tabular-nums; }
    .hueco-tile .piezas .cantidad.cero { color: var(--bs-danger); }
    .hueco-tile-wrap:has(.huerfanas), .hueco-tile.huerfanas { grid-column: 1 / -1; }
    .hueco-tile.huerfanas, .hueco-tile.huerfanas:hover { width: 100%; padding-left: .65rem; transform: none; border-color: var(--bs-border-color); }
    .hueco-tile.huerfanas .piezas { display: grid; grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); column-gap: 1.25rem; row-gap: .2rem; font-size: .75rem; }
    .hueco-tile.huerfanas .nombre-grupo:hover .nombre { text-decoration: underline; }
    .hueco-tile .piezas .btn-baja { border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); color: inherit; border-radius: .4rem; padding: .05rem .45rem; line-height: 1.3; touch-action: manipulation; }
    .hueco-tile .piezas .btn-baja:hover { border-color: var(--bs-danger); color: var(--bs-danger); }
    @media (min-width: 992px) {
        .hueco-tile.huerfanas .piezas { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: .45rem; font-size: .9rem; }
        .hueco-tile.huerfanas .miniatura { width: 3.2rem; height: 3.2rem; border-radius: .4rem; }
        .hueco-tile.huerfanas .cantidad { font-size: 1rem; }
    }
    .hueco-tile .vacio { font-size: .7rem; color: var(--bs-secondary-color); font-style: italic; }

    .hueco-del-form { position: absolute; top: -.5rem; left: -.5rem; margin: 0; }
    .hueco-del-form .btn-del {
        /* Círculo visual pequeño, pero área táctil real más grande (padding
           en vez de solo el tamaño del círculo) para que sea tocable con el
           dedo sin acertar por casualidad. */
        width: 1.6rem;
        height: 1.6rem;
        padding: 0;
        line-height: 1;
        border-radius: 50%;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        color: var(--bs-secondary-color);
        font-size: .8rem;
        touch-action: manipulation;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .hueco-del-form .btn-del:hover { background: var(--bs-danger); border-color: var(--bs-danger); color: #fff; }

    .hueco-grid > form { margin: 0; }
    .hueco-add {
        width: 100%;
        height: 100%;
        min-height: 4.75rem;
        border-radius: .85rem;
        border: 1.5px dashed var(--bs-border-color);
        background: transparent;
        color: var(--bs-secondary-color);
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .12s ease;
    }
    .hueco-add:hover { border-style: solid; border-color: var(--bs-primary); color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .08); }
    .hueco-add small { font-size: .6rem; }

    .estuche-card { border-radius: 1rem; }
    .estuche-titulo { font-size: 1.15rem; font-weight: 700; letter-spacing: .02em; }

    /* En móvil, un hueco por fila a todo el ancho: la casilla fija de
       13rem obligaba a leer el contenido apretado o a hacer scroll
       horizontal para ver el resto. */
    @media (max-width: 575.98px) {
        .hueco-grid { grid-template-columns: 1fr; grid-auto-rows: auto; }
    }

    .estuche-toggle {
        border: 0;
        background: transparent;
        width: 100%;
        text-align: left;
        color: inherit;
        border-radius: .5rem;
        margin: -.25rem -.4rem;
        padding: .25rem .4rem;
    }
    .estuche-toggle:hover { background: var(--bs-secondary-bg); }
    .estuche-toggle .chevron { margin-left: auto; color: var(--bs-secondary-color); transition: transform .15s ease; }
    .estuche-toggle.colapsado .chevron { transform: rotate(-90deg); }
    .caja-card { border: 1.5px dashed var(--bs-warning); background: rgba(var(--bs-warning-rgb), .06); }
    .caja-card .hueco-tile.huerfanas { background: transparent; border-style: dashed; }
    .caja-card .destino { font-size: .7rem; color: var(--bs-secondary-color); white-space: nowrap; }
    .caja-card .destino.sin { color: var(--bs-warning-text-emphasis); }
    .caja-card .hueco-tile.huerfanas .piezas { grid-template-columns: 1fr; }
    .caja-card .fila { flex-wrap: wrap; }
    .caja-card .acciones-caja { display: flex; gap: .3rem; flex: none; }
    .caja-card .acciones-caja form { margin: 0; }
    .hueco-tile.huerfanas .fila:has(.acciones-sin) { flex-wrap: wrap; }
    .acciones-sin { display: flex; gap: .3rem; order: 3; flex: 0 0 100%; justify-content: flex-end; margin-top: .15rem; }
    .acciones-sin .btn { padding: .05rem .4rem; font-size: .75rem; }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-geo-alt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Ubicaciones</strong>

    <a href="<?= site_url('piezas/salidas') ?>" class="btn btn-sm btn-outline-secondary ms-auto" title="Albaranes de salida">
        <i class="bi bi-receipt"></i> Albaranes
    </a>
    <a href="<?= site_url('piezas/ubicaciones/cajas') ?>" class="btn btn-sm btn-outline-secondary" title="Impresas y de pedido pendientes de guardar">
        <i class="bi bi-box-seam"></i> Cajas
    </a>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-plegar-todo>
        <i class="bi bi-arrows-collapse"></i> <span>Plegar todo</span>
    </button>
    <a href="<?= site_url('piezas/ubicaciones/imprimir') ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Documento para imprimir: qué hay en cada estuche y dónde está cada pieza">
        <i class="bi bi-printer"></i> Imprimir
    </a>
    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEstuche">
        <i class="bi bi-plus-lg"></i> Nuevo estuche
    </button>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php
$titulosCaja = [
    'impresas' => ['Caja de impresas', 'bi-box-seam'],
    'pedido'   => ['Caja de pedido', 'bi-bag'],
];
?>
<div class="d-flex flex-column gap-3 mb-3">
    <?php foreach ($cajas as $caja => $piezas): ?>
        <?php [$titulo, $icono] = $titulosCaja[$caja]; ?>
        <div class="card estuche-card caja-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 flex-wrap<?= $piezas !== [] ? ' mb-3' : '' ?>">
                    <button type="button" class="estuche-toggle d-flex align-items-center gap-2 flex-wrap w-auto flex-grow-1"
                        data-estuche-id="caja-<?= $caja ?>" title="Plegar/desplegar">
                        <i class="bi <?= $icono ?> fs-5 text-warning"></i>
                        <span class="estuche-titulo"><?= $titulo ?></span>
                        <span class="badge text-bg-warning fw-normal">Caja</span>
                        <span class="text-muted small">
                            <?= $piezas === [] ? 'vacía' : count($piezas) . ' piezas · ' . array_sum(array_column($piezas, 'cantidad')) . ' ud.' ?>
                        </span>
                        <?php if ($piezas !== []): ?><i class="bi bi-chevron-down chevron"></i><?php endif; ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalMeterCaja"
                        data-caja="<?= $caja ?>" data-titulo="<?= esc($titulo, 'attr') ?>" title="Pasar a esta caja piezas que están en un hueco o sin ubicación">
                        <i class="bi bi-box-arrow-in-down"></i> Meter piezas
                    </button>
                </div>

                <?php if ($piezas !== []): ?>
                    <div class="hueco-grid" id="huecos-caja-<?= $caja ?>">
                        <div class="hueco-tile huerfanas">
                            <div class="piezas">
                                <?php foreach ($piezas as $c): ?>
                                    <div class="fila">
                                        <a href="<?= site_url('piezas/existencias/' . (int) $c['id']) ?>" class="nombre-grupo text-reset text-decoration-none">
                                            <?php if ($c['imagen']): ?>
                                                <img src="<?= esc($c['imagen'], 'attr') ?>" alt="" class="miniatura" loading="lazy">
                                            <?php endif; ?>
                                            <span>
                                                <span class="nombre d-block"><?= esc($c['nombre']) ?></span>
                                                <?php if ($c['hueco'] !== null): ?>
                                                    <span class="destino"><i class="bi bi-arrow-right-short"></i><?= esc($c['hueco']) ?></span>
                                                <?php else: ?>
                                                    <span class="destino sin"><i class="bi bi-exclamation-triangle"></i> sin hueco</span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                        <span class="cantidad<?= (int) $c['cantidad'] <= 0 ? ' cero' : '' ?>"><?= (int) $c['cantidad'] ?></span>
                                        <?php if ((int) $c['cantidad'] > 0): ?>
                                            <span class="acciones-caja">
                                                <?php if ($c['hueco'] !== null): ?>
                                                    <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="volver" value="ubicaciones">
                                                        <input type="hidden" name="caja" value="<?= $caja ?>">
                                                        <input type="hidden" name="variante_id" value="<?= (int) $c['id'] ?>">
                                                        <input type="hidden" name="cantidad" value="<?= (int) $c['cantidad'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary" title="Guardar las <?= (int) $c['cantidad'] ?> en <?= esc($c['hueco'], 'attr') ?>">
                                                            <i class="bi bi-arrow-down-square"></i> <?= esc($c['hueco']) ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm <?= $c['hueco'] === null ? 'btn-primary' : 'btn-outline-secondary' ?>"
                                                    data-bs-toggle="modal" data-bs-target="#modalGuardarCaja"
                                                    data-caja="<?= $caja ?>" data-variante="<?= (int) $c['id'] ?>" data-nombre="<?= esc($c['nombre'], 'attr') ?>"
                                                    data-cantidad="<?= (int) $c['cantidad'] ?>" data-con-hueco="<?= $c['hueco'] !== null ? '1' : '0' ?>"
                                                    title="<?= $c['hueco'] === null ? 'Elegir hueco y guardar' : 'Guardar solo algunas' ?>">
                                                    <?php if ($c['hueco'] === null): ?><i class="bi bi-geo-alt"></i> Elegir hueco<?php else: ?><i class="bi bi-123"></i><?php endif; ?>
                                                </button>
                                                <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/cambiar') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="volver" value="ubicaciones">
                                                    <input type="hidden" name="caja" value="<?= $caja ?>">
                                                    <input type="hidden" name="variante_id" value="<?= (int) $c['id'] ?>">
                                                    <input type="hidden" name="cantidad" value="<?= (int) $c['cantidad'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                                        title="Pasar las <?= (int) $c['cantidad'] ?> a la caja de <?= $caja === 'impresas' ? 'pedido' : 'impresas' ?>">
                                                        <i class="bi <?= $caja === 'impresas' ? 'bi-bag' : 'bi-box-seam' ?>"></i> <?= $caja === 'impresas' ? 'A pedido' : 'A impresas' ?>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#modalSalidaCaja"
                                                    data-caja="<?= $caja ?>" data-variante="<?= (int) $c['id'] ?>" data-nombre="<?= esc($c['nombre'], 'attr') ?>"
                                                    data-cantidad="<?= (int) $c['cantidad'] ?>" title="Alguien se lleva algunas o todas">
                                                    <i class="bi bi-box-arrow-right"></i> Se las lleva
                                                </button>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($filas)): ?>
    <p class="text-muted small">Todavía no hay estuches. Crea el primero con «Nuevo estuche» y luego añade huecos dentro.</p>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($filas as $f): ?>
            <?php $estuche = $f['estuche']; ?>
            <div class="card estuche-card">
                <div class="card-body">
                    <button type="button" class="estuche-toggle d-flex align-items-center gap-2 flex-wrap mb-3"
                        data-estuche-id="<?= (int) $estuche['id'] ?>" title="Plegar/desplegar huecos">
                        <i class="bi bi-archive fs-5 text-primary"></i>
                        <span class="estuche-titulo"><?= esc($estuche['codigo']) ?></span>
                        <?php if ($estuche['zona']): ?>
                            <span class="badge text-bg-secondary fw-normal"><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span>
                        <?php endif; ?>
                        <span class="text-muted small">
                            <?= count($f['huecos']) ?> huecos
                        </span>
                        <i class="bi bi-chevron-down chevron"></i>
                    </button>

                    <div class="hueco-grid" id="huecos-<?= (int) $estuche['id'] ?>">
                        <?php foreach ($f['huecos'] as $h): ?>
                            <div class="hueco-tile-wrap">
                                <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $h['hueco']['id']) ?>"
                                    class="hueco-tile<?= $h['contenido'] !== [] ? ' con-stock' : '' ?>">
                                    <div class="cabecera">
                                        <span class="codigo"><?= esc($h['codigo']) ?></span>
                                        <?php if ($h['contenido'] !== []): ?>
                                            <span class="total"><?= (int) $h['unidades'] ?> ud.</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($h['contenido'] === []): ?>
                                        <span class="vacio">Vacío</span>
                                    <?php else: ?>
                                        <div class="piezas">
                                            <?php foreach ($h['contenido'] as $c): ?>
                                                <div class="fila">
                                                    <span class="nombre-grupo">
                                                        <?php if ($c['img']): ?>
                                                            <img src="<?= esc($c['img'], 'attr') ?>" alt="" class="miniatura" loading="lazy">
                                                        <?php endif; ?>
                                                        <span class="nombre"><?= esc($c['nombre']) ?></span>
                                                    </span>
                                                    <span class="cantidad<?= (int) $c['stock'] <= 0 ? ' cero' : '' ?>"><?= (int) $c['stock'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $h['hueco']['id'] . '/borrar') ?>"
                                    class="hueco-del-form"
                                    onsubmit="return confirm('¿Quitar el hueco «<?= esc($h['codigo'], 'js') ?>»?<?= $h['contenido'] !== [] ? ' Lo que había aquí queda sin ubicación, no se pierde.' : '' ?>');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-del" title="Quitar hueco">&times;</button>
                                </form>
                            </div>
                        <?php endforeach; ?>

                        <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/huecos/crear') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="hueco-add" title="Añadir hueco (<?= esc($f['siguiente']) ?>)">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </form>
                    </div>

                    <div class="text-start mt-2">
                        <button type="button" class="btn btn-sm btn-link link-secondary small text-decoration-none p-0"
                            data-bs-toggle="modal" data-bs-target="#modalEditarEstuche<?= (int) $estuche['id'] ?>">Editar</button>
                        <span class="text-muted small"> · </span>
                        <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/borrar') ?>" class="d-inline"
                            onsubmit="return confirm('¿Borrar el estuche «<?= esc($estuche['codigo'], 'js') ?>» y todos sus huecos? El historial de stock que tuvieran queda sin asignar, no se pierde.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-link link-secondary small text-decoration-none p-0">Borrar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalEditarEstuche<?= (int) $estuche['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/actualizar') ?>" class="modal-content">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h6 class="modal-title">Editar estuche</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small mb-1">Código</label>
                                <input type="text" name="codigo" required maxlength="20" class="form-control"
                                    value="<?= esc($estuche['codigo']) ?>" autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Zona (opcional)</label>
                                <input type="text" name="zona" maxlength="100" class="form-control"
                                    value="<?= esc((string) $estuche['zona']) ?>" autocomplete="off">
                            </div>
                            <div class="mb-1">
                                <label class="form-label small mb-1">Notas (opcional)</label>
                                <textarea name="notas" rows="2" class="form-control"><?= esc((string) $estuche['notas']) ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$gruposHuerfanas = [
    ['clave' => 'sin-hueco-con', 'titulo' => 'Sin ubicación · con unidades', 'icono' => 'text-warning',
        'piezas' => array_filter($huerfanas, static fn ($c) => $c['stock'] > 0)],
    ['clave' => 'sin-hueco-cero', 'titulo' => 'Sin ubicación · sin unidades', 'icono' => 'text-secondary',
        'piezas' => array_filter($huerfanas, static fn ($c) => $c['stock'] <= 0)],
];
?>
<?php foreach ($gruposHuerfanas as $g): ?>
    <?php if ($g['piezas'] === []) continue; ?>
    <div class="card estuche-card mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <button type="button" class="estuche-toggle d-flex align-items-center gap-2 flex-wrap w-auto flex-grow-1"
                    data-estuche-id="<?= $g['clave'] ?>" title="Plegar/desplegar">
                    <i class="bi bi-question-square fs-5 <?= $g['icono'] ?>"></i>
                    <span class="estuche-titulo"><?= $g['titulo'] ?></span>
                    <span class="text-muted small">
                        <?= count($g['piezas']) ?> piezas<?php if ($g['clave'] === 'sin-hueco-con'): ?> · <span data-total-con><?= array_sum(array_column($g['piezas'], 'stock')) ?></span> ud.<?php endif; ?>
                    </span>
                    <i class="bi bi-chevron-down chevron"></i>
                </button>
                <?php if ($g['clave'] === 'sin-hueco-con'): ?>
                    <form method="post" action="<?= site_url('piezas/ubicaciones/sin-ubicacion/a-caja') ?>" class="input-group input-group-sm w-auto"
                        onsubmit="return confirm('¿Pasar las <?= (int) array_sum(array_column($g['piezas'], 'stock')) ?> ud. sin ubicación a la caja de ' + this.caja.value + '?');">
                        <?= csrf_field() ?>
                        <select name="caja" class="form-select form-select-sm">
                            <option value="impresas">a impresas</option>
                            <option value="pedido">a pedido</option>
                        </select>
                        <button type="submit" class="btn btn-outline-warning"><i class="bi bi-box-seam"></i> Todo a caja</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="hueco-grid" id="huecos-<?= $g['clave'] ?>">
                <div class="hueco-tile huerfanas">
                    <div class="piezas">
                        <?php foreach ($g['piezas'] as $c): ?>
                            <div class="fila">
                                <a href="<?= site_url('piezas/existencias/' . $c['id']) ?>" class="nombre-grupo text-reset text-decoration-none">
                                    <?php if ($c['img']): ?>
                                        <img src="<?= esc($c['img'], 'attr') ?>" alt="" class="miniatura" loading="lazy">
                                    <?php endif; ?>
                                    <span class="nombre"><?= esc($c['nombre']) ?></span>
                                </a>
                                <span class="acciones-sin">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Asignarle hueco"
                                        data-bs-toggle="modal" data-bs-target="#modalColocar"
                                        data-variante="<?= (int) $c['id'] ?>" data-nombre="<?= esc($c['nombre'], 'attr') ?>">
                                        <i class="bi bi-geo-alt"></i> Hueco
                                    </button>
                                    <?php if ((int) $c['stock'] > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning" title="Pasar a una caja"
                                            data-bs-toggle="modal" data-bs-target="#modalACaja"
                                            data-variante="<?= (int) $c['id'] ?>" data-nombre="<?= esc($c['nombre'], 'attr') ?>" data-cantidad="<?= (int) $c['stock'] ?>">
                                            <i class="bi bi-box-seam"></i> A caja
                                        </button>
                                    <?php endif; ?>
                                </span>
                                <?php if ((int) $c['stock'] > 0): ?>
                                    <button type="button" class="cantidad btn-baja" title="Dar de baja"
                                        data-bs-toggle="modal" data-bs-target="#modalBaja"
                                        data-variante="<?= (int) $c['id'] ?>" data-nombre="<?= esc($c['nombre'], 'attr') ?>" data-stock="<?= (int) $c['stock'] ?>"><?= (int) $c['stock'] ?></button>
                                <?php else: ?>
                                    <span class="cantidad cero"><?= (int) $c['stock'] ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="modalGuardarCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/guardar') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="ubicaciones">
            <input type="hidden" name="caja">
            <input type="hidden" name="variante_id">
            <div class="modal-header">
                <h6 class="modal-title">Guardar en hueco</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small fw-semibold mb-2" data-nombre></p>
                <div class="mb-2">
                    <label class="form-label small mb-1">Cantidad</label>
                    <input type="number" name="cantidad" min="1" class="form-control form-control-sm" required>
                </div>
                <div data-elegir-hueco>
                    <label class="form-label small mb-1">Hueco</label>
                    <select name="hueco_id" class="form-select form-select-sm">
                        <option value="">Elige…</option>
                        <?php foreach ($huecos as $hid => $codigo): ?>
                            <option value="<?= (int) $hid ?>"><?= esc($codigo) ?><?= isset($huecosLibres[$hid]) ? ' (libre)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pasa a ser el hueco de esta pieza.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-arrow-down-square"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalColocar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" action="<?= site_url('piezas/ubicaciones/colocar') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="variante_id">
            <div class="modal-header">
                <h6 class="modal-title">Asignar hueco</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small fw-semibold mb-2" data-nombre></p>
                <select name="hueco_id" class="form-select form-select-sm" required>
                    <option value="">Elige…</option>
                    <?php foreach ($huecos as $hid => $codigo): ?>
                        <option value="<?= (int) $hid ?>"><?= esc($codigo) ?><?= isset($huecosLibres[$hid]) ? ' (libre)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Pasa a ser su hueco, con todas sus unidades.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary">Colocar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalACaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/meter') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="ubicaciones">
            <input type="hidden" name="variante_id">
            <div class="modal-header">
                <h6 class="modal-title">Pasar a una caja</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small fw-semibold mb-2" data-nombre></p>
                <div class="mb-2">
                    <label class="form-label small mb-1">Caja</label>
                    <select name="caja" class="form-select form-select-sm">
                        <option value="impresas">Caja de impresas</option>
                        <option value="pedido">Caja de pedido</option>
                    </select>
                </div>
                <label class="form-label small mb-1">Cantidad</label>
                <input type="number" name="cantidad" min="1" class="form-control form-control-sm" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-warning">Meter</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        ['modalColocar', 'modalACaja'].forEach(function (id) {
            var modal = document.getElementById(id);
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (e) {
                var b = e.relatedTarget, f = modal.querySelector('form');
                f.variante_id.value = b.dataset.variante;
                modal.querySelector('[data-nombre]').textContent = b.dataset.nombre;
                if (f.cantidad) {
                    f.cantidad.value = b.dataset.cantidad;
                    f.cantidad.max = b.dataset.cantidad;
                }
            });
        });
    })();
</script>

<div class="modal fade" id="modalMeterCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/meter') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="ubicaciones">
            <input type="hidden" name="caja">
            <input type="hidden" name="variante_id">
            <div class="modal-header">
                <h6 class="modal-title">Meter en <span data-titulo></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Busca la pieza (nombre o SKU)…" data-buscar autocomplete="off">
                <div class="list-group list-group-flush small mb-2" data-resultados></div>
                <div class="d-none" data-elegida>
                    <p class="small fw-semibold mb-2" data-nombre></p>
                    <label class="form-label small mb-1">Cantidad</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="cantidad" min="1" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" data-todas>Todas</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-warning" data-enviar disabled><i class="bi bi-box-arrow-in-down"></i> Meter</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('modalMeterCaja');
        if (!modal) return;
        var f = modal.querySelector('form'), buscar = modal.querySelector('[data-buscar]'),
            lista = modal.querySelector('[data-resultados]'), elegida = modal.querySelector('[data-elegida]'),
            enviar = modal.querySelector('[data-enviar]'), espera = null;

        modal.addEventListener('show.bs.modal', function (e) {
            f.caja.value = e.relatedTarget.dataset.caja;
            modal.querySelector('[data-titulo]').textContent = e.relatedTarget.dataset.titulo.toLowerCase();
            f.variante_id.value = '';
            buscar.value = '';
            lista.innerHTML = '';
            elegida.classList.add('d-none');
            enviar.disabled = true;
        });
        modal.addEventListener('shown.bs.modal', function () { buscar.focus(); });

        buscar.addEventListener('input', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                var q = buscar.value.trim();
                if (q.length < 2) { lista.innerHTML = ''; return; }
                fetch('<?= site_url('piezas/ubicaciones/cajas/buscar') ?>?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        lista.innerHTML = '';
                        if (!d.resultados.length) {
                            lista.innerHTML = '<div class="text-muted px-1">Nada con unidades fuera de las cajas.</div>';
                        }
                        d.resultados.forEach(function (p) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'list-group-item list-group-item-action d-flex justify-content-between gap-2';
                            b.innerHTML = '<span></span><span class="text-muted text-nowrap"></span>';
                            b.firstChild.textContent = p.nombre;
                            b.lastChild.textContent = (p.hueco || 'sin ubicación') + ' · ' + p.fuera + ' ud.';
                            b.addEventListener('click', function () {
                                f.variante_id.value = p.id;
                                f.cantidad.value = p.fuera;
                                f.cantidad.max = p.fuera;
                                modal.querySelector('[data-nombre]').textContent = p.nombre + ' (' + p.fuera + ' fuera de las cajas)';
                                elegida.classList.remove('d-none');
                                lista.innerHTML = '';
                                enviar.disabled = false;
                                f.cantidad.focus();
                            });
                            lista.appendChild(b);
                        });
                    });
            }, 250);
        });
        modal.querySelector('[data-todas]').addEventListener('click', function () { f.cantidad.value = f.cantidad.max; });
    })();
</script>

<div class="modal fade" id="modalSalidaCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" action="<?= site_url('piezas/ubicaciones/cajas/salida') ?>" class="modal-content"
            onsubmit="return confirm('¿Dar salida a ' + this.cantidad.value + ' pieza(s)? Dejan de estar en el inventario.');">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="ubicaciones">
            <input type="hidden" name="caja">
            <input type="hidden" name="variante_id">
            <div class="modal-header">
                <h6 class="modal-title">Se las lleva</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small fw-semibold mb-2" data-nombre></p>
                <div class="mb-2">
                    <label class="form-label small mb-1">Cantidad</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="cantidad" min="1" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" data-todas>Todas</button>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Quién / adónde</label>
                    <select name="destino_id" class="form-select form-select-sm">
                        <?php foreach ($destinos as $did => $nombreDestino): ?>
                            <option value="<?= (int) $did ?>"><?= esc($nombreDestino) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-1">Nota (opcional)</label>
                    <input type="text" name="nota" maxlength="200" class="form-control form-control-sm" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-right"></i> Dar salida</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('modalSalidaCaja');
        if (!modal) return;
        var f = modal.querySelector('form');
        modal.addEventListener('show.bs.modal', function (e) {
            var b = e.relatedTarget;
            f.caja.value = b.dataset.caja;
            f.variante_id.value = b.dataset.variante;
            f.cantidad.value = 1;
            f.cantidad.max = b.dataset.cantidad;
            f.nota.value = '';
            modal.querySelector('[data-nombre]').textContent = b.dataset.nombre + ' (' + b.dataset.cantidad + ' en la caja)';
        });
        modal.querySelector('[data-todas]').addEventListener('click', function () {
            f.cantidad.value = f.cantidad.max;
        });
    })();
</script>

<script>
    (function () {
        var modal = document.getElementById('modalGuardarCaja');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', function (e) {
            var b = e.relatedTarget, f = modal.querySelector('form');
            var conHueco = b.dataset.conHueco === '1';
            f.caja.value = b.dataset.caja;
            f.variante_id.value = b.dataset.variante;
            f.cantidad.value = b.dataset.cantidad;
            f.cantidad.max = b.dataset.cantidad;
            f.hueco_id.value = '';
            f.hueco_id.required = !conHueco;
            modal.querySelector('[data-elegir-hueco]').classList.toggle('d-none', conHueco);
            modal.querySelector('[data-nombre]').textContent = b.dataset.nombre;
        });
    })();
</script>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast align-items-center border-0" id="toastBaja" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" data-toast-texto></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/existencias/movimiento') ?>" class="modal-content" data-baja-form>
            <?= csrf_field() ?>
            <input type="hidden" name="variante_id" data-baja-variante>
            <input type="hidden" name="sentido" value="baja">
            <input type="hidden" name="volver" value="ubicaciones">
            <div class="modal-header">
                <h6 class="modal-title">Dar de baja · <span data-baja-nombre></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small mb-1">Cantidad <span class="text-muted">(hay <span data-baja-stock></span>)</span></label>
                    <input type="number" name="cantidad" min="1" step="1" value="1" required
                        class="form-control" autocomplete="off" data-baja-cantidad>
                </div>
                <div class="mb-1">
                    <label class="form-label small mb-1">Motivo</label>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach (['Se lo llevó Esther', 'Recuento por descuadre', 'Rotura', 'Se lleva a pintura'] as $sug): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-baja-sugerencia><?= esc($sug) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <textarea name="nota" rows="3" required class="form-control" data-baja-nota
                        placeholder="Por qué se da de baja (rotura, regalo, recuento…)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-danger" data-baja-enviar>Dar de baja</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEstuche" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/ubicaciones/crear') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h6 class="modal-title">Nuevo estuche</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small mb-1">Código</label>
                    <input type="text" name="codigo" required maxlength="20" class="form-control"
                        placeholder="E1" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Zona (opcional)</label>
                    <input type="text" name="zona" maxlength="100" class="form-control"
                        placeholder="Taller / Almacén" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Número de huecos (opcional)</label>
                    <input type="number" name="num_huecos" min="0" max="50" class="form-control"
                        placeholder="10" autocomplete="off">
                    <div class="form-text">Se crean ya rotulados H1, H2… con la nomenclatura habitual. Puedes añadir más luego, uno a uno.</div>
                </div>
                <div class="mb-1">
                    <label class="form-label small mb-1">Notas (opcional)</label>
                    <textarea name="notas" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalBaja = document.getElementById('modalBaja');
        if (modalBaja) {
            var notaBaja = modalBaja.querySelector('[data-baja-nota]');
            var formBaja = modalBaja.querySelector('[data-baja-form]');
            var enviarBaja = modalBaja.querySelector('[data-baja-enviar]');
            var toastEl = document.getElementById('toastBaja');
            var botonOrigen = null;

            function avisar(texto, ok) {
                toastEl.querySelector('[data-toast-texto]').textContent = texto;
                toastEl.classList.toggle('text-bg-success', ok);
                toastEl.classList.toggle('text-bg-danger', !ok);
                bootstrap.Toast.getOrCreateInstance(toastEl).show();
            }

            formBaja.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!botonOrigen) return;
                var cantidad = parseInt(formBaja.cantidad.value, 10) || 0;
                enviarBaja.disabled = true;
                fetch(formBaja.action, {
                    method: 'POST',
                    body: new FormData(formBaja),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.ok) { avisar(res.mensaje || 'No se pudo registrar.', false); return; }
                        var stock = res.stock;
                        if (stock > 0) {
                            botonOrigen.textContent = stock;
                            botonOrigen.dataset.stock = stock;
                        } else {
                            var cero = document.createElement('span');
                            cero.className = 'cantidad cero';
                            cero.textContent = stock;
                            botonOrigen.replaceWith(cero);
                        }
                        var total = document.querySelector('[data-total-con]');
                        if (total) total.textContent = Math.max(0, (parseInt(total.textContent, 10) || 0) - cantidad);
                        bootstrap.Modal.getOrCreateInstance(modalBaja).hide();
                        avisar(res.mensaje, true);
                    })
                    .catch(function () { avisar('Error de conexión, no se registró la baja.', false); })
                    .finally(function () { enviarBaja.disabled = false; });
            });

            modalBaja.addEventListener('show.bs.modal', function (ev) {
                botonOrigen = ev.relatedTarget;
                var d = ev.relatedTarget.dataset;
                modalBaja.querySelector('[data-baja-variante]').value = d.variante;
                modalBaja.querySelector('[data-baja-nombre]').textContent = d.nombre;
                modalBaja.querySelector('[data-baja-stock]').textContent = d.stock;
                var cantidad = modalBaja.querySelector('[data-baja-cantidad]');
                cantidad.value = 1;
                cantidad.max = d.stock;
                notaBaja.value = '';
            });
            modalBaja.querySelectorAll('[data-baja-sugerencia]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    notaBaja.value = btn.textContent.trim();
                    notaBaja.focus();
                });
            });
        }

        // Recuerda qué estuches se han plegado, por estuche, entre visitas
        // — solo en este navegador (localStorage), no afecta a nadie más.
        var plegables = [];
        var plegarTodo = document.querySelector('[data-plegar-todo]');

        function refrescarPlegarTodo() {
            if (!plegarTodo) return;
            var todos = plegables.length > 0 && plegables.every(function (p) { return p.plegado(); });
            plegarTodo.querySelector('span').textContent = todos ? 'Desplegar todo' : 'Plegar todo';
            plegarTodo.querySelector('i').className = 'bi ' + (todos ? 'bi-arrows-expand' : 'bi-arrows-collapse');
        }

        document.querySelectorAll('.estuche-toggle').forEach(function (btn) {
            var grid = document.getElementById('huecos-' + btn.dataset.estucheId);
            if (!grid) return;
            var clave = 'piezas_huecos_colapsado_' + btn.dataset.estucheId;

            function aplicar(colapsado) {
                grid.classList.toggle('d-none', colapsado);
                btn.classList.toggle('colapsado', colapsado);
                try { localStorage.setItem(clave, colapsado ? '1' : '0'); } catch (e) {}
                refrescarPlegarTodo();
            }

            plegables.push({ aplicar: aplicar, plegado: function () { return grid.classList.contains('d-none'); } });

            var colapsado = false;
            try { colapsado = localStorage.getItem(clave) === '1'; } catch (e) {}
            aplicar(colapsado);

            btn.addEventListener('click', function () {
                aplicar(!grid.classList.contains('d-none'));
            });
        });

        refrescarPlegarTodo();
        if (plegarTodo) {
            plegarTodo.addEventListener('click', function () {
                var plegar = !plegables.every(function (p) { return p.plegado(); });
                plegables.forEach(function (p) { p.aplicar(plegar); });
            });
        }
    });
</script>

<?= $this->endSection() ?>
