<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$formatos = rod_formatos();
$porFormato = [];
$sinFormato = [];
foreach ($escenas as $e) {
    if ($e['formato'] && isset($formatos[$e['formato']])) $porFormato[$e['formato']][] = $e;
    else $sinFormato[] = $e;
}
$cuenta = static function (array $ts): string { $n = count($ts); return $n ? $n . ($n === 1 ? ' toma' : ' tomas') : ''; };
?>
<div class="fv rd" id="rfFmt" data-asignar="<?= $url('escenas') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => '¿Foto o vídeo? Marca las tomas y toca el formato. «Foto y vídeo» deja en el plató dos marcas, una para cada parte.'])) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-camera-reels"></i><div>Aún no hay tomas.</div><a class="fv-btn fv-btn-pri" href="<?= $url('revisar') ?>">Ir a revisar ideas</a></div>
    <?php endif; ?>

    <?php if ($sinFormato): ?>
        <?php
        $destinos = [];
        foreach ($formatos as $k => [$txt, $ico]) {
            $destinos[] = ['id' => $k, 'nombre' => $txt, 'icono' => $ico, 'sub' => $cuenta($porFormato[$k] ?? [])];
        }
        ?>
        <div class="rf-momento">
            <?= view('rodajes/flujo/_asignar', [
                'pid' => $pid, 'tomas' => $sinFormato, 'portadas' => $portadas ?? [], 'destinos' => $destinos,
                'titulo' => count($sinFormato) . (count($sinFormato) === 1 ? ' toma sin formato' : ' tomas sin formato'),
                'campo' => 'formato', 'icono' => 'bi-camera', 'sinDestinos' => '', 'prefijo' => 'fo-',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($formatos as $k => [$txt, $ico]): if (empty($porFormato[$k])) continue; $ts = $porFormato[$k]; ?>
        <section class="fv-panel rf-mom-grupo" id="fo-<?= $k ?>" data-ancla>
            <div class="rf-zona-h">
                <span class="rf-mom-ico"><i class="bi <?= $ico ?>"></i></span>
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
                        <select class="form-select rd-sel" data-eid="<?= (int) $t['id'] ?>" data-campo="formato" data-recargar aria-label="Formato">
                            <option value="">Sin formato</option>
                            <?php foreach ($formatos as $fk => [$fl]): ?><option value="<?= $fk ?>" <?= $t['formato'] === $fk ? 'selected' : '' ?>><?= esc($fl) ?></option><?php endforeach; ?>
                        </select>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>
<?= view('rodajes/flujo/_asignar_js') ?>
<?= view('rodajes/flujo/_sin_recarga', ['raiz' => 'rfFmt']) ?>
<?= $this->endSection() ?>
