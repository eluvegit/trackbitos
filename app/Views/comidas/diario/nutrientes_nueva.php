<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$fmt = static function ($n, $d = 0) {
    $s = number_format((float) $n, $d, '.', '');
    return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
};
$f = $fechaSel->format('Y-m-d');
$titulo = ucfirst(\CodeIgniter\I18n\Time::parse($f)->toLocalizedString("EEEE d 'de' MMMM"));
$grupos = ['Macronutrientes' => [], 'Micronutrientes' => []];
foreach ($items as $n) $grupos[($n['categoria'] ?? '') === 'macro' ? 'Macronutrientes' : 'Micronutrientes'][] = $n;
$porClave = array_column($items, 'valor', 'clave');
$fuera = count(array_filter($items, fn ($n) => ($n['min'] !== null && $n['valor'] < $n['min']) || ($n['max'] !== null && $n['valor'] > $n['max'])));
?>
<div class="fv rc ancho">
    <?= view('comidas/_cabecera', [
        'activa' => 'diario',
        'migas'  => [$titulo => site_url('comidas/diario/' . $f)],
        'titulo' => 'Nutrientes',
        'sub'    => '<span>' . esc($titulo) . '</span><span class="fv-dot"></span>' . ($fuera ? '<span class="fv-estado e-warn">' . $fuera . ' fuera de rango</span>' : '<span class="fv-estado e-ok">Todo en rango</span>'),
        'acciones' => '<a class="fv-btn" href="' . site_url('comidas/diario/' . $f) . '"><i class="bi bi-arrow-left"></i>Volver al día</a>',
    ]) ?>

    <div class="cm-dia">
        <?php foreach ($grupos as $nombre => $lista): ?>
            <section class="fv-panel">
                <div class="fv-ph"><h3><?= $nombre ?></h3><span class="cuenta"><?= count($lista) ?></span></div>
                <?php if (!$lista): ?><div class="fv-vacio">Sin datos.</div><?php endif; ?>
                <div class="cm-nutris">
                    <?php foreach ($lista as $n): ?>
                        <?php
                        $min = $n['min']; $max = $n['max']; $v = (float) $n['valor'];
                        $escala = max(($max ?? $min ?? 0) * 1.2, $v, 0.0001);
                        $estado = ($min === null && $max === null) ? '' : (($min !== null && $v < $min) ? 'low' : (($max !== null && $v > $max) ? 'high' : 'ok'));
                        $dec = in_array($n['unidad'], ['g'], true) ? 1 : 0;
                        ?>
                        <div class="cm-nutri">
                            <div class="cm-macro-t">
                                <span class="n"><?= esc($n['nombre']) ?></span>
                                <span class="v"><?= $fmt($v, $dec) ?> <?= esc($n['unidad']) ?></span>
                                <?php if ($estado === 'low'): ?><span class="q e-warn">bajo</span><?php elseif ($estado === 'high'): ?><span class="q e-bad">alto</span><?php endif; ?>
                                <span class="r"><?= ($min !== null || $max !== null) ? ($min !== null ? $fmt($min) : '—') . '–' . ($max !== null ? $fmt($max) : '—') . ' ' . esc($n['unidad']) : 'sin objetivo' ?></span>
                            </div>
                            <div class="cm-barra<?= $estado ? ' s-' . $estado : '' ?>">
                                <?php if ($min !== null || $max !== null): ?><span class="zona" style="left:<?= round(($min ?? 0) / $escala * 100, 1) ?>%;width:<?= round((($max ?? $escala) - ($min ?? 0)) / $escala * 100, 1) ?>%"></span><?php endif; ?>
                                <span class="lleno" style="width:<?= round(min(100, $v / $escala * 100), 1) ?>%"></span>
                            </div>
                        </div>
                        <?php if (($n['clave'] ?? '') === 'omega6_mg' && isset($porClave['omega3_mg'])): ?>
                            <?php $o3 = (float) $porClave['omega3_mg']; ?>
                            <div class="cm-ratio"><span>Ratio omega-6 : omega-3</span><b><?= $o3 > 0 ? $fmt($v / $o3, 2) . ' : 1' : '—' ?></b></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
