<?php
/**
 * Parcial: piezas como galería de carpetas — tarjetas de tamaño fijo con
 * icono de carpeta, ID + fecha legibles y el nombre en varias líneas
 * (recortado con puntos suspensivos si no cabe). Espera $piezas en el
 * scope de quien lo incluya.
 */
?>
<?php
// Si se listan las carpetas de una unidad, arrastramos ?desde=ID para que
// el botón "Subir" de la carpeta vuelva a ESTA unidad, no a otra.
$qsDesde = isset($unidad['id']) ? '?desde=' . (int) $unidad['id'] : '';
?>
<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="d-flex flex-wrap gap-3">
        <?php foreach ($piezas as $p): ?>
            <a href="<?= site_url('silo/' . $p['id']) . $qsDesde ?>"
               class="text-decoration-none text-body border rounded p-2 d-flex flex-column align-items-center text-center silo-carpeta"
               style="width: 160px; height: 240px;">
                <i class="bi bi-folder-fill text-warning" style="font-size: 2.75rem; line-height: 1;"></i>
                <span class="text-muted mt-1" style="font-size: .7rem;">
                    #<?= esc($p['id_negocio']) ?> · <?= esc(silo_fecha_humana($p['fecha'] ?? null)) ?>
                </span>
                <span class="d-block text-center mt-1 silo-carpeta-badges" title="<?= esc($p['nombre_carpeta']) ?>"><?= silo_badges_carpeta($p, true) ?></span>
                <?php $coincidencias = $p['ficheros_coincidentes'] ?? []; ?>
                <?php if ($coincidencias): ?>
                    <span class="badge bg-info-subtle text-info-emphasis mt-1" style="font-size: .6rem;"
                          title="<?= esc(implode("\n", array_column($coincidencias, 'nombre')), 'attr') ?>">
                        <i class="bi bi-search"></i> <?= count($coincidencias) ?> fichero<?= count($coincidencias) === 1 ? '' : 's' ?>
                    </span>
                <?php endif; ?>
                <span class="text-muted mt-auto pt-1" style="font-size: .7rem;"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
