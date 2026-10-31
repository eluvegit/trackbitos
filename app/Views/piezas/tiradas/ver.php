<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-calendar-range text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/tiradas') ?>" class="text-decoration-none text-muted fw-normal">Tiradas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($tirada['nombre']) ?></strong>
    <span class="ms-auto d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPiezas"><i class="bi bi-puzzle"></i> Piezas</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPlacas"><i class="bi bi-list-ol"></i> Placas</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAjustes" title="Ajustes de la tirada"><i class="bi bi-gear"></i></button>
    </span>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<style>
    .tr { --tr-sunk: var(--bs-tertiary-bg); --tr-line: var(--bs-border-color); --tr-muted: var(--bs-secondary-color);
          --tr-amber: #f0a83a; --tr-amber-soft: #3a2c14; --tr-red: #f06a62; --tr-red-soft: #3b1d1c; --tr-ok: #5cc07c;
          display: grid; gap: 1.4rem; }
    .tr h6 { font-weight: 700; margin: 0 0 .6rem; }
    .tr .mono { font-family: var(--bs-font-monospace); font-variant-numeric: tabular-nums; }
    .tr-panel { background: var(--bs-body-bg); border: 1px solid var(--tr-line); border-radius: 10px; padding: 14px 16px; min-width: 0; }
    .tr-controls { display: flex; flex-wrap: wrap; gap: 10px 22px; align-items: center; }
    .tr-step { display: inline-flex; align-items: center; border: 1px solid var(--tr-line); border-radius: 8px; overflow: hidden; }
    .tr-step button { border: 0; background: none; color: inherit; width: 32px; height: 32px; font-weight: 700; }
    .tr-step button:hover { background: var(--tr-sunk); }
    .tr-step output { min-width: 52px; text-align: center; font-weight: 700; }
    .tr-status { border-left: 4px solid var(--tr-ok); background: var(--bs-body-bg); border-radius: 0 10px 10px 0; padding: 10px 16px; }
    .tr-status.warn { border-color: var(--tr-amber); }
    .tr-status.bad { border-color: var(--tr-red); }
    .tr-status strong { font-size: 1.1rem; display: block; }
    .tr-dot { width: 10px; height: 10px; border-radius: 3px; display: inline-block; flex: none; }
    .tr-resin { font: 700 .66rem/1 var(--bs-font-monospace); text-transform: uppercase; padding: 3px 5px; border-radius: 4px; border: 1px solid var(--tr-line); color: var(--tr-muted); }

    .tr-bars { display: grid; gap: 16px; }
    .tr-bar { display: grid; grid-template-columns: 170px 1fr 230px; gap: 12px; align-items: center; }
    .tr-bar .nm { display: flex; align-items: center; gap: 8px; font-weight: 700; }
    .tr-track { position: relative; height: 18px; background: var(--tr-sunk); border-radius: 5px; }
    .tr-track .fill, .tr-track .plan { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 5px; }
    .tr-track .plan { opacity: .28; }
    .tr-track .need { position: absolute; top: -4px; bottom: -4px; width: 2px; background: var(--bs-body-color); }
    .tr-track .need.obj { background: var(--tr-amber); width: 2px; }
    .tr-track .need.obj::after { content: "margen"; color: var(--tr-amber); top: auto; bottom: -15px; }
    .tr-track .need::after { content: "mínimo"; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font: 600 .62rem var(--bs-font-monospace); color: var(--tr-muted); white-space: nowrap; }
    .tr-bar .num { font-size: .88rem; text-align: right; }
    .tr-bar .est { font-size: .78rem; }
    .tr-est-ok { color: var(--tr-ok); } .tr-est-margen { color: var(--tr-amber); } .tr-est-minimo { color: var(--tr-red); }
    .tr-fail { font: 700 .66rem var(--bs-font-monospace); color: var(--tr-red); }
    .tr-lin { display: flex; gap: 4px; margin-bottom: 4px; }
    .tr-dolls { display: flex; flex-wrap: wrap; gap: 10px 28px; align-items: baseline; border-top: 1px solid var(--tr-line); padding-top: 12px; }
    .tr-big { font-size: 2rem; font-weight: 800; line-height: 1; }
    .tr-legend { display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: .82rem; color: var(--tr-muted); }
    .tr-legend i { display: inline-block; width: 14px; height: 10px; border-radius: 2px; vertical-align: -1px; margin-right: 5px; }
    @media (max-width: 640px) { .tr-bar { grid-template-columns: 1fr auto; } .tr-bar .tr-track { grid-column: 1 / -1; grid-row: 2; } }

    .tr-scroll { overflow-x: auto; }
    .tr-gantt { min-width: 620px; display: grid; grid-template-columns: 110px 1fr; row-gap: 6px; position: relative; }
    .tr-glab { font-weight: 700; font-size: .88rem; display: flex; align-items: center; gap: 6px; }
    .tr-gdays, .tr-grow { display: grid; grid-template-columns: repeat(var(--n), 1fr); }
    .tr-gdays span { font: 600 .72rem var(--bs-font-monospace); text-align: center; color: var(--tr-muted); }
    .tr-gdays span.over { color: var(--tr-red); }
    .tr-grow { height: 30px; background: var(--tr-sunk); border-radius: 5px; }
    .tr-gcell { display: flex; gap: 2px; padding: 3px 2px; min-width: 0; }
    .tr-blk { flex: 1; min-width: 0; border-radius: 3px; font: 700 .62rem/24px var(--bs-font-monospace); text-align: center; color: #111; overflow: hidden; border: 0; padding: 0; }
    .tr-blk.pend { background: transparent !important; color: var(--bs-body-color); box-shadow: inset 0 0 0 2px var(--bc); }
    .tr-blk.late { box-shadow: inset 0 0 0 2px var(--tr-red); color: var(--tr-red); }
    .tr-glines { position: absolute; left: 110px; top: 0; bottom: 0; right: 0; pointer-events: none; }
    .tr-vline { position: absolute; top: 0; bottom: 0; width: 0; }
    .tr-vline span { position: absolute; top: -1px; font: 700 .64rem/1.2 var(--bs-font-monospace); white-space: nowrap; padding: 2px 4px; border-radius: 3px; }
    .tr-vline.conf { border-left: 2px dashed var(--tr-amber); }
    .tr-vline.conf span { background: var(--tr-amber-soft); color: var(--tr-amber); transform: translateX(-100%); }
    .tr-vline.dead { border-left: 3px solid var(--tr-red); }
    .tr-vline.dead span { background: var(--tr-red-soft); color: var(--tr-red); transform: translateX(-100%); }
    .tr-vline.today { border-left: 2px solid var(--bs-body-color); }
    .tr-vline.today span { background: var(--bs-body-color); color: var(--bs-body-bg); transform: translateX(2px); }
    .tr-overzone { position: absolute; top: 0; bottom: 0; right: 0; background: repeating-linear-gradient(45deg, transparent 0 6px, var(--tr-red-soft) 6px 12px); opacity: .8; }

    .tr-grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; min-width: 640px; }
    .tr-grid th { font: 700 .7rem var(--bs-font-monospace); text-transform: uppercase; color: var(--tr-muted); padding: 0 6px; }
    .tr-grid td { padding: 0 3px; vertical-align: top; }
    .tr-dayn { font-weight: 800; font-size: 1.1rem; padding-top: 10px !important; white-space: nowrap; }
    .tr-dayn small { display: block; font: 600 .64rem var(--bs-font-monospace); color: var(--tr-muted); }
    tr.past .tr-cell { opacity: .72; }
    tr.isconf .tr-dayn { color: var(--tr-amber); }
    tr.over .tr-dayn { color: var(--tr-red); }
    .tr-cell { display: grid; gap: 2px; width: 100%; min-height: 58px; padding: 7px 9px; border-radius: 7px; background: var(--bs-body-bg); color: inherit; text-align: left;
               border: 1px solid var(--tr-line); border-left: 5px solid var(--bc, var(--tr-line)); }
    .tr-cell .code { font: 700 .82rem var(--bs-font-monospace); }
    .tr-cell .what { font-size: .82rem; color: var(--tr-muted); line-height: 1.25; }
    .tr-cell.done { background: color-mix(in srgb, var(--bc) 14%, var(--bs-body-bg)); }
    .tr-cell.done .code::after { content: " ✓"; color: var(--tr-ok); }
    .tr-cell.late { border-color: var(--tr-red); border-left-color: var(--tr-red); }
    .tr-cell.sel { outline: 2px solid var(--bs-body-color); outline-offset: 1px; }
    .tr-cell.empty { border-style: dashed; border-left-width: 1px; background: transparent; color: var(--tr-muted); font-size: .8rem; align-content: center; }
    .tr-chg { font: 700 .62rem var(--bs-font-monospace); color: var(--tr-amber); text-transform: uppercase; }
    .tr-away { font: 600 .74rem var(--bs-font-monospace); padding: 6px 8px; border-radius: 6px; border: 1px solid var(--tr-line); color: var(--tr-muted); background: none; white-space: nowrap; margin-top: 8px; }
    .tr-away[aria-pressed="true"] { background: var(--tr-red-soft); color: var(--tr-red); border-color: var(--tr-red); }

    .tr-detail { border-left: 5px solid var(--bc, var(--tr-line)); display: grid; gap: 8px; }
    .tr-detail .code { font: 800 1.4rem var(--bs-font-monospace); }
    .tr-why { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; }
    .tr-why article { background: var(--bs-body-bg); border: 1px solid var(--tr-line); border-radius: 10px; padding: 12px 16px; }
    .tr-why h3 { font-size: 1rem; font-weight: 700; margin: 0 0 4px; }
    .tr-why p { margin: 0; font-size: .93rem; }
    .tr-tbl td.n, .tr-tbl th.n { text-align: right; font-variant-numeric: tabular-nums; }
