<?php
/**
 * Fragmento de resultados de Enlaces: aviso de "recientes", aviso de "afina",
 * estado vacío y la lista. Lo pinta index.php en la carga inicial y lo
 * devuelve `Enlaces::buscarResultados()` en cada búsqueda en vivo — por eso
 * es un parcial y no HTML suelto dentro de index.
 *
 * Se incluye dentro de `#enlResultados` (contenedor en index.php).
 * Enlaces con `data-enl-nav` = el JS navega sin recargar; sin JS funcionan igual.
 */
if (!function_exists('enl_color_from_string')) {
    function enl_color_from_string(string $str): array
    {
        $hue = crc32($str) % 360;
        return [
            "hsl({$hue}, 65%, 70%)",
            "hsla({$hue}, 65%, 55%, .16)",
        ];
    }
}
?>

<?php if (empty($enlaces)): ?>
    <?php $hayFiltrosActivos = $q !== '' || $panelActiveCount > 0; ?>
    <div class="enl-empty text-center text-muted py-5">
        <?php if ($hayFiltrosActivos): ?>
            <i class="bi bi-filter-circle fs-1 d-block mb-2"></i>
            <p class="mb-2">Ningún enlace coincide con la búsqueda o los filtros aplicados.</p>
            <a href="<?= site_url('enlaces') ?>" class="btn btn-sm btn-outline-secondary" data-enl-nav>Quitar filtros y ver todos</a>
        <?php elseif (!empty($totalEnlaces)): ?>
            <i class="bi bi-search fs-1 d-block mb-2"></i>
            <p class="mb-2">Tienes <?= $totalEnlaces ?> enlace<?= $totalEnlaces === 1 ? '' : 's' ?> guardado<?= $totalEnlaces === 1 ? '' : 's' ?>. Busca por título, URL, nota, categoría o etiqueta, o usa los filtros.</p>
        <?php else: ?>
            <i class="bi bi-link-45deg fs-1 d-block mb-2"></i>
            <p class="mb-2">Todavía no hay enlaces guardados.</p>
            <a href="<?= site_url('enlaces/crear') ?>" class="btn btn-sm btn-primary">Agregar el primero</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($enlaces) && !empty($modoReciente)): ?>
    <div class="enl-hint">
        <i class="bi bi-clock-history"></i>
        Últimos <?= count($enlaces) ?> añadidos. Busca por título, URL, nota, categoría o etiqueta para ver más.
    </div>
<?php endif; ?>

<?php if (!empty($hayMas)): ?>
    <div class="enl-narrow">
        <span>
            <i class="bi bi-funnel"></i>
            Más de <?= (int) $topeResultados ?> resultados<?= empty($sugerenciasRefinar) ? '. Afina la búsqueda.' : '. Prueba a añadir una etiqueta:' ?>
        </span>
        <?php foreach ($sugerenciasRefinar as $s): ?>
            <a href="<?= esc($s['url'], 'attr') ?>" class="enl-narrow-chip" data-enl-nav>
                <?= esc($s['texto']) ?> <span><?= (int) $s['total'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="enl-list">
    <?php foreach ($enlaces as $e): ?>
        <?php
        $isVisto = (bool) $e['visto'];
        $relevancia = (int) $e['relevancia'];
        $host = parse_url($e['url'], PHP_URL_HOST);
        $dominio = $host ? preg_replace('/^www\./', '', $host) : '';
        $tieneBadges = !empty($catsPorEnlace[$e['id']]) || !empty($tagsPorEnlace[$e['id']]);
        ?>
        <div class="enl-item <?= $isVisto ? 'is-visto' : '' ?>">
            <a href="<?= site_url('enlaces/pagina/' . $e['id']) ?>" class="enl-item-favicon" title="Ver página interna">
                <?php if ($dominio): ?>
                    <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($dominio) ?>&sz=32"
                         alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                <?php else: ?>
                    <i class="bi bi-link-45deg"></i>
                <?php endif; ?>
            </a>

            <div class="enl-item-body">
                <div class="enl-item-title-row">
                    <a href="<?= esc($e['url']) ?>" target="_blank" rel="noopener" class="enl-item-title">
                        <?= esc($e['titulo']) ?>
                    </a>
                </div>

                <div class="enl-item-meta">
                    <?php if ($dominio): ?><span class="enl-item-domain"><?= esc($dominio) ?></span> · <?php endif; ?>
                    <?= date('d/m/Y', strtotime($e['fecha'])) ?>
                    <?php if ($relevancia > 0): ?>
                        · <span class="enl-item-relevancia"><i class="bi bi-star-fill"></i> <?= $relevancia ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($coincidencias[$e['id']])): ?>
                    <div class="enl-item-match">
                        <i class="bi bi-search"></i> Coincide en <?= esc(implode(', ', $coincidencias[$e['id']])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($e['extra'])): ?>
                    <div class="enl-item-extra"><?= esc(mb_strimwidth(strip_tags($e['extra']), 0, 160, '…')) ?></div>
                <?php endif; ?>

                <?php if ($tieneBadges): ?>
                    <div class="enl-item-badges">
                        <?php foreach (($catsPorEnlace[$e['id']] ?? []) as $c): if (!$c) continue;
                            [$colorTxt, $colorBg] = enl_color_from_string($c['nombre']);
                        ?>
                            <span class="enl-badge-cat" style="color: <?= $colorTxt ?>; background: <?= $colorBg ?>;">
                                #<?= esc($c['nombre']) ?>
                            </span>
                        <?php endforeach; ?>
                        <?php foreach (($tagsPorEnlace[$e['id']] ?? []) as $t): if (!$t) continue; ?>
                            <span class="enl-badge-tag"><?= esc($t['nombre']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="enl-item-actions">
                <button data-id="<?= $e['id'] ?>" class="enl-icon-btn btn-toggle-visto" title="Marcar visto/no visto">
                    <i class="bi <?= $isVisto ? 'bi-check-square-fill' : 'bi-square' ?>"></i>
                </button>
                <a class="enl-icon-btn" href="<?= site_url('enlaces/editar/' . $e['id']) ?>" title="Editar">
                    <i class="bi bi-pencil"></i>
                </a>
                <a class="enl-icon-btn enl-icon-btn-danger" href="<?= site_url('enlaces/borrar/' . $e['id']) ?>"
                   onclick="return confirm('¿Eliminar enlace?');" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
