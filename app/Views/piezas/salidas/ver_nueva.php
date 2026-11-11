<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
    $id       = (int) $salida['id'];
    $borrador = $salida['estado'] === 'borrador';
    $total    = array_sum(array_column($lineas, 'cantidad'));
    $destino  = $destinos[$salida['destino_id']] ?? 'Sin destino';
    $titulosCaja = ['impresas' => ['Caja de impresas', 'bi-box-seam'], 'pedido' => ['Caja de pedido', 'bi-bag']];
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/salidas') ?>">Albaranes</a><i class="bi bi-chevron-right"></i><span>#<?= $id ?></span></nav>
        <div class="fv-acciones">
            <button type="button" class="fv-gbtn" data-bs-toggle="modal" data-bs-target="#modalDatosAlbaran"><i class="bi bi-pencil"></i> Editar datos</button>
            <?php if (!$borrador): ?>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/reabrir') ?>"
                    onsubmit="return confirm('¿Reabrir? Las piezas vuelven a su sitio hasta que lo confirmes otra vez.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="fv-gbtn"><i class="bi bi-arrow-counterclockwise"></i> Reabrir para corregir</button>
                </form>
            <?php endif; ?>
            <?php if ($borrador): ?>
                <span class="fv-vsep"></span>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/borrar') ?>"
                    onsubmit="return confirm('¿Borrar este albarán y su foto?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="fv-gbtn peligro"><i class="bi bi-trash"></i> Borrar</button>
                </form>
            <?php endif; ?>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/salidas/' . $id . '?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <div class="pv-hero-fila">
            <h1><?= esc($destino) ?></h1>
            <?php if ($borrador): ?>
                <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/confirmar') ?>" class="al-pie"
                    onsubmit="return confirm('¿Confirmar el albarán? Las <?= (int) $total ?> pieza(s) salen del inventario.');">
                    <?= csrf_field() ?>
                    <?php if ($lineas === []): ?><span class="aviso">Añade algo para poder confirmar</span><?php endif; ?>
                    <button type="submit" class="fv-btn fv-btn-pri" <?= $lineas === [] ? 'disabled' : '' ?>><i class="bi bi-check2-circle"></i> Confirmar salida · <?= (int) $total ?> ud.</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="fv-meta">
            <?php if ($borrador): ?>
                <span class="fv-estado e-warn">Borrador · todavía no toca el stock</span>
            <?php else: ?>
                <span class="fv-estado e-ok">Confirmado el <?= esc(date('d/m/Y H:i', strtotime((string) $salida['confirmada_en']))) ?></span>
            <?php endif; ?>
            <span class="fv-dot"></span>
            <span class="fv-mono">#<?= $id ?></span>
            <span><?= esc(date('d/m/Y', strtotime($salida['fecha']))) ?></span>
            <span><b><?= (int) $total ?></b> ud.</span>
            <?php if (!empty($salida['nota'])): ?>
                <span class="fv-dot"></span>
                <span><?= esc($salida['nota']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="al-grid">
        <div class="fv-col al-izq">
            <section class="fv-panel fv-media">
                <?php if ($foto): ?>
                    <a href="<?= esc($foto['vista'], 'attr') ?>" class="fv-escena al-foto" data-foto-grande title="Ver en grande">
                        <img src="<?= esc($foto['vista'], 'attr') ?>" alt="Foto del albarán">
                    </a>
                <?php else: ?>
                    <button type="button" class="fv-escena al-foto vacia" data-bs-toggle="modal" data-bs-target="#modalDatosAlbaran">
                        <i class="bi bi-camera"></i> Añadir la foto del albarán
                    </button>
                <?php endif; ?>
            </section>
        </div>

        <div class="fv-col">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Se lleva</h3><span class="cuenta"><?= count($lineas) ?></span><span class="der t3" style="font-size:12px"><?= (int) $total ?> ud.</span></div>
                <?php if ($lineas === []): ?>
                    <p class="fv-vacio">Todavía nada.<?= $borrador ? ' Busca abajo lo que se lleva o añádelo desde las cajas.' : '' ?></p>
                <?php else: ?>
                    <div class="pv-lista al-lineas">
                        <?php foreach ($lineas as $l): ?>
                            <div class="pv-fila">
                                <span class="pv-ico"><?php if ($l['imagen']): ?><img src="<?= esc($l['imagen'], 'attr') ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-box"></i><?php endif; ?></span>
                                <div class="pv-cuerpo">
                                    <?php if ($l['variante_id']): ?>
                                        <a href="<?= site_url('piezas/existencias/' . (int) $l['variante_id']) ?>" class="pv-nom"><?= esc($l['nombre']) ?></a>
                                    <?php else: ?>
                                        <span class="pv-nom"><?= esc($l['nombre']) ?></span>
                                    <?php endif; ?>
                                    <div class="pv-sub">
                                        <?php if ($l['origen'] === 'hueco'): ?>
                                            <span><i class="bi bi-geo-alt"></i> <?= $l['hueco'] ? '<span class="fv-mono">' . esc($l['hueco']) . '</span>' : 'Sin ubicación' ?></span>
                                        <?php else: ?>
                                            <span><i class="bi <?= $titulosCaja[$l['origen']][1] ?? 'bi-box' ?>"></i> <?= esc($origenes[$l['origen']]) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="pv-n"><?= (int) $l['cantidad'] ?></span>
                                <?php if ($borrador): ?>
                                    <form method="post" action="<?= site_url('piezas/salidas/lineas/' . (int) $l['id'] . '/borrar') ?>" class="pv-acc"
                                        onsubmit="return confirm('¿Quitar esta línea del albarán?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="fv-ibtn peligro" title="Quitar línea"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($borrador): ?>
                    <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/lineas/crear') ?>" class="al-add" data-linea>
                        <?= csrf_field() ?>
                        <input type="hidden" name="variante_id">
                        <div class="fv-campo">
                            <input type="search" class="fv-input" placeholder="Añadir pieza: nombre o SKU…" data-buscar autocomplete="off">
                            <input type="search" class="fv-input hueco" placeholder="o hueco: E1H2" data-hueco autocomplete="off" title="Código del hueco y Enter: sale la pieza que vive ahí">
                        </div>
                        <div class="al-res" data-resultados></div>
                        <div class="al-elegida d-none" data-elegida>
                            <div class="nom" data-nombre></div>
                            <div class="fv-campo">
                                <select name="origen" class="fv-input"></select>
                                <input type="number" name="cantidad" min="1" value="1" class="fv-input" required>
                                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i> Añadir</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <?php if ($borrador): ?>
                <section class="fv-panel al-cajas">
                    <div class="fv-ph"><h3>Desde las cajas</h3><span class="der t3" style="font-size:12px">Lo más rápido: añade con +</span></div>
                    <?php foreach ($cajas as $caja => $piezas): ?>
                        <?php [$tituloCaja, $iconoCaja] = $titulosCaja[$caja]; ?>
                        <div class="al-caja">
                            <div class="al-caja-h">
                                <button type="button" <?= $piezas !== [] ? 'data-bs-toggle="collapse" data-bs-target="#caja-' . $caja . '" aria-expanded="true"' : 'disabled' ?> title="Plegar/desplegar">
                                    <i class="bi bi-chevron-right ch"></i><i class="bi <?= $iconoCaja ?>"></i> <?= $tituloCaja ?>
                                </button>
                                <span class="n"><?= $piezas === [] ? 'vacía' : array_sum(array_column($piezas, 'cantidad')) . ' ud.' ?></span>
                                <?php if ($piezas !== []): ?>
                                    <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/lineas/caja') ?>"
                                        onsubmit="return confirm('¿Añadir al albarán todo lo de la <?= mb_strtolower($tituloCaja) ?>?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="caja" value="<?= $caja ?>">
                                        <button type="submit" class="fv-btn fv-btn-sm">Toda la caja</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if ($piezas !== []): ?>
                                <div class="pv-lista al-caja-l collapse show" id="caja-<?= $caja ?>" data-plegable-caja>
                                    <?php foreach ($piezas as $p): ?>
                                        <div class="pv-fila">
                                            <span class="pv-ico"><?php if ($p['imagen']): ?><img src="<?= esc($p['imagen'], 'attr') ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-box"></i><?php endif; ?></span>
                                            <div class="pv-cuerpo"><span class="pv-nom" style="font-size:13px"><?= esc($p['nombre']) ?></span></div>
                                            <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/lineas/crear') ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="variante_id" value="<?= (int) $p['id'] ?>">
                                                <input type="hidden" name="origen" value="<?= $caja ?>">
                                                <input type="number" name="cantidad" value="1" min="1" max="<?= (int) $p['cantidad'] ?>" class="fv-input" title="Hay <?= (int) $p['cantidad'] ?>">
                                                <span class="de">/ <?= (int) $p['cantidad'] ?></span>
                                                <button type="submit" class="fv-ibtn" title="Añadir al albarán"><i class="bi bi-plus-lg"></i></button>
                                                <button type="submit" name="cantidad" value="<?= (int) $p['cantidad'] ?>" class="fv-btn fv-btn-sm" title="Añadir las <?= (int) $p['cantidad'] ?>">Todas</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDatosAlbaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= site_url('piezas/salidas/' . $id . '/actualizar') ?>" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Datos del albarán <span class="fw-normal">#<?= $id ?></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= $foto ? 'Cambiar foto' : 'Añadir foto' ?></label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label">Quién / adónde</label>
                        <select name="destino_id" class="form-select">
                            <?php foreach ($destinos as $did => $nombre): ?>
                                <option value="<?= (int) $did ?>"<?= (int) $salida['destino_id'] === (int) $did ? ' selected' : '' ?>><?= esc($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" value="<?= esc($salida['fecha'], 'attr') ?>" class="form-control">
                    </div>
                </div>
                <div>
                    <label class="form-label">Nota</label>
                    <input type="text" name="nota" maxlength="500" value="<?= esc((string) $salida['nota'], 'attr') ?>" class="form-control" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar datos</button>
            </div>
        </form>
    </div>
</div>

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
                        var v = document.createElement('div');
                        v.className = 'vacio';
                        v.textContent = d.aviso || 'Nada.';
                        lista.appendChild(v);
                    }
                    d.resultados.forEach(function (p) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.innerHTML = '<span></span><span></span>';
                        b.firstChild.textContent = p.nombre;
                        var donde = [];
                        if (p.hay.hueco) donde.push((p.hueco || 'sin ubic.') + ': ' + p.hay.hueco);
                        if (p.hay.impresas) donde.push('impresas: ' + p.hay.impresas);
                        if (p.hay.pedido) donde.push('pedido: ' + p.hay.pedido);
                        var totalPieza = p.hay.hueco + p.hay.impresas + p.hay.pedido;
                        b.lastChild.textContent = donde.length ? totalPieza + ' en total — ' + donde.join(' · ') : 'sin unidades';
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

<?= $this->include('piezas/_foto_grande') ?>

<?= $this->endSection() ?>
