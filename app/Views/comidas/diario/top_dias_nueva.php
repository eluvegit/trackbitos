<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$fmt = static function ($v, $d = 0) {
    $s = number_format((float) $v, $d, '.', '');
    return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
};
$pos = 0;
?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa' => 'diario',
        'migas'  => ['Mejores días' => null],
        'titulo' => 'Mejores días',
        'sub'    => '<span>Los 28 días más cercanos a tus objetivos, en 4 semanas para replicar un mes. Toca uno para ver qué comiste.</span>',
    ]) ?>

    <?php if (empty($semanas)): ?>
        <div class="fv-panel rc-vacio">No hay suficientes días con registros para hacer el ranking.</div>
    <?php else: ?>
        <div class="rc-lista cm-lista">
            <?php foreach ($semanas as $i => $dias): ?>
                <div class="cm-semana">Semana <?= $i + 1 ?></div>
                <?php foreach ($dias as $d): $pos++; ?>
                    <a class="rc-fila con-pos" href="<?= site_url('comidas/diario/' . $d['fecha']) ?>">
                        <span class="cm-pos"><?= $pos ?></span>
                        <span class="rc-txt">
                            <span class="rc-t"><span class="t"><?= esc(ucfirst((new \CodeIgniter\I18n\Time($d['fecha']))->toLocalizedString('EEE d MMM y'))) ?></span></span>
                            <span class="cm-macrostxt"><span>P <b><?= $fmt($d['totales']['proteina_g']) ?></b></span><span>C <b><?= $fmt($d['totales']['carbohidratos_g']) ?></b></span><span>G <b><?= $fmt($d['totales']['grasas_g']) ?></b></span><span>Az <b><?= $fmt($d['totales']['azucares_g'] ?? 0) ?></b></span></span>
                        </span>
                        <span class="cm-k"><?= $fmt($d['totales']['kcal']) ?> <small>kcal</small></span>
                        <span class="cm-k" title="Puntuación (0 = ideal)"><small><?= $fmt($d['score'], 2) ?></small></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
