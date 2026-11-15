<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — gestionar habitaciones (estilo fv): orden arrastrando, editar, borrar.
 *
 * @var array $habitaciones
 */
?>
<div class="fv rc estrecho">
    <?= view('hogar/_cabecera', [
        'activa'   => 'gestionar',
        'titulo'   => 'Habitaciones',
        'sub'      => '<span>Arrastra <i class="bi bi-grip-vertical"></i> para cambiar el orden.</span>',
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('hogar/habitaciones/nueva') . '"><i class="bi bi-plus-lg"></i>Nueva</a>',
    ]) ?>

    <?php if (!$habitaciones): ?>
        <div class="fv-panel rc-vacio">No hay habitaciones todavía.</div>
    <?php else: ?>
        <div class="hg-tareas" id="hgHabs">
            <?php foreach ($habitaciones as $h): ?>
                <div class="hg-t" data-id="<?= (int) $h['id'] ?>" style="grid-template-columns:auto 36px minmax(0,1fr) auto">
                    <i class="bi bi-grip-vertical hg-asa" title="Arrastra para reordenar"></i>
                    <span class="rc-ico"><i class="bi bi-<?= esc($h['icono'] ?: 'house') ?>"></i></span>
                    <a class="rc-txt" href="<?= site_url('hogar/' . $h['id']) ?>" style="text-decoration:none"><span class="t"><?= esc($h['nombre']) ?></span></a>
                    <div class="rc-btns">
                        <a class="fv-ibtn" href="<?= site_url('hogar/habitaciones/editar/' . $h['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= site_url('hogar/habitaciones/borrar/' . $h['id']) ?>" onsubmit="return confirm('¿Borrar «<?= esc($h['nombre'], 'js') ?>» y todas sus tareas?')" style="margin:0">
                            <?= csrf_field() ?><button type="submit" class="fv-ibtn" title="Borrar"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const lista = document.getElementById('hgHabs');
    if (!lista) return;
    Sortable.create(lista, {
        handle: '.hg-asa', animation: 150, ghostClass: 'hg-fantasma',
        onEnd: () => fetch('<?= site_url('hogar/habitaciones/reordenar') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ orden: [...lista.querySelectorAll('.hg-t')].map(f => f.dataset.id) }),
        }),
    });
})();
</script>
<?= $this->endSection() ?>
