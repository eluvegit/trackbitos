<?php
$pid = (int) $proyecto['id'];
$claves = array_keys($pasos);
$pos = array_search($paso, $claves, true);
$num = static fn ($p, $k) => $p['ok'] ? '<i class="bi bi-check-lg"></i>' : $k;
$k = 0;
?>
<nav class="rf-pasos" aria-label="Pasos del rodaje">
    <?php foreach ($pasos as $clave => $p): $k++; ?>
        <a href="<?= site_url("rodajes/$pid/$clave") ?>" class="<?= trim(($clave === $paso ? 'on' : '') . ($p['ok'] ? ' ok' : '')) ?>"<?= $clave === $paso ? ' aria-current="step"' : '' ?>>
            <span class="n"><?= $num($p, $k) ?></span><span class="t"><?= $p['txt'] ?></span><?php if ($p['cuenta'] !== ''): ?><small><?= $p['cuenta'] ?></small><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
<?php $act = $pos !== false ? $pasos[$paso] : null; ?>
<details class="rf-pasos-m">
    <summary>
        <?php if ($act): ?>
            <span class="n"><?= $num($act, $pos + 1) ?></span>
            <span class="t"><small>Paso <?= $pos + 1 ?> de <?= count($pasos) ?></small><b><?= $act['txt'] ?></b></span>
            <?php if ($act['cuenta'] !== ''): ?><span class="c"><?= $act['cuenta'] ?></span><?php endif; ?>
        <?php else: ?>
            <span class="n"><i class="bi bi-list-ol"></i></span>
            <span class="t"><small><?= count(array_filter($pasos, static fn ($p) => $p['ok'])) ?> de <?= count($pasos) ?> listos</small><b>Pasos del rodaje</b></span>
        <?php endif; ?>
        <i class="bi bi-chevron-down"></i>
    </summary>
    <div class="rf-pasos-l">
        <?php $k = 0; foreach ($pasos as $clave => $p): $k++; ?>
            <a href="<?= site_url("rodajes/$pid/$clave") ?>" class="<?= trim(($clave === $paso ? 'on' : '') . ($p['ok'] ? ' ok' : '')) ?>"<?= $clave === $paso ? ' aria-current="step"' : '' ?>>
                <span class="n"><?= $num($p, $k) ?></span><span class="t"><?= $p['txt'] ?></span><?php if ($p['cuenta'] !== ''): ?><small><?= $p['cuenta'] ?></small><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</details>
<script>
(() => {
    const d = document.currentScript.previousElementSibling;
    if (!d || !d.matches('.rf-pasos-m')) return;
    document.addEventListener('click', ev => { if (d.open && !d.contains(ev.target)) d.open = false; });
    document.addEventListener('keydown', ev => { if (ev.key === 'Escape') d.open = false; });
})();
</script>
