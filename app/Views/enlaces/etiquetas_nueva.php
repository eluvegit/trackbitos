<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — limpieza de etiquetas (estilo fv): filtrar, renombrar en el
 * sitio (si el nombre ya existe, se fusiona), marcar varias y fusionarlas,
 * borrar las que no usa nadie. Las de menos uso salen primero.
 *
 * @var array $etiquetas con uso
 * @var int   $sinUso
 * @var int   $unaVez
 */
$n = count($etiquetas);
ob_start(); ?>
<?php if ($sinUso > 0): ?>
    <form method="post" action="<?= site_url('enlaces/etiquetas/borrar-sin-uso') ?>" onsubmit="return confirm('¿Borrar las <?= (int) $sinUso ?> etiquetas que no usa ningún enlace?');" style="margin:0">
        <?= csrf_field() ?>
        <button type="submit" class="fv-btn fv-btn-peligro"><i class="bi bi-trash"></i>Borrar las <?= (int) $sinUso ?> sin uso</button>
    </form>
<?php endif; ?>
<?php $acciones = ob_get_clean(); ?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'activa'   => 'etiquetas',
        'titulo'   => 'Etiquetas',
        'sub'      => '<span>' . $n . ' etiquetas</span><span class="fv-dot"></span><span' . ($sinUso ? ' class="e-warn"' : '') . '>' . (int) $sinUso . ' sin uso</span><span class="fv-dot"></span><span>' . (int) $unaVez . ' usadas una sola vez</span>',
        'acciones' => $acciones,
    ]) ?>

    <div class="rc-info"><i class="bi bi-lightbulb"></i><span>Para ordenar: marca varias que signifiquen lo mismo y fusiónalas, o cambia el nombre de una al de otra que ya existe (se juntan solas).</span></div>

    <div class="rc-herr">
        <div class="fv-seg" id="etqSeg">
            <button type="button" data-f="all" aria-pressed="true">Todas</button>
            <button type="button" data-f="0">Sin uso</button>
            <button type="button" data-f="1">Usadas 1 vez</button>
        </div>
        <input type="search" class="fv-input" id="etqBuscar" placeholder="Filtrar por nombre…" autocomplete="off">
        <span class="fv-mono t3" id="etqCount" style="font-size:12px;margin-left:auto"></span>
    </div>

    <form class="en-nueva" method="post" action="<?= site_url('enlaces/etiquetas/guardar') ?>">
        <?= csrf_field() ?>
        <input class="fv-input" name="nombre" placeholder="Nueva etiqueta…" required maxlength="120" autocomplete="off">
        <button type="submit" class="fv-btn"><i class="bi bi-plus-lg"></i>Añadir</button>
    </form>

    <div class="en-gest" id="etqList">
        <?php foreach ($etiquetas as $t): ?>
            <?php $uso = (int) $t['uso']; ?>
            <div class="en-g" data-uso="<?= $uso ?>" data-nombre="<?= esc(mb_strtolower($t['nombre']), 'attr') ?>">
                <input type="checkbox" class="etq-check" value="<?= (int) $t['id'] ?>" data-nombre="<?= esc($t['nombre'], 'attr') ?>" aria-label="Seleccionar <?= esc($t['nombre'], 'attr') ?>">
                <form method="post" action="<?= site_url('enlaces/etiquetas/renombrar/' . (int) $t['id']) ?>" class="en-renombrar">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" value="<?= esc($t['nombre'], 'attr') ?>" maxlength="120" aria-label="Nombre">
                    <button type="submit" class="fv-ibtn" hidden title="Guardar nombre (Enter)"><i class="bi bi-check-lg e-ok"></i></button>
                </form>
                <?php if ($uso): ?>
                    <a class="c" href="<?= site_url('enlaces') . '?tag_ids%5B%5D=' . (int) $t['id'] ?>" title="Ver sus enlaces"><?= $uso ?></a>
                <?php else: ?>
                    <span class="c cero" title="Sin uso">0</span>
                <?php endif; ?>
                <a class="fv-ibtn en-borrar" href="<?= site_url('enlaces/etiquetas/borrar/' . (int) $t['id']) ?>" onclick="return confirm('¿Borrar «<?= esc($t['nombre'], 'js') ?>»?<?= $uso ? ' Se quitará de ' . $uso . ' enlace(s).' : '' ?>');" title="Borrar"><i class="bi bi-trash"></i></a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="fv-panel rc-vacio" id="etqVacio" hidden>Ninguna etiqueta coincide.</div>

    <form method="post" action="<?= site_url('enlaces/etiquetas/fusionar') ?>" class="en-fusion" id="etqMerge" hidden onsubmit="return confirm('¿Fusionar las etiquetas marcadas en una sola?');">
        <?= csrf_field() ?>
        <i class="bi bi-signpost-split e-acc"></i>
        <span>Fusionar <b id="etqMergeN">0</b> en</span>
        <select name="destino" class="rc-sel" id="etqMergeDestino"></select>
        <span id="etqMergeInputs"></span>
        <div class="der" style="display:flex;gap:6px">
            <button type="button" class="fv-btn fv-btn-sm" id="etqMergeCancel">Cancelar</button>
            <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri">Fusionar</button>
        </div>
    </form>
