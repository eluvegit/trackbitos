<?php
/**
 * Una tarjeta del repaso (o el estado "no queda nada"). Una sola a la vez
 * para no saturar; cada botón es una decisión completa, sin formularios.
 * Variables: $tarjeta = Enlaces::repasoTarjeta().
 */
if (!function_exists('rep_hace')) {
    function rep_hace(?string $fecha): string
    {
        $t = $fecha ? strtotime($fecha) : false;
        if (!$t) return '';
        $dias = (int) floor((time() - $t) / 86400);
        if ($dias < 1)   return 'hoy';
        if ($dias < 31)  return 'hace ' . $dias . ($dias === 1 ? ' día' : ' días');
        $meses = (int) floor($dias / 30.44);
        if ($meses < 12) return 'hace ' . $meses . ($meses === 1 ? ' mes' : ' meses');
        $anios = (int) floor($meses / 12);
        return 'hace ' . $anios . ($anios === 1 ? ' año' : ' años');
    }
}

$item = $tarjeta['item'];
?>
<?php if (!$item): ?>
    <div class="rep-vacio">
        <?php if (!empty($tarjeta['catNombre'])): ?>
            <div class="rep-vacio-emoji">✨</div>
            <p class="mb-3"><strong><?= esc($tarjeta['catNombre']) ?></strong> está al día.</p>
            <a href="<?= site_url('enlaces/repaso') ?>" class="btn btn-primary">Seguir al azar</a>
        <?php else: ?>
            <div class="rep-vacio-emoji">🎉</div>
            <p class="mb-1">No queda nada por repasar ahora mismo.</p>
            <p class="text-muted small mb-3">Los que mandaste a "Luego" volverán cuando les toque.</p>
            <a href="<?= site_url('enlaces') ?>" class="btn btn-outline-secondary">Volver a Enlaces</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $host    = parse_url((string) $item['url'], PHP_URL_HOST);
    $dominio = $host ? preg_replace('/^www\./', '', $host) : '';
    $titulo  = trim((string) $item['titulo']) !== '' ? $item['titulo'] : ($dominio ?: $item['url']);
    $nota    = trim(strip_tags((string) ($item['extra'] ?? '')));
    ?>
    <div class="rep-card" data-id="<?= (int) $item['id'] ?>">
        <div class="rep-meta">
            <?php if ($dominio): ?>
                <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($dominio) ?>&sz=32"
                     alt="" width="16" height="16" loading="lazy" onerror="this.style.visibility='hidden'">
                <span><?= esc($dominio) ?></span> ·
            <?php endif; ?>
            <span title="<?= esc((string) $item['fecha'], 'attr') ?>">guardado <?= esc(rep_hace($item['fecha'])) ?></span>
        </div>

        <a href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener" class="rep-titulo" data-rep-abrir>
            <?= esc($titulo) ?>
        </a>

        <?php if (!empty($tarjeta['cats'])): ?>
            <div class="rep-cats">
                <?php foreach ($tarjeta['cats'] as $c): ?>
                    <span class="rep-cat">#<?= esc($c['nombre']) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($nota !== ''): ?>
            <div class="rep-nota"><?= esc(mb_strimwidth($nota, 0, 280, '…')) ?></div>
        <?php endif; ?>

        <?php // Opcionales: se guardan junto con la decisión que tomes (no hace falta tocarlas). ?>
        <?php $rel = (int) $item['relevancia']; ?>
        <div class="rep-estrellas" data-rel="<?= $rel ?>" title="Puntuar (opcional): se guarda con la decisión">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="rep-estrella<?= $i <= $rel ? ' is-on' : '' ?>" data-v="<?= $i ?>" aria-label="<?= $i ?> estrella<?= $i === 1 ? '' : 's' ?>">★</button>
            <?php endfor; ?>
            <span class="rep-estrellas-hint">Shift+1–5</span>
        </div>

        <div class="rep-abrir-fila">
            <a href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right"></i> Abrir <kbd>O</kbd>
            </a>
            <a href="<?= site_url('enlaces/pagina/' . $item['id']) ?>" target="_blank" class="btn btn-sm btn-link text-muted">Página</a>
        </div>

        <div class="rep-acciones">
            <?php // "Me lo quedo" = visto=1 (+ estrellas si las tocaste): una sola forma de quedárselo. ?>
            <button type="button" class="rep-btn rep-btn-visto" data-accion="visto">
                <span class="rep-btn-ico">✓</span> Me lo quedo <kbd>1</kbd>
            </button>
            <button type="button" class="rep-btn rep-btn-fuera" data-accion="fuera">
                <span class="rep-btn-ico">🗃</span> Fuera <kbd>2</kbd>
            </button>
            <?php if ($tarjeta['puedeLuego']): ?>
                <button type="button" class="rep-btn rep-btn-luego" data-accion="luego">
                    <span class="rep-btn-ico">↷</span> Luego <kbd>3</kbd>
                </button>
            <?php else: ?>
                <div class="rep-btn rep-btn-luego is-off" title="Ya se ha pospuesto dos veces">
                    <span class="rep-btn-ico">↷</span> Ya pospuesto 2 veces: toca decidir
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php // El resto de la tanda: solo para verlas venir, sin acciones. ?>
    <?php if (!empty($tarjeta['siguientes'])): ?>
        <div class="rep-cola">
            <div class="rep-cola-label">Después, en esta tanda (<?= count($tarjeta['siguientes']) ?>)</div>
            <?php foreach ($tarjeta['siguientes'] as $s): ?>
                <?php
                $sHost    = parse_url((string) $s['url'], PHP_URL_HOST);
                $sDominio = $sHost ? preg_replace('/^www\./', '', $sHost) : '';
                $sTitulo  = trim((string) $s['titulo']) !== '' ? $s['titulo'] : ($sDominio ?: $s['url']);
                ?>
                <div class="rep-cola-item">
                    <?php if ($sDominio): ?>
                        <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($sDominio) ?>&sz=32"
                             alt="" width="14" height="14" loading="lazy" onerror="this.style.visibility='hidden'">
                    <?php endif; ?>
                    <span class="rep-cola-titulo"><?= esc($sTitulo) ?></span>
                    <?php if ($sDominio): ?><span class="rep-cola-dominio"><?= esc($sDominio) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
