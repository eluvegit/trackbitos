<?php
/**
 * Buscador de Open Library + portada (elegida o subida). Rellena los campos
 * title/author/total_pages/isbn/cover_url del formulario que lo contiene.
 *
 * @var array|null $libro  libro existente (ajustes) o null (alta)
 */
helper('lectura');
$libro ??= null;
$cover = $libro['cover_url'] ?? (old('cover_url') ?? '');
?>
<div class="lc-campo" data-lc-buscador="<?= site_url('reading/buscar') ?>">
    <label for="lcBuscar"><?= $libro ? 'Buscar portada y datos' : 'Buscar el libro' ?></label>
    <div class="fv-campo">
        <input class="fv-input" id="lcBuscar" type="search" placeholder="título o autor…" value="<?= esc($libro['title'] ?? '', 'attr') ?>" data-lc-q autocomplete="off">
        <button type="button" class="fv-btn" data-lc-buscar><i class="bi bi-search"></i><span>Buscar</span></button>
    </div>
    <div class="lc-resultados" data-lc-resultados hidden></div>

    <div class="lc-elegido" data-lc-elegido <?= $cover ? '' : 'hidden' ?>>
        <div class="lc-portada"><img src="<?= esc($cover, 'attr') ?>" alt="" data-lc-preview></div>
        <div style="display:grid;gap:6px">
            <span class="lc-ayuda">Portada</span>
            <button type="button" class="fv-btn fv-btn-sm" data-lc-quitar><i class="bi bi-x-lg"></i>Quitar</button>
        </div>
    </div>
    <label class="lc-subir">
        <i class="bi bi-upload"></i><span data-lc-subir-txt>¿No aparece? Sube tú la portada</span>
        <input type="file" name="cover_image" accept="image/*" data-lc-archivo>
    </label>
    <input type="hidden" name="cover_url" value="<?= esc($cover, 'attr') ?>">
    <input type="hidden" name="isbn" value="<?= esc($libro['isbn'] ?? (old('isbn') ?? ''), 'attr') ?>">
</div>
