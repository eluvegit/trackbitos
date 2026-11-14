<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/journal.css') ?>?v=9">
<?php
$horas = function ($min) {
    $h = (int) $min / 60;
    if ($h <= 0) return '0 h';
    return ($h >= 10 ? (string) round($h) : str_replace('.', ',', (string) round($h, 1))) . ' h';
};
$hoy = new DateTime('today');
$dias = function ($fecha) use ($hoy) {
    if (!$fecha) return null;
    return $hoy->diff(new DateTime(date('Y-m-d', strtotime($fecha))))->days;
};
$hecha = fn ($t) => !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00';
$fecha = fn ($v) => !empty($v) && $v !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($v)) : '';
$orden = function ($a, $b) use ($hecha) {
    if ($hecha($a) !== $hecha($b)) return $hecha($a) ? 1 : -1;
    if (!empty($a['is_current']) !== !empty($b['is_current'])) return !empty($a['is_current']) ? -1 : 1;
    $pa = (int) ($a['amplitude'] ?? 0) > 0 ? (int) $a['completed'] / (int) $a['amplitude'] : 0;
    $pb = (int) ($b['amplitude'] ?? 0) > 0 ? (int) $b['completed'] / (int) $b['amplitude'] : 0;
    if ($pa != $pb) return $pa > $pb ? -1 : 1;
    return (int) ($b['time_spent'] ?? 0) <=> (int) ($a['time_spent'] ?? 0);
};
foreach ($tasksByCategory as &$ts) {
    usort($ts, $orden);
}
unset($ts);
?>
<div class="fv jn solo-estrella" id="jn">
    <div class="jn-top">
        <h1>Journal</h1>
        <a class="jn-ib" href="<?= site_url('journal/que-hacer') ?>" title="¿Qué hago ahora?"><i class="bi bi-shuffle"></i></a>
        <a class="jn-ib" href="<?= site_url('journal/focalizar') ?>" title="Focalizar"><i class="bi bi-bullseye"></i></a>
        <button type="button" class="jn-ib js-f" data-f="solo-estrella" title="Solo con estrella"><i class="bi bi-star-fill"></i></button>
        <button type="button" class="jn-ib js-f" data-f="sin-hechas" title="Ocultar hechas"><i class="bi bi-eye-slash"></i></button>
        <button type="button" class="jn-ib" id="jnVista" title="Cambiar a columnas"><i class="bi bi-grid"></i></button>
        <a class="jn-ib" href="<?= site_url('journal') ?>?nueva=0" title="Vista clásica"><i class="bi bi-arrow-left-right"></i></a>
    </div>

    <div class="jn-lista" id="jnLista">
        <?php foreach ($categories as $i => $cat): ?>
            <?php
            $n = $cat['name'];
            $p = $progressByCategory[$n];
            $d = $dias($lastCategoryActivity[$n] ?? null);
            $dc = $d === null || $d > 180 ? 'viejo' : ($d <= 30 ? 'reciente' : '');
            $fila = intdiv($i, 2) * 3;
            $tot = max(1, $p['total']);
            $vivas = max(0, min($p['current'], $p['total'] - $p['completed']));
            ?>
            <div class="jn-cat" data-cat="<?= (int) $cat['id'] ?>">
                <button type="button" class="jn-h" style="--o:<?= $fila + $i % 2 ?>">
                    <span class="l-a">
                        <span class="jn-dot" style="background:<?= esc($cat['color'] ?? '#666', 'attr') ?>"></span>
                        <span class="nm"><?= esc($n) ?></span>
                        <span class="st js-cur"><?php if ($p['current']): ?><i class="bi bi-star-fill"></i><?= $p['current'] ?><?php endif; ?></span>
                    </span>
                    <span class="l-b">
                        <span class="ct js-ct"><?= $p['completed'] ?>/<?= $p['total'] ?></span>
                        <span class="dd <?= $dc ?>" title="Días desde la última vez"><?= $d === null ? '–' : $d . 'd' ?></span>
                        <span class="hh js-hh"><?= $horas($totalTimeByCategory[$n] ?? 0) ?></span>
                    </span>
                    <span class="ln"><i class="js-ln-ok" style="width:<?= $p['completed'] / $tot * 100 ?>%"></i><i class="js-ln-cur" style="width:<?= $vivas / $tot * 100 ?>%"></i></span>
                </button>
                <div class="jn-b" style="--o:<?= $fila + 2 ?>">
                    <div class="jn-tareas">
                        <?php foreach ($tasksByCategory[$n] ?? [] as $t): ?>
                            <?php
                            $subs = $subtasksByTask[$t['id']] ?? [];
                            $sd = count(array_filter($subs, fn ($s) => !empty($s['is_done'])));
                            $amp = (int) ($t['amplitude'] ?? 0);
                            $prog = $subs ? '' : ($amp === 100 ? (int) $t['completed'] . '%' : ($amp > 0 ? (int) $t['completed'] . '/' . $amp : ''));
                            $done = $hecha($t);
                            ?>
                            <div class="jn-t <?= $done ? 'hecha' : '' ?> <?= !empty($t['is_current']) ? 'on' : '' ?>" data-id="<?= (int) $t['id'] ?>">
                                <button type="button" class="jn-star js-star" title="Estrella"><i class="bi bi-star-fill"></i></button>
                                <a class="nm" href="<?= site_url('journal/edit/' . $t['id']) ?>"><?= esc($t['title']) ?></a>
                                <span class="p"><?= $prog ?></span>
                                <button type="button" class="jn-sb js-subs <?= $subs ? 'con' : '' ?>" title="Subtareas"><i class="bi bi-list-check"></i><span class="js-sc"><?= $subs ? $sd . '/' . count($subs) : '' ?></span></button>
                                <button type="button" class="hh js-tiempo" title="Añadir tiempo"><?= $horas($t['time_spent'] ?? 0) ?></button>
                                <button type="button" class="jn-ck js-completar" title="<?= $done ? 'Ver resumen / reabrir' : 'Marcar como terminada' ?>"
                                        data-title="<?= esc($t['title'], 'attr') ?>"
                                        data-start="<?= $fecha($t['start_time'] ?? null) ?>"
                                        data-end="<?= $fecha($t['end_time'] ?? null) ?>"
                                        data-time="<?= (int) ($t['time_spent'] ?? 0) ?>"
                                        data-note="<?= esc($t['note'] ?? '', 'attr') ?>"
                                        data-done="<?= $done ? '1' : '0' ?>"><i class="bi <?= $done ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button>
                            </div>
                            <div class="jn-subs" data-task="<?= (int) $t['id'] ?>">
                                <div class="jn-s-top">
                                    <label class="jn-s-add"><i class="bi bi-plus-lg"></i><input type="text" class="js-s-nueva" placeholder="Nueva subtarea" maxlength="255" enterkeyhint="done"></label>
                                    <button type="button" class="jn-s-ib js-s-caps" title="Generar capítulos / partes"><i class="bi bi-collection-play"></i></button>
                                    <button type="button" class="jn-s-ib js-s-ia" title="Sugerir subtareas con IA"><i class="bi bi-stars"></i></button>
                                    <button type="button" class="jn-s-ib js-s-ocultar" title="Ocultar/mostrar subtareas hechas"><i class="bi bi-eye-slash"></i></button>
                                    <button type="button" class="jn-s-ib js-s-empujar" title="Empujar las hechas al final"><i class="bi bi-arrow-down-square"></i></button>
                                </div>
                                <div class="jn-s-lista">
                                    <?php foreach ($subs as $s): ?>
                                        <?php $sOk = !empty($s['is_done']); ?>
                                        <div class="jn-s <?= $sOk ? 'hecha' : '' ?>" data-id="<?= (int) $s['id'] ?>">
                                            <span class="jn-s-asa" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                                            <button type="button" class="jn-s-ck js-s-ck" aria-label="Marcar como hecha"><i class="bi <?= $sOk ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button>
                                            <span class="nm"><?= esc($s['title']) ?></span>
                                            <button type="button" class="hh js-s-tiempo" title="Añadir tiempo"><?= $horas($s['time_spent'] ?? 0) ?></button>
                                            <div class="dropdown">
                                                <button type="button" class="jn-s-ib" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones"><i class="bi bi-three-dots"></i></button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><button type="button" class="dropdown-item js-s-renombrar"><i class="bi bi-pencil"></i> Renombrar</button></li>
                                                    <li><button type="button" class="dropdown-item js-s-arriba"><i class="bi bi-arrow-bar-up"></i> Mover al principio</button></li>
                                                    <li><button type="button" class="dropdown-item js-s-abajo"><i class="bi bi-arrow-bar-down"></i> Mover al final</button></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><button type="button" class="dropdown-item text-danger js-s-borrar"><i class="bi bi-trash"></i> Eliminar</button></li>
                                                </ul>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="jn-s-vacio">Sin subtareas todavía.</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="jn-vacio">Nada con este filtro.</p>
                    <label class="jn-nueva"><i class="bi bi-plus-lg"></i><input type="text" class="js-nueva" placeholder="Nueva tarea en <?= esc($n, 'attr') ?>" enterkeyhint="done"></label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="taskCompleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tcTitle">Completar tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="tcBadge" class="alert alert-success py-1 px-2 small d-none">
                    <i class="bi bi-check-circle-fill"></i> Esta tarea ya está marcada como terminada.
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" for="tcStart">Inicio</label>
                        <input type="date" id="tcStart" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" for="tcEnd">Fin</label>
                        <input type="date" id="tcEnd" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1" for="tcTime">Tiempo invertido (minutos)</label>
                    <input type="number" id="tcTime" class="form-control form-control-sm" min="0">
                    <div class="small text-muted mt-1" id="tcTimeHint">= 0.00 h</div>
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1" for="tcNote">Nota</label>
                    <textarea id="tcNote" class="form-control form-control-sm" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-sm me-auto d-none" id="tcReopenBtn">Reabrir tarea</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="tcSaveBtn">Marcar como terminada</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="timeModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeModalTitle">Añadir tiempo / Fecha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="number" id="timeMinutes" class="form-control mb-2" placeholder="Minutos" min="1">
                <input type="date" id="taskDate" class="form-control mb-2" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="saveTimeBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

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