</div>

<script>
(() => {
    const list = document.getElementById('etqList');
    const filas = [...list.querySelectorAll('.en-g')];
    const seg = document.getElementById('etqSeg');
    const buscar = document.getElementById('etqBuscar');
    let filtro = 'all';

    function aplicar() {
        const q = buscar.value.trim().toLowerCase();
        let n = 0;
        filas.forEach(f => {
            const ok = (filtro === 'all' || f.dataset.uso === filtro) && (!q || f.dataset.nombre.includes(q));
            f.hidden = !ok;
            if (ok) n++;
        });
        document.getElementById('etqCount').textContent = n + ' / ' + filas.length;
        document.getElementById('etqVacio').hidden = n > 0;
        list.hidden = n === 0;
    }
    seg.addEventListener('click', e => {
        const b = e.target.closest('button');
        if (!b) return;
        seg.querySelectorAll('button').forEach(x => x.setAttribute('aria-pressed', String(x === b)));
        filtro = b.dataset.f;
        aplicar();
    });
    buscar.addEventListener('input', aplicar);
    aplicar();

    // Renombrar: el ✓ solo aparece si el nombre cambia; Esc deshace.
    list.addEventListener('input', e => {
        const i = e.target.closest('.en-renombrar input');
        if (i) i.form.querySelector('button').hidden = i.value.trim() === '' || i.value.trim() === i.defaultValue.trim();
    });
    list.addEventListener('keydown', e => {
        const i = e.target.closest('.en-renombrar input');
        if (i && e.key === 'Escape') { i.value = i.defaultValue; i.form.querySelector('button').hidden = true; i.blur(); }
    });

    // Fusionar: aparece al marcar 2 o más.
    const merge = document.getElementById('etqMerge');
    const sel = document.getElementById('etqMergeDestino');
    const marcadas = () => [...list.querySelectorAll('.etq-check:checked')];
    function refrescar() {
        const m = marcadas();
        merge.hidden = m.length < 2;
        if (m.length < 2) return;
        document.getElementById('etqMergeN').textContent = m.length;
        const prev = sel.value;
        sel.innerHTML = m.map(c => `<option value="${c.value}">${c.dataset.nombre.replace(/</g, '&lt;')}</option>`).join('');
        if (m.some(c => c.value === prev)) sel.value = prev;
        document.getElementById('etqMergeInputs').innerHTML = m.map(c => `<input type="hidden" name="origen[]" value="${c.value}">`).join('');
    }
    list.addEventListener('change', e => { if (e.target.classList.contains('etq-check')) refrescar(); });
    document.getElementById('etqMergeCancel').addEventListener('click', () => { marcadas().forEach(c => { c.checked = false; }); refrescar(); });
})();
</script>
<?php $this->endSection(); ?>
