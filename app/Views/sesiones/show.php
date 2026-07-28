<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">

<?php
$s = $sesion;
$sesionId = (int) $s['id'];
$general = $moodboard_por_situacion['general'] ?? [];

$labelEstado = [
    'idea'          => 'Idea',
    'planificacion' => 'Planificación',
    'edicion'       => 'Edición',
    'subiendo'      => 'Subiendo',
    'completado'    => 'Completado',
];
$ordenEstados = array_keys($labelEstado);

// Claves de color al estilo admin-badge (is-warning/is-success/is-neutral)
// para el pill de entrega a la modelo.
$entregaClave = [
    'no_aplica' => 'neutral',
    'pendiente' => 'warning',
    'entregado' => 'success',
];
$labelEntrega = [
    'no_aplica' => 'No aplica',
    'pendiente' => 'Pendiente de entregar',
    'entregado' => 'Entregado',
];

$partes = ['foto' => ['icono' => 'bi-camera', 'nombre' => 'Fotografía'], 'video' => ['icono' => 'bi-camera-video', 'nombre' => 'Vídeo']];
$pausada = (int) $s['pausada'] === 1;

// Un color y un icono distintos por sección, para que la ficha se pueda
// escanear de un vistazo en vez de ser una columna de tarjetas idénticas.
$secInfo = [
    'notas'       => ['color' => 'primary',   'icono' => 'bi-journal-text',      'titulo' => 'Notas'],
    'briefing'    => ['color' => 'info',      'icono' => 'bi-file-text',         'titulo' => 'Briefing'],
    'moodboard'   => ['color' => 'warning',   'icono' => 'bi-images',            'titulo' => 'Moodboard'],
    'situaciones' => ['color' => 'danger',    'icono' => 'bi-collection',        'titulo' => 'Situaciones'],
    'equipo'      => ['color' => 'secondary', 'icono' => 'bi-bag-check',         'titulo' => 'Equipo'],
    'releases'    => ['color' => 'success',   'icono' => 'bi-file-earmark-text', 'titulo' => 'Model releases'],
];

// Todas las secciones arrancan plegadas; solo el seguimiento (línea de
// vida) queda siempre visible.
$secciones = array_fill_keys(array_keys($secInfo), false);
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-camera text-primary"></i>
    <a href="<?= site_url('sesiones') ?>" class="text-decoration-none text-muted fw-normal">Sesiones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($s['titulo']) ?></strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4" id="sesionHeader" data-id="<?= $sesionId ?>">
    <div>
        <h2 class="fw-bold mb-1"><?= esc($s['titulo']) ?></h2>
        <?php if ($s['fecha_sesion']): ?>
            <div class="text-muted"><i class="bi bi-calendar-event"></i> <?= esc($s['fecha_sesion']) ?></div>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= site_url('sesiones/' . $sesionId . '/editar') ?>" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/borrar') ?>"
              onsubmit="return confirm('¿Borrar esta sesión? Se perderá todo su contenido (moodboard, equipo, model releases, historial). No se puede deshacer.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger rounded-circle ses-btn-icon" title="Borrar sesión"><i class="bi bi-trash"></i></button>
        </form>
    </div>
</div>