</style>

<div class="tr" id="tr-app"></div>

<?php // ---- Ajustes generales de la tirada ---- ?>
<div class="modal fade" id="modalAjustes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/actualizar') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Ajustes de la tirada</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-12"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?= esc($tirada['nombre']) ?>" required></div>
                <div class="col-sm-6"><label class="form-label">Día 1</label><input type="date" name="fecha_inicio" class="form-control" value="<?= esc((string) $tirada['fecha_inicio']) ?>"></div>
                <div class="col-sm-6"><label class="form-label">Plazo (último día de impresión)</label><input type="number" min="1" name="plazo_dias" class="form-control" value="<?= (int) $tirada['plazo_dias'] ?>"></div>
                <div class="col-sm-4">
                    <label class="form-label">Máquinas</label>
                    <textarea name="maquinas" rows="3" class="form-control mono"><?= esc($tirada['maquinas']) ?></textarea>
                    <div class="form-text">Una por línea, «nombre: resina» con la que empieza.</div>
                </div>
                <div class="col-sm-4"><label class="form-label">Placas por máquina y día</label><input type="number" min="1" max="10" name="placas_dia" class="form-control" value="<?= (int) $tirada['placas_dia'] ?>"></div>
                <div class="col-sm-4">
                    <label class="form-label">De ellas, por correo</label><input type="number" min="0" max="10" name="placas_correo" class="form-control" value="<?= (int) $tirada['placas_correo'] ?>">
                    <div class="form-text">Las primeras del día; las demás necesitan que estés en casa.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Por qué está ordenado así</label>
                    <textarea name="notas" rows="8" class="form-control"><?= esc((string) $tirada['notas']) ?></textarea>
                    <div class="form-text">Un bloque por razón, separados por una línea en blanco; la primera línea de cada bloque es su título.</div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="submit" form="formBorrarTirada" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Borrar tirada</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
<form method="post" id="formBorrarTirada" action="<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/borrar') ?>"
    onsubmit="return confirm('¿Borrar la tirada «<?= esc($tirada['nombre'], 'js') ?>» con sus piezas y placas?');">
    <?= csrf_field() ?>
</form>

<?php // ---- Piezas del encargo ---- ?>
<div class="modal fade" id="modalPiezas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form method="post" action="<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/piezas') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Piezas de la tirada</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">
                    <b>Necesarias</b>: el mínimo que hace falta. <b>Margen</b>: % de más que quieres imprimir para cubrir fallos.
                    <b>Por placa</b>: cuántas caben. <b>Prioridad pintura</b>: 0–9, más alta = antes a pintar (sirve para ordenar la cola).
                    <b>Espera confirmación</b>: no se imprime antes del día de confirmación.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tablaPiezas">
                        <thead><tr><th>Nombre</th><th>Resina</th><th>Necesarias</th><th>Margen %</th><th>Por placa</th><th>Prioridad pintura</th><th>Espera confirmación</th><th>Color</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($piezas as $i => $pz): ?>
                                <tr>
                                    <td><input type="hidden" name="piezas[<?= $i ?>][id]" value="<?= (int) $pz['id'] ?>"><input type="text" name="piezas[<?= $i ?>][nombre]" class="form-control form-control-sm" value="<?= esc($pz['nombre']) ?>"></td>
                                    <td><input type="text" name="piezas[<?= $i ?>][resina]" class="form-control form-control-sm" style="width: 7rem;" value="<?= esc($pz['resina']) ?>"></td>
                                    <td><input type="number" min="0" name="piezas[<?= $i ?>][necesarias]" class="form-control form-control-sm" style="width: 6rem;" value="<?= (int) $pz['necesarias'] ?>"></td>
                                    <td><input type="number" min="0" name="piezas[<?= $i ?>][margen]" class="form-control form-control-sm" style="width: 5rem;" value="<?= (int) $pz['margen'] ?>"></td>
                                    <td><input type="number" min="1" name="piezas[<?= $i ?>][por_placa]" class="form-control form-control-sm" style="width: 6rem;" value="<?= (int) $pz['por_placa'] ?>"></td>
                                    <td><input type="number" min="0" max="9" name="piezas[<?= $i ?>][prioridad]" class="form-control form-control-sm" style="width: 4.5rem;" value="<?= (int) $pz['prioridad'] ?>"></td>
                                    <td><input type="checkbox" name="piezas[<?= $i ?>][espera_confirmacion]" value="1" class="form-check-input" <?= $pz['espera_confirmacion'] ? 'checked' : '' ?>></td>
                                    <td><input type="color" name="piezas[<?= $i ?>][color]" class="form-control form-control-sm form-control-color" value="<?= esc($pz['color']) ?>"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="addPieza"><i class="bi bi-plus-lg"></i> Añadir pieza</button>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar piezas</button></div>
        </form>
    </div>
