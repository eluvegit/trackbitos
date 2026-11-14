<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\MenuPrincipal;
$nAvisos = ($mostrarAlerta ? 1 : 0) + count($recordatoriosUrgentes);
?>
<div class="tb-dash">
    <?= $this->include('dashboard/_tb_cabecera') ?>
    <div class="tb-a">
        <?php if ($nAvisos): ?>
            <details class="tb-plegable tb-hoy-movil">
                <summary><i class="bi bi-bell e-<?= count(array_filter($recordatoriosUrgentes, fn ($r) => $r['nivel'] === 'caducado')) ? 'bad' : 'warn' ?>"></i>Avisos<span class="s"><?= $nAvisos ?> pendiente<?= $nAvisos === 1 ? '' : 's' ?></span><i class="bi bi-chevron-down"></i></summary>
                <div class="cuerpo"><?= $this->include('dashboard/_tb_avisos') ?></div>
            </details>
        <?php endif; ?>

        <div class="tb-tiles" id="dashboard-grid">
            <?php foreach (MenuPrincipal::modulos() as $m): $v = $vivos[$m['ruta']] ?? null; ?>
                <div class="tb-tile-w" data-key="<?= esc($m['ruta'], 'attr') ?>">
                    <span class="grip" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                    <a class="tb-tile" href="<?= site_url($m['ruta']) ?>" title="<?= esc($m['texto'], 'attr') ?>">
                        <?php if ($v && in_array($v['e'], ['warn', 'bad', 'info'], true)): ?><span class="tb-estado e-<?= $v['e'] ?>" title="<?= esc($v['t'], 'attr') ?>"></span><?php endif; ?>
                        <span class="ic"><i class="bi <?= $m['icono'] ?>"></i></span>
                        <span class="n"><?= esc($m['titulo']) ?></span>
                        <span class="d"><?= esc($m['corto']) ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <aside class="tb-col">
            <?php if ($nAvisos): ?>
                <div class="tb-panel tb-avisos-a">
                    <div class="tb-ph"><h3>Avisos</h3><span class="cuenta"><?= $nAvisos ?></span><a class="der" href="<?= site_url('recordatorios') ?>">Recordatorios</a></div>
                    <?= $this->include('dashboard/_tb_avisos') ?>
                </div>
            <?php endif; ?>
            <div class="tb-panel">
                <div class="tb-ph"><h3>Tareas fijadas</h3><span class="cuenta"><?= count($tareasFijadas) ?></span></div>
                <?= $this->include('dashboard/_tb_fijadas') ?>
            </div>
            <?php if (!empty($librosLeyendo)): ?>
                <div class="tb-panel">
                    <div class="tb-ph"><h3>Leyendo</h3></div>
                    <?= $this->include('dashboard/_tb_leyendo') ?>
                </div>
            <?php endif; ?>
            <div class="tb-panel">
                <div class="tb-ph"><h3>Atajos</h3></div>
                <?= $this->include('dashboard/_tb_atajos') ?>
            </div>
        </aside>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var grid = document.getElementById('dashboard-grid');
    var KEY = 'trackbitos_dashboard_order';
    var guardado = [];
    try { guardado = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) {}
    if (guardado.length) {
        var porClave = {};
        Array.from(grid.children).forEach(function (el) { porClave[el.dataset.key] = el; });
        guardado.forEach(function (k) { if (porClave[k]) { grid.appendChild(porClave[k]); delete porClave[k]; } });
        Object.keys(porClave).forEach(function (k) { grid.appendChild(porClave[k]); });
    }
    new Sortable(grid, {
        handle: '.grip', animation: 150, ghostClass: 'tb-ghost',
        onEnd: function () {
            try { localStorage.setItem(KEY, JSON.stringify(Array.from(grid.children).map(function (el) { return el.dataset.key; }))); } catch (e) {}
        }
    });
});
</script>
<?= $this->endSection() ?>
