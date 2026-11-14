<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/journal.css') ?>?v=8">
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
                            $prog = $subs ? $sd . '/' . count($subs) : ($amp === 100 ? (int) $t['completed'] . '%' : ($amp > 0 ? (int) $t['completed'] . '/' . $amp : ''));
                            $done = $hecha($t);
                            ?>
                            <div class="jn-t <?= $done ? 'hecha' : '' ?> <?= !empty($t['is_current']) ? 'on' : '' ?>" data-id="<?= (int) $t['id'] ?>">
                                <button type="button" class="jn-star js-star" title="Estrella"><i class="bi bi-star-fill"></i></button>
                                <a class="nm" href="<?= site_url('journal/edit/' . $t['id']) ?>"><?= esc($t['title']) ?></a>
                                <span class="p"><?= $prog ?></span>
                                <button type="button" class="hh js-tiempo" title="Añadir tiempo"><?= $horas($t['time_spent'] ?? 0) ?></button>
                                <button type="button" class="jn-ck js-completar" title="<?= $done ? 'Ver resumen / reabrir' : 'Marcar como terminada' ?>"
                                        data-title="<?= esc($t['title'], 'attr') ?>"
                                        data-start="<?= $fecha($t['start_time'] ?? null) ?>"
                                        data-end="<?= $fecha($t['end_time'] ?? null) ?>"
                                        data-time="<?= (int) ($t['time_spent'] ?? 0) ?>"
                                        data-note="<?= esc($t['note'] ?? '', 'attr') ?>"
                                        data-done="<?= $done ? '1' : '0' ?>"><i class="bi <?= $done ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button>
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

    lista.addEventListener('click', async e => {
        const h = e.target.closest('.jn-h');
        if (h) {
            const id = h.closest('.jn-cat').dataset.cat;
            abierta = abierta === id ? null : id;
            guarda('jnAbierta', abierta || '');
            cromo();
            return;
        }
        const fila = e.target.closest('.jn-t');
        if (!fila) return;
        const id = fila.dataset.id;

        if (e.target.closest('.js-star')) {
            const data = await post(URL + '/toggle-current/' + id);
            fila.classList.toggle('on', !!data.is_current);
            aplicarResumen(data.category_summary);
            vacios();
        } else if (e.target.closest('.js-tiempo')) {
            tareaId = id;
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
                    <button type="button" class="hh js-tiempo" title="Añadir tiempo">0 h</button>
                    <button type="button" class="jn-ck js-completar" title="Marcar como terminada" data-title="${esc(d.task.title)}" data-start="" data-end="" data-time="0" data-note="" data-done="0"><i class="bi bi-circle"></i></button>
                </div>`);
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
