<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>
<?= $this->include('silo/_estilos_nivel') ?>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('dashboard') ?>">Dashboard</a> / Silo
    <span class="silo-control-iconos">
        <a href="<?= site_url('silo/vocabulario') ?>" title="Vocabulario"><i class="bi bi-tags"></i></a>
        <a href="<?= site_url('silo/unidades') ?>" title="Unidades"><i class="bi bi-hdd-stack"></i></a>
        <a href="<?= site_url('silo/mi-pc') ?>" title="Mi PC"><i class="bi bi-pc-display"></i></a>
        <a href="<?= site_url('silo/ranking') ?>" title="Lo que más ocupa"><i class="bi bi-bar-chart-line"></i></a>
        <a href="<?= site_url('silo/datos-faltan') ?>" title="Datos que faltan"><i class="bi bi-clipboard-x"></i></a>
        <a href="<?= site_url('silo/crear') ?>" class="text-success" title="Nueva pieza"><i class="bi bi-plus-circle"></i></a>
    </span>
</div>
<h1 class="silo-control-titulo">Catálogo de <strong>Piezas</strong></h1>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if (!empty($atributoFiltro)): ?>
    <div class="alert alert-info py-2 d-flex align-items-center gap-2">
        <i class="bi bi-funnel"></i>
        Filtrando por <strong><?= esc($atributoFiltro['nombre']) ?></strong>
        <a href="<?= site_url('silo') ?>" class="ms-auto btn btn-sm btn-outline-secondary">
            Quitar filtro <i class="bi bi-x-lg"></i>
        </a>
    </div>
<?php endif; ?>

<?php $vista = $vista ?? 'lista2'; ?>

<form method="get" action="<?= site_url('silo') ?>" class="row g-2 mb-3">
    <?php if (!empty($filtros['atributo_id'])): ?>
        <input type="hidden" name="atributo_id" value="<?= (int) $filtros['atributo_id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="vista" value="<?= esc($vista, 'attr') ?>">
    <input type="hidden" name="orden" value="<?= esc($filtros['orden'] ?? 'nombre', 'attr') ?>">
    <div class="col-sm-6 col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Buscar por ID, nombre de carpeta o de fichero..."
               value="<?= esc($filtros['q'] ?? '') ?>">
    </div>
    <div class="col-sm-6 col-md-3">
        <select name="categoria_id" class="form-select">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (string) ($filtros['categoria_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="anio" class="form-select">
            <option value="">Todos los años</option>
            <?php foreach ($anios as $a): ?>
                <option value="<?= (int) $a ?>" <?= (string) ($filtros['anio'] ?? '') === (string) $a ? 'selected' : '' ?>>
                    <?= (int) $a ?>
                </option>
            <?php endforeach; ?>
            <option value="sin_fecha" <?= ($filtros['anio'] ?? '') === 'sin_fecha' ? 'selected' : '' ?>>Sin fecha</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary">Buscar</button>
    </div>
</form>

<?php
$qsBase = static fn (array $overrides = []) => site_url('silo') . '?' . http_build_query(array_merge(
    array_filter([
        'q'            => $filtros['q'] ?? null,
        'categoria_id' => $filtros['categoria_id'] ?? null,
        'atributo_id'  => $filtros['atributo_id'] ?? null,
        'anio'         => $filtros['anio'] ?? null,
    ]),
    ['vista' => $vista, 'orden' => $filtros['orden'] ?? 'nombre'],
    $overrides
));
$vistaQs = static fn ($v) => $qsBase(['vista' => $v]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div class="btn-group btn-group-sm" role="group" aria-label="Orden">
        <a href="<?= esc($qsBase(['orden' => 'nombre']), 'attr') ?>"
           class="btn btn-outline-secondary <?= ($filtros['orden'] ?? 'nombre') === 'nombre' ? 'active' : '' ?>" title="Orden alfabético (por ID de alta)">
            <i class="bi bi-sort-alpha-down"></i> Nombre
        </a>
        <a href="<?= esc($qsBase(['orden' => 'anio']), 'attr') ?>"
           class="btn btn-outline-secondary <?= ($filtros['orden'] ?? 'nombre') === 'anio' ? 'active' : '' ?>" title="Ordenar por año (cronológico)">
            <i class="bi bi-sort-numeric-down"></i> Año
        </a>
    </div>
    <div class="btn-group btn-group-sm" role="group" aria-label="Forma de ver las carpetas">
        <a href="<?= esc($vistaQs('lista2'), 'attr') ?>"
           class="btn btn-outline-secondary <?= $vista === 'lista2' ? 'active' : '' ?>" title="Listado">
            <i class="bi bi-card-text"></i>
        </a>
        <a href="<?= esc($vistaQs('galeria2'), 'attr') ?>"
           class="btn btn-outline-secondary <?= $vista === 'galeria2' ? 'active' : '' ?>" title="Galería de carpetas">
            <i class="bi bi-grid-1x2"></i>
        </a>
    </div>
</div>

<?= $this->include(match ($vista) {
    'galeria'  => 'silo/_galeria_piezas',
    'lista2'   => 'silo/_listado_piezas_v2',
    'galeria2' => 'silo/_galeria_piezas_v2',
    default    => 'silo/_listado_piezas',
}) ?>

<?= $this->endSection() ?>