<div class="admin-panel mb-3">
    <div class="admin-panel-header">
        <span>Seguimiento de la sesión</span>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span id="pausadaBadge" class="admin-badge is-<?= $pausada ? 'warning' : 'success' ?>">
                <?= $pausada ? 'Pausada' : 'En marcha' ?>
            </span>
            <button type="button" id="btnPausada" class="btn btn-sm admin-btn-ghost"
                    title="<?= $pausada ? 'Pulsa para reanudar' : 'Pulsa para pausar' ?>">
                <i class="bi <?= $pausada ? 'bi-play-fill' : 'bi-pause-fill' ?> me-1"></i><span id="btnPausadaTexto"><?= $pausada ? 'Reanudar' : 'Pausar' ?></span>
            </button>

            <div class="dropdown entrega-dropdown">
                <button type="button" class="btn btn-sm admin-badge-btn is-<?= $entregaClave[$s['entrega_modelos']] ?> dropdown-toggle" data-bs-toggle="dropdown">
                    <?= $labelEntrega[$s['entrega_modelos']] ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($labelEntrega as $val => $label): ?>
                        <li><a class="dropdown-item entrega-opcion" href="#" data-valor="<?= $val ?>"><?= $label ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="p-3">
        <?php foreach ($partes as $parte => $info): ?>
            <?php $estadoActual = $s['estado_' . $parte]; ?>
            <?php if ($estadoActual === null): continue; endif; ?>
            <?php $indiceActual = array_search($estadoActual, $ordenEstados, true); ?>
            <div class="admin-timeline-bloque">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="admin-timeline-parte"><i class="bi <?= $info['icono'] ?>"></i> <?= esc($info['nombre']) ?></div>
                    <div class="dropdown estado-dropdown" data-parte="<?= $parte ?>">
                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                            Cambiar etapa
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($labelEstado as $val => $label): ?>
                                <li><a class="dropdown-item estado-opcion" href="#" data-estado="<?= $val ?>"><?= $label ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="admin-timeline">
                    <?php foreach ($ordenEstados as $i => $estado): ?>
                        <div class="admin-timeline-step <?= $i < $indiceActual ? 'is-done' : ($i === $indiceActual ? 'is-current' : '') ?>" data-estado="<?= $estado ?>">
                            <div class="admin-timeline-dot">
                                <?php if ($i < $indiceActual): ?>
                                    <i class="bi bi-check-lg"></i>
                                <?php else: ?>
                                    <?= $i + 1 ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="admin-timeline-label"><?= $labelEstado[$estado] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($secciones as $clave => $abierta): ?>
    <?php $info = $secInfo[$clave]; ?>
    <div class="ses-card mb-3">
        <button class="ses-card-header <?= $abierta ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccion-<?= $clave ?>">
            <span class="ses-icon-chip bg-<?= $info['color'] ?>-subtle text-<?= $info['color'] ?>-emphasis"><i class="bi <?= $info['icono'] ?>"></i></span>
            <span class="ses-card-header-titulo"><?= $info['titulo'] ?></span>
            <i class="bi bi-chevron-down ses-chevron"></i>
        </button>
        <div class="collapse <?= $abierta ? 'show' : '' ?>" id="seccion-<?= $clave ?>">
            <div class="ses-card-body">
                <?php if ($clave === 'notas'): ?>
                    <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/actualizar') ?>" class="campo-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="titulo" value="<?= esc($s['titulo']) ?>">
                        <input type="hidden" name="fecha_sesion" value="<?= esc($s['fecha_sesion'] ?? '') ?>">
                        <textarea name="notas" class="form-control mb-2" rows="4"><?= esc($s['notas'] ?? '') ?></textarea>
                        <button type="submit" class="btn btn-outline-primary rounded-pill">Guardar notas</button>
                    </form>

                <?php elseif ($clave === 'briefing'): ?>
                    <?php
                    // El briefing se guarda como HTML (editor Quill): se
                    // arranca envolviendo el texto plano previo en <p> por
                    // línea para no perder los saltos ya guardados.
                    $briefingHtml = trim((string) ($s['briefing'] ?? ''));
                    if ($briefingHtml !== '' && strip_tags($briefingHtml) === $briefingHtml) {
                        $briefingHtml = implode('', array_map(
                            static fn ($linea) => '<p>' . esc($linea) . '</p>',
                            preg_split('/\r\n|\r|\n/', $briefingHtml)
                        ));
                    }
                    ?>
                    <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/actualizar') ?>" class="campo-form" id="briefingForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="titulo" value="<?= esc($s['titulo']) ?>">
                        <input type="hidden" name="fecha_sesion" value="<?= esc($s['fecha_sesion'] ?? '') ?>">
                        <div id="briefingEditor" class="mb-2"><?= $briefingHtml ?></div>
                        <textarea name="briefing" id="briefingHidden" class="d-none"></textarea>
                        <div class="form-text mb-2">Se incluye en el informe exportable, por ejemplo para pasárselo a la modelo.</div>
                        <button type="submit" class="btn btn-outline-primary rounded-pill">Guardar briefing</button>
                    </form>

                <?php elseif ($clave === 'moodboard'): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <a href="<?= site_url('sesiones/' . $sesionId . '/exportar') ?>" class="btn btn-outline-secondary rounded-pill btn-sm" target="_blank">
                            <i class="bi bi-printer"></i> Exportar todo
                        </a>
                    </div>
                    <div class="gallery-grid moodboard-grid mb-2" id="moodboardGeneral" data-situacion-id="">
                        <?php foreach ($general as $item): ?>
                            <?= view('sesiones/_moodboard_item', ['item' => $item, 'situaciones' => $situaciones]) ?>
                        <?php endforeach; ?>
                    </div>
                    <?= view('sesiones/_moodboard_form', ['sesionId' => $sesionId, 'situacionId' => null]) ?>

                <?php elseif ($clave === 'situaciones'): ?>
                    <div class="accordion" id="situacionesAccordion">
                        <?php foreach ($situaciones as $sit): ?>
                            <?php $items = $moodboard_por_situacion[$sit['id']] ?? []; ?>
                            <div class="accordion-item situacion-item" data-id="<?= (int) $sit['id'] ?>">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#situacion<?= $sit['id'] ?>">
                                        <?= esc($sit['nombre']) ?>
                                        <span class="badge bg-secondary ms-2 situacion-count"><?= count($items) ?></span>
                                    </button>
                                </h2>
                                <div id="situacion<?= $sit['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#situacionesAccordion">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-end mb-2">
                                            <a href="<?= site_url('sesiones/' . $sesionId . '/situaciones/' . $sit['id'] . '/exportar') ?>" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                                                <i class="bi bi-printer"></i> Exportar
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger situacion-borrar"><i class="bi bi-trash"></i> Borrar situación</button>
                                        </div>
                                        <div class="gallery-grid moodboard-grid mb-2" data-situacion-id="<?= (int) $sit['id'] ?>">
                                            <?php foreach ($items as $item): ?>
                                                <?= view('sesiones/_moodboard_item', ['item' => $item, 'situaciones' => $situaciones]) ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?= view('sesiones/_moodboard_form', ['sesionId' => $sesionId, 'situacionId' => $sit['id']]) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form id="situacionForm" class="d-flex gap-2 mt-3">
                        <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nueva situación..." required>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></button>
                    </form>

                <?php elseif ($clave === 'equipo'): ?>
                    <ul class="list-unstyled equipo-list" id="equipoList">
                        <?php foreach ($equipo as $item): ?>
                            <li class="equipo-item d-flex align-items-center gap-2 mb-2" data-id="<?= (int) $item['id'] ?>">
                                <input type="checkbox" class="form-check-input equipo-check" <?= (int) $item['marcado'] === 1 ? 'checked' : '' ?>>
                                <span class="equipo-nombre flex-grow-1 <?= (int) $item['marcado'] === 1 ? 'text-decoration-line-through text-muted' : '' ?>"><?= esc($item['item']) ?></span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 equipo-borrar"><i class="bi bi-x-lg"></i></button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <form id="equipoForm" class="d-flex gap-2 mt-2">
                        <input type="text" name="item" class="form-control form-control-sm" placeholder="Añadir ítem..." required>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></button>
                    </form>

                <?php elseif ($clave === 'releases'): ?>
                    <ul class="list-unstyled releases-list" id="releasesList">
                        <?php foreach ($model_releases as $r): ?>
                            <li class="d-flex align-items-center gap-2 mb-2" data-id="<?= (int) $r['id'] ?>">
                                <a href="<?= base_url($r['ruta_archivo']) ?>" target="_blank" rel="noopener" class="flex-grow-1"><?= esc($r['nombre_modelo']) ?></a>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 release-borrar"><i class="bi bi-x-lg"></i></button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <form id="releaseForm" class="d-flex flex-column gap-2 mt-2" enctype="multipart/form-data">
                        <input type="text" name="nombre_modelo" class="form-control form-control-sm" placeholder="Nombre del modelo" required>
                        <input type="file" name="archivo" class="form-control form-control-sm" accept="image/*,application/pdf" required>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-upload"></i> Subir</button>
                    </form>

                    <hr>

                    <h6 class="fw-bold mb-2"><i class="bi bi-chat-left-text me-1"></i>Mensajes a modelos</h6>
                    <ul class="list-unstyled mensajes-list mb-3" id="mensajesList">
                        <?php foreach ($mensajes_modelo as $m): ?>
                            <li class="mensaje-item mb-2 p-2" data-id="<?= (int) $m['id'] ?>">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong><?= esc($m['nombre_modelo']) ?></strong>
                                        <span class="text-muted small ms-1"><?= esc(date('d/m/Y', strtotime($m['creado_at']))) ?></span>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-link p-0 mensaje-copiar" title="Copiar" data-mensaje="<?= esc($m['mensaje'], 'attr') ?>"><i class="bi bi-clipboard"></i></button>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 mensaje-borrar" title="Borrar"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                                <div class="mensaje-texto small text-muted"><?= nl2br(esc($m['mensaje'])) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted small mensajes-vacio" <?= empty($mensajes_modelo) ? '' : 'style="display:none"' ?>>Todavía no hay mensajes guardados.</p>

                    <form id="mensajeForm" class="d-flex flex-column gap-2">
                        <input type="text" name="nombre_modelo" class="form-control form-control-sm" placeholder="Nombre del modelo/dueño" required list="modelosDatalist">
                        <datalist id="modelosDatalist">
                            <?php foreach ($model_releases as $r): ?>
                                <option value="<?= esc($r['nombre_modelo']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <textarea name="mensaje" class="form-control" rows="5" placeholder="Mensaje completo (enlaces de referencia, horario, ubicación...)" required></textarea>
                        <button type="submit" class="btn btn-sm btn-primary align-self-start"><i class="bi bi-plus-lg"></i> Guardar mensaje</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
