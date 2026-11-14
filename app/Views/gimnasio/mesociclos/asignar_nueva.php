<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos',
        'migas' => ['Mesociclos' => site_url('gimnasio/mesociclos'), 'Plan' => site_url('gimnasio/mesociclos/' . $planId), 'Asignar' => null],
        'titulo' => 'Asignar bloque']) ?>

    <form method="post" action="<?= site_url('gimnasio/mesociclos/' . $planId . '/asignar/' . $bloqueId) ?>" class="fv-panel gm-form">
        <div class="gm-fila2">
            <div class="gm-campo"><label for="asF">Fecha prevista</label><input type="date" id="asF" name="fecha_prevista" class="fv-input"></div>
            <div class="gm-campo"><label for="asE">Estado</label>
                <select id="asE" name="estado" class="gm-sel"><option value="pendiente">Pendiente</option><option value="hecho">Hecho</option><option value="saltado">Saltado</option></select>
            </div>
        </div>
        <div class="gm-campo"><label for="asId">Entrenamiento (ID, opcional)</label><input type="number" id="asId" name="entrenamiento_id" class="fv-input"></div>
        <div class="gm-form-pie">
            <a class="fv-btn" href="<?= site_url('gimnasio/mesociclos/' . $planId . '/simplificado') ?>">Cancelar</a>
            <button class="fv-btn fv-btn-pri">Asignar</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
