<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * @var array $proyectos
 * @var array $sesiones  [id => sesión]
 * @var array $progreso  [proyecto_id => ['total','hechas']]
 * @var array $portadas  [proyecto_id => ruta]
 */
$totalTomas = array_sum(array_column($progreso, 'total'));
$hechas     = array_sum(array_column($progreso, 'hechas'));
$sub = ['<span>' . count($proyectos) . ' ' . (count($proyectos) === 1 ? 'proyecto' : 'proyectos') . '</span>'];
if ($totalTomas) $sub[] = '<span>' . $hechas . ' de ' . $totalTomas . ' tomas hechas</span>';
?>
<div class="fv rd">
    <?= view('rodajes/_cabecera', [
        'titulo'   => 'Rodajes',
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('rodajes/create') . '"><i class="bi bi-plus-lg"></i>Nuevo proyecto</a>',
    ]) ?>

    <?php if (!$proyectos): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-camera-reels"></i>
            <div>Todavía no hay proyectos de rodaje.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url('rodajes/create') ?>"><i class="bi bi-plus-lg"></i>Crear el primero</a>
        </div>
    <?php else: ?>
        <div class="rd-proyectos">
            <?php foreach ($proyectos as $p): ?>
                <?php
                $id  = (int) $p['id'];
                $pr  = $progreso[$id] ?? ['total' => 0, 'hechas' => 0];
                $ses = !empty($p['sesion_id']) ? ($sesiones[(int) $p['sesion_id']] ?? null) : null;
                $img = $portadas[$id] ?? null;
                ?>
                <article class="rd-proy">
                    <a class="rd-proy-img<?= $img ? '' : ' sin' ?>" href="<?= site_url("rodajes/$id/escenas") ?>">
                        <?php if ($img): ?><img src="<?= base_url($img) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-film"></i><?php endif; ?>
                        <?php if (trim((string) $p['codigo']) !== ''): ?><span class="cod"><?= esc($p['codigo']) ?></span><?php endif; ?>
                    </a>
                    <div class="rd-proy-cuerpo">
                        <a class="t" href="<?= site_url("rodajes/$id/escenas") ?>"><?= esc($p['titulo']) ?></a>
                        <?php if ($ses): ?>
                            <div><a class="rd-ses" href="<?= site_url('sesiones/' . $ses['id']) ?>"><i class="bi bi-camera"></i><?= esc($ses['titulo']) ?></a></div>
                        <?php endif; ?>
                        <?php if (trim((string) $p['descripcion']) !== ''): ?><p class="d"><?= esc($p['descripcion']) ?></p><?php endif; ?>
                        <div class="rd-prog">
                            <?php if ($pr['total']): ?>
                                <div class="rd-barra"><i class="hecha" style="width:<?= round($pr['hechas'] / $pr['total'] * 100) ?>%"></i></div>
                                <span><?= $pr['hechas'] ?>/<?= $pr['total'] ?> tomas</span>
                            <?php else: ?>
                                <span>Sin escenas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="rd-proy-pie">
                        <a class="fv-btn fv-btn-pri fv-btn-sm" href="<?= site_url("rodajes/$id/plato") ?>"><i class="bi bi-phone"></i>Plató</a>
                        <a class="fv-ibtn" href="<?= site_url("rodajes/$id/escenas") ?>" title="Escenas"><i class="bi bi-list-ul"></i></a>
                        <a class="fv-ibtn" href="<?= site_url("rodajes/$id/escenas/storyboard") ?>" title="Storyboard"><i class="bi bi-layout-text-window-reverse"></i></a>
                        <a class="fv-ibtn" href="<?= site_url("rodajes/$id/escenas/ordenrodaje") ?>" title="Plan por clasificación"><i class="bi bi-sort-down"></i></a>
                        <a class="fv-ibtn" href="<?= site_url("rodajes/$id/dialogos") ?>" title="Diálogos"><i class="bi bi-chat-quote"></i></a>
                        <a class="fv-ibtn" href="<?= site_url("rodajes/edit/$id") ?>" title="Editar proyecto"><i class="bi bi-pencil"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
