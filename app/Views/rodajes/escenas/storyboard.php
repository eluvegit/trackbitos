<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .storyboard-group {
        margin-bottom: 2rem;
    }

    .storyboard-group h2 {
        font-size: 16px;
        margin-bottom: .5rem;
        border-bottom: 2px solid #ccc;
        padding-bottom: .25rem;
    }

    .storyboard-scenes {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }

    .storyboard-scene {
        width: 220px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px;
        background: #fff;
        font-size: 12px;
    }

    .scene-images {
    display: flex;
    justify-content: flex-start; /* que se alineen a la izquierda */
    align-items: flex-start;
    gap: 6px;
    margin-bottom: 6px;
    flex-wrap: wrap; /* por si una imagen es demasiado ancha */
}

.scene-images img {
    height: auto;
    max-height: 220px;     /* límite de altura */
    width: auto;           /* sin ancho fijo */
    max-width: 100%;       /* no sobrepasar el contenedor */
    object-fit: contain;   /* que no recorte nada */
    border-radius: 4px;
    border: 1px solid rgba(0, 0, 0, .12);
}


    .scene-info strong {
        display: block;
        font-size: 13px;
        margin-bottom: 2px;
    }

    .scene-info .small {
        font-size: 11px;
        color: #666;
    }

    /* Pantalla */
    .scene-images {
        display: flex;
        gap: 6px;
        margin-bottom: 6px
    }

    .scene-images-2 img {
        flex: 1 1 50%
    }

    .scene-images-1 img {
        flex: 1 1 100%
    }

    .scene-images img {
        width: 100%;
        height: auto;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid rgba(0, 0, 0, .12);
        max-height: 220px;
        /* opcional para que no se hagan muy altas en pantalla */
    }

    /* Impresión */
    @media print {
        .scene-images {
            gap: 3mm;
            margin-bottom: 3mm
        }

        .scene-images img {
            max-height: 45mm;
            /* ajusta a tu gusto para PDF */
            border: 1px solid #000;
            border-radius: 0;
        }
    }
</style>

<div class="container py-3">
    <h1 class="mb-4">Storyboard — <?= esc($proyecto['titulo']) ?></h1>
    <div class="d-flex gap-2 mb-3">
            <a class="btn btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver</a>
        </div>

    <?php foreach ($groups as $g): ?>
        <div class="storyboard-group">
            <h2><?= esc($g['_title']) ?></h2>
            <div class="storyboard-scenes">
                <?php foreach ($g['items'] as $item): $esc = $item['escena']; ?>
                    <div class="storyboard-scene">
                        <?php
                        $hasLugar = !empty($item['cover_lugar']);
                        $hasInsp  = !empty($item['cover_insp']);
                        $count    = ($hasLugar ? 1 : 0) + ($hasInsp ? 1 : 0);
                        ?>
                        <div class="scene-images scene-images-<?= $count ?>">
                            <?php if ($hasLugar): ?>
                                <img src="<?= base_url($item['cover_lugar']) ?>" alt="Lugar/Objetos">
                            <?php endif; ?>
                            <?php if ($hasInsp): ?>
                                <img src="<?= base_url($item['cover_insp']) ?>" alt="Inspiración">
                            <?php endif; ?>
                        </div>

                        <div class="scene-info">
                            <strong><?= esc($esc['escena_bloque']) ?> · Orden <?= esc($esc['orden']) ?></strong>
                            <?php if (!empty($esc['escena_tomas'])): ?>
                                <div class="small">Toma/s: <?= esc($esc['escena_tomas']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($esc['escena_accion'])): ?>
                                <div class="small">Acción: <?= esc($esc['escena_accion']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($esc['escena_ubicacion'])): ?>
                                <div class="small">📍 <?= esc($esc['escena_ubicacion']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>