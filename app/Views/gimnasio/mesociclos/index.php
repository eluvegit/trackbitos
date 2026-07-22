<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="mp-header mb-3">
    <a href="<?= site_url('gimnasio') ?>" class="mp-back"><i class="bi bi-chevron-left"></i> Gimnasio</a>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-1">
        <h2 class="mp-title mb-0"><i class="bi bi-diagram-3 text-primary"></i> Mesociclos</h2>
        <a class="btn btn-primary btn-sm" href="<?= site_url('gimnasio/mesociclos/nuevo') ?>">
            <i class="bi bi-plus-lg"></i> Nuevo plan
        </a>
    </div>
</div>

<?php if (empty($planes)): ?>
    <p class="text-muted">No hay planes aún. Crea el primero con "Nuevo plan".</p>
<?php else: ?>
    <div class="mp-list">
        <?php foreach ($planes as $p): ?>
            <?php $pct = $p['_pendientes'] === 0 ? 100 : (int) $p['_progreso']; ?>
            <div class="mp-card">
                <div class="mp-card-top">
                    <a href="<?= site_url('gimnasio/mesociclos/' . $p['id']) ?>" class="mp-card-nombre"><?= esc($p['nombre']) ?></a>
                    <span class="mp-card-ejercicio"><?= esc($p['ejercicio']) ?></span>
                </div>

                <div class="mp-card-meta">
                    e1RM <strong><?= number_format((float) $p['e1rm_base'], 1) ?>kg</strong>
                    · <?= (int) $p['_pendientes'] ?> pendiente<?= (int) $p['_pendientes'] === 1 ? '' : 's' ?>
                    · <?= (int) $p['_hechos'] ?> hecho<?= (int) $p['_hechos'] === 1 ? '' : 's' ?>
                    <?php if ($p['_siguiente'] !== null): ?>
                        · Siguiente <strong>#<?= (int) $p['_siguiente'] ?></strong>
                    <?php endif; ?>
                </div>

                <div class="mp-progress">
                    <div class="mp-progress-bar" style="width: <?= $pct ?>%;"></div>
                    <span class="mp-progress-label"><?= $pct ?>%</span>
                </div>

                <div class="mp-card-actions">
                    <a class="mp-pill" href="<?= site_url('gimnasio/mesociclos/' . $p['id']) ?>">
                        <i class="bi bi-eye"></i> Ver
                    </a>
                    <a class="mp-pill" href="<?= site_url('gimnasio/mesociclos/' . $p['id'] . '/simplificado') ?>">
                        <i class="bi bi-list-check"></i> Simplificado
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.mp-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.mp-back:hover { color: var(--bs-emphasis-color); }
.mp-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.mp-list { display: flex; flex-direction: column; gap: 10px; }

.mp-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    background: var(--bs-tertiary-bg);
    padding: 12px 14px;
}

.mp-card-top { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.mp-card-nombre {
    font-size: 1rem;
    font-weight: 700;
    color: var(--bs-emphasis-color);
    text-decoration: none;
}
.mp-card-nombre:hover { text-decoration: underline; }
.mp-card-ejercicio {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #f59e0b;
    background: rgba(245, 158, 11, .12);
    border-radius: 999px;
    padding: .15rem .55rem;
}

.mp-card-meta { font-size: .8rem; color: var(--bs-secondary-color); margin-top: 4px; }
.mp-card-meta strong { color: var(--bs-emphasis-color); }

.mp-progress {
    position: relative;
    margin-top: 8px;
    height: 18px;
    border-radius: 999px;
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    overflow: hidden;
}
.mp-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
    transition: width .2s ease;
}
.mp-progress-label {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    font-weight: 700;
    color: var(--bs-emphasis-color);
    text-shadow: 0 1px 2px rgba(0,0,0,.4);
}

.mp-card-actions { display: flex; gap: 6px; margin-top: 10px; }
.mp-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .75rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
    font-size: .78rem;
    text-decoration: none;
}
.mp-pill:hover { filter: brightness(1.2); }
</style>

<?= $this->endSection() ?>
