<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$c = $constancia;
$pendientes = array_values(array_filter($semana, fn ($g) => $g['pendiente']));
$volSemana = array_sum(array_column($semana, 'volumen'));
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'stats', 'titulo' => 'Estadísticas',
        'sub' => '<span>Récords con 1RM estimado (Epley)</span><span class="fv-dot"></span><span>volumen = series × reps × kg</span>']) ?>

    <div class="gm-cifras">
        <div class="gm-cifra acc"><span class="k"><i class="bi bi-fire"></i> Racha</span><span class="v"><?= $c['racha'] ?><small>sem</small></span><span class="s">semanas seguidas</span></div>
        <div class="gm-cifra"><span class="k">Esta semana</span><span class="v"><?= $c['esta_semana'] ?><small>sesiones</small></span><span class="s"><?= gim_num($volSemana) ?> kg de volumen</span></div>
        <div class="gm-cifra"><span class="k">Media</span><span class="v"><?= str_replace('.', ',', (string) $c['media8']) ?><small>/sem</small></span><span class="s">últimas 8 semanas</span></div>
        <div class="gm-cifra"><span class="k">En <?= date('Y') ?></span><span class="v"><?= $c['este_anio'] ?><small>sesiones</small></span><span class="s"><?= $c['ultima'] ? 'última ' . esc(gim_hace($c['ultima'])) : '' ?></span></div>
    </div>

    <div class="gm-grid2">
        <section class="fv-panel">
            <div class="fv-ph"><h3>Series por grupo · esta semana</h3><span class="cuenta">raya = media 4 sem</span></div>
            <?php if (!$semana): ?>
                <p class="fv-vacio">Sin series en las últimas 5 semanas.</p>
            <?php else: ?>
                <?php $max = max(1, ...array_map(fn ($g) => max($g['series'], $g['media']), $semana)); ?>
                <div class="gm-barras">
                    <?php foreach ($semana as $g): ?>
                        <div class="gm-barra<?= $g['pendiente'] ? ' pend' : '' ?>" title="<?= esc($g['nombre']) ?>: <?= $g['series'] ?> series esta semana, media <?= $g['media'] ?>">
                            <span class="nm"><?= esc($g['nombre']) ?></span>
                            <span class="pista"><i style="width:<?= round($g['series'] / $max * 100) ?>%"></i><b style="left:<?= round($g['media'] / $max * 100) ?>%"></b></span>
                            <span class="vl"><?= $g['series'] ?> / <?= str_replace('.', ',', (string) $g['media']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($pendientes): ?>
                    <p class="gm-ayuda" style="color:var(--fv-warn)"><i class="bi bi-exclamation-circle"></i> Sueles trabajar <?= esc(implode(', ', array_column($pendientes, 'nombre'))) ?> y esta semana aún no.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="fv-panel">
            <div class="fv-ph"><h3>Últimos 6 meses</h3><a class="fv-link der" href="<?= site_url('gimnasio/entrenamientos') ?>">Entrenos <i class="bi bi-chevron-right"></i></a></div>
            <?= gim_heatmap($dias, 26) ?>
            <div class="gm-leyenda">
                <?php foreach (gim_tipos() as $t => $col): ?><span><i style="--c:<?= $col ?>"></i><?= esc($t) ?></span><?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="fv-ph"><h3 style="font-size:15px;color:var(--fv-text)">Principales</h3><a class="fv-link der" href="<?= site_url('gimnasio/ejercicios') ?>">Elegir con <i class="bi bi-star-fill" style="color:var(--fv-warn)"></i> en Ejercicios</a></div>

    <?php if (!$principales): ?>
        <div class="gm-vacio"><i class="bi bi-star"></i>No tienes ejercicios principales. Márcalos con la estrella en <a href="<?= site_url('gimnasio/ejercicios') ?>" style="color:var(--fv-accent)">Ejercicios</a> o en su ficha.</div>
    <?php endif; ?>

    <div class="gm-grid2">
        <?php foreach ($principales as $p): ?>
            <?php $prog = $p['prog']; $pr = $prog['pr']; $ul = $prog['ultimo']; ?>
            <section class="fv-panel gm-princ">
                <div class="gm-princ-h">
                    <h3><?= esc($p['nombre']) ?></h3>
                    <a class="fv-link" href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $p['id']) ?>">Historial <i class="bi bi-chevron-right"></i></a>
                </div>
                <?php if (!$pr): ?>
                    <p class="fv-vacio">Sin series con peso todavía.</p>
                <?php else: ?>
                    <?php $diff = round($ul['e1rm'] - $pr['e1rm'], 1); ?>
                    <div class="gm-dos">
                        <div><span class="k"><i class="bi bi-trophy-fill gm-pr"></i> Récord</span><span class="v"><?= gim_kg($pr['e1rm']) ?><small>kg</small></span><span class="s"><?= gim_kg($pr['peso']) ?> × <?= $pr['reps'] ?> · <?= esc(gim_fecha_corta($pr['fecha'], false)) ?></span></div>
                        <div><span class="k">Última</span><span class="v"><?= gim_kg($ul['e1rm']) ?><small>kg</small></span>
                            <span class="s"><?= gim_kg($ul['peso']) ?> × <?= $ul['reps'] ?> · <?= esc(gim_hace($ul['fecha'])) ?>
                                <?php if ($diff == 0): ?><span class="gm-delta ok">= récord</span><?php else: ?><span class="gm-delta mal"><?= str_replace('.', ',', (string) $diff) ?></span><?php endif; ?></span></div>
                    </div>
                    <?= gim_linea(array_slice($prog['progresion'], -30), 'e1rm', $pr['fecha'], 640, 150) ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
