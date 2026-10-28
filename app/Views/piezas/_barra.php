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
            <?php // La nevera: piezas hechas pero incompletas o que no funcionan bien,
                  // aparcadas fuera del listado sin fecha de caducidad (a diferencia de
                  // la papelera). Mismo criterio de visibilidad que ella. ?>
            <?php if (!empty($neveraCount) || !$enIndex): ?>
                <li>
                    <a class="dropdown-item" href="<?= site_url('piezas/nevera') ?>">
                        <i class="bi bi-snow"></i> Nevera
                        <?php if (!empty($neveraCount)): ?><span class="badge text-bg-secondary"><?= (int) $neveraCount ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php // Un color por área, para reconocer cada botón sin leer el title. ?>
    <style>
        .pz-c { --pz-c: var(--bs-secondary-color); }
        .pz-c > i { color: var(--pz-c); }
        .pz-c:hover, .pz-c.active { z-index: 2; border-color: var(--pz-c); background: color-mix(in srgb, var(--pz-c) 14%, transparent); }
        .pz-diseno     { --pz-c: #f59f00; }
        .pz-imprimir   { --pz-c: #3d8bfd; }
        .pz-inventario { --pz-c: #20c997; }
        .pz-tpv        { --pz-c: #ef5b5b; }
        .pz-pedidos    { --pz-c: #a370f7; }
        .pz-ayuda      { --pz-c: #0dcaf0; }
    </style>

    <?php // Copia de seguridad: mismo destino que "Estadísticas > Backup" en el
          // desplegable, pero de un clic — es de las cosas que se hacen sin pensar,
          // no algo de uso ocasional que merezca estar escondido. ?>
    <a href="<?= site_url('piezas/estadisticas/backup') ?>" class="btn btn-sm btn-outline-secondary pz-c"
        title="Copia de seguridad: el .blend de referencia de cada pieza más su historial en texto. Sin STL ni fotos.">
        <i class="bi bi-download"></i>
    </a>

    <div class="btn-group">
        <a href="<?= site_url('piezas/pendientes') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-diseno<?= $activa('pendientes') ?>" title="Pendientes">
            <i class="bi bi-list-check"></i>
        </a>
        <?php // Mismo anexo que en Pedidos: vistazo rápido sin entrar a la pantalla completa. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary pz-c pz-diseno" data-bs-toggle="modal"
            data-bs-target="#modalPendientes" title="Ver pendientes de crear">
            <i class="bi bi-eye"></i>
        </button>
        <a href="<?= site_url('piezas/revisar') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-diseno<?= $activa('revisar') ?>" title="Revisar impresiones: marcar impresa / validar / descartar por lotes">
            <i class="bi bi-clipboard-check"></i>
        </a>
    </div>

    <div class="btn-group">
        <a href="<?= site_url('piezas/galeria') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-imprimir<?= $activa('galeria') ?>" title="Galería">
            <i class="bi bi-grid-3x3-gap"></i>
            <?php if (!empty($carritoCount)): ?>
                <span class="badge text-bg-primary"><?= (int) $carritoCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-imprimir<?= $activa('placas') ?>" title="Placas">
            <i class="bi bi-printer"></i>
        </a>
        <a href="<?= site_url('piezas/cuaderno') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-imprimir<?= $activa('cuaderno') ?>" title="Cuaderno: todas las pruebas, conclusiones y notas de las bitácoras de golpe">
            <i class="bi bi-journal-bookmark"></i>
        </a>
        <a href="<?= site_url('piezas/tiradas') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-imprimir<?= $activa('tiradas') ?>" title="Tiradas: plan de placas por días para una producción grande">
            <i class="bi bi-calendar-range"></i>
        </a>
        <?php // Calculadora: cuánto tarda una placa a partir de su número de capas. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary pz-c pz-imprimir" data-bs-toggle="modal" data-bs-target="#modalCalcTiempo" title="Calcular tiempo estimado por capas">
            <i class="bi bi-stopwatch"></i>
        </button>
    </div>

    <div class="btn-group">
        <a href="<?= site_url('piezas/existencias') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-inventario<?= $activa('existencias') ?>" title="Existencias: inventario de piezas producidas">
            <i class="bi bi-boxes"></i>
        </a>
        <a href="<?= site_url('piezas/ubicaciones') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-inventario<?= $activa('ubicaciones') ?>" title="Ubicaciones: dónde está guardada cada pieza">
            <i class="bi bi-geo-alt"></i>
        </a>
    </div>

    <div class="btn-group">
        <a href="<?= site_url('inventario') ?>" target="_blank" class="btn btn-sm btn-outline-secondary pz-c pz-tpv" title="TPV inventario: la vista de consulta del usuario de inventario (se abre en otra pestaña)">
            <i class="bi bi-shop"></i>
        </a>
        <a href="<?= site_url('piezas/guias') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-tpv<?= $activa('guias') ?>" title="Guías del TPV: asistentes para elegir pieza (p. ej. guía de brazos)">
            <i class="bi bi-signpost-split"></i>
        </a>
    </div>

    <div class="btn-group">
        <a href="<?= site_url('piezas/pedidos') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-pedidos<?= $activa('pedidos') ?>" title="Pedidos">
            <i class="bi bi-cart-check"></i>
        </a>
        <?php // Vistazo rápido al último pedido sin entrar al tablero — mismo
              // hueco que ya usan otros botones "..." de detalle suelto. ?>
        <button type="button" class="btn btn-sm btn-outline-secondary pz-c pz-pedidos" data-bs-toggle="modal"
            data-bs-target="#modalUltimoPedido" title="Ver el último pedido entrante">
            <i class="bi bi-eye"></i>
        </button>
    </div>

    <a href="<?= site_url('piezas/ayuda') ?>" class="btn btn-sm btn-outline-secondary pz-c pz-ayuda<?= $activa('ayuda') ?>" title="Ayuda: cómo funciona todo el módulo">
        <i class="bi bi-question-circle"></i>
    </a>
    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalFamilia" title="Pieza nueva">
        <i class="bi bi-plus-lg"></i>
    </button>
