<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
    $id       = (int) $salida['id'];
    $borrador = $salida['estado'] === 'borrador';
    $total    = array_sum(array_column($lineas, 'cantidad'));
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-receipt text-primary"></i>
    <a href="<?= site_url('piezas/salidas') ?>" class="text-decoration-none text-muted fw-normal">Albaranes</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">#<?= $id ?> · <?= esc($destinos[$salida['destino_id']] ?? 'Sin destino') ?></strong>
    <?php if ($borrador): ?>
        <span class="badge text-bg-warning">Borrador · no toca el stock</span>
    <?php else: ?>
        <span class="badge text-bg-success">Confirmado <?= esc(substr((string) $salida['confirmada_en'], 0, 16)) ?></span>
    <?php endif; ?>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <?php if ($foto): ?>
            <a href="<?= esc($foto['original'], 'attr') ?>" target="_blank" title="Ver original">
                <img src="<?= esc($foto['vista'], 'attr') ?>" alt="Foto del albarán" class="img-fluid rounded border mb-2">
            </a>
        <?php else: ?>
            <div class="rounded border border-dashed text-muted small p-4 text-center mb-2">Sin foto</div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/actualizar') ?>" enctype="multipart/form-data" class="card card-body">
            <?= csrf_field() ?>
            <div class="mb-2">
                <label class="form-label small mb-1"><?= $foto ? 'Cambiar foto' : 'Añadir foto' ?></label>
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
            </div>
            <div class="row g-2 mb-2">
                <div class="col-7">
                    <label class="form-label small mb-1">Quién / adónde</label>
                    <select name="destino_id" class="form-select form-select-sm">
                        <?php foreach ($destinos as $did => $nombre): ?>
                            <option value="<?= (int) $did ?>"<?= (int) $salida['destino_id'] === (int) $did ? ' selected' : '' ?>><?= esc($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-5">
                    <label class="form-label small mb-1">Fecha</label>
                    <input type="date" name="fecha" value="<?= esc($salida['fecha'], 'attr') ?>" class="form-control form-control-sm">
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label small mb-1">Nota</label>
                <input type="text" name="nota" maxlength="500" value="<?= esc((string) $salida['nota'], 'attr') ?>" class="form-control form-control-sm" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">Guardar datos</button>
        </form>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <strong>Se lleva</strong>
                <span class="badge rounded-pill text-bg-secondary"><?= (int) $total ?> ud.</span>
            </div>
            <?php if ($lineas === []): ?>
                <p class="text-muted small p-3 mb-0">Todavía nada. <?= $borrador ? 'Añade abajo lo que se lleva y de dónde sale.' : '' ?></p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($lineas as $l): ?>
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <?php if ($l['imagen']): ?>
                                <img src="<?= esc($l['imagen'], 'attr') ?>" alt="" width="40" height="40" class="rounded object-fit-cover" loading="lazy">
                            <?php endif; ?>
                            <div class="flex-grow-1 min-w-0">
                                <?php if ($l['variante_id']): ?>
                                    <a href="<?= site_url('piezas/existencias/' . (int) $l['variante_id']) ?>" class="text-decoration-none"><?= esc($l['nombre']) ?></a>
                                <?php else: ?>
                                    <?= esc($l['nombre']) ?>
                                <?php endif; ?>
                                <div class="small text-muted">
                                    de <?= $l['origen'] === 'hueco' ? 'su hueco' . ($l['hueco'] ? ' ' . esc($l['hueco']) : ' (sin ubicación)') : esc(mb_strtolower($origenes[$l['origen']])) ?>
                                </div>
                            </div>
                            <span class="fw-bold"><?= (int) $l['cantidad'] ?></span>
                            <?php if ($borrador): ?>
                                <form method="post" action="<?= site_url('piezas/salidas/lineas/' . (int) $l['id'] . '/borrar') ?>"
                                    onsubmit="return confirm('¿Quitar esta línea del albarán?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Quitar línea"><i class="bi bi-x-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($borrador): ?>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/lineas/crear') ?>" class="card-footer" data-linea>
                    <?= csrf_field() ?>
                    <input type="hidden" name="variante_id">
                    <div class="d-flex gap-2 mb-2">
                        <input type="search" class="form-control form-control-sm" placeholder="Añadir pieza: nombre o SKU…" data-buscar autocomplete="off">
                        <input type="search" class="form-control form-control-sm" style="max-width: 9rem;" placeholder="o hueco: E1H2 ⏎" data-hueco autocomplete="off" title="Código del hueco y Enter: sale la pieza que vive ahí">
                    </div>
                    <div class="list-group small mb-2" data-resultados></div>
                    <div class="d-none" data-elegida>
                        <div class="small fw-semibold mb-1" data-nombre></div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <select name="origen" class="form-select form-select-sm" style="width: auto;"></select>
                            <input type="number" name="cantidad" min="1" value="1" class="form-control form-control-sm" style="width: 5rem;" required>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Añadir</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($borrador): ?>
            <?php $titulosCaja = ['impresas' => ['Caja de impresas', 'bi-box-seam'], 'pedido' => ['Caja de pedido', 'bi-bag']]; ?>
            <div class="card mt-3 border-warning-subtle">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-right text-warning"></i> <strong>Desde las cajas</strong>
                    <span class="small text-muted">lo más rápido: añade con +</span>
                </div>
                <?php foreach ($cajas as $caja => $piezas): ?>
                    <?php [$tituloCaja, $iconoCaja] = $titulosCaja[$caja]; ?>
                    <div class="px-3 pt-2 pb-1 d-flex align-items-center gap-2 bg-body-tertiary border-top">
                        <button type="button" class="btn btn-sm btn-link text-reset text-decoration-none p-0 d-flex align-items-center gap-2"
                            <?= $piezas !== [] ? 'data-bs-toggle="collapse" data-bs-target="#caja-' . $caja . '" aria-expanded="true"' : 'disabled' ?> title="Plegar/desplegar">
                            <i class="bi bi-chevron-down plegar-caja"></i>
                            <i class="bi <?= $iconoCaja ?>"></i>
                            <span class="fw-semibold small"><?= $tituloCaja ?></span>
                        </button>
                        <span class="small text-muted"><?= $piezas === [] ? 'nada disponible' : array_sum(array_column($piezas, 'cantidad')) . ' ud.' ?></span>
                        <?php if ($piezas !== []): ?>
                            <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/lineas/caja') ?>" class="ms-auto"
                                onsubmit="return confirm('¿Añadir al albarán todo lo de la <?= mb_strtolower($tituloCaja) ?>?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="caja" value="<?= $caja ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning py-0">Toda la caja</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($piezas !== []): ?>
                        <ul class="list-group list-group-flush collapse show" id="caja-<?= $caja ?>" data-plegable-caja>
                            <?php foreach ($piezas as $p): ?>
                                <li class="list-group-item d-flex align-items-center gap-2 py-1">
                                    <?php if ($p['imagen']): ?>
                                        <img src="<?= esc($p['imagen'], 'attr') ?>" alt="" width="32" height="32" class="rounded object-fit-cover" loading="lazy">
                                    <?php endif; ?>
                                    <span class="flex-grow-1 small"><?= esc($p['nombre']) ?></span>
                                    <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/lineas/crear') ?>" class="d-flex gap-1 align-items-center">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="variante_id" value="<?= (int) $p['id'] ?>">
                                        <input type="hidden" name="origen" value="<?= $caja ?>">
                                        <input type="number" name="cantidad" value="1" min="1" max="<?= (int) $p['cantidad'] ?>"
                                            class="form-control form-control-sm" style="width: 4rem;" title="Hay <?= (int) $p['cantidad'] ?>">
                                        <span class="small text-muted text-nowrap">/ <?= (int) $p['cantidad'] ?></span>
                                        <button type="submit" class="btn btn-sm btn-primary" title="Añadir al albarán"><i class="bi bi-plus-lg"></i></button>
                                        <button type="submit" name="cantidad" value="<?= (int) $p['cantidad'] ?>" class="btn btn-sm btn-outline-secondary" title="Añadir las <?= (int) $p['cantidad'] ?>">Todas</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-3 flex-wrap">
            <?php if ($borrador): ?>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/confirmar') ?>"
                    onsubmit="return confirm('¿Confirmar el albarán? Las <?= (int) $total ?> pieza(s) salen del inventario.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success" <?= $lineas === [] ? 'disabled' : '' ?>><i class="bi bi-check2-circle"></i> Confirmar salida</button>
                </form>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/borrar') ?>" class="ms-auto"
                    onsubmit="return confirm('¿Borrar este albarán y su foto?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Borrar albarán</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/reabrir') ?>"
                    onsubmit="return confirm('¿Reabrir? Las piezas vuelven a su sitio hasta que lo confirmes otra vez.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Reabrir para corregir</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .plegar-caja { transition: transform .15s ease; font-size: .75rem; }
    [aria-expanded="false"] .plegar-caja { transform: rotate(-90deg); }
</style>

<?php if ($borrador): ?>
<script>
    document.querySelectorAll('[data-plegable-caja]').forEach(function (ul) {
        var clave = 'piezas_albaran_caja_plegada_' + ul.id;
        var boton = document.querySelector('[data-bs-target="#' + ul.id + '"]');
        try {
            if (localStorage.getItem(clave) === '1') {
                ul.classList.remove('show');
                boton.setAttribute('aria-expanded', 'false');
            }
        } catch (e) {}
        ul.addEventListener('hidden.bs.collapse', function () { try { localStorage.setItem(clave, '1'); } catch (e) {} });
        ul.addEventListener('shown.bs.collapse', function () { try { localStorage.setItem(clave, '0'); } catch (e) {} });
    });
</script>
<script>
    (function () {
        var f = document.querySelector('[data-linea]');
        var buscar = f.querySelector('[data-buscar]'), hueco = f.querySelector('[data-hueco]'), lista = f.querySelector('[data-resultados]'),
            elegida = f.querySelector('[data-elegida]'), espera = null;
        var etiquetas = <?= json_encode($origenes, JSON_UNESCAPED_UNICODE) ?>;

        function elegir(p, origen) {
            f.variante_id.value = p.id;
            f.querySelector('[data-nombre]').textContent = p.nombre;
            f.origen.innerHTML = '';
            Object.keys(etiquetas).forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o;
                opt.textContent = (o === 'hueco' ? 'Hueco ' + (p.hueco || '(sin ubicación)') : etiquetas[o]) + ' · ' + p.hay[o] + ' ud.';
                f.origen.appendChild(opt);
            });
            f.origen.value = origen || (p.hay.pedido > 0 ? 'pedido' : (p.hay.impresas > 0 ? 'impresas' : 'hueco'));
            f.cantidad.value = 1;
            elegida.classList.remove('d-none');
            lista.innerHTML = '';
            buscar.value = '';
            hueco.value = '';
            f.cantidad.focus();
        }

        function consultar(url, origen, auto) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    lista.innerHTML = '';
                    if (auto && d.resultados.length === 1) {
                        elegir(d.resultados[0], origen);
                        return;
                    }
                    if (!d.resultados.length) {
                        lista.innerHTML = '<div class="text-muted px-1">' + (d.aviso || 'Nada.') + '</div>';
                    }
                    d.resultados.forEach(function (p) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'list-group-item list-group-item-action d-flex justify-content-between gap-2';
                        b.innerHTML = '<span></span><span class="text-muted text-nowrap"></span>';
                        b.firstChild.textContent = p.nombre;
                        var donde = [];
                        if (p.hay.hueco) donde.push((p.hueco || 'sin ubic.') + ': ' + p.hay.hueco);
                        if (p.hay.impresas) donde.push('impresas: ' + p.hay.impresas);
                        if (p.hay.pedido) donde.push('pedido: ' + p.hay.pedido);
                        b.lastChild.textContent = donde.join(' · ') || 'sin unidades';
                        b.addEventListener('click', function () { elegir(p, origen); });
                        lista.appendChild(b);
                    });
                });
        }

        buscar.addEventListener('input', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                var q = buscar.value.trim();
                if (q.length < 2) { lista.innerHTML = ''; return; }
                consultar('<?= site_url('piezas/salidas/buscar') ?>?q=' + encodeURIComponent(q));
            }, 250);
        });

        hueco.addEventListener('input', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                var c = hueco.value.trim();
                if (c.length < 2) { lista.innerHTML = ''; return; }
                consultar('<?= site_url('piezas/salidas/hueco') ?>?codigo=' + encodeURIComponent(c), 'hueco');
            }, 300);
        });
        hueco.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            clearTimeout(espera);
            var c = hueco.value.trim();
            if (c.length >= 2) consultar('<?= site_url('piezas/salidas/hueco') ?>?codigo=' + encodeURIComponent(c), 'hueco', true);
        });
    })();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
