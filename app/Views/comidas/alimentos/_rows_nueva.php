<?php
$fmt = static function ($v, $d = 1) {
    $s = number_format((float) $v, $d, '.', '');
    return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
};
?>
<?php if (empty($rows)): ?>
    <div class="rc-vacio">Sin resultados</div>
<?php else: ?>
    <?php foreach ($rows as $r): ?>
        <div class="rc-fila">
            <a class="rc-txt" href="<?= site_url('comidas/alimentos/edit/' . $r['id']) ?>">
                <span class="rc-t"><span class="t"><?= esc($r['nombre']) ?></span><?php if (!empty($r['marca'])): ?><span class="rc-mudo"><?= esc($r['marca']) ?></span><?php endif; ?><?php if (!empty($r['es_receta'])): ?><span class="fv-estado e-info">receta</span><?php endif; ?></span>
                <span class="cm-macrostxt"><span>P <b><?= $fmt($r['proteina_g'] ?? 0) ?></b></span><span>C <b><?= $fmt($r['carbohidratos_g'] ?? 0) ?></b></span><span>G <b><?= $fmt($r['grasas_g'] ?? 0) ?></b></span></span>
            </a>
            <span class="cm-k"><?= $fmt($r['kcal'] ?? 0, 0) ?> <small>kcal</small></span>
            <div class="rc-btns">
                <a class="fv-ibtn" href="<?= site_url('comidas/porciones/alimento/' . $r['id']) ?>" title="Porciones"><i class="bi bi-collection"></i></a>
                <a class="fv-ibtn" href="<?= site_url('comidas/alimentos/edit/' . $r['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                <form action="<?= site_url('comidas/alimentos/delete/' . $r['id']) ?>" method="post" style="margin:0" onsubmit="return confirm('¿Eliminar «<?= esc($r['nombre'], 'js') ?>»?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="fv-ibtn" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
