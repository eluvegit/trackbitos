<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('lectura');
$js = base_url('assets/js/lectura.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/lectura.js') ?: 1);
$estado = old('status') ?: 'quiero_leer';
?>
<div class="fv lc estrecho">
    <?= view('reading/_cabecera', ['migas' => ['Añadir libro' => null], 'titulo' => 'Añadir libro',
        'sub' => '<span>Solo hace falta el título. Lo demás, si quieres.</span>']) ?>

    <form class="fv-panel lc-form" action="<?= site_url('reading/crear') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <?= view('reading/_buscador', ['libro' => null]) ?>

        <div class="lc-campo">
            <label for="lcTitulo">Título</label>
            <input class="fv-input" id="lcTitulo" name="title" required maxlength="255" value="<?= esc(old('title') ?? '', 'attr') ?>">
        </div>
        <div class="lc-campo">
            <label for="lcAutor">Autor</label>
            <input class="fv-input" id="lcAutor" name="author" maxlength="255" value="<?= esc(old('author') ?? '', 'attr') ?>">
        </div>
        <div class="lc-fila2">
            <div class="lc-campo">
                <label for="lcTotal">Páginas totales</label>
                <input class="fv-input" id="lcTotal" name="total_pages" type="number" min="1" value="<?= esc(old('total_pages') ?? '', 'attr') ?>">
            </div>
            <div class="lc-campo">
                <label for="lcMinimo">Día satisfactorio (págs.)</label>
                <input class="fv-input" id="lcMinimo" name="min_goal_pages" type="number" min="1" value="<?= esc(old('min_goal_pages') ?? '1', 'attr') ?>">
            </div>
        </div>
        <p class="lc-ayuda" style="margin-top:-6px">Puede ser 1 página. En serio: aquí no hay mínimo «correcto».</p>

        <div class="lc-campo">
            <span class="lc-lbl">¿Dónde está ahora?</span>
            <div class="lc-seg">
                <label><input type="radio" name="status" value="quiero_leer" <?= $estado !== 'leyendo' ? 'checked' : '' ?>><span><i class="bi bi-bookmark"></i>Por leer</span></label>
                <label><input type="radio" name="status" value="leyendo" <?= $estado === 'leyendo' ? 'checked' : '' ?>><span><i class="bi bi-play-fill"></i>Ya lo estoy leyendo</span></label>
            </div>
        </div>

        <div class="lc-campo">
            <label for="lcAncla">¿A qué rutina lo enganchas?</label>
            <input class="fv-input" id="lcAncla" name="anchor_routine" maxlength="255" value="<?= esc(old('anchor_routine') ?? '', 'attr') ?>" placeholder="ej: gotas oftálmicas, café de la mañana, antes de dormir…">
            <p class="lc-ayuda">Engancharlo a un hueco fijo evita tener que decidir «¿leo hoy?» cada vez.</p>
        </div>

        <div class="lc-form-pie">
            <a class="fv-btn" href="<?= site_url('reading') ?>">Cancelar</a>
            <button type="submit" class="fv-btn fv-btn-pri">Añadir libro</button>
        </div>
    </form>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
