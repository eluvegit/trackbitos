<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Coche — averías (estilo fv).
 *
 * @var array $averias de la más reciente a la más antigua
 */
$n = count($averias);
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'   => 'coche',
        'activa'   => 'averias',
        'titulo'   => 'Averías',
        'clasica'  => true,
        'sub'      => '<span>' . $n . ' ' . ($n === 1 ? 'avería apuntada' : 'averías apuntadas') . '</span>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('coche/averias/nueva') . '"><i class="bi bi-plus-lg"></i>Apuntar avería</a>',
    ]) ?>

    <?php if (!$averias): ?>
        <div class="fv-panel rc-vacio">No hay averías apuntadas. Mejor así.</div>
    <?php else: ?>
        <div class="rc-hist">
            <?php foreach ($averias as $a): ?>
                <div class="rc-h" style="align-items:start">
                    <div class="rc-ico"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="rc-txt">
                        <span class="t" style="white-space:normal"><?= nl2br(esc(trim((string) $a['notes']) ?: 'Avería sin descripción')) ?></span>
                        <div class="rc-meta">
                            <span><i class="bi bi-calendar3"></i><?= rec_fecha($a['date']) ?> · <?= rec_hace($a['date']) ?></span>
                            <?php if (!empty($a['kilometers'])): ?><span><i class="bi bi-speedometer"></i><?= number_format((int) $a['kilometers'], 0, ',', '.') ?> km</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="rc-btns">
                        <a class="fv-ibtn" href="<?= site_url('coche/averias/editar/' . $a['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= site_url('coche/averias/borrar/' . $a['id']) ?>" onsubmit="return confirm('¿Borrar esta avería?')">
                            <?= csrf_field() ?><button type="submit" class="fv-ibtn" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
