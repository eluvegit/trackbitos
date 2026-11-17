<?php
$tipo = $i['origen'];
$txt  = rod_texto_idea($i);
$est  = ['pendiente' => ['Sin revisar', 'neu'], 'toma' => ['Toma', 'ok'], 'variante' => ['Variante', 'info'], 'descartada' => ['Descartada', 'bad']][$i['estado'] ?? 'pendiente'] ?? ['Sin revisar', 'neu'];
$marcable ??= false;
$href ??= null;
$on ??= false;
$grande ??= false;
$tag = $marcable ? 'label' : ($href ? 'a' : 'div');
?>
<<?= $tag ?> class="rf-idea<?= ($i['estado'] ?? '') === 'descartada' ? ' off' : '' ?><?= $on ? ' on' : '' ?><?= $grande ? ' grande' : '' ?>"<?= $tag === 'a' ? ' href="' . $href . '"' : '' ?>>
    <?php if ($marcable): ?><input type="checkbox" name="ids[]" value="<?= (int) $i['id'] ?>"><span class="rf-check" aria-hidden="true"></span><?php endif; ?>
    <span class="rf-media <?= $tipo ?>">
        <?php if ($tipo === 'archivo'): ?>
            <img src="<?= base_url($i['ruta_archivo']) ?>" alt="" loading="lazy">
            <?php if ($txt !== '' && !$grande): ?><span class="cap"><?= esc($txt) ?></span><?php endif; ?>
        <?php else: ?>
            <span class="kind"><i class="bi <?= $tipo === 'enlace' ? 'bi-link-45deg' : 'bi-chat-left-text' ?>"></i><?= $tipo === 'enlace' ? 'Enlace' : 'Nota' ?></span>
            <span class="tx"><?= esc($txt) ?></span>
        <?php endif; ?>
    </span>
    <?php if (!$grande): ?>
        <span class="rf-pie"><i class="rf-dot <?= $est[1] ?>"></i><?= $est[0] ?><?php if (!empty($i['buscar_ref'])): ?><span class="rf-flag">Buscar ref.</span><?php endif; ?></span>
    <?php endif; ?>
</<?= $tag ?>>
