<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?><?= esc($guia['nombre']) ?><?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<?php $url = static fn (string $o) => inventario_url('guia/' . (int) $guia['id']) . ($o === '' ? '' : '?o=' . $o); ?>

<nav class="inv-migas">
    <a href="<?= $url('') ?>" class="inv-chip <?= $migas === [] && !$resultado ? 'activo' : '' ?>"><i class="bi bi-arrow-counterclockwise"></i> Empezar</a>
    <?php foreach ($migas as $m): ?>
        <i class="bi bi-chevron-right inv-migas-sep"></i>
        <?php if ($m['auto']): ?>
            <span class="inv-chip fijo" title="Única opción posible">
                <span class="inv-migas-paso"><?= esc($m['paso']) ?>:</span> <?= esc($m['texto']) ?>
            </span>
        <?php else: ?>
            <a href="<?= $url($m['o']) ?>" class="inv-chip" title="Cambiar <?= esc($m['paso'], 'attr') ?>">
                <span class="inv-migas-paso"><?= esc($m['paso']) ?>:</span> <?= esc($m['texto']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<?php if ($resultado): ?>
    <?php $p = $resultado; $codigo = $p['codigo'] ?? null; ?>
    <div class="inv-resultado">
        <button type="button" class="inv-resultado-foto" data-foto
            data-grande="<?= esc($p['img_grande'] ?? '', 'attr') ?>"
            data-nombre="<?= esc($p['nombre'], 'attr') ?>"
            data-codigo="<?= esc($codigo ?? 'Sin ubicación', 'attr') ?>"
            data-stock="<?= (int) $p['stock'] ?>"
            data-sin-hueco="<?= $codigo ? '0' : '1' ?>"
            <?= $p['img'] ? '' : 'disabled' ?>>
            <?php if ($p['img_grande'] ?? $p['img']): ?>
                <img src="<?= esc($p['img_grande'] ?? $p['img'], 'attr') ?>" alt="">
                <span class="inv-lupa"><i class="bi bi-arrows-fullscreen"></i></span>
            <?php else: ?>
                <i class="bi bi-image inv-sin-foto"></i>
            <?php endif; ?>
        </button>
        <div class="inv-resultado-datos">
            <span class="inv-resultado-etiqueta"><i class="bi bi-check-circle-fill"></i> Tu pieza es</span>
            <h2><?= esc($p['nombre']) ?></h2>
            <span class="inv-codigo grande <?= $codigo ? '' : 'sin' ?>"><?= esc($codigo ?? 'Sin ubicación') ?></span>
            <span class="inv-stock <?= $p['stock'] > 0 ? '' : 'cero' ?>"><strong><?= (int) $p['stock'] ?></strong> uds.</span>
            <a href="<?= $url('') ?>" class="inv-boton-texto"><i class="bi bi-arrow-counterclockwise"></i> Elegir otra</a>
        </div>
    </div>
<?php elseif ($paso && $opciones !== []): ?>
    <p class="inv-pregunta">
        <span>Paso <?= count($migas) + 1 ?> de <?= (int) $total ?></span>
        Elige <?= esc(mb_strtolower($paso['nombre'])) ?>
    </p>
    <div class="inv-botones">
        <?php foreach ($opciones as $o): ?>
            <a href="<?= $url($o['o']) ?>" class="inv-boton con-foto">
                <span class="inv-boton-foto">
                    <?php if ($o['img']): ?>
                        <img src="<?= esc($o['img'], 'attr') ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <?= icono_guia($guia['icono']) ?>
                    <?php endif; ?>
                </span>
                <strong><?= esc($o['nombre']) ?></strong>
                <?php if (count($migas) + 1 < $total): ?>
                    <span><?= (int) $o['piezas'] ?> <?= $o['piezas'] === 1 ? 'opción' : 'opciones' ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="inv-vacio"><?= icono_guia($guia['icono']) ?><p>Esta guía todavía no tiene piezas.</p></div>
<?php endif; ?>
<?= $this->endSection() ?>
