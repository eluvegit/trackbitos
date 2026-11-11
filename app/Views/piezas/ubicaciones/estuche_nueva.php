<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
/**
 * Un solo estuche (UbicacionesController::verEstuche): sus huecos uno por
 * uno con lo que hay dentro. La cifra de cada pieza es su total; en pequeño,
 * lo que hay aquí y lo que sigue en las cajas (helper piezas_stock).
 */
helper('piezas_stock');
$idEst     = (int) $estuche['id'];
$nPiezas   = array_sum(array_map(static fn (array $f) => count($f['contenido']), $filas));
$total     = array_sum(array_column($filas, 'total'));
$aqui      = array_sum(array_column($filas, 'aqui'));
$ocupados  = count(array_filter($filas, static fn (array $f) => $f['contenido'] !== []));
?>

<div class="fv pv eu">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/ubicaciones') ?>">Ubicaciones</a><i class="bi bi-chevron-right"></i><span>Estuche <?= esc($estuche['codigo']) ?></span></nav>
        <div class="fv-acciones">
            <?php if (count($estuches) > 1): ?>
                <select class="fv-input eu-saltar" aria-label="Ir a otro estuche" onchange="if (this.value) location.href = this.value">
                    <?php foreach ($estuches as $e): ?>
                        <option value="<?= site_url('piezas/ubicaciones/' . (int) $e['id']) ?>" <?= (int) $e['id'] === $idEst ? 'selected' : '' ?>>Estuche <?= esc($e['codigo']) ?><?= $e['zona'] ? ' · ' . esc($e['zona']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button type="button" class="fv-gbtn" data-bs-toggle="modal" data-bs-target="#modalEditarEstuche"><i class="bi bi-pencil"></i> Editar</button>
            <a href="<?= site_url('piezas/ubicaciones/cajas') ?>" class="fv-gbtn" title="Impresas y de pedido pendientes de guardar"><i class="bi bi-box-seam"></i> Cajas</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1 class="fv-mono"><?= esc($estuche['codigo']) ?></h1>
        <div class="fv-meta">
            <?php if ($estuche['zona']): ?><span><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span><span class="fv-dot"></span><?php endif; ?>
            <span><b><?= count($filas) ?></b> huecos · <?= $ocupados ?> ocupados</span>
            <span><b><?= $nPiezas ?></b> pieza<?= $nPiezas === 1 ? '' : 's' ?></span>
            <span title="Todo lo que existe de las piezas de este estuche"><b><?= (int) $total ?></b> ud. en total</span>
            <?php if ($aqui !== $total): ?><span class="t3"><?= (int) $aqui ?> aquí, el resto en las cajas</span><?php endif; ?>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if (!empty($estuche['notas'])): ?>
        <div class="fv-banner"><i class="bi bi-sticky"></i><span><?= nl2br(esc($estuche['notas'])) ?></span></div>
    <?php endif; ?>

    <?php if ($nPiezas > 0): ?>
        <div class="eu-busca">
            <label class="eu-buscar">
                <i class="bi bi-search"></i>
                <input type="search" id="euBuscar" placeholder="Buscar en el estuche por nombre, SKU o hueco" autocomplete="off" aria-label="Buscar en el estuche">
                <span class="eu-cuenta" id="euCuenta"><b><?= $nPiezas ?></b> piezas</span>
            </label>
        </div>
    <?php endif; ?>

    <?php if ($filas === []): ?>
        <p class="fv-vacio">Este estuche no tiene huecos todavía.</p>
    <?php endif; ?>

    <div class="eu-grid">
        <?php foreach ($filas as $h): ?>
            <?php $hid = (int) $h['hueco']['id']; ?>
            <section class="fv-panel eu-hueco<?= $h['contenido'] === [] ? ' vacio' : '' ?>" data-eu-hueco="<?= esc(mb_strtolower($h['codigo']), 'attr') ?>">
                <div class="fv-ph">
                    <a href="<?= site_url('piezas/ubicaciones/huecos/' . $hid) ?>" class="eu-cod fv-mono" title="Abrir el hueco"><?= esc($h['codigo']) ?></a>
                    <?php if ($h['contenido'] !== []): ?>
                        <span class="cuenta" title="Total de las piezas de este hueco"><?= (int) $h['total'] ?> ud.<?= $h['aqui'] !== $h['total'] ? ' · ' . (int) $h['aqui'] . ' aquí' : '' ?></span>
                    <?php endif; ?>
                    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . $hid . '/borrar') ?>" class="der"
                        onsubmit="return confirm('¿Quitar el hueco «<?= esc($h['codigo'], 'js') ?>»?<?= $h['contenido'] !== [] ? ' Lo que había aquí queda sin ubicación, no se pierde.' : '' ?>');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="volver" value="estuche">
                        <button type="submit" class="fv-ibtn peligro" title="Quitar hueco"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
                <?php if (!empty($h['hueco']['notas'])): ?><p class="es-ayuda"><?= esc($h['hueco']['notas']) ?></p><?php endif; ?>
                <?php if ($h['contenido'] === []): ?>
                    <p class="fv-vacio">Vacío.</p>
                <?php else: ?>
                    <div class="pv-lista">
                        <?php foreach ($h['contenido'] as $c): ?>
                            <?php $buscable = mb_strtolower($c['nombre'] . ' ' . ($c['sku'] ?? '') . ' ' . $h['codigo']); ?>
                            <div class="pv-fila eu-fila" data-buscar="<?= esc($buscable, 'attr') ?>">
                                <span class="pv-ico g"><?php if ($c['img']): ?><img src="<?= esc($c['img'], 'attr') ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?></span>
                                <div class="pv-cuerpo">
                                    <a href="<?= site_url('piezas/existencias/' . (int) $c['id']) ?>" class="pv-nom" title="<?= esc($c['nombre'], 'attr') ?>"><?= esc($c['nombre']) ?></a>
                                    <div class="pv-sub">
                                        <?php if (!empty($c['sku'])): ?><span class="fv-mono"><?= esc($c['sku']) ?></span><?php endif; ?>
                                        <?= stock_desglose($c['desglose'], 'hueco', 'aquí', true) ?>
                                    </div>
                                </div>
                                <span class="pv-n<?= $c['desglose']['total'] <= 0 ? ' e-bad' : '' ?>" title="Total de la pieza"><?= (int) $c['desglose']['total'] ?><small>ud.</small></span>
                                <span class="st-mov">
                                    <button type="button" class="fv-ibtn alta" title="Dar de alta"<?= stock_mov_attrs((int) $c['id'], $c['nombre'], $c['desglose'], 'alta') ?>><i class="bi bi-plus-lg"></i></button>
                                    <button type="button" class="fv-ibtn baja" title="Dar de baja"<?= stock_mov_attrs((int) $c['id'], $c['nombre'], $c['desglose'], 'baja') ?>><i class="bi bi-dash-lg"></i></button>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <form method="post" action="<?= site_url('piezas/ubicaciones/' . $idEst . '/huecos/crear') ?>" class="eu-add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="estuche">
            <button type="submit" class="eu-add" title="Añadir hueco <?= esc($siguiente) ?>">
                <i class="bi bi-plus-lg"></i><span>Añadir hueco <?= esc($siguiente) ?></span>
            </button>
        </form>
    </div>

    <p class="fv-vacio d-none" id="euSinResultados">Nada de este estuche coincide con la búsqueda.</p>
