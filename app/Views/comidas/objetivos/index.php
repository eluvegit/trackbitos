<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Límites diarios por nutriente</h3>
  <div class="d-flex gap-2">
    <a href="<?= site_url('comidas/objetivos/create') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Nuevo límite
    </a>
  </div>
</div>

<?php if (session('ok')): ?>
  <div class="alert alert-success py-2"><?= esc(session('ok')) ?></div>
<?php endif; ?>
<?php if (session('errors')): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="min-width:220px">Nutriente</th>
            <th>Tipo</th>
            <th class="text-end">Umbral</th>
            <th>Nivel</th>
            <th>Mensaje</th>
            <th class="text-end" style="width:160px">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              Aún no hay límites. Crea el primero con el botón <strong>“Nuevo límite”</strong>.
            </td>
          </tr>
        <?php else: ?>
          <?php
            $badgeNivel = function($nivel) {
              return $nivel === 'critical' ? 'danger' : ($nivel === 'warning' ? 'warning' : 'info');
            };
// Colores para el tipo de límite
$badgeTipoClass = function ($tipo) {
  // exceso = rojo; falta = gris (ajusta a tu gusto)
  return $tipo === 'exceso' ? 'text-bg-danger' : 'text-bg-primary';
};
?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= esc($r['nutriente_nombre'] ?? ('#'.$r['nutriente_id'])) ?></div>
                <div class="text-muted small">
                  <?= esc($r['nutriente_clave'] ?? '') ?>
                  <?php if (!empty($r['categoria'])): ?> · <?= esc($r['categoria']) ?><?php endif; ?>
                  <?php if (!empty($r['es_macro'])): ?> · <span class="badge bg-light text-dark border">macro</span><?php endif; ?>
                </div>
              </td>
              <td>
  <span class="badge rounded-pill <?= $badgeTipoClass($r['tipo'] ?? '') ?>">
    <?= esc(ucfirst($r['tipo'])) ?>
  </span>
</td>

              <td class="text-end">
                <?= esc(rtrim(rtrim(number_format((float)($r['umbral'] ?? 0), 2, '.', ''), '0'), '.')) ?>
                <span class="text-muted"><?= esc($r['nutriente_unidad'] ?? '') ?></span>
              </td>
              <td>
                <span class="badge bg-<?= $badgeNivel($r['nivel'] ?? 'warning') ?>">
                  <?= esc($r['nivel'] ?? 'warning') ?>
                </span>
              </td>
              <td class="text-muted"><?= esc($r['mensaje'] ?? '') ?></td>
              <td class="text-end">
                <a href="<?= site_url('comidas/objetivos/edit/'.$r['id']) ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="<?= site_url('comidas/objetivos/delete/'.$r['id']) ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('¿Eliminar este límite?');">
                  <i class="bi bi-trash"></i> Eliminar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection(); ?>
