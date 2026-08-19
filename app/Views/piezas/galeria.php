<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-grid-3x3-gap text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Galería</strong>

    <?php if (!empty($carrito)): ?>
        <div class="ms-auto d-flex gap-2">
            <form method="post" action="<?= site_url('piezas/carrito/vaciar') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary"
                    onclick="return confirm('¿Vaciar la placa?');">Vaciar placa</button>
            </form>
            <a href="<?= site_url('piezas/carrito/descargar') ?>" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-zip"></i> Descargar placa (<?= count($carrito) ?>)
            </a>
        </div>
    <?php endif; ?>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Piezas validadas, y también las que ya tienen una versión "para imprimir" o "impresa, sin
    validar" — esta pantalla es para meter STL en placas, y esas dos ya pueden tener uno adjunto
    aunque el resultado físico no esté juzgado todavía. Añade a la placa las que quieras imprimir
    juntas y descarga todos los STL de golpe en un .zip para el laminador.
</p>

<?php
/**
 * Qué le falta a la versión que se ofrece, cuando no es la validada — mismo
 * vocabulario y estilo que `$badgeMadurez` del índice, para no decir dos
 * cosas distintas de lo mismo según la pantalla.
 */
$badgeEstadoVersion = static function (array $version): string {
    if ($version['estado'] === 'impresa') {
        return '<span class="badge text-bg-primary" title="Impresa, pendiente de juzgar el resultado">'
            . '<i class="bi bi-printer-fill"></i> sin validar</span>';
    }
    if ($version['estado'] === 'borrador') {
        return '<span class="badge text-bg-secondary" title="Promocionada, pendiente de imprimir de prueba">'
            . '<i class="bi bi-printer"></i> para imprimir</span>';
    }

    return '';
};
?>

<?php $total = array_sum(array_map(fn($g) => count($g['piezas']), $grupos)); ?>

<?php if ($total === 0): ?>
    <p class="text-muted">Todavía no hay ninguna versión validada, ni ninguna "para imprimir". En cuanto promociones o valides una aparecerá aquí.</p>
