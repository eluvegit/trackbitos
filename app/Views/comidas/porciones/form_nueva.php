<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $aid = $alimento['id'] ?? $row['alimento_id']; ?>
<div class="fv rc estrecho">
    <?= view('comidas/_cabecera', [
        'activa' => 'alimentos',
        'migas'  => ['Alimentos' => site_url('comidas/alimentos'), $alimento['nombre'] => site_url('comidas/porciones/alimento/' . $aid), (isset($row) ? 'Editar' : 'Nueva') => null],
        'titulo' => isset($row) ? 'Editar porción' : 'Nueva porción',
        'sub'    => '<span>' . esc($alimento['nombre']) . '</span>',
    ]) ?>
    <form method="post" class="fv-panel rc-form" action="<?= isset($row) ? site_url('comidas/porciones/update/' . $row['id']) : site_url('comidas/porciones/store') ?>">
        <?= csrf_field() ?>
        <?php if (!isset($row)): ?><input type="hidden" name="alimento_id" value="<?= esc($alimento['id']) ?>"><?php endif; ?>
        <label class="rc-campo"><span>Nombre de la porción</span><input type="text" name="descripcion" class="fv-input" maxlength="120" required value="<?= esc(old('descripcion', $row['descripcion'] ?? '')) ?>" placeholder="Ej. taza, rebanada, cucharada sopera…"></label>
        <label class="rc-campo"><span>Equivale a (gramos)</span><input type="number" name="gramos_equivalentes" step="1" min="1" inputmode="numeric" class="fv-input cm-num" value="<?= esc(old('gramos_equivalentes', $row['gramos_equivalentes'] ?? '')) ?>" placeholder="Ej. 150"></label>
        <label class="rc-inter"><input type="checkbox" name="es_predeterminada" value="1" <?= old('es_predeterminada', $row['es_predeterminada'] ?? 0) ? 'checked' : '' ?>><span><b>Predeterminada</b><small>Sale elegida al apuntar este alimento en el diario.</small></span></label>
        <div class="rc-pie">
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i><?= isset($row) ? 'Guardar cambios' : 'Crear porción' ?></button>
            <a class="fv-btn" href="<?= site_url('comidas/porciones/alimento/' . $aid) ?>">Cancelar</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
