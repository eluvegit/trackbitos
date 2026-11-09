<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=13">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-bitacora.css') ?>?v=9">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-enviar.css') ?>?v=3">
<?php
$idPlaca = (int) $placa['id'];
$paralelo = count($envios) > 1;
$volver = (string) current_url(true);
$estado = $placa['enviada_en'] ? ['e-ok', 'Enviada'] : ($placa['poner_en'] ? ['e-warn', 'Guardada sin enviar'] : ['e-acc', 'Sin enviar']);
?>

<div class="fv bt en<?= $paralelo ? ' en-par' : '' ?>">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/placas') ?>">Placas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="fv-mono">#<?= $idPlaca ?></a><i class="bi bi-chevron-right"></i><span>Enviar</span></nav>
        <div class="fv-acciones">
            <?php foreach (['anterior' => 'bi-chevron-left', 'siguiente' => 'bi-chevron-right'] as $lado => $icono): ?>
                <?php $v = $vecinas[$lado] ?? null; ?>
                <?php if ($v): ?>
                    <a href="<?= site_url('piezas/placa/' . (int) $v['id'] . '/enviar') ?>" class="fv-gbtn bt-vecina" title="<?= esc(ucfirst($lado) . ': ' . $v['nombre'], 'attr') ?>">
                        <?php if ($lado === 'anterior'): ?><i class="bi <?= $icono ?>"></i> Anterior <span class="fv-mono">#<?= (int) $v['id'] ?></span><?php else: ?>Siguiente <span class="fv-mono">#<?= (int) $v['id'] ?></span> <i class="bi <?= $icono ?>"></i><?php endif; ?>
                    </a>
                <?php else: ?>
                    <span class="fv-gbtn bt-vecina apagada"><?php if ($lado === 'anterior'): ?><i class="bi <?= $icono ?>"></i> Anterior<?php else: ?>Siguiente <i class="bi <?= $icono ?>"></i><?php endif; ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>" class="fv-gbtn"><i class="bi bi-journal-text"></i> <span class="lbl-m">Bitácora</span></a>
            <span class="fv-vsep"></span>
            <a href="<?= current_url() ?>?nueva=0" class="fv-gbtn" title="Volver al envío de siempre"><i class="bi bi-arrow-left-right"></i> <span class="lbl-m">Vista clásica</span></a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Enviar placa <span class="t3 fv-mono">#<?= $idPlaca ?></span></h1>
        <div class="fv-meta">
            <span class="fv-estado <?= $estado[0] ?>"><?= esc($estado[1]) ?></span>
            <?php if ($placa['impresora']): ?><span class="bt-maq <?= esc($placa['impresora'], 'attr') ?>"><?= esc($placa['impresora']) ?></span><?php endif; ?>
            <span class="fv-dot"></span>
            <span class="text-truncate"><?= esc($placa['nombre']) ?></span>
            <?php if ($destino !== ''): ?><span class="fv-dot"></span><span>a <b><?= esc($destino) ?></b></span><?php endif; ?>
        </div>
    </div>

    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>
    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if ($destino === ''): ?>
        <div class="fv-aviso"><i class="bi bi-exclamation-triangle"></i><span>Falta <code>PIEZAS_PLACA_EMAIL</code> (y la configuración SMTP) en el <code>.env</code>: se puede preparar, pero no enviar.</span></div>
    <?php endif; ?>

    <form method="get" class="en-con">
        <i class="bi bi-layout-split t3"></i>
        <label for="envCon" class="bt-lbl">En paralelo con</label>
        <select name="con" id="envCon" class="bt-ed" onchange="this.form.submit()">
            <option value="0">— solo esta placa —</option>
            <?php foreach ($candidatas as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $con ? 'selected' : '' ?>>
                    <?= esc($c['nombre']) ?><?= $c['poner_en'] ? ' · ' . esc(date('d/m H:i', strtotime($c['poner_en']))) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($paralelo): ?>
        <div data-sync></div>
        <div class="en-cols">
            <?php foreach ($envios as $e): ?>
                <div class="en-col <?= $e['placa']['impresora'] ? 'imp-' . esc($e['placa']['impresora'], 'attr') : '' ?>">
                    <div class="en-col-tit">
                        <span class="fv-mono t3">#<?= (int) $e['placa']['id'] ?></span>
                        <a href="<?= site_url('piezas/placa/' . (int) $e['placa']['id'] . '/bitacora/editar') ?>" class="text-truncate"><?= esc($e['placa']['nombre']) ?></a>
                    </div>
                    <?= view('piezas/_placa_enviar_form_nuevo', compact('e', 'paralelo', 'destino', 'calc', 'desfase', 'volver')) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= view('piezas/_placa_enviar_form_nuevo', ['e' => $envios[0]] + compact('paralelo', 'destino', 'calc', 'desfase', 'volver')) ?>
    <?php endif; ?>
</div>

<script>
(function () {
    function dos(n) { return String(n).padStart(2, '0'); }
    function hm(d) { return dos(d.getHours()) + ':' + dos(d.getMinutes()); }
    function imp(p) { return typeof p.imp === 'function' ? p.imp() : p.imp; }
    function fila(p, ini, total, manda) {
        var a = p.poner(), b = p.fin(), i = imp(p);
        var izq = a && total ? (a - ini) / total * 100 : 0, ancho = a && b && total ? (b - a) / total * 100 : 0;
        return '<div class="en-sync-fila">'
            + (i ? '<span class="bt-maq ' + i + '">' + i + '</span>' : '<span class="bt-maq">—</span>')
            + '<span class="fv-mono t3">#' + p.id + '</span>'
            + '<span class="en-sync-pista"><span class="' + (i || '') + '" style="left:' + izq + '%;width:' + ancho + '%"></span></span>'
            + '<span class="fv-mono">' + (a ? hm(a) : '--:--') + ' → ' + (b ? hm(b) : '--:--') + '</span>'
            + '<span class="en-sync-rol t3">' + (manda ? 'manda' : '') + '</span></div>';
    }
    window.enSync = function () {
        var ps = window.enPlacas || [], cajas = document.querySelectorAll('[data-sync]');
        if (!ps.length || !cajas.length) return;
        var ref, seg;
        if (ps.length > 1) {
            ref = ps[1].enviada && !ps[0].enviada ? ps[1] : ps[0];
            seg = ref === ps[0] ? ps[1] : ps[0];
        } else {
            seg = ps[0];
            ref = seg.pareja();
        }
        var html;
        if (!ref) {
            html = '<div class="en-sync"><div class="en-sync-txt t3"><i class="bi bi-flag"></i> Primera del día: esta marca la hora. Pon a qué hora quieres que acabe y la otra se pondrá a la misma hora.</div></div>';
        } else if (ref.poner() && seg.poner() && ref.poner().toDateString() !== seg.poner().toDateString() && Math.abs(ref.poner() - seg.poner()) > 864e5) {
            var dm = function (d) { return dos(d.getDate()) + '/' + dos(d.getMonth() + 1); };
            html = '<div class="en-sync warn"><div class="en-sync-ver"><span class="en-sync-txt">Son de días distintos: #' + ref.id + ' el ' + dm(ref.poner()) + ' y #' + seg.id + ' el ' + dm(seg.poner()) + '.</span>'
                + (seg.setPoner ? '<button type="button" class="fv-btn fv-btn-sm" data-sync-a="misma">Mismo día y hora (' + dm(ref.poner()) + ' ' + hm(ref.poner()) + ')</button>' : '') + '</div></div>';
            window.enSyncAcciones = { misma: function () { seg.setPoner(ref.poner()); } };
        } else if (!ref.fin() || !seg.fin()) {
            html = '<div class="en-sync"><div class="en-sync-txt t3">Falta la duración de alguna de las dos para compararlas.</div></div>';
        } else {
            var ini = Math.min(ref.poner(), seg.poner()), total = Math.max(ref.fin(), seg.fin()) - ini;
            var diff = Math.round((seg.fin() - ref.fin()) / 60000), a = Math.abs(diff);
            var tono = a <= 30 ? 'ok' : (a <= 60 ? 'warn' : 'bad');
            var txt = a === 0 ? 'Acaban a la vez.' : '#' + seg.id + ' acaba ' + a + ' min ' + (diff > 0 ? 'después' : 'antes') + ' que #' + ref.id + '.';
            if (a > 0 && a <= 30) txt += ' Bien: da tiempo a escurrir una y preparar la otra.';
            if (a > 60 && seg.porCapa) txt += ' Son unas ' + Math.round(a / seg.porCapa) + ' capas de diferencia.';
            var botones = '';
            if (seg.setPoner && +seg.poner() !== +ref.poner()) {
                botones += '<button type="button" class="fv-btn fv-btn-sm" data-sync-a="misma">Misma hora (' + hm(ref.poner()) + ')</button>';
            }
            var juntas = new Date(ref.fin().getTime() - (seg.fin() - seg.poner()));
            if (seg.setPoner && a > 30) {
                botones += '<button type="button" class="fv-btn fv-btn-sm" data-sync-a="juntas">Acabar a la vez (ponerla ' + hm(juntas) + ')</button>';
            }
            html = '<div class="en-sync ' + tono + '">' + fila(ref, ini, total, true) + fila(seg, ini, total, false)
                + '<div class="en-sync-ver"><span class="en-sync-txt">' + txt + '</span>' + botones + '</div></div>';
            window.enSyncAcciones = { misma: function () { seg.setPoner(ref.poner()); }, juntas: function () { seg.setPoner(juntas); } };
        }
        cajas.forEach(function (c) { c.innerHTML = html; });
    };
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-sync-a]');
        if (b && window.enSyncAcciones) window.enSyncAcciones[b.getAttribute('data-sync-a')]();
    });
    window.enSync();
})();
</script>

<?= $this->endSection() ?>
