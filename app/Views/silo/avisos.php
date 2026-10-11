<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('dashboard') ?>">Dashboard</a> / <a href="<?= site_url('silo') ?>">Silo</a> / Avisos
    <span class="silo-control-iconos">
        <a href="<?= site_url('silo/tareas') ?>" title="Tareas pendientes"><i class="bi bi-list-check"></i></a>
    </span>
</div>
<h1 class="silo-control-titulo">Panel de <strong>avisos</strong></h1>

<?php
$tipoInfo = [
    'id_duplicado'         => ['IDs duplicados', 'bi-exclamation-octagon-fill', 'danger',
        'Dos carpetas con el mismo ID: solo una llega al catálogo. Renombra la otra con un ID nuevo.'],
    'error_ingesta'        => ['Errores al ingestar', 'bi-x-octagon-fill', 'danger',
        'Estas carpetas no se pudieron dar de alta o actualizar en esta pasada.'],
    'hash_distinto'        => ['Contenido distinto con la misma fecha', 'bi-shield-exclamation', 'danger',
        'Mismo tamaño y fecha que en la última sincronización pero otro contenido: posible corrupción del disco. Compáralo con otra copia.'],
    'ficheros_cambiados'   => ['Carpetas con ficheros cambiados', 'bi-pencil-square', 'info',
        'Se añadieron, borraron o modificaron ficheros desde el escaneo anterior (si fueron fotos o vídeos, se regeneran sus miniaturas).'],
    'carpeta_desaparecida' => ['Carpetas desaparecidas', 'bi-folder-x', 'warning',
        'Ya no estaban en el Maestro: sus piezas se borraron del catálogo.'],
];
$motivoInfo = [
    'no_es_pieza'   => 'Carpetas sin ID de pieza al principio',
    'no_es_carpeta' => 'Ficheros sueltos en la raíz',
    'prefijo'       => 'Empiezan por «.», «_» o «~» (ocultas)',
    'lista_negra'   => 'En la lista negra del agente',
];
$historialInfo = [
    'escaneo'              => ['Escaneo', 'text-bg-secondary'],
    'ficheros_cambiados'   => ['Cambios', 'text-bg-info'],
    'hash_distinto'        => ['Contenido distinto', 'text-bg-danger'],
    'catalogo_mas_nuevo'   => ['Réplica más nueva', 'text-bg-danger'],
    'catalogo_restaurado'  => ['Restaurado', 'text-bg-success'],
    'id_duplicado'         => ['ID duplicado', 'text-bg-danger'],
    'error_ingesta'        => ['Error', 'text-bg-danger'],
    'carpeta_desaparecida' => ['Desaparecida', 'text-bg-warning'],
    'carpeta_saltada'      => ['Ignorada', 'text-bg-dark'],
    'copia'                => ['Copia a USB', 'text-bg-primary'],
    'copia_sobrante'       => ['Sobra en la copia', 'text-bg-warning'],
    'copia_error'          => ['Error de copia', 'text-bg-danger'],
];
$nombreUnidad = static fn ($nivel, $numero) => $nivel !== null ? 'Nivel ' . (int) $nivel . ' #' . (int) $numero : '—';
?>

<?php foreach ($catalogoMasNuevo as $e): ?>
    <div class="alert alert-danger">
        <i class="bi bi-database-exclamation me-1"></i>
        <strong>La base de datos va por detrás de un disco</strong> (<?= esc(substr((string) $e['creado_en'], 0, 16)) ?>, unidad <?= (int) $e['unidad_id'] ?>).
        <div class="small mt-1"><?= esc($e['detalle']) ?></div>
    </div>
<?php endforeach; ?>

<h2 class="h6 text-muted text-uppercase mb-2" style="letter-spacing: .05em;">Último escaneo de cada unidad</h2>

<?php if (empty($porUnidad)): ?>
    <p class="text-muted">Todavía no se ha escaneado ninguna unidad.</p>
<?php endif; ?>

