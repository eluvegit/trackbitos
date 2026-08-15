<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="jt-header mb-3">
    <a href="<?= site_url('journal') ?>" class="jt-back"><i class="bi bi-chevron-left"></i> Journal</a>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php if (!empty($task['image'])): ?>
    <div class="jt-image mb-3">
        <img src="<?= base_url($task['image']) ?>" alt="Imagen actual">
        <form action="<?= site_url('journal/delete-image/' . $task['id']) ?>" method="post"
              onsubmit="return confirm('¿Eliminar esta imagen?')" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="jt-image-remove" title="Eliminar imagen">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    </div>
<?php endif; ?>

<form action="<?= site_url('journal/edit/' . $task['id']) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Título + estrella -->
    <div class="jt-title-row mb-3">
        <button type="button" class="jt-star" id="starBtn" aria-pressed="<?= !empty($task['is_current']) ? 'true' : 'false' ?>" title="Marcar como actual">
            <i class="bi <?= !empty($task['is_current']) ? 'bi-star-fill' : 'bi-star' ?>"></i>
        </button>
        <input type="text" name="title" id="title" class="jt-title-input"
               value="<?= esc($task['title'] ?? '') ?>" placeholder="Título" required>
        <input type="checkbox" name="is_current" id="is_current" value="1" class="d-none"
               <?= !empty($task['is_current']) ? 'checked' : '' ?>>
    </div>

    <!-- Periodo -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Periodo</div>
        <div class="row g-2">
            <div class="col-6">
                <label for="start_time" class="jt-label">Inicio</label>
                <input type="date" name="start_time" id="start_time" class="form-control"
                       value="<?= !empty($task['start_time']) && $task['start_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['start_time'])) : '' ?>">
            </div>
            <div class="col-6">
                <label for="end_time" class="jt-label">Fin</label>
                <input type="date" name="end_time" id="end_time" class="form-control"
                       value="<?= !empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($task['end_time'])) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Progreso -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Progreso</div>
        <div class="row g-2">
            <div class="col-6">
                <label for="amplitude" class="jt-label">Amplitud (total)</label>
                <input type="number" name="amplitude" id="amplitude" class="form-control"
                       min="1" required value="<?= esc($task['amplitude'] ?? '') ?>" placeholder="Ej. 10">
            </div>
            <div class="col-6">
                <label for="completed" class="jt-label">Completados</label>
                <input type="number" name="completed" id="completed" class="form-control"
                       min="0" value="<?= esc($task['completed'] ?? '') ?>" placeholder="Ej. 4">
            </div>
        </div>
        <div class="jt-progress mt-2">
            <div class="jt-progress-bar" id="jtProgressBar" style="width:0%"></div>
        </div>
        <div class="jt-hint" id="jtProgressLabel">0%</div>
    </div>

    <!-- Tiempo invertido -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Tiempo invertido</div>
        <div class="input-group">
            <input type="number" name="time_spent" id="time_spent" class="form-control" min="0"
                   value="<?= esc($task['time_spent'] ?? '') ?>">
            <span class="input-group-text">min</span>
        </div>
        <div class="jt-hint" id="timeHint">= 0.00 h</div>
    </div>

    <!-- Nota -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Nota</div>
        <textarea name="note" id="note" class="form-control" rows="3" placeholder="Nota"><?= esc($task['note'] ?? '') ?></textarea>
    </div>

    <!-- Imagen opcional -->
    <div class="jt-section mb-3">
        <div class="jt-section-title">Imagen</div>
        <input type="file" name="image" id="image" class="form-control">
    </div>

    <div class="d-flex gap-2 mb-3">
        <a href="<?= site_url('journal') ?>" class="btn btn-outline-secondary flex-fill">Cancelar</a>
        <button type="submit" class="btn btn-primary flex-fill">Guardar</button>
    </div>
</form>

