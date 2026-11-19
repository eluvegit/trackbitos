<?php
/**
 * Tarjeta de escena en la pizarra.
 *
 * @var array       $e
 * @var int         $pid
 * @var string      $vista
 * @var string|null $parte  en Producción, la parte que representa ('' / foto / video); null en el resto
 * @var array|null  $mini   ['ruta', 'video']
 */
$eid      = (int) $e['id'];
$parte    = $parte ?? null;
$estados  = rod_toma_estados();
$formatos = rod_formatos();
$partes   = rod_estados_partes($e);
$dosPartes = count($partes) > 1;

// En Producción la tarjeta es una parte: su estado y su medio son los de esa parte.
$estado = $parte !== null ? ($partes[$parte] ?? 'pendiente') : rod_estado_escena($e);
$medio  = $parte !== null && $dosPartes ? $parte : (string) ($e['formato'] ?? '');

$chips = [];
if ($vista !== 'narrativa' && trim((string) $e['escena_bloque']) !== '') $chips[] = '<span><i class="bi bi-collection"></i>' . esc($e['escena_bloque']) . '</span>';
if (!in_array($vista, ['ubicacion', 'rodaje'], true) && trim((string) $e['escena_ubicacion']) !== '') $chips[] = '<span><i class="bi bi-geo-alt"></i>' . esc($e['escena_ubicacion']) . '</span>';
if (!in_array($vista, ['montaje', 'rodaje'], true) && ($e['setup_nombre'] ?? '') !== '') $chips[] = '<span><i class="bi bi-camera-reels"></i>' . esc($e['setup_nombre']) . '</span>';
$buscar = mb_strtolower(implode(' ', [
    $e['escena_bloque'], $e['escena_descripcion'], $e['escena_accion'], $e['escena_ubicacion'], $e['camara_soporte'], $e['setup_nombre'] ?? '', $e['plano_notas'],
]));
$ref = $eid . ($parte !== null && $parte !== '' ? ':' . $parte : '');
?>
<article class="rp-tarjeta <?= $estado ?>" data-id="<?= $ref ?>" data-medio="<?= esc($medio, 'attr') ?>" data-buscar="<?= esc($buscar, 'attr') ?>">
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
            <?php if ($parte !== null && $dosPartes): ?>
                <span class="rd-formato <?= $parte ?>"><i class="bi <?= $formatos[$parte][1] ?>"></i><?= $formatos[$parte][0] ?></span>
            <?php else: ?>
                <?= rod_formato_chip($e, $parte !== null) ?>
            <?php endif; ?>
            <?php if ($parte === null): ?>
                <?php foreach ($partes as $p => $est): ?>
                    <?php if ($est === 'pendiente') continue; ?>
                    <span class="fv-estado <?= $estados[$est][2] ?>"><?= $dosPartes ? '<i class="bi ' . $formatos[$p][1] . '"></i> ' : '' ?><?= $estados[$est][0] ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
            <button type="button" class="rp-mover fv-ibtn" title="Mover a…"><i class="bi bi-arrows-move"></i></button>
        </div>
        <a class="rp-t" href="<?= site_url("rodajes/$pid/escenas/show/$eid") ?>"><?= esc(rod_titulo_escena($e)) ?></a>
        <?php if ($chips): ?><div class="rp-chips"><?= implode('', $chips) ?></div><?php endif; ?>
    </div>
</article>
