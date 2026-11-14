<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('lectura');
$js = base_url('assets/js/lectura.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/lectura.js') ?: 1);
$estadosUi = lec_estados();
$leyendoN = $conteos['leyendo'] ?? 0;
$totalLibros = array_sum($conteos);

$sub = [];
if ($leyendoN) $sub[] = '<span>' . $leyendoN . ' en curso</span>';
$sub[] = '<span>' . $terminados . ' ' . ($terminados === 1 ? 'leído' : 'leídos') . ' en ' . $anio . '</span>';
if ($totalLibros) $sub[] = '<span>' . $totalLibros . ' en la biblioteca</span>';
?>
<div class="fv lc">
    <?= view('reading/_cabecera', [
        'titulo'   => 'Lectura',
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('reading/nuevo') . '"><i class="bi bi-plus-lg"></i>Añadir libro</a>',
    ]) ?>

    <div class="lc-cifras">
        <div class="lc-cifra acc">
            <span class="k">Leídos en <?= $anio ?>
                <button type="button" class="fv-ibtn" data-lc-meta-toggle title="<?= $meta ? 'Cambiar la meta' : 'Ponerte una meta (opcional)' ?>"><i class="bi bi-flag"></i></button>
            </span>
            <span class="v"><?= $terminados ?><?php if ($meta): ?><small>de <?= $meta ?></small><?php endif; ?></span>
            <?php if ($meta): ?>
                <div class="lc-barra<?= $terminados >= $meta ? ' ok' : '' ?>"><i style="width:<?= min(100, round($terminados / $meta * 100)) ?>%"></i></div>
            <?php else: ?>
                <span class="s">sin meta, y está bien así</span>
            <?php endif; ?>
            <form class="lc-meta-form" method="post" action="<?= site_url('reading/meta') ?>" hidden data-lc-meta>
                <input type="hidden" name="tab" value="<?= esc($tabActual, 'attr') ?>">
                <input class="fv-input" type="number" name="target_books" min="0" max="999" value="<?= $meta ?: '' ?>" placeholder="nº">
                <button class="fv-btn fv-btn-sm">Guardar</button>
            </form>
        </div>
        <div class="lc-cifra">
            <span class="k"><i class="bi bi-clock-history"></i>Última vez que leíste</span>
            <?php if ($ultimaVez): ?>
                <?php $hace = (int) round((strtotime(date('Y-m-d')) - strtotime($ultimaVez['fecha'])) / 86400); ?>
                <span class="v"><?= $hace === 0 ? 'Hoy' : ($hace === 1 ? 'Ayer' : $hace . '<small>' . 'días</small>') ?></span>
                <span class="s"><?= $hace > 1 ? esc(lec_ultima_vez($ultimaVez['fecha'])) . ' · ' : '' ?><a href="<?= site_url('reading/libro/' . (int) $ultimaVez['book_id']) ?>"><?= esc($ultimaVez['title']) ?></a></span>
            <?php else: ?>
                <span class="v">—</span>
                <span class="s">aún no hay sesiones</span>
            <?php endif; ?>
        </div>
        <div class="lc-cifra">
            <span class="k">Últimos 30 días</span>
            <span class="v"><?= $global['tocados'] ?><small><?= $global['tocados'] === 1 ? 'día' : 'días' ?> leyendo</small></span>
            <?= lec_puntos($global['ventana']) ?>
        </div>
    </div>

    <nav class="lc-nav">
        <?php foreach ($estadosUi as $valor => [$ico, , $pestana]): ?>
            <a href="<?= site_url('reading') . '?tab=' . $valor ?>" class="<?= $valor === $tabActual ? 'on' : '' ?>">
                <i class="bi <?= $ico ?>"></i><?= $pestana ?><?php if (!empty($conteos[$valor])): ?><small><?= $conteos[$valor] ?></small><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (empty($libros)): ?>
        <section class="fv-panel">
            <p class="fv-vacio">
                <?= match ($tabActual) {
                    'leyendo'     => 'Ningún libro en curso. Elige uno de «Por leer» o añade uno nuevo.',
                    'quiero_leer' => 'La lista está vacía. Añade el primero con «Añadir libro».',
                    'terminado'   => 'Aún no hay libros terminados.',
                    default       => 'No hay libros aquí.',
                } ?>
            </p>
        </section>

    <?php elseif ($tabActual === 'leyendo'): ?>
        <div class="lc-actuales">
            <?php foreach ($libros as $libro): ?>
                <?php
                $id = (int) $libro['id'];
                $base = site_url('reading/libro/' . $id);
                $hoy = $libro['hoy'];
                ?>
                <article class="lc-actual" data-lc-libro>
                    <?= lec_portada($libro, 'a', 'href="' . $base . '"') ?>
                    <div class="pp">
                        <a class="t" href="<?= $base ?>"><?= esc($libro['title']) ?></a>
                        <?php if (!empty($libro['author'])): ?><div class="d"><?= esc($libro['author']) ?></div><?php endif; ?>
                        <div class="lc-ultima" data-lc-ultima>
                            <i class="bi bi-clock-history"></i>
                            <?php if ($libro['ultima']): ?>Última vez leído <b><?= esc(lec_ultima_vez($libro['ultima'])) ?></b><?php else: ?>Aún sin sesiones<?php endif; ?>
                        </div>

                        <?php if ($libro['progreso'] !== null): ?>
                            <div class="lc-barra"><i data-lc-fill style="width:<?= (int) $libro['progreso'] ?>%"></i></div>
                        <?php endif; ?>
                        <label class="lc-pag">
                            pág.
                            <input class="fv-input" type="number" inputmode="numeric" min="0"
                                   <?= !empty($libro['total_pages']) ? 'max="' . (int) $libro['total_pages'] . '"' : '' ?>
                                   value="<?= (int) $libro['current_page'] ?>" data-lc-pagina="<?= $base ?>/pagina">
                            <?php if (!empty($libro['total_pages'])): ?>de <?= (int) $libro['total_pages'] ?><?php endif; ?>
                            <i class="bi bi-check2 lc-ok"></i>
                            <?php if ($libro['progreso'] !== null): ?><b data-lc-pct><?= (int) $libro['progreso'] ?>%</b><?php endif; ?>
                        </label>

                        <?= lec_puntos($libro['constancia']['ventana']) ?>

                        <div class="lc-hoy" data-lc-hoy>
                            <?php if (!$hoy): ?>
                                <button type="button" class="fv-btn fv-btn-sm fv-btn-pri" data-lc-rapido="<?= $base ?>/sesion"><i class="bi bi-check-lg"></i>Hoy he leído</button>
                                <button type="button" class="fv-btn fv-btn-sm" data-lc-notoca="<?= $base ?>/hoy-no-toca">Hoy no toca</button>
                            <?php elseif ($hoy['skipped']): ?>
                                <span class="lc-hecho neu"><i class="bi bi-moon"></i>Hoy no toca</span>
                            <?php else: ?>
                                <span class="lc-hecho"><i class="bi bi-check-circle-fill"></i>Leído hoy</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <?php if (count($libros) > 8): ?>
            <div class="lc-filtro">
                <input class="fv-input" type="search" placeholder="Filtrar por título o autor…" data-lc-filtro>
            </div>
        <?php endif; ?>
        <div class="lc-estante">
            <?php foreach ($libros as $libro): ?>
                <a class="lc-libro" href="<?= site_url('reading/libro/' . (int) $libro['id']) ?>"
                   data-lc-busca="<?= esc(($libro['title'] ?? '') . ' ' . ($libro['author'] ?? ''), 'attr') ?>">
                    <?= lec_portada($libro) ?>
                    <span class="t"><?= esc($libro['title']) ?></span>
                    <?php if (!empty($libro['author'])): ?><span class="d"><?= esc($libro['author']) ?></span><?php endif; ?>
                    <?php if ($tabActual === 'terminado'): ?>
                        <span class="m"><?= lec_estrellas($libro['rating'] !== null ? (int) $libro['rating'] : null) ?><?= esc(lec_fecha($libro['finished_at'])) ?></span>
                    <?php elseif ($libro['progreso']): ?>
                        <span class="m"><?= (int) $libro['progreso'] ?>% · pág. <?= (int) $libro['current_page'] ?></span>
                    <?php elseif (!empty($libro['total_pages'])): ?>
                        <span class="m"><?= (int) $libro['total_pages'] ?> págs.</span>
                    <?php endif; ?>
                    <?php if ($libro['ultima'] && in_array($tabActual, ['pausado', 'abandonado'], true)): ?>
                        <span class="m"><i class="bi bi-clock-history"></i><?= esc(lec_ultima_vez($libro['ultima'])) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
