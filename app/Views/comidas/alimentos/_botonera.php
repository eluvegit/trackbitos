<div class="d-flex gap-2 flex-wrap">
  <div class="dropdown">
    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-trophy"></i> Mejores alimentos
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="<?= site_url('comidas/alimentos/ranking/proteinas') ?>">Top 100 · Más proteínas</a></li>
      <li><a class="dropdown-item" href="<?= site_url('comidas/alimentos/ranking/carbohidratos') ?>">Top 100 · Menos carbohidratos</a></li>
      <li><a class="dropdown-item" href="<?= site_url('comidas/alimentos/ranking/grasas') ?>">Top 100 · Menos grasas</a></li>
      <li><a class="dropdown-item" href="<?= site_url('comidas/alimentos/ranking/kcal') ?>">Top 100 · Menos calorías</a></li>
    </ul>
  </div>

  <div class="dropdown">
    <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="bi bi-bar-chart"></i> Consumo
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="<?= site_url('comidas/alimentos/ranking/mas-consumidos') ?>">Top 100 · Más consumidos</a></li>
      <li><a class="dropdown-item" href="<?= site_url('comidas/alimentos/ranking/menos-consumidos') ?>">Top 100 · Menos consumidos</a></li>
    </ul>
  </div>

  <a class="btn btn-primary" href="<?= site_url('comidas/alimentos/create') ?>">Nuevo</a>
</div>
