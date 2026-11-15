<?php
/**
 * Una tarjeta del repaso (estilo fv), o el «no queda nada». Una sola a la
 * vez; cada botón es una decisión completa.
 * Variables: $tarjeta = Enlaces::repasoTarjeta().
 */
helper('enlaces');
$item = $tarjeta['item'];
?>
<?php if (!$item): ?>
    <div class="fv-panel en-rep-vacio">
        <?php if (!empty($tarjeta['catNombre'])): ?>
            <div class="emoji">✨</div>
            <div><b><?= esc($tarjeta['catNombre']) ?></b> está al día.</div>
            <a href="<?= site_url('enlaces/repaso') ?>" class="fv-btn fv-btn-pri">Seguir al azar</a>
        <?php else: ?>
            <div class="emoji">🎉</div>
            <div>No queda nada por repasar ahora mismo.</div>
            <div class="t3" style="font-size:12.5px">Los que mandaste a «Luego» volverán cuando les toque.</div>
            <a href="<?= site_url('enlaces') ?>" class="fv-btn">Volver a Enlaces</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $dominio = enl_dominio($item['url']);
    $titulo  = trim((string) $item['titulo']) !== '' ? $item['titulo'] : ($dominio ?: $item['url']);
    $nota    = trim(strip_tags((string) ($item['extra'] ?? '')));
    $rel     = (int) $item['relevancia'];
    ?>
    <div class="en-rep-card" data-id="<?= (int) $item['id'] ?>">
        <div class="rc-meta en-meta">
            <?php if ($dominio): ?>
                <span class="en-dominio"><img src="https://www.google.com/s2/favicons?domain=<?= urlencode($dominio) ?>&sz=32" alt="" width="14" height="14" onerror="this.remove()"><?= esc($dominio) ?></span>
            <?php endif; ?>
            <span title="<?= esc((string) $item['fecha'], 'attr') ?>">guardado <?= esc(enl_hace($item['fecha'])) ?></span>
        </div>

        <a href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener" class="en-rep-titulo" data-rep-abrir><?= esc($titulo) ?></a>

        <?php if (!empty($tarjeta['cats'])): ?>
            <div class="en-badges">
                <?php foreach ($tarjeta['cats'] as $c): ?><span class="en-cat" style="--h:<?= crc32($c['nombre']) % 360 ?>"><?= esc($c['nombre']) ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($nota !== ''): ?><div class="en-rep-nota"><?= esc(mb_strimwidth($nota, 0, 280, '…')) ?></div><?php endif; ?>

        <div class="en-rep-fila">
            <a href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener" class="fv-btn"><i class="bi bi-box-arrow-up-right"></i>Abrir <kbd>O</kbd></a>
            <a href="<?= site_url('enlaces/pagina/' . $item['id']) ?>" target="_blank" class="fv-btn fv-btn-sm" style="height:36px"><i class="bi bi-journal-text"></i>Página</a>
            <?php // Opcionales: se guardan junto con la decisión (no hace falta tocarlas). ?>
            <div class="en-estrellas rep-estrellas der" data-rel="<?= $rel ?>" title="Puntuar (opcional, Shift+1–5): se guarda con la decisión">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="en-estrella<?= $i <= $rel ? ' on' : '' ?>" data-v="<?= $i ?>" aria-label="<?= $i ?> estrellas">★</button>
                <?php endfor; ?>
            </div>
        </div>

        <div class="en-rep-acc">
            <button type="button" class="en-rep-btn quedo" data-accion="visto"><i class="bi bi-check-circle-fill"></i>Me lo quedo <kbd>1</kbd></button>
            <button type="button" class="en-rep-btn" data-accion="fuera"><i class="bi bi-archive"></i>Fuera <kbd>2</kbd></button>
            <?php if ($tarjeta['puedeLuego']): ?>
                <button type="button" class="en-rep-btn luego" data-accion="luego"><i class="bi bi-arrow-return-right"></i>Luego <kbd>3</kbd></button>
            <?php else: ?>
                <div class="en-rep-btn off" title="Ya se ha pospuesto dos veces">Ya pospuesto 2 veces: toca decidir</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($tarjeta['siguientes'])): ?>
        <div class="en-rep-cola">
            <div class="en-rep-cola-h">Después, en esta tanda (<?= count($tarjeta['siguientes']) ?>)</div>
            <?php foreach ($tarjeta['siguientes'] as $s): ?>
                <?php $sd = enl_dominio($s['url']); ?>
                <div class="en-rep-cola-i">
                    <?php if ($sd): ?><img src="https://www.google.com/s2/favicons?domain=<?= urlencode($sd) ?>&sz=32" alt="" width="14" height="14" loading="lazy" onerror="this.remove()"><?php endif; ?>
                    <span class="t"><?= esc(trim((string) $s['titulo']) !== '' ? $s['titulo'] : ($sd ?: $s['url'])) ?></span>
                    <?php if ($sd): ?><span class="d"><?= esc($sd) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
