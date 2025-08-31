<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="  mb-3">
    <h3 class="mb-0">YouTube</h3>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-sm btn-outline-secondary mt-3">← Volver</a>
    <a class="btn btn-sm btn-primary mt-3" href="<?= site_url('youtube/crear') ?>">Nueva lista</a>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mt-3">
    <?php foreach ($listas as $l):
        $url    = site_url('youtube/' . $l['slug']);
        $title  = esc($l['nombre']);
        $total  = $l['total'] ?? null;
        $vistos = $l['vistos'] ?? null;
        $rel    = $l['relevantes'] ?? null;
        $pct    = (isset($total, $vistos) && $total) ? max(0, min(100, round($vistos * 100 / $total))) : null;

        // color del anillo según progreso
        $ringClass = 'trk-low';
        if ($pct !== null) {
            if ($pct >= 70)      $ringClass = 'trk-good';
            elseif ($pct >= 40)  $ringClass = 'trk-mid';
        }
    ?>
        <div class="col">
            <div class="trk-card position-relative">
                <!-- Anillo progreso -->
                <?php
                $pct = (isset($total, $vistos) && $total) ? round($vistos * 100 / $total) : null;
                ?>
                <div class="trk-ring <?= $ringClass ?>" style="--p: <?= $pct !== null ? $pct . '%' : '0%' ?>;">
                    <div class="trk-ring__label"><?= $pct !== null ? $pct . '%' : '—' ?></div>
                </div>



                <!-- Contenido -->
                <div class="trk-body">
                    <div class="trk-title">
                        <?= $title ?>
                        <a href="<?= $url ?>" class="stretched-link" aria-label="Abrir <?= $title ?>"></a>
                    </div>

                    <?php if ($total !== null): ?>
                        <div class="trk-sub">
                            <span><?= (int)$vistos ?>/<?= (int)$total ?> vistos</span>
                            <?php if ($rel !== null): ?>
                                <span>· ★ <?= (int)$rel ?> relevantes</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Chevron -->
                <div class="trk-chevron" aria-hidden="true">›</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    /* ====== Tarjeta limpia, amigable ====== */
    .trk-card {
        --trk-border: rgba(0, 0, 0, .08);
        --trk-bg: var(--bs-body-bg);
        display: grid;
        grid-template-columns: 56px 1fr 18px;
        gap: .9rem;
        align-items: center;
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid var(--trk-border);
        background: var(--trk-bg);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        transition: transform .15s ease, box-shadow .2s ease, border-color .15s ease;
    }

    .trk-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .10);
        border-color: rgba(99, 102, 241, .28);
    }

    .trk-body {
        min-width: 0;
    }

    .trk-title {
        font-weight: 600;
        font-size: 1rem;
        line-height: 1.15;
        color: var(--bs-emphasis-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .trk-sub {
        margin-top: .25rem;
        color: var(--bs-secondary-color);
        font-size: .9rem;
    }

    /* Chevron */
    .trk-chevron {
        font-size: 26px;
        color: rgba(0, 0, 0, .35);
        transition: transform .15s ease, color .15s ease;
    }

    .trk-card:hover .trk-chevron {
        transform: translateX(2px);
        color: rgba(0, 0, 0, .55);
    }

    /* Anillo de progreso (conic-gradient) */
    .trk-ring {
        --trk-accent: #6366f1;
        --trk-track: rgba(0, 0, 0, .08);
        position: relative;
        width: 48px;
        height: 48px;
    }

    .trk-ring::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: conic-gradient(var(--trk-accent) var(--p), var(--trk-track) 0);
    }

    .trk-ring::after {
        content: "";
        position: absolute;
        inset: 6px;
        border-radius: 50%;
        background: var(--trk-bg);
        box-shadow: inset 0 0 0 1px var(--trk-border);
    }

    .trk-ring__label {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        font-size: .75rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
    }

    /* Estados de color segun progreso */
    .trk-low {
        --trk-accent: #9ca3af;
    }

    /* gris */
    .trk-mid {
        --trk-accent: #f59e0b;
    }

    /* ámbar */
    .trk-good {
        --trk-accent: #10b981;
    }

    /* verde */

    /* Modo oscuro-friendly */
    .text-bg-dark .trk-card,
    .bg-dark .trk-card {
        --trk-border: rgba(255, 255, 255, .08);
        --trk-bg: rgba(255, 255, 255, .02);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04);
    }

    .text-bg-dark .trk-chevron {
        color: rgba(255, 255, 255, .5);
    }

    .text-bg-dark .trk-card:hover .trk-chevron {
        color: rgba(255, 255, 255, .75);
    }

    .text-bg-dark .trk-ring {
        --trk-track: rgba(255, 255, 255, .12);
    }
</style>


<style>
    /* ====== Trackbitos vibes ====== */
    .track-card {
        position: relative;
        display: grid;
        grid-template-columns: 44px 1fr 18px;
        gap: .9rem;
        align-items: center;
        padding: .9rem 1rem;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, .06);
        background:
            radial-gradient(1200px 200px at -10% -20%, rgba(99, 102, 241, .08), transparent 60%),
            var(--bs-body-bg);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        transition: transform .15s ease, box-shadow .2s ease, border-color .15s ease;
    }

    .track-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(0, 0, 0, .10);
        border-color: rgba(99, 102, 241, .25);
    }

    .track-card:active {
        transform: translateY(0);
    }

    .track-card-accent {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 16px;
        pointer-events: none;
        background: linear-gradient(90deg, rgba(99, 102, 241, .12), rgba(99, 102, 241, 0));
        opacity: .0;
        transition: opacity .2s ease;
    }

    .track-card:hover .track-card-accent {
        opacity: .6;
    }

    .track-card-icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: rgba(99, 102, 241, .12);
        color: rgb(79, 70, 229);
    }

    .track-card-body {
        min-width: 0;
    }

    .track-card-title {
        font-weight: 600;
        line-height: 1.1;
        font-size: 1rem;
        color: var(--bs-emphasis-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .track-card-meta .badge {
        font-weight: 600;
    }

    .track-card-chevron {
        font-size: 26px;
        line-height: 1;
        color: rgba(0, 0, 0, .35);
        transition: transform .15s ease, color .15s ease;
    }

    .track-card:hover .track-card-chevron {
        transform: translateX(2px);
        color: rgba(0, 0, 0, .55);
    }

    /* Dark mode friendly (si usas .bg-dark/.text-bg-dark) */
    .text-bg-dark .track-card,
    .bg-dark .track-card {
        border-color: rgba(255, 255, 255, .08);
        background:
            radial-gradient(1200px 200px at -10% -20%, rgba(99, 102, 241, .18), transparent 60%),
            rgba(255, 255, 255, .02);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .04);
    }

    .text-bg-dark .track-card:hover,
    .bg-dark .track-card:hover {
        border-color: rgba(99, 102, 241, .45);
        box-shadow: 0 10px 28px rgba(0, 0, 0, .35);
    }

    .text-bg-dark .track-card-icon {
        background: rgba(99, 102, 241, .2);
        color: rgba(167, 139, 250, 1);
    }

    .text-bg-dark .track-card-chevron {
        color: rgba(255, 255, 255, .5);
    }

    .text-bg-dark .track-card:hover .track-card-chevron {
        color: rgba(255, 255, 255, .75);
    }
</style>

<?= $this->endSection() ?>