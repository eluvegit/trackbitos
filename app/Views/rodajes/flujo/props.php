<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$url = static fn (string $r = '') => site_url("rodajes/$pid/$r");
$grupos = [];
foreach ($escenas as $e) $grupos[trim((string) $e['escena_bloque']) ?: 'Sin temática'][] = $e;
$norm = static fn (array $l) => array_map('rod_normalizar', $l);

/** Botones de prop (activo = lo lleva) + campo para uno nuevo. */
$chips = static function (string $tipo, int $id, array $opciones, array $activos) use ($norm): string {
    $act = $norm($activos);
    $h = '<div class="rf-pchips">';
    foreach ($opciones as $p) {
        $on = in_array(rod_normalizar($p), $act, true);
        $h .= '<button type="button" class="rf-pchip' . ($on ? ' on' : '') . '" data-tipo="' . $tipo . '" data-id="' . $id . '" data-prop="' . esc($p, 'attr') . '" aria-pressed="' . ($on ? 'true' : 'false') . '">'
            . ($on ? '<i class="bi bi-check-lg"></i>' : '') . esc($p) . '</button>';
    }
    return $h . '<input class="rf-pnuevo" data-tipo="' . $tipo . '" data-id="' . $id . '" placeholder="+ otro" autocomplete="off" enterkeyhint="done" aria-label="Añadir otro prop"></div>';
};
?>
<div class="fv rd" id="rfProps" data-marcar="<?= $url('props/marcar') ?>">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Qué hay que llevar a cada toma. Toca un prop para ponerlo o quitarlo. En las variantes marcas solo lo que llevan además de su toma (la toma lleva el móvil; la variante, además, una taza).'])) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio"><i class="bi bi-box-seam"></i><div>Aún no hay tomas.</div><a class="fv-btn fv-btn-pri" href="<?= $url('revisar') ?>">Ir a revisar ideas</a></div>
    <?php else: ?>
        <section class="fv-panel rf-plista" id="pp-lista" data-ancla>
            <div class="rf-zona-h"><span class="rf-mom-ico"><i class="bi bi-box-seam"></i></span><h2>Props del rodaje</h2><small><?= count($lista) ?: '' ?></small></div>
            <?php if ($lista): ?>
                <div class="rf-plista-l"><?php foreach ($lista as $p): ?><span><?= esc($p) ?></span><?php endforeach; ?></div>
            <?php else: ?>
                <p class="t3">Todavía no hay ninguno. Añádelos aquí o escribe uno en cualquier toma.</p>
            <?php endif; ?>
            <?php if ($sesion): ?>
                <form method="post" action="<?= $url('props/nuevo') ?>" class="rf-plista-f"><?= csrf_field() ?>
                    <input class="fv-input" name="prop" placeholder="Añadir props, separados por comas" autocomplete="off" required>
                    <button class="fv-btn"><i class="bi bi-plus-lg"></i>Añadir</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php $gi = 0; foreach ($grupos as $nombre => $ts): $gi++; ?>
        <section class="fv-panel rf-pgrupo" id="pp-g<?= $gi ?>" data-ancla>
            <div class="rf-zona-h"><h2><?= esc($nombre) ?></h2><small><?= count($ts) ?> <?= count($ts) === 1 ? 'toma' : 'tomas' ?></small></div>
            <?php foreach ($ts as $e): ?>
                <?php $pt = $portadas[(int) $e['id']] ?? null; $tp = rod_props($e); ?>
                <div class="rf-ptoma">
                    <div class="rf-pfila">
                        <span class="rf-foto mini"><?php if ($pt): ?><img src="<?= base_url($pt) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-film"></i><?php endif; ?></span>
                        <div class="rf-pcuerpo">
                            <a class="rf-ptit" href="<?= $url('escenas/edit/' . $e['id']) ?>"><b class="rf-tnum">#<?= (int) $e['orden'] ?></b> <?= esc(rod_titulo_escena($e)) ?></a>
                            <?= $chips('t', (int) $e['id'], $lista, $tp) ?>
                        </div>
                    </div>
                    <?php foreach ($e['variantes'] as $v): ?>
                        <?php
                        $fv = $v['moodboard_item_id'] ? ($fotos['v'][(int) $v['moodboard_item_id']] ?? null) : null;
                        $opc = array_values(array_filter($lista, static fn ($p) => !in_array(rod_normalizar($p), $norm($tp), true)));
                        ?>
                        <div class="rf-pfila rf-pvar">
                            <?php if ($fv): ?><?= view('rodajes/flujo/_foto', ['i' => $fv, 'clase' => 'mini'], ['saveData' => false]) ?><?php else: ?><span class="rf-foto mini"><i class="bi bi-diamond"></i></span><?php endif; ?>
                            <div class="rf-pcuerpo">
                                <span class="rf-ptit"><span class="rf-pbadge">Variante</span> <?= esc($v['texto']) ?></span>
                                <small class="rf-phereda"><?= $tp ? 'Con la toma ya lleva: ' . esc(implode(', ', $tp)) . '. Además:' : 'Lleva:' ?></small>
                                <?= $chips('v', (int) $v['id'], $opc, rod_props_extra($e, $v)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
<?= view('rodajes/flujo/_sin_recarga', ['raiz' => 'rfProps']) ?>
<script>
(() => {
    const raiz = () => document.getElementById('rfProps');
    const guardar = (el, prop, on) => {
        const g = el.closest('[data-ancla]');
        return fetch(raiz().dataset.marcar, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo: el.dataset.tipo, id: el.dataset.id, prop, on }),
        }).then(r => {
            if (!r.ok) throw 0;
            return window.rfRefrescar ? window.rfRefrescar(g && g.id ? '#' + g.id : null) : location.reload();
        }).catch(() => { alert('No se ha podido guardar.'); location.reload(); });
    };
    document.addEventListener('click', ev => {
        const b = ev.target.closest('#rfProps .rf-pchip');
        if (!b || b.disabled) return;
        b.disabled = true;
        b.classList.toggle('on');
        guardar(b, b.dataset.prop, b.classList.contains('on'));
    });
    document.addEventListener('keydown', ev => {
        const i = ev.target.closest && ev.target.closest('#rfProps .rf-pnuevo');
        if (!i || ev.key !== 'Enter') return;
        ev.preventDefault();
        const vals = i.value.split(',').map(s => s.trim()).filter(Boolean);
        if (!vals.length) return;
        i.disabled = true;
        vals.reduce((p, v, k) => p.then(() => k < vals.length - 1
            ? fetch(raiz().dataset.marcar, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'Content-Type': 'application/json' }, body: JSON.stringify({ tipo: i.dataset.tipo, id: i.dataset.id, prop: v, on: true }) })
            : guardar(i, v, true)), Promise.resolve());
    });
})();
</script>
<?= $this->endSection() ?>
