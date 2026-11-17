<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$franjas = \App\Models\RodajesEscenaModel::FRANJAS;
$porSetup = [];
$sinSetup = [];
foreach ($escenas as $e) {
    if ($e['setup_id']) $porSetup[(int) $e['setup_id']][] = $e;
    else $sinSetup[] = $e;
}
?>
<div class="fv rd" id="rfSet" data-asignar="<?= $url('escenas') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'El montaje técnico: lente, soporte, fps y luz. Es lo que cuesta cambiar, así que se define una vez y cada toma lo elige. Los props van en cada toma.'])) ?>

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
            $destinos[] = ['id' => (int) $s['id'], 'nombre' => $s['nombre'], 'imagen' => $imgSetup[(int) $s['id']], 'sub' => html_entity_decode(strip_tags(rod_setup_linea($s)), ENT_QUOTES) ?: ($n ? $n . ($n === 1 ? ' toma' : ' tomas') : '')];
        }
        ?>
        <?= view('rodajes/flujo/_asignar', [
            'pid' => $pid, 'tomas' => $sinSetup, 'portadas' => $portadas ?? [], 'destinos' => $destinos,
            'titulo' => count($sinSetup) . (count($sinSetup) === 1 ? ' toma sin setup' : ' tomas sin setup'),
            'campo' => 'setup_id', 'icono' => 'bi-camera',
            'sinDestinos' => 'Aún no hay setups: crea el primero abajo.',
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
            <form method="post" action="<?= $url("setups/$sid/guardar") ?>" class="fv-panel rd-form rf-setup" id="s<?= $sid ?>"><?= csrf_field() ?>
                <label class="rf-setup-img" title="<?= !empty($s['imagen']) ? 'Cambiar la foto del montaje' : 'Subir una foto del montaje' ?>">
                    <?php if ($imgSetup[$sid]): ?><img src="<?= base_url($imgSetup[$sid]) ?>" alt="" loading="lazy"><?php if (empty($s['imagen'])): ?><small>foto de una toma · toca para subir la del montaje</small><?php endif; ?>
                    <?php else: ?><span><i class="bi bi-camera"></i>Foto del montaje</span><?php endif; ?>
                    <input type="file" name="imagen" accept="image/*" form="rfSetImg<?= $sid ?>" hidden onchange="this.form.submit()">
                </label>
                <input class="fv-input rf-setup-n" name="nombre" value="<?= esc($s['nombre'], 'attr') ?>" aria-label="Nombre del setup">
                <div class="rd-filas-f cam">
                    <div class="rd-campo"><label>Lente</label><input class="fv-input" name="optica" value="<?= esc((string) $s['optica'], 'attr') ?>" placeholder="35mm f/1.8"></div>
                    <div class="rd-campo"><label>Soporte</label>
                        <select class="form-select rd-sel" name="soporte"><option value="">—</option>
                            <?php foreach (array_unique(array_filter(['Mano', 'Trípode', 'Gimbal', 'Otro', (string) $s['soporte']])) as $o): ?><option <?= $s['soporte'] === $o ? 'selected' : '' ?>><?= esc($o) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rd-campo"><label>FPS</label><input class="fv-input" name="fps" value="<?= esc((string) $s['fps'], 'attr') ?>" placeholder="25"></div>
                </div>
                <div class="rd-campo"><label>Luz</label><input class="fv-input" name="luz" value="<?= esc((string) $s['luz'], 'attr') ?>" placeholder="Natural + reflector"></div>
                <div class="rd-campo"><label>Notas</label><textarea class="fv-input" name="notas" rows="2" placeholder="Ajustes, perfil de color, qué revisar antes de disparar…"><?= esc((string) $s['notas']) ?></textarea></div>
                <small class="t3"><?= $ts ? count($ts) . ' ' . (count($ts) === 1 ? 'toma' : 'tomas') . ': ' . esc(implode(' · ', array_keys($donde))) : 'Sin tomas todavía' ?></small>
                <div class="rd-form-pie">
                    <?php if (!$ts): ?><button class="fv-btn fv-btn-sm fv-btn-peligro izq" formaction="<?= $url("setups/$sid/borrar") ?>" onclick="return confirm('¿Quitar el setup «<?= esc($s['nombre'], 'js') ?>»?')"><i class="bi bi-trash"></i></button><?php endif; ?>
                    <button class="fv-btn fv-btn-sm"><i class="bi bi-check-lg"></i>Guardar</button>
                </div>
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
document.getElementById('rfSet').addEventListener('change', ev => {
    const s = ev.target.closest('select[data-eid]');
    if (!s) return;
    fetch(document.getElementById('rfSet').dataset.asignar + '/' + s.dataset.eid + '/asignar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
        body: JSON.stringify({ campo: s.dataset.campo, valor: s.value }),
    }).then(r => { if (!r.ok) throw 0; location.reload(); }).catch(() => alert('No se ha podido guardar.'));
});
</script>
<?= $this->endSection() ?>
