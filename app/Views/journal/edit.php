<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$amplitude   = (int)($task['amplitude'] ?? 0);
$completed   = (int)($task['completed'] ?? 0);
$pct         = $amplitude > 0 ? min(100, (int)round($completed / $amplitude * 100)) : 0;
$subDone     = count(array_filter($subtasks, fn($s) => !empty($s['is_done'])));
$fmtDate     = fn($v) => (!empty($v) && $v !== '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($v)) : '';
$startDate   = $fmtDate($task['start_time'] ?? '');
$endDate     = $fmtDate($task['end_time'] ?? '');
$imgExts     = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif', 'svg'];
$subtaskTitleById = array_column($subtasks, 'title', 'id');
?>

<div class="jt-header mb-2">
    <a href="<?= site_url('journal') ?>" class="jt-back"><i class="bi bi-chevron-left"></i> Journal</a>
    <a href="<?= site_url('journal/edit/' . $task['id']) ?>?nueva=1" class="jt-back ms-3"><i class="bi bi-arrow-left-right"></i> Vista nueva</a>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<!-- Cabecera: imagen + título + resumen -->
<div class="jt-hero mb-3">
    <div class="jt-hero-img">
        <?php if (!empty($task['image'])): ?>
            <img src="<?= base_url($task['image']) ?>" alt="Imagen de la tarea">
            <form action="<?= site_url('journal/delete-image/' . $task['id']) ?>" method="post"
                  onsubmit="return confirm('¿Eliminar esta imagen?')" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="jt-image-remove" title="Eliminar imagen"><i class="bi bi-x-lg"></i></button>
            </form>
        <?php else: ?>
            <button type="button" class="jt-hero-img-empty js-pick-image" title="Añadir imagen"><i class="bi bi-image"></i></button>
        <?php endif; ?>
    </div>

    <div class="jt-hero-main">
        <div class="jt-title-row">
            <button type="button" class="jt-star" id="starBtn" aria-pressed="<?= !empty($task['is_current']) ? 'true' : 'false' ?>" title="Marcar como actual">
                <i class="bi <?= !empty($task['is_current']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
            </button>
            <input type="text" name="title" id="title" form="taskForm" class="jt-title-input"
                   value="<?= esc($task['title'] ?? '') ?>" placeholder="Título" required>
            <input type="checkbox" name="is_current" id="is_current" form="taskForm" value="1" class="d-none"
                   <?= !empty($task['is_current']) ? 'checked' : '' ?>>
        </div>

        <div class="jt-stats">
            <span class="jt-stat jt-stat-progress" title="Progreso">
                <span class="jt-stat-bar"><span id="heroBar" style="width: <?= $pct ?>%"></span></span>
                <b id="heroPct"><?= $pct ?>%</b>
            </span>
            <span class="jt-stat" title="Tiempo invertido"><i class="bi bi-clock"></i> <b id="heroHours"><?= number_format(((int)($task['time_spent'] ?? 0)) / 60, 1) ?></b> h</span>
            <span class="jt-stat" title="Subtareas hechas"><i class="bi bi-check2-square"></i> <b id="heroSubs"><?= $subDone ?>/<?= count($subtasks) ?></b></span>
            <?php if ($startDate || $endDate): ?>
                <span class="jt-stat" title="Periodo"><i class="bi bi-calendar3"></i>
                    <?= $startDate ? date('d/m/y', strtotime($startDate)) : '…' ?> → <?= $endDate ? date('d/m/y', strtotime($endDate)) : '…' ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="jt-workspace">

    <!-- ============ Columna lateral: datos de la tarea ============ -->
    <aside class="jt-side">
        <details class="jt-card jt-fold" id="detailsPanel" open>
            <summary>
                <span><i class="bi bi-sliders"></i> Detalles</span>
                <i class="bi bi-chevron-down jt-fold-icon"></i>
            </summary>

            <form action="<?= site_url('journal/edit/' . $task['id']) ?>" method="post" enctype="multipart/form-data" id="taskForm" class="jt-fold-body">
                <?= csrf_field() ?>

                <div class="jt-field-group">
                    <div class="jt-field">
                        <label for="start_time" class="jt-label">Inicio</label>
                        <input type="date" name="start_time" id="start_time" class="form-control form-control-sm" value="<?= $startDate ?>">
                    </div>
                    <div class="jt-field">
                        <label for="end_time" class="jt-label">Fin</label>
                        <input type="date" name="end_time" id="end_time" class="form-control form-control-sm" value="<?= $endDate ?>">
                    </div>
                </div>

                <div class="jt-field-group">
                    <div class="jt-field">
                        <label for="completed" class="jt-label">Completados</label>
                        <input type="number" name="completed" id="completed" class="form-control form-control-sm"
                               min="0" value="<?= esc($task['completed'] ?? '') ?>" placeholder="Ej. 4">
                    </div>
                    <div class="jt-field">
                        <label for="amplitude" class="jt-label">De un total de</label>
                        <input type="number" name="amplitude" id="amplitude" class="form-control form-control-sm"
                               min="1" required value="<?= esc($task['amplitude'] ?? '') ?>" placeholder="Ej. 10">
                    </div>
                </div>
                <div class="jt-progress-wrap">
                    <div class="jt-progress"><div class="jt-progress-bar" id="jtProgressBar" style="width:0%"></div></div>
                    <span class="jt-hint" id="jtProgressLabel">0%</span>
                </div>

                <div class="jt-field">
                    <label for="time_spent" class="jt-label">Tiempo invertido</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="time_spent" id="time_spent" class="form-control" min="0"
                               value="<?= esc($task['time_spent'] ?? '') ?>">
                        <span class="input-group-text">min</span>
                        <span class="input-group-text jt-hint-inline" id="timeHint">= 0.00 h</span>
                    </div>
                </div>

                <div class="jt-field">
                    <label for="note" class="jt-label">Nota</label>
                    <textarea name="note" id="note" class="form-control form-control-sm" rows="3" placeholder="Algo que recordar sobre la tarea…"><?= esc($task['note'] ?? '') ?></textarea>
                </div>

                <div class="jt-field">
                    <label for="image" class="jt-label"><?= !empty($task['image']) ? 'Cambiar imagen' : 'Imagen' ?></label>
                    <input type="file" name="image" id="image" accept="image/*" class="form-control form-control-sm">
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= site_url('journal') ?>" class="btn btn-sm btn-outline-secondary flex-fill">Volver</a>
                    <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-check2"></i> Guardar</button>
                </div>
            </form>
        </details>

        <!-- Historial de fechas -->
        <details class="jt-card jt-fold" id="logsPanel">
            <summary>
                <span><i class="bi bi-calendar-check"></i> Historial <span class="jt-count"><?= count($logs) ?></span></span>
                <i class="bi bi-chevron-down jt-fold-icon"></i>
            </summary>
            <div class="jt-fold-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted small mb-0">Sin registros todavía.</p>
                <?php else: ?>
                    <div class="jt-log-list">
                        <?php foreach ($logs as $log): ?>
                            <button type="button" class="jt-log-item js-edit-log"
                                    data-id="<?= (int)$log['id'] ?>"
                                    data-date="<?= esc($log['log_date']) ?>"
                                    data-minutes="<?= (int)$log['minutes'] ?>">
                                <span class="jt-log-date"><?= date('d/m/Y', strtotime($log['log_date'])) ?></span>
                                <span class="jt-log-minutes"><?= (int)$log['minutes'] ?> min</span>
                                <i class="bi bi-pencil jt-log-edit-icon"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </details>

        <form action="<?= site_url('journal/delete/' . $task['id']) ?>" method="post" class="jt-danger"
              onsubmit="return confirm('¿Seguro que quieres eliminar esta tarea?');">
            <?= csrf_field() ?>
            <button type="submit" class="jt-danger-btn"><i class="bi bi-trash"></i> Eliminar tarea</button>
        </form>
    </aside>

    <!-- ============ Zona principal: pestañas de trabajo ============ -->
    <section class="jt-main">
        <div class="jt-tabs" role="tablist">
            <button type="button" class="jt-tab" role="tab" data-tab="subtareas">
                <i class="bi bi-list-check"></i> <span class="jt-tab-label">Subtareas</span> <span class="jt-count" id="countSubtareas"><?= count($subtasks) ?></span>
            </button>
            <button type="button" class="jt-tab" role="tab" data-tab="materiales">
                <i class="bi bi-images"></i> <span class="jt-tab-label">Materiales</span> <span class="jt-count" id="countMateriales"><?= count($files) ?></span>
            </button>
            <button type="button" class="jt-tab" role="tab" data-tab="enlaces">
                <i class="bi bi-link-45deg"></i> <span class="jt-tab-label">Enlaces</span> <span class="jt-count" id="countEnlaces"><?= count($links) ?></span>
            </button>
        </div>

        <!-- ---------- Subtareas ---------- -->
        <div class="jt-panel jt-card" data-panel="subtareas">
            <div class="jt-toolbar">
                <div class="jt-subtask-add">
                    <i class="bi bi-plus-lg jt-add-icon"></i>
                    <input type="text" id="subtaskInput" class="form-control form-control-sm" placeholder="Añadir subtarea y pulsar Enter…" maxlength="255">
                    <button type="button" id="subtaskAddBtn" class="btn btn-sm btn-primary" title="Añadir"><i class="bi bi-plus-lg"></i></button>
                </div>
                <div class="jt-toolbar-actions">
                    <button type="button" class="jt-icon-btn js-hide-done" title="Ocultar/mostrar subtareas hechas"><i class="bi bi-eye-slash"></i></button>
                    <button type="button" class="jt-icon-btn js-push-done" title="Empujar las hechas al final"><i class="bi bi-sort-down"></i></button>
                    <button type="button" id="subtaskSuggestBtn" class="btn btn-sm btn-outline-primary" title="Sugerir subtareas con IA">
                        <i class="bi bi-stars"></i> <span class="d-none d-sm-inline">Sugerir</span>
                    </button>
                </div>
            </div>

            <div class="jt-subprogress <?= empty($subtasks) ? 'd-none' : '' ?>" id="subProgress">
                <div class="jt-progress"><div class="jt-progress-bar" id="subProgressBar" style="width: <?= count($subtasks) ? round($subDone / count($subtasks) * 100) : 0 ?>%"></div></div>
                <span class="jt-hint" id="subProgressLabel"><?= $subDone ?> de <?= count($subtasks) ?> hechas</span>
            </div>

            <div class="jt-subtask-list" id="subtaskList" data-task-id="<?= (int)$task['id'] ?>">
                <?php foreach ($subtasks as $s):
                    $isDone = !empty($s['is_done']);
                    $subFiles = array_values(array_filter($files, fn($f) => (int)($f['subtask_id'] ?? 0) === (int)$s['id']));
                    $subLinks = array_values(array_filter($links, fn($l) => (int)($l['subtask_id'] ?? 0) === (int)$s['id']));
                    $attachCount = count($subFiles) + count($subLinks);
                ?>
                    <div class="jt-subtask-item <?= $isDone ? 'is-done' : '' ?>" data-id="<?= (int)$s['id'] ?>">
                        <div class="jt-subtask-row">
                            <span class="jt-subtask-handle" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                            <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha">
                                <i class="bi <?= $isDone ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                            </button>
                            <span class="jt-subtask-title"><?= esc($s['title']) ?></span>
                            <?php if ($attachCount > 0): ?>
                                <button type="button" class="jt-chip-btn" data-bs-toggle="collapse" data-bs-target="#subAttach<?= (int)$s['id'] ?>" title="Ver materiales/enlaces asociados">
                                    <i class="bi bi-paperclip"></i> <?= $attachCount ?>
                                </button>
                            <?php endif; ?>
                            <span class="jt-chip-btn jt-subtask-time subtask-time-trigger" data-subtask-id="<?= (int)$s['id'] ?>" title="Añadir tiempo">
                                <?= number_format(((int)($s['time_spent'] ?? 0)) / 60, 2) ?> h
                            </span>
                            <div class="dropdown">
                                <button type="button" class="jt-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><button type="button" class="dropdown-item js-edit-subtask"><i class="bi bi-pencil"></i> Renombrar</button></li>
                                    <li><button type="button" class="dropdown-item js-move-top-subtask"><i class="bi bi-arrow-bar-up"></i> Mover al principio</button></li>
                                    <li><button type="button" class="dropdown-item js-move-bottom-subtask"><i class="bi bi-arrow-bar-down"></i> Mover al final</button></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><button type="button" class="dropdown-item text-danger js-delete-subtask"><i class="bi bi-trash"></i> Eliminar</button></li>
                                </ul>
                            </div>
                        </div>
                        <?php if ($attachCount > 0): ?>
                            <div class="collapse jt-subtask-attachments" id="subAttach<?= (int)$s['id'] ?>">
                                <?php foreach ($subFiles as $f): ?>
                                    <a href="<?= base_url($f['ruta_archivo']) ?>" target="_blank" class="jt-subtask-attachment-link">
                                        <i class="bi bi-file-earmark"></i> <span><?= esc($f['nombre_original']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach ($subLinks as $l): ?>
                                    <a href="<?= esc($l['url'], 'attr') ?>" target="_blank" rel="noopener" class="jt-subtask-attachment-link">
                                        <i class="bi bi-link-45deg"></i> <span><?= esc($l['titulo'] ?: $l['url']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="jt-empty <?= empty($subtasks) ? '' : 'd-none' ?>" id="subtaskEmptyMsg">
                <i class="bi bi-list-check"></i>
                <span>Sin subtareas todavía. Escribe arriba o pulsa <b>Sugerir</b>.</span>
            </div>
        </div>

        <!-- ---------- Materiales ---------- -->
        <div class="jt-panel jt-card" data-panel="materiales">
            <label class="jt-drop" id="materialDrop" for="materialInput">
                <i class="bi bi-cloud-arrow-up"></i>
                <span><b>Arrastra archivos aquí</b> o pulsa para elegirlos</span>
                <input type="file" id="materialInput" multiple class="d-none">
            </label>
            <div class="jt-drop-opts">
                <label for="materialSubtaskSelect" class="jt-label mb-0">Asociar a</label>
                <select id="materialSubtaskSelect" class="form-select form-select-sm">
                    <option value="">(sin subtarea)</option>
                    <?php foreach ($subtasks as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= esc($s['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="jt-toolbar jt-gallery-bar <?= empty($files) ? 'd-none' : '' ?>" id="materialesToolbar">
                <div class="btn-group btn-group-sm" role="group" aria-label="Vista">
                    <button type="button" class="btn btn-outline-secondary js-mat-view" data-view="gallery" title="Galería"><i class="bi bi-grid-3x3-gap"></i></button>
                    <button type="button" class="btn btn-outline-secondary js-mat-view" data-view="list" title="Lista"><i class="bi bi-list-ul"></i></button>
                </div>
                <span class="jt-gallery-count" id="galleryCount"></span>
                <div class="jt-toolbar-actions" id="gallerySelTools">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="gallerySelectAll">Seleccionar todas</button>
                    <button type="button" class="btn btn-sm btn-primary" id="galleryDownloadBtn" disabled>
                        <i class="bi bi-download"></i> <span id="gallerySelCount">0</span>
                    </button>
                </div>
            </div>

            <!-- Galería (se construye en JS a partir del listado) -->
            <div class="jt-gallery-grid" id="galleryGrid"></div>

            <div class="jt-list-heading d-none" id="otherFilesHeading">Otros archivos</div>
            <div class="jt-materiales-list" id="materialesList">
                <?php foreach ($files as $f):
                    $ext = strtolower(pathinfo($f['nombre_original'], PATHINFO_EXTENSION));
                    $icon = match (true) {
                        in_array($ext, $imgExts, true) => 'bi-file-earmark-image',
                        $ext === 'pdf' => 'bi-file-earmark-pdf',
                        in_array($ext, ['doc', 'docx'], true) => 'bi-file-earmark-word',
                        in_array($ext, ['xls', 'xlsx'], true) => 'bi-file-earmark-excel',
                        in_array($ext, ['zip', 'rar', '7z'], true) => 'bi-file-earmark-zip',
                        in_array($ext, ['mp4', 'mov', 'avi', 'mkv'], true) => 'bi-file-earmark-play',
                        default => 'bi-file-earmark',
                    };
                    $sizeLabel = '';
                    if (!empty($f['tamano'])) {
                        $sizeLabel = $f['tamano'] < 1024 * 1024
                            ? round($f['tamano'] / 1024, 1) . ' KB'
                            : round($f['tamano'] / 1024 / 1024, 1) . ' MB';
                    }
                ?>
                    <div class="jt-material-item" data-id="<?= (int)$f['id'] ?>" data-url="<?= esc(base_url($f['ruta_archivo']), 'attr') ?>" data-nombre="<?= esc($f['nombre_original'], 'attr') ?>" data-descripcion="<?= esc($f['descripcion'] ?? '', 'attr') ?>" data-subtask-id="<?= (int)($f['subtask_id'] ?? 0) ?>">
                        <div class="jt-material-row">
                            <a href="<?= base_url($f['ruta_archivo']) ?>" target="_blank" class="jt-material-link">
                                <?php if (in_array($ext, $imgExts, true)): ?>
                                    <img class="jt-material-thumb" loading="lazy" src="<?= base_url($f['ruta_archivo']) ?>" alt="">
                                <?php else: ?>
                                    <i class="bi <?= $icon ?>"></i>
                                <?php endif; ?>
                                <span class="jt-material-name"><?= esc($f['nombre_original']) ?></span>
                            </a>
                            <span class="jt-material-size"><?= esc($sizeLabel) ?></span>
                            <button type="button" class="jt-icon-btn js-edit-material" title="Editar material" aria-label="Editar material"><i class="bi bi-pencil"></i></button>
                            <button type="button" class="jt-icon-btn jt-icon-danger js-delete-material" title="Eliminar material" aria-label="Eliminar material"><i class="bi bi-trash"></i></button>
                        </div>
                        <?php if (!empty($f['descripcion'])): ?>
                            <div class="jt-material-desc"><?= esc($f['descripcion']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($f['subtask_id']) && isset($subtaskTitleById[$f['subtask_id']])): ?>
                            <div class="jt-material-subtask"><i class="bi bi-list-check"></i> <span><?= esc($subtaskTitleById[$f['subtask_id']]) ?></span></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="jt-empty <?= empty($files) ? '' : 'd-none' ?>" id="materialesEmptyMsg">
                <i class="bi bi-images"></i>
                <span>Sin materiales todavía. Fotos de referencia, PDFs, documentos…</span>
            </div>
        </div>

        <!-- ---------- Enlaces ---------- -->
        <div class="jt-panel jt-card" data-panel="enlaces">
            <div class="jt-link-add">
                <input type="url" id="linkUrlInput" class="form-control form-control-sm" placeholder="Pega una URL https://…">
                <input type="text" id="linkTituloInput" class="form-control form-control-sm" placeholder="Texto (opcional)" maxlength="255">
                <select id="linkSubtaskSelect" class="form-select form-select-sm">
                    <option value="">(sin subtarea)</option>
                    <?php foreach ($subtasks as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= esc($s['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="linkAddBtn" class="btn btn-sm btn-primary" title="Añadir enlace"><i class="bi bi-plus-lg"></i></button>
            </div>

            <div class="jt-materiales-list" id="linksList">
                <?php foreach ($links as $l): $host = parse_url($l['url'], PHP_URL_HOST) ?: ''; ?>
                    <div class="jt-material-item" data-id="<?= (int)$l['id'] ?>" data-url="<?= esc($l['url'], 'attr') ?>" data-titulo="<?= esc($l['titulo'] ?? '', 'attr') ?>" data-descripcion="<?= esc($l['descripcion'] ?? '', 'attr') ?>" data-subtask-id="<?= (int)($l['subtask_id'] ?? 0) ?>">
                        <div class="jt-material-row">
                            <a href="<?= esc($l['url'], 'attr') ?>" target="_blank" rel="noopener" class="jt-material-link">
                                <i class="bi bi-link-45deg"></i>
                                <span class="jt-material-name"><?= esc($l['titulo'] ?: $l['url']) ?></span>
                            </a>
                            <span class="jt-material-size jt-link-host"><?= esc(preg_replace('/^www\./', '', $host)) ?></span>
                            <button type="button" class="jt-icon-btn js-edit-link" title="Editar enlace" aria-label="Editar enlace"><i class="bi bi-pencil"></i></button>
                            <button type="button" class="jt-icon-btn jt-icon-danger js-delete-link" title="Eliminar enlace" aria-label="Eliminar enlace"><i class="bi bi-trash"></i></button>
                        </div>
                        <?php if (!empty($l['descripcion'])): ?>
                            <div class="jt-material-desc"><?= esc($l['descripcion']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($l['subtask_id']) && isset($subtaskTitleById[$l['subtask_id']])): ?>
                            <div class="jt-material-subtask"><i class="bi bi-list-check"></i> <span><?= esc($subtaskTitleById[$l['subtask_id']]) ?></span></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="jt-empty <?= empty($links) ? '' : 'd-none' ?>" id="linksEmptyMsg">
                <i class="bi bi-link-45deg"></i>
                <span>Sin enlaces todavía. Tutoriales, tiendas, referencias…</span>
            </div>
        </div>
    </section>
</div>

<!-- Barra flotante de cambios sin guardar -->
<div class="jt-savebar" id="saveBar" aria-live="polite">
    <span><i class="bi bi-pencil-square"></i> Cambios sin guardar</span>
    <button type="submit" form="taskForm" class="btn btn-sm btn-primary"><i class="bi bi-check2"></i> Guardar</button>
</div>

<!-- Visor de imágenes -->
<div class="modal fade jt-lightbox" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="jt-lb-top">
                <span class="jt-lb-name" id="lbName"></span>
                <span class="jt-lb-pos" id="lbPos"></span>
                <a class="jt-lb-btn" id="lbDownload" title="Descargar"><i class="bi bi-download"></i></a>
                <a class="jt-lb-btn" id="lbOpen" target="_blank" title="Abrir original"><i class="bi bi-box-arrow-up-right"></i></a>
                <button type="button" class="jt-lb-btn jt-lb-del" id="lbDelete" title="Eliminar imagen"><i class="bi bi-trash"></i></button>
                <button type="button" class="jt-lb-btn" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="jt-lb-stage">
                <button type="button" class="jt-lb-nav jt-lb-prev" id="lbPrev" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
                <img id="lbImg" alt="">
                <button type="button" class="jt-lb-nav jt-lb-next" id="lbNext" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="jt-lb-desc" id="lbDesc"></div>
        </div>
    </div>
</div>

<!-- MODAL SUGERIR SUBTAREAS -->
<div class="modal fade" id="subtaskSuggestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-stars"></i> Subtareas sugeridas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="subtaskSuggestContexto" class="form-label small mb-1">Contexto extra (opcional)</label>
                    <textarea id="subtaskSuggestContexto" class="form-control form-control-sm" rows="2" placeholder="Algo que ayude a generar mejores subtareas..."></textarea>
                </div>
                <div id="subtaskSuggestLoading" class="text-muted small d-none">Pensando...</div>
                <div id="subtaskSuggestError" class="text-danger small d-none"></div>
                <div id="subtaskSuggestList" class="d-flex flex-column gap-2"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-outline-primary btn-sm" id="subtaskSuggestGenerateBtn"><i class="bi bi-stars"></i> Generar</button>
                <button class="btn btn-primary btn-sm" id="subtaskSuggestAddBtn" disabled>Añadir seleccionadas</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RENOMBRAR SUBTAREA -->
<div class="modal fade" id="subtaskEditModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Renombrar subtarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="subtaskEditInput" class="form-control" maxlength="255">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="subtaskEditSaveBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AÑADIR TIEMPO A SUBTAREA -->
<div class="modal fade" id="subtaskTimeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir tiempo a subtarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="number" id="subtaskTimeMinutes" class="form-control" placeholder="Minutos" min="1">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="subtaskTimeSaveBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR MATERIAL -->
<div class="modal fade" id="materialEditModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="materialEditNombre" class="form-label">Nombre</label>
                    <input type="text" id="materialEditNombre" class="form-control" maxlength="255">
                </div>
                <div class="mb-2">
                    <label for="materialEditDescripcion" class="form-label">Descripción (opcional)</label>
                    <textarea id="materialEditDescripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label for="materialEditSubtask" class="form-label">Subtarea (opcional)</label>
                    <select id="materialEditSubtask" class="form-select">
                        <option value="">(sin subtarea)</option>
                        <?php foreach ($subtasks as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= esc($s['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="materialEditSaveBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR ENLACE -->
<div class="modal fade" id="linkEditModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar enlace</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label for="linkEditUrl" class="form-label">URL</label>
                    <input type="url" id="linkEditUrl" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label for="linkEditTitulo" class="form-label">Texto (opcional)</label>
                    <input type="text" id="linkEditTitulo" class="form-control" maxlength="255">
                </div>
                <div class="mb-2">
                    <label for="linkEditDescripcion" class="form-label">Descripción (opcional)</label>
                    <textarea id="linkEditDescripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-2">
                    <label for="linkEditSubtask" class="form-label">Subtarea (opcional)</label>
                    <select id="linkEditSubtask" class="form-select">
                        <option value="">(sin subtarea)</option>
                        <?php foreach ($subtasks as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= esc($s['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="linkEditSaveBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: editar registro del historial -->
<div class="modal fade" id="modalEditLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="logId">
                <div class="mb-2">
                    <label for="logDate" class="form-label">Fecha</label>
                    <input type="date" id="logDate" class="form-control">
                </div>
                <div class="mb-2">
                    <label for="logMinutes" class="form-label">Minutos</label>
                    <input type="number" id="logMinutes" class="form-control" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveLogBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Journal · edición de tarea =====
   Escritorio: cabecera + columna lateral (detalles, plegable y fija) +
   zona principal con pestañas. Móvil: todo en una columna, detalles plegados. */
:root { --jt-accent: #7c3aed; --jt-accent-soft: rgba(124,58,237,.14); --jt-radius: 14px; }

.jt-back {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: .85rem; color: var(--bs-secondary-color); text-decoration: none;
}
.jt-back:hover { color: var(--bs-emphasis-color); }

/* ---------- Cabecera ---------- */
.jt-hero { display: flex; gap: 14px; align-items: center; }
.jt-hero-img { position: relative; flex: 0 0 auto; }
.jt-hero-img img, .jt-hero-img-empty {
    width: 76px; height: 76px; border-radius: 16px; object-fit: cover; display: block;
    border: 1px solid var(--bs-border-color);
}
.jt-hero-img-empty {
    display: grid; place-items: center; background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color); font-size: 1.5rem; border-style: dashed; cursor: pointer;
}
.jt-hero-img-empty:hover { color: var(--jt-accent); border-color: var(--jt-accent); }
.jt-image-remove {
    position: absolute; top: -6px; right: -6px; width: 22px; height: 22px;
    display: grid; place-items: center; border-radius: 50%; border: none;
    background: #dc3545; color: #fff; font-size: .7rem; opacity: 0; transition: opacity .15s;
}
.jt-hero-img:hover .jt-image-remove, .jt-image-remove:focus { opacity: 1; }
@media (hover: none) { .jt-image-remove { opacity: 1; } }

.jt-hero-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
.jt-title-row { display: flex; align-items: center; gap: 8px; }
.jt-star {
    flex: 0 0 auto; width: 38px; height: 38px; display: grid; place-items: center;
    border-radius: 50%; border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg);
    color: #adb5bd; font-size: 1.05rem; cursor: pointer;
}
.jt-star[aria-pressed="true"] { color: #ffc107; border-color: rgba(255,193,7,.4); background: rgba(255,193,7,.12); }
.jt-title-input {
    flex: 1 1 auto; min-width: 0; font-size: 1.35rem; font-weight: 700;
    padding: .25rem .5rem; border-radius: 10px; border: 1px solid transparent;
    background: transparent; color: var(--bs-emphasis-color);
}
.jt-title-input:hover { border-color: var(--bs-border-color); }
.jt-title-input:focus { outline: none; border-color: var(--jt-accent); background: var(--bs-body-bg); box-shadow: 0 0 0 .2rem rgba(124,58,237,.2); }

.jt-stats { display: flex; flex-wrap: wrap; gap: 6px; }
.jt-stat {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px; font-size: .78rem;
    background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);
    color: var(--bs-secondary-color);
}
.jt-stat b { color: var(--bs-emphasis-color); font-weight: 600; }
.jt-stat-bar { width: 64px; height: 6px; border-radius: 999px; background: var(--jt-accent-soft); overflow: hidden; }
.jt-stat-bar span { display: block; height: 100%; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .2s; }

/* ---------- Estructura ---------- */
.jt-workspace { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; padding-bottom: 70px; }
.jt-workspace > * { min-width: 0; }
.jt-side { display: flex; flex-direction: column; gap: 10px; }
/* Una columna: Detalles arriba, luego las pestañas, y al final historial y borrar */
@media (max-width: 991.98px) {
    .jt-side { display: contents; }
    #detailsPanel { order: 1; }
    .jt-main { order: 2; }
    #logsPanel { order: 3; }
    .jt-danger { order: 4; }
}
@media (min-width: 992px) {
    .jt-workspace { grid-template-columns: 320px minmax(0, 1fr); align-items: start; }
    .jt-side { position: sticky; top: 12px; max-height: calc(100vh - 24px); overflow-y: auto; }
}

.jt-card {
    background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);
    border-radius: var(--jt-radius); padding: 12px 14px;
}

/* Bloques plegables (Detalles / Historial) */
.jt-fold { padding: 0; }
.jt-fold > summary {
    list-style: none; cursor: pointer; user-select: none;
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; font-weight: 600; font-size: .9rem; color: var(--bs-emphasis-color);
}
.jt-fold > summary::-webkit-details-marker { display: none; }
.jt-fold > summary i:first-child { color: var(--bs-secondary-color); margin-right: 4px; }
.jt-fold-icon { color: var(--bs-secondary-color); transition: transform .15s; }
.jt-fold[open] .jt-fold-icon { transform: rotate(180deg); }
.jt-fold-body { padding: 0 14px 14px; display: flex; flex-direction: column; gap: 10px; }

.jt-field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.jt-field { display: flex; flex-direction: column; min-width: 0; }
.jt-label { font-size: .72rem; color: var(--bs-secondary-color); margin-bottom: 3px; font-weight: 500; }
.jt-hint { font-size: .74rem; color: var(--bs-secondary-color); white-space: nowrap; }
.jt-hint-inline { font-size: .75rem; color: var(--bs-secondary-color); }
.jt-progress-wrap { display: flex; align-items: center; gap: 8px; margin-top: -4px; }
.jt-progress-wrap .jt-progress { flex: 1 1 auto; }
.jt-progress { height: 6px; border-radius: 999px; background: var(--jt-accent-soft); overflow: hidden; }
.jt-progress-bar { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #7c3aed, #a78bfa); transition: width .2s ease; }

.jt-count {
    display: inline-block; min-width: 20px; padding: 0 6px; border-radius: 999px;
    font-size: .7rem; font-weight: 600; line-height: 18px; text-align: center;
    background: var(--bs-secondary-bg); color: var(--bs-secondary-color);
}

.jt-log-list { display: flex; flex-direction: column; gap: 4px; max-height: 260px; overflow-y: auto; }
.jt-log-item {
    display: flex; align-items: center; gap: 10px; padding: 6px 10px; border-radius: 8px;
    border: 1px solid transparent; background: var(--bs-body-bg); color: var(--bs-emphasis-color);
    text-align: left; cursor: pointer;
}
.jt-log-item:hover { border-color: var(--bs-border-color); }
.jt-log-date { font-weight: 600; font-size: .82rem; }
.jt-log-minutes { font-size: .78rem; color: var(--bs-secondary-color); margin-left: auto; }
.jt-log-edit-icon { color: var(--bs-secondary-color); font-size: .75rem; }

.jt-danger { text-align: center; }
.jt-danger-btn {
    border: none; background: none; color: var(--bs-secondary-color);
    font-size: .8rem; padding: 6px 10px; border-radius: 8px;
}
.jt-danger-btn:hover { color: #dc3545; background: rgba(220,53,69,.1); }

/* ---------- Pestañas ---------- */
.jt-tabs {
    display: flex; gap: 4px; padding: 4px; margin-bottom: 10px;
    background: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color); border-radius: 12px;
}
.jt-tab {
    flex: 1 1 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 7px 8px; border: none; border-radius: 9px; background: transparent;
    color: var(--bs-secondary-color); font-size: .88rem; font-weight: 500; white-space: nowrap;
}
.jt-tab:hover { color: var(--bs-emphasis-color); }
.jt-tab.active { background: var(--bs-body-bg); color: var(--bs-emphasis-color); box-shadow: 0 1px 3px rgba(0,0,0,.25); }
.jt-tab.active .jt-count { background: var(--jt-accent-soft); color: #a78bfa; }
.jt-panel { display: none; flex-direction: column; gap: 10px; }
.jt-panel.active { display: flex; }

.jt-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.jt-toolbar-actions { display: flex; align-items: center; gap: 4px; margin-left: auto; }

.jt-icon-btn {
    flex: 0 0 auto; width: 30px; height: 30px; display: grid; place-items: center;
    border-radius: 8px; border: none; background: transparent; color: var(--bs-secondary-color);
}
.jt-icon-btn:hover { background: var(--bs-secondary-bg); color: var(--bs-emphasis-color); }
.jt-icon-btn.active { color: #0d6efd; background: rgba(13,110,253,.14); }
.jt-icon-danger:hover { background: rgba(220,53,69,.12); color: #dc3545; }

.jt-empty {
    display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center;
    padding: 26px 10px; color: var(--bs-secondary-color); font-size: .85rem;
}
.jt-empty i { font-size: 1.8rem; opacity: .5; }

/* ---------- Subtareas ---------- */
.jt-subtask-add { flex: 1 1 260px; display: flex; align-items: center; gap: 6px; position: relative; }
.jt-subtask-add .form-control { flex: 1 1 auto; padding-left: 30px; }
.jt-add-icon { position: absolute; left: 10px; color: var(--bs-secondary-color); font-size: .8rem; pointer-events: none; }
.jt-subprogress { display: flex; align-items: center; gap: 10px; }
.jt-subprogress .jt-progress { flex: 1 1 auto; }

.jt-subtask-list { display: flex; flex-direction: column; gap: 2px; }
.jt-subtask-list.hide-done .jt-subtask-item.is-done { display: none; }
.jt-subtask-list.push-done .jt-subtask-item.is-done { order: 1; }

.jt-subtask-item {
    display: flex; flex-direction: column; gap: 2px; padding: 2px 4px;
    border-radius: 10px; border: 1px solid transparent; background: var(--bs-body-bg);
    transition: opacity .15s ease, background-color .15s ease;
}
.jt-subtask-item:hover { border-color: var(--bs-border-color); }
.jt-subtask-item.sortable-ghost { opacity: .3; }
.jt-subtask-item.sortable-chosen { background: var(--bs-secondary-bg); }
.jt-subtask-item.is-done { opacity: .55; }
.jt-subtask-row { display: flex; align-items: center; gap: 4px; min-height: 36px; }

.jt-subtask-handle {
    flex: 0 0 auto; display: grid; place-items: center; width: 18px; height: 30px;
    color: var(--bs-secondary-color); cursor: grab; touch-action: none; opacity: .35;
}
.jt-subtask-item:hover .jt-subtask-handle { opacity: 1; }
@media (hover: none) { .jt-subtask-handle { opacity: .7; } }
.jt-subtask-handle:active { cursor: grabbing; }

.jt-subtask-check {
    flex: 0 0 auto; width: 30px; height: 30px; display: grid; place-items: center;
    border-radius: 50%; border: none; background: transparent; color: var(--bs-secondary-color);
    font-size: 1.05rem; cursor: pointer;
}
.jt-subtask-check:hover { color: #10b981; }
.jt-subtask-item.is-done .jt-subtask-check { color: #10b981; }

.jt-subtask-title { flex: 1 1 auto; min-width: 0; font-size: .9rem; color: var(--bs-emphasis-color); word-break: break-word; padding: 4px 0; }
.jt-subtask-item.is-done .jt-subtask-title { text-decoration: line-through; color: var(--bs-secondary-color); }

.jt-chip-btn {
    flex: 0 0 auto; display: inline-flex; align-items: center; gap: 3px;
    padding: 1px 8px; border-radius: 999px; border: 1px solid var(--bs-border-color);
    background: transparent; color: var(--bs-secondary-color); font-size: .72rem;
    cursor: pointer; white-space: nowrap; line-height: 20px;
}
.jt-chip-btn:hover { background: var(--bs-secondary-bg); color: var(--bs-emphasis-color); }
.jt-subtask-attachments { display: flex; flex-direction: column; gap: 3px; padding: 0 0 6px 52px; }
.jt-subtask-attachment-link { display: flex; align-items: center; gap: 6px; font-size: .8rem; text-decoration: none; color: inherit; min-width: 0; }
.jt-subtask-attachment-link span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.jt-subtask-attachment-link:hover { text-decoration: underline; }
.jt-subtask-item .dropdown-item { font-size: .85rem; display: flex; gap: 8px; align-items: center; }

/* ---------- Materiales ---------- */
.jt-drop {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 14px; border: 2px dashed var(--bs-border-color); border-radius: 12px;
    color: var(--bs-secondary-color); font-size: .85rem; cursor: pointer; text-align: center;
    transition: border-color .15s, background-color .15s, color .15s; margin: 0;
}
.jt-drop i { font-size: 1.4rem; }
.jt-drop:hover, .jt-drop.is-over { border-color: var(--jt-accent); color: var(--bs-emphasis-color); background: var(--jt-accent-soft); }
.jt-drop.is-busy { opacity: .6; pointer-events: none; }
.jt-drop-opts { display: flex; align-items: center; gap: 8px; margin-top: -4px; }
.jt-drop-opts .jt-label { white-space: nowrap; }
.jt-drop-opts .form-select { flex: 1 1 auto; max-width: 280px; }

.jt-gallery-count { font-size: .78rem; color: var(--bs-secondary-color); }
.jt-gallery-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    grid-auto-rows: max-content; gap: 8px;
}
.jt-gallery-grid:empty { display: none; }
.jt-thumb {
    position: relative; margin: 0; border-radius: 10px; overflow: hidden;
    background: var(--bs-body-bg); border: 2px solid transparent; cursor: zoom-in;
}
.jt-thumb.is-selected { border-color: var(--bs-primary); }
.jt-thumb.is-hl { border-color: var(--bs-info); }
.jt-thumb-img { display: block; aspect-ratio: 1 / 1; background: var(--bs-secondary-bg); }
.jt-thumb-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .2s; }
.jt-thumb:hover .jt-thumb-img img { transform: scale(1.04); }
.jt-thumb-check, .jt-thumb-act {
    position: absolute; width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,.55); color: #fff; text-decoration: none; border: none;
    opacity: 0; transition: opacity .15s; cursor: pointer;
}
.jt-thumb-check { top: 5px; left: 5px; }
.jt-thumb-check input { cursor: pointer; margin: 0; width: 16px; height: 16px; }
.jt-thumb-act.dl { top: 5px; right: 5px; }
.jt-thumb-act.ed { top: 37px; right: 5px; }
.jt-thumb-act.del { top: 69px; right: 5px; }
.jt-thumb-act:hover { background: var(--bs-primary); color: #fff; }
.jt-thumb-act.del:hover { background: #dc3545; }
.jt-material-thumb {
    flex: 0 0 auto; width: 34px; height: 34px; border-radius: 6px;
    object-fit: cover; background: var(--bs-secondary-bg); display: block;
}
.jt-material-item:has(.jt-material-thumb) .jt-material-desc,
.jt-material-item:has(.jt-material-thumb) .jt-material-subtask { padding-left: 42px; }
.jt-thumb:hover .jt-thumb-check, .jt-thumb:hover .jt-thumb-act,
.jt-thumb.is-selected .jt-thumb-check, .jt-gallery-grid.selecting .jt-thumb-check { opacity: 1; }
@media (hover: none) { .jt-thumb-check, .jt-thumb-act { opacity: 1; } }
.jt-thumb figcaption {
    font-size: .7rem; padding: 3px 6px; overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; color: var(--bs-secondary-color);
}

.jt-list-heading { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; color: var(--bs-secondary-color); margin-top: 4px; }
.jt-materiales-list { display: flex; flex-direction: column; gap: 2px; }
.jt-materiales-list:empty { display: none; }
#materialesList.only-files .jt-material-item.is-image { display: none; }
.jt-material-item {
    display: flex; flex-direction: column; gap: 1px; padding: 3px 4px 3px 8px;
    border-radius: 9px; border: 1px solid transparent; background: var(--bs-body-bg);
}
.jt-material-item:hover { border-color: var(--bs-border-color); }
.jt-material-item.is-hl { border-color: var(--bs-info); }
.jt-material-row { display: flex; align-items: center; gap: 6px; min-height: 32px; }
.jt-material-desc, .jt-material-subtask { font-size: .74rem; padding: 0 0 3px 24px; word-break: break-word; }
.jt-material-desc { color: var(--bs-secondary-color); }
.jt-material-subtask { color: var(--bs-primary); }
.jt-material-link { display: flex; align-items: center; gap: 8px; flex: 1 1 auto; min-width: 0; text-decoration: none; color: inherit; font-size: .86rem; }
.jt-material-link:hover { text-decoration: underline; }
.jt-material-link i { color: var(--bs-secondary-color); }
.jt-material-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.jt-material-size { flex: 0 0 auto; font-size: .72rem; color: var(--bs-secondary-color); }
.jt-link-host { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ---------- Enlaces ---------- */
.jt-link-add { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr) minmax(0, 1fr) auto; gap: 6px; }

/* ---------- Barra de guardar ---------- */
.jt-savebar {
    position: fixed; left: 50%; bottom: 16px; z-index: 1040;
    transform: translate(-50%, 140%); transition: transform .2s ease;
    display: flex; align-items: center; gap: 14px; padding: 8px 8px 8px 16px;
    border-radius: 999px; background: var(--bs-body-bg); border: 1px solid var(--jt-accent);
    box-shadow: 0 8px 28px rgba(0,0,0,.4); font-size: .85rem; color: var(--bs-emphasis-color);
}
.jt-savebar.show { transform: translate(-50%, 0); }
.jt-savebar .btn { border-radius: 999px; }

/* ---------- Visor ---------- */
.jt-lightbox .modal-content { background: rgba(10,10,14,.96); border: none; color: #fff; }
.jt-lb-top { display: flex; align-items: center; gap: 6px; padding: 8px 10px; }
.jt-lb-name { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .85rem; }
.jt-lb-pos { font-size: .78rem; opacity: .6; margin-right: 6px; }
.jt-lb-btn {
    width: 34px; height: 34px; display: grid; place-items: center; border-radius: 8px;
    border: none; background: rgba(255,255,255,.08); color: #fff; text-decoration: none;
}
.jt-lb-btn:hover { background: var(--bs-primary); color: #fff; }
.jt-lb-del { margin-left: 6px; }
.jt-lb-del:hover { background: #dc3545; }
.jt-lb-stage { position: relative; display: flex; align-items: center; justify-content: center; min-height: 50vh; }
/* Tamaño fijo + contain: las imágenes pequeñas también se ven grandes, sin deformarse */
.jt-lb-stage img { width: 100%; height: 75vh; object-fit: contain; display: block; }
.jt-lb-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border-radius: 50%; border: none;
    background: rgba(0,0,0,.5); color: #fff; font-size: 1.2rem; display: grid; place-items: center;
}
.jt-lb-nav:hover { background: var(--bs-primary); }
.jt-lb-prev { left: 10px; }
.jt-lb-next { right: 10px; }
.jt-lb-desc { padding: 6px 12px 12px; font-size: .8rem; opacity: .75; text-align: center; }
.jt-lb-desc:empty { display: none; }

/* ---------- Móvil ---------- */
@media (max-width: 575.98px) {
    .jt-hero { align-items: flex-start; gap: 10px; }
    .jt-hero-img img, .jt-hero-img-empty { width: 56px; height: 56px; border-radius: 12px; }
    .jt-title-input { font-size: 1.1rem; }
    .jt-star { width: 32px; height: 32px; font-size: .95rem; }
    .jt-stat-bar { width: 40px; }
    .jt-tab { gap: 4px; font-size: .8rem; padding: 7px 4px; }
    .jt-tab > i { display: none; }
    .jt-card { padding: 10px; }
    .jt-fold { padding: 0; }
    .jt-gallery-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 5px; }
    .jt-link-add { grid-template-columns: minmax(0, 1fr) auto; }
    .jt-link-add #linkUrlInput { grid-column: 1 / -1; }
    .jt-link-add #linkTituloInput { grid-column: 1 / -1; }
    .jt-link-host { display: none; }
    .jt-chip-btn { padding: 1px 6px; }
    .jt-drop { padding: 12px; }
    .jt-drop-opts .form-select { max-width: none; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Estrella ---
    const starBtn = document.getElementById('starBtn');
    const isCurrentInput = document.getElementById('is_current');
    starBtn.addEventListener('click', () => {
        const nuevo = !isCurrentInput.checked;
        isCurrentInput.checked = nuevo;
        starBtn.setAttribute('aria-pressed', nuevo ? 'true' : 'false');
        starBtn.querySelector('i').className = nuevo ? 'bi bi-star-fill' : 'bi bi-star';
    });

    // --- Amplitud / Completados: máximo sincronizado en vivo + barra de progreso ---
    const amplitudeInput = document.getElementById('amplitude');
    const completedInput = document.getElementById('completed');
    const progressBar = document.getElementById('jtProgressBar');
    const progressLabel = document.getElementById('jtProgressLabel');

    function actualizarProgreso() {
        const amplitude = parseInt(amplitudeInput.value, 10) || 0;
        let completed = parseInt(completedInput.value, 10) || 0;

        completedInput.max = amplitude || '';
        if (amplitude && completed > amplitude) {
            completed = amplitude;
            completedInput.value = amplitude;
        }

        const pct = amplitude > 0 ? Math.min(100, Math.round((completed / amplitude) * 100)) : 0;
        progressBar.style.width = pct + '%';
        progressLabel.textContent = pct + '%';
        document.getElementById('heroBar').style.width = pct + '%';
        document.getElementById('heroPct').textContent = pct + '%';
    }
    amplitudeInput.addEventListener('input', actualizarProgreso);
    completedInput.addEventListener('input', actualizarProgreso);
    actualizarProgreso();

    // --- Tiempo invertido -> horas ---
    const timeInput = document.getElementById('time_spent');
    const timeHint = document.getElementById('timeHint');
    function actualizarHint() {
        const mins = parseInt(timeInput.value, 10) || 0;
        timeHint.textContent = '= ' + (mins / 60).toFixed(2) + ' h';
        document.getElementById('heroHours').textContent = (mins / 60).toFixed(1);
    }
    timeInput.addEventListener('input', actualizarHint);
    actualizarHint();

    // --- Editar registro del historial ---
    const modalEl = document.getElementById('modalEditLog');
    const modal = new bootstrap.Modal(modalEl);
    const logIdInput = document.getElementById('logId');
    const logDateInput = document.getElementById('logDate');
    const logMinutesInput = document.getElementById('logMinutes');
    const saveLogBtn = document.getElementById('saveLogBtn');

    document.querySelectorAll('.js-edit-log').forEach(btn => {
        btn.addEventListener('click', () => {
            logIdInput.value = btn.dataset.id;
            logDateInput.value = btn.dataset.date;
            logMinutesInput.value = btn.dataset.minutes;
            modal.show();
        });
    });

    saveLogBtn.addEventListener('click', async () => {
        const id = logIdInput.value;
        saveLogBtn.disabled = true;

        try {
            const res = await fetch('<?= site_url('journal/update-log') ?>/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                },
                body: JSON.stringify({
                    log_date: logDateInput.value,
                    minutes: parseInt(logMinutesInput.value, 10) || 0,
                }),
            });
            const data = await res.json();
            if (!data.success) throw new Error();

            const item = document.querySelector('.js-edit-log[data-id="' + id + '"]');
            item.dataset.date = logDateInput.value;
            item.dataset.minutes = logMinutesInput.value;
            const [y, m, d] = logDateInput.value.split('-');
            item.querySelector('.jt-log-date').textContent = d + '/' + m + '/' + y;
            item.querySelector('.jt-log-minutes').textContent = (parseInt(logMinutesInput.value, 10) || 0) + ' min';

            modal.hide();
        } catch (err) {
            alert('No se pudo guardar el cambio.');
        } finally {
            saveLogBtn.disabled = false;
        }
    });

    // --- Subtareas ---
    const subtaskList = document.getElementById('subtaskList');
    const subtaskEmptyMsg = document.getElementById('subtaskEmptyMsg');
    const subtaskInput = document.getElementById('subtaskInput');
    const subtaskAddBtn = document.getElementById('subtaskAddBtn');

    // Filtros visuales de subtareas hechas (ocultar / empujar abajo).
    // Solo afectan a la presentación: el orden real en el DOM/BD no cambia.
    const subtaskHideBtn = document.querySelector('.js-hide-done');
    const subtaskPushBtn = document.querySelector('.js-push-done');
    const subtaskFilterPrefs = {
        hide: localStorage.getItem('journalSubtaskHideDone') === '1',
        push: localStorage.getItem('journalSubtaskPushDone') === '1',
    };

    function applySubtaskFilters() {
        subtaskList.classList.toggle('hide-done', subtaskFilterPrefs.hide);
        subtaskList.classList.toggle('push-done', subtaskFilterPrefs.push);
        subtaskHideBtn.classList.toggle('active', subtaskFilterPrefs.hide);
        subtaskPushBtn.classList.toggle('active', subtaskFilterPrefs.push);
    }

    subtaskHideBtn.addEventListener('click', () => {
        subtaskFilterPrefs.hide = !subtaskFilterPrefs.hide;
        localStorage.setItem('journalSubtaskHideDone', subtaskFilterPrefs.hide ? '1' : '0');
        applySubtaskFilters();
    });
    subtaskPushBtn.addEventListener('click', () => {
        subtaskFilterPrefs.push = !subtaskFilterPrefs.push;
        localStorage.setItem('journalSubtaskPushDone', subtaskFilterPrefs.push ? '1' : '0');
        applySubtaskFilters();
    });
    applySubtaskFilters();

    const subtaskEditModal = new bootstrap.Modal(document.getElementById('subtaskEditModal'));
    const subtaskEditInput = document.getElementById('subtaskEditInput');
    const subtaskEditSaveBtn = document.getElementById('subtaskEditSaveBtn');
    let subtaskEditItem = null;

    const subtaskTimeModal = new bootstrap.Modal(document.getElementById('subtaskTimeModal'));
    const subtaskTimeMinutes = document.getElementById('subtaskTimeMinutes');
    const subtaskTimeSaveBtn = document.getElementById('subtaskTimeSaveBtn');
    let subtaskTimeItem = null;

    async function postJSON(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
            },
            body: body !== undefined ? JSON.stringify(body) : undefined,
        });
        return res.json();
    }

    function buildSubtaskItem(subtask) {
        const item = document.createElement('div');
        item.className = 'jt-subtask-item';
        item.dataset.id = subtask.id;
        item.innerHTML = `
            <div class="jt-subtask-row">
                <span class="jt-subtask-handle" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha"><i class="bi bi-circle"></i></button>
                <span class="jt-subtask-title"></span>
                <span class="jt-chip-btn jt-subtask-time subtask-time-trigger" data-subtask-id="${subtask.id}" title="Añadir tiempo">0.00 h</span>
                <div class="dropdown">
                    <button type="button" class="jt-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones"><i class="bi bi-three-dots"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button type="button" class="dropdown-item js-edit-subtask"><i class="bi bi-pencil"></i> Renombrar</button></li>
                        <li><button type="button" class="dropdown-item js-move-top-subtask"><i class="bi bi-arrow-bar-up"></i> Mover al principio</button></li>
                        <li><button type="button" class="dropdown-item js-move-bottom-subtask"><i class="bi bi-arrow-bar-down"></i> Mover al final</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="button" class="dropdown-item text-danger js-delete-subtask"><i class="bi bi-trash"></i> Eliminar</button></li>
                    </ul>
                </div>
            </div>
        `;
        item.querySelector('.jt-subtask-title').textContent = subtask.title;
        return item;
    }

    function persistSubtaskOrder() {
        const orden = [...subtaskList.querySelectorAll('.jt-subtask-item')].map(item => item.dataset.id);
        postJSON('<?= site_url('journal/subtasks/reordenar') ?>', { orden });
    }

    async function addSubtask() {
        const title = subtaskInput.value.trim();
        if (!title) return;

        subtaskAddBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskList.dataset.taskId + '/crear', { title });
            if (!data.success) throw new Error();

            subtaskList.prepend(buildSubtaskItem(data.subtask));
            subtaskInput.value = '';
            subtaskEmptyMsg.classList.add('d-none');
            applyProgress(data.progress);
        } catch (err) {
            alert('No se pudo añadir la subtarea.');
        } finally {
            subtaskAddBtn.disabled = false;
            subtaskInput.focus();
        }
    }

    function applyProgress(progress) {
        if (!progress) return;
        amplitudeInput.value = progress.amplitude;
        completedInput.value = progress.completed;
        actualizarProgreso();
    }

    subtaskAddBtn.addEventListener('click', addSubtask);
    subtaskInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSubtask();
        }
    });

    subtaskList.addEventListener('click', async (e) => {
        const timeTrigger = e.target.closest('.subtask-time-trigger');
        if (timeTrigger) {
            subtaskTimeItem = timeTrigger.closest('.jt-subtask-item');
            subtaskTimeMinutes.value = '';
            subtaskTimeModal.show();
            return;
        }

        const toggleBtn = e.target.closest('.js-toggle-subtask');
        if (toggleBtn) {
            const item = toggleBtn.closest('.jt-subtask-item');
            const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + item.dataset.id + '/toggle');
            if (!data.success) return;

            const isDone = !!data.is_done;
            item.classList.toggle('is-done', isDone);
            toggleBtn.querySelector('i').className = isDone ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            applyProgress(data.progress);
            return;
        }

        const moveTopBtn = e.target.closest('.js-move-top-subtask');
        if (moveTopBtn) {
            const item = moveTopBtn.closest('.jt-subtask-item');
            subtaskList.prepend(item);
            persistSubtaskOrder();
            return;
        }

        const moveBottomBtn = e.target.closest('.js-move-bottom-subtask');
        if (moveBottomBtn) {
            const item = moveBottomBtn.closest('.jt-subtask-item');
            subtaskList.appendChild(item);
            persistSubtaskOrder();
            return;
        }

        const editBtn = e.target.closest('.js-edit-subtask');
        if (editBtn) {
            subtaskEditItem = editBtn.closest('.jt-subtask-item');
            subtaskEditInput.value = subtaskEditItem.querySelector('.jt-subtask-title').textContent;
            subtaskEditModal.show();
            return;
        }

        const deleteBtn = e.target.closest('.js-delete-subtask');
        if (deleteBtn) {
            if (!confirm('¿Eliminar esta subtarea?')) return;

            const item = deleteBtn.closest('.jt-subtask-item');
            const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + item.dataset.id + '/borrar');
            if (!data.success) return;

            item.remove();
            if (!subtaskList.querySelector('.jt-subtask-item')) {
                subtaskEmptyMsg.classList.remove('d-none');
            }
            applyProgress(data.progress);
        }
    });

    subtaskTimeSaveBtn.addEventListener('click', async () => {
        if (!subtaskTimeItem) return;

        const minutes = parseInt(subtaskTimeMinutes.value, 10) || 0;
        if (minutes <= 0) return;

        subtaskTimeSaveBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskTimeItem.dataset.id + '/add-time', { minutes });
            if (!data.success) throw new Error();

            const span = subtaskTimeItem.querySelector('.jt-subtask-time');
            if (span) span.textContent = (data.subtask_minutes / 60).toFixed(2) + ' h';

            // El servidor ya ha sumado esos minutos al total de la tarea; reflejarlo
            // en el campo del formulario para que al guardar no se pise con el valor viejo.
            if (data.task_minutes !== undefined) {
                timeInput.value = data.task_minutes;
                actualizarHint();
            }

            subtaskTimeModal.hide();
        } catch (err) {
            alert('No se pudo añadir el tiempo.');
        } finally {
            subtaskTimeSaveBtn.disabled = false;
        }
    });

    subtaskEditSaveBtn.addEventListener('click', async () => {
        if (!subtaskEditItem) return;

        const title = subtaskEditInput.value.trim();
        if (!title) return;

        subtaskEditSaveBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskEditItem.dataset.id + '/editar', { title });
            if (!data.success) throw new Error();

            subtaskEditItem.querySelector('.jt-subtask-title').textContent = data.title;
            subtaskEditModal.hide();
        } catch (err) {
            alert('No se pudo renombrar la subtarea.');
        } finally {
            subtaskEditSaveBtn.disabled = false;
        }
    });

    document.getElementById('subtaskEditInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            subtaskEditSaveBtn.click();
        }
    });

    // --- Sugerir subtareas (IA) ---
    const subtaskSuggestBtn = document.getElementById('subtaskSuggestBtn');
    const subtaskSuggestModal = new bootstrap.Modal(document.getElementById('subtaskSuggestModal'));
    const subtaskSuggestContexto = document.getElementById('subtaskSuggestContexto');
    const subtaskSuggestLoading = document.getElementById('subtaskSuggestLoading');
    const subtaskSuggestError = document.getElementById('subtaskSuggestError');
    const subtaskSuggestList = document.getElementById('subtaskSuggestList');
    const subtaskSuggestGenerateBtn = document.getElementById('subtaskSuggestGenerateBtn');
    const subtaskSuggestAddBtn = document.getElementById('subtaskSuggestAddBtn');

    subtaskSuggestBtn.addEventListener('click', () => {
        subtaskSuggestContexto.value = '';
        subtaskSuggestList.innerHTML = '';
        subtaskSuggestError.classList.add('d-none');
        subtaskSuggestLoading.classList.add('d-none');
        subtaskSuggestAddBtn.disabled = true;
        subtaskSuggestModal.show();
        subtaskSuggestContexto.focus();
    });

    subtaskSuggestGenerateBtn.addEventListener('click', async () => {
        subtaskSuggestList.innerHTML = '';
        subtaskSuggestError.classList.add('d-none');
        subtaskSuggestLoading.classList.remove('d-none');
        subtaskSuggestAddBtn.disabled = true;
        subtaskSuggestGenerateBtn.disabled = true;

        try {
            const data = await postJSON('<?= site_url('journal/tasks') ?>/' + subtaskList.dataset.taskId + '/sugerir-subtareas', {
                contexto: subtaskSuggestContexto.value.trim(),
            });
            if (!data.success || !data.subtareas || !data.subtareas.length) {
                throw new Error(data.error || 'Sin sugerencias');
            }

            data.subtareas.forEach((titulo, i) => {
                const label = document.createElement('label');
                label.className = 'd-flex align-items-center gap-2';
                label.innerHTML = `
                    <input type="checkbox" class="form-check-input mt-0" checked id="sugerencia${i}">
                    <span>${titulo.replace(/</g, '&lt;')}</span>
                `;
                subtaskSuggestList.appendChild(label);
            });
            subtaskSuggestAddBtn.disabled = false;
        } catch (err) {
            subtaskSuggestError.textContent = err.message === 'Sin sugerencias'
                ? 'No se generaron sugerencias. Prueba de nuevo.'
                : (err.message || 'No se pudo contactar con la IA.');
            subtaskSuggestError.classList.remove('d-none');
        } finally {
            subtaskSuggestLoading.classList.add('d-none');
            subtaskSuggestGenerateBtn.disabled = false;
        }
    });

    subtaskSuggestAddBtn.addEventListener('click', async () => {
        const seleccionadas = [...subtaskSuggestList.querySelectorAll('input:checked')]
            .map(cb => cb.nextElementSibling.textContent);
        if (!seleccionadas.length) return;

        subtaskSuggestAddBtn.disabled = true;
        try {
            let lastProgress = null;
            const anchor = subtaskList.firstChild;
            for (const title of seleccionadas) {
                const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskList.dataset.taskId + '/crear', { title });
                if (data.success) {
                    subtaskList.insertBefore(buildSubtaskItem(data.subtask), anchor);
                    lastProgress = data.progress;
                }
            }
            persistSubtaskOrder();
            subtaskEmptyMsg.classList.add('d-none');
            applyProgress(lastProgress);
            subtaskSuggestModal.hide();
        } catch (err) {
            alert('No se pudieron añadir todas las subtareas.');
        } finally {
            subtaskSuggestAddBtn.disabled = false;
        }
    });

    Sortable.create(subtaskList, {
        handle: '.jt-subtask-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: () => persistSubtaskOrder(),
    });

    // --- Materiales (histórico de archivos) ---
    const materialesList = document.getElementById('materialesList');
    const materialesEmptyMsg = document.getElementById('materialesEmptyMsg');
    const materialInput = document.getElementById('materialInput');
    const materialSubtaskSelect = document.getElementById('materialSubtaskSelect');

    function materialIcon(name) {
        const ext = (name.split('.').pop() || '').toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext)) return 'bi-file-earmark-image';
        if (ext === 'pdf') return 'bi-file-earmark-pdf';
        if (['doc', 'docx'].includes(ext)) return 'bi-file-earmark-word';
        if (['xls', 'xlsx'].includes(ext)) return 'bi-file-earmark-excel';
        if (['zip', 'rar', '7z'].includes(ext)) return 'bi-file-earmark-zip';
        if (['mp4', 'mov', 'avi', 'mkv'].includes(ext)) return 'bi-file-earmark-play';
        return 'bi-file-earmark';
    }

    function formatSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function buildMaterialItem(f) {
        const div = document.createElement('div');
        div.className = 'jt-material-item';
        div.dataset.id = f.id;
        div.dataset.url = f.url;
        div.dataset.nombre = f.nombre_original;
        div.dataset.descripcion = f.descripcion || '';
        div.dataset.subtaskId = f.subtask_id || '';
        div.innerHTML = `
            <div class="jt-material-row">
                <a href="${f.url}" target="_blank" class="jt-material-link">
                    ${materialIcon(f.nombre_original) === 'bi-file-earmark-image'
                        ? '<img class="jt-material-thumb" loading="lazy" alt="">'
                        : `<i class="bi ${materialIcon(f.nombre_original)}"></i>`}
                    <span class="jt-material-name"></span>
                </a>
                <span class="jt-material-size">${formatSize(f.tamano)}</span>
                <button type="button" class="jt-icon-btn js-edit-material" title="Editar material" aria-label="Editar material"><i class="bi bi-pencil"></i></button>
                <button type="button" class="jt-icon-btn jt-icon-danger js-delete-material" title="Eliminar material" aria-label="Eliminar material"><i class="bi bi-trash"></i></button>
            </div>
        `;
        div.querySelector('.jt-material-name').textContent = f.nombre_original;
        const thumbImg = div.querySelector('.jt-material-thumb');
        if (thumbImg) thumbImg.src = f.url;
        setMaterialSubtask(div, f.subtask_id, f.subtask_title);
        return div;
    }

    function setMaterialDesc(item, descripcion) {
        item.dataset.descripcion = descripcion || '';
        let descEl = item.querySelector('.jt-material-desc');
        if (descripcion) {
            if (!descEl) {
                descEl = document.createElement('div');
                descEl.className = 'jt-material-desc';
                item.appendChild(descEl);
            }
            descEl.textContent = descripcion;
        } else if (descEl) {
            descEl.remove();
        }
    }

    function setMaterialSubtask(item, subtaskId, subtaskTitle) {
        item.dataset.subtaskId = subtaskId || '';
        let subEl = item.querySelector('.jt-material-subtask');
        if (subtaskId && subtaskTitle) {
            if (!subEl) {
                subEl = document.createElement('div');
                subEl.className = 'jt-material-subtask';
                subEl.innerHTML = '<i class="bi bi-list-check"></i> <span></span>';
                item.appendChild(subEl);
            }
            subEl.querySelector('span').textContent = subtaskTitle;
        } else if (subEl) {
            subEl.remove();
        }
    }

    // --- Galería de imágenes (se reconstruye a partir del listado) ---
    // Vista "gallery": miniaturas de las imágenes + listado solo con los demás
    // archivos. Vista "list": listado completo, sin miniaturas.
    const materialesToolbar = document.getElementById('materialesToolbar');
    const galleryGrid = document.getElementById('galleryGrid');
    const galleryCount = document.getElementById('galleryCount');
    const gallerySelTools = document.getElementById('gallerySelTools');
    const gallerySelectAll = document.getElementById('gallerySelectAll');
    const galleryDownloadBtn = document.getElementById('galleryDownloadBtn');
    const gallerySelCount = document.getElementById('gallerySelCount');
    const otherFilesHeading = document.getElementById('otherFilesHeading');
    const IMG_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif', 'svg'];
    let matView = 'gallery';
    try { matView = localStorage.getItem('journalMatView') || 'gallery'; } catch (e) {}

    function isImageName(name) {
        return IMG_EXTS.includes(((name || '').split('.').pop() || '').toLowerCase());
    }

    function updateGallerySelection() {
        const checks = galleryGrid.querySelectorAll('.jt-thumb input[type=checkbox]');
        let n = 0;
        checks.forEach(c => {
            c.closest('.jt-thumb').classList.toggle('is-selected', c.checked);
            if (c.checked) n++;
        });
        gallerySelCount.textContent = n;
        galleryDownloadBtn.disabled = n === 0;
        galleryGrid.classList.toggle('selecting', n > 0);
        gallerySelectAll.textContent = (n > 0 && n === checks.length) ? 'Quitar selección' : 'Seleccionar todas';
    }

    function syncGallery() {
        const selected = new Set([...galleryGrid.querySelectorAll('.jt-thumb input:checked')]
            .map(c => c.closest('.jt-thumb').dataset.id));
        galleryGrid.innerHTML = '';

        const all = [...materialesList.querySelectorAll('.jt-material-item')];
        all.forEach(it => it.classList.toggle('is-image', isImageName(it.dataset.nombre)));
        const items = all.filter(it => it.dataset.url && it.classList.contains('is-image'));
        const others = all.length - items.length;

        const showGallery = matView === 'gallery' && items.length > 0;
        if (showGallery) {
            items.forEach(it => {
                const fig = document.createElement('figure');
                fig.className = 'jt-thumb';
                fig.dataset.id = it.dataset.id;
                fig.innerHTML = `
                    <div class="jt-thumb-img"><img loading="lazy" alt=""></div>
                    <label class="jt-thumb-check" title="Seleccionar"><input type="checkbox"></label>
                    <a class="jt-thumb-act dl" title="Descargar" aria-label="Descargar"><i class="bi bi-download"></i></a>
                    <button type="button" class="jt-thumb-act ed" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="jt-thumb-act del" title="Eliminar" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                    <figcaption></figcaption>
                `;
                fig.querySelector('img').src = it.dataset.url;
                fig.querySelector('img').alt = it.dataset.nombre;
                const dl = fig.querySelector('.dl');
                dl.href = it.dataset.url;
                dl.setAttribute('download', it.dataset.nombre);
                fig.querySelector('figcaption').textContent = it.dataset.nombre;
                fig.title = it.dataset.descripcion ? it.dataset.nombre + ' — ' + it.dataset.descripcion : it.dataset.nombre;
                fig.querySelector('input').checked = selected.has(it.dataset.id);
                galleryGrid.appendChild(fig);
            });
        }

        materialesList.classList.toggle('only-files', showGallery);
        otherFilesHeading.classList.toggle('d-none', !(showGallery && others > 0));
        materialesToolbar.classList.toggle('d-none', all.length === 0);
        gallerySelTools.classList.toggle('d-none', !showGallery);
        document.querySelectorAll('.js-mat-view').forEach(b => b.classList.toggle('active', b.dataset.view === matView));

        const parts = [];
        if (items.length) parts.push(items.length + (items.length === 1 ? ' imagen' : ' imágenes'));
        if (others) parts.push(others + (others === 1 ? ' archivo' : ' archivos'));
        galleryCount.textContent = parts.join(' · ');
        updateGallerySelection();
    }

    document.querySelectorAll('.js-mat-view').forEach(b => b.addEventListener('click', () => {
        matView = b.dataset.view;
        try { localStorage.setItem('journalMatView', matView); } catch (e) {}
        syncGallery();
    }));

    galleryGrid.addEventListener('change', e => {
        if (e.target.matches('input[type=checkbox]')) updateGallerySelection();
    });

    galleryGrid.addEventListener('click', e => {
        const fig = e.target.closest('.jt-thumb');
        if (!fig) return;
        if (e.target.closest('.jt-thumb-check, .dl')) return; // casilla y descarga: comportamiento normal
        if (e.target.closest('.del')) {
            const item = materialesList.querySelector(`.jt-material-item[data-id="${fig.dataset.id}"]`);
            if (item) deleteMaterial(item);
            return;
        }
        if (e.target.closest('.ed')) {
            materialesList.querySelector(`.jt-material-item[data-id="${fig.dataset.id}"] .js-edit-material`)?.click();
            return;
        }
        // Con alguna seleccionada, pulsar la miniatura alterna la selección (como en una galería de fotos)
        if (galleryGrid.classList.contains('selecting')) {
            const cb = fig.querySelector('input');
            cb.checked = !cb.checked;
            updateGallerySelection();
            return;
        }
        openLightbox([...galleryGrid.querySelectorAll('.jt-thumb')].indexOf(fig));
    });

    gallerySelectAll.addEventListener('click', () => {
        const checks = [...galleryGrid.querySelectorAll('.jt-thumb input[type=checkbox]')];
        const all = checks.length > 0 && checks.every(c => c.checked);
        checks.forEach(c => c.checked = !all);
        updateGallerySelection();
    });

    galleryDownloadBtn.addEventListener('click', async () => {
        const links = [...galleryGrid.querySelectorAll('.jt-thumb')]
            .filter(f => f.querySelector('input').checked)
            .map(f => f.querySelector('.dl'));
        // Una a una con una pequeña pausa: el navegador puede pedir permiso
        // la primera vez para "descargar varios archivos".
        for (const a of links) {
            a.click();
            await new Promise(r => setTimeout(r, 250));
        }
    });

    // Resaltar a la vez la fila del listado y su miniatura al pasar el ratón
    function highlight(id, on) {
        materialesList.querySelector(`.jt-material-item[data-id="${id}"]`)?.classList.toggle('is-hl', on);
        galleryGrid.querySelector(`.jt-thumb[data-id="${id}"]`)?.classList.toggle('is-hl', on);
    }
    [materialesList, galleryGrid].forEach(el => {
        el.addEventListener('mouseover', e => {
            const t = e.target.closest('.jt-material-item, .jt-thumb');
            if (t) highlight(t.dataset.id, true);
        });
        el.addEventListener('mouseout', e => {
            const t = e.target.closest('.jt-material-item, .jt-thumb');
            if (t && !t.contains(e.relatedTarget)) highlight(t.dataset.id, false);
        });
    });

    // --- Visor de imágenes (anterior/siguiente con flechas o deslizando) ---
    const lightboxEl = document.getElementById('lightboxModal');
    const lightbox = new bootstrap.Modal(lightboxEl);
    const lbImg = document.getElementById('lbImg');
    let lbIndex = 0;

    function lbItems() {
        return [...materialesList.querySelectorAll('.jt-material-item.is-image')];
    }
    function showLb(i) {
        const items = lbItems();
        if (!items.length) return;
        lbIndex = (i + items.length) % items.length;
        const it = items[lbIndex];
        lbImg.src = it.dataset.url;
        lbImg.alt = it.dataset.nombre;
        document.getElementById('lbName').textContent = it.dataset.nombre;
        document.getElementById('lbPos').textContent = (lbIndex + 1) + ' / ' + items.length;
        document.getElementById('lbDesc').textContent = it.dataset.descripcion || '';
        const dl = document.getElementById('lbDownload');
        dl.href = it.dataset.url;
        dl.setAttribute('download', it.dataset.nombre);
        document.getElementById('lbOpen').href = it.dataset.url;
        const multi = items.length > 1;
        document.getElementById('lbPrev').classList.toggle('d-none', !multi);
        document.getElementById('lbNext').classList.toggle('d-none', !multi);
    }
    function openLightbox(i) { showLb(i); lightbox.show(); }
    // Borrar desde el visor: tras borrar, pasa a la siguiente imagen (o se cierra si no quedan)
    document.getElementById('lbDelete').addEventListener('click', async () => {
        const item = lbItems()[lbIndex];
        if (!item || !(await deleteMaterial(item))) return;
        if (lbItems().length) showLb(lbIndex); else lightbox.hide();
    });
    document.getElementById('lbPrev').addEventListener('click', () => showLb(lbIndex - 1));
    document.getElementById('lbNext').addEventListener('click', () => showLb(lbIndex + 1));
    document.addEventListener('keydown', e => {
        if (!lightboxEl.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') showLb(lbIndex - 1);
        if (e.key === 'ArrowRight') showLb(lbIndex + 1);
    });
    let touchX = null;
    lightboxEl.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    lightboxEl.addEventListener('touchend', e => {
        if (touchX === null) return;
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 50) showLb(lbIndex + (dx < 0 ? 1 : -1));
        touchX = null;
    });

    syncGallery();

    // --- Subida de materiales: selector de archivos o arrastrar y soltar ---
    const materialDrop = document.getElementById('materialDrop');

    async function uploadMaterials(fileList) {
        if (!fileList || !fileList.length) return;

        const formData = new FormData();
        for (const file of fileList) {
            formData.append('archivo', file);
        }
        if (materialSubtaskSelect.value) {
            formData.append('subtask_id', materialSubtaskSelect.value);
        }

        materialInput.disabled = true;
        materialDrop.classList.add('is-busy');
        try {
            const res = await fetch('<?= site_url('journal/tasks') ?>/' + subtaskList.dataset.taskId + '/materiales', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                },
                body: formData,
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'No se pudieron subir los archivos.');

            data.files.forEach(f => materialesList.prepend(buildMaterialItem(f)));
            materialesEmptyMsg.classList.add('d-none');
            syncGallery();
        } catch (err) {
            alert(err.message || 'No se pudieron subir los archivos.');
        } finally {
            materialInput.value = '';
            materialInput.disabled = false;
            materialDrop.classList.remove('is-busy');
        }
    }

    materialInput.addEventListener('change', () => uploadMaterials(materialInput.files));

    ['dragenter', 'dragover'].forEach(ev => materialDrop.addEventListener(ev, e => {
        e.preventDefault();
        materialDrop.classList.add('is-over');
    }));
    ['dragleave', 'drop'].forEach(ev => materialDrop.addEventListener(ev, e => {
        e.preventDefault();
        materialDrop.classList.remove('is-over');
    }));
    materialDrop.addEventListener('drop', e => uploadMaterials(e.dataTransfer.files));

    // Pegar una imagen (Ctrl+V) con la pestaña Materiales abierta la sube directamente
    document.addEventListener('paste', e => {
        if (!document.querySelector('[data-panel="materiales"].active')) return;
        if (e.target.closest('input, textarea')) return;
        const files = [...(e.clipboardData?.files || [])];
        if (files.length) uploadMaterials(files);
    });

    const materialEditModal = new bootstrap.Modal(document.getElementById('materialEditModal'));
    const materialEditNombre = document.getElementById('materialEditNombre');
    const materialEditDescripcion = document.getElementById('materialEditDescripcion');
    const materialEditSubtask = document.getElementById('materialEditSubtask');
    const materialEditSaveBtn = document.getElementById('materialEditSaveBtn');
    let materialEditItem = null;

    materialesList.addEventListener('click', async (e) => {
        const listThumb = e.target.closest('.jt-material-thumb');
        if (listThumb) {
            e.preventDefault();
            openLightbox(lbItems().indexOf(listThumb.closest('.jt-material-item')));
            return;
        }

        const editBtn = e.target.closest('.js-edit-material');
        if (editBtn) {
            materialEditItem = editBtn.closest('.jt-material-item');
            materialEditNombre.value = materialEditItem.dataset.nombre || '';
            materialEditDescripcion.value = materialEditItem.dataset.descripcion || '';
            materialEditSubtask.value = materialEditItem.dataset.subtaskId || '';
            materialEditModal.show();
            return;
        }

        const delBtn = e.target.closest('.js-delete-material');
        if (!delBtn) return;
        deleteMaterial(delBtn.closest('.jt-material-item'));
    });

    // Borra un material (desde el listado, la miniatura o el visor). Devuelve true si se borró.
    async function deleteMaterial(item) {
        if (!confirm('¿Eliminar «' + (item.dataset.nombre || 'este material') + '»?')) return false;

        const data = await postJSON('<?= site_url('journal/materiales') ?>/' + item.dataset.id + '/borrar');
        if (!data.success) {
            alert('No se pudo eliminar el material.');
            return false;
        }

        item.remove();
        syncGallery();
        if (!materialesList.querySelector('.jt-material-item')) {
            materialesEmptyMsg.classList.remove('d-none');
        }
        return true;
    }

    materialEditSaveBtn.addEventListener('click', async () => {
        if (!materialEditItem) return;

        const nombre = materialEditNombre.value.trim();
        if (!nombre) return;

        materialEditSaveBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/materiales') ?>/' + materialEditItem.dataset.id + '/editar', {
                nombre_original: nombre,
                descripcion: materialEditDescripcion.value.trim(),
                subtask_id: materialEditSubtask.value,
            });
            if (!data.success) throw new Error(data.error || 'No se pudo editar el material.');

            materialEditItem.dataset.nombre = data.file.nombre_original;
            materialEditItem.querySelector('.jt-material-name').textContent = data.file.nombre_original;
            setMaterialDesc(materialEditItem, data.file.descripcion);
            setMaterialSubtask(materialEditItem, data.file.subtask_id, data.file.subtask_title);
            syncGallery();
            materialEditModal.hide();
        } catch (err) {
            alert(err.message || 'No se pudo editar el material.');
        } finally {
            materialEditSaveBtn.disabled = false;
        }
    });

    // --- Enlaces (URL + texto libre) ---
    const linksList = document.getElementById('linksList');
    const linksEmptyMsg = document.getElementById('linksEmptyMsg');
    const linkUrlInput = document.getElementById('linkUrlInput');
    const linkTituloInput = document.getElementById('linkTituloInput');
    const linkSubtaskSelect = document.getElementById('linkSubtaskSelect');
    const linkAddBtn = document.getElementById('linkAddBtn');

    function buildLinkItem(l) {
        const div = document.createElement('div');
        div.className = 'jt-material-item';
        div.dataset.id = l.id;
        div.dataset.url = l.url;
        div.dataset.titulo = l.titulo || '';
        div.dataset.descripcion = l.descripcion || '';
        div.dataset.subtaskId = l.subtask_id || '';
        div.innerHTML = `
            <div class="jt-material-row">
                <a href="${l.url}" target="_blank" rel="noopener" class="jt-material-link">
                    <i class="bi bi-link-45deg"></i>
                    <span class="jt-material-name"></span>
                </a>
                <span class="jt-material-size jt-link-host"></span>
                <button type="button" class="jt-icon-btn js-edit-link" title="Editar enlace" aria-label="Editar enlace"><i class="bi bi-pencil"></i></button>
                <button type="button" class="jt-icon-btn jt-icon-danger js-delete-link" title="Eliminar enlace" aria-label="Eliminar enlace"><i class="bi bi-trash"></i></button>
            </div>
        `;
        div.querySelector('.jt-material-name').textContent = l.titulo || l.url;
        try { div.querySelector('.jt-link-host').textContent = new URL(l.url).hostname.replace(/^www\./, ''); } catch (e) {}
        div.querySelector('.jt-material-link').href = l.url;
        setLinkSubtask(div, l.subtask_id, l.subtask_title);
        return div;
    }

    function setLinkDesc(item, descripcion) {
        item.dataset.descripcion = descripcion || '';
        let descEl = item.querySelector('.jt-material-desc');
        if (descripcion) {
            if (!descEl) {
                descEl = document.createElement('div');
                descEl.className = 'jt-material-desc';
                item.appendChild(descEl);
            }
            descEl.textContent = descripcion;
        } else if (descEl) {
            descEl.remove();
        }
    }

    function setLinkSubtask(item, subtaskId, subtaskTitle) {
        item.dataset.subtaskId = subtaskId || '';
        let subEl = item.querySelector('.jt-material-subtask');
        if (subtaskId && subtaskTitle) {
            if (!subEl) {
                subEl = document.createElement('div');
                subEl.className = 'jt-material-subtask';
                subEl.innerHTML = '<i class="bi bi-list-check"></i> <span></span>';
                item.appendChild(subEl);
            }
            subEl.querySelector('span').textContent = subtaskTitle;
        } else if (subEl) {
            subEl.remove();
        }
    }

    async function addLink() {
        const url = linkUrlInput.value.trim();
        if (!url) return;

        linkAddBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/tasks') ?>/' + subtaskList.dataset.taskId + '/enlaces', {
                url,
                titulo: linkTituloInput.value.trim(),
                subtask_id: linkSubtaskSelect.value,
            });
            if (!data.success) throw new Error(data.error || 'No se pudo añadir el enlace.');

            linksList.appendChild(buildLinkItem(data.link));
            linksEmptyMsg.classList.add('d-none');
            linkUrlInput.value = '';
            linkTituloInput.value = '';
        } catch (err) {
            alert(err.message || 'No se pudo añadir el enlace.');
        } finally {
            linkAddBtn.disabled = false;
        }
    }

    linkAddBtn.addEventListener('click', addLink);
    linkUrlInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') addLink(); });
    linkTituloInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') addLink(); });

    const linkEditModal = new bootstrap.Modal(document.getElementById('linkEditModal'));
    const linkEditUrl = document.getElementById('linkEditUrl');
    const linkEditTitulo = document.getElementById('linkEditTitulo');
    const linkEditDescripcion = document.getElementById('linkEditDescripcion');
    const linkEditSubtask = document.getElementById('linkEditSubtask');
    const linkEditSaveBtn = document.getElementById('linkEditSaveBtn');
    let linkEditItem = null;

    linksList.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.js-edit-link');
        if (editBtn) {
            linkEditItem = editBtn.closest('.jt-material-item');
            linkEditUrl.value = linkEditItem.dataset.url || linkEditItem.querySelector('.jt-material-link').href;
            linkEditTitulo.value = linkEditItem.dataset.titulo || '';
            linkEditDescripcion.value = linkEditItem.dataset.descripcion || '';
            linkEditSubtask.value = linkEditItem.dataset.subtaskId || '';
            linkEditModal.show();
            return;
        }

        const delBtn = e.target.closest('.js-delete-link');
        if (!delBtn) return;
        if (!confirm('¿Eliminar este enlace?')) return;

        const item = delBtn.closest('.jt-material-item');
        const data = await postJSON('<?= site_url('journal/enlaces') ?>/' + item.dataset.id + '/borrar');
        if (!data.success) return;

        item.remove();
        if (!linksList.querySelector('.jt-material-item')) {
            linksEmptyMsg.classList.remove('d-none');
        }
    });

    linkEditSaveBtn.addEventListener('click', async () => {
        if (!linkEditItem) return;
        const url = linkEditUrl.value.trim();
        if (!url) { linkEditUrl.focus(); return; }

        linkEditSaveBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/enlaces') ?>/' + linkEditItem.dataset.id + '/editar', {
                url,
                titulo: linkEditTitulo.value.trim(),
                descripcion: linkEditDescripcion.value.trim(),
                subtask_id: linkEditSubtask.value,
            });
            if (!data.success) throw new Error(data.error || 'No se pudo editar el enlace.');

            linkEditItem.dataset.url = data.link.url;
            linkEditItem.dataset.titulo = data.link.titulo || '';
            linkEditItem.querySelector('.jt-material-link').href = data.link.url;
            linkEditItem.querySelector('.jt-material-name').textContent = data.link.titulo || data.link.url;
            try { linkEditItem.querySelector('.jt-link-host').textContent = new URL(data.link.url).hostname.replace(/^www\./, ''); } catch (e) {}
            setLinkDesc(linkEditItem, data.link.descripcion);
            setLinkSubtask(linkEditItem, data.link.subtask_id, data.link.subtask_title);
            linkEditModal.hide();
        } catch (err) {
            alert(err.message || 'No se pudo editar el enlace.');
        } finally {
            linkEditSaveBtn.disabled = false;
        }
    });

    // --- Pestañas Subtareas / Materiales / Enlaces (recuerda la última) ---
    const tabs = [...document.querySelectorAll('.jt-tab')];
    const panels = [...document.querySelectorAll('.jt-panel')];
    function showTab(name) {
        if (!panels.some(p => p.dataset.panel === name)) name = 'subtareas';
        tabs.forEach(t => {
            const on = t.dataset.tab === name;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panels.forEach(p => p.classList.toggle('active', p.dataset.panel === name));
        try { localStorage.setItem('journalEditTab', name); } catch (e) {}
    }
    tabs.forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));
    let initialTab = 'subtareas';
    try { initialTab = localStorage.getItem('journalEditTab') || 'subtareas'; } catch (e) {}
    showTab(initialTab);

    // --- Detalles plegados por defecto en móvil (el trabajo diario son las pestañas) ---
    const detailsPanel = document.getElementById('detailsPanel');
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        detailsPanel.removeAttribute('open');
    }

    // Botón "añadir imagen" de la cabecera: abre Detalles y el selector de archivo
    document.querySelector('.js-pick-image')?.addEventListener('click', () => {
        detailsPanel.setAttribute('open', '');
        document.getElementById('image').click();
    });

    // --- Barra "Cambios sin guardar" (el formulario puede estar plegado) ---
    const taskForm = document.getElementById('taskForm');
    const saveBar = document.getElementById('saveBar');
    const markDirty = () => saveBar.classList.add('show');
    document.querySelectorAll('[form="taskForm"], #taskForm input, #taskForm textarea').forEach(el => {
        el.addEventListener('input', markDirty);
        el.addEventListener('change', markDirty);
    });
    starBtn.addEventListener('click', markDirty);
    // Ctrl/Cmd + S guarda la tarea
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            if (taskForm.reportValidity()) taskForm.requestSubmit();
        }
    });

    // --- Contadores de pestañas y resumen de subtareas, siempre al día ---
    const subProgress = document.getElementById('subProgress');
    function updateCounts() {
        const subs = subtaskList.querySelectorAll('.jt-subtask-item');
        const done = subtaskList.querySelectorAll('.jt-subtask-item.is-done').length;
        document.getElementById('countSubtareas').textContent = subs.length;
        document.getElementById('countMateriales').textContent = materialesList.querySelectorAll('.jt-material-item').length;
        document.getElementById('countEnlaces').textContent = linksList.querySelectorAll('.jt-material-item').length;
        document.getElementById('heroSubs').textContent = done + '/' + subs.length;
        subProgress.classList.toggle('d-none', subs.length === 0);
        document.getElementById('subProgressBar').style.width = (subs.length ? Math.round(done / subs.length * 100) : 0) + '%';
        document.getElementById('subProgressLabel').textContent = done + ' de ' + subs.length + ' hechas';
    }
    const countsObserver = new MutationObserver(updateCounts);
    countsObserver.observe(subtaskList, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    countsObserver.observe(materialesList, { childList: true });
    countsObserver.observe(linksList, { childList: true });
    updateCounts();
});
</script>

<?= $this->endSection() ?>
