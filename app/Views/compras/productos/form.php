<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/compras.css') ?>?v=4">
<?php $volver = $volver ?? site_url('compras/productos/' . $producto['supermercado_id']); ?>
<div class="fv cp">
    <div class="fv-top">
        <div class="fv-crumbs">
            <a href="<?= site_url('compras') ?>">Compras</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?= $volver ?>"><?= esc($supermercado['nombre'] ?? 'Supermercado') ?></a>
            <i class="bi bi-chevron-right"></i>
            <span>Producto</span>
        </div>
    </div>

    <div class="fv-hero">
        <h1><?= esc($producto['nombre']) ?></h1>
        <?php if ($producto['precio'] !== null && $producto['precio'] !== ''): ?>
            <div class="fv-meta"><span class="cp-mono"><?= number_format((float) $producto['precio'], 2, ',', '.') ?> €</span></div>
        <?php endif; ?>
    </div>

    <div class="fv-panel">
        <form class="cp-form" action="<?= site_url('compras/productos/' . $producto['id'] . '/actualizar') ?>" method="post">
            <?= csrf_field() ?>
            <label class="cp-f">
                <span>Nombre</span>
                <input type="text" name="nombre" class="fv-input" value="<?= esc($producto['nombre']) ?>" required autocomplete="off">
            </label>
            <label class="cp-f">
                <span>Zona</span>
                <select name="zona_id" class="fv-input">
                    <option value="">Sin zona</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= (int) $z['id'] ?>" <?= (int) ($producto['zona_id'] ?? 0) === (int) $z['id'] ? 'selected' : '' ?>><?= esc($z['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="cp-f">
                <span>Imagen <small>URL</small></span>
                <div class="cp-prev">
                    <span class="cp-th" id="cpPrev">
                        <?php if (!empty($producto['imagen'])): ?>
                            <img src="<?= esc($producto['imagen'], 'attr') ?>" alt="">
                        <?php else: ?>
                            <i class="bi bi-image"></i>
                        <?php endif; ?>
                    </span>
                    <input type="url" name="imagen" id="cpImg" class="fv-input" value="<?= esc($producto['imagen']) ?>" placeholder="https://…" autocomplete="off">
                </div>
            </div>
            <div class="cp-botones">
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar cambios</button>
                <a class="fv-btn" href="<?= $volver ?>">Cancelar</a>
            </div>
        </form>

        <form class="cp-borrar" action="<?= site_url('compras/productos/' . $producto['id'] . '/borrar') ?>" method="post"
              onsubmit="return confirm('¿Borrar <?= esc($producto['nombre'], 'js') ?> del catálogo?')">
            <?= csrf_field() ?>
            <button type="submit" class="fv-btn fv-btn-sm fv-btn-peligro"><i class="bi bi-trash"></i>Borrar producto</button>
        </form>
    </div>
</div>
<script>
(() => {
    const inp = document.getElementById('cpImg'), prev = document.getElementById('cpPrev');
    inp.addEventListener('change', () => {
        const u = inp.value.trim();
        prev.innerHTML = u ? '<img alt="">' : '<i class="bi bi-image"></i>';
        if (u) prev.firstChild.src = u;
    });
})();
</script>
<?= $this->endSection() ?>
