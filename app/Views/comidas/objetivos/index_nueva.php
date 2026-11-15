<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$num = static fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
$nivel = ['info' => ['e-info', 'info'], 'warning' => ['e-warn', 'aviso'], 'critical' => ['e-bad', 'crítico']];
$grupos = [];
foreach ($rows as $r) {
    $k = $r['nutriente_id'];
    $grupos[$k] ??= ['r' => $r, 'falta' => null, 'exceso' => null];
    $grupos[$k][$r['tipo'] === 'falta' ? 'falta' : 'exceso'] = $r;
}
$n = count($grupos);
?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa'   => 'objetivos',
        'titulo'   => 'Objetivos diarios',
        'sub'      => '<span>' . $n . ' ' . ($n === 1 ? 'nutriente' : 'nutrientes') . ' con mínimo o máximo</span><span class="fv-dot"></span><span>Se usan en las barras del diario y en Mejores días</span>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('comidas/objetivos/create') . '"><i class="bi bi-plus-lg"></i>Nuevo límite</a>',
    ]) ?>

    <div class="rc-lista cm-lista">
        <?php if (!$grupos): ?><div class="rc-vacio">Aún no hay límites. Crea el primero con «Nuevo límite».</div><?php endif; ?>
        <?php foreach ($grupos as $g): ?>
            <?php $r = $g['r']; $u = esc($r['nutriente_unidad'] ?? ''); $mensajes = array_filter([$g['falta']['mensaje'] ?? '', $g['exceso']['mensaje'] ?? '']); ?>
            <div class="rc-fila cm-obj">
                <div class="rc-txt">
                    <span class="rc-t"><span class="t"><?= esc($r['nutriente_nombre'] ?? '#' . $r['nutriente_id']) ?></span><?php if (!empty($r['es_macro'])): ?><span class="rc-mudo">macro</span><?php endif; ?></span>
                    <span class="rc-meta"><span class="cm-num"><?= esc($r['nutriente_clave'] ?? '') ?></span><?php foreach ($mensajes as $m): ?><span><?= esc($m) ?></span><?php endforeach; ?></span>
                </div>
                <div class="rc-btns cm-obj-lim">
                    <?php foreach (['falta' => 'mín', 'exceso' => 'máx'] as $tipo => $lbl): ?>
                        <?php if ($l = $g[$tipo]): ?>
                            <?php [$cls, $txt] = $nivel[$l['nivel']] ?? $nivel['warning']; ?>
                            <span>
                                <a class="fv-btn fv-btn-sm" href="<?= site_url('comidas/objetivos/edit/' . $l['id']) ?>" title="Nivel: <?= $txt ?>"><span class="fv-estado <?= $cls ?>"><?= $lbl ?></span><span class="cm-num"><?= $num($l['umbral']) ?> <?= $u ?></span></a>
                                <a class="fv-ibtn" href="<?= site_url('comidas/objetivos/delete/' . $l['id']) ?>" title="Eliminar" onclick="return confirm('¿Eliminar el <?= $lbl ?>imo de <?= esc($r['nutriente_nombre'] ?? '', 'js') ?>?');"><i class="bi bi-x-lg"></i></a>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
