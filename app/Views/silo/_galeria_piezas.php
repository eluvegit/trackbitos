<?php
/**
 * Parcial: piezas como galería de carpetas — tarjetas de tamaño fijo con
 * icono de carpeta, ID + fecha legibles y el nombre en varias líneas
 * (recortado con puntos suspensivos si no cabe). Espera $piezas en el
 * scope de quien lo incluya.
 */
?>
<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="d-flex flex-wrap gap-3">
        <?php foreach ($piezas as $p): ?>
            <a href="<?= site_url('silo/' . $p['id']) ?>"
               class="text-decoration-none text-body border rounded p-2 d-flex flex-column align-items-center text-center silo-carpeta"
               style="width: 160px; height: 190px;">
                <i class="bi bi-folder-fill text-warning" style="font-size: 2.75rem; line-height: 1;"></i>
                <span class="text-muted mt-1" style="font-size: .7rem;">
                    #<?= esc($p['id_negocio']) ?> · <?= esc(silo_fecha_humana($p['fecha'] ?? null)) ?>
                </span>
                <span class="d-flex flex-wrap justify-content-center gap-1 mt-1 silo-carpeta-badges" title="<?= esc($p['nombre_carpeta']) ?>"><?= silo_badges_carpeta($p) ?></span>
                <span class="text-muted mt-auto pt-1" style="font-size: .7rem;"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <style>
        .silo-carpeta { transition: background-color .12s ease, border-color .12s ease; }
        .silo-carpeta:hover { background-color: var(--bs-tertiary-bg); border-color: var(--bs-secondary); }
        .silo-carpeta-badges {
            overflow: hidden;
            max-height: 5.5rem;
        }
        .silo-carpeta-badges .badge { font-size: .62rem; }
    </style>
<?php endif; ?>
