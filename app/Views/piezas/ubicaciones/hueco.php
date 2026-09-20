<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    /* Tarjetas estrechas de ancho fijo en vez de una única lista a lo ancho
       de la pantalla — en monitores anchos una fila de texto suelto queda
       perdida y el título/badge de la cabecera se separan demasiado. */
    .piezas-grid { display: flex; flex-wrap: wrap; gap: .65rem; }
    .pieza-card {
        width: 15rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        padding: .6rem .7rem;
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    .pieza-card .miniatura {
        width: 2.5rem;
        height: 2.5rem;
        object-fit: cover;
        border-radius: .5rem;
        background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
        flex: none;
    }
    .pieza-card .miniatura-vacia {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: .5rem;
        background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-secondary-color);
        flex: none;
    }
    .pieza-card .cuerpo { flex: 1 1 auto; min-width: 0; }
    .pieza-card .nombre {
        display: block;
        text-decoration: none;
        color: inherit;
        font-weight: 500;
        font-size: .85rem;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .pieza-card .nombre:hover { color: var(--bs-primary); }
    .pieza-card .pie { display: flex; align-items: center; justify-content: space-between; gap: .4rem; margin-top: .25rem; }

    @media (max-width: 400px) {
        .pieza-card { width: 100%; }
    }

    .accion-panel {
        border-radius: .85rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
        padding: .85rem 1rem;
    }
    .accion-panel .titulo {
        font-size: .8rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .6rem;
    }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-geo-alt text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="text-decoration-none text-muted fw-normal">Ubicaciones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($codigo) ?></strong>

    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/borrar') ?>" class="ms-auto"
        onsubmit="return confirm('¿Borrar el hueco «<?= esc($codigo, 'js') ?>»? Lo que había aquí queda sin ubicación, no se pierde.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Borrar hueco</button>
    </form>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>
<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if ($estuche || $hueco['notas']): ?>
    <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
        <?php if ($estuche): ?><span><i class="bi bi-archive"></i> Estuche <?= esc($estuche['codigo']) ?></span><?php endif; ?>
        <?php if ($estuche && $estuche['zona']): ?><span><i class="bi bi-signpost"></i> <?= esc($estuche['zona']) ?></span><?php endif; ?>
        <?php if ($hueco['notas']): ?><span><i class="bi bi-sticky"></i> <?= nl2br(esc($hueco['notas'])) ?></span><?php endif; ?>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-2">
    <div class="small fw-semibold text-body-secondary"><i class="bi bi-boxes"></i> Contenido</div>
    <?php if (!empty($filas)): ?>
        <span class="badge rounded-pill text-bg-primary"><?= array_sum(array_column($filas, 'stock')) ?> uds. en total</span>
    <?php endif; ?>
</div>

<?php if (empty($filas)): ?>
    <p class="text-muted small">Vacío por ahora.</p>
<?php else: ?>
    <div class="piezas-grid mb-3">
        <?php foreach ($filas as $f): ?>
            <div class="pieza-card">
                <?php if ($f['img']): ?>
                    <img src="<?= esc($f['img'], 'attr') ?>" alt="" class="miniatura" loading="lazy">
                <?php else: ?>
                    <span class="miniatura-vacia"><i class="bi bi-box-seam"></i></span>
                <?php endif; ?>

                <div class="cuerpo">
                    <a href="<?= site_url('piezas/existencias/' . (int) $f['variante']['id']) ?>" class="nombre" title="<?= esc($f['nombre'], 'attr') ?>">
                        <?= esc($f['nombre']) ?>
                    </a>
                    <div class="pie">
                        <span class="badge text-bg-light border"><?= (int) $f['stock'] ?> uds.</span>

                        <?php if (!empty($destinos)): ?>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" data-bs-toggle="dropdown" title="Mover a otro hueco">
                                    <i class="bi bi-arrow-right-circle"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 15rem;">
                                    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/mover-pieza') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="variante_id" value="<?= (int) $f['variante']['id'] ?>">
                                        <label class="form-label small mb-1 text-muted">Mover esta pieza a</label>
                                        <select name="destino_id" required class="form-select form-select-sm mb-2">
                                            <option value="">Elige hueco…</option>
                                            <?php foreach ($destinosPorEstuche as $grupo): ?>
                                                <optgroup label="<?= esc($grupo['estuche']['codigo']) ?>">
                                                    <?php foreach ($grupo['huecos'] as $h): ?>
                                                        <option value="<?= $h['id'] ?>"><?= esc($h['codigo']) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary w-100">Mover</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($destinos) || !empty($hayAlgoQueTraer)): ?>
    <div class="row g-3">
        <?php if (!empty($destinos)): ?>
            <div class="col-12 col-md-6">
                <div class="accion-panel h-100">
                    <div class="titulo"><i class="bi bi-box-arrow-right"></i> Vaciar este hueco</div>
                    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/mover') ?>"
                        class="d-flex flex-wrap gap-2 align-items-center">
                        <?= csrf_field() ?>
                        <span class="text-muted small">Mover todo lo de aquí a</span>
                        <select name="destino_id" required class="form-select form-select-sm" style="max-width: 11rem;">
                            <option value="">Elige hueco…</option>
                            <?php foreach ($destinosPorEstuche as $grupo): ?>
                                <optgroup label="<?= esc($grupo['estuche']['codigo']) ?>">
                                    <?php foreach ($grupo['huecos'] as $h): ?>
                                        <option value="<?= $h['id'] ?>"><?= esc($h['codigo']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Mover</button>
                    </form>
                    <p class="text-muted small mb-0 mt-2">
                        Para fusionar dos huecos: mueve lo de aquí y luego borra el que quede vacío. Para separar solo
                        una pieza, usa el botón <i class="bi bi-arrow-right-circle"></i> junto a ella arriba.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($hayAlgoQueTraer)): ?>
            <div class="col-12 col-md-6">
                <div class="accion-panel h-100">
                    <div class="titulo"><i class="bi bi-box-arrow-in-down"></i> Traer pieza aquí</div>
                    <form method="post" action="<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/traer-pieza') ?>"
                        class="d-flex flex-wrap gap-2 align-items-center" data-traer-form>
                        <?= csrf_field() ?>
                        <div class="position-relative flex-grow-1" style="min-width: 12rem;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Busca por nombre o SKU…"
                                    data-traer-buscar autocomplete="off">
                            </div>
                            <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;"
                                data-traer-resultados></div>
                        </div>
                        <input type="hidden" name="variante_id" data-traer-id>
                        <button type="submit" class="btn btn-sm btn-primary" data-traer-enviar disabled>Traer</button>
                    </form>
                    <p class="text-muted small mb-0 mt-2">Trae toda su cantidad, esté en otro hueco o suelta sin asignar.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            var form = document.querySelector('[data-traer-form]');
            if (!form) return;

            var caja        = form.querySelector('[data-traer-buscar]');
            var resultados  = form.querySelector('[data-traer-resultados]');
            var idInput     = form.querySelector('[data-traer-id]');
            var enviar      = form.querySelector('[data-traer-enviar]');
            var espera      = null;
            var urlBuscar   = '<?= site_url('piezas/ubicaciones/huecos/' . (int) $hueco['id'] . '/pieza-buscar') ?>';

            function limpiarEleccion() {
                idInput.value = '';
                enviar.disabled = true;
            }

            caja.addEventListener('input', function () {
                limpiarEleccion();
                clearTimeout(espera);
                var q = caja.value.trim();
                if (q.length < 2) {
                    resultados.style.display = 'none';
                    resultados.innerHTML = '';
                    return;
                }
                espera = setTimeout(function () {
                    fetch(urlBuscar + '?q=' + encodeURIComponent(q), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            var lista = d.resultados || [];
                            resultados.innerHTML = lista.length
                                ? lista.map(function (p) {
                                    var texto = p.nombre.replace(/&/g, '&amp;').replace(/</g, '&lt;');
                                    return '<button type="button" class="list-group-item list-group-item-action py-1 small" '
                                        + 'data-traer-opcion data-id="' + p.id + '" data-nombre="' + texto + '">'
                                        + texto + ' <span class="text-muted">(' + p.stock + ')</span></button>';
                                }).join('')
                                : '<div class="list-group-item py-1 small text-muted">Sin resultados</div>';
                            resultados.style.display = 'block';
                        });
                }, 250);
            });

            caja.addEventListener('blur', function () {
                setTimeout(function () { resultados.style.display = 'none'; }, 200);
            });

            resultados.addEventListener('click', function (e) {
                var opcion = e.target.closest('[data-traer-opcion]');
                if (!opcion) return;

                idInput.value = opcion.getAttribute('data-id');
                caja.value = opcion.getAttribute('data-nombre');
                enviar.disabled = false;
                resultados.style.display = 'none';
            });
        })();
    </script>
<?php endif; ?>

<?= $this->endSection() ?>
