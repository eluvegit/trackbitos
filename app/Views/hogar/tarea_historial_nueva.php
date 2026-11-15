<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — historial de una tarea (estilo fv): cifras, calendario de dos
 * meses y cada vez que se hizo (se puede borrar un registro equivocado).
 *
 * @var array       $tarea
 * @var array       $habitacion
 * @var array       $timeline [['id', 'fecha', 'intervalo'], ...] más reciente primero
 * @var array       $calendario [['etiqueta', 'semanas'], ...]
 * @var int         $totalVeces
 * @var float|null  $media, $minIntervalo, $maxIntervalo
 * @var string|null $primeraVez, $ultimaVez
 */
helper('recordatorio');
$f = (int) $tarea['frecuencia_dias'];
$dias = static fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
?>
<div class="fv rc estrecho">
    <?= view('hogar/_cabecera', [
        'migas'    => [$habitacion['nombre'] => site_url('hogar/' . $habitacion['id']), 'Historial' => null],
        'titulo'   => esc($tarea['nombre']),
        'sub'      => $f ? '<span><i class="bi bi-arrow-clockwise"></i> Cada ' . $f . ' ' . ($f === 1 ? 'día' : 'días') . '</span>' : '<span>Sin frecuencia</span>',
        'acciones' => '<a class="fv-ibtn" href="' . site_url('hogar/tareas/editar/' . $tarea['id']) . '" title="Editar"><i class="bi bi-pencil"></i></a>',
    ]) ?>

    <div class="rc-cifras">
        <div class="rc-cifra"><span class="k">Veces</span><span class="v"><?= $totalVeces ?></span><span class="s"><?= $primeraVez ? 'desde ' . rec_fecha($primeraVez) : 'Nunca hecha' ?></span></div>
        <div class="rc-cifra"><span class="k">Cada… de media</span><span class="v"><?= $dias($media) ?> <small>días</small></span><span class="s"><?= $media !== null ? 'entre ' . $dias($minIntervalo) . ' y ' . $dias($maxIntervalo) : 'Hace falta hacerla dos veces' ?></span></div>
        <div class="rc-cifra"><span class="k">Última vez</span><span class="v" style="font-size:18px;line-height:1.5"><?= $ultimaVez ? rec_hace($ultimaVez) : '—' ?></span><span class="s"><?= $ultimaVez ? rec_fecha($ultimaVez) : '' ?></span></div>
    </div>

    <div class="fv-panel hg-meses">
        <?php foreach ($calendario as $mes): ?>
            <div class="hg-mes">
                <h4><?= esc($mes['etiqueta']) ?></h4>
                <div class="hg-cal">
                    <?php foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $d): ?><span class="dow"><?= $d ?></span><?php endforeach; ?>
                    <?php foreach ($mes['semanas'] as $semana): foreach ($semana as $c): ?>
                        <?php if (!$c): ?><span class="vacio"></span><?php else: ?>
                            <span class="<?= trim(($c['veces'] ? 'si' : '') . ($c['esHoy'] ? ' hoy' : '')) ?>" title="<?= $c['veces'] ? $c['veces'] . ($c['veces'] === 1 ? ' vez' : ' veces') : '' ?>"><?= $c['dia'] ?></span>
                        <?php endif; ?>
                    <?php endforeach; endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($timeline): ?>
        <div class="rc-hist">
            <?php foreach ($timeline as $it): ?>
                <div class="rc-h">
                    <span class="rc-ico"><i class="bi bi-check2"></i></span>
                    <div class="rc-txt">
                        <span class="t"><?= rec_fecha($it['fecha']) ?> <small style="color:var(--fv-text-3);font-weight:400"><?= date('H:i', strtotime($it['fecha'])) ?></small></span>
                        <div class="rc-meta">
                            <span><?= rec_hace($it['fecha']) ?></span>
                            <?php if ($it['intervalo'] !== null): ?><span class="<?= $f && $it['intervalo'] > $f ? 'e-warn' : '' ?>"><i class="bi bi-arrow-left-right"></i><?= $dias($it['intervalo']) ?> días desde la anterior</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="rc-btns">
                        <form method="post" action="<?= site_url('hogar/tareas/logs/' . $it['id'] . '/borrar') ?>" onsubmit="return confirm('¿Borrar este registro?')" style="margin:0">
                            <?= csrf_field() ?><button type="submit" class="fv-ibtn" title="Borrar registro"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="fv-panel rc-vacio">Todavía no se ha hecho nunca.</div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
