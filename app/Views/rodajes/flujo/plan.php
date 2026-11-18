<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$nT = 0; $nV = 0; $nS = 0;
$hayOpc = false;
$anclas = [];
foreach ($plan as $f) foreach ($f['zonas'] as $z) foreach ($z['setups'] as $s) { $nS++; foreach ($s['escenas'] as $e) { $nT++; $nV += count($e['variantes']); if (($e['prioridad'] ?? 'imp') === 'opc') $hayOpc = true; foreach ($e['variantes'] as $v) if ($v['prioridad'] === 'opc') $hayOpc = true; } }
ob_start(); ?>
<a class="fv-btn" href="<?= $url('plan' . ($soloImp ? '' : '?imp=1')) ?>" aria-pressed="<?= $soloImp ? 'true' : 'false' ?>"><i class="bi <?= $soloImp ? 'bi-check-square' : 'bi-square' ?>"></i>Solo imprescindibles</a>
<a class="fv-btn fv-btn-pri" href="<?= $url('plato?orden=plan') ?>"><i class="bi bi-phone"></i>Ir al plató</a>
<?php $acciones = ob_get_clean(); ?>
<div class="fv rd">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Así queda la jornada: franja, zona en orden de ruta, setup y tomas. Con las flechas de cada «Monta» eliges qué montaje va antes en esa zona. Dentro de cada setup las tomas se ordenan para cambiar los mínimos props.'])) ?>

    <div class="rf-plan-sum fv-mono"><?= $nT ?> tomas · <?= $nV ?> variantes · <?= $nS ?> montajes de setup</div>

    <?php if (!$plan): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-list-ol"></i><div>No hay tomas todavía.</div><a class="fv-btn fv-btn-pri" href="<?= $url('revisar') ?>">Revisar ideas</a></div>
    <?php endif; ?>

    <?php foreach ($plan as $f): ?>
        <section class="rf-franja f-<?= $f['franja'] ?: 'sin' ?>">
            <h2 class="rf-franja-h"><i></i><?= esc($f['nombre']) ?></h2>
            <?php foreach ($f['zonas'] as $z): ?>
                <?php
                $zid = (int) ($z['zona']['id'] ?? 0);
                $ancla = $zid && !isset($anclas[$zid]) ? ' id="z' . $zid . '"' : ''; if ($zid) $anclas[$zid] = true;
                $reales = array_values(array_filter($z['setups'], static fn ($x) => $x['setup'] !== null));
                $si = -1;
                ?>
                <div class="fv-panel rf-pz"<?= $ancla ?>>
                    <div class="rf-pz-h">
                        <?php if ($z['zona']): ?><span class="rf-num"><?= $z['n'] ?></span><b><?= esc($z['zona']['nombre']) ?></b><?php else: ?><b class="t3">Sin zona</b><?php endif; ?>
                        <?php if (!empty($z['zona']['notas'])): ?><p><?= esc($z['zona']['notas']) ?></p><?php endif; ?>
                    </div>
                    <?php foreach ($z['setups'] as $s): if ($s['setup']) $si++; ?>
                        <div class="rf-ps">
                            <div class="rf-ps-h">
                                <span class="rf-monta"><?= $s['setup'] ? 'Monta' : 'Sin setup' ?></span>
                                <?php if ($s['setup']): ?><b><?= esc($s['setup']['nombre']) ?></b><span class="t2"><?= rod_setup_linea($s['setup']) ?></span><?php if ($s['setup']['notas']): ?><span class="rf-ps-notas"><?= esc($s['setup']['notas']) ?></span><?php endif; ?><?php endif; ?>
                                <?php if ($s['setup'] && $zid && count($reales) > 1): ?>
                                    <span class="rf-ps-orden">
                                        <?php foreach ([-1 => ['bi-arrow-up', 'Montar antes en esta zona', $si === 0], 1 => ['bi-arrow-down', 'Montar después en esta zona', $si === count($reales) - 1]] as $dir => [$ico, $tit, $off]): ?>
                                            <form method="post" action="<?= $url("ubicaciones/$zid/setup-orden") ?>"><?= csrf_field() ?>
                                                <input type="hidden" name="setup_id" value="<?= (int) $s['setup']['id'] ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><?php if ($soloImp): ?><input type="hidden" name="imp" value="1"><?php endif; ?>
                                                <button class="fv-ibtn" title="<?= $tit ?>" <?= $off ? 'disabled' : '' ?>><i class="bi <?= $ico ?>"></i></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <ol class="rf-pt">
                                <?php $prev = null; foreach ($s['escenas'] as $e): ?>
                                    <?php $pr = rod_props($e); $punto = $puntos[$e['punto_id']]['nombre'] ?? null; ?>
                                    <li>
                                        <div class="rf-pt-1">
                                            <?php $fp = $fotos['t'][(int) $e['id']][0] ?? null; if ($fp): ?><?= view('rodajes/flujo/_foto', ['i' => $fp], ['saveData' => false]) ?><?php endif; ?>
                                            <?php if ($hayOpc): ?><span class="rf-prio <?= ($e['prioridad'] ?? 'imp') === 'opc' ? 'opc' : 'imp' ?>"><?= ($e['prioridad'] ?? 'imp') === 'opc' ? 'Si da tiempo' : 'Imprescindible' ?></span><?php endif; ?>
                                            <a href="<?= $url('escenas/edit/' . $e['id']) ?>"><?= esc(rod_titulo_escena($e)) ?></a>
                                            <?= rod_formato_chip($e) ?>
                                            <?php if ($punto): ?><small class="t3"><i class="bi bi-pin-map"></i> <?= esc($punto) ?></small><?php endif; ?>
                                        </div>
                                        <?php $d = rod_props_diff($prev, $pr); if ($d !== ''): ?><div class="rf-diff"><?= $d ?></div><?php endif; ?>
                                        <?php if ($e['variantes']): ?>
                                            <ul class="rf-pv"><?php foreach ($e['variantes'] as $v): ?><li><?php $fv = $v['moodboard_item_id'] ? ($fotos['v'][(int) $v['moodboard_item_id']] ?? null) : null; if ($fv): ?><?= view('rodajes/flujo/_foto', ['i' => $fv, 'clase' => 'mini'], ['saveData' => false]) ?><?php endif; ?><?= esc($v['texto']) ?><?php if ($hayOpc && $v['prioridad'] === 'imp'): ?> <span class="rf-prio imp">Imp.</span><?php endif; ?></li><?php endforeach; ?></ul>
                                        <?php endif; ?>
                                    </li>
                                    <?php $prev = $pr; endforeach; ?>
                            </ol>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
