<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-folder2 text-warning"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($pieza['nombre_carpeta']) ?></strong>

    <a href="<?= site_url('silo/' . $pieza['id'] . '/editar') ?>" class="text-decoration-none ms-1" title="Reclasificar">
        <i class="bi bi-pencil"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if (!empty($proxies)): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($proxies as $p): ?>
            <div class="position-relative" style="width: 160px;">
                <img src="<?= esc($p['url']) ?>" alt="" class="rounded w-100" style="height: 100px; object-fit: cover;">
                <?php if ($p['tipo'] === 'video'): ?>
                    <span class="position-absolute top-50 start-50 translate-middle text-white bg-dark bg-opacity-50 rounded-circle p-1">
                        <i class="bi bi-play-fill fs-4"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<details class="mb-3">
    <summary class="text-muted small mb-2">Más información</summary>

    <dl class="row mt-2">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?= esc($pieza['id_negocio']) ?></dd>

        <dt class="col-sm-3">Fecha</dt>
        <dd class="col-sm-9"><?= esc($pieza['fecha'] ?? 'sin fecha') ?></dd>

        <dt class="col-sm-3">Categoría</dt>
        <dd class="col-sm-9"><?= $categoria ? esc($categoria['nombre']) : 'sin_clasificar' ?></dd>

        <?php if (!empty($pieza['tipo'])): ?>
            <dt class="col-sm-3">Tipo</dt>
            <dd class="col-sm-9"><?= esc($pieza['tipo']) ?></dd>
        <?php endif; ?>

        <?php if (!empty($pieza['fuente'])): ?>
            <dt class="col-sm-3">Fuente</dt>
            <dd class="col-sm-9"><?= esc($pieza['fuente']) ?></dd>
        <?php endif; ?>

        <?php if (!empty($atributos)): ?>
            <dt class="col-sm-3">Evento/Lugar/Persona/Tema</dt>
            <dd class="col-sm-9">
                <?php foreach ($atributos as $a): ?>
                    <span class="badge text-bg-light border me-1"><?= esc(ucfirst($a['tipo'])) ?>: <?= esc($a['nombre']) ?></span>
                <?php endforeach; ?>
            </dd>
        <?php endif; ?>

        <?php if (!empty($pieza['notas'])): ?>
            <dt class="col-sm-3">Notas</dt>
            <dd class="col-sm-9"><?= nl2br(esc($pieza['notas'])) ?></dd>
        <?php endif; ?>
    </dl>

    <h6 class="mt-3 mb-2">Ubicaciones físicas</h6>
    <?php if (empty($ubicaciones)): ?>
        <p class="text-muted small">Sin ubicaciones registradas — esta pieza no llegó por ingesta (alta manual) o aún no se ha simulado/escaneado.</p>
    <?php else: ?>
        <table class="table table-sm">
            <thead>
                <tr><th>Unidad</th><th>Copia</th><th>Ruta</th><th></th></tr>
            </thead>
            <tbody>
                <?php $copiaLabel = [1 => 'Maestro', 2 => 'Año', 3 => 'Temática']; ?>
                <?php foreach ($ubicaciones as $u): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('silo/unidades/' . $u['unidad_id']) ?>" class="text-decoration-none">
                                Nivel <?= (int) $u['nivel'] ?> #<?= (int) $u['numero'] ?>
                            </a>
                            <?php if (!empty($u['unidad_etiqueta'])): ?>
                                <span class="text-muted small">(<?= esc($u['unidad_etiqueta']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($copiaLabel[(int) $u['copia']] ?? $u['copia']) ?></td>
                        <td><code class="small"><?= esc($u['ruta_relativa']) ?></code></td>
                        <td>
                            <form method="post" action="<?= site_url('silo/ubicacion/' . $u['id'] . '/borrar') ?>"
                                  onsubmit="return confirm('¿Quitar esta ubicación? (limpieza de una ingesta equivocada, no mueve nada en disco)')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <form method="post" action="<?= site_url('silo/' . $pieza['id'] . '/borrar') ?>" class="mt-3"
          onsubmit="return confirm('¿Eliminar esta pieza del catálogo?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-trash"></i> Eliminar pieza del catálogo
        </button>
    </form>
</details>

<?php if (empty($ficheros)): ?>
    <p class="text-muted">Sin ficheros registrados todavía.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr><th>Nombre</th><th>Tipo</th><th>Tamaño</th><th>Hash</th><th>Ingestado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($ficheros as $f): ?>
                    <tr>
                        <td class="d-flex align-items-center gap-2">
                            <i class="bi <?= silo_icono_tipo($f['tipo']) ?> text-muted"></i>
                            <?= esc($f['nombre']) ?>
                        </td>
                        <td><span class="badge text-bg-light border"><?= esc($f['tipo']) ?></span></td>
                        <td class="text-nowrap"><?= esc(silo_formatear_tamano($f['tamano_bytes'] ?? null)) ?></td>
                        <td>
                            <?php if (!empty($f['hash'])): ?>
                                <code class="small" title="<?= esc($f['hash']) ?>"><?= esc(substr($f['hash'], 0, 12)) ?>…</code>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small text-nowrap"><?= esc($f['creado_en'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
