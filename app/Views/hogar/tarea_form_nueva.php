<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — editar tarea (estilo fv).
 *
 * @var array $tarea
 */
$t   = $tarea;
$hab = (new \App\Models\HogarHabitacionModel())->find($t['habitacion_id']);
?>
<div class="fv rc estrecho">
    <?= view('hogar/_cabecera', [
        'migas'    => [($hab['nombre'] ?? 'Habitación') => site_url('hogar/' . $t['habitacion_id']), 'Editar tarea' => null],
        'titulo'   => 'Editar tarea',
        'acciones' => '<a class="fv-btn" href="' . site_url('hogar/tareas/' . $t['id'] . '/historial') . '"><i class="bi bi-clock-history"></i>Historial</a>',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('hogar/tareas/actualizar/' . $t['id']) ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <label class="rc-campo">
                <span>Tarea</span>
                <input class="fv-input rc-grande" name="nombre" value="<?= esc((string) old('nombre', $t['nombre']), 'attr') ?>" required maxlength="150">
            </label>
            <label class="rc-campo" style="max-width:220px">
                <span>Cada cuántos días <small>(vacío = sin frecuencia)</small></span>
                <input type="number" class="fv-input" name="frecuencia_dias" min="1" inputmode="numeric" value="<?= esc((string) old('frecuencia_dias', $t['frecuencia_dias'] ?? ''), 'attr') ?>">
            </label>
        </div>
        <div class="rc-pie">
            <button type="submit" class="fv-btn fv-btn-peligro" formaction="<?= site_url('hogar/tareas/borrar/' . $t['id']) ?>" formnovalidate onclick="return confirm('¿Borrar esta tarea y su historial?')"><i class="bi bi-trash"></i>Borrar</button>
            <div class="der">
                <a class="fv-btn" href="<?= site_url('hogar/' . $t['habitacion_id']) ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
