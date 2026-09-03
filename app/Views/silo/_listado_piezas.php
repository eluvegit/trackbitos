<?php
/**
 * Parcial: listado de piezas estilo explorador de ficheros (icono de
 * carpeta + nombre + tamaño sumado). Espera $piezas en el scope de quien
 * incluya este parcial. La fecha de subida no se muestra aquí — no es
 * relevante para un vistazo rápido de qué hay (indicación del usuario,
 * 2026-09-03).
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
    <div class="list-group">
        <?php foreach ($piezas as $p): ?>
            <a href="<?= site_url('silo/' . $p['id']) . $qsDesde ?>"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                <i class="bi bi-folder2 text-warning fs-5"></i>
                <span class="flex-grow-1 d-flex flex-wrap align-items-center gap-1" title="<?= esc($p['nombre_carpeta']) ?>"><?= silo_badges_carpeta($p) ?></span>
                <span class="text-muted small text-nowrap"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
