<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-hdd-stack text-primary"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Unidades</strong>
    <a href="<?= site_url('silo/mi-pc') ?>" class="text-decoration-none ms-1 text-muted" title="Mi PC">
        <i class="bi bi-pc-display"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('silo/unidades/crear') ?>" class="row g-2 align-items-end mb-4" style="max-width: 560px;">
    <?= csrf_field() ?>
    <div class="col-sm-4">
        <label class="form-label small">Nivel</label>
        <select name="nivel" class="form-select form-select-sm" required>
            <option value="1">1 - Maestro</option>
            <option value="2">2 - Año</option>
            <option value="3">3 - Temática</option>
        </select>
    </div>
    <div class="col-sm-6">
        <label class="form-label small">Etiqueta (opcional)</label>
        <input type="text" name="etiqueta" class="form-control form-control-sm" placeholder="ej. Maestro #1, USB rojo...">
    </div>
    <div class="col-sm-2">
        <button type="submit" class="btn btn-sm btn-primary w-100">Crear</button>
    </div>
    <div class="col-sm-6">
        <label class="form-label small">Año o categoría (opcional, solo nivel 2/3)</label>
        <input type="text" name="agrupador" class="form-control form-control-sm" placeholder="ej. 2026, o una categoría">
        <div class="form-text">Regístrala así si ya tienes el disco antes de que la propagación le toque el turno — si no, se crea sola cuando haga falta.</div>
    </div>
    <div class="col-sm-6">
        <label class="form-label small">Capacidad en TB (opcional)</label>
        <input type="number" name="capacidad_tb" min="0.5" step="0.5" class="form-control form-control-sm" placeholder="ej. 1, 2, 1.5, 3, 0.5 — vacío si no se conoce">
    </div>
</form>

<?php $nivelLabel = [1 => 'Nivel 1 — Maestro', 2 => 'Nivel 2 — Año', 3 => 'Nivel 3 — Temática']; ?>

<?php foreach ([1, 2, 3] as $nivel): ?>
    <h6 class="mt-4"><?= $nivelLabel[$nivel] ?></h6>
    <?php if (empty($porNivel[$nivel])): ?>
        <p class="text-muted small">Ninguna todavía.</p>
    <?php else: ?>
        <table class="table table-sm align-middle">
            <thead>
                <tr><th>Unidad</th><th>Etiqueta</th><?php if ($nivel !== 1): ?><th>Cubo</th><?php endif; ?><th>Contenido</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($porNivel[$nivel] as $u): ?>
                    <?php $numPiezas = $piezasPorUnidad[$u['id']] ?? 0; ?>
                    <tr>
                        <td class="text-nowrap">
                            <a href="<?= site_url('silo/unidades/' . $u['id']) ?>" class="text-decoration-none">
                                <i class="bi bi-hdd"></i> #<?= (int) $u['numero'] ?>
                            </a>
                        </td>
                        <td>
                            <form method="post" action="<?= site_url('silo/unidades/' . $u['id'] . '/etiqueta') ?>" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="text" name="etiqueta" class="form-control form-control-sm"
                                       value="<?= esc($u['etiqueta'] ?? '') ?>" placeholder="sin etiqueta">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Guardar etiqueta">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                        </td>
                        <?php if ($nivel !== 1): ?>
                            <td>
                                <?php if ($u['agrupador']): ?>
                                    <span class="badge text-bg-light border"><?= esc($u['agrupador']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="text-muted small">
                            <?= $numPiezas ?> pieza<?= $numPiezas === 1 ? '' : 's' ?>
                            <?php if ($u['capacidad_bytes']): ?>
                                · <?= esc(silo_formatear_tamano($usoPorUnidad[$u['id']] ?? 0)) ?>
                                / <?= esc(silo_formatear_tamano((int) $u['capacidad_bytes'])) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $u['sellada']): ?>
                                <span class="badge text-bg-secondary">sellada</span>
                            <?php else: ?>
                                <span class="badge text-bg-success">activa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="<?= site_url('silo/unidades/' . $u['id'] . '/fichero-control') ?>"
                               class="btn btn-sm btn-outline-secondary" title="Descargar .silo_unit.json">
                                <i class="bi bi-file-earmark-code"></i>
                            </a>
                            <?php if (!(int) $u['sellada']): ?>
                                <form method="post" action="<?= site_url('silo/unidades/' . $u['id'] . '/sellar') ?>" class="d-inline"
                                      onsubmit="return confirm('¿Sellar esta unidad? No debería volver a escribirse (plan Silo §2).')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Sellar">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= site_url('silo/unidades/' . $u['id'] . '/borrar') ?>" class="d-inline"
                                  onsubmit="return confirm(<?= $numPiezas > 0
                                      ? json_encode("Esta unidad tiene {$numPiezas} pieza(s) registrada(s). ¿Seguro que quieres borrarla?")
                                      : json_encode('¿Borrar esta unidad vacía?') ?>)">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar unidad">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endforeach; ?>

<?= $this->endSection() ?>
