<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$c = $constancia;
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$pendientes = array_values(array_filter($semana, fn ($g) => $g['pendiente']));
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'inicio', 'titulo' => 'Gimnasio',
        'sub' => $c['ultima'] ? '<span>Último entreno ' . esc(gim_hace($c['ultima'])) . '</span><span class="fv-dot"></span><span>' . $c['total'] . ' sesiones registradas</span>' : '']) ?>

    <?php if ($hoy): ?>
        <a class="gm-cta" href="<?= site_url('gimnasio/entrenamientos/registro/' . $hoy['id']) ?>">
            <i class="bi bi-play-circle-fill"></i>
            <div><b>Seguir con el entreno de hoy</b><small><?= $hoy['ejercicios'] ?> ejercicios · <?= $hoy['series'] ?> series<?= $hoy['tipo'] ? ' · ' . esc($hoy['tipo']) : '' ?></small></div>
            <i class="bi bi-chevron-right der"></i>
        </a>
    <?php else: ?>
        <form method="post" action="<?= site_url('gimnasio/entrenamientos/crear') ?>">
            <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
            <button class="gm-cta">
                <i class="bi bi-plus-circle-fill"></i>
                <div><b>Empezar el entreno de hoy</b><small><?= esc(ucfirst(gim_fecha_corta(date('Y-m-d')))) ?><?= $c['esta_semana'] ? ' · ' . $c['esta_semana'] . ' ya esta semana' : '' ?></small></div>
                <i class="bi bi-chevron-right der"></i>
            </button>
        </form>
    <?php endif; ?>

    <div class="gm-cifras">
        <div class="gm-cifra acc">
            <span class="k"><i class="bi bi-fire"></i> Racha</span>
            <span class="v"><?= $c['racha'] ?><small><?= $c['racha'] === 1 ? 'semana' : 'semanas' ?></small></span>
            <span class="s">seguidas entrenando</span>
        </div>
        <div class="gm-cifra">
            <span class="k">Esta semana</span>
            <span class="v"><?= $c['esta_semana'] ?><small>sesiones</small></span>
            <span class="s">media <?= str_replace('.', ',', (string) $c['media8']) ?>/sem (8 sem)</span>
        </div>
        <div class="gm-cifra">
            <span class="k">En <?= date('Y') ?></span>
            <span class="v"><?= $c['este_anio'] ?><small>sesiones</small></span>
            <span class="s"><?= $c['total'] ?> en total</span>
        </div>
    </div>

    <section class="fv-panel">
        <div class="fv-ph"><h3>Último año</h3><a class="fv-link der" href="<?= site_url('gimnasio/entrenamientos') ?>">Todos los entrenos <i class="bi bi-chevron-right"></i></a></div>
        <?= gim_heatmap($dias, 53) ?>
        <div class="gm-leyenda">
            <?php foreach (gim_tipos() as $t => $col): ?><span><i style="--c:<?= $col ?>"></i><?= esc($t) ?></span><?php endforeach; ?>
            <span><i style="--c:#71717a"></i>Sin tipo</span>
        </div>
    </section>

    <div class="gm-grid2">
        <section class="fv-panel">
            <div class="fv-ph"><h3>Esta semana por grupo</h3><span class="cuenta">series · raya = media 4 sem</span></div>
            <?php if (!$semana): ?>
                <p class="fv-vacio">Sin series en las últimas 5 semanas.</p>
            <?php else: ?>
                <?php $max = max(1, ...array_map(fn ($g) => max($g['series'], $g['media']), $semana)); ?>
                <div class="gm-barras">
                    <?php foreach (array_slice($semana, 0, 8) as $g): ?>
                        <div class="gm-barra<?= $g['pendiente'] ? ' pend' : '' ?>">
                            <span class="nm"><?= esc($g['nombre']) ?></span>
                            <span class="pista"><i style="width:<?= round($g['series'] / $max * 100) ?>%"></i><b style="left:<?= round($g['media'] / $max * 100) ?>%" title="Media: <?= $g['media'] ?>"></b></span>
                            <span class="vl"><?= $g['series'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($pendientes): ?>
                    <p class="gm-ayuda" style="color:var(--fv-warn)"><i class="bi bi-exclamation-circle"></i> Sin tocar esta semana: <?= esc(implode(', ', array_column($pendientes, 'nombre'))) ?>.</p>
                <?php endif; ?>
            <?php endif; ?>
            <a class="fv-link" href="<?= site_url('gimnasio/ejercicios/principales') ?>">Estadísticas <i class="bi bi-chevron-right"></i></a>
        </section>

        <div style="display:grid;gap:20px">
            <?php if ($ultima): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Última sesión</h3><span class="cuenta"><?= esc(gim_hace($ultima['fecha'])) ?></span></div>
                    <a class="gm-fila" href="<?= site_url('gimnasio/entrenamientos/registro/' . $ultima['id']) ?>" style="padding:0">
                        <span class="gm-fecha"><b><?= date('j', strtotime($ultima['fecha'])) ?></b><small><?= gim_mes_corto($ultima['fecha']) ?></small></span>
                        <span class="pp">
                            <span class="t"><?= esc(ucfirst(gim_fecha_corta($ultima['fecha']))) ?><?php if ($ultima['tipo_sesion']): ?><span class="gm-tipo" style="--c:<?= gim_tipo_color($ultima['tipo_sesion']) ?>"><?= esc($ultima['tipo_sesion']) ?></span><?php endif; ?></span>
                            <span class="d"><?= esc(implode(' · ', $ultima['_nombres']) ?: 'Sin ejercicios') ?></span>
                        </span>
                        <?php if (!empty($ultima['_dia']['volumen'])): ?><span class="n"><?= gim_num($ultima['_dia']['volumen']) ?> kg</span><?php endif; ?>
                    </a>
                </section>
            <?php endif; ?>

            <?php if ($siguienteBloque): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Mesociclo · toca</h3><a class="fv-link der" href="<?= site_url('gimnasio/mesociclos/' . $siguienteBloque['plan_id']) ?>"><?= esc($siguienteBloque['_plan']) ?> <i class="bi bi-chevron-right"></i></a></div>
                    <div class="gm-dos">
                        <div><span class="k"><?= esc(ucfirst($siguienteBloque['_ejercicio'])) ?> · #<?= (int) $siguienteBloque['orden'] ?></span><span class="v"><?= gim_kg($siguienteBloque['_top']) ?><small>kg</small></span><span class="s">top × <?= (int) $siguienteBloque['top_reps_min'] ?><?= $siguienteBloque['top_reps_max'] ? '–' . (int) $siguienteBloque['top_reps_max'] : '' ?> reps</span></div>
                        <div><span class="k">Back-off</span><span class="v"><?= (int) $siguienteBloque['bo_sets'] ?>×<?= (int) $siguienteBloque['bo_reps'] ?></span><span class="s"><?= esc($siguienteBloque['notas'] ?: $siguienteBloque['bloque_tipo']) ?></span></div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($principales): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Récords</h3><span class="cuenta">1RM estimado</span></div>
                    <div class="gm-filas">
                        <?php foreach ($principales as $p): $pr = $p['prog']['pr']; $ul = $p['prog']['ultimo']; ?>
                            <a class="gm-fila" href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $p['id']) ?>">
                                <span class="pp"><span class="t"><?= esc($p['nombre']) ?></span>
                                    <span class="d"><?= $ul ? 'Última: ' . gim_kg($ul['peso']) . ' kg × ' . $ul['reps'] . ' · ' . esc(gim_hace($ul['fecha'])) : 'Sin series con peso' ?></span></span>
                                <?php if ($pr): ?><span class="n"><i class="bi bi-trophy-fill gm-pr"></i> <?= gim_kg($pr['e1rm']) ?> kg</span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <div class="gm-grid3">
        <a class="gm-acceso" href="<?= site_url('gimnasio/ejercicios') ?>"><i class="bi bi-list-ul"></i><div><b>Ejercicios</b><small>Tus ejercicios por grupo</small></div></a>
        <a class="gm-acceso" href="<?= site_url('gimnasio/plantillas') ?>"><i class="bi bi-collection"></i><div><b>Plantillas</b><small>Rutinas que repites</small></div></a>
        <a class="gm-acceso" href="<?= site_url('gimnasio/mesociclos') ?>"><i class="bi bi-diagram-3"></i><div><b>Mesociclos</b><small>Progresión por bloques</small></div></a>
        <a class="gm-acceso" href="<?= site_url('gimnasio/catalogo') ?>"><i class="bi bi-play-btn"></i><div><b>Catálogo</b><small>1.300+ con animación</small></div></a>
        <a class="gm-acceso" href="<?= site_url('gimnasio/ejercicios/principales') ?>"><i class="bi bi-graph-up"></i><div><b>Estadísticas</b><small>Récords y volumen</small></div></a>
        <a class="gm-acceso" href="<?= site_url('gimnasio/entrenamientos') ?>"><i class="bi bi-calendar3"></i><div><b>Entrenos</b><small>Historial completo</small></div></a>
    </div>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
