<?php
/**
 * Resultados de Enlaces (estilo fv): avisos «recientes» / «afina», vacío y
 * la lista. Lo pinta index_nueva en la carga y lo devuelve
 * `Enlaces::buscarResultados()` en cada búsqueda en vivo.
 * Enlaces con `data-enl-nav` = el JS navega sin recargar.
 */
if (!function_exists('enl_color')) {
    function enl_color(string $str): int
    {
        return crc32($str) % 360;
    }
}
$hayFiltros = $q !== '' || $panelActiveCount > 0;
?>
<?php if (empty($enlaces)): ?>
    <div class="fv-panel rc-vacio">
        <?php if ($hayFiltros): ?>
            <i class="bi bi-funnel" style="font-size:24px"></i>
            <div>Ningún enlace coincide con la búsqueda o los filtros.</div>
            <div style="margin-top:12px"><a href="<?= site_url('enlaces') ?>" class="fv-btn" data-enl-nav>Quitar todo y ver los últimos</a></div>
        <?php elseif (!empty($totalEnlaces)): ?>
            <i class="bi bi-search" style="font-size:24px"></i>
            <div>Tienes <?= (int) $totalEnlaces ?> enlaces. Busca por título, URL, nota, categoría o etiqueta.</div>
        <?php else: ?>
            <i class="bi bi-link-45deg" style="font-size:28px"></i>
            <div>Todavía no hay enlaces guardados.</div>
            <div style="margin-top:12px"><a href="<?= site_url('enlaces/crear') ?>" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i>Agregar el primero</a></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($enlaces) && !empty($modoReciente)): ?>
    <div class="en-aviso"><i class="bi bi-clock-history"></i>Los <?= count($enlaces) ?> últimos que guardaste. Escribe arriba para buscar entre todos.</div>
<?php endif; ?>

<?php if (!empty($hayMas) && empty($modoReciente)): ?>
    <div class="en-afina">
        <span><i class="bi bi-funnel"></i>Más de <?= (int) $topeResultados ?> resultados<?= empty($sugerenciasRefinar) ? '. Afina un poco la búsqueda.' : '. Acota con una etiqueta:' ?></span>
        <?php foreach ($sugerenciasRefinar as $s): ?>
            <a href="<?= esc($s['url'], 'attr') ?>" class="en-chip" data-enl-nav><?= esc($s['texto']) ?> <small><?= (int) $s['total'] ?></small></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($enlaces)): ?>
    <div class="en-lista">
        <?php foreach ($enlaces as $e): ?>
            <?php
            $id      = (int) $e['id'];
            $visto   = (bool) $e['visto'];
            $rel     = (int) $e['relevancia'];
            $dominio = enl_dominio($e['url']);
            $titulo  = trim((string) $e['titulo']) !== '' ? $e['titulo'] : ($dominio ?: $e['url']);
            $nota    = trim(strip_tags((string) ($e['extra'] ?? '')));
            $archivado = !empty($e['archivado_at']);
            ?>
            <div class="en-item<?= $visto ? ' visto' : '' ?><?= $archivado ? ' archivado' : '' ?>" data-id="<?= $id ?>">
                <button type="button" class="en-check btn-toggle-visto" data-id="<?= $id ?>" aria-pressed="<?= $visto ? 'true' : 'false' ?>" title="<?= $visto ? 'Visto (tocar para desmarcar)' : 'Marcar como visto' ?>"><i class="bi <?= $visto ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button>

                <div class="en-cuerpo">
                    <a href="<?= esc($e['url'], 'attr') ?>" target="_blank" rel="noopener" class="en-titulo"><?= esc($titulo) ?></a>
                    <div class="rc-meta en-meta">
                        <?php if ($dominio): ?>
                            <span class="en-dominio"><img src="https://www.google.com/s2/favicons?domain=<?= urlencode($dominio) ?>&sz=32" alt="" width="14" height="14" loading="lazy" onerror="this.remove()"><?= esc($dominio) ?></span>
                        <?php endif; ?>
                        <span title="<?= esc(date('d/m/Y', strtotime($e['fecha'])), 'attr') ?>"><?= esc(enl_hace($e['fecha'])) ?></span>
                        <?php if ($rel > 0): ?><span class="en-rel"><?= str_repeat('★', $rel) ?></span><?php endif; ?>
                        <?php if ($archivado): ?><span class="e-neu"><i class="bi bi-archive"></i>Archivado</span><?php endif; ?>
                    </div>
                    <?php if (!empty($coincidencias[$id])): ?>
                        <div class="en-match"><i class="bi bi-search"></i>Coincide en <?= esc(implode(', ', $coincidencias[$id])) ?></div>
                    <?php endif; ?>
                    <?php if ($nota !== ''): ?>
                        <div class="en-nota"><?= esc(mb_strimwidth($nota, 0, 180, '…')) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($catsPorEnlace[$id]) || !empty($tagsPorEnlace[$id])): ?>
                        <div class="en-badges">
                            <?php foreach (($catsPorEnlace[$id] ?? []) as $c): if (!$c) continue; ?>
                                <span class="en-cat" style="--h:<?= enl_color($c['nombre']) ?>"><?= esc($c['nombre']) ?></span>
                            <?php endforeach; ?>
                            <?php foreach (($tagsPorEnlace[$id] ?? []) as $t): if (!$t) continue; ?>
                                <span class="en-tag"><?= esc($t['nombre']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rc-btns en-btns">
                    <?php if ($archivado): ?>
                        <button type="button" class="fv-ibtn btn-desarchivar" data-id="<?= $id ?>" title="Sacar del archivo"><i class="bi bi-box-arrow-up"></i></button>
                    <?php endif; ?>
                    <a class="fv-ibtn en-pagina" href="<?= site_url('enlaces/pagina/' . $id) ?>" title="Página de notas"><i class="bi bi-journal-text"></i></a>
                    <a class="fv-ibtn en-editar" href="<?= site_url('enlaces/editar/' . $id) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a class="fv-ibtn en-borrar" href="<?= site_url('enlaces/borrar/' . $id) ?>" onclick="return confirm('¿Eliminar este enlace?');" title="Eliminar"><i class="bi bi-trash"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
