<?php
/**
 * Ventana móvil de constancia. Deliberadamente NO es una racha que se
 * resetea a 0: cuenta cuántos días de los últimos N tocaste el libro, sin
 * comparar un día con otro ni marcar en rojo los días saltados.
 *
 * Espera $constancia = ['tocados' => int, 'dias' => int, 'ventana' => [['fecha'=>Y-m-d,'tocado'=>bool], ...]]
 */
?>
<div class="rd-streak">
    <div class="rd-streak-text">
        <?= (int) $constancia['tocados'] ?> de los últimos <?= (int) $constancia['dias'] ?> días tocaste este libro
    </div>
    <div class="rd-streak-dots" title="Cada punto es un día; relleno = lo tocaste">
        <?php foreach ($constancia['ventana'] as $dia): ?>
            <span class="rd-streak-dot <?= $dia['tocado'] ? 'is-filled' : '' ?>"></span>
        <?php endforeach; ?>
    </div>
</div>

<style>
.rd-streak { margin: .75rem 0; }
.rd-streak-text { font-size: .85rem; color: var(--bs-secondary-color); margin-bottom: .4rem; }
.rd-streak-dots { display: flex; flex-wrap: wrap; gap: 4px; }
.rd-streak-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
}
.rd-streak-dot.is-filled { background: var(--bs-primary); border-color: var(--bs-primary); }
</style>
