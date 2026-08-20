<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    $idPlaca = (int) $placa['id'];

    // La resina que se fue en esta placa. Solo tiene sentido si están los dos
    // pesos y el de después es menor: al revés (o iguales) es que uno de los
    // dos está mal apuntado, y más vale no enseñar un número inventado.
    $gastado = null;
    if ($placa['peso_antes'] !== null && $placa['peso_despues'] !== null) {
        $diferencia = (float) $placa['peso_antes'] - (float) $placa['peso_despues'];
        $gastado = $diferencia > 0 ? $diferencia : null;
    }

    $peso = static fn($v) => $v === null ? null : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');

    // Minutos a "2 h 35 min", que es como se dice en voz alta.
    $duracion = static function ($minutos): ?string {
        if ($minutos === null) {
            return null;
        }
        $minutos = (int) $minutos;
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return $h ? ($m ? "{$h} h {$m} min" : "{$h} h") : "{$m} min";
    };

    // Cuánto se desvió lo real de lo prometido, con su signo: "+18 min" duele
    // más que un número pelado, y es lo que se viene a mirar aquí.
    $desvio = static function (?int $real, $referencia) use ($duracion): ?string {
        if ($real === null || $referencia === null) {
            return null;
        }
        $diferencia = $real - (int) $referencia;
        if ($diferencia === 0) {
            return 'clavado';
        }

        return ($diferencia > 0 ? '+' : '−') . $duracion(abs($diferencia));
    };

    $veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;
    $colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-journal-text text-primary"></i>
    <a href="<?= site_url('piezas') ?>" class="text-decoration-none text-muted fw-normal">Piezas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($placa['nombre']) ?></strong>

    <?php if ($placa['veredicto'] && isset($veredictos[$placa['veredicto']])): ?>
        <span class="badge text-bg-<?= $colorVeredicto[$placa['veredicto']] ?? 'secondary' ?>">
            <?= esc($veredictos[$placa['veredicto']]) ?>
        </span>
    <?php else: ?>
        <span class="badge text-bg-secondary" title="Todavía no la has juzgado">sin juzgar</span>
    <?php endif; ?>

    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/editar') ?>"
        class="btn btn-sm btn-outline-primary ms-auto">
        <i class="bi bi-pencil"></i> Editar bitácora
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php // Cabecera de datos duros: fechas, exposición y resina. En fila de
      // tarjetitas para poder compararlas de un vistazo entre placas. ?>
<div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">MONTADA</div>
            <div class="small"><?= esc(date('d/m/Y H:i', strtotime($placa['creado_en']))) ?></div>
        </div></div>
    </div>
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">IMPRESA</div>
            <div class="small">
                <?= $placa['impresa_en'] ? esc(date('d/m/Y H:i', strtotime($placa['impresa_en']))) : '<span class="text-muted">sin anotar</span>' ?>
            </div>
        </div></div>
    </div>
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">EXPOSICIÓN</div>
            <div class="small"><?= $placa['exposicion'] ? esc($placa['exposicion']) : '<span class="text-muted">sin anotar</span>' ?></div>
        </div></div>
    </div>
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">RESINA GASTADA</div>
            <div class="small">
                <?php if ($gastado !== null): ?>
                    <strong><?= esc($peso($gastado)) ?> g</strong>
                    <span class="text-muted" style="font-size: .75rem;">
                        (<?= esc($peso($placa['peso_antes'])) ?> → <?= esc($peso($placa['peso_despues'])) ?>)
                    </span>
                <?php elseif ($placa['peso_antes'] !== null || $placa['peso_despues'] !== null): ?>
                    <?= esc($peso($placa['peso_antes']) ?? '—') ?> → <?= esc($peso($placa['peso_despues']) ?? '—') ?> g
                <?php else: ?>
                    <span class="text-muted">sin pesar</span>
                <?php endif; ?>
                <?php if ($placa['resina_estimada'] !== null): ?>
                    <div class="text-muted" style="font-size: .75rem;">
                        el programa decía <?= esc($peso($placa['resina_estimada'])) ?> g
                        <?php if ($gastado !== null): ?>
                            <?php $dif = $gastado - (float) $placa['resina_estimada']; ?>
                            (<?= $dif >= 0 ? '+' : '−' ?><?= esc($peso(abs($dif))) ?> g)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div></div>
    </div>
</div>

<?php // Lo prometido contra lo que pasó. Es la sección que da sentido a guardar
      // tres tiempos distintos: saber a cuál de los dos relojes creer. ?>