.ses-card {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 1.25rem;
    box-shadow: 0 10px 30px -12px rgba(0, 0, 0, .45);
    overflow: hidden;
}
.ses-card-body {
    padding: 1.5rem;
}
.ses-btn-icon {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.ses-card-header {
    width: 100%;
    background: transparent;
    border: none;
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: 1rem 1.25rem;
    text-align: left;
}
.ses-icon-chip {
    width: 38px;
    height: 38px;
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.ses-card-header-titulo {
    font-weight: 700;
    font-size: 1.05rem;
    flex-grow: 1;
}
.ses-chevron {
    color: var(--bs-secondary-color);
    transition: transform .2s;
}
.ses-card-header.collapsed .ses-chevron {
    transform: rotate(-90deg);
}

/* ---- "Seguimiento de la sesión": misma estructura que el panel de
   pedidos de sterclicks (app/Views/admin/orders/view.php +
   public/assets/css/admin.css), pero con la paleta oscura de trackbitos
   en vez de la suya (clara), para que encaje con el resto de la página. ---- */
.admin-panel {
    background: var(--bs-tertiary-bg);
    color: var(--bs-body-color);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    box-shadow: 0 10px 30px -12px rgba(0, 0, 0, .45);
    overflow: hidden;
}
.admin-panel-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--bs-border-color);
    font-weight: 700;
    color: var(--bs-body-color);
}
.admin-btn-ghost {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    color: var(--bs-body-color);
    font-weight: 700;
    border-radius: 8px;
}
.admin-btn-ghost:hover {
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    border-color: var(--bs-border-color);
}
.admin-badge,
.admin-badge-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.admin-badge-btn {
    border: none;
}
.admin-badge.is-success, .admin-badge-btn.is-success { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
.admin-badge.is-warning, .admin-badge-btn.is-warning { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
.admin-badge.is-neutral, .admin-badge-btn.is-neutral { background: var(--bs-secondary-bg-subtle); color: var(--bs-secondary-text-emphasis); }

.admin-timeline-bloque {
    margin-top: 1.5rem;
}
.admin-timeline-bloque:first-of-type {
    margin-top: 0;
}
.admin-timeline-parte {
    font-weight: 700;
    color: var(--bs-body-color);
}
.admin-timeline {
    display: flex;
    justify-content: space-between;
    margin: 6px 0 8px;
    position: relative;
}
.admin-timeline::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 20px;
    right: 20px;
    height: 2px;
    background: var(--bs-border-color);
    z-index: 0;
}
.admin-timeline-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    z-index: 1;
    gap: 6px;
}
.admin-timeline-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bs-body-bg);
    border: 2px solid var(--bs-border-color);
    color: var(--bs-secondary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}