<!-- Subtareas -->
<div class="jt-section mb-3">
    <div class="jt-section-title d-flex align-items-center justify-content-between">
        <span>Subtareas</span>
        <button type="button" id="subtaskSuggestBtn" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-stars"></i> Sugerir subtareas
        </button>
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
                    <span class="jt-subtask-handle" title="Arrastrar para reordenar">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
                    <button type="button" class="jt-subtask-check js-toggle-subtask" aria-label="Marcar como hecha">
                        <i class="bi <?= $isDone ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                    </button>
                    <span class="jt-subtask-title"><?= esc($s['title']) ?></span>
                    <?php if ($attachCount > 0): ?>
                        <button type="button" class="jt-subtask-attach-toggle" data-bs-toggle="collapse" data-bs-target="#subAttach<?= (int)$s['id'] ?>" title="Ver materiales/enlaces asociados">
                            <i class="bi bi-paperclip"></i> <?= $attachCount ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="jt-subtask-edit js-edit-subtask" title="Renombrar subtarea" aria-label="Renombrar subtarea">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="jt-subtask-delete js-delete-subtask" title="Eliminar subtarea" aria-label="Eliminar subtarea">
                        <i class="bi bi-trash"></i>
                    </button>
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

    <p class="text-muted small mb-2 <?= empty($subtasks) ? '' : 'd-none' ?>" id="subtaskEmptyMsg">Sin subtareas todavía.</p>

    <div class="jt-subtask-add">
        <input type="text" id="subtaskInput" class="form-control form-control-sm" placeholder="Nueva subtarea..." maxlength="255">
        <button type="button" id="subtaskAddBtn" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</div>

<?php $subtaskTitleById = array_column($subtasks, 'title', 'id'); ?>

