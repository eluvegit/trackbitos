<?php
/**
 * Parcial: piezas como galería de carpetas (cuadrícula de tarjetas), la
 * alternativa al listado para ver de un vistazo qué carpetas hay. Espera
 * $piezas en el scope de quien lo incluya.
 */
?>
<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-5 g-3">
        <?php foreach ($piezas as $p): ?>
            <div class="col">
                <a href="<?= site_url('silo/' . $p['id']) ?>"
                   class="text-decoration-none text-body d-block h-100 border rounded p-3 text-center silo-carpeta">
                    <i class="bi bi-folder-fill text-warning d-block mb-2" style="font-size: 2.5rem; line-height: 1;"></i>
                    <span class="d-block small text-truncate" title="<?= esc($p['nombre_carpeta']) ?>"><?= esc($p['nombre_carpeta']) ?></span>
                    <span class="d-block text-muted" style="font-size: .75rem;"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <style>
        .silo-carpeta { transition: background-color .12s ease, border-color .12s ease; }
        .silo-carpeta:hover { background-color: var(--bs-tertiary-bg); border-color: var(--bs-secondary); }
    </style>
<?php endif; ?>
