<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('text'); ?>

<?php
$e = $escena ?? [];
$orden     = (int)($e['orden'] ?? 0);
$bloque    = trim($e['escena_bloque'] ?? '');
$tomas     = trim($e['escena_tomas'] ?? '');
$ubic      = trim($e['escena_ubicacion'] ?? '');
$hora      = trim($e['plano_hora_dia'] ?? '');
$plano     = trim($e['camara_tipo_plano'] ?? '');
$angulo    = trim($e['camara_angulo'] ?? '');
$mov       = trim($e['camara_movimiento'] ?? '');
$soporte   = trim($e['camara_soporte'] ?? '');
$optica    = trim($e['camara_optica'] ?? '');
$apertura  = trim($e['camara_apertura'] ?? '');
$fps       = trim($e['camara_fps'] ?? '');
$vel       = trim($e['camara_velocidad'] ?? '');
$iso       = trim($e['camara_iso'] ?? '');
$wb        = trim($e['camara_wb'] ?? '');
$nd        = trim($e['camara_nd'] ?? '');
$fx        = trim($e['escena_efecto_especial'] ?? '');
$actores   = ($e['plano_actores'] ?? 'N') === 'S';

$renderRefs = function (string $txt) {
    return nl2br(auto_link(esc($txt), 'both', true));
};
?>

<style>
    /* ====== IMPRESIÓN A4, TEXTO CONDENSADO SIN CAJAS ====== */
    @media print {

        /* Oculta cualquier cabecera/pie propio de la página */
        .no-print,
        .print-hide-header,
        .print-hide-footer,
        header,
        footer,
        .toolbar,
        .page-header,
        .page-footer {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
        }

        /* Márgenes de página (ajusta si quieres más área útil) */
        @page {
            margin: 10mm;
        }
    }

    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    :root {
        --print-font: 10px;
        --line: 1.25;
        --gap: 10px;
    }

    @media print {

        html,
        body {
            background: #fff !important;
        }

        body {
            font-size: var(--print-font);
            line-height: var(--line);
            color: #000;
        }

        .no-print {
            display: none !important;
        }

        .h1 {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .muted {
            color: #333;
        }

        .sep {
            border: 0;
            border-top: 1px solid #000;
            margin: 6px 0;
        }

        .tiny {
            font-size: 9px;
        }

        /* Bloque textual: 2 columnas para comprimir a una sola página */
        .print-onepage {
            column-count: 2;
            column-gap: var(--gap);
            page-break-after: always;
        }

        .section {
            break-inside: avoid;
            margin-bottom: 6px;
        }

        .section h2 {
            font-size: 11px;
            margin: 0 0 4px 0;
        }

        .section h3 {
            font-size: 10px;
            margin: 0 0 3px 0;
        }

        .kv {
            margin: 0 0 2px 0;
        }

        .kv dt {
            float: left;
            width: 40%;
            clear: left;
            font-weight: 600;
        }

        .kv dd {
            margin-left: 42%;
        }

        .kv:after {
            content: "";
            display: block;
            clear: both;
        }

        /* Chips inline (sin cajas) */
        .chips {
            margin: 2px 0 4px 0;
        }

        .chip {
            margin-right: 6px;
        }

        /* Galerías: nueva página, miniaturas básicas */
        .print-break {
            page-break-before: always;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }

        .gallery img {
            width: 100%;
            height: 45mm;
            object-fit: cover;
            border: 1px solid #000;
        }

        /* Si aún se pasa, descomenta para reducir un poco todo el bloque de texto */
        /* .print-onepage { zoom: 0.94; } */
    }

    @media screen {
        .toolbar {
            position: sticky;
            top: 0;
            background: #fff;
            padding: .75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
            margin-bottom: .75rem;
        }

        .h1 {
            font-size: 20px;
            margin-bottom: .25rem;
        }

        .sep {
            border: 0;
            border-top: 1px solid rgba(0, 0, 0, .2);
            margin: .5rem 0;
        }
    }

    @media print {
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(40mm, 1fr));
            /* columnas adaptables */
            gap: 4mm;
        }

        .gallery img {
            width: 100%;
            height: auto;
            /* mantiene proporción */
            max-height: 40mm;
            /* límite para no ocupar toda la página */
            object-fit: cover;
            border: 1px solid #000;
        }
    }

    @media screen {
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            /* responsive en pantalla */
            gap: 8px;
        }

        .gallery img {
            width: 100%;
            height: auto;
            max-height: 180px;
            /* más pequeña en pantalla */
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, .15);
        }
    }

    .kv-2col {
        display: grid;
        grid-template-columns: auto 1fr auto 1fr;
        /* 2 columnas de pares dt/dd */
        column-gap: 12px;
        row-gap: 4px;
    }

    .kv-2col dt {
        font-weight: bold;
        margin: 0;
    }

    .kv-2col dd {
        margin: 0;
    }
