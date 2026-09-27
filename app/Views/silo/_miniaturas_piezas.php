<?php
/**
 * Parcial: carpetas como PORTADAS — cada carpeta compone sus miniaturas
 * (proxies del agente: fotos primero, luego fotogramas de vídeo) en un
 * mosaico, con el nombre completo de la carpeta encima y #ID · año ·
 * tamaño en chips. Todas las tarjetas tienen la misma altura; las que aún
 * no tienen proxies salen con un fondo liso y el icono de carpeta.
 * Espera $piezas y $proxiesPorPieza (pieza_id => proxies) en el scope.
 */
$proxiesPorPieza = $proxiesPorPieza ?? [];
$qsDesde         = isset($unidad['id']) ? '?desde=' . (int) $unidad['id'] : '';
?>
<style>
    .silo-mini-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: .75rem;
    }
    .silo-mini {
        position: relative;
        display: block;
        height: 230px;
        border-radius: .5rem;
        overflow: hidden;
        background: var(--bs-tertiary-bg);
        color: #fff;
        text-decoration: none;
        outline: 1px solid var(--bs-border-color);
        transition: outline-color .12s ease;
    }
    .silo-mini:hover { outline-color: var(--bs-secondary); color: #fff; }
    .silo-mini:hover .silo-mini-mosaico img { transform: scale(1.04); }

    /* Mosaico: la 1ª miniatura manda (grande a la izquierda) y el resto
       rellena. El nº de celdas cambia la rejilla (data-n = 1..5). */
    .silo-mini-mosaico {
        position: absolute; inset: 0;
        display: grid; gap: 2px;
        grid-template-columns: 1fr; grid-template-rows: 1fr;
    }
    .silo-mini-mosaico[data-n="2"] { grid-template-columns: 1fr 1fr; }
    .silo-mini-mosaico[data-n="3"] { grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
    .silo-mini-mosaico[data-n="4"] { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
    .silo-mini-mosaico[data-n="5"] { grid-template-columns: 2fr 1fr 1fr; grid-template-rows: 1fr 1fr; }
    .silo-mini-mosaico[data-n="3"] > :first-child,
    .silo-mini-mosaico[data-n="5"] > :first-child { grid-row: span 2; }
    .silo-mini-mosaico img {
        width: 100%; height: 100%; min-height: 0;
        object-fit: cover; display: block;
        transition: transform .3s ease;
    }
    .silo-mini-vacia {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        padding-bottom: 3rem;
        background: linear-gradient(135deg, rgba(245, 158, 11, .18), rgba(245, 158, 11, .04));
    }
    .silo-mini-vacia i { font-size: 3.5rem; color: rgba(251, 191, 36, .55); }

    /* Capa de texto: degradado de abajo arriba para que se lea el nombre
       sobre cualquier foto. */
    .silo-mini-texto {
        position: absolute; left: 0; right: 0; bottom: 0;
        max-height: 100%;
        overflow: hidden;
        padding: 2.5rem .7rem .6rem;
        background: linear-gradient(to top, rgba(0, 0, 0, .88) 0%, rgba(0, 0, 0, .7) 55%, rgba(0, 0, 0, 0) 100%);
    }
    .silo-mini-nombre {
        font-size: .8rem;
        font-weight: 600;
        line-height: 1.25;
        overflow-wrap: anywhere;
        text-shadow: 0 1px 2px rgba(0, 0, 0, .6);
    }
    .silo-mini-chips {
        position: absolute; top: .45rem; left: .45rem; right: .45rem;
        display: flex; flex-wrap: wrap; gap: .3rem;
        pointer-events: none;
    }
    .silo-mini-chip {
        font-size: .66rem;
        line-height: 1;
        padding: .28rem .45rem;
        border-radius: 999px;
        background: rgba(0, 0, 0, .62);
        backdrop-filter: blur(3px);
        white-space: nowrap;
    }
    .silo-mini-chip-tamano { margin-left: auto; }
</style>

<?php if (empty($piezas)): ?>
    <p class="text-muted">Vacío.</p>
<?php else: ?>
    <div class="silo-mini-grid">
        <?php foreach ($piezas as $p): ?>
            <?php
            $partes        = silo_carpeta_partes($p);
            $miniaturas    = array_slice($proxiesPorPieza[(int) $p['id']] ?? [], 0, 5);
            $coincidencias = $p['ficheros_coincidentes'] ?? [];
            ?>
            <a href="<?= site_url('silo/' . $p['id']) . $qsDesde ?>" class="silo-mini"
               title="<?= esc($p['nombre_carpeta'], 'attr') ?>">

                <?php if ($miniaturas): ?>
                    <span class="silo-mini-mosaico" data-n="<?= count($miniaturas) ?>">
                        <?php foreach ($miniaturas as $m): ?>
                            <img src="<?= esc(silo_proxy_url($m['url']), 'attr') ?>" alt="" loading="lazy" decoding="async">
                        <?php endforeach; ?>
                    </span>
                <?php else: ?>
                    <span class="silo-mini-vacia"><i class="bi bi-folder-fill"></i></span>
                <?php endif; ?>

                <span class="silo-mini-chips">
                    <span class="silo-mini-chip">#<?= esc($p['id_negocio']) ?></span>
                    <?php if ($partes['anio'] !== ''): ?>
                        <span class="silo-mini-chip"><i class="bi bi-calendar3 me-1"></i><?= esc($partes['anio']) ?></span>
                    <?php endif; ?>
                    <?php if ($coincidencias): ?>
                        <span class="silo-mini-chip"><i class="bi bi-search me-1"></i><?= count($coincidencias) ?></span>
                    <?php endif; ?>
                    <span class="silo-mini-chip silo-mini-chip-tamano"><?= esc(silo_formatear_tamano($p['tamano_bytes'] ?? null)) ?></span>
                </span>

                <span class="silo-mini-texto">
                    <span class="silo-mini-nombre d-block"><?= esc($p['nombre_carpeta']) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