<?php foreach ($porUnidad as $unidadId => $bloque): ?>
    <?php
    $unidad   = $unidades[$unidadId] ?? null;
    $porTipo  = [];
    foreach ($bloque['eventos'] as $e) {
        $porTipo[$e['tipo']][] = $e;
    }
    $saltadas  = $porTipo['carpeta_saltada'] ?? [];
    $problemas = 0;
    foreach (\App\Models\SiloEventoModel::TIPOS_PROBLEMA as $t) {
        $problemas += count($porTipo[$t] ?? []);
    }
    ?>
    <div class="card mb-3 <?= $problemas ? 'border-danger' : '' ?>">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-hdd"></i>
            <a href="<?= site_url('silo/unidades/' . $unidadId) ?>" class="text-decoration-none fw-semibold">
                <?= esc($unidad ? $nombreUnidad($unidad['nivel'], $unidad['numero']) : 'Unidad ' . $unidadId) ?>
            </a>
            <?php if (!empty($unidad['etiqueta'])): ?>
                <span class="text-muted small">(<?= silo_nombre_con_badges($unidad['etiqueta']) ?>)</span>
            <?php endif; ?>
            <span class="ms-auto small text-muted">
                <?= esc(substr((string) $bloque['escaneo']['creado_en'], 0, 16)) ?> · <?= esc($bloque['escaneo']['detalle']) ?>
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($tipoInfo as $tipoClave => [$titulo, $icono, $color, $ayuda]): ?>
                <?php if (empty($porTipo[$tipoClave])) { continue; } ?>
                <div class="mb-3">
                    <div class="text-<?= $color ?>-emphasis fw-semibold">
                        <i class="bi <?= $icono ?> me-1"></i><?= esc($titulo) ?>
                        <span class="badge text-bg-<?= $color ?>"><?= count($porTipo[$tipoClave]) ?></span>
                    </div>
                    <div class="text-muted small mb-1"><?= esc($ayuda) ?></div>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($porTipo[$tipoClave] as $e): ?>
                            <li class="py-1 border-bottom">
                                <code class="silo-mono"><?= esc($e['referencia']) ?></code>
                                <?php if (!empty($e['detalle'])): ?>
                                    <div class="text-muted"><?= esc($e['detalle']) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <?php if (!$problemas): ?>
                <p class="text-success-emphasis mb-2"><i class="bi bi-check2-circle me-1"></i>Sin problemas en este escaneo.</p>
            <?php endif; ?>

            <?php if ($saltadas): ?>
                <?php
                $porMotivo = [];
                foreach ($saltadas as $e) {
                    $porMotivo[(string) $e['motivo']][] = $e['referencia'];
                }
                ?>
                <details>
                    <summary class="text-muted small">
                        <i class="bi bi-eye-slash me-1"></i><?= count($saltadas) ?> entrada<?= count($saltadas) === 1 ? '' : 's' ?> de la raíz ignorada<?= count($saltadas) === 1 ? '' : 's' ?> (no se catalogan)
                    </summary>
                    <?php foreach ($porMotivo as $motivo => $nombres): ?>
                        <div class="small mt-2">
                            <span class="fw-semibold"><?= esc($motivoInfo[$motivo] ?? $motivo) ?></span>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <?php foreach ($nombres as $n): ?>
                                    <code class="silo-mono border rounded px-1"><?= esc($n) ?></code>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </details>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<h2 class="h6 text-muted text-uppercase mt-4 mb-2" style="letter-spacing: .05em;">Historial</h2>

<div class="btn-group btn-group-sm flex-wrap mb-2" role="group" aria-label="Filtrar historial">
    <a href="<?= site_url('silo/avisos') ?>" class="btn btn-outline-secondary <?= $tipo === null ? 'active' : '' ?>">Todo</a>
    <?php foreach ($historialInfo as $clave => [$texto, $_]): ?>
        <a href="<?= site_url('silo/avisos?tipo=' . $clave) ?>" class="btn btn-outline-secondary <?= $tipo === $clave ? 'active' : '' ?>"><?= esc($texto) ?></a>
    <?php endforeach; ?>
</div>
<?php if ($tipo === null): ?>
    <div class="text-muted small mb-2">Sin las carpetas ignoradas, que se repiten igual en cada escaneo — están en su filtro.</div>
<?php endif; ?>
<div class="text-muted small mb-2">
    Lo que se repite en varios escaneos sale en una sola fila (×N). Un problema que ya no apareció en el
    último escaneo de su unidad está <span class="badge text-bg-success">Resuelto</span>.
</div>

<?php if (empty($historial)): ?>
    <p class="text-muted">Nada que mostrar.</p>
<?php else: ?>
    <table class="table table-sm silo-tabla-control align-middle small">
        <thead>
            <tr><th>Cuándo</th><th>Qué</th><th>Unidad</th><th>Detalle</th></tr>
        </thead>
        <tbody>
            <?php foreach ($historial as $e): ?>
                <?php [$texto, $clase] = $historialInfo[$e['tipo']] ?? [$e['tipo'], 'text-bg-secondary']; ?>
                <?php $veces = (int) $e['veces']; ?>
                <tr class="<?= $e['resuelto'] ? 'opacity-50' : '' ?>">
                    <td class="text-nowrap text-muted">
                        <?= esc(substr((string) $e['ultima'], 0, 16)) ?>
                        <?php if ($veces > 1): ?>
                            <div style="font-size: .7rem;">×<?= $veces ?> desde <?= esc(substr((string) $e['primera'], 0, 10)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <span class="badge <?= $clase ?>"><?= esc($texto) ?></span>
                        <?php if ($e['resuelto']): ?>
                            <span class="badge text-bg-success">Resuelto</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap"><?= esc($nombreUnidad($e['nivel'], $e['numero'])) ?></td>
                    <td>
                        <?php if (!empty($e['referencia'])): ?>
                            <code class="silo-mono"><?= esc($e['referencia']) ?></code>
                        <?php endif; ?>
                        <?php if (!empty($e['motivo'])): ?>
                            <span class="text-muted">· <?= esc($motivoInfo[$e['motivo']] ?? $e['motivo']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($e['detalle'])): ?>
                            <div class="text-muted"><?= esc($e['detalle']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?= $this->endSection() ?>
