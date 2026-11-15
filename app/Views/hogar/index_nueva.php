<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — habitaciones (estilo fv).
 *
 * @var array $habitaciones con total, hechas, atrasadas, pct
 * @var int   $totalPendientes
 */
$atrasadas = array_sum(array_column($habitaciones, 'atrasadas'));
$sub = ['<span>' . count($habitaciones) . ' ' . (count($habitaciones) === 1 ? 'habitación' : 'habitaciones') . '</span>'];
$sub[] = '<span>' . $totalPendientes . ' ' . ($totalPendientes === 1 ? 'tarea pendiente' : 'tareas pendientes') . '</span>';
if ($atrasadas) $sub[] = '<span class="e-warn">' . $atrasadas . ' ' . ($atrasadas === 1 ? 'toca ya' : 'tocan ya') . '</span>';
?>
<div class="fv rc">
    <?= view('hogar/_cabecera', [
        'activa'   => 'inicio',
        'titulo'   => 'Hogar',
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('hogar/pendientes') . '"><i class="bi bi-list-check"></i>Qué toca</a>',
    ]) ?>

    <div class="hg-habs">
        <?php foreach ($habitaciones as $h): ?>
            <?php
            $completa = $h['total'] && $h['hechas'] === $h['total'];
            $barra = $h['atrasadas'] ? 'warn' : '';
            ?>
            <a class="hg-hab<?= $completa ? ' completa' : '' ?>" href="<?= site_url('hogar/' . $h['id']) ?>">
                <div class="hg-hab-cab">
                    <span class="rc-ico"><i class="bi bi-<?= esc($h['icono'] ?: 'house') ?>"></i></span>
                    <span class="n"><?= esc($h['nombre']) ?></span>
                </div>
                <div class="rc-barra <?= $barra ?>"><i style="width:<?= (int) $h['pct'] ?>%"></i></div>
                <div class="s">
                    <span><?= $h['total'] ? $h['hechas'] . ' de ' . $h['total'] . ' hechas' : 'Sin tareas' ?></span>
                    <?php if ($h['atrasadas']): ?><span class="e-warn"><?= $h['atrasadas'] ?> <?= $h['atrasadas'] === 1 ? 'toca' : 'tocan' ?></span><?php elseif ($completa): ?><span class="e-ok">✓</span><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <a class="hg-hab hg-hab-nueva" href="<?= site_url('hogar/habitaciones/nueva') ?>"><i class="bi bi-plus-lg"></i>Nueva habitación</a>
    </div>
</div>
<?= $this->endSection() ?>