.admin-timeline-step.is-done .admin-timeline-dot {
    background: var(--bs-danger);
    border-color: var(--bs-danger);
    color: #fff;
}
.admin-timeline-step.is-current .admin-timeline-dot {
    border-color: var(--bs-danger);
    color: var(--bs-danger);
    box-shadow: 0 0 0 4px rgba(var(--bs-danger-rgb), .2);
}
.admin-timeline-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--bs-body-color);
}
.admin-timeline-step:not(.is-done):not(.is-current) .admin-timeline-label {
    color: var(--bs-secondary-color);
}
@media (max-width: 640px) {
    /* En vertical: puntos alineados a la izquierda con una línea que los
       conecta y la etiqueta a la derecha de cada uno — un stepper
       vertical normal, en vez de una columna de círculos sueltos sin
       nada que los conecte. */
    .admin-timeline {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
    }
    .admin-timeline::before {
        top: 20px;
        bottom: 20px;
        left: 15px;
        right: auto;
        width: 2px;
        height: auto;
    }
    .admin-timeline-step {
        flex-direction: row;
        align-items: center;
        text-align: left;
        gap: 12px;
        padding: 10px 0;
    }
    .admin-timeline-label {
        text-align: left;
    }
}

/* Más aire en los menús desplegables de esta ficha (Cambiar etapa /
   Entrega a la modelo) — por defecto Bootstrap los deja muy apretados. */
.estado-dropdown .dropdown-menu,
.entrega-dropdown .dropdown-menu {
    padding: .5rem;
}
.estado-dropdown .dropdown-item,
.entrega-dropdown .dropdown-item {
    padding: .5rem .75rem;
    border-radius: .5rem;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}
.gallery-item {
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bs-tertiary-bg);
    display: block;
    border: 1px solid var(--bs-border-color);
    position: relative;
}
.gallery-item img { width: 100%; height: 100%; object-fit: contain; background: rgba(0, 0, 0, .15); }
.gallery-item .item-borrar {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0,0,0,.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
}
.gallery-item .item-vincular-dropdown {
    position: absolute;
    top: 4px;
    left: 4px;
}
.gallery-item .item-vincular {
    background: rgba(0,0,0,.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
}
.moodboard-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 8px; }

.mensaje-item {
    background: var(--bs-primary-bg-subtle);
    color: var(--bs-primary-text-emphasis);
    border: none;
    border-radius: 16px 16px 16px 4px;
}
.mensaje-item .text-muted {
    color: var(--bs-primary-text-emphasis) !important;
    opacity: .7;
}
.mensaje-texto {
    white-space: pre-wrap;
    margin-top: 4px;
}

