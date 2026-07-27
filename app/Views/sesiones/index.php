<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$titulosColumna = [
    'planificacion' => 'Planificación',
    'edicion'       => 'Edición',
    'subiendo'      => 'Subiendo',
    'completado'    => 'Completado',
];

$colorEstado = [
    'planificacion' => 'bg-secondary',
    'edicion'       => 'bg-warning text-dark',
    'subiendo'      => 'bg-info text-dark',
    'completado'    => 'bg-success',
];

$iconoParte = ['foto' => 'bi-camera', 'video' => 'bi-camera-video'];
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h5 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-camera text-primary"></i> Sesiones
    </h5>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= site_url('sesiones/ideas') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-lightbulb"></i> Ideas
        </a>
        <a href="<?= site_url('sesiones/crear') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva sesión
        </a>
    </div>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (empty($sesiones)): ?>
    <p class="text-muted">Todavía no hay sesiones. <a href="<?= site_url('sesiones/crear') ?>">Crea la primera</a>.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table kanban-table align-middle" id="kanbanTabla">
        <thead>
            <tr>
                <th class="kanban-col-sesion">Sesión</th>
                <?php foreach ($titulosColumna as $estado => $titulo): ?>
                    <th><?= esc($titulo) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sesiones as $s): ?>
                <tr>
                    <td class="kanban-col-sesion">
                        <a href="<?= site_url('sesiones/' . $s['id']) ?>" class="fw-semibold text-decoration-none">
                            <?= esc($s['titulo']) ?>
                        </a>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php if ((int) $s['pausada'] === 1): ?>
                                <span class="badge bg-secondary"><i class="bi bi-pause-fill"></i> Pausada</span>
                            <?php endif; ?>
                            <?php if ($s['entrega_modelos'] === 'pendiente'): ?>
                                <span class="badge bg-warning text-dark">Pendiente entrega</span>
                            <?php elseif ($s['entrega_modelos'] === 'entregado'): ?>
                                <span class="badge bg-success">✓ Entregado</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php foreach ($titulosColumna as $estado => $tituloEstado): ?>
                        <td class="kanban-cell" data-estado="<?= $estado ?>">
                            <div class="cell-inner" data-row-group="sesion-<?= (int) $s['id'] ?>">
                                <?php foreach (['foto', 'video'] as $parte): ?>
                                    <?php if ($s['estado_' . $parte] === $estado): ?>
                                        <a href="<?= site_url('sesiones/' . $s['id']) ?>"
                                           class="kanban-chip <?= $colorEstado[$estado] ?>"
                                           data-id="<?= (int) $s['id'] ?>"
                                           data-parte="<?= $parte ?>"
                                           title="<?= esc($s['titulo']) ?> — <?= $parte === 'foto' ? 'Fotografía' : 'Vídeo' ?>">
                                            <i class="bi <?= $iconoParte[$parte] ?>"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.kanban-table th, .kanban-table td {
    vertical-align: middle;
}
.kanban-col-sesion {
    min-width: 200px;
}
.kanban-cell {
    min-width: 110px;
}
.cell-inner {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 44px;
    align-items: center;
}
.kanban-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 1.1rem;
    cursor: grab;
}
.kanban-chip.sortable-ghost { opacity: .3; }
.kanban-chip.sortable-chosen { outline: 2px solid var(--bs-primary); }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    document.querySelectorAll('.cell-inner').forEach(cell => {
        Sortable.create(cell, {
            group: cell.dataset.rowGroup,
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: async (evt) => {
                if (evt.from === evt.to) return;

                const chip = evt.item;
                const id = chip.dataset.id;
                const parte = chip.dataset.parte;
                const nuevoEstado = evt.to.closest('td').dataset.estado;

                try {
                    const res = await fetch(`<?= site_url('sesiones') ?>/${id}/estado`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ parte, estado: nuevoEstado }),
                    });

                    if (!res.ok) throw new Error('estado change failed');
                } catch (e) {
                    console.error(e);
                    // revertir el movimiento
                    evt.from.insertBefore(chip, evt.from.children[evt.oldIndex] || null);
                }
            },
        });
    });
})();
</script>

<?= $this->endSection() ?>
