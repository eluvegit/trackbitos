<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('gimnasio'); ?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'mesociclos',
        'migas' => ['Mesociclos' => site_url('gimnasio/mesociclos'), $plan['nombre'] => site_url('gimnasio/mesociclos/' . $plan['id']), 'Simplificado' => null],
        'titulo' => 'Lo que toca', 'sub' => '<span>Todos los bloques con el e1RM actual (' . gim_kg($plan['e1rm_base']) . ' kg), tope alto de cada rango</span>']) ?>

    <section class="fv-panel" style="padding:8px 14px">
        <div class="gm-filas">
            <?php foreach ($rows as $r): ?>
                <div class="gm-fila">
                    <span class="n" style="width:34px;text-align:left">#<?= $r['orden'] ?></span>
                    <span class="pp">
                        <span class="t">Top <?= esc($r['top']) ?> · <?= gim_kg($r['topKg']) ?> kg</span>
                        <span class="d">Back-off <?= esc($r['bo']) ?> · <?= gim_kg($r['boKg']) ?> kg · <?= esc($r['bloque_tipo']) ?></span>
                    </span>
                    <a class="fv-gbtn" href="<?= site_url('gimnasio/mesociclos/' . $plan['id'] . '/asignar/' . $r['bloque_id']) ?>">Asignar</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$rows): ?><p class="fv-vacio">Este plan no tiene bloques.</p><?php endif; ?>
    </section>
</div>
<?= $this->endSection() ?>
