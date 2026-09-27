<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('dashboard') ?>">Dashboard</a> / <a href="<?= site_url('silo') ?>">Silo</a> / Tareas
</div>
<h1 class="silo-control-titulo">Lista de <strong>tareas</strong></h1>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<p class="text-muted small">
    Lo que queda por hacer en disco. «Renombrar» / «Mover» salen solos al escanear, cuando una
    carpeta del Maestro cambió de nombre y su Copia 2/3 se quedó con el nombre o la carpeta de
    año/temática antigua: hazlo en disco y pulsa <strong>Hecho</strong> — aquí no se mueve nada.
</p>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $verCerradas ? '' : 'active' ?>" href="<?= site_url('silo/tareas') ?>">
            Pendientes <span class="badge text-bg-secondary"><?= (int) $pendientes ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $verCerradas ? 'active' : '' ?>" href="<?= site_url('silo/tareas?ver=cerradas') ?>">Cerradas</a>
    </li>
</ul>

<?php
$tipoInfo = [
    'renombrar_copia' => ['Renombrar', 'bi-pencil', 'text-warning'],
    'mover_copia'     => ['Mover', 'bi-arrow-left-right', 'text-warning'],
    'escaneo_maestro' => ['Escanear', 'bi-arrow-repeat', 'text-info'],
];
$estadoClase = [
    'pendiente' => 'text-bg-warning',
    'en_curso'  => 'text-bg-info',
    'hecha'     => 'text-bg-success',
    'error'     => 'text-bg-danger',
    'cancelada' => 'text-bg-secondary',
];
$copiaLabel = [1 => 'Maestro', 2 => 'Año', 3 => 'Temática'];
$nombreUnidad = static fn (?array $u) => $u ? 'Nivel ' . (int) $u['nivel'] . ' #' . (int) $u['numero'] : '';
?>

<?php if (empty($tareas)): ?>
    <p class="text-success-emphasis"><i class="bi bi-check2-circle me-1"></i><?= $verCerradas ? 'Todavía no hay tareas cerradas.' : 'Nada pendiente.' ?></p>
<?php else: ?>
    <table class="table table-sm silo-tabla-control align-middle">
        <thead>
            <tr><th>Tarea</th><th>Unidad</th><th>Detalle</th><th><?= $verCerradas ? 'Estado' : '' ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($tareas as $t): ?>
                <?php
                [$tipoTexto, $tipoIcono, $tipoColor] = $tipoInfo[$t['tipo']] ?? [$t['tipo'], 'bi-gear', 'text-secondary'];
                $d        = $t['datos'];
                $pieza    = $piezas[(int) ($d['pieza_id'] ?? 0)] ?? null;
                $destino  = $unidades[(int) ($d['unidad_destino_id'] ?? 0)] ?? null;
                $esCopia  = in_array($t['tipo'], ['renombrar_copia', 'mover_copia'], true);
                $unidadTx = $t['nivel'] !== null ? 'Nivel ' . (int) $t['nivel'] . ' #' . (int) $t['numero'] : '—';
                ?>
                <tr>
                    <td class="text-nowrap">
                        <i class="bi <?= $tipoIcono ?> <?= $tipoColor ?> me-1"></i><?= esc($tipoTexto) ?>
                        <div class="text-muted" style="font-size: .7rem;">#<?= (int) $t['id'] ?> · <?= esc(substr((string) $t['creado_en'], 0, 16)) ?></div>
                    </td>
                    <td class="text-nowrap">
                        <?php if ($t['unidad_id']): ?>
                            <a href="<?= site_url('silo/unidades/' . $t['unidad_id']) ?>" class="text-decoration-none"><?= esc($unidadTx) ?></a>
                        <?php else: ?>—<?php endif; ?>
                        <?php if ($esCopia): ?>
                            <div class="text-muted" style="font-size: .7rem;">Copia <?= esc($copiaLabel[(int) ($d['copia'] ?? 0)] ?? '') ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($esCopia): ?>
                            <?php if ($pieza): ?>
                                <a href="<?= site_url('silo/' . $pieza['id']) ?>" class="text-decoration-none">#<?= esc($pieza['id_negocio']) ?></a>
                            <?php endif; ?>
                            <div><span class="text-muted">de</span> <code class="silo-mono"><?= esc($d['desde'] ?? '') ?></code></div>
                            <div><span class="text-muted">a&nbsp;&nbsp;</span> <code class="silo-mono"><?= esc($d['hasta'] ?? '') ?></code></div>
                            <?php if ($t['tipo'] === 'mover_copia'): ?>
                                <div class="text-muted">
                                    <?php if ($destino): ?>
                                        en <?= esc($nombreUnidad($destino)) ?><?= (int) $destino['id'] === (int) $t['unidad_id'] ? ' (la misma unidad)' : '' ?>
                                    <?php else: ?>
                                        <span class="text-danger-emphasis fst-italic">sin unidad de destino con sitio</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($t['error']): ?>
                            <span class="text-danger-emphasis"><?= esc($t['error']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($verCerradas): ?>
                            <span class="badge <?= $estadoClase[$t['estado']] ?? 'text-bg-secondary' ?>"><?= esc($t['estado']) ?></span>
                        <?php elseif ($esCopia && ($t['tipo'] === 'renombrar_copia' || $destino)): ?>
                            <form method="post" action="<?= site_url('silo/reubicacion/' . $t['id'] . '/hecha') ?>"
                                  onsubmit="return confirm('¿Ya está hecho en disco? Solo actualiza el catálogo.')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-check2"></i> Hecho</button>
                            </form>
                        <?php else: ?>
                            <span class="badge <?= $estadoClase[$t['estado']] ?? 'text-bg-secondary' ?>"><?= esc($t['estado']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?= $this->endSection() ?>
