<?php
/**
 * Tarjeta de un modelo dentro de una sesión (pestaña Modelos, estilo fv).
 * El perfil (nombre, contacto, notas, opciones de ropa) es reutilizable:
 * lo que se edita aquí vale para todas sus sesiones. Lo único propio de
 * esta sesión es qué ropa se elige (chip marcado). El controlador devuelve
 * este mismo HTML tras cada cambio (Sesiones::tarjetaModelo).
 *
 * @var array $m   perfil + 'ropa' (cada una con 'elegida') + 'fotos' (la 1.ª = avatar)
 * @var int   $sid
 */
$mid = (int) $m['id'];
$nElegidas = count(array_filter($m['ropa'], static fn ($r) => (int) $r['elegida'] === 1));
?>
<section class="fv-panel ss-modelo" data-id="<?= $mid ?>">
    <div class="fv-ph">
        <?php if (!empty($m['fotos'])): ?>
            <a class="ss-mod-avatar" href="<?= base_url($m['fotos'][0]['imagen']) ?>" target="_blank" rel="noopener"><img src="<?= base_url($m['fotos'][0]['imagen']) ?>" alt=""></a>
        <?php else: ?>
            <i class="bi bi-person-circle t3"></i>
        <?php endif; ?>
        <input class="ss-mod-nombre t1" data-campo="nombre" value="<?= esc($m['nombre'], 'attr') ?>" maxlength="150" aria-label="Nombre">
        <span class="cuenta" title="Ropa elegida para esta sesión"><?= $nElegidas ?>/<?= count($m['ropa']) ?></span>
        <button type="button" class="fv-ibtn der ss-mod-quitar" title="Quitar de esta sesión (el perfil se conserva)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="ss-mod-datos">
        <?php $ig = ses_instagram($m['contacto'] ?? null); ?>
        <div class="ss-mod-contacto">
            <input class="fv-input" data-campo="contacto" value="<?= esc($m['contacto'] ?? '', 'attr') ?>" maxlength="150" placeholder="Instagram (@usuario) o teléfono">
            <a class="ss-ig" data-ig href="<?= $ig ? 'https://www.instagram.com/' . esc($ig, 'attr') . '/' : '#' ?>" target="_blank" rel="noopener"<?= $ig ? '' : ' hidden' ?> title="Abrir el perfil de Instagram"><i class="bi bi-instagram"></i><span>@<?= esc($ig ?? '') ?></span><i class="bi bi-box-arrow-up-right"></i></a>
        </div>
        <textarea class="fv-input" data-campo="notas" rows="2" placeholder="Notas del perfil: tallas, disponibilidad…"><?= esc($m['notas'] ?? '') ?></textarea>
    </div>

    <div class="ss-mod-fotos" data-zona="fotos">
        <span class="ss-lbl"><i class="bi bi-person-square"></i> Fotos del perfil <small class="t3">— arrastra aquí; la primera es la principal</small></span>
        <div class="ss-mod-fotos-lista">
            <?php foreach ($m['fotos'] ?? [] as $i => $f): ?>
                <div class="ss-mod-foto<?= $i === 0 ? ' principal' : '' ?>" data-id="<?= (int) $f['id'] ?>">
                    <a href="<?= base_url($f['imagen']) ?>" target="_blank" rel="noopener"><img src="<?= base_url($f['imagen']) ?>" alt="" loading="lazy"></a>
                    <div class="ss-mod-foto-acc">
                        <?php if ($i > 0): ?><button type="button" class="ss-foto-principal" title="Poner como principal"><i class="bi bi-star"></i></button><?php endif; ?>
                        <button type="button" class="ss-foto-borrar" title="Borrar foto"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
            <label class="ss-mod-foto-add" title="Añadir fotos"><input type="file" accept="image/*" multiple hidden><i class="bi bi-plus-lg"></i><span>Fotos</span></label>
        </div>
    </div>

    <div class="ss-ropa">
        <span class="ss-lbl"><i class="bi bi-handbag"></i> Opciones de ropa <small class="t3">— toca para elegir las de esta sesión · arrastra o pega (Ctrl+V) fotos para añadir outfits</small></span>
        <div class="ss-ropa-lista">
            <?php foreach ($m['ropa'] as $r): ?>
                <div class="ss-ropa-op<?= (int) $r['elegida'] === 1 ? ' on' : '' ?>" data-id="<?= (int) $r['id'] ?>">
                    <button type="button" class="ss-ropa-elegir" title="<?= (int) $r['elegida'] === 1 ? 'Elegida para esta sesión' : 'Elegir para esta sesión' ?>">
                        <?php if ($r['imagen']): ?><img src="<?= base_url($r['imagen']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-handbag"></i><?php endif; ?>
                        <span><?= esc($r['texto']) ?></span>
                        <i class="bi bi-check-circle-fill ss-ropa-ok"></i>
                    </button>
                    <button type="button" class="ss-ropa-borrar" title="Borrar opción del perfil"><i class="bi bi-x-lg"></i></button>
                </div>
            <?php endforeach; ?>
        </div>
        <form class="ss-form-mini ss-ropa-nueva">
            <input class="fv-input" name="texto" maxlength="150" placeholder="Nueva opción: «vestido rojo», «vaqueros + blanca»…">
            <label class="fv-btn fv-btn-sm ss-archivo" title="Foto (opcional)"><input type="file" name="imagen" accept="image/*"><i class="bi bi-camera"></i><span>Foto</span></label>
            <button type="submit" class="fv-btn fv-btn-sm"><i class="bi bi-plus-lg"></i>Añadir</button>
        </form>
    </div>
</section>
