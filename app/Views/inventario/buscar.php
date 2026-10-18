<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Buscar<?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<div class="inv-buscador" data-buscador>
    <i class="bi bi-search"></i>
    <input type="search" placeholder="Escribe el nombre de la pieza…" value="<?= esc($q, 'attr') ?>"
        autocomplete="off" data-buscar autofocus>
    <button type="button" class="inv-limpiar" data-limpiar aria-label="Borrar" hidden><i class="bi bi-x-circle-fill"></i></button>
    <div class="inv-sugerencias" data-sugerencias hidden></div>
</div>

<?= view('inventario/_chips', ['categorias' => $categorias, 'modo' => 'filtro', 'activa' => $cat]) ?>

<p class="inv-contador" data-contador></p>

<div class="inv-piezas" data-resultados>
    <?php foreach ($piezas as $p): ?>
        <?= view('inventario/_pieza', ['p' => $p]) ?>
    <?php endforeach; ?>
</div>

<div class="inv-vacio" data-vacio hidden>
    <i class="bi bi-emoji-frown"></i>
    <p>No hay ninguna pieza con ese nombre.</p>
</div>
<?= $this->endSection() ?>
