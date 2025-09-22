<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('text'); ?>

<?php
$e = $escena;

// Helpers
$orden     = (int)($e['orden'] ?? 0);
$bloque    = trim($e['escena_bloque'] ?? '');
$tomas     = trim($e['escena_tomas'] ?? '');
$ubic      = trim($e['escena_ubicacion'] ?? '');
$hora      = trim($e['plano_hora_dia'] ?? '');
$plano     = trim($e['camara_tipo_plano'] ?? '');
$angulo    = trim($e['camara_angulo'] ?? '');
$mov       = trim($e['camara_movimiento'] ?? '');
$soporte   = trim($e['camara_soporte'] ?? '');
$optica    = trim($e['camara_optica'] ?? '');
$apertura  = trim($e['camara_apertura'] ?? '');
$fps       = trim($e['camara_fps'] ?? '');
$vel       = trim($e['camara_velocidad'] ?? '');
$iso       = trim($e['camara_iso'] ?? '');
$wb        = trim($e['camara_wb'] ?? '');
$nd        = trim($e['camara_nd'] ?? '');
$fx        = trim($e['escena_efecto_especial'] ?? '');
$actores   = ($e['plano_actores'] ?? 'N') === 'S';

// Chips compactos
$chips = array_filter([$plano, $angulo, $mov, $soporte]);

// Función para auto-link seguro con saltos de línea
$renderRefs = function(string $txt) {
    return nl2br(auto_link(esc($txt), 'both', true));
};
?>

