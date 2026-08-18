<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-pc-display text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Máquinas</strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Cada equipo se da de alta solo la primera vez que ejecutas el script, con el nombre que trae
    el sistema (<code>DESKTOP-4F2K1</code> y cosas así). Aquí puedes ponerle uno que se entienda:
    es el que aparece en los avisos de <em>«sesión abierta en…»</em>, donde tiene que quedar claro
    a qué equipo ir a mirar. Lo demás no se toca —
    el <abbr title="Identificador único que genera el propio script">UUID</abbr> es su identidad y
    lo pone el cliente, no el navegador.
</p>

<?php if (empty($maquinas)): ?>
    <p class="text-muted">
        Todavía no se ha registrado ninguna máquina. Aparecerán solas en cuanto ejecutes
        <code>trackbitos</code> en cada equipo.
    </p>
<?php else: ?>
    <?php foreach ($maquinas as $m): ?>
        <div class="card shadow-sm mb-2">
            <div class="card-body p-3">
                <form method="post" action="<?= site_url('piezas/maquina/' . (int) $m['id'] . '/renombrar') ?>"
                    class="d-flex gap-2 align-items-center flex-wrap mb-2">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" value="<?= esc($m['nombre'], 'attr') ?>"
                        class="form-control form-control-sm" style="max-width: 16rem;" maxlength="100" required>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-check-lg"></i> Guardar
                    </button>

                    <?php if ($m['sesiones_abiertas'] > 0): ?>
                        <span class="badge text-bg-warning ms-auto">
                            <i class="bi bi-lock-fill"></i>
                            <?= (int) $m['sesiones_abiertas'] ?> sesión(es) abierta(s) aquí
                        </span>
                    <?php endif; ?>
                </form>

                <div class="small text-muted d-flex gap-3 flex-wrap">
                    <span><i class="bi bi-hdd-network"></i> <?= esc($m['hostname'] ?? '—') ?></span>
                    <span><i class="bi bi-window"></i> <?= esc($m['so'] ?? '—') ?></span>
                    <span title="<?= esc($m['ultima_vez'] ?? '', 'attr') ?>">
                        <i class="bi bi-clock"></i>
                        <?php if (empty($m['ultima_vez'])): ?>
                            sin usar todavía
                        <?php elseif ($m['dias_sin_verse'] === 0): ?>
                            vista hoy
                        <?php else: ?>
                            vista hace <?= (int) $m['dias_sin_verse'] ?> día(s)
                        <?php endif; ?>
                    </span>
                    <span class="font-monospace text-truncate" title="<?= esc($m['uuid'], 'attr') ?>">
                        <i class="bi bi-fingerprint"></i> <?= esc(substr($m['uuid'], 0, 8)) ?>…
                    </span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <p class="text-muted small mt-3 mb-0">
        Si reinstalas el sistema de un equipo, el script genera un UUID nuevo y aparecerá aquí como
        una máquina distinta. Es lo correcto: el disco anterior ya no existe, y lo que se quedara
        descargado en él necesita cierre forzado de todos modos.
    </p>
<?php endif; ?>

<?= $this->endSection() ?>
