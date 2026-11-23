<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .export-group {
        margin-bottom: 2rem;
    }

    .export-group h2 {
        font-size: 16px;
        margin-bottom: .75rem;
        border-bottom: 2px solid var(--bs-border-color);
        padding-bottom: .25rem;
    }

    .export-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }

    .export-item {
        aspect-ratio: 1 / 1;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }

    .export-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .export-item .export-enlace {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        text-align: center;
        padding: 8px;
        font-size: 12px;
        color: var(--bs-body-color);
    }

    .export-briefing {
        white-space: pre-wrap;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 2rem;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .export-item img {
            border: 1px solid #000;
            border-radius: 0;
        }
    }
</style>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a class="btn btn-outline-secondary" href="<?= site_url('sesiones/' . $sesion['id']) ?>">← Volver a la sesión</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir / guardar como PDF</button>
    </div>

    <h1 class="mb-4"><?= esc($sesion['titulo']) ?></h1>

    <?php
    $ubicaciones ??= [];
    $modelos ??= [];
    $horaS = !empty($sesion['hora_sesion']) ? substr($sesion['hora_sesion'], 0, 5) : '';
    ?>
    <?php if (!empty($sesion['fecha_sesion']) || $horaS): ?>
        <p class="fs-5 mb-3">
            <i class="bi bi-calendar3"></i> <?= !empty($sesion['fecha_sesion']) ? esc(date('d/m/Y', strtotime($sesion['fecha_sesion']))) : '' ?>
            <?php if ($horaS): ?> · <i class="bi bi-clock"></i> <?= esc($horaS) ?><?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($sesion['briefing'])): ?>
        <div class="export-briefing"><?= $sesion['briefing'] ?></div>
    <?php endif; ?>

    <?php if ($ubicaciones): ?>
        <div class="export-group">
            <h2>Hora y ubicaciones</h2>
            <table class="table table-sm align-middle">
                <?php foreach ($ubicaciones as $u): ?>
                    <tr>
                        <td style="width: 70px" class="fw-semibold"><?= $u['hora'] ? esc(substr($u['hora'], 0, 5)) : '—' ?></td>
                        <td><?= esc($u['nombre']) ?><?php if (trim((string) $u['notas']) !== ''): ?><br><small class="text-muted"><?= esc($u['notas']) ?></small><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

    <?php foreach ($modelos as $m): ?>
        <?php $elegidas = array_values(array_filter($m['ropa'], static fn ($r) => (int) $r['elegida'] === 1)); ?>
        <div class="export-group">
            <h2><?= esc($m['nombre']) ?> — ropa</h2>
            <?php if (!$elegidas): ?>
                <p class="text-muted small">Sin ropa elegida para esta sesión.</p>
            <?php else: ?>
                <div class="export-grid">
                    <?php foreach ($elegidas as $r): ?>
                        <div>
                            <div class="export-item">
                                <?php if ($r['imagen']): ?><img src="<?= base_url($r['imagen']) ?>" alt=""><?php else: ?><div class="export-enlace"><?= esc($r['texto']) ?></div><?php endif; ?>
                            </div>
                            <?php if ($r['imagen']): ?><small><?= esc($r['texto']) ?></small><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($grupos)): ?>
        <p class="text-muted">No hay elementos de moodboard para exportar.</p>
    <?php endif; ?>

    <?php foreach ($grupos as $g): ?>
        <div class="export-group">
            <h2><?= esc($g['nombre']) ?></h2>
            <?php if (empty($g['items'])): ?>
                <p class="text-muted small">Sin elementos.</p>
            <?php else: ?>
                <div class="export-grid">
                    <?php foreach ($g['items'] as $item): ?>
                        <div class="export-item">
                            <?php if ($item['origen'] === 'archivo'): ?>
                                <img src="<?= base_url($item['ruta_archivo']) ?>" alt="Referencia moodboard">
                            <?php elseif ($item['origen'] === 'texto'): ?>
                                <div class="export-enlace"><?= esc($item['nota']) ?></div>
                            <?php else: ?>
                                <div class="export-enlace">
                                    <a href="<?= esc($item['url_externa'], 'attr') ?>" target="_blank" rel="noopener"><?= esc($item['url_externa']) ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