<?php else: ?>
    <?php foreach ($grupos as $grupo): ?>
        <?php if (empty($grupo['piezas'])) continue; // Categoría vacía: aquí no aporta nada, a diferencia del índice. ?>
        <?php $categoria = $grupo['categoria']; ?>
        <?php $idGrupo = $categoria ? 'cat-' . (int) $categoria['id'] : 'cat-sin'; ?>
        <div class="mb-3" data-grupo>
            <?php // Igual que en el índice: pliega toda la línea, no solo la flecha. ?>
            <div class="d-flex align-items-center gap-2 border-bottom pb-1 mb-2 user-select-none"
                style="cursor: pointer" data-plegar="<?= $idGrupo ?>">
                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-body"
                    aria-controls="<?= $idGrupo ?>" aria-expanded="true">
                    <i class="bi bi-chevron-down" data-chevron></i>
                </button>
                <span class="fw-semibold text-uppercase small <?= $categoria ? '' : 'text-muted fst-italic' ?>">
                    <?= $categoria ? esc($categoria['nombre']) : 'Sin clasificar' ?>
                </span>
                <span class="badge border text-body-secondary"><?= count($grupo['piezas']) ?></span>
            </div>

            <div id="<?= $idGrupo ?>">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
                    <?php foreach ($grupo['piezas'] as $p): ?>
                        <?php
                            $variante = $p['variante'];
                            $version  = $p['version'];
                            $esValidada = $version['estado'] === 'validada';
                            $enCarrito = in_array((int) $version['id'], $carrito, true);
                            $stls      = (int) ($p['stls'] ?? 0);
                            $tieneStl  = $stls > 0;
                        ?>
                        <div class="col">
                            <div class="card shadow-sm h-100">
                                <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>">
                                    <?php if ($p['miniatura']): ?>
                                        <img src="<?= $p['miniatura'] ?>" class="card-img-top" style="aspect-ratio: 1; object-fit: cover;"
                                            alt="<?= esc($p['familiaNombre']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center bg-body-secondary text-muted"
                                            style="aspect-ratio: 1;">
                                            <i class="bi bi-box" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="card-body p-2">
                                    <div class="small fw-semibold text-truncate">
                                        <a href="<?= site_url('piezas/variante/' . (int) $variante['id']) ?>"
                                            class="text-decoration-none text-body"><?= esc($p['familiaNombre']) ?></a>
                                    </div>
                                    <?php if ($p['variosVariantes']): ?>
                                        <div class="text-muted small text-truncate"><?= esc($variante['nombre']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted small d-flex align-items-center flex-wrap gap-1">
                                        <?php if ($esValidada): ?>
                                            <span class="badge text-bg-success">
                                                <i class="bi bi-check-circle-fill"></i> v<?= sprintf('%03d', (int) $version['numero']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span>v<?= sprintf('%03d', (int) $version['numero']) ?></span>
                                            <?= $badgeEstadoVersion($version) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($variante['sku'])): ?>
                                            <span><?= esc($variante['sku']) ?></span>
                                        <?php endif; ?>
                                        <?php // Se imprime en trozos: saberlo aquí evita mandar a la placa media pieza creyendo que va entera. ?>
                                        <?php if ($stls > 1): ?>
                                            <span title="Se imprime en <?= $stls ?> trozos"><i class="bi bi-boxes"></i> <?= $stls ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$tieneStl): ?>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-exclamation-circle"></i> sin STL — adjúntalo desde la ficha
                                        </div>
                                    <?php elseif ($enCarrito): ?>
                                        <form method="post" action="<?= site_url('piezas/carrito/quitar/' . (int) $version['id']) ?>" class="mt-1">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-success w-100 py-0">
                                                <i class="bi bi-check-lg"></i> En la placa
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= site_url('piezas/carrito/agregar/' . (int) $version['id']) ?>" class="mt-1">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-primary w-100 py-0">
                                                <i class="bi bi-plus-lg"></i> Añadir a la placa
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
(function () {
    // Plegar categorías, igual que en el índice pero con su propia clave de
    // localStorage: son dos pantallas distintas y no tiene por qué coincidir
    // qué categorías tienes plegadas en cada una.
    var CERRADAS = 'piezas_galeria_categorias_cerradas';

    function cerradas() {
        try { return JSON.parse(localStorage.getItem(CERRADAS)) || []; } catch (e) { return []; }
    }

    function pintar(id, abierta) {
        var cuerpo = document.getElementById(id);
        if (!cuerpo) return;
        cuerpo.classList.toggle('d-none', !abierta);

        var cabecera = document.querySelector('[data-plegar="' + id + '"]');
        if (!cabecera) return;

        var chevron = cabecera.querySelector('[data-chevron]');
        if (chevron) {
            chevron.classList.toggle('bi-chevron-down', abierta);
            chevron.classList.toggle('bi-chevron-right', !abierta);
        }
        var boton = cabecera.querySelector('[aria-controls]');
        if (boton) boton.setAttribute('aria-expanded', abierta ? 'true' : 'false');
    }

    cerradas().forEach(function (id) { pintar(id, false); });

    document.querySelectorAll('[data-plegar]').forEach(function (cabecera) {
        cabecera.addEventListener('click', function (e) {
            if (e.target.closest('form, a')) return;

            var id = cabecera.getAttribute('data-plegar');
            var lista = cerradas();
            var estabaCerrada = lista.indexOf(id) !== -1;

            lista = estabaCerrada ? lista.filter(function (x) { return x !== id; }) : lista.concat([id]);
            localStorage.setItem(CERRADAS, JSON.stringify(lista));
            pintar(id, estabaCerrada);
        });
    });
})();
</script>

<?= $this->endSection() ?>
