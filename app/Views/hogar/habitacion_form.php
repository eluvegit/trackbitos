<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">
    <i class="bi bi-house-heart text-primary"></i>
    <?= isset($habitacion) ? 'Editar habitación' : 'Nueva habitación' ?>
</h5>

<a href="<?= site_url('hogar') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<?php
$iconos = [
    'house'        => 'Genérico',
    'cup-hot'      => 'Cocina',
    'droplet-half' => 'Baño',
    'tv'           => 'Salón',
    'moon-stars'   => 'Dormitorio',
    'door-open'    => 'Entrada / Pasillo',
    'tree'         => 'Jardín / Terraza',
    'car-front'    => 'Garaje',
    'briefcase'    => 'Estudio / Oficina',
    'building'     => 'Otro',
];
$iconoActual = $habitacion['icono'] ?? 'house';
?>

<form method="post" action="<?= isset($habitacion)
    ? site_url('hogar/habitaciones/actualizar/' . $habitacion['id'])
    : site_url('hogar/habitaciones/crear') ?>" style="max-width: 420px;">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre de la habitación</label>
        <input type="text" class="form-control" name="nombre" id="nombre" required
               value="<?= esc($habitacion['nombre'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="icono" class="form-label">Icono</label>
        <select class="form-select" name="icono" id="icono">
            <?php foreach ($iconos as $value => $label): ?>
                <option value="<?= esc($value) ?>" <?= $iconoActual === $value ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text"><i id="icono-preview" class="bi bi-<?= esc($iconoActual) ?> fs-4 text-primary"></i></div>
    </div>

    <button type="submit" class="btn btn-primary">
        <?= isset($habitacion) ? 'Guardar cambios' : 'Crear habitación' ?>
    </button>
</form>

<script>
    document.getElementById('icono')?.addEventListener('change', (e) => {
        document.getElementById('icono-preview').className = 'bi bi-' + e.target.value + ' fs-4 text-primary';
    });
</script>

<?= $this->endSection() ?>
