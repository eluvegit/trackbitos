<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
  .tipo-card{
    cursor:pointer;
    border:2px solid #dee2e6;
    border-radius:1rem;
    padding:1rem;
    text-align:center;
    font-weight:600;
    transition:all .15s ease-in-out;
    user-select:none;
  }
  .tipo-card:hover{
    border-color:#0d6efd;
    background:#e7f1ff;
    transform:translateY(-1px);
  }
  .tipo-card .icon{
    font-size:1.4rem;
    display:block;
    margin-bottom:.25rem;
  }
</style>

<div class="container my-3 text-center">
  <h4>Selecciona el tipo de comida</h4>
  <p class="text-muted">Día: <?= $fechaSel->format('d/m/Y') ?></p>

  <div class="row g-1 mb-3" id="tipoSelector">
    <?php
      // Etiquetas e iconos opcionales
      $labels = [
        'desayuno' => 'Desayuno',
        'almuerzo' => 'Almuerzo',
        'merienda' => 'Merienda',
        'cena'     => 'Cena',
        'nocturna' => 'Nocturna',
      ];
      $icons = [
        'desayuno' => '🍳',
        'almuerzo' => '🥪',
        'merienda' => '🍎',
        'cena'     => '🍽️',
        'nocturna' => '🌙',
      ];
      foreach ($tipos as $t):
        $label = $labels[$t] ?? ucfirst(str_replace('_',' ', $t));
        $icon  = $icons[$t]  ?? '🍽️';
        $href  = site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $t);
    ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= $href ?>" class="text-decoration-none text-reset">
          <div class="tipo-card p-2" data-tipo="<?= esc($t) ?>">
            <span class=""><?= $icon ?> <?= esc($label) ?></span>
            <span></span>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4">
    <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d')) ?>"
       class="btn btn-outline-secondary">⬅ Volver al resumen</a>
  </div>
</div>

<?= $this->endSection() ?>
