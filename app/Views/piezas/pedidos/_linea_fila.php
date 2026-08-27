<?php
/**
 * Una fila de la tabla de líneas del pedido, siempre editable (sin estado
 * "viendo" / "editando" separados). Aparte de _linea_form para poder
 * reusarla tal cual en las respuestas AJAX de añadir/editar línea
 * (PedidosController::agregarLinea/editarLinea) sin duplicar el HTML.
 *
 * Espera $linea ya enriquecida (PedidosController::enriquecerLinea): con
 * nombreFamilia, nombreVariante y foto además de las columnas de la tabla.
 */
$completa = (int) $linea['cantidad_completada'] >= (int) $linea['cantidad'];
$formId   = 'form-linea-' . (int) $linea['id'];
?>
<tr data-linea-id="<?= (int) $linea['id'] ?>" class="<?= $completa ? 'fila-completa' : '' ?>">
    <td style="width: 34px;">
        <?php if ($linea['foto']): ?>
            <img src="<?= esc($linea['foto'], 'attr') ?>" alt="" loading="lazy" class="rounded border"
                style="width: 34px; height: 34px; object-fit: contain;">
        <?php else: ?>
            <span class="d-inline-flex align-items-center justify-content-center rounded border text-body-tertiary"
                style="width: 34px; height: 34px;"><i class="bi bi-box" style="font-size: .8rem;"></i></span>
        <?php endif; ?>
    </td>
    <td style="min-width: 15em;">
        <div class="position-relative mb-1">
            <input type="text" class="form-control form-control-sm" data-buscar-variante autocomplete="off"
                placeholder="Buscar pieza del catálogo…"
                value="<?= $linea['nombreVariante'] ? esc($linea['nombreFamilia'] . ' · ' . $linea['nombreVariante'], 'attr') : '' ?>"
                form="<?= $formId ?>">
            <input type="hidden" name="variante_id" data-variante-id value="<?= (int) ($linea['variante_id'] ?? 0) ?>" form="<?= $formId ?>">
            <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;" data-resultados-variante></div>
        </div>
        <input type="text" name="descripcion_libre" class="form-control form-control-sm"
            placeholder="…o descripción (pieza futura)" value="<?= esc($linea['descripcion_libre'] ?? '', 'attr') ?>"
            form="<?= $formId ?>">
        <?php if ($linea['sku']): ?>
            <span class="badge border text-body-secondary font-monospace fw-normal mt-1"><?= esc($linea['sku']) ?></span>
        <?php endif; ?>
        <?php if (!$linea['nombreVariante'] && !empty($linea['descripcion_libre'])): ?>
            <span class="badge text-bg-info mt-1" title="Aún no existe en el catálogo">futura</span>
        <?php elseif (!$linea['nombreVariante'] && $linea['variante_id']): ?>
            <span class="text-muted small fst-italic d-block mt-1">pieza borrada</span>
        <?php endif; ?>
    </td>
    <td>
        <input type="number" name="cantidad" class="form-control form-control-sm" min="1" required
            style="width: 4.5em;" value="<?= (int) $linea['cantidad'] ?>" form="<?= $formId ?>">
    </td>
    <td>
        <div class="d-flex align-items-center gap-1">
            <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>" data-form-completada>
                <?= csrf_field() ?>
                <input type="hidden" name="delta" value="-1">
                <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Una menos"
                    <?= (int) $linea['cantidad_completada'] <= 0 ? 'disabled' : '' ?>>−</button>
            </form>
            <span class="small" style="min-width: 3em; text-align: center;">
                <?= (int) $linea['cantidad_completada'] ?>/<?= (int) $linea['cantidad'] ?>
                <?= $completa ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?>
            </span>
            <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/completada') ?>" data-form-completada>
                <?= csrf_field() ?>
                <input type="hidden" name="delta" value="1">
                <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="Una más"
                    <?= $completa ? 'disabled' : '' ?>>+</button>
            </form>
        </div>
    </td>
    <td style="min-width: 12em;">
        <textarea name="notas" class="form-control form-control-sm" maxlength="150" rows="2"
            placeholder="Notas" form="<?= $formId ?>"><?= esc($linea['notas'] ?? '') ?></textarea>
    </td>
    <td class="text-nowrap">
        <?php if ($linea['variante_id']): ?>
            <a href="<?= site_url('piezas/variante/' . $linea['variante_id']) ?>" class="btn btn-sm btn-outline-primary" title="Ver pieza">
                <i class="bi bi-eye"></i>
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-sm btn-primary" form="<?= $formId ?>" title="Guardar cambios de esta línea">
            <i class="bi bi-check-lg"></i>
        </button>
        <form method="post" action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/borrar') ?>" class="d-inline"
            data-form-borrar-linea>
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Borrar línea">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    </td>
</tr>
