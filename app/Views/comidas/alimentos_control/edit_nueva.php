<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="fv rc estrecho">
    <?= view('comidas/_cabecera', [
        'activa' => 'control',
        'migas'  => ['Frecuencia' => site_url('comidas/alimentos-control'), $alimento['nombre'] ?? 'Editar' => null],
        'titulo' => esc($alimento['nombre'] ?? 'Editar control'),
        'sub'    => '<span>Cuántas veces quieres comerlo en cada periodo</span>',
    ]) ?>
    <form method="post" action="<?= site_url('comidas/alimentos-control/edit/' . $control['id']) ?>" class="fv-panel rc-form">
        <?= csrf_field() ?>
        <div class="rc-filas">
            <label class="rc-campo"><span>Periodo (días)</span><input type="number" name="periodo_dias" class="fv-input cm-num" value="<?= esc($control['periodo_dias']) ?>" min="1" required></label>
            <label class="rc-campo"><span>Mínimo de veces</span><input type="number" name="min_veces" class="fv-input cm-num" value="<?= esc($control['min_veces']) ?>" min="0" required></label>
            <label class="rc-campo"><span>Máximo de veces</span><input type="number" name="max_veces" class="fv-input cm-num" value="<?= esc($control['max_veces']) ?>" min="1" required></label>
        </div>
        <label class="rc-campo"><span>Unidad</span><input type="text" name="unidad" class="fv-input" value="<?= esc($control['unidad']) ?>"></label>
        <div class="rc-pie">
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar cambios</button>
            <a href="<?= site_url('comidas/alimentos-control') ?>" class="fv-btn">Cancelar</a>
            <a href="<?= site_url('comidas/alimentos-control/delete/' . $control['id']) ?>" class="fv-btn fv-btn-peligro der" onclick="return confirm('¿Dejar de controlar este alimento?');"><i class="bi bi-trash"></i>Dejar de controlar</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
