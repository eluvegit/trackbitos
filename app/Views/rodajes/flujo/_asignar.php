<?php
/**
 * Asignar tomas a una zona o a un setup, como en Temáticas: marcas las tomas
 * (cada una con su foto) y tocas el destino (cada uno con su imagen).
 *
 * @var int    $pid
 * @var string $titulo     «3 tomas sin zona»
 * @var array  $tomas      escenas
 * @var array  $portadas   [escena_id => ruta]
 * @var array  $destinos   [['id' (int|string),'nombre','imagen'|null,'sub', 'icono'?, 'clase'?]]
 * @var string $prefijo    opcional: id del bloque destino = prefijo + valor (aviso «→ X · Ver» y destello)
 * @var string $campo      ubicacion_id | setup_id | franja
 * @var string $icono      icono de reserva del destino sin imagen
 * @var string $sinDestinos texto si aún no hay zonas/setups
 * @var array  $crear      opcional ['url','texto']: tarjeta para crear un destino nuevo
 *                         (y asignarle las marcadas) sin salir de la bandeja
 */
$crear = $crear ?? null;
?>
<section class="rf-sec rf-asigna" data-prefijo="<?= esc($prefijo ?? '', 'attr') ?>" data-campo="<?= esc($campo, 'attr') ?>" data-url="<?= site_url("rodajes/$pid/escenas") ?>">
    <div class="rf-sec-h">
        <h2><?= esc($titulo) ?></h2>
        <span class="der rf-sec-acc"><button type="button" class="fv-link" data-todas>Marcar todas</button></span>
    </div>
    <?php
    // Agrupadas por temática: sin esto, una toma sin descripción se titula con
    // el nombre de su temática y todas las de un grupo parecen la misma tarjeta.
    $grupos = [];
    foreach ($tomas as $t) $grupos[trim((string) ($t['escena_bloque'] ?? ''))][] = $t;
    if (isset($grupos[''])) { $sin = $grupos['']; unset($grupos['']); $grupos[''] = $sin; }
    ?>
    <?php foreach ($grupos as $tema => $ts): ?>
        <div class="rf-tema" data-grupo>
            <div class="rf-tema-h">
                <b><?= $tema !== '' ? esc($tema) : 'Sin temática' ?></b>
                <small><?= count($ts) ?> <?= count($ts) === 1 ? 'toma' : 'tomas' ?></small>
                <?php if (count($ts) > 1): ?><button type="button" class="fv-link der" data-grupo-todas>Marcar las <?= count($ts) ?></button><?php endif; ?>
            </div>
            <div class="rf-ideas">
                <?php foreach ($ts as $t): ?>
                    <?php
                    $ruta = $portadas[(int) $t['id']] ?? null;
                    $desc = trim((string) ($t['escena_descripcion'] ?? ''));
                    $txt  = $desc !== '' ? $desc : 'Toma #' . (int) $t['orden'];
                    ?>
                    <label class="rf-idea">
                        <input type="checkbox" value="<?= (int) $t['id'] ?>"><span class="rf-check" aria-hidden="true"></span>
                        <span class="rf-media <?= $ruta ? 'archivo' : 'texto' ?>">
                            <?php if ($ruta): ?>
                                <img src="<?= base_url($ruta) ?>" alt="" loading="lazy"><span class="cap"><b class="rf-tnum">#<?= (int) $t['orden'] ?></b> <?= esc($desc) ?></span>
                            <?php else: ?>
                                <span class="kind"><i class="bi bi-film"></i>#<?= (int) $t['orden'] ?></span><span class="tx"><?= esc($txt) ?></span>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="rf-destinos-h"><b data-n>0</b> marcadas → toca dónde van</div>
    <?php if (!$destinos && !$crear): ?>
        <div class="rf-vacio"><?= $sinDestinos ?></div>
    <?php else: ?>
        <div class="rf-destinos">
            <?php if ($crear): ?>
                <form class="rf-destino rf-destino-nuevo" data-crear="<?= esc($crear['url'], 'attr') ?>">
                    <span class="img"><i class="bi bi-plus-lg"></i></span>
                    <input class="fv-input" name="nombre" placeholder="<?= esc($crear['texto'], 'attr') ?>" autocomplete="off" enterkeyhint="done" aria-label="<?= esc($crear['texto'], 'attr') ?>">
                    <button class="fv-btn fv-btn-sm">Crear</button>
                </form>
            <?php endif; ?>
            <?php foreach ($destinos as $d): ?>
                <button type="button" class="rf-destino<?= !empty($d['clase']) ? ' ' . esc($d['clase'], 'attr') : '' ?>" data-valor="<?= esc((string) $d['id'], 'attr') ?>" disabled>
                    <span class="img"><?php if (!empty($d['imagen'])): ?><img src="<?= base_url($d['imagen']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi <?= esc($d['icono'] ?? $icono, 'attr') ?>"></i><?php endif; ?></span>
                    <span class="t"><?= esc($d['nombre']) ?></span>
                    <?php if ($d['sub'] !== ''): ?><small><?= esc($d['sub']) ?></small><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= view('rodajes/flujo/_asignar_js') ?>
