<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Guion de diálogos (estilo fv): las escenas con diálogo en orden de rodaje.
 * Oscuro en pantalla, claro al imprimir.
 *
 * @var array $proyecto
 * @var array $escenas solo las que tienen sonido_dialogo_escrito
 */
$pid = (int) $proyecto['id'];
$n   = count($escenas);

ob_start(); ?>
<?php if ($escenas): ?><button type="button" class="fv-btn" onclick="window.print()"><i class="bi bi-printer"></i>Imprimir</button><?php endif; ?>
<a class="fv-btn" href="<?= site_url("rodajes/$pid/escenas/storyboard") ?>"><i class="bi bi-layout-text-window-reverse"></i>Storyboard</a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rd estrecho dialogos">
    <?= view('rodajes/_cabecera', [
        'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Diálogos' => null],
        'titulo'   => 'Diálogos',
        'sub'      => '<span>' . esc($proyecto['titulo']) . '</span><span class="fv-dot"></span><span>' . $n . ' ' . ($n === 1 ? 'escena con diálogo' : 'escenas con diálogo') . '</span>',
        'acciones' => $acciones,
    ]) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-chat-quote"></i>
            <div>Ninguna escena tiene diálogo escrito todavía.</div>
            <small>Se escribe en la sección «Sonido» de cada escena.</small>
        </div>
    <?php else: ?>
        <?php
        // Encabezado de escena al estilo guion: «2. IDENTIFICACION — ORDENADOR».
        $slug = static fn (array $e) => mb_strtoupper(implode(' — ', array_filter([
            trim((string) $e['escena_bloque']) ?: 'Sin bloque',
            trim((string) $e['escena_ubicacion']),
        ])));
        ?>
        <article class="dl-hoja">
            <header class="dl-portada">
                <h2><?= esc(mb_strtoupper($proyecto['titulo'])) ?></h2>
                <div class="dl-sello">Líneas de diálogo</div>
                <?php if (trim((string) ($proyecto['codigo'] ?? '')) !== ''): ?><div class="dl-cod"><?= esc($proyecto['codigo']) ?></div><?php endif; ?>
            </header>

            <?php foreach ($escenas as $e): ?>
                <?php $eid = (int) $e['id']; ?>
                <section class="dl-esc">
                    <h3 class="dl-slug">
                        <a href="<?= site_url("rodajes/$pid/escenas/show/$eid") ?>"><?= (int) $e['orden'] ?>.</a>
                        <?= esc($slug($e)) ?>
                        <?php if ($f = rod_formato_chip($e, false)): ?><span class="dl-fmt"><?= $f ?></span><?php endif; ?>
                    </h3>
                    <?php if (trim((string) $e['escena_descripcion']) !== ''): ?>
                        <p class="dl-accion"><?= esc(trim($e['escena_descripcion'])) ?></p>
                    <?php endif; ?>
                    <div class="dl-texto"><?= esc(trim($e['sonido_dialogo_escrito'])) ?></div>
                </section>
            <?php endforeach; ?>

            <footer class="dl-fin">— FIN —<span><?= esc($proyecto['titulo']) ?> · <?= date('Y') ?></span></footer>
        </article>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
