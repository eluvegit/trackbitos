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
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalTipos"><i class="bi bi-tags"></i> Tipos</button>
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
    .tr-bar { display: grid; grid-template-columns: 170px 1fr 170px; gap: 12px; align-items: center; }
    .tr-bar .nm { display: flex; align-items: center; gap: 8px; font-weight: 700; }
    .tr-track { position: relative; height: 18px; background: var(--tr-sunk); border-radius: 5px; }
    .tr-track .fill, .tr-track .plan { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 5px; }
    .tr-track .plan { opacity: .28; }
    .tr-track .need { position: absolute; top: -4px; bottom: -4px; width: 2px; background: var(--bs-body-color); }
    .tr-track .need::after { content: "necesario"; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font: 600 .62rem var(--bs-font-monospace); color: var(--tr-muted); white-space: nowrap; }
    .tr-bar .num { font-size: .88rem; text-align: right; }
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
                <div class="col-sm-4"><label class="form-label">Día 1</label><input type="date" name="fecha_inicio" class="form-control" value="<?= esc((string) $tirada['fecha_inicio']) ?>"></div>
                <div class="col-sm-4"><label class="form-label">Plazo (último día de impresión)</label><input type="number" min="1" name="plazo_dias" class="form-control" value="<?= (int) $tirada['plazo_dias'] ?>"></div>
                <div class="col-sm-2"><label class="form-label">Hombres</label><input type="number" min="0" name="objetivo_h" class="form-control" value="<?= (int) $tirada['objetivo_h'] ?>"></div>
                <div class="col-sm-2"><label class="form-label">Mujeres</label><input type="number" min="0" name="objetivo_m" class="form-control" value="<?= (int) $tirada['objetivo_m'] ?>"></div>
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
    onsubmit="return confirm('¿Borrar la tirada «<?= esc($tirada['nombre'], 'js') ?>» con sus tipos y placas?');">
    <?= csrf_field() ?>
</form>

<?php // ---- Tipos de pieza ---- ?>
<div class="modal fade" id="modalTipos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form method="post" action="<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/tipos') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Tipos de pieza</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">
                    <b>Por muñeco</b>: cuántas lleva cada uno (brazos 2). <b>H/M</b>: hay versión de hombre y de mujer.
                    <b>Espera confirmación</b>: no se imprime antes del día de confirmación del diseño.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tablaTipos">
                        <thead><tr><th>Nombre</th><th>Resina</th><th>Por placa</th><th>Por muñeco</th><th>H/M</th><th>Espera confirmación</th><th>Color</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($tipos as $i => $t): ?>
                                <tr>
                                    <td><input type="hidden" name="tipos[<?= $i ?>][id]" value="<?= (int) $t['id'] ?>"><input type="text" name="tipos[<?= $i ?>][nombre]" class="form-control form-control-sm" value="<?= esc($t['nombre']) ?>"></td>
                                    <td><input type="text" name="tipos[<?= $i ?>][resina]" class="form-control form-control-sm" style="width: 7rem;" value="<?= esc($t['resina']) ?>"></td>
                                    <td><input type="number" min="1" name="tipos[<?= $i ?>][por_placa]" class="form-control form-control-sm" style="width: 6rem;" value="<?= (int) $t['por_placa'] ?>"></td>
                                    <td><input type="number" min="1" name="tipos[<?= $i ?>][por_muneco]" class="form-control form-control-sm" style="width: 5rem;" value="<?= (int) $t['por_muneco'] ?>"></td>
                                    <td><input type="checkbox" name="tipos[<?= $i ?>][sexuado]" value="1" class="form-check-input" <?= $t['sexuado'] ? 'checked' : '' ?>></td>
                                    <td><input type="checkbox" name="tipos[<?= $i ?>][bloqueado]" value="1" class="form-check-input" <?= $t['bloqueado'] ? 'checked' : '' ?>></td>
                                    <td><input type="color" name="tipos[<?= $i ?>][color]" class="form-control form-control-sm form-control-color" value="<?= esc($t['color']) ?>"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="addTipo"><i class="bi bi-plus-lg"></i> Añadir tipo</button>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar tipos</button></div>
        </form>
    </div>
</div>