<!-- Materiales (histórico de archivos de referencia para hacer la tarea) -->
<div class="jt-section mb-3">
    <div class="jt-section-title">Materiales</div>

    <div class="jt-materiales-list mb-2" id="materialesList">
        <?php foreach ($files as $f):
            $ext = strtolower(pathinfo($f['nombre_original'], PATHINFO_EXTENSION));
            $icon = match (true) {
                in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true) => 'bi-file-earmark-image',
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
            <div class="jt-material-item" data-id="<?= (int)$f['id'] ?>" data-nombre="<?= esc($f['nombre_original'], 'attr') ?>" data-descripcion="<?= esc($f['descripcion'] ?? '', 'attr') ?>" data-subtask-id="<?= (int)($f['subtask_id'] ?? 0) ?>">
                <div class="jt-material-row">
                    <a href="<?= base_url($f['ruta_archivo']) ?>" target="_blank" class="jt-material-link">
                        <i class="bi <?= $icon ?>"></i>
                        <span class="jt-material-name"><?= esc($f['nombre_original']) ?></span>
                    </a>
                    <span class="jt-material-size"><?= esc($sizeLabel) ?></span>
                    <button type="button" class="jt-subtask-edit js-edit-material" title="Editar material" aria-label="Editar material">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="jt-subtask-delete js-delete-material" title="Eliminar material" aria-label="Eliminar material">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <?php if (!empty($f['descripcion'])): ?>
                    <div class="jt-material-desc"><?= esc($f['descripcion']) ?></div>
                <?php endif; ?>
                <?php if (!empty($f['subtask_id']) && isset($subtaskTitleById[$f['subtask_id']])): ?>
                    <div class="jt-material-subtask"><i class="bi bi-list-check"></i> <?= esc($subtaskTitleById[$f['subtask_id']]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="text-muted small mb-2 <?= empty($files) ? '' : 'd-none' ?>" id="materialesEmptyMsg">Sin materiales todavía.</p>

    <div class="d-flex gap-2 align-items-start">
        <input type="file" id="materialInput" class="form-control form-control-sm" multiple style="flex: 1 1 60%;">
        <select id="materialSubtaskSelect" class="form-select form-select-sm" style="flex: 1 1 40%;">
            <option value="">(sin subtarea)</option>
            <?php foreach ($subtasks as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= esc($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Enlaces (URLs de referencia para la tarea, con texto libre opcional) -->
<div class="jt-section mb-3">
    <div class="jt-section-title">Enlaces</div>

    <div class="jt-materiales-list mb-2" id="linksList">
        <?php foreach ($links as $l): ?>
            <div class="jt-material-item" data-id="<?= (int)$l['id'] ?>" data-titulo="<?= esc($l['titulo'] ?? '', 'attr') ?>" data-descripcion="<?= esc($l['descripcion'] ?? '', 'attr') ?>" data-subtask-id="<?= (int)($l['subtask_id'] ?? 0) ?>">
                <div class="jt-material-row">
                    <a href="<?= esc($l['url'], 'attr') ?>" target="_blank" rel="noopener" class="jt-material-link">
                        <i class="bi bi-link-45deg"></i>
                        <span class="jt-material-name"><?= esc($l['titulo'] ?: $l['url']) ?></span>
                    </a>
                    <button type="button" class="jt-subtask-edit js-edit-link" title="Editar enlace" aria-label="Editar enlace">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="jt-subtask-delete js-delete-link" title="Eliminar enlace" aria-label="Eliminar enlace">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <?php if (!empty($l['descripcion'])): ?>
                    <div class="jt-material-desc"><?= esc($l['descripcion']) ?></div>
                <?php endif; ?>
                <?php if (!empty($l['subtask_id']) && isset($subtaskTitleById[$l['subtask_id']])): ?>
                    <div class="jt-material-subtask"><i class="bi bi-list-check"></i> <?= esc($subtaskTitleById[$l['subtask_id']]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="text-muted small mb-2 <?= empty($links) ? '' : 'd-none' ?>" id="linksEmptyMsg">Sin enlaces todavía.</p>

    <div class="jt-link-add">
        <input type="url" id="linkUrlInput" class="form-control form-control-sm" placeholder="https://...">
        <input type="text" id="linkTituloInput" class="form-control form-control-sm" placeholder="Texto (opcional)" maxlength="255">
        <select id="linkSubtaskSelect" class="form-select form-select-sm">
            <option value="">(sin subtarea)</option>
            <?php foreach ($subtasks as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= esc($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" id="linkAddBtn" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i>
        </button>
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

<form action="<?= site_url('journal/delete/' . $task['id']) ?>" method="post" class="mb-4"
      onsubmit="return confirm('¿Seguro que quieres eliminar esta tarea?');">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-trash"></i> Eliminar tarea
    </button>
</form>

<!-- Historial de fechas -->
<div class="jt-section">
    <div class="jt-section-title">Historial de fechas</div>

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
.jt-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.jt-back:hover { color: var(--bs-emphasis-color); }

.jt-image {
    position: relative;
    display: inline-block;
}
.jt-image img {
    max-width: 140px;
    max-height: 140px;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    display: block;
}
.jt-image-remove {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: #dc3545;
    color: #fff;
}

.jt-title-row { display: flex; align-items: center; gap: 8px; }
.jt-star {
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: #adb5bd;
    font-size: 1.15rem;
    cursor: pointer;
}
.jt-star[aria-pressed="true"] {
    color: #ffc107;
    border-color: rgba(255,193,7,.4);
    background: rgba(255,193,7,.12);
}
.jt-title-input {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 1.05rem;
    font-weight: 700;
    padding: .55rem .8rem;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
}
.jt-title-input:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 .2rem rgba(124,58,237,.2);
}

.jt-section {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 14px;
    padding: 12px 14px;
}
.jt-section-title {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--bs-secondary-color);
    font-weight: 700;
    margin-bottom: 8px;
}
.jt-label {
    font-size: .74rem;
    color: var(--bs-secondary-color);
    margin-bottom: 2px;
}

.jt-progress {
    height: 8px;
    border-radius: 999px;
    background: rgba(124,58,237,.12);
    overflow: hidden;
}
.jt-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
    transition: width .2s ease;
}
.jt-hint {
    margin-top: 4px;
    font-size: .74rem;
    color: var(--bs-secondary-color);
}

.jt-subtask-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.jt-subtask-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 6px 8px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    transition: opacity .15s ease, background-color .15s ease;
}
.jt-subtask-row {
    display: flex;
    align-items: center;
    gap: 6px;
}
.jt-subtask-item.sortable-ghost { opacity: .3; }
.jt-subtask-item.sortable-chosen { background: var(--bs-tertiary-bg); }
.jt-subtask-item.is-done { opacity: .6; }

.jt-subtask-attach-toggle {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: transparent;
    color: var(--bs-secondary-color);
    font-size: .75rem;
    cursor: pointer;
}
.jt-subtask-attach-toggle:hover { background: var(--bs-tertiary-bg); }
.jt-subtask-attachments {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-left: 30px;
}
.jt-subtask-attachment-link {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .8rem;
    text-decoration: none;
    color: inherit;
    min-width: 0;
}
.jt-subtask-attachment-link span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.jt-subtask-attachment-link:hover { text-decoration: underline; }

.jt-subtask-handle {
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    width: 24px;
    height: 32px;
    color: var(--bs-secondary-color);
    cursor: grab;
    touch-action: none;
}
.jt-subtask-handle:active { cursor: grabbing; }

.jt-subtask-check {
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    font-size: 1.05rem;
    cursor: pointer;
}
.jt-subtask-item.is-done .jt-subtask-check { color: #10b981; }

.jt-subtask-title {
    flex: 1 1 auto;
    min-width: 0;
    font-size: .9rem;
    color: var(--bs-emphasis-color);
    word-break: break-word;
}
.jt-subtask-item.is-done .jt-subtask-title {
    text-decoration: line-through;
    color: var(--bs-secondary-color);
}

.jt-subtask-edit,
.jt-subtask-delete {
    flex: 0 0 auto;
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    cursor: pointer;
}
.jt-subtask-edit:hover { background: rgba(13,110,253,.12); color: #0d6efd; }
.jt-subtask-delete:hover { background: rgba(220,53,69,.12); color: #dc3545; }

.jt-subtask-add { display: flex; gap: 6px; }
.jt-subtask-add .form-control { flex: 1 1 auto; }

.jt-materiales-list { display: flex; flex-direction: column; gap: 6px; }
.jt-material-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 6px 8px;
    border-radius: 8px;
    background: var(--bs-tertiary-bg);
}
.jt-material-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.jt-material-desc {
    font-size: .75rem;
    color: var(--bs-secondary-color);
    padding-left: 26px;
    word-break: break-word;
}
.jt-material-subtask {
    font-size: .75rem;
    color: var(--bs-primary);
    padding-left: 26px;
}
.jt-material-link {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1 1 auto;
    min-width: 0;
    text-decoration: none;
    color: inherit;
}
.jt-material-link:hover { text-decoration: underline; }
.jt-material-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.jt-material-size { flex: 0 0 auto; font-size: .75rem; color: var(--bs-secondary-color); }

.jt-link-add { display: flex; flex-wrap: wrap; gap: 6px; }
.jt-link-add #linkUrlInput { flex: 1 1 100%; }
.jt-link-add #linkTituloInput { flex: 1 1 auto; }
.jt-link-add #linkSubtaskSelect { flex: 1 1 auto; max-width: 160px; }

.jt-log-list { display: flex; flex-direction: column; gap: 6px; }
.jt-log-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    color: var(--bs-emphasis-color);
    text-align: left;
    cursor: pointer;
}
.jt-log-item:hover { background: var(--bs-tertiary-bg); }
.jt-log-date { font-weight: 600; font-size: .85rem; }
.jt-log-minutes { font-size: .8rem; color: var(--bs-secondary-color); margin-left: auto; }
.jt-log-edit-icon { color: var(--bs-secondary-color); font-size: .8rem; }

@media (max-width: 400px) {
    .jt-title-input { font-size: 1rem; }
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

    const subtaskEditModal = new bootstrap.Modal(document.getElementById('subtaskEditModal'));
    const subtaskEditInput = document.getElementById('subtaskEditInput');
    const subtaskEditSaveBtn = document.getElementById('subtaskEditSaveBtn');
    let subtaskEditItem = null;

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
                <button type="button" class="jt-subtask-edit js-edit-subtask" title="Renombrar subtarea" aria-label="Renombrar subtarea"><i class="bi bi-pencil"></i></button>
                <button type="button" class="jt-subtask-delete js-delete-subtask" title="Eliminar subtarea" aria-label="Eliminar subtarea"><i class="bi bi-trash"></i></button>
            </div>
        `;
        item.querySelector('.jt-subtask-title').textContent = subtask.title;
        return item;
    }

    async function addSubtask() {
        const title = subtaskInput.value.trim();
        if (!title) return;

        subtaskAddBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskList.dataset.taskId + '/crear', { title });
            if (!data.success) throw new Error();

            subtaskList.appendChild(buildSubtaskItem(data.subtask));
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
            for (const title of seleccionadas) {
                const data = await postJSON('<?= site_url('journal/subtasks') ?>/' + subtaskList.dataset.taskId + '/crear', { title });
                if (data.success) {
                    subtaskList.appendChild(buildSubtaskItem(data.subtask));
                    lastProgress = data.progress;
                }
            }
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
        onEnd: () => {
            const orden = [...subtaskList.querySelectorAll('.jt-subtask-item')].map(item => item.dataset.id);
            postJSON('<?= site_url('journal/subtasks/reordenar') ?>', { orden });
        },
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
        div.dataset.nombre = f.nombre_original;
        div.dataset.descripcion = f.descripcion || '';
        div.dataset.subtaskId = f.subtask_id || '';
        div.innerHTML = `
            <div class="jt-material-row">
                <a href="${f.url}" target="_blank" class="jt-material-link">
                    <i class="bi ${materialIcon(f.nombre_original)}"></i>
                    <span class="jt-material-name"></span>
                </a>
                <span class="jt-material-size">${formatSize(f.tamano)}</span>
                <button type="button" class="jt-subtask-edit js-edit-material" title="Editar material" aria-label="Editar material"><i class="bi bi-pencil"></i></button>
                <button type="button" class="jt-subtask-delete js-delete-material" title="Eliminar material" aria-label="Eliminar material"><i class="bi bi-trash"></i></button>
            </div>
        `;
        div.querySelector('.jt-material-name').textContent = f.nombre_original;
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

    materialInput.addEventListener('change', async () => {
        if (!materialInput.files.length) return;

        const formData = new FormData();
        for (const file of materialInput.files) {
            formData.append('archivo', file);
        }
        if (materialSubtaskSelect.value) {
            formData.append('subtask_id', materialSubtaskSelect.value);
        }

        materialInput.disabled = true;
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

            data.files.forEach(f => materialesList.appendChild(buildMaterialItem(f)));
            materialesEmptyMsg.classList.add('d-none');
        } catch (err) {
            alert(err.message || 'No se pudieron subir los archivos.');
        } finally {
            materialInput.value = '';
            materialInput.disabled = false;
        }
    });

    const materialEditModal = new bootstrap.Modal(document.getElementById('materialEditModal'));
    const materialEditNombre = document.getElementById('materialEditNombre');
    const materialEditDescripcion = document.getElementById('materialEditDescripcion');
    const materialEditSubtask = document.getElementById('materialEditSubtask');
    const materialEditSaveBtn = document.getElementById('materialEditSaveBtn');
    let materialEditItem = null;

    materialesList.addEventListener('click', async (e) => {
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
        if (!confirm('¿Eliminar este material?')) return;

        const item = delBtn.closest('.jt-material-item');
        const data = await postJSON('<?= site_url('journal/materiales') ?>/' + item.dataset.id + '/borrar');
        if (!data.success) return;

        item.remove();
        if (!materialesList.querySelector('.jt-material-item')) {
            materialesEmptyMsg.classList.remove('d-none');
        }
    });

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
        div.dataset.titulo = l.titulo || '';
        div.dataset.descripcion = l.descripcion || '';
        div.dataset.subtaskId = l.subtask_id || '';
        div.innerHTML = `
            <div class="jt-material-row">
                <a href="${l.url}" target="_blank" rel="noopener" class="jt-material-link">
                    <i class="bi bi-link-45deg"></i>
                    <span class="jt-material-name"></span>
                </a>
                <button type="button" class="jt-subtask-edit js-edit-link" title="Editar enlace" aria-label="Editar enlace"><i class="bi bi-pencil"></i></button>
                <button type="button" class="jt-subtask-delete js-delete-link" title="Eliminar enlace" aria-label="Eliminar enlace"><i class="bi bi-trash"></i></button>
            </div>
        `;
        div.querySelector('.jt-material-name').textContent = l.titulo || l.url;
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
    const linkEditTitulo = document.getElementById('linkEditTitulo');
    const linkEditDescripcion = document.getElementById('linkEditDescripcion');
    const linkEditSubtask = document.getElementById('linkEditSubtask');
    const linkEditSaveBtn = document.getElementById('linkEditSaveBtn');
    let linkEditItem = null;

    linksList.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.js-edit-link');
        if (editBtn) {
            linkEditItem = editBtn.closest('.jt-material-item');
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

        linkEditSaveBtn.disabled = true;
        try {
            const data = await postJSON('<?= site_url('journal/enlaces') ?>/' + linkEditItem.dataset.id + '/editar', {
                titulo: linkEditTitulo.value.trim(),
                descripcion: linkEditDescripcion.value.trim(),
                subtask_id: linkEditSubtask.value,
            });
            if (!data.success) throw new Error(data.error || 'No se pudo editar el enlace.');

            linkEditItem.dataset.titulo = data.link.titulo || '';
            linkEditItem.querySelector('.jt-material-name').textContent = data.link.titulo || data.link.url;
            setLinkDesc(linkEditItem, data.link.descripcion);
            setLinkSubtask(linkEditItem, data.link.subtask_id, data.link.subtask_title);
            linkEditModal.hide();
        } catch (err) {
            alert(err.message || 'No se pudo editar el enlace.');
        } finally {
            linkEditSaveBtn.disabled = false;
        }
    });
});
</script>

<?= $this->endSection() ?>
