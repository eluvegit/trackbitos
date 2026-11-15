<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Coche — resumen (estilo fv): lo que toca, lo último que se hizo y accesos.
 *
 * @var array      $avisos          recordatorios de categoría «vehiculo» con su estado
 * @var array      $ultimasAcciones últimas acciones del historial
 * @var int        $totalAcciones
 * @var array|null $ultimaAveria
 */
$pendientes = array_values(array_filter($avisos, static fn ($a) => in_array($a['nivel'], ['caducado', 'urgente'], true)));
$cad        = count(array_filter($avisos, static fn ($a) => $a['nivel'] === 'caducado'));
$siguiente  = null;
foreach ($avisos as $a) {
    if ($a['nivel'] !== 'sinfecha' && $a['dias'] > 0) { $siguiente = $a; break; }
}
$icono = static function (string $titulo): string {
    $t = mb_strtolower($titulo);
    return match (true) {
        str_contains($t, 'aceite')  => 'bi-droplet-half',
        str_contains($t, 'fuera')   => 'bi-stars',
        str_contains($t, 'dentro')  => 'bi-wind',
        str_contains($t, 'rueda')   => 'bi-circle',
        str_contains($t, 'itv')     => 'bi-patch-check',
        str_contains($t, 'bater')   => 'bi-battery-half',
        default                     => 'bi-wrench',
    };
};
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'   => 'coche',
        'activa'   => 'resumen',
        'titulo'   => 'Coche',
        'clasica'  => true,
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('coche/acciones/nueva') . '"><i class="bi bi-plus-lg"></i>Apuntar acción</a>',
    ]) ?>

    <div class="rc-cifras">
        <a class="rc-cifra" href="<?= site_url('coche/recordatorios') ?>">
            <span class="k"><i class="bi bi-bell"></i>Pendiente</span>
            <span class="v <?= $cad ? 'e-bad' : ($pendientes ? 'e-warn' : 'e-ok') ?>"><?= count($pendientes) ?: '✓' ?></span>
            <span class="s"><?= $pendientes ? ($cad ? $cad . ' ' . ($cad === 1 ? 'vencido' : 'vencidos') : 'en menos de un mes') : 'Todo al día' ?></span>
        </a>
        <a class="rc-cifra" href="<?= site_url('coche/recordatorios') ?>">
            <span class="k"><i class="bi bi-calendar3"></i>Lo siguiente</span>
            <span class="v" style="font-size:17px;line-height:1.5"><?= $siguiente ? esc($siguiente['titulo']) : '—' ?></span>
            <span class="s"><?= $siguiente ? esc($siguiente['texto']) . ' · ' . rec_fecha($siguiente['fecha_mostrar']) : 'Nada con fecha' ?></span>
        </a>
        <a class="rc-cifra" href="<?= site_url('coche/averias') ?>">
            <span class="k"><i class="bi bi-exclamation-triangle"></i>Última avería</span>
            <span class="v" style="font-size:17px;line-height:1.5"><?= $ultimaAveria ? rec_hace($ultimaAveria['date']) : 'Ninguna' ?></span>
            <span class="s"><?= $ultimaAveria ? rec_fecha($ultimaAveria['date']) : 'Sin averías apuntadas' ?></span>
        </a>
    </div>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Avisos</h3><span class="cuenta"><?= count($avisos) ?></span><a class="fv-link der" href="<?= site_url('coche/recordatorios') ?>">Ver todos <i class="bi bi-arrow-right"></i></a></div>
        <?= view('recordatorios/_lista', ['recordatorios' => $avisos, 'volver' => 'coche', 'limite' => 6, 'vacio' => 'No hay avisos del coche todavía.']) ?>
    </section>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Últimas acciones</h3><span class="cuenta"><?= (int) $totalAcciones ?></span><a class="fv-link der" href="<?= site_url('coche/acciones') ?>">Historial <i class="bi bi-arrow-right"></i></a></div>
        <?php if (!$ultimasAcciones): ?>
            <div class="fv-vacio rc-vacio">No hay acciones apuntadas todavía.</div>
        <?php else: ?>
            <div class="rc-hist">
                <?php foreach ($ultimasAcciones as $a): ?>
                    <div class="rc-h">
                        <div class="rc-ico"><i class="bi <?= $icono($a['title']) ?>"></i></div>
                        <div class="rc-txt">
                            <span class="t"><?= esc($a['title']) ?></span>
                            <div class="rc-meta">
                                <span><i class="bi bi-calendar3"></i><?= rec_fecha($a['date']) ?> · <?= rec_hace($a['date']) ?></span>
                                <?php if (!empty($a['kilometers'])): ?><span><i class="bi bi-speedometer"></i><?= number_format((int) $a['kilometers'], 0, ',', '.') ?> km</span><?php endif; ?>
                                <?php if (trim((string) $a['notes']) !== ''): ?><span><?= esc($a['notes']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="rc-btns"><a class="fv-ibtn" href="<?= site_url('coche/acciones/editar/' . $a['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?= $this->endSection() ?>