</div>

<div class="modal fade" id="modalEditarEstuche" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('piezas/ubicaciones/' . $idEst . '/actualizar') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="estuche">
            <div class="modal-header">
                <h6 class="modal-title">Editar estuche</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small mb-1">Código</label>
                    <input type="text" name="codigo" required maxlength="20" class="form-control" value="<?= esc($estuche['codigo']) ?>" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label small mb-1">Zona (opcional)</label>
                    <input type="text" name="zona" maxlength="100" class="form-control" value="<?= esc((string) $estuche['zona']) ?>" autocomplete="off">
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

<?= $this->include('piezas/_modal_mov_stock') ?>

<style>
    .eu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 14px; align-items: start; }
    .eu-hueco.vacio { opacity: .6; }
    .eu-cod { font-size: 15px; font-weight: 600; color: var(--fv-text) !important; text-decoration: none; }
    .eu-cod:hover { color: var(--fv-accent) !important; }
    .eu-fila .st-mov { opacity: 0; transition: opacity .12s; }
    .eu-fila:hover .st-mov, .eu-fila:focus-within .st-mov { opacity: 1; }
    @media (hover: none) { .eu-fila .st-mov { opacity: 1; } }
    .eu-add-form { margin: 0; }
    .eu-add { width: 100%; min-height: 96px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border-radius: var(--fv-r-lg); border: 1.5px dashed var(--fv-border-2); background: transparent; color: var(--fv-text-3); font-size: 13px; transition: all .12s; }
    .eu-add .bi { font-size: 20px; }
    .eu-add:hover { border-style: solid; border-color: var(--fv-accent); color: var(--fv-accent); background: rgb(255 106 61 / .06); }
    .eu-saltar { width: auto; height: 32px; padding: 0 10px; font-size: 13px; }
    .eu-busca { position: sticky; top: 0; z-index: 8; background: var(--fv-bg); padding: 8px 0 12px; margin: -8px 0 4px; }
    .eu-buscar { display: flex; align-items: center; gap: 12px; height: 50px; padding: 0 16px; margin: 0; border-radius: 14px; background: var(--fv-panel); border: 1px solid var(--fv-border-2); cursor: text; transition: border-color .15s, box-shadow .15s; }
    .eu-buscar:focus-within { border-color: rgb(255 106 61 / .55); box-shadow: 0 0 0 4px rgb(255 106 61 / .14); }
    .eu-buscar > .bi { font-size: 18px; color: var(--fv-text-3); }
    .eu-buscar input { flex: 1; min-width: 0; background: none !important; border: 0; outline: 0; box-shadow: none; color: var(--fv-text); font-size: 16px; }
    .eu-buscar input::placeholder { color: var(--fv-text-3); }
    .eu-cuenta { font-size: 12.5px; color: var(--fv-text-3); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .eu-cuenta b { color: var(--fv-text); font-weight: 500; }
    @media (max-width: 560px) { .eu-grid { grid-template-columns: minmax(0, 1fr); } .eu-saltar { max-width: 46vw; } }
</style>
<script>
(function () {
    var input = document.getElementById('euBuscar');
    if (!input) return;
    var filas   = document.querySelectorAll('.eu-fila[data-buscar]');
    var huecos  = document.querySelectorAll('.eu-hueco');
    var anadir  = document.querySelector('.eu-add-form');
    var cuenta  = document.getElementById('euCuenta');
    var sinRes  = document.getElementById('euSinResultados');
    var plano   = function (t) { return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };

    function aplicar() {
        var terminos = plano(input.value.trim()).split(/\s+/).filter(Boolean);
        var visibles = 0;
        filas.forEach(function (f) {
            var texto = plano(f.dataset.buscar || '');
            var ok = terminos.every(function (t) { return texto.indexOf(t) !== -1; });
            f.classList.toggle('d-none', !ok);
            if (ok) visibles++;
        });
        // Buscando, solo quedan los huecos con alguna pieza que encaje (o
        // cuyo código encaje, para encontrar "H3" aunque esté vacío).
        huecos.forEach(function (h) {
            var porCodigo = terminos.length && terminos.every(function (t) { return plano(h.dataset.euHueco || '').indexOf(t) !== -1; });
            if (porCodigo) h.querySelectorAll('.eu-fila').forEach(function (f) { f.classList.remove('d-none'); });
            var alguna = !!h.querySelector('.eu-fila:not(.d-none)');
            h.classList.toggle('d-none', terminos.length > 0 && !alguna && !porCodigo);
        });
        if (anadir) anadir.classList.toggle('d-none', terminos.length > 0);
        var hay = document.querySelector('.eu-hueco:not(.d-none)');
        sinRes.classList.toggle('d-none', !!hay || !terminos.length);
        visibles = document.querySelectorAll('.eu-fila:not(.d-none)').length;
        cuenta.innerHTML = terminos.length ? '<b>' + visibles + '</b> de ' + filas.length : '<b>' + filas.length + '</b> piezas';
    }

    input.addEventListener('input', aplicar);
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { input.value = ''; aplicar(); } });
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && !e.target.closest('input, textarea, select')) { e.preventDefault(); input.focus(); }
    });
})();
</script>

<?= $this->endSection() ?>
