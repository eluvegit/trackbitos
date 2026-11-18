<?php $pid = (int) $proyecto['id']; $k = 0; ?>
<nav class="rf-pasos" aria-label="Pasos del rodaje">
    <?php foreach ($pasos as $clave => $p): $k++; ?>
        <a href="<?= site_url("rodajes/$pid/$clave") ?>" class="<?= trim(($clave === $paso ? 'on' : '') . ($p['ok'] ? ' ok' : '')) ?>"<?= $clave === $paso ? ' aria-current="step"' : '' ?>>
            <span class="n"><?= $p['ok'] ? '<i class="bi bi-check-lg"></i>' : $k ?></span><span class="t"><?= $p['txt'] ?></span><?php if ($p['cuenta'] !== ''): ?><small><?= $p['cuenta'] ?></small><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
<script>
// Móvil (pasos con scroll lateral): deja a la vista el paso actual.
(() => { const n = document.currentScript.previousElementSibling, a = n && n.querySelector('a.on'); if (a && n.scrollWidth > n.clientWidth) n.scrollLeft = a.offsetLeft - (n.clientWidth - a.offsetWidth) / 2; })();
</script>
