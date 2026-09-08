<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-clipboard-x text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Datos que faltan</strong>
</h5>

<p class="text-muted small">
    Piezas a las que les falta algo en la clasificación. Se mira lo que hay asignado
    (categoría + temática/lugar/personas), que es justo lo que se corrige en
    «Reclasificar» — el nombre de carpeta no se toca. Las piezas completas no salen aquí.
    Las dos últimas pestañas repasan la etiqueta de contenido «(Fotos + Vídeos + Montajes)»
    que se escribe al final de la temática.
</p>

<?php
$pestanas = [
    'sin_tematica'           => ['Sin temática', 'bi-collection'],
    'sin_lugar'              => ['Sin lugar', 'bi-geo-alt'],
    'sin_personas'           => ['Sin personas', 'bi-people'],
    'mal'                    => ['Mal en general', 'bi-exclamation-triangle'],
    'sin_etiqueta_contenido' => ['Sin etiqueta (Fotos/Vídeos)', 'bi-tag'],
    'contenido_descuadra'    => ['Contenido no cuadra', 'bi-exclamation-diamond'],
];

// Primera pestaña con contenido (si están todas vacías, la primera).
$activa = array_key_first($pestanas);
foreach ($pestanas as $clave => $_) {
    if (!empty($grupos[$clave])) { $activa = $clave; break; }
}

$pintarLista = static function (array $piezas, bool $conMotivos = false): string {
    if ($piezas === []) {
        return '<p class="text-success-emphasis mb-0"><i class="bi bi-check2-circle me-1"></i>Nada pendiente aquí.</p>';
    }

    $html = '<div class="list-group">';
    foreach ($piezas as $p) {
        $html .= '<a href="' . site_url('silo/' . $p['id'] . '/editar') . '" '
            . 'class="list-group-item list-group-item-action d-flex align-items-center gap-2">'
            . '<i class="bi bi-folder2 text-warning fs-5"></i>'
            . '<span class="flex-grow-1 d-flex flex-wrap align-items-center gap-1" '
            . 'title="' . esc($p['nombre_carpeta'], 'attr') . '">'
            . silo_badges_carpeta($p) . '</span>';

        if ($conMotivos && !empty($p['motivos'])) {
            foreach ($p['motivos'] as $motivo) {
                $html .= '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle fw-normal">'
                    . esc($motivo) . '</span>';
            }
        }

        $html .= '<span class="text-muted small text-nowrap">#' . esc($p['id_negocio']) . '</span>'
            . '<i class="bi bi-pencil-square text-muted"></i>'
            . '</a>';
    }

    return $html . '</div>';
};

/**
 * Listado de las dos pestañas de contenido, con un subfiltro de "pills" por
 * combinación (Todas · Fotos · Vídeos · Fotos + Vídeos…). `$modo` decide qué
 * enseña cada fila:
 *   - 'falta_etiqueta': `combo` = lo que la carpeta contiene de verdad
 *     (para poder añadir el paréntesis correcto de golpe).
 *   - 'descuadra':      `combo` = lo que declara y NO está en disco.
 */
