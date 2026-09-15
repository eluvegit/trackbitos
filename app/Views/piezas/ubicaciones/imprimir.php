<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    /**
     * Chuleta física de dónde está cada cosa, para imprimir o guardar como
     * PDF (mismo patrón que Sesiones::exportar: HTML con @media print, sin
     * librería de PDF). Sin cantidades a propósito — el recuento ya vive en
     * Existencias, esto es solo para localizar a ojo. Los huecos vacíos
     * llevan líneas en blanco para apuntar a mano lo que se guarde ahí sin
     * tener que reimprimir el documento cada vez.
     *
     * Sin rastro de la app al imprimir: se oculta la barra de navegación
     * (logo + "Trackbitos") además del pie de página, y el título de la
     * pestaña (ver 'title' en UbicacionesController::imprimir()) tampoco la
     * nombra — es lo que casi todos los navegadores meten en la cabecera
     * automática de impresión. La URL de esa cabecera no se puede quitar
     * desde la página: se apaga a mano en el diálogo de impresión
     * ("Más ajustes" → desmarcar "Encabezados y pies de página").
     */
?>

<style>
    .estuche-imprimir { break-inside: avoid; margin-bottom: 1.25rem; }
    .estuche-imprimir .titulo { font-size: 1.05rem; font-weight: 700; }
    .hueco-imprimir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); gap: .6rem; margin-top: .5rem; }
    .hueco-imprimir {
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        padding: .45rem .6rem;
        break-inside: avoid;
    }
    .hueco-imprimir .codigo { font-weight: 700; font-size: .9rem; }
    .hueco-imprimir ul { list-style: none; margin: .25rem 0 0; padding: 0; font-size: .78rem; }
    .hueco-imprimir li { display: flex; align-items: center; gap: .35rem; margin-top: .25rem; }
    .hueco-imprimir .linea-vacia {
        border-bottom: 1px solid var(--bs-border-color);
        height: 1.1rem;
        margin-top: .35rem;
    }
    .miniatura-imprimir {
        width: 1.4rem;
        height: 1.4rem;
        object-fit: cover;
        border-radius: .25rem;
        border: 1px solid var(--bs-border-color);
        flex: none;
    }
    .tabla-por-pieza { font-size: .82rem; }
    .tabla-por-pieza .miniatura-imprimir { width: 1.8rem; height: 1.8rem; }

    @media print {
        .no-print { display: none !important; }
        nav.navbar { display: none !important; }
        .hueco-imprimir { border-color: #000; }
        .hueco-imprimir .linea-vacia { border-bottom-color: #000; }
        .miniatura-imprimir { border-color: #000; }
        .salto-pagina { break-before: page; }
        a { color: inherit !important; text-decoration: none !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Volver a Ubicaciones
    </a>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimir / guardar como PDF
    </button>
</div>

<h5 class="mb-1"><i class="bi bi-geo-alt text-primary"></i> Dónde está cada cosa</h5>
<p class="text-muted small mb-4">
    Sin cantidades (eso lo lleva Existencias) — solo para localizar a ojo. Los huecos vacíos llevan
    líneas en blanco: apunta ahí a mano lo que vayas guardando y no hace falta reimprimir esto cada vez.
</p>

<h6 class="fw-semibold mb-2">Por estuche</h6>
<?php if (empty($porEstuche)): ?>
    <p class="text-muted small">Todavía no hay estuches.</p>
<?php else: ?>
    <?php foreach ($porEstuche as $f): ?>
        <?php $estuche = $f['estuche']; ?>
        <div class="estuche-imprimir">
            <div class="titulo">
                <i class="bi bi-archive"></i> <?= esc($estuche['codigo']) ?>
                <?php if ($estuche['zona']): ?>
                    <span class="text-muted fw-normal small"> — <?= esc($estuche['zona']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (empty($f['huecos'])): ?>
                <p class="text-muted small mb-0">Sin huecos todavía.</p>
            <?php else: ?>
                <div class="hueco-imprimir-grid">
                    <?php foreach ($f['huecos'] as $h): ?>
                        <div class="hueco-imprimir">
                            <div class="codigo"><?= esc($estuche['codigo'] . $h['codigo']) ?></div>
                            <?php if ($h['piezas'] !== []): ?>
                                <ul>
                                    <?php foreach ($h['piezas'] as $pz): ?>
                                        <li>
                                            <?php if ($pz['img']): ?>
                                                <img src="<?= esc($pz['img'], 'attr') ?>" alt="" class="miniatura-imprimir">
                                            <?php endif; ?>
                                            <span><?= esc($pz['nombre']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="linea-vacia"></div>
                                <div class="linea-vacia"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h6 class="fw-semibold mb-2 mt-4 salto-pagina">Por pieza</h6>
<?php if (empty($porPieza)): ?>
    <p class="text-muted small">Ninguna pieza tiene ubicación todavía.</p>
<?php else: ?>
    <table class="table table-sm table-borderless tabla-por-pieza mb-0">
        <thead>
            <tr class="text-muted border-bottom">
                <th></th>
                <th>Pieza</th>
                <th>Dónde está</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($porPieza as $p): ?>
                <tr class="border-bottom">
                    <td>
                        <?php if ($p['img']): ?>
                            <img src="<?= esc($p['img'], 'attr') ?>" alt="" class="miniatura-imprimir">
                        <?php endif; ?>
                    </td>
                    <td><?= esc($p['nombre']) ?></td>
                    <td>
                        <?= esc(implode(', ', $p['codigos'])) ?>
                        <?php if ($p['sinAsignar']): ?>
                            <?= $p['codigos'] !== [] ? ', ' : '' ?><span class="text-muted">sin asignar</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?= $this->endSection() ?>
