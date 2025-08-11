<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">🏋️ Gimnasio</h2>

<div class="row row-cols-1 row-cols-md-3 g-4">

    <div class="col d-flex">
    <a href="<?= site_url('gimnasio/ejercicios') ?>" class="text-decoration-none text-dark w-100 h-100">
        <div class="card shadow-sm w-100 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title">📋 Ejercicios</h5>
                    <p class="card-text text-muted">Gestiona y clasifica todos los ejercicios disponibles.</p>
                </div>
                <div class="mt-3 text-end">
                    <span class="btn btn-sm btn-outline-primary">Ver ejercicios</span>
                </div>
            </div>
        </div>
    </a>
</div>

<div class="col d-flex">
    <a href="<?= site_url('gimnasio/entrenamientos') ?>" class="text-decoration-none text-dark w-100 h-100">
        <div class="card shadow-sm w-100 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title">📆 Entrenamientos</h5>
                    <p class="card-text text-muted">Registra y consulta tus entrenamientos diarios.</p>
                </div>
                <div class="mt-3 text-end">
                    <span class="btn btn-sm btn-outline-primary">Ir a entrenamientos</span>
                </div>
            </div>
        </div>
    </a>
</div>


    <div class="col d-flex">
        <a href="<?= site_url('gimnasio/ejercicios/principales') ?>" class="text-decoration-none text-dark w-100 h-100">
            <div class="card shadow-sm w-100 h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title">📊 Estadísticas</h5>
                        <p class="card-text text-muted">Consulta tu progreso de los 3 ejercicios principales.</p>
                    </div>
                    <div class="mt-3 text-end">
                        <span class="btn btn-sm btn-outline-primary">Ir a estadísticas</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col d-flex">
        <div class="card shadow-sm w-100 h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <h5 class="card-title">📁 Plantillas</h5>
                    <p class="card-text text-muted">Guarda tus rutinas frecuentes. (Próximamente)</p>
                </div>
                <div class="mt-3 text-end">
                    <span class="btn btn-sm btn-secondary disabled">En desarrollo</span>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>