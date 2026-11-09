<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-varias.css') ?>?v=9">
<?php
    $activas    = array_values(array_filter($resinas, static fn ($r) => !$r['archivada']));
    $archivadas = array_values(array_filter($resinas, static fn ($r) => (bool) $r['archivada']));
    $enUso = 0;
    foreach ($enMaquina as $imps) { $enUso += count($imps); }

    $fila = static function (array $r) use ($usos, $enMaquina): string {
        $id = (int) $r['id'];
        $uso = $usos[$id] ?? null;
        ob_start(); ?>
        <div class="rs-fila<?= $r['archivada'] ? ' archivada' : '' ?>">
            <form method="post" action="<?= site_url('piezas/resinas/' . $id . '/actualizar') ?>" class="rs-form" data-rs-form>
                <?= csrf_field() ?>
                <input type="text" name="nombre" class="fv-input nom" maxlength="120" required value="<?= esc($r['nombre'], 'attr') ?>" title="Nombre">
                <input type="text" name="notas" class="fv-input notas" placeholder="Notas (proporciones, lote, exposición…)" value="<?= esc((string) $r['notas'], 'attr') ?>">
                <button class="fv-ibtn guardar" title="Guardar cambios"><i class="bi bi-check-lg"></i></button>
            </form>
            <div class="rs-pie">
                <?php foreach ($enMaquina[$id] ?? [] as $imp): ?>
                    <span class="fv-estado e-info">En la cubeta de <?= esc($imp) ?></span>
                <?php endforeach; ?>
                <span><i class="bi bi-grid-3x3"></i><?= $uso ? (int) $uso['n'] . ' placa' . ((int) $uso['n'] === 1 ? '' : 's') : 'Sin placas' ?></span>
                <?php if ($uso): ?>
                    <span><i class="bi bi-clock"></i>Última, el <?= esc(date('d/m/Y', strtotime($uso['ultima']))) ?></span>
                <?php endif; ?>
                <span class="der">
                    <form method="post" action="<?= site_url('piezas/resinas/' . $id . '/archivar') ?>"
                        <?= $r['archivada'] ? '' : 'onsubmit="return confirm(\'¿Archivar esta resina? Deja de salir en el selector de las placas nuevas.\');"' ?>>
                        <?= csrf_field() ?>
                        <button class="fv-btn fv-btn-sm" title="<?= $r['archivada'] ? 'Vuelve a salir en el selector' : 'Deja de salir en el selector de placas nuevas' ?>">
                            <i class="bi <?= $r['archivada'] ? 'bi-box-arrow-up' : 'bi-archive' ?>"></i> <?= $r['archivada'] ? 'Desarchivar' : 'Archivar' ?>
                        </button>
                    </form>
                    <?php if (!$uso): ?>
                        <form method="post" action="<?= site_url('piezas/resinas/' . $id . '/borrar') ?>" onsubmit="return confirm('¿Borrar esta resina?');">
                            <?= csrf_field() ?>
                            <button class="fv-ibtn peligro" title="Borrar (solo si no la usa ninguna placa)"><i class="bi bi-trash"></i></button>
                        </form>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php return ob_get_clean();
    };
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Resinas</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn" title="Histórico de placas"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/resinas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Resinas</h1>
        <div class="fv-meta">
            <span><b><?= count($activas) ?></b> en uso</span>
            <?php if ($archivadas): ?><span><?= count($archivadas) ?> archivada<?= count($archivadas) === 1 ? '' : 's' ?></span><?php endif; ?>
            <?php if ($enUso): ?>
                <span class="fv-dot"></span>
                <span class="fv-estado e-info"><?= $enUso ?> en la cubeta ahora</span>
            <?php endif; ?>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Para qué sirve?</summary>
        <div class="fv-cuerpo">
            <span>Define cada resina (o mezcla) <b>una vez</b> y elígela en la bitácora de cada placa. Una placa nueva hereda la de la anterior; en el selector se marca cuál sigue en la cubeta de cada impresora.</span>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('piezas/resinas/crear') ?>" class="fv-campo pv-estrecha">
        <?= csrf_field() ?>
        <input type="text" name="nombre" class="fv-input" maxlength="120" required placeholder="Nueva resina: Elegoo ABS-like standard Grey + 66 g RESIONE F80">
        <button class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i> Añadir</button>
    </form>

    <section class="fv-panel pv-estrecha">
        <div class="fv-ph"><h3>En uso</h3><span class="cuenta"><?= count($activas) ?></span></div>
        <?php if ($activas === []): ?>
            <p class="fv-vacio">Todavía no hay resinas.</p>
        <?php else: ?>
            <div>
                <?php foreach ($activas as $r): ?><?= $fila($r) ?><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($archivadas !== []): ?>
        <details class="fv-panel pv-estrecha fv-mas">
            <summary><i class="bi bi-chevron-right ch"></i> <span class="t2">Archivadas</span> <span class="fv-mono t3"><?= count($archivadas) ?></span></summary>
            <div style="margin-top: 14px">
                <?php foreach ($archivadas as $r): ?><?= $fila($r) ?><?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-rs-form]').forEach(function (f) {
    f.addEventListener('input', function () { f.classList.add('cambiada'); });
});
</script>

<?= $this->endSection() ?>
