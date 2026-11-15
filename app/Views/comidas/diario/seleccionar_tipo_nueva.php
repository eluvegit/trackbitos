<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$f = $fechaSel->format('Y-m-d');
$titulo = ucfirst(\CodeIgniter\I18n\Time::parse($f)->toLocalizedString("EEEE d 'de' MMMM"));
$iconos = ['desayuno' => 'bi-cup-hot', 'almuerzo' => 'bi-sun', 'merienda' => 'bi-apple', 'cena' => 'bi-moon-stars', 'nocturna' => 'bi-moon'];
?>
<div class="fv rc estrecho">
    <?= view('comidas/_cabecera', [
        'activa' => 'diario',
        'migas'  => [$titulo => site_url('comidas/diario/' . $f)],
        'titulo' => '¿Qué comida?',
    ]) ?>
    <div class="rc-lista cm-lista">
        <?php foreach ($tipos as $t): ?>
            <?php $n = count($ingPorTipo[$t] ?? []); ?>
            <a class="rc-fila" style="grid-template-columns:36px minmax(0,1fr) auto" href="<?= site_url('comidas/diario/' . $f . '/' . $t) ?>">
                <span class="rc-ico"><i class="bi <?= $iconos[$t] ?? 'bi-egg-fried' ?>"></i></span>
                <span class="rc-txt"><span class="rc-t"><span class="t"><?= esc(ucfirst(str_replace('_', ' ', $t))) ?></span></span><span class="rc-meta"><?= $n ? $n . ' ' . ($n === 1 ? 'registro' : 'registros') : 'Sin registros' ?></span></span>
                <i class="bi bi-chevron-right t3"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
