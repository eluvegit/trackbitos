<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>


<?php
$fmt = fn($n, $d = 0) => rtrim(rtrim(number_format((float)$n, $d, '.', ''), '0'), '.');
$goal = fn($k) => (float)($objetivos[$k] ?? 0);
$val  = fn($k) => (float)($resumen[$k]   ?? 0);
$clamp = fn($p) => max(0, min(100, $p));

$kcal = $val('kcal');
$kcalGoal = $goal('kcal');
$c = $val('carbohidratos_g');
$cGoal = $goal('carbohidratos_g');
$f = $val('grasas_g');
$fGoal = $goal('grasas_g');
$p = $val('proteina_g');
$pGoal = $goal('proteina_g');

// % de kcal por macro (4/9 kcal)
$kcC = $c * 4;
$kcF = $f * 9;
$kcP = $p * 4;
$kcTot = max(1, $kcC + $kcF + $kcP);
$pcC = $kcC / $kcTot * 100;
$pcF = $kcF / $kcTot * 100;
$pcP = $kcP / $kcTot * 100;

// % progreso frente a objetivo
$prog = [
  'kcal' => $clamp($kcalGoal ? ($kcal / $kcalGoal * 100) : 0),
  'c'    => $clamp($cGoal ? ($c / $cGoal * 100) : 0),
  'f'    => $clamp($fGoal ? ($f / $fGoal * 100) : 0),
  'p'    => $clamp($pGoal ? ($p / $pGoal * 100) : 0),
];

// “Remaining”
$rem = [
  'c' => max(0, $cGoal - $c),
  'f' => max(0, $fGoal - $f),
  'p' => max(0, $pGoal - $p),
  'k' => max(0, $kcalGoal - $kcal),
];
?>
<style>
  .ring {
    --p: 0;
    --c: #0d6efd;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: conic-gradient(var(--c) calc(var(--p)*1%), #e9ecef 0);
    position: relative
  }

  .ring::after {
    content: "";
    position: absolute;
    inset: 10px;
    background: #fff;
    border-radius: 50%
  }

  .ring-inner {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1
  }

  .mini {
    width: 72px;
    height: 72px
  }

  .mini::after {
    inset: 8px
  }

  .goalbar {
    height: 6px;
    border-radius: 4px;
    background: #e9ecef;
    overflow: hidden
  }

  .goalbar>span {
    display: block;
    height: 100%
  }

  .legend-dot {
    width: .6rem;
    height: .6rem;
    border-radius: 50%;
    display: inline-block;
    margin-right: .4rem
  }
</style>
<?php
$hoy = new DateTimeImmutable('today');
$fechaSel = $fechaSel ?? $hoy;

// rango: 7 días antes hasta +2 días
$dias = [];
for ($i = -7; $i <= 2; $i++) {
  $d = $hoy->modify("$i day");
  $dias[] = $d;
}
?>

