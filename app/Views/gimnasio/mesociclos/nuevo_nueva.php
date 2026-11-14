<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos',
        'migas' => ['Mesociclos' => site_url('gimnasio/mesociclos'), 'Nuevo' => null], 'titulo' => 'Nuevo plan']) ?>

    <form method="post" action="<?= site_url('gimnasio/mesociclos/nuevo') ?>" class="fv-panel gm-form">
        <div class="gm-campo"><label for="mNombre">Nombre</label><input type="text" id="mNombre" name="nombre" class="fv-input" value="Mesociclo Sentadilla" required></div>
        <div class="gm-campo"><label for="mEj">Ejercicio</label><input type="text" id="mEj" name="ejercicio" class="fv-input" value="sentadilla" required></div>
        <div class="gm-fila2">
            <div class="gm-campo"><label for="mE1">e1RM base (kg)</label><input type="number" step="0.5" inputmode="decimal" id="mE1" name="e1rm_base" class="fv-input" required></div>
            <div class="gm-campo"><label for="mRed">Redondeo (kg)</label><input type="number" step="0.5" inputmode="decimal" id="mRed" name="redondeo_kg" class="fv-input" value="5"></div>
        </div>
        <p class="gm-ayuda">Tu 1RM estimado lo ves en Estadísticas. Los pesos de cada bloque se redondean al múltiplo indicado.</p>
        <div class="gm-form-pie">
            <a class="fv-btn" href="<?= site_url('gimnasio/mesociclos') ?>">Cancelar</a>
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i> Crear plan</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
