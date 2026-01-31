<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content'); ?>

<div class="container mt-3">

    <div class="mb-4 d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Alimentos controlados</h4>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddControl">+</button>
    </div>

    <div class="row">
        <?php foreach ($alimentos_controlados as $alimento_id => $control): ?>
            <?php
            $alimento_index = array_search($alimento_id, array_column($alimentos_todos, 'id'));
            $alimento = $alimentos_todos[$alimento_index];
            $pct = min(100, ($control['veces_en_periodo'] / $control['max_veces']) * 100);
            ?>
            <div class="col-md-4 mb-2">
                <div class="card shadow-sm border-<?= $control['estado'] ?> py-2">
                    <div class="card-body text-center p-2">
                        <h6 class="card-title mb-1"><?= esc($alimento['nombre']) ?></h6>
                        <p class="mb-1" style="font-size:0.8em;">
                            Última: <?= $control['dias_desde_ultima'] !== null ? $control['dias_desde_ultima'] . ' días' : 'Nunca' ?><br>
                            Veces en últimos <?= $control['periodo_dias'] ?> días: <?= $control['veces_en_periodo'] ?>
                            (rango <?= $control['min_veces'] ?>–<?= $control['max_veces'] ?>)
                        </p>
                        <div class="progress mb-1" style="height:6px;">
                            <div class="progress-bar bg-<?= $control['estado'] ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-center gap-1">
                            <a href="<?= site_url('comidas/alimentos-control/edit/' . $control['id']) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Editar</a>
                            <a href="<?= site_url('comidas/alimentos-control/delete/' . $control['id']) ?>" class="btn btn-sm btn-outline-danger py-0 px-2">Eliminar</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Botones de orden -->
    <div class="mb-2 d-flex gap-2">
        <button id="ordenAlfabetico" class="btn btn-sm btn-outline-primary">Alfabético</button>
        <button id="ordenDias" class="btn btn-sm btn-outline-secondary">Por días</button>
    </div>

    <!-- Listado simple de ingredientes no controlados -->
    <?php if (!empty($ingredientes_no_controlados)): ?>
        <div class="mt-4">
            <h5>Otros ingredientes</h5>

            <ul id="listaNoControlados" class="list-group">
                <?php foreach ($ingredientes_no_controlados as $ing): ?>
                    <li class="list-group-item d-flex justify-content-start"
                        data-nombre="<?= esc($ing['nombre']) ?>"
                        data-dias="<?= $ing['dias_desde'] !== null ? $ing['dias_desde'] : 999 ?>">
                        <span class="badge bg-secondary rounded-pill me-3" style="width:75px">
                            <?= $ing['dias_desde'] !== null ? $ing['dias_desde'] . ' días' : 'Nunca' ?>
                        </span>
                        <span class="me-auto"><?= esc($ing['nombre']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>

<!-- Botón para abrir modal -->
<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddControl">+</button>

<!-- Modal -->
<div class="modal fade" id="modalAddControl" tabindex="-1" aria-labelledby="modalAddControlLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="<?= site_url('comidas/alimentos-control/add') ?>" method="post" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalAddControlLabel">Añadir control</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="alimento_id" class="form-label">Alimento</label>
          <select name="alimento_id" id="alimento_id" class="form-select" required>
            <?php foreach ($alimentos_todos as $al): ?>
              <option value="<?= $al['id'] ?>"><?= esc($al['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="periodo_dias" class="form-label">Periodo (días)</label>
          <input type="number" name="periodo_dias" id="periodo_dias" class="form-control" min="1" value="7" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Frecuencia</label>
          <div class="d-flex gap-2">
            <input type="number" name="min_veces" class="form-control" placeholder="Mínimo" min="0" value="0" required>
            <input type="number" name="max_veces" class="form-control" placeholder="Máximo" min="1" value="1" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary">Añadir</button>
      </div>
    </form>
  </div>
</div>



<script>
    const ul = document.getElementById('listaNoControlados');

    function ordenarLista(byDias = false) {
        const lis = Array.from(ul.querySelectorAll('li'));
        lis.sort((a, b) => {
            if (byDias) {
                return parseInt(a.dataset.dias) - parseInt(b.dataset.dias);
            } else {
                return a.dataset.nombre.localeCompare(b.dataset.nombre);
            }
        });
        ul.innerHTML = '';
        lis.forEach(li => ul.appendChild(li));
    }

    document.getElementById('ordenAlfabetico').addEventListener('click', () => ordenarLista(false));
    document.getElementById('ordenDias').addEventListener('click', () => ordenarLista(true));
</script>

<?= $this->endSection(); ?>
