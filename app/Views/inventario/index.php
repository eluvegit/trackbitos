<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Inventario<?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<?= view('inventario/_chips', ['categorias' => $categorias, 'modo' => 'enlace']) ?>

<div class="inv-botones">
    <a href="<?= inventario_url('buscar') ?>" class="inv-boton">
        <span class="inv-boton-icono"><i class="bi bi-search"></i></span>
        <strong>Buscar</strong>
        <span>Encuentra una pieza por su nombre</span>
    </a>
    <a href="<?= inventario_url('categorias') ?>" class="inv-boton">
        <span class="inv-boton-icono"><i class="bi bi-grid"></i></span>
        <strong>Categorías</strong>
        <span><?= count($categorias) ?> categorías · <?= (int) $total ?> piezas</span>
    </a>
    <a href="<?= inventario_url('estuches') ?>" class="inv-boton">
        <span class="inv-boton-icono"><i class="bi bi-archive"></i></span>
        <strong>Estuches</strong>
        <span><?= (int) $estuches ?> estuches</span>
    </a>
</div>
<?= $this->endSection() ?>