<style>
/* ---------- Estilos de impresión A4 ---------- */
@page {
  size: A4 portrait;
  margin: 12mm;
}
@media print {
  html, body {
    background: #fff !important;
  }
  .no-print {
    display: none !important;
  }
  .print-card,
  .print-section,
  .print-row-break {
    page-break-inside: avoid;
  }
  .print-break-before {
    page-break-before: always;
  }
  .print-small { font-size: 12px; }
  .print-h1 { font-size: 20px; margin-bottom: 6px; }
  .print-muted { color: #666 !important; }
  .badge { border: 1px solid #ccc !important; color: #333 !important; background: #f7f7f7 !important; }
  .img-thumb { max-height: 60mm; object-fit: cover; }
  .card { border: 1px solid #dcdcdc !important; box-shadow: none !important; }
}

/* ---------- Estilos de pantalla (mejoras ligeras) ---------- */
.toolbar-sticky.no-print {
  position: sticky; top: 0; z-index: 10; background: #fff; padding: .75rem 0; border-bottom: 1px solid rgba(0,0,0,.05);
}
.print-card .card-header { background: #f8f9fa; }
.img-thumb { width: 100%; height: 180px; object-fit: cover; border-radius: .375rem; border: 1px solid rgba(0,0,0,.1); }
.ref-list a { word-break: break-all; }
</style>

<div class="container py-3">

  <!-- Toolbar pantalla -->
  <div class="toolbar-sticky no-print d-flex justify-content-between align-items-start mb-3">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h1 class="mb-0"><?= esc($proyecto['titulo']) ?> — Escena</h1>
        <span class="badge bg-secondary">Orden <?= esc($orden) ?></span>
        <?php if ($hora): ?><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><?= esc($hora) ?></span><?php endif; ?>
        <?php if ($actores): ?><span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Actores</span><?php endif; ?>
        <?php if ($fx): ?><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">FX</span><?php endif; ?>
      </div>
      <div class="text-muted small mt-1">Proyecto #<?= esc($proyecto['id']) ?> · Escena #<?= esc($e['id']) ?></div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver</a>
      <a class="btn btn-outline-primary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $e['id']) ?>?print=1">Vista impresión</a>
      <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir / PDF</button>
      <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">✏️ Editar</a>
    </div>
  </div>

  <!-- Cabecera (apta para impresión) -->
  <div class="print-card card shadow-sm border-0 mb-3">
    <div class="card-body">
      <h2 class="card-title mb-1 print-h1"><?= esc($bloque ?: 'Bloque sin título') ?><?php if ($tomas): ?> <small class="text-muted">• Toma/s: <?= esc($tomas) ?></small><?php endif; ?></h2>
      <div class="print-muted mb-2"><?= $ubic ? '📍 ' . esc($ubic) : '📍 Ubicación no indicada' ?></div>

      <?php if (!empty($chips)): ?>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <?php foreach ($chips as $c): ?>
            <span class="badge text-bg-light border"><?= esc($c) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Resumen cámara compacto -->
      <div class="small text-muted">
        <?php if ($optica): ?><span><strong>Óptica:</strong> <?= esc($optica) ?></span><?php endif; ?>
        <?php if ($optica && ($apertura || $fps || $vel)): ?> · <?php endif; ?>
        <?php if ($apertura): ?><span><strong>ƒ/</strong><?= esc($apertura) ?></span><?php endif; ?>
        <?php if ($apertura && ($fps || $vel)): ?> · <?php endif; ?>
        <?php if ($fps): ?><span><strong>FPS:</strong> <?= esc($fps) ?></span><?php endif; ?>
        <?php if ($fps && $vel): ?> · <?php endif; ?>
        <?php if ($vel): ?><span><strong>Vel:</strong> <?= esc($vel) ?></span><?php endif; ?>
        <?php if ($iso || $wb || $nd): ?> · <?php endif; ?>
        <?php if ($iso): ?><span><strong>ISO:</strong> <?= esc($iso) ?></span><?php endif; ?>
        <?php if ($iso && $wb): ?> · <?php endif; ?>
        <?php if ($wb): ?><span><strong>WB:</strong> <?= esc($wb) ?></span><?php endif; ?>
        <?php if (($iso || $wb) && $nd): ?> · <?php endif; ?>
        <?php if ($nd): ?><span><strong>ND:</strong> <?= esc($nd) ?></span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-3 print-row-break">
    <!-- COLUMNA 1 -->
    <div class="col-lg-6">
      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Objetivo narrativo</strong></div>
        <div class="card-body"><p class="mb-0"><?= nl2br(esc($e['escena_objetivo'] ?? '')) ?></p></div>
      </div>

      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Acción</strong></div>
        <div class="card-body"><p class="mb-0"><?= nl2br(esc($e['escena_accion'] ?? '')) ?></p></div>
      </div>

      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Continuidad</strong></div>
        <div class="card-body">
          <div class="mb-2"><em>Con escena previa:</em><br><?= nl2br(esc($e['escena_cont_previa'] ?? '')) ?></div>
          <div><em>Con escena posterior:</em><br><?= nl2br(esc($e['escena_cont_posterior'] ?? '')) ?></div>
        </div>
      </div>

      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Construcción del plano</strong></div>
        <div class="card-body">
          <div class="mb-2"><em>Esquema de iluminación:</em><br><?= nl2br(esc($e['plano_esquema_iluminacion'] ?? '')) ?></div>
          <div class="mb-2"><em>Objetos en escena:</em><br><?= nl2br(esc($e['plano_objetos'] ?? '')) ?></div>
          <div class="mb-2"><em>Toma alternativa:</em><br><?= nl2br(esc($e['plano_toma_alternativa'] ?? '')) ?></div>
          <div><em>Notas:</em><br><?= nl2br(esc($e['plano_notas'] ?? '')) ?></div>
        </div>
      </div>
    </div>

    <!-- COLUMNA 2 -->
    <div class="col-lg-6">
      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Detalles de cámara</strong></div>
        <div class="card-body">
          <div class="row row-cols-2 g-2 small">
            <div><strong>Cámara:</strong><br><?= esc($e['camara_modelo'] ?? '') ?></div>
            <div><strong>Óptica:</strong><br><?= esc($e['camara_optica'] ?? '') ?></div>
            <div><strong>Apertura:</strong><br><?= esc($apertura) ?></div>
            <div><strong>FPS:</strong><br><?= esc($fps) ?></div>
            <div><strong>Velocidad:</strong><br><?= esc($vel) ?></div>
            <div><strong>ISO:</strong><br><?= esc($iso) ?></div>
            <div><strong>WB:</strong><br><?= esc($wb) ?></div>
            <div><strong>Filtro ND:</strong><br><?= esc($nd) ?></div>
            <div><strong>Tipo de plano:</strong><br><?= esc($plano) ?></div>
            <div><strong>Ángulo:</strong><br><?= esc($angulo) ?></div>
            <div><strong>Movimiento:</strong><br><?= esc($mov) ?></div>
            <div><strong>Soporte:</strong><br><?= esc($soporte) ?></div>
          </div>
        </div>
      </div>

      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Sonido</strong></div>
        <div class="card-body">
          <div class="row row-cols-2 g-2 small">
            <div><strong>Ambiente:</strong><br><?= ($e['sonido_ambiente'] ?? 'N') === 'S' ? 'Sí' : 'No' ?></div>
            <div><strong>Antiviento:</strong><br><?= ($e['sonido_antiviento'] ?? 'N') === 'S' ? 'Sí' : 'No' ?></div>
          </div>
          <div class="mt-2"><em>Diálogo escrito:</em><br><?= nl2br(esc($e['sonido_dialogo_escrito'] ?? '')) ?></div>
        </div>
      </div>

      <div class="print-card card shadow-sm border-0 mb-3">
        <div class="card-header bg-light"><strong>Referencias (texto)</strong></div>
        <div class="card-body ref-list">
          <div class="mb-2"><em>Lugar / objetos:</em><br><?= $renderRefs((string)($e['plano_ref_lugar_texto'] ?? '')) ?></div>
          <div><em>Inspiración:</em><br><?= $renderRefs((string)($e['plano_ref_inspiracion_texto'] ?? '')) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- GALERÍAS (con page-break-inside avoid) -->
  <div class="row g-3 mt-1 print-row-break">
    <div class="col-lg-6">
      <div class="print-card card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <strong>Referencias: Lugar y objetos</strong>
          <?php if (!empty($imagenes_lugar)): ?>
            <button class="btn btn-sm btn-outline-secondary no-print" onclick="copiarURLs('gal_lugar')">Copiar URLs</button>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if (empty($imagenes_lugar)): ?>
            <div class="text-muted">Sin imágenes.</div>
          <?php else: ?>
            <div id="gal_lugar" class="row g-2">
              <?php foreach ($imagenes_lugar as $img): $src = base_url($img['ruta']); ?>
                <div class="col-6 col-md-4">
                  <a href="<?= $src ?>" target="_blank" rel="noopener">
                    <img src="<?= $src ?>" alt="" class="img-thumb">
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="print-card card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <strong>Referencias: Inspiración</strong>
          <?php if (!empty($imagenes_insp)): ?>
            <button class="btn btn-sm btn-outline-secondary no-print" onclick="copiarURLs('gal_insp')">Copiar URLs</button>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if (empty($imagenes_insp)): ?>
            <div class="text-muted">Sin imágenes.</div>
          <?php else: ?>
            <div id="gal_insp" class="row g-2">
              <?php foreach ($imagenes_insp as $img): $src = base_url($img['ruta']); ?>
                <div class="col-6 col-md-4">
                  <a href="<?= $src ?>" target="_blank" rel="noopener">
                    <img src="<?= $src ?>" alt="" class="img-thumb">
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer pantalla -->
  <div class="mt-4 d-flex gap-2 no-print">
    <a class="btn btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver a escenas</a>
    <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir / PDF</button>
    <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">✏️ Editar escena</a>
  </div>
</div>

<script>
function copiarURLs(galeriaId) {
  const root = document.getElementById(galeriaId);
  if (!root) return;
  const urls = [...root.querySelectorAll('a[href]')].map(a => a.href).join('\n');
  navigator.clipboard.writeText(urls)
    .then(() => alert('URLs copiadas al portapapeles'))
    .catch(() => alert('No se pudieron copiar las URLs'));
}

// Modo impresión directo con ?print=1 (oculta toolbar y lanza print)
(function(){
  const params = new URLSearchParams(window.location.search);
  if (params.get('print') === '1') {
    setTimeout(() => window.print(), 300);
  }
})();
</script>

<?= $this->endSection() ?>
