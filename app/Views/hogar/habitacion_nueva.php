<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — checklist de una habitación (estilo fv): marcar, renovar,
 * reordenar arrastrando y añadir tareas.
 *
 * @var array $habitacion
 * @var array $tareas con tiempo_relativo y atrasada
 */
$hid    = (int) $habitacion['id'];
$hechas = count(array_filter($tareas, static fn ($t) => (int) $t['estado'] === 1));
$total  = count($tareas);

ob_start(); ?>
<button type="button" class="fv-btn" id="hgRenovarTodo"<?= $hechas ? '' : ' hidden' ?>><i class="bi bi-arrow-clockwise"></i>Renovar todo</button>
<a class="fv-ibtn" href="<?= site_url('hogar/habitaciones/editar/' . $hid) ?>" title="Editar habitación"><i class="bi bi-pencil"></i></a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rc estrecho">
    <?= view('hogar/_cabecera', [
        'migas'    => [$habitacion['nombre'] => null],
        'titulo'   => '<i class="bi bi-' . esc($habitacion['icono'] ?: 'house') . '" style="font-size:.8em;color:var(--fv-text-3)"></i> ' . esc($habitacion['nombre']),
        'sub'      => '<span id="hgCuenta">' . $hechas . ' de ' . $total . ' hechas</span>',
        'acciones' => $acciones,
    ]) ?>

    <?php if ($tareas): ?>
        <div class="hg-tareas" id="hgTareas">
            <?php foreach ($tareas as $t): ?>
                <?php $hecha = (int) $t['estado'] === 1; $f = (int) $t['frecuencia_dias']; ?>
                <div class="hg-t<?= $hecha ? ' hecha' : '' ?><?= $t['atrasada'] ? ' atrasada' : '' ?>" data-id="<?= (int) $t['id'] ?>">
                    <i class="bi bi-grip-vertical hg-asa" title="Arrastra para reordenar"></i>
                    <button type="button" class="hg-check" <?= $hecha ? 'disabled' : '' ?> aria-label="Marcar como hecha"><i class="bi <?= $hecha ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i></button>
                    <div class="rc-txt">
                        <span class="t"><?= esc($t['nombre']) ?></span>
                        <div class="rc-meta">
                            <span class="hg-tiempo"><i class="bi bi-clock"></i><?= esc($t['tiempo_relativo']) ?></span>
                            <?php if ($f): ?><span><i class="bi bi-arrow-clockwise"></i>cada <?= $f ?> <?= $f === 1 ? 'día' : 'días' ?></span><?php endif; ?>
                            <span class="e-warn hg-toca"<?= $t['atrasada'] ? '' : ' hidden' ?>><i class="bi bi-exclamation-circle"></i>Toca hacerla</span>
                        </div>
                    </div>
                    <div class="rc-btns">
                        <button type="button" class="fv-ibtn hg-renovar" title="Renovar (volver a pendiente)"<?= $hecha ? '' : ' hidden' ?>><i class="bi bi-arrow-clockwise"></i></button>
                        <a class="fv-ibtn" href="<?= site_url('hogar/tareas/' . $t['id'] . '/historial') ?>" title="Historial"><i class="bi bi-clock-history"></i></a>
                        <a class="fv-ibtn" href="<?= site_url('hogar/tareas/editar/' . $t['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="fv-panel rc-vacio">Todavía no hay tareas en esta habitación. Añade la primera abajo.</div>
    <?php endif; ?>

    <form class="hg-nueva" method="post" action="<?= site_url('hogar/tareas/crear') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="habitacion_id" value="<?= $hid ?>">
        <input type="text" class="fv-input" name="nombre" placeholder="Nueva tarea…" required maxlength="150">
        <input type="number" class="fv-input" name="frecuencia_dias" min="1" inputmode="numeric" placeholder="cada X días">
        <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i>Añadir</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const H = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' };
    const post = (url, body) => fetch(url, { method: 'POST', headers: H, body: body ? JSON.stringify(body) : null });
    const lista = document.getElementById('hgTareas');
    const todo = document.getElementById('hgRenovarTodo');

    function contar() {
        const filas = lista ? lista.querySelectorAll('.hg-t') : [];
        const hechas = lista ? lista.querySelectorAll('.hg-t.hecha').length : 0;
        document.getElementById('hgCuenta').textContent = hechas + ' de ' + filas.length + ' hechas';
        todo.hidden = !hechas;
    }
    function aplicar(fila, d) {
        const hecha = d.estado === 1;
        fila.classList.toggle('hecha', hecha);
        fila.classList.toggle('atrasada', !!d.atrasada);
        const c = fila.querySelector('.hg-check');
        c.disabled = hecha;
        c.querySelector('i').className = 'bi ' + (hecha ? 'bi-check-circle-fill' : 'bi-circle');
        fila.querySelector('.hg-tiempo').lastChild.textContent = d.tiempo_relativo;
        fila.querySelector('.hg-toca').hidden = !d.atrasada;
        fila.querySelector('.hg-renovar').hidden = !hecha;
        contar();
    }

    lista?.addEventListener('click', async ev => {
        const b = ev.target.closest('.hg-check, .hg-renovar');
        if (!b || b.disabled) return;
        const fila = b.closest('.hg-t');
        b.disabled = true;
        const r = await post('<?= site_url('hogar/tareas') ?>/' + fila.dataset.id + (b.classList.contains('hg-check') ? '/marcar' : '/renovar'));
        b.disabled = false;
        if (r.ok) { if (navigator.vibrate) navigator.vibrate(15); aplicar(fila, await r.json()); }
    });

    todo.addEventListener('click', async () => {
        if (!confirm('¿Renovar todas las tareas marcadas de esta habitación?')) return;
        const r = await post('<?= site_url('hogar/' . $hid . '/renovar-todo') ?>');
        if (!r.ok) return;
        const d = await r.json();
        lista.querySelectorAll('.hg-t.hecha').forEach(f => aplicar(f, { estado: 0, tiempo_relativo: d.tiempo_relativo, atrasada: false }));
    });

    if (lista) Sortable.create(lista, {
        handle: '.hg-asa', animation: 150, ghostClass: 'hg-fantasma',
        onEnd: () => post('<?= site_url('hogar/tareas/reordenar') ?>', { orden: [...lista.querySelectorAll('.hg-t')].map(f => f.dataset.id) }),
    });
})();
</script>
<?= $this->endSection() ?>
