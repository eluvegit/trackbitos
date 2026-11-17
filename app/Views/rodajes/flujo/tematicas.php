<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$pid = (int) $proyecto['id'];
$grupos = [['id' => '', 'nombre' => 'Bandeja', 'items' => $porSit[''] ?? [], 'vacio' => 'Vacía: todo está agrupado.']];
foreach ($situaciones as $s) $grupos[] = ['id' => (int) $s['id'], 'nombre' => $s['nombre'], 'items' => $porSit[$s['id']] ?? [], 'vacio' => 'Sin ideas todavía.'];
?>
<div class="fv rd">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Agrupa la bandeja: marca varias ideas y muévelas juntas a una temática, o crea una nueva con ellas. No es obligatorio; una idea suelta también puede pasar a toma.'])) ?>

    <form method="post" action="<?= site_url("rodajes/$pid/tematicas/mover") ?>" id="rfSel">
        <?= csrf_field() ?>
        <div class="rf-selbar">
            <span class="fv-mono"><b id="rfN">0</b> marcadas</span>
            <select name="destino" class="form-select rd-sel" aria-label="Mover a">
                <option value="">Mover a…</option>
                <?php foreach ($situaciones as $s): ?><option value="<?= (int) $s['id'] ?>"><?= esc($s['nombre']) ?></option><?php endforeach; ?>
                <option value="0">Bandeja (sin temática)</option>
            </select>
            <button class="fv-btn fv-btn-sm" name="accion" value="mover" data-req>Mover</button>
            <span class="fv-vsep"></span>
            <input class="fv-input" name="nombre" id="rfNombre" placeholder="Nueva temática con las marcadas" autocomplete="off">
            <button class="fv-btn fv-btn-sm" name="accion" value="nueva" id="rfNueva"><i class="bi bi-plus-lg"></i>Crear</button>
            <span class="fv-vsep"></span>
            <button class="fv-btn fv-btn-sm" name="accion" value="descartar" data-req onclick="return confirm('¿Descartar las ideas marcadas?')"><i class="bi bi-x-lg"></i>Descartar</button>
            <button class="fv-btn fv-btn-sm" name="accion" value="recuperar" data-req title="Vuelve a dejar sin revisar las descartadas marcadas"><i class="bi bi-arrow-counterclockwise"></i>Recuperar</button>
        </div>

        <?php foreach ($grupos as $g): ?>
            <section class="rf-sec">
                <div class="rf-sec-h">
                    <h2 data-nombre><?= esc($g['nombre']) ?></h2>
                    <?php if ($g['id'] !== ''): ?>
                        <span class="rf-renombrar" hidden>
                            <input class="fv-input" name="nombre" form="rfTem<?= $g['id'] ?>" value="<?= esc($g['nombre'], 'attr') ?>" aria-label="Nombre de la temática" autocomplete="off">
                            <button class="fv-btn fv-btn-sm" form="rfTem<?= $g['id'] ?>"><i class="bi bi-check-lg"></i>Guardar</button>
                        </span>
                    <?php endif; ?>
                    <span><?= count($g['items']) ?> <?= count($g['items']) === 1 ? 'idea' : 'ideas' ?></span>
                    <span class="der rf-sec-acc">
                        <?php if ($g['items']): ?><button type="button" class="fv-link" data-todas>Marcar todas</button><?php endif; ?>
                        <?php if ($g['id'] !== ''): ?>
                            <button type="button" class="fv-ibtn" data-renombrar title="Renombrar"><i class="bi bi-pencil"></i></button>
                            <button class="fv-ibtn" form="rfTemB<?= $g['id'] ?>" title="Borrar temática"
                                    onclick="return confirm('¿Borrar la temática «<?= esc($g['nombre'], 'js') ?>»?<?= $g['items'] ? (count($g['items']) === 1 ? ' Su idea vuelve a la bandeja.' : ' Sus ' . count($g['items']) . ' ideas vuelven a la bandeja.') : '' ?>')"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($g['items']): ?>
                    <div class="rf-ideas">
                        <?php foreach ($g['items'] as $i): ?><?= view('rodajes/flujo/_idea', ['i' => $i, 'marcable' => true]) ?><?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rf-vacio"><?= $g['vacio'] ?></div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </form>
    <?php foreach ($situaciones as $st): ?>
        <form id="rfTem<?= (int) $st['id'] ?>" method="post" action="<?= site_url("rodajes/$pid/tematicas/" . (int) $st['id'] . '/guardar') ?>" hidden><?= csrf_field() ?></form>
        <form id="rfTemB<?= (int) $st['id'] ?>" method="post" action="<?= site_url("rodajes/$pid/tematicas/" . (int) $st['id'] . '/borrar') ?>" hidden><?= csrf_field() ?></form>
    <?php endforeach; ?>
    <div class="rd-form-pie"><a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/revisar") ?>">Siguiente: revisar ideas<i class="bi bi-arrow-right"></i></a></div>
</div>
<script>
(() => {
    const f = document.getElementById('rfSel');
    const n = document.getElementById('rfN');
    const contar = () => {
        const k = f.querySelectorAll('input[name="ids[]"]:checked').length;
        n.textContent = k;
        f.querySelectorAll('[data-req]').forEach(b => b.disabled = !k);
    };
    f.addEventListener('change', contar);
    f.querySelectorAll('[data-todas]').forEach(b => b.addEventListener('click', () => {
        const cajas = [...b.closest('.rf-sec').querySelectorAll('input[name="ids[]"]')];
        const todas = cajas.every(c => c.checked);
        cajas.forEach(c => c.checked = !todas);
        contar();
    }));
    f.querySelectorAll('[data-renombrar]').forEach(b => b.addEventListener('click', () => {
        const h = b.closest('.rf-sec-h');
        const caja = h.querySelector('.rf-renombrar');
        caja.hidden = !caja.hidden;
        h.querySelector('[data-nombre]').hidden = !caja.hidden;
        if (!caja.hidden) caja.querySelector('input').select();
    }));
    f.querySelectorAll('.rf-renombrar input').forEach(i => i.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById(i.getAttribute('form')).submit(); }
        if (e.key === 'Escape') i.closest('.rf-sec-h').querySelector('[data-renombrar]').click();
    }));
    document.getElementById('rfNombre').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('rfNueva').click(); }
    });
    contar();
})();
</script>
<?= $this->endSection() ?>
