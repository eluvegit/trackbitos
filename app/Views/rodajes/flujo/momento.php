<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$franjas = \App\Models\RodajesEscenaModel::FRANJAS;
$iconosFranja = ['dia' => 'bi-sun', 'manana' => 'bi-sunrise', 'mediodia' => 'bi-brightness-high', 'tarde' => 'bi-cloud-sun', 'dorada' => 'bi-sunset', 'noche' => 'bi-moon-stars'];
$porFranja = [];
$sinFranja = [];
foreach ($escenas as $e) {
    if ($e['franja'] && isset($franjas[$e['franja']])) $porFranja[$e['franja']][] = $e;
    else $sinFranja[] = $e;
}
$cuenta = static function (array $ts): string { $n = count($ts); return $n ? $n . ($n === 1 ? ' toma' : ' tomas') : ''; };
?>
<div class="fv rd" id="rfMom" data-asignar="<?= $url('escenas') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => '¿De día o de noche? Marca todas y toca «Día»; luego cambia solo las que sean distintas. «Día y noche» desdobla la toma en dos, una para cada momento.'])) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-sun"></i><div>Aún no hay tomas.</div><a class="fv-btn fv-btn-pri" href="<?= $url('revisar') ?>">Ir a revisar ideas</a></div>
    <?php endif; ?>

    <?php if ($sinFranja): ?>
        <?php
        // Primero lo que se usa al marcar en bloque (día / noche / ambas), luego el detalle.
        $destinos = [
            ['id' => 'dia', 'nombre' => 'Día', 'icono' => $iconosFranja['dia'], 'sub' => $cuenta($porFranja['dia'] ?? []), 'clase' => 'rf-mom-dia'],
            ['id' => 'noche', 'nombre' => 'Noche', 'icono' => $iconosFranja['noche'], 'sub' => $cuenta($porFranja['noche'] ?? []), 'clase' => 'rf-mom-noche'],
            ['id' => 'dia_noche', 'nombre' => 'Día y noche', 'icono' => 'bi-circle-half', 'sub' => 'se hace dos veces', 'clase' => 'rf-mom-ambas'],
        ];
        foreach ($franjas as $k => $txt) {
            if ($k === 'dia' || $k === 'noche') continue;
            $destinos[] = ['id' => $k, 'nombre' => $txt, 'icono' => $iconosFranja[$k] ?? 'bi-clock', 'sub' => $cuenta($porFranja[$k] ?? []), 'clase' => 'rf-mom-fina'];
        }
        ?>
        <div class="rf-momento">
            <?= view('rodajes/flujo/_asignar', [
                'pid' => $pid, 'tomas' => $sinFranja, 'portadas' => $portadas ?? [], 'destinos' => $destinos,
                'titulo' => count($sinFranja) . (count($sinFranja) === 1 ? ' toma sin momento' : ' tomas sin momento'),
                'campo' => 'franja', 'icono' => 'bi-sun', 'sinDestinos' => '', 'prefijo' => 'f-',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($franjas as $k => $txt): if (empty($porFranja[$k])) continue; $ts = $porFranja[$k]; ?>
        <section class="fv-panel rf-mom-grupo" id="f-<?= $k ?>" data-ancla>
            <div class="rf-zona-h">
                <span class="rf-mom-ico"><i class="bi <?= $iconosFranja[$k] ?? 'bi-clock' ?>"></i></span>
                <h2><?= esc($txt) ?></h2>
                <small><?= $cuenta($ts) ?></small>
            </div>
            <ul class="rf-tz">
                <?php foreach ($ts as $t): ?>
                    <?php
                    $pt = $portadas[(int) $t['id']] ?? null;
                    $desc = trim((string) $t['escena_descripcion']);
                    $tema = trim((string) $t['escena_bloque']);
                    $zona = $zonas[(int) $t['ubicacion_id']]['nombre'] ?? '';
                    ?>
                    <li>
                        <span class="rf-foto mini"><?php if ($pt): ?><img src="<?= base_url($pt) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-film"></i><?php endif; ?></span>
                        <a href="<?= $url('escenas/edit/' . $t['id']) ?>"><b class="rf-tnum">#<?= (int) $t['orden'] ?></b> <?= esc($desc !== '' ? $desc : ($tema !== '' ? $tema : 'Toma')) ?></a>
                        <small><?= esc(implode(' · ', array_filter([$desc !== '' ? $tema : '', $zona]))) ?: '&nbsp;' ?></small>
                        <select class="form-select rd-sel" data-eid="<?= (int) $t['id'] ?>" data-campo="franja" data-recargar aria-label="Momento del día">
                            <option value="">Sin momento</option>
                            <?php foreach ($franjas as $fk => $fl): ?><option value="<?= $fk ?>" <?= $t['franja'] === $fk ? 'selected' : '' ?>><?= esc($fl) ?></option><?php endforeach; ?>
                            <option value="dia_noche">Día y noche (dos tomas)</option>
                        </select>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>
<?= view('rodajes/flujo/_asignar_js') ?>
<?= view('rodajes/flujo/_sin_recarga', ['raiz' => 'rfMom']) ?>
<?= $this->endSection() ?>