<div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">ESTIMADO (PROGRAMA)</div>
            <div class="small"><?= $duracion($placa['minutos_estimados']) ? esc($duracion($placa['minutos_estimados'])) : '<span class="text-muted">—</span>' ?></div>
        </div></div>
    </div>
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">PREVISTO (MÁQUINA)</div>
            <div class="small"><?= $duracion($placa['minutos_previstos']) ? esc($duracion($placa['minutos_previstos'])) : '<span class="text-muted">—</span>' ?></div>
        </div></div>
    </div>
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">TIEMPO REAL</div>
            <div class="small">
                <strong><?= $duracion($placa['minutos_reales']) ? esc($duracion($placa['minutos_reales'])) : '—' ?></strong>
                <?php $dEstimado = $desvio($placa['minutos_reales'], $placa['minutos_estimados']); ?>
                <?php $dPrevisto = $desvio($placa['minutos_reales'], $placa['minutos_previstos']); ?>
                <?php if ($dEstimado || $dPrevisto): ?>
                    <div class="text-muted" style="font-size: .75rem;">
                        <?php if ($dEstimado): ?>programa: <?= esc($dEstimado) ?><?php endif; ?>
                        <?php if ($dEstimado && $dPrevisto): ?> · <?php endif; ?>
                        <?php if ($dPrevisto): ?>máquina: <?= esc($dPrevisto) ?><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div></div>
    </div>
    <div class="col">
        <div class="card h-100"><div class="card-body p-2">
            <div class="text-muted" style="font-size: .7rem;">RESINA USADA</div>
            <div class="small">
                <?= $placa['resina'] ? esc($placa['resina']) : '<span class="text-muted">sin anotar</span>' ?>
                <?php if ($placa['temperatura'] !== null): ?>
                    <div class="text-muted" style="font-size: .75rem;">
                        <i class="bi bi-thermometer-half"></i> <?= esc($peso($placa['temperatura'])) ?> °C
                    </div>
                <?php endif; ?>
            </div>
        </div></div>
    </div>
</div>

<h6 class="mt-4"><i class="bi bi-box"></i> Qué llevaba</h6>
<?php if (empty($piezas)): ?>
    <p class="text-muted small">Ninguna de esas versiones existe ya.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle" style="font-size: .8rem;">
            <thead>
                <tr class="text-muted">
                    <th style="width: 2.5rem;"></th>
                    <th>Pieza</th>
                    <th class="text-end" style="width: 5rem;">Copias</th>
                    <th>Soportes y pruebas de esta pieza</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; ?>
                <?php foreach ($piezas as $p): ?>
                    <?php $total += (int) $p['fila']['cantidad']; ?>
                    <tr>
                        <td>
                            <?php if ($p['miniatura']): ?>
                                <img src="<?= $p['miniatura'] ?>" loading="lazy" alt=""
                                    class="rounded border" style="width: 28px; height: 28px; object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['variante'] && $p['familia']): ?>
                                <a href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) ?>"
                                    class="text-decoration-none text-body">
                                    <?= esc($p['familia']['nombre']) ?> - <?= esc($p['variante']['nombre']) ?>
                                    <span class="text-muted">· v<?= sprintf('%03d', (int) $p['version']['numero']) ?></span>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">(esa pieza ya no existe)</span>
                            <?php endif; ?>
                            <?php if (!$p['disponible']): ?>
                                <span class="text-warning ms-1" title="El STL ya no está en el almacén">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><strong>×<?= (int) $p['fila']['cantidad'] ?></strong></td>
                        <td class="text-muted">
                            <?= $p['fila']['notas'] ? nl2br(esc($p['fila']['notas'])) : '' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="text-muted">
                    <td colspan="2" class="text-end">Total de copias en la placa</td>
                    <td class="text-end"><strong><?= $total ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>

<h6 class="mt-4"><i class="bi bi-question-circle"></i> Qué se estaba probando</h6>
<?php if (empty($pruebas)): ?>
    <p class="text-muted small">
        Nada apuntado. Las preguntas se escriben antes de imprimir ("¿aguanta la espada sin
        soporte en la punta?") y la respuesta se rellena al mirar la pieza ya curada.
    </p>
<?php else: ?>
    <dl class="row mb-0" style="font-size: .85rem;">
        <?php foreach ($pruebas as $prueba): ?>
            <dt class="col-sm-5 fw-semibold"><?= esc($prueba['pregunta']) ?></dt>
            <dd class="col-sm-7">
                <?= $prueba['respuesta']
                    ? nl2br(esc($prueba['respuesta']))
                    : '<span class="text-muted">pendiente de responder</span>' ?>
            </dd>
        <?php endforeach; ?>
    </dl>
<?php endif; ?>

<div class="row g-3 mt-1">
    <div class="col-md-6">
        <h6><i class="bi bi-chat-left-text"></i> Notas y mejoras</h6>
        <div class="card"><div class="card-body p-2 small">
            <?= $placa['notas']
                ? nl2br(esc($placa['notas']))
                : '<span class="text-muted">Sin notas.</span>' ?>
        </div></div>
    </div>
    <div class="col-md-6">
        <h6><i class="bi bi-flag"></i> Conclusiones</h6>
        <div class="card border-primary"><div class="card-body p-2 small">
            <?= $placa['conclusiones']
                ? nl2br(esc($placa['conclusiones']))
                : '<span class="text-muted">Sin conclusiones todavía — es lo que leerás antes de montar la siguiente placa.</span>' ?>
        </div></div>
    </div>
</div>

<?= $this->endSection() ?>
