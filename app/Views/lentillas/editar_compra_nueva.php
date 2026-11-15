<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lentillas — editar una compra (estilo fv).
 *
 * @var array $compra
 */
$c = $compra;
$tipos = ['Lentillas', 'Líquido', 'Gafas', 'Otro'];
if (!in_array($c['tipo'], $tipos, true)) $tipos[] = (string) $c['tipo'];
?>
<div class="fv rc estrecho">
    <?= view('recordatorios/_cabecera', [
        'modulo' => 'lentillas',
        'activa' => 'compras',
        'migas'  => ['Compras' => site_url('lentillas/compras'), 'Editar' => null],
        'titulo' => 'Editar compra',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('lentillas/compras/actualizar/' . $c['id']) ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <div class="rc-filas">
                <label class="rc-campo">
                    <span>Qué</span>
                    <select class="rc-sel" name="tipo" required>
                        <?php foreach ($tipos as $t): ?><option value="<?= esc($t, 'attr') ?>" <?= $c['tipo'] === $t ? 'selected' : '' ?>><?= esc($t) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="rc-campo">
                    <span>Precio (€)</span>
                    <input type="number" class="fv-input" name="precio" step="0.01" min="0" inputmode="decimal" value="<?= esc((string) $c['precio'], 'attr') ?>" required>
                </label>
                <label class="rc-campo">
                    <span>Fecha</span>
                    <input type="date" class="fv-input" name="fecha" value="<?= esc(substr((string) $c['fecha'], 0, 10), 'attr') ?>" required>
                </label>
            </div>
            <label class="rc-campo">
                <span>Notas</span>
                <textarea class="fv-input" name="notas" rows="2"><?= esc((string) $c['notas']) ?></textarea>
            </label>
        </div>
        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= site_url('lentillas/compras') ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
