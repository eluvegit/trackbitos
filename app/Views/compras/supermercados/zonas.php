<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/compras.css') ?>?v=4">
<div class="fv cp">
    <?php if (session('message')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i><?= esc(session('message')) ?></div>
    <?php endif; ?>

    <div class="fv-top">
        <div class="fv-crumbs">
            <a href="<?= site_url('compras') ?>">Compras</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?= $volver ?>"><?= esc($supermercado_nombre) ?></a>
            <i class="bi bi-chevron-right"></i>
            <span>Zonas</span>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Recorrido</h1>
        <div class="fv-meta">
            <span><?= esc($supermercado_nombre) ?></span>
            <span class="fv-dot"></span>
            <span><span class="cp-mono"><?= count($zonas) ?></span> <?= count($zonas) === 1 ? 'zona' : 'zonas' ?></span>
        </div>
    </div>

    <form class="cp-una" action="<?= site_url('compras/zonas/nuevo') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">
        <input type="text" name="nombre" class="fv-input" placeholder="Nueva zona, p. ej. Frutería" required autocomplete="off" enterkeyhint="done">
        <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i>Añadir</button>
    </form>

    <?php if (!empty($sugerencias)): ?>
        <div class="cp-sug">
            <?php foreach ($sugerencias as $sugerencia): ?>
                <form action="<?= site_url('compras/zonas/nuevo') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="supermercado_id" value="<?= esc($supermercado_id) ?>">
                    <input type="hidden" name="nombre" value="<?= esc($sugerencia) ?>">
                    <button type="submit" class="fv-btn"><i class="bi bi-plus"></i><?= esc($sugerencia) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($zonas): ?>
        <p class="cp-ayuda">Ordénalas como las recorres en la tienda: la lista de la compra sigue este orden. Arrastra <i class="bi bi-grip-vertical"></i> para moverlas.</p>
    <?php endif; ?>

    <div class="cp-filas" id="zonaList">
        <?php foreach ($zonas as $z): ?>
            <div class="cp-fila" data-id="<?= (int) $z['id'] ?>">
                <span class="cp-grip" title="Arrastrar"><i class="bi bi-grip-vertical"></i></span>
                <span class="cp-nm" data-nombre="<?= esc($z['nombre'], 'attr') ?>"><?= esc($z['nombre']) ?></span>
                <button type="button" class="cp-ib js-renombrar" title="Renombrar" data-id="<?= (int) $z['id'] ?>"><i class="bi bi-pencil"></i></button>
                <form action="<?= site_url('compras/zonas/' . $z['id'] . '/borrar') ?>" method="post" class="m-0"
                      onsubmit="return confirm('¿Eliminar la zona <?= esc($z['nombre'], 'js') ?>? Sus productos se quedarán sin zona.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="cp-ib peligro" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($zonas)): ?>
        <p class="cp-vacio">Todavía no hay zonas. Sin zonas, la lista sale en un solo bloque.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const list = document.getElementById('zonaList');
    if (!list) return;

    Sortable.create(list, {
        handle: '.cp-grip',
        animation: 150,
        onEnd: () => fetch('<?= site_url('compras/zonas/reordenar') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'Content-Type': 'application/json' },
            body: JSON.stringify({ orden: [...list.children].map(i => i.dataset.id) }),
        }),
    });

    list.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-renombrar');
        if (!btn) return;
        const nameEl = btn.closest('.cp-fila').querySelector('.cp-nm');
        const actual = nameEl.dataset.nombre;
        const nuevo = (prompt('Nuevo nombre de la zona:', actual) || '').trim();
        if (!nuevo || nuevo === actual) return;

        const res = await fetch('<?= site_url('compras/zonas') ?>/' + btn.dataset.id + '/renombrar', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'nombre=' + encodeURIComponent(nuevo) + '&<?= csrf_token() ?>=<?= csrf_hash() ?>',
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.ok) {
            nameEl.textContent = data.nombre;
            nameEl.dataset.nombre = data.nombre;
        }
    });
})();
</script>
<?= $this->endSection() ?>
