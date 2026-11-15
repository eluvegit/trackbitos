<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $n = count($rows); ?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa'   => 'alimentos',
        'migas'    => ['Alimentos' => site_url('comidas/alimentos'), $alimento['nombre'] => site_url('comidas/alimentos/edit/' . $alimento['id']), 'Porciones' => null],
        'titulo'   => 'Porciones',
        'sub'      => '<span>' . esc($alimento['nombre']) . '</span><span class="fv-dot"></span><span>' . $n . ' ' . ($n === 1 ? 'porción' : 'porciones') . '</span>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('comidas/porciones/create/' . $alimento['id']) . '"><i class="bi bi-plus-lg"></i>Nueva porción</a>',
    ]) ?>
    <div class="rc-lista cm-lista">
        <?php if (!$rows): ?><div class="rc-vacio">No hay porciones para este alimento.</div><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <div class="rc-fila">
                <a class="rc-txt" href="<?= site_url('comidas/porciones/edit/' . $r['id']) ?>">
                    <span class="rc-t"><span class="t"><?= esc($r['descripcion']) ?></span><?php if (!empty($r['es_predeterminada'])): ?><span class="fv-estado e-acc">predeterminada</span><?php endif; ?></span>
                </a>
                <span class="cm-k"><?= number_format((float) $r['gramos_equivalentes'], 0) ?> <small>g</small></span>
                <div class="rc-btns">
                    <a class="fv-ibtn" href="<?= site_url('comidas/porciones/edit/' . $r['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a class="fv-ibtn" href="<?= site_url('comidas/porciones/delete/' . $r['id']) ?>" title="Eliminar" onclick="return confirm('¿Eliminar esta porción?');"><i class="bi bi-trash"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
