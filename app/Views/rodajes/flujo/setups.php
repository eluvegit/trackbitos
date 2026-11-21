<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$franjas = \App\Models\RodajesEscenaModel::FRANJAS;
$porSetup = [];
$sinSetup = [];
$sinVideo = [];
foreach ($escenas as $e) {
    if ($e['setup_id']) $porSetup[(int) $e['setup_id']][] = $e;
    else $sinSetup[] = $e;
    if (($e['formato'] ?? '') === 'ambas') {
        if ($e['setup_video_id']) $porSetup[(int) $e['setup_video_id']][] = $e + ['_video' => true];
        else $sinVideo[] = $e;
    }
}
$tipos = ['foto' => ['Foto', 'bi-camera'], 'video' => ['Vídeo', 'bi-camera-video']];
?>
<div class="fv rd" id="rfSet" data-asignar="<?= $url('escenas') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'El montaje técnico: lente, soporte y luz. Es lo que cuesta cambiar, así que se define una vez y cada toma lo elige. Los props van en el paso siguiente.'])) ?>

    <?php
    // Imagen de cada setup: la suya, si no la foto de una de sus tomas.
    $imgSetup = [];
    foreach ($setups as $s) {
        $img = $s['imagen'] ?? null;
        if (!$img) foreach ($porSetup[(int) $s['id']] ?? [] as $t) if ($img = $portadas[(int) $t['id']] ?? null) break;
        $imgSetup[(int) $s['id']] = $img ?: null;
    }
    ?>
    <?php if ($sinSetup): ?>
        <?php
        $destinos = [];
        foreach ($setups as $s) {
            $n = count($porSetup[(int) $s['id']] ?? []);
            $destinos[] = ['id' => (int) $s['id'], 'nombre' => ($s['tipo'] ? $tipos[$s['tipo']][0] . ' · ' : '') . $s['nombre'], 'imagen' => $imgSetup[(int) $s['id']], 'sub' => html_entity_decode(strip_tags(rod_setup_linea($s)), ENT_QUOTES) ?: ($n ? $n . ($n === 1 ? ' toma' : ' tomas') : '')];
        }
        ?>
        <?= view('rodajes/flujo/_asignar', [
            'pid' => $pid, 'tomas' => $sinSetup, 'portadas' => $portadas ?? [], 'destinos' => $destinos,
            'titulo' => count($sinSetup) . (count($sinSetup) === 1 ? ' toma sin setup' : ' tomas sin setup'),
            'campo' => 'setup_id', 'icono' => 'bi-camera', 'prefijo' => 's',
            'sinDestinos' => 'Aún no hay setups: crea el primero abajo.',
        ]) ?>
    <?php endif; ?>
    <?php $setupsVideo = array_values(array_filter($setups, static fn ($s) => ($s['tipo'] ?? '') !== 'foto')); ?>
    <?php if ($sinVideo): ?>
        <?= view('rodajes/flujo/_asignar', [
            'pid' => $pid, 'tomas' => $sinVideo, 'portadas' => $portadas ?? [],
            'destinos' => array_map(static fn ($s) => ['id' => (int) $s['id'], 'nombre' => $s['nombre'], 'imagen' => $imgSetup[(int) $s['id']], 'sub' => html_entity_decode(strip_tags(rod_setup_linea($s)), ENT_QUOTES)], $setupsVideo),
            'titulo' => count($sinVideo) . (count($sinVideo) === 1 ? ' toma de foto y vídeo sin setup de vídeo' : ' tomas de foto y vídeo sin setup de vídeo'),
            'campo' => 'setup_video_id', 'icono' => 'bi-camera-video', 'prefijo' => 's',
            'sinDestinos' => 'Aún no hay setups de vídeo: crea uno abajo y márcalo como Vídeo.',
        ]) ?>
    <?php endif; ?>

    <div class="rf-setups">
        <?php foreach ($setups as $s): ?>
            <?php
            $sid = (int) $s['id'];
            $ts = $porSetup[$sid] ?? [];
            $donde = [];
            foreach ($ts as $t) $donde[($zonas[$t['ubicacion_id']]['nombre'] ?? 'Sin zona') . ($t['franja'] ? ' (' . mb_strtolower($franjas[$t['franja']] ?? '') . ')' : '')] = true;
            ?>
            <?php $tp = (string) ($s['tipo'] ?? ''); ?>
            <form method="post" action="<?= $url("setups/$sid/guardar") ?>" class="fv-panel rd-form rf-setup" id="s<?= $sid ?>" data-tp="<?= esc($tp, 'attr') ?>" data-ancla><?= csrf_field() ?>
                <div class="rf-setup-cab">
                    <label class="rf-setup-img" title="<?= !empty($s['imagen']) ? 'Cambiar la foto del montaje' : 'Subir una foto del montaje' ?>">
                        <?php if (!empty($s['imagen'])): ?><img src="<?= base_url($s['imagen']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-camera"></i><?php endif; ?>
                        <input type="file" name="imagen" accept="image/*" form="rfSetImg<?= $sid ?>" hidden onchange="this.form.requestSubmit()">
                    </label>
                    <input class="fv-input rf-setup-n" name="nombre" value="<?= esc($s['nombre'], 'attr') ?>" aria-label="Nombre del setup">
                </div>
                <div class="rf-tipo" role="radiogroup" aria-label="Tipo de setup">
                    <?php foreach ($tipos as $k => [$txt, $ico]): ?>
                        <label><input type="radio" name="tipo" value="<?= $k ?>" <?= $tp === $k ? 'checked' : '' ?>><span><i class="bi <?= $ico ?>"></i><?= $txt ?></span></label>
                    <?php endforeach; ?>
                    <?php if ($tp === ''): ?><small class="t3">Sin marcar: elige si es de foto o de vídeo.</small><?php endif; ?>
                    <button class="fv-btn fv-btn-sm rf-tipo-ok"><i class="bi bi-check-lg"></i>Guardar</button>
                </div>
                <div class="rd-filas-f cam">
                    <div class="rd-campo"><label>Lente</label><input class="fv-input" name="optica" value="<?= esc((string) $s['optica'], 'attr') ?>" placeholder="35mm f/1.8"></div>
                    <div class="rd-campo"><label>Soporte</label>
                        <select class="form-select rd-sel" name="soporte"><option value="">—</option>
                            <?php foreach (array_unique(array_filter(['Mano', 'Trípode', 'Gimbal', 'Otro', (string) $s['soporte']])) as $o): ?><option <?= $s['soporte'] === $o ? 'selected' : '' ?>><?= esc($o) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="rf-params">
                    <div class="rd-campo"><label>Diafragma</label><input class="fv-input" name="apertura" value="<?= esc((string) $s['apertura'], 'attr') ?>" placeholder="2.8" inputmode="decimal"></div>
                    <div class="rd-campo"><label data-et-foto="Velocidad" data-et-video="Obturación"><?= $tp === 'video' ? 'Obturación' : 'Velocidad' ?></label><input class="fv-input" name="velocidad" value="<?= esc((string) $s['velocidad'], 'attr') ?>" placeholder="1/125"></div>
                    <div class="rd-campo"><label>ISO</label><input class="fv-input" name="iso" value="<?= esc((string) $s['iso'], 'attr') ?>" inputmode="numeric"></div>
                    <div class="rd-campo" data-tipo="video"><label>FPS</label><input class="fv-input" name="fps" value="<?= esc((string) $s['fps'], 'attr') ?>" inputmode="numeric" placeholder="25"></div>
                    <div class="rd-campo" data-tipo="video"><label>ND</label><input class="fv-input" name="nd" value="<?= esc((string) $s['nd'], 'attr') ?>"></div>
                </div>
                <div class="rd-campo"><label data-et-foto="Flash / luz" data-et-video="Foco de luz"><?= $tp === 'video' ? 'Foco de luz' : ($tp === 'foto' ? 'Flash / luz' : 'Luz') ?></label><input class="fv-input" name="luz" value="<?= esc((string) $s['luz'], 'attr') ?>" placeholder="Natural + reflector"></div>
                <div class="rd-campo"><label>Notas</label><textarea class="fv-input" name="notas" rows="2" placeholder="Ajustes, perfil de color, qué revisar antes de disparar…"><?= esc((string) $s['notas']) ?></textarea></div>
                <div class="rd-campo">
                    <span class="rd-lbl">Tomas con este setup<?= $ts ? ' · ' . count($ts) : '' ?></span>
                    <?php if ($ts): ?>
                        <ul class="rf-tz">
                            <?php foreach ($ts as $t): ?>
                                <?php
                                $pt = $portadas[(int) $t['id']] ?? null;
                                $desc = trim((string) $t['escena_descripcion']);
                                $tema = trim((string) $t['escena_bloque']);
                                $info = array_filter([$desc !== '' ? $tema : '', $zonas[$t['ubicacion_id']]['nombre'] ?? '', $franjas[$t['franja']] ?? '']);
                                ?>
                                <li>
                                    <span class="rf-foto mini"><?php if ($pt): ?><img src="<?= base_url($pt) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-film"></i><?php endif; ?></span>
                                    <a href="<?= $url('escenas/edit/' . $t['id']) ?>"><b class="rf-tnum">#<?= (int) $t['orden'] ?></b><?php if (($t['formato'] ?? '') === 'ambas'): ?> <i class="bi <?= empty($t['_video']) ? 'bi-camera' : 'bi-camera-video' ?>" title="<?= empty($t['_video']) ? 'Como setup de foto' : 'Como setup de vídeo' ?>"></i><?php endif; ?> <?= esc($desc !== '' ? $desc : ($tema !== '' ? $tema : 'Toma')) ?></a>
                                    <small><?= $info ? esc(implode(' · ', $info)) : '&nbsp;' ?></small>
                                    <select class="form-select rd-sel" data-eid="<?= (int) $t['id'] ?>" data-campo="<?= empty($t['_video']) ? 'setup_id' : 'setup_video_id' ?>" data-recargar aria-label="Cambiar de setup">
                                        <option value="">Sin setup (a la bandeja)</option>
                                        <?php foreach ($setups as $o): ?><option value="<?= (int) $o['id'] ?>" <?= (int) $o['id'] === $sid ? 'selected' : '' ?>><?= esc($o['nombre']) ?></option><?php endforeach; ?>
                                    </select>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?><small class="t3">Ninguna todavía: márcalas arriba y toca este setup.</small><?php endif; ?>
                </div>
                <?php if (!$ts): ?>
                    <div class="rd-form-pie">
                        <button class="fv-btn fv-btn-sm fv-btn-peligro izq" formaction="<?= $url("setups/$sid/borrar") ?>" onclick="return confirm('¿Quitar el setup «<?= esc($s['nombre'], 'js') ?>»?')"><i class="bi bi-trash"></i></button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endforeach; ?>
    </div>

    <?php foreach ($setups as $s): ?>
        <form id="rfSetImg<?= (int) $s['id'] ?>" method="post" action="<?= $url('setups/' . (int) $s['id'] . '/imagen') ?>" enctype="multipart/form-data" hidden><?= csrf_field() ?></form>
    <?php endforeach; ?>
    <form method="post" action="<?= $url('setups/crear') ?>" class="rd-herr"><?= csrf_field() ?>
        <input class="fv-input" name="nombre" placeholder="Nuevo setup, p. ej. «50mm trípode»" autocomplete="off" required>
        <button class="fv-btn"><i class="bi bi-plus-lg"></i>Crear setup</button>
    </form>
</div>
<script>
// Foto / vídeo: oculta los campos del otro tipo (data-tp + CSS) y cambia las etiquetas.
// Delegado en document porque el guardado sin recarga sustituye los formularios.
document.addEventListener('change', ev => {
    const r = ev.target;
    if (r.name !== 'tipo' || !r.closest('.rf-setup')) return;
    const f = r.closest('.rf-setup'), t = r.value;
    f.dataset.tp = t;
    f.querySelectorAll('[data-et-foto]').forEach(l => { l.textContent = l.dataset['et' + (t === 'foto' ? 'Foto' : 'Video')]; });
});
</script>
<?= view('rodajes/flujo/_asignar_js') ?>
<?= view('rodajes/flujo/_sin_recarga', ['raiz' => 'rfSet']) ?>
<?= $this->endSection() ?>
