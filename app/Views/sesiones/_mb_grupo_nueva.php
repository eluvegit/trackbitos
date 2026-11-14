<?php
/**
 * Un grupo del moodboard (estilo fv): "General" (id '') o una situación.
 * Es zona de soltar/pegar imágenes (ver assets/js/sesiones.js).
 *
 * @var array $g   ['id' => int|'', 'nombre' => string, 'items' => array]
 * @var int   $sid sesión
 */
$esSit = $g['id'] !== '';
?>
<section class="fv-panel ss-grupo" data-situacion="<?= $g['id'] ?>" tabindex="0">
    <div class="fv-ph">
        <?php if ($esSit): ?><span class="ss-asa" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span><?php endif; ?>
        <h3 class="t1"><?= esc($g['nombre']) ?></h3>
        <span class="cuenta" data-n><?= count($g['items']) ?></span>
        <div class="der ss-grupo-acc">
            <?php if ($esSit): ?>
                <a class="fv-ibtn" href="<?= site_url("sesiones/$sid/situaciones/{$g['id']}/exportar") ?>" target="_blank" title="Exportar esta situación"><i class="bi bi-printer"></i></a>
                <button type="button" class="fv-ibtn ss-sit-borrar" title="Borrar situación"><i class="bi bi-trash"></i></button>
            <?php endif; ?>
        </div>
    </div>
    <div class="ss-mb">
        <?php foreach ($g['items'] as $item): ?>
            <?= view('sesiones/_mb_item_nueva', ['item' => $item]) ?>
        <?php endforeach; ?>
        <label class="ss-mb-add" title="Subir imágenes">
            <input type="file" accept="image/*" multiple hidden>
            <i class="bi bi-upload"></i><span>Subir</span>
        </label>
        <button type="button" class="ss-mb-add ss-mb-enlace" title="Añadir enlace"><i class="bi bi-link-45deg"></i><span>Enlace</span></button>
    </div>
    <form class="ss-enlace-form" hidden>
        <input class="fv-input" type="url" name="url_externa" placeholder="https://…" required>
        <input class="fv-input" name="nota" placeholder="Nota (opcional)">
        <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri">Añadir</button>
    </form>
</section>