<ul class="nav nav-pills my-3">
  <?php foreach ($dias as $d):
    $isActive = $d->format('Y-m-d') === $fechaSel->format('Y-m-d');
    $label = $d->format('D d/m'); // Ej: Sat 16/08
  ?>
    <li class="nav-item">
      <a class="nav-link <?= $isActive ? 'active' : '' ?>"
        href="<?= site_url('comidas/diario/' . $d->format('Y-m-d')) ?>">
        <?= $label ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="row">
  <!-- CARD 1 -->
  <div class="col-md-6  mb-3">

    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">Lo que llevas..</h5>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-3">

          <!-- Donut kcal -->
          <div class="ring" style="--p:<?= $fmt($prog['kcal'], 1) ?>;--c:#20c997">
            <div class="ring-inner">
              <div style="font-weight:700;font-size:1.25rem"><?= $fmt($kcal, 0) ?></div>
              <div class="text-muted" style="font-size:.9rem">cal</div>
            </div>
          </div>
          <!-- % breakdown -->
          <div class="flex-grow-1">
            <div class="row text-center g-2">
              <div class="col-4">
                <div style="color:#1abc9c;font-weight:700"><?= $fmt($pcC, 0) ?>%</div>
                <small class="text-muted"><?= $fmt($c, 0) ?>g Carbo</small>
              </div>
              <div class="col-4">
                <div style="color:#e74c3c;font-weight:700"><?= $fmt($pcF, 0) ?>%</div>
                <small class="text-muted"><?= $fmt($f, 0) ?>g Fat</small>
              </div>
              <div class="col-4">
                <div style="color:#9b59b6;font-weight:700"><?= $fmt($pcP, 0) ?>%</div>
                <small class="text-muted"><?= $fmt($p, 0) ?>g Protein</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- CARD 2 -->
  <div class="col-md-6  mb-3">
    <div class="card h-100">
      <div class="card-header">Te quedan..</div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-4">
          <div class="text-center">
            <div class="ring mini" style="--p:<?= $fmt($prog['c'], 1) ?>;--c:#1abc9c">
              <div class="ring-inner">
                <div style="font-weight:700"><?= $fmt($rem['c'], 0) ?></div>
                <div class="text-muted">g</div>
              </div>
            </div>
            <div class="mt-1 text-muted" style="font-size:.9rem">Carbo</div>
          </div>
          <div class="text-center">
            <div class="ring mini" style="--p:<?= $fmt($prog['f'], 1) ?>;--c:#e74c3c">
              <div class="ring-inner">
                <div style="font-weight:700"><?= $fmt($rem['f'], 0) ?></div>
                <div class="text-muted">g</div>
              </div>
            </div>
            <div class="mt-1 text-muted" style="font-size:.9rem">Fat</div>
          </div>
          <div class="text-center">
            <div class="ring mini" style="--p:<?= $fmt($prog['p'], 1) ?>;--c:#9b59b6">
              <div class="ring-inner">
                <div style="font-weight:700"><?= $fmt($rem['p'], 0) ?></div>
                <div class="text-muted">g</div>
              </div>
            </div>
            <div class="mt-1 text-muted" style="font-size:.9rem">Protein</div>
          </div>
          <div class="text-center">
            <div class="ring mini" style="--p:<?= $fmt($prog['kcal'], 1) ?>;--c:#20c997">
              <div class="ring-inner">
                <div style="font-weight:700"><?= $fmt($rem['k'], 0) ?></div>
                <div class="text-muted">kcal</div>
              </div>
            </div>
            <div class="mt-1 text-muted" style="font-size:.9rem">Calories</div>
          </div>
        </div>

        <!-- Steps -->
        <?php if (isset($steps, $stepsGoal)):
          $pctSteps = $clamp($stepsGoal ? ($steps / $stepsGoal * 100) : 0);
        ?>
          <div class="mt-3 text-muted small d-flex justify-content-between">
            <span><?= number_format($steps) ?> Fitbit Steps</span>
            <span><?= number_format($stepsGoal) ?></span>
          </div>
          <div class="goalbar mb-1">
            <span style="width:<?= $fmt($pctSteps, 0) ?>%;background:#6c757d"></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CARD 3 -->
  <div class="col-md-6  mb-3">
    <div class="card h-100">
      <div class="card-header">Percent of Daily Goals</div>
      <div class="card-body">
        <?php
        $rows = [
          ['Calories',     $prog['kcal'], '#20c997'],
          ['Carbohydrate', $prog['c'],    '#1abc9c'],
          ['Fat',          $prog['f'],    '#e74c3c'],
          ['Protein',      $prog['p'],    '#9b59b6'],
        ];
        ?>
        <?php foreach ($rows as [$label, $pct, $color]): ?>
          <div class="d-flex align-items-center mb-3">
            <div class="me-3" style="width:70px;color:#6c757d;font-size:.9rem"><?= esc($label) ?></div>
            <div class="flex-grow-1 goalbar">
              <span style="width:<?= $fmt($pct, 0) ?>%;background:<?= $color ?>"></span>
            </div>
            <div class="ms-3" style="width:42px;text-align:right;color:#6c757d"><?= $fmt($pct, 0) ?>%</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- CARD 4: Micros -->
  <div class="col-md-6  mb-3">
    <div class="card h-100">
      <div class="card-header">Otros datos clave</div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
          <?php foreach (['fibra_g' => 'Fibra (g)', 'sodio_mg' => 'Sodio (mg)', 'azucares_g' => 'Azúcares (g)'] as $k => $label): ?>
            <div>
              <small class="text-muted"><?= esc($label) ?></small>
              <div class="h5 mb-0"><?= esc($resumen[$k] ?? 0) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>