<div class="modal fade" id="subtaskCapsModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-collection-play"></i> Generar capítulos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small text-muted mb-1" for="capsNombre">Nombre</label>
                <input type="text" id="capsNombre" class="form-control form-control-sm mb-2" maxlength="240" placeholder="Capítulo">
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" for="capsCantidad">Cuántos</label>
                        <input type="number" id="capsCantidad" class="form-control form-control-sm" min="1" max="500" inputmode="numeric">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" for="capsDesde">Empezar en</label>
                        <input type="number" id="capsDesde" class="form-control form-control-sm" min="0" inputmode="numeric">
                    </div>
                </div>
                <div class="small text-muted" id="capsVista"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="capsCrearBtn" disabled>Crear</button>
            </div>
        </div>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const URL = '<?= site_url('journal') ?>';
    const HOY = '<?= date('Y-m-d') ?>';
    const jn = document.getElementById('jn');
    const lista = document.getElementById('jnLista');
    const lee = k => { try { return localStorage.getItem(k); } catch (e) { return null; } };
    const guarda = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };
    const esc = s => String(s).replace(/[&<>"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
    const horas = min => { const h = (parseInt(min, 10) || 0) / 60; return h <= 0 ? '0 h' : (h >= 10 ? Math.round(h) : String(Math.round(h * 10) / 10).replace('.', ',')) + ' h'; };
    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: body ? JSON.stringify(body) : null,
    }).then(r => r.json());

    const filtros = { 'solo-estrella': lee('jnEstrella') !== '0', 'sin-hechas': lee('jnHechas') === '1' };
    let vista = lee('jnVista') === 'col' ? 'col' : 'lista';
    let abierta = lee('jnAbierta');

    function vacios() {
        lista.querySelectorAll('.jn-cat').forEach(c => {
            const visibles = [...c.querySelectorAll('.jn-t')].some(t => getComputedStyle(t).display !== 'none');
            c.classList.toggle('vacia', !visibles);
        });
    }
    function cromo() {
        Object.entries(filtros).forEach(([k, v]) => {
            jn.classList.toggle(k, v);
            jn.querySelector(`.js-f[data-f="${k}"]`).classList.toggle('on', v);
        });
        jn.classList.toggle('col', vista === 'col');
        const b = document.getElementById('jnVista');
        b.innerHTML = vista === 'col' ? '<i class="bi bi-list"></i>' : '<i class="bi bi-grid"></i>';
        b.title = vista === 'col' ? 'Cambiar a lista' : 'Cambiar a columnas';
        lista.querySelectorAll('.jn-cat').forEach(c => c.classList.toggle('abierta', c.dataset.cat === abierta));
        vacios();
    }
    cromo();

    jn.querySelectorAll('.js-f').forEach(b => b.addEventListener('click', () => {
        const k = b.dataset.f;
        filtros[k] = !filtros[k];
        guarda(k === 'solo-estrella' ? 'jnEstrella' : 'jnHechas', filtros[k] ? '1' : '0');
        cromo();
    }));
    document.getElementById('jnVista').addEventListener('click', () => {
        vista = vista === 'col' ? 'lista' : 'col';
        guarda('jnVista', vista);
        cromo();
    });

    function aplicarResumen(s) {
        if (!s) return;
        const c = lista.querySelector(`.jn-cat[data-cat="${s.cat_id}"]`);
        if (!c) return;
        const tot = Math.max(1, s.total), vivas = Math.max(0, Math.min(s.current, s.total - s.completed));
        c.querySelector('.js-ct').textContent = s.completed + '/' + s.total;
        c.querySelector('.js-cur').innerHTML = s.current ? '<i class="bi bi-star-fill"></i>' + s.current : '';
        c.querySelector('.js-hh').textContent = horas(Math.round(parseFloat(s.totalHours) * 60));
        c.querySelector('.js-ln-ok').style.width = (s.completed / tot * 100) + '%';
        c.querySelector('.js-ln-cur').style.width = (vivas / tot * 100) + '%';
    }

    const timeModal = new bootstrap.Modal(document.getElementById('timeModal'));
    const tcModal = new bootstrap.Modal(document.getElementById('taskCompleteModal'));
    let tareaId = null;
    let subId = null;

    lista.addEventListener('click', async e => {
        const h = e.target.closest('.jn-h');
        if (h) {
            const id = h.closest('.jn-cat').dataset.cat;
            abierta = abierta === id ? null : id;
            guarda('jnAbierta', abierta || '');
            cromo();
            return;
        }
        if (await clickSubtarea(e)) return;
        const fila = e.target.closest('.jn-t');
        if (!fila) return;
        const id = fila.dataset.id;

        if (e.target.closest('.js-subs')) {
            const panel = fila.nextElementSibling;
            const abre = !panel.classList.contains('abierta');
            panel.classList.toggle('abierta', abre);
            fila.classList.toggle('subs-abiertas', abre);
            if (abre && !panel.querySelector('.jn-s')) panel.querySelector('.js-s-nueva').focus();
        } else if (e.target.closest('.js-star')) {
            const data = await post(URL + '/toggle-current/' + id);
            fila.classList.toggle('on', !!data.is_current);
            aplicarResumen(data.category_summary);
            vacios();
        } else if (e.target.closest('.js-tiempo')) {
            tareaId = id;
            subId = null;
            document.getElementById('timeModalTitle').textContent = 'Añadir tiempo / Fecha';
            document.getElementById('taskDate').classList.remove('d-none');
            document.getElementById('timeMinutes').value = '';
            document.getElementById('taskDate').value = HOY;
            timeModal.show();
        } else if (e.target.closest('.js-completar')) {
            const b = e.target.closest('.js-completar');
            const hecha = b.dataset.done === '1';
            tareaId = id;
            document.getElementById('tcTitle').textContent = (hecha ? 'Resumen: ' : 'Completar: ') + b.dataset.title;
            document.getElementById('tcStart').value = b.dataset.start || '';
            document.getElementById('tcEnd').value = b.dataset.end || (hecha ? '' : HOY);
            document.getElementById('tcTime').value = b.dataset.time || 0;
            document.getElementById('tcNote').value = b.dataset.note || '';
            pista();
            document.getElementById('tcBadge').classList.toggle('d-none', !hecha);
            document.getElementById('tcReopenBtn').classList.toggle('d-none', !hecha);
            document.getElementById('tcSaveBtn').textContent = hecha ? 'Guardar cambios' : 'Marcar como terminada';
            tcModal.show();
        }
    });

    document.getElementById('timeModal').addEventListener('shown.bs.modal', () => document.getElementById('timeMinutes').focus());
    document.getElementById('timeMinutes').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('saveTimeBtn').click(); });
    document.getElementById('saveTimeBtn').addEventListener('click', async function () {
        if (this.disabled || !tareaId) return;
        this.disabled = true;
        try {
            const minutes = parseInt(document.getElementById('timeMinutes').value, 10) || 0;
            if (subId) {
                // Tiempo a la subtarea (el servidor también lo suma a la tarea padre)
                if (minutes <= 0) throw new Error('Indica los minutos');
                const d = await post(URL + '/subtasks/' + subId + '/add-time', { minutes });
                if (!d.success) throw new Error('Error guardando tiempo');
                lista.querySelector(`.jn-s[data-id="${subId}"] .js-s-tiempo`).textContent = horas(d.subtask_minutes);
                const fila = lista.querySelector(`.jn-t[data-id="${d.task_id}"]`);
                if (fila) {
                    fila.querySelector('.js-tiempo').textContent = horas(d.task_minutes);
                    fila.querySelector('.js-completar').dataset.time = d.task_minutes;
                }
                aplicarResumen(d.category_summary);
                timeModal.hide();
                return;
            }
            const date = document.getElementById('taskDate').value || '';
            if (minutes > 0) {
                const d = await post(URL + '/add-time/' + tareaId, { minutes });
                if (!d.success) throw new Error('Error guardando tiempo');
                lista.querySelector(`.jn-t[data-id="${tareaId}"] .js-tiempo`).textContent = horas(d.minutes);
                lista.querySelector(`.jn-t[data-id="${tareaId}"] .js-completar`).dataset.time = d.minutes;
                aplicarResumen(d.category_summary);
            }
            if (date) {
                const d = await post(URL + '/add-log/' + tareaId, { date, minutes });
                if (!d.success) throw new Error(d.error || 'Error guardando la fecha');
            }
            timeModal.hide();
        } catch (err) {
            alert(err.message || 'Error al guardar');
        } finally {
            this.disabled = false;
        }
    });

    const tcTime = document.getElementById('tcTime');
    const pista = () => document.getElementById('tcTimeHint').textContent = '= ' + ((parseInt(tcTime.value, 10) || 0) / 60).toFixed(2) + ' h';
    tcTime.addEventListener('input', pista);
    async function completar(reabrir) {
        if (!tareaId) return;
        try {
            const d = await post(URL + '/tasks/' + tareaId + '/completar', {
                start_time: document.getElementById('tcStart').value,
                end_time: document.getElementById('tcEnd').value,
                time_spent: parseInt(tcTime.value, 10) || 0,
                note: document.getElementById('tcNote').value,
                reopen: reabrir ? 1 : 0,
            });
            if (!d.success) throw new Error();
            const fila = lista.querySelector(`.jn-t[data-id="${tareaId}"]`);
            const b = fila.querySelector('.js-completar');
            const f = v => v && v !== '0000-00-00 00:00:00' ? v.substring(0, 10) : '';
            Object.assign(b.dataset, { done: d.is_done ? '1' : '0', start: f(d.start_time), end: f(d.end_time), time: d.time_spent, note: d.note || '' });
            b.title = d.is_done ? 'Ver resumen / reabrir' : 'Marcar como terminada';
            b.innerHTML = `<i class="bi ${d.is_done ? 'bi-check-circle-fill' : 'bi-circle'}"></i>`;
            fila.classList.toggle('hecha', !!d.is_done);
            fila.classList.toggle('on', !!d.is_current);
            fila.querySelector('.js-tiempo').textContent = horas(d.time_spent);
            aplicarResumen(d.category_summary);
            vacios();
            tcModal.hide();
        } catch (err) {
            alert('No se pudo guardar la tarea.');
        }
    }
    document.getElementById('tcSaveBtn').addEventListener('click', () => completar(false));
    document.getElementById('tcReopenBtn').addEventListener('click', () => {
        if (confirm('¿Reabrir esta tarea?')) completar(true);
    });

    // ---------------------------------------------------------------
    // Subtareas (mismo comportamiento que la vista clásica): añadir,
    // marcar, tiempo, renombrar, mover, borrar, arrastrar, IA y filtros
    // visuales de hechas (ocultar / empujar al final, guardados en
    // localStorage con las mismas claves que la clásica).
    // ---------------------------------------------------------------
    const SUB = URL + '/subtasks/';
    const prefsSub = { ocultar: lee('journalSubtaskHideDone') === '1', empujar: lee('journalSubtaskPushDone') === '1' };

    function filtrosSub() {
        lista.querySelectorAll('.jn-subs').forEach(p => {
            p.classList.toggle('ocultar', prefsSub.ocultar);
            p.classList.toggle('empujar', prefsSub.empujar);
            p.querySelector('.js-s-ocultar').classList.toggle('on', prefsSub.ocultar);
            p.querySelector('.js-s-empujar').classList.toggle('on', prefsSub.empujar);
        });
    }

    function itemSub(s) {
        const d = document.createElement('div');
        d.className = 'jn-s';
        d.dataset.id = s.id;
        d.innerHTML = `
            <span class="jn-s-asa" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
            <button type="button" class="jn-s-ck js-s-ck" aria-label="Marcar como hecha"><i class="bi bi-circle"></i></button>
            <span class="nm">${esc(s.title)}</span>
            <button type="button" class="hh js-s-tiempo" title="Añadir tiempo">${horas(s.time_spent || 0)}</button>
            <div class="dropdown">
                <button type="button" class="jn-s-ib" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones"><i class="bi bi-three-dots"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button type="button" class="dropdown-item js-s-renombrar"><i class="bi bi-pencil"></i> Renombrar</button></li>
                    <li><button type="button" class="dropdown-item js-s-arriba"><i class="bi bi-arrow-bar-up"></i> Mover al principio</button></li>
                    <li><button type="button" class="dropdown-item js-s-abajo"><i class="bi bi-arrow-bar-down"></i> Mover al final</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item text-danger js-s-borrar"><i class="bi bi-trash"></i> Eliminar</button></li>
                </ul>
            </div>`;
        return d;
    }

    // Contador "hechas/total" en el botón de la fila; con subtareas, la
    // columna de progreso por amplitud deja de tener sentido y se vacía.
    function contarSub(panel) {
        const fila = panel.previousElementSibling;
        const total = panel.querySelectorAll('.jn-s').length;
        const hechas = panel.querySelectorAll('.jn-s.hecha').length;
        fila.querySelector('.js-sc').textContent = total ? hechas + '/' + total : '';
        fila.querySelector('.js-subs').classList.toggle('con', total > 0);
        if (total) fila.querySelector('.p').textContent = '';
        panel.classList.toggle('sin', total === 0);
    }

    const guardarOrden = panel => post(SUB + 'reordenar', { orden: [...panel.querySelectorAll('.jn-s')].map(s => s.dataset.id) });

    function iniciarSubpaneles() {
        lista.querySelectorAll('.jn-subs:not([data-ini])').forEach(panel => {
            panel.dataset.ini = '1';
            contarSub(panel);
            Sortable.create(panel.querySelector('.jn-s-lista'), {
                handle: '.jn-s-asa',
                animation: 150,
                ghostClass: 'fantasma',
                onEnd: () => guardarOrden(panel),
            });
        });
        filtrosSub();
    }

    async function clickSubtarea(e) {
        const panel = e.target.closest('.jn-subs');
        if (!panel) return false;
        const taskId = panel.dataset.task;

        if (e.target.closest('.js-s-ocultar') || e.target.closest('.js-s-empujar')) {
            const k = e.target.closest('.js-s-ocultar') ? 'ocultar' : 'empujar';
            prefsSub[k] = !prefsSub[k];
            guarda(k === 'ocultar' ? 'journalSubtaskHideDone' : 'journalSubtaskPushDone', prefsSub[k] ? '1' : '0');
            filtrosSub();
            return true;
        }
        if (e.target.closest('.js-s-caps')) {
            abrirCapitulos(panel);
            return true;
        }
        if (e.target.closest('.js-s-ia')) {
            abrirSugerencias(panel);
            return true;
        }
        const item = e.target.closest('.jn-s');
        if (!item) return true;
        const sid = item.dataset.id;
        const caja = panel.querySelector('.jn-s-lista');

        if (e.target.closest('.js-s-ck')) {
            const d = await post(SUB + sid + '/toggle');
            if (!d.success) return true;
            item.classList.toggle('hecha', !!d.is_done);
            item.querySelector('.js-s-ck i').className = 'bi ' + (d.is_done ? 'bi-check-circle-fill' : 'bi-circle');
            contarSub(panel);
        } else if (e.target.closest('.js-s-tiempo')) {
            subId = sid;
            tareaId = taskId;
            document.getElementById('timeModalTitle').textContent = 'Añadir tiempo a subtarea';
            document.getElementById('taskDate').classList.add('d-none');
            document.getElementById('timeMinutes').value = '';
            timeModal.show();
        } else if (e.target.closest('.js-s-arriba')) {
            caja.prepend(item);
            guardarOrden(panel);
        } else if (e.target.closest('.js-s-abajo')) {
            caja.appendChild(item);
            guardarOrden(panel);
        } else if (e.target.closest('.js-s-renombrar')) {
            editando = item;
            document.getElementById('subtaskEditInput').value = item.querySelector('.nm').textContent;
            editModal.show();
        } else if (e.target.closest('.js-s-borrar')) {
            if (!confirm('¿Eliminar esta subtarea?')) return true;
            const d = await post(SUB + sid + '/borrar');
            if (!d.success) return true;
            item.remove();
            contarSub(panel);
        }
        return true;
    }

    lista.addEventListener('keydown', async e => {
        const inp = e.target.closest('.js-s-nueva');
        if (!inp || e.key !== 'Enter') return;
        e.preventDefault();
        const title = inp.value.trim();
        if (!title) return;
        const panel = inp.closest('.jn-subs');
        inp.disabled = true;
        try {
            const d = await post(SUB + panel.dataset.task + '/crear', { title });
            if (!d.success) throw new Error();
            panel.querySelector('.jn-s-lista').prepend(itemSub(d.subtask));
            inp.value = '';
            contarSub(panel);
        } catch (err) {
            alert('No se pudo añadir la subtarea.');
        } finally {
            inp.disabled = false;
            inp.focus();
        }
    });

    // Renombrar
    const editModal = new bootstrap.Modal(document.getElementById('subtaskEditModal'));
    const editInput = document.getElementById('subtaskEditInput');
    const editBtn = document.getElementById('subtaskEditSaveBtn');
    let editando = null;
    document.getElementById('subtaskEditModal').addEventListener('shown.bs.modal', () => editInput.select());
    editInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); editBtn.click(); } });
    editBtn.addEventListener('click', async () => {
        const title = editInput.value.trim();
        if (!editando || !title) return;
        editBtn.disabled = true;
        try {
            const d = await post(SUB + editando.dataset.id + '/editar', { title });
            if (!d.success) throw new Error();
            editando.querySelector('.nm').textContent = d.title;
            editModal.hide();
        } catch (err) {
            alert('No se pudo renombrar la subtarea.');
        } finally {
            editBtn.disabled = false;
        }
    });

    // Sugerir con IA
    const iaModal = new bootstrap.Modal(document.getElementById('subtaskSuggestModal'));
    const iaCtx = document.getElementById('subtaskSuggestContexto');
    const iaLoading = document.getElementById('subtaskSuggestLoading');
    const iaError = document.getElementById('subtaskSuggestError');
    const iaList = document.getElementById('subtaskSuggestList');
    const iaGen = document.getElementById('subtaskSuggestGenerateBtn');
    const iaAdd = document.getElementById('subtaskSuggestAddBtn');
    let iaPanel = null;

    function abrirSugerencias(panel) {
        iaPanel = panel;
        iaCtx.value = '';
        iaList.innerHTML = '';
        iaError.classList.add('d-none');
        iaLoading.classList.add('d-none');
        iaAdd.disabled = true;
        iaModal.show();
    }
    document.getElementById('subtaskSuggestModal').addEventListener('shown.bs.modal', () => iaCtx.focus());

    iaGen.addEventListener('click', async () => {
        if (!iaPanel) return;
        iaList.innerHTML = '';
        iaError.classList.add('d-none');
        iaLoading.classList.remove('d-none');
        iaAdd.disabled = true;
        iaGen.disabled = true;
        try {
            const d = await post(URL + '/tasks/' + iaPanel.dataset.task + '/sugerir-subtareas', { contexto: iaCtx.value.trim() });
            if (!d.success || !d.subtareas || !d.subtareas.length) throw new Error(d.error || 'Sin sugerencias');
            d.subtareas.forEach(t => {
                const l = document.createElement('label');
                l.className = 'd-flex align-items-center gap-2';
                l.innerHTML = `<input type="checkbox" class="form-check-input mt-0" checked><span>${esc(t)}</span>`;
                iaList.appendChild(l);
            });
            iaAdd.disabled = false;
        } catch (err) {
            iaError.textContent = err.message === 'Sin sugerencias'
                ? 'No se generaron sugerencias. Prueba de nuevo.'
                : (err.message || 'No se pudo contactar con la IA.');
            iaError.classList.remove('d-none');
        } finally {
            iaLoading.classList.add('d-none');
            iaGen.disabled = false;
        }
    });

    iaAdd.addEventListener('click', async () => {
        if (!iaPanel) return;
        const elegidas = [...iaList.querySelectorAll('input:checked')].map(c => c.nextElementSibling.textContent);
        if (!elegidas.length) return;
        const caja = iaPanel.querySelector('.jn-s-lista');
        const ancla = caja.firstChild;
        iaAdd.disabled = true;
        try {
            for (const title of elegidas) {
                const d = await post(SUB + iaPanel.dataset.task + '/crear', { title });
                if (d.success) caja.insertBefore(itemSub(d.subtask), ancla);
            }
            guardarOrden(iaPanel);
            contarSub(iaPanel);
            iaModal.hide();
        } catch (err) {
            alert('No se pudieron añadir todas las subtareas.');
        } finally {
            iaAdd.disabled = false;
        }
    });

    // Generador de capítulos: crea "Nombre N" … "Nombre N+cantidad-1" al
    // final de la lista. "Empezar en" se propone a partir del número más
    // alto que ya exista con ese mismo nombre, para poder ir añadiendo
    // temporadas/tandas sin repetir.
    const capsModal = new bootstrap.Modal(document.getElementById('subtaskCapsModal'));
    const capsNombre = document.getElementById('capsNombre');
    const capsCantidad = document.getElementById('capsCantidad');
    const capsDesde = document.getElementById('capsDesde');
    const capsVista = document.getElementById('capsVista');
    const capsBtn = document.getElementById('capsCrearBtn');
    let capsPanel = null;
    let capsDesdeTocado = false;

    const capsNom = () => capsNombre.value.trim() || 'Capítulo';
    function capsSiguiente() {
        const re = new RegExp('^' + capsNom().replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s+(\\d+)$', 'i');
        let max = 0;
        capsPanel.querySelectorAll('.jn-s .nm').forEach(n => {
            const m = n.textContent.trim().match(re);
            if (m) max = Math.max(max, parseInt(m[1], 10));
        });
        return max + 1;
    }
    function capsPintar() {
        if (!capsDesdeTocado) capsDesde.value = capsSiguiente();
        const n = parseInt(capsCantidad.value, 10) || 0;
        const d = parseInt(capsDesde.value, 10);
        const ok = n >= 1 && n <= 500 && !isNaN(d) && d >= 0;
        capsBtn.disabled = !ok;
        capsBtn.textContent = ok ? `Crear ${n}` : 'Crear';
        capsVista.textContent = ok ? (n === 1 ? `${capsNom()} ${d}` : `${capsNom()} ${d} … ${capsNom()} ${d + n - 1}`) : '';
    }
    function abrirCapitulos(panel) {
        capsPanel = panel;
        capsDesdeTocado = false;
        capsNombre.value = lee('journalCapsNombre') || 'Capítulo';
        capsCantidad.value = '';
        capsPintar();
        capsModal.show();
    }
    document.getElementById('subtaskCapsModal').addEventListener('shown.bs.modal', () => capsCantidad.focus());
    capsNombre.addEventListener('input', capsPintar);
    capsCantidad.addEventListener('input', capsPintar);
    capsDesde.addEventListener('input', () => { capsDesdeTocado = true; capsPintar(); });
    [capsNombre, capsCantidad, capsDesde].forEach(i => i.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); capsBtn.click(); }
    }));

    capsBtn.addEventListener('click', async () => {
        if (!capsPanel || capsBtn.disabled) return;
        const n = parseInt(capsCantidad.value, 10);
        const d = parseInt(capsDesde.value, 10);
        const nombre = capsNom();
        guarda('journalCapsNombre', nombre);
        const caja = capsPanel.querySelector('.jn-s-lista');
        capsBtn.disabled = true;
        let fallos = 0;
        for (let i = 0; i < n; i++) {
            capsBtn.textContent = `Creando ${i + 1}/${n}…`;
            try {
                const r = await post(SUB + capsPanel.dataset.task + '/crear', { title: `${nombre} ${d + i}` });
                if (r.success) caja.appendChild(itemSub(r.subtask)); else fallos++;
            } catch (err) {
                fallos++;
            }
        }
        await guardarOrden(capsPanel);
        contarSub(capsPanel);
        capsModal.hide();
        capsPintar();
        if (fallos) alert(`No se pudieron crear ${fallos} de ${n} capítulos.`);
    });

    iniciarSubpaneles();

    lista.addEventListener('keydown', async e => {
        const inp = e.target.closest('.js-nueva');
        if (!inp || e.key !== 'Enter') return;
        const title = inp.value.trim();
        if (!title) return;
        const cat = inp.closest('.jn-cat');
        inp.disabled = true;
        try {
            const d = await post(URL + '/create', { title, category_id: cat.dataset.cat });
            if (!d.success) throw new Error();
            cat.querySelector('.jn-tareas').insertAdjacentHTML('beforeend',
                `<div class="jn-t recien" data-id="${d.task.id}">
                    <button type="button" class="jn-star js-star" title="Estrella"><i class="bi bi-star-fill"></i></button>
                    <a class="nm" href="${URL}/edit/${d.task.id}">${esc(d.task.title)}</a>
                    <span class="p">1%</span>
                    <button type="button" class="jn-sb js-subs" title="Subtareas"><i class="bi bi-list-check"></i><span class="js-sc"></span></button>
                    <button type="button" class="hh js-tiempo" title="Añadir tiempo">0 h</button>
                    <button type="button" class="jn-ck js-completar" title="Marcar como terminada" data-title="${esc(d.task.title)}" data-start="" data-end="" data-time="0" data-note="" data-done="0"><i class="bi bi-circle"></i></button>
                </div>
                <div class="jn-subs" data-task="${d.task.id}">
                    <div class="jn-s-top">
                        <label class="jn-s-add"><i class="bi bi-plus-lg"></i><input type="text" class="js-s-nueva" placeholder="Nueva subtarea" maxlength="255" enterkeyhint="done"></label>
                        <button type="button" class="jn-s-ib js-s-caps" title="Generar capítulos / partes"><i class="bi bi-collection-play"></i></button>
                        <button type="button" class="jn-s-ib js-s-ia" title="Sugerir subtareas con IA"><i class="bi bi-stars"></i></button>
                        <button type="button" class="jn-s-ib js-s-ocultar" title="Ocultar/mostrar subtareas hechas"><i class="bi bi-eye-slash"></i></button>
                        <button type="button" class="jn-s-ib js-s-empujar" title="Empujar las hechas al final"><i class="bi bi-arrow-down-square"></i></button>
                    </div>
                    <div class="jn-s-lista"></div>
                    <p class="jn-s-vacio">Sin subtareas todavía.</p>
                </div>`);
            iniciarSubpaneles();
            aplicarResumen(d.category_summary);
            inp.value = '';
            vacios();
        } catch (err) {
            alert('No se pudo crear la tarea.');
        } finally {
            inp.disabled = false;
            inp.focus();
        }
    });
});
</script>
<?= view('journal/_reloj') ?>
<?= $this->endSection() ?>