</style>

<div class="container py-3">

    <!-- Toolbar pantalla (no se imprime) -->
    <div class="toolbar no-print d-flex justify-content-between">
        <div>
            <div class="h1"><?= esc($proyecto['titulo']) ?> — Escena</div>
            <div class="tiny muted">
                Proyecto #<?= esc($proyecto['id']) ?> · Escena #<?= esc($e['id']) ?>
            </div>
        </div>
        <div class="d-flex gap-2 mb-3">
            <a class="btn btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver</a>
            <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $e['id']) ?>?print=1">🖨️ PDF</a>
            <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">✏️ Editar</a>
        </div>
    </div>

    <!-- ENCABEZADO IMPRESIÓN -->
    <div class="section">
        <div class="h1"><?= esc($bloque ?: 'Bloque sin título') ?><?php if ($tomas): ?> · Toma/s: <?= esc($tomas) ?><?php endif; ?></div>
        <div class="muted tiny">
            Orden: <?= esc($orden) ?><?php if ($hora): ?> · Hora del día: <?= esc($hora) ?><?php endif; ?><?php if ($ubic): ?> · 📍 <?= esc($ubic) ?><?php endif; ?><?php if ($actores): ?> · Actores: Sí<?php endif; ?><?php if ($fx): ?> · FX: <?= esc($fx) ?><?php endif; ?>
        </div>
        <?php if ($plano || $angulo || $mov || $soporte): ?>
            <div class="chips tiny">
                <?php if ($plano): ?><span class="chip"><strong>Plano:</strong> <?= esc($plano) ?></span><?php endif; ?>
                <?php if ($angulo): ?><span class="chip"><strong>Ángulo:</strong> <?= esc($angulo) ?></span><?php endif; ?>
                <?php if ($mov): ?><span class="chip"><strong>Movimiento:</strong> <?= esc($mov) ?></span><?php endif; ?>
                <?php if ($soporte): ?><span class="chip"><strong>Soporte:</strong> <?= esc($soporte) ?></span><?php endif; ?>
            </div>
        <?php endif; ?>
        <hr class="sep">
    </div>

    <!-- BLOQUE TEXTUAL EN 2 COLUMNAS (UNA PÁGINA) -->
    <div class="print-onepage">

        <!-- ESCENA -->
        <div class="section">
            <h2>Escena</h2>
            <dl class="kv">
                <?php if (!empty($e['escena_descripcion'])): ?>
                    <dt>Descripción</dt>
                    <dd><?= nl2br(esc($e['escena_descripcion'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($e['escena_objetivo'])): ?>
                    <dt>Objetivo narrativo</dt>
                    <dd><?= nl2br(esc($e['escena_objetivo'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($e['escena_accion'])): ?>
                    <dt>Acción</dt>
                    <dd><?= nl2br(esc($e['escena_accion'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($e['escena_cont_previa'])): ?>
                    <dt>Continuidad (previa)</dt>
                    <dd><?= nl2br(esc($e['escena_cont_previa'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($e['escena_cont_posterior'])): ?>
                    <dt>Continuidad (posterior)</dt>
                    <dd><?= nl2br(esc($e['escena_cont_posterior'])) ?></dd>
                <?php endif; ?>
            </dl>
            <hr class="sep">
        </div>

        <!-- CÁMARA -->
        <div class="section">
            <h2>Cámara</h2>
            <dl class="kv kv-2col">
                <?php if ($optica): ?><dt>Óptica</dt>
                    <dd><?= esc($optica) ?></dd><?php endif; ?>
                <?php if ($apertura): ?><dt>Apertura</dt>
                    <dd><?= esc($apertura) ?></dd><?php endif; ?>
                <?php if ($fps): ?><dt>FPS</dt>
                    <dd><?= esc($fps) ?></dd><?php endif; ?>
                <?php if ($vel): ?><dt>Velocidad</dt>
                    <dd><?= esc($vel) ?></dd><?php endif; ?>
                <?php if ($iso): ?><dt>ISO</dt>
                    <dd><?= esc($iso) ?></dd><?php endif; ?>
                <?php if ($wb): ?><dt>Balance blancos</dt>
                    <dd><?= esc($wb) ?></dd><?php endif; ?>
                <?php if ($nd): ?><dt>Filtro ND</dt>
                    <dd><?= esc($nd) ?></dd><?php endif; ?>
                <?php if ($plano): ?><dt>Tipo de plano</dt>
                    <dd><?= esc($plano) ?></dd><?php endif; ?>
                <?php if ($angulo): ?><dt>Ángulo</dt>
                    <dd><?= esc($angulo) ?></dd><?php endif; ?>
                <?php if ($mov): ?><dt>Movimiento</dt>
                    <dd><?= esc($mov) ?></dd><?php endif; ?>
                <?php if ($soporte): ?><dt>Soporte</dt>
                    <dd><?= esc($soporte) ?></dd><?php endif; ?>
                <?php if (!empty($e['camara_modelo'])): ?><dt>Cámara</dt>
                    <dd><?= esc($e['camara_modelo']) ?></dd><?php endif; ?>
            </dl>
            <hr class="sep">
        </div>


        <!-- CONSTRUCCIÓN DEL PLANO -->
        <div class="section">
            <h2>Construcción del plano</h2>
            <dl class="kv">
                <?php if (!empty($e['plano_esquema_iluminacion'])): ?>
                    <dt>Esquema de iluminación</dt>
                    <dd><?= nl2br(esc($e['plano_esquema_iluminacion'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($e['plano_objetos'])): ?>
                    <dt>Objetos en escena</dt>
                    <dd><?= nl2br(esc($e['plano_objetos'])) ?></dd>
                <?php endif; ?>
                <dt>Actores</dt>
                <dd><?= $actores ? 'Sí' : 'No' ?></dd>
                <?php if (!empty($e['plano_toma_alternativa'])): ?>
                    <dt>Toma alternativa</dt>
                    <dd><?= nl2br(esc($e['plano_toma_alternativa'])) ?></dd>
                <?php endif; ?>
                <?php if (!empty($e['plano_notas'])): ?>
                    <dt>Notas</dt>
                    <dd><?= nl2br(esc($e['plano_notas'])) ?></dd>
                <?php endif; ?>
            </dl>
            <hr class="sep">
        </div>

        <!-- SONIDO -->
        <div class="section">
            <h2>Sonido</h2>
            <dl class="kv">
                <dt>Ambiente</dt>
                <dd><?= ($e['sonido_ambiente'] ?? 'N') === 'S' ? 'Sí' : 'No' ?></dd>
                <dt>Antiviento</dt>
                <dd><?= ($e['sonido_antiviento'] ?? 'N') === 'S' ? 'Sí' : 'No' ?></dd>
                <?php if (!empty($e['sonido_dialogo_escrito'])): ?>
                    <dt>Diálogo escrito</dt>
                    <dd><?= nl2br(esc($e['sonido_dialogo_escrito'])) ?></dd>
                <?php endif; ?>
            </dl>
            <hr class="sep">
        </div>

        <!-- REFERENCIAS (TEXTO) -->
        <div class="section">
            <h2>Referencias (texto)</h2>
            <?php if (!empty($e['plano_ref_lugar_texto'])): ?>
                <h3>Lugar / objetos</h3>
                <div class="tiny"><?= $renderRefs((string)$e['plano_ref_lugar_texto']) ?></div>
            <?php endif; ?>
            <?php if (!empty($e['plano_ref_inspiracion_texto'])): ?>
                <h3>Inspiración</h3>
                <div class="tiny"><?= $renderRefs((string)$e['plano_ref_inspiracion_texto']) ?></div>
            <?php endif; ?>
        </div>

    </div><!-- /print-onepage -->

    <!-- GALERÍAS (en páginas aparte) -->
    <div class="print-break">
        <h2 class="mt-5">Referencias: Lugar y objetos</h2>
        <?php if (empty($imagenes_lugar)): ?>
            <div class="tiny muted">Sin imágenes.</div>
        <?php else: ?>
            <div class="gallery">
                <?php foreach ($imagenes_lugar as $img): $src = base_url($img['ruta']); ?>
                    <a href="<?= $src ?>" target="_blank" rel="noopener">
                        <img src="<?= $src ?>" alt="">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="print-break">
        <h2 class="mt-5">Referencias: Inspiración</h2>
        <?php if (empty($imagenes_insp)): ?>
            <div class="tiny muted">Sin imágenes.</div>
        <?php else: ?>
            <div class="gallery">
                <?php foreach ($imagenes_insp as $img): $src = base_url($img['ruta']); ?>
                    <a href="<?= $src ?>" target="_blank" rel="noopener">
                        <img src="<?= $src ?>" alt="">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Controles pantalla -->
    <div class="no-print mt-3 d-flex gap-2">
        <a class="btn btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver</a>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir / PDF</button>
        <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $e['id']) ?>">✏️ Editar</a>
    </div>
</div>

<script>
    // Auto-print si llega ?print=1
    (function() {
        const p = new URLSearchParams(location.search);
        if (p.get('print') === '1') setTimeout(() => window.print(), 300);
    })();
</script>

<?= $this->endSection() ?>