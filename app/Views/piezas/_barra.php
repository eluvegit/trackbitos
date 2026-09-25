<?php
$ruta    = uri_string();
$enIndex = $ruta === 'piezas';
$carritoCount ??= count(array_unique((array) session('piezas_carrito')));
$activa  = static fn (string $s) => ($ruta === 'piezas/' . $s || str_starts_with($ruta, 'piezas/' . $s . '/')) ? ' active' : '';
?>
    <div class="dropdown ms-auto">
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php if (!$enIndex || !empty($familias)): ?>
                <?php // Colocar piezas es una tarea aparte de mirarlas: los selectores solo estorban el resto del tiempo. ?>
                <li>
                    <button type="button" class="dropdown-item" id="btnOrganizar">
                        <i class="bi bi-arrows-move"></i> Organizar
                    </button>
                </li>
            <?php endif; ?>
            <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCategorias">
                    <i class="bi bi-folder"></i> Categorías
                </button>
            </li>
            <li><a class="dropdown-item" href="<?= site_url('piezas/maquinas') ?>"><i class="bi bi-pc-display"></i> Máquinas</a></li>
            <li><a class="dropdown-item" href="<?= site_url('piezas/estadisticas') ?>"><i class="bi bi-hdd-stack"></i> Estadísticas</a></li>
            <?php // Checklist recordatorio que aparece antes de promocionar una variante. ?>
            <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalPautas">
                    <i class="bi bi-check2-square"></i> Pautas
                </button>
            </li>
            <?php // Precio por litro y densidad de la resina: con eso y el volumen con soportes de cada trozo sale el coste por pieza. ?>
            <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalResina">
                    <i class="bi bi-droplet"></i> Resina
                </button>
            </li>
            <?php // Solo aparece cuando hay algo dentro: no tiene sentido un enlace a una papelera vacía. ?>
            <?php if (!empty($papeleraCount) || !$enIndex): ?>
                <li>
                    <a class="dropdown-item" href="<?= site_url('piezas/papelera') ?>">
                        <i class="bi bi-trash"></i> Papelera
                        <?php if (!empty($papeleraCount)): ?><span class="badge text-bg-secondary"><?= (int) $papeleraCount ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php // Copia de seguridad: mismo destino que "Estadísticas > Backup" en el
          // desplegable, pero de un clic — es de las cosas que se hacen sin pensar,
          // no algo de uso ocasional que merezca estar escondido. ?>
    <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="btn btn-sm btn-outline-secondary"
        title="Copia de seguridad: el .blend de referencia de cada pieza más su historial en texto. Sin STL ni fotos.">
        <i class="bi bi-download"></i>
    </a>
    <div class="btn-group">
        <a href="<?= site_url('piezas/pendientes') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('pendientes') ?>" title="Pendientes">
            <i class="bi bi-list-check"></i>
        </a>
        <?php // Mismo anexo que en Pedidos: vistazo rápido sin entrar a la pantalla completa. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
            data-bs-target="#modalPendientes" title="Ver pendientes de crear">
            <i class="bi bi-eye"></i>
        </button>
    </div>
    <a href="<?= site_url('piezas/revisar') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('revisar') ?>" title="Revisar impresiones: marcar impresa / validar / descartar por lotes">
        <i class="bi bi-clipboard-check"></i>
    </a>
    <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('placas') ?>" title="Placas">
        <i class="bi bi-printer"></i>
    </a>
    <a href="<?= site_url('piezas/existencias') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('existencias') ?>" title="Existencias: inventario de piezas producidas">
        <i class="bi bi-boxes"></i>
    </a>
    <a href="<?= site_url('piezas/ubicaciones') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('ubicaciones') ?>" title="Ubicaciones: dónde está guardada cada pieza">
        <i class="bi bi-geo-alt"></i>
    </a>
    <div class="btn-group">
        <a href="<?= site_url('piezas/pedidos') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('pedidos') ?>" title="Pedidos">
            <i class="bi bi-cart-check"></i>
        </a>
        <?php // Vistazo rápido al último pedido sin entrar al tablero — mismo
              // hueco que ya usan otros botones "..." de detalle suelto. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
            data-bs-target="#modalUltimoPedido" title="Ver el último pedido entrante">
            <i class="bi bi-eye"></i>
        </button>
    </div>
    <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary<?= $activa('galeria') ?>" title="Galería">
        <i class="bi bi-grid-3x3-gap"></i>
        <?php if (!empty($carritoCount)): ?>
            <span class="badge text-bg-primary"><?= (int) $carritoCount ?></span>
        <?php endif; ?>
    </a>
    <?php // Calculadora: cuánto tarda una placa a partir de su número de capas. ?>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCalcTiempo" title="Calcular tiempo estimado por capas">
        <i class="bi bi-stopwatch"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalFamilia" title="Pieza nueva">
        <i class="bi bi-plus-lg"></i>
    </button>
