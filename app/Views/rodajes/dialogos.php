<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    /* Fondo general de la página en oscuro */
    body {
        background-color: #121212;
        color: #e0e0e0;
    }

    .script-container {
        max-width: 900px;
        margin: 0 auto;
        background-color: #1e1e1e;
        /* Gris muy oscuro pero distinguible */
        min-height: 100vh;
        padding: 60px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border-left: 1px solid #333;
        border-right: 1px solid #333;
    }

    .scene-header {
        border-bottom: 1px solid #333;
        margin-top: 3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
    }

    .dialogue-text {
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.875rem;
        line-height: 1;
        color: #d1d1d1;
        /* Blanco suave para evitar reflejos altos */
        padding-left: 1.5rem;
        border-left: 2px solid #444;
        background: rgba(255, 255, 255, 0.02);
        padding: 1rem 0.8rem;
        border-radius: 0 8px 8px 0;
    }

    .meta-info {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: #888;
    }

    .scene-title {
        color: #fff;
        letter-spacing: 0.5px;
    }

    /* Ajustes para los Badges en Dark Mode */
    .badge-dark-mode {
        background-color: #333;
        color: #bbb;
        border: 1px solid #444;
        padding: 0.5em 1em;
    }

    /* Estilo para impresión: Volvemos a blanco y negro automáticamente */
    @media print {
        body {
            background: white !important;
            color: black !important;
        }

        .no-print {
            display: none !important;
        }

        .script-container {
            box-shadow: none;
            padding: 0;
            width: 100%;
            max-width: 100%;
            background: white !important;
            border: none;
        }

        .scene-block {
            break-inside: avoid;
            border-bottom: 1px solid #eee;
            margin-bottom: 2rem;
        }

        .dialogue-text {
            color: black !important;
            border-left: 2px solid #ccc !important;
            background: none !important;
        }

        .scene-title {
            color: black !important;
        }

        .meta-info {
            color: #666 !important;
        }
    }

    /* Estilo para que el enlace no parezca un link azul genérico */
    .hover-link {
        transition: all 0.2s ease;
        display: inline-block;
    }

    .hover-link:hover {
        transform: scale(1.1);
        /* Efecto de aumento sutil */
        filter: brightness(1.2);
        /* Brilla un poco más al pasar el mouse */
    }

    /* Evitar que el enlace se vea en la impresión */
    @media print {
        .hover-link span {
            color: black !important;
            text-decoration: none !important;
        }
    }
</style>

<div class="container-fluid bg-black py-3 no-print border-bottom border-secondary">
    <div class="d-flex justify-content-between align-items-center px-lg-5">
        <div>
            <h1 class="h5 mb-0 text-white"><?= esc($proyecto['titulo']) ?></h1>
            <p class="text-secondary small mb-0">Script de Diálogos • Dark Review</p>
        </div>
        <div class="btn-group">
            <a href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>" class="btn btn-sm btn-outline-light">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-info">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>
</div>

<div class="script-container">
    <div class="text-center mb-5">
        <h1 class="display-6 fw-bold text-white text-uppercase"><?= esc($proyecto['titulo']) ?></h1>
        <span class="badge badge-dark-mode mt-2">REGISTRO DE DIÁLOGOS</span>
        <div class="mt-4 border-top border-secondary w-25 mx-auto"></div>
    </div>

    <?php if (empty($escenas)): ?>
        <div class="text-center py-5 border border-secondary rounded bg-dark">
            <i class="bi bi-chat-left-dots fs-1 text-secondary"></i>
            <p class="mt-3 text-secondary">No se han encontrado diálogos en este proyecto.</p>
        </div>
    <?php else: ?>
        <?php foreach ($escenas as $e): ?>
            <div class="scene-block mb-5">
                <div class="scene-header mb-3 pb-2">
                    <div class="mb-1">
                        <h3 class="h5 fw-bold mb-0 scene-title text-uppercase">
                            <a href="<?= site_url("rodajes/{$proyecto['id']}/escenas/show/{$e['id']}") ?>"
                                class="text-decoration-none me-2 hover-link"
                                title="Ver detalle de escena">
                                <span class="text-primary">#<?= esc($e['orden']) ?></span>
                            </a>
                            <?= esc($e['escena_bloque'] ?: 'Sin Identificar') ?>
                        </h3>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="meta-info small">
                            <i class="bi bi-geo-alt-fill me-1"></i><?= esc($e['escena_ubicacion'] ?: 'Localización no definida') ?>
                        </div>
                    </div>
                </div>

                <div class="dialogue-text">
                    <?= nl2br(esc($e['sonido_dialogo_escrito'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="text-center mt-5 pt-5 border-top border-secondary meta-info">
        MASTER DIALOGUE LIST • PROYECTO ID: <?= $proyecto['id'] ?> • <?= date('Y') ?>
    </div>
</div>

<?= $this->endSection() ?>