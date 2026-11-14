<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/journal.css') ?>?v=8">
<?php
$horas = function ($min) {
    $h = (int) $min / 60;
    if ($h <= 0) return '';
    return ($h >= 10 ? (string) round($h) : str_replace('.', ',', (string) round($h, 1))) . ' h';
};
$pips = function ($cat) {
    $h = '<span class="qn-pips" data-cat-id="' . (int) $cat['id'] . '" data-peso="' . (int) $cat['peso'] . '">';
    for ($k = 1; $k <= 5; $k++) {
        $h .= '<button type="button" class="' . ($k <= (int) $cat['peso'] ? 'on' : '') . '" data-k="' . $k . '" aria-label="Peso ' . $k . '"></button>';
    }
    return $h . '</span>';
};
?>
<div class="fv jq" id="jq">
    <div class="jn-top">
        <a class="jf-volver" href="<?= site_url('journal') ?>"><i class="bi bi-chevron-left"></i>Journal</a>
        <span style="margin-right:auto"></span>
        <button type="button" class="jq-ronda" id="jqRonda"><i class="bi bi-shuffle"></i>Otra ronda</button>
        <a class="jn-ib" href="<?= site_url('journal/que-hacer') ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a>
    </div>
    <h1 class="jq-h1">¿Qué hago ahora?</h1>

    <div class="jq-grupos" id="jqGrupos">
        <?php if (empty($sugeridos)): ?>
            <p class="jn-vacio" style="display:block">No hay categorías para sugerir: revisa que alguna tenga peso mayor que 0 y tareas.</p>
        <?php endif; ?>
        <?php foreach ($sugeridos as $s): $cat = $s['categoria']; ?>
            <div class="jq-grupo">
                <div class="jq-gcab">
                    <span class="jn-dot" style="background:<?= esc($cat['color'] ?: '#666', 'attr') ?>"></span>
                    <b><?= esc($cat['name']) ?></b>
                    <span class="jq-dias"><?= $s['dias'] ?> d sin tocar</span>
                    <?= $pips($cat) ?>
                </div>
                <?php foreach ($s['tareas_muestra'] as $t):
                    $amp = (int) ($t['amplitude'] ?? 0);
                    $prog = $amp === 100 ? (int) $t['completed'] . '%' : ($amp > 0 ? (int) $t['completed'] . '/' . $amp : '');
                    $dato = trim($prog . ($prog && $horas($t['time_spent'] ?? 0) ? ' · ' : '') . $horas($t['time_spent'] ?? 0));
                ?>
                    <div class="jq-t">
                        <button type="button" class="jn-star js-star <?= !empty($t['is_current']) ? 'on' : '' ?>" data-id="<?= (int) $t['id'] ?>" title="Estrella"><i class="bi bi-star-fill"></i></button>
                        <a class="nm" href="<?= site_url('journal/edit/' . $t['id']) ?>"><?= esc($t['title']) ?><?php if ($dato): ?><small><?= $dato ?></small><?php endif; ?></a>
                        <button type="button" class="jq-play js-play" data-id="<?= (int) $t['id'] ?>" data-title="<?= esc($t['title'], 'attr') ?>" title="Empezar"><i class="bi bi-play-fill"></i></button>
                    </div>
                <?php endforeach; ?>
                <?php if ($s['tareas_total'] > count($s['tareas_muestra'])): ?>
                    <p class="jq-mas">+ <?= $s['tareas_total'] - count($s['tareas_muestra']) ?> más en <?= esc($cat['name']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <details class="jq-pesos">
        <summary><i class="bi bi-chevron-right"></i>Pesos de todas las categorías<span class="mono"><?= count($categorias) ?></span></summary>
        <p class="jq-ayuda">Más peso, más papeletas en el sorteo. Con 0 no sale nunca: toca la casilla encendida más alta para bajarla.</p>
        <?php foreach ($categorias as $cat): ?>
            <div class="jq-peso">
                <span class="jn-dot" style="background:<?= esc($cat['color'] ?: '#666', 'attr') ?>"></span>
                <span class="nm"><?= esc($cat['name']) ?></span>
                <span class="h"><?= esc(str_replace('.', ',', (string) ($horasPorCategoria[$cat['name']] ?? 0))) ?> h</span>
                <?= $pips($cat) ?>
            </div>
        <?php endforeach; ?>
    </details>
</div>
<?= view('journal/_reloj') ?>
<script>
(() => {
    const URL = '<?= site_url('journal') ?>';
    const jq = document.getElementById('jq');

    jq.addEventListener('click', async e => {
        const star = e.target.closest('.js-star');
        if (star) {
            const d = await fetch(URL + '/toggle-current/' + star.dataset.id, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
            star.classList.toggle('on', !!d.is_current);
            return;
        }
        const play = e.target.closest('.js-play');
        if (play) {
            window.JReloj && window.JReloj.empezar(play.dataset.id, play.dataset.title);
            return;
        }
        const pip = e.target.closest('.qn-pips button');
        if (pip) {
            const caja = pip.parentElement;
            const k = parseInt(pip.dataset.k, 10);
            const nuevo = parseInt(caja.dataset.peso, 10) === k ? k - 1 : k;
            const res = await fetch(URL + '/categorias/' + caja.dataset.catId + '/peso', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'peso=' + nuevo,
            });
            if (!res.ok) return;
            const d = await res.json();
            if (!d.success) return;
            jq.querySelectorAll(`.qn-pips[data-cat-id="${caja.dataset.catId}"]`).forEach(c => {
                c.dataset.peso = d.peso;
                c.querySelectorAll('button').forEach(b => b.classList.toggle('on', parseInt(b.dataset.k, 10) <= d.peso));
            });
        }
    });

    document.getElementById('jqRonda').addEventListener('click', async function () {
        this.disabled = true;
        try {
            const html = await fetch(location.pathname, { headers: { 'X-Requested-With': 'fetch' } }).then(r => r.text());
            const nuevo = new DOMParser().parseFromString(html, 'text/html').getElementById('jqGrupos');
            if (nuevo) document.getElementById('jqGrupos').replaceWith(nuevo);
        } finally {
            this.disabled = false;
        }
    });
})();
</script>
<?= $this->endSection() ?>
