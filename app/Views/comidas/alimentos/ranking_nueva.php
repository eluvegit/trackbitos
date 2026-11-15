<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$fmt = static function ($v, $d = 1) {
    if (!is_numeric($v)) return $v;
    $s = number_format((float) $v, $d, '.', '');
    return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
};
$tipoActual = basename((string) parse_url(current_url(), PHP_URL_PATH));
$tipos = [
    'proteinas'        => 'Más proteína',
    'carbohidratos'    => 'Menos carbos',
    'grasas'           => 'Menos grasa',
    'kcal'             => 'Menos kcal',
    'mas-consumidos'   => 'Más comidos',
    'menos-consumidos' => 'Menos comidos',
];
$esConsumo = $campo === 'veces';
$macros = ['proteina_g' => 'P', 'carbohidratos_g' => 'C', 'grasas_g' => 'G'];
?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa' => 'alimentos',
        'migas'  => ['Alimentos' => site_url('comidas/alimentos'), 'Rankings' => null],
        'titulo' => esc($title),
        'sub'    => '<span>' . ($esConsumo ? 'Cuenta lo que comes directamente y como ingrediente de una receta' : 'Valores por 100 g, recetas incluidas') . '</span>',
    ]) ?>

    <nav class="cm-seg">
        <?php foreach ($tipos as $k => $txt): ?>
            <a href="<?= site_url('comidas/alimentos/ranking/' . $k) ?>" class="<?= $k === $tipoActual ? 'on' : '' ?>"><?= $txt ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="rc-lista cm-lista">
        <?php if (empty($rows)): ?>
            <div class="rc-vacio">Sin resultados</div>
        <?php endif; ?>
        <?php foreach ($rows as $i => $r): ?>
            <a class="rc-fila con-pos" href="<?= site_url('comidas/alimentos/edit/' . $r['id']) ?>">
                <span class="cm-pos"><?= $i + 1 ?></span>
                <span class="rc-txt">
                    <span class="rc-t"><span class="t"><?= esc($r['nombre']) ?></span><?php if (!empty($r['es_receta'])): ?><span class="fv-estado e-info">receta</span><?php endif; ?></span>
                    <?php if ($esConsumo): ?>
                        <span class="cm-macrostxt"><span>directo <b><?= (int) ($r['directo'] ?? 0) ?></b></span><span>en recetas <b><?= (int) ($r['via_recetas'] ?? 0) ?></b></span></span>
                    <?php else: ?>
                        <span class="cm-macrostxt"><?php foreach ($macros as $c => $l): ?><span class="<?= $c === $campo ? 'on' : '' ?>"><?= $l ?> <b><?= $fmt($r[$c] ?? 0) ?></b></span><?php endforeach; ?></span>
                    <?php endif; ?>
                </span>
                <?php if ($esConsumo): ?>
                    <span class="cm-k destacado"><?= (int) ($r['veces'] ?? 0) ?> <small>veces</small></span>
                <?php elseif ($campo === 'kcal'): ?>
                    <span class="cm-k destacado"><?= $fmt($r['kcal'] ?? 0, 0) ?> <small>kcal</small></span>
                <?php else: ?>
                    <span class="cm-k destacado"><?= $fmt($r[$campo] ?? 0) ?> <small>g</small></span>
                <?php endif; ?>
                <span class="cm-k"><?= $campo === 'kcal' ? '' : $fmt($r['kcal'] ?? 0, 0) . ' <small>kcal</small>' ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
