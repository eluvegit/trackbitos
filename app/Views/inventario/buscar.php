<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Buscar<?= $this->endSection() ?>

<?= $this->section('cabecera') ?>
<div class="inv-buscador" data-buscador>
    <i class="bi bi-search"></i>
    <input type="search" placeholder="Nombre o hueco (E1H2)…" value="<?= esc($q, 'attr') ?>"
        autocomplete="off" enterkeyhint="search" data-buscar>
    <button type="button" class="inv-limpiar" data-limpiar aria-label="Borrar" hidden><i class="bi bi-x-circle-fill"></i></button>
</div>
<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<?= view('inventario/_chips', ['categorias' => $categorias, 'modo' => 'filtro', 'activa' => $cat]) ?>

<div class="inv-contador-fila">
    <p class="inv-contador" data-contador></p>
    <button type="button" class="inv-chip inv-interruptor <?= $soloStock ? 'activo' : '' ?>" data-solo-stock aria-pressed="<?= $soloStock ? 'true' : 'false' ?>">
        <i class="bi bi-check2-circle"></i> Solo con stock
    </button>
</div>

<div class="inv-piezas" data-resultados>
    <?php foreach ($piezas as $p): ?>
        <?= view('inventario/_pieza', ['p' => $p]) ?>
    <?php endforeach; ?>
</div>

<div class="inv-vacio" data-vacio hidden>
    <i class="bi bi-emoji-frown"></i>
    <p>No hay ninguna pieza que coincida.</p>
</div>
<?= $this->endSection() ?>
