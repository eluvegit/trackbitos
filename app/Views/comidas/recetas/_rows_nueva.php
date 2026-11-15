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
            <a class="rc-txt" href="<?= site_url('comidas/recetas/edit/' . $r['id']) ?>">
                <span class="rc-t"><span class="t"><?= esc($r['nombre']) ?></span></span>
                <?php if ($r['kcal'] !== null): ?>
                    <span class="cm-macrostxt"><span>P <b><?= $fmt($r['proteina_g']) ?></b></span><span>C <b><?= $fmt($r['carbohidratos_g']) ?></b></span><span>G <b><?= $fmt($r['grasas_g']) ?></b></span><span class="t3">por 100 g</span></span>
                <?php else: ?>
                    <span class="rc-meta">Sin ingredientes todavía</span>
                <?php endif; ?>
            </a>
            <span class="cm-k"><?= $r['kcal'] !== null ? $fmt($r['kcal'], 0) . ' <small>kcal</small>' : '' ?></span>
            <div class="rc-btns">
                <a class="fv-ibtn" href="<?= site_url('comidas/recetas/edit/' . $r['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                <form action="<?= site_url('comidas/recetas/delete/' . $r['id']) ?>" method="post" style="margin:0" onsubmit="return confirm('¿Eliminar la receta «<?= esc($r['nombre'], 'js') ?>»?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="fv-ibtn" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
