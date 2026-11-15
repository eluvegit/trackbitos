<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — repaso diario (estilo fv): una tarjeta al azar cada vez,
 * tres decisiones (me lo quedo / fuera / luego), estrellas opcionales,
 * deshacer, racha y pausa cada 10.
 */
?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'activa' => 'repaso',
        'titulo' => 'Repaso',
        'sub'    => '<span>Un enlace guardado cada vez. Decide y pasa al siguiente.</span>',
    ]) ?>

    <?php if (!empty($sinMigrar)): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i>Falta pasar la migración del repaso: <code>php spark migrate</code>.</div>
    <?php else: ?>
        <div class="en-rep-cifras" id="repStats"><?= view('enlaces/_repaso_stats_nueva', ['stats' => $stats]) ?></div>

        <?php // Temática opcional: por defecto al azar, sin tener que decidir nada. ?>
        <div class="en-pills">
            <a href="<?= site_url('enlaces/repaso') ?>" class="en-chip<?= $cat === 0 ? ' on' : '' ?>"><i class="bi bi-shuffle"></i>Al azar</a>
            <?php foreach ($categorias as $c): ?>
                <a href="<?= site_url('enlaces/repaso') . '?cat=' . (int) $c['id'] ?>" class="en-chip<?= $cat === (int) $c['id'] ? ' on' : '' ?>"><?= esc($c['nombre']) ?> <small><?= (int) $c['total'] ?></small></a>
            <?php endforeach; ?>
        </div>

        <div id="repTarjeta"><?= view('enlaces/_repaso_tarjeta_nueva', ['tarjeta' => $tarjeta]) ?></div>

        <?php // Cada N tarjetas: parar o seguir, los dos botones igual de grandes. ?>
        <div class="fv-panel en-rep-vacio" id="repPausa" hidden>
            <div class="emoji">👏</div>
            <div>Llevas <b data-rep-pausa-n>10</b> hoy. ¿Sigues o lo dejas por hoy?</div>
            <div class="en-rep-acc" style="width:min(360px,100%)">
                <button type="button" class="en-rep-btn" id="repSigo">Sigo <kbd>Enter</kbd></button>
                <a href="<?= site_url('enlaces') ?>" class="en-rep-btn">Lo dejo por hoy</a>
            </div>
        </div>

        <div class="en-pista">Shift+1–5 estrellas · 1–3 decidir · O abrir · Z deshacer</div>
    <?php endif; ?>
</div>

<div class="fv en-toast" id="repToast" hidden>
    <span data-rep-toast-txt></span>
    <button type="button" id="repDeshacer">Deshacer <kbd>Z</kbd></button>
</div>

<?php if (empty($sinMigrar)): ?>
<script>
(() => {
    const ACCION_URL = '<?= site_url('enlaces/repaso/accion') ?>';
    const DESHACER_URL = '<?= site_url('enlaces/repaso/deshacer') ?>';
    const CAT = <?= (int) $cat ?>;
    const elTarjeta = document.getElementById('repTarjeta');
    const elStats = document.getElementById('repStats');
    const elPausa = document.getElementById('repPausa');
    const elToast = document.getElementById('repToast');
    const elToastTx = elToast.querySelector('[data-rep-toast-txt]');
    const MENSAJES = { visto: '✓ Te lo quedas', fuera: 'Archivado', luego: 'Vuelve en 7 días' };
    let ultimo = null, toastTimer = null, ocupado = false;

    async function enviar(url, datos) {
        if (ocupado) return;
        ocupado = true;
        elTarjeta.classList.add('en-cargando');
        try {
            const fd = new FormData();
            Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
            fd.append('cat', CAT);
            const r = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json().catch(() => ({}));
            if (!r.ok) { toast(d.error || 'No se pudo guardar', null); return; }
            pintar(d);
        } catch (e) {
            toast('Sin conexión, prueba otra vez', null);
        } finally {
            ocupado = false;
            elTarjeta.classList.remove('en-cargando');
        }
    }

    function pintar(d) {
        elTarjeta.innerHTML = d.tarjeta;
        elStats.innerHTML = d.stats;
        if (d.deshacer) {
            elStats.querySelector('[data-rep-hoy]')?.classList.add('pop');
            if (navigator.vibrate) navigator.vibrate(15);
            toast(MENSAJES[d.accion] || 'Hecho', d.deshacer);
        } else {
            ocultarToast();
        }
        if (d.pausa) {
            elPausa.querySelector('[data-rep-pausa-n]').textContent = d.hoy;
            elTarjeta.hidden = true;
            elPausa.hidden = false;
        }
    }
    function seguir() { elPausa.hidden = true; elTarjeta.hidden = false; }
    function toast(txt, id) {
        ultimo = id;
        elToastTx.textContent = txt;
        document.getElementById('repDeshacer').hidden = !id;
        elToast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(ocultarToast, 6000);
    }
    function ocultarToast() { elToast.hidden = true; ultimo = null; }

    function decidir(accion) {
        const card = elTarjeta.querySelector('.en-rep-card');
        if (!card || elTarjeta.hidden || !card.querySelector(`[data-accion="${accion}"]`)) return;
        const datos = { accion };
        const est = card.querySelector('.rep-estrellas');
        // Solo se mandan si las tocaste: si no, la relevancia no cambia.
        if (est && est.dataset.tocada) datos.estrellas = est.dataset.rel;
        enviar(ACCION_URL + '/' + card.dataset.id, datos);
    }
    // Tocar la que ya está marcada la quita (vuelve a 0).
    function puntuar(v) {
        const est = elTarjeta.querySelector('.en-rep-card .rep-estrellas');
        if (!est || elTarjeta.hidden) return;
        const nuevo = Number(est.dataset.rel) === v ? 0 : v;
        est.dataset.rel = nuevo;
        est.dataset.tocada = '1';
        est.querySelectorAll('.en-estrella').forEach(s => s.classList.toggle('on', Number(s.dataset.v) <= nuevo));
    }
    function deshacer() {
        if (!ultimo) return;
        const id = ultimo;
        ocultarToast();
        seguir();
        enviar(DESHACER_URL + '/' + id, {});
    }

    elTarjeta.addEventListener('click', e => {
        const s = e.target.closest('.en-estrella');
        if (s) { puntuar(Number(s.dataset.v)); return; }
        const b = e.target.closest('[data-accion]');
        if (b) decidir(b.dataset.accion);
    });
    document.getElementById('repDeshacer').addEventListener('click', deshacer);
    document.getElementById('repSigo').addEventListener('click', seguir);

    const TECLAS = { '1': 'visto', '2': 'fuera', '3': 'luego' };
    document.addEventListener('keydown', e => {
        if (e.ctrlKey || e.metaKey || e.altKey || e.target.closest('input, textarea, select, [contenteditable]')) return;
        const k = e.key.toLowerCase();
        if (!elPausa.hidden) { if (k === 'enter' || k === ' ') { e.preventDefault(); seguir(); } return; }
        const dig = /^Digit([1-5])$/.exec(e.code);
        if (e.shiftKey && dig) { e.preventDefault(); puntuar(Number(dig[1])); return; }
        if (TECLAS[k]) { e.preventDefault(); decidir(TECLAS[k]); }
        else if (k === 'o') { const a = elTarjeta.querySelector('[data-rep-abrir]'); if (a) { e.preventDefault(); window.open(a.href, '_blank', 'noopener'); } }
        else if (k === 'z') { e.preventDefault(); deshacer(); }
    });
})();
</script>
<?php endif; ?>
<?php $this->endSection(); ?>
