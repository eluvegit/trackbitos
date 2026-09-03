<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
/** KB para lo pequeño, MB con un decimal en cuanto pasa de 1 MB — mismo criterio que la ficha de variante. */
$tamanoLegible = function (int $bytes): string {
    return $bytes >= 1024 * 1024
        ? number_format($bytes / (1024 * 1024), 1) . ' MB'
        : number_format($bytes / 1024, 0) . ' KB';
};
$maximo = $piezas === [] ? 0 : max(array_column($piezas, 'bytes'));

/**
 * A dónde lleva el nombre de una pieza: a su ficha si sólo tiene una
 * variante viva (como en el índice), o al índice anclado en su fila si
 * tiene varias. Sin variantes vivas, al índice a secas.
 */
$enlaceFamilia = function (array $familia): string {
    $variantes = $familia['variantes'] ?? [];
    if (count($variantes) === 1) {
        return site_url('piezas/variante/' . (int) $variantes[0]['id']);
    }
    if (count($variantes) > 1) {
        return site_url('piezas') . '#familia-' . (int) $familia['id'];
    }

    return site_url('piezas');
};

/**
 * Cabecera de un ranking: en pantalla ancha es solo el título (las dos
 * columnas caben a la vez); en estrecha es el botón que pliega/despliega
 * su bloque, que arranca plegado. El truco es `collapse d-lg-block` en el
 * cuerpo: en `lg+` se ve siempre pase lo que pase con el collapse.
 */
$cabeceraRanking = function (string $titulo, string $id): string {
    return '<h6 class="mb-2">'
        . '<button type="button"'
        . ' class="btn btn-link p-0 text-reset text-decoration-none fw-semibold d-flex align-items-center gap-2 w-100 estad-toggle collapsed"'
        . ' data-bs-toggle="collapse" data-bs-target="#' . $id . '"'
        . ' aria-expanded="false" aria-controls="' . $id . '">'
        . esc($titulo)
        . '<i class="bi bi-chevron-down ms-auto small d-lg-none estad-chevron"></i>'
        . '</button>'
        . '</h6>';
};
?>

<style>
    /* El chevron gira al desplegar; en pantalla ancha ni se ve (d-lg-none)
       porque los dos rankings están siempre abiertos. */
    .estad-toggle .estad-chevron {
        transition: transform .2s ease;
    }
    .estad-toggle:not(.collapsed) .estad-chevron {
        transform: rotate(180deg);
    }
</style>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-hdd-stack text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Estadísticas</strong>

    <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="btn btn-sm btn-outline-secondary ms-auto"
        title="Fotografía de ahora mismo: el .blend de referencia de cada pieza (validada, o si no hay la más reciente) más su historial en texto. Sin STL ni fotos — se pueden rehacer.">
        <i class="bi bi-download"></i> Copia de seguridad
    </a>
</h5>

<p class="text-muted small">
    Lo que ocupa <code>writable/piezas</code> de verdad en disco — ficheros, no filas de la base
    de datos. Incluye la papelera de ficheros (invariante 6): lo que ya se apartó pero todavía no
    se ha purgado.
</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <div class="text-muted small">Almacén completo</div>
                <div class="fs-4 fw-semibold"><?= $tamanoLegible($total) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <div class="text-muted small">En la papelera</div>
                <div class="fs-4 fw-semibold <?= $totalPapelera > 0 ? 'text-warning' : '' ?>">
                    <?= $tamanoLegible($totalPapelera) ?>
                </div>
                <?php if ($totalPapelera > 0): ?>
                    <div class="small text-muted mt-1">
                        Se liberaría ejecutando <code>php spark piezas:purgar</code> en el servidor
                        (no hay botón en la web a propósito: es una acción de mantenimiento, no del
                        día a día).
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-3">
    <div class="col-lg-6">
        <?= $cabeceraRanking('Top impresas', 'rankingImpresas') ?>
        <div class="collapse d-lg-block" id="rankingImpresas">
            <p class="text-muted small">
                Ranking por copias físicas: la <strong>cantidad</strong> de cada versión que ha ido en
                una placa ya marcada como impresa, sumando todas las placas impresas (las de reparto
                incluidas, sin contar dos veces la misma copia). No mira el estado de la versión —cuenta
                lo que se mandó a la impresora, no lo que se juzgó después.
            </p>

            <?php if (empty($topImpresas)): ?>
                <p class="text-muted">Todavía no hay ninguna placa marcada como impresa.</p>
            <?php else: ?>
                <?php $maxUnidades = max(array_column($topImpresas, 'unidades')); ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($topImpresas as $indice => $fila): ?>
                        <?php $porcentaje = $maxUnidades > 0 ? (int) round($fila['unidades'] / $maxUnidades * 100) : 0; ?>
                        <a href="<?= esc($enlaceFamilia($fila['familia']), 'attr') ?>" class="list-group-item px-0 py-2 text-decoration-none text-body">
                            <div class="d-flex justify-content-between align-items-baseline gap-2">
                                <span class="text-truncate">
                                    <span class="text-muted small me-1">#<?= $indice + 1 ?></span>
                                    <strong><?= esc($fila['familia']['nombre']) ?></strong>
                                </span>
                                <span class="flex-shrink-0">
                                    <strong><?= $fila['unidades'] ?></strong>
                                    <span class="text-muted small"><?= $fila['unidades'] === 1 ? 'unidad' : 'unidades' ?></span>
                                </span>
                            </div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?= $porcentaje ?>%;"></div>
                            </div>
                            <div class="small text-muted mt-1">
                                en <?= $fila['placas'] ?> <?= $fila['placas'] === 1 ? 'placa' : 'placas' ?>
                                <?php if (!empty($fila['ultima'])): ?>
                                    · última el <?= esc(date('d/m/Y', strtotime((string) $fila['ultima']))) ?>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <?= $cabeceraRanking('Piezas que más pesan', 'rankingPeso') ?>
        <div class="collapse d-lg-block" id="rankingPeso">
            <p class="text-muted small">
                Suma de sus versiones (.blend + .stl) y sus sesiones, apartadas o no. Si una pieza destaca
                mucho sobre las demás, esa es la candidata a aligerar — desde su ficha, con "liberar sitio"
                en la sesión que sobre, o revisando si el .blend necesita decimarse.
            </p>

            <?php if (empty($piezas)): ?>
                <p class="text-muted">Todavía no hay ningún fichero que pese algo.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($piezas as $fila): ?>
                        <?php $porcentaje = $maximo > 0 ? (int) round($fila['bytes'] / $maximo * 100) : 0; ?>
                        <a href="<?= esc($enlaceFamilia($fila['familia']), 'attr') ?>" class="list-group-item px-0 py-2 text-decoration-none text-body">
                            <div class="d-flex justify-content-between align-items-baseline gap-2">
                                <strong class="text-truncate"><?= esc($fila['familia']['nombre']) ?></strong>
                                <span class="text-muted small flex-shrink-0"><?= $tamanoLegible($fila['bytes']) ?></span>
                            </div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar" style="width: <?= $porcentaje ?>%;"></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