.campo-guardado {
    color: var(--bs-success);
}

/* Editor de briefing (Quill) con la paleta oscura de trackbitos */
#briefingEditor {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-top: none;
    border-radius: 0 0 .375rem .375rem;
    color: var(--bs-body-color);
    min-height: 160px;
}
.ql-toolbar.ql-snow {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: .375rem .375rem 0 0;
}
.ql-snow .ql-stroke { stroke: var(--bs-secondary-color); }
.ql-snow .ql-fill { fill: var(--bs-secondary-color); }
.ql-snow .ql-picker { color: var(--bs-secondary-color); }
.ql-snow .ql-picker-options { background: var(--bs-tertiary-bg); }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(() => {
    const sesionId = <?= $sesionId ?>;
    const base = '<?= site_url('sesiones') ?>/' + sesionId;
    const csrf = '<?= csrf_hash() ?>';
    // Lista viva de situaciones (id + nombre) para construir el menú
    // "Vincular a situación" de cada foto, incluidas las creadas sin
    // recargar la página.
    const situacionesConocidas = <?= json_encode(array_map(
        static fn ($sit) => ['id' => (int) $sit['id'], 'nombre' => $sit['nombre']],
        $situaciones
    )) ?>;

    async function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    }

    async function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: formData,
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/[<>&"]/g, c => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;' }[c]));
    }

    // Muestra "Guardado ✓" un instante en el botón de un mini-formulario,
    // en vez de recargar la página.
    function marcarGuardado(btn) {
        const original = btn.textContent;
        btn.textContent = 'Guardado ✓';
        btn.classList.add('campo-guardado');
        setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('campo-guardado');
        }, 1500);
    }

    // ---- Cambiar etapa (foto / vídeo, independientes) ----
    function actualizarTimeline(timeline, nuevoEstado) {
        const pasos = Array.from(timeline.querySelectorAll('.admin-timeline-step'));
        const nuevoIndex = pasos.findIndex(p => p.dataset.estado === nuevoEstado);

        pasos.forEach((paso, i) => {
            const dot = paso.querySelector('.admin-timeline-dot');

            paso.classList.remove('is-done', 'is-current');

            if (i === nuevoIndex) {
                paso.classList.add('is-current');
                dot.textContent = String(i + 1);
                return;
            }

            if (i < nuevoIndex) {
                paso.classList.add('is-done');
                dot.innerHTML = '<i class="bi bi-check-lg"></i>';
            } else {
                dot.textContent = String(i + 1);
            }
        });
    }

    document.querySelectorAll('.estado-dropdown').forEach(dd => {
        const parte = dd.dataset.parte;
        const timeline = dd.closest('.admin-timeline-bloque').querySelector('.admin-timeline');
        dd.querySelectorAll('.estado-opcion').forEach(opt => {
            opt.addEventListener('click', async (e) => {
                e.preventDefault();
                const res = await post(`${base}/estado`, { parte, estado: opt.dataset.estado });
                if (!res.ok) { console.error('No se pudo cambiar el estado'); return; }
                const data = await res.json();
                actualizarTimeline(timeline, data.estado);
            });
        });
    });

    // ---- Pausada (play/pausa) ----
    const btnPausada = document.getElementById('btnPausada');
    const pausadaBadge = document.getElementById('pausadaBadge');
    btnPausada.addEventListener('click', async () => {
        const res = await post(`${base}/toggle-pausada`);
        if (!res.ok) { console.error('No se pudo cambiar pausada'); return; }
        const data = await res.json();
        const icono = btnPausada.querySelector('i');
        icono.classList.toggle('bi-play-fill', data.valor);
        icono.classList.toggle('bi-pause-fill', !data.valor);
        document.getElementById('btnPausadaTexto').textContent = data.valor ? 'Reanudar' : 'Pausar';
        btnPausada.title = data.valor ? 'Pulsa para reanudar' : 'Pulsa para pausar';
        pausadaBadge.textContent = data.valor ? 'Pausada' : 'En marcha';
        pausadaBadge.classList.toggle('is-warning', data.valor);
        pausadaBadge.classList.toggle('is-success', !data.valor);
    });

    // ---- Entrega a la modelo ----
    document.querySelectorAll('.entrega-opcion').forEach(opt => {
        opt.addEventListener('click', async (e) => {
            e.preventDefault();
            const valor = opt.dataset.valor;
            const res = await post(`${base}/entrega-modelos`, { valor });
            if (!res.ok) { console.error('No se pudo cambiar la entrega'); return; }
            const btn = document.querySelector('.entrega-dropdown .dropdown-toggle');
            btn.textContent = opt.textContent;
            btn.classList.remove('is-neutral', 'is-warning', 'is-success');
            const clave = { no_aplica: 'neutral', pendiente: 'warning', entregado: 'success' }[valor];
            btn.classList.add('is-' + clave);
        });
    });

    // ---- Notas / Briefing: guardar sin recargar ----
    // El editor Quill se inicializa aparte y al final del script (ver
    // abajo): depende de un script externo (CDN) y si ese script no carga
    // por lo que sea, no debe tirarse abajo el resto de listeners de esta
    // página (equipo, situaciones, moodboard...) que van después.
    let quill = null;

    document.querySelectorAll('.campo-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (form.id === 'briefingForm' && quill) {
                document.getElementById('briefingHidden').value = quill.root.innerHTML;
            }
            const btn = form.querySelector('button[type="submit"]');
            const res = await postForm(`${base}/actualizar`, new FormData(form));
            if (!res.ok) { console.error('No se pudo guardar'); return; }
            marcarGuardado(btn);
        });
    });

    // ---- Equipo ----
    const equipoList = document.getElementById('equipoList');

    equipoList.addEventListener('change', async (e) => {
        if (!e.target.classList.contains('equipo-check')) return;
        const li = e.target.closest('.equipo-item');
        const res = await post(`${base}/equipo/${li.dataset.id}/toggle`);
        if (!res.ok) { e.target.checked = !e.target.checked; return; }
        const data = await res.json();
        li.querySelector('.equipo-nombre').classList.toggle('text-decoration-line-through', data.marcado);
        li.querySelector('.equipo-nombre').classList.toggle('text-muted', data.marcado);
    });

    equipoList.addEventListener('click', async (e) => {
        const btn = e.target.closest('.equipo-borrar');
        if (!btn) return;
        const li = btn.closest('.equipo-item');
        const res = await post(`${base}/equipo/${li.dataset.id}/borrar`);
        if (res.ok) li.remove();
    });

    document.getElementById('equipoForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = e.target.item;
        const nombre = input.value.trim();
        if (!nombre) return;
        const res = await post(`${base}/equipo/agregar`, { item: nombre });
        if (!res.ok) return;
        const data = await res.json();
        const li = document.createElement('li');
        li.className = 'equipo-item d-flex align-items-center gap-2 mb-2';
        li.dataset.id = data.item.id;
        li.innerHTML = `<input type="checkbox" class="form-check-input equipo-check">
            <span class="equipo-nombre flex-grow-1">${escapeHtml(data.item.item)}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 equipo-borrar"><i class="bi bi-x-lg"></i></button>`;
        equipoList.appendChild(li);
        input.value = '';
    });

    // ---- Situaciones ----
    function crearMoodboardForm(sesionId, situacionId) {
        const sufijo = situacionId ?? 'general';
        const ocultoArchivo = situacionId !== null ? `<input type="hidden" name="situacion_id" value="${situacionId}">` : '';
        const div = document.createElement('div');
        div.className = 'd-flex flex-wrap gap-3 mt-2';
        div.innerHTML = `
            <form class="moodboard-form d-flex align-items-center gap-2" data-origen="archivo" enctype="multipart/form-data">
                ${ocultoArchivo}
                <input type="file" name="archivo" accept="image/*" multiple class="form-control form-control-sm" required id="archivo-${sufijo}">
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i></button>
            </form>
            <form class="moodboard-form d-flex align-items-center gap-2" data-origen="enlace">
                ${ocultoArchivo}
                <input type="url" name="url_externa" placeholder="https://..." class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-link-45deg"></i></button>
            </form>`;
        return div;
    }

    document.getElementById('situacionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = e.target.nombre;
        const nombre = input.value.trim();
        if (!nombre) return;
        const res = await post(`${base}/situaciones/crear`, { nombre });
        if (!res.ok) return;
        const data = await res.json();
        const sit = data.situacion;
        situacionesConocidas.push({ id: sit.id, nombre: sit.nombre });

        const item = document.createElement('div');
        item.className = 'accordion-item situacion-item';
        item.dataset.id = sit.id;
        item.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#situacion${sit.id}">
                    ${escapeHtml(sit.nombre)}
                    <span class="badge bg-secondary ms-2 situacion-count">0</span>
                </button>
            </h2>
            <div id="situacion${sit.id}" class="accordion-collapse collapse show" data-bs-parent="#situacionesAccordion">
                <div class="accordion-body">
                    <div class="d-flex justify-content-end mb-2">
                        <a href="${base}/situaciones/${sit.id}/exportar" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                            <i class="bi bi-printer"></i> Exportar
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger situacion-borrar"><i class="bi bi-trash"></i> Borrar situación</button>
                    </div>
                    <div class="gallery-grid moodboard-grid mb-2" data-situacion-id="${sit.id}"></div>
                </div>
            </div>`;
        item.querySelector('.accordion-body').appendChild(crearMoodboardForm(sesionId, sit.id));

        document.getElementById('situacionesAccordion').appendChild(item);
        input.value = '';
    });

    document.getElementById('situacionesAccordion').addEventListener('click', async (e) => {
        const btn = e.target.closest('.situacion-borrar');
        if (!btn) return;
        if (!confirm('¿Borrar esta situación? Su moodboard quedará como general.')) return;
        const item = btn.closest('.situacion-item');
        const res = await post(`${base}/situaciones/${item.dataset.id}/borrar`);
        if (!res.ok) return;

        const idx = situacionesConocidas.findIndex(s => String(s.id) === item.dataset.id);
        if (idx !== -1) situacionesConocidas.splice(idx, 1);

        // El moodboard de la situación borrada pasa a "general" en servidor;
        // reflejamos lo mismo aquí moviendo sus fotos al grid general.
        const general = document.getElementById('moodboardGeneral');
        item.querySelectorAll('.gallery-item').forEach(el => {
            el.dataset.situacionId = '';
            general.appendChild(el);
        });
        item.remove();
    });

    // ---- Moodboard (archivo / enlace / borrar / vincular) — delegado,
    // también sirve para situaciones creadas dinámicamente ----
    function opcionesVincular() {
        const opciones = [`<li><a class="dropdown-item item-vincular-opcion" href="#" data-situacion-id="">General</a></li>`];
        situacionesConocidas.forEach(sit => {
            opciones.push(`<li><a class="dropdown-item item-vincular-opcion" href="#" data-situacion-id="${sit.id}">${escapeHtml(sit.nombre)}</a></li>`);
        });
        return opciones.join('');
    }

    function crearGalleryItem(item, situacionId) {
        const esArchivo = item.origen === 'archivo';
        const src = esArchivo ? '<?= base_url() ?>' + item.ruta_archivo : item.url_externa;
        const div = document.createElement('div');
        div.className = 'gallery-item';
        div.dataset.id = item.id;
        div.dataset.situacionId = situacionId ?? '';
        if (item.nota) div.title = item.nota;
        div.innerHTML = `
            <a href="${escapeHtml(src)}" target="_blank" rel="noopener">
                ${esArchivo
                    ? `<img src="${escapeHtml(src)}" alt="Referencia moodboard" loading="lazy">`
                    : `<div class="d-flex align-items-center justify-content-center h-100 text-center p-2">
                        <span><i class="bi bi-link-45deg d-block fs-3"></i><small class="text-muted text-break">${escapeHtml(item.url_externa)}</small></span>
                       </div>`}
            </a>
            <button type="button" class="item-borrar" title="Borrar"><i class="bi bi-x"></i></button>
            <div class="dropdown item-vincular-dropdown">
                <button type="button" class="item-vincular" title="Vincular a situación" data-bs-toggle="dropdown"><i class="bi bi-link-45deg"></i></button>
                <ul class="dropdown-menu">${opcionesVincular()}</ul>
            </div>`;
        return div;
    }

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.moodboard-form');
        if (!form) return;
        e.preventDefault();

        const destino = form.closest('.accordion-body')?.querySelector('.gallery-grid')
            || document.getElementById('moodboardGeneral');

        let res;
        if (form.dataset.origen === 'archivo') {
            res = await postForm(`${base}/moodboard/subir`, new FormData(form));
        } else {
            res = await post(`${base}/moodboard/enlace`, Object.fromEntries(new FormData(form).entries()));
        }

        if (!res.ok) { console.error('No se pudo añadir al moodboard'); return; }
        const data = await res.json();
        const items = data.items || [data.item];
        const situacionId = destino.dataset.situacionId || null;
        items.forEach(item => destino.appendChild(crearGalleryItem(item, situacionId)));

        const situacionItem = form.closest('.situacion-item');
        if (situacionItem) {
            const contador = situacionItem.querySelector('.situacion-count');
            contador.textContent = String(parseInt(contador.textContent, 10) + items.length);
        }

        form.reset();
    });

    document.addEventListener('click', async (e) => {
        const borrarBtn = e.target.closest('.item-borrar');
        if (borrarBtn) {
            const el = borrarBtn.closest('.gallery-item');
            const situacionItem = el.closest('.situacion-item');
            const res = await post(`${base}/moodboard/${el.dataset.id}/borrar`);
            if (!res.ok) return;
            el.remove();
            if (situacionItem) {
                const contador = situacionItem.querySelector('.situacion-count');
                contador.textContent = String(Math.max(0, parseInt(contador.textContent, 10) - 1));
            }
            return;
        }

        const vincularOpcion = e.target.closest('.item-vincular-opcion');
        if (vincularOpcion) {
            e.preventDefault();
            const el = vincularOpcion.closest('.gallery-item');
            const nuevaSituacionId = vincularOpcion.dataset.situacionId || '';
            const res = await post(`${base}/moodboard/${el.dataset.id}/vincular`, { situacion_id: nuevaSituacionId || null });
            if (!res.ok) { console.error('No se pudo vincular la foto'); return; }

            const origenSituacionItem = el.closest('.situacion-item');
            const destino = nuevaSituacionId
                ? document.querySelector(`.gallery-grid[data-situacion-id="${nuevaSituacionId}"]`)
                : document.getElementById('moodboardGeneral');
            if (!destino) return;

            destino.appendChild(el);
            el.dataset.situacionId = nuevaSituacionId;

            if (origenSituacionItem) {
                const contador = origenSituacionItem.querySelector('.situacion-count');
                contador.textContent = String(Math.max(0, parseInt(contador.textContent, 10) - 1));
            }
            const destinoSituacionItem = destino.closest('.situacion-item');
            if (destinoSituacionItem) {
                const contador = destinoSituacionItem.querySelector('.situacion-count');
                contador.textContent = String(parseInt(contador.textContent, 10) + 1);
            }
        }
    });

    // ---- Model releases ----
    document.getElementById('releaseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await postForm(`${base}/releases/subir`, new FormData(e.target));
        if (!res.ok) { console.error('No se pudo subir el release'); return; }
        const data = await res.json();
        const li = document.createElement('li');
        li.className = 'd-flex align-items-center gap-2 mb-2';
        li.dataset.id = data.release.id;
        li.innerHTML = `
            <a href="<?= base_url() ?>${data.release.ruta_archivo}" target="_blank" rel="noopener" class="flex-grow-1">${escapeHtml(data.release.nombre_modelo)}</a>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 release-borrar"><i class="bi bi-x-lg"></i></button>`;
        document.getElementById('releasesList').appendChild(li);

        // Sugerencia de autocompletado para el nombre en el formulario de mensajes
        const option = document.createElement('option');
        option.value = data.release.nombre_modelo;
        document.getElementById('modelosDatalist').appendChild(option);

        e.target.reset();
    });

    document.getElementById('releasesList').addEventListener('click', async (e) => {
        const btn = e.target.closest('.release-borrar');
        if (!btn) return;
        if (!confirm('¿Borrar este model release? Se eliminará también el archivo subido. No se puede deshacer.')) return;
        const li = btn.closest('li');
        const res = await post(`${base}/releases/${li.dataset.id}/borrar`);
        if (res.ok) li.remove();
    });

    // ---- Mensajes a modelos ----
    const mensajesList = document.getElementById('mensajesList');
    const mensajesVacio = document.querySelector('.mensajes-vacio');

    function crearMensajeItem(m) {
        const li = document.createElement('li');
        li.className = 'mensaje-item mb-2 p-2';
        li.dataset.id = m.id;
        li.innerHTML = `
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <strong>${escapeHtml(m.nombre_modelo)}</strong>
                    <span class="text-muted small ms-1">${new Date().toLocaleDateString('es-ES')}</span>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-link p-0 mensaje-copiar" title="Copiar" data-mensaje="${escapeHtml(m.mensaje)}"><i class="bi bi-clipboard"></i></button>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 mensaje-borrar" title="Borrar"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="mensaje-texto small text-muted">${escapeHtml(m.mensaje).replace(/\n/g, '<br>')}</div>`;
        return li;
    }

    document.getElementById('mensajeForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const res = await post(`${base}/mensajes/crear`, Object.fromEntries(new FormData(form).entries()));
        if (!res.ok) { console.error('No se pudo guardar el mensaje'); return; }
        const data = await res.json();
        if (mensajesVacio) mensajesVacio.style.display = 'none';
        mensajesList.insertBefore(crearMensajeItem(data.item), mensajesList.firstChild);
        form.reset();
    });

    mensajesList.addEventListener('click', async (e) => {
        const copiar = e.target.closest('.mensaje-copiar');
        if (copiar) {
            try {
                await navigator.clipboard.writeText(copiar.dataset.mensaje);
            } catch (err) {
                console.error('No se pudo copiar', err);
            }
            return;
        }

        const borrar = e.target.closest('.mensaje-borrar');
        if (!borrar) return;
        const li = borrar.closest('.mensaje-item');
        const res = await post(`${base}/mensajes/${li.dataset.id}/borrar`);
        if (res.ok) {
            li.remove();
            if (!mensajesList.querySelector('.mensaje-item') && mensajesVacio) {
                mensajesVacio.style.display = '';
            }
        }
    });

    // ---- Editor de briefing (Quill) ----
    // Se inicializa el último y aislado en su propio try/catch: si el CDN
    // no carga, el resto de la página (ya registrada arriba) sigue
    // funcionando; el briefing simplemente se queda como texto plano.
    const briefingEditorEl = document.getElementById('briefingEditor');
    if (briefingEditorEl) {
        try {
            quill = new Quill('#briefingEditor', {
                theme: 'snow',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ header: [2, 3, false] }], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] },
            });
        } catch (err) {
            console.error('No se pudo cargar el editor de briefing', err);
        }
    }
})();
</script>

<?= $this->endSection() ?>
