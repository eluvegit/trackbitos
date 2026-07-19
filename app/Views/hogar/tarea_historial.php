<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="hogar-header mb-3">
    <a href="<?= site_url('hogar/' . $habitacion['id']) ?>" class="hogar-back">
        <i class="bi bi-chevron-left"></i> <?= esc($habitacion['nombre']) ?>
    </a>
    <h2 class="hogar-title mb-0 mt-1">
        <i class="bi bi-clock-history text-primary"></i>
        <?= esc($tarea['nombre']) ?>
    </h2>
</div>

<!-- Resumen -->
<div class="hist-stats mb-3">
    <div class="hist-stat">
        <span class="hist-stat-num"><?= $totalVeces ?></span>
        <span class="hist-stat-label">Veces hecha</span>
    </div>

    <div class="hist-stat">
        <span class="hist-stat-num"><?= $media !== null ? $media : '—' ?></span>
        <span class="hist-stat-label">Media (días)</span>
    </div>

    <?php if ($minIntervalo !== null): ?>
        <div class="hist-stat">
            <span class="hist-stat-num"><?= $minIntervalo ?>–<?= $maxIntervalo ?></span>
            <span class="hist-stat-label">Rango (días)</span>
        </div>
    <?php endif; ?>

    <?php if ($tarea['frecuencia_dias']): ?>
        <div class="hist-stat">
            <span class="hist-stat-num"><?= (int)$tarea['frecuencia_dias'] ?></span>
            <span class="hist-stat-label">Objetivo (días)</span>
        </div>
    <?php endif; ?>
</div>

<?php if ($media !== null && $tarea['frecuencia_dias']): ?>
    <?php $cumple = $media <= $tarea['frecuencia_dias']; ?>
    <div class="alert <?= $cumple ? 'alert-success' : 'alert-warning' ?> py-2 small">
        <?php if ($cumple): ?>
            <i class="bi bi-check-circle"></i>
            Vas cumpliendo el objetivo: la haces de media cada <?= $media ?> días (objetivo: cada <?= (int)$tarea['frecuencia_dias'] ?>).
        <?php else: ?>
            <i class="bi bi-exclamation-triangle"></i>
            La estás haciendo más despacio de lo previsto: de media cada <?= $media ?> días (objetivo: cada <?= (int)$tarea['frecuencia_dias'] ?>).
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($primeraVez): ?>
    <p class="text-muted small">
        Primera vez registrada: <?= date('d/m/Y', strtotime($primeraVez)) ?> ·
        Última vez: <?= date('d/m/Y H:i', strtotime($ultimaVez)) ?>
    </p>
<?php endif; ?>

<!-- Calendario de los últimos dos meses -->
<div class="cal-wrap mb-4">
    <?php foreach ($calendario as $mes): ?>
        <div class="cal-month">
            <div class="cal-titulo"><?= esc($mes['etiqueta']) ?></div>
            <div class="cal-dow">
                <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
            <div class="cal-grid">
                <?php foreach ($mes['semanas'] as $semana): ?>
                    <?php foreach ($semana as $celda): ?>
                        <?php if ($celda === null): ?>
                            <div class="cal-day is-vacio"></div>
                        <?php else: ?>
                            <div class="cal-day <?= $celda['veces'] > 0 ? 'is-marcado' : '' ?> <?= $celda['esHoy'] ? 'is-hoy' : '' ?>"
                                 title="<?= $celda['veces'] > 0 ? $celda['veces'] . ' vez/veces' : '' ?>">
                                <?= $celda['dia'] ?>
                                <?php if ($celda['veces'] > 1): ?>
                                    <span class="cal-day-badge"><?= $celda['veces'] ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Línea de tiempo -->
<?php if (empty($timeline)): ?>
    <p class="text-muted">Todavía no se ha marcado esta tarea ninguna vez.</p>
<?php else: ?>
    <div class="hist-timeline">
        <?php foreach ($timeline as $item): ?>
            <div class="hist-item">
                <i class="bi bi-check-circle-fill hist-item-icon"></i>
                <div class="hist-item-body">
                    <div class="hist-item-fecha"><?= date('d/m/Y H:i', strtotime($item['fecha'])) ?></div>
                    <?php if ($item['intervalo'] !== null): ?>
                        <div class="hist-item-intervalo"><?= $item['intervalo'] ?> días después de la vez anterior</div>
                    <?php else: ?>
                        <div class="hist-item-intervalo">Primer registro</div>
                    <?php endif; ?>
                </div>
                <form action="<?= site_url('hogar/tareas/logs/' . $item['id'] . '/borrar') ?>" method="post" class="m-0"
                      onsubmit="return confirm('¿Eliminar este registro? (por ejemplo si marcaste la tarea por error)')">
                    <?= csrf_field() ?>
                    <button type="submit" class="hist-item-borrar" title="Eliminar este registro">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.hogar-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.hogar-back:hover { color: var(--bs-emphasis-color); }
.hogar-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.hist-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 12px 14px;
}
.hist-stat { display: flex; flex-direction: column; }
.hist-stat-num { font-size: 1.2rem; font-weight: 700; color: var(--bs-emphasis-color); line-height: 1.1; }
.hist-stat-label { font-size: .7rem; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: .04em; }

.hist-timeline { display: flex; flex-direction: column; gap: 2px; position: relative; }
.hist-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 4px;
    border-left: 2px solid var(--bs-border-color);
    margin-left: 8px;
    padding-left: 14px;
}
.hist-item-icon { color: #10b981; font-size: .9rem; margin-left: -21px; background: var(--bs-body-bg); }
.hist-item-fecha { font-weight: 600; font-size: .9rem; color: var(--bs-emphasis-color); }
.hist-item-intervalo { font-size: .78rem; color: var(--bs-secondary-color); }

.hist-item form { margin-left: auto; flex: 0 0 auto; }

.hist-item-borrar {
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: var(--bs-secondary-color);
}
.hist-item-borrar:hover { background: rgba(220,53,69,.12); color: #dc3545; }

.cal-wrap { display: flex; gap: 1.5rem; flex-wrap: wrap; }
.cal-month { flex: 1 1 260px; min-width: 240px; }

.cal-titulo {
    font-weight: 600;
    font-size: .85rem;
    color: var(--bs-emphasis-color);
    text-transform: capitalize;
    margin-bottom: 6px;
}

.cal-dow {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    margin-bottom: 4px;
}
.cal-dow span {
    text-align: center;
    font-size: .68rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
}

.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.cal-day {
    position: relative;
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border-radius: 8px;
    font-size: .78rem;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
}
.cal-day.is-vacio { background: transparent; }

.cal-day.is-marcado {
    background: rgba(16,185,129,.2);
    color: #10b981;
    font-weight: 700;
}

.cal-day.is-hoy {
    box-shadow: inset 0 0 0 2px #7c3aed;
}

.cal-day-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    min-width: 14px;
    height: 14px;
    padding: 0 3px;
    border-radius: 999px;
    background: #7c3aed;
    color: #fff;
    font-size: .58rem;
    font-weight: 700;
    display: grid;
    place-items: center;
}

@media (max-width: 575.98px) {
    .cal-wrap { gap: 1rem; }
    .cal-month { min-width: 100%; }
}
</style>

<?= $this->endSection() ?>