</div>

<?php // ---- Placas, en orden de cola, con sus líneas de pieza ---- ?>
<div class="modal fade" id="modalPlacas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="post" action="<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/placas') ?>" class="modal-content" id="formPlacas">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Placas en orden de cola</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">
                    Cada máquina coge, de las placas de su resina, la primera de esta lista que ya se pueda imprimir ese día.
                    Una placa lleva una o varias piezas, todas de la misma resina. Las impresas lo siguen estando y conservan sus fallidas.
                </p>
                <?php if ($piezas === []): ?>
                    <p class="text-warning">Define antes las piezas.</p>
                <?php endif; ?>
                <div class="text-danger small mb-2" id="placasError"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tablaPlacas">
                        <thead><tr><th></th><th>Código</th><th>Piezas que lleva</th><th>Por qué va ahí</th><th></th></tr></thead>
                        <tbody>
                            <?php $j = 0; foreach ($placas as $i => $p): ?>
                                <tr data-placa data-i="<?= $i ?>">
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-sm btn-link p-0" data-mover="-1" title="Subir"><i class="bi bi-arrow-up"></i></button>
                                        <button type="button" class="btn btn-sm btn-link p-0" data-mover="1" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                                    </td>
                                    <td><input type="hidden" name="placas[<?= $i ?>][id]" value="<?= (int) $p['id'] ?>"><input type="text" name="placas[<?= $i ?>][codigo]" class="form-control form-control-sm" style="width: 5rem;" value="<?= esc($p['codigo']) ?>"><?= $p['impresa_en'] ? '<span class="small text-success">✓ impresa</span>' : '' ?></td>
                                    <td data-lineas>
                                        <?php foreach ($lineas[(int) $p['id']] ?? [] as $l): $j++; ?>
                                            <div class="tr-lin">
                                                <input type="hidden" name="placas[<?= $i ?>][lineas][<?= $j ?>][id]" value="<?= (int) $l['id'] ?>">
                                                <select name="placas[<?= $i ?>][lineas][<?= $j ?>][pieza_id]" class="form-select form-select-sm" data-pieza style="width: 10rem;">
                                                    <?php foreach ($piezas as $pz): ?>
                                                        <option value="<?= (int) $pz['id'] ?>" <?= (int) $pz['id'] === (int) $l['pieza_id'] ? 'selected' : '' ?>><?= esc($pz['nombre']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="number" min="0" name="placas[<?= $i ?>][lineas][<?= $j ?>][cantidad]" class="form-control form-control-sm" style="width: 5.5rem;" data-cant value="<?= (int) $l['cantidad'] ?>">
                                                <button type="button" class="btn btn-sm btn-link text-danger p-0" data-quitar-linea title="Quitar pieza"><i class="bi bi-x"></i></button>
                                            </div>
                                        <?php endforeach; ?>
                                        <button type="button" class="btn btn-sm btn-link p-0" data-add-linea><i class="bi bi-plus"></i> pieza</button>
                                    </td>
                                    <td><textarea name="placas[<?= $i ?>][motivo]" rows="1" class="form-control form-control-sm" style="min-width: 18rem;"><?= esc((string) $p['motivo']) ?></textarea></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($piezas !== []): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addPlaca"><i class="bi bi-plus-lg"></i> Añadir placa</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ordenarPrioridad" title="Ordena la cola por la prioridad de pintura más alta de cada placa; a igual prioridad, se queda como está"><i class="bi bi-sort-down"></i> Ordenar por prioridad de pintura</button>
                <?php endif; ?>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar placas</button></div>
        </form>
    </div>
</div>

<script>
(function () {
    var CSRF_NOMBRE = '<?= csrf_token() ?>';
    var CSRF_VALOR = '<?= csrf_hash() ?>';
    var URL_AJUSTES = '<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/ajustes') ?>';
    var URL_PLACA = '<?= site_url('piezas/tiradas/placa') ?>/';

    var PLAZO = <?= (int) $tirada['plazo_dias'] ?>;
    var HOY = <?= (int) $hoy ?>;
    var POR_DIA = <?= (int) $tirada['placas_dia'] ?>, POR_CORREO = <?= (int) $tirada['placas_correo'] ?>;
    var MAQ = <?= json_encode($maquinas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var NOTAS = <?= json_encode((string) $tirada['notas'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var PIEZAS = <?= json_encode(array_map(static fn (array $pz) => [
        'id' => (int) $pz['id'], 'nombre' => $pz['nombre'], 'resina' => $pz['resina'], 'nec' => (int) $pz['necesarias'], 'margen' => (int) $pz['margen'],
        'porPlaca' => (int) $pz['por_placa'], 'prio' => (int) $pz['prioridad'], 'espera' => (bool) $pz['espera_confirmacion'], 'color' => $pz['color'],
    ], $piezas), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var PLACAS = <?= json_encode(array_map(static fn (array $p) => [
        'id' => (int) $p['id'], 'codigo' => $p['codigo'], 'motivo' => (string) $p['motivo'], 'impresa' => $p['impresa_en'] !== null,
        'lineas' => array_map(static fn (array $l) => ['id' => (int) $l['id'], 'pieza' => (int) $l['pieza_id'], 'cant' => (int) $l['cantidad'], 'fall' => (int) $l['fallidas']], $lineas[(int) $p['id']] ?? []),
    ], $placas), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    var S = {
        conf: <?= (int) $tirada['dia_confirmacion'] ?>,
        cambio: <?= $tirada['cambio_resina'] ? 'true' : 'false' ?>,
        fuera: <?= json_encode(array_values(array_filter(array_map('intval', explode(',', (string) $tirada['dias_fuera']))))) ?>
    };
    var selected = null;

    var PZ = {}, P = {};
    PIEZAS.forEach(function (pz) { PZ[pz.id] = pz; });
    PLACAS = PLACAS.filter(function (p) { return p.lineas.length; });
    PLACAS.forEach(function (p) { P[p.id] = p; });

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function plural(n, uno, varios) { return n + ' ' + (n === 1 ? uno : varios); }
    function post(url, campos) {
        var datos = new FormData();
        Object.keys(campos).forEach(function (k) { datos.append(k, campos[k]); });
        datos.append(CSRF_NOMBRE, CSRF_VALOR);
        return fetch(url, { method: 'POST', body: datos, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (!d.ok) throw d; return d; });
    }
    function resinaDe(p) { return PZ[p.lineas[0].pieza].resina; }
    function colorDe(p) { return PZ[p.lineas[0].pieza].color; }
    function esperaDe(p) { return p.lineas.some(function (l) { return PZ[l.pieza].espera; }); }
    function pendientesIguales(p, l) {
        return PLACAS.filter(function (o) { return o !== p && !o.impresa && o.lineas.some(function (x) { return x.pieza === l.pieza && x.cant === l.cant; }); }).length;
    }
    function fallidasDe(p) { return p.lineas.reduce(function (a, l) { return a + l.fall; }, 0); }

    // ---- simulación: cada máquina coge de la cola de su resina la primera placa ya imprimible ----
    var RESINAS_MAQ = MAQ.map(function (m) { return m.resina; });
    function simulate(conf, cambio, fuera) {
        var colas = {}, sinMaquina = [];
        PLACAS.forEach(function (p) {
            var r = resinaDe(p);
            if (!cambio && RESINAS_MAQ.indexOf(r) < 0) { sinMaquina.push(p.id); return; }
            (colas[r] = colas[r] || []).push(p.id);
        });
        var actual = RESINAS_MAQ.slice(), rows = [], last = 0, changes = 0;
        function quedan() { return Object.keys(colas).some(function (r) { return colas[r].length; }); }
        function take(r, day) {
            var q = colas[r] || [];
            for (var i = 0; i < q.length; i++) { if (!esperaDe(P[q[i]]) || day >= conf) return q.splice(i, 1)[0]; }
            return null;
        }
        for (var day = 1; day <= 365 && quedan(); day++) {
            var row = { day: day, m: MAQ.map(function () { return new Array(POR_DIA).fill(null); }) };
            for (var s = 0; s < POR_DIA; s++) {
                if (s >= POR_CORREO && fuera.indexOf(day) >= 0) continue;
                MAQ.forEach(function (m, i) {
                    var c = take(actual[i], day), ch = false;
                    if (!c && cambio) {
                        var otras = Object.keys(colas).filter(function (r) { return r !== actual[i]; });
                        for (var k = 0; k < otras.length && !c; k++) { c = take(otras[k], day); if (c) { actual[i] = otras[k]; changes++; ch = otras[k]; } }
                    }
                    if (c) { row.m[i][s] = { id: c, change: ch }; last = day; }
                });
            }
            rows.push(row);
        }
        Object.keys(colas).forEach(function (r) { sinMaquina = sinMaquina.concat(colas[r]); });
        rows.length = last;
        var where = {};
        rows.forEach(function (r) { r.m.forEach(function (slots, i) { slots.forEach(function (c, s) { if (c) where[c.id] = { day: r.day, m: i, s: s }; }); }); });
        return { rows: rows, last: last, changes: changes, where: where, sinMaquina: sinMaquina };
    }
    function latestConf(cambio) {
        for (var c = PLAZO; c >= 1; c--) { var r = simulate(c, cambio, S.fuera); if (r.last <= PLAZO && !r.sinMaquina.length) return c; }
        return 0;
    }

    // ---- cálculos por pieza: buenas = cantidad - fallidas ----
    function cuenta(ids) {
        var r = {};
        PIEZAS.forEach(function (pz) { r[pz.id] = { cant: 0, fall: 0, buenas: 0, placas: 0 }; });
        ids.forEach(function (id) { P[id].lineas.forEach(function (l) { var o = r[l.pieza]; o.cant += l.cant; o.fall += l.fall; o.buenas += l.cant - l.fall; o.placas++; }); });
        return r;
    }
    function objetivo(pz) { return pz.nec + Math.ceil(pz.nec * pz.margen / 100); }
    function balance(pz, plan, hecho) {
        var obj = objetivo(pz), prev = plan.buenas, imp = hecho.buenas;
        var b = { nec: pz.nec, obj: obj, prev: prev, imp: imp, fall: plan.fall, faltaMin: Math.max(0, pz.nec - imp), faltaObj: Math.max(0, obj - imp) };
        if (prev < pz.nec) { b.estado = 'minimo'; b.texto = 'Faltan ' + (pz.nec - prev) + ' para el mínimo: añade placa'; }
        else if (prev < obj) { b.estado = 'margen'; b.texto = 'Margen +' + (prev - pz.nec) + ' de +' + (obj - pz.nec); }
        else { b.estado = 'ok'; b.texto = pz.nec ? 'Margen cubierto +' + (prev - pz.nec) : 'Sin mínimo fijado'; }
        if (plan.fall) b.texto += ' · ' + plan.fall + ' fallidas';
        return b;
    }
    function cubierta(c) {
        var pct = null, lim = null;
        PIEZAS.forEach(function (pz) {
            if (!pz.nec) return;
            var v = Math.min(1, c[pz.id].buenas / pz.nec);
            if (pct === null || v < pct) { pct = v; lim = pz.nombre; }
        });
        return { pct: pct === null ? null : Math.floor(pct * 100), lim: lim };
    }
    function desc(p) {
        return p.lineas.map(function (l) { return l.cant + ' ' + esc(PZ[l.pieza].nombre); }).join(' + ');
    }
    function slotNombre(s) { return s < POR_CORREO ? 'correo' : (POR_DIA - POR_CORREO > 1 ? 'en casa ' + (s - POR_CORREO + 1) : 'en casa'); }

    // ---- render ----
    function render() {
        var sim = simulate(S.conf, S.cambio, S.fuera);
        var alt = simulate(S.conf, !S.cambio, S.fuera);
        var lcNo = latestConf(false), lcSi = latestConf(true);
        var margin = PLAZO - sim.last;

        var allIds = PLACAS.map(function (p) { return p.id; });
        var doneIds = allIds.filter(function (id) { return P[id].impresa; });
        var planIds = Object.keys(sim.where).filter(function (k) { return sim.where[k].day <= HOY; });
        var cPlan = cuenta(allIds), cDone = cuenta(doneIds), cToday = cuenta(planIds);
        var BAL = {};
        PIEZAS.forEach(function (pz) { BAL[pz.id] = balance(pz, cPlan[pz.id], cDone[pz.id]); });
        var bajoMin = PIEZAS.filter(function (pz) { return BAL[pz.id].estado === 'minimo'; });
        var sinMargen = PIEZAS.filter(function (pz) { return BAL[pz.id].estado === 'margen'; });
        var N = Math.max(PLAZO, sim.last, 1);

        var cls = sim.sinMaquina.length || margin < 0 || bajoMin.length ? 'bad' : margin <= 1 || sinMargen.length ? 'warn' : '';
        var head = !PLACAS.length ? 'Todavía no hay placas: defínelas en «Placas».'
            : margin < 0 ? 'No entra en plazo: la última placa sale el día ' + sim.last + ' (' + plural(-margin, 'día', 'días') + ' tarde).'
            : 'La última placa sale el día ' + sim.last + ': ' + plural(margin, 'día', 'días') + ' de margen.';
        var lines = [];
        bajoMin.forEach(function (pz) { lines.push('<b class="text-danger">' + esc(pz.nombre) + ': faltan ' + (pz.nec - BAL[pz.id].prev) + ' para el mínimo (' + pz.nec + ').</b> Añade una placa con ellas.'); });
        sinMargen.forEach(function (pz) { var b = BAL[pz.id]; lines.push('<b style="color:var(--tr-amber)">' + esc(pz.nombre) + ': llega al mínimo, pero el margen es +' + (b.prev - b.nec) + ' de +' + (b.obj - b.nec) + '.</b>' + (b.fall ? ' ' + plural(b.fall, 'fallida', 'fallidas') + '.' : '')); });
        if (sim.sinMaquina.length) lines.push('<b class="text-danger">' + plural(sim.sinMaquina.length, 'placa', 'placas') + ' sin máquina de su resina</b> (' + sim.sinMaquina.map(function (id) { return esc(P[id].codigo); }).join(', ') + '): añade una máquina o permite el cambio de resina.');
        lines.push('Cambios de resina: <b>' + sim.changes + '</b>.');
        if (!S.cambio) {
            if (alt.last < sim.last) lines.push('Permitiendo el cambio de resina terminarías el día ' + alt.last + ' (' + (sim.last - alt.last) + ' antes) con ' + plural(alt.changes, 'cambio', 'cambios') + '.');
            lines.push('Sin cambiar resina, la confirmación puede llegar como tarde el <b>día ' + lcNo + '</b>. Con cambio, hasta el <b>día ' + lcSi + '</b>.');
        } else {
            lines.push('Sin cambio de resina terminarías el día ' + alt.last + '. Con el cambio, la confirmación puede llegar como tarde el <b>día ' + lcSi + '</b> (sin cambio: día ' + lcNo + ').');
        }
        if (lcNo === 0 && lcSi === 0 && PLACAS.length) lines.push('Ni confirmando el día 1 entra en plazo con estas máquinas.');

        var h = '';
        h += '<div class="tr-controls">' +
            '<span class="small text-muted">' + plural(PIEZAS.length, 'pieza', 'piezas') + ' · ' + plural(PLACAS.length, 'placa', 'placas') + ' · ' + plural(MAQ.length, 'máquina', 'máquinas') + ' · plazo día ' + PLAZO + ' · hoy es el <b>día ' + HOY + '</b></span>' +
            '<span class="d-flex align-items-center gap-2"><b class="small">Confirmación</b><span class="tr-step"><button type="button" data-conf="-1" aria-label="Un día antes">−</button><output>día ' + S.conf + '</output><button type="button" data-conf="1" aria-label="Un día después">+</button></span></span>' +
            '<span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="trCambio"' + (S.cambio ? ' checked' : '') + '><label class="form-check-label small fw-semibold" for="trCambio">Permitir cambio de resina cuando una máquina se queda sin nada que imprimir</label></span>' +
            '<span class="small text-muted" id="trMsg"></span></div>';
        h += '<div class="tr-status ' + cls + '"><strong>' + head + '</strong><span class="small">' + lines.join('<br>') + '</span></div>';

        // balance por pieza
        h += '<section><h6>Balance por pieza</h6><div class="tr-panel tr-bars">';
        PIEZAS.forEach(function (pz) {
            var b = BAL[pz.id], esc1 = Math.max(cPlan[pz.id].cant, b.obj, 1), td = cToday[pz.id].buenas;
            h += '<div class="tr-bar"><div class="nm"><span class="tr-dot" style="background:' + pz.color + '"></span>' + esc(pz.nombre) + ' <span class="tr-resin">' + esc(pz.resina) + '</span></div>' +
                '<div class="tr-track" title="Buenas impresas ' + b.imp + ' · previstas hasta hoy ' + td + ' · previstas en total ' + b.prev + '">' +
                '<div class="plan" style="width:' + Math.min(100, td / esc1 * 100) + '%;background:' + pz.color + '"></div>' +
                '<div class="fill" style="width:' + Math.min(100, b.imp / esc1 * 100) + '%;background:' + pz.color + '"></div>' +
                (b.nec ? '<div class="need" style="left:' + Math.min(100, b.nec / esc1 * 100) + '%"></div>' : '') +
                (b.obj > b.nec ? '<div class="need obj" style="left:' + Math.min(100, b.obj / esc1 * 100) + '%"></div>' : '') + '</div>' +
                '<div class="num"><span class="mono">' + b.imp + ' / ' + b.nec + '</span> <span class="text-muted mono">(previstas ' + b.prev + ')</span>' +
                '<div class="est tr-est-' + b.estado + '">' + b.texto + '</div>' +
                (b.faltaMin ? '<div class="est text-muted">Por imprimir buenas: ' + b.faltaMin + ' para el mínimo' + (b.faltaObj > b.faltaMin ? ', ' + b.faltaObj + ' con margen' : '') + '</div>' : '') + '</div></div>';
        });
        h += '<div class="tr-legend"><span><i style="background:var(--bs-body-color)"></i>fuerte: buenas impresas</span><span><i style="background:var(--bs-secondary-color);opacity:.4"></i>claro: previsto hasta hoy</span><span>raya: mínimo · raya ámbar: con margen</span><span>fallidas restan de lo impreso y de lo previsto</span></div>';
        var cv = cubierta(cDone), ch = cubierta(cToday);
        if (cv.pct !== null) {
            h += '<div class="tr-dolls"><div><div class="small text-muted text-uppercase fw-semibold">Tirada cubierta con lo impreso</div><span class="tr-big mono">' + cv.pct + ' %</span></div>' +
                (cv.pct < 100 ? '<div class="text-muted">limitada por <b>' + esc(cv.lim) + '</b></div>' : '') +
                '<div class="text-muted">Según el plan, al cerrar hoy: <b class="mono">' + ch.pct + ' %</b></div></div>';
        }
        h += '</div></section>';

        // línea de tiempo
        h += '<section><h6>Línea de tiempo</h6><div class="tr-panel"><div class="tr-scroll"><div class="tr-gantt" style="--n:' + N + '"><div></div><div class="tr-gdays" style="margin-top:22px">';
        for (var d = 1; d <= N; d++) h += '<span class="' + (d > PLAZO ? 'over' : '') + '">' + d + '</span>';
        h += '</div>';
        PIEZAS.forEach(function (pz) {
            h += '<div class="tr-glab"><span class="tr-dot" style="background:' + pz.color + '"></span>' + esc(pz.nombre) + '</div><div class="tr-grow">';
            for (var d = 1; d <= N; d++) {
                h += '<div class="tr-gcell">';
                var r = sim.rows[d - 1];
                if (r) r.m.forEach(function (slots) { slots.forEach(function (c) {
                    if (!c || !P[c.id].lineas.some(function (l) { return l.pieza === pz.id; })) return;
                    var st = P[c.id].impresa ? '' : (d < HOY ? ' pend late' : ' pend');
                    h += '<button type="button" class="tr-blk' + st + '" data-id="' + c.id + '" style="background:' + pz.color + ';--bc:' + pz.color + '" title="' + esc(P[c.id].codigo) + '">' + esc(P[c.id].codigo) + '</button>';
                }); });
                h += '</div>';
            }
            h += '</div>';
        });
        h += '<div class="tr-glines">';
        if (N > PLAZO) h += '<div class="tr-overzone" style="left:' + (PLAZO / N * 100) + '%"></div>';
        if (HOY <= N) h += '<div class="tr-vline today" style="left:' + ((HOY - 1) / N * 100) + '%"><span>hoy</span></div>';
        h += '<div class="tr-vline conf" style="left:' + ((S.conf - 1) / N * 100) + '%"><span>confirmación</span></div>';
        h += '<div class="tr-vline dead" style="left:' + (PLAZO / N * 100) + '%"><span>plazo · día ' + PLAZO + '</span></div>';
        h += '</div></div></div>';
        h += '<div class="tr-legend mt-2"><span><i style="background:var(--bs-secondary-color)"></i>relleno: impresa</span><span><i style="box-shadow:inset 0 0 0 2px var(--bs-secondary-color)"></i>hueco: pendiente</span><span><i style="box-shadow:inset 0 0 0 2px #f06a62"></i>rojo: debía haber salido antes de hoy</span><span>Discontinua: desde ese día se imprime lo que espera confirmación.</span></div></div></section>';

        // detalle
        h += '<section id="trDetalle">' + detail(sim) + '</section>';

        // rejilla por día
        h += '<section><h6>Placas por día</h6><p class="small text-muted mb-2">Pulsa una placa para ver qué lleva y por qué va ahí, márcala como impresa y apunta sus fallidas. «Fuera» quita los huecos de casa de ese día y el plan se recoloca solo.</p>' +
            '<div class="tr-panel tr-scroll" style="padding:8px 10px"><table class="tr-grid"><thead><tr><th>Día</th>';
        MAQ.forEach(function (m) { for (var s = 0; s < POR_DIA; s++) h += '<th>' + esc(m.nombre) + ' · ' + slotNombre(s) + '</th>'; });
        h += '<th></th></tr></thead><tbody>';
        var maxRow = Math.max(N, S.fuera.length ? Math.max.apply(null, S.fuera) : 0);
        for (var d2 = 1; d2 <= maxRow; d2++) {
            var r = sim.rows[d2 - 1] || { m: MAQ.map(function () { return new Array(POR_DIA).fill(null); }) };
            var away = S.fuera.indexOf(d2) >= 0;
            var trc = (d2 < HOY ? 'past ' : '') + (d2 === S.conf ? 'isconf ' : '') + (d2 > PLAZO ? 'over' : '');
            h += '<tr class="' + trc + '"><td class="tr-dayn">' + d2 + (d2 === S.conf ? '<small>confirmación</small>' : d2 === HOY ? '<small>hoy</small>' : d2 > PLAZO ? '<small>fuera de plazo</small>' : '') + '</td>';
            r.m.forEach(function (slots, i) { slots.forEach(function (c, s) {
                if (!c) { h += '<td><div class="tr-cell empty">' + (s >= POR_CORREO && away ? 'fuera de casa' : 'libre') + '</div></td>'; return; }
                var p = P[c.id], f = fallidasDe(p);
                var st = (p.impresa ? ' done' : (d2 < HOY ? ' late' : '')) + (selected === c.id ? ' sel' : '');
                h += '<td><button type="button" class="tr-cell' + st + '" data-id="' + c.id + '" style="--bc:' + colorDe(p) + '">' +
                    (c.change ? '<span class="tr-chg">cambio a ' + esc(c.change) + '</span>' : '') +
                    '<span class="code">' + esc(p.codigo) + (f ? ' <span class="tr-fail">✗ ' + f + '</span>' : '') + '</span><span class="what">' + desc(p) + '</span></button></td>';
            }); });
            h += '<td>' + (POR_DIA > POR_CORREO ? '<button type="button" class="tr-away" data-day="' + d2 + '" aria-pressed="' + away + '">' + (away ? 'fuera' : 'en casa') + '</button>' : '') + '</td></tr>';
        }
        h += '</tbody></table></div></section>';

        // por qué
        var bloques = NOTAS.split(/\n\s*\n/).map(function (b) { return b.trim(); }).filter(Boolean);
        if (bloques.length) {
            h += '<section><h6>Por qué está ordenado así</h6><div class="tr-why">';
            bloques.forEach(function (b) {
                var ls = b.split('\n');
                h += '<article>' + (ls.length > 1 ? '<h3>' + esc(ls[0]) + '</h3><p>' + esc(ls.slice(1).join(' ')) + '</p>' : '<p>' + esc(ls[0]) + '</p>') + '</article>';
            });
            h += '</div></section>';
        }

        // resumen
        h += '<section><div class="tr-panel tr-scroll"><table class="table table-sm tr-tbl mb-0"><thead><tr><th>Pieza</th><th>Resina</th><th class="n">Prioridad</th><th class="n">Por placa</th><th class="n">Placas</th><th class="n">Total</th><th class="n">Fallidas</th><th class="n">Necesarias</th><th class="n">Margen pedido</th><th class="n">Margen real</th></tr></thead><tbody>';
        PIEZAS.forEach(function (pz) {
            var c = cPlan[pz.id], b = BAL[pz.id], real = b.prev - b.nec;
            h += '<tr><td><span class="tr-dot me-2" style="background:' + pz.color + '"></span>' + esc(pz.nombre) + (pz.espera ? ' <span class="small text-muted">· espera confirmación</span>' : '') + '</td><td>' + esc(pz.resina) + '</td><td class="n">' + pz.prio + '</td><td class="n">' + pz.porPlaca + '</td><td class="n">' + c.placas + '</td><td class="n">' + c.cant + '</td><td class="n' + (c.fall ? ' text-danger' : '') + '">' + c.fall + '</td><td class="n">' + b.nec + '</td><td class="n">+' + (b.obj - b.nec) + ' (' + pz.margen + ' %)</td><td class="n tr-est-' + b.estado + '">' + (real >= 0 ? '+' : '') + real + '</td></tr>';
        });
        h += '<tr><td><b>Total</b></td><td></td><td></td><td></td><td class="n"><b>' + PLACAS.length + '</b></td><td></td><td></td><td></td><td></td><td></td></tr></tbody></table></div></section>';

        document.getElementById('tr-app').innerHTML = h;
    }

    function detail(sim) {
        if (!selected || !P[selected]) return '<div class="tr-panel tr-detail"><div class="small text-muted text-uppercase fw-semibold">Detalle de placa</div><div class="text-muted">Pulsa cualquier placa de la línea de tiempo o de la rejilla para ver su contenido y el motivo de su posición.</div></div>';
        var p = P[selected], w = sim.where[selected];
        var pos = w ? 'Día ' + w.day + ' · máquina ' + esc(MAQ[w.m].nombre) + ' · ' + slotNombre(w.s) : 'Sin hueco en el plan';
        var st = p.impresa ? 'Impresa' : (w && w.day < HOY ? 'Atrasada' : 'Pendiente');
        var col = st === 'Impresa' ? 'var(--tr-ok)' : st === 'Atrasada' ? 'var(--tr-red)' : 'var(--tr-muted)';
        var tabla = '<table class="table table-sm mb-0" style="max-width:760px"><thead><tr><th>Pieza</th><th class="text-end">Lleva</th>' + (p.impresa ? '<th class="text-end">Fallidas</th><th class="text-end">Buenas</th>' : '') + '<th></th></tr></thead><tbody>' +
            p.lineas.map(function (l) {
                var pz = PZ[l.pieza], iguales = pendientesIguales(p, l);
                var prop = iguales || pz.porPlaca === l.cant
                    ? '<label class="small text-muted"><input type="checkbox" class="form-check-input me-1" data-prop="' + l.id + '" checked>si cambia, aplicar también a ' +
                      (iguales ? plural(iguales, 'placa pendiente', 'placas pendientes') + ' con ' + l.cant : '') + (iguales && pz.porPlaca === l.cant ? ' y a ' : '') + (pz.porPlaca === l.cant ? '«por placa»' : '') + '</label>'
                    : '';
                return '<tr><td><span class="tr-dot me-2" style="background:' + pz.color + '"></span>' + esc(pz.nombre) + '</td>' +
                    '<td class="text-end"><input type="number" min="1" class="form-control form-control-sm d-inline-block text-end" style="width:5.5rem" data-cant="' + l.id + '" value="' + l.cant + '"></td>' +
                    (p.impresa ? '<td class="text-end"><input type="number" min="0" class="form-control form-control-sm d-inline-block text-end" style="width:5.5rem" data-fall="' + l.id + '" value="' + l.fall + '"></td><td class="text-end mono">' + (l.cant - l.fall) + '</td>' : '') +
                    '<td>' + prop + '</td></tr>';
            }).join('') + '</tbody></table>';
        return '<div class="tr-panel tr-detail" style="--bc:' + colorDe(p) + '"><div class="d-flex flex-wrap gap-3 align-items-baseline"><span class="code">' + esc(p.codigo) + '</span><span class="tr-resin">' + esc(resinaDe(p)) + '</span><span class="text-muted">' + pos + '</span><span class="mono" style="color:' + col + '">' + st + '</span></div>' +
            tabla +
            (p.motivo ? '<p class="mb-0" style="max-width:70ch">' + esc(p.motivo) + '</p>' : '') +
            '<div class="d-flex gap-2 flex-wrap">' +
            '<button type="button" class="btn btn-sm btn-primary" id="trLineas">Guardar ' + (p.impresa ? 'cantidades y fallidas' : 'cantidades') + '</button>' +
            '<button type="button" class="btn btn-sm ' + (p.impresa ? 'btn-outline-secondary' : 'btn-success') + '" id="trMarcar">' + (p.impresa ? 'Desmarcar' : 'Marcar como impresa') + '</button><button type="button" class="btn btn-sm btn-outline-secondary" id="trCerrar">Cerrar</button></div></div>';
    }

    function aviso(texto) { var m = document.getElementById('trMsg'); if (m) m.textContent = texto; }
    var guardarTimer = null;
    function guardarAjustes() {
        clearTimeout(guardarTimer);
        guardarTimer = setTimeout(function () {
            post(URL_AJUSTES, { dia_confirmacion: S.conf, cambio_resina: S.cambio ? 1 : 0, dias_fuera: S.fuera.join(',') })
                .then(function () { aviso('Guardado'); })
                .catch(function () { aviso('No se pudo guardar.'); });
        }, 600);
    }

    var app = document.getElementById('tr-app');
    app.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) return;
        if (b.dataset.conf) { S.conf = Math.max(1, Math.min(Math.max(PLAZO, 60), S.conf + Number(b.dataset.conf))); render(); guardarAjustes(); }
        else if (b.dataset.day) { var d = Number(b.dataset.day), i = S.fuera.indexOf(d); if (i >= 0) S.fuera.splice(i, 1); else S.fuera.push(d); render(); guardarAjustes(); }
        else if (b.dataset.id) { selected = Number(b.dataset.id); render(); var ds = document.getElementById('trDetalle'); if (ds) ds.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
        else if (b.id === 'trCerrar') { selected = null; render(); }
        else if (b.id === 'trMarcar' && selected) {
            var p = P[selected], f = fallidasDe(p);
            if (p.impresa && !confirm('¿Desmarcar ' + p.codigo + ' como impresa?' + (f ? ' Se borran sus ' + f + ' fallidas.' : ''))) return;
            b.disabled = true;
            post(URL_PLACA + selected + '/impresa', {})
                .then(function (d) { p.impresa = d.impresa_en !== null; if (!p.impresa) p.lineas.forEach(function (l) { l.fall = 0; }); render(); })
                .catch(function () { b.disabled = false; aviso('No se pudo marcar la placa.'); });
        }
        else if (b.id === 'trLineas' && selected) {
            var campos = {};
            app.querySelectorAll('[data-cant]').forEach(function (inp) { campos['cantidad[' + inp.dataset.cant + ']'] = inp.value || 1; });
            app.querySelectorAll('[data-fall]').forEach(function (inp) { campos['fallidas[' + inp.dataset.fall + ']'] = inp.value || 0; });
            app.querySelectorAll('[data-prop]').forEach(function (inp) { if (inp.checked) campos['propagar[' + inp.dataset.prop + ']'] = 1; });
            b.disabled = true;
            post(URL_PLACA + selected + '/lineas', campos)
                .then(function (d) {
                    try { sessionStorage.setItem('trAviso', 'Guardado' + (d.propagadas ? ' · ' + plural(d.propagadas, 'placa pendiente cambiada', 'placas pendientes cambiadas') : '')); } catch (err) {}
                    location.hash = 'placa-' + selected;
                    location.reload();
                })
                .catch(function () { b.disabled = false; aviso('No se pudo guardar.'); });
        }
    });
    app.addEventListener('change', function (e) {
        if (e.target.id === 'trCambio') { S.cambio = e.target.checked; render(); guardarAjustes(); }
    });
    var m = /^#placa-(\d+)$/.exec(location.hash);
    if (m && P[Number(m[1])]) selected = Number(m[1]);
    render();
    if (selected) { var ds0 = document.getElementById('trDetalle'); if (ds0) ds0.scrollIntoView({ block: 'center' }); }
    try { var av = sessionStorage.getItem('trAviso'); if (av) { aviso(av); sessionStorage.removeItem('trAviso'); } } catch (err) {}

    // ---- modales de edición: piezas y placas con sus líneas ----
    var contador = 1000;
    function opcionesPieza(sel) {
        return PIEZAS.map(function (pz) { return '<option value="' + pz.id + '"' + (pz.id === sel ? ' selected' : '') + '>' + esc(pz.nombre) + '</option>'; }).join('');
    }
    function nuevaLinea(i, piezaId) {
        var j = contador++, pz = PZ[piezaId];
        var div = document.createElement('div');
        div.className = 'tr-lin';
        div.innerHTML = '<select name="placas[' + i + '][lineas][' + j + '][pieza_id]" class="form-select form-select-sm" data-pieza style="width:10rem">' + opcionesPieza(piezaId) + '</select>' +
            '<input type="number" min="0" name="placas[' + i + '][lineas][' + j + '][cantidad]" class="form-control form-control-sm" style="width:5.5rem" data-cant value="' + (pz ? pz.porPlaca : 0) + '">' +
            '<button type="button" class="btn btn-sm btn-link text-danger p-0" data-quitar-linea title="Quitar pieza"><i class="bi bi-x"></i></button>';
        return div;
    }
    document.addEventListener('click', function (e) {
        var q = e.target.closest('[data-quitar-fila]');
        if (q) { q.closest('tr').remove(); return; }
        var ql = e.target.closest('[data-quitar-linea]');
        if (ql) { ql.closest('.tr-lin').remove(); return; }
        var al = e.target.closest('[data-add-linea]');
        if (al) {
            var tr = al.closest('tr[data-placa]'), prev = tr.querySelector('[data-pieza]');
            al.parentNode.insertBefore(nuevaLinea(tr.dataset.i, prev ? Number(prev.value) : PIEZAS[0].id), al);
            return;
        }
        var mv = e.target.closest('[data-mover]');
        if (mv) {
            var fila = mv.closest('tr');
            if (mv.dataset.mover === '-1' && fila.previousElementSibling) fila.parentNode.insertBefore(fila, fila.previousElementSibling);
            if (mv.dataset.mover === '1' && fila.nextElementSibling) fila.parentNode.insertBefore(fila.nextElementSibling, fila);
        }
    });

    document.getElementById('addPieza').addEventListener('click', function () {
        var i = contador++;
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="text" name="piezas[' + i + '][nombre]" class="form-control form-control-sm" placeholder="Nombre"></td>' +
            '<td><input type="text" name="piezas[' + i + '][resina]" class="form-control form-control-sm" style="width:7rem" value="' + esc(RESINAS_MAQ[0] || '') + '"></td>' +
            '<td><input type="number" min="0" name="piezas[' + i + '][necesarias]" class="form-control form-control-sm" style="width:6rem" value="0"></td>' +
            '<td><input type="number" min="0" name="piezas[' + i + '][margen]" class="form-control form-control-sm" style="width:5rem" value="0"></td>' +
            '<td><input type="number" min="1" name="piezas[' + i + '][por_placa]" class="form-control form-control-sm" style="width:6rem" value="1"></td>' +
            '<td><input type="number" min="0" max="9" name="piezas[' + i + '][prioridad]" class="form-control form-control-sm" style="width:4.5rem" value="0"></td>' +
            '<td><input type="checkbox" name="piezas[' + i + '][espera_confirmacion]" value="1" class="form-check-input"></td>' +
            '<td><input type="color" name="piezas[' + i + '][color]" class="form-control form-control-sm form-control-color" value="#9aa5b3"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>';
        document.querySelector('#tablaPiezas tbody').appendChild(tr);
        tr.querySelector('input').focus();
    });

    var tablaPlacas = document.getElementById('tablaPlacas');
    tablaPlacas.addEventListener('change', function (e) {
        if (!e.target.matches('[data-pieza]')) return;
        var cant = e.target.closest('.tr-lin').querySelector('[data-cant]'), pz = PZ[Number(e.target.value)];
        if (pz && !(Number(cant.value) > 0)) cant.value = pz.porPlaca;
    });

    var addPlaca = document.getElementById('addPlaca');
    if (addPlaca) addPlaca.addEventListener('click', function () {
        var i = contador++;
        var sels = tablaPlacas.querySelectorAll('tbody [data-pieza]');
        var ultima = sels.length ? Number(sels[sels.length - 1].value) : PIEZAS[0].id;
        var tr = document.createElement('tr');
        tr.setAttribute('data-placa', '');
        tr.dataset.i = i;
        tr.innerHTML = '<td class="text-nowrap"><button type="button" class="btn btn-sm btn-link p-0" data-mover="-1" title="Subir"><i class="bi bi-arrow-up"></i></button> <button type="button" class="btn btn-sm btn-link p-0" data-mover="1" title="Bajar"><i class="bi bi-arrow-down"></i></button></td>' +
            '<td><input type="text" name="placas[' + i + '][codigo]" class="form-control form-control-sm" style="width:5rem" placeholder="Código"></td>' +
            '<td data-lineas><button type="button" class="btn btn-sm btn-link p-0" data-add-linea><i class="bi bi-plus"></i> pieza</button></td>' +
            '<td><textarea name="placas[' + i + '][motivo]" rows="1" class="form-control form-control-sm" style="min-width:18rem"></textarea></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>';
        var celda = tr.querySelector('[data-lineas]');
        celda.insertBefore(nuevaLinea(i, ultima), celda.firstChild);
        tablaPlacas.querySelector('tbody').appendChild(tr);
        tr.querySelector('input[type=text]').focus();
    });

    function prioridadFila(tr) {
        var max = 0;
        tr.querySelectorAll('[data-pieza]').forEach(function (s) { var pz = PZ[Number(s.value)]; if (pz && pz.prio > max) max = pz.prio; });
        return max;
    }
    var ordenar = document.getElementById('ordenarPrioridad');
    if (ordenar) ordenar.addEventListener('click', function () {
        var tbody = tablaPlacas.querySelector('tbody');
        Array.prototype.slice.call(tbody.querySelectorAll('tr[data-placa]'))
            .map(function (tr, i) { return { tr: tr, i: i, p: prioridadFila(tr) }; })
            .sort(function (a, b) { return b.p - a.p || a.i - b.i; })
            .forEach(function (o) { tbody.appendChild(o.tr); });
    });

    document.getElementById('formPlacas').addEventListener('submit', function (e) {
        var mal = [];
        tablaPlacas.querySelectorAll('tbody tr[data-placa]').forEach(function (tr) {
            var resinas = {};
            tr.querySelectorAll('[data-pieza]').forEach(function (s) { var pz = PZ[Number(s.value)]; if (pz) resinas[pz.resina] = true; });
            if (Object.keys(resinas).length > 1) mal.push((tr.querySelector('input[type=text]').value || '¿sin código?') + ' (' + Object.keys(resinas).join(', ') + ')');
        });
        if (mal.length) { e.preventDefault(); document.getElementById('placasError').textContent = 'Mezclan resinas: ' + mal.join(' · ') + '. Una placa va en una sola resina.'; }
    });
})();
</script>

<?= $this->endSection() ?>
