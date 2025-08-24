<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>

<?php
// ✅ Usa old() para rehidratar tras validación fallida
$isEdit   = !empty($row);
$title    = $title ?? ($isEdit ? 'Editar límite' : 'Nuevo límite');
$action   = $isEdit ? site_url('comidas/objetivos/update/'.$row['id']) : site_url('comidas/objetivos/store');

$nutSel   = (string) old('nutriente_id', $row['nutriente_id'] ?? '');
$tipoSel  = (string) old('tipo',         $row['tipo'] ?? 'exceso');
$umbral   =         old('umbral',        isset($row['umbral']) ? (string)$row['umbral'] : ''); // ← punto, no coma
$nivelSel = (string) old('nivel',        $row['nivel'] ?? 'warning');
$mensaje  = (string) old('mensaje',      $row['mensaje'] ?? '');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?= esc($title) ?></h3>
  <a href="<?= site_url('comidas/objetivos') ?>" class="btn btn-outline-secondary">← Volver</a>
</div>

<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nutriente</label>
          <select name="nutriente_id" id="nutriente_id" class="form-select" required>
            <option value="">-- Selecciona --</option>
            <?php foreach (($nutrientes ?? []) as $n): ?>
              <option
                value="<?= $n['id'] ?>"
                data-unidad="<?= esc($n['unidad']) ?>"
                <?= ((string)$nutSel === (string)$n['id']) ? 'selected' : '' ?>>
                <?= esc($n['nombre']) ?> <?= $n['unidad'] ? '(' . esc($n['unidad']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Selecciona el nutriente al que aplica el límite.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Tipo</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo" id="t_falta" value="falta" <?= $tipoSel==='falta' ? 'checked' : '' ?>>
              <label class="form-check-label" for="t_falta">Falta (umbral mínimo)</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="tipo" id="t_exceso" value="exceso" <?= $tipoSel==='exceso' ? 'checked' : '' ?>>
              <label class="form-check-label" for="t_exceso">Exceso (umbral máximo)</label>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Umbral <span id="unidad_label" class="text-muted"></span></label>
          <!-- ⚠️ Mantén punto como separador; acepta coma en el POST en el controlador -->
          <input type="number" step="0.01" min="0" inputmode="decimal"
                 name="umbral" value="<?= esc($umbral) ?>" class="form-control" placeholder="Ej. 2000" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Nivel de severidad</label>
          <select name="nivel" class="form-select" required>
            <option value="info"     <?= $nivelSel==='info' ? 'selected' : '' ?>>Info</option>
            <option value="warning"  <?= $nivelSel==='warning' ? 'selected' : '' ?>>Aviso</option>
            <option value="critical" <?= $nivelSel==='critical' ? 'selected' : '' ?>>Crítico</option>
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Mensaje (opcional)</label>
          <input type="text" name="mensaje" class="form-control" value="<?= esc($mensaje) ?>" placeholder="Ej. «Azúcares altos»">
        </div>
      </div>

      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><?= $isEdit ? 'Guardar cambios' : 'Crear límite' ?></button>
        <a href="<?= site_url('comidas/objetivos') ?>" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
  // Muestra la unidad al lado del campo "Umbral" según el nutriente seleccionado
  document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('nutriente_id');
    const lbl = document.getElementById('unidad_label');
    const update = () => {
      const opt = sel?.options[sel.selectedIndex];
      const unidad = opt?.getAttribute('data-unidad') || '';
      lbl.textContent = unidad ? `(${unidad})` : '';
    };
    sel?.addEventListener('change', update);
    update();
  });
</script>

<?= $this->endSection(); ?>
