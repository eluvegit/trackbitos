<span class="btn-group btn-group-sm">
    <?php foreach (['anterior' => 'bi-chevron-left', 'siguiente' => 'bi-chevron-right'] as $lado => $icono): ?>
        <?php $v = $vecinas[$lado]; ?>
        <a class="btn btn-outline-secondary <?= $v ? '' : 'disabled' ?>"
            href="<?= $v ? site_url('piezas/placa/' . (int) $v['id'] . $sufijo) : '#' ?>"
            title="<?= $v ? esc(ucfirst($lado) . ': ' . $v['nombre'], 'attr') : 'No hay ' . $lado ?>">
            <?php if ($lado === 'anterior'): ?><i class="bi <?= $icono ?>"></i> Anterior<?php else: ?>Siguiente <i class="bi <?= $icono ?>"></i><?php endif; ?>
        </a>
    <?php endforeach; ?>
</span>
