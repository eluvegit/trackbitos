<input type="hidden" id="dashboardCsrfToken" value="<?= csrf_hash() ?>">
<div id="tareasFijadasList">
    <?php foreach ($tareasFijadas as $f): ?>
        <div class="tb-fila" data-task-id="<?= (int) $f['task_id'] ?>">
            <i class="bi bi-pin-angle"></i>
            <a class="txt" href="<?= site_url('journal/edit/' . (int) $f['task_id']) ?>#subtaskList"><?= esc($f['tarea']['title']) ?><?php if (!empty($f['tarea']['category'])): ?> <span class="sub">· <?= esc($f['tarea']['category']) ?></span><?php endif; ?></a>
            <button type="button" class="x" data-desfijar title="Quitar de fijadas"><i class="bi bi-x-lg"></i></button>
        </div>
    <?php endforeach; ?>
    <?php if (empty($tareasFijadas)): ?>
        <div class="tb-vacio" id="tareasFijadasVacio">Ninguna tarea fijada.</div>
    <?php endif; ?>
</div>
<div class="tb-fijar">
    <input type="text" id="buscarTareaFijar" autocomplete="off" placeholder="Fijar una tarea…">
    <div class="tb-fijar-res" id="resultadosTareaFijar"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var token = document.getElementById('dashboardCsrfToken');
    var lista = document.getElementById('tareasFijadasList');
    var caja = document.getElementById('buscarTareaFijar');
    var res = document.getElementById('resultadosTareaFijar');
    var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;'); };
    var post = function (url, fd) {
        return fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token.value }, body: fd || null }).then(function (r) { return r.json(); });
    };
    var espera = null;
    caja.addEventListener('input', function () {
        clearTimeout(espera);
        var q = caja.value.trim();
        if (q.length < 2) { res.style.display = 'none'; return; }
        espera = setTimeout(function () {
            fetch('<?= site_url('dashboard/tarea-buscar') ?>?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var t = d.resultados || [];
                    res.innerHTML = t.length ? t.map(function (x) {
                        return '<button type="button" data-elegir-tarea data-id="' + x.id + '"' + (x.fijada ? ' disabled' : '') + '>' + esc(x.texto) + (x.fijada ? ' (ya fijada)' : '') + '</button>';
                    }).join('') : '<div class="tb-vacio" style="padding:6px 8px">Sin resultados</div>';
                    res.style.display = 'block';
                });
        }, 250);
    });
    caja.addEventListener('focusout', function () { setTimeout(function () { res.style.display = 'none'; }, 200); });
    res.addEventListener('click', function (e) {
        var b = e.target.closest('[data-elegir-tarea]');
        if (!b || b.disabled) return;
        var fd = new FormData();
        fd.append('task_id', b.getAttribute('data-id'));
        post('<?= site_url('dashboard/tarea-fijada') ?>', fd).then(function (d) {
            if (!d.ok) { alert(d.mensaje || 'No se pudo fijar.'); return; }
            var v = document.getElementById('tareasFijadasVacio'); if (v) v.remove();
            var t = d.tarea;
            lista.insertAdjacentHTML('beforeend', '<div class="tb-fila" data-task-id="' + t.id + '"><i class="bi bi-pin-angle"></i><a class="txt" href="<?= site_url('journal/edit/') ?>' + t.id + '#subtaskList">' + esc(t.title) + (t.category ? ' <span class="sub">· ' + esc(t.category) + '</span>' : '') + '</a><button type="button" class="x" data-desfijar title="Quitar de fijadas"><i class="bi bi-x-lg"></i></button></div>');
            caja.value = ''; res.style.display = 'none';
        }).catch(function () { alert('No se pudo conectar con el servidor.'); });
    });
    lista.addEventListener('click', function (e) {
        var b = e.target.closest('[data-desfijar]');
        if (!b) return;
        var fila = b.closest('[data-task-id]');
        if (!confirm('¿Quitar esta tarea de fijadas?')) return;
        post('<?= site_url('dashboard/tarea-fijada') ?>/' + fila.dataset.taskId + '/quitar').then(function (d) {
            if (!d.ok) { alert('No se pudo quitar.'); return; }
            fila.remove();
            if (!lista.querySelector('[data-task-id]')) lista.insertAdjacentHTML('beforeend', '<div class="tb-vacio" id="tareasFijadasVacio">Ninguna tarea fijada.</div>');
        }).catch(function () { alert('No se pudo conectar con el servidor.'); });
    });
});
</script>
