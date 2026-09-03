<?php
/**
 * Parcial: listado de piezas estilo explorador de ficheros (icono de
 * carpeta + nombre + tamaño sumado). Espera $piezas en el scope de quien
 * incluya este parcial. La fecha de subida no se muestra aquí — no es
 * relevante para un vistazo rápido de qué hay (indicación del usuario,
 * 2026-09-03).
 */
?>
<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($piezas as $p): ?>
            <a href="<?= site_url('silo/' . $p['id']) ?>"
               class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                <i class="bi bi-folder2 text-warning fs-5"></i>
                <span class="flex-grow-1 text-truncate"><?= esc($p['nombre_carpeta']) ?></span>
                <span class="text-muted small text-nowrap"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
