<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0"><?= esc($title) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('comidas/diario/hoy') ?>">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<p class="text-muted small">
    Los días históricos más cercanos a tus objetivos (kcal, proteína, carbohidratos, grasas),
    agrupados en 4 semanas para replicar un mes completo. Toca un día para ver qué comiste.
</p>

<?php
$fmt = function ($v, $d = 0) {
    $s = number_format((float) $v, $d, '.', '');
    return strpos($s, '.') !== false ? rtrim(rtrim($s, '0'), '.') : $s;
};
$pos = 0;
?>

<?php if (empty($semanas)): ?>
    <div class="alert alert-light border">No hay suficientes días con registros para generar el ranking.</div>
<?php endif; ?>

<?php foreach ($semanas as $i => $dias): ?>
    <h6 class="text-muted mt-4 mb-2">Semana <?= $i + 1 ?></h6>
    <div class="list-group ranking-list mb-2">
        <?php foreach ($dias as $d): $pos++; ?>
            <a href="<?= site_url('comidas/diario/' . $d['fecha']) ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="badge rounded-pill text-bg-secondary flex-shrink-0"><?= $pos ?></span>
                        <span class="fw-semibold text-truncate">
                            <?= (new \CodeIgniter\I18n\Time($d['fecha']))->toLocalizedString('EEE d MMM y') ?>
                        </span>
                    </div>
                    <span class="text-muted small flex-shrink-0">score <?= $fmt($d['score'], 2) ?></span>
                </div>
                <div class="ranking-valores mt-1">
                    <span class="text-primary">C</span> <?= $fmt($d['totales']['carbohidratos_g']) ?>g
                    <span class="text-danger ms-2">P</span> <?= $fmt($d['totales']['proteina_g']) ?>g
                    <span class="text-success ms-2">G</span> <?= $fmt($d['totales']['grasas_g']) ?>g
                    <span class="text-warning ms-2">KC</span> <?= $fmt($d['totales']['kcal']) ?>
                    <span class="text-muted ms-2">Az</span> <?= $fmt($d['totales']['azucares_g'] ?? 0) ?>g
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<style>
    .ranking-list .list-group-item {
        padding: 0.4rem 0.6rem;
    }

    .ranking-valores {
        font-size: 0.75rem;
    }
</style>

<?= $this->endSection() ?>
