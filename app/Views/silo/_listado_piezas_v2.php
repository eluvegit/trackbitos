<?php
/**
 * Parcial — PRUEBA A/B, variante A2: listado con el TEXTO como protagonista.
 * Jerarquía (la temática manda):
 *   · Línea 1 (titular, grande): temática — o la categoría si la carpeta no
 *     tiene temática — + eventos.
 *   · Línea 2 (metadatos, atenuada, una sola línea con recorte): año ·
 *     categoría · 📍 lugares · 👤 personas.
 * Los badges de contenido (Fotos/Vídeos/Montajes) NO van con el texto: se
 * llevan al bloque derecho, delante de lo que ocupa la carpeta y separados
 * de la capacidad con una línea vertical. Icono de carpeta, tamaño, #ID y
 * sub-fila de coincidencias iguales que _listado_piezas.php. Espera
 * $piezas en el scope.
 */
?>
<?php $qsDesde = isset($unidad['id']) ? '?desde=' . (int) $unidad['id'] : ''; ?>
<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($piezas as $p): ?>
            <?php
            $partes        = silo_carpeta_partes($p);
            $coincidencias = $p['ficheros_coincidentes'] ?? [];
            $muestra       = array_slice($coincidencias, 0, 8);
            $resto         = count($coincidencias) - count($muestra);

            $titulo    = $partes['temas'] ? implode(' · ', $partes['temas']) : $partes['categoria'];
            $tituloEsTema = (bool) $partes['temas'];

            // Metadatos de la línea 2: [icono|'', texto].
            $meta = [];
            if ($partes['anio'] !== '')                     { $meta[] = ['', $partes['anio']]; }
            if ($tituloEsTema && $partes['categoria'] !== '') { $meta[] = ['bi-collection-fill', $partes['categoria']]; }
            if ($partes['lugares'])                         { $meta[] = ['bi-geo-alt', implode(', ', $partes['lugares'])]; }
            if ($partes['personas'])                        { $meta[] = ['bi-people', implode(', ', $partes['personas'])]; }
            foreach ($partes['otros'] as $lista)            { $meta[] = ['bi-tag', implode(', ', $lista)]; }
            ?>
            <a href="<?= site_url('silo/' . $p['id']) . $qsDesde ?>"
               class="list-group-item list-group-item-action d-flex align-items-center gap-3<?= $coincidencias ? ' border-bottom-0' : '' ?>">
                <i class="bi bi-folder2 text-warning fs-5 flex-shrink-0"></i>
                <span class="flex-grow-1" style="min-width:0;" title="<?= esc($p['nombre_carpeta']) ?>">
                    <span class="d-flex align-items-center gap-2" style="min-width:0;">
                        <span class="silo-lista-v2-titulo text-truncate" style="min-width:0;">
                            <?php if ($titulo !== ''): ?>
                                <?= esc($titulo) ?>
                            <?php else: ?>
                                <span class="text-muted fst-italic fw-normal">sin temática</span>
                            <?php endif; ?>
                        </span>
                        <?php foreach ($partes['eventos'] as $ev): ?>
                            <span class="badge text-bg-info fw-normal flex-shrink-0"><i class="bi bi-calendar-event me-1"></i><?= esc($ev) ?></span>
                        <?php endforeach; ?>
                    </span>
                    <?php if ($meta): ?>
                        <span class="d-block text-muted small text-truncate" style="line-height:1.3;">
                            <?php foreach ($meta as $i => [$ic, $txt]): ?>
                                <?php if ($i): ?><span class="mx-1">·</span><?php endif; ?><?php if ($ic): ?><i class="bi <?= $ic ?> me-1"></i><?php endif; ?><?= esc($txt) ?>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </span>
                <span class="d-flex align-items-center gap-2 flex-shrink-0">
                    <?php if ($partes['contenido']): ?>
                        <span class="silo-lista-v2-contenido text-nowrap pe-2 border-end"><?= silo_badges_contenido_claves($partes['contenido']) ?></span>
                    <?php endif; ?>
                    <span class="text-muted small text-nowrap"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
                </span>
                <span class="badge silo-badge-id text-nowrap flex-shrink-0" title="ID de la carpeta (para búsquedas rápidas)">#<?= esc($p['id_negocio']) ?></span>
            </a>
            <?php if ($coincidencias): ?>
                <div class="list-group-item py-2 ps-5 small">
                    <div class="text-muted mb-1">
                        <i class="bi bi-search"></i>
                        Coincide en <?= count($coincidencias) ?> fichero<?= count($coincidencias) === 1 ? '' : 's' ?>:
                    </div>
                    <?php foreach ($muestra as $f): ?>
                        <div class="text-truncate">
                            <i class="bi <?= $f['tipo'] === 'video' ? 'bi-film' : ($f['tipo'] === 'foto' ? 'bi-image' : 'bi-file-earmark') ?> text-muted"></i>
                            <?= esc($f['nombre']) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($resto > 0): ?>
                        <div class="text-muted">y <?= $resto ?> más…</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
