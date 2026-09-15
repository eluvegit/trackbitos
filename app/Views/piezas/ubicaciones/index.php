<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    /* Huecos como casillas cuadradas dentro de su estuche, en vez de la
       lista de badges anterior — se ve de un vistazo cuántos hay y cuáles
       tienen stock, como el propio estuche físico rotulado. */
    .hueco-grid { display: flex; flex-wrap: wrap; gap: .65rem; }

    .hueco-tile-wrap { position: relative; }

    .hueco-tile {
        /* Ya no es una casilla cuadrada fija: crece a lo alto (con tope)
           para que quepan los nombres de las piezas que contiene, en vez
           de esconderlos detrás de un clic. */
        width: 9.5rem;
        min-height: 4.75rem;
        max-height: 11rem;
        padding: .5rem .65rem;
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
    .hueco-tile .uds { font-size: .68rem; color: var(--bs-secondary-color); white-space: nowrap; }
    .hueco-tile.con-stock { border-color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .12); }
    .hueco-tile .piezas { font-size: .7rem; line-height: 1.3; overflow-y: auto; }
    .hueco-tile .piezas .fila { display: flex; justify-content: space-between; gap: .4rem; }
    .hueco-tile .piezas .nombre { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hueco-tile .piezas .cant { color: var(--bs-secondary-color); white-space: nowrap; }
    .hueco-tile .vacio { font-size: .7rem; color: var(--bs-secondary-color); font-style: italic; }

    .hueco-del-form { position: absolute; top: -.5rem; right: -.5rem; margin: 0; }
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

    .hueco-add {
        width: 9.5rem;
        height: 4.75rem;
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
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-geo-alt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Ubicaciones</strong>

    <a href="<?= site_url('piezas/ubicaciones/imprimir') ?>" target="_blank" class="btn btn-sm btn-outline-secondary ms-auto" title="Documento para imprimir: qué hay en cada estuche y dónde está cada pieza">
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

<?php if (empty($filas)): ?>
    <p class="text-muted small">Todavía no hay estuches. Crea el primero con «Nuevo estuche» y luego añade huecos dentro.</p>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($filas as $f): ?>
            <?php $estuche = $f['estuche']; ?>
            <div class="card estuche-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-archive fs-5 text-primary"></i>
                            <span class="estuche-titulo"><?= esc($estuche['codigo']) ?></span>
                            <?php if ($estuche['zona']): ?>
                                <span class="badge text-bg-secondary fw-normal"><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                                data-bs-toggle="modal" data-bs-target="#modalEditarEstuche<?= (int) $estuche['id'] ?>" title="Editar estuche">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="post" action="<?= site_url('piezas/ubicaciones/' . (int) $estuche['id'] . '/borrar') ?>"
                                onsubmit="return confirm('¿Borrar el estuche «<?= esc($estuche['codigo'], 'js') ?>» y todos sus huecos? El historial de stock que tuvieran queda sin asignar, no se pierde.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Borrar estuche">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="hueco-grid">
                        <?php foreach ($f['huecos'] as $h): ?>
                            <div class="hueco-tile-wrap">
                                <a href="<?= site_url('piezas/ubicaciones/huecos/' . (int) $h['hueco']['id']) ?>"
                                    class="hueco-tile<?= $h['unidades'] > 0 ? ' con-stock' : '' ?>">
                                    <div class="cabecera">
                                        <span class="codigo"><?= esc($h['codigo']) ?></span>
                                        <?php if ($h['unidades'] > 0): ?>
                                            <span class="uds"><?= (int) $h['unidades'] ?> uds.</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($h['contenido'] === []): ?>
                                        <span class="vacio">Vacío</span>
                                    <?php else: ?>
                                        <div class="piezas">
                                            <?php foreach ($h['contenido'] as $c): ?>
                                                <div class="fila">
                                                    <span class="nombre"><?= esc($c['nombre']) ?></span>
                                                    <span class="cant"><?= (int) $c['stock'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $h['hueco']['id'] . '/borrar') ?>"
                                    class="hueco-del-form"
                                    onsubmit="return confirm('¿Quitar el hueco «<?= esc($h['codigo'], 'js') ?>»?<?= $h['unidades'] > 0 ? ' Tiene ' . (int) $h['unidades'] . ' uds. — el historial de stock queda sin asignar, no se pierde.' : '' ?>');">
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

<?= $this->endSection() ?>
