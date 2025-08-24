<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container my-3 text-center">

    <h4>Selecciona el tipo de comida</h4>
    <p class="text-muted">Día: <?= $fechaSel->format('d/m/Y') ?></p>

    <div class="row g-1">
        <?php foreach ($tipos as $t): ?>
            <div class="col-6 col-md-3">
                <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $t ) ?>"
                   class="btn btn-lg btn-outline-primary w-100 p-1">
                    <?= ucfirst(str_replace('_', ' ', $t)) ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d')) ?>" 
           class="btn btn-outline-secondary">⬅ Volver al resumen</a>
    </div>
</div>

<?= $this->endSection() ?>
