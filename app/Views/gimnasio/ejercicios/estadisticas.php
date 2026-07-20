<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php helper('gimnasio'); ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-graph-up-arrow text-primary"></i>
    <a href="<?= site_url('gimnasio/ejercicios') ?>" class="text-decoration-none text-muted fw-normal">Ejercicios</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($ejercicio['nombre']) ?></strong>
</h5>

<?php if (empty($progresion)): ?>
    <p class="text-muted">Todavía no hay series con peso registradas para este ejercicio.</p>
<?php else: ?>
    <?php $diff = round($ultimo['e1rm'] - $pr['e1rm'], 1); ?>

    <div class="gim-stat-card mb-3">
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

        <?php if (count($progresion) >= 2): ?>
            <div class="gim-chart">
                <?= gim_svg_chart($progresion) ?>
            </div>
            <p class="text-muted small text-center mb-0"><?= count($progresion) ?> sesiones registradas · 1RM estimado en el tiempo</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($seriesDetalle)): ?>
    <!-- Buscador por peso concreto -->
    <div class="gim-search mb-2">
        <i class="bi bi-search"></i>
        <input type="number" step="any" id="gimBuscadorPeso" class="form-control" placeholder="Buscar por peso concreto (kg)…">
    </div>
    <p class="text-muted small mb-2 d-none" id="gimBuscadorResumen"></p>

    <div class="gim-series-list" id="gimSeriesList">
        <?php foreach ($seriesDetalle as $fila): ?>
            <?php
            $peso = (float) ($fila['peso'] ?? 0);
            $reps = (int) $fila['repeticiones'];
            $series = (int) $fila['series'];
            ?>
            <div class="gim-series-item" data-peso="<?= $peso ?>" data-reps="<?= $reps ?>">
                <span class="gim-series-fecha"><?= date('d/m/Y', strtotime($fila['fecha'])) ?></span>
                <span class="gim-series-detalle">
                    <?= $peso > 0 ? $peso . 'kg × ' . $reps : $series . ' series × ' . $reps . ' reps' ?>
                </span>
                <?php if ($fila['rpe'] !== null && $fila['rpe'] !== ''): ?>
                    <span class="gim-series-rpe">RPE <?= esc($fila['rpe']) ?></span>
                <?php endif; ?>
                <?php if (!empty($fila['nota'])): ?>
                    <span class="gim-series-nota" title="<?= esc($fila['nota']) ?>"><i class="bi bi-chat-left-text"></i></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="text-muted small mt-2 d-none" id="gimSinResultados">No hay series registradas a ese peso.</p>
<?php else: ?>
    <p class="text-muted">No hay registros para este ejercicio.</p>
<?php endif; ?>

<style>
.gim-stat-card {
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    background: var(--bs-tertiary-bg);
    padding: 12px 14px;
}
.gim-stat-numbers { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
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

.gim-badge { display: inline-block; margin-left: 4px; font-size: .68rem; color: var(--bs-secondary-color); }
.gim-badge-pr { color: #f59e0b; font-weight: 700; }

.gim-chart { padding: 4px 0 0; }
.gim-chart-svg { width: 100%; height: 110px; display: block; }

.gim-search { position: relative; display: flex; align-items: center; }
.gim-search i { position: absolute; left: 12px; color: var(--bs-secondary-color); }
.gim-search input { padding-left: 34px; }

.gim-series-list { display: flex; flex-direction: column; gap: 4px; }
.gim-series-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 12px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    font-size: .85rem;
}
.gim-series-item:hover { background: var(--bs-tertiary-bg); }
.gim-series-fecha { color: var(--bs-secondary-color); flex: 0 0 auto; width: 80px; }
.gim-series-detalle { color: var(--bs-emphasis-color); flex: 1 1 auto; font-weight: 600; }
.gim-series-rpe { color: var(--bs-secondary-color); font-size: .74rem; flex: 0 0 auto; }
.gim-series-nota { color: var(--bs-secondary-color); flex: 0 0 auto; cursor: help; }

@media (max-width: 420px) {
    .gim-stat-numbers { grid-template-columns: 1fr; }
    .gim-series-fecha { width: 68px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('gimBuscadorPeso');
    if (!input) return;

    const items = document.querySelectorAll('.gim-series-item');
    const resumen = document.getElementById('gimBuscadorResumen');
    const sinResultados = document.getElementById('gimSinResultados');

    input.addEventListener('input', () => {
        const valor = input.value.trim();

        if (valor === '') {
            items.forEach(item => item.style.display = '');
            resumen.classList.add('d-none');
            sinResultados.classList.add('d-none');
            return;
        }

        const objetivo = parseFloat(valor);
        let coincidencias = 0;
        let mejorReps = 0;
        let mejorFecha = null;

        items.forEach(item => {
            const peso = parseFloat(item.dataset.peso);
            const coincide = Math.abs(peso - objetivo) < 0.01;
            item.style.display = coincide ? '' : 'none';
            if (coincide) {
                coincidencias++;
                const reps = parseInt(item.dataset.reps, 10);
                if (reps > mejorReps) {
                    mejorReps = reps;
                    mejorFecha = item.querySelector('.gim-series-fecha').textContent;
                }
            }
        });

        sinResultados.classList.toggle('d-none', coincidencias !== 0);

        if (coincidencias > 0) {
            resumen.textContent = coincidencias + ' serie' + (coincidencias === 1 ? '' : 's') +
                ' a ' + objetivo + 'kg · mejor: ' + mejorReps + ' reps (' + mejorFecha + ')';
            resumen.classList.remove('d-none');
        } else {
            resumen.classList.add('d-none');
        }
    });
});
</script>

<?= $this->endSection() ?>
