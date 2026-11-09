<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
    $borradores = count(array_filter($salidas, static fn ($s) => $s['estado'] === 'borrador'));
    $confirmados = count($salidas) - $borradores;
    $udsSalidas = 0;
    foreach ($salidas as $s) {
        if ($s['estado'] !== 'borrador') { $udsSalidas += (int) ($resumen[$s['id']]['uds'] ?? 0); }
    }
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Albaranes</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/existencias') ?>" class="fv-gbtn" title="Stock de cada pieza"><i class="bi bi-boxes"></i> Existencias</a>
            <a href="<?= site_url('piezas/ubicaciones') ?>" class="fv-gbtn" title="Estuches y huecos de guardado"><i class="bi bi-geo-alt"></i> Ubicaciones</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/salidas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <div class="pv-hero-fila">
            <h1>Albaranes</h1>
            <button type="button" class="fv-btn fv-btn-pri" data-bs-toggle="modal" data-bs-target="#modalNuevoAlbaran"><i class="bi bi-plus-lg"></i> Nuevo albarán</button>
        </div>
        <div class="fv-meta">
            <?php if ($borradores): ?>
                <span class="fv-estado e-warn"><?= $borradores ?> borrador<?= $borradores === 1 ? '' : 'es' ?> sin confirmar</span>
                <span class="fv-dot"></span>
            <?php endif; ?>
            <span><b><?= $confirmados ?></b> confirmado<?= $confirmados === 1 ? '' : 's' ?></span>
            <span><b><?= $udsSalidas ?></b> ud. han salido</span>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <div class="pv-dos">
        <section class="fv-panel">
            <div class="fv-ph">
                <div class="pv-chips" id="alFiltro">
                    <button type="button" class="pv-chip" data-f="" aria-pressed="true">Todos <span class="n"><?= count($salidas) ?></span></button>
                    <button type="button" class="pv-chip" data-f="borrador" aria-pressed="false"><span class="fv-estado e-warn"></span>Borradores <span class="n"><?= $borradores ?></span></button>
                    <button type="button" class="pv-chip" data-f="confirmada" aria-pressed="false"><span class="fv-estado e-ok"></span>Confirmados <span class="n"><?= $confirmados ?></span></button>
                </div>
            </div>
            <?php if ($salidas === []): ?>
                <p class="fv-vacio">Todavía no hay albaranes.</p>
            <?php else: ?>
                <div class="pv-lista">
                    <?php foreach ($salidas as $s): ?>
                        <?php $r = $resumen[$s['id']] ?? null; $borr = $s['estado'] === 'borrador'; ?>
                        <a href="<?= site_url('piezas/salidas/' . (int) $s['id']) ?>" class="pv-fila al-fila al-filtro" data-estado="<?= $borr ? 'borrador' : 'confirmada' ?>">
                            <span class="pv-ico g">
                                <?php if (!empty($s['ruta_imagen'])): ?>
                                    <img src="<?= esc(imagen_pieza($s, 'salidas'), 'attr') ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-receipt"></i>
                                <?php endif; ?>
                            </span>
                            <div class="pv-cuerpo">
                                <div class="tit">
                                    <span class="num">#<?= (int) $s['id'] ?></span>
                                    <span class="pv-nom"><?= esc($destinos[$s['destino_id']] ?? 'Sin destino') ?></span>
                                    <span class="f"><?= esc(date('d/m/Y', strtotime($s['fecha']))) ?></span>
                                </div>
                                <div class="pv-sub">
                                    <span class="fv-estado <?= $borr ? 'e-warn' : 'e-ok' ?>"><?= $borr ? 'Borrador' : 'Confirmado' ?></span>
                                    <span><?= $r ? (int) $r['lineas'] . ' línea' . ((int) $r['lineas'] === 1 ? '' : 's') : 'Sin líneas' ?></span>
                                    <?php if (!empty($s['nota'])): ?><span class="nota"><?= esc($s['nota']) ?></span><?php endif; ?>
                                </div>
                            </div>
                            <span class="pv-n"><?= $r ? (int) $r['uds'] : 0 ?><small>ud.</small></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <p class="fv-vacio d-none" id="alVacio">Nada en este filtro.</p>
            <?php endif; ?>
        </section>

        <aside class="pv-lado">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Destinos</h3><span class="cuenta"><?= count($destinos) ?></span></div>
                <div>
                    <?php foreach ($destinos as $did => $nombre): ?>
                        <?php $n = (int) ($usos[$did] ?? 0); ?>
                        <div class="al-dest">
                            <form method="post" action="<?= site_url('piezas/salidas/destinos/' . (int) $did . '/renombrar') ?>">
                                <?= csrf_field() ?>
                                <input type="text" name="nombre" maxlength="60" value="<?= esc($nombre, 'attr') ?>" class="fv-input" required title="Pulsa para renombrar">
                                <button type="submit" class="fv-ibtn guardar" title="Guardar nombre"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <span class="n" title="Albaranes con este destino"><?= $n ?> alb.</span>
                            <form method="post" action="<?= site_url('piezas/salidas/destinos/' . (int) $did . '/borrar') ?>"
                                onsubmit="return confirm('¿Borrar el destino «<?= esc($nombre, 'js') ?>»?<?= $n > 0 ? ' Sus ' . $n . ' albarán(es) quedarán sin destino.' : '' ?>');">
                                <?= csrf_field() ?>
                                <button type="submit" class="fv-ibtn peligro" title="Borrar destino"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" action="<?= site_url('piezas/salidas/destinos/crear') ?>" class="al-nuevo-dest">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" maxlength="60" class="fv-input" placeholder="Nuevo destino…" required>
                    <button type="submit" class="fv-btn fv-btn-sm"><i class="bi bi-plus-lg"></i> Añadir</button>
                </form>
            </section>
        </aside>
    </div>
</div>

<div class="modal fade" id="modalNuevoAlbaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= site_url('piezas/salidas/crear') ?>" enctype="multipart/form-data" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Nuevo albarán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Foto (opcional)</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control">
                    <div class="form-text">La que manda Esther. Luego, en el albarán, apuntas qué se lleva.</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label">Quién / adónde</label>
                        <select name="destino_id" class="form-select">
                            <?php foreach ($destinos as $did => $nombre): ?>
                                <option value="<?= (int) $did ?>"><?= esc($nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" class="form-control">
                    </div>
                </div>
                <div>
                    <label class="form-label">Nota (opcional)</label>
                    <input type="text" name="nota" maxlength="500" class="form-control" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-receipt"></i> Crear albarán</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var caja = document.getElementById('alFiltro');
    if (!caja) return;
    var filas = document.querySelectorAll('.al-filtro'), vacio = document.getElementById('alVacio');
    caja.addEventListener('click', function (e) {
        var b = e.target.closest('[data-f]');
        if (!b) return;
        caja.querySelectorAll('[data-f]').forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
        var n = 0;
        filas.forEach(function (f) {
            var ok = !b.dataset.f || f.dataset.estado === b.dataset.f;
            f.hidden = !ok;
            if (ok) n++;
        });
        if (vacio) vacio.classList.toggle('d-none', n > 0);
    });
})();
</script>

<?= $this->endSection() ?>
