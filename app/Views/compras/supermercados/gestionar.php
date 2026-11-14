<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/compras.css') ?>?v=4">
<?php $visibles = count(array_filter($supermercados, fn ($s) => $s['visible'])); ?>
<div class="fv cp">
    <div class="fv-top">
        <div class="fv-crumbs">
            <a href="<?= site_url('compras') ?>">Compras</a>
            <i class="bi bi-chevron-right"></i>
            <span>Ordenar y ocultar</span>
        </div>
        <div class="fv-acciones">
            <a class="fv-gbtn" href="<?= site_url('compras/supermercados/nuevo') ?>"><i class="bi bi-plus-lg"></i>Nuevo</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Supermercados</h1>
        <div class="fv-meta">
            <span class="cp-mono" id="cpVisibles"><?= $visibles ?></span><span>&nbsp;visibles de <?= count($supermercados) ?></span>
        </div>
    </div>

    <p class="cp-ayuda">Arrastra <i class="bi bi-grip-vertical"></i> para cambiar el orden. El interruptor los quita de la portada sin borrarlos.</p>

    <div class="cp-filas" id="superList">
        <?php foreach ($supermercados as $s): ?>
            <div class="cp-fila <?= $s['visible'] ? '' : 'oculto' ?>" data-id="<?= (int) $s['id'] ?>">
                <span class="cp-grip" title="Arrastrar"><i class="bi bi-grip-vertical"></i></span>
                <a class="cp-nm" href="<?= site_url('compras/' . $s['id']) ?>"><?= esc($s['nombre']) ?></a>
                <a class="cp-ib" href="<?= site_url('compras/supermercados/editar/' . $s['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                <button type="button" class="cp-sw <?= $s['visible'] ? 'on' : '' ?>" role="switch"
                        aria-checked="<?= $s['visible'] ? 'true' : 'false' ?>" aria-label="Mostrar en la portada"
                        data-id="<?= (int) $s['id'] ?>"></button>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($supermercados)): ?>
        <p class="fv-vacio cp-vacio">Todavía no hay supermercados.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const list = document.getElementById('superList');
    if (!list) return;
    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'Content-Type': 'application/json' },
        body: body ? JSON.stringify(body) : null,
    });

    Sortable.create(list, {
        handle: '.cp-grip',
        animation: 150,
        onEnd: () => post('<?= site_url('compras/supermercados/reordenar') ?>', { orden: [...list.children].map(i => i.dataset.id) }),
    });

    list.addEventListener('click', async (e) => {
        const sw = e.target.closest('.cp-sw');
        if (!sw) return;
        const res = await post('<?= site_url('compras/supermercados') ?>/' + sw.dataset.id + '/visibilidad');
        if (!res.ok) return;
        const on = !sw.classList.contains('on');
        sw.classList.toggle('on', on);
        sw.setAttribute('aria-checked', on);
        sw.closest('.cp-fila').classList.toggle('oculto', !on);
        document.getElementById('cpVisibles').textContent = list.querySelectorAll('.cp-sw.on').length;
    });
})();
</script>
<?= $this->endSection() ?>