<?php // ---- Placas, en orden de cola ---- ?>
<div class="modal fade" id="modalPlacas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="post" action="<?= site_url('piezas/tiradas/' . (int) $tirada['id'] . '/placas') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Placas en orden de cola</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">
                    Cada máquina coge, de las placas de su resina, la primera de esta lista que ya se pueda imprimir ese día.
                    Subir o bajar una placa cambia cuándo sale. Las que ya están impresas lo siguen estando.
                </p>
                <?php if ($tipos === []): ?>
                    <p class="text-warning">Define antes los tipos de pieza.</p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tablaPlacas">
                        <thead><tr><th></th><th>Código</th><th>Tipo</th><th>H</th><th>M</th><th>Unidades</th><th>Por qué va ahí</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($placas as $i => $p): ?>
                                <tr data-placa>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-sm btn-link p-0" data-mover="-1" title="Subir"><i class="bi bi-arrow-up"></i></button>
                                        <button type="button" class="btn btn-sm btn-link p-0" data-mover="1" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                                    </td>
                                    <td><input type="hidden" name="placas[<?= $i ?>][id]" value="<?= (int) $p['id'] ?>"><input type="text" name="placas[<?= $i ?>][codigo]" class="form-control form-control-sm" style="width: 5rem;" value="<?= esc($p['codigo']) ?>"><?= $p['impresa_en'] ? '<span class="small text-success">✓ impresa</span>' : '' ?></td>
                                    <td>
                                        <select name="placas[<?= $i ?>][tipo_id]" class="form-select form-select-sm" data-tipo style="width: 9rem;">
                                            <?php foreach ($tipos as $t): ?>
                                                <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === (int) $p['tipo_id'] ? 'selected' : '' ?>><?= esc($t['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" min="0" name="placas[<?= $i ?>][hombres]" class="form-control form-control-sm" style="width: 5rem;" data-h value="<?= (int) $p['hombres'] ?>"></td>
                                    <td><input type="number" min="0" name="placas[<?= $i ?>][mujeres]" class="form-control form-control-sm" style="width: 5rem;" data-m value="<?= (int) $p['mujeres'] ?>"></td>
                                    <td><input type="number" min="0" name="placas[<?= $i ?>][unidades]" class="form-control form-control-sm" style="width: 5.5rem;" data-u value="<?= (int) $p['unidades'] ?>"></td>
                                    <td><textarea name="placas[<?= $i ?>][motivo]" rows="1" class="form-control form-control-sm" style="min-width: 18rem;"><?= esc((string) $p['motivo']) ?></textarea></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($tipos !== []): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addPlaca"><i class="bi bi-plus-lg"></i> Añadir placa</button>
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
    var URL_IMPRESA = '<?= site_url('piezas/tiradas/placa') ?>/';

    var PLAZO = <?= (int) $tirada['plazo_dias'] ?>;
    var HOY = <?= (int) $hoy ?>;
    var OBJ = { h: <?= (int) $tirada['objetivo_h'] ?>, m: <?= (int) $tirada['objetivo_m'] ?> };
    var POR_DIA = <?= (int) $tirada['placas_dia'] ?>, POR_CORREO = <?= (int) $tirada['placas_correo'] ?>;
    var MAQ = <?= json_encode($maquinas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var NOTAS = <?= json_encode((string) $tirada['notas'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var TIPOS = <?= json_encode(array_map(static fn (array $t) => [
        'id' => (int) $t['id'], 'nombre' => $t['nombre'], 'resina' => $t['resina'], 'porPlaca' => (int) $t['por_placa'],
        'porMuneco' => (int) $t['por_muneco'], 'sexuado' => (bool) $t['sexuado'], 'bloqueado' => (bool) $t['bloqueado'], 'color' => $t['color'],
    ], $tipos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var PLACAS = <?= json_encode(array_map(static fn (array $p) => [
        'id' => (int) $p['id'], 'tipo' => (int) $p['tipo_id'], 'codigo' => $p['codigo'], 'h' => (int) $p['hombres'],
        'm' => (int) $p['mujeres'], 'u' => (int) $p['unidades'], 'motivo' => (string) $p['motivo'], 'impresa' => $p['impresa_en'] !== null,
    ], $placas), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    var S = {
        conf: <?= (int) $tirada['dia_confirmacion'] ?>,
        cambio: <?= $tirada['cambio_resina'] ? 'true' : 'false' ?>,
        fuera: <?= json_encode(array_values(array_filter(array_map('intval', explode(',', (string) $tirada['dias_fuera']))))) ?>
    };
    var selected = null;

    var TIPO = {}, P = {};
    TIPOS.forEach(function (t) { TIPO[t.id] = t; });
    PLACAS.forEach(function (p) { P[p.id] = p; });
    var NOBJ = OBJ.h + OBJ.m;

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

    // ---- simulación: cada máquina coge de la cola de su resina la primera placa ya imprimible ----
    var RESINAS_MAQ = MAQ.map(function (m) { return m.resina; });
    function simulate(conf, cambio, fuera) {
        var colas = {}, sinMaquina = [];
        PLACAS.forEach(function (p) {
            var r = TIPO[p.tipo].resina;
            if (!cambio && RESINAS_MAQ.indexOf(r) < 0) { sinMaquina.push(p.id); return; }
            (colas[r] = colas[r] || []).push(p.id);
        });
        var actual = RESINAS_MAQ.slice(), rows = [], last = 0, changes = 0;
        function quedan() { return Object.keys(colas).some(function (r) { return colas[r].length; }); }
        function take(r, day) {
            var q = colas[r] || [];
            for (var i = 0; i < q.length; i++) { if (!TIPO[P[q[i]].tipo].bloqueado || day >= conf) return q.splice(i, 1)[0]; }
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

    // ---- cálculos ----
    function units(ids) {
        var r = {};
        TIPOS.forEach(function (t) { r[t.id] = { h: 0, m: 0, u: 0 }; });
        ids.forEach(function (id) { var p = P[id], o = r[p.tipo]; o.u += p.u; o.h += p.h; o.m += p.m; });
        return r;
    }
    function dolls(u) {
        var h = Infinity, m = Infinity, sueltos = Infinity, lim = null, limS = null;
        TIPOS.forEach(function (t) {
            if (t.sexuado) {
                h = Math.min(h, Math.floor(u[t.id].h / t.porMuneco));
                m = Math.min(m, Math.floor(u[t.id].m / t.porMuneco));
            } else {
                var n = Math.floor(u[t.id].u / t.porMuneco);
                if (n < sueltos) { sueltos = n; limS = t.nombre; }
            }
        });
        if (h === Infinity) { h = 0; m = 0; var tot = sueltos === Infinity ? 0 : sueltos; return { h: null, m: null, tot: tot, lim: null }; }
        var tot2 = Math.min(h + m, sueltos);
        if (tot2 < h + m) lim = limS;
        return { h: h, m: m, tot: tot2, lim: lim };
    }
    function necesario(t) { return t.porMuneco * NOBJ; }
    function desc(p) {
        var t = TIPO[p.tipo];
        if (!t.sexuado) return p.u + ' ' + esc(t.nombre.toLowerCase());
        var parts = []; if (p.h) parts.push(p.h + ' H'); if (p.m) parts.push(p.m + ' M');
        return esc(t.nombre) + ' · ' + parts.join(' + ');
    }
    function slotNombre(s) { return s < POR_CORREO ? 'correo' : (POR_DIA - POR_CORREO > 1 ? 'en casa ' + (s - POR_CORREO + 1) : 'en casa'); }

    // ---- render ----
    function render() {
        var sim = simulate(S.conf, S.cambio, S.fuera);
        var alt = simulate(S.conf, !S.cambio, S.fuera);
        var lcNo = latestConf(false), lcSi = latestConf(true);
        var margin = PLAZO - sim.last;
        var cls = sim.sinMaquina.length || margin < 0 ? 'bad' : margin <= 1 ? 'warn' : '';
        var head = !PLACAS.length ? 'Todavía no hay placas: defínelas en «Placas».'
            : margin < 0 ? 'No entra en plazo: la última placa sale el día ' + sim.last + ' (' + plural(-margin, 'día', 'días') + ' tarde).'
            : 'La última placa sale el día ' + sim.last + ': ' + plural(margin, 'día', 'días') + ' de margen.';
        var lines = [];
        if (sim.sinMaquina.length) lines.push('<b class="text-danger">' + plural(sim.sinMaquina.length, 'placa', 'placas') + ' sin máquina de su resina</b> (' + sim.sinMaquina.map(function (id) { return esc(P[id].codigo); }).join(', ') + '): añade una máquina o permite el cambio de resina.');
        lines.push('Cambios de resina: <b>' + sim.changes + '</b>.');
        if (!S.cambio) {
            if (alt.last < sim.last) lines.push('Permitiendo el cambio de resina terminarías el día ' + alt.last + ' (' + (sim.last - alt.last) + ' antes) con ' + plural(alt.changes, 'cambio', 'cambios') + '.');
            lines.push('Sin cambiar resina, la confirmación puede llegar como tarde el <b>día ' + lcNo + '</b>. Con cambio, hasta el <b>día ' + lcSi + '</b>.');
        } else {
            lines.push('Sin cambio de resina terminarías el día ' + alt.last + '. Con el cambio, la confirmación puede llegar como tarde el <b>día ' + lcSi + '</b> (sin cambio: día ' + lcNo + ').');
        }
        if (lcNo === 0 && lcSi === 0 && PLACAS.length) lines.push('Ni confirmando el día 1 entra en plazo con estas máquinas.');

        var allIds = PLACAS.map(function (p) { return p.id; });
        var doneIds = allIds.filter(function (id) { return P[id].impresa; });
        var planIds = Object.keys(sim.where).filter(function (k) { return sim.where[k].day <= HOY; });
        var uDone = units(doneIds), uPlan = units(allIds), uToday = units(planIds);
        var dDone = dolls(uDone), dToday = dolls(uToday);
        var N = Math.max(PLAZO, sim.last, 1);

        var h = '';
        h += '<div class="tr-controls">' +
            '<span class="small text-muted">' + (NOBJ ? NOBJ + ' muñecos (' + OBJ.h + ' H / ' + OBJ.m + ' M) · ' : '') + plural(MAQ.length, 'máquina', 'máquinas') + ' · plazo día ' + PLAZO + ' · hoy es el <b>día ' + HOY + '</b></span>' +
            '<span class="d-flex align-items-center gap-2"><b class="small">Confirmación del diseño</b><span class="tr-step"><button type="button" data-conf="-1" aria-label="Un día antes">−</button><output>día ' + S.conf + '</output><button type="button" data-conf="1" aria-label="Un día después">+</button></span></span>' +
            '<span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="trCambio"' + (S.cambio ? ' checked' : '') + '><label class="form-check-label small fw-semibold" for="trCambio">Permitir cambio de resina cuando una máquina se queda sin nada que imprimir</label></span>' +
            '<span class="small text-muted" id="trMsg"></span></div>';
        h += '<div class="tr-status ' + cls + '"><strong>' + head + '</strong><span class="small">' + lines.join('<br>') + '</span></div>';

        // progreso
        h += '<section><h6>Progreso por tipo</h6><div class="tr-panel tr-bars">';
        TIPOS.forEach(function (t) {
            var d = uDone[t.id].u, pl = uPlan[t.id].u || 1, td = uToday[t.id].u, ne = necesario(t);
            var sub = t.sexuado ? '<div class="text-muted mono" style="font-size:.78rem">H ' + uDone[t.id].h + '/' + t.porMuneco * OBJ.h + ' · M ' + uDone[t.id].m + '/' + t.porMuneco * OBJ.m + '</div>' : '';
            var marg = ne ? Math.round((uPlan[t.id].u / ne - 1) * 100) : 0;
            h += '<div class="tr-bar"><div class="nm"><span class="tr-dot" style="background:' + t.color + '"></span>' + esc(t.nombre) + ' <span class="tr-resin">' + esc(t.resina) + '</span></div>' +
                '<div class="tr-track" title="Impreso ' + d + ' · previsto hasta hoy ' + td + ' · total ' + uPlan[t.id].u + '">' +
                '<div class="plan" style="width:' + Math.min(100, td / pl * 100) + '%;background:' + t.color + '"></div>' +
                '<div class="fill" style="width:' + Math.min(100, d / pl * 100) + '%;background:' + t.color + '"></div>' +
                (ne ? '<div class="need" style="left:' + Math.min(100, ne / pl * 100) + '%"></div>' : '') + '</div>' +
                '<div class="num mono">' + d + ' / ' + ne + ' <span class="text-muted">(' + (marg >= 0 ? '+' : '') + marg + ' %)</span>' + sub + '</div></div>';
        });
        h += '<div class="tr-legend"><span><i style="background:var(--bs-body-color)"></i>fuerte: impreso</span><span><i style="background:var(--bs-secondary-color);opacity:.4"></i>claro: previsto hasta hoy</span><span>raya: lo necesario; a su derecha, margen</span></div>';
        if (NOBJ) {
            h += '<div class="tr-dolls"><div><div class="small text-muted text-uppercase fw-semibold">Muñecos completos con lo impreso</div><span class="tr-big mono">' + dDone.tot + '</span> <span class="text-muted">/ ' + NOBJ + '</span></div>' +
                (dDone.h !== null ? '<div class="mono">H ' + dDone.h + ' · M ' + dDone.m + (dDone.lim ? ' <span class="text-muted">(limitado por ' + esc(dDone.lim.toLowerCase()) + ')</span>' : '') + '</div>' : '') +
                '<div class="text-muted">Según el plan, al cerrar hoy: <b class="mono">' + dToday.tot + '</b></div></div>';
        }
        h += '</div></section>';

        // línea de tiempo
        h += '<section><h6>Línea de tiempo</h6><div class="tr-panel"><div class="tr-scroll"><div class="tr-gantt" style="--n:' + N + '"><div></div><div class="tr-gdays" style="margin-top:22px">';
        for (var d = 1; d <= N; d++) h += '<span class="' + (d > PLAZO ? 'over' : '') + '">' + d + '</span>';
        h += '</div>';
        TIPOS.forEach(function (t) {
            h += '<div class="tr-glab"><span class="tr-dot" style="background:' + t.color + '"></span>' + esc(t.nombre) + '</div><div class="tr-grow">';
            for (var d = 1; d <= N; d++) {
                h += '<div class="tr-gcell">';
                var r = sim.rows[d - 1];
                if (r) r.m.forEach(function (slots) { slots.forEach(function (c) {
                    if (!c || P[c.id].tipo !== t.id) return;
                    var st = P[c.id].impresa ? '' : (d < HOY ? ' pend late' : ' pend');
                    h += '<button type="button" class="tr-blk' + st + '" data-id="' + c.id + '" style="background:' + t.color + ';--bc:' + t.color + '" title="' + esc(P[c.id].codigo) + '">' + esc(P[c.id].codigo) + '</button>';
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
        h += '<section><h6>Placas por día</h6><p class="small text-muted mb-2">Pulsa una placa para ver qué lleva y por qué va ahí, y márcala como impresa. «Fuera» quita los huecos de casa de ese día y el plan se recoloca solo.</p>' +
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
                var p = P[c.id], t = TIPO[p.tipo];
                var st = (p.impresa ? ' done' : (d2 < HOY ? ' late' : '')) + (selected === c.id ? ' sel' : '');
                h += '<td><button type="button" class="tr-cell' + st + '" data-id="' + c.id + '" style="--bc:' + t.color + '">' +
                    (c.change ? '<span class="tr-chg">cambio a ' + esc(c.change) + '</span>' : '') +
                    '<span class="code">' + esc(p.codigo) + '</span><span class="what">' + desc(p) + '</span></button></td>';
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
        h += '<section><div class="tr-panel tr-scroll"><table class="table table-sm tr-tbl mb-0"><thead><tr><th>Tipo</th><th>Resina</th><th class="n">Por placa</th><th class="n">Placas</th><th class="n">Total</th><th class="n">Necesario</th><th class="n">Margen</th></tr></thead><tbody>';
        TIPOS.forEach(function (t) {
            var n = PLACAS.filter(function (p) { return p.tipo === t.id; }).length, tot = uPlan[t.id].u, ne = necesario(t);
            h += '<tr><td><span class="tr-dot me-2" style="background:' + t.color + '"></span>' + esc(t.nombre) + '</td><td>' + esc(t.resina) + '</td><td class="n">' + t.porPlaca + '</td><td class="n">' + n + '</td><td class="n">' + tot + '</td><td class="n">' + ne + '</td><td class="n' + (tot < ne ? ' text-danger' : '') + '">' + (tot - ne >= 0 ? '+' : '') + (tot - ne) + '</td></tr>';
        });
        h += '<tr><td><b>Total</b></td><td></td><td></td><td class="n"><b>' + PLACAS.length + '</b></td><td></td><td></td><td></td></tr></tbody></table></div></section>';

        document.getElementById('tr-app').innerHTML = h;
    }

    function detail(sim) {
        if (!selected || !P[selected]) return '<div class="tr-panel tr-detail"><div class="small text-muted text-uppercase fw-semibold">Detalle de placa</div><div class="text-muted">Pulsa cualquier placa de la línea de tiempo o de la rejilla para ver su contenido y el motivo de su posición.</div></div>';
        var p = P[selected], w = sim.where[selected], t = TIPO[p.tipo];
        var pos = w ? 'Día ' + w.day + ' · máquina ' + esc(MAQ[w.m].nombre) + ' · ' + slotNombre(w.s) : 'Sin hueco en el plan';
        var st = p.impresa ? 'Impresa' : (w && w.day < HOY ? 'Atrasada' : 'Pendiente');
        var col = st === 'Impresa' ? 'var(--tr-ok)' : st === 'Atrasada' ? 'var(--tr-red)' : 'var(--tr-muted)';
        return '<div class="tr-panel tr-detail" style="--bc:' + t.color + '"><div class="d-flex flex-wrap gap-3 align-items-baseline"><span class="code">' + esc(p.codigo) + '</span><b>' + desc(p) + '</b><span class="tr-resin">' + esc(t.resina) + '</span><span class="text-muted">' + pos + '</span><span class="mono" style="color:' + col + '">' + st + '</span></div>' +
            (p.motivo ? '<p class="mb-0" style="max-width:70ch">' + esc(p.motivo) + '</p>' : '') +
            '<div class="d-flex gap-2"><button type="button" class="btn btn-sm ' + (p.impresa ? 'btn-outline-secondary' : 'btn-success') + '" id="trMarcar">' + (p.impresa ? 'Desmarcar' : 'Marcar como impresa') + '</button><button type="button" class="btn btn-sm btn-outline-secondary" id="trCerrar">Cerrar</button></div></div>';
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
            b.disabled = true;
            post(URL_IMPRESA + selected + '/impresa', {})
                .then(function (d) { P[selected].impresa = d.impresa_en !== null; render(); })
                .catch(function () { b.disabled = false; alert('No se pudo marcar la placa.'); });
        }
    });
    app.addEventListener('change', function (e) {
        if (e.target.id === 'trCambio') { S.cambio = e.target.checked; render(); guardarAjustes(); }
    });
    render();

    // ---- modales de edición: filas de tipos y placas ----
    var contador = 1000;
    document.addEventListener('click', function (e) {
        var q = e.target.closest('[data-quitar-fila]');
        if (q) { q.closest('tr').remove(); return; }
        var mv = e.target.closest('[data-mover]');
        if (mv) {
            var tr = mv.closest('tr');
            if (mv.dataset.mover === '-1' && tr.previousElementSibling) tr.parentNode.insertBefore(tr, tr.previousElementSibling);
            if (mv.dataset.mover === '1' && tr.nextElementSibling) tr.parentNode.insertBefore(tr.nextElementSibling, tr);
        }
    });

    document.getElementById('addTipo').addEventListener('click', function () {
        var i = contador++;
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="text" name="tipos[' + i + '][nombre]" class="form-control form-control-sm" placeholder="Nombre"></td>' +
            '<td><input type="text" name="tipos[' + i + '][resina]" class="form-control form-control-sm" style="width:7rem" value="' + esc(RESINAS_MAQ[0] || '') + '"></td>' +
            '<td><input type="number" min="1" name="tipos[' + i + '][por_placa]" class="form-control form-control-sm" style="width:6rem" value="1"></td>' +
            '<td><input type="number" min="1" name="tipos[' + i + '][por_muneco]" class="form-control form-control-sm" style="width:5rem" value="1"></td>' +
            '<td><input type="checkbox" name="tipos[' + i + '][sexuado]" value="1" class="form-check-input"></td>' +
            '<td><input type="checkbox" name="tipos[' + i + '][bloqueado]" value="1" class="form-check-input"></td>' +
            '<td><input type="color" name="tipos[' + i + '][color]" class="form-control form-control-sm form-control-color" value="#9aa5b3"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>';
        document.querySelector('#tablaTipos tbody').appendChild(tr);
        tr.querySelector('input').focus();
    });

    // H/M solo para tipos con sexo; para ellos las unidades son H + M.
    function ajustarFila(tr, rellenar) {
        var t = TIPO[Number(tr.querySelector('[data-tipo]').value)];
        if (!t) return;
        var h = tr.querySelector('[data-h]'), m = tr.querySelector('[data-m]'), u = tr.querySelector('[data-u]');
        h.disabled = m.disabled = !t.sexuado;
        u.readOnly = t.sexuado;
        if (rellenar) { if (t.sexuado) { h.value = t.porPlaca; m.value = 0; } else { u.value = t.porPlaca; } }
        if (t.sexuado) u.value = (Number(h.value) || 0) + (Number(m.value) || 0);
    }
    var tablaPlacas = document.getElementById('tablaPlacas');
    tablaPlacas.querySelectorAll('tr[data-placa]').forEach(function (tr) { ajustarFila(tr, false); });
    tablaPlacas.addEventListener('change', function (e) { var tr = e.target.closest('tr[data-placa]'); if (tr) ajustarFila(tr, e.target.matches('[data-tipo]')); });
    tablaPlacas.addEventListener('input', function (e) { var tr = e.target.closest('tr[data-placa]'); if (tr && (e.target.matches('[data-h]') || e.target.matches('[data-m]'))) ajustarFila(tr, false); });

    var addPlaca = document.getElementById('addPlaca');
    if (addPlaca) addPlaca.addEventListener('click', function () {
        var i = contador++;
        var filas = tablaPlacas.querySelectorAll('tbody tr[data-placa]');
        var ultimo = filas.length ? filas[filas.length - 1].querySelector('[data-tipo]').value : String(TIPOS[0].id);
        var tr = document.createElement('tr');
        tr.setAttribute('data-placa', '');
        tr.innerHTML = '<td class="text-nowrap"><button type="button" class="btn btn-sm btn-link p-0" data-mover="-1" title="Subir"><i class="bi bi-arrow-up"></i></button> <button type="button" class="btn btn-sm btn-link p-0" data-mover="1" title="Bajar"><i class="bi bi-arrow-down"></i></button></td>' +
            '<td><input type="text" name="placas[' + i + '][codigo]" class="form-control form-control-sm" style="width:5rem" placeholder="Código"></td>' +
            '<td><select name="placas[' + i + '][tipo_id]" class="form-select form-select-sm" data-tipo style="width:9rem">' +
            TIPOS.map(function (t) { return '<option value="' + t.id + '"' + (String(t.id) === ultimo ? ' selected' : '') + '>' + esc(t.nombre) + '</option>'; }).join('') + '</select></td>' +
            '<td><input type="number" min="0" name="placas[' + i + '][hombres]" class="form-control form-control-sm" style="width:5rem" data-h value="0"></td>' +
            '<td><input type="number" min="0" name="placas[' + i + '][mujeres]" class="form-control form-control-sm" style="width:5rem" data-m value="0"></td>' +
            '<td><input type="number" min="0" name="placas[' + i + '][unidades]" class="form-control form-control-sm" style="width:5.5rem" data-u value="0"></td>' +
            '<td><textarea name="placas[' + i + '][motivo]" rows="1" class="form-control form-control-sm" style="min-width:18rem"></textarea></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar-fila title="Quitar"><i class="bi bi-x-lg"></i></button></td>';
        tablaPlacas.querySelector('tbody').appendChild(tr);
        ajustarFila(tr, true);
        tr.querySelector('input[type=text]').focus();
    });
})();
</script>

<?= $this->endSection() ?>
