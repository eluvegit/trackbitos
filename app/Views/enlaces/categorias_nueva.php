<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — categorías (estilo fv). Pocas y útiles: cada fila lleva a la
 * búsqueda filtrada por ella.
 *
 * @var array $categorias
 * @var array $conteoPorCategoria
 */
usort($categorias, static fn ($a, $b) => [($conteoPorCategoria[$b['id']] ?? 0), $a['nombre']] <=> [($conteoPorCategoria[$a['id']] ?? 0), $b['nombre']]);
$n = count($categorias);
?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'activa' => 'categorias',
        'titulo' => 'Categorías',
        'sub'    => '<span>' . $n . ' ' . ($n === 1 ? 'categoría' : 'categorías') . '</span><span class="fv-dot"></span><span>De la más usada a la que menos</span>',
    ]) ?>

    <form class="en-nueva" method="post" action="<?= site_url('enlaces/categorias/guardar') ?>">
        <?= csrf_field() ?>
        <input class="fv-input" name="nombre" placeholder="Nueva categoría…" required maxlength="100" autocomplete="off">
        <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i>Añadir</button>
    </form>

    <?php if ($categorias): ?>
        <div class="en-gest">
            <?php foreach ($categorias as $c): ?>
                <?php $t = (int) ($conteoPorCategoria[$c['id']] ?? 0); ?>
                <div class="en-g en-cat-g" style="grid-template-columns:30px minmax(0,1fr) auto auto">
                    <span class="rc-ico" style="color:hsl(<?= crc32($c['nombre']) % 360 ?> 70% 72%)"><i class="bi bi-hash"></i></span>
                    <a class="n" href="<?= site_url('enlaces') . '?cats%5B%5D=' . (int) $c['id'] ?>" title="Ver sus enlaces"><?= esc($c['nombre']) ?></a>
                    <span class="c<?= $t ? '' : ' cero' ?>"><?= $t ?></span>
                    <a class="fv-ibtn en-borrar" href="<?= site_url('enlaces/categorias/borrar/' . (int) $c['id']) ?>" onclick="return confirm('¿Borrar la categoría «<?= esc($c['nombre'], 'js') ?>»?<?= $t ? ' Los ' . $t . ' enlaces se quedan, solo sin esta categoría.' : '' ?>');" title="Borrar"><i class="bi bi-trash"></i></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="fv-panel rc-vacio">Todavía no hay categorías.</div>
    <?php endif; ?>
</div>
<?php $this->endSection(); ?>
