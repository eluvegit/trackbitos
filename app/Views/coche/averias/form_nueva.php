<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Coche — apuntar / editar una avería (estilo fv).
 *
 * @var array|null $averia
 */
$a      = $averia ?? null;
$editar = (bool) $a;
?>
<div class="fv rc estrecho">
    <?= view('recordatorios/_cabecera', [
        'modulo' => 'coche',
        'activa' => 'averias',
        'migas'  => ['Averías' => site_url('coche/averias'), ($editar ? 'Editar' : 'Apuntar') => null],
        'titulo' => $editar ? 'Editar avería' : 'Apuntar avería',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('coche/averias/guardar') ?>">
        <?= csrf_field() ?>
        <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><?php endif; ?>

        <div class="fv-panel">
            <label class="rc-campo">
                <span>Qué ha pasado</span>
                <textarea class="fv-input" name="notes" rows="4" placeholder="Qué falló, qué se arregló, dónde, cuánto costó…" <?= $editar ? '' : 'autofocus' ?>><?= esc((string) old('notes', $a['notes'] ?? '')) ?></textarea>
            </label>
            <div class="rc-filas">
                <label class="rc-campo">
                    <span>Fecha</span>
                    <input type="date" class="fv-input" name="date" value="<?= esc((string) old('date', $a['date'] ?? date('Y-m-d')), 'attr') ?>" required>
                </label>
                <label class="rc-campo">
                    <span>Kilómetros <small>(opcional)</small></span>
                    <input type="number" class="fv-input" name="kilometers" min="0" inputmode="numeric" value="<?= esc((string) old('kilometers', $a['kilometers'] ?? ''), 'attr') ?>">
                </label>
            </div>
        </div>

        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= site_url('coche/averias') ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
