<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    $abiertas = 0;
    foreach ($maquinas as $m) { $abiertas += (int) $m['sesiones_abiertas']; }
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Máquinas</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/maquinas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Máquinas</h1>
        <div class="fv-meta">
            <span><b><?= count($maquinas) ?></b> equipo<?= count($maquinas) === 1 ? '' : 's' ?></span>
            <?php if ($abiertas): ?>
                <span class="fv-dot"></span>
                <span class="fv-estado e-warn"><?= $abiertas ?> sesión<?= $abiertas === 1 ? '' : 'es' ?> abierta<?= $abiertas === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿De dónde salen?</summary>
        <div class="fv-cuerpo">
            <span>Cada equipo se da de alta solo la primera vez que ejecutas el script, con el nombre que trae el sistema (<code>DESKTOP-4F2K1</code> y cosas así). Aquí le pones uno que se entienda: es el que sale en los avisos de <em>«sesión abierta en…»</em>. El <b>UUID</b> es su identidad y lo pone el script, no el navegador.</span>
            <span>Si reinstalas el sistema de un equipo, aparecerá como una máquina distinta. Es lo correcto: lo que quedara descargado en el disco anterior necesita cierre forzado de todos modos.</span>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <section class="fv-panel pv-estrecha">
        <?php if (empty($maquinas)): ?>
            <p class="fv-vacio">Todavía no hay ninguna. Aparecerán solas en cuanto ejecutes <code>trackbitos</code> en cada equipo.</p>
        <?php else: ?>
            <div>
                <?php foreach ($maquinas as $m): ?>
                    <div class="rs-fila">
                        <form method="post" action="<?= site_url('piezas/maquina/' . (int) $m['id'] . '/renombrar') ?>" class="rs-form" data-rs-form>
                            <?= csrf_field() ?>
                            <input type="text" name="nombre" value="<?= esc($m['nombre'], 'attr') ?>" class="fv-input nom" maxlength="100" required title="Pulsa para renombrar">
                            <button class="fv-ibtn guardar" title="Guardar nombre"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <div class="rs-pie">
                            <?php if ($m['sesiones_abiertas'] > 0): ?>
                                <span class="fv-estado e-warn"><?= (int) $m['sesiones_abiertas'] ?> sesión<?= (int) $m['sesiones_abiertas'] === 1 ? '' : 'es' ?> abierta<?= (int) $m['sesiones_abiertas'] === 1 ? '' : 's' ?> aquí</span>
                            <?php endif; ?>
                            <span title="<?= esc($m['ultima_vez'] ?? '', 'attr') ?>"><i class="bi bi-clock"></i><?php if (empty($m['ultima_vez'])): ?>Sin usar todavía<?php elseif ($m['dias_sin_verse'] === 0): ?>Vista hoy<?php else: ?>Vista hace <?= (int) $m['dias_sin_verse'] ?> día<?= (int) $m['dias_sin_verse'] === 1 ? '' : 's' ?><?php endif; ?></span>
                            <span><i class="bi bi-hdd-network"></i><?= esc($m['hostname'] ?? '—') ?></span>
                            <span><i class="bi bi-window"></i><?= esc($m['so'] ?? '—') ?></span>
                            <span class="fv-mono" title="<?= esc($m['uuid'], 'attr') ?>"><i class="bi bi-fingerprint"></i><?= esc(substr($m['uuid'], 0, 8)) ?>…</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.querySelectorAll('[data-rs-form]').forEach(function (f) {
    f.addEventListener('input', function () { f.classList.add('cambiada'); });
});
</script>

<?= $this->endSection() ?>
