<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$acciones = '<button type="button" class="fv-gbtn" data-bs-toggle="modal" data-bs-target="#gmRenombrar"><i class="bi bi-pencil"></i> Renombrar</button>'
    . '<form method="post" action="' . site_url('gimnasio/plantillas/eliminar/' . $plantilla_id) . '" data-confirm="¿Eliminar esta plantilla?" class="m-0"><button class="fv-ibtn" title="Eliminar plantilla"><i class="bi bi-trash"></i></button></form>';
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'plantillas',
        'migas' => ['Plantillas' => site_url('gimnasio/plantillas'), $plantilla['nombre'] => null],
        'titulo' => esc($plantilla['nombre']), 'acciones' => $acciones,
        'sub' => !empty($plantilla['notas']) ? '<span>' . esc($plantilla['notas']) . '</span>' : '<span>Plantilla · se copia tal cual al aplicarla a una sesión</span>']) ?>

    <?= view('gimnasio/_editor', [
        'ejerciciosAgrupados' => $ejerciciosAgrupados,
        'claveEe'    => 'pe_id',
        'recientes'  => $recientes,
        'grupos'     => $grupos,
        'todos'      => $todos,
        'cronometro' => false,
        'vacioTxt'   => 'Esta plantilla está vacía. Pulsa <b>+</b> para añadir ejercicios y series.',
        'config'     => [
            'id' => (int) $plantilla_id, 'campoId' => 'plantilla_id', 'claveEe' => 'pe_id',
            'campoReordenar' => 'plantilla_ejercicio_id', 'cronometro' => false, 'excluir' => 0,
            'urls' => [
                'guardar'    => site_url('gimnasio/plantillas/guardar-serie'),
                'actualizar' => site_url('gimnasio/plantillas/actualizar-serie') . '/',
                'eliminar'   => site_url('gimnasio/plantillas/eliminar-serie') . '/',
                'reordenar'  => site_url('gimnasio/plantillas/reordenar-ejercicio'),
                'porGrupo'   => site_url('gimnasio/ejercicios/por-grupo') . '/',
                'ultima'     => site_url('gimnasio/entrenamientos/ultima-sesion') . '/',
                'stats'      => site_url('gimnasio/ejercicios/estadisticas') . '/',
            ],
        ],
    ]) ?>
</div>

<div class="modal fade" id="gmRenombrar" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('gimnasio/plantillas/renombrar/' . $plantilla_id) ?>" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="gmPlN">Nombre</label>
                <input type="text" name="nombre" id="gmPlN" class="form-control mb-3" value="<?= esc($plantilla['nombre']) ?>" required>
                <label class="form-label" for="gmPlNotas">Notas</label>
                <textarea name="notas" id="gmPlNotas" class="form-control" rows="2"><?= esc($plantilla['notas'] ?? '') ?></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
