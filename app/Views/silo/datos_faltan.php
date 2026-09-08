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
</p>

<?php
$pestanas = [
    'sin_tematica' => ['Sin temática', 'bi-collection'],
    'sin_lugar'    => ['Sin lugar', 'bi-geo-alt'],
    'sin_personas' => ['Sin personas', 'bi-people'],
    'mal'          => ['Mal en general', 'bi-exclamation-triangle'],
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
?>

<ul class="nav nav-tabs mb-3" role="tablist">
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
            <?= $pintarLista($grupos[$clave] ?? [], $clave === 'mal') ?>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
