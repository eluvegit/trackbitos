<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'plantillas', 'titulo' => 'Plantillas',
        'sub' => '<span>Rutinas que repites: se aplican a una sesión con «Reutilizar»</span>']) ?>

    <form method="post" action="<?= site_url('gimnasio/plantillas/crear') ?>" class="gm-nueva">
        <input type="text" name="nombre" class="fv-input" style="height:40px" placeholder="Nueva plantilla (Empuje A, Full body…)" required autocomplete="off">
        <button class="fv-btn fv-btn-pri" style="height:40px"><i class="bi bi-plus-lg"></i> Crear</button>
    </form>

    <?php if (!$plantillas): ?>
        <div class="gm-vacio"><i class="bi bi-collection"></i>Todavía no tienes plantillas. Crea una arriba o guarda una sesión como plantilla desde su menú <i class="bi bi-three-dots" style="display:inline;font-size:inherit"></i>.</div>
    <?php else: ?>
        <section class="fv-panel" style="padding:8px 14px">
            <div class="gm-filas">
                <?php foreach ($plantillas as $p): ?>
                    <div class="gm-fila">
                        <a class="pp" href="<?= site_url('gimnasio/plantillas/editar/' . $p['id']) ?>">
                            <span class="t"><?= esc($p['nombre']) ?></span>
                            <span class="d"><?= $p['_nombres'] ? esc(implode(' · ', $p['_nombres'])) : 'Vacía' ?></span>
                            <?php if (!empty($p['notas'])): ?><span class="d" style="font-style:italic"><?= esc($p['notas']) ?></span><?php endif; ?>
                        </a>
                        <span class="n"><?= (int) $p['num_ejercicios'] ?> ej.</span>
                        <span class="acc">
                            <a class="fv-ibtn" href="<?= site_url('gimnasio/plantillas/editar/' . $p['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= site_url('gimnasio/plantillas/eliminar/' . $p['id']) ?>" data-confirm="¿Eliminar la plantilla «<?= esc($p['nombre']) ?>»?">
                                <button class="fv-ibtn" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
