<?php
$dSem = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$mes  = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = ucfirst($dSem[(int) date('w')]) . ', ' . (int) date('j') . ' de ' . $mes[(int) date('n')];
$cad = count(array_filter($recordatoriosUrgentes, fn ($r) => $r['nivel'] === 'caducado'));
$urg = count($recordatoriosUrgentes) - $cad;
?>
<div class="tb-hero"><h1><?= esc($titulo ?? $fecha) ?></h1></div>
<div class="tb-meta">
    <?php if (isset($titulo)): ?><span><?= $fecha ?></span><?php endif; ?>
    <?php if ($cad): ?><span class="tb-estado e-bad"><?= $cad ?> <?= $cad === 1 ? 'recordatorio caducado' : 'recordatorios caducados' ?></span><?php endif; ?>
    <?php if ($urg): ?><span class="tb-estado e-warn"><?= $urg ?> por vencer</span><?php endif; ?>
    <?php if ($mostrarAlerta): ?><span class="tb-estado e-warn">Lentillas: <?= $dias ?> días</span><?php endif; ?>
    <?php if (isset($vivos['hogar'])): ?><span class="tb-estado e-<?= $vivos['hogar']['e'] ?>">Hogar: <?= mb_strtolower($vivos['hogar']['t']) ?></span><?php endif; ?>
    <?php if (!$cad && !$urg && !$mostrarAlerta && !isset($vivos['hogar'])): ?><span class="tb-estado e-ok">Todo en orden</span><?php endif; ?>
</div>
