<?php
// $sesionId: int, $situacionId: int|null (null = moodboard general)
$sufijo = $situacionId ?? 'general';
?>
<div class="d-flex flex-wrap gap-3 mt-2">
    <form class="moodboard-form d-flex align-items-center gap-2" data-origen="archivo" enctype="multipart/form-data">
        <?php if ($situacionId !== null): ?><input type="hidden" name="situacion_id" value="<?= (int) $situacionId ?>"><?php endif; ?>
        <input type="file" name="archivo" accept="image/*" multiple class="form-control form-control-sm" required id="archivo-<?= $sufijo ?>">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i></button>
    </form>

    <form class="moodboard-form d-flex align-items-center gap-2" data-origen="enlace">
        <?php if ($situacionId !== null): ?><input type="hidden" name="situacion_id" value="<?= (int) $situacionId ?>"><?php endif; ?>
        <input type="url" name="url_externa" placeholder="https://..." class="form-control form-control-sm" required>
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-link-45deg"></i></button>
    </form>
</div>
