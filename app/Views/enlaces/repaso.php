<!-- app/Views/enlaces/repaso.php -->
<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3 rep-wrap">

    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <h5 class="mb-0">Repaso de enlaces</h5>
        <a href="<?= site_url('enlaces') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
    </div>

    <?php if (!empty($sinMigrar)): ?>
        <div class="alert alert-warning">Falta pasar la migración del repaso: <code>php spark migrate</code>.</div>
    <?php else: ?>

        <div class="rep-stats" id="repStats"><?= $this->include('enlaces/_repaso_stats') ?></div>

        <?php // Temática opcional: por defecto al azar, sin tener que decidir nada. ?>
        <div class="rep-temas">
            <a href="<?= site_url('enlaces/repaso') ?>" class="rep-tema<?= $cat === 0 ? ' active' : '' ?>">🎲 Al azar</a>
            <?php foreach ($categorias as $c): ?>
                <a href="<?= site_url('enlaces/repaso') . '?cat=' . (int) $c['id'] ?>"
                   class="rep-tema<?= $cat === (int) $c['id'] ? ' active' : '' ?>">
                    <?= esc($c['nombre']) ?> <span><?= (int) $c['total'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div id="repTarjeta"><?= view('enlaces/_repaso_tarjeta', ['tarjeta' => $tarjeta]) ?></div>

        <?php // Cada N tarjetas: parar o seguir, los dos botones igual de grandes. ?>
        <div class="rep-pausa" id="repPausa" hidden>
            <div class="rep-vacio-emoji">👏</div>
            <p class="mb-3">Llevas <strong data-rep-pausa-n>10</strong> hoy. ¿Sigues o lo dejas por hoy?</p>
            <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-outline-primary rep-pausa-btn" id="repSigo">Sigo <kbd>Enter</kbd></button>
                <a href="<?= site_url('enlaces') ?>" class="btn btn-outline-primary rep-pausa-btn">Lo dejo por hoy</a>
            </div>
        </div>

        <div class="rep-hint text-muted small">Shift+1–5 estrellas · 1–3 decidir · O abrir · Z deshacer</div>

        <div class="rep-toast" id="repToast" hidden>
            <span data-rep-toast-txt></span>
            <button type="button" class="btn btn-sm btn-link" id="repDeshacer">Deshacer <kbd>Z</kbd></button>
        </div>
    <?php endif; ?>
</div>

<style>
    .rep-wrap { max-width: 640px; }

    .rep-stats {
        display: flex; flex-wrap: wrap; align-items: center; gap: 10px 20px;
        margin-bottom: 14px;
    }
    .rep-stat { display: flex; align-items: baseline; gap: 6px; }
    .rep-stat-num { font-size: 1.6rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1; }
    .rep-stat-label { color: var(--bs-secondary-color); font-size: .85rem; }
    .rep-stat.is-pendiente .rep-stat-num { opacity: .55; }
    .rep-stat-num.rep-pop { animation: rep-pop .35s ease; }
    @keyframes rep-pop { 50% { transform: scale(1.25); } }
    .rep-stat-mini {
        flex-basis: 100%;
        display: flex; flex-wrap: wrap; gap: 4px 12px;
        font-size: .75rem; color: var(--bs-secondary-color);
    }

    .rep-temas { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
    .rep-tema {
        padding: 3px 10px; border-radius: 999px; font-size: .8rem;
        border: 1px solid var(--bs-border-color); color: var(--bs-body-color);
        text-decoration: none; white-space: nowrap;
    }
    .rep-tema span { font-size: .68rem; opacity: .6; font-variant-numeric: tabular-nums; }
    .rep-tema:hover { background: var(--bs-tertiary-bg); }
    .rep-tema.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

    .rep-card, .rep-vacio, .rep-pausa {
        border: 1px solid var(--bs-border-color); border-radius: 16px;
        background: var(--bs-body-bg); padding: 20px;
    }
    .rep-card { animation: rep-in .22s ease; }
    @keyframes rep-in { from { opacity: 0; transform: translateY(8px); } }
    .rep-vacio, .rep-pausa { text-align: center; padding: 32px 20px; }
    .rep-vacio-emoji { font-size: 2.4rem; margin-bottom: 8px; }

    .rep-meta { display: flex; align-items: center; gap: 6px; font-size: .8rem; color: var(--bs-secondary-color); margin-bottom: 8px; }
    .rep-titulo {
        display: block; font-size: 1.25rem; font-weight: 600; line-height: 1.3;
        color: var(--bs-emphasis-color); text-decoration: none; word-break: break-word;
    }
    .rep-titulo:hover { text-decoration: underline; }
    .rep-cats { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .rep-cat { font-size: .75rem; padding: 2px 8px; border-radius: 999px; background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); }
    .rep-nota { margin-top: 12px; font-size: .9rem; color: var(--bs-secondary-color); white-space: pre-line; }
    .rep-estrellas { display: flex; align-items: center; gap: 2px; margin-top: 12px; }
    .rep-estrella {
        border: none; background: none; padding: 0 2px; font-size: 1.5rem; line-height: 1;
        color: var(--bs-border-color); cursor: pointer; transition: transform .08s ease, color .1s ease;
    }
    .rep-estrella.is-on { color: #f59e0b; }
    .rep-estrella:hover { transform: scale(1.15); }
    .rep-estrellas-hint { margin-left: 8px; font-size: .68rem; color: var(--bs-secondary-color); opacity: .7; }
    .rep-abrir-fila { margin-top: 14px; display: flex; align-items: center; gap: 4px; }

    .rep-acciones { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 16px; }
    .rep-btn {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        min-height: 52px; padding: 8px; border-radius: 12px;
        border: 1px solid var(--bs-border-color); background: var(--bs-body-bg);
        color: var(--bs-body-color); font-weight: 500; cursor: pointer;
        transition: background-color .12s ease, transform .08s ease;
    }
    .rep-btn:hover { background: var(--bs-tertiary-bg); }
    .rep-btn:active { transform: scale(.97); }
    .rep-btn-ico { font-size: 1.1rem; }
    .rep-btn-visto { grid-column: 1 / -1; }
    .rep-btn-visto:hover  { border-color: #10b981; }
    .rep-btn-fuera:hover  { border-color: #94a3b8; }
    .rep-btn-luego:hover  { border-color: #7c3aed; }
    .rep-btn.is-off { cursor: default; opacity: .5; font-size: .78rem; font-weight: 400; text-align: center; }
    .rep-btn.is-off:hover { background: var(--bs-body-bg); border-color: var(--bs-border-color); }
    .rep-btn kbd, .rep-abrir-fila kbd, .rep-pausa kbd, .rep-toast kbd {
        font-size: .65rem; padding: 1px 5px; opacity: .55;
        background: var(--bs-tertiary-bg); color: var(--bs-body-color);
    }
    #repTarjeta.rep-cargando { opacity: .5; pointer-events: none; }

    .rep-pausa-btn { min-width: 150px; }
    .rep-hint { text-align: center; margin-top: 12px; }

    .rep-toast {
        position: fixed; left: 50%; bottom: 20px; transform: translateX(-50%);
        display: flex; align-items: center; gap: 8px;
        padding: 8px 8px 8px 16px; border-radius: 12px;
        background: var(--bs-emphasis-color); color: var(--bs-body-bg);
        box-shadow: 0 6px 24px rgba(0,0,0,.2); z-index: 1080;
        max-width: calc(100% - 32px);
    }
    .rep-toast .btn-link { color: inherit; font-weight: 600; }
    .rep-toast kbd { background: transparent; color: inherit; }

    @media (max-width: 420px) {
        .rep-btn kbd, .rep-abrir-fila kbd, .rep-hint, .rep-estrellas-hint { display: none; }
    }
</style>

<?php if (empty($sinMigrar)): ?>
<script>
(() => {
    const ACCION_URL   = '<?= site_url('enlaces/repaso/accion') ?>';
    const DESHACER_URL = '<?= site_url('enlaces/repaso/deshacer') ?>';
    const CAT = <?= (int) $cat ?>;

    const elTarjeta = document.getElementById('repTarjeta');
    const elStats   = document.getElementById('repStats');
    const elPausa   = document.getElementById('repPausa');
    const elToast   = document.getElementById('repToast');
    const elToastTx = elToast.querySelector('[data-rep-toast-txt]');

    const MENSAJES = {
        visto:  '✓ Te lo quedas',
        fuera:  '🗃 Archivado',
        luego:  '↷ Vuelve en 7 días',
    };

    let ultimoRepaso = null;
    let toastTimer = null;
    let ocupado = false;

    async function enviar(url, datos) {
        if (ocupado) return;
        ocupado = true;
        elTarjeta.classList.add('rep-cargando');
        try {
            const fd = new FormData();
            Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
            fd.append('cat', CAT);
            const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { mostrarToast(data.error || 'No se pudo guardar', null); return; }
            pintar(data);
        } catch (err) {
            mostrarToast('Sin conexión, prueba otra vez', null);
        } finally {
            ocupado = false;
            elTarjeta.classList.remove('rep-cargando');
        }
    }

    function pintar(data) {
        elTarjeta.innerHTML = data.tarjeta;
        elStats.innerHTML = data.stats;
        if (data.deshacer) {
            const hoy = elStats.querySelector('[data-rep-hoy]');
            if (hoy) hoy.classList.add('rep-pop');
            mostrarToast(MENSAJES[data.accion] || 'Hecho', data.deshacer);
        } else {
            ocultarToast();
        }
        if (data.pausa) {
            elPausa.querySelector('[data-rep-pausa-n]').textContent = data.hoy;
            elTarjeta.hidden = true;
            elPausa.hidden = false;
        }
    }

    function seguir() {
        elPausa.hidden = true;
        elTarjeta.hidden = false;
    }

    function mostrarToast(texto, repasoId) {
        ultimoRepaso = repasoId;
        elToastTx.textContent = texto;
        document.getElementById('repDeshacer').hidden = !repasoId;
        elToast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(ocultarToast, 6000);
    }

    function ocultarToast() {
        elToast.hidden = true;
        ultimoRepaso = null;
    }

    function decidir(accion) {
        const card = elTarjeta.querySelector('.rep-card');
        if (!card || elTarjeta.hidden) return;
        if (!card.querySelector(`[data-accion="${accion}"]`)) return; // p.ej. "luego" agotado
        const datos = { accion };
        const est = card.querySelector('.rep-estrellas');
        // Solo se mandan si las tocaste: si no, la relevancia no se toca.
        if (est && est.dataset.tocada) datos.estrellas = est.dataset.rel;
        enviar(ACCION_URL + '/' + card.dataset.id, datos);
    }

    // Estrellas: tocar la misma que ya está marcada la quita (vuelve a 0).
    function puntuar(v) {
        const est = elTarjeta.querySelector('.rep-card .rep-estrellas');
        if (!est || elTarjeta.hidden) return;
        const nuevo = Number(est.dataset.rel) === v ? 0 : v;
        est.dataset.rel = nuevo;
        est.dataset.tocada = '1';
        est.querySelectorAll('.rep-estrella').forEach(s => s.classList.toggle('is-on', Number(s.dataset.v) <= nuevo));
    }

    function deshacer() {
        if (!ultimoRepaso) return;
        const id = ultimoRepaso;
        ocultarToast();
        seguir();
        enviar(DESHACER_URL + '/' + id, {});
    }

    elTarjeta.addEventListener('click', (ev) => {
        const s = ev.target.closest('.rep-estrella');
        if (s) { puntuar(Number(s.dataset.v)); return; }
        const b = ev.target.closest('[data-accion]');
        if (b) decidir(b.dataset.accion);
    });
    document.getElementById('repDeshacer').addEventListener('click', deshacer);
    document.getElementById('repSigo').addEventListener('click', seguir);

    const TECLAS = { '1': 'visto', '2': 'fuera', '3': 'luego' };
    document.addEventListener('keydown', (ev) => {
        if (ev.ctrlKey || ev.metaKey || ev.altKey) return;
        if (ev.target.closest('input, textarea, select, [contenteditable]')) return;
        const k = ev.key.toLowerCase();

        if (!elPausa.hidden) {
            if (k === 'enter' || k === ' ') { ev.preventDefault(); seguir(); }
            return;
        }
        // Shift+1..5 → estrellas (por código de tecla: con Shift, ev.key es "!", "\"", …)
        const dig = /^Digit([1-5])$/.exec(ev.code);
        if (ev.shiftKey && dig) { ev.preventDefault(); puntuar(Number(dig[1])); return; }
        if (TECLAS[k]) { ev.preventDefault(); decidir(TECLAS[k]); }
        else if (k === 'o') {
            const a = elTarjeta.querySelector('[data-rep-abrir]');
            if (a) { ev.preventDefault(); window.open(a.href, '_blank', 'noopener'); }
        }
        else if (k === 'z') { ev.preventDefault(); deshacer(); }
    });
})();
</script>
<?php endif; ?>
<?php $this->endSection(); ?>
