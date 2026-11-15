<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * YouTube — listas (estilo fv).
 *
 * @var array $listas con total, vistos, relevantes
 */
$total  = array_sum(array_map('intval', array_column($listas, 'total')));
$vistos = array_sum(array_map('intval', array_column($listas, 'vistos')));
$relev  = array_sum(array_map('intval', array_column($listas, 'relevantes')));
$pend   = $total - $vistos;
$pct    = $total ? (int) round($vistos * 100 / $total) : 0;

// Primero las que tienen algo pendiente; las terminadas, al final.
usort($listas, static function ($a, $b) {
    $pa = (int) $a['total'] - (int) $a['vistos'];
    $pb = (int) $b['total'] - (int) $b['vistos'];
    return [(int) ($pa === 0), $a['nombre']] <=> [(int) ($pb === 0), $b['nombre']];
});
$n = count($listas);
?>
<div class="fv rc">
    <?= view('youtube/_cabecera', [
        'titulo'   => 'YouTube',
        'sub'      => '<span>' . $n . ' ' . ($n === 1 ? 'lista' : 'listas') . '</span><span class="fv-dot"></span><span>' . $total . ' vídeos</span>'
                    . ($pend ? '<span class="fv-dot"></span><span class="e-warn">' . $pend . ' por ver</span>' : ''),
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('youtube/crear') . '"><i class="bi bi-plus-lg"></i>Nueva lista</a>',
    ]) ?>

    <?php if ($total): ?>
        <div class="rc-cifras">
            <div class="rc-cifra">
                <span class="k"><i class="bi bi-check-circle"></i>Vistos</span>
                <span class="v"><?= $vistos ?> <small>/ <?= $total ?></small></span>
                <div class="rc-barra"><i style="width:<?= $pct ?>%"></i></div>
            </div>
            <div class="rc-cifra">
                <span class="k"><i class="bi bi-hourglass-split"></i>Por ver</span>
                <span class="v"><?= $pend ?></span>
                <span class="s"><?= 100 - $pct ?>% del total</span>
            </div>
            <div class="rc-cifra">
                <span class="k"><i class="bi bi-star"></i>Relevantes</span>
                <span class="v"><?= $relev ?></span>
                <span class="s">marcados con estrella</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="yt-listas">
        <?php foreach ($listas as $l): ?>
            <?php
            $t = (int) $l['total'];
            $v = (int) $l['vistos'];
            $r = (int) $l['relevantes'];
            $p = $t ? (int) round($v * 100 / $t) : 0;
            $hecha = $t && $v === $t;
            ?>
            <a class="yt-lista<?= $hecha ? ' hecha' : '' ?>" href="<?= site_url('youtube/' . $l['slug']) ?>">
                <div class="yt-lista-cab">
                    <span class="rc-ico"><i class="bi <?= $hecha ? 'bi-check-lg' : 'bi-collection-play' ?>"></i></span>
                    <span class="n"><?= esc($l['nombre']) ?></span>
                    <span class="pct"><?= $t ? $p . '%' : '—' ?></span>
                </div>
                <div class="rc-barra"><i style="width:<?= $p ?>%"></i></div>
                <div class="s">
                    <span><?= $t ? $v . ' de ' . $t . ' vistos' : 'Sin vídeos' ?></span>
                    <?php if ($r): ?><span class="e-warn"><i class="bi bi-star-fill"></i> <?= $r ?></span><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <a class="yt-lista yt-lista-nueva" href="<?= site_url('youtube/crear') ?>"><i class="bi bi-plus-lg"></i>Nueva lista</a>
    </div>
</div>
<?= $this->endSection() ?>