$pintarContenido = static function (array $piezas, string $modo): string {
    if ($piezas === []) {
        return '<p class="text-success-emphasis mb-0"><i class="bi bi-check2-circle me-1"></i>Nada pendiente aquí.</p>';
    }

    // Recuento por combinación, ordenado: menos "+" primero, alfabético,
    // "sin detectar" ('') al final.
    $combos = [];
    foreach ($piezas as $p) {
        $k          = $p['combo'] ?? '';
        $combos[$k] = ($combos[$k] ?? 0) + 1;
    }
    uksort($combos, static function ($a, $b) {
        if ($a === '') { return 1; }
        if ($b === '') { return -1; }
        return [substr_count($a, '+'), $a] <=> [substr_count($b, '+'), $b];
    });

    $ayuda = $modo === 'descuadra'
        ? 'Filtrar por lo que declara y falta en disco'
        : 'Filtrar por lo que contiene la carpeta';

    $html = '<div data-df-filtro>'
        . '<div class="small text-muted mb-1">' . esc($ayuda) . '</div>'
        . '<div class="d-flex flex-wrap gap-1 mb-3">'
        . '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill active" data-combo="__all__">'
        . 'Todas <span class="badge rounded-pill text-bg-secondary ms-1">' . count($piezas) . '</span></button>';

    foreach ($combos as $combo => $n) {
        $html .= '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" '
            . 'data-combo="' . esc($combo, 'attr') . '">'
            . esc(silo_contenido_combo_label($combo))
            . ' <span class="badge rounded-pill text-bg-light border ms-1">' . $n . '</span></button>';
    }
    $html .= '</div><div class="list-group">';

    foreach ($piezas as $p) {
        $combo = $p['combo'] ?? '';
        $html .= '<a href="' . site_url('silo/' . $p['id'] . '/editar') . '" '
            . 'class="list-group-item list-group-item-action d-flex align-items-center gap-2 df-fila" '
            . 'data-combo="' . esc($combo, 'attr') . '">'
            . '<i class="bi bi-folder2 text-warning fs-5"></i>'
            . '<span class="flex-grow-1 d-flex flex-wrap align-items-center gap-1" '
            . 'title="' . esc($p['nombre_carpeta'], 'attr') . '">'
            . silo_badges_carpeta($p) . '</span>';

        if ($modo === 'descuadra') {
            $html .= '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle fw-normal text-nowrap">falta</span>'
                . silo_badges_contenido_claves($p['contenido_falta'] ?? []);
        } elseif (!empty($p['contenido_real'])) {
            $html .= silo_badges_contenido_claves($p['contenido_real']);
        } else {
            $html .= '<span class="badge bg-body-secondary text-muted border fw-normal text-nowrap">sin fotos ni vídeos</span>';
        }

        $html .= '<span class="text-muted small text-nowrap">#' . esc($p['id_negocio']) . '</span>'
            . '<i class="bi bi-pencil-square text-muted"></i>'
            . '</a>';
    }

    return $html . '</div></div>';
};
?>

<ul class="nav nav-tabs mb-3 flex-wrap" role="tablist">
    <?php foreach ($pestanas as $clave => [$titulo, $icono]): ?>
        <?php $n = count($grupos[$clave] ?? []); ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $clave === $activa ? 'active' : '' ?>"
                    id="tab-<?= $clave ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= $clave ?>"
                    type="button" role="tab">
                <i class="bi <?= $icono ?> me-1"></i><?= esc($titulo) ?>
                <span class="badge rounded-pill <?= $n > 0 ? 'text-bg-secondary' : 'text-bg-light border' ?> ms-1"><?= $n ?></span>
            </button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
    <?php foreach ($pestanas as $clave => $_): ?>
        <div class="tab-pane fade <?= $clave === $activa ? 'show active' : '' ?>"
             id="pane-<?= $clave ?>" role="tabpanel" aria-labelledby="tab-<?= $clave ?>">
            <?php if ($clave === 'sin_etiqueta_contenido'): ?>
                <?= $pintarContenido($grupos[$clave] ?? [], 'falta_etiqueta') ?>
            <?php elseif ($clave === 'contenido_descuadra'): ?>
                <?= $pintarContenido($grupos[$clave] ?? [], 'descuadra') ?>
            <?php else: ?>
                <?= $pintarLista($grupos[$clave] ?? [], $clave === 'mal') ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('[data-df-filtro]').forEach(function (box) {
    var pills = box.querySelectorAll('button[data-combo]');
    var filas = box.querySelectorAll('.df-fila');
    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            pills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            var target = pill.getAttribute('data-combo');
            filas.forEach(function (fila) {
                var show = target === '__all__' || fila.getAttribute('data-combo') === target;
                fila.classList.toggle('d-none', !show);
            });
        });
    });
});
</script>

<?= $this->endSection() ?>
