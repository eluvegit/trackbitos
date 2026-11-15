<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Coche — historial de acciones (estilo fv), por años.
 *
 * @var array $acciones todas, de la más reciente a la más antigua
 * @var array $avisos   recordatorios de categoría «vehiculo» (para saber a cuál pertenece cada acción)
 */
$n = count($acciones);
$tituloAviso = array_column($avisos, 'titulo', 'id');
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'   => 'coche',
        'activa'   => 'historial',
        'titulo'   => 'Historial',
        'clasica'  => true,
        'sub'      => '<span>' . $n . ' ' . ($n === 1 ? 'acción apuntada' : 'acciones apuntadas') . '</span>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('coche/acciones/nueva') . '"><i class="bi bi-plus-lg"></i>Apuntar acción</a>',
    ]) ?>

    <?php if (!$acciones): ?>
        <div class="fv-panel rc-vacio">No hay acciones apuntadas todavía.</div>
    <?php else: ?>
        <div class="rc-herr"><input type="search" class="fv-input" id="rcBuscar" placeholder="Buscar en el historial…" autocomplete="off"></div>
        <div class="rc-hist" id="rcHist">
            <?php $anio = null; ?>
            <?php foreach ($acciones as $a): ?>
                <?php $y = substr((string) $a['date'], 0, 4); ?>
                <?php if ($y !== $anio): $anio = $y; ?><div class="rc-anio"><?= $y ?></div><?php endif; ?>
                <div class="rc-h" data-buscar="<?= esc(mb_strtolower($a['title'] . ' ' . $a['notes']), 'attr') ?>">
                    <div class="rc-ico"><i class="bi bi-wrench"></i></div>
                    <div class="rc-txt">
                        <span class="t"><?= esc($a['title']) ?></span>
                        <div class="rc-meta">
                            <span><i class="bi bi-calendar3"></i><?= rec_fecha($a['date']) ?> · <?= rec_hace($a['date']) ?></span>
                            <?php if (!empty($a['kilometers'])): ?><span><i class="bi bi-speedometer"></i><?= number_format((int) $a['kilometers'], 0, ',', '.') ?> km</span><?php endif; ?>
                            <?php if (!empty($a['recordatorio_id']) && isset($tituloAviso[$a['recordatorio_id']])): ?><span title="Renueva este aviso"><i class="bi bi-bell"></i><?= esc($tituloAviso[$a['recordatorio_id']]) ?></span><?php endif; ?>
                        </div>
                        <?php if (trim((string) $a['notes']) !== ''): ?><div class="rc-notas"><?= esc($a['notes']) ?></div><?php endif; ?>
                    </div>
                    <div class="rc-btns">
                        <a class="fv-ibtn" href="<?= site_url('coche/acciones/editar/' . $a['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= site_url('coche/acciones/borrar/' . $a['id']) ?>" onsubmit="return confirm('¿Borrar esta acción? Si renueva un aviso, el aviso se recalcula.')">
                            <?= csrf_field() ?><button type="submit" class="fv-ibtn" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
(() => {
    const b = document.getElementById('rcBuscar');
    if (!b) return;
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    b.addEventListener('input', () => {
        const q = norm(b.value.trim());
        document.querySelectorAll('#rcHist .rc-h').forEach(f => f.hidden = q !== '' && !norm(f.dataset.buscar).includes(q));
        document.querySelectorAll('#rcHist .rc-anio').forEach(a => a.hidden = q !== '');
    });
})();
</script>
<?= $this->endSection() ?>