<h1 class="h3 mb-3 mt-5">Diario · Hoy</h1>
<?php $tipos = ['almuerzo' => 'Almuerzo', 'merienda' => 'Merienda', 'cena' => 'Cena', 'comida_nocturna' => 'Comida nocturna', 'desayuno' => 'Desayuno']; ?>
<ul class="nav nav-tabs my-3" id="tipoTabs" role="tablist">
  <?php foreach ($tipos as $k => $v): ?>
    <li class="nav-item" role="presentation">
      <!-- pestañas -->
      <button class="nav-link <?= ($tipo ?? '') === $k ? 'active' : '' ?>"
        onclick="location.href='<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $k) ?>'">
        <?= esc($v) ?>
      </button>
    </li>
  <?php endforeach; ?>
</ul>

<div class="row g-3">
  <!-- Columna izquierda -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Ingreso rápido</div>
      <div class="card-body">
        <!-- FORM DE BUSQUEDA (GET) -->
        <form class="row g-2 mb-3" method="get"
          action="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . ($tipo ?? 'almuerzo')) ?>">
          <input type="hidden" name="tipo" value="<?= esc($tipo ?? 'almuerzo') ?>">
          <div class="col-md-8">
            <label class="form-label">Buscar alimento</label>
            <input name="q" value="<?= esc($q ?? '') ?>" class="form-control" placeholder="Ej. Avena, Tortilla...">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100">Buscar</button>
          </div>
          <?php if (!empty($resultados)): ?>
            <input type="hidden" name="alimento_id" id="alimento_id_hidden"
              value="<?= esc($alimento_id ?? $resultados[0]['id']) ?>">
          <?php endif; ?>
        </form>


        <!-- FORM DE ALTA (POST) -->
        <form method="post"
          action="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . ($tipo ?? 'almuerzo')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="fecha" value="<?= esc($fechaSel->format('Y-m-d')) ?>">
          <input type="hidden" name="tipo" value="<?= esc($tipo ?? 'almuerzo') ?>">


          <?php if (session('errors')): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach (session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Selecciona alimento</label>
            <select name="item_id" id="selectItem" class="form-select" onchange="onItemChange()">
              <?php foreach (($resultados ?? []) as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ($alimento_id ?? 0) == $r['id'] ? 'selected' : '' ?>><?= esc($r['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Resultados filtrados por la búsqueda.</small>
          </div>

          <div id="linkPorcionesWrapper" class="<?= !empty($alimento_id) ? '' : 'd-none' ?> mb-3 text-end">
            <a id="linkPorciones" class="small" target="_blank"
              href="<?= !empty($alimento_id) ? site_url('comidas/porciones/alimento/' . intval($alimento_id)) : '#' ?>">
              Gestionar porciones de este alimento
            </a>
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="togglePorcion">
              <label class="form-check-label" for="togglePorcion">Usar porción habitual</label>
            </div>
          </div>

          <div id="cantidadGramos" class="row g-2">
            <div class="col-8 col-md-6">
              <label class="form-label">Cantidad (g)</label>
              <input type="number" step="1" min="1" name="cantidad_gramos" class="form-control" placeholder="Ej. 100">
            </div>
          </div>

          <div id="cantidadPorcion" class="row g-2 d-none">
            <div class="col-7">
              <label class="form-label">Porción</label>
              <select name="porcion_id" id="selectPorcion" class="form-select"></select>
            </div>
            <div class="col-5">
              <label class="form-label">Nº porciones</label>
              <input type="number" step="0.25" min="0.25" name="porciones" class="form-control" value="1">
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary">Añadir</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Columna derecha -->
  <div class="col-md-6">
    <?php
    $mapTitulos = [
      'almuerzo' => 'Almuerzo',
      'merienda' => 'Merienda',
      'cena' => 'Cena',
      'comida_nocturna' => 'Comida nocturna',
      'desayuno' => 'Desayuno',
    ];
    $mostroAlgo = false;
    ?>
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Diario por comidas</span>
        <small class="text-muted">Hoy</small>
      </div>
      <div class="card-body p-0">
        <?php foreach (($tiposLista ?? []) as $k):
          $lista = $ingPorTipo[$k] ?? [];
          $r = $resumenTipos[$k] ?? ['kcal' => 0, 'proteina_g' => 0, 'carbohidratos_g' => 0, 'grasas_g' => 0];
          $sinSubtotales = $r['kcal'] == 0 && $r['proteina_g'] == 0 && $r['carbohidratos_g'] == 0 && $r['grasas_g'] == 0;
          if (empty($lista) && $sinSubtotales) continue;
          $mostroAlgo = true;
        ?>

          <div class="p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0"><?= esc($mapTitulos[$k] ?? ucfirst($k)) ?></h5>
              <a class="btn btn-sm btn-outline-primary"
                href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $k) ?>">Añadir aquí</a>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="text-end small text-muted">
                <span class="me-3">kcal: <strong><?= esc($r['kcal'] ?? 0) ?></strong></span>
                <span class="me-3">Proteína: <strong><?= esc($r['proteina_g'] ?? 0) ?> g</strong></span>
                <span class="me-3">Carbs: <strong><?= esc($r['carbohidratos_g'] ?? 0) ?> g</strong></span>
                <span>Grasas: <strong><?= esc($r['grasas_g'] ?? 0) ?> g</strong></span>
              </div>
            </div>
            <?php if (!empty($lista)): ?>
              <ul class="list-group mb-2">
                <?php foreach ($lista as $i): ?>
                  <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <span><?= esc($i['nombre']) ?></span>
                        <span class="text-secondary ms-2"><?= esc($i['cantidad_label']) ?></span>
                      </div>
                      <a href="#" class="btn btn-sm btn-outline-danger">x</a> <!-- si luego quieres eliminar -->
                    </div>

                    <?php if (!empty($i['macros'])): ?>
                      <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark border"><?= esc($i['macros']['kcal'] ?? 0) ?> kcal</span>
                        <span class="badge bg-light text-dark border">P: <?= esc($i['macros']['proteina_g'] ?? 0) ?> g</span>
                        <span class="badge bg-light text-dark border">C: <?= esc($i['macros']['carbohidratos_g'] ?? 0) ?> g</span>
                        <span class="badge bg-light text-dark border">G: <?= esc($i['macros']['grasas_g'] ?? 0) ?> g</span>
                      </div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>


          </div>
        <?php endforeach; ?>

        <?php if (!$mostroAlgo): ?>
          <div class="p-3">
            <p class="text-muted mb-0">No hay ingestas registradas hoy.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<script>
  const toggle = document.getElementById('togglePorcion');
  const divG = document.getElementById('cantidadGramos');
  const divP = document.getElementById('cantidadPorcion');
  if (toggle && divG && divP) {
    toggle.addEventListener('change', () => {
      divG.classList.toggle('d-none', toggle.checked);
      divP.classList.toggle('d-none', !toggle.checked);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    onItemChange();
  });

  function onItemChange() {
    const sel = document.getElementById('selectItem');
    const alimentoId = sel?.value || null;
    const linkPorciones = document.getElementById('linkPorciones');
    const linkWrapper = document.getElementById('linkPorcionesWrapper');
    const selectPorcion = document.getElementById('selectPorcion');
    if (alimentoId && linkPorciones && linkWrapper) {
      linkPorciones.href = '<?= site_url('comidas/porciones/alimento') ?>/' + alimentoId;
      linkWrapper.classList.remove('d-none');
    } else if (linkWrapper) {
      linkWrapper.classList.add('d-none');
    }
    if (selectPorcion && alimentoId) {
      fetch('<?= site_url('comidas/diario/porciones') ?>/' + alimentoId)
        .then(r => r.json())
        .then(rows => {
          selectPorcion.innerHTML = '';
          if (!Array.isArray(rows) || rows.length === 0) {
            const opt = document.createElement('option');
            opt.text = 'Sin porciones registradas';
            opt.value = '';
            selectPorcion.add(opt);
            return;
          }
          rows.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.text = `${p.descripcion} (${p.gramos_equivalentes} g)`;
            selectPorcion.add(opt);
          });
        });
    }
  }
</script>
<?= $this->endSection(); ?>