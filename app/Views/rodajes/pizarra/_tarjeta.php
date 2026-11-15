<?php
/**
 * Tarjeta de escena en la pizarra.
 *
 * @var array       $e
 * @var int         $pid
 * @var string      $vista
 * @var array|null  $mini ['ruta', 'video']
 */
$eid    = (int) $e['id'];
$estado = $e['toma_estado'] ?: 'pendiente';
$est    = rod_toma_estados()[$estado] ?? null;
$chips  = [];
if ($vista !== 'narrativa' && trim((string) $e['escena_bloque']) !== '') $chips[] = '<span><i class="bi bi-collection"></i>' . esc($e['escena_bloque']) . '</span>';
if ($vista !== 'ubicacion' && trim((string) $e['escena_ubicacion']) !== '') $chips[] = '<span><i class="bi bi-geo-alt"></i>' . esc($e['escena_ubicacion']) . '</span>';
if ($vista !== 'montaje' && trim((string) $e['camara_soporte']) !== '') $chips[] = '<span><i class="bi bi-camera-reels"></i>' . esc($e['camara_soporte']) . '</span>';
$buscar = mb_strtolower(implode(' ', [
    $e['escena_bloque'], $e['escena_descripcion'], $e['escena_accion'], $e['escena_ubicacion'], $e['camara_soporte'], $e['plano_notas'],
]));
?>
<article class="rp-tarjeta <?= $estado ?>" data-id="<?= $eid ?>" data-buscar="<?= esc($buscar, 'attr') ?>">
    <?php if ($mini): ?>
        <div class="rp-mini">
            <?php if ($mini['video']): ?>
                <video src="<?= base_url($mini['ruta']) ?>#t=0.1" muted playsinline preload="metadata"></video><i class="bi bi-play-fill"></i>
            <?php else: ?>
                <img src="<?= base_url($mini['ruta']) ?>" alt="" loading="lazy">
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="rp-cuerpo">
        <div class="rp-linea">
            <span class="rp-num">#<?= (int) $e['orden'] ?></span>
            <?php if ($vista !== 'produccion' && $estado !== 'pendiente' && $est): ?>
                <span class="fv-estado <?= $est[2] ?>"><?= $est[0] ?></span>
            <?php endif; ?>
            <button type="button" class="rp-mover fv-ibtn" title="Mover a…"><i class="bi bi-arrows-move"></i></button>
        </div>
        <a class="rp-t" href="<?= site_url("rodajes/$pid/escenas/show/$eid") ?>"><?= esc(rod_titulo_escena($e)) ?></a>
        <?php if ($chips): ?><div class="rp-chips"><?= implode('', $chips) ?></div><?php endif; ?>
    </div>
</article>
