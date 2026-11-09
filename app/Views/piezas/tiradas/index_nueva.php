<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    $enMarcha = 0;
    foreach ($filas as $f) {
        if (!($f['placas'] && $f['impresas'] === $f['placas'])) { $enMarcha++; }
    }
?>

<div class="fv pv">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Tiradas</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/placas') ?>" class="fv-gbtn" title="Histórico de placas"><i class="bi bi-printer"></i> Placas</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/tiradas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Tiradas</h1>
        <div class="fv-meta">
            <span><b><?= $enMarcha ?></b> en marcha</span>
            <span><?= count($filas) - $enMarcha ?> terminadas</span>
        </div>
    </div>

    <details class="fv-mas pv-ayuda">
        <summary><i class="bi bi-chevron-right ch"></i> ¿Qué es una tirada?</summary>
        <div class="fv-cuerpo">
            <span>Un encargo grande repartido en placas por días y máquinas: qué placa va antes, en qué impresora y con qué resina, cuándo se pasa del plazo si algo espera confirmación, y cuántas piezas buenas llevas de cada una frente a lo necesario.</span>
        </div>
    </details>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <section class="fv-panel pv-estrecha">
        <?php if ($filas === []): ?>
            <p class="fv-vacio">Todavía no hay tiradas.</p>
        <?php else: ?>
            <div class="pv-lista">
                <?php foreach ($filas as $f): ?>
                    <?php
                        $t = $f['tirada'];
                        $pct = $f['placas'] ? round($f['impresas'] / $f['placas'] * 100) : 0;
                        $terminada = $f['placas'] && $f['impresas'] === $f['placas'];
                        $fuera = !$terminada && $f['dia'] > (int) $t['plazo_dias'];
                    ?>
                    <a href="<?= site_url('piezas/tiradas/' . (int) $t['id']) ?>" class="pv-fila">
                        <span class="pv-ico"><i class="bi bi-calendar-range"></i></span>
                        <div class="pv-cuerpo">
                            <span class="pv-nom"><?= esc($t['nombre']) ?></span>
                            <span class="es-barra"><i class="<?= $terminada ? 'e-ok' : ($fuera ? 'e-bad' : 'e-acc') ?>" style="width: <?= $pct ?>%"></i></span>
                            <div class="pv-sub">
                                <?php if ($terminada): ?>
                                    <span class="fv-estado e-ok">Terminada</span>
                                <?php elseif ($fuera): ?>
                                    <span class="fv-estado e-bad">Fuera de plazo</span>
                                <?php else: ?>
                                    <span class="fv-estado e-acc">Día <?= (int) $f['dia'] ?> de <?= (int) $t['plazo_dias'] ?></span>
                                <?php endif; ?>
                                <?php if ($t['fecha_inicio']): ?><span>Desde el <?= esc(date('d/m/Y', strtotime($t['fecha_inicio']))) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <span class="pv-n"><?= (int) $f['impresas'] ?><small>/ <?= (int) $f['placas'] ?> placas</small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="fv-panel pv-estrecha">
        <div class="fv-ph"><h3>Tirada nueva</h3></div>
        <form method="post" action="<?= site_url('piezas/tiradas/crear') ?>" class="fv-campo">
            <?= csrf_field() ?>
            <input type="text" name="nombre" class="fv-input" style="flex: 2 1 200px" placeholder="Nombre" required>
            <input type="date" name="fecha_inicio" class="fv-input" style="flex: 0 0 150px" value="<?= date('Y-m-d') ?>" title="Día 1 de la tirada">
            <?php if ($filas !== []): ?>
                <select name="copiar_de" class="fv-input" style="flex: 1 1 180px" title="Trae sus piezas, placas, máquinas y notas, sin nada impreso">
                    <option value="">Vacía</option>
                    <?php foreach ($filas as $f): ?>
                        <option value="<?= (int) $f['tirada']['id'] ?>">Copia de <?= esc($f['tirada']['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i> Crear tirada</button>
        </form>
    </section>
</div>

<?= $this->endSection() ?>
