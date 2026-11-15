<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="fv rc estrecho">
    <?= view('comidas/_cabecera', [
        'activa' => 'peso',
        'migas'  => ['Peso' => site_url('comidas/peso'), 'Importar' => null],
        'titulo' => 'Importar CSV de báscula',
    ]) ?>
    <form method="post" action="<?= site_url('comidas/peso/importar') ?>" enctype="multipart/form-data" class="fv-panel rc-form">
        <?= csrf_field() ?>
        <div class="rc-info"><i class="bi bi-info-circle"></i><span>Sube el CSV exportado de la báscula Tanita. Se leen la fecha, el peso y el resto de métricas (IMC, % grasa, masa muscular…). Si un día tiene varias pesadas se usa la más cercana al mediodía, y si la fecha ya existe se actualiza en vez de duplicarse.</span></div>
        <label class="rc-campo"><span>Archivo CSV</span><input type="file" name="csv" accept=".csv" class="fv-input" style="padding-top:7px" required></label>
        <div class="rc-pie">
            <button class="fv-btn fv-btn-pri"><i class="bi bi-upload"></i>Importar</button>
            <a class="fv-btn" href="<?= site_url('comidas/peso') ?>">Volver</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
