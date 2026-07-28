<?php
// $item: fila de moodboard_items (origen=archivo|enlace)
// $situaciones (opcional): lista de situaciones de la sesión, para poder
// vincular este item a una de ellas (o quitarlo a "general"). Solo se pasa
// desde Sesiones — Ideas no tiene situaciones y el control no se muestra.
$esArchivo   = $item['origen'] === 'archivo';
$src         = $esArchivo ? base_url($item['ruta_archivo']) : $item['url_externa'];
$situaciones = $situaciones ?? null;
?>
<div class="gallery-item" data-id="<?= (int) $item['id'] ?>" data-situacion-id="<?= $item['situacion_id'] ?? '' ?>" title="<?= esc($item['nota'] ?? '') ?>">
    <a href="<?= esc($src, 'attr') ?>" target="_blank" rel="noopener">
        <?php if ($esArchivo): ?>
            <img src="<?= esc($src, 'attr') ?>" alt="Referencia moodboard" loading="lazy">
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center h-100 text-center p-2">
                <span><i class="bi bi-link-45deg d-block fs-3"></i><small class="text-muted text-break"><?= esc($item['url_externa']) ?></small></span>
            </div>
        <?php endif; ?>
    </a>
    <button type="button" class="item-borrar" title="Borrar"><i class="bi bi-x"></i></button>
    <?php if ($situaciones !== null): ?>
        <div class="dropdown item-vincular-dropdown">
            <button type="button" class="item-vincular" title="Vincular a situación" data-bs-toggle="dropdown"><i class="bi bi-link-45deg"></i></button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item item-vincular-opcion" href="#" data-situacion-id="">General</a></li>
                <?php foreach ($situaciones as $sit): ?>
                    <li><a class="dropdown-item item-vincular-opcion" href="#" data-situacion-id="<?= (int) $sit['id'] ?>"><?= esc($sit['nombre']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
