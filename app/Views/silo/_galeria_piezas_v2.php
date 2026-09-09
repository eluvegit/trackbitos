<?php
/**
 * Parcial — PRUEBA A/B, variante B2: galería de carpetas con la tarjeta
 * alineada a la IZQUIERDA y el TEXTO como protagonista.
 * Jerarquía (la temática manda):
 *   · cabecera pequeña: icono de carpeta + #ID · fecha
 *   · titular grande: temática (hasta 3 líneas) — o la categoría si no hay
 *     temática
 *   · metadatos atenuados: año · categoría  /  📍 lugares · 👤 personas
 *   · abajo (fijo): eventos, badges de contenido, coincidencias, tamaño
 * Espera $piezas en el scope de quien lo incluya.
 */
?>
<?php $qsDesde = isset($unidad['id']) ? '?desde=' . (int) $unidad['id'] : ''; ?>
<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="d-flex flex-wrap gap-3">
        <?php foreach ($piezas as $p): ?>
            <?php
            $partes        = silo_carpeta_partes($p);
            $coincidencias = $p['ficheros_coincidentes'] ?? [];
            $tituloEsTema  = (bool) $partes['temas'];
            $catEnMeta     = $tituloEsTema && $partes['categoria'] !== '';
            ?>
            <a href="<?= site_url('silo/' . $p['id']) . $qsDesde ?>"
               class="text-decoration-none text-body border rounded p-2 d-flex flex-column text-start silo-carpeta silo-carpeta-v2"
               style="width: 190px; min-height: 200px;" title="<?= esc($p['nombre_carpeta']) ?>">

                <span class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-folder-fill text-warning" style="font-size: 1.5rem; line-height: 1;"></i>
                    <span class="text-muted lh-sm" style="font-size: .64rem;">
                        #<?= esc($p['id_negocio']) ?><br><?= esc(silo_fecha_humana($p['fecha'] ?? null)) ?>
                    </span>
                </span>

                <span class="silo-carpeta-v2-titulo d-block mb-1">
                    <?php if ($tituloEsTema): ?>
                        <?= esc(implode(' · ', $partes['temas'])) ?>
                    <?php elseif ($partes['categoria'] !== ''): ?>
                        <?= esc($partes['categoria']) ?>
                    <?php else: ?>
                        <span class="text-muted fst-italic fw-normal">sin temática</span>
                    <?php endif; ?>
                </span>

                <?php if ($partes['anio'] !== '' || $catEnMeta): ?>
                    <span class="d-block text-muted silo-carpeta-v2-sub mb-1">
                        <?php if ($partes['anio'] !== ''): ?><?= esc($partes['anio']) ?><?php endif; ?>
                        <?php if ($partes['anio'] !== '' && $catEnMeta): ?><span class="mx-1">·</span><?php endif; ?>
                        <?php if ($catEnMeta): ?><i class="bi bi-collection-fill me-1"></i><?= esc($partes['categoria']) ?><?php endif; ?>
                    </span>
                <?php endif; ?>

                <?php if ($partes['lugares'] || $partes['personas']): ?>
                    <span class="d-block text-muted silo-carpeta-v2-sub mb-1">
                        <?php if ($partes['lugares']): ?><i class="bi bi-geo-alt me-1"></i><?= esc(implode(', ', $partes['lugares'])) ?><?php endif; ?>
                        <?php if ($partes['lugares'] && $partes['personas']): ?><span class="mx-1">·</span><?php endif; ?>
                        <?php if ($partes['personas']): ?><i class="bi bi-people me-1"></i><?= esc(implode(', ', $partes['personas'])) ?><?php endif; ?>
                    </span>
                <?php endif; ?>

                <span class="mt-auto pt-1">
                    <?php foreach ($partes['eventos'] as $ev): ?>
                        <span class="badge text-bg-info fw-normal d-inline-block mb-1" style="font-size: .6rem;">
                            <i class="bi bi-calendar-event me-1"></i><?= esc($ev) ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if ($partes['contenido']): ?>
                        <span class="d-block mb-1"><?= silo_badges_contenido_claves($partes['contenido']) ?></span>
                    <?php endif; ?>
                    <?php if ($coincidencias): ?>
                        <span class="badge bg-info-subtle text-info-emphasis d-inline-block mb-1" style="font-size: .6rem;"
                              title="<?= esc(implode("\n", array_column($coincidencias, 'nombre')), 'attr') ?>">
                            <i class="bi bi-search"></i> <?= count($coincidencias) ?> fichero<?= count($coincidencias) === 1 ? '' : 's' ?>
                        </span>
                    <?php endif; ?>
                    <span class="text-muted d-block" style="font-size: .7rem;"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
