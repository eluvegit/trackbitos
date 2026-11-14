<?php if ($mostrarAlerta): ?>
    <a class="tb-aviso warn" href="<?= site_url('lentillas') ?>"><i class="bi bi-eye"></i><div><b>Cambiar lentillas</b><br><?= $dias ?> días desde la última sustitución</div></a>
<?php endif; ?>
<?php foreach ($recordatoriosUrgentes as $r): ?>
    <a class="tb-aviso <?= $r['nivel'] === 'caducado' ? 'bad' : 'warn' ?>" href="<?= site_url('recordatorios') ?>"><i class="bi <?= $r['nivel'] === 'caducado' ? 'bi-calendar-x' : 'bi-calendar-event' ?>"></i><div><b><?= esc($r['titulo']) ?></b><br><?= esc($r['texto']) ?></div></a>
<?php endforeach; ?>
<?php if (!$mostrarAlerta && empty($recordatoriosUrgentes)): ?>
    <div class="tb-vacio">Nada pendiente.</div>
<?php endif; ?>
