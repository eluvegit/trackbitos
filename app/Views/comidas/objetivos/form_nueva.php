<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('comidas/objetivos/update/' . $row['id']) : site_url('comidas/objetivos/store');
$nutSel = (string) old('nutriente_id', $row['nutriente_id'] ?? '');
$tipoSel = (string) old('tipo', $row['tipo'] ?? 'exceso');
$umbral = old('umbral', isset($row['umbral']) ? (string) $row['umbral'] : '');
$nivelSel = (string) old('nivel', $row['nivel'] ?? 'warning');
?>
<div class="fv rc estrecho">
    <?= view('comidas/_cabecera', [
        'activa' => 'objetivos',
        'migas'  => ['Objetivos' => site_url('comidas/objetivos'), ($isEdit ? 'Editar' : 'Nuevo') => null],
        'titulo' => $isEdit ? 'Editar límite' : 'Nuevo límite',
    ]) ?>
    <form method="post" action="<?= $action ?>" class="fv-panel rc-form">
        <?= csrf_field() ?>
        <label class="rc-campo"><span>Nutriente</span>
            <select name="nutriente_id" id="cmNutriente" class="fv-input rc-sel" required>
                <option value="">— Elige —</option>
                <?php foreach ($nutrientes ?? [] as $n): ?>
                    <option value="<?= $n['id'] ?>" data-unidad="<?= esc($n['unidad']) ?>" <?= $nutSel === (string) $n['id'] ? 'selected' : '' ?>><?= esc($n['nombre']) ?><?= $n['unidad'] ? ' (' . esc($n['unidad']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="rc-campo"><span class="rc-lbl">Tipo</span>
            <div class="rc-cats">
                <label><input type="radio" name="tipo" value="falta" <?= $tipoSel === 'falta' ? 'checked' : '' ?>><span><i class="bi bi-arrow-down"></i>Mínimo (avisa si falta)</span></label>
                <label><input type="radio" name="tipo" value="exceso" <?= $tipoSel === 'exceso' ? 'checked' : '' ?>><span><i class="bi bi-arrow-up"></i>Máximo (avisa si te pasas)</span></label>
            </div>
        </div>
        <div class="rc-filas">
            <label class="rc-campo"><span>Umbral <small id="cmUnidad"></small></span><input type="number" step="0.01" min="0" inputmode="decimal" name="umbral" value="<?= esc($umbral) ?>" class="fv-input rc-grande cm-num" placeholder="Ej. 2000" required></label>
            <label class="rc-campo"><span>Importancia</span>
                <select name="nivel" class="fv-input rc-sel" required>
                    <option value="info" <?= $nivelSel === 'info' ? 'selected' : '' ?>>Info</option>
                    <option value="warning" <?= $nivelSel === 'warning' ? 'selected' : '' ?>>Aviso</option>
                    <option value="critical" <?= $nivelSel === 'critical' ? 'selected' : '' ?>>Crítico</option>
                </select>
            </label>
        </div>
        <label class="rc-campo"><span>Mensaje <small>(opcional)</small></span><input type="text" name="mensaje" class="fv-input" value="<?= esc(old('mensaje', $row['mensaje'] ?? '')) ?>" placeholder="Ej. «Azúcares altos»"></label>
        <div class="rc-pie">
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i><?= $isEdit ? 'Guardar cambios' : 'Crear límite' ?></button>
            <a href="<?= site_url('comidas/objetivos') ?>" class="fv-btn">Cancelar</a>
            <?php if ($isEdit): ?><a href="<?= site_url('comidas/objetivos/delete/' . $row['id']) ?>" class="fv-btn fv-btn-peligro der" onclick="return confirm('¿Eliminar este límite?');"><i class="bi bi-trash"></i>Eliminar</a><?php endif; ?>
        </div>
    </form>
</div>
<script>
(() => {
    const s = document.getElementById('cmNutriente'), u = document.getElementById('cmUnidad');
    const pon = () => { const x = s.selectedOptions[0]?.dataset.unidad || ''; u.textContent = x ? '(' + x + ')' : ''; };
    s.addEventListener('change', pon); pon();
})();
</script>
<?= $this->endSection() ?>
