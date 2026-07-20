<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
helper('gimnasio');

function tituloBonito($clave)
{
    return [
        'press banca' => 'Press banca',
        'peso muerto' => 'Peso muerto',
        'sentadillas' => 'Sentadillas',
    ][$clave] ?? ucfirst($clave);
}
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-graph-up-arrow text-primary"></i>
    <a href="<?= site_url('gimnasio') ?>" class="text-decoration-none text-muted fw-normal">Gimnasio</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Estadísticas principales</strong>
</h5>
<p class="text-muted small mb-3">
    Progresión estimada a 1 repetición máxima (fórmula de Epley) de tus tres básicos, para comparar de un vistazo
    tu mejor marca con lo que estás haciendo ahora.
</p>

<?php foreach (['press banca', 'peso muerto', 'sentadillas'] as $clave): ?>
    <?php $bloque = $bloques[$clave] ?? null; ?>
    <div class="gim-stat-card mb-3">
        <div class="gim-stat-header">
            <strong><?= tituloBonito($clave) ?></strong>
            <?php if (!empty($ejercicios[$clave])): ?>
                <a href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $ejercicios[$clave]['id']) ?>" class="gim-stat-link">
                    Historial completo <i class="bi bi-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($ejercicios[$clave])): ?>
            <p class="text-muted small mb-0 p-3">No se ha encontrado el ejercicio "<?= tituloBonito($clave) ?>" en tu base de datos.</p>
        <?php elseif (empty($bloque['progresion'])): ?>
            <p class="text-muted small mb-0 p-3">Todavía no hay series con peso registradas para <?= tituloBonito($clave) ?>.</p>
        <?php else: ?>
            <?php
            $pr = $bloque['pr'];
            $ultimo = $bloque['ultimo'];
            $diff = round($ultimo['e1rm'] - $pr['e1rm'], 1);
            ?>
            <div class="gim-stat-numbers">
                <div class="gim-stat-num">
                    <span class="gim-stat-num-label"><i class="bi bi-trophy-fill"></i> Mejor marca (est.)</span>
                    <span class="gim-stat-num-value"><?= $pr['e1rm'] ?> kg</span>
                    <span class="gim-stat-num-sub"><?= $pr['peso'] ?>kg × <?= $pr['reps'] ?> · <?= date('d/m/Y', strtotime($pr['fecha'])) ?></span>
                </div>
                <div class="gim-stat-num">
                    <span class="gim-stat-num-label"><i class="bi bi-calendar-check"></i> Última sesión</span>
                    <span class="gim-stat-num-value"><?= $ultimo['e1rm'] ?> kg</span>
                    <span class="gim-stat-num-sub">
                        <?= $ultimo['peso'] ?>kg × <?= $ultimo['reps'] ?> · <?= date('d/m/Y', strtotime($ultimo['fecha'])) ?>
                        <?php if ($diff === 0.0): ?>
                            <span class="gim-badge gim-badge-pr">= tu PR</span>
                        <?php else: ?>
                            <span class="gim-badge">(<?= $diff ?> kg vs. PR)</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <?php if (count($bloque['progresion']) >= 2): ?>
                <div class="gim-chart">
                    <?= gim_svg_chart($bloque['progresion']) ?>
                </div>
                <p class="text-muted small text-center mb-0"><?= count($bloque['progresion']) ?> sesiones registradas · 1RM estimado en el tiempo</p>
            <?php endif; ?>

            <div class="gim-recent">
                <?php foreach ($bloque['reciente'] as $r): ?>
                    <div class="gim-recent-item <?= $r['fecha'] === $pr['fecha'] ? 'is-pr' : '' ?>">
                        <span class="gim-recent-fecha"><?= date('d/m/Y', strtotime($r['fecha'])) ?></span>
                        <span class="gim-recent-peso"><?= $r['peso'] ?>kg × <?= $r['reps'] ?></span>
                        <span class="gim-recent-e1rm"><?= $r['e1rm'] ?> kg <?php if ($r['fecha'] === $pr['fecha']): ?><i class="bi bi-trophy-fill"></i><?php endif; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<style>
.gim-stat-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    background: var(--bs-tertiary-bg);
    overflow: hidden;
}
.gim-stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
}
.gim-stat-header strong { color: var(--bs-emphasis-color); }
.gim-stat-link {
    font-size: .78rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.gim-stat-link:hover { color: var(--bs-emphasis-color); }

.gim-stat-numbers {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 12px 14px;
}
.gim-stat-num { display: flex; flex-direction: column; gap: 2px; }
.gim-stat-num-label {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    gap: .3rem;
}
.gim-stat-num-value { font-size: 1.4rem; font-weight: 700; color: var(--bs-emphasis-color); line-height: 1.1; }
.gim-stat-num-sub { font-size: .72rem; color: var(--bs-secondary-color); }

.gim-badge {
    display: inline-block;
    margin-left: 4px;
    font-size: .68rem;
    color: var(--bs-secondary-color);
}
.gim-badge-pr { color: #f59e0b; font-weight: 700; }

.gim-chart { padding: 4px 10px 0; }
.gim-chart-svg { width: 100%; height: 100px; display: block; }

.gim-recent { display: flex; flex-direction: column; padding: 4px 0 6px; }
.gim-recent-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 14px;
    font-size: .82rem;
    border-top: 1px solid var(--bs-border-color);
}
.gim-recent-item.is-pr { background: rgba(245, 158, 11, .08); }
.gim-recent-fecha { color: var(--bs-secondary-color); flex: 0 0 auto; width: 80px; }
.gim-recent-peso { color: var(--bs-emphasis-color); flex: 1 1 auto; }
.gim-recent-e1rm { color: var(--bs-secondary-color); flex: 0 0 auto; display: flex; align-items: center; gap: .3rem; }
.gim-recent-item.is-pr .gim-recent-e1rm { color: #f59e0b; font-weight: 700; }

@media (max-width: 420px) {
    .gim-stat-numbers { grid-template-columns: 1fr; }
    .gim-recent-fecha { width: 68px; }
}
</style>

<?= $this->endSection() ?>
